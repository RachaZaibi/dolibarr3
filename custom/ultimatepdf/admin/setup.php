<?php
/* Copyright (C) 2004-2017  Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2010-2012	Regis Houssin	<regis.houssin@capnetworks.com>
 * Copyright (C) 2011-2020	Philippe Grand	<philippe.grand@atoo-net.com>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file    ultimatepdf/admin/setup.php
 * \ingroup ultimatepdf
 * \brief   ultimatepdf setup page.
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

global $langs, $user, $conf, $db;

// Libraries
dol_include_once('/ultimatepdf/class/actions_ultimatepdf.class.php', 'ActionsUltimatepdf');
require_once '../lib/ultimatepdf.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formadmin.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';

// Translations
$langs->loadLangs(array("admin", "other", "ultimatepdf@ultimatepdf"));

// Access control
if (!$user->rights->ultimatepdf->config) accessforbidden();

// Parameters
$action = GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');
$confirm = GETPOST('confirm', 'alpha');
$urlfile = GETPOST('urlfile', 'alpha');
$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');

if (!$sortorder) $sortorder = "ASC";
if (!$sortfield) $sortfield = "position_name";

/*
 * Actions
 */

$form = new Form($db);
$formadmin = new FormAdmin($db);
$formother = new FormOther($db);
$formfile = new FormFile($db);

$object = new ActionsUltimatepdf($db);
$object->doActions($parameters = false, $object, $action);


/*
 * View
 */

$wikihelp = 'EN:Module_Ultimatepdf_EN#Setup_models|FR:Module_Ultimatepdf_FR#Configuration_des_mod.C3.A8les';
$page_name = "UltimatepdfSetup";
llxHeader('', $langs->trans($page_name), $wikihelp);

// Subheader
$linkback = '<a href="' . ($backtopage ? $backtopage : DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1') . '">' . $langs->trans("BackToModuleList") . '</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'ultimatepdf@ultimatepdf');

// Configuration header
$head = ultimatepdf_prepare_head();
print dol_get_fiche_head($head, 'designs', $object->getTitle($action), -1, "ultimatepdf@ultimatepdf");

// Check current version
if (!checkUltimatepdfVersion())
	dol_htmloutput_mesg($langs->trans("UltimatepdfUpgradeIsNeeded"), '', 'error', 1);

// Assign template values
$object->assign_values($action);

// Show errors
dol_htmloutput_errors((!empty($object->error) ? '' : isset($object->error)), $object->errors);

// Show messages
dol_htmloutput_mesg($object->mesg, '', 'ok');

// Show the template
$object->display();

// Footer
llxFooter();
// Close database handler
$db->close();
?>