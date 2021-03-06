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

namespace Ikarus\SPS\Workflow;


use Countable;
use Ikarus\SPS\Register\MemoryRegisterInterface;
use Ikarus\SPS\Workflow\Context\_InternalWorkflowContext;
use Ikarus\SPS\Workflow\Context\_StepTreeItem;
use Ikarus\SPS\Workflow\Context\StepData;
use Ikarus\SPS\Workflow\Context\WorkflowContextInterface;
use Ikarus\SPS\Workflow\Step\AbstractStep;
use Ikarus\SPS\Workflow\Step\StepAwareInterface;
use Ikarus\SPS\Workflow\Step\StepGeneratorInterface;
use Ikarus\SPS\Workflow\Step\StepInterface;
use Ikarus\SPS\Workflow\Step\StepResetInterface;
use TASoft\Util\ValueInjector;

class WorkflowManager implements WorkflowManagerInterface, Countable
{
	/** @var StepInterface[] */
	private $steps = [];
	private $_step_count=1;
	/** @var _StepTreeItem[] */
	private $_step_tree = [];
	private $stepDataObjects = [];

	/** @var WorkflowContextInterface */
	private $processContext;

	/** @var _StepTreeItem */
	private $currentStepItem;

	private $loopWorkflow = false;

	/**
	 * @inheritDoc
	 */
	public function addStep($step, StepData $stepData = NULL)
	{
		if($step instanceof StepInterface || $step instanceof StepGeneratorInterface) {
			if($step instanceof AbstractStep) {
				$vi = new ValueInjector($step, AbstractStep::class);
				if(NULL === $vi->step)
					$vi->step = $this->_step_count;
			}
			$this->steps[  $step->getStep() ] = $step;
			$this->_step_count = max($this->_step_count, $step->getStep()) + 1;

			if($stepData)
				$this->stepDataObjects[$step->getStep()] = $stepData;

			$this->_step_tree = [];
		}
		return $this;
	}

	/**
	 * @inheritDoc
	 */
	public function removeStep($step)
	{
		if(is_int($step))
			unset($this->steps[$step]);
		elseif($step instanceof StepInterface || $step instanceof StepGeneratorInterface) {
			unset($this->steps[$step->getStep()]);
		} elseif(is_string($step)) {
			foreach($this->steps as $s) {
				if($s->getName() == $step) {
					unset($this->steps[$s->getStep()]);
					break;
				}
			}
		}
		$this->_step_tree = [];
		$this->currentStepItem = NULL;

		return $this;
	}

	/**
	 * @inheritDoc
	 */
	public function isLoopWorkflow(): bool
	{
		return $this->loopWorkflow;
	}

	/**
	 * @param bool $loopWorkflow
	 * @return WorkflowManager
	 */
	public function setLoopWorkflow(bool $loopWorkflow): WorkflowManager
	{
		$this->loopWorkflow = $loopWorkflow;
		return $this;
	}

	private function _updateStepTree() {
		if(!$this->_step_tree) {
			ksort($this->steps);
			$tree = [];

			/** @var _StepTreeItem $last */
			$last = NULL;
			foreach($this->steps as $stepIdx => $step) {
				$ti = new _StepTreeItem();
				if($last)
					$last->nextTreeItem = $ti;
				$ti->step = $step instanceof StepGeneratorInterface ? $step->generateStep() : $step;
				if($sd = $this->stepDataObjects[$stepIdx] ?? 0)
					$ti->stepData = $sd;
				$last = $tree[] = $ti;
			}

			$s = reset($tree);
			if($s) {
				if($this->isLoopWorkflow() && $last) {
					$last->nextTreeItem = $s;
				}

				$this->makeCurrentStep($s);
			}

			$this->_step_tree = $tree;
		}
	}

