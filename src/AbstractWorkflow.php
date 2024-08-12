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

namespace Ikarus\SPS\Workflow;

use Ikarus\SPS\Plugin\AbstractPlugin;
use Ikarus\SPS\Register\MemoryRegisterInterface;
use Ikarus\SPS\Workflow\Exception\DuplicateInstructionException;
use Ikarus\SPS\Workflow\Exception\InstructionReferenceException;
use Ikarus\SPS\Workflow\Instruction\AlternatingInstructionInterface;
use Ikarus\SPS\Workflow\Instruction\Control\Jump;
use Ikarus\SPS\Workflow\Instruction\Control\Label;
use Ikarus\SPS\Workflow\Instruction\InstructionInterface;
use Ikarus\SPS\Workflow\Instruction\MutableInstructionInterface;
use TASoft\Collection\Exception\DuplicatedObjectException;

abstract class AbstractWorkflow implements FailureDependentWorkflowInterface
{
	const IDLE_INSTRUCTION_NUMBER = -1;
	const IDLE_INSTRUCTION_NAME = 'Waiting';

	/** @var InstructionInterface[] */
	private $instructions = [];
	/** @var AlternatingInstructionInterface */
	private $alternative_instructions = [];

	/** @var Label[] */
	private $label_instructions = [];

	/** @var InstructionInterface|null */
	private $pending_instruction;

	/** @var InstructionInterface|null */
	private $failed_instruction;

	private $inst_map = [];
	private $pending_map = [];

	private $link_instructions = true;

	private $status = MemoryRegisterInterface::STATUS_OFF;

	private static $workflows = [];

	public static function registerWorkflow(WorkflowInterface $workflow, string $name): void
	{
		if(isset(self::$workflows[$name]))
			throw new DuplicatedObjectException("Duplicate workflow for name %s", 0, NULL, $name);
		self::$workflows[ $name ] = $workflow;
	}

	public static function getWorkflowByName(string $name): ?WorkflowInterface
	{
		return self::$workflows[ $name ] ?? NULL;
	}

	public function getInstructionsCount(): int
	{
		return count($this->instructions);
	}

	public function hasPendingInstructions(): bool
	{
		return (bool) $this->pending_instruction;
	}

	private function makePendentInstruction(?InstructionInterface $instruction): void
	{
		if($instruction) {
			if($instruction instanceof MutableInstructionInterface)
				$instruction->reset();

			$this->pending_instruction = $instruction;

			if(($idx = array_search($instruction, $this->instructions))!==false) {
				$this->pending_map = $this->inst_map[$idx];
			} else
				$this->pending_map = NULL;
		} else {
			$this->pending_map = [static::IDLE_INSTRUCTION_NUMBER, static::IDLE_INSTRUCTION_NAME];
			$this->pending_instruction = NULL;
			$this->status = AbstractPlugin::statusDisable( $this->status );
		}
	}

	private function linkInstructions() {
		foreach(array_merge($this->instructions, $this->alternative_instructions) as $instruction) {
			if($instruction::class == Jump::class) {
				$lb = $instruction->getLabel();
				if(!($this->label_instructions[$lb] ?? false))
					throw (new InstructionReferenceException("No reference found for %s", 5, NULL, $lb))
						->setInstruction($instruction)
						->setReference($lb);
				$ref = $this->label_instructions[$lb];
				$instruction->setNextInstruction($ref);
			}
		}
	}

	public function process(MemoryRegisterInterface $register)
	{
		repeat_immediately:

		if(AbstractPlugin::isStatusOn($this->status)) {

			if($this->link_instructions) {
				$this->link_instructions = false;
				$this->linkInstructions();
			}

			if($this->pending_instruction) {
				switch ($this->pending_instruction->process($register)) {
					case InstructionInterface::PROCESS_RESULT_SUCCESS:
						$this->makePendentInstruction( $this->pending_instruction->getNextInstruction() );
						break;

					case InstructionInterface::PROCESS_RESULT_CONTINUE_IMMEDIATELY:
						$this->makePendentInstruction( $this->pending_instruction->getNextInstruction() );
						goto repeat_immediately;

					case InstructionInterface::PROCESS_RESULT_REPEAT:
						break;

					case InstructionInterface::PROCESS_RESULT_FAILURE_AND_CONTINUE:
						$this->status = AbstractPlugin::statusError($this->status);
						$this->failed_instruction = $this->pending_instruction;
						$this->makePendentInstruction( $this->pending_instruction->getNextInstruction() );
						break;

					case InstructionInterface::PROCESS_RESULT_FAILURE_AND_REPEAT:
						$this->status = AbstractPlugin::statusError($this->status);
						$this->failed_instruction = $this->pending_instruction;
						break;
				}
			}
		}
	}

