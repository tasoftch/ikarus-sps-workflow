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

namespace Ikarus\SPS\Workflow\Model;


use Ikarus\SPS\Workflow\Context\StepData;
use Ikarus\SPS\Workflow\Step\StepGeneratorInterface;
use Ikarus\SPS\Workflow\Step\StepInterface;

/**
 * Interface StepComponentInterface
 *
 * A step component must implement the direct step generator interface or be compilable by implementing the compiler interface.
 *
 * @package Ikarus\SPS\Workflow\Model
 * @see StepComponentCompilerInterface
 * @see StepComponentGeneratorInterface
 */
interface StepComponentInterface
{
	const OPTION_PROCESS_KICK_START = 1<<0;
	const OPTION_CIRCULAR_PROCESSING = 1<<1;

	/**
	 * A uniquely defined name for this component
	 *
	 * @return string
	 */
	public function getComponentName(): string;

	/**
	 * The display name for this component
	 *
	 * @return string|null
	 */
	public function getLabel(): ?string;

	/**
	 * A description for this component
	 *
	 * @return string|null
	 */
	public function getDescription(): ?string;

	/**
	 * Options for this component
	 *
	 * @return int
	 */
	public function getOptions(): int;

	/**
	 * Generates the step configured to the user data.
	 *
	 * If you want to use compilers to create steps, this method must not relate to the component itself.
	 *
	 * @param StepData|null $userData
	 * @param string $stepName
	 * @param int $step
	 * @return StepInterface|StepGeneratorInterface
	 */
	public function makeStep(?StepData $userData, string $stepName, int $step);
}