	private function makeCurrentStep(?_StepTreeItem $item) {
		$ctx = $this->_getWorkflowProcessContext();

		if($this->currentStepItem) {
			$s = $this->currentStepItem->step;
			if($s instanceof StepAwareInterface)
				$s->stepWillEndProcess($ctx, $ctx->getMemoryRegister());
		}
		$this->currentStepItem = $item;

		if($item) {
			$s = $item->step;
			if($s instanceof StepAwareInterface)
				$s->stepDidBeginProcess($ctx, $ctx->getMemoryRegister());
		}
	}

	/**
	 * @inheritDoc
	 */
	public function getCurrentStep(): ?StepInterface
	{
		return $this->currentStepItem ? $this->currentStepItem->step : NULL;
	}

	/**
	 * @inheritDoc
	 */
	public function needsProcess(): bool {
		$this->_updateStepTree();
		return $this->currentStepItem ? true : false;
	}

	/**
	 * Finds the very first initial step
	 *
	 * @return StepInterface|null
	 */
	public function getInitialStep(): ?StepInterface {
		$this->_updateStepTree();
		if($s = reset($this->_step_tree))
			return $s->step;
		return NULL;
	}

	/**
	 * Restarts a suspended workflow by a given step
	 *
	 * @param $step
	 */
	public function restartWithStep($step) {
		$s = $this->_getStep($step);
		$this->makeCurrentStep($s);
	}



	/**
	 * @inheritDoc
	 */
	public function getStep($step): ?StepInterface
	{
		$s = $this->_getStep($step);
		return $s ? $s->step : NULL;
	}

	/**
	 * @inheritDoc
	 */
	public function reset()
	{
		$ctx = $this->_getWorkflowProcessContext();

		array_walk($this->steps, function($s) use ($ctx) {
			if($s instanceof StepResetInterface)
				$s->reset($ctx);
		});

		$this->_step_tree = NULL;
		$this->_updateStepTree();
	}

	public function suspend() {
		$this->reset();
		$this->makeCurrentStep(NULL);
	}

	/**
	 * @inheritDoc
	 */
	private function _getStep($step): ?_StepTreeItem
	{
		$this->_updateStepTree();
		foreach($this->_step_tree as $item) {
			if($item->isEqual($step))
				return $item;
		}
		return NULL;
	}

	private function _getWorkflowProcessContext(): _InternalWorkflowContext {
		if(!$this->processContext) {
			$this->processContext = new _InternalWorkflowContext($this);
		}
		return $this->processContext;
	}

	private function _nextStep() {
		if($this->currentStepItem) {
			$this->makeCurrentStep($this->currentStepItem->nextTreeItem);
		} else
			$this->makeCurrentStep(NULL);
	}

	/**
	 * @inheritDoc
	 */
	public function getWorkflowProcessContext(): WorkflowContextInterface
	{
		return $this->_getWorkflowProcessContext();
	}

	/**
	 * @inheritDoc
	 */
	public function process(MemoryRegisterInterface $register = NULL)
	{
		$ctx = $this->_getWorkflowProcessContext();
		if($register && NULL === $ctx->getMemoryRegister())
			$ctx->setMemoryRegister($register);

		$this->_updateStepTree();

		repeat:
		if($step = $this->getCurrentStep()) {
			$ctx->resetCustomValues();
			$ctx->setValue("@NS", true);

			$ctx->setStepData( $this->stepDataObjects[ $step->getStep() ] ?? NULL );
			$step->process($ctx, $ctx->getMemoryRegister());

			if($ns = $ctx->getValue("@NS")) {
				if($ns === true)
					$this->_nextStep();
				elseif($ns instanceof _StepTreeItem) {
					$this->makeCurrentStep($ns);
				}
			}

			if($ctx->getValue("@repeat")) {
				$this->reset();
				if($ctx->getValue("@continue"))
					goto repeat;
			} elseif($ctx->getValue("@continue")) {
				goto repeat;
			} elseif($ctx->getValue("@terminate")) {
				$this->suspend();
			}
		}
	}

	public function count()
	{
		return count($this->steps);
	}
}