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

define("ADDON_SWAP_TIME", 10);
define("CHRONO_BOOST_RATE", 1.5);
define("CHRONO_BOOST_HUMAN_DELAY", 0.1);
define("ENERGY_RATE", 0.5625);
define("LARVA_TIME", 15);
define("MULE_MINING", 2.9);
define("WARPGATE_QUEUE_REDUCTION", 10);

/**
 * Flags for product types.
 */
define("Protoss", 1);
define("Terran", 2);
define("Zerg", 4);
define("Unit", 8);
define("Structure", 16);
define("Upgrade", 32);
define("Morph", 64);
define("Base", 128);
define("Worker", 256);
define("Geyser", 512);
define("Booster", 1024);
define("Ability", 2048);
define("Spellcaster", 4096);

/**
 * Products are structures, units, upgrades, morphs, addons, addon swaps,
 * abilities, etc. Basically, anything that is buildable in the game.
 */
class Product {

	/// private static members

	/**
	 * List of designated products for specific types.
	 * @var array
	 */
	private static $_designated = array();
	
	/// public static members

	/**
	 * List of all exposed products.
	 * @var array
	 */
	public static $all = array();
	
	/// private static members

	/**
	 * Last unique identifier created.
	 * @var int
	 */
	private static $last_uid = 0;

	/// public members

	/**
	 * Energy cost of this product.
	 * @var float
	 */
	public $energyCost;

	/**
	 * Maximum energy on this spellcaster.
	 * @var float
	 */
	public $energyMax;

	/**
	 * Initial energy on this spellcaster.
	 * @var float
	 */
	public $energyStart;

	/**
	 * Production queues expended to build this product.
	 * @var array
	 */
	public $expends;

	/**
	 * If true, all production queues are required; if false, only one of them.
	 * @var bool
	 */
	public $expendsAll;

	/**
	 * Gas cost of this product.
	 * @var float
	 */
	public $gasCost;

	/**
	 * Larva cost of this product.
	 * @var int
	 */
	public $larvaCost;

	/**
	 * Mineral cost of this product.
	 * @var float
	 */
	public $mineralCost;

	/**
	 * Name of this product.
	 * @var string
	 */
	public $name;

	/**
	 * Prerequisite structures or upgrades to build this product.
	 * @var array
	 */
	public $prerequisites;

	/**
	 * Type of spellcaster needed to use this ability.
	 * @var Product 
	 */
	public $spellcaster;

	/**
	 * Supply capacity provided by this product.
	 * @var int
	 */
	public $supplyCapacity;

	/**
	 * Supply cost of this product.
	 * @var int
	 */
	public $supplyCost;

	/**
	 * Time it takes to complete this product.
	 * @var float
	 */
	public $timeCost;

	/**
	 * Type of product.
	 * @var int
	 */
	public $type;

	/**
	 * Unique identifier of this product.
	 * @var int
	 */
	public $uid;

	/**
	 * For a morph, a list of products that are yielded by the morph.
	 * @var array
	 */
	public $yields;
	
	/// constructor

	/**
	 * Create a new product.
	 * @param string $name
	 * @param int $type
	 * @param array $prerequisites
	 * @param array $expends
	 * @param int $supplyCost
	 * @param float $mineralCost
	 * @param float $gasCost
	 * @param float $timeCost
	 * @param bool $exposed
	 */
	public function __construct($name, $type, $prerequisites, $expends, $supplyCost, $mineralCost, $gasCost, $timeCost, $exposed = true) {
		$this->gasCost = $gasCost;
		$this->larvaCost = (($type & Zerg) && ($type & Unit)) ? 1 : 0;
		$this->mineralCost = $mineralCost;
		$this->name = $name;
		$this->expends = $expends;
		$this->expendsAll = false;
		$this->prerequisites = $prerequisites;
		$this->supplyCost = $supplyCost;
		$this->timeCost = $timeCost;
		$this->type = $type;
		
		// set uid & append to all
		$this->uid = Product::$last_uid++;
		if($exposed) {
			Product::$all[] = $this;
		}
	}

	/**
	 * When a product is unset
	 */
	public function drop() {
		foreach(Product::$all as &$candidate) { 
			if($this->uid == $candidate->uid) {
				unset($candidate);
				break;
			}
		}
	}
	
	/// operators

	/**
	 * Convert to a string.
	 * @return string
	 */
	public function __toString() {
		return isset($this->name) ? $this->name : "n/a";
	}
	
	/// public static methods

	/**
	 * Find exposed product by name.
	 * @param string $name
	 * @return Product
	 */
	public static function byName($name) {
		foreach(Product::$all as $candidate) {
			if(strcasecmp($name, $candidate->name) == 0) {
				return $candidate;
			}
		}
	}

	/**
	 * Get designated product of given type.
	 * @param int $type
	 * @return Product
	 */
	public static function designated($type) {
		return Product::$_designated[$type];
	}
	
	/// public methods

	/**
	 * Make a specific product the designated product of its type.
	 * @param int $type
	 */
	public function designate($type) {
		Product::$_designated[$type] = $this;
		$this->type |= $type;
	}

	/**
	 * Promote this product to a spellcaster type.
	 * @param float $energyStart
	 * @param float $energyMax
	 */
	public function makeSpellcaster($energyStart, $energyMax) {
		$this->type |= Spellcaster;
		$this->energyStart = $energyStart;
		$this->energyMax = $energyMax;
	}

	/**
	 * Get race of this product.
	 * @return int
	 */
	public function race() {
		return $this->type & (Protoss | Terran | Zerg);
	}
};

/**
 * Specific constructor for abilities.
 */
class Ability extends Product {

	/// constructor
	public function __construct($name, $race, $prerequisites, $spellcaster, $energyCost, $time, $exposed = true) {
		parent::__construct($name, $race | Ability, $prerequisites, null, 0, null, null, $time, $exposed);
		$this->energyCost = $energyCost;
		$this->spellcaster = $spellcaster;
	}
};

/**
 * Specific constructor for morphs.
 */
class Morph extends Product {
	public function __construct($name, $race, $prerequisites, $expends, $yields, $supplyCost, $mineral, $gas, $time, $exposed = true) {
		parent::__construct($name, $race | Morph, $prerequisites, $expends, $supplyCost, $mineral, $gas, $time, $exposed);
		$this->yields = $yields;
		$this->expendsAll = true;
	}
};

/**
 * Specific constructor for Protoss structures.
 */
class ProtossStructure extends Product {

