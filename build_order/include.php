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
 * Path to root of sc2calc.org
 * @var string
 */
$pathToRoot = "../";

/**
 * Obtain the value of a GET parameter, or a given default value if it doesn't
 * exist.
 * @param string $key
 * @param mixed $defaultValue
 * @return mixed
 */
function _GET($key, $defaultValue = null) {
	return isset($_GET[$key])? $_GET[$key] : $defaultValue;
}

/**
 * Remove elements from an array that have a given value.
 * @param array $array
 * @param mixed $remove
 * @return array
 */
function array_remove(&$array, $remove) {
	foreach($array as $key => $value) {
		if($value == $remove) unset($array[$key]);
	}
	return $array;
}

/**
 * Obtain value of last element in array.
 * @param array $array
 * @return mixed
 */
function array_top($array) {
	return $array[count($array) - 1];
}

/**
 * Obtain the first value that is not null.
 * @param mixed $value1
 * @param mixed $value2
 * @return mixed
 */
function coalesce($value1, $value2) {
	return $value1 === null ? $value2 : $value1;
}

/**
 * Determine if a string value can be converted to an integer.
 * @param string $value
 * @return bool 
 */
function is_intval($value) {
	return 1 === preg_match('/^[+-]?[0-9]+$/', $value);
}

/**
 * Obtain the index of the first element in the given array that has the
 * highest.
 * value
 * @param array $array
 * @return mixed
 */
function max_index($array) {
	$maxValue = max($array);
	foreach($array as $key => $value) {
		if($value == $maxValue) {
			$maxKey = $key;
			break;
		}
	}
	return $maxKey;
}

/**
 * Obtain the rounded value of a floating point number. The only difference
 * between this and round() is that round() can return negative zero.
 * @param float $float
 * @return int
 */
function simple_round($float) {
	return round($float) == 0 ? 0 : round($float);
}

/**
 * Convert time in seconds to a string of the form "m:ss". Special cases are if
 * given time is not numeric, or if given time is infinite.
 * @param float $seconds
 * @return string
 */
function simple_time($seconds) {
	if(!is_numeric($seconds)) {
		return "NaN";
	}
	if($seconds === INF) {
		return "&#8734;";
	}
	return ($seconds < 0 ? "-" : "") . floor(abs($seconds) / 60) . ":" . str_pad(round(abs($seconds) % 60), 2, "0", STR_PAD_LEFT);
}

/**
 * Output trace message, for debugging purposes.
 * @param <type> $message
 * @param <type> $level
 */
function tracemsg($message, $level = 0) {
	switch($level) {
	case -1:
		$style = " style=\"font-size: smaller;\"";
		break;
	case 1:
		$style = " style=\"color: blue;\"";
		break;
	case 2:
		$style = " style=\"color: blue;\"";
		break;
	default:
		$style = "";
	}
	echo "<div". $style .">". $message. "</div>";
}

/**
 * Throw an error, with formatted paragraph.
 * @param string $message
 * @param bool $die If true, the function will cause death of the script.
 */
function throw_error($message, $description = null, $die = true) {
	echo "<p style=\"border-left: 3px solid crimson; padding-left: 8px;\"><span class=\"error\">". $message ."</span>";
	if($description !== null) {
		echo "<br/><span style=\"font-size: 10pt;\">". $description ."</span>";
	}
	echo "</p>";
	if($die) die;
}
?>