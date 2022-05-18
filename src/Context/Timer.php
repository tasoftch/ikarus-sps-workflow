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


class Timer
{
	const TIMER_UNIT_MICRO_SECONDS = -2;
	const TIMER_UNIT_MILLI_SECONDS = -1;
	const TIMER_UNIT_SECONDS = 0;
	const TIMER_UNIT_MINUTES = 1;

	private $timeout = 0;
	private $timer = 0;

	/**
	 * Timer constructor.
	 * @param int $timeout
	 * @param int $unit
	 * @param bool $enable
	 */
	public function __construct(int $timeout = 0, int $unit = self::TIMER_UNIT_SECONDS, bool $enable = true)
	{
		if($timeout<=0)
			$this->timeout = 0;
		else {
			switch ($unit) {
				case static::TIMER_UNIT_MICRO_SECONDS:
					$timeout /= 1000000;
					break;
				case static::TIMER_UNIT_MILLI_SECONDS:
					$timeout /= 1000;
					break;
				case static::TIMER_UNIT_MINUTES:
					$timeout *= 60;
					break;
			}
			$this->timeout = $timeout;
		}
		if($enable)
			$this->reset();
	}

	/**
	 * Resets the timer
	 */
	public function reset() {
		if($this->timeout) {
			$this->timer = microtime(true) + $this->timeout;
		} else {
			$this->timer = -1;
		}
	}

	/**
	 * Invalidates the timer, so it will always return true from isTimeUp() method.
	 */
	public function invalidate() {
		$this->timer = -1;
	}

	/**
	 * @return bool
	 */
	public function isTimeUp(): bool {
		return microtime(true) > $this->timer;
	}
}