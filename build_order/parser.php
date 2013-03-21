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

define("RegexWorker", "((worker|probe|drone|SCV'?)s?)");
define("RegexDelay", "(\(-?(?P<delay>\d+)\s*s(ec(onds)?)?(\s+lost)?\))");
define("RegexInitiate", "(\(send\s+". RegexWorker ."?\s*(@|at\s+)(?P<initiate_amount>\d+)\s+(?P<initiate_resource>gas|minerals?)\))");
function RegexProduct($tag = "product") {
	return "(?P<". $tag .">[0-9a-zA-Z :-]+)";
}

/**
 * Parser reads a text-based build order and extracts jobs from it.
 */
class Parser {

	/// public members

	/**
	 * @var array List of checkpoints read
	 */
	public $checkpoints;

	/**
	 * @var array List of options read
	 */
	public $options;

	/**
	 * @var array List of jobs read
	 */
	public $jobs;

	/// constructor

	/**
	 * Parse a build order
	 * @param string $buildOrder
	 */
	public function __construct($buildOrder) {
		Logger::enter("Parser::_construct");

		$this->checkpoints = array();
		$this->options = array();
		$this->jobs = array();

		// parse line-by-line
		$lines = preg_split("/([\n])/", $buildOrder);
		foreach($lines as $lineNumber => $line) {
			$this->read($lineNumber, trim($line));
		}
		if(count($this->jobs) == 0) {
			throw_error("No commands found in build order!",
				"Your build order is empty. For a quick demonstration of the workings of this calculator, click one of the examples listed under <i>Examples of complete build orders</i>.");
		}

		Logger::leave("Parser::_construct");
	}

	/// public methods

