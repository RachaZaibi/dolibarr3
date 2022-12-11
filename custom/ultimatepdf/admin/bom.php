<?php
/* Copyright (C) 2011 Regis Houssin  <regis.houssin@capnetworks.com>
 * Copyright (C) 2011-2022	Philippe Grand	<philippe.grand@atoo-net.com>
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
 *  \file       htdocs/custom/ultimatepdf/admin/options.php
 *  \ingroup    ultimatepdf
 *  \brief      Page d'administration/configuration du module ultimatepdf
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

global $db, $langs, $user, $conf;

// Libraries
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
require_once("../lib/ultimatepdf.lib.php");

// Translations
$langs->loadLangs(array("admin", "orders", "ultimatepdf@ultimatepdf"));

// Security check
if (! $user->rights->ultimatepdf->config) accessforbidden();

// Parameters
$action = GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');


/*
 * Action
 */
if (preg_match('/set_(.*)/', $action, $reg)) {
	$code = $reg[1];
	if (dolibarr_set_const($db, $code, 1, 'chaine', 0, '', $conf->entity) > 0) {
		Header("Location: " . $_SERVER["PHP_SELF"]);
		exit;
	} else {
		dol_print_error($db);
	}
}

if (preg_match('/del_(.*)/', $action, $reg)) {
	$code = $reg[1];
	if (dolibarr_del_const($db, $code, $conf->entity) > 0) {
		Header("Location: " . $_SERVER["PHP_SELF"]);
		exit;
	} else {
		dol_print_error($db);
	}
}

/*
 * View
 */

$wikihelp = 'EN:Module_Ultimatepdf_EN#Boms_tab|FR:Module_Ultimatepdf_FR#Onglet_Boms';
$page_name = "UltimatepdfSetup";
llxHeader('', $langs->trans($page_name), $wikihelp);

$form = new Form($db);

