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
 * A production queues represents an entity that has queue-like availability to
 * build certain objects. An obvious example would be a structure that produces
 * units or upgrades. The same mechanic is also used for building addons and
 * swapping addons.
 */
class ProductionQueue {

	/// public members

	/**
	 * Time when production queue is next available
	 * @var float
	 */
	public $available;

	/**
	 * Amount of time the production queue has been in use
	 * @var float
	 */
	public $busyTime;

	/**
	 * Time when production queue was last chronoboosted
	 * @var float
	 */
	public $chronoboosted;

	/**
	 * Time when production queue was created
	 * @var float
	 */
	public $created;

	/**
	 * Time when production queue was destroyed
	 * @var float
	 */
	public $destroyed;

	/**
	 * Type of production queue
	 * @var Product
	 */
	public $structure;
	
	/**
	 * Tag to reference this specific production queue
	 * @var string
	 */
	public $tag;
	
	/// constructor

	/**
	 * Create new production queue
	 * @param Product $structure
	 * @param float $available
	 * @param string $tag
	 */
	public function __construct($structure, $available = 0, $tag = null) {
		$this->structure = $structure;
		$this->available = $available;
		$this->busyTime = 0;
		$this->created = $available;
		$this->chronoboosted = -INF;
		if($tag !== null) $this->tag = $tag;
	}

	/// operators

	public function __tostring() {
		return (string)$this->structure;
	}
	
	/// public methods

	/**
	 * Mark the production queue as busy for a given period of time
	 * @param float $startTime
	 * @param float $endTime
	 * @param bool $busy
	 */
	public function busy($startTime, $endTime, $busy = true) {
		$this->available = $endTime;
		if($busy) {
			$this->busyTime += $endTime - $startTime;
		}
	}
};

/**
 * A set of production queues with functions to choose which of those queues to
 * expend.
 */
class ProductionQueues {

	/// class constants
	const debugFlag = 8;

	/// static public members

	/**
	 * If true, will echo debug messages
	 * @var bool
	 */
	static public $debug = false;

	/// public members
	
	/**
	 * The end of the timeline.
	 * @var float
	 */
	public $timeEnds;

	/// private members
	private $_lastUpdated = 0;
	private $_queues = array();
	
	/// constructor
	public function __clone() {
		$queues = array();
		foreach($this->_queues as $queue) {
			$queues[] = clone $queue;
		}
		$this->_queues = $queues;
	}
	
	/// operators
	public function __toString() {
		$result = "<table id=\"queues\" class=\"display\" cellpadding=0 cellspacing=0>".
			"<thead>".
				"<tr>".
					"<th>Structure</th>".
					"<th>Created</th>".
					"<th>Destroyed</th>".
					"<th>Busy time</th>".
					"<th>Busy percentage</th>".
				"</tr>".
			"</thead>".
			"<tbody>";
		foreach($this->_queues as $queue) {
			$existed = (isset($queue->destroyed) ? $queue->destroyed : $this->timeEnds) - $queue->created;
			if($queue->busyTime != 0 && $existed != 0) {
				$result .= "<tr>".
					"<td class=\"left\">". $queue->structure ."</td>".
					"<td class=\"center\">". simple_time($queue->created) ."</td>".
					"<td class=\"center\">". (isset($queue->destroyed) ? simple_time($queue->destroyed) : "") ."</td>".
					"<td class=\"center\">". simple_time($queue->busyTime) ."</td>".
					"<td class=\"center\">".number_format(100 * $queue->busyTime / $existed) ."%</td>".
				"</tr>";
			}
		}
		$result .= "</tbody></table>";
		return $result;
	}
	
	/// public methods

	/**
	 * Add a production queue to the list
	 * @param ProductionQueue $queue
	 */
	public function add($queue) {
		if(self::$debug) tracemsg("ProductionQueues::add(". $queue->structure .")");
		$this->_queues[] = $queue;
	}
	
	/**
	 * Find next available queues, either of any expended structure, or
	 * of all expended structures.
	 * @param array $expends Expended structures
	 * @param bool $expendsAll If true, find all expended structures
	 * @param bool $tagsRequired
	 * @return array Array of references to the available queues
	 */
	public function choose($time, $expends, $expendsAll = false, $tagsRequired = null) {
		$queues = array();
		foreach($expends as $expend) {
			if($expendsAll) {
				unset($candidate);
			}
			foreach($this->select($expend, $tagsRequired) as $queue) {
				if($queue->available <= $time) {
					if(!isset($candidate)) {
						$candidate = $queue;
					} elseif($candidate->chronoboosted === INF) {
						$candidate = $queue;
					} elseif($candidate->chronoboosted < $queue->chronoboosted) {
						$candidate = $queue;
					}
				} else {
					if(self::$debug) tracemsg("ProductionQueues::choose(), queue rejected: available at ". simple_time($queue->available) .", but needed at ". simple_time($time));
				}
			}
			if($expendsAll) {
				if(!isset($candidate)) {
					throw_error("No production queue of type <i>". $expend ."</i> is available!",
						"This error message should not occur. Please report this message with your build order on the thread linked at bottom of the page.");
				}
				$queues[] = $candidate;
			}
		}
		if(!isset($candidate)) {
			throw_error("No production queue of type <i>". implode("</i>, or <i>", $expends) ."</i> is available!",
				"This error message should not occur. Please report this message with your build order on the thread linked at bottom of the page.");
		}
		if(!$expendsAll) {
			return array($candidate);
		} else {
			return $queues;
		}
	}
	
