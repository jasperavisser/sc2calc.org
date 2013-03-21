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
 * Energy reservations are used to reserve energy on a spellcaster for future
 * use. The need for this stems from the fact that the scheduler cannot always
 * process energy consumption in chronological order.
 */
class EnergyReservation {

	/// public members

	/**
	 * @var float Amount of energy to be reserved
	 */
	public $energy;

	/**
	 * @var float Time at which energy is reserved
	 */
	public $time;

	/// constructor

	/**
	 *
	 * @param float $time
	 * @param float $energy
	 */
	public function __construct($time, $energy) {
		$this->energy = $energy;
		$this->time = $time;
	}
};

/**
 * A spellcaster represents an entity that generates energy and can use that
 * energy to allow abilities to be executed. 
 */
class Spellcaster {

	/// public members

	/**
	 * @var Product type of spellcaster
	 */
	public $casterType;

	/**
	 * @var float Time spellcaster was created
	 */
	public $created;

	/**
	 * @var float Time spellcaster was destroyed
	 */
	public $destroyed = INF;

	/**
	 * @var float Amount of energy stored at time of last update
	 */
	public $energy;

	/**
	 * @var array(EnergyReservation) Reservations of future energy use
	 */
	public $reservations;

	/**
	 * Tag to reference this specific spellcaster
	 * @var string
	 */
	public $tag;

	/// private members

	/**
	 * @var string Time of last update
	 */
	private $_lastUpdated;

	/// constructor

	/**
	 * Create a new spellcaster.
	 * @param Product $casterType Type of caster
	 * @param float $created Time of creation
	 */
	public function __construct($casterType, $created = 0, $tag = null) {
		$this->created = $created;
		$this->casterType = $casterType;
		$this->_lastUpdated = $created;
		$this->energy = $this->casterType->energyStart;
		$this->reservations = array();
		if($tag !== null) $this->tag = $tag;
	}

	/// public methods

	/**
	 * Calculate energy available on this spellcaster at time it was last
	 * updated.
	 * @param bool $onlyFree If true, subtract energy that is reserved.
	 * @return float Amount of energy
	 */
	public function energy($onlyFree = true) {
		$energy = $this->energy;
		if($onlyFree) {
			foreach($this->reservations as $reservation) {
				$energy -= $reservation->energy;
			}
		}
		return $energy;
	}

	/**
	 * Calculate surplus energy on this caster of the given type at a time in
	 * the future.
	 * @param float $time Time in the future
	 * @return float Amount of energy, ignoring reservations past given time
	 */
	public function surplus($time) {
		$spellcaster = clone $this;
		$spellcaster->update($time);
		return $spellcaster->energy(false);
	}

	/**
	 * Update spellcaster up to given time.
	 * @param float $time
	 */
	public function update($time) {

		// remove expired reservations
		foreach($this->reservations as $key => $reservation) {
			if($reservation->time <= $this->_lastUpdated) {
				$this->energy -= $reservation->energy;
				unset($this->reservations[$key]);
			}
		}

		// update energy
		if($time > $this->_lastUpdated) {
			$this->energy = min($this->casterType->energyMax,
				$this->energy + ($time - $this->_lastUpdated) * ENERGY_RATE);
			$this->_lastUpdated = $time;
		}
	}

	/**
	 * Calculate when the given amount of energy is available.
	 * @param float $energy Amount of energy
	 * @return float Time
	 */
	public function when($energy) {
		$when = max(0, $energy - $this->energy()) / ENERGY_RATE + $this->_lastUpdated;
		if($when > $this->destroyed) {
			$when = INF;
		}
		return $when;
	}
};

/**
 * Spellcasters is a set of spellcaster objects, with functions to choose one of
 * those spellcasters.
 */
class Spellcasters {

	/// class constants
	const debugFlag = 64;

	/// static public members

	/**
	 * If true, will echo debug messages
	 * @var bool
	 */
	static public $debug = false;

	/// private members

	/**
	 * List of spellcasters
	 * @var array
	 */
	private $_spellcasters = array();

	/// constructor

	/**
	 * Clone list of spellcasters.
	 */
	public function __clone() {
		$spellcasters = array();
		foreach($this->_spellcasters as $spellcaster) {
			$spellcasters[] = clone $spellcaster;
		}
		$this->_spellcasters = $spellcasters;
	}

	/// public methods

	/**
	 * Add a spellcaster to the list.
	 * @param Spellcaster $spellcaster Spellcaster to be added
	 */
	public function add($spellcaster) {
		if(self::$debug) tracemsg("Spellcasters::add(". $spellcaster->casterType . " at ". simple_time($spellcaster->created) .")");
		$this->_spellcasters[] = $spellcaster;
	}

	/**
	 * Get the spellcaster of the given type that has the most free energy.
	 * @param Product $casterType
	 * @param array $tagsRequired
	 * @return Spellcaster
	 */
	public function choose($casterType, $time, $tagsRequired = null) {
		if(self::$debug) tracemsg("Spellcasters::choose(". $casterType .", ". simple_time($time) .", ". ($tagsRequired === null ? "null" : $tagsRequired) .")");
		foreach($this->select($casterType, $tagsRequired) as $spellcaster) {
			if($spellcaster->created <= $time && $spellcaster->destroyed >= $time) {
				if(!isset($candidate)) {
					$candidate = $spellcaster;
				} elseif($spellcaster->energy() > $candidate->energy()) {
					$candidate = $spellcaster;
				}
			}
		}
		if(isset($candidate)) {
			if(self::$debug) tracemsg("Spellcasters::choose(), chosen ". $candidate->casterType ." created at ". simple_time($candidate->created));
			return $candidate;
		}
	}