// Subheader
$linkback = '<a href="' . ($backtopage ? $backtopage : DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1') . '">' . $langs->trans("BackToModuleList") . '</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'ultimatepdf@ultimatepdf');

// Configuration header
$head = ultimatepdf_prepare_head();
print dol_get_fiche_head($head, 'boms', $langs->trans("ModuleSetup"), 0, "ultimatepdf@ultimatepdf");

print '<div align="center" class="info">';
print '<em><b>' . $langs->trans("SetUpHeader") . '</em></b>';
print '</div>';

/*
 * Formulaire parametres divers
 */

// Addresses Area
print load_fiche_titre($langs->trans("PDFAddressForging"), '', '') . '<br>';

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Parameters") . '</td>' . "\n";
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="100">' . $langs->trans("Value") . '</td>' . "\n";
print '</tr>';

/*
 * Formulaire parametres divers
 */

print load_fiche_titre($langs->trans("UltimatepdfMiscellaneous"), '', '') . '<br>';
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Parameters") . '</td>' . "\n";
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="100">' . $langs->trans("Value") . '</td>' . "\n";
print '</tr>';

// Add Product barcode under product's description within boms.
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("ShowProductsBarcodeInsideBomUltimatepdf"), $langs->trans("ShowProductsBarcodeInsideBomUltimatepdfDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATEPDF_GENERATE_BOMS_WITH_PRODUCTS_BARCODE');
} else {
	if ($conf->global->ULTIMATEPDF_GENERATE_BOMS_WITH_PRODUCTS_BARCODE == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATEPDF_GENERATE_BOMS_WITH_PRODUCTS_BARCODE">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATEPDF_GENERATE_BOMS_WITH_PRODUCTS_BARCODE == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATEPDF_GENERATE_BOMS_WITH_PRODUCTS_BARCODE">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// add barcode at top within boms.
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("ShowByDefaultBarcodeAtTopInsideBomsUltimatepdf"), $langs->trans("ShowByDefaultBarcodeAtTopInsideBomsUltimatepdfDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATEPDF_GENERATE_BOMS_WITH_TOP_BARCODE');
} else {
	if ($conf->global->ULTIMATEPDF_GENERATE_BOMS_WITH_TOP_BARCODE == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATEPDF_GENERATE_BOMS_WITH_TOP_BARCODE">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATEPDF_GENERATE_BOMS_WITH_TOP_BARCODE == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATEPDF_GENERATE_BOMS_WITH_TOP_BARCODE">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// add my comp QRcode at top within boms.
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("ShowByDefaultMycompQRcodeAtTopInsideBomsUltimatepdf"), $langs->trans("ShowByDefaultMycompQRcodeAtTopInsideBomsUltimatepdfDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATEPDF_GENERATE_BOMS_WITH_MYCOMP_QRCODE');
} else {
	if ($conf->global->ULTIMATEPDF_GENERATE_BOMS_WITH_MYCOMP_QRCODE == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATEPDF_GENERATE_BOMS_WITH_MYCOMP_QRCODE">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATEPDF_GENERATE_BOMS_WITH_MYCOMP_QRCODE == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATEPDF_GENERATE_BOMS_WITH_MYCOMP_QRCODE">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// do not repeat header.
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("DoNotRepeatHeadInsideUltimatepdf"), $langs->trans("DoNotRepeatHeadInsideUltimatepdfDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATE_BOMS_PDF_DONOTREPEAT_HEAD');
} else {
	if ($conf->global->ULTIMATE_BOMS_PDF_DONOTREPEAT_HEAD == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATE_BOMS_PDF_DONOTREPEAT_HEAD">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATE_BOMS_PDF_DONOTREPEAT_HEAD == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATE_BOMS_PDF_DONOTREPEAT_HEAD">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';
print '</table>';

print '<div align="center" class="info">';
print '<em><b>' . $langs->trans("SetCoreBloc") . '</em></b>';
print '</div>';
print '</td></tr>';

print load_fiche_titre($langs->trans("UltimatepdfSpecificBoms"), '', '') . '<br>';
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Parameters") . '</td>' . "\n";
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="100">' . $langs->trans("Value") . '</td>' . "\n";
print '</tr>';

// Add line between products lines
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("ShowByDefaultDashBetweenLinesInsideUltimatepdf"), $langs->trans("ShowByDefaultDashBetweenLinesInsideUltimatepdfDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATE_BOMS_PDF_DASH_BETWEEN_LINES');
} else {
	if ($conf->global->ULTIMATE_BOMS_PDF_DASH_BETWEEN_LINES == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATE_BOMS_PDF_DASH_BETWEEN_LINES">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATE_BOMS_PDF_DASH_BETWEEN_LINES == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATE_BOMS_PDF_DASH_BETWEEN_LINES">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// display column line number
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("ShowByDefaultColumnLineNumberInsideUltimatepdf"), $langs->trans("ShowByDefaultColumnLineNumberInsideUltimatepdfDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATE_BOMS_WITH_LINE_NUMBER');
} else {
	if ($conf->global->ULTIMATE_BOMS_WITH_LINE_NUMBER == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATE_BOMS_WITH_LINE_NUMBER">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATE_BOMS_WITH_LINE_NUMBER == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATE_BOMS_WITH_LINE_NUMBER">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// add photos within boms.
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("ShowByDefaultPhotosInsideBomsUltimatepdf"), $langs->trans("ShowByDefaultPhotosInsideBomsUltimatepdfDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATE_GENERATE_BOMS_WITH_PICTURE');
} else {
	if ($conf->global->ULTIMATE_GENERATE_BOMS_WITH_PICTURE == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=ULTIMATE_GENERATE_BOMS_WITH_PICTURE">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATE_GENERATE_BOMS_WITH_PICTURE == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATE_GENERATE_BOMS_WITH_PICTURE">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// display Qty column 
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("UltimateGenerateBomsWithQty"), $langs->trans("UltimateGenerateBomsWithQtyDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATE_GENERATE_BOMS_WITH_QTY');
} else {
	if ($conf->global->ULTIMATE_GENERATE_BOMS_WITH_QTY == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATE_GENERATE_BOMS_WITH_QTY">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATE_GENERATE_BOMS_WITH_QTY == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATE_GENERATE_BOMS_WITH_QTY">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// display weight column 
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("UltimateGenerateBomsWithWeightColumn"), $langs->trans("UltimateGenerateBomsWithWeightColumnDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATE_GENERATE_BOMS_WITH_WEIGHT_COLUMN');
} else {
	if ($conf->global->ULTIMATE_GENERATE_BOMS_WITH_WEIGHT_COLUMN == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATE_GENERATE_BOMS_WITH_WEIGHT_COLUMN">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATE_GENERATE_BOMS_WITH_WEIGHT_COLUMN == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATE_GENERATE_BOMS_WITH_WEIGHT_COLUMN">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

print '</table>';

print '<div align="center" class="info">';
print '<em><b>' . $langs->trans("SetFooterBloc") . '</em></b>';
print '</div>';
print '</td></tr>';

print load_fiche_titre($langs->trans("UltimatepdfSpecificBoms"), '', '') . '<br>';
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Parameters") . '</td>' . "\n";
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="100">' . $langs->trans("Value") . '</td>' . "\n";
print '</tr>';

// Fix weight default rounding 
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("UltimateGenerateOrdersWeightDefaultRound"), $langs->trans("UltimateGenerateOrdersWeightDefaultRoundDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('MAIN_WEIGHT_DEFAULT_ROUND');
} else {
	if ($conf->global->MAIN_WEIGHT_DEFAULT_ROUND == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_MAIN_WEIGHT_DEFAULT_ROUND">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->MAIN_WEIGHT_DEFAULT_ROUND == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_MAIN_WEIGHT_DEFAULT_ROUND">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';
print '</table>';

// Footer
llxFooter();
// Close database handler
$db->close();
?>