	/// constructor
	public function __construct($name, $prerequisites, $mineral, $gas, $time, $exposed = true) {
		parent::__construct($name, Protoss | Structure, $prerequisites, null, null, $mineral, $gas, $time, $exposed);
	}
};

/**
 * Specific constructor for Terran structures.
 */
class TerranStructure extends Product {

	/// constructor
	public function __construct($name = null, $prerequisites = array(), $mineral = null, $gas = null, $time = null, $exposed = true) {
		parent::__construct($name, Terran | Structure, $prerequisites, null, null, $mineral, $gas, $time, $exposed);
	}
};

/**
 * Specific constructor for units.
 */
class Unit extends Product {

	/// constructor
	public function __construct($name, $race, $prerequisites, $expends, $supplyCost, $mineral, $gas, $time, $exposed = true) {
		parent::__construct($name, $race | Unit, $prerequisites, $expends, $supplyCost, $mineral, $gas, $time, $exposed);
	}
};

/**
 * Specific constructor for upgrades.
 */
class Upgrade extends Product {

	/// constructor
	public function __construct($name, $race, $prerequisites, $expends, $mineral, $gas, $time, $exposed = true) {
		parent::__construct($name, $race | Upgrade, $prerequisites, $expends, null, $mineral, $gas, $time, $exposed);
	}
};

/**
 * Specific constructor for Zerg structures.
 */
class ZergStructure extends Product {

	/// constructor
	public function __construct($name = null, $prerequisites = array(), $supplyCost = null, $mineral = null, $gas = null, $time = null, $exposed = true) {
		parent::__construct($name, Zerg | Structure, $prerequisites, null, $supplyCost, $mineral, $gas, $time, $exposed);
		$this->supplyCost = -1;
	}
};

/// Protoss structures
$Nexus 				= new ProtossStructure("Nexus"				, array()							,  400,	   0,  100);
$Pylon 				= new ProtossStructure("Pylon"				, array()							,  100,	   0,   25);
$Assimilator		= new ProtossStructure("Assimilator"		, array()							,   75,	   0,   30);
$Gateway			= new ProtossStructure("Gateway"			, array($Nexus, $Pylon)				,  150,	   0,   65);
$Warpgate			= new ProtossStructure("Warpgate"			, array()							, null, null, null, false);
$Forge				= new ProtossStructure("Forge"				, array($Nexus, $Pylon)				,  150,	   0,   45);
$PhotonCannon		= new ProtossStructure("Photon Cannon"		, array($Forge, $Pylon)				,  150,	   0,   40);
$CyberneticsCore	= new ProtossStructure("Cybernetics Core"	, array($Gateway, $Pylon)			,  150,	   0,   50);
$TwilightCouncil	= new ProtossStructure("Twilight Council"	, array($CyberneticsCore, $Pylon)	,  150,	 100,   50);
$RoboticsFacility	= new ProtossStructure("Robotics Facility"	, array($CyberneticsCore, $Pylon)	,  200,	 100,   65);
$Stargate			= new ProtossStructure("Stargate"			, array($CyberneticsCore, $Pylon)	,  150,	 150,   60);
$TemplarArchives	= new ProtossStructure("Templar Archives"	, array($TwilightCouncil, $Pylon)	,  150,	 200,   50);
$DarkShrine			= new ProtossStructure("Dark Shrine"		, array($TwilightCouncil, $Pylon)	,  100,	 250,  100);
$RoboticsBay		= new ProtossStructure("Robotics Bay"		, array($RoboticsFacility, $Pylon)	,  200,	 200,   65);
$FleetBeacon		= new ProtossStructure("Fleet Beacon"		, array($Stargate, $Pylon)			,  300,	 200,   60);

/// Protoss upgrades
$WarpgateUpgrade		= new Upgrade("Warpgate"				, Protoss, array()					, array($CyberneticsCore)				,  50,  50, 140);
$GroundWeaponsLevel1	= new Upgrade("Ground Weapons Level 1"	, Protoss, array()					, array($Forge)							, 100, 100, 160);
$GroundWeaponsLevel2	= new Upgrade("Ground Weapons Level 2"	, Protoss, array($TwilightCouncil)	, array($Forge)							, 175, 175, 190);
$GroundWeaponsLevel3	= new Upgrade("Ground Weapons Level 3"	, Protoss, array($TwilightCouncil)	, array($Forge)							, 250, 250, 220);
$AirWeaponsLevel1		= new Upgrade("Air Weapons Level 1"		, Protoss, array()					, array($CyberneticsCore)				, 100, 100, 160);
$AirWeaponsLevel2		= new Upgrade("Air Weapons Level 2"		, Protoss, array($FleetBeacon)		, array($CyberneticsCore)				, 175, 175, 190);
$AirWeaponsLevel3		= new Upgrade("Air Weapons Level 3"		, Protoss, array($FleetBeacon)		, array($CyberneticsCore)				, 250, 250, 220);
$GroundArmorLevel1		= new Upgrade("Ground Armor Level 1"	, Protoss, array()					, array($Forge)							, 100, 100, 160);
$GroundArmorLevel2		= new Upgrade("Ground Armor Level 2"	, Protoss, array($TwilightCouncil)	, array($Forge)							, 175, 175, 190);
$GroundArmorLevel3		= new Upgrade("Ground Armor Level 3"	, Protoss, array($TwilightCouncil)	, array($Forge)							, 250, 250, 220);
$AirArmorLevel1			= new Upgrade("Air Armor Level 1"		, Protoss, array()					, array($CyberneticsCore)				, 150, 150, 160);
$AirArmorLevel2			= new Upgrade("Air Armor Level 2"		, Protoss, array($FleetBeacon)		, array($CyberneticsCore)				, 225, 225, 190);
$AirArmorLevel3			= new Upgrade("Air Armor Level 3"		, Protoss, array($FleetBeacon)		, array($CyberneticsCore)				, 300, 300, 220);
$ShieldsLevel1			= new Upgrade("Shields Level 1"			, Protoss, array()					, array($Forge)							, 200, 200, 160);
$ShieldsLevel2			= new Upgrade("Shields Level 2"			, Protoss, array($TwilightCouncil)	, array($Forge)							, 300, 300, 190);
$ShieldsLevel3			= new Upgrade("Shields Level 3"			, Protoss, array($TwilightCouncil)	, array($Forge)							, 400, 400, 220);
$Charge					= new Upgrade("Charge"					, Protoss, array()					, array($TwilightCouncil)				, 200, 200, 140);
$GraviticBoosters		= new Upgrade("Gravitic Boosters"		, Protoss, array()					, array($RoboticsBay)					, 100, 100,  80);
$GraviticDrive			= new Upgrade("Gravitic Drive"			, Protoss, array()					, array($RoboticsBay)					, 100, 100,  80);
$FluxVanes				= new Upgrade("Flux Vanes"				, Protoss, array()					, array($FleetBeacon)					, 150, 150,  80);
$ExtendedThermalLance	= new Upgrade("Extended Thermal Lance"	, Protoss, array()					, array($RoboticsBay)					, 200, 200, 140);
$PsionicStorm			= new Upgrade("Psionic Storm"			, Protoss, array()					, array($TemplarArchives)				, 200, 200, 110);
$Hallucination			= new Upgrade("Hallucination"			, Protoss, array()					, array($CyberneticsCore)				, 100, 100, 110);
$Blink					= new Upgrade("Blink"					, Protoss, array()					, array($TwilightCouncil)				, 150, 150, 110);
$KhaydarinAmulet		= new Upgrade("Khaydarin Amulet"		, Protoss, array()					, array($TemplarArchives)				, 150, 150, 110);
$GravitonCatapult		= new Upgrade("Graviton Catapult"		, Protoss, array()					, array($FleetBeacon)					, 150, 150,  80);

