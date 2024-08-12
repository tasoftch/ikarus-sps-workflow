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

namespace Ikarus\SPS\Workflow\Instruction;

use Ikarus\SPS\Register\MemoryRegisterInterface;

interface InstructionInterface
{
	/** @var int Marks the instruction as completed and will continue with the next instruction in the next cycle */
	const PROCESS_RESULT_SUCCESS = 1;
	const PROCESS_RESULT_CONTINUE_IMMEDIATELY = 2;
	const PROCESS_RESULT_REPEAT = 3;

	const PROCESS_RESULT_FAILURE_AND_REPEAT = -1;
	const PROCESS_RESULT_FAILURE_AND_CONTINUE = -2;

	/**
	 * @param MemoryRegisterInterface $register
	 * @return int
	 * @see InstructionInterface::PROCESS_RESULT_* constants
	 */
	public function process(MemoryRegisterInterface $register): int;

	/**
	 * Gets the next instruction or null if the workflow has completed.
	 *
	 * @return InstructionInterface|null
	 */
	public function getNextInstruction(): ?InstructionInterface;
}