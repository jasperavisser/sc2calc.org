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

$logtime = microtime(true);
set_time_limit(1);

require("include.php");
require("logger.php");

Logger::enter("update.php, require()");
require("farm.php");
require("hatchery.php");
require("income.php");
require("job.php");
require("mutation.php");
require("parser.php");
require("product.php");
require("queue.php");
require("scheduler.php");
require("spellcaster.php");
require("timeline.php");
Logger::leave("update.php, require()");

// parse input
$parser = new Parser(_GET("buildOrder"));
$unscheduledJobs = $parser->jobs;
$checkpoints = $parser->checkpoints;
$options = $parser->options;

// check racial consistency
foreach($unscheduledJobs as $job) {
	if($job->race() !== null) {
		if(isset($race) && $job->race() !==$race) {
			throw_error("The build order contains structures or units of more than one race.");
		}
		$race = $job->race();
	}
}
if(!isset($race)) {
	throw_error("The build order contains no units, structures, upgrades or morphs.");
}

// process options
$parsedOptions = array();
$debugFlags = 0;
$parsedOptions["Startup Build Delay"] = 0;
$parsedOptions["Startup Mining Delay"] = 0;
foreach($options as $option => $value) {
	switch(strtolower($option)) {
	case "debug":
		if(preg_match("/(?P<flags>\d+)/i", $value, $terms)) {
			$debugFlags = (int)$terms["flags"];
		}
		break;
	case "startup build delay":
		if(preg_match("/(?P<delay>\d+)\s*s(ec(onds)?)?/i", $value, $terms)) {
			$parsedOptions["Startup Build Delay"] = (int)$terms["delay"];
		}
		break;
	case "startup mining delay":
		if(preg_match("/(?P<delay>\d+)\s*s(ec(onds)?)?/i", $value, $terms)) {
			$parsedOptions["Startup Mining Delay"] = (int)$terms["delay"];
		}
		break;
	}
}
$options = $parsedOptions;

// anchor
$anchor = "./?buildOrder=". urlencode(_GET("buildOrder"));
echo "<p>Copy <a href=\"". $anchor ."\" target=\"_blank\">link to this build</a> to share with friends.</p>";

// initialize timeline
$timeline = new Timeline($race);
$timeline->startupBuildDelay = $parsedOptions["Startup Build Delay"];
sort($checkpoints);
foreach($checkpoints as $checkpoint) {
	$timeline->checkpoints[] = new Checkpoint("Checkpoint", $checkpoint);
}
$timeline->debug = (bool)($debugFlags & 1);
ProductionQueues::$debug = (bool)($debugFlags & ProductionQueues::debugFlag);
Spellcasters::$debug = (bool)($debugFlags & Spellcasters::debugFlag);

// initial income
$timeline->income = new IncomeSlots(50, 0);
$timeline->income->debug = (bool)($debugFlags & 2);
if($options["Startup Mining Delay"] > 0) {
	$income = new IncomeSlot(0, $options["Startup Mining Delay"]);
	$income->mineralMiners = array();
	$timeline->income[] = $income;
	$income = new IncomeSlot($options["Startup Mining Delay"]);
	$income->mineralMiners = array(6);
	$income->basesOperational = array(true);
	$timeline->income[] = $income;
} else {
	$income = new IncomeSlot();
	$income->mineralMiners = array(6);
	$income->basesOperational = array(true);
	$timeline->income[] = $income;
}

// initial conditions
if($race == Protoss) {
	$timeline->spellcasters->add(new Spellcaster($Nexus, 0));
	$timeline->farms->add(new Farm(0, $Nexus->supplyCapacity));
} elseif($race == Terran) {
	$timeline->farms->add(new Farm(0, $CommandCenter->supplyCapacity));
} elseif($race == Zerg) {
	Hatcheries::$debug = (bool)($debugFlags & Hatcheries::debugFlag);
	$timeline->hatcheries->add(new Hatchery(0, 3));
	$timeline->farms->add(new Farm(0, $Hatchery->supplyCapacity));
	$timeline->farms->add(new Farm(0, $Overlord->supplyCapacity));
	//$timeline->queues->add(new ProductionQueue($Hatchery));
}
$timeline->queues->add(new ProductionQueue(Product::designated($race | Base)));
$timeline->supplyCount = 6;

// create recurring worker job
$job = new BuildJob(Product::designated($race | Worker));
$job->recurring = true;
$unscheduledJobs[] = $job;

// schedule jobs
$scheduler = new Scheduler($timeline, $unscheduledJobs);
Scheduler::$debug = (bool)($debugFlags & Scheduler::debugFlag);
$scheduledJobs = $scheduler->schedule();

// render timeline
echo (string)$timeline;

// render queues
$timeEnds = 0;
foreach($scheduledJobs as $job) {
	$timeEnds = max($timeEnds, $job->timeCompleted);
}
?>
<h3>Usage of production queues</h3>
<p>This table shows the busy time of your production queues. Production queues which remain completely unused are not shown. A production queue is considered <i>destroyed</i> when the structure is morphed into another structure, such as a Warpgate or Orbital Command.</p>
<p>The timeline ends at <?php echo simple_time($timeEnds); ?>.</p>
<?php
$timeline->queues->timeEnds = $timeEnds;
echo (string)$timeline->queues;

// render income
?>
<h3>Income</h3>
<p>This table shows the income generated by your workers. The timeline is divided into timeslots, each corresponding with a different distribution of workers. A new timeslot starts when a worker is created, assigned to a job, or transferred to a new base or geyser.</p>
<p>You have mined a total of <em><?php echo simple_round($timeline->income->totalMineral($timeEnds)); ?></em> minerals and <em><?php echo simple_round($timeline->income->totalGas($timeEnds)); ?></em> gas at <?php echo simple_time($timeEnds); ?>.</p>
<?php
echo (string)$timeline->income;

// render hatcheries
if($race == Zerg) {
	?>
	<h3>Larvae generated</h3>
	<p>This table shows the larvae generated by your hatcheries.</p>
	<?php
	$timeline->hatcheries->update($timeEnds);
	echo (string)$timeline->hatcheries;
}

// render function call log
if($debugFlags & 16) Logger::report();

// render page speed
echo "<p><small>Execution time: ". number_format(microtime(true) - $logtime, 4) ." seconds</small></p>";
?>