<?php
class IncomeSnippet {

	/// protected members
	protected $_allowMULE;
	protected $_allowQueen;
	protected $_peonName;

	/// constructor
	public function __construct($peonName, $allowMULE = false, $allowQueen = false) {
		$this->_allowMULE = $allowMULE;
		$this->_allowQueen = $allowQueen;
		$this->_peonName = $peonName;
	}
	
	/// public methods
	public function render() {
		?>
		<fieldset id="income" class="collapsed">
			<legend>Income</legend>
			<div class="collapsible">
				<table id="bases" class="p">
					<tr>
						<th></th>
						<th class="center">Mineral patches</th>
						<th class="center"><?php echo $this->_peonName; ?>s on minerals</th>
						<?php if($this->_allowMULE) { ?><th class="center">MULE</th><?php } ?>
						<?php if($this->_allowQueen) { ?><th class="center">Queen</th><?php } ?>
						<th class="center">Geysers used</th>
					</tr>
					<tr>
						<td><em class="baseType">Normal base</em></td>
						<td class="center"><input type="text" class="patches" size="2" value="8"/></td>
						<td class="center"><input type="text" class="peons" size="2" value="24"/></td>
						<?php if($this->_allowMULE) { ?><td class="center"><input type="checkbox" class="MULE" checked /></td><?php } ?>
						<?php if($this->_allowQueen) { ?><td class="center"><input type="checkbox" class="queen" checked /></td><?php } ?>
						<td class="center"><input type="text" class="geysers" size="1" value="2"/></td>
					</tr>
				</table>
				<p>Click here for <em id="moreBases">more bases</em> or <em id="fewerBases">fewer bases</em>.</p>
				<p>With this distribution, your income will be <em id="mineralMined" class="mineralMined">?</em> minerals per second and <em id="gasMined" class="gasMined">?</em> gas per second. You can find the math behind the income per <?php echo $this->_peonName; ?> in <a href="http://www.teamliquid.net/forum/viewmessage.php?topic_id=140055" target="_blank">this article</a>.</p>
				<?php if($this->_allowQueen) { ?><p>In addition, your bases will spawn a larva every <em id="larvaInterval">?</em> seconds.</p><?php } ?>
				<p class="warnings"></p>
			</div>
		</fieldset>
		<?php
	}
	
	public function renderHead() {
		?>
		<script type="text/javascript"><!--
			function updateIncome() {
			
				var mineralMined = 0;
				var gasMined = 0;
				var larvaSpawned = 0;
				var warnings = [];
				$("#bases tr:not(:first)").each(function() {
					
					var peons = coalesce(parseInt($(".peons", this).val()), 0);
					var patches = coalesce(parseInt($(".patches", this).val()), 0);
					var MULE = $(".MULE", this) ? $(".MULE", this).attr("checked") : false;
					var queen = $(".queen", this) ? $(".queen", this).attr("checked") : false;
					var geysers = coalesce(parseInt($(".geysers", this).val()), 0);
					var highYield = ($(".baseType", this).html() == "High yield base");
					
					mineralMined = mineralMined +
						(Math.min(patches * 2, peons) * 0.7
						+ Math.min(patches, Math.max(0, peons - patches * 2)) * 0.3
						+ (MULE ? 2.9 : 0))
						* (highYield ? (7 / 5) : 1);
					
					gasMined = gasMined + 1.9 * Math.min(2, geysers);
					
					larvaSpawned = larvaSpawned + (1 / 15) + (queen ? (1 / 10) : 0);
					
					if(peons > patches * 3) {
						warnings = warnings.concat("A "+ $(".baseType", this).html() + " with " + patches +" patches of minerals can have at most " + (patches * 3) + " <?php echo $this->_peonName; ?>s mining minerals.");
					}
					if(geysers > 2) {
						warnings = warnings.concat("Bases can have at most 2 geysers.");
					}
				});
				$(".mineralMined").html(mineralMined.toFixed(1));
				$(".gasMined").html(gasMined.toFixed(1));
				<?php if($this->_allowQueen) { ?>
					var larvaInterval = 1 / larvaSpawned;
					$("#larvaInterval").html(larvaInterval.toFixed(1));
				<?php } ?>
				
				// warnings
				if(warnings.length == 0) {
					$("#income .warnings").hide();
					$("#income .warnings").html("");
				} else {
					$("#income .warnings").show();
					$("#income .warnings").html(warnings.join("<br/>"));
				}
				
				update();
			}
			
			$(function() {
				
				// swap mineral type
				$(".baseType").live("click", function() {
					$(this).html($(this).html() == "Normal base" ? "High yield base" : "Normal base");
					var peons = $(".peons", $(this).parents("tr:first"));
					if(peons.val() == 24 && $(this).html() == "High yield base") {
						peons.val(18);
					} else if(peons.val() == 18 && $(this).html() == "Normal base") {
						peons.val(24);
					}
					var patches = $(".patches", $(this).parents("tr:first"));
					if(patches.val() == 8 && $(this).html() == "High yield base") {
						patches.val(6);
					} else if(patches.val() == 6 && $(this).html() == "Normal base") {
						patches.val(8);
					}
					updateIncome();
				});
				
				// more or fewer bases
				$("#fewerBases").click(function() {
					if($("#bases tr").length > 2) {
						$("#bases tr:last").remove();
						updateIncome();
					}
				});
				$("#moreBases").click(function() {
					$("#bases tr:last").clone().appendTo($("#bases"));
					updateIncome();
				});
				
				// update
				$("#income input").live("change", updateIncome);
				updateIncome();
			
			});
		//--></script>
		<?php
	}
}
?>