	/**
	 * Expend the given amount of energy from a spellcaster of the given type.
	 * Uses whichever spellcaster has the most free energy.
	 * @param Product $casterType Type of spellcaster
	 * @param float $energy Energy to be expended
	 * @param array $tagsRequired
	 * @return Spellcaster Spellcaster used
	 */
	public function expend($casterType, $energy, $time, $tagsRequired = null) {
		if(self::$debug) tracemsg("Spellcasters::expend(". $casterType .", ". $energy .", ". simple_time($time) .", ". ($tagsRequired === null ? "null" : $tagsRequired) .")");
		$spellcaster = $this->choose($casterType, $time, $tagsRequired);
		if(!isset($spellcaster) || round($spellcaster->energy()) < $energy) {
			if(self::$debug) tracemsg("Spellcasters::expend(), chosen spellcaster has ". $spellcaster->energy() ." free energy.");
			throw_error("No spellcaster of type <i>". $casterType->name ."</i> has enough energy.",
				"This error message should not occur. Please report this message with your build order on the thread linked at bottom of the page.");
		}
		$spellcaster->energy -= $energy;
		return $spellcaster;
	}

	/**
	 * Remove the spellcaster of the given type with the least energy.
	 * @param Product $casterType
	 * @param float $time Time of removal
	 */
	public function remove($casterType, $time) {
		if(self::$debug) tracemsg("Spellcasters::remove(". $casterType .", ". simple_time($time) .")");

		// choose a spellcaster to remove
		foreach($this->select($casterType, $tagsRequired) as $spellcaster) {
			if($spellcaster->created <= $time) {
				if(!isset($candidate)) {
					$candidate = $spellcaster;
				} elseif($spellcaster->energy() < $candidate->energy()) {
					$candidate = $spellcaster;
				}
			}
		}

		// if no such spellcaster exists, throw an error
		if(!isset($candidate)) {
			throw_error("No spellcaster of type <i>". $casterType->name ."</i> could be removed.",
				"This error message should not occur. Please report this message with your build order on the thread linked at bottom of the page.");
		}

		// mark it as destroyed
		if(self::$debug) tracemsg("Spellcasters::remove(), chosen ". $candidate->casterType ." created at ". simple_time($candidate->created));
		$candidate->destroyed = $time;
	}

	/**
	 * Reserves given amount of energy on an spellcaster of the given type.
	 * @param Product $casterType Type of spellcaster
	 * @param float $energy Energy to be reserved
	 * @param float $time Time of reservation
	 * @param array $tagsRequired
	 * @return Spellcaster Spellcaster used
	 */
	public function reserve($casterType, $energy, $time, $tagsRequired = null) {
		$spellcaster = $this->choose($casterType, $time, $tagsRequired);
		if(!isset($spellcaster)) {
			throw_error("No spellcaster of type <i>". $casterType->name ."</i> exists.",
				"This error message should not occur. Please report this message with your build order on the thread linked at bottom of the page.");
		}
		$spellcaster->reservations[] = new EnergyReservation($time, $energy);
		return $spellcaster;
	}

	/**
	 * Find all spellcasters of given type with one of the given tags.
	 * @param Product $casterType
	 * @param string $tagsRequired
	 * @return array Array of references to the spellcasters
	 */
	public function select($casterType = null, $tagsRequired = null) {
		$spellcasters = array();
		foreach($this->_spellcasters as $spellcaster) {
			if($casterType === null || $spellcaster->casterType->uid == $casterType->uid) {
				if($tagsRequired === null || (isset($spellcaster->tag) && in_array($spellcaster->tag, $tagsRequired))) {
					$spellcasters[] = $spellcaster;
				}
			}
		}
		return $spellcasters;
	}

	/**
	 * Calculate surplus energy on all casters of the given type at a time in
	 * the future.
	 * @param Product $casterType Type of caster; if null, all casters are returned
	 * @param float $time
	 * @param array $tagsRequired
	 * @return float
	 */
	public function surplus($casterType, $time, $tagsRequired = null) {
		$surplus = array();
		foreach($this->select($casterType, $tagsRequired) as $spellcaster) {
			if($spellcaster->created <= $time && $spellcaster->destroyed >= $time) {
				$surplus[] = round($spellcaster->surplus($time));
			}
		}
		return $surplus;
	}

	/**
	 * Update spellcasters up to given time.
	 * @param float $time
	 */
	public function update($time) {
		foreach($this->_spellcasters as $spellcaster) {
			$spellcaster->update($time);
		}
	}

	/**
	 * Calculate time when a caster of the given type has the given amount of
	 * free energy.
	 * @param Product $casterType
	 * @param float $energy
	 * @param array $tagsRequired
	 * @return float
	 */
	public function when($casterType, $energy, $tagsRequired = null) {
		$time = INF;
		foreach($this->select($casterType, $tagsRequired) as $spellcaster) {
			$time = min($time, $spellcaster->when($energy));
		}
		return $time;
	}
};
?>