	/**
	 * Mark existing queues as destroyed, and create new queues of different
	 * types. The newly created queues inherit tags from the destroyed queues
	 * in the order in which they appear.
	 * @param array $queuesDestroyed
	 * @param float $timeDestroyed
	 * @param array $queueTypesCreated
	 * @param float $timeCreated
	 */
	public function morph($queuesDestroyed, $timeDestroyed, $queueTypesCreated, $timeCreated) {
		if(self::$debug) {
			tracemsg("ProductionQueues::morph(". implode(" + ", $queuesDestroyed) .", ". simple_time($timeDestroyed) .", ".
				implode(" + ", $queueTypesCreated) .", ". simple_time($timeCreated) .")");
		}
		
		// queues destroyed
		foreach($queuesDestroyed as $queue) {
			$queue->destroyed = $timeDestroyed;
		}

		// queues created
		for($i = 0; $i < count($queueTypesCreated); $i++) {
			if($queueTypesCreated[$i] !== null) {
			    if(isset($queuesDestroyed[$i])) {
				    $tag = $queuesDestroyed[$i]->tag;
				}
				if(self::$debug) tracemsg("ProductionQueues::morph(), queue ". $queueTypesCreated[$i] ." gets tag ". ($tag === null ? "null" : $tag));
			    $this->_queues[] = new ProductionQueue($queueTypesCreated[$i], $timeCreated, $tag);
			}
		}
	}

	/**
	 * Find all queues of given structure with one of the given tags
	 * @param Product $structure Structure type of the queues
	 * @param string $tagsRequired
	 * @return array Array of references to the queues
	 */
	public function select($structure, $tagsRequired = null) {
		$queues = array();
		foreach($this->_queues as $queue) {
			if($queue->structure->uid == $structure->uid && !isset($queue->destroyed)) {
				if($tagsRequired === null || (isset($queue->tag) && in_array($queue->tag, $tagsRequired))) {
					$queues[] = $queue;
				}
			}
		}
		return $queues;
	}

	/**
	 * Update all production queues up to given time.
	 * @param float $time
	 */
	public function update($time) {
		$this->_lastUpdated = $time;
	}

	/**
	 * Calculate when the given queue types are available.
	 * @param array $queueTypes
	 * @param bool $expendsAll
	 * @param array $tagsRequired
	 * @return float
	 */
	public function when($queueTypes, $expendsAll, $tagsRequired = null) {
		
		if(!isset($queueTypes) || count($queueTypes) == 0) {
			return $this->_lastUpdated;
		}
		
		// when are production queues available
		if(self::$debug) tracemsg("ProductionQueues::when(), Looking for available queues");
		$queuesAvailable = $expendsAll ? 0 : INF;
		$unavailableQueues = array();

		foreach($queueTypes as $expend) {
			$queues = $this->select($expend, $tagsRequired);
			
			// when is production queue of this type available
			$queueAvailable = INF;
			foreach($queues as $queue) {
				$queueAvailable = min($queueAvailable, $queue->available);
			}
			if(self::$debug) tracemsg("ProductionQueues::when(), ". count($queues) ." Queues of type ". $expend .", earliest available at ". simple_time($queueAvailable));
			if($queueAvailable === INF) {
				$unavailableQueues[] = $expend;
			}
			if($expendsAll) {
				$queuesAvailable = max($queuesAvailable, $queueAvailable);
			} else {
				$queuesAvailable = min($queuesAvailable, $queueAvailable);
			}
		}
		if(self::$debug) tracemsg("ProductionQueues::when(), all queues available at ". simple_time($queuesAvailable));

		// some or all queues are unavailable
		if($queuesAvailable === INF) {
			if(self::$debug) tracemsg("ProductionQueues::when(), no production queue of type <i>". implode("</i>, <i>", $unavailableQueues) ."</i> is available.");
			return array(INF, $unavailableQueues);
		}

		return array(max($this->_lastUpdated, $queuesAvailable), null);
	}
};
?>