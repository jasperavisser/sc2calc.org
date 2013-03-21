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
 * Mutation of the income, used to handle probe transfers, scouting, temporary
 * unavailability of workers, etc.
 */
class Mutation {

	/// public members

	/**
	 * Delay between negative and positive effect of mutation.
	 * @var float
	 */
	public $delay;

	/**
	 * Number of workers put on or taken off gas.
	 * @var int
	 */
	public $gasChange;

	/**
	 * Number of workers put on or taken off mineral.
	 * @var int
	 */
	public $mineralChange;

	/**
	 * Time at which this mutation occurs.
	 * @var float
	 */
	public $time;
	
	/// protected members

	/**
	 * Workers taken off gas, specified per geyser.
	 * @var array
	 */
	protected $_gasNegativeChange;

	/**
	 * Workers put on gas, specified per geyser.
	 * @var array
	 */
	protected $_gasPositiveChange;

	/**
	 * Workers taken off minerals, specified per base.
	 * @var array
	 */
	protected $_mineralNegativeChange;

	/**
	 * Workers put on minerals, specified per base.
	 * @var array
	 */
	protected $_mineralPositiveChange;
	
	/// constructor

	/**
	 * Create new mutation.
	 * @param int $mineralChange
	 * @param int $gasChange
	 */
	public function __construct($mineralChange = null, $gasChange = null) {
		$this->mineralChange = $mineralChange;
		$this->gasChange = $gasChange;
	}
	
	/// operators

	/**
	 * Convert to a string.
	 * @return string
	 */
	public function __tostring() {
		$changes = array();

		// describe transfer from mineral to gas or vice versa
		if($this->gasChange == -$this->mineralChange) {
			if($this->gasChange > 0) {
				return "Transfer ". $this->gasChange ." workers to gas";
			} else {
				return "Transfer ". $this->mineralChange ." workers to minerals";
			}
		}

		// describe gas change
		if(isset($this->_gasNegativeChange) && isset($this->_gasPositiveChange)) {
			foreach($this->_gasNegativeChange as $i => $change) {
				if($change != 0) {
					$changes[] = "-". $change ." workers on ". (count($this->_gasNegativeChange) > 1 ? (" on geyser #". $i) : "gas");
				}
			}
			foreach($this->_gasPositiveChange as $i => $change) {
				if($change != 0) {
					$changes[] = "+". $change ." workers on ". (count($this->_gasPositiveChange) > 1 ? (" on geyser #". $i) : "gas");
				}
			}
		} elseif(is_int($this->gasChange)) {
			$changes[] = ($this->gasChange > 0 ? "+" : "") . $this->gasChange ." workers on gas";
		}

		// describe mineral change
		if(isset($this->_mineralNegativeChange) && isset($this->_mineralPositiveChange)) {
			foreach($this->_mineralNegativeChange as $i => $change) {
				if($change != 0) {
					$changes[] = "-". $change ." workers on ". (count($this->_mineralNegativeChange) > 1 ? (" on geyser #". $i) : "minerals");
				}
			}
			foreach($this->_mineralPositiveChange as $i => $change) {
				if($change != 0) {
					$changes[] = "+". $change ." workers on ". (count($this->_mineralPositiveChange) > 1 ? (" on geyser #". $i) : "minerals");
				}
			}
		} elseif(is_int($this->mineralChange)) {
			$changes[] = ($this->mineralChange > 0 ? "+" : "") . $this->mineralChange ." workers on minerals";
		}

		return implode(", ", $changes);
	}

	/// public methods

	/**
	 * Apply this mutation to given income slot.
	 * @param IncomeSlot $slot
	 */
	public function apply($slot) {
		$this->applyNegative($slot);
		$this->applyPositive($slot);
	}

	/**
	 * Apply negative effect of this mutation to given income slot.
	 * @param IncomeSlot $slot
	 */
	public function applyNegative($slot) {
		$this->distribute($slot);
		for($i = 0; $i < count($this->_gasNegativeChange); $i++) {
			if(($slot->gasMiners[$i] += $this->_gasNegativeChange[$i]) < 0) {
				throw_error("Attempting to take workers off gas where there were none.",
					"Your build order contains a job that takes workers off gas, but you either didn't put workers on gas, or are trying to take more workers off gas than were put on. Most likely, the job that takes workers off gas was placed earlier in the queue than the job that puts the workers on gas. Ensure that the job that takes workers off gas must be queued later, for example by writing <em>@100 gas take 3 off gas</em>.");
			}
		}
		for($i = 0; $i < count($this->_mineralNegativeChange); $i++) {
			if(($slot->mineralMiners[$i] += $this->_mineralNegativeChange[$i]) < 0) {
				throw_error("Attempting to take workers off minerals where there were none.",
					"Either you're trying to put more workers on gas, or transfer more workers to a new Nexus than you have workers available.");
			}
		}
	}

