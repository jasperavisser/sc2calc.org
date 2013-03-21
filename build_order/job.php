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
 * Availability of a job at a given moment while scheduling
 */
class Availability {

	/// class constants
	const Available						= 0;
	const InsufficientSupply			= 1;
	const InsufficientSupplyCapacity	= 2;
	const NoGasProduction				= 3;
	const NoLarvaProduction				= 4;
	const NoMineralProduction			= 5;
	const MissingDependency				= 6;
	const MissingPrerequisite			= 7;
	const MissingProductionQueue		= 8;
	const MissingSpellcaster			= 9;

	/// public members

	/**
	 * @var Job Previous job which is not scheduled
	 */
	public $missingDependency;

	/**
	 * @var Product Product which has not already been built, but which is
	 * required for this job
	 */
	public $missingPrerequisite;

	/**
	 * @var array Products which represent production queues that this job
	 * needs, but which have not been built yet
	 */
	public $missingQueues;

	/**
	 * @var Product Type of spellcaster required to execute this job, and which
	 * has not been built yet
	 */
	public $missingSpellcaster;

	/**
	 * List of tags that indicates which production queues can be used
	 * @var array
	 */
	public $tagsRequired;

	/**
	 * @var int Status of availability
	 */
	public $status;

	/**
	 * @var int Current supply count
	 */
	public $supplyCount;

	/**
	 * @var int Supply count needed by this job's trigger
	 */
	public $supplyNeeded;

	/// constructor

	/**
	 * Create new availability.
	 * @param string $status
	 */
	public function  __construct($status) {
		$this->status = $status;
	}

	/// operators

	/**
	 * Convert this to string.
	 * @return string
	 */
	public function  __tostring() {
		switch($this->status) {
			case Availability::Available:
				return "";
				
			case Availability::InsufficientSupply:
				return "There is ". ($this->supplyCount > $this->supplyNeeded ? "too much" : "insufficient") ." supply.";

			case Availability::InsufficientSupplyCapacity:
				return "There is insufficient supply capacity.";

			case Availability::NoGasProduction:
				return "No gas is being mined.";

			case Availability::NoLarvaProduction:
				$result = "No larva are being generated";
				$result .= (isset($this->tagsRequired) ? (" from a hatchery with tag". (count($this->tagsRequired) > 1 ? "s" : "") ." #". implode(" or #", $this->tagsRequired)) : "") .".";
				return $result;

			case Availability::NoMineralProduction:
				return "No minerals are being mined.";

			case Availability::MissingDependency:
				return "The job <i>". $this->missingDependency ."</i> on which it depends could not be scheduled.";

			case Availability::MissingPrerequisite:
				return "The prerequisite <i>". $this->missingPrerequisite ."</i> does not exist.";

			case Availability::MissingProductionQueue:
				$result = "No production queue". (count($this->missingQueues) > 1 ? "s" : "") .
					" of type ";
				for($i = 0; $i < count($this->missingQueues); $i++) {
					$result .= ($i > 0 ? (($i == count($this->missingQueues) - 1) ? " and " : ", ") : "").
						"<i>". $this->missingQueues[$i]. "</i>";
				}
				$result .= (count($this->missingQueues) > 1 ? " exist" : " exists");
				$result .= (isset($this->tagsRequired) ? (" with tag". (count($this->tagsRequired) > 1 ? "s" : "") ." #". implode(" or #", $this->tagsRequired)) : "") .".";
				return $result;

			case Availability::MissingSpellcaster:
				return "No spellcasters of type <i>". $this->missingSpellcaster ."</i> exist".
					(isset($this->tagsRequired) ? (" with tag". (count($this->tagsRequired) > 1 ? "s" : "") ." #". implode(" or #", $this->tagsRequired)) : "") .".";
		}
	}

	/// public methods

