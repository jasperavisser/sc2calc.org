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
 * A hatchery is any structure that produces larvae.
 */
class Hatchery {

	/// public members

	/**
	 * Time when hatchery was completed
	 * @var float
	 */
	public $created;

	/**
	 * Initial number of larvae on this hatchery
	 * @var int
	 */
	public $initialLarvae;

	/**
	 * Number of larvae currently on this hatchery
	 * @var int
	 */
	public $larvae = 0;

	/**
	 * Time when next larva will be generated
	 * @var float
	 */
	public $nextLarvaGenerated;

	/**
	 * Number that indicates in which order the hatcheries were created
	 * @var int
	 */
	public $order;

	/**
	 * Tag to reference this specific hatchery
	 * @var string
	 */
	public $tag;

	/**
	 * Rebate on time required to generate next larva.
	 * @var float
	 */
	public $timeRebate = 0;

	/**
	 * For each vomit, time when it expires
	 * @var array
	 */
	public $vomitExpires = array();
	
	/// private members

	/**
	 * For each larva, time when it was generated
	 * @var array
	 */
	private $_generated = array();

	/**
	 * Time when hatchery was last updated
	 * @var float
	 */
	private $_lastUpdated;

	/// constructor

	/**
	 * Create new hatchery.
	 * @param float $created
	 * @param int $initialLarvae
	 */
	public function __construct($created, $initialLarvae = 1, $tag = null) {
		$this->created = $created;
		$this->initialLarvae = $initialLarvae;
		$this->tag = $tag;
		$this->generateLarvae($this->created, $this->initialLarvae);
		$this->_lastUpdated = $this->created;
	}

	/// operators

	/**
	 * Convert to a string.
	 * @return string
	 */
	public function __tostring() {
		$result = "<tr>".
			"<td>". $this->order ."</td>".
			"<td class=\"center\">". simple_time($this->created) ."</td>".
			"<td class=\"center\">";
		$i = 0;
		foreach($this->_generated as $generated) {
			$result .= ($i > 0 ? (($i % 10 == 0) ? "<br/>" : " &gt; ") : "") . simple_time($generated);
			$i++;
		}
		$result .= "</tr>";
		return $result;
	}

	/// public methods

	/**
	 * Generate given number of larvae at the given time.
	 * @param float $time
	 * @param int $number
	 * @param bool $resetGeneration
	 */
	public function generateLarvae($time, $number = 1, $resetGeneration = true) {
		for($i = 0; $i < $number; $i++) {
			$this->_generated[] = $time;
		}
		$this->larvae += $number;
		$this->larvae = min(19, $this->larvae);
		if($resetGeneration) {
			$this->nextLarvaGenerated = $time + LARVA_TIME;
			$this->timeRebate = 0;
			if(Hatcheries::$debug) tracemsg("Hatcheries[". $this->order ."]::generateLarvae(), at ". simple_time($time) ." we generate a larva. The next larva will be generated at ". simple_time($this->nextLarvaGenerated) .". This hatchery now has ". $this->larvae ." larvae.");
		} elseif($this->larvae > 2) {
			$this->timeRebate = $time - $this->nextLarvaGenerated + LARVA_TIME;
			if(Hatcheries::$debug) tracemsg("Hatcheries[". $this->order ."]::generateLarvae(), at ". simple_time($time) ." we rise above 2 larvae. The next larva would be generated at ". simple_time($this->nextLarvaGenerated) .", so the rebate is set at ". $this->timeRebate ." seconds.");
		}
	}

	/**
	 * Time when next larva is generated, infinite if there are 3 or more larvae
	 * available.
	 * @return float
	 */
	public function nextGenerated() {
		return $this->larvae < 3 ? $this->nextLarvaGenerated : INF;
	}

	/**
	 * Get time when next vomit expires, INF if none.
	 * @return float
	 */
	public function nextVomit() {
		if(count($this->vomitExpires) > 0) {
			return min($this->vomitExpires);
		}
		return INF;
	}

	/**
	 * Calculate surplus number of larvae at a given time in the future.
	 * @param float $time
	 * @return int
	 */
	public function surplus($time) {
		if(Hatcheries::$debug) tracemsg("Hatchery::surplus(". simple_time($time) .")");
		$hatchery = clone $this;
		$hatchery->update($time, false);
		return $hatchery->larvae;
	}

