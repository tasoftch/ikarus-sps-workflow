<?php
/*
 * BSD 3-Clause License
 *
 * Copyright (c) 2024, TASoft Applications
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
 */

namespace Ikarus\SPS\Workflow\Instruction\Timing;

use Ikarus\SPS\Register\MemoryRegisterInterface;
use Ikarus\SPS\Tool\Timing\Timer;
use Ikarus\SPS\Workflow\Instruction\AlternatingInstructionInterface;
use Ikarus\SPS\Workflow\Instruction\Condition\ConditionInterface;
use Ikarus\SPS\Workflow\Instruction\InstructionInterface;
use Ikarus\SPS\Workflow\Instruction\Timing\AbstractTimingInstruction;

/**
 * Checks if a condition is true in a given time.
 * If the condition reaches true, the workflow will continue.
 * If the time is up, it continues with the alternative instruction.
 */
class TimedConditionInstruction extends AbstractTimingInstruction implements AlternatingInstructionInterface
{
	/** @var ConditionInterface */
	private $condition;

	/** @var InstructionInterface|null */
	private $alternativeInstruction;

	/** @var bool */
	private $result;

	/**
	 * @param ConditionInterface $condition
	 * @param InstructionInterface|null $alternativeInstruction
	 */
	public function __construct(Timer $timer,  ConditionInterface $condition, InstructionInterface $alternativeInstruction = NULL)
	{
		parent::__construct($timer);
		$this->condition = $condition;
		$this->alternativeInstruction = $alternativeInstruction;
	}

	public function getAlternativeInstruction(): ?InstructionInterface
	{
		return $this->alternativeInstruction;
	}

	public function setAlternativeInstruction(?InstructionInterface $alternativeInstruction): TimedConditionInstruction
	{
		$this->alternativeInstruction = $alternativeInstruction;
		return $this;
	}

	protected function waitInstruction(MemoryRegisterInterface $register): int
	{
		if($this->condition->process($register)) {
			$this->result = true;
			return self::PROCESS_RESULT_CONTINUE_IMMEDIATELY;
		}
		return self::PROCESS_RESULT_REPEAT;
	}

	/**
	 * @inheritDoc
	 */
	protected function timerCompletedInstruction(MemoryRegisterInterface $register): int
	{
		$this->result = false;
		return self::PROCESS_RESULT_CONTINUE_IMMEDIATELY;
	}

	public function getNextInstruction(): ?InstructionInterface
	{
		if($this->result)
			return parent::getNextInstruction();
		else
			return $this->getAlternativeInstruction();
	}
}