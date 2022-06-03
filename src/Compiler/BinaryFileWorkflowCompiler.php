<?php
/*
 * BSD 3-Clause License
 *
 * Copyright (c) 2022, TASoft Applications
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 *  Redistributions of source code must retain the above copyright notice, this
 *   list of conditions and the following disclaimer.
 *
 *  Redistributions in binary form must reproduce the above copyright notice,
 *   this list of conditions and the following disclaimer in the documentation
 *   and/or other materials provided with the distribution.
 *
 *  Neither the name of the copyright holder nor the names of its
 *   contributors may be used to endorse or promote products derived from
 *   this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE
 * FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
 * DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
 * OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 */

namespace Ikarus\SPS\Workflow\Compiler;


use Ikarus\SPS\Workflow\Compiler\Design\StepDesignInterface;
use Ikarus\SPS\Workflow\Compiler\Design\WorkflowDesignInterface;
use Ikarus\SPS\Workflow\Compiler\Provider\WorkflowProviderInterface;
use Ikarus\SPS\Workflow\Context\WorkflowContextInterface;
use Ikarus\SPS\Workflow\Exception\CompilationException;
use Ikarus\SPS\Workflow\Model\StepComponentCompilableInterface;
use Ikarus\SPS\Workflow\Model\StepComponentCompilableResetInterface;
use Ikarus\SPS\Workflow\Model\StepComponentInterface;
use Ikarus\SPS\Workflow\Step\CallbackResetStep;
use Ikarus\SPS\Workflow\Step\CallbackStep;
use Ikarus\SPS\Workflow\WorkflowManager;

class BinaryFileWorkflowCompiler extends AbstractExternalWorkflowCompiler
{
	/** @var string */
	private $filename;

	protected $classImports = [
		WorkflowManager::class,
		WorkflowContextInterface::class
	];

	/**
	 * BinaryFileProcedureCompiler constructor.
	 * @param string $filename
	 */
	public function __construct(string $filename)
	{
		$this->filename = $filename;
	}

	protected function inspectStepComponent(StepComponentInterface $component, StepDesignInterface $forStep)
	{
		parent::inspectStepComponent($component, $forStep);
		if($component instanceof StepComponentCompilableResetInterface)
			$this->addClassImport(CallbackResetStep::class);
		else
			$this->addClassImport(CallbackStep::class);
		if($component instanceof StepComponentCompilableInterface) {
			foreach($component->getRequiredClasses() as $class => $alias) {
				if(is_numeric($class) && is_string($alias)) {
					$class = $alias;
					$alias = NULL;
				}

				$this->addRequiredClass($class, $alias);
			}
		}
	}

	/**
	 * @inheritDoc
	 */
	public function compile(WorkflowProviderInterface $provider)
	{
		$workflows = $this->prepareFromProvider($provider);

		$content = "<?php
/**
 * Compiled workflows by Ikarus SPS at " . date("d.m.Y G:i:s") . "
 */
" . $this->stringifyClassImports();

		$content.= <<< EOT

return new class() {
	private \$WFL = [];
	private \$ACTIVE = [];
	private \$SUSP = [];
	
	public function __construct() {
		\$FN = [
EOT;

		foreach($this->usedComponents as $component) {
			if(!$component instanceof StepComponentCompilableInterface)
				throw new CompilationException("Can not compile non-compilable step component %s", 0, NULL, $component->getComponentName());

			$data = $this->exportExternalCodeForComponent($component, 'getExecutable', true);

			if($component instanceof StepComponentCompilableResetInterface) {
				$reset = $this->exportExternalCodeForComponent($component, 'getResetExecutable', true);
				$code = "return (new CallbackResetStep(\$stepName,$data,\$step))->setResetCallback($reset)";
			} else {
				$code = "return new CallbackStep(\$stepName,$data,\$step)";
			}

			$content .= sprintf("\n\t\t\t'%s' => function(\$userData,\$stepName,\$step) { $code },", $component->getComponentName());
		}

		$content = rtrim($content, ',\n\t\r') . "\n\t\t];";

		/** @var WorkflowDesignInterface $workflow */

		foreach($workflows as $name => $workflow) {
			list($options, $steps) = $workflow;

			if($options & StepComponentInterface::OPTION_CIRCULAR_PROCESSING)
				$content .= "\n\t\t\$this->WFL['$name'] = \$WFL = (new WorkflowManager())->setLoopWorkflow(true);";
			else
				$content .= "\n\t\t\$this->WFL['$name'] = \$WFL = new WorkflowManager();";

			if($options & StepComponentInterface::OPTION_PROCESS_KICK_START)
				$content .= "\n\t\t\$this->ACTIVE[] = '$name';";

			/** @var StepDesignInterface $step */
			foreach($steps as $step) {
				$cn = $step->getComponentName();
				if($data = $step->getStepData()) {
					$content .= "\n\t\t\$d=unserialize(" . var_export( serialize( $data ), true) . ");";
				} else
					$content .= "\n\t\t\$d=NULL;";
				$content .= sprintf("\n\t\t\$WFL->addStep( \$FN['$cn'](\$d,'%s',%d), \$d);", $step->getName(), $step->getStep());
			}
		}
		$content .= "\n\t}\n";
		$content .= <<< 'EOT'

	public function __invoke($memoryRegister) {
		$active = $memoryRegister->fetchValue("WFL", 'ACTIVE');
		if(is_iterable($active)) {
			foreach($active as $a) {
				if(isset($this->WFL[$a]) && !in_array($a, $this->ACTIVE))
					$this->ACTIVE[] = $a;
			}
			$memoryRegister->putValue(0, "ACTIVE", "WFL");
		}

		$active = $memoryRegister->fetchValue("WFL", 'SUSPEND');
		if(is_iterable($active)) {
			foreach($active as $a) {
				if(in_array($a, $this->ACTIVE) && !in_array($a, $this->SUSP))
					$this->SUSP[] = $a;
			}
		}

		$active = $memoryRegister->fetchValue("WFL", 'RESUME');
		if(is_iterable($active)) {
			foreach($active as $a) {
				if(in_array($a, $this->ACTIVE) && (($idx = array_search($a, $this->SUSP)) !== false))
					unset($this->SUSP[$a]);
			}
		}

		$done = [];
		foreach($this->ACTIVE as $idx => $aw) {
			if(!in_array($aw, $this->SUSP)) {
				$this->WFL[$aw]->process($memoryRegister);
				if(!$this->WFL[$aw]->needsProcess()) {
					$done[] = $idx;
				}
			}
		}
		$this->ACTIVE = array_filter($this->ACTIVE, function ($v) use ($done) {return!in_array($v, $done);});
		$memoryRegister->putValue($this->ACTIVE, 'RUNNING', "WFL", false);
	}
EOT;
;

		$content .= "\n};";
		file_put_contents($this->filename, $content);
	}

	private function _parseClassName($className) {
		$cn = explode("\\", $className);
		return array_pop($cn);
	}

	protected function addRequiredClass(string $className, string $alias = NULL)
	{
		if(!$alias)
			$alias = $this->_parseClassName($className);

		$this->addClassImport($className, $alias);
	}
}