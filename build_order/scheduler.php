<?php

// This file is part of sc2calc.org - http://sc2calc.org/
//
// sc2calc.org is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// sc2calc.org is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with sc2calc.org. If not, see <http://www.gnu.org/licenses/>.

/**
 * @package sc2calc.org
 * @copyright 2010 Jasper Abraham Visser
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * The scheduler is responsible for planning the timing of the jobs that
 * constitute the build order. It schedules the jobs with fixed triggers in the
 * order in which they appear, and all other jobs wherever possible.
 */
class Scheduler {

	/// class constants
	const debugFlag = 4;

	/// static public members

	/**
	 * If true, will echo debug messages
	 * @var bool
	 */
	static public $debug = false;

	/// private members

	/**
	 * @var array Array of unscheduled fixed jobs
	 */
	private $_fixedJobs;

	/**
	 * @var array Array of unscheduled non-recurring floating jobs
	 */
	private $_floatingJobs;
	
	/**
	 * @var array Array of recurring floating jobs
	 */
	private $_recurringJobs;

	/**
	 * @var array Array of scheduled jobs
	 */
	private $_scheduledJobs;
	
	/**
	 * @var Timeline Timeline to schedule on
	 */
	private $_timeline;

	/// constructor

	/**
	 * Initialize the scheduler
	 * @param Timeline $timeline Timeline to schedule jobs on
	 * @param array $unscheduledJobs Jobs to be scheduled
	 */
	public function __construct($timeline, $unscheduledJobs) {
		$this->_timeline = $timeline;
		$this->_scheduledJobs = array();
		$this->_fixedJobs = array();
		$this->_floatingJobs = array();
		$this->_recurringJobs = array();
		foreach($unscheduledJobs as $job) {
			if(isset($job->triggerGas) || isset($job->triggerSupply) || isset($job->triggerMineral)) {
				$this->_fixedJobs[] = $job;
			} elseif($job->recurring) {
				$this->_recurringJobs[] = $job;
			} else {
				$this->_floatingJobs[] = $job;
			}
		}
	}

	/// public methods

	/**
	 * Schedule all jobs
	 * @return array Scheduled jobs, ordered by start time
	 */
	public function schedule() {
		Logger::enter("Scheduler::schedule");
		if(self::$debug) tracemsg("Scheduler::schedule(), scheduling ". count($this->_fixedJobs) ." fixed jobs, ". count($this->_floatingJobs) ." floating jobs, ". count($this->_recurringJobs) ." recurring jobs.");

		// process fixed jobs
		foreach ($this->_fixedJobs as $i => $job) {
			
			// squeeze in non-recurring floating jobs until fixed job is available
			do {
				$this->_timeline->calculate($job, $this->_scheduledJobs);
			} while($job->timeStarted === INF && !$this->deadEnd($job) && $this->squeeze($job));

			// fixed job could not be scheduled
			if($job->timeStarted === INF) {
				$this->reportUnavailable(array($job));
			}
			
			/**
			 * If this job has no supply requirement, estimate the proper supply
			 * requirement to prevent squeezing in floating jobs that would make
			 * the next job with a supply requirement impossible.
			 */
			if (!isset($job->triggerSupply)) {
				$supplyDelta = 0;
				$triggerSupply = 0;
				for ($j = $i; $j < count($this->_fixedJobs); $j++) {
					if (isset($this->_fixedJobs[$j]->triggerSupply)) {
						$triggerSupply = $this->_fixedJobs[$j]->triggerSupply;
						break;
					} else {
						$supplyDelta += $this->_fixedJobs[$j]->supplyCost(false);
					}
				}
				
				$job->triggerSupply = $triggerSupply - $supplyDelta;
			}

			// squeeze in any floating jobs that will fit
			while($this->squeeze($job)) {
				$this->_timeline->calculate($job, $this->_scheduledJobs);
				if($job->timeStarted === INF) {
					$this->reportUnavailable(array($job));
				}
			}

			// process fixed job
			$this->process($job);
		}

		// process remaining floating jobs
		while(count($this->_floatingJobs) > 0) {

			// pick earliest available non-recurring job
			$job = $this->earliest($this->_floatingJobs);
			if(!isset($job)) {
				$this->reportUnavailable($this->_floatingJobs);
			}

			// squeeze in some recurring floating jobs
			while($this->squeeze($job, true)) {
				$this->_timeline->calculate($job, $this->_scheduledJobs);
				if($job->timeStarted === INF) {
					$this->reportUnavailable(array($job));
				}
			}
			$this->process($job);

		}

		// process remaining checkpoints
		$this->_timeline->processCheckpoints();

		Logger::leave("Scheduler::schedule");
		return $this->_scheduledJobs;
	}

