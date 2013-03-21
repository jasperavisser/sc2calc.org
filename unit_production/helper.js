
// functions
function coalesce(val1, val2) {
	return !val1 ? val2 : val1;
}

function getFloat(id) {
	return Math.max(0, coalesce(parseFloat($("#" + id).val(), 10), 0));
}

function getInt(id) {
	return Math.max(0, coalesce(parseInt($("#" + id).val(), 10), 0));
}

function set_int(id, value) {
	$("#" + id).val(value);
}

// classes
function Unit(name, supply, mineral, gas, time, reducedTime, larva) {
	
	// members
	this.name = name;
	this.supply = supply;
	this.mineral = mineral;
	this.gas = gas;
	this.time = time;
	this.reducedTime = reducedTime;
	this.larva = larva;
	
	// methods
	this.code = function() {
		return this.name.replace(/\W/g, '');
	};
	
	this.getTime = function(reduced) {
		return (this.reducedTime !== undefined && reduced) ? this.reducedTime : this.time;
	}
	
	this.tooltip = function() {
		return "<div id=\"" + this.code() + "_tooltip\" class=\"tooltip\">" +
			"<div class=\"title\">" + this.name + "</div>" + 
			"<table>" +
				"<tr><td width=\"100\">Supply</td><td class=\"right\">" + this.supply + "</td></tr>" +
				"<tr><td width=\"100\">Mineral</td><td class=\"right\">" + this.mineral + "</td></tr>" +
				"<tr><td width=\"100\">Gas</td><td class=\"right\">" + this.gas + "</td></tr>" +
				"<tr><td width=\"100\">Time</td><td class=\"right\">" + this.time + (this.reducedTime ? (" / " + this.reducedTime) : "") + " sec</td></tr>" +
			"</table>" +
		"</div>";
	};
}

// classes
function Structure(name, units, multiplier) {
	
	// members
	this.name = name;
	this.units = units;
	this.multiplier = multiplier !== undefined ? multiplier : 1;

	// methods
	this.code = function() {
		return this.name.replace(/\W/g, '');
	};
	
}

// protoss
var Probe			= new Unit("Probe"			,  1,  50,   0,  17);
var Mothership		= new Unit("Mothership"		,  8, 400, 400, 160);
var Zealot 			= new Unit("Zealot"			,  2, 100,   0,  38,  28);
var Stalker			= new Unit("Stalker"		,  2, 125,  50,  42,  32);
var Sentry			= new Unit("Sentry"			,  2,  50, 100,  37,  32);
var HighTemplar		= new Unit("High Templar"	,  2,  50, 150,  55,  45);
var DarkTemplar		= new Unit("Dark Templar"	,  2, 125, 125,  55,  45);
var Observer		= new Unit("Observer"	 	,  1,  25,  75,  40);
var Immortal		= new Unit("Immortal"	 	,  4, 250, 100,  50);
var WarpPrism		= new Unit("Warp Prism"	 	,  2, 200,   0,  50);
var Colossus		= new Unit("Colossus"	 	,  6, 300, 200,  75);
var Phoenix			= new Unit("Phoenix"		,  2, 150, 100,  35);
var VoidRay			= new Unit("Void Ray"	 	,  3, 250, 150,  60);
var Carrier			= new Unit("Carrier"		,  6, 350, 250, 120);

var Nexus				= new Structure("Nexus"				, [Probe, Mothership]);
var Gateway				= new Structure("Gateway"			, [Zealot, Stalker, Sentry, HighTemplar, DarkTemplar]);
var RoboticsFacility	= new Structure("Robotics Facility"	, [Observer, Immortal, WarpPrism, Colossus]);
var Stargate			= new Structure("Stargate"			, [Phoenix, VoidRay, Carrier]);

var ProtossStructures = [
	Nexus,
	Gateway,
	RoboticsFacility,
	Stargate];
var ProtossUnits = [
	Probe, Mothership,
	Zealot, Stalker, Sentry, HighTemplar, DarkTemplar,
	Observer, Immortal, WarpPrism, Colossus,
	Phoenix, VoidRay, Carrier];