/// Protoss morphs
$TransformToWarpgate		= new Morph("Transform to Warpgate"	, Protoss, array($WarpgateUpgrade)		, array($Gateway)	, array($Warpgate)						, null,    0,    0,   10);
$TransformToGateway			= new Morph("Transform to Gateway"	, Protoss, array($WarpgateUpgrade)		, array($Warpgate)	, array($Gateway)						, null,    0,    0,   10);

/// Protoss units
$Probe			= new Unit("Probe"			, Protoss, array()					, array($Nexus)					, 1,  50,   0,  17);
$Mothership		= new Unit("Mothership"		, Protoss, array($FleetBeacon)		, array($Nexus)					, 8, 400, 400, 160);
$Zealot 		= new Unit("Zealot"			, Protoss, array()					, array($Gateway, $Warpgate)	, 2, 100,   0,  38);
$Stalker		= new Unit("Stalker"		, Protoss, array($CyberneticsCore)	, array($Gateway, $Warpgate)	, 2, 125,  50,  42);
$Sentry			= new Unit("Sentry"			, Protoss, array($CyberneticsCore)	, array($Gateway, $Warpgate)	, 2,  50, 100,  42);
$HighTemplar	= new Unit("High Templar"	, Protoss, array($TemplarArchives)	, array($Gateway, $Warpgate)	, 2,  50, 150,  55);
$DarkTemplar	= new Unit("Dark Templar"	, Protoss, array($DarkShrine)		, array($Gateway, $Warpgate)	, 2, 125, 125,  55);
$Observer		= new Unit("Observer"		, Protoss, array()					, array($RoboticsFacility)		, 1,  50, 100,  40);
$Immortal		= new Unit("Immortal"		, Protoss, array()					, array($RoboticsFacility)		, 4, 250, 100,  50);
$WarpPrism		= new Unit("Warp Prism"		, Protoss, array()					, array($RoboticsFacility)		, 2, 200,   0,  50);
$Colossus		= new Unit("Colossus"		, Protoss, array($RoboticsBay)		, array($RoboticsFacility)		, 6, 300, 200,  75);
$Phoenix		= new Unit("Phoenix"		, Protoss, array()					, array($Stargate)				, 2, 150, 100,  45);
$VoidRay		= new Unit("Void Ray"		, Protoss, array()					, array($Stargate)				, 3, 250, 150,  60);
$Carrier		= new Unit("Carrier"		, Protoss, array($FleetBeacon)		, array($Stargate)				, 6, 350, 250, 120);
$Interceptor	= new Unit("Interceptor"	, Protoss, array()					, array($Carrier)				, 0,  25,   0,   8);

/// Terran structures
$CommandCenter		= new TerranStructure("Command Center"		, array()				,  400,	   0,  100);
$OrbitalCommand		= new TerranStructure("Orbital Command"		, array()				, null,	null, null, false);
$PlanetaryFortress	= new TerranStructure("Planetary Fortress"	, array()				, null,	null, null, false);
$SupplyDepot		= new TerranStructure("Supply Depot"		, array($CommandCenter)	,  100,	   0,   30);
$Refinery			= new TerranStructure("Refinery"			, array($CommandCenter)	,   75,	   0,   30);
$Barracks			= new TerranStructure("Barracks"			, array($CommandCenter)	,  150,	   0,   60);
$EngineeringBay	 	= new TerranStructure("Engineering Bay"		, array($CommandCenter)	,  125,	   0,   35);
$Bunker				= new TerranStructure("Bunker"				, array($Barracks)		,  100,	   0,   35);
$MissileTurret		= new TerranStructure("Missile Turret"		, array($EngineeringBay),  100,	   0,   25);
$SensorTower		= new TerranStructure("Sensor Tower"		, array($EngineeringBay),  125,	 100,   25);
$Factory			= new TerranStructure("Factory"				, array($Barracks)		,  150,	 100,   60);
$GhostAcademy		= new TerranStructure("Ghost Academy"		, array($Barracks)		,  150,	  50,   40);
$Armory				= new TerranStructure("Armory"				, array($Factory)		,  150,	 100,   65);
$Starport			= new TerranStructure("Starport"			, array($Factory)		,  150,	 100,   50);
$FusionCore			= new TerranStructure("Fusion Core"			, array($Starport)		,  150,	 150,   50);

/// Terran addons
$BarracksOnReactor	= new TerranStructure("Barracks with attached Reactor"	, null, null, null, null, false);
$BarracksOnTechLab	= new TerranStructure("Barracks with attached Tech Lab"	, null, null, null, null, false);
$FactoryOnReactor	= new TerranStructure("Factory with attached Reactor"	, null, null, null, null, false);
$FactoryOnTechLab	= new TerranStructure("Factory with attached Tech Lab"	, null, null, null, null, false);
$StarportOnReactor	= new TerranStructure("Starport with attached Reactor"	, null, null, null, null, false);
$StarportOnTechLab	= new TerranStructure("Starport with attached Tech Lab"	, null, null, null, null, false);
$Reactor			= new TerranStructure("Reactor"							, null, null, null, null, false);
$ReactorOnBarracks	= new TerranStructure("Reactor attached to Barracks"	, null, null, null, null, false);
$ReactorOnFactory	= new TerranStructure("Reactor attached to Factory"		, null, null, null, null, false);
$ReactorOnStarport	= new TerranStructure("Reactor attached to Starport"	, null, null, null, null, false);
$TechLab			= new TerranStructure("Tech Lab"						, null, null, null, null, false);
$TechLabOnBarracks	= new TerranStructure("Tech Lab attached to Barracks"	, null, null, null, null, false);
$TechLabOnFactory	= new TerranStructure("Tech Lab attached to Factory"	, null, null, null, null, false);
$TechLabOnStarport	= new TerranStructure("Tech Lab attached to Starport"	, null, null, null, null, false);

