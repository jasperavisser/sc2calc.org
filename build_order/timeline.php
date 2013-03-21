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
 * Checkpoints are fixed time events that are placed on the timeline to report
 * the state of the build at that time.
 */
class Checkpoint {
	
	/// public members
	
	/**
	 * Descriptive text for this checkpoint
	 * @var string
	 */
	public $description;
	
	/**
	 * Reported completion time of this checkpoint
	 * @var float
	 */
	public $timeCompleted;

	/**
	 * Time at which this checkpoint is triggered
	 * @var float
	 */
	public $timeStarted;

	/// constructor

	/**
	 * Create a new checkpoint.
	 * @param string $description
	 * @param float $timeStarted
	 * @param float $timeCompleted
	 */
	public function __construct($description, $timeStarted, $timeCompleted = null) {
		$this->description = $description;
		$this->timeStarted = $timeStarted;
		$this->timeCompleted = coalesce($timeCompleted, $timeStarted);
	}

	/// public static methods

	/**
	 * Compare two checkpoints by start time.
	 * @param Checkpoint $checkpoint1
	 * @param Checkpoint $checkpoint2
	 * @return int
	 */
	public static function compare($checkpoint1, $checkpoint2) {
		if($checkpoint1->timeStarted > $checkpoint2->timeStarted) {
			return 1;
		} elseif($checkpoint1->timeStarted < $checkpoint2->timeStarted) {
			return-1;
		}
		return 0;
	}
}

/**
 * Events are logged on the timeline when a job is completed, or a checkpoint is
 * handled.
 */
class Event {

	/// public members

	/**
	 * Descriptive text for this event.
	 * @var string
	 */
	public $description;

	/**
	 * Surplus energy at the time this event is started.
	 * @var array
	 */
	public $energySurplus;

	/**
	 * Surplus gas at the time this event is started.
	 * @var array
	 */
	public $gasSurplus;

	/**
	 * Surplus larvae at the time this event is started.
	 * @var array
	 */
	public $larvae;

	/**
	 * Surplus mineral at the time this event is started.
	 * @var array
	 */
	public $mineralSurplus;

	/**
	 * Number that indicates in which order the events are created.
	 * @var int
	 */
	public $order;

	/**
	 * Supply capacity at the time of this event.
	 * @var int
	 */
	public $supplyCapacity;

	/**
	 * Supply capacity at the time of this event.
	 * @var int
	 */
	public $supplyCount;

	/**
	 * Time when this event is completed.
	 * @var float
	 */
	public $timeCompleted;

	/**
	 * Time when this event is started.
	 * @var float
	 */
	public $timeStarted;
	
	/// operators

	/**
	 * Convert to a string.
	 * @return string
	 */
	public function __tostring() {
		return "<tr>".
			"<td class=\"right\">". $this->order ."</td>".
			"<td class=\"center\">". simple_time($this->timeStarted) ."</td>".
			"<td class=\"center\">". simple_time($this->timeCompleted) ."</td>".
			"<td class=\"right\">". $this->supplyCount ." / ". $this->supplyCapacity ."</td>".
			(count($this->larvae) ? ("<td class=\"right\">". implode(", ", $this->larvae) ."</td>") : "").
			"<td class=\"left\">". $this->description ."</td>".
			"<td class=\"right\">". simple_round($this->mineralSurplus) ."</td>".
			"<td class=\"right\">". simple_round($this->gasSurplus) ."</td>".
			"<td class=\"center\">". (isset($this->energySurplus) ? implode(", ", $this->energySurplus) : "") ."</td>".
			"</tr>";
	}
};

/**
 * Timeline represents both the current state in the build order, and the
 * history of the jobs that have been handled.
 */
class Timeline {

	/// public members

	/**
	 * List of unhandled checkpoints.
	 * @var array
	 */
	public $checkpoints = array();

	/**
	 * If true, will echo debug messages
	 * @var bool
	 */
	public $debug = false;
	
	/**
	 * List of current farms.
	 * @var Farms
	 */
	public $farms;
	
