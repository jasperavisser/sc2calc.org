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

require("include.php");
require("../page.php");

/**
 * Render the build order page.
 */
class BuildOrderPage extends Page {

	/// public members

	/**
	 * The build order to initially show.
	 * @var string
	 */
	public $buildOrder;
	
	/// constructor

	/**
	 * Create a new build order page.
	 * @param <type> $buildOrder
	 */
	public function __construct($buildOrder) {
		$this->buildOrder = $buildOrder;
		parent::__construct();
	}
	
	/// protected methods

	/**
	 * Get a desciptive title for this page.
	 * @return string
	 */
	protected function getTitle() {
		return "Build order calculator";
	}

	/**
	 * Render the page body.
	 */
	protected function  renderContent() {
		?>
		<p>You can use this page to calculate the optimal timing of your build order.</p>
		<p>You will need to write the build order in a manner that the calculator can understand. The allowed syntax of build orders is described in some detail in <a href="syntax.php" target="_blank">the syntax guide</a>. Or you can simply look at the examples provided below. The syntax is designed to be easily readable by humans, so it should be fairly easy to pick up.</p>
		<fieldset class="collapsed">
			<legend>Examples</legend>
			<div class="collapsible">
				<ul>
					<li><em id="4gate">4 Gate</em> <small>(see <a href="http://wiki.teamliquid.net/starcraft2/4_Warpgate_Rush" target="_blank">Liquipedia</a>)</small></li>
					<li><em id="1010gate">10/10 Gate</em> <small>(see <a href="http://wiki.teamliquid.net/starcraft2/10/10_Gate_(vs._Protoss)" target="_blank">Liquipedia</a>)</small></li>
					<li><em id="1gatestar">1 Gateway Stargate</em> <small>(see <a href="http://wiki.teamliquid.net/starcraft2/1_Gateway_Stargate_(vs._Terran)" target="_blank">Liquipedia</a>)</small></li>
					<li><em id="111">111 (a.k.a. Destiny Cloudfist)</em> <small>(see <a href="http://wiki.teamliquid.net/starcraft2/1Rax_1Fact_1Port" target="_blank">Liquipedia</a>)</small></li>
					<li><em id="3raxstim">3 Rax Stim</em> <small>(see <a href="http://wiki.teamliquid.net/starcraft2/3_Rax_Stim_(vs._Terran)" target="_blank">Liquipedia</a>)</small></li>
					<li><em id="6pool">6 Pool</em> <small>(see <a href="http://wiki.teamliquid.net/starcraft2/6_pool" target="_blank">Liquipedia</a>)</small></li>
					<li><em id="14pool15hatch">14 Pool, 15 Hatch</em> <small>(see <a href="http://wiki.teamliquid.net/starcraft2/14_pool_15_hatch" target="_blank">Liquipedia</a>)</small></li>
				</ul>
			</div>
		</fieldset>
		<fieldset>
			<legend>Calculation</legend>
			<div class="collapsible">
				<p><a class="reminder" href="list.php" target="_blank">Complete list of units &amp; structures</a>Fill out your build order below.</p>
				<textarea id="buildOrder" class="code" rows="5"><?php echo htmlspecialchars($this->buildOrder); ?></textarea>
				<p><input type="button" class="update" value="Calculate"/></p>
			</div>
			<div class="collapsible">
				<div id="timing"><p>Click the link above to get the timing of your build order.</p></div>
			</div>
		</fieldset>
		<fieldset class="collapsed">
			<legend>Version</legend>
			<div class="collapsible">
				<h3>0.7</h3>
				<ul>
					<li>Updated to follow Patch 1.4.0 & 1.4.2</li>
				</ul>
				<h3>0.6.4</h3>
				<ul>
					<li>Updated to follow Patch 1.3.3</li>
					<li>Fixed: Building a Zerg structure with a resource trigger would sometimes cause an error (thanks to duban)</li>
					<li>Fixed: Extractor trick did not check for prerequisites of whatever was built (thanks to Slybeetle)</li>
				</ul>
				<h3>0.6.3</h3>
				<ul>
					<li>Updated to follow Patch 1.3.0</li>
					<li>Fixed: Incorrect build time of Siege Tank (thanks to Ragwortshire &amp; Slybeetle)</li>
				</ul>
				<h3>0.6.2</h3>
				<ul>
					<li>Fixed: Comments with non-alphanumeric characters were not accepted (thanks to SlyBeetle)</li>
					<li>Fixed: Incorrect cost of Air Armor (thanks to SlyBeetle)</li>
					<li>Fixed: Incorrect build time of Ghost (thanks to SlyBeetle)</li>
					<li>Added command to build something, then cancel it, e.g. 14 Fake Hatchery</li>
					<li>Added command to kill a previously built unit, e.g. 22 Kill Zealot</li>
				</ul>
				<h3>0.6.1</h3>
				<ul>
					<li>Documented comment syntax (thanks to shingbi)</li>
					<li>Fixed: Syntax was not always case-insensitive (thanks to Ragwortshire)</li>
					<li>Fixed: Incorrect build time for Spore Crawler (thanks to Ragwortshire)</li>
					<li>Fixed: Morph time was added to busy time of production queues (thanks to Ragwortshire)</li>
				</ul>
				<h3>0.6</h3>
				<ul>
					<li>Updated to follow Patch 1.2.0</li>
				</ul>
				<h3>0.5.7</h3>
				<ul>
					<li>Fixed: Multitude of errors when using chronoboost (thanks to DFarce)</li>
					<li>Fixed: Chronoboosts taking place after the last job would report wrong amount of energy remaining (thanks to anourkey)</li>
				</ul>
				<h3>0.5.6</h3>
				<ul>
					<li>Fixed: Abilities could be used by spellcasters that were in production (thanks to Drae)</li>
					<li>Fixed: Warning messages when swapping an addon (thanks to itgl72)</li>
					<li>Fixed: Workers were rallied to bases that were still in production (thank to Darko)</li>
					<li>Fixed: Rounding error caused some jobs to be postponed incorrectly (thank to Darko)</li>
				</ul>
				<h3>0.5.5</h3>
				<ul>
					<li>Fixed: Could not transfer workers to a geyser that had been started, but not yet completed. The transfers are now delayed until the geyser is completed. (thanks to CarbonTwelve)</li>
					<li>Fixed: Larva produced at the exact time a Spawn Larvae expires could not be used (thanks to jacobman)</li>
					<li>Fixed: Warnings when building an addon (thanks to itgl72)</li>
					<li>Fixed: Addon didn't inherit tag from the structure it was built on (thanks to Intolerant)</li>
				</ul>
				<h3>0.5.4</h3>
				<ul>
					<li>Fixed: Drones from extractor trick would not mine (thanks to King of Town)</li>
					<li>Fixed: Larva generated was produced 15 seconds after dropping below 3 larvae. The correct behavior is to postpone larva generation while a hatchery has 3+ larvae, but not reset the timer (thanks to icezar)</li>
					<li>Fixed: Explicitly building drones could cause calculator to warn about insufficient supply capacity (thanks to Fritti)</li>
					<li>Fixed: Some transfer jobs were reported out-of-order (thanks to Arta)</li>
					<li>Fixed: Chronoboost failed on jobs triggered by amount of resources (thanks to EntropyFails)</li>
				</ul>
				<h3>0.5.3</h3>
				<ul>
					<li>More flexible syntax for extractor tricks, e.g. 10 Double Extractor trick into Roach</li>
					<li>Fixed: Calldown: Extra Supplies did not increase supply capacity (thanks to Presence)</li>
					<li>Fixed: Hatchery could be morphed into a Queen (thanks to icezar)</li>
				</ul>
				<h3>0.5.2</h3>
				<ul>
					<li>Implemented Proxy structures, e.g. 10 Scout (30 seconds), then Proxy Gateway</li>
					<li>Fixed: Error after workers were taken off gas and all gas was sent (thanks to icezar)</li>
					<li>Fixed: Startup build delay was ignored (thanks to CarbonTwelve)</li>
					<li>Fixed: Recurring jobs would fail if the first job on a line (thanks to koj)</li>
				</ul>
				<h3>0.5.1</h3>
				<ul>
					<li>Rewritten the description of the syntax of build orders</li>
					<li>Fixed: Error when transferring probes off gas (thanks to slith)</li>
					<li>Fixed: Extractor trick would give an error (thanks to King of Town)</li>
					<li>Fixed: Double extractor trick would consume 1 larva (thanks to King of Town)</li>
					<li>Fixed: Larvae were only generated at 15 second interval (thanks to Hurkyl)</li>
				</ul>
				<h3>0.5</h3>
				<ul>
					<li>New layout</li>
					<li>Reimplemented sending workers early when building a structure, e.g. 12 Gateway (send @120 minerals)</li>
					<li>Fixed: Error when chronoboosting (thanks to shingbi)</li>
				</ul>
				<h3>0.4.5</h3>
				<ul>
					<li>You can now tag a spellcaster and refer back to the tagged spellcaster when using abilities, e.g. <br/>14 Queen #bertha, 18 Spawn Larvae from #bertha</li>
					<li>You can now tag a hatchery and refer back to the tagged hatchery when building Zerg units, e.g. <br/>14 Hatchery #natural, 18 Roach from #natural</li>
					<li>Recurring jobs can be canceled, e.g. 30 Cancel Drone</li>
				</ul>
				<h3>0.4.4</h3>
				<ul>
					<li>You can now tag a production queue and refer back to the tagged queue when buildings units, upgrades or morphs, e.g. 12 Barracks #1, 16 Marine from #1</li>
					<li>Fixed: Automatic MULE would still not be used as much as possible (thanks again to FaZ-)</li>
				</ul>
				<h3>0.4.3</h3>
				<ul>
					<li>Fixed: No proper warning if supply capacity was insufficient (thanks to suckit987)</li>
					<li>Fixed: In some cases, a warning would be shown that a production queue was not available, when in fact it was</li>
				</ul>
				<h3>0.4.2</h3>
				<ul>
					<li>Fixed: Automatic MULE would not be used as much as possible (thanks to FaZ-, Eeryck &amp; shingbi)</li>
					<li>Fixed: Nydus Worm was missing (thanks to Nolari)</li>
					<li>More descriptive error messages</li>
				</ul>
				<h3>0.4.1</h3>
				<ul>
					<li>Fixed: Spawn Larvae would be delayed until end of timeline (thanks to KingKiron)</li>
					<li>Fixed: Spawn Larvae on multiple Queens would cause an error (thanks to KeyserSoze &amp; icezar)</li>
					<li>Fixed: Repeated job with a resource trigger would require the same resource amount for each repeat (thanks to Intolerant)</li>
				</ul>
				<h3>0.4</h3>
				<ul>
					<li>New scheduler algorithm</li>
					<li>Recurring jobs, e.g. 6 Drone [auto]</li>
					<li>Results now show times larva are generated</li>
					<li>Added MULE, Extra Supplies &amp; Scanner Sweep abilities</li>
					<li>Fixed: Spawn Larvae could be queued multiple times on the same hatchery (thanks to Tsabo)</li>
					<li>Fixed: No error was displayed when trying to morph a Warpgate without having an unmorphed Gateway (thanks to Corvette)</li>
					<li>Fixed: Double extractor trick cost was 75 minerals (thanks to Sidus &amp; Lisky)</li>
					<li>Fixed: Extractor trick didn't refund minerals (thanks to Sidus &amp; Lisky)</li>
					<li>Regression: Workers are not sent early when this is specified, e.g. 12 Gateway (send @120 minerals)</li>
				</ul>
				<h3>0.3.10</h3>
				<ul>
					<li>Fixed: Scouting at a fixed supply count could cause negative mineral counts (thanks to icezar)</li>
					<li>Fixed: In some cases, larvae would go unused (thanks to Phrencys &amp; Sidus)</li>
					<li>Fixed: In some cases, an error would occur that no hatcheries were producing larvae (thanks to Bitters)</li>
					<li>Extractor trick</li>
				</ul>
				<h3>0.3.9</h3>
				<ul>
					<li>Fixed: Spawn Larvae could be cast on uncompleted Hatcheries (thanks to icezar)</li>
					<li>Fixed: In some cases, an error would occur that no hatcheries were producing larvae (thanks to icezar)</li>
					<li>Fixed: In some cases, Spawn Larvae would delay earlier larva generation (thanks to icezar)</li>
				</ul>
				<h3>0.3.8</h3>
				<ul>
					<li>Worker can be sent early when building a structure, e.g. 12 Gateway (send @120 minerals)</li>
					<li>Startup delay for mineral mining, e.g. #Startup mining delay = 3 seconds</li>
					<li>Startup delay for worker production, e.g. #Startup build delay = 3 seconds</li>
				</ul>
				<h3>0.3.7</h3>
				<ul>
					<li>Fixed: Prerequisites were not accepted if they appeared later in the build order (thanks to Bitters)</li>
					<li>Zerg results now show available larvae</li>
					<li>Worker transfer travel time can be specified, e.g. 13 Assimilator &gt; transfer 3 probes (3 seconds lost)</li>
				</ul>
				<h3>0.3.6</h3>
				<ul>
					<li>Fixed: Spawn Larvae was not delayed until a Hatchery was available (thanks to kidcrash89)</li>
				</ul>
				<h3>0.3.5</h3>
				<ul>
					<li>Results show supply capacity</li>
					<li>Units are delayed until sufficient supply capacity exists</li>
					<li>Fixed: Building a hatchery would delay drone production (thanks to Deathfairy)</li>
				</ul>
				<h3>0.3.4</h3>
				<ul>
					<li>Fixed: Spawn Larvae build time was displayed as 2.5 seconds (its cooldown timer) (thanks to Sidus &amp; ylmson)</li>
					<li>Fixed: Spawn Larvae could be placed earlier in timeline (thanks to ylmson)</li>
				</ul>
				<h3>0.3.3</h3>
				<ul>
					<li>Fixed: error on jobs with resource trigger (thanks to Sidus)</li>
					<li>Jobs can be queued after Spawn Larvae completes, e.g. 14 Spawn Larvae &gt; Zergling [4]</li>
				</ul>
				<h3>0.3.2</h3>
				<ul>
					<li>Fixed: Starport build time incorrectly at 25 seconds (thanks to Sleight)</li>
					<li>Fixed: Structures depending on Lair could not be built (thanks to QwiXXeR)</li>
					<li>Results show evolution of income</li>
					<li>Results give permalink to build order</li>
				</ul>
				<h3>0.3.1</h3>
				<ul>
					<li>Fixed: Strict mode delays structures until a unit is completed (thanks to ylmson &amp; Sidus)</li>
					<li>Fixed: Initial Hatchery spawns with only 1 larva (thanks to Sidus)</li>
					<li>Fixed: Zerg structures, upgrades and morphs use larvae (thanks to Sidus)</li>
					<li>Fixed: Error on transferring workers (thanks to Zazaodh)</li>
				</ul>
				<h3>0.3</h3>
				<ul>
					<li>Zerg support</li>
					<li>Checkpoints show resources gathered at a given time</li>
					<li>Results show energy surplus</li>
				</ul>
				<h3>0.2.2</h3>
				<ul>
					<li>Results show production queue usage</li>
				</ul>
				<h3>0.2.1</h3>
				<ul>
					<li>Tactical Nuke, Interceptor &amp; Salvage Bunker</li>
					<li>Strict Mode (read Limitations for details)</li>
					<li>Better error messages for invalid add-ons</li>
				</ul>
				<h3>0.2</h3>
				<ul>
					<li>Terran support</li>
					<li>Show Chrono Boost &amp; MULE in timing</li>
					<li>Updated to follow Patch 1.1.2</li>
				</ul>
				<h3>0.1</h3>
				<ul>
					<li>Protoss support</li>
				</ul>
			</div>
		</fieldset>
		<p>For discussion, please go to <a href="http://www.teamliquid.net/forum/viewmessage.php?topic_id=159994&currentpage=All" target="_blank">the teamliquid forum</a>.</p>
		<br/>
		<br/>
		<?php
	}