/// Terran morphs
$SalvageBunker				= new Morph("Salvage Bunker"		, Terran, null					, array($Bunker)		, array()										, null, -100,    0,    3);
$MorphToOrbitalCommand		= new Morph("Orbital Command"		, Terran, array($Barracks)		, array($CommandCenter)	, array($OrbitalCommand)						, null,  150,    0,   35);
$MorphToPlanetaryFortress	= new Morph("Planetary Fortress"	, Terran, array($EngineeringBay), array($CommandCenter)	, array($PlanetaryFortress)						, null,  150,  150,   50);
$BuildReactorOnBarracks		= new Morph("Reactor on Barracks"	, Terran, null					, array($Barracks), array($BarracksOnReactor, $ReactorOnBarracks)	, null,   50,   50,	  50);
$BuildTechLabOnBarracks		= new Morph("Tech Lab on Barracks"	, Terran, null					, array($Barracks), array($BarracksOnTechLab, $TechLabOnBarracks)	, null,   50,   25,	  25);
$BuildReactorOnFactory		= new Morph("Reactor on Factory"	, Terran, null					, array($Factory)	, array($FactoryOnReactor, $ReactorOnFactory)	, null,   50,   50,	  50);
$BuildTechLabOnFactory		= new Morph("Tech Lab on Factory"	, Terran, null					, array($Factory)	, array($FactoryOnTechLab, $TechLabOnFactory)	, null,   50,   25,	  25);
$BuildReactorOnStarport		= new Morph("Reactor on Starport"	, Terran, null					, array($Starport), array($StarportOnReactor, $ReactorOnStarport)	, null,   50,   50,	  50);
$BuildTechLabOnStarport		= new Morph("Tech Lab on Starport"	, Terran, null					, array($Starport), array($StarportOnTechLab, $TechLabOnStarport)	, null,   50,   25,	  25);
$SwapReactorOnBarracksToFactory		= new Morph("Swap Reactor on Barracks to Factory"	, Terran,	null,					array($BarracksOnReactor, $ReactorOnBarracks, $Factory)	, array($Barracks, null, $FactoryOnReactor, $ReactorOnFactory)	,		null, null, null, ADDON_SWAP_TIME);
$SwapReactorOnBarracksToStarport	= new Morph("Swap Reactor on Barracks to Starport"	, Terran,	null,					array($BarracksOnReactor, $ReactorOnBarracks, $Starport), array($Barracks, null, $StarportOnReactor, $ReactorOnStarport)	,		null, null, null, ADDON_SWAP_TIME);
$SwapReactorOnFactoryToBarracks		= new Morph("Swap Reactor on Factory to Barracks"	, Terran,	null,					array($FactoryOnReactor, $ReactorOnFactory, $Barracks)	, array($Factory, null, $BarracksOnReactor, $ReactorOnBarracks)	, 		null, null, null, ADDON_SWAP_TIME);
$SwapReactorOnFactoryToStarport		= new Morph("Swap Reactor on Factory to Starport"	, Terran,	null,					array($FactoryOnReactor, $ReactorOnFactory, $Starport)	, array($Factory, null, $StarportOnReactor, $ReactorOnStarport,)	, 		null, null, null, ADDON_SWAP_TIME);
$SwapReactorOnStarportToBarracks	= new Morph("Swap Reactor on Starport to Barracks"	, Terran,	null,					array($StarportOnReactor, $ReactorOnStarport, $Barracks), array($Starport, null, $BarracksOnReactor, $ReactorOnBarracks)	, 		null, null, null, ADDON_SWAP_TIME);
$SwapReactorOnStarportToFactory		= new Morph("Swap Reactor on Starport to Factory"	, Terran,	null,					array($StarportOnReactor, $ReactorOnStarport, $Factory)	, array($Starport, null, $FactoryOnReactor, $ReactorOnFactory)	, 		null, null, null, ADDON_SWAP_TIME);
$SwapTechLabOnBarracksToFactory		= new Morph("Swap Tech Lab on Barracks to Factory"	, Terran,	null,					array($BarracksOnTechLab, $TechLabOnBarracks, $Factory)	, array($Barracks, null, $FactoryOnTechLab, $TechLabOnFactory)	,		null, null, null, ADDON_SWAP_TIME);
$SwapTechLabOnBarracksToStarport	= new Morph("Swap Tech Lab on Barracks to Starport"	, Terran,	null,					array($BarracksOnTechLab, $TechLabOnBarracks, $Starport), array($Barracks, null, $StarportOnTechLab, $TechLabOnStarport)	,		null, null, null, ADDON_SWAP_TIME);
$SwapTechLabOnFactoryToBarracks		= new Morph("Swap Tech Lab on Factory to Barracks"	, Terran,	null,					array($FactoryOnTechLab, $TechLabOnFactory, $Barracks)	, array($Factory, null, $BarracksOnTechLab, $TechLabOnBarracks)	, 		null, null, null, ADDON_SWAP_TIME);
$SwapTechLabOnFactoryToStarport		= new Morph("Swap Tech Lab on Factory to Starport"	, Terran,	null,					array($FactoryOnTechLab, $TechLabOnFactory, $Starport)	, array($Factory, null, $StarportOnTechLab, $TechLabOnStarport)	, 		null, null, null, ADDON_SWAP_TIME);
$SwapTechLabOnStarportToBarracks	= new Morph("Swap Tech Lab on Starport to Barracks"	, Terran,	null,					array($StarportOnTechLab, $TechLabOnStarport, $Barracks), array($Starport, null, $BarracksOnTechLab, $TechLabOnBarracks)	, 		null, null, null, ADDON_SWAP_TIME);
$SwapTechLabOnStarportToFactory		= new Morph("Swap Tech Lab on Starport to Factory"	, Terran,	null,					array($StarportOnTechLab, $TechLabOnStarport, $Factory)	, array($Starport, null, $FactoryOnTechLab, $TechLabOnFactory)	, 		null, null, null, ADDON_SWAP_TIME);
$SwapReactorOnBarracksWithTechLabFactory	= new Morph("Swap Reactor on Barracks with Tech Lab on Factory"	, Terran,	null,					array($BarracksOnReactor, $ReactorOnBarracks, $FactoryOnTechLab, $TechLabOnFactory)		, array($BarracksOnTechLab, $TechLabOnBarracks, $FactoryOnReactor, $ReactorOnFactory)	,		null, null, null, ADDON_SWAP_TIME);
$SwapReactorOnBarracksWithTechLabStarport	= new Morph("Swap Reactor on Barracks with Tech Lab on Starport", Terran,	null,					array($BarracksOnReactor, $ReactorOnBarracks, $StarportOnTechLab, $TechLabOnStarport)	, array($BarracksOnTechLab, $TechLabOnBarracks, $StarportOnReactor, $ReactorOnStarport)	,		null, null, null, ADDON_SWAP_TIME);
$SwapReactorOnFactoryWithTechLabBarracks	= new Morph("Swap Reactor on Factory with Tech Lab on Barracks"	, Terran,	null,					array($BarracksOnTechLab, $TechLabOnBarracks, $FactoryOnReactor, $ReactorOnFactory)		, array($BarracksOnReactor, $ReactorOnBarracks, $FactoryOnTechLab, $TechLabOnFactory)	, 		null, null, null, ADDON_SWAP_TIME);
$SwapReactorOnFactoryWithTechLabStarport	= new Morph("Swap Reactor on Factory with Tech Lab on Starport"	, Terran,	null,					array($StarportOnTechLab, $TechLabOnStarport, $FactoryOnReactor, $ReactorOnFactory)		, array($StarportOnReactor, $ReactorOnStarport, $FactoryOnTechLab, $TechLabOnFactory)	, 		null, null, null, ADDON_SWAP_TIME);
$SwapReactorOnStarportWithTechLabBarracks	= new Morph("Swap Reactor on Starport with Tech Lab on Barracks", Terran,	null,					array($BarracksOnTechLab, $TechLabOnBarracks, $StarportOnReactor, $ReactorOnStarport)	, array($BarracksOnReactor, $ReactorOnBarracks, $StarportOnTechLab, $TechLabOnStarport)	, 		null, null, null, ADDON_SWAP_TIME);
$SwapReactorOnStarportWithTechLabFactory	= new Morph("Swap Reactor on Starport with Tech Lab on Factory"	, Terran,	null,					array($FactoryOnTechLab, $TechLabOnFactory, $StarportOnReactor, $ReactorOnStarport)		, array($FactoryOnReactor, $ReactorOnFactory, $StarportOnTechLab, $TechLabOnStarport)	, 		null, null, null, ADDON_SWAP_TIME);
$SwapTechLabOnBarracksWithReactorFactory	= new Morph("Swap Tech Lab on Barracks with Reactor on Factory"	, Terran,	null,					array($BarracksOnTechLab, $TechLabOnBarracks, $FactoryOnReactor, $ReactorOnFactory)		, array($BarracksOnReactor, $ReactorOnBarracks, $FactoryOnTechLab, $TechLabOnFactory)	,		null, null, null, ADDON_SWAP_TIME);
$SwapTechLabOnBarracksWithReactorStarport	= new Morph("Swap Tech Lab on Barracks with Reactor on Starport", Terran,	null,					array($BarracksOnTechLab, $TechLabOnBarracks, $StarportOnReactor, $ReactorOnStarport)	, array($BarracksOnReactor, $ReactorOnBarracks, $StarportOnTechLab, $TechLabOnStarport)	,		null, null, null, ADDON_SWAP_TIME);
$SwapTechLabOnFactoryWithReactorBarracks	= new Morph("Swap Tech Lab on Factory with Reactor on Barracks"	, Terran,	null,					array($BarracksOnReactor, $ReactorOnBarracks, $FactoryOnTechLab, $TechLabOnFactory)		, array($BarracksOnTechLab, $TechLabOnBarracks, $FactoryOnReactor, $ReactorOnFactory)	, 		null, null, null, ADDON_SWAP_TIME);
$SwapTechLabOnFactoryWithReactorStarport	= new Morph("Swap Tech Lab on Factory with Reactor on Starport"	, Terran,	null,					array($StarportOnReactor, $ReactorOnStarport, $FactoryOnTechLab, $TechLabOnFactory)		, array($StarportOnTechLab, $TechLabOnStarport, $FactoryOnReactor, $ReactorOnFactory)	, 		null, null, null, ADDON_SWAP_TIME);
$SwapTechLabOnStarportWithReactorBarracks	= new Morph("Swap Tech Lab on Starport with Reactor on Barracks", Terran,	null,					array($BarracksOnReactor, $ReactorOnBarracks, $StarportOnTechLab, $TechLabOnStarport)	, array($BarracksOnTechLab, $TechLabOnBarracks, $StarportOnReactor, $ReactorOnStarport)	, 		null, null, null, ADDON_SWAP_TIME);
$SwapTechLabOnStarportWithReactorFactory	= new Morph("Swap Tech Lab on Starport with Reactor on Factory"	, Terran,	null,					array($FactoryOnReactor, $ReactorOnFactory, $StarportOnTechLab, $TechLabOnStarport)		, array($FactoryOnTechLab, $TechLabOnFactory, $StarportOnReactor, $ReactorOnStarport)	, 		null, null, null, ADDON_SWAP_TIME);

