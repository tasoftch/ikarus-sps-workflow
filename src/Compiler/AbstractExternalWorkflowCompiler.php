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

namespace Ikarus\SPS\Workflow\Compiler;

use Ikarus\SPS\Workflow\Compiler\Provider\WorkflowProviderInterface;
use Ikarus\SPS\Workflow\Context\StepData;
use Ikarus\SPS\Workflow\Model\StepComponentInterface;
use ReflectionException;

abstract class AbstractExternalWorkflowCompiler extends AbstractWorkflowCompiler
{
	private $canCompileExternal = true;

	protected $classImports = [
		StepData::class,
	];

	/**
	 * @param $className
	 * @param null $alias
	 */
	public function addClassImport($className, $alias = NULL) {
		if(NULL == $alias) {
			$cn = explode("\\", $className);
			$alias = array_pop($cn);
		}
		$this->classImports[$className] = $alias;
	}

	/**
	 * @return bool
	 */
	public function canCompileExternal(): bool
	{
		return $this->canCompileExternal;
	}

	/**
	 * @return string
	 */
	protected function stringifyClassImports(): string {
		$contents = "";
		foreach($this->classImports as $class => $alias) {
			if(is_numeric($class) && is_string($alias)) {
				$class = $alias;
				$alias = 0;
			}
			$cn = explode("\\", $class);
			$cn = array_pop($cn);
			if($alias == $cn || !$alias)
				$contents .= "use $class;\n";
			else
				$contents .= "use $class as $alias;\n";
		}
		return $contents;
	}

	/**
	 * @inheritDoc
	 */
	protected function prepareFromProvider(WorkflowProviderInterface $provider): array
	{
		$this->canCompileExternal = true;
		return parent::prepareFromProvider($provider);
	}

	/**
	 * @param StepComponentInterface $component
	 * @return string|null
	 */
	protected function exportExternalCodeForComponent(StepComponentInterface $component, $methodName, bool $strip_whitespace = false): ?string {
		try {
			$cl = $component->getExecutable(NULL, "", 0);
			$ref = new \ReflectionFunction( $cl );
		} catch (ReflectionException $e) {
			trigger_error($e->getMessage(), E_USER_WARNING);
			return NULL;
		}

		$content =  token_get_all(file_get_contents($ref->getFileName()));
		$s = 0;
		$c = 1;

		$captured = "";
		foreach($content as $token) {
			if($s==11) {
				if($token == '{')
					$c++;
				if($token == '}')
					$c--;
				if($c == 0)
					break;

				if($strip_whitespace) {
					if($token[0] == T_COMMENT || $token[0] == T_DOC_COMMENT  || $token[0] == T_WHITESPACE) {
						$captured .= " ";
						continue;
					}
				}
				$captured.= is_array($token) ? $token[1] : $token;
			}

			if($token[0] == T_COMMENT || $token[0] == T_WHITESPACE)
				continue;

			if($s == 0 && $token[0] == T_PUBLIC) {
				$s = 1;
				continue;
			}

			if($s == 1 && $token[0] == T_FUNCTION) {
				$s = 2;
				continue;
			}

			if($s == 2) {
				if($token[0] == T_STRING && strtolower($token[1]) == strtolower($methodName)) {
					$s=10;
					continue;
				} else
					$s = 0;
			}

			if($s == 10 && $token[0] == T_RETURN) {
				$s =11;
				continue;
			}
		}
		return trim($captured, "\ \t\n\r\0\x0B ;");
	}
}