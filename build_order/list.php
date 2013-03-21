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

/**
 * Render a list of all products.
 */
require("product.php");

header("Content-type: text/plain");

/**
 * Render all products of the given type.
 * @param int $type
 */
function listProducts($type) {
	echo "# ";
	if($type & Protoss) {
		echo "Protoss ";
	} elseif($type & Terran) {
		echo "Terran ";
	} elseif($type & Zerg) {
		echo "Zerg ";
	}
	if($type & Structure) {
		echo "Structure\n";
	} elseif($type & Unit) {
		echo "Unit\n";
	} elseif($type & Morph) {
		echo "Morph\n";
	} elseif($type & Upgrade) {
		echo "Upgrade\n";
	} elseif($type & Ability) {
		echo "Ability\n";
	}
	
	foreach(Product::$all as $product) {
		if(($product->type & $type) == $type && !empty($product->name)) {
			echo $product ."\n";
		}
	}
	echo "\n";
}

// Protoss
listProducts(Protoss | Structure);	
listProducts(Protoss | Morph);
listProducts(Protoss | Unit);
listProducts(Protoss | Upgrade);
listProducts(Protoss | Ability);

// Terran
listProducts(Terran | Structure);	
listProducts(Terran | Morph);
listProducts(Terran | Unit);
listProducts(Terran | Upgrade);
listProducts(Terran | Ability);

// Zerg
listProducts(Zerg | Structure);	
listProducts(Zerg | Morph);
listProducts(Zerg | Unit);
listProducts(Zerg | Upgrade);
listProducts(Zerg | Ability);
?>