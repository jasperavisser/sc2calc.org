<?php
require("page.php");

class TerranUnitProductionPage extends UnitProductionPage {
	
	/// constructor
	public function __construct() {
		$this->_incomeSnippet = new IncomeSnippet("SCV", true);
		parent::__construct();
	}
	
	/// protected methods
	protected function getTitle() {
		return "What can a Terran afford?";
	}
	
	protected function renderHEAD() {
		parent::renderHEAD();
		?>
		<script type="text/javascript"><!--
		
			function updateProduction() {

				// terrans never get reduced time
				var reducedTime = false;
			
				// structures & units 
				var amount = [];
				var cycle = [];
				$.each(TerranStructures, function(i, structure) {
					amount[structure.code()] = getInt(structure.code());
					
					// production cycles
					cycle[structure.code()] = 0;
					$.each(structure.units, function(i, unit) {
						var unitCode = structure.code() + "_" + unit.code();
						amount[unitCode] = getInt(unitCode);
						cycle[unitCode] = amount[unitCode] * unit.getTime(reducedTime) / structure.multiplier;
						cycle[structure.code()] = cycle[structure.code()] + cycle[unitCode];
					});
				});
				
				// include supply cost?
				var Supply = { mineral: $("#includeSupply").attr("checked") ? 12.5 : 0 };
				
				// resource consumption
				var mineralConsumption = 0;
				var gasConsumption = 0;
				$.each(TerranStructures, function(i, structure) {
					$.each(structure.units, function(i, unit) {
						var unitCode = structure.code() + "_" + unit.code();
						mineralConsumption = mineralConsumption +
							amount[structure.code()] / Math.max(1, cycle[structure.code()]) * cycle[unitCode] * (unit.mineral + unit.supply * Supply.mineral) / (unit.getTime(reducedTime) / structure.multiplier);
						gasConsumption = gasConsumption +
							amount[structure.code()] / Math.max(1, cycle[structure.code()]) * cycle[unitCode] * unit.gas / (unit.getTime(reducedTime) / structure.multiplier);
					});
				});
				$("#mineralConsumption").html(mineralConsumption.toFixed(2));
				$("#gasConsumption").html(gasConsumption.toFixed(2));
				
				// comparison
				updateComparison(mineralConsumption, gasConsumption);
				
				// unit production rate
				var rate = [];
				$.each(TerranStructures, function(i, structure) {
					$.each(structure.units, function(i, unit) {
						var unitCode = structure.code() + "_" + unit.code();
						if(cycle[unitCode] * amount[structure.code()]) {
							if(rate[unit.code()] === undefined) {
								rate[unit.code()] = 0;
							}
							rate[unit.code()] = rate[unit.code()] + 
								amount[structure.code()] * amount[unitCode] / cycle[structure.code()];
						}
					});
				});
				
				// units produced
				$("#produced ul").empty();
				$.each(TerranUnits, function(i, unit) {
					if(rate[unit.code()] !== undefined) {
						$("#produced ul").append("<li>1 " + unit.name + " per " + Math.ceil(1 / rate[unit.code()]) + " seconds</li>");
					}
				});
				
			}
			
			$(function() {
				
				// tooltips
				$.each(TerranUnits, function(i, unit) {
					$("#tooltips").append(unit.tooltip());
				});
				$.each(TerranStructures, function(i, structure) {
					$.each(structure.units, function(i, unit) {
						var unitCode = structure.code() + "_" + unit.code();
						$("#" + unitCode).ezpz_tooltip({contentId: unit.code() + "_tooltip"});
					});
				});
				
				// examples
				$("#3raxmm").click(function() {
					$("#unitProduction input:not(:hidden)").val("");
					$("#CommandCenter").val(1);
					$("#CommandCenter_SCV").val(1);
					$("#BarracksReactor").val(1);
					$("#BarracksTechLab").val(2);
					$("#BarracksTechLab_Marauder").val(1);
					update();
				});
				$("#111").click(function() {
					$("#unitProduction input:not(:hidden)").val("");
					$("#CommandCenter").val(1);
					$("#CommandCenter_SCV").val(1);
					$("#BarracksReactor").val(1);
					$("#FactoryTechLab").val(1);
					$("#FactoryTechLab_Hellion").val(1);
					$("#FactoryTechLab_SiegeTank").val(1);
					$("#FactoryTechLab_Thor").val(1);
					$("#StarportTechLab").val(1);
					$("#StarportTechLab_Banshee").val(1);
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
			<p class="collapsible">Choose the number of buildings in your base, and choose the mixture of units that will be trained out of these buildings. Only the ratio of units is required, not the actual desired number of units in your army. For example, 2 barracks with tech labs with a unit mixture of <em>M 1, Md 2, R 0, G 1</em> means that you have 2 barracks with tech labs in your base, and you train an army that consists of an equal number of marines and ghosts and twice as many marauders.</p>
		</fieldset>
		<fieldset id="unitProduction">
			<legend>Unit production</legend>
			<div class="collapsible">
				<table class="p">
					<tr>
						<th  style="width: 300px;">Buildings</th>
						<th colspan="5">Units produced</th>
					</tr>
					<tr>
						<td><input type="text" size="2" id="CommandCenter" value="1"/> Command Centers</td>
						<td colspan="5"><input type="hidden" id="CommandCenter_SCV" value="1"/>Only SCVs</td>
					</tr>
					<tr>
						<td><input type="text" size="2" id="Barracks"/> Barracks</td>
						<td colspan="5"><input type="hidden" id="Barracks_Marine" value="1"/>Only Marines</td>
					</tr>
					<tr>
						<td><input type="text" size="2" id="BarracksReactor" value="1"/> Barracks w/ Reactor</td>
						<td colspan="5"><input type="hidden" id="BarracksReactor_Marine" value="1"/>Only Marines</td>
					</tr>
					<tr>
						<td><input type="text" size="2" id="BarracksTechLab" value="2"/> Barracks w/ Tech Labs</td>
						<td class="right">M  <input type="text" size="2" id="BarracksTechLab_Marine"/></td>
						<td class="right">Md <input type="text" size="2" id="BarracksTechLab_Marauder" value="1"/></td>
						<td class="right">R  <input type="text" size="2" id="BarracksTechLab_Reaper"/></td>
						<td class="right">G  <input type="text" size="2" id="BarracksTechLab_Ghost"/></td>
						<td></td>
					</tr>
					<tr>
						<td><input type="text" size="2" id="Factory"/> Factories</td>
						<td colspan="5"><input type="hidden" id="Factory_Hellion" value="1"/>Only Hellions</td>
					</tr>
					<tr>
						<td><input type="text" size="2" id="FactoryReactor"/> Factories w/ Reactor</td>
						<td colspan="5"><input type="hidden" id="FactoryReactor_Hellion" value="1"/>Only Hellions</td>
					</tr>
					<tr>
						<td><input type="text" size="2" id="FactoryTechLab"/> Factories w/ Tech Labs</td>
						<td class="right">H  <input type="text" size="2" id="FactoryTechLab_Hellion"/></td>
						<td class="right">ST <input type="text" size="2" id="FactoryTechLab_SiegeTank"/></td>
						<td class="right">T  <input type="text" size="2" id="FactoryTechLab_Thor"/></td>
						<td></td>
						<td></td>
					</tr>
					<tr>
						<td><input type="text" size="2" id="Starport"/> Starports</td>
						<td class="right">V  <input type="text" size="2" id="Starport_Viking"/></td>
						<td class="right">Me <input type="text" size="2" id="Starport_Medivac"/></td>
						<td></td>
						<td></td>
						<td></td>
					</tr>
					<tr>
						<td><input type="text" size="2" id="StarportReactor"/> Starports w/ Reactor</td>
						<td class="right">V  <input type="text" size="2" id="StarportReactor_Viking"/></td>
						<td class="right">Me <input type="text" size="2" id="StarportReactor_Medivac"/></td>
						<td></td>
						<td></td>
						<td></td>
					</tr>
					<tr>
						<td><input type="text" size="2" id="StarportTechLab"/> Starport w/ Tech Labs</td>
						<td class="right">V  <input type="text" size="2" id="StarportTechLab_Viking"/></td>
						<td class="right">Me <input type="text" size="2" id="StarportTechLab_Medivac"/></td>
						<td class="right">R  <input type="text" size="2" id="StarportTechLab_Raven"/></td>
						<td class="right">B  <input type="text" size="2" id="StarportTechLab_Banshee"/></td>
						<td class="right">BC <input type="text" size="2" id="StarportTechLab_Battlecruiser"/></td>
					</tr>
				</table>
				<p><input type="checkbox" id="includeSupply" checked /> Include the cost of supply depots required</p>
			</div>
		</fieldset>
		<fieldset>
			<legend>Some examples</legend>
			<ul class="collapsible">
				<li><em id="3raxmm">3 Rax: Marine + Marauder</em></li>
				<li><em id="111">1 Rax, 1 Factory, 1 Starport</em></li>
			</ul>
		</fieldset>
		<?php
		$this->_incomeSnippet->render();
	}
}

new TerranUnitProductionPage();
?>