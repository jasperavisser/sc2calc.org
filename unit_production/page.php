<?php
require("include.php");

require("../page.php");
require("incomeSnippet.php");

abstract class UnitProductionPage extends Page {
	
	/// protected members
	protected $_incomeSnippet;
	
	/// protected methods
	protected function renderContent() {
		$this->renderIntroduction();
		$this->renderProductionForm();
		?>
		<fieldset id="comparison">
			<legend>Can you afford this?</legend>
			<div class="collapsible">
				<p>Below is the amount of resources used to produce your army, assuming that all buildings are continually producing units.</p>
				<table class="p">
					<tr>
						<th style="width: 150px;">Resource</th>
						<th class="right" style="width: 150px;">Mined</th>
						<th class="right" style="width: 150px;">Consumed</th>
						<th class="right" style="width: 150px;">Surplus</th>
						<th class="right" style="width: 200px;">Consumed / Mined</th>
					</tr>
					<tr>
						<td>Minerals</td>
						<td class="right"><em class="mineralMined">?</em> / sec</td>
						<td class="right"><em id="mineralConsumption">?</em> / sec</td>
						<td class="right"><em class="mineralSurplus">?</em> / sec</td>
						<td class="right"><em id="mineralRatio">?</em></td>
					</tr>
					<tr>
						<td>Vespene Gas</td>
						<td class="right"><em class="gasMined">?</em> / sec</td>
						<td class="right"><em id="gasConsumption">?</em> / sec</td>
						<td class="right"><em class="gasSurplus">?</em> / sec</td>
						<td class="right"><em id="gasRatio">?</em></td>
					</tr>
				</table>
				<p>What this means is that continually producing units from all buildings costs <em class="surplus">?</em> than your bases will produce.</p>
				<p>There are additional places to sink your resources into, such as new buildings, expansions and upgrades, which will limit the amount of resources available for growing your army. It's also quite alright if the percentage of resources you can spend exceeds 100%. In many circumstances, buildings may be idle for short periods. It's usually better to have a bit too much production capacity.</p>
				<p>N.B. All calculations above assume the game speed is set to <em>Normal</em>. On <em>Faster</em> speed, which is common for ladder games, everything is 21% faster. You mine 21% more per second, and you produce units 21% faster. For the purpose of comparing the resources mined to the resources spent, this balances out.</p>
			</div>
		</fieldset>
		<?php $this->renderProduced(); ?>

		<fieldset class="collapsed">
			<legend>Version</legend>
			<div class="collapsible">
				<h3>1.1.0</h3>
				<ul>
					<li>Updated to follow Patch 1.2.0</li>
				</ul>
				<h3>1.0.1</h3>
				<ul>
					<li>Fixed: If last base was high yield, all patches in previous bases would also be counted as high yield (thanks to alcapwned).</li>
				</ul>
			</div>
		</fieldset>

		<p>For discussion, please go to <a href="http://www.teamliquid.net/forum/viewmessage.php?topic_id=155279" target="_blank">the teamliquid forum</a>.</p>
		<br/>
		<br/>
		<div id="tooltips"></div>
		<?php
	}
	
	protected function renderHEAD() {
		parent::renderHEAD();
		?>
		<script type="text/javascript" src="helper.js"></script>
		<script type="text/javascript"><!--

			function update() {
				
				// highlights
				$("input:not([readonly])").each(
					function() {
						$(this).css("background", isNaN(parseInt($(this).val())) ? "white" : "greenyellow");
					}
				);
				
				// production
				updateProduction();
			}
		
			function updateComparison(mineralConsumption, gasConsumption) {
				
				// resources mined
				var mineralMined = $("#mineralMined").html();
				var gasMined = $("#gasMined").html();
				
				// calculate consumed / mined
				var mineralRatio = mineralConsumption / mineralMined * 100;
				if(mineralConsumption == 0 && mineralMined == 0) {
					mineralRatio = 0;
				}
				var gasRatio = gasConsumption / gasMined * 100;
				if(gasConsumption == 0 && gasMined == 0) {
					gasRatio = 0;
				}
				$("#mineralRatio").html(mineralRatio.toFixed(0) + "%");
				$("#gasRatio").html(gasRatio.toFixed(0) + "%");
				
				// calculate surplus
				var surplus = [];
				if(mineralConsumption == 0 && mineralMined == 0) {
				} else if(mineralRatio == Number.POSITIVE_INFINITY) {
					surplus = surplus.concat("infinitely more minerals");
				} else {
					surplus = surplus.concat(Math.abs(mineralRatio - 100).toFixed(0) + "% " + (mineralRatio > 100 ? "more" : "less") + " minerals");
				}
				if(gasConsumption == 0 && gasMined == 0) {
				} else if(gasRatio == Number.POSITIVE_INFINITY) {
					surplus = surplus.concat("infinitely more gas");
				} else {
					surplus = surplus.concat(Math.abs(gasRatio - 100).toFixed(0) + "% " + (gasRatio > 100 ? "more" : "less") + " gas");
				}
				$(".surplus").html(surplus.join(" and "));
				
				// calculate absolute surplus
				var mineralSurplus = mineralMined - mineralConsumption;
				var gasSurplus = gasMined - gasConsumption;
				$(".mineralSurplus").html(mineralSurplus.toFixed(2));
				$(".gasSurplus").html(gasSurplus.toFixed(2));
			}
			
			$(function() {
				// monitor input
				$("input").change(update);
				update();
			});
		//--></script>
		<script type="text/javascript"><!--
			var _gaq = _gaq || [];
			_gaq.push(['_setAccount', 'UA-18765033-1']);
			_gaq.push(['_trackPageview']);

			(function() {
				var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
				ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
				var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
			})();
		//--></script>
		<?php
	}
	
	protected function renderIntroduction() {
		?>
		<p>This doodad will help you determine how many unit-producing structures your bases can support.</p>
		<?php
	}
	
	protected function renderProduced() {
		?>
		<fieldset id="produced">
			<legend>Units produced</legend>
			<div class="collapsible">
				<p>If you were able to afford continually producing units from all buildings, you would produce:</p>
				<ul>
				</ul>
			</div>
		</fieldset>
		<?php
	}

	/// abstract protected methods
	abstract protected function renderProductionForm();
}
?>
