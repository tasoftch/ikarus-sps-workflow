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

namespace Ikarus\SPS\Workflow\Instruction\Condition;

use Ikarus\SPS\Register\MemoryRegisterInterface;
use Ikarus\SPS\Workflow\Instruction\AbstractInstruction;
use Ikarus\SPS\Workflow\Instruction\AlternatingInstructionInterface;
use Ikarus\SPS\Workflow\Instruction\InstructionInterface;

class ConditionInstruction extends AbstractInstruction implements AlternatingInstructionInterface
{
	/** @var ConditionInterface */
	private $condition;

	/** @var InstructionInterface|null */
	private $alternativeInstruction;

	/** @var bool */
	private $conditionResult;

	/**
	 * @param ConditionInterface $condition
	 * @param InstructionInterface|null $alternativeInstruction
	 */
	public function __construct(ConditionInterface $condition, InstructionInterface $alternativeInstruction = NULL)
	{
		$this->condition = $condition;
		$this->alternativeInstruction = $alternativeInstruction;
	}

	/**
	 * @inheritDoc
	 */
	public function process(MemoryRegisterInterface $register): int
	{
		$this->conditionResult = $this->condition->process($register);
		return self::PROCESS_RESULT_CONTINUE_IMMEDIATELY;
	}

	public function getNextInstruction(): ?InstructionInterface
	{
		if($this->getConditionResult())
			return parent::getNextInstruction();
		else
			return $this->getAlternativeInstruction();
	}

	public function getAlternativeInstruction(): ?InstructionInterface
	{
		return $this->alternativeInstruction;
	}

	public function setAlternativeInstruction(?InstructionInterface $alternativeInstruction): ConditionInstruction
	{
		$this->alternativeInstruction = $alternativeInstruction;
		return $this;
	}

	public function getCondition(): ConditionInterface
	{
		return $this->condition;
	}

	public function getConditionResult(): bool
	{
		return $this->conditionResult;
	}
}