	/**
	 * Get a descriptive text that helps the user understand the error
	 * @return string
	 */
	public function description() {
		switch($this->status) {
			case Availability::Available:
				return "";
				
			case Availability::InsufficientSupply:
				return "The trigger supply count for this job is ". $this->supplyNeeded .", but at this point in the build order the achieved supply count is ". ($this->supplyCount > $this->supplyNeeded ? "already" : "only") ." ". $this->supplyCount .".";

			case Availability::InsufficientSupplyCapacity:
				return "You may need to add some Overlords, Supply Depots or Pylons to accommodate it.";

			case Availability::NoGasProduction:
				return "Usually, this means that you didn't put workers on gas. It could also be that you took workers off gas before enough gas was gathered. To put workers on gas when you build an assimilator, write <em>12 Assimilator &gt; transfer 3 workers</em> or <em>12 Assimilator &gt; +3</em>. Similarly for a Refinery or an Extractor.";

			case Availability::NoLarvaProduction:
				return "This error message should not occur. Please report this message with your build order on the thread linked at bottom of the page.";

			case Availability::NoMineralProduction:
				return "You may have taken all remaining workers off minerals, or used up all your Drones to build structures.";

			case Availability::MissingDependency:
				return "";

			case Availability::MissingPrerequisite:
				return "You must ensure that the prerequisite structure or upgrade can be scheduled before this job.";

			case Availability::MissingProductionQueue:
				return "You must ensure that the required production queue exists before this job.";

			case Availability::MissingSpellcaster:
				return "You must ensure that the required spellcaster exists before this job.";
		}
	}

	/**
	 * Determine if the given job could solve the reason of this unavailability.
	 * @param Job $job
	 * @return bool True, if the given job can actively contribute to making
	 * this job available
	 */
	public function solvedBy($job) {
		switch($this->status) {
			case Availability::Available:
				return true;

			// job must affect supply
			case Availability::InsufficientSupply:
				$supplyGap = $this->supplyNeeded - $this->supplyCount;
				if($supplyGap > 0) {
					return $job->supplyCost(true) <= $supplyGap && $job->supplyCost(true) > 0;
				} elseif($supplyGap < 0) {
					return $job->supplyCost(true) >= $supplyGap && $job->supplyCost(true) < 0;
				}
				return true;

			// job must increase supply capacity
			case Availability::InsufficientSupplyCapacity:
				$productsCreated = $job->productsCreated();
				if($productsCreated !== null) {
					foreach($productsCreated as $product) {
						if($product !== null && $product->supplyCapacity > 0) {
							return true;
						}
					}
				}
				return false;

			case Availability::NoGasProduction:
				return false;
			case Availability::NoLarvaProduction:
				return false;
			case Availability::NoMineralProduction:
				return false;
			case Availability::MissingDependency:
				return false;
			case Availability::MissingPrerequisite:
				return false;
			case Availability::MissingProductionQueue:
				return false;
			case Availability::MissingSpellcaster:
				return false;
			default:
				throw_error("Job was unavailable, but no reason was specified.");
		}
	}
};

/**
 * Dependency of a job on another job.
 */
class Dependency {

	/// class constants
	const AtCompletion	= 0;
	const AtStart		= 1;

	/// public members

	/**
	 * @var Job Previous job on which it depends
	 */
	public $job;

	/**
	 * @var int Whether the job can be scheduled after the start or completion
	 * of the previous job.
	 */
	public $type;
	
	/// constructor

	/**
	 * Create a new dependency.
	 * @param Job $job
	 * @param int $type
	 */
	public function __construct($job, $type) {
		$this->job = $job;
		$this->type = $type;
	}
};

/**
 * Jobs are the basic components of a build order. They represent, for example,
 * the construction of a unit, structure, or a mutation in income.
 */
abstract class Job {

	/// public members

	/**
	 * Availability of this job
	 * @var Availability
	 */
	public $availability;

	/**
	 * Number of chronoboosts to use on this job
	 * @var int
	 */
	public $chronoboost;

	/**
	 * Previous job which must be scheduled before this one
	 * @var Dependency 
	 */
	public $dependency;

	/**
	 * Amount of gas at which to initiate this job, i.e. send the worker early
	 * @var float
	 */
	public $initiateGas;