	/**
	 * Update the state of this hatchery up to given time.
	 * @param float $time
	 * @param bool $debug
	 */
	public function update($time) {
		if(Hatcheries::$debug) tracemsg("Hatchery::update(". simple_time($time) .")");

		if(Hatcheries::$debug) tracemsg("Hatchery::update(), nextGenerated=". simple_time($this->nextGenerated()) .", nextVomit=". simple_time($this->nextVomit()));
		while($this->nextGenerated() <= $time || $this->nextVomit() <= $time) {
			
			// expire vomits
			foreach($this->vomitExpires as $key => $vomitExpire) {
				if($vomitExpire <= min($time, $this->nextGenerated())) {
					if(Hatcheries::$debug) tracemsg("Hatchery::update(), expiring vomit at ". simple_time($vomitExpire));
					unset($this->vomitExpires[$key]);
					$this->generateLarvae($vomitExpire, 4, false);
				}
			}

			// generate larvae
			if($this->nextGenerated() <= $time) {
				if(Hatcheries::$debug) tracemsg("Hatchery::update(), generating larva at ". simple_time($this->nextLarvaGenerated));
				$this->generateLarvae($this->nextLarvaGenerated);
			}
		}

		$this->_lastUpdated = max($this->created, $time);
	}

	/**
	 * Queue vomit on this hatchery at the given time.
	 * @global Product $SpawnLarvae
	 * @param float $time
	 */
	public function vomit($time) {
		global $SpawnLarvae;
		if(Hatcheries::$debug) tracemsg("Hatchery::vomit(". simple_time($time) .")");
		$this->vomitExpires[] = $time + $SpawnLarvae->timeCost;
		sort($this->vomitExpires);
	}

	/**
	 * Calculate time when the next larva is available.
	 * @return float
	 */
	public function when() {
		if($this->larvae > 0) {
			return $this->_lastUpdated;
		}
		return min($this->nextLarvaGenerated, $this->nextVomit());
	}

	/**
	 * Calculate time when another vomit can be queued on this hatchery.
	 * @return float
	 */
	public function whenVomit() {
		return count($this->vomitExpires) > 0 ? max($this->vomitExpires) : $this->created;
	}
};

/**
 * The set of hatcheries available.
 */
class Hatcheries {

	/// class constants
	const debugFlag = 32;

	/// static public members

	/**
	 * If true, will echo debug messages
	 * @var bool
	 */
	static public $debug = false;

	/// private members

	/**
	 * List of hatcheries
	 * @var array
	 */
	private $_hatcheries = array();

	/**
	 *
	 * @var bool
	 */
	private $_isClone = false;

	/**
	 * Time when hatcheries were last updated
	 * @var float
	 */
	private $_lastUpdated;

	/// constructor
	
	/**
	 * Create a copy of this.
	 */
	public function  __clone() {
		$hatcheries = array();
		foreach($this->_hatcheries as $hatchery) {
			$hatcheries[] = clone $hatchery;
		}
		$this->_hatcheries = $hatcheries;
		$this->_isClone = true;
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
					"<th>Created</th>".
					"<th>Larvae generated at</th>".
				"</tr>".
			"</thead>".
			"<tbody>".
				implode("", $this->_hatcheries) .
			"</tbody>".
		"</table>";
	}

	/// public methods

	/**
	 * Add a hatchery to the list.
	 * @param Hatchery $hatchery
	 */
	public function add($hatchery) {
		$this->_hatcheries[] = $hatchery;
		$hatchery->order = count($this->_hatcheries);
	}

	/**
	 * Use up a single larva from any hatchery that has one available and that
	 * has the required tags at the given time.
	 * @param float $time
	 * @param int $larvae
	 * @param array $tagsRequired
	 */
	public function expend($time, $larvae, $tagsRequired = null) {
		if(self::$debug) tracemsg(($this->_isClone ? "lon " : "") ."Hatcheries::expend(". simple_time($time) .")");

		// update all
		$this->update($time);
		for($i = 0; $i < $larvae; $i++) {

			// choose hatchery
			foreach($this->select($tagsRequired) as $hatchery) {
				if(self::$debug) tracemsg(($this->_isClone ? "lon " : "") ."Hatcheries::expend(), hatchery created at ". simple_time($hatchery->created) ." has ". $hatchery->larvae ." larvae.");
				if($hatchery->larvae > 0 && $hatchery->created <= $time) {
					if(!isset($candidate)) {
						$candidate = $hatchery;
					} elseif($hatchery->larvae > $candidate->larvae) {
						$candidate = $hatchery;
					} elseif($hatchery->larvae == $candidate->larvae && $hatchery->nextVomit() < $candidate->nextVomit()) {
						$candidate = $hatchery;
					} elseif($hatchery->larvae == $candidate->larvae && $hatchery->nextVomit() == $candidate->nextVomit() && $hatchery->nextLarvaGenerated < $candidate->nextLarvaGenerated) {
						$candidate = $hatchery;
					}
				}
			}
			if(!isset($candidate)) {
				throw_error("No hatcheries have larvae available at ". simple_time($time) .".",
					"This error message should not occur. Please report this message with your build order on the thread linked at bottom of the page.");
			}

			// reset time next larva is generated
			if($candidate->larvae == 3) {
				$candidate->nextLarvaGenerated = $time + LARVA_TIME - $candidate->timeRebate;
				if(self::$debug) tracemsg(($this->_isClone ? "lon " : "") ."Hatcheries::expend(), at ". simple_time($time) ." we drop below 3 larvae. The rebate is ". $candidate->timeRebate ." seconds, so the next larva is generated at ". simple_time($candidate->nextLarvaGenerated));
			}

			// use larva
			if(self::$debug) tracemsg(($this->_isClone ? "lon " : "") ."Hatcheries[". $candidate->order ."]::larvae-- = ". ($candidate->larvae - 1));
			$candidate->larvae--;
		}

		if(self::$debug) tracemsg(($this->_isClone ? "lon " : "") ."Hatcheries::expend(), done!");
	}