	/**
	 * List of current hatcheries.
	 * @var Hatcheries
	 */
	public $hatcheries;

	/**
	 * List of current income slots.
	 * @var IncomeSlots
	 */
	public $income;

	/**
	 * List of current production queues.
	 * @var ProductionQueues
	 */
	public $queues;

	/**
	 * Race of the build order.
	 * @var int
	 */
	public $race;

	/**
	 * List of current spellcasters.
	 * @var Spellcasters
	 */
	public $spellcasters;

	/**
	 * Number of seconds to initially not build.
	 * @var int
	 */
	public $startupBuildDelay;

	/**
	 * Current supply count.
	 * @var int
	 */
	public $supplyCount = 0;

	/// private members

	/**
	 * List of events, representing handled jobs and checkpoints.
	 * @var array
	 */
	private $_events = array();
	
	/// constructor

	/**
	 * Create a new timeline.
	 * @param int $race
	 */
	public function __construct($race) {
		$this->race = $race;
		$this->farms = new Farms();
		$this->hatcheries = new Hatcheries();
		$this->income = new IncomeSlots();
		$this->queues = new ProductionQueues();
		$this->spellcasters = new Spellcasters();
	}

	/// operators

	/**
	 * Convert to a string.
	 * @return string
	 */
	public function __tostring() {
		return "<table id=\"pool\" class=\"display\" cellpadding=0 cellspacing=0>".
			"<thead>".
				"<tr>".
					"<th>#</th>".
					"<th>Started</th>".
					"<th>Completed</th>".
					"<th>Supply</th>".
					($this->race == Zerg ? "<th>Larvae</th>" : "") .
					"<th>Object</th>".
					"<th>Minerals</th>".
					"<th>Gas</th>".
					"<th>Energy</th>".
				"</tr>".
			"</thead>".
			"<tbody>".
				implode("", $this->_events) .
			"</tbody>".
		"</table>";
	}

	/// public methods
	
	/**
	 * Add a checkpoint to the timeline.
	 * @param Checkpoint $checkpoint
	 */
	public function addCheckpoint($checkpoint) {
		$this->checkpoints[] = $checkpoint;
		usort($this->checkpoints, array("Checkpoint", "compare"));
	}