/// Terran units
$SCV			= new Unit("SCV"			, Terran, array()				, array($CommandCenter, $OrbitalCommand, $PlanetaryFortress)					, 1,  50,   0,  17);
$Marine			= new Unit("Marine"			, Terran, array()				, array($Barracks, $BarracksOnReactor, $ReactorOnBarracks, $BarracksOnTechLab)	, 1,  50,   0,  25);
$Marauder		= new Unit("Marauder"		, Terran, array()				, array($BarracksOnTechLab)														, 2, 100,  25,  30);
$Reaper			= new Unit("Reaper"			, Terran, array()				, array($BarracksOnTechLab)														, 1,  50,  50,  45);
$Ghost			= new Unit("Ghost"			, Terran, array($GhostAcademy)	, array($BarracksOnTechLab)														, 2, 150, 150,  40);
$Hellion		= new Unit("Hellion"		, Terran, array()				, array($Factory, $FactoryOnReactor, $ReactorOnFactory, $FactoryOnTechLab)		, 2, 100,   0,  30);
$SiegeTank		= new Unit("Siege Tank"		, Terran, array()				, array($FactoryOnTechLab)														, 3, 150, 125,  45);
$Thor			= new Unit("Thor"			, Terran, array($Armory)		, array($FactoryOnTechLab)														, 6, 300, 200,  60);
$Viking			= new Unit("Viking"			, Terran, array()				, array($Starport, $StarportOnReactor, $ReactorOnStarport, $StarportOnTechLab)	, 2, 150,  75,  42);
$Medivac		= new Unit("Medivac"		, Terran, array()				, array($Starport, $StarportOnReactor, $ReactorOnStarport, $StarportOnTechLab)	, 2, 100, 100,  42);
$Banshee		= new Unit("Banshee"		, Terran, array()				, array($StarportOnTechLab)														, 3, 150, 100,  60);
$Raven			= new Unit("Raven"			, Terran, array()				, array($StarportOnTechLab)														, 2, 100, 200,  60);
$Battlecruiser	= new Unit("Battlecruiser"	, Terran, array($FusionCore)	, array($StarportOnTechLab)														, 6, 400, 300,  90);
$TacticalNuke	= new Unit("Tactical Nuke"	, Terran, array($Factory)		, array($GhostAcademy)															, 0, 100, 100,  60);