	/**
	 * Find all hatcheries with one of the given tags.
	 * @param string $tagsRequired
	 * @return array Array of references to the hatcheries
	 */
	public function select($tagsRequired = null) {
		$hatcheries = array();
		foreach($this->_hatcheries as $hatchery) {
			if($tagsRequired === null || (isset($hatchery->tag) && in_array($hatchery->tag, $tagsRequired))) {
				$hatcheries[] = $hatchery;
			}
		}
		return $hatcheries;
	}

	/**
	 * Calculate surplus numbers of larvae on all hatcheries that have the
	 * required tags at a given time in the future.
	 * @param float $time
	 * @param array $tagsRequired
	 * @return array
	 */
	public function surplus($time, $tagsRequired = null) {
		$larvae = array();
		foreach($this->select($tagsRequired) as $hatchery) {
			if($hatchery->created <= $time) {
				$larvae[] = $hatchery->surplus($time);
			}
		}
		return $larvae;
	}

	/**
	 * Update the state of all hatcheries up to given time.
	 * @param float $time
	 */
	public function update($time) {
		if(self::$debug) tracemsg(($this->_isClone ? "lon " : "") ."Hatcheries::update(". simple_time($time) .")");

		if($time < $this->_lastUpdated) {
			throw_error("Cannot generate larvae in the past.",
				"This error message should not occur. Please report this message with your build order on the thread linked at bottom of the page.");
		}
		
		// generate larvae
		foreach($this->_hatcheries as $hatchery) {
			$hatchery->update($time, self::$debug);
		}

		$this->_lastUpdated = $time;
	}

	/**
	 * Queue use of vomit at given time in the future.
	 * @param float $time
	 */
	public function vomit($time) {

		// choose hatchery
		foreach($this->_hatcheries as $hatchery) {
			if($hatchery->created <= $time) {
				if(!isset($candidate)) {
					$candidate = $hatchery;
				} elseif($hatchery->whenVomit() < $candidate->whenVomit()) {
					$candidate = $hatchery;
				}
			}
		}

		// queue vomit
		if(self::$debug) tracemsg(($this->_isClone ? "lon " : "") ."Hatcheries::vomit(), vomitting to hatchery created at ". simple_time($candidate->created));
		$candidate->vomit($time);
	}

	/**
	 * Calculate time when hatcheries with the required tags has the given
	 * number of free larva.
	 * @param int $larvae
	 * @param array $tagsRequired
	 * @return float
	 */
	public function when($larvae, $tagsRequired = null) {
		$time = INF;
		if($larvae == 1) {
			foreach($this->select($tagsRequired) as $hatchery) {
				if(self::$debug) tracemsg(($this->_isClone ? "lon " : "") ."Hatcheries[". $hatchery->order ."]::when()=". simple_time($hatchery->when()) .", has ". $hatchery->larvae ." larvae.");
				$time = min($time, $hatchery->when());
			}
		} else {
			$hatcheries = clone $this;
			for($i = 0; $i < $larvae; $i++) {
				$time = $hatcheries->when(1, $tagsRequired);
				$hatcheries->expend($time, 1, $tagsRequired);
			}
		}
		if(self::$debug) tracemsg(($this->_isClone ? "lon " : "") ."Hatcheries::when(), returns ". $time);
		return $time;
	}

	/**
	 * Calculate time when another vomit can be queued on any hatchery.
	 * @return float
	 */
	public function whenVomit() {
		$time = INF;
		foreach($this->_hatcheries as $hatchery) {
			$time = min($time, $hatchery->whenVomit());
		}
		if(self::$debug) tracemsg(($this->_isClone ? "lon " : "") ."Hatcheries::whenVomit(), returns ". simple_time($time));
		return $time;
	}
};
?>