	/**
	 * Calculate time when the given job can be scheduled.
	 * @param Job $job
	 * @return int
	 */
	public function calculate($job, $scheduledJobs) {
		global $SpawnLarvae;
		Logger::enter("Timeline::calculate");
		
		if($this->debug) tracemsg("Timeline::calculate(". $job .")");

		// start off optimistic
		$job->timeInitiated = 0;
		$job->timeStarted = $this->startupBuildDelay;

		// trigger supply is not met
		if(isset($job->triggerSupply) && $job->triggerSupply != $this->supplyCount) {
			if($this->debug) tracemsg("Timeline::calculate(), trigger supply count is not met.");
			$job->availability = new Availability(Availability::InsufficientSupply);
			$job->availability->supplyCount = $this->supplyCount;
			$job->availability->supplyNeeded = $job->triggerSupply;
			$job->timeStarted = INF;
			Logger::leave("Timeline::calculate");
			return $job->timeStarted;
		}

		// when are dependencies met
		if(isset($job->dependency)) {
			$found = false;
			foreach($scheduledJobs as $scheduledJob) {
				if($scheduledJob == $job->dependency->job) {
					$found = true;
					break;
				}
			}
			if(!$found) {
				$job->availability = new Availability(Availability::MissingDependency);
				$job->availability->missingDependency = $job->dependency->job;
				$job->timeStarted = INF;
				Logger::leave("Timeline::calculate");
				return $job->timeStarted;
			}
			$job->timeStarted = max($job->timeStarted, $job->dependency->type == Dependency::AtStart ? $job->dependency->job->timeStarted : $job->dependency->job->timeCompleted);
		}
		if($this->debug) tracemsg("Timeline::calculate(), dependency met at ". simple_time($job->timeStarted));

		// when are prerequisites met
		$prerequisites = $job->prerequisites();
		if($prerequisites !== null) {
			foreach($prerequisites as $prerequisite) {

				// skip base
				if($prerequisite->type & Base) {
					continue;
				}

				// find earliest job to meet prerequisite
				$prerequisiteMet = INF;
				foreach($scheduledJobs as $scheduledJob) {
					$productsCreated = $scheduledJob->productsCreated();
					if($productsCreated !== null) {
						foreach($productsCreated as $product) {
							if($product !== null && $product->uid == $prerequisite->uid) {
								$prerequisiteMet = min($prerequisiteMet, $scheduledJob->timeCompleted);
							}
						}
					}
				}
				if($prerequisiteMet === INF) {
					$job->availability = new Availability(Availability::MissingPrerequisite);
					$job->availability->missingPrerequisite = $prerequisite;
					$job->timeStarted = INF;
					Logger::leave("Timeline::calculate");
					return $job->timeStarted;
				}
				$job->timeStarted = max($job->timeStarted, $prerequisiteMet);
			}
		}
		if($this->debug) tracemsg("Timeline::calculate(), prerequisites met at ". simple_time($job->timeStarted));
		
		// when is spellcaster available
		if($job->energyCost() > 0) {
			$job->timeStarted = max($job->timeStarted, $this->spellcasters->when($job->spellcasterTypeExpended(), $job->energyCost(), $job->tagsRequired));
			if($this->debug) tracemsg("Timeline::calculate(), spellcaster available at ". simple_time($job->timeStarted));
			if($job->timeStarted === INF) {
				$job->availability = new Availability(Availability::MissingSpellcaster);
				$job->availability->missingSpellcaster = $job->spellcasterTypeExpended();
				if(isset($job->tagsRequired)) {
					$job->availability->tagsRequired = $job->tagsRequired;
				}
				Logger::leave("Timeline::calculate");
				return $job->timeStarted;
			}
		}
		
		// when is larva available
		if($job->larvaCost() > 0) {
			$job->timeStarted = max($job->timeStarted, $this->hatcheries->when($job->larvaCost(), $job->tagsRequired));
			if($this->debug) tracemsg("Timeline::calculate(), larva available at ". simple_time($job->timeStarted));
			if($job->timeStarted === INF) {
				$job->availability = new Availability(Availability::NoLarvaProduction);
				if(isset($job->tagsRequired)) {
					$job->availability->tagsRequired = $job->tagsRequired;
				}
				Logger::leave("Timeline::calculate");
				return $job->timeStarted;
			}
		}

		// when is supply capacity available
		if($job->supplyCost(false) > 0) {
			$job->timeStarted = max($job->timeStarted, $this->farms->when($this->supplyCount + $job->supplyCost(true)));
			if($this->debug) tracemsg("Timeline::calculate(), supply capacity available at ". simple_time($job->timeStarted));
			if($job->timeStarted === INF) {
				$job->availability = new Availability(Availability::InsufficientSupplyCapacity);
				Logger::leave("Timeline::calculate");
				return $job->timeStarted;
			}
		}

		// when are production queues available
		list($queueTypesExpended, $expendAll) = $job->queueTypesExpended();
		if($queueTypesExpended !== null) {
			list($time, $unavailableQueues) = $this->queues->when($queueTypesExpended, $expendAll, $job->tagsRequired);
			$job->timeStarted = max($job->timeStarted, $time);
			if($this->debug) tracemsg("Timeline::calculate(), production queues available at ". simple_time($time));
			if($job->timeStarted === INF) {
				$job->availability = new Availability(Availability::MissingProductionQueue);
				$job->availability->missingQueues = $unavailableQueues;
				if(isset($job->tagsRequired)) {
					$job->availability->tagsRequired = $job->tagsRequired;
				}
				Logger::leave("Timeline::calculate");
				return $job->timeStarted;
			}
		}

		// for spawn larvae, delay until a hatchery is vomit-free
		$productsCreated = $job->productsCreated();
		if($productsCreated !== null && count($productsCreated) > 0 && $productsCreated[0]->uid == $SpawnLarvae->uid) {
			$job->timeStarted = max($job->timeStarted, $this->hatcheries->whenVomit());
		}

		// delay transferring workers to gas until there is room for them
		$job->timeStarted = max($job->timeStarted, $job->when($this->income));

		// when are there enough resources to send worker
		$initiateGas = isset($job->initiateGas) ? $job->initiateGas : $job->gasCost();
		$initiateMineral = isset($job->initiateMineral) ? $job->initiateMineral : $job->mineralCost();
		$job->timeInitiated = $this->income->when($initiateMineral, $initiateGas);
		if($this->debug) tracemsg("Timeline::calculate(), Job ". $job ." can initiate". (isset($job->initiateMineral) ? (" when ". $job->initiateMineral ." minerals are available") : "") ." at ". simple_time($job->timeInitiated));

		// when are there enough resources to start building
		$gasCost = isset($job->triggerGas) ? max($job->triggerGas, $job->gasCost()) : $job->gasCost();
		$mineralCost = isset($job->triggerMineral) ? max($job->triggerMineral, $job->mineralCost()) : $job->mineralCost();
		if($job->timeInitiated === INF) {
			$job->timeStarted = INF;
		} elseif(isset($job->initiateGas) || isset($job->initiateMineral)) {
			$income = clone $this->income;
			$mutation = new Mutation(-1);
			$mutation->time = $job->timeInitiated;
			$income->splice($mutation);
			$job->timeStarted = max($job->timeStarted, $income->when($mineralCost, $gasCost));
		} else {
			$job->timeStarted = max($job->timeStarted, $this->income->when($mineralCost, $gasCost));
		}
		
		// no gas is being produced
		if($this->debug) tracemsg("Timeline::calculate(), ". $mineralCost ." Minerals and ". $gasCost ." gas available at ". simple_time($job->timeStarted));
		if($job->timeStarted === INF) {
			$job->availability = new Availability(Availability::NoGasProduction);
			Logger::leave("Timeline::calculate");
			return $job->timeStarted;
		}

		if($this->debug) tracemsg("Timeline::calculate(), Job ". $job ." can start at ". simple_time($job->timeStarted));
		$job->availability = new Availability(Availability::Available);
		Logger::leave("Timeline::calculate");
		return $job->timeStarted;
	}

