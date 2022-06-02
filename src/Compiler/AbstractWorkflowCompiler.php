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


use Ikarus\SPS\Workflow\Compiler\Design\Parser\WorkflowDesignParserInterface;
use Ikarus\SPS\Workflow\Compiler\Design\StepDesignInterface;
use Ikarus\SPS\Workflow\Compiler\Design\WorkflowDesignInterface;
use Ikarus\SPS\Workflow\Compiler\Provider\StepComponentProviderInterface;
use Ikarus\SPS\Workflow\Compiler\Provider\WorkflowProviderInterface;
use Ikarus\SPS\Workflow\Exception\ComponentNotFoundException;
use Ikarus\SPS\Workflow\Exception\IllegalWorkflowDesignException;
use Ikarus\SPS\Workflow\Model\StepComponentInterface;

abstract class AbstractWorkflowCompiler implements WorkflowCompilerInterface
{
	/** @var StepComponentProviderInterface */
	private $stepComponentProvider;
	/** @var WorkflowDesignParserInterface */
	private $designParser;

	protected $usedComponents = [];

	/**
	 * @return StepComponentProviderInterface
	 */
	public function getStepComponentProvider(): StepComponentProviderInterface
	{
		return $this->stepComponentProvider;
	}

	/**
	 * @param StepComponentProviderInterface $stepComponentProvider
	 * @return AbstractWorkflowCompiler
	 */
	public function setStepComponentProvider(StepComponentProviderInterface $stepComponentProvider): AbstractWorkflowCompiler
	{
		$this->stepComponentProvider = $stepComponentProvider;
		return $this;
	}

	/**
	 * @return WorkflowDesignParserInterface
	 */
	public function getDesignParser(): WorkflowDesignParserInterface
	{
		return $this->designParser;
	}

	/**
	 * @param WorkflowDesignParserInterface $designParser
	 * @return AbstractWorkflowCompiler
	 */
	public function setDesignParser(WorkflowDesignParserInterface $designParser): AbstractWorkflowCompiler
	{
		$this->designParser = $designParser;
		return $this;
	}

	/**
	 * @param StepComponentInterface $component
	 * @param StepDesignInterface $forStep
	 */
	protected function inspectStepComponent(StepComponentInterface $component, StepDesignInterface $forStep) {
	}

	/**
	 * @param WorkflowProviderInterface $provider
	 * @return array
	 */
	protected function prepareFromProvider(WorkflowProviderInterface $provider): array
	{
		$components = [];
		$getComponent = function($name) use (&$components) {
			if(!isset($components[$name])) {
				$c = $this->getStepComponentProvider()->getStepComponent($name);
				if(!$c)
					throw (new ComponentNotFoundException("Component $name not found"))->setComponentName($name);
				$components[$name] = $c;
			}
			return $components[$name];
		};

		$workflows = [];
		foreach($provider->yieldWorkflow($name, $design, $options) as $r) {
			if(! $design instanceof WorkflowDesignInterface)
				$design = $this->getDesignParser()->parseDesign($design, $name, $options);
			if(! $design instanceof WorkflowDesignInterface)
				throw (new IllegalWorkflowDesignException("Illegal workflow design"))->setWorkflowDesign($design);

			$steps = [];
			foreach($design->getSteps() as $step) {
				$cmp = $getComponent( $step->getComponentName() );
				$this->inspectStepComponent($cmp, $step);

				$steps[] = $step;
			}
			$workflows[$name] = [$options, $steps];
		}

		$this->usedComponents = $components;
		return $workflows;
	}
}