	/**
	 * Parse a single line of a build order
	 * @param int $lineNumber
	 * @param string $line
	 */
	public function read($lineNumber, $line) {
		global $ScoutingWorker, $Drone;
		
		$line = str_replace(",", "", $line);
		
		$jobStack = array();

		// split line into commands
		$commands = preg_split("/(&|>|\s+and\s+|\s+then\s+)/", $line, null, PREG_SPLIT_DELIM_CAPTURE);
		for($i = 0; $i < count($commands); $i += 2) {
			$command = trim($commands[$i]);
			$operator = $i == 0 ? null : trim($commands[$i - 1]);
			
			// wipe slate
			unset($job);
			unset($product);
			unset($triggerGas);
			unset($triggerMineral);
			unset($triggerSupply);

			// extract trigger from command
			if(empty($operator)) {

				// trigger is a supply number
				if(preg_match("/^(?P<supply>\d+)\s+(?P<command>.+)$/", $command, $terms)) {
					$triggerSupply = (int)$terms["supply"];
					$command = trim($terms["command"]);

				// trigger is a resource number
				} elseif(preg_match("/^@(?P<amount>\d+)\s+(?P<resource>minerals?|gas)\s+(?P<command>.+)$/", $command, $terms)) {
					if(strcasecmp($terms["resource"], "gas") == 0) {
						$triggerGas = $terms["amount"];
					} else {
						$triggerMineral = $terms["amount"];
					}
					$command = trim($terms["command"]);
				}
			}

			// repeating or recurring
			unset($repeat);
			$recurring = false;
			if(preg_match("/^\s*(?P<command>.*)\s*\[(?P<repeat>\d+)\]\s*$/", $command, $terms)) {
				$repeat = (int)$terms["repeat"];
				$command = trim($terms["command"]);
			} elseif(preg_match("/^\s*(?P<command>.*)\s*\[(?P<repeat>auto)\]\s*$/i", $command, $terms)) {
				$recurring = true;
				$command = trim($terms["command"]);
			} elseif(preg_match("/^\s*(?P<repeat>constant)\s+(?P<command>.*)\s*$/i", $command, $terms)) {
				$recurring = true;
				$command = trim($terms["command"]);
			}

			// required tags
			if(preg_match("/^\s*(?P<command>.*?)\s+from\s+(?P<tags>#[a-zA-Z0-9]+(\s*(\s+and\s+)?\s*#[a-zA-Z0-9]+)*)\s*$/i", $command, $terms)) {
				if(preg_match_all("/#(?<tags>[a-zA-Z0-9]+)/", $terms["tags"], $tags)) {
					$tagsRequired = $tags["tags"];
					$command = trim($terms["command"]);
				} else {
					unset($tagsRequired);
				}
			} else {
				unset($tagsRequired);
			}

			// tag
			if(preg_match("/^\s*(?P<command>.*?)\s*#(?P<tag>[a-zA-Z0-9]+)\s*$/", $command, $terms)) {
				$tag = $terms["tag"];
				$command = trim($terms["command"]);
			} else {
				unset($tag);
			}

			// chrono boost
			if(preg_match("/^\s*(?P<command>.*?)\s*(?P<chronoboost>\*+)\s*$/", $command, $terms)) {
				$chronoboost = strlen($terms["chronoboost"]);
				$command = trim($terms["command"]);
			} else {
				$chronoboost = 0;
			}

			// skip empty command
			if($command == "") {
				continue;
			}

			// scout
			if(preg_match("/^\s*scout\s*". RegexDelay ."?/i", $command, $terms)) {
				if(!empty($terms["delay"])) {
					$delay = (int)$terms["delay"];
				} else {
					$delay = 0;
				}
				$job = new ScoutJob($delay);
				
			// option
			} elseif(preg_match("/^\s*#(?P<name>[\w\s]+)=(?P<value>[a-zA-Z0-9\s]+)$/i", $command, $terms)) {
				$this->options[trim($terms["name"])] = trim($terms["value"]);

			// comment
			} elseif(preg_match("/^\s*#.*$/i", $command, $terms)) {

			// transfer workers to product constructed in previous job
			} elseif(preg_match("/^\s*(transfer\s+|\s*[+])(?P<workers>\d+)\s*". RegexWorker ."?\s*". RegexDelay ."?$/i", $command, $terms)) {

				// transfer target
				unset($transferTarget);
				if(count($jobStack) != 0 && array_top($jobStack)->productBuilt() !== null) {
					$transferTarget = array_top($jobStack)->productBuilt();
				}

				if(count($jobStack) > 0 && $transferTarget->type & Base) {
					$mutation = new TransferMutation((int)$terms["workers"]);
				} elseif(count($jobStack) > 0 && $transferTarget->type & Geyser) {
					$mutation = new Mutation(-$terms["workers"], $terms["workers"]);
				} else {
					throw_error("Line <i>". ($lineNumber + 1) ."</i> : Transfer workers where?",
						"You can only use the syntax <em>transfer 3 workers</em> or <em>+3</em> directly after a job that builds a base or a geyser. In other cases, please write something like <em>put 3 workers on gas</em> or <em>+3 on gas</em>.");
				}
				if(isset($terms["delay"])) {
					$mutation->delay = (int)$terms["delay"];
				}
				$job = new MutateJob($mutation);

			// transfer workers off one resource to another
			} elseif(preg_match("/^\s*(?P<verb>put|take|-|[+])\s*(?P<workers>\d+)(\s+". RegexWorker .")?\s+(?P<preposition>on|off)\s+(?P<resource>gas|minerals?)\s*". RegexDelay ."?/i", $command, $terms)) {
				$positive = (strcasecmp($terms["verb"], "put") == 0 || $terms["verb"] == "+");
				if($positive xor strcasecmp($terms["preposition"], "on") == 0) {
					throw_error("Line <i>". ($lineNumber + 1) ."</i> : You can't <i>". ($positive ? "put" : "take") ."</i> miners <i>". $terms["preposition"] ."</i> a resource.");
				}
				if($positive xor strcasecmp($terms["resource"], "gas") == 0) {
					$mutation = new Mutation($terms["workers"], -$terms["workers"]);
				} else {
					$mutation = new Mutation(-$terms["workers"], $terms["workers"]);
				}
				if(isset($terms["delay"])) {
					$mutation->delay = (int)$terms["delay"];
				}
				$job = new MutateJob($mutation);

			// checkpoint
			} elseif(preg_match("/^\s*(?P<minutes>[0-9]{1,2}):(?<seconds>[0-9]{2})\s+checkpoint\s*$/i", $command, $terms)) {
				$this->checkpoints[] = (int)$terms["minutes"] * 60 + (int)$terms["seconds"];
				//tracemsg("Checkpoint at ". ((int)$terms["minutes"] * 60 + (int)$terms["seconds"]) ." seconds");

			// cancel
			} elseif(preg_match("/^Cancel ". RegexProduct() ."\s*$/i", $command, $terms)) {

				// create job
				$product = Product::byName(trim($terms["product"]));
				if(empty($product)) {
					throw_error("Line <i>". ($lineNumber + 1) ."</i> : Unknown object <i>". $terms["product"] ."</i>",
						"For a complete list of units, structures, upgrades, morphs and abilities, please refer to <a href=\"list.php\" target=\"_blank\">this list</a>. If you are trying to do something other than building, please check the <i>single line examples</i> for the syntax of the other commands. The syntax is not case-sensitive, but it is very specific in the spelling.");
				} else {
					$job = new CancelJob($product);
				}

			// trick
			} elseif(preg_match("/^(?P<plural>double\s+)?". RegexProduct("pledge_product") ."\s+trick(\s+into\s+". RegexProduct("turn_product") .")?\s*$/i", $command, $terms)) {

				// parse pledge
				$pledgeProduct = Product::byName($terms["pledge_product"]);
				if(empty($pledgeProduct)) {
					throw_error("Line <i>". ($lineNumber + 1) ."</i> : Unknown object <i>". $terms["product"] ."</i>",
						"For a complete list of units, structures, upgrades, morphs and abilities, please refer to <a href=\"list.php\" target=\"_blank\">this list</a>. If you are trying to do something other than building, please check the <i>single line examples</i> for the syntax of the other commands. The syntax is not case-sensitive, but it is very specific in the spelling.");
				}
				$pledgeCount = empty($terms["plural"]) ? 1 : 2;

				// parse turn
				if(empty($terms["turn_product"])) {
					$turnProduct = $Drone;
					$turnCount = $pledgeCount;
				} else {
					$turnProduct = Product::byName($terms["turn_product"]);
					if(empty($turnProduct)) {
						throw_error("Line <i>". ($lineNumber + 1) ."</i> : Unknown object <i>". $terms["product"] ."</i>",
							"For a complete list of units, structures, upgrades, morphs and abilities, please refer to <a href=\"list.php\" target=\"_blank\">this list</a>. If you are trying to do something other than building, please check the <i>single line examples</i> for the syntax of the other commands. The syntax is not case-sensitive, but it is very specific in the spelling.");
					}
					$turnCount = isset($repeat) ? $repeat : 1;
				}
				unset($repeat);

				// create job
				$job = new TrickJob($pledgeProduct, $pledgeCount, $turnProduct, $turnCount);

			// fake
			} elseif(preg_match("/^\s*fake ". RegexProduct("pledge_product") ."\s*$/i", $command, $terms)) {

				// parse pledge
				$pledgeProduct = Product::byName($terms["pledge_product"]);
				if(empty($pledgeProduct)) {
					throw_error("Line <i>". ($lineNumber + 1) ."</i> : Unknown object <i>". $terms["product"] ."</i>",
						"For a complete list of units, structures, upgrades, morphs and abilities, please refer to <a href=\"list.php\" target=\"_blank\">this list</a>. If you are trying to do something other than building, please check the <i>single line examples</i> for the syntax of the other commands. The syntax is not case-sensitive, but it is very specific in the spelling.");
				}
				$pledgeCount = 1;

				// parse turn
				$turnProduct = null;
				$turnCount = 0;

				// create job
				$job = new TrickJob($pledgeProduct, $pledgeCount, $turnProduct, $turnCount);

			// kill
			} elseif(preg_match("/^\s*kill ". RegexProduct("product") ."\s*$/i", $command, $terms)) {

				// parse product
				$product = Product::byName($terms["product"]);
				if(empty($product)) {
					throw_error("Line <i>". ($lineNumber + 1) ."</i> : Unknown object <i>". $terms["product"] ."</i>",
						"For a complete list of units, structures, upgrades, morphs and abilities, please refer to <a href=\"list.php\" target=\"_blank\">this list</a>. If you are trying to do something other than building, please check the <i>single line examples</i> for the syntax of the other commands. The syntax is not case-sensitive, but it is very specific in the spelling.");
				} elseif($product->type & Structure) {
					throw_error("Line <i>". ($lineNumber + 1) ."</i> : Cannot kill structure <i>". $terms["product"] ."</i>");
				}

				// create job
				$job = new KillJob($product);

			// build
			} elseif(preg_match("/^(?P<proxy>proxy\s+)?". RegexProduct() ."(?P<priority>\s*!)?\s*". RegexInitiate ."?$/i", $command, $terms)) {

				// create job
				$product = Product::byName(trim($terms["product"]));
				if(empty($product)) {
					throw_error("Line <i>". ($lineNumber + 1) ."</i> : Unknown command <i>". $command ."</i>",
						"For a complete list of units, structures, upgrades, morphs and abilities, please refer to <a href=\"list.php\" target=\"_blank\">this list</a>. If you are trying to do something other than building, please check the <i>single line examples</i> for the syntax of the other commands. The syntax is not case-sensitive, but it is very specific in the spelling.");
				} else {
					$job = new BuildJob($product);
				}

				// initiate at given resource amount
				if(isset($terms["initiate_amount"]) && isset($terms["initiate_resource"])) {
					if(strcasecmp($terms["initiate_resource"], "gas") == 0) {
						$job->initiateGas = (int)$terms["initiate_amount"];
					} else {
						$job->initiateMineral = (int)$terms["initiate_amount"];
					}
				}

				// proxy
				if(!empty($terms["proxy"])) {
					$job->queueTypeExpended = $ScoutingWorker;
				}

				// priority job
				if(isset($terms["priority"])) {
					$job->superPriority = true;
				}

			// unknown command
			} else {
				throw_error("Line <i>". ($lineNumber + 1) ."</i> : Unknown command <i>". $command ."</i>",
					"For a complete list of units, structures, upgrades, morphs and abilities, please refer to <a href=\"list.php\" target=\"_blank\">this list</a>. If you are trying to do something other than building, please check the <i>single line examples</i> for the syntax of the other commands. The syntax is not case-sensitive, but it is very specific in the spelling.");
			}

			if(isset($job)) {

				// trigger is the previous job
				if(!isset($triggerGas) && !isset($triggerMineral) && !isset($triggerSupply)) {
					if(count($jobStack) == 0) {
						throw_error("Line <i>". ($lineNumber + 1) ."</i> : There is no trigger to this job.",
							"The trigger for a job can either by a supply count (for example <em>12 Gateway</em>) or an amount of resources (for example <em>@100 gas take 3 off gas</em>). A job that appears at the start of a line must have one of these triggers.");
					} else {
						$dependency = new Dependency(array_top($jobStack), ($operator == ">" || strcasecmp($operator, "then") == 0) ? Dependency::AtCompletion : Dependency::AtStart);
					}
				}

				// set triggers
				if(isset($dependency)) {
					$job->dependency = $dependency;
				}
				if(isset($triggerGas)) {
					$job->triggerGas = $triggerGas;
				}
				if(isset($triggerMineral)) {
					$job->triggerMineral = $triggerMineral;
				}
				if(isset($triggerSupply)) {
					$job->triggerSupply = $triggerSupply;
				}

				// if job is recurring and its production queue appears in the
				// stack, tag the queue and have job require that tag
				if(!isset($tagsRequired) && isset($product) && isset($product->expends)
					&& ($recurring)) {
					unset($queueJob);
					for($j = count($jobStack) - 1; $j >= 0 && !isset($queueJob); $j--) {
						if($jobStack[$j]->productBuilt() !== null) {
							foreach($product->expends as $expended) {
								if($expended->uid == $jobStack[$j]->productBuilt()->uid) {
									$queueJob = $jobStack[$j];
									break;
								}
							}
						}
					}
					if(isset($queueJob)) {
						if(!isset($queueJob->tag)) {
							$queueJob->tag = uniqid();
						}
						$tagsRequired = array($queueJob->tag);
					}
				}

				// tag
				if(isset($tag)) {
					$job->tag = $tag;
				}

				// require tags
				if(isset($tagsRequired)) {
					$job->tagsRequired = $tagsRequired;
				}

				// chrono boost
				if($chronoboost && (!isset($product) || ($product->type & Structure) || ($product->type & Protoss) == 0)) {
					throw_error("Line <i>". ($lineNumber + 1) ."</i> : Could not chrono boost <i>". $command ."</i>");
				}
				$job->chronoboost = $chronoboost;

				// add job(s)
				$this->jobs[] = $job;
				$jobStack[] = $job;

				// repeat job
				for($j = 1; $j < (isset($repeat) ? $repeat : 1); $j++) {
					$job = clone $job;
					$job->dependency = new Dependency(array_top($jobStack), Dependency::AtStart);
					if(isset($job->triggerSupply)) {
						$job->triggerSupply += $job->supplyCost();
					}
					if(isset($product) && ($product->type & Morph)) {
						$job->tagsRequired = null;
					}
					unset($job->triggerGas);
					unset($job->triggerMineral);
					$this->jobs[] = $job;
					$jobStack[] = $job;
				}

				// recur job after the first go
				if($recurring) {
					$job = clone $job;
					$job->dependency = new Dependency(array_top($jobStack), Dependency::AtStart);
					unset($job->triggerGas);
					unset($job->triggerMineral);
					unset($job->triggerSupply);
					$job->recurring = $recurring;
					$this->jobs[] = $job;
				}
			}
		}
	}
};
?>
