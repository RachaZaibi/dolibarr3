<?php
/* Copyright (C) 2011-2019 Regis Houssin  <regis.houssin@capnetworks.com>
 * Copyright (C) 2012-2021 Philippe Grand <philippe.grand@atoo-net.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *		\file       /ultimatepdf/css/ultimatepdf.css.php
 *		\brief      Fichier de style CSS complementaire du module Ultimatepdf
 */

//if (! defined('NOREQUIREUSER')) define('NOREQUIREUSER','1');	// Not disabled cause need to load personalized language
//if (! defined('NOREQUIREDB'))   define('NOREQUIREDB','1');	// Not disabled to increase speed. Language code is found on url.
if (!defined('NOREQUIRESOC'))    define('NOREQUIRESOC', '1');
//if (! defined('NOREQUIRETRAN')) define('NOREQUIRETRAN','1');	// Not disabled cause need to do translations
if (!defined('NOCSRFCHECK'))     define('NOCSRFCHECK', 1);
if (!defined('NOTOKENRENEWAL'))  define('NOTOKENRENEWAL', 1);
if (!defined('NOLOGIN'))         define('NOLOGIN', 1);
if (!defined('NOREQUIREMENU'))   define('NOREQUIREMENU', 1);
if (!defined('NOREQUIREHTML'))   define('NOREQUIREHTML', 1);
if (!defined('NOREQUIREAJAX'))   define('NOREQUIREAJAX', '1');
if (!defined('NOREQUIREHOOK'))   define('NOREQUIREHOOK', '1');  // Disable "main.inc.php" hooks

define('ISLOADEDBYSTEELSHEET', '1');

$res = 0;
$res = @include '../../main.inc.php';					// For "root" directory
if (!$res && file_exists($_SERVER['DOCUMENT_ROOT'] . "/main.inc.php"))
	$res = @include($_SERVER['DOCUMENT_ROOT'] . "/main.inc.php"); // Use on dev env only
if (!$res) $res = @include '../../../main.inc.php';	// For "custom" directory
if (!$res) @include("../../../../../dolibarr/htdocs/main.inc.php");	// Used on dev env only


// Define css type
header('Content-type: text/css');
// Important: Following code is to avoid page request by browser and PHP CPU at
// each Dolibarr page access.
if (empty($dolibarr_nocache)) header('Cache-Control: max-age=3600, public, must-revalidate');
else header('Cache-Control: no-cache');


if (!empty($_GET["lang"])) $langs->setDefaultLang($_GET["lang"]);	// If language was forced on URL by the main.inc.php
$langs->load("main", 0, 1);
$right = ($langs->direction == 'rtl' ? 'left' : 'right');
$left = ($langs->direction == 'rtl' ? 'right' : 'left');
?>
div.info {
background: #8db6c8;
}
.updficon {
background-image: url('<?php echo dol_buildpath('/ultimatepdf/img/object_ultimatepdf.png', 1) ?>');
background-repeat: no-repeat;
}
.updficon-large {
background-image: url('<?php echo dol_buildpath('/ultimatepdf/img/swiss.png', 1) ?>');
background-repeat: no-repeat;
}

.padding-left20 {
padding-left: 20px!important;
}

#design {
width: 200px;
}
#select2-design-container {
color: #444 !important;
}

#switchdesign {
<?php if (GETPOSTISSET('theme') && GETPOST('theme', 'aZ', 1) !== 'eldy') { ?>
	padding-top: 3px;
<?php } elseif ($conf->global->MAIN_THEME === 'eldy' && !empty($conf->global->ULTIMATEPDF_DROPDOWN_MENU_DISABLED)) { ?>
	padding-top: 19px;
<?php } else { ?>
	padding-top: 3px;
<?php } ?>
}

