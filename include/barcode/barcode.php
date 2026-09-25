<?php
// This file is part of Quipux – Document Management System
//
// Quipux is free software and is currently under a process of technical
// modernization and functional improvement carried out by
// EXDUCERE ONLINE CIA. LTDA., as part of the development of a new version
// of the Quipux platform.
//
// Quipux is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Quipux is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Quipux. If not, see <http://www.gnu.org/licenses/>.

/**
 * @package    barcode
 * @author     2025 Casen Xu <casenxu@exducereonline.com>
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

	require("barcode.inc.php");

	$bar= new BARCODE();
	
	if($bar==false)
		die($bar->error());
	// OR $bar= new BARCODE("I2O5");

	$barnumber=$bdata;
	//$barnumber="200780";
	//$barnumber="801221905";
	//$barnumber="A40146B";
	//$barnumber="Code 128";
	//$barnumber="TEST8052";
	//$barnumber="TEST93";
	
	$bar->setSymblogy($encode);
	$bar->setHeight($height);
	//$bar->setFont("arial");
	$bar->setScale($scale);
	$bar->setHexColor($color,$bgcolor);

	/*$bar->setSymblogy("UPC-E");
	$bar->setHeight(50);
	$bar->setFont("arial");
	$bar->setScale(2);
	$bar->setHexColor("#000000","#FFFFFF");*/

	//OR
	//$bar->setColor(255,255,255)   RGB Color
	//$bar->setBGColor(0,0,0)   RGB Color

  	
	$return = $bar->genBarCode($barnumber,$type,$file);
	if($return==false)
		$bar->error(true);
	
?>