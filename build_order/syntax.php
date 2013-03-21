<?php
require("include.php");
require("../page.php");

class SyntaxPage extends Page {

	/// protected methods
	protected function getTitle() {
		return "Build order syntax";
	}

	protected function renderContent() {
		?>
		<h2>Content</h2>
		<ul>
			<li><a href="#example">Example</a></li>
			<li><a href="#flow">Flow</a></li>
			<li><a href="#produce">Structures, units &amp; upgrades</a></li>
			<li><a href="#addons">Add-ons</a></li>
			<li><a href="#morphing">Morphing</a></li>
			<li><a href="#transferring">Transferring workers</a></li>
			<li><a href="#repeat">Repeating</a></li>
			<li><a href="#scout">Scouting</a></li>
			<li><a href="#abilities">Abilities</a></li>
			<li><a href="#miscellaneous">Miscellaneous</a></li>
			<li><a href="#realism">Realism</a></li>
		</ul>

		<a name="example"><h2>Example</h2></a>
		<table class="p code">
			<tr class="first">
				<td><code>10 Supply Depot</code></td>
				<td>Build a Supply Depot.</td>
			</tr>
			<tr>
				<td><code>12 Barracks</code></td>
				<td>Build a Barracks.</td>
			</tr>
			<tr>
				<td><code>13 Refinery, then put 3 SCVs on gas (2 seconds)</code></td>
				<td>
					Build a Refinery.<br/>
					When it completes, put 3 SCVs on it.<br/>
					Assume the SCVs lose 2 seconds of mining time.
				</td>
			</tr>
			<tr>
				<td><code>15 Orbital Command, then constant Calldown: MULE</code></td>
				<td>
					Morph the Command Center into an Orbital Command.<br/>
					When it completes, start constant MULE production.
				</td>
			</tr>
			<tr>
				<td><code>15 Refinery, then put 3 SCVs on gas (2 seconds)</code></td>
				<td>
					Build a Refinery.<br/>
					When it completes, put 3 SCVs on it.<br/>
					Assume the SCVs lose 2 seconds of mining time.
				</td>
			</tr>
			<tr>
				<td><code>15 Marine</code></td>
				<td>Build a single Marine.</td>
			</tr>
			<tr>
				<td><code>16 Supply Depot &amp; Marine [2]</code></td>
				<td>
					Build another Supply Depot.<br/>
					When it started building, build two more Marines.
				</td>
			</tr>
			<tr>
				<td><code>19 Factory</code></td>
				<td>Build a Factory.</td>
			</tr>
			<tr>
				<td><code>20 Reactor on Barracks</code></td>
				<td>Build a Reactor on the Barracks.</td>
			</tr>
			<tr>
				<td><code>21 Starport</code></td>
				<td>Build a Starport.</td>
			</tr>
			<tr>
				<td><code>22 Tech Lab on Factory</code></td>
				<td>Build a Tech Lab on the Factory.</td>
			</tr>
			<tr class="last">
				<td><code>23 Swap Tech Lab on Factory to Starport</code></td>
				<td>
					Lift-off the Factory and Starport, then land them in each others previous position.
				</td>
			</tr>
		</table>

		<a name="flow"><h2>Flow</h2></a>
		<dl>
			<dt>line =</dt>
			<dd>fixed_job [glue variable_jobs]</dd>
			<dd>| checkpoint</dd>
			<dd>| comment</dd>
			<dt>fixed_job =</dt>
			<dd>(integer | ("@" integer ("minerals" | "gas"))) job</dd>
			<dt>glue =</dt>
			<dd>"and" | "&" | "then" | ">"</dd>
			<dt>variable_jobs =</dt>
			<dd>job [glue variable_jobs]</dd>
		</dl>
		<p>A build order consists of a number of jobs, such as building structures or transferring workers. The first job on a line must have a trigger, some fixed number of supply, minerals or gas, which must be achieved before the job can be started. These <em>fixed jobs</em> will always be performed in the order in which they appear.</p>
		<table class="p code">
			<tr class="first">
				<td><code>10 Supply Depot</code></td>
				<td>Build a Supply Depot when you have 10 supply.</td>
			</tr>
			<tr>
				<td><code>@100 minerals Supply Depot</code></td>
				<td>Build a Supply Depot when you have 100 minerals.</td>
			</tr>
			<tr class="last">
				<td><code>@100 gas Supply Depot</code></td>
				<td>Build a Supply Depot when you have 100 gas.</td>
			</tr>
		</table>
		<p>Other jobs can be written after the first job on a line. They will be performed after the preceeding job has been started or has completed, depending on the operator written between the jobs. We shall call them <em>variable jobs</em>.</p>
		<table class="p code">
			<tr class="first">
				<td>
					<code>12 Barracks &amp; Refinery<span class="or">or</span></code>
					<code>12 Barracks and Refinery</code>
				</td>
				<td>
					1) Build a Barracks.<br/>
					2) After the Barracks has started building, build a Refinery.
				</td>
			</tr>
			<tr>
				<td>
					<code>12 Barracks &gt; Refinery <span class="or">or</span></code>
					<code>12 Barracks then Refinery <span class="or">or</span></code>
					<code>12 Barracks, then Refinery</code>
				</td>
				<td>
					1) Build a Barracks.<br/>
					2) After the Barracks has completed, build a Refinery.
				</td>
			</tr>
			<tr class="last">
				<td><code>12 Barracks &amp; Refinery &gt; put 3 SCVs on gas</code></td>
				<td>
					1) Build a Barracks.<br/>
					2) After the Barracks has started building, build a Refinery.<br/>
					3) After the Refinery has completed, put 3 SCVs on it.<br/>
				</td>
			</tr>
		</table>
		<p>Unlike fixed jobs, the variable jobs may not be performed in the order in which they appear. The time when they are performed may even be a matter of choice, when there are not enough resources available. As a rule of thumb, <em>fixed jobs take precedence over variable jobs</em> whenever a choice must be made.</p>

		<a name="produce"><h2>Structures, units &amp; upgrades</h2></a>
		<dl>
			<dt>build_job =</dt>
			<dd>object_name [chronoboosts] [tag] [send_worker]</dd>
			<dt>chronoboosts = </dt>
			<dd>{ "*" }</dd>
			<dt>tag =</dt>
			<dd>"#" string</dd>
			<dd>| "from #" string</dd>
		</dl>
		<p>Building anything is as simple as writing down the exact name of the structure, unit or upgrade you want to build. Please use the <a href="list.php" target="_blank">complete list of all structures, unit, upgrades and morphs</a> to find the proper names.</p>
		<table class="p code">
			<tr class="first">
				<td><code>9 Pylon</code></td>
				<td>Build a Pylon when you have 9 supply.</td>
			</tr>
			<tr>
				<td><code>12 Gateway</code></td>
				<td>Build a Gateway when you have 12 supply.</td>
			</tr>
			<tr class="last">
				<td><code>12 Gateway, then Zealot</code></td>
				<td>
					1) Build a Gateway when you have 12 supply.<br/>
					2) After the Gateway has completed, build a Zealot.
				</td>
			</tr>
		</table>
		<p>You can <em>tag</em> any structure or spellcaster with an alphanumeric name, so that you can refer back to it at a later point in the build order. This is useful if you want to build units from one specific structure, or morph a specific structure. If not specified, it is up to the reader of the build order to choose which production queue or spellcaster to use.</p>
		<p>N.B. If you put a unit, upgrade, or morph on the same line after a structure from which it can be built, that structure will always be used.</p>
		<table class="p code">
			<tr class="first">
				<td>
					<code>
						12 Gateway #1<br/>
						...<br/>
						18 Zealot from #1
					</code>
				</td>
				<td>
					1) Build a Gateway.<br/>
					2) At a later point, build a Zealot from that Gateway.
				</td>
			</tr>
			<tr>
				<td>
					<code>
						12 Gateway #bob<br/>
						...<br/>
						18 Zealot from #bob
					</code>
				</td>
				<td>
					1) Build a Gateway.<br/>
					2) At a later point, build a Zealot from that Gateway.
				</td>
			</tr>
			<tr class="last">
				<td>
					<code>
						12 Gateway, then Zealot
					</code>
				</td>
				<td>
					1) Build a Gateway.<br/>
					2) After the Gateway has completed, build a Zealot from that Gateway.
				</td>
			</tr>
		</table>

		<a name="addons"><h2>Add-ons</h2></a>
		<p>The syntax for building add-ons and swapping them between structures is listed in <a href="list.php" target="_blank">this list</a> under Terran Morphs.</p>
		<table class="p code">
			<tr class="first">
				<td><code>14 Tech Lab on Barracks</code></td>
				<td>Build a Tech Lab on a Barracks.</td>
			</tr>
			<tr>
				<td><code>12 Barracks, then Tech Lab on Barracks</code></td>
				<td>
					1) Build a Barracks.<br/>
					2) After the Barracks has completed, build a Tech Lab on it.
				</td>
			</tr>
			<tr>
				<td><code>20 Swap Tech Lab on Barracks to Factory</code></td>
				<td>Swap the Tech Lab from a Barracks to a Factory.</td>
			</tr>
			<tr>
				<td><code>22 Swap Reactor on Barracks with Tech Lab on Factory</code></td>
				<td>Swap the Reactor from a Barracks with the Tech Lab from a Factory.</td>
			</tr>
			<tr>
				<td><code>12 Barracks #1, then Reactor on Barracks #1<br/>15 constant Marine from #1</code></td>
				<td>
					1) Build a Barracks.<br/>
					2) After the Barracks has completed, build a Reactor on it.<br/>
					3) When you reach 15 supply, use that Barracks constantly build Marines.
				</td>
			</tr>
			<tr class="last">
				<td><code>12 Barracks, then Reactor on Barracks, then constant Marine</code></td>
				<td>
					1) Build a Barracks.<br/>
					2) After the Barracks has completed, build a Reactor on it.<br/>
					3) After the Reactor has completed, constantly build Marines from that Barracks.
				</td>
			</tr>
		</table>

		<a name="morphing"><h2>Morphing</h2></a>
		<p>The syntax for unit and structure morphs is listed in <a href="list.php" target="_blank">this list</a> under Protoss, Terran &amp; Zerg Morphs.</p>
		<table class="p code">
			<tr class="first">
				<td><code>15 Orbital Command</code></td>
				<td>Morph a Command Center into an Orbital Command.</td>
			</tr>
			<tr class="last">
				<td><code>20 Gateway, then Transform to Warpgate</code></td>
				<td>
					1) Build a Gateway.<br/>
					2) After the Gateway has completed, morph it to a Warpgate.
				</td>
			</tr>
		</table>

		<a name="transferring"><h2>Transferring workers</h2></a>
		<dl>
			<dt>transfer_job = </dt>
			<dd>("transfer" | "+") integer [worker_name] [transfer_time]</dd>
			<dd>| ("put" | "+") integer [worker_name] "on" ("gas" | "minerals") [transfer_time]</dd>
			<dd>| ("take" | "-") integer [worker_name] "off" ("gas" | "minerals") [transfer_time]</dd>
			<dt>worker_name = </dt>
			<dd>"drones" | "probes" | "SCVs" | "workers"</dd>
		</dl>
		<table class="p code">
			<tr class="first">
				<td>
					<code>
						14 Assimilator &gt; +3<span class="or">or</span><br/>
						14 Assimilator, then transfer 3<span class="or">or</span><br/>
						14 Assimilator, then transfer 3 probes
					</code>
				</td>
				<td>
					1) Build an Assimilator.<br/>
					2) After the Assimilator has completed, put 3 probes on it.
				</td>
			</tr>
			<tr>
				<td>
					<code>
						21 Nexus &gt; +8<span class="or">or</span><br/>
						21 Nexus, then transfer 8<span class="or">or</span><br/>
						21 Nexus, then transfer 8 probes
					</code>
				</td>
				<td>
					1) Build a Nexus.<br/>
					2) After the Nexus has completed, transfer 8 probes to it.
				</td>
			</tr>
			<tr>
				<td>
					<code>
						@100 gas -3 off gas<span class="or">or</span><br/>
						@100 gas take 3 off gas<span class="or">or</span><br/>
						@100 gas take 3 probes off gas
					</code>
				</td>
				<td>After 100 gas has been mined, take 3 probes off gas.</td>
			</tr>
			<tr class="last">
				<td>
					<code>
						18 Queen &gt; +3 on gas<span class="or">or</span><br/>
						18 Queen, then put 3 on gas<span class="or">or</span><br/>
						18 Queen, then put 3 drones on gas
					</code>
				</td>
				<td>
					1) Build a Queen.<br/>
					2) After the Queen has completed, put 3 drones on (back) on gas.
				</td>
			</tr>
		</table>

		<a name="repeat"><h2>Repeating</h2></a>
		<dl>
			<dt>job =</dt>
			<dd>single_job</dd>
			<dd>| single_job "[" (integer | "auto") "]"</dd>
			<dd>| "constant" single_job</dd>
			<dd>| "cancel" object_name</dd>
			<dt>single_job = </dt>
			<dd>build_job| scout_job | transfer_job  | trick_job | fake_job | kill_job</dd>
		</dl>
		<table class="p code">
			<tr class="first">
				<td><code>10 Gateway [2]</code></td>
				<td>Build 2 Gateways when you have 10 supply.</td>
			</tr>
			<tr>
				<td><code>7 Zergling [3]</code></td>
				<td>Build 3 pairs of Zergling, one at 7 supply, another at 8 supply and another at 9 supply.</td>
			</tr>
			<tr>
				<td>
					<code>
						12 Gateway, then Zealot [auto]<span class="or">or</span><br/>
						12 Gateway, then constant Zealot
					</code>
				</td>
				<td>
					1) Build a Gateway when you have 12 supply.<br/>
					2) After the Gateway has completed, constantly build Zealots from that Gateway.
				</td>
			</tr>
			<tr class="last">
				<td>
					<code>
						12 Gateway, then constant Zealot<br/>
						...<br/>
						22 Cancel Zealot
					</code>
				</td>
				<td>
					1) Build a Gateway when you have 12 supply.<br/>
					2) After the Gateway has completed, constantly build Zealots from that Gateway.<br/>
					3) When you have 22 supply, stop constant Zealot production.
				</td>
			</tr>
		</table>
		<p>N.B. <em>Constant jobs</em> will be performed whenever possible without delaying any fixed or variable job. If there are multiple constant jobs (e.g. constant SCV production and constant Marine production), the choice as to which job to prioritize is up to the reader.</p>

		<a name="scout"><h2>Scouting</h2></a>
		<dl>
			<dt>scout_job =</dt>
			<dd>"scout"</dd>
		</dl>
		<table class="p code">
			<tr class="first">
				<td><code>10 Supply Depot, then Scout</code></td>
				<td>
					1) Build a Supply Depot.<br/>
					2) After the Supply Depot has completed, send an SCV to scout.
				</td>
			</tr>
			<tr>
				<td><code>10 Supply Depot, then Scout (30 seconds)</code></td>
				<td>
					1) Build a Supply Depot.<br/>
					2) After the Supply Depot has completed, send an SCV to scout.<br/>
					(The scout will arrive at the location to be scouted after 30 seconds.)
				</td>
			</tr>
			<tr>
				<td><code>12 Proxy Barracks</code></td>
				<td>Build a Barracks using an SCV previously sent to scout.</td>
			</tr>
			<tr class="last">
				<td>
					<code>
						10 Supply Depot, then Scout (30 seconds)<br/>
						12 Proxy Barracks
					</code>
				</td>
				<td>
					1) Build a Supply Depot.<br/>
					2) After the Supply Depot has completed, send an SCV to scout, arriving after 30 seconds.<br/>
					3) Build a Barracks using the SCV previously sent to scout.
				</td>
			</tr>
		</table>

		<a name="abilities"><h2>Abilities</h2></a>
		<table class="p code">
			<tr class="first">
				<td><code>16 Queen, then constant Spawn Larvae</code></td>
				<td>
					1) Build a Queen.<br/>
					2) After the Queen has completed, constantly use Spawn Larvae ability.
				</td>
			</tr>
			<tr>
				<td><code>15 Orbital Command, then constant Calldown: MULE</code></td>
				<td>
					1) Morph a Command Center into an Orbital Command.<br/>
					2) After the Orbital Command has completed, constantly use Calldown: MULE ability.
				</td>
			</tr>
			<tr>
				<td><code>15 Zealot*</code></td>
				<td>
					1) Build a Zealot.<br/>
					2) Use Chronoboost ability on the Gateway building the Zealot.
				</td>
			</tr>
			<tr class="last">
				<td><code>17 Cybernetics Core, then Warpgate***</code></td>
				<td>
					1) Build a Cybernetics Core.<br/>
					2) Build the Warpgate upgrade at that Cybernetics Core.<br/>
					3) Use Chronoboost ability on the Cybernetics Core thrice.
				</td>
			</tr>
		</table>

		<a name="miscellaneous"><h2>Miscellaneous</h2></a>
		<dl>
			<dt>checkpoint =</dt>
			<dd>integer ":" integer "checkpoint"</dd>
			<dt>trick_job =</dt>
			<dd>["double"] string "trick" ["into" string ["[" integer "]"]]</dd>
			<dt>fake_job =</dt>
			<dd>"fake" string</dd>
			<dt>kill_job =</dt>
			<dd>"kill" string</dd>
			<dt>comment =</dt>
			<dd>"#" string</dd>
		</dl>
		<table class="p code">
			<tr class="first">
				<td><code>5:00 checkpoint</code></td>
				<td>In the results, the resources and energy surplus at 5 minutes will be shown.</td>
			</tr>
			<tr>
				<td><code>14 Fake Hatchery</code></td>
				<td>
					1) Build a Hatchery.<br/>
					2) Cancel the Hatchery
				</td>
			</tr>
			<tr>
				<td><code>22 Kill Zealot [2]</code></td>
				<td>
					Lose two Zealots when you have 22 supply
				</td>
			</tr>
			<tr>
				<td><code>10 Extractor Trick</code></td>
				<td>
					1) Build an Extractor.<br/>
					2) After the Extractor has started building, build a Drone.<br/>
					3) Cancel the Extractor.
				</td>
			</tr>
			<tr>
				<td><code>10 Double Extractor Trick</code></td>
				<td>
					1) Build two Extractors.<br/>
					2) After the Extractors have started building, build two Drones.<br/>
					3) Cancel the Extractors.
				</td>
			</tr>
			<tr>
				<td><code>10 Double Extractor Trick into Drone</code></td>
				<td>
					1) Build two Extractors.<br/>
					2) After the Extractors have started building, build <i>one</i> Drone.<br/>
					3) Cancel the Extractors.
				</td>
			</tr>
			<tr>
				<td><code>10 Double Extractor Trick into Zergling [2]</code></td>
				<td>
					1) Build two Extractors.<br/>
					2) After the Extractors have started building, build two pairs of Zergling.<br/>
					3) Cancel the Extractors.
				</td>
			</tr>
			<tr class="last">
				<td><code># build this barracks outside the opponent's natural</code></td>
				<td>
					Comments are ignored by the calculator.
				</td>
			</tr>
		</table>

		<a name="realism"><h2>Realism</h2></a>
		<dl>
			<dt>transfer_time =</dt>
			<dd>"(" integer ("s" | "sec" | "seconds") ["lost"] ")"</dd>
			<dt>send_worker =</dt>
			<dd>"(send @" integer ("gas" | "minerals") ")"</dd>
		</dl>
		<table class="p code">
			<tr class="first">
				<td><code># Startup build delay = 3 seconds</code></td>
				<td>Nothing is built in the first three seconds.</td>
			</tr>
			<tr>
				<td><code># Startup mining delay = 2 seconds</code></td>
				<td>No minerals are mined in the first two seconds.</td>
			</tr>
			<tr>
				<td><code>12 Gateway (send @120 minerals)</code></td>
				<td>
					1) Send probe when you have 120 minerals.<br/>
					2) Build Gateway when 150 minerals become available.
				</td>
			</tr>
			<tr class="last">
				<td>
					<code>
						22 Nexus > +8 (10s)<span class="or">or</span><br/>
						22 Nexus, then transfer 8 probes (10 seconds lost)
					</code>
				</td>
				<td>
					1) Build a Nexus.<br/>
					2) After Nexus has completed, transfer 8 probes to the new base.<br/>
					(these workers aren't mining for 10 seconds)
				</td>
			</tr>
		</table>

		<br/>
		<br/>
		<?php
	}

	protected function renderHEAD() {
		parent::renderHEAD();
		?>
		<script type="text/javascript"><!--
			$(function() {
				$("table.code tr.first td:first-child").corner("8px top");
				$("table.code tr.last td:first-child").corner("8px bottom");
				$("dl").corner("8px");
				
				$("a[href^=\"#\"]").click(function() {
					var name = $(this).attr("href").substring(1);
					var anchor = $("a[name=\"" + name + "\"]");
					$("h2", anchor).effect("highlight", {}, 1500);
				});
			});
			//--></script>
		<?php
	}
};

$page = new SyntaxPage();
?>