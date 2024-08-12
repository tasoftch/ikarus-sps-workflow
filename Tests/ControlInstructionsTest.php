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

use Ikarus\SPS\Register\InternalMemoryRegister;
use Ikarus\SPS\Workflow\DirectWorkflow;
use Ikarus\SPS\Workflow\Exception\DuplicateInstructionException;
use Ikarus\SPS\Workflow\Exception\InstructionReferenceException;
use Ikarus\SPS\Workflow\Instruction\Control\InstructionBlock;
use Ikarus\SPS\Workflow\Instruction\Control\Jump;
use Ikarus\SPS\Workflow\Instruction\Control\Label;
use Ikarus\SPS\Workflow\Instruction\Debug\ConsoleLogInstruction;
use PHPUnit\Framework\TestCase;

class ControlInstructionsTest extends TestCase
{
	public function testControlInstructionRegistration() {
		$wf = new DirectWorkflow();

		$wf->appendInstruction(new ConsoleLogInstruction('Hello '));
		$wf->appendInstruction(new Jump("test"));
		$wf->appendInstruction(new ConsoleLogInstruction('world!'));
		$wf->appendInstruction(new Label('test'));

		$mr = new InternalMemoryRegister();

		$wf->enable();
		$wf->process($mr);

		$this->expectOutputString('Hello ');
	}

	public function testDuplicateLabel() {
		$wf = new DirectWorkflow();

		$this->expectException(DuplicateInstructionException::class);

		$wf->appendInstruction(new ConsoleLogInstruction('Hello '));
		$wf->appendInstruction(new Label("test"));
		$wf->appendInstruction(new ConsoleLogInstruction('world!'));
		$wf->appendInstruction(new Label('test'));
	}

	public function testInvalidJumpReference() {
		$wf = new DirectWorkflow();

		$wf->appendInstruction(new ConsoleLogInstruction('Hello '));
		$wf->appendInstruction(new Jump("test"));
		$wf->appendInstruction(new ConsoleLogInstruction('world!'));
		//$wf->appendInstruction(new LabelInstruction('test'));

		$this->expectException(InstructionReferenceException::class);

		$wf->enable();

		$mr = new InternalMemoryRegister();
		$wf->process($mr);
	}

	public function testInstructionBlock() {
		$wf = new DirectWorkflow();

		$wf->appendInstruction(new ConsoleLogInstruction('1'));
		$wf->appendInstruction(new ConsoleLogInstruction('2'));
		$wf->appendInstruction(
			new InstructionBlock(
				new ConsoleLogInstruction('3'),
				new ConsoleLogInstruction('4'),
				new ConsoleLogInstruction('5'),
			)
		);
		$wf->appendInstruction(new ConsoleLogInstruction('6'));

		$wf->enable();

		$mr = new InternalMemoryRegister();
		$wf->process($mr);

		$this->expectOutputString('123456');
	}
}