// terran
var SCV				= new Unit("SCV"			,  1,  50,   0,  17);
var Marine 			= new Unit("Marine"			,  1,  50,   0,  25);
var Marauder 		= new Unit("Marauder"		,  2, 100,  25,  30);
var Reaper 			= new Unit("Reaper"			,  1,  50,  50,  45);
var Ghost			= new Unit("Ghost"			,  2, 200, 100,  50);
var Hellion 		= new Unit("Hellion"		,  2, 100,   0,  30);
var SiegeTank		= new Unit("Siege Tank"		,  3, 150, 125,  50);
var Thor 			= new Unit("Thor"			,  6, 300, 200,  60);
var Viking 			= new Unit("Viking"			,  2, 150,  75,  42);
var Medivac 		= new Unit("Medivac"		,  2, 100, 100,  42);
var Raven 			= new Unit("Raven"			,  2, 100, 200,  60);
var Banshee 		= new Unit("Banshee"		,  3, 150, 100,  60);
var Battlecruiser 	= new Unit("Battlecruiser"	,  6, 400, 300,  90);

var CommandCenter	= new Structure("Command Center"		, [SCV]);
var Barracks		= new Structure("Barracks"				, [Marine]);
var BarracksReactor	= new Structure("Barracks + Reactor"	, [Marine], 2);
var BarracksTechLab	= new Structure("Barracks + Tech Lab"	, [Marine, Marauder, Reaper, Ghost]);
var Factory			= new Structure("Factory"				, [Hellion]);
var FactoryReactor	= new Structure("Factory + Reactor"		, [Hellion], 2);
var FactoryTechLab	= new Structure("Factory + Tech Lab"	, [Hellion, SiegeTank, Thor]);
var Starport		= new Structure("Starport"				, [Viking, Medivac]);
var StarportReactor	= new Structure("Starport + Reactor"	, [Viking, Medivac], 2);
var StarportTechLab	= new Structure("Starport + Tech Lab"	, [Viking, Medivac, Raven, Banshee, Battlecruiser]);

var TerranStructures = [
	CommandCenter,
	Barracks,
	BarracksReactor,
	BarracksTechLab,
	Factory,
	FactoryReactor,
	FactoryTechLab,
	Starport,
	StarportReactor,
	StarportTechLab];
var TerranUnits = [
	SCV,
	Marine, Marauder, Reaper, Ghost,
	Hellion, SiegeTank, Thor,
	Viking, Medivac, Raven, Banshee, Battlecruiser];

// zerg
var Drone			= new Unit("Drone"			,  1,  50,   0,  17, null,    1);
var Overlord		= new Unit("Overlord"		, -8, 100,   0,  25, null,    1);
var Queen			= new Unit("Queen"			,  2, 150,   0,  50, null,    0);
var Zergling		= new Unit("Zergling"		, .5,  25,   0,  24, null,   .5);
var Baneling		= new Unit("Baneling"		, .5,  50,  25,  44, null,   .5);
var Roach			= new Unit("Roach"			,  2,  75,  25,  27, null,    1);
var Hydralisk		= new Unit("Hydralisk"		,  2, 100,  50,  33, null,    1);
var Overseer		= new Unit("Overseer"		, -8, 150,  50,  42, null,    1);
var Mutalisk		= new Unit("Mutalisk"		,  2, 100, 100,  33, null,    1);
var Corruptor		= new Unit("Corruptor"		,  2, 150, 100,  40, null,    1);
var BroodLord		= new Unit("BroodLord"		,  2, 300, 250,  74, null,    1);
var Infestor		= new Unit("Infestor"		,  2, 100, 150,  50, null,    1);
var Ultralisk		= new Unit("Ultralisk"		,  6, 300, 200,  55, null,    1);

var ZergUnits = [
	Drone, Overlord,
	Queen, Zergling, Baneling, Roach,
	Overseer, Hydralisk, Mutalisk, Corruptor, Infestor,
	BroodLord, Ultralisk];
