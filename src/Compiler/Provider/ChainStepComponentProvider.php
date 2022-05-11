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

namespace Ikarus\SPS\Workflow\Compiler\Provider;


use Ikarus\SPS\Workflow\Model\StepComponentInterface;

class ChainStepComponentProvider implements StepComponentProviderInterface
{
	/** @var StepComponentProviderInterface[] */
	private $providers = [];

	/**
	 * ChainStepComponentProvider constructor.
	 * @param StepComponentProviderInterface ...$providers
	 */
	public function __construct(...$providers)
	{
		foreach($providers as $provider) {
			if($provider instanceof StepComponentProviderInterface)
				$this->providers[] = $provider;
		}
	}

	/**
	 * @inheritDoc
	 */
	public function getStepComponent(string $componentName): ?StepComponentInterface
	{
		foreach($this->getProviders() as $provider) {
			if($nc = $provider->getStepComponent($componentName))
				return $nc;
		}
		return NULL;
	}

	/**
	 * @param StepComponentProviderInterface $provider
	 * @return $this
	 */
	public function addProvider(StepComponentProviderInterface $provider): ChainStepComponentProvider {
		$this->providers[] = $provider;
		return $this;
	}

	/**
	 * @param StepComponentProviderInterface $provider
	 * @return $this
	 */
	public function removeProvider(StepComponentProviderInterface $provider): ChainStepComponentProvider
	{
		if(($idx = array_search($provider, $this->providers)) !== false)
			unset($this->providers[$idx]);
		return $this;
	}

	/**
	 * @return StepComponentProviderInterface[]
	 */
	public function getProviders(): array
	{
		return $this->providers;
	}
}