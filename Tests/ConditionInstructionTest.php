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
use Ikarus\SPS\Workflow\Instruction\Condition\AllOfCondition;
use Ikarus\SPS\Workflow\Instruction\Condition\AnyOfCondition;
use Ikarus\SPS\Workflow\Instruction\Condition\FalseCondition;
use Ikarus\SPS\Workflow\Instruction\Condition\NotCondition;
use Ikarus\SPS\Workflow\Instruction\Condition\TrueCondition;
use PHPUnit\Framework\TestCase;

class ConditionInstructionTest extends TestCase
{
	public function testTrueAndFalseConditions() {
		$inst = new TrueCondition();
		$mr = new InternalMemoryRegister();

		$this->assertTrue($inst->process($mr));

		$inst = new FalseCondition();
		$this->assertFalse($inst->process($mr));

		$inst = new NotCondition(new TrueCondition());
		$this->assertFalse($inst->process($mr));
	}

	public function testCompoundConditions() {
		$mr = new InternalMemoryRegister();

		$any = new AnyOfCondition(
			new FalseCondition(),
			new FalseCondition(),
			new FalseCondition(),
			new FalseCondition()
		);

		$this->assertFalse($any->process($mr));

		$any = new AnyOfCondition(
			new FalseCondition(),
			new FalseCondition(),
			new FalseCondition(),
			new FalseCondition(),
			new TrueCondition()
		);
		$this->assertTrue($any->process($mr));

		$all = new AllOfCondition(
			new TrueCondition(),
			new TrueCondition(),
			new TrueCondition(),
			new TrueCondition()
		);

		$this->assertTrue($all->process($mr));

		$all = new AllOfCondition(
			new TrueCondition(),
			new TrueCondition(),
			new FalseCondition(),
			new TrueCondition()
		);

		$this->assertFalse($all->process($mr));
	}
}
