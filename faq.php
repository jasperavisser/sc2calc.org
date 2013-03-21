<?php
require("page.php");

class FAQPage extends Page {
	
	/// protected methods
	protected function getTitle() {
		return "FAQ";
	}
	
	protected function renderContent() {
		?>
		<fieldset class="collapsed">
			<legend>How much minerals per second does a probe gather?</legend>
			<div class="collapsible">
				<p>A probe gathers <em>5</em> minerals per roundtrip. Roundtrips consist of the probe moving to a mineral patch, mining a bit, and then returning to the nexus. According to <a href="http://www.teamliquid.net/forum/viewmessage.php?topic_id=140055" target="_blank">this thread by Piousflea</a> on teamliquid forum, the travel time of a probe is <em>3.8</em> for the closest mineral patch or <em>4.8</em> seconds for a farther mineral patch. The time spent mining is <em>2.9</em> sec. Thus, on average a probe will have a roundtrip time of <em>7.2</em> seconds.</p>
				<p>If two probes mine the same mineral patch, they can do so unimpeded. Once one probe is finished mining and starts travelling, the other probe can easily finish mining before the first returns, since the travel time is greater than the time spent mining.</p>
				<p>However, if there are three probes mining the same patch of minerals, there will be not be enough time for two probes to finish mining while the other travels. The limiting factor is the availability of the mineral patch. The three probes will require <em>2.9 * 3 = 8.7</em> seconds to mine in shifts. So the roundtrip time of each probe is increased to <em>8.7</em> seconds.</p>
				<p>A single probe gathers <em>5 / 7.2 = 0.69</em> minerals per second. A second probe on the same patch gathers the same amount of minerals per second. When a third probe is placed on the same patch, the efficiency of all three probes is somewhat reduced, and each will gather <em>5 / 8.7 = 0.57</em> minerals per second.</p>
				<p>Does this mean a third probe on the same patch is useless or even counterproductive? Not at all. Three probes on the same patch do gather more minerals per second than two probes. But they do gather less minerals than three probes on different patches would. It is wise to spread the probes out as much as possible.</p>
				<table class="p">
					<tr>
						<th style="width: 200px;">Number of probes</th>
						<th>Minerals mined</th>
					</tr>
					<tr>
						<td>1</td>
						<td>0.69 / second</td>
					</tr>
					<tr>
						<td>2</td>
						<td>1.38 / second</td>
					</tr>
					<tr>
						<td>3 or more</td>
						<td>1.70 / second</td>
					</tr>
				</table>
				<p>N.B. These calculations apply to a single patch of minerals only. The AI of a probe allows it to move to a different patch of minerals when the one it has previously mined is now occupied.</p>
			</div>
		</fieldset>
		<fieldset class="collapsed">
			<legend>How much gas per second does a probe gather?</legend>
			<div class="collapsible">
				<p>A probe gathers <em>4</em> gas per roundtrip. Roundtrips consist of the probe moving to an assimilator, mining a bit, and then returning to the nexus. According to <a href="http://www.teamliquid.net/forum/viewmessage.php?topic_id=140055" target="_blank">this thread by Piousflea</a> on teamliquid forum, the travel time of a probe is <em>3.6</em> for the closest geyser or <em>5.0</em> seconds for a farther geyser. The time spent mining is <em>2.1</em> sec. Thus, on average a probe will have a roundtrip time of <em>6.4</em> seconds.</p>
				<p>If two probes mine the same assimilator, they can do so unimpeded. Once one probe is finished mining and starts travelling, the other probe can easily finish mining before the first returns, since the travel time is greater than the time spent mining. If the travel time is more than twice the mining time, a third probe can also use the same assimilator without pause.</p>
				<p>If the travel time is less than twice the mining speed, a third probe cannot mine the same assimilator without causing all the probes on that assimilator to have to wait for short periods of time while another finishes mining. 
				<p>A third probe can also be accommodated with delays if the travel time to the assimilator is at least twice the mining time. This is the case if the travel time is at least <em>4.2</em> seconds. If the travel time is less than that, the three probes will add a short pause to their roundtrip as they wait for another probe to finish mining.</p>
				<p>Similarly, a fourth probe can mine to assimilator if the travel time is greater than <em>4.2</em> seconds. Four probes require <em>2.1 * 4 = 8.4</em> seconds to mine in shifts. Since the maximum roundtrip time is <em>7.1</em> seconds, this does require all probes to add a short pause to their roundtrip.</p>
				<p>On average, single probe gathers <em>4 / 6.4 = 0.63</em> gas per second. A second and third probe on the same patch gather the same amount of gas per second. When a fourth probe is placed on the same assimilator, the efficiency of all four probes is severely reduced, and each will gather <em>4 / 8.4 = 0.48</em> gas per second.</p>
				<p>Does this mean a fourth probe on the same patch is useless or even counterproductive? Useless, yes, except if the travel time to the assimilator is on the high end of the spectrum. In that case, a marginal gain can be obtained by mining the assimilator with four probes.</p>
				<table class="p">
					<tr>
						<th style="width: 200px;">Number of probes</th>
						<th>Gas mined</th>
					</tr>
					<tr>
						<td>1</td>
						<td>0.63 / second</td>
					</tr>
					<tr>
						<td>2</td>
						<td>1.25 / second</td>
					</tr>
					<tr>
						<td>3</td>
						<td>1.88 / second</td>
					</tr>
					<tr>
						<td>4 or more</td>
						<td>1.90 / second</td>
					</tr>
				</table>
			</div>
		</fieldset>
		<fieldset class="collapsed">
			<legend>How much many probes are needed to optimize mining?</legend>
			<div class="collapsible">
				<p>That depends somewhat on your definition of optimal mining. The short answer is <em>24</em> probes on minerals and <em>3</em> probes per geyser. More probes will yield very little, if any, additional resources. If it is a high yield mineral expansion, then you need no more than <em>18</em> probes on minerals. That's <em>3</em> probes per patch of minerals.</p>
				<p>The long answer requires that we look at the time it takes for a probe to pay for itself, and take into account when you will be needing those minerals for other purposes. Obviously, the latter is a judgement call based on many factors, ingame and even outside the game. We can however put a number on how long it takes a probe to pay for itself.</p>
				<p>A probe costs <em>50</em> minerals and takes <em>17</em> seconds to produce, ignoring chrono-boost. Any probe that is the first or second to be assigned to a mineral patch, will yield <em>0.69</em> minerals per second. If it is the third probe to be assigned to a mineral patch, it will yield <em>0.32</em> minerals per second. In effect, probes #1 and #2 will mine <em>50</em> minerals in <em>50 / 0.69 = 72.5</em> seconds, and probe #3 in <em>50 / 0.32 = 156.3</em> seconds. Since you pay for a probe in advance, the production time must be included.</p>
				<p>So the first <em>2</em> probes per patch, or the first <em>16</em> probes per base, will each pay for itself in <em>89.5</em> seconds. Similarly, the third probe per patch, or the next <em>8</em> probes per base, will each pay for itself in <em>173.3</em> seconds.</p>
				<p>If you are planning on fighting a game-winning battle before a probe would pay for itself, it may not be worth getting that probe. However, if the battle does not result in a sure win or loss, you probably would've been better off having that additional probe for the long run.</p>
				<p>When you expand, it is worthwhile to transfer any probes over <em>16</em> directly to your expansion, where they can gather minerals more efficiently. As a rule of thumb, it is best to split your probes evenly among your bases.</p>
			</div>
		</fieldset>
		<fieldset class="collapsed">
			<legend>How long does it take for a probe to pay for itself?</legend>
			<div class="collapsible">
				<p>It takes <em>17</em> seconds to produce a probe; after that, the probe will add <em>0.69</em> minerals per second (if it is the first or second probe on a mineral patch) or <em>0.32</em> minerals per second (if it is the third probe on a mineral patch). In economics, this is known as the capitalization rate. The investment of producing a probe, <em>50</em> minerals, is fully capitalized when it has paid for itself.</p>
				<p>Thus, it takes <em>17 + 50 / 0.69 = 89.5</em> seconds for the first and second probe on a mineral patch to pay for itself.</p>
				<p>Similarly, it takes <em>17 + 50 / 0.32 = 183.3</em> seconds for the third probe on a patch to pay for itself.</p>
			</div>
		</fieldset>
		<p>For discussion, please go to <a href="http://www.teamliquid.net/forum/viewmessage.php?topic_id=155279" target="_blank">the teamliquid forum</a>.</p>
		<br/>
	<?php
	}
	
	protected function renderHEAD() {
		parent::renderHEAD();
	}
};

$page = new FAQPage();
?>