img.switchdesign {
cursor:pointer;
/*padding: <?php echo ($conf->browser->phone ? '0' : '8') ?>px 0px 0px 0px;*/
/*margin: 0px 0px 0px 8px;*/
text-decoration: none;
color: white;
font-weight: bold;
}
<!-- Set Logo height -->
.ui-widget-header {
background:#b9cd6d;
border: 1px solid #b9cd6d;
color: #FFFFFF;
font-weight: bold;
}
.ui-widget-content {
background: #cedc98;
border: 1px solid #DDDDDD;
color: #333333;
}
.ui-state-active {
border: 1px solid #fbd850;
color: #eb8f00;
font-weight: bold;
}
.ui-icon-gripsmall-diagonal-sw {
background-image: url('<?php echo dol_buildpath("/ultimatepdf/img/ui-icons_sw_256x240.png", 1); ?>')!important;
}
.ui-resizable-sw {
bottom: 1px;
left: 1px;
}
#container_logo, #container_otherlogo { width: 440px; height: 220px; }
#container2, #container3, #container4, #container5, #container6, #container7, #container8, #container9, #container10, #container11, #container12, #container_unit, #container_unit { width: 208px; height: 295px; }
#container_desc { width: 210px; height: 295px; }
#container_for_freetext { width: 210px; height: 95px; }
#container_desc h3 { text-align: center; margin: 0; margin-bottom: 10px; }
#container_AddressesBlocks { width: 210px; height: 160px; }
#resizable_desc, #container_desc { padding: 5px;}
#resizable-1, #resizable-3 {background-position: top left;
width: 150px; height: 150px; }
#resizable-1, #resizable-3, #container_logo, #container_otherlogo { padding: 1em !important; }
#resizable-5 {
left: 10px;
right: 10px;
top : 10px;
bottom : 10px;
width: 190px;
height: 277px;
}
#resizable-7 {
background-position: top left;
width: 30px; height: 295px;
}
#resizable-9 {
left: 100px;
background-position: top;
width: 30px; height: 295px;
}
#resizable-11 {
background-position: bottom left;
width: 208px; height: 295px;
}
#resizable-11 h3 { text-align: center; margin: 0; margin-bottom: 10px; }
#resizable-13 {
background-position: top left;
width: 30px;
height: 295px;
}
#resizable-15 {
left: 110px;
background-position: top left;
width: 30px;
height: 295px;
}
#resizable-17 {
left: 120px;
background-position: top left;
width: 30px;
height: 295px;
}
#resizable-19 {
left: 130px;
background-position: top left;
width: 30px;
height: 295px;
}
#resizable-21 {
left: 140px;
background-position: top left;
width: 30px;
height: 295px;
}
#resizable-25 {
left: 100px;
background-position: top left;
width: 30px; height: 295px;
}
#resizable-27 {
left: 110px;
background-position: top left;
width: 30px; height: 295px;
}
#resizable-29 {
left: 120px;
background-position: top left;
width: 30px; height: 295px;
}
#resizable_desc {
background-position: top left;
width: 110px;
height: 295px;
}
#resizable_unit {
left: 150px;
background-position: top left;
width: 20px;
height: 295px;
}

#sender_frame {
position:relative;
float:left;
height:100%;
width:93px;
background-color:IndianRed;
}
#recipient_frame {
position:relative;
float:left;
height:100%;
width:93px;
background-color:BurlyWood;
}

::-webkit-input-placeholder {
color: #003f7f;
}
:-moz-placeholder { /* Firefox 18- */
color: #003f7f;
}
::-moz-placeholder { /* Firefox 19+ */
color: #003f7f;
}
:-ms-input-placeholder {
color: #003f7f;
}

<?php
if (($conf->global->MAIN_THEME === 'eldy' && empty($conf->global->ULTIMATEPDF_DROPDOWN_MENU_DISABLED) && !GETPOSTISSET('theme')) || (GETPOSTISSET('theme') && GETPOST('theme', 'aZ', 1) === 'eldy')) {
	include dol_buildpath('/ultimatepdf/css/dropdown.inc.php');
}?>
<!-- End set Logo height -->