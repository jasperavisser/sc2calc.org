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
 * Income slots are used to keep track of the resource income at different time
 * intervals of the build. Income slots are separated from adjacent slots by
 * mutations, such as the construction of a new worker or the transfer of
 * workers to a new base.
 */
class IncomeSlot {

	/// public members

	/**
	 * For each base, whether the geyser is already operational.
	 * @var array
	 */
	public $basesOperational = array();

	/**
	 * Time when this slot ends
	 * @var float
	 */
	public $endTime;

	/**
	 * Number of gas miners per geyser
	 * @var array
	 */
	public $gasMiners = array();

	/**
	 * For each geyser, whether the geyser is already operational.
	 * @var array
	 */
	public $geysersOperational = array();

	/**
	 * Number of mineral miners per base
	 * @var array
	 */
	public $mineralMiners = array();

	/**
	 * Number of MULEs
	 * @var int
	 */
	public $MULEs = 0;

	/**
	 * Number that indicates the chronological order of the slots
	 * @var int
	 */
	public $order;

	/**
	 * Time when this slot starts
	 * @var float
	 */
	public $startTime;

	/// private members

	/**
	 * Time when slot was last updated
	 * @var float
	 */
	private $_lastUpdated;

	/// constructor

	/**
	 * Create new income slot.
	 * @param float $startTime
	 * @param float $endTime
	 */
	public function __construct($startTime = 0, $endTime = INF) {
		$this->start($startTime);
		$this->endTime = $endTime;
	}

	/// operators

	/**
	 * Convert to a string.
	 * @return string
	 */
	public function __tostring() {
		return "<tr>".
			"<td class=\"right\">". $this->order ."</td>".
			"<td class=\"center\">". simple_time($this->startTime) ."</td>".
			"<td class=\"center\">".simple_time($this->endTime) ."</td>".
			"<td class=\"center\">". $this->mineralRate() ."</td>".
			"<td class=\"center\">".	$this->gasRate() ."</td>".
			"<td class=\"center\">". implode(", ", $this->mineralMiners) ."". ($this->MULEs ? (" +". $this->MULEs ." mules") : "") ."</td>".
			"<td class=\"center\">". implode(", ", $this->gasMiners) ."</td>".
			"</tr>";
	}

	/// public methods

	/**
	 * Calculate duration of this slot.
	 * @return float
	 */
	public function duration() {
		return $this->endTime - $this->_lastUpdated;
	}

	/**
	 * Calculate rate at which gas is mined in gas per second.
	 * @return float
	 */
	public function gasRate() {
		$gasRate = 0;
		for($i = 0; $i < count($this->gasMiners); $i++) {
			if($this->geysersOperational[$i]) {
				$gasRate += min($this->gasMiners[$i], 3) * 0.63;
			}
		}
		return $gasRate;
	}

	/**
	 * Calculate rate at which mineral is mined in mineral per second.
	 * @return float
	 */
	public function mineralRate() {
		$mineralRate = 0;
		for($i = 0; $i < count($this->mineralMiners); $i++) {
			if($this->basesOperational[$i]) {
				$mineralRate += min($this->mineralMiners[$i], 16) * 0.7
					+ min(max($this->mineralMiners[$i] - 16, 0), 8) * 0.3;
			}
		}
		return $mineralRate + $this->MULEs * MULE_MINING;
	}

	/**
	 * Reset start time of this slot.
	 * @param float $time
	 */
	public function start($time) {
		$this->startTime = $time;
		$this->_lastUpdated = $this->startTime;
	}

	/**
	 * Calculate surplus resources at a given time in the future.
	 * @param float $time
	 * @return array
	 */
	public function surplus($time) {
		if($this->_lastUpdated <= $time && $this->endTime >= $time) {
			return array($this->gasRate() * ($time - $this->_lastUpdated),
				$this->mineralRate() * ($time - $this->_lastUpdated));
		} elseif($this->_lastUpdated <= $time && $this->endTime <= $time) {
			return array($this->gasRate() * ($this->endTime - $this->_lastUpdated),
				$this->mineralRate() * ($this->endTime - $this->_lastUpdated));
		}
		return array(0, 0);
	}

