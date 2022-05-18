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

namespace Context;

use Ikarus\SPS\Workflow\Context\Timer;
use PHPUnit\Framework\TestCase;

class TimerTest extends TestCase
{
	public function testNullTimer() {
		$tm = new Timer();

		$this->assertTrue($tm->isTimeUp());
	}

	public function testMicroTimer() {
		$tm = new Timer(100, Timer::TIMER_UNIT_MICRO_SECONDS);
		$this->assertFalse($tm->isTimeUp());
		usleep(100);
		$this->assertTrue($tm->isTimeUp());
	}

	public function testMillitimer() {
		$tm = new Timer(100, Timer::TIMER_UNIT_MILLI_SECONDS);
		$this->assertFalse($tm->isTimeUp());
		$this->assertFalse($tm->isTimeUp());
		$this->assertFalse($tm->isTimeUp());
		$this->assertFalse($tm->isTimeUp());
		$this->assertFalse($tm->isTimeUp());
		$this->assertFalse($tm->isTimeUp());
		$this->assertFalse($tm->isTimeUp());
		$this->assertFalse($tm->isTimeUp());
		usleep(100000);
		$this->assertTrue($tm->isTimeUp());
	}

	public function testSecondTimer() {
		$tm = new Timer(1);
		$ms = microtime(true);
		while ($tm->isTimeUp() == false) {
		}
		$this->assertEquals(1, round(microtime(true) - $ms, 4));
	}
}