/// Terran upgrades
$InfantryWeaponsLevel1	= new Upgrade("Infantry Weapons Level 1"	, Terran, array()		, array($EngineeringBay)	, 100, 100, 160);
$InfantryWeaponsLevel2	= new Upgrade("Infantry Weapons Level 2"	, Terran, array($Armory), array($EngineeringBay)	, 175, 175, 190);
$InfantryWeaponsLevel3	= new Upgrade("Infantry Weapons Level 3"	, Terran, array($Armory), array($EngineeringBay)	, 250, 250, 220);
$VehicleWeaponsLevel1	= new Upgrade("Vehicle Weapons Level 1"		, Terran, array()		, array($Armory)			, 100, 100, 160);
$VehicleWeaponsLevel2	= new Upgrade("Vehicle Weapons Level 2"		, Terran, array()		, array($Armory)			, 175, 175, 190);
$VehicleWeaponsLevel3	= new Upgrade("Vehicle Weapons Level 3"		, Terran, array()		, array($Armory)			, 250, 250, 220);
$ShipWeaponsLevel1		= new Upgrade("Ship Weapons Level 1"		, Terran, array()		, array($Armory)			, 100, 100, 160);
$ShipWeaponsLevel2		= new Upgrade("Ship Weapons Level 2"		, Terran, array()		, array($Armory)			, 175, 175, 190);
$ShipWeaponsLevel3		= new Upgrade("Ship Weapons Level 3"		, Terran, array()		, array($Armory)			, 250, 250, 220);
$InfantryArmorLevel1	= new Upgrade("Infantry Armor Level 1"		, Terran, array()		, array($EngineeringBay)	, 100, 100, 160);
$InfantryArmorLevel2	= new Upgrade("Infantry Armor Level 2"		, Terran, array($Armory), array($EngineeringBay)	, 175, 175, 190);
$InfantryArmorLevel3	= new Upgrade("Infantry Armor Level 3"		, Terran, array($Armory), array($EngineeringBay)	, 250, 250, 220);
$VehiclePlatingLevel1	= new Upgrade("Vehicle Plating Level 1"		, Terran, array()		, array($Armory)			, 100, 100, 160);
$VehiclePlatingLevel2	= new Upgrade("Vehicle Plating Level 2"		, Terran, array()		, array($Armory)			, 175, 175, 190);
$VehiclePlatingLevel3	= new Upgrade("Vehicle Plating Level 3"		, Terran, array()		, array($Armory)			, 250, 250, 220);
$ShipPlatingLevel1		= new Upgrade("Ship Plating Level 1"		, Terran, array()		, array($Armory)			, 100, 100, 160);
$ShipPlatingLevel2		= new Upgrade("Ship Plating Level 2"		, Terran, array()		, array($Armory)			, 175, 175, 190);
$ShipPlatingLevel3		= new Upgrade("Ship Plating Level 3"		, Terran, array()		, array($Armory)			, 250, 250, 220);
$NitroPacks				= new Upgrade("Nitro Packs"					, Terran, array()		, array($TechLabOnBarracks)	,  50,  50, 100);
$HiSecAutoTracking		= new Upgrade("Hi-Sec Auto Tracking"		, Terran, array()		, array($EngineeringBay)	, 100, 100,  80);
$PersonalCloaking		= new Upgrade("Personal Cloaking"			, Terran, array()		, array($GhostAcademy)		, 150, 150, 120);
$CloakingField			= new Upgrade("Cloaking Field"				, Terran, array()		, array($TechLabOnStarport)	, 200, 200, 110);
$StrikeCannons			= new Upgrade("250mm Strike Cannons"		, Terran, array()		, array($TechLabOnFactory)	, 150, 150, 110);
$SeekerMissile			= new Upgrade("Seeker Missile"				, Terran, array()		, array($TechLabOnStarport)	, 150, 150, 110);
$WeaponRefit			= new Upgrade("Weapon Refit"				, Terran, array()		, array($FusionCore)		, 150, 150,  60);
$SiegeTech				= new Upgrade("Siege Tech"					, Terran, array()		, array($TechLabOnFactory)	, 100, 100,  80);
$Stimpack				= new Upgrade("Stimpack"					, Terran, array()		, array($TechLabOnBarracks)	, 100, 100, 140);
$ConcussiveShells		= new Upgrade("Concussive Shells"			, Terran, array()		, array($TechLabOnBarracks)	,  50,  50,  60);
$MoebiusReactor			= new Upgrade("Moebius Reactor"				, Terran, array()		, array($GhostAcademy)		, 100, 100,  80);
$CaduceusReactor		= new Upgrade("Caduceus Reactor"			, Terran, array()		, array($TechLabOnStarport)	, 100, 100,  80);
$CorvidReactor			= new Upgrade("Corvid Reactor"				, Terran, array()		, array($TechLabOnStarport)	, 150, 150, 110);
$BehemothReactor		= new Upgrade("Behemoth Reactor"			, Terran, array()		, array($FusionCore)		, 150, 150,  80);
$NeosteelFrame			= new Upgrade("Neosteel Frame"				, Terran, array()		, array($EngineeringBay)	, 100, 100, 110);
$BuildingArmor			= new Upgrade("Building Armor"				, Terran, array()		, array($EngineeringBay)	, 150, 150, 140);
$DurableMaterials		= new Upgrade("Durable Materials"			, Terran, array()		, array($TechLabOnStarport)	, 150, 150, 110);
$CombatShield			= new Upgrade("Combat Shield"				, Terran, array()		, array($TechLabOnBarracks)	, 100, 100, 110);
$InfernalPreIgniter		= new Upgrade("Infernal Pre-Igniter"		, Terran, array()		, array($TechLabOnFactory)	, 150, 150, 110);