	/**
	 * Amount of mineral at which to initiate this job, i.e. send the worker early
	 * @var float
	 */
	public $initiateMineral;

	/**
	 * Order in which the jobs were picked up by the scheduler
	 * @var int
	 */
	public $pickOrder;

	/**
	 * Additional queue type expended by this job.
	 * @var Product
	 */
	public $queueTypeExpended;

	/**
	 * If true, this job will be scheduled repeatedly until cancelled
	 * @var bool
	 */
	public $recurring;

	/**
	 * @deprecated
	 * @var bool
	 */
	public $superPriority;

	/**
	 * Tag that can be referred to by other jobs
	 * @var string
	 */
	public $tag;

	/**
	 * Tags of queues or spellcasters that can be used to perform this job
	 * @var array
	 */
	public $tagsRequired;

	/**
	 * Time when the job is completed
	 * @var float
	 */
	public $timeCompleted = INF;

	/**
	 * Time when the job is initiated, i.e. when the worker is dispatched
	 * @var float
	 */
	public $timeInitiated = INF;

	/**
	 * Time when the job is started
	 * @var float
	 */
	public $timeStarted = INF;

	/**
	 * Type of job, see class constants
	 * @var int
	 */
	public $type;

	/**
	 * Amount of gas that triggers the start of the job
	 * @var float
	 */
	public $triggerGas;

	/**
	 * Amount of mineral that triggers the start of the job
	 * @var float
	 */
	public $triggerMineral;

	/**
	 * Amount of supply that triggers the start of the job
	 * @var int
	 */
	public $triggerSupply;
	
	/// operators

	/**
	 * Convert to a string.
	 * @return string
	 */
	public function __toString() {
		return
			(isset($this->triggerGas) ? ("@". $this->triggerGas ." gas ") : "") .
			(isset($this->triggerMineral) ? ("@". $this->triggerMineral ." minerals ") : "") .
			(isset($this->triggerSupply) ? ($this->triggerSupply ." ") : "") .
			$this->description() .
			($this->recurring ? " [auto]" : "");
	}
	
	/// public methods

	/**
	 * Indicates whether any production queues expended by the job must be
	 * tagged as busy.
	 * @return bool
	 */
	public function busiesQueues() {
		return false;
	}

	/**
	 * Cancel all recurring jobs that are targetted by this job.
	 * @param array $recurringJobs
	 */
	public function cancel(&$recurringJobs) {
	}

	/**
	 * Indicates whether the job consumes any scarce resources, including time.
	 * @return bool
	 */
	public function consumptive() {
		return true;
	}

	/**
	 * Get short description of this job.
	 * @return string
	 */
	abstract public function description();

	/**
	 * Get the duration of the job.
	 * @return float
	 */
	public function duration() {
		return 0;
	}

	/**
	 * Get energy cost of this job.
	 * @return float
	 */
	public function energyCost() {
		return 0;
	}

	/**
	 * Get gas cost of this job.
	 * @return float
	 */
	public function gasCost() {
		return 0;
	}

	/**
	 * Amount of gas refunded after this job is completed.
	 * @return float
	 */
	public function gasRefund() {
		return 0;
	}

	/**
	 * Get larva cost of this job.
	 * @return int
	 */
	public function larvaCost() {
		return 0;
	}

	/**
	 * Get mineral cost of this job.
	 * @return float
	 */
	public function mineralCost() {
		return 0;
	}

	/**
	 * Amount of mineral refunded after this job is completed.
	 * @return float
	 */
	public function mineralRefund() {
		return 0;
	}

	public function morph() {
		return false;
	}