	/**
	 * Determine if the job can be accomodated before the fixed job, without
	 * stalling the fixed job.
	 * @param Job $job
	 * @param Job $fixedJob 
	 * @return bool
	 */
	public function canAccommodate($job, $fixedJob) {
		if($this->debug || Hatcheries::$debug) tracemsg ("Timeline::canAccommodate(". $job .", ". $fixedJob .")");

		// only jobs that could clash
		if($job->larvaCost() == 0 && $job->energyCost() == 0 && $job->queueTypesExpended() === null) {
			return true;
		}
		if($fixedJob->larvaCost() == 0 && $fixedJob->energyCost() == 0 && $fixedJob->queueTypesExpended() === null) {
			return true;
		}

		// remember previous queues & spellcasters state
		$holdHatcheries = $this->hatcheries;
		$holdQueues = $this->queues;
		$holdSpellcasters = $this->spellcasters;
		$this->hatcheries = clone $this->hatcheries;
		$this->queues = clone $this->queues;
		$this->spellcasters = clone $this->spellcasters;

		// can we use larvae without delaying fixed job?
		if($job->larvaCost() > 0) {
			$this->hatcheries->expend($job->timeStarted, $job->larvaCost(), $job->tagsRequired);
		}
		if($fixedJob->larvaCost() > 0) {
			$larvaeAvailable = $this->hatcheries->when($fixedJob->larvaCost(), $fixedJob->tagsRequired);
		} else {
			$larvaeAvailable = -INF;
		}

		// can we use production queues without delaying fixed job?
		$this->queue($job, $job->timeStarted, true);
		list($queueTypesExpended, $expendAll) = $job->queueTypesExpended();
		if($queueTypesExpended !== null) {
			list($queuesAvailable, $unused) = $this->queues->when($queueTypesExpended, $expendAll, $fixedJob->tagsRequired);
		} else {
			$queuesAvailable = -INF;
		}

		// can we use spellcaster without delaying fixed job?
		if($job->energyCost() > 0) {
			$this->spellcasters->update($job->timeStarted);
			$this->spellcasters->expend($job->spellcasterTypeExpended(), $job->energyCost(), $job->timeStarted, $job->tagsRequired);
		}
		if($fixedJob->energyCost() > 0) {
			$spellcasterAvailable = $this->spellcasters->when($fixedJob->spellcasterTypeExpended(), $fixedJob->energyCost(), $fixedJob->tagsRequired);
		} else {
			$spellcasterAvailable = -INF;
		}

		// reinstate remembered queues & spellcasters state
		$this->hatcheries = $holdHatcheries;
		$this->queues = $holdQueues;
		$this->spellcasters = $holdSpellcasters;

		return
			$larvaeAvailable <= $fixedJob->timeStarted &&
			$queuesAvailable <= $fixedJob->timeStarted &&
			$spellcasterAvailable <= $fixedJob->timeStarted;
	}

