<?php
require("page.php");

class ProtossUnitProductionPage extends UnitProductionPage {
	
	/// constructor
	public function __construct() {
		$this->_incomeSnippet = new IncomeSnippet("Probe");
		parent::__construct();
	}
	
	/// protected methods
	protected function getTitle() {
		return "What can a Protoss afford?";
	}
	
	protected function renderHEAD() {
		parent::renderHEAD();
		?>
		<script type="text/javascript"><!--
		
			function updateProduction() {
		
				// gateways or warpgates?
				var reducedTime = $("#transform").html() == "Warpgates";

				// structures & units 
				var amount = [];
				var cycle = [];
				$.each(ProtossStructures, function(i, structure) {
					amount[structure.name] = getInt(structure.code());
					
					// production cycles
					cycle[structure.name] = 0;
					$.each(structure.units, function(i, unit) {
						amount[unit.name] = getInt(unit.code());
						cycle[unit.name] = amount[unit.name] * unit.getTime(reducedTime);
						cycle[structure.name] = cycle[structure.name] + cycle[unit.name];
					});
				});
				
				// include supply cost?
				var Supply = { mineral: $("#includeSupply").attr("checked") ? 12.5 : 0 };
				
				// resource consumption
				var mineralConsumption = 0;
				var gasConsumption = 0;
				$.each(ProtossStructures, function(i, structure) {
					$.each(structure.units, function(i, unit) {
						mineralConsumption = mineralConsumption +
							amount[structure.name] / Math.max(1, cycle[structure.name]) * cycle[unit.name] * (unit.mineral + unit.supply * Supply.mineral) / unit.getTime(reducedTime);
						gasConsumption = gasConsumption +
							amount[structure.name] / Math.max(1, cycle[structure.name]) * cycle[unit.name] * unit.gas / unit.getTime(reducedTime);
					});
				});
				$("#mineralConsumption").html(mineralConsumption.toFixed(2));
				$("#gasConsumption").html(gasConsumption.toFixed(2));
				
				// comparison
				updateComparison(mineralConsumption, gasConsumption);
				
				// produced
				$("#produced ul").empty();
				$.each(ProtossStructures, function(i, structure) {
					$.each(structure.units, function(i, unit) {
						if(cycle[unit.name] * amount[structure.name]) {
							$("#produced ul").append("<li>1 " + unit.name + " per " + Math.ceil(cycle[structure.name] / (amount[structure.name] * amount[unit.name])) + " seconds</li>");
						}
					});
				});
			}
			
			$(function() {
			
				// tooltips
				$.each(ProtossUnits, function(i, unit) {
					$("#tooltips").append(unit.tooltip());
					$("#" + unit.code()).ezpz_tooltip({contentId: unit.code() + "_tooltip"});
				});
				
				// monitor input
				$("#transform").click(function() {
					$("#transform").html($("#transform").html() == "Gateways" ? "Warpgates" : "Gateways");
					update();
				});
				
				$("#4gate").click(function() {
					$("#unitProduction input").val("");
					$("#Nexus").val(1);
					$("#Probe").val(1);
					$("#Gateway").val(4);
					$("#transform").html("Warpgates");
					$("#Zealot").val(2);
					$("#Stalker").val(2);
					$("#Sentry").val(1);
					update();
				});
				
				$("#2gate1robo").click(function() {
					$("#unitProduction input").val("");
					$("#Nexus").val(1);
					$("#Probe").val(1);
					$("#Gateway").val(2);
					$("#transform").html("Warpgates");
					$("#Zealot").val(3);
					$("#Stalker").val(3);
					$("#Sentry").val(1);
					$("#RoboticsFacility").val(1);
					$("#Colossus").val(1);
					update();
				});
				
				$("#2gate1star").click(function() {
					$("#unitProduction input").val("");
					$("#Nexus").val(1);
					$("#Probe").val(1);
					$("#Gateway").val(2);
					$("#transform").html("Warpgates");
					$("#Zealot").val(3);
					$("#Stalker").val(3);
					$("#Sentry").val(1);
					$("#Stargate").val(1);
					$("#VoidRay").val(1);
					update();
				});
			});
		//--></script>
		<?php
		$this->_incomeSnippet->renderHead();
	}

	protected function renderProductionForm() {
		?>
		<fieldset class="collapsed">
			<legend>How to use</legend>
			<p class="collapsible">Choose the number of buildings in your base, and choose the mixture of units that will be warped out of these buildings. Only the ratio of units is required, not the actual desired number of units in your army. For example, 2 warpgates with a unit mixture of <em>Z 2, St 2, Se 1</em> means that you have 2 warpgates in your base, and you warp in an army that consists of an equal number of zealots and stalkers and half as many sentries.</p>
			<p class="collapsible">Click on <em>Warpgates</em> to switch between Gateways and Warpgates.</p>
		</fieldset>
		<fieldset id="unitProduction">
			<legend>Unit production</legend>
			<div class="collapsible">
				<table class="p">
					<tr>
						<th style="width: 300px;">Buildings</th>
						<th colspan="5">Units produced</th>
					</tr>
					<tr>
						<td><input type="text" id="Nexus" size="2" value="1"/> Nexi</td>
						<td class="right">P <input type="text" id="Probe" size="2" value="1"/></td>
						<td class="right">M <input type="text" id="Mothership" size="2"/></td>
						<td></td>
						<td></td>
						<td></td>
					</tr>
					<tr>
						<td><input type="text" id="Gateway" size="2" value="4"/> <em id="transform">Warpgates</em></td>
						<td class="right">Z <input type="text" id="Zealot" size="2" value="2"/></td>
						<td class="right">St <input type="text" id="Stalker" size="2" value="2"/></td>
						<td class="right">Se <input type="text" id="Sentry" size="2" value="1"/></td>
						<td class="right">HT <input type="text" id="HighTemplar" size="2"/></td>
						<td class="right">DT <input type="text" id="DarkTemplar" size="2"/></td>
					</tr>
					<tr>
						<td><input type="text" id="RoboticsFacility" size="2"/> Robotics Facilities</td>
						<td class="right">O <input type="text" id="Observer" size="2"/></td>
						<td class="right">I <input type="text" id="Immortal" size="2"/></td>
						<td class="right">WP <input type="text" id="WarpPrism" size="2"/></td>
						<td class="right">Co <input type="text" id="Colossus" size="2"/></td>
						<td></td>
					</tr>
					<tr>
						<td><input type="text" id="Stargate" size="2"/> Stargates</td>
						<td class="right">P <input type="text" id="Phoenix" size="2"/></td>
						<td class="right">VR <input type="text" id="VoidRay" size="2"/></td>
						<td class="right">Ca <input type="text" id="Carrier" size="2"/></td>
						<td></td>
						<td></td>
					</tr>
				</table>
				<p><input type="checkbox" id="includeSupply" checked> Include the cost of pylon required</p>
			</div>
		</fieldset>
		<fieldset>
			<legend>Some examples</legend>
			<ul class="collapsible">
				<li><em id="4gate">4 Gate</em></li>
				<li><em id="2gate1robo">2 Gate, 1 Robo: Colossus</em></li>
				<li><em id="2gate1star">2 Gate, 1 Stargate: Voidray</em></li>
			</ul>
		</fieldset>
		<?php
		$this->_incomeSnippet->render();
	}
}

new ProtossUnitProductionPage();
?>