	/**
	 * Apply positive effect of this mutation to given income slot.
	 * @param IncomeSlot $slot
	 */
	public function applyPositive($slot) {
		for($i = 0; $i < count($this->_gasPositiveChange); $i++) {
			$slot->gasMiners[$i] += $this->_gasPositiveChange[$i];
		}
		for($i = 0; $i < count($this->_mineralPositiveChange); $i++) {
			$slot->mineralMiners[$i] += $this->_mineralPositiveChange[$i];
		}
	}

	/**
	 * Choose distribution of workers taken off or put on resources per geyser
	 * or base, based on the given income slot. This distribution is then
	 * solidified, and applied to every subsequent income slot.
	 * @param IncomeSlot $slot
	 */
	public function distribute($slot) {
		
		// don't distribute twice
		if(isset($this->_gasNegativeChange) || isset($this->_mineralNegativeChange)) {
			return;
		}
		
		// auto-distribute miners on gas
		if(!empty($this->gasChange)) {
			
			// single geyser
			if(count($slot->gasMiners) == 0) {
				throw_error("You don't have any geysers!",
					"Workers can only be transferred to an Assimilator after the Assimilator has been completed. You can set this up by writing <em>13 Assimilator &gt; put 3 on gas</em>.");
				
			// single geyser
			} elseif(count($slot->gasMiners) == 1) {
				$gasChange = array($this->gasChange);
				
			// multiple geysers
			} else {
				$gasChange = array_fill(0, count($slot->gasMiners), 0);
				
				// take miners off gas
				if($this->gasChange < 0) {
					$left = -$this->gasChange;
					do {
						$mostSaturated = 0;
						for($i = 1; $i < count($slot->gasMiners); $i++) {
							if($slot->gasMiners[$i] + $gasChange[$i] > $slot->gasMiners[$mostSaturated] + $gasChange[$mostSaturated]) {
								$mostSaturated = $i;
							}
						}
						$gasChange[$mostSaturated]--;
					} while(--$left > 0);
				
				// put miners on gas
				} else {
					$left = $this->gasChange;
					do {
						$leastSaturated = 0;
						for($i = 1; $i < count($slot->gasMiners); $i++) {
							if($slot->gasMiners[$i] + $gasChange[$i] < $slot->gasMiners[$leastSaturated] + $gasChange[$leastSaturated]) {
								$leastSaturated = $i;
							}
						}
						$gasChange[$leastSaturated]++;
					} while(--$left > 0);
				}
			}
			
			// store changes
			$this->_gasNegativeChange = array();
			$this->_gasPositiveChange = array();
			for($i = 0; $i < count($gasChange); $i++) {
				$this->_gasNegativeChange[] = $gasChange[$i] < 0 ? $gasChange[$i] : 0;
				$this->_gasPositiveChange[] = $gasChange[$i] > 0 ? $gasChange[$i] : 0;
			}
		}
		
		// auto-distribute miners on minerals
		if(!empty($this->mineralChange)) {
			
			// single base
			if(count($slot->mineralMiners) == 1) {
				$mineralChange = array($this->mineralChange);
				
			// multiple bases
			} else {
				$mineralChange = array_fill(0, count($slot->mineralMiners), 0);
				
				// take miners off minerals
				if($this->mineralChange < 0) {
					$left = -$this->mineralChange;
					do {
						$mostSaturated = 0;
						for($i = 1; $i < count($slot->mineralMiners); $i++) {
							if($slot->mineralMiners[$i] + $mineralChange[$i] > $slot->mineralMiners[$mostSaturated] + $mineralChange[$mostSaturated]) {
								$mostSaturated = $i;
							}
						}
						$mineralChange[$mostSaturated]--;
					} while(--$left > 0);
				
				// put miners on minerals
				} else {
					$left = $this->mineralChange;
					do {
						$leastSaturated = 0;
						for($i = 1; $i < count($slot->mineralMiners); $i++) {
							if($slot->basesOperational[$i]) {
								if($slot->mineralMiners[$i] + $mineralChange[$i] < $slot->mineralMiners[$leastSaturated] + $mineralChange[$leastSaturated]) {
									$leastSaturated = $i;
								}
							}
						}
						$mineralChange[$leastSaturated]++;
					} while(--$left > 0);
				}
			}
			
			// store changes
			$this->_mineralNegativeChange = array();
			$this->_mineralPositiveChange = array();
			for($i = 0; $i < count($mineralChange); $i++) {
				$this->_mineralNegativeChange[] = $mineralChange[$i] < 0 ? $mineralChange[$i] : 0;
				$this->_mineralPositiveChange[] = $mineralChange[$i] > 0 ? $mineralChange[$i] : 0;
			}
		}
	}