	/// private methods

	/**
	 *
	 * @param bool $recurring
	 * @return array
	 */
	private function candidates($recurring = null) {
		
		// choose candidates
		if($recurring === null) {
			$candidates = array_merge($this->_floatingJobs, $this->_recurringJobs);
		} elseif($recurring) {
			$candidates = $this->_recurringJobs;
		} else {
			$candidates = $this->_floatingJobs;
		}

		// calculate all candidates
		// ignore candidates that are not available ever
		foreach($candidates as $key => $job) {
			$this->_timeline->calculate($job, $this->_scheduledJobs);
			if($job->timeStarted === INF) {
				unset($candidates[$key]);
			}
		}
		
		return $candidates;
	}

	/**
	 * Determine if job could be solved by squeezing in some floating jobs. Note
	 * that at present, this will always return false if there are non-recurring
	 * floating jobs remaining.
	 * @param Job $job
	 * @return bool
	 */
	private function deadEnd($job) {
		$candidates = $this->candidates();
		foreach($candidates as $candidate) {
			if(!$candidate->recurring || $job->availability->solvedBy($candidate)) {
				if(self::$debug) tracemsg("Scheduler::deadEnd(". $job ."), job reports <i>". $job->availability ."</i>. But it is not a dead end, because of ". $candidate);
				return false;
			}
		}
		return true;
	}

	/**
	 * Get earliest available job from the given jobs.
	 * @param array $jobs Jobs to choose from.
	 * @param bool $recurring If set, only either recurring or non-recurring
	 * jobs are considered.
	 * @return Job Chosen job
	 */
	private function earliest($jobs, $recurring = null) {
		foreach($jobs as $job) {
			if($recurring === null || $job->recurring == $recurring) {
				$this->_timeline->calculate($job, $this->_scheduledJobs);
				if($job->timeStarted !== INF) {
					if(!isset($candidate) || $job->timeStarted < $candidate->timeStarted) {
						$candidate = $job;
					}
				}
			}
		}
		if(isset($candidate)) {
			return $candidate;
		}
	}

	/**
	 * Process a job, remove it from unscheduled jobs, and process all mutations
	 * that are available afterwards.
	 * @param Job $job
	 */
	private function process($job) {
		if(self::$debug) tracemsg("Scheduler::process(" . $job . "), job starts at ". simple_time($job->timeStarted));

		// move to scheduled jobs
		$job->pickOrder = count($this->_scheduledJobs);
		$this->_scheduledJobs[] = $job;
		array_remove($this->_fixedJobs, $job);
		array_remove($this->_floatingJobs, $job);
		array_remove($this->_recurringJobs, $job);

		// update timeline
		$this->_timeline->process($job);

		// cancel recurring jobs
		$job->cancel($this->_recurringJobs);
		
		// process any available non-consumptive jobs
		foreach($this->_floatingJobs as $job) {
			if(!$job->consumptive()) {
				$this->_timeline->calculate($job, $this->_scheduledJobs);
				if($job->timeStarted !== INF) {
					$this->_timeline->process($job, true);
					$job->pickOrder = count($this->_scheduledJobs);
					$this->_scheduledJobs[] = $job;
					array_remove($this->_floatingJobs, $job);
				}
			}
		}
	}

	/**
	 * Throw an error, reporting all of the given jobs that are unavailable,
	 * except those that are dependant on another unavailable job.
	 * @param array $jobs
	 */
	private function reportUnavailable($jobs) {
		foreach($jobs as $job) {
			$this->_timeline->calculate($job, $this->_scheduledJobs);
			if($job->availability->status != Availability::MissingDependency && $job->availability->status != Availability::Available) {
				throw_error("Job <i>" . $job . "</i> could not be scheduled. " . $job->availability, $job->availability->description(), false);
			}
		}
		die;
	}
	