	public function getStatus(): int
	{
		return $this->status;
	}

	public function getCurrentInstructionNumber(): int
	{
		return $this->pending_map[0] ?? static::IDLE_INSTRUCTION_NUMBER;
	}

	public function getCurrentInstructionName(): ?string
	{
		return $this->pending_map[1] ?? static::IDLE_INSTRUCTION_NAME;
	}

	public function getFailedInstruction(): ?InstructionInterface
	{
		return $this->failed_instruction;
	}

	public function releaseFailureForInstruction(?InstructionInterface $instruction)
	{
		if($instruction === NULL || $instruction === $this->failed_instruction) {
			$this->failed_instruction = NULL;
			$this->status = AbstractPlugin::statusErrorRelease($this->status);
		}
	}

	public function enable() {
		if(!AbstractPlugin::isStatusOn($this->status)) {
			$this->status = AbstractPlugin::statusEnable($this->status);
			$this->makePendentInstruction( reset($this->instructions) );
		}
		return $this;
	}

	public function disable() {
		if(AbstractPlugin::isStatusOn($this->status)) {
			$this->makePendentInstruction( NULL );
		}
		return $this;
	}

	/**
	 * Adds an instruction to the workflow without connecting it!
	 *
	 * @param InstructionInterface $instruction
	 * @param int|NULL $number
	 * @param string|NULL $name
	 * @return static
	 */
	public function addInstruction(InstructionInterface $instruction, int $number = NULL, string $name = NULL) {
		if(!in_array($instruction, $this->instructions)) {
			$idx = count($this->instructions);
			$this->instructions[] = $instruction;
			$this->inst_map[$idx] = [$number === NULL ? $idx : $number, $name];

			if($instruction instanceof Label) {
				if($this->label_instructions[$instruction->getLabel()] ?? false)
					throw (new DuplicateInstructionException("Instruction with label %s already exists", 4, NULL, $instruction->getLabel()))->setInstruction($instruction);
				$this->label_instructions[ $instruction->getLabel() ] = $instruction;
			}

			if($instruction instanceof AlternatingInstructionInterface)
				$this->alternative_instructions[] = $instruction->getAlternativeInstruction();

			$this->link_instructions = true;
		} else {
			throw (new DuplicateInstructionException("Dplicatie instruction"))->setInstruction($instruction);
		}
		return $this;
	}

	/**
	 * Appends an instruction to the workflow and links it to the last instruction
	 *
	 * @param InstructionInterface $instruction
	 * @param int|NULL $number
	 * @param string|NULL $name
	 * @return $this
	 */
	public function appendInstruction(InstructionInterface $instruction, int $number = NULL, string $name = NULL) {
		if(count($this->instructions) > 0) {
			$last = end($this->instructions);
		}

		$this->addInstruction($instruction, $number, $name);

		if(isset($last) && ($last instanceof MutableInstructionInterface)) {
			$last->setNextInstruction($instruction);
		}
		return $this;
	}

	/**
	 * @param string $name
	 * @return InstructionInterface|null
	 */
	public function findInstructionByName(string $name): ?InstructionInterface {
		foreach($this->inst_map as $idx => $map) {
			if($map[1] == $name)
				return $this->instructions[$idx];
		}
		return NULL;
	}

	/**
	 * @param int $number
	 * @return InstructionInterface|null
	 */
	public function findInstructionByNumber(int $number): ?InstructionInterface {
		foreach($this->inst_map as $idx => $map) {
			if($map[0] == $number)
				return $this->instructions[$idx];
		}
		return NULL;
	}

	/**
	 * @param $instruction
	 * @return static
	 */
	public function removeInstruction($instruction) {
		if(is_numeric($instruction))
			$instruction = $this->findInstructionByNumber($instruction);
		elseif(is_string($instruction))
			$instruction = $this->findInstructionByName($instruction);

		if($instruction instanceof InstructionInterface) {
			if(($idx = array_search($instruction, $this->instructions)) !== false) {
				unset($this->instructions[$idx]);
				unset($this->inst_map[$idx]);

				$this->link_instructions = true;
			}
		}
		return $this;
	}

	/**
	 * @return $this
	 */
	public function removeAllInstructions() {
		$this->instructions = [];
		$this->inst_map = [];
		$this->link_instructions = true;
		return $this;
	}
}