	/**
	 * Calculate the earliest time when this mutation could be applied to the
	 * given income slots, after the given time.
	 * @param float $time
	 * @param IncomeSlots $income
	 * @return float
	 */
	public function when($time, $income) {

		//if putting drones on gas, delay until there is a geyser with <3 available
		if($this->gasChange > 0) {

			for($i = 0; $i < count($income); $i++) {
				$slot = $income[$i];
				if($slot->endTime > $time) {
					for($j = 0; $j < count($slot->gasMiners); $j++) {
						if($slot->geysersOperational[$j] && $slot->gasMiners[$j] < 3) {
							return $slot->startTime;
						}
					}
				}
			}

			return INF;
		}
	}

	/// public static methods

	/**
	 * Compare two mutations by time.
	 * @param Mutation $mutation1
	 * @param Mutation $mutation2
	 * @return int
	 */
	public static function compare($mutation1, $mutation2) {
		if($mutation1->time > $mutation2->time) {
			return 1;
		} elseif($mutation1->time < $mutation2->time) {
			return-1;
		}
		return 0;
	}
};

/**
 * Mutation that completes a new base.
 */
class BaseCompletedMutation extends Mutation {

	/// operators

	/**
	 * Convert to a string.
	 * @return string
	 */
	public function __tostring() {
		return "New base completed";
	}

	/// public methods

	/**
	 * Add new base to given income slot.
	 * @param IncomeSlot $slot
	 */
	public function apply($slot) {
		foreach($slot->basesOperational as $key => $baseOperational) {
			if(!$baseOperational) {
				$slot->basesOperational[$key] = true;
				return;
			}
		}
		throw_error("There is no base to be completed.");
	}
};

/**
 * Mutation that adds a new base to income slots.
 */
class BaseStartedMutation extends Mutation {

	/// operators

	/**
	 * Convert to a string.
	 * @return string
	 */
	public function __tostring() {
		return "New base started";
	}

	/// public methods

	/**
	 * Add new base to given income slot.
	 * @param IncomeSlot $slot
	 */
	public function apply($slot) {
		$slot->mineralMiners[] = 0;
		$slot->basesOperational[] = false;
	}
};

/**
 * Mutation that completes a new geyser.
 */
class GeyserCompletedMutation extends Mutation {

	/// operators

	/**
	 * Convert to a string.
	 * @return string
	 */
	public function __tostring() {
		return "New geyser completed";
	}

	/// public methods

	/**
	 * Add new geyser to given income slot.
	 * @param IncomeSlot $slot
	 */
	public function apply($slot) {
		foreach($slot->geysersOperational as $key => $geyserOperational) {
			if(!$geyserOperational) {
				$slot->geysersOperational[$key] = true;
				return;
			}
		}
		throw_error("There is no geyser to be completed.");
	}
};

/**
 * Mutation that adds a new geyser to income slots.
 */
class GeyserStartedMutation extends Mutation {

	/// operators

	/**
	 * Convert to a string.
	 * @return string
	 */
	public function __tostring() {
		return "New geyser started";
	}

	/// public methods

	/**
	 * Add new geyser to given income slot.
	 * @param IncomeSlot $slot
	 */
	public function apply($slot) {
		$slot->gasMiners[] = 0;
		$slot->geysersOperational[] = false;
	}
};

/**
 * Mutation that transfers workers from one resource to another.
 */
class TransferMutation extends Mutation {

	/// operators

	/**
	 * Convert to a string.
	 * @return string
	 */
	public function __tostring() {
		return 
			(empty($this->mineralChange) ? "" : ("Transfer ". $this->mineralChange . " workers to new base")).
			(empty($this->gasChange) ? "" : ("Transfer ". $this->gasChange . " workers to new geyser"));
	}

	/// public methods

