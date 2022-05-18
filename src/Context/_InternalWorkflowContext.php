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

namespace Ikarus\SPS\Workflow\Context;


use Ikarus\SPS\Register\MemoryRegisterInterface;
use Ikarus\SPS\Workflow\WorkflowManager;
use Ikarus\SPS\Workflow\WorkflowManagerInterface;
use TASoft\Util\ValueInjector;

class _InternalWorkflowContext implements WorkflowContextInterface
{
	private $data = [];
	private $manager;
	private $mr;
	/** @var StepData|null */
	private $stepData;

	/**
	 * _InternalWotkflowContext constructor.
	 * @param WorkflowManagerInterface $manager
	 */
	public function __construct(WorkflowManagerInterface $manager)
	{
		$this->manager = new ValueInjector( $manager, WorkflowManager::class );
	}

	public function getStepData(): ?StepData
	{
		return $this->stepData;
	}


	/**
	 * @inheritDoc
	 */
	public function setValue(string $key, $value)
	{
		if($key[0] == '@')
			$this->data["$key"] = $value;
		else
			$this->data["v$key"] = $value;
	}

	/**
	 * @inheritDoc
	 */
	public function getValue(string $key)
	{
		if($key[0] == '@')
			return $this->data[$key] ?? NULL;
		return $this->data["v$key"] ?? NULL;
	}

	/**
	 * @inheritDoc
	 */
	public function repeatStep()
	{
		$this->data["@NS"] = false;
	}

	/**
	 * @inheritDoc
	 */
	public function jumpToStep($step): bool
	{
		$step = $this->manager->_getStep($step);
		if($step) {
			$this->data["NS"] = $step;
			return true;
		}
		return false;
	}

	public function resetCustomValues() {
		$keys = array_filter(array_keys($this->data), function($k) {
			return $k[0] == '@';
		});
		foreach($keys as $key)
			unset($this->data[$key]);
	}

	/**
	 * @inheritDoc
	 */
	public function continueNextStepInCurrentCycle()
	{
		$this->data["@continue"] = 1;
	}

	public function getMemoryRegister(): ?MemoryRegisterInterface
	{
		return $this->mr;
	}

	/**
	 * @param MemoryRegisterInterface $mr
	 * @return _InternalWorkflowContext
	 */
	public function setMemoryRegister(MemoryRegisterInterface $mr): _InternalWorkflowContext
	{
		$this->mr = $mr;
		return $this;
	}

	/**
	 * @param StepData|null $stepData
	 * @return _InternalWorkflowContext
	 */
	public function setStepData(?StepData $stepData): _InternalWorkflowContext
	{
		$this->stepData = $stepData;
		return $this;
	}
}