	/**
	 * Get all mutations to income that are caused by this job.
	 * @global Product $CalldownMULE
	 * @return Mutations
	 */
	public function mutations() {
		global $CalldownMULE;

		// mutations caused by created products
		$mutations = new Mutations();

		if($this->productsCreated() !== null) {
			foreach($this->productsCreated() as $product) {
				if($product === null) {
					continue;
				}

				// worker is produced
				if($product->type & Worker) {
					$mutations->add(new Mutation(1, 0), $this->timeCompleted);
				}

				// new base is produced
				if($product->type & Base) {
					$mutations->add(new BaseStartedMutation(), $this->timeStarted);
					$mutations->add(new BaseCompletedMutation(), $this->timeCompleted);
				}

				// new geyser is developed
				if($product->type & Geyser) {
					$mutations->add(new GeyserStartedMutation(), $this->timeStarted);
					$mutations->add(new GeyserCompletedMutation(), $this->timeCompleted);
				}

				// MULE
				if($product->uid == $CalldownMULE->uid) {
					$mutations->add(new MULEMutation(1), $this->timeStarted);
					$mutations->add(new MULEMutation(-1), $this->timeCompleted);
				}
			}

			$mutations->sort();
		}
		return $mutations;
	}

	/**
	 * List of prerequisite structures and upgrades for this job.
	 * @return array
	 */
	public function prerequisites() {
		return null;
	}

	/**
	 * @todo Deprecate this.
	 * @return Product
	 */
	public function productBuilt() {
		return null;
	}

	/**
	 * List of products created by this job.
	 * @return array
	 */
	public function productsCreated() {
		return null;
	}

	/**
	 * List of products destroyed by this job.
	 * @return array
	 */
	public function productsDestroyed() {
		return null;
	}

	/**
	 * List of production queue types created by this job.
	 * @return array
	 */
	public function queueTypesCreated() {
		return null;
	}

	/**
	 * Get list of expended queue types.
	 * @return array
	 */
	public function queueTypesExpended() {
		if(isset($this->queueTypeExpended)) {
			return array($this->queueTypeExpended, false);
		}
		return array(null, false);
	}

	/**
	 * Race of this job.
	 * @return int
	 */
	public function race() {
		return null;
	}

	/**
	 * Get expended spellcaster types.
	 * @return Product
	 */
	public function spellcasterTypeExpended() {
		return null;
	}

	/**
	 * Get supply cost of this job.
	 * @param bool $allowTrick If true, don't report supply cost for tricks
	 * @return int
	 */
	public function supplyCost($allowTrick = false) {
		return 0;
	}

	/**
	 * Calculate earliest time when income allows this job to be performed.
	 * @param IncomeSlots $income
	 * @return float
	 */
	public function when($income) {
		return 0;
	}
};

class BuildJob extends Job {

	/// private members

	/**
	 * Product to be built by this job.
	 * @var Product
	 */
	private $_product;

	/// constructor

	/**
	 * Create a new Build job.
	 * @param Product $product
	 */
	public function __construct($product) {
		$this->_product = $product;
	}

	/// public methods
	public function busiesQueues() {
		return !($this->_product->type & Morph);
	}

	public function description() {
		if($this->_product->type & Ability) {
			return "<em>". (string)$this->_product ."</em>";
		} else {
			return (string)$this->_product;
		}
	}

	public function duration() {
		return $this->_product->timeCost;
	}

	public function energyCost() {
		return $this->_product->energyCost;
	}

	public function gasCost() {
		return $this->_product->gasCost;
	}

	public function larvaCost() {
		return $this->_product->larvaCost;
	}

	public function mineralCost() {
		return $this->_product->mineralCost;
	}

	public function morph() {
		return (bool)($this->_product->type & Morph);
	}

	public function mutations() {
		$mutations = parent::mutations();
		
		// occupy worker
		if($this->_product->type & Structure) {

			// when does worker leave
			$workerLeaves = $this->timeInitiated;
			$travelTime = $this->timeStarted - $this->timeInitiated;

			// when does worker return
			switch($this->_product->type & (Protoss | Terran | Zerg)) {
			case Protoss:
				$workerReturns = $this->timeStarted + $travelTime;
				break;
			case Terran:
				$workerReturns = $this->timeCompleted + $travelTime;
				break;
			case Zerg:
				$workerReturns = INF;
				break;
			}

			// splice income
			if($workerLeaves != $workerReturns) {
				$mutations->add(new Mutation(-1, 0), $workerLeaves);
				if($workerReturns !== INF) {
					$mutations->add(new Mutation(1, 0), $workerReturns);
				}
			}
		}

		$mutations->sort();
		return $mutations;
	}