	/**
	 * Choose distribution of workers taken off or put on resources per geyser
	 * or base, based on the given income slot. This distribution is then
	 * solidified, and applied to every subsequent income slot.
	 * @param IncomeSlot $slot
	 */
	public function distribute($slot) {
		
		if(isset($this->_gasNegativeChange) || isset($this->_mineralNegativeChange)) {
			return;
		}
		
		if(!empty($this->mineralChange)) {
			
			// single base
			if(count($slot->mineralMiners) == 1) {
				throw_error("Cannot transfer workers if you have only one base.",
					"This error message should not occur. Please report this message with your build order on the thread linked at bottom of the page.");
				
			// multiple bases
			} else {
				$mineralChange = array_fill(0, count($slot->mineralMiners), 0);
				
				// take miners off minerals
				$left = $this->mineralChange;
				do {
					$mostSaturated = 0;
					for($i = 1; $i < count($slot->mineralMiners) - 1; $i++) {
						if($slot->mineralMiners[$i] + $mineralChange[$i] > $slot->mineralMiners[$mostSaturated] + $mineralChange[$mostSaturated]) {
							$mostSaturated = $i;
						}
					}
					$mineralChange[$mostSaturated]--;
				} while(--$left > 0);
				
				// put miners on minerals at new base
				$mineralChange[count($mineralChange) - 1] += $this->mineralChange;
			}
			
			// store changes
			$this->_mineralNegativeChange = array();
			$this->_mineralPositiveChange = array();
			for($i = 0; $i < count($mineralChange); $i++) {
				$this->_mineralNegativeChange[] = $mineralChange[$i] < 0 ? $mineralChange[$i] : 0;
				$this->_mineralPositiveChange[] = $mineralChange[$i] > 0 ? $mineralChange[$i] : 0;
			}
		}
		
		// transfer N miners to new geyser from other geysers
		if(!empty($this->gasChange)) {
			
			// single geyser
			if(count($slot->gasMiners) == 1) {
				throw_error("Cannot transfer workers if you have only one geyser.",
					"This error message should not occur. Please report this message with your build order on the thread linked at bottom of the page.");
				
			// multiple geysers
			} else {
				$gasChange = array_fill(0, count($slot->gasMiners), 0);
				
				// take miners off gas
				$left = $this->gasChange;
				do {
					$mostSaturated = 0;
					for($i = 1; $i < count($slot->gasMiners) - 1; $i++) {
						if($slot->gasMiners[$i] + $gasChange[$i] > $slot->gasMiners[$mostSaturated] + $gasChange[$mostSaturated]) {
							$mostSaturated = $i;
						}
					}
					$gasChange[$mostSaturated]--;
				} while(--$left > 0);
				
				// put miners on gas at new geyser
				$gasChange[count($gasChange) - 1] += $this->gasChange;
			}
			
			// store changes
			$this->_gasNegativeChange = array();
			$this->_gasPositiveChange = array();
			for($i = 0; $i < count($gasChange); $i++) {
				$this->_gasNegativeChange[] = $gasChange[$i] < 0 ? $gasChange[$i] : 0;
				$this->_gasPositiveChange[] = $gasChange[$i] > 0 ? $gasChange[$i] : 0;
			}
		}
	}
};

/**
 * Mutation that adds a MULE to income slots.
 */
class MULEMutation extends Mutation {

	/// private members

	/**
	 * Number of MULEs to add.
	 * @var int
	 */
	private $_MULEs;

	/// constructor

	/**
	 * Create a new MULE mutation.
	 * @param int $MULEs
	 */
	public function __construct($MULEs) {
		$this->_MULEs = $MULEs;
	}

	/// operators

	/**
	 * Convert to a string.
	 * @return string
	 */
	public function __tostring() {
		return "Start MULE use";
	}

	/// public methods

	/**
	 * Add a number of MULEs to given income slot.
	 * @param IncomeSlot $slot
	 */
	public function apply($slot) {
		$slot->MULEs += $this->_MULEs;
	}
};

/**
 * Mutation that sends one worker to scout.
 */
class ScoutMutation extends Mutation {

	/// constructor

	/**
	 * Create a new scout mutation.
	 */
	public function __construct() {
		$this->mineralChange = -1;
	}

	/// operators

	/**
	 * Convert to a string.
	 * @return string
	 */
	public function __tostring() {
		return "Send scout";
	}
};

/**
 * Set of mutations.
 */
class Mutations implements Iterator, Countable {

	/// private members

	/**
	 * List of mutations.
	 * @var array
	 */
	private $_mutations = array();

	/**
	 * Part of Iterator implementation.
	 * @var int
	 */
	private $_position;

	/// public methods
	
	/**
	 * Add a mutation to the list.
	 * @param Mutation $mutation
	 * @param float $time
	 */
	public function add($mutation, $time) {
		$mutation->time = $time;
		$this->_mutations[] = $mutation;
	}

	/**
	 * Sort mutations by time.
	 */
	public function sort() {
		usort($this->_mutations, array("Mutation", "compare"));
	}

	/// Countable implementation
	public function count() {
		return count($this->_mutations);
	}
	
	/// Iterator implementation
    function current() {
        return $this->_mutations[$this->_position];
    }

    function key() {
        return $this->_position;
    }

    function next() {
        ++$this->_position;
    }

    function rewind() {
        $this->_position = 0;
    }

    function valid() {
        return isset($this->_mutations[$this->_position]);
    }
};
?>