	/**
	 * Schedule as a floating job that can be squeezed in without delaying the
	 * fixed job. If there is supply gap before the fixed job, a floating
	 * jobs is scheduled as needed to bridge the gap, possibly delaying the
	 * fixed job.
	 * @param Job $fixedJob Fixed job
	 * @param bool $recurring If set, consider either only recurring or
	 * non-recurring jobs.
	 * @return bool True, if a job could be squeezed in.
	 */
	private function squeeze($fixedJob, $recurring = null) {
		Logger::enter("Scheduler::squeeze");
		if(self::$debug) tracemsg("Scheduler::squeeze(". $fixedJob .", ". ($recurring === null ? "null" : ($recurring ? "true" : "false")) .")");
		
		// squeezing is mandatory if the fixed job is unavailable
		$mandatory = $fixedJob->availability->status != Availability::Available;
		if(self::$debug) tracemsg("Scheduler::squeeze(), squeezing is ". ($mandatory ? "" : "not ") ."mandatory!");

		// choose candidates
		if($recurring === null) {
			$candidates = array_merge($this->_floatingJobs, $this->_recurringJobs);
		} elseif($recurring) {
			$candidates = $this->_recurringJobs;
		} else {
			$candidates = $this->_floatingJobs;
		}
		if(self::$debug) tracemsg("Scheduler::squeeze(), choosing from ". count($candidates). " candidates!");

		// ignore recurring candidates that build the same product as the fixed job
		if(!$mandatory && $fixedJob->productBuilt() !== null) {
			foreach($candidates as $key => $job) {
				if($job->recurring && $job->productBuilt() !== null && $job->productBuilt()->uid == $fixedJob->productBuilt()->uid) {
					if(self::$debug) tracemsg("Scheduler::squeeze(), eliminating ". $job .", which builds the same product as ". $fixedJob .".");
					unset($candidates[$key]);
				}
			}
		}

		// ignore candidates that are not available before fixed job starts
		// if mandatory, instead ignore candidates that are not available ever
		foreach($candidates as $key => $job) {
			$this->_timeline->calculate($job, $this->_scheduledJobs);
			if(!$mandatory && $job->timeStarted > $fixedJob->timeStarted) {
				if(self::$debug) tracemsg("Scheduler::squeeze(), eliminating ". $job .", which is available at ". simple_time($job->timeStarted). ", but fixed job starts at ". simple_time($fixedJob->timeStarted));
				unset($candidates[$key]);
			} elseif($mandatory && $job->timeStarted === INF) {
				if(self::$debug) tracemsg("Scheduler::squeeze(), eliminating ". $job .", which is unavailable because ". $job->availability);
				unset($candidates[$key]);
			}
		}

		// ignore jobs that affect supply the wrong way
		if(isset($fixedJob->triggerSupply)) {
			$supplyGap = $fixedJob->triggerSupply - $this->_timeline->supplyCount;
			if(self::$debug) tracemsg("Scheduler::squeeze(), supply gap is ". $supplyGap .", current supply count is ". $this->_timeline->supplyCount .", fixed job is triggered at ". $fixedJob->triggerSupply);
			foreach($candidates as $key => $job) {
				if($supplyGap == 0 && $job->supplyCost(true) != 0) {
					if(self::$debug) tracemsg("Scheduler::squeeze(), eliminating <i>". $job ."</i>; Supply gap is ". $supplyGap .", job's supply cost is ". $job->supplyCost(true));
					unset($candidates[$key]);
				} elseif($supplyGap > 0 && ($job->supplyCost(true) > $supplyGap || $job->supplyCost(true) < 0)) {
					if(self::$debug) tracemsg("Scheduler::squeeze(), #3 Eliminating ". $job);
					unset($candidates[$key]);
				} elseif($supplyGap < 0 && ($job->supplyCost(true) < $supplyGap || $job->supplyCost(true) > 0)) {
					if(self::$debug) tracemsg("Scheduler::squeeze(), #4 Eliminating ". $job);
					unset($candidates[$key]);
				}
			}
		}

		// if not mandatory, ignore jobs that exceed surplus minerals or gas
		if(!$mandatory) {
			foreach($candidates as $key => $job) {

				// always allow jobs that don't cost resources
				if($job->mineralCost() == 0 && $job->gasCost() == 0) {
					continue;
				}

				// the job affects income
				$mutations = $job->mutations();
				$mutations->sort();
				if(count($mutations) > 0) {

					// calculate job start & complete time
					$jobComplete = $this->_timeline->whenComplete($job);

					// set up alternate reality income
					$income = clone $this->_timeline->income;
					foreach($mutations as $mutation) {
						$income->splice($mutation);
					}

				// the job does not affect income
				} else {
					$income = $this->_timeline->income;
				}

				// if surplus is not great enough
				list($gasSurplus, $mineralSurplus) = $income->surplus($fixedJob->timeStarted);
				if(round($gasSurplus) < $job->gasCost() + $fixedJob->gasCost()) {
					if(self::$debug) tracemsg("Scheduler::squeeze(), eliminating ". $job .". Gas needed for both jobs is ".
						($job->gasCost() + $fixedJob->gasCost()) .", gas surplus is ". $gasSurplus .".");
					unset($candidates[$key]);
				}
				if(round($mineralSurplus) < $job->mineralCost() + $fixedJob->mineralCost()) {
					if(self::$debug) tracemsg("Scheduler::squeeze(), eliminating ". $job .". Mineral needed for both jobs is ".
						($job->mineralCost() + $fixedJob->mineralCost()) .", mineral surplus is ". $mineralSurplus .".");
					unset($candidates[$key]);
				}
			}
		}

		// if not mandatory, ignore jobs whose larvae, production queue
		// or spellcaster usage would stall fixed job
		if(!$mandatory) {
			foreach($candidates as $key => $job) {
				if(!$this->_timeline->canAccommodate($job, $fixedJob)) {
					if(self::$debug) tracemsg("Scheduler::squeeze(), #16 Eliminating ". $job);
					unset($candidates[$key]);
				}
			}
		}

		// ignore candidates that exceed supply gap
		$supplyGap = $this->supplyGap();
		foreach($candidates as $key => $job) {
			if($supplyGap >= 0 && $job->supplyCost(true) > $supplyGap) {
				if(self::$debug) tracemsg("Scheduler::squeeze(), #9 Eliminating ". $job);
				unset($candidates[$key]);
			}
		}

		// ignore jobs that cause fixed job to exceed supply capacity
		foreach($candidates as $job) {
			if($job->supplyCost(true) > 0) {

				// how much supply capacity is needed
				$supplyNeeded = $this->_timeline->supplyCount + $fixedJob->supplyCost(true) + $job->supplyCost(true);

				// discard candidate if supply capacity is not available
				$time = $this->_timeline->farms->when($supplyNeeded);
				if(!$mandatory && $time > $fixedJob->timeStarted) {
					if(self::$debug) tracemsg("Scheduler::squeeze(), #14 Eliminating ". $job);
					unset($candidates[$key]);
				} elseif($mandatory && $time === INF) {
					if(self::$debug) tracemsg("Scheduler::squeeze(), #15 Eliminating ". $job);
					unset($candidates[$key]);
				}
			}
		}

		// no floating jobs are available
		if(count($candidates) == 0) {
			if(self::$debug) tracemsg("Scheduler::squeeze(), all jobs were eliminated!");

			// if mandatory, throw an error
			if($mandatory) {
				$this->reportUnavailable(array($fixedJob));
			}

			Logger::leave("Scheduler::squeeze");
			return false;
		}

		// choose earliest available job
		$job = $this->earliest($candidates);

		// process floating job
		if(self::$debug) {
			$report = "";
			foreach($candidates as $candidate) {
				$report .= (isset($notFirst) ? ", " : "") . $candidate . "(". simple_time($candidate->timeStarted) .")";
				$notFirst = true;
			}
			tracemsg("Scheduler::squeeze(), remaining candidates are ". $report);
		}
		if(self::$debug) tracemsg("Scheduler::squeeze(), chosen ". $job);
		$this->process($job);

		// reschedule, if recurring
		if($job->recurring) {
			$this->_recurringJobs[] = clone $job;
		}
		Logger::leave("Scheduler::squeeze");
		return true;
	}

	/**
	 * Gap between current supply count and supply trigger of next fixed
	 * job that has one.
	 * @return int Supply gap.
	 */
	private function supplyGap() {
		foreach($this->_fixedJobs as $job) {
			if(isset($job->triggerSupply)) {
				return $job->triggerSupply - $this->_timeline->supplyCount;
			}
		}
		return INF;
	}
};
?>