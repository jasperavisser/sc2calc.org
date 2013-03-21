<?php
abstract class Page {
	
	/// constructor
	public function __construct() {
		?>
		<!DOCTYPE html>
		<html lang="nl">
			<head>
				<?php $this->renderHEAD(); ?>
			</head>
			<body>
				<?php $this->renderBODY(); ?>
			</body>
		</html>
		<?php
	}
	/// protected methods
	protected function renderBODY() {
		global $pathToRoot;
		?>
		<a href="http://sc2calc.org/" class="banner">
			<h1><?php echo $this->getTitle(); ?></h1>
		</a>
		<div class="menu">
			<table id="menu">
				<tr>
					<td style="width: 120px;" class="group">
						<a href="<?php echo $pathToRoot; ?>build_order/">Build order <img src="http://image.sc2calc.org/arrow_right.gif"/></a>
					</td>
					<td class="group">
						<div class="unfold" style="width: 120px;">
							<a href="<?php echo $pathToRoot; ?>unit_production/">Unit production <img src="http://image.sc2calc.org/arrow_right.gif"/></a>
							<a class="race" href="<?php echo $pathToRoot; ?>unit_production/protoss.php"><img src="http://image.sc2calc.org/protoss.png"/> Protoss</a>
							<a class="race" href="<?php echo $pathToRoot; ?>unit_production/terran.php"><img src="http://image.sc2calc.org/terran.png"/> Terran</a>
							<a class="race" href="<?php echo $pathToRoot; ?>unit_production/zerg.php"><img src="http://image.sc2calc.org/zerg.png"/> Zerg</a>
						</div>
					</td>
					<td>&nbsp;</td>
					<td style="width: 90px;">
						<a class="race" href="<?php echo $pathToRoot; ?>faq.php"><img src="http://image.sc2calc.org/faq.png"/> FAQ</a>
					</td>
				</tr>
			</table>
		</div>
		<div id="tooltips"></div>
		<?php
		$this->renderContent();
		?>
		<div class="footer">
			<ul class="copyleft">
				<li>&copy; 2010 Jasper A. Visser</li>
			</ul>
			<ul>
				<li><a href="<?php echo $pathToRoot; ?>about.php">About</a></li>
				<li><a href="http://www.teamliquid.net/mytlnet/index.php?view=new&to=Haploid" target="_blank">Contact</a></li>
			</ul>
		</div>
		<?php
	}

	protected function renderHEAD() {
		global $pathToRoot;
		?>
		<meta http-equiv="content-type" content="text/html; charset=utf-8">
		<title><?php echo $this->getTitle(); ?> - StarCraft 2</title>
		<link rel="stylesheet" type="text/css" href="<?php echo $pathToRoot; ?>style.css"/>
		<link rel="icon" href="http://image.sc2calc.org/favicon.ico" type="image/x-icon"/>
		<script type="text/javascript" src="<?php echo $pathToRoot; ?>jquery.js"></script>
		<script type="text/javascript" src="<?php echo $pathToRoot; ?>jquery.effects.core.js"></script>
		<script type="text/javascript" src="<?php echo $pathToRoot; ?>jquery.effects.highlight.js"></script>
		<script type="text/javascript" src="<?php echo $pathToRoot; ?>jquery.corner.js"></script>
		<script type="text/javascript" src="<?php echo $pathToRoot; ?>jquery.ezpz.js"></script>
		<script type="text/javascript"><!--
			$(function() {
				// collapsibles
				$("fieldset > legend").click(function() {
					$(this).parent().toggleClass("collapsed");
				});
				
				// menu unfold
				$("table#menu div.unfold").each(function(){
					$(this).hover(function(){
						$(this).animate({width: "380px"}, {queue: false, duration: 500});
					},function() {
						$(this).animate({width: "120px"}, {queue: false, duration: 500});
					});
				});
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

	/// abstract protected methods
	abstract protected function getTitle();
	abstract protected function renderContent();
};

/*

<?php
require("page.php");

class XXXPage extends Page {
	
	/// protected methods
	protected function getTitle() {
		return "XXX";
	}
	
	protected function renderBODY() {
		parent::renderBODY();
		?>
		<?php
	}

	protected function renderContent() {
		?>
		<?php
	}
	
	protected function renderHEAD() {
		parent::renderHEAD();
		?>
		<script type="text/javascript"><!--
			$(function() {
			});
		//--></script>
		<?php
	}
};

new XXXPage();
?>

*/
?>