	/**
	 * Log an event.
	 * @param string $description
	 * @param float $timeStarted
	 * @param float $timeCompleted
	 */
	public function log($description, $timeStarted, $timeCompleted) {
		$event = new Event();

		$event->description = $description;
		$energySurplus = $this->spellcasters->surplus(null, $timeStarted);
		foreach($energySurplus as $energy) {
			$event->energySurplus[] = simple_round($energy);
		}
		list($event->gasSurplus, $event->mineralSurplus) = $this->income->surplus($timeStarted);
		$event->larvae = $this->hatcheries->surplus($timeStarted);
		$event->order = count($this->_events);
		//tracemsg("Logging ". $description .", supply capacity is ". $this->farms->surplus($timeStarted) ." at ". simple_time($timeStarted));
		$event->supplyCapacity = $this->farms->surplus($timeStarted);
		$event->supplyCount = $this->supplyCount;
		$event->timeCompleted = $timeCompleted;
		$event->timeStarted = $timeStarted;

		$this->_events[] = $event;
	}

	/**
	 * Process a single job, update the timeline accordingly, and handle all
	 * job-specific tasks.
	 * @global Product $SpawnLarvae
	 * @global Product $Warpgate
	 * @global Product $ScoutingWorker
	 * @param Job $job
	 * @param bool $intheFuture
	 */
	public function process($job, $intheFuture = false) {
		global $SpawnLarvae, $Warpgate, $ScoutingWorker;
		Logger::enter("Timeline::process");
		if($this->debug || Hatcheries::$debug) tracemsg("Timeline::process(". $job .", ". ($intheFuture ? "true": "false") .")");

		// reset time completed
		$job->timeCompleted = INF;

		// handle mutations up to job start
		foreach($job->mutations() as $mutation) {
			if($mutation->time < $job->timeStarted) {
				$this->income->splice($mutation);
			}
		}

		// handle checkpoints up to job start
		if(!$intheFuture) {
			$this->processCheckpoints($job->timeStarted);
		}

		// update all
		if(!$intheFuture) {
			$this->update($job->timeStarted);
		}
		
		// expend resources
		$this->income->expend($job->mineralCost(), $job->gasCost());
		
		// when is job completed
		$job->timeCompleted = $job->timeStarted + $job->duration();

		// refund resources
		$this->income->expend(-$job->gasRefund(), 0);
		$this->income->expend(-$job->mineralRefund(), 0);
		
		// use production queues
		list($queueTypesExpended, $expendAll) = $job->queueTypesExpended();
		if($queueTypesExpended !== null) {
			list($job->timeCompleted, $queues) = $this->queue($job);
		}

		// use energy
		if($job->energyCost() > 0) {
			$this->spellcasters->expend($job->spellcasterTypeExpended(), $job->energyCost(), $job->timeStarted, $job->tagsRequired);
		}

		// special case: build is complete in 5 seconds when using a warpgate
		if(isset($queues) && count($queues) == 1) {
			if($queues[0]->structure == $Warpgate) {
				$job->timeCompleted = $job->timeStarted + 5;
			}
		}

		// use larva
		if($job->larvaCost() > 0) {
			$this->hatcheries->expend($job->timeStarted, $job->larvaCost(), $job->tagsRequired);
		}

		// new products
		if($job->productsCreated() !== null) {
			foreach($job->productsCreated() as $product) {
				if($product !== null) {

					// spawn larvae
					if($product->uid == $SpawnLarvae->uid) {
						$this->hatcheries->vomit($job->timeStarted);
					}

					// new hatchery
					if(($product->type & Base) && ($product->type & Zerg)) {
						$this->hatcheries->add(new Hatchery($job->timeCompleted, 1, $job->tag));
					}

					// new spellcaster
					if($product->type & Spellcaster) {
						$this->spellcasters->add(new Spellcaster($product, $job->timeCompleted, $job->tag));
					}

					// new farm
					if($product->supplyCapacity > 0) {
						//tracemsg("Adding farm ". $product ." at ". simple_time($job->timeCompleted));
						$this->farms->add(new Farm($job->timeCompleted, $product->supplyCapacity));
					}
				}
			}
		}

		// destroy products
		if($job->productsDestroyed() !== null) {
			foreach($job->productsDestroyed() as $product) {

				// destroy spellcaster
				if($product->type & Spellcaster) {
					$this->spellcasters->remove($product, $job->timeCompleted);
				}

				// destroy farm
				if($product->supplyCapacity > 0) {
					$this->farms->remove($product->supplyCapacity, $job->timeCompleted);
				}
			}
		}

		// process mutations
		foreach($job->mutations() as $mutation) {
			if($mutation->time >= $job->timeStarted) {
				$this->income->splice($mutation);
			}
		}

		// add or morph production queues
		$queueTypesCreated = $job->queueTypesCreated();
		if($queueTypesCreated !== null) {
			if(isset($queues) && $job->morph()) {
				$this->queues->morph($queues, $job->timeStarted, $queueTypesCreated, $job->timeCompleted);
			} else {
				foreach($queueTypesCreated as $queueType) {
					$this->queues->add(new ProductionQueue($queueType, $job->timeCompleted, $job->tag));
				}
			}
		}

		// create event
		if($intheFuture) {
			$this->addCheckpoint(new Checkpoint($job->description(), $job->timeStarted, $job->timeCompleted));
		} else {
			$this->log($job->description(), $job->timeStarted, $job->timeCompleted);
		}

		// update supply count
		if($this->debug) tracemsg("Timeline::process(), supply count = ". $this->supplyCount ." + ". $job->supplyCost(false) .".");
		$this->supplyCount += $job->supplyCost(false);
		Logger::leave("Timeline::process");
	}

