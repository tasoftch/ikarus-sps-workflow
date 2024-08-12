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

use Ikarus\SPS\Plugin\AbstractPlugin;
use Ikarus\SPS\Register\InternalMemoryRegister;
use Ikarus\SPS\Register\MemoryRegisterInterface;
use Ikarus\SPS\Tool\Timing\Timer;
use Ikarus\SPS\Workflow\DirectWorkflow;
use Ikarus\SPS\Workflow\Instruction\CallbackInstruction;
use Ikarus\SPS\Workflow\Instruction\Condition\BrickHasStatusCondition;
use Ikarus\SPS\Workflow\Instruction\Control\Jump;
use Ikarus\SPS\Workflow\Instruction\Control\Label;
use Ikarus\SPS\Workflow\Instruction\Debug\ConsoleLogInstruction;
use Ikarus\SPS\Workflow\Instruction\Timing\TimedConditionInstruction;
use Ikarus\SPS\Workflow\Instruction\Timing\WaitForTimer;
use PHPUnit\Framework\TestCase;

class TimingInstructionsTest extends TestCase
{
	public function testSimpleTimer() {
		$wf = new DirectWorkflow();

		$wf->appendInstruction( new ConsoleLogInstruction('1') );
		$wf->appendInstruction( new WaitForTimer(new Timer(100, Timer::TIMING_UNIT_MILLI_SECONDS)) );
		$wf->appendInstruction( new ConsoleLogInstruction('2') );

		$mr = new InternalMemoryRegister();

		$wf->enable();
		$wf->process($mr);

		$wf->process($mr);
		$wf->process($mr);
		$wf->process($mr);

		$this->expectOutputString('1');
		usleep(50000);

		$wf->process($mr);

		$this->expectOutputString('1');
		usleep(100000);
		$wf->process($mr);
		$wf->process($mr);
		$wf->process($mr);

		$this->expectOutputString('12');
		$this->assertFalse($wf->hasPendingInstructions());
	}

	public function testTimedConditionOnFalse() {
		$wf = new DirectWorkflow();
		$mr = new InternalMemoryRegister();

		$mr->setStatus(0, 'test', false);
		$mr->setStatus(1, 'test');

		$wf->appendInstruction( new ConsoleLogInstruction('1') );
		$wf->appendInstruction(
			new TimedConditionInstruction(
				new Timer(100, Timer::TIMING_UNIT_MILLI_SECONDS),
				new BrickHasStatusCondition('test', 2),
				new Jump('finish')
			)
		);
		$wf->appendInstruction( new ConsoleLogInstruction('2') );
		$wf->appendInstruction( new Label('finish') );

		$wf->enable();
		$wf->process($mr);

		$this->expectOutputString('1');
		usleep(50000);

		$wf->process($mr);
		$wf->process($mr);
		$wf->process($mr);
		$wf->process($mr);

		$this->expectOutputString('1');
		$this->assertTrue($wf->hasPendingInstructions());

		usleep(70000);

		$wf->process($mr);
		$wf->process($mr);
		$wf->process($mr);

		$this->expectOutputString('1');
		$this->assertFalse($wf->hasPendingInstructions());
	}

	public function testTimedConditionOnTrue() {
		$wf = new DirectWorkflow();
		$mr = new InternalMemoryRegister();

		$mr->setStatus(0, 'test', false);
		$mr->setStatus(1, 'test');

		$wf->appendInstruction( new ConsoleLogInstruction('1') );
		$wf->appendInstruction(
			new TimedConditionInstruction(
				new Timer(100, Timer::TIMING_UNIT_MILLI_SECONDS),
				new BrickHasStatusCondition('test', 2),
				new Jump('finish')
			)
		);
		$wf->appendInstruction( new ConsoleLogInstruction('2') );
		$wf->appendInstruction( new Label('finish') );

		$wf->enable();
		$wf->process($mr);

		$this->expectOutputString('1');
		usleep(50000);

		$mr->setStatus( MemoryRegisterInterface::STATUS_ON, 'test' );

		$wf->process($mr);
		$wf->process($mr);

		usleep(70000);

		$wf->process($mr);
		$wf->process($mr);
		$wf->process($mr);

		$this->expectOutputString('12');
		$this->assertFalse($wf->hasPendingInstructions());
	}

	public function testTimerReset() {
		$wf = new DirectWorkflow();
		$mr = new InternalMemoryRegister();

		$mr->setStatus(0, 'test', false);
		$mr->setStatus(1, 'test');

		$CHECKPOINT_1 = false;
		$CHECKPOINT_2 = false;
		$CHECKPOINT_3 = false;

		$wf->appendInstruction( new CallbackInstruction(function() use (&$CHECKPOINT_1) {
			$CHECKPOINT_1 = true;
		}) );

		$wf->appendInstruction(
			new TimedConditionInstruction(
				new Timer(10, Timer::TIMING_UNIT_MILLI_SECONDS),
				new BrickHasStatusCondition('test', 2),
				new Jump('finish')
			)
		);
		$wf->appendInstruction( new CallbackInstruction(function() use (&$CHECKPOINT_2) {
			$CHECKPOINT_2 = true;
		}) );
		$wf->appendInstruction( new Label('finish') );
		$wf->appendInstruction( new CallbackInstruction(function() use (&$CHECKPOINT_3) {
			$CHECKPOINT_3 = true;
		}) );

		$this->assertFalse( AbstractPlugin::isStatusOn($wf->getStatus()) );
		$wf->enable();
		$wf->process($mr);
		$this->assertTrue( AbstractPlugin::isStatusOn($wf->getStatus()) );

		$this->assertTrue($CHECKPOINT_1);
		$this->assertFalse($CHECKPOINT_2);
		$this->assertFalse($CHECKPOINT_3);

		usleep(5000);

		$wf->process($mr);

		$this->assertTrue($CHECKPOINT_1);
		$this->assertFalse($CHECKPOINT_2);
		$this->assertFalse($CHECKPOINT_3);
		$this->assertTrue($wf->hasPendingInstructions());

		usleep(7000);

		$wf->process($mr);

		$this->assertFalse($wf->hasPendingInstructions());

		$this->assertTrue($CHECKPOINT_1);
		$this->assertFalse($CHECKPOINT_2);
		$this->assertTrue($CHECKPOINT_3);

		$this->assertFalse( AbstractPlugin::isStatusOn($wf->getStatus()) );

		$CHECKPOINT_1 = $CHECKPOINT_2 = $CHECKPOINT_3 = false;

		$wf->enable();
		$wf->process($mr);

		$this->assertTrue($CHECKPOINT_1);
		$this->assertFalse($CHECKPOINT_2);
		$this->assertFalse($CHECKPOINT_3);

		usleep(5000);

		$wf->process($mr);
		$mr->setStatus(2, 'test');
		$wf->process($mr);

		$this->assertTrue($CHECKPOINT_1);
		$this->assertTrue($CHECKPOINT_2);
		$this->assertTrue($CHECKPOINT_3);
		$this->assertFalse( AbstractPlugin::isStatusOn($wf->getStatus()) );
		$this->assertFalse($wf->hasPendingInstructions());

		usleep(7000);

		$this->assertFalse( AbstractPlugin::isStatusOn($wf->getStatus()) );
		$this->assertFalse($wf->hasPendingInstructions());
	}
}
