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

abstract class AbstractStepComponent implements StepComponentInterface
{
	private $componentName;
	private $label;
	private $description;
	private $options = 0;
	private $groupName;

	/**
	 * AbstractStepComponent constructor.
	 * @param string $componentName
	 * @param Description|Label|Option ...$items
	 */
	public function __construct(string $componentName, ...$items)
	{
		$this->componentName = $componentName;

		foreach($items as $item) {
			if($item instanceof Description)
				$this->description = $item->getName();
			elseif($item instanceof Label)
				$this->label = $item->getName();
			elseif($item instanceof Option)
				$this->options |= $item->getOption();
		}
	}

	/**
	 * @inheritDoc
	 */
	public function getComponentName(): string
	{
		return $this->componentName;
	}

	/**
	 * @inheritDoc
	 */
	public function getLabel(): ?string
	{
		return $this->label;
	}

	/**
	 * @inheritDoc
	 */
	public function getDescription(): ?string
	{
		return $this->description;
	}

	/**
	 * @inheritDoc
	 */
	public function getOptions(): int
	{
		return $this->options;
	}
}