/// Zerg structures
$Hatchery			= new ZergStructure("Hatchery"			, array()					,   -1,  300,	   0,  100);
$Extractor			= new ZergStructure("Extractor"			, array()					,   -1,   25,	   0,   30);
$SpawningPool		= new ZergStructure("Spawning Pool"		, array($Hatchery)			,   -1,  200,	   0,   65);
$EvolutionChamber	= new ZergStructure("Evolution Chamber"	, array($Hatchery)			,   -1,   75,	   0,   35);
$SpineCrawler		= new ZergStructure("Spine Crawler"		, array($SpawningPool)		,   -1,  100,	   0,   50);
$SporeCrawler		= new ZergStructure("Spore Crawler"		, array($EvolutionChamber)	,   -1,   75,	   0,   30);
$RoachWarren		= new ZergStructure("Roach Warren"		, array($SpawningPool)		,   -1,  150,	   0,   55);
$BanelingNest		= new ZergStructure("Baneling Nest"		, array($SpawningPool)		,   -1,  100,	  50,   60);
$Lair				= new ZergStructure("Lair"				, array()					, null, null, null, null, false);
$HydraliskDen		= new ZergStructure("Hydralisk Den"		, array($Lair)				,   -1,  100,	 100,   40);
$InfestationPit		= new ZergStructure("Infestation Pit"	, array($Lair)				,   -1,  100,	 100,   50);
$Spire				= new ZergStructure("Spire"				, array($Lair)				,   -1,  200,	 200,  100);
$NydusNetwork		= new ZergStructure("Nydus Network"		, array($Lair)				,   -1,  150,	 200,   50);
$NydusWorm			= new ZergStructure("Nydus Worm"		, array($NydusNetwork)		, null,  100,	 100,   20);
$Hive				= new ZergStructure("Hive"				, array()					, null, null, null, null, false);
$UltraliskCavern	= new ZergStructure("Ultralisk Cavern"	, array($Hive)				,   -1,  150,	 200,   65);
$GreaterSpire		= new ZergStructure("Greater Spire"		, array()					, null, null, null, null, false);

/// Zerg structure morphs
$MorphToLair			= new Morph("Lair"			, Zerg, array($SpawningPool)	, array($Hatchery)	, array($Lair)			, null,  150,  100,   80);
$MorphToHive			= new Morph("Hive"			, Zerg, array($InfestationPit)	, array($Lair)		, array($Hive)			, null,  200,  150,  100);
$MorphToGreaterSpire	= new Morph("Greater Spire"	, Zerg, array($Hive)			, array($Spire)		, array($GreaterSpire)	, null,  100,  150,  100);

/// Zerg units
$Drone		= new Unit("Drone"		, Zerg, array()					, null							,    1,   50,    0,   17);
$Overlord	= new Unit("Overlord"	, Zerg, array()					, null							,    0,  100,    0,   25);
$Queen		= new Unit("Queen"		, Zerg, array($SpawningPool)	, array($Hatchery, $Lair, $Hive),    2,  150,    0,   50);
$Queen->larvaCost = 0;
$Zergling	= new Unit("Zergling"	, Zerg, array($SpawningPool)	, null							,    1,   50,    0,   24);
$Baneling	= new Unit("Baneling"	, Zerg, array()					, null							, null, null, null, null, false);
$Roach		= new Unit("Roach"		, Zerg, array($RoachWarren)		, null							,    2,   75,   25,   27);
$Overseer	= new Unit("Overseer"	, Zerg, array()					, null							, null, null, null, null, false);
$Hydralisk	= new Unit("Hydralisk"	, Zerg, array($HydraliskDen)	, null							,    2,  100,   50,   33);
$Mutalisk	= new Unit("Mutalisk"	, Zerg, array($Spire)			, null							,    2,  100,  100,   33);
$Corruptor	= new Unit("Corruptor"	, Zerg, array($Spire)			, null							,    2,  150,  100,   40);
$BroodLord	= new Unit("Brood Lord"	, Zerg, array()					, null							, null, null, null, null, false);
$Infestor	= new Unit("Infestor"	, Zerg, array($InfestationPit)	, null							,    2,  100,  150,   50);
$Ultralisk	= new Unit("Ultralisk"	, Zerg, array($UltraliskCavern)	, null							,    6,  300,  200,   70);

/// Zerg unit morphs
$MorphToBaneling	= new Morph("Baneling"			, Zerg, array($BanelingNest), null		, array()			, null,   25,   25,   20);
$MorphToOverseer	= new Morph("Overseer"			, Zerg, array($Lair)		, null		, array()			, null,   50,  100,   17);
$MorphToBroodLord	= new Morph("Brood Lord"		, Zerg, array($GreaterSpire), null		, array()			, null,  150,  150,   34);

