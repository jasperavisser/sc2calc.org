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
 * Logged function call.
 */
class Call {

	/// public members

	/**
	 * Name of function called
	 * @var string 
	 */
	public $function;

	/**
	 * Time in microseconds the function was called
	 * @var int 
	 */
	public $time;

	/// constructor

	/**
	 * Create new logged function call.
	 * @param string $function
	 * @param int $time
	 */
	public function  __construct($function, $time) {
		$this->function = $function;
		$this->time = $time;
	}
};

/**
 * Logger keeps track of number of calls to some functions, and the time spent
 * in those functions.
 */
class Logger {

	/**
	 * Associative array of the number of calls to logged functions,
	 * where the function name is the key.
	 * @var array
	 */
	public static $callCount;

	/**
	 * Associative array of time spent in logged functions, where
	 * the function name is the key.
	 * @var array 
	 */
	public static $timeSpent;

	/**
	 * Stack of function calls in progress.
	 * @var array
	 */
	private static $_stack = array();

	/**
	 * Log a new function call, noting the time the function was entered
	 * @param string $function
	 */
	public static function enter($function) {
		$time = microtime(true);
		array_push(self::$_stack, new Call($function, $time));
		if(isset(self::$callCount[$function])) {
			self::$callCount[$function]++;
		} else {
			self::$callCount[$function] = 1;
		}
	}

	/**
	 * Close a function call, noting the time the function was left
	 * @param string $function
	 */
	public static function leave($function) {
		$time = microtime(true);
		$call = array_pop(self::$_stack);
		if($call->function != $function) {
			die("Trying to leave function <i>". $function ."</i>, but the top of the stack is <i>". $call->function ."</i>");
		}
		if(isset(self::$timeSpent[$function])) {
			self::$timeSpent[$function] += $time - $call->time;
		} else {
			self::$timeSpent[$function] = $time - $call->time;
		}
	}

	/**
	 * Generate report of time spent in logged functions.
	 */
	public static function report() {
		echo "<table id=\"pool\" class=\"display\" cellpadding=0 cellspacing=0>".
			"<thead>".
				"<tr>".
					"<th>Function</th>".
					"<th>Time spent</th>".
					"<th>Calls</th>".
				"</tr>".
			"</thead>".
			"<tbody>";
		foreach(self::$timeSpent as $function => $timeSpent) {
			echo "<tr><td class=\"left\">". $function ."</td><td class=\"center\">". number_format($timeSpent, 4) ." seconds</td><td class=\"center\">". self::$callCount[$function] ."</td></tr>";
		}
		echo "</tbody></table>";
	}
};
?>