	/**
	 * Render the page head.
	 */
	protected function renderHEAD() {
		parent::renderHEAD();
		?>
		<link rel="stylesheet" type="text/css" href="../datatables.css"/>
		<script type="text/javascript" src="../jquery.autogrowtextarea.js"></script>
		<script type="text/javascript" src="../jquery.datatables.js"></script>
		<script type="text/javascript"><!--
			$(function() {
			
				$("textarea").corner("8px keep");
				$("code.p").corner("8px");
				$("table.code tr.first td:first-child").corner("8px top");
				$("table.code tr.last td:first-child").corner("8px bottom");

				$("textarea").autogrow().focus();
				
				$(".update").click(function() {
					$.get("update.php", { buildOrder: $("#buildOrder").val()}, function(response) {
						$("#timing").html(response);
						$("#timing").effect("highlight", {}, 1500);
						$(".display td:last-child").addClass("last");
						$(".display").dataTable({
							bFilter: false,
							bPaginate: false,
							bInfo: false,
							aaSorting: [[0, "asc"]],
							fnRowCallback: function(nRow, aData, iDisplayIndex) {
								if(iDisplayIndex == 0) {
									$(nRow).addClass("first");
								} else {
									$(nRow).removeClass("first");
								}
								return nRow;
							}
						});
					});
				});
				
				$("#1010gate").click(function() {
					$("#buildOrder").val("10 Pylon\n" +
						"10 Gateway [2]\n" +
						"12 Zealot* [3]\n" +
						"18 Pylon");
					$("#buildOrder").trigger("change");
				});
				$("#4gate").click(function() {
					$("#buildOrder").val(
						"9 Pylon\n" + 
						"10 Probe*\n" +
						"12 Gateway and scout\n" +
						"12 Probe*\n" +
						"14 Assimilator, then put 3 probes on gas (2 seconds)\n" +
						"16 Pylon\n" +
						"18 Cybernetics Core\n" +
						"19 Zealot\n" +
						"23 Pylon\n" +
						"24 Stalker\n" +
						"26 Warpgate**\n" +
						"27 Gateway\n" +
						"28 Sentry\n" +
						"30 Gateway [2], then Transform to Warpgate [4]\n" +
						"31 Pylon");
					$("#buildOrder").trigger("change");
				});
				$("#1gatestar").click(function() {
					$("#buildOrder").val(
						"9 Pylon\n" +
						"10 Probe*\n" +
						"12 Gateway\n" +
						"12 Probe*\n" +
						"14 Assimilator, then put 3 probes on gas (2 seconds)\n" +
						"15 Probe*\n" +
						"16 Pylon\n" +
						"18 Cybernetics Core\n" +
						"18 Assimilator, then put 3 probes on gas (2 seconds)\n" +
						"18 Probe*\n" +
						"21 Stalker\n" +
						"23 Probe*\n" +
						"24 Stargate\n" +
						"24 Pylon\n" +
						"25 Warpgate\n" +
						"26 Stalker\n" +
						"29 Void Ray**\n");
					$("#buildOrder").trigger("change");
				});
				$("#111").click(function() {
					$("#buildOrder").val(
						"10 Supply Depot\n" +
						"12 Barracks\n" +
						"13 Refinery, then put 3 SCVs on gas (2 seconds)\n" +
						"15 Orbital Command, then constant Calldown: MULE\n" +
						"15 Refinery, then put 3 SCVs on gas (2 seconds)\n" +
						"15 Marine\n" +
						"16 Supply Depot and Marine [2]\n" +
						"19 Factory\n" +
						"20 Reactor on Barracks\n" +
						"21 Starport\n" +
						"22 Tech Lab on Factory\n" +
						"23 Swap Tech Lab on Factory to Starport\n");
					$("#buildOrder").trigger("change");
				});
				$("#3raxstim").click(function() {
					$("#buildOrder").val(
						"10 Supply Depot\n" +
						"12 Barracks\n" +
						"14 Refinery, then put 3 SCVs on gas (2 seconds)\n" +
						"16 Orbital Command, then constant Calldown: MULE\n" +
						"17 Barracks, then Tech Lab on Barracks, then Stimpack\n" +
						"18 Barracks, then Reactor on Barracks\n");
					$("#buildOrder").trigger("change");
				});
				$("#6pool").click(function() {
					$("#buildOrder").val(
						"6 Spawning Pool\n" +
						"7 Zergling [3]\n" +
						"10 Overlord\n" +
						"10 Queen\n" +
						"12 Zergling\n");
					$("#buildOrder").trigger("change");
				});
				$("#14pool15hatch").click(function() {
					$("#buildOrder").val(
						"9 Overlord\n" +
						"14 Spawning Pool (send @175 minerals)\n" +
						"15 Hatchery, then transfer 8 drones (10 seconds lost)\n" +
						"16 Extractor, then transfer 3 drones (2 seconds lost)\n" +
						"16 Queen, then Spawn Larvae\n" +
						"18 Overlord\n");
					$("#buildOrder").trigger("change");
				});
				<?php if(strlen($this->buildOrder) > 0) { ?>
					$(".update").trigger("click");
				<?php } ?>
			});
		//--></script>
		<?php
	}
};

new BuildOrderPage(_GET("buildOrder"));
?>
