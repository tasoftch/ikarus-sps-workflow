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

namespace Ikarus\SPS\Workflow\Compiler\Provider;


use Generator;
use Ikarus\SPS\Workflow\Compiler\Design\WorkflowDesignInterface;
use Ikarus\SPS\Workflow\Exception\IllegalWorkflowDesignException;

class StaticWorkflowProvider implements WorkflowProviderInterface
{
	/** @var WorkflowDesignInterface|scalar[] */
	private $workflows = [];

	/**
	 * StaticWorkflowProvider constructor.
	 * @param WorkflowDesignInterface[] $workflows
	 */
	public function __construct(... $workflows)
	{
		$this->addWorkflows($workflows);
	}

	public function addWorkflows(array $workflows) {
		foreach($workflows as $workflow)
			$this->addWorkflow($workflow);
	}

	public function addWorkflow($workflow, string $name = NULL, int $options = NULL) {
		if(NULL === $name) {
			if(is_object($workflow) && method_exists($workflow, 'getname'))
				$name = $workflow->getName();
			elseif(is_array($workflow) && isset($workflow['name']))
				$name = $workflow['name'];
			else
				throw (new IllegalWorkflowDesignException("No name detected from design"))->setWorkflowDesign($workflow);
		}

		if(NULL === $options) {
			if(is_object($workflow) && method_exists($workflow, 'getoptions'))
				$options = $workflow->getOptions();
			elseif(is_array($workflow) && isset($workflow['options']))
				$options = $workflow['options'];
			else
				throw (new IllegalWorkflowDesignException("No options detected from design"))->setWorkflowDesign($workflow);
		}

		$this->workflows[] = [$workflow, $name, $options];
	}

	/**
	 * @inheritDoc
	 */
	public function yieldWorkflow(&$name, &$design, &$options)
	{
		foreach($this->workflows as $workflow) {
			list($design, $name, $options) = $workflow;
			yield $workflow;
		}

	}
}