/// Zerg upgrades
$MeleeAttacksLevel1			= new Upgrade("Melee Attacks Level 1"	, Zerg, array()			, array($EvolutionChamber)		, 100, 100, 160);
$MeleeAttacksLevel2			= new Upgrade("Melee Attacks Level 2"	, Zerg, array($Lair)	, array($EvolutionChamber)		, 150, 150, 190);
$MeleeAttacksLevel3			= new Upgrade("Melee Attacks Level 3"	, Zerg, array($Hive)	, array($EvolutionChamber)		, 200, 200, 220);
$MissileAttacksLevel1		= new Upgrade("Missile Attacks Level 1"	, Zerg, array()			, array($EvolutionChamber)		, 100, 100, 160); 
$MissileAttacksLevel2		= new Upgrade("Missile Attacks Level 2"	, Zerg, array($Lair)	, array($EvolutionChamber)		, 150, 150, 190);
$MissileAttacksLevel3		= new Upgrade("Missile Attacks Level 3"	, Zerg, array($Hive)	, array($EvolutionChamber)		, 200, 200, 220);
$FlyerAttacksLevel1			= new Upgrade("Flyer Attacks Level 1"	, Zerg, array()			, array($Spire, $GreaterSpire)	, 100, 100, 160);
$FlyerAttacksLevel2			= new Upgrade("Flyer Attacks Level 2"	, Zerg, array($Lair)	, array($Spire, $GreaterSpire)	, 175, 175, 190);
$FlyerAttacksLevel3			= new Upgrade("Flyer Attacks Level 3"	, Zerg, array($Hive)	, array($Spire, $GreaterSpire)	, 250, 250, 220);
$GroundCarapaceLevel1		= new Upgrade("Ground Carapace Level 1"	, Zerg, array()			, array($EvolutionChamber)		, 150, 150, 160);
$GroundCarapaceLevel2		= new Upgrade("Ground Carapace Level 2"	, Zerg, array($Lair)	, array($EvolutionChamber)		, 225, 225, 190);
$GroundCarapaceLevel3		= new Upgrade("Ground Carapace Level 3"	, Zerg, array($Hive)	, array($EvolutionChamber)		, 300, 300, 220);
$FlyerCarapaceLevel1		= new Upgrade("Flyer Carapace Level 1"	, Zerg, array()			, array($Spire, $GreaterSpire)	, 150, 150, 160);
$FlyerCarapaceLevel2		= new Upgrade("Flyer Carapace Level 2"	, Zerg, array($Lair)	, array($Spire, $GreaterSpire)	, 225, 225, 190);
$FlyerCarapaceLevel3		= new Upgrade("Flyer Carapace Level 3"	, Zerg, array($Hive)	, array($Spire, $GreaterSpire)	, 300, 300, 220);
$CentrifugalHooks			= new Upgrade("Centrifugal Hooks"		, Zerg, array($Lair)	, array($BanelingNest)			, 150, 150, 110);
$GlialReconstitution		= new Upgrade("Glial Reconstitution"	, Zerg, array($Lair)	, array($RoachWarren)			, 100, 100, 110);
$MetabolicBoost				= new Upgrade("Metabolic Boost"			, Zerg, array()			, array($SpawningPool)			, 100, 100, 110);
$PneumatizedCarapace		= new Upgrade("Pneumatized Carapace"	, Zerg, array($Lair)	, array($Hatchery, $Lair, $Hive), 100, 100,  60);
$GroovedSpines				= new Upgrade("Grooved Spines"			, Zerg, array()			, array($HydraliskDen)			, 150, 150,  80);
$NeuralParasite				= new Upgrade("Neural Parasite"			, Zerg, array()			, array($InfestationPit)		, 150, 150, 110);
$PathogenGlands				= new Upgrade("Pathogen Glands"			, Zerg, array()			, array($InfestationPit)		, 150, 150,  80);
$AdrenalGlands				= new Upgrade("Adrenal Glands"			, Zerg, array($Hive)	, array($SpawningPool)			, 200, 200, 130);
$VentralSacs				= new Upgrade("Ventral Sacs"			, Zerg, array($Lair)	, array($Hatchery, $Lair, $Hive), 200, 200, 130);
$TunnelingClaws				= new Upgrade("Tunneling Claws"			, Zerg, array($Lair)	, array($RoachWarren)			, 150, 150, 110);
$ChitinousPlating			= new Upgrade("Chitinous Plating"		, Zerg, array()			, array($UltraliskCavern)		, 150, 150, 110);
$Burrow						= new Upgrade("Burrow"					, Zerg, array($Lair)	, array($Hatchery, $Lair, $Hive), 100, 100, 100);

/// Spellcasters & Abilities
$Queen->makeSpellcaster(25, 200);
$SpawnLarvae			= new Ability("Spawn Larvae"			, Zerg		, null, $Queen			, 25,  40);
$CreepTumor				= new Ability("Creep Tumor"				, Zerg		, null, $Queen			, 25,  15);
$Nexus->makeSpellcaster(0, 100);
$ChronoBoost			= new Ability("Chrono Boost"			, Protoss	, null, $Nexus			, 25,  20, false);
$OrbitalCommand->makeSpellcaster(50, 100);
$CalldownMULE			= new Ability("Calldown: MULE"			, Terran	, null, $OrbitalCommand	, 50,  90);
$CalldownExtraSupplies	= new Ability("Calldown: Extra Supplies", Terran	, null, $OrbitalCommand	, 50,   0);
$ScannerSweep			= new Ability("Scanner Sweep"			, Terran	, null, $OrbitalCommand	, 50,  12);

/// Supply
$CommandCenter->supplyCapacity			= 11;
$SupplyDepot->supplyCapacity			=  8;
$CalldownExtraSupplies->supplyCapacity	=  8;
$Hatchery->supplyCapacity				=  2;
$Overlord->supplyCapacity				=  8;
$Overseer->supplyCapacity				=  8;
$Nexus->supplyCapacity					= 10;
$Pylon->supplyCapacity					=  8;

/// Proxy
$ScoutingWorker = new Product("Scouting Worker", 0, null, null, null, null, null, null, false);

/// Designations
$CommandCenter->designate(Terran | Base);
$SCV->designate(Terran | Worker);
$Refinery->designate(Terran | Geyser);
$MorphToOrbitalCommand->designate(Terran | Booster);

$Nexus->designate(Protoss | Base);
$Probe->designate(Protoss | Worker);
$Assimilator->designate(Protoss | Geyser);
$Nexus->designate(Protoss | Booster);

$Hatchery->designate(Zerg | Base);
$Drone->designate(Zerg | Worker);
$Extractor->designate(Zerg | Geyser);
$Queen->designate(Zerg | Booster);

/// Patch 1.1.2
$Barracks->prerequisites = array($SupplyDepot);
$NitroPacks->prerequisites = array($Factory);

// Patch 1.2.0
$Observer->mineralCost = 25;
$Observer->gasCost = 75;
$Phoenix->timeCost = 35;

// Patch 1.3.0
$KhaydarinAmulet->drop();
unset($KhaydarinAmulet);
$Bunker->timeCost = 40;
$Stimpack->timeCost = 170;

// Patch 1.3.3
$WarpgateUpgrade->timeCost = 160;
$Sentry->timeCost = 37;
$SalvageBunker->mineralCost = -75;
$Ghost->mineralCost = 200;
$Ghost->gasCost = 100;

// Patch 1.4.0
$Blink->timeCost = 140;
$Barracks->timeCost = 65;
$Overseer->gasCost = 50;
$Ultralisk->timeCost = 55;

// Patch 1.4.2
$GroundWeaponsLevel2->mineralCost = 150;
$GroundWeaponsLevel3->mineralCost = 200;
$GroundArmorLevel2->mineralCost = 150;
$GroundArmorLevel3->mineralCost = 200;
$ShieldsLevel1->mineralCost = 150;
$ShieldsLevel2->mineralCost = 225;
$ShieldsLevel3->mineralCost = 300;
$GroundWeaponsLevel2->gasCost = 150;
$GroundWeaponsLevel3->gasCost = 200;
$GroundArmorLevel2->gasCost = 150;
$GroundArmorLevel3->gasCost = 200;
$ShieldsLevel1->gasCost = 150;
$ShieldsLevel2->gasCost = 225;
$ShieldsLevel3->gasCost = 300;
?>