	/**
	 * Update this slot up to the given time.
	 * @param float $time
	 */
	public function update($time) {
		$this->_lastUpdated = min($this->endTime, max($this->startTime, $time));
	}

	/**
	 * Calculate when the needed amount of resources would be available.
	 * @param float $mineralNeeded
	 * @param float $gasNeeded
	 * @return array
	 */
	public function when($mineralNeeded, $gasNeeded) {
		if($this->duration() == 0) {
			return array(INF, INF);
		}

		// when is mineral achieved
		if($mineralNeeded <= 0) {
			$mineralTime = $this->_lastUpdated;
		} elseif($this->mineralRate() == 0) {
			$mineralTime = INF;
		} else {
			$mineralTime = $mineralNeeded / $this->mineralRate() + $this->_lastUpdated;
			if($mineralTime > $this->endTime) {
				$mineralTime = INF;
			}
		}

		// when is gas achieved
		if($gasNeeded <= 0) {
			$gasTime = $this->_lastUpdated;
		} elseif($this->gasRate() == 0) {
			$gasTime = INF;
		} else {
			$gasTime = $gasNeeded / $this->gasRate() + $this->_lastUpdated;
			if($gasTime > $this->endTime) {
				$gasTime = INF;
			}
		}
		
		return array($mineralTime, $gasTime);
	}
};

/**
 * Set of all income slots currently known to exist.
 * @see IncomeSlot
 */
class IncomeSlots implements ArrayAccess, Countable {

	/// public members

	/**
	 * If true, will echo debug messages
	 * @var bool
	 */
	public $debug = false;

	/// private members

	/**
	 * Amount of gas stored
	 * @var float
	 */
	private $_gasStored;

	/**
	 * Amount of gas stored at beginning of game
	 * @var float
	 */
	private $_initialGasStored;

	/**
	 * Amount of mineral stored at beginning of game
	 * @var float
	 */
	private $_initialMineralStored;

	/**
	 * Time when income slots were last updated
	 * @var float
	 */
	private $_lastUpdated;

	/**
	 * Amount of mineral stored
	 * @var float
	 */
	private $_mineralStored;

	/**
	 * List of income slots
	 * @var array
	 */
	private $_slots = array();

	/// constructor

	/**
	 * Create new list of income slots.
	 * @param float $initialMineral
	 * @param float $initialGas
	 */
	public function __construct($initialMineral = 0, $initialGas = 0) {
		$this->_initialGasStored = $initialGas;
		$this->_initialMineralStored = $initialMineral;
		$this->_lastUpdated = 0;
		$this->_gasStored = $this->_initialGasStored;
		$this->_mineralStored = $this->_initialMineralStored;
	}

	/**
	 * Clone this list of income slots.
	 */
	public function __clone() {
		$slots = array();
		foreach($this->_slots as $slot) {
			$slots[] = clone $slot;
		}
		$this->_slots = $slots;
	}

	/// operators

	/**
	 * Convert to a string.
	 * @return string
	 */
	public function __tostring() {
		$order = 0;
		foreach($this->_slots as $slot) {
			$slot->order = ++$order;
		}
		return "<table id=\"pool\" class=\"display\" cellpadding=0 cellspacing=0>".
			"<thead>".
				"<tr>".
					"<th>#</th>".
					"<th>Started</th>".
					"<th>Ended</th>".
					"<th>Minerals per second</th>".
					"<th>Gas per second</th>".
					"<th>Workers on mineral</th>".
					"<th>Workers on gas</th>".
				"</tr>".
			"</thead>".
			"<tbody>".
				"<tr>".
					"<td class=\"right\">0</td>".
					"<td class=\"center\"></td>".
					"<td></td>".
					"<td class=\"center\">50</td>".
					"<td class=\"center\">0</td>".
					"<td></td>".
					"<td></td>".
				"</tr>".
				implode("", $this->_slots) .
			"</tbody>".
		"</table>";
	}