	public function prerequisites() {
		return $this->_product->prerequisites;
	}

	public function productBuilt() {
		return $this->_product;
	}

	public function productsCreated() {
		if($this->_product->type & Morph) {
			return $this->_product->yields;
		} else {
			return array($this->_product);
		}
	}

	public function queueTypesCreated() {
		if(($this->_product->type & Structure) || ($this->_product->type & Spellcaster)) {
			return array($this->_product);
		} elseif($this->_product->type & Morph) {
			return $this->_product->yields;
		}
	}

	public function queueTypesExpended() {
		if(isset($this->_product->expends)) {
			$queueTypes = $this->_product->expends;
			$expendAll = $this->_product->expendsAll;
		} else {
			$queueTypes = array();
			$expendAll = false;
		}
		if(isset($this->queueTypeExpended)) {
			$queueTypes[] = $this->queueTypeExpended;
		}
		$queueTypes = count($queueTypes) == 0 ? null : $queueTypes;
		return array($queueTypes, $expendAll);
	}

	public function race() {
		return $this->_product->race();
	}

	public function spellcasterTypeExpended() {
		return $this->_product->spellcaster;
	}

	public function supplyCost($allowTrick = false) {
		return $this->_product->supplyCost;
	}
};

class CancelJob extends Job {

	/// private members

	/**
	 * Type of product of which to cancel recurring jobs.
	 * @var Product
	 */
	private $_cancelledProduct;

	/// constructor

	/**
	 * Create a new Cancel job.
	 * @param Product $cancelledProduct
	 */
	public function __construct($cancelledProduct) {
		$this->_cancelledProduct = $cancelledProduct;
	}

	/// public methods
	public function cancel(&$recurringJobs) {
		$cancelled = false;
		foreach($recurringJobs as $key => $recurringJob) {
			if($recurringJob->productBuilt() !== null && $recurringJob->productBuilt()->uid == $this->_cancelledProduct->uid) {
				unset($recurringJobs[$key]);
				$cancelled = true;
			}
		}
		if(!$cancelled) {
			throw_error("There is no recurring job for ". $this->_cancelledProduct->name ." to be cancelled.", "The cancel command can only be used to cancel recurring jobs, like <em>16 Marine [auto]</em>.");
		}
	}

	public function description() {
		return "Cancel ". (string)$this->_cancelledProduct;
	}

	public function duration() {
		return 0;
	}

	public function mutations() {
		return new Mutations();
	}
};

class MutateJob extends Job {

	/// private members

	/**
	 * Mutation associated with a mutation job
	 * @var Mutation
	 */
	private $_mutation;

	/// constructor

	/**
	 * Create a new Mutation job.
	 * @param Mutation $mutation
	 */
	public function __construct($mutation) {
		$this->_mutation = $mutation;
	}

	/// public methods
	public function consumptive() {
		return false;
	}

	public function description() {
		return (string)$this->_mutation;
	}

	public function duration() {
		return $this->_mutation->delay;
	}

	public function mutations() {
		$mutations = new Mutations();
		$mutations->add($this->_mutation, $this->timeStarted);
		return $mutations;
	}
	
	public function when($income) {
		return $this->_mutation->when($this->timeStarted, $income);
	}
};

class ScoutJob extends MutateJob {

	/// constructor
	public function  __construct($delay = 0) {
		$mutation = new ScoutMutation();
		$mutation->delay = $delay;
		parent::__construct($mutation);
	}

	/// public methods
	public function queueTypesCreated() {
		global $ScoutingWorker;
		return array($ScoutingWorker);
	}
};

class TrickJob extends Job {

	/// private members

	/**
	 * Type of structure to build, usually Extractor.
	 * @var Product
	 */
	private $_pledgeProduct;

