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

use Ikarus\SPS\Workflow\Step\CallbackResetStep;
use Ikarus\SPS\Workflow\Step\CallbackStep;
use Ikarus\SPS\Workflow\Step\MarkerStep;
use Ikarus\SPS\Workflow\Step\RepeatWholeWorkflowStep;
use Ikarus\SPS\Workflow\WorkflowManager;
use PHPUnit\Framework\TestCase;

class WorkflowManagerTest extends TestCase
{
	public function testAddStep() {
		$wfl = new WorkflowManager();
		$this->assertFalse($wfl->needsProcess());

		$wfl->addStep(new MarkerStep('mk', 9));

		$this->assertTrue($wfl->needsProcess());
	}

	public function testRemoveStep() {
		$wfl = new WorkflowManager();
		$wfl->addStep(new MarkerStep('mk1', 9));
		$wfl->addStep(new MarkerStep('mk2', 8));
		$wfl->addStep($mk = new MarkerStep('mk3', 7));

		$this->assertCount(3, $wfl);

		$wfl->removeStep('mk1');
		$this->assertCount(2, $wfl);

		$wfl->removeStep(8);
		$this->assertCount(1, $wfl);
		$this->assertTrue($wfl->needsProcess());

		$wfl->removeStep($mk);
		$this->assertCount(0, $wfl);

		$this->assertFalse($wfl->needsProcess());
	}

	public function testFluentSteps() {
		$wfl = new WorkflowManager();

		$wfl->addStep(new MarkerStep('mk1', 9));
		$wfl->addStep(new MarkerStep('mk2', 8));
		$wfl->addStep($mk = new MarkerStep('mk3', 7));

		$this->assertTrue( $wfl->needsProcess() );
		$wfl->process();
		$this->assertFalse( $wfl->needsProcess() );
	}

	public function testDefaultSteps() {
		$wfl = new WorkflowManager();

		$A = $B = $C = false;

		$wfl->addStep(new CallbackStep('cb1', function() use (&$A) {
			$A = true;
		}));

		$wfl->addStep(new CallbackStep('cb1', function() use (&$B) {
			$B = true;
		}));

		$wfl->addStep(new CallbackStep('cb1', function() use (&$C) {
			$C = true;
		}));

		$wfl->process();
		$this->assertTrue($A);
		$this->assertFalse($B);
		$this->assertFalse($C);

		$wfl->process();
		$this->assertTrue($A);
		$this->assertTrue($B);
		$this->assertFalse($C);

		$wfl->process();
		$this->assertTrue($A);
		$this->assertTrue($B);
		$this->assertTrue($C);

		$this->assertFalse( $wfl->needsProcess() );

		// Nothing must happen!
		$wfl->process();
		$wfl->process();
		$wfl->process();
		$wfl->process();
		$wfl->process();

		$this->assertFalse( $wfl->needsProcess() );
	}

	public function testWorkflowLoopSteps() {
		$wfl = new WorkflowManager();
		$wfl->setLoopWorkflow(true);

		$A = $B = $C = false;

		$wfl->addStep(new CallbackStep('cb1', function() use (&$A) {
			$A = true;
		}));

		$wfl->addStep((new CallbackResetStep('cb1', function() use (&$B) {
			$B = true;
		}))
			->setResetCallback(function() use (&$C) {
				// Must not be reached.
				// In loop mode, no reset gets called.
				$C = true;
			})
		);

		$wfl->process();
		$wfl->process();
		$this->assertTrue($A);
		$this->assertTrue($B);

		$A = $B = false;
		$this->assertFalse($C);
		$wfl->process();
		$this->assertFalse($C);

		$wfl->process();
		$this->assertTrue($A);
		$this->assertTrue($B);
	}

	public function testRepeatWorkflow() {
		$wfl = new WorkflowManager();

		$A = $B = false;

		$wfl->addStep(new CallbackStep('cb1', function() use (&$A) {
			$A = true;
		}));

		$wfl->addStep((new CallbackResetStep('cb1', function() use (&$B) {
			$B = true;
		}))
			->setResetCallback(function() use (&$A, &$B) {
				$A = $B = false;
			})
		);
		$wfl->addStep(new RepeatWholeWorkflowStep('rp', 44));


		$wfl->process();
		$wfl->process();
		$this->assertTrue($A);
		$this->assertTrue($B);

		$wfl->process();
		$this->assertTrue($A);
		$this->assertFalse($B);

		$wfl->process();
		$this->assertTrue($A);
		$this->assertTrue($B);
	}
}
