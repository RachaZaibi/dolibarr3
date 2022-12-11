<?php
/* Copyright (C) 2011-2021	Philippe Grand	<philippe.grand@atoo-net.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * 	\file		htdocs/custom/ultimatepdf/admin/about.php
 * 	\ingroup	ultimatepdf
 * 	\brief		about page
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include($_SERVER["CONTEXT_DOCUMENT_ROOT"] . "/main.inc.php");
// Try main.inc.php into web root detected using web root caluclated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
$tmp2 = realpath(__FILE__);
$i = strlen($tmp) - 1;
$j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
    $i--;
    $j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1)) . "/main.inc.php")) $res = @include(substr($tmp, 0, ($i + 1)) . "/main.inc.php");
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php")) $res = @include(dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php");
// Try main.inc.php using relative path
if (!$res && file_exists("../../main.inc.php")) $res = @include("../../main.inc.php");
if (!$res && file_exists($_SERVER['DOCUMENT_ROOT'] . "/main.inc.php")) $res = @include($_SERVER['DOCUMENT_ROOT'] . "/main.inc.php"); // Use on dev env only
if (!$res && file_exists("../../../main.inc.php")) $res = @include("../../../main.inc.php");
if (!$res) die("Include of main fails");

global $langs, $user;

// Libraries
require_once("../lib/ultimatepdf.lib.php");
dol_include_once("ultimatepdf/class/actions_ultimatepdf.class.php");
dol_include_once("ultimatepdf/lib/vendor/autoload.php");

// Get Markdown class
use Michelf\Markdown;

// Translations
$langs->loadLangs(array("admin", "ultimatepdf@ultimatepdf"));

// Access control
if (!$user->rights->ultimatepdf->config) accessforbidden();

/*
 * View
 */

$wikihelp = 'EN:Module_Ultimatepdf_EN|FR:Module_Ultimatepdf_FR';
$page_name = $langs->trans("About");
llxHeader('', $page_name, $wikihelp);

// Subheader
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">' . $langs->trans("BackToModuleList") . '</a>';

print load_fiche_titre($page_name, $linkback, 'ultimatepdf@ultimatepdf');

// Configuration header
$head = ultimatepdf_prepare_head();
print dol_get_fiche_head($head, 'about', $langs->trans("Module300100Name"), 0, "ultimatepdf@ultimatepdf");

// About page goes here
if ($user->conf->MAIN_LANG_DEFAULT == 'fr_FR') {
    $buffer = file_get_contents(dol_buildpath('/ultimatepdf/README-FR.md', 0));
} else {
    $buffer = file_get_contents(dol_buildpath('/ultimatepdf/README.md', 0));
}
print Markdown::defaultTransform($buffer);

print dol_get_fiche_end();

// Footer
llxFooter();
// Close database handler
$db->close();
?>