	/**
	 * Process checkpoints in chronological order up to given time.
	 * @param float $time
	 */
	public function processCheckpoints($time = INF) {
		if($this->debug) tracemsg ("Timeline::processCheckpoints(". simple_time($time) .")");
		foreach($this->checkpoints as $key => $checkpoint) {
			if($checkpoint->timeStarted <= $time) {
				$this->update($checkpoint->timeStarted);
				$this->log($checkpoint->description, $checkpoint->timeStarted, $checkpoint->timeCompleted);
				unset($this->checkpoints[$key]);
			}
		}
	}

	/**
	 * Calculate time when job would be completed, and expend production queues.
	 * @global Product $ChronoBoost
	 * @global Product $Nexus
	 * @global Product $Warpgate
	 * @param Job $job
	 * @param float $time
	 * @param bool $tentative If true, chronoboosts will not be logged. Use this
	 * to perform dry runs of the queue use.
	 * @return array(int,array) First element is time job would be completed,
	 * second element is list of production queues used.
	 */
	public function queue($job, $time = null, $tentative = false) {
		global $ChronoBoost, $Nexus, $Warpgate;
		if($this->debug) tracemsg("Timeline::queue(". $job .", ". simple_time($time) .", ". ($tentative ? "true" : "false") .")");
		
		if($time === null) {
			$time = $job->timeStarted;
		}
		if($this->debug) tracemsg("Timeline::queue(), job starts at ". simple_time($time));

		// choose queues
		list($queueTypesExpended, $expendAll) = $job->queueTypesExpended();
		if($queueTypesExpended !== null) {
			$queues = $this->queues->choose($time, $queueTypesExpended, $expendAll, $job->tagsRequired);
		}

		// build time
		$buildTime = $job->duration();
		if(isset($queues) && count($queues) == 1 && $queues[0]->structure == $Warpgate) {
			$buildTime -= WARPGATE_QUEUE_REDUCTION;
		}

		// previous chrono boost overlaps this job
		if(isset($queues) && count($queues) == 1 && $queues[0]->chronoboosted + $ChronoBoost->timeCost > $time) {
			$boostTime = $queues[0]->chronoboosted;

			// calculate overlap with job
			$overlapStart = max($boostTime, $time);
			$overlapEnd = min($boostTime + $ChronoBoost->timeCost * CHRONO_BOOST_RATE, $time + $buildTime);
			$overlap = max(0, $overlapEnd - $overlapStart);

			// reduce build time
			$buildTime -= $overlap - $overlap / CHRONO_BOOST_RATE;
		}

		// chrono boosts
		if(isset($queues) && count($queues) == 1) {

			// process chronoboosts in an alternate reality
			$spellcasters = clone $this->spellcasters;
			for($i = 0; $i < $job->chronoboost; $i++) {

				// start time of chrono boost
				$boostTime = max($queues[0]->chronoboosted + $ChronoBoost->timeCost, $spellcasters->when($Nexus, $ChronoBoost->energyCost));
				if($boostTime < $time + $buildTime) {
					$boostTime = max($boostTime, $time + CHRONO_BOOST_HUMAN_DELAY);

					// calculate overlap with job
					$overlapStart = max($boostTime, $time);
					$overlapEnd = min($boostTime + $ChronoBoost->timeCost * CHRONO_BOOST_RATE, $time + $buildTime);
					$overlap = max(0, $overlapEnd - $overlapStart);

					// reduce build time
					$buildTime -= $overlap - $overlap / CHRONO_BOOST_RATE;

					// expend spellcasters
					$spellcasters->update($boostTime);
					$spellcasters->expend($Nexus, $ChronoBoost->energyCost, $boostTime);

					// log chronoboost & reserve energy
					if(!$tentative) {
						$this->addCheckpoint(new Checkpoint("<em>CB: ". $job->description() ."</em>", $boostTime, $boostTime + $ChronoBoost->timeCost));
						$spellcaster = $this->spellcasters->reserve($Nexus, $ChronoBoost->energyCost, $boostTime);
					}

					// queue is now chrono boosted
					$queues[0]->chronoboosted = $boostTime;
				}
			}
		}

		// build complete
		$completed = $time + $buildTime;

		// queue is now unavailable
		if(isset($queues)) {
			foreach($queues as $queue) {
				$queue->busy($time, $completed, $job->busiesQueues());
			}
			return array($completed, $queues);
		} else {
			return array($completed, null);
		}
	}

	/**
	 * Update all things up to the given time.
	 * @param float $time
	 */
	public function update($time) {
		
		// update resources
		$this->income->update($time);
		
		// update hatcheries
		$this->hatcheries->update($time);
		
		// update spellcasters
		$this->spellcasters->update($time);
		
		// update production queues
		$this->queues->update($time);
	}

	/**
	 * Calculate time when job would be completed.
	 * @param Job $job
	 * @return array(int,array) First element is time job would be completed,
	 * second element is list of production queues used.
	 */
	public function whenComplete($job) {

		// remember previous queues & spellcasters state
		$holdQueues = clone $this->queues;
		$holdSpellcasters = clone $this->spellcasters;

		// try to accomodate job before fixed job
		$result = $this->queue($job, $job->timeStarted, true);

		// reinstate remembered queues & spellcasters state
		$this->queues = $holdQueues;
		$this->spellcasters = $holdSpellcasters;

		return $result;
	}
};
?>