<?php
require("page.php");

class ZergUnitProductionPage extends UnitProductionPage {
	
	/// constructor
	public function __construct() {
		$this->_incomeSnippet = new IncomeSnippet("Drone", false, true);
		parent::__construct();
	}
	
	/// protected methods
	protected function getTitle() {
		return "What can a Zerg afford?";
	}
	
	protected function renderHEAD() {
		parent::renderHEAD();
		?>
		<script type="text/javascript"><!--
		
			function updateProduction() {
				
				// auto-overlord
				var autoOverlord = $("#autoOverlord").attr("checked");
				if(autoOverlord) {
					$("#Overlord").parent().hide();
					$("#Overlord").val("");
				} else {
					$("#Overlord").parent().show();
				}
				
				// get unit weights
				var weight = [];
				var supplyConsumption = 0;
				$.each(ZergUnits, function(i, unit) {
					weight[unit.name] = getInt(unit.name) * unit.larva;
					supplyConsumption = supplyConsumption + getInt(unit.name) * unit.supply;
				});
				
				// add overlords
				if($("#autoOverlord").attr("checked")) {				
					weight["Overlord"] = Math.max(0, supplyConsumption / 8);
				}
				
				// normalize unit weights
				var weightTotal = 0;
				$.each(ZergUnits, function(i, unit) {
					weightTotal = weightTotal + weight[unit.name];
				});
				if(weightTotal != 0) {
					$.each(ZergUnits, function(i, unit) {
						weight[unit.name] = weight[unit.name] / weightTotal;
					});
				}
				
				// larva interval
				var larvaInterval = $("#larvaInterval").html();
				
				// resource consumption
				var mineralConsumption = 0;
				var gasConsumption = 0;
				$.each(ZergUnits, function(i, unit) {
					if(unit.larva) {
						mineralConsumption = mineralConsumption + weight[unit.name] * unit.mineral / (unit.larva * larvaInterval);
						gasConsumption = gasConsumption + weight[unit.name] * unit.gas / (unit.larva * larvaInterval);
					}
				});
				$("#mineralConsumption").html(mineralConsumption.toFixed(2));
				$("#gasConsumption").html(gasConsumption.toFixed(2));
				
				// comparison
				updateComparison(mineralConsumption, gasConsumption);
				
				// produced
				$("#produced ul").empty();
				$.each(ZergUnits, function(i, unit) {
					if(weight[unit.name]) {
						$("#produced ul").append("<li>" + (1 / unit.larva) + " " + unit.name + " per " + Math.ceil(larvaInterval / weight[unit.name]) + " seconds</li>");
					}
				});
			}
			
			$(function() {
			
				// tooltips
				$.each(ZergUnits, function(i, unit) {
					$("#tooltips").append(unit.tooltip());
					$("#" + unit.code()).ezpz_tooltip({contentId: unit.code() + "_tooltip"});
				});
				
				$("#ling").click(function() {
					$("#unitProduction input").val("");
					$("#Zergling").val(1);
					update();
				});
				
				$("#lingblingmuta").click(function() {
					$("#unitProduction input").val("");
					$("#Drone").val(1);
					$("#Zergling").val(2);
					$("#Baneling").val(1);
					$("#Mutalisk").val(1);
					update();
				});
				
				$("#roachhydra").click(function() {
					$("#unitProduction input").val("");
					$("#Drone").val(1);
					$("#Roach").val(2);
					$("#Hydralisk").val(1);
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
			<div class="collapsible">
				<p>Choose the mixture of units spawned from your larvae. For example, (D=1, Z=2) would mean that you spawn drones and twice as many zergling. Only the ratio of units is relevant, so (D=4, Z=8) is exactly the same as (D=1, Z=2). For units that are morphed from other units (Baneling, Brood Lords and Overseers), the cost of the original unit is included (for example, the cost of a Baneling would be 50 mineral and 25 gas).</p>
				<p>N.B. You don't have to add Zergling if you want to spawn Baneling.</p>
			</div>
		</fieldset>
		<fieldset id="unitProduction">
			<legend>Unit production</legend>
			<div class="collapsible">
				<table class="p">
					<tr>
						<th style="width: 150px;">Tier</th>
						<th colspan="5">Units produced</th>
					</tr>
					<tr>
						<td>Tier 0</td>
						<td class="right">D <input type="text" id="Drone" size="2" value="1"/></td>
						<td class="right">O <input type="text" id="Overlord" size="2"/></td>
						<td></td>
						<td></td>
						<td></td>
					</tr>
					<tr>
						<td>Tier 1</td>
						<td class="right">Z <input type="text" id="Zergling" size="2" value="2"/></td>
						<td class="right">B <input type="text" id="Baneling" size="2" value="1"/></td>
						<td class="right">R <input type="text" id="Roach" size="2"/></td>
						<td></td>
						<td></td>
					</tr>
					<tr>
						<td>Tier 2</td>
						<td class="right">OS <input type="text" id="Overseer" size="2"/></td>
						<td class="right">H <input type="text" id="Hydralisk" size="2"/></td>
						<td class="right">M <input type="text" id="Mutalisk" size="2" value="1"/></td>
						<td class="right">C <input type="text" id="Corruptor" size="2"/></td>
						<td class="right">I <input type="text" id="Infestor" size="2"/></td>
					</tr>
					<tr>
						<td>Tier 3</td>
						<td class="right">BL <input type="text" id="BroodLord" size="2"/></td>
						<td class="right">U <input type="text" id="Ultralisk" size="2"/></td>
						<td></td>
						<td></td>
						<td></td>
					</tr>
				</table>
				<p><input type="checkbox" id="autoOverlord" checked> Automatically add overlords as needed</p>
			</div>
		</fieldset>
		<fieldset>
			<legend>Some examples</legend>
			<ul class="collapsible">
				<li><em id="ling">Only Zergling</em></li>
				<li><em id="lingblingmuta">Drones, Zergling, Baneling, Mutalisks in 1:2:1:1 ratio</em></li>
				<li><em id="roachhydra">Drones, Roaches, Hydralisks in 1:2:1 ratio</em></li>
			</ul>
		</fieldset>
		<?php
		$this->_incomeSnippet->render();
	}
	
	protected function renderProduced() {
		?>
		<fieldset id="produced">
			<legend>Units produced</legend>
			<div class="collapsible">
				<p>If you were able to afford continually producing units from all larvae, you would produce:</p>
				<ul>
				</ul>
			</div>
		</fieldset>
		<?php
	}
}

new ZergUnitProductionPage();
?>