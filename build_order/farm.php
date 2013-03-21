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
 * A farm represents a product that increases supply capacity.
 */
class Farm {

	/// public members

	/**
	 * Amount of supply capacity provided by this farm
	 * @var int
	 */
	public $capacity;

	/**
	 * Time when this farm was created
	 * @var float
	 */
	public $created;

	/**
	 * Time when this farm was destroyed
	 * @var float
	 */
	public $destroyed = INF;

	/// constructor

	/**
	 * Create new farm.
	 * @param float $created
	 * @param int $capacity
	 */
	public function __construct($created, $capacity) {
		$this->capacity = $capacity;
		$this->created = $created;
	}

	/// public static methods

	/**
	 * Compare two farms by time created.
	 * @param Farm $farm1
	 * @param Farm $farm2
	 * @return int
	 */
	public static function compare($farm1, $farm2) {
		return $farm1->created > $farm2->created ? 1 : ($farm1->created == $farm2->created ? 0 : -1);
	}
};

/**
 * Farms are the total collection of farms that are available.
 */
class Farms {

	/// private members

	/**
	 * List of farms, always kept sorted by time created
	 * @var array
	 */
	private $_farms = array();

	/// public methods

	/**
	 * Add a new farm to the list.
	 * @param Farm $farm
	 */
	public function add($farm) {
		$this->_farms[] = $farm;
		uasort($this->_farms, array("Farm", "compare"));
	}

	/**
	 * Remove a farm with the given supply capacity at the given time.
	 * @param int $supplyCapacity Supply capacity to remove
	 * @param float $time Time of removal
	 */
	public function remove($supplyCapacity, $time) {
		foreach($this->_farms as &$farm) {
			if($farm->capacity == $supplyCapacity && $farm->created <= $time) {
				$farm->destroyed = $time;
				break;
			}
		}
	}

	/**
	 * Calculate supply capacity at a given time.
	 * @param float $time
	 * @return int
	 */
	public function surplus($time) {
		$capacity = 0;
		foreach($this->_farms as $farm) {
			if($farm->created <= $time && $farm->destroyed >= $time) {
				$capacity += $farm->capacity;
			}
		}
		return $capacity;
	}

	/**
	 * Calculate when the supply capacity is greater than or equal to the given
	 * capacity.
	 * @param int $capacity
	 * @return float
	 */
	public function when($capacity) {
		foreach($this->_farms as $farm) {
			if($farm->destroyed === INF) { // TODO: this is kinda dirty
				$capacity -= $farm->capacity;
				if($capacity <= 0) {
					return $farm->created;
				}
			}
		}
		return INF;
	}
};
