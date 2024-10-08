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

namespace Ikarus\SPS\Workflow;

use Ikarus\SPS\Register\MemoryRegisterInterface;
use Ikarus\SPS\Workflow\Instruction\InstructionInterface;

interface WorkflowInterface
{
	/**
	 * Makes the workflow being active
	 *
	 * @return void
	 */
	public function enable();

	/**
	 * Disables the workflow
	 *
	 * @return void
	 */
	public function disable();

	/**
	 * Returning true will cause a process() call to handle the instructions.
	 *
	 * @return bool
	 */
	public function hasPendingInstructions(): bool;

	/**
	 * Processes all pending instructions
	 *
	 * @param MemoryRegisterInterface $register
	 * @return void
	 */
	public function process(MemoryRegisterInterface $register);

	/**
	 * OM, OFF or ERROR status
	 *
	 * @see MemoryRegisterInterface
	 * @return int
	 */
	public function getStatus(): int;

	/**
	 * @return int
	 */
	public function getInstructionsCount(): int;

	/**
	 * @return int
	 */
	public function getCurrentInstructionNumber(): int;

	/**
	 * @return string|null
	 */
	public function getCurrentInstructionName(): ?string;
}