	/**
	 * Number of pledge products to build.
	 * @var int
	 */
	private $_pledgeCount;

	/**
	 * Type of unit to build, usually Drone.
	 * @var Product
	 */
	private $_turnProduct;

	/**
	 * Number of turn products to build.
	 * @var int
	 */
	private $_turnCount;

	/// constructor

	/**
	 * Create a new Trick job.
	 * @param Product $pledgeProduct
	 * @param int $pledgeCount
	 * @param Product $turnProduct
	 * @param int $turnCount
	 */
	public function  __construct($pledgeProduct, $pledgeCount, $turnProduct, $turnCount) {
		$this->_pledgeProduct = $pledgeProduct;
		$this->_pledgeCount = $pledgeCount;
		if($turnProduct !== null) $this->_turnProduct = $turnProduct;
		$this->_turnCount = $turnCount;
	}

	/// public methods
	public function description() {
		global $Drone;
		switch($this->_pledgeCount) {
			case 1:
				$result = "";
				break;
			case 2:
				$result = "Double ";
				break;
			default:
			 throw_error("Er... what?");
		}
		if(isset($this->_turnProduct)) {
			$result .= $this->_pledgeProduct ." Trick";
		} else {
			$result .= "Fake ". $this->_pledgeProduct;
		}
		if($this->_turnCount != 0 && 
			($this->_turnCount != 1 || $this->_turnProduct->uid != $Drone->uid)) {
			$result .= " into ". $this->_turnCount ." ". $this->_turnProduct ."s";
		}
		return $result;
	}

	public function duration() {
		if(isset($this->_turnProduct)) {
			return $this->_turnProduct->timeCost;
		} else {
			return 0;
		}
	}

	public function gasCost() {
		return $this->_pledgeCount * $this->_pledgeProduct->gasCost +
			(isset($this->_turnProduct) ? ($this->_turnCount * $this->_turnProduct->gasCost) : 0);
	}

	public function gasRefund() {
		return $this->_pledgeCount * floor(3 * $this->_pledgeProduct->gasCost / 4);
	}

	public function larvaCost() {
		return $this->_pledgeCount * $this->_pledgeProduct->larvaCost +
			(isset($this->_turnProduct) ? ($this->_turnCount * $this->_turnProduct->larvaCost) : 0);
	}

	public function mineralCost() {
		return $this->_pledgeCount * $this->_pledgeProduct->mineralCost +
			(isset($this->_turnProduct) ? ($this->_turnCount * $this->_turnProduct->mineralCost) : 0);
	}

	public function mineralRefund() {
		return $this->_pledgeCount * floor(3 * $this->_pledgeProduct->mineralCost / 4);
	}
	
	public function prerequisites() {
		return array_merge($this->_pledgeProduct->prerequisites, $this->_turnProduct->prerequisites);
	}

	public function productsCreated() {
		return ($this->_turnCount != 0 && isset($this->_turnProduct)) ?
			array_fill(0, $this->_turnCount, $this->_turnProduct) : array();
	}

	public function race() {
		return $this->_pledgeProduct->race();
	}

	public function supplyCost($allowTrick = false) {
		if($allowTrick) {
			return $this->_pledgeCount * $this->_pledgeProduct->supplyCost +
				(isset($this->_turnProduct) ? ($this->_turnCount * $this->_turnProduct->supplyCost) : 0);
		} else {
			return isset($this->_turnProduct) ? ($this->_turnCount * $this->_turnProduct->supplyCost) : 0;
		}
	}
};

class KillJob extends Job {

	/// private members

	/**
	 * Product to be killed by this job.
	 * @var Product
	 */
	private $_product;

	/// constructor
	public function  __construct($product) {
		$this->_product = $product;
	}

	/// public methods
	public function description() {
		return "Kill ". (string)$this->_product;
	}

	public function productsDestroyed() {
		return array($this->_product);
	}

	public function supplyCost($allowTrick = false) {
		return -$this->_product->supplyCost;
	}
};

?>