	/// public methods

	/**
	 * Expend the given amount of resources from the income slots, eating up the
	 * earliest slots first.
	 * @param float $mineral
	 * @param float $gas
	 */
	public function expend($mineral, $gas) {
		if($this->debug) tracemsg("IncomeSlots::expend(". $mineral .", ". $gas .")");
		$this->_gasStored = round($this->_gasStored - $gas);
		$this->_mineralStored = round($this->_mineralStored - $mineral);
		if($this->debug) tracemsg("IncomeSlots::expend(), after expending, we got ". $this->_mineralStored ." minerals and ". $this->_gasStored ." gas.");
	}

	/**
	 * Get the amount of gas stored.
	 * @return float
	 */
	public function gasStored() {
		return $this->_gasStored;
	}

	/**
	 * Get the amount of mineral stored.
	 * @return float
	 */
	public function mineralStored() {
		return $this->_mineralStored;
	}

	/**
	 * Splice a mutation into these income slots. The mutation will split
	 * one slot in twain, and affect all slots after its mutation point.
	 * @param Mutation $mutation
	 */
	public function splice($mutation) {
		if($this->debug) tracemsg("IncomeSlots::splice(". $mutation ." @ ". simple_time($mutation->time) .")");

		if(isset($mutation->delay)) {

			// find negative splice point
			for($i = 0; $i < count($this->_slots); $i++) {
				$slot = $this->_slots[$i];
				if($slot->endTime === INF || $slot->endTime > $mutation->time) {
					$newSlot = clone $slot;
					$newSlot->start($mutation->time);
					$slot->endTime = $mutation->time;
					array_splice($this->_slots, $i + 1, 0, array($newSlot));
					$spliceSlot = $i + 1;
					break;
				}
			}

			// update income beyond negative splice point
			for($i = $spliceSlot; $i < count($this->_slots); $i++) {
				$mutation->applyNegative($this->_slots[$i]);
			}

			// find positive splice point
			for($i = 0; $i < count($this->_slots); $i++) {
				$slot = $this->_slots[$i];
				if($slot->endTime === INF || $slot->endTime > $mutation->time + $mutation->delay) {
					$newSlot = clone $slot;
					$newSlot->start($mutation->time + $mutation->delay);
					$slot->endTime = $mutation->time + $mutation->delay;
					array_splice($this->_slots, $i + 1, 0, array($newSlot));
					$spliceSlot = $i + 1;
					break;
				}
			}

			// update income beyond positive splice point
			for($i = $spliceSlot; $i < count($this->_slots); $i++) {
				$mutation->applyPositive($this->_slots[$i]);
			}

		} else {

			// find splice point
			for($i = 0; $i < count($this->_slots); $i++) {
				$slot = $this->_slots[$i];
				if($slot->endTime === INF || $slot->endTime > $mutation->time) {
					$newSlot = clone $slot;
					$newSlot->start($mutation->time);
					$slot->endTime = $mutation->time;
					array_splice($this->_slots, $i + 1, 0, array($newSlot));
					$spliceSlot = $i + 1;
					break;
				}
			}

			// update income beyond splice point
			for($i = $spliceSlot; $i < count($this->_slots); $i++) {
				$mutation->apply($this->_slots[$i]);
			}
		}
	}

	/**
	 * Calculate surplus gas and mineral at a given time in the future.
	 * @param float $time
	 * @return array
	 */
	public function surplus($time) {
		$gasSurplus = $this->_gasStored;
		$mineralSurplus = $this->_mineralStored;
		foreach($this->_slots as $slot) {
			list($gas, $mineral) = $slot->surplus($time);
			$mineralSurplus += $mineral;
			$gasSurplus += $gas;
		}
		return array($gasSurplus, $mineralSurplus);

	}

