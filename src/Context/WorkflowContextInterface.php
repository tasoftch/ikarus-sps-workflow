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

interface WorkflowContextInterface
{
	/**
	 * Writes a persistent value to the context.
	 * This value maintains available during the whole workflow process.
	 *
	 * @param string $key
	 * @param $value
	 */
	public function setValue(string $key, $value);

	/**
	 * @param string $key
	 * @return mixed
	 */
	public function getValue(string $key);

	/**
	 * If available, this method returns the step data from creating the step.
	 * It may be changed and remains for ever.
	 *
	 * @return StepData|null
	 */
	public function getStepData(): ?StepData;

	/**
	 * Normally each step gets called once during workflow process.
	 * If the step requires a repetition next cycle it can call this method.
	 */
	public function repeatStep();

	/**
	 * Looks for the given step reference as stepnumber or stepname and will move the workflow to it if found.
	 * This method returns false if it does not find the referenced step.
	 *
	 * @param int|string $step
	 * @return bool
	 */
	public function jumpToStep($step): bool;

	/**
	 * Calling this method will continue with the next step immediately without waiting for the next cycle.
	 */
	public function continueNextStepInCurrentCycle();
}