	/**
	 * Calculate total gas mined before given time.
	 * @param float $time
	 * @return float
	 */
	public function totalGas($time) {
		$totalGas = $this->_initialGasStored;
		foreach($this->_slots as $slot) {
			$overlap = min($time, $slot->endTime) - min($time, $slot->startTime);
			$totalGas += $overlap * $slot->gasRate();
		}
		return $totalGas;
	}
	
	/**
	 * Calculate total minerals mined before given time.
	 * @param float $time
	 * @return float
	 */
	public function totalMineral($time) {
		$totalMineral = $this->_initialMineralStored;
		foreach($this->_slots as $slot) {
			$overlap = min($time, $slot->endTime) - min($time, $slot->startTime);
			$totalMineral += $overlap * $slot->mineralRate();
		}
		return $totalMineral;
	}

	/**
	 * Update time slots up to the given time.
	 * @param float $time
	 */
	public function update($time) {
		if($this->debug) tracemsg("IncomeSlots::update(". simple_time($time) .")");
		foreach($this->_slots as $slot) {
			list($gasSurplus, $mineralSurplus) = $slot->surplus($time);
			if($this->debug) tracemsg("IncomeSlots::update(), we gain ". $mineralSurplus ." minerals and ". $gasSurplus ." gas.");
			if($this->debug) tracemsg("from slot starting at ". $slot->startTime ." and ending at ". $slot->endTime);
			$this->_gasStored += $gasSurplus;
			$this->_mineralStored += $mineralSurplus;
			$slot->update($time);
			if($this->debug) tracemsg("IncomeSlots::update(), we got ". $this->_mineralStored ." minerals and ". $this->_gasStored ." gas.");
		}
		$this->_lastUpdated = $time;
	}

	/**
	 * Calculate when the given amount of resources is available.
	 * @param float $mineralNeeded
	 * @param float $gasNeeded
	 * @return float
	 */
	public function when($mineralNeeded, $gasNeeded) {
		if($this->debug) tracemsg("IncomeSlots::when(". $mineralNeeded .", ". $gasNeeded .")");

		// how much is needed
		$mineralNeeded -= $this->_mineralStored;
		$gasNeeded -= $this->_gasStored;

		// calculate breaking points
		foreach($this->_slots as $slot) {
			list($mineralTimeInSlot, $gasTimeInSlot) = $slot->when($mineralNeeded, $gasNeeded);

			if($mineralTimeInSlot === INF) {
				$mineralNeeded -= $slot->mineralRate() * $slot->duration();
			} elseif(!isset($mineralTime)) {
				$mineralTime = $mineralTimeInSlot;
			}

			if($gasTimeInSlot === INF) {
				$gasNeeded -= $slot->gasRate() * $slot->duration();
			} elseif(!isset($gasTime)) {
				$gasTime = $gasTimeInSlot;
			}

			if(isset($gasTime) && isset($mineralTime)) {
				break;
			}
		}

		if(!isset($gasTime)) {
			$gasTime = INF;
		}
		if(!isset($mineralTime)) {
			$mineralTime = INF;
		}

		if($this->debug) tracemsg("IncomeSlots::when(), mineralTime=". $mineralTime .", gasTime=". $gasTime);
		if($this->debug) tracemsg("IncomeSlots::when(), storedMineral=". $this->_mineralStored .", storedGas=". $this->_gasStored);
		return max($mineralTime, $gasTime);
	}

	/// ArrayAccess implementation
	public function offsetExists($key) {
		return isset($this->_slots[$key]);
	}

	public function offsetGet($key) {
		return $this->_slots[$key];
	}

	public function offsetSet($key, $value) {
        if(is_null($key)) {
            $this->_slots[] = $value;
        }
        else {
            $this->_slots[$key] = $value;
        }
    }

	public function offsetUnset($key) {
		unset($this->_slots[$key]);
	}

	/// Countable implementation
	public function count() {
		return count($this->_slots);
	}
};
?>