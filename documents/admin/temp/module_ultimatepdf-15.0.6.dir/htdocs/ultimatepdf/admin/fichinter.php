<?php
/* Copyright (C) 2011 Regis Houssin  <regis.houssin@capnetworks.com>
 * Copyright (C) 2011-2021	Philippe Grand	<philippe.grand@atoo-net.com>
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
 *  \file       ultimatepdf/admin/fichinter.php
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
require_once(DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php");
require_once("../lib/ultimatepdf.lib.php");

// Translations
$langs->loadLangs(array("admin", "bills", "sendings", "interventions", "deliveries", "ultimatepdf@ultimatepdf"));

// Security check
if (!$user->rights->ultimatepdf->config) accessforbidden();

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

$wikihelp = 'EN:Module_Ultimatepdf_EN#Interventions_tab|FR:Module_Ultimatepdf_FR#Onglet_Interventions';
$page_name = "UltimatepdfSetup";
llxHeader('', $langs->trans($page_name), $wikihelp);

// Subheader
$linkback = '<a href="' . ($backtopage ? $backtopage : DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1') . '">' . $langs->trans("BackToModuleList") . '</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'ultimatepdf@ultimatepdf');

// Configuration header
$head = ultimatepdf_prepare_head();
print dol_get_fiche_head($head, 'ficheinter', $langs->trans("ModuleSetup"), 0, "ultimatepdf@ultimatepdf");
print '<div align="center" class="info">';
print '<em><b>' . $langs->trans("SetUpHeader") . '</em></b>';
print '</div>';

// Addresses
print load_fiche_titre($langs->trans("PDFAddressForging"), '', '') . '<br>';

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Parameters") . '</td>' . "\n";
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="100">' . $langs->trans("Value") . '</td>' . "\n";
print '</tr>';

// add also details for contact address.
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("ShowAlsoTargetDetails"), $langs->trans("ShowAlsoTargetDetailsDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATE_PDF_FICHINTER_ADDALSOTARGETDETAILS');
} else {
	if ($conf->global->ULTIMATE_PDF_FICHINTER_ADDALSOTARGETDETAILS == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATE_PDF_FICHINTER_ADDALSOTARGETDETAILS">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATE_PDF_FICHINTER_ADDALSOTARGETDETAILS == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATE_PDF_FICHINTER_ADDALSOTARGETDETAILS">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// add also details for client address.
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("ShowAlsoClientDetails"), $langs->trans("ShowAlsoClientDetailsDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATE_PDF_FICHINTER_ADDALSOCLIENTDETAILS');
} else {
	if ($conf->global->ULTIMATE_PDF_FICHINTER_ADDALSOCLIENTDETAILS == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATE_PDF_FICHINTER_ADDALSOCLIENTDETAILS">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATE_PDF_FICHINTER_ADDALSOCLIENTDETAILS == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATE_PDF_FICHINTER_ADDALSOCLIENTDETAILS">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';
print '</table>';

print '<div align="center" class="info">';
print '<em><b>' . $langs->trans("SetCoreBloc") . '</em></b>';
print '</div>';
print '</td></tr>';

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

// add barcode at top within shipments.
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("ShowByDefaultBarcodeAtTopInsideFichinterUltimatepdf"), $langs->trans("ShowByDefaultBarcodeAtTopInsideFichinterUltimatepdfDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATEPDF_GENERATE_FICHINTER_WITH_TOP_BARCODE');
} else {
	if ($conf->global->ULTIMATEPDF_GENERATE_FICHINTER_WITH_TOP_BARCODE == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATEPDF_GENERATE_FICHINTER_WITH_TOP_BARCODE">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATEPDF_GENERATE_FICHINTER_WITH_TOP_BARCODE == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATEPDF_GENERATE_FICHINTER_WITH_TOP_BARCODE">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// add thirdparty QRcode at top within Shipments.
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("ShowByDefaultQRcodeAtTopInsideFichinterUltimatepdf"), $langs->trans("ShowByDefaultQRcodeAtTopInsideFichinterUltimatepdfDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATEPDF_GENERATE_FICHINTER_WITH_TOP_QRCODE');
} else {
	if ($conf->global->ULTIMATEPDF_GENERATE_FICHINTER_WITH_TOP_QRCODE == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATEPDF_GENERATE_FICHINTER_WITH_TOP_QRCODE">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATEPDF_GENERATE_FICHINTER_WITH_TOP_QRCODE == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATEPDF_GENERATE_FICHINTER_WITH_TOP_QRCODE">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// add order link QRcode at top within FICHINTER.
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("ShowByDefaultOrderLinkQRcodeAtTopInsideFichinterUltimatepdf"), $langs->trans("ShowByDefaultOrderLinkQRcodeAtTopInsideFichinterUltimatepdfDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATEPDF_GENERATE_ORDERLINK_WITH_TOP_QRCODE');
} else {
	if ($conf->global->ULTIMATEPDF_GENERATE_ORDERLINK_WITH_TOP_QRCODE == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATEPDF_GENERATE_ORDERLINK_WITH_TOP_QRCODE">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATEPDF_GENERATE_ORDERLINK_WITH_TOP_QRCODE == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATEPDF_GENERATE_ORDERLINK_WITH_TOP_QRCODE">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// add my comp QRcode at top within Shipments.
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("ShowByDefaultMycompQRcodeAtTopInsideFichinterUltimatepdf"), $langs->trans("ShowByDefaultMycompQRcodeAtTopInsideFichinterUltimatepdfDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATEPDF_GENERATE_FICHINTER_WITH_MYCOMP_QRCODE');
} else {
	if ($conf->global->ULTIMATEPDF_GENERATE_FICHINTER_WITH_MYCOMP_QRCODE == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATEPDF_GENERATE_FICHINTER_WITH_MYCOMP_QRCODE">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATEPDF_GENERATE_FICHINTER_WITH_MYCOMP_QRCODE == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATEPDF_GENERATE_FICHINTER_WITH_MYCOMP_QRCODE">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// display project reference.
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("DisplayRefProjectInsideFichinterUltimatepdf"), $langs->trans("DisplayRefProjectInsideFichinterUltimatepdfDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATE_FICHINTER_PDF_SHOW_PROJECT');
} else {
	if ($conf->global->ULTIMATE_FICHINTER_PDF_SHOW_PROJECT == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATE_FICHINTER_PDF_SHOW_PROJECT">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATE_FICHINTER_PDF_SHOW_PROJECT == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATE_FICHINTER_PDF_SHOW_PROJECT">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// display project title.
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("DisplayTitleProjectInsideFichinterUltimatepdf"), $langs->trans("DisplayTitleProjectInsideFichinterUltimatepdfDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATE_FICHINTER_PDF_SHOW_PROJECT_TITLE');
} else {
	if ($conf->global->ULTIMATE_FICHINTER_PDF_SHOW_PROJECT_TITLE == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATE_FICHINTER_PDF_SHOW_PROJECT_TITLE">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATE_FICHINTER_PDF_SHOW_PROJECT_TITLE == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATE_FICHINTER_PDF_SHOW_PROJECT_TITLE">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

/*
 * Formulaire parametres divers
 */

print load_fiche_titre($langs->trans("UltimatepdfSpecificInterventions"), '', '') . '<br>';
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Parameters") . '</td>' . "\n";
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="100">' . $langs->trans("Value") . '</td>' . "\n";
print '</tr>';

// display sale representative signature within shipments note .
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("ShowByDefaultSaleRepSignatureInsideUltimatepdf"), $langs->trans("ShowByDefaultSaleRepSignatureInsideUltimatepdfDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('MAIN_ADD_SALE_REP_SIGNATURE_IN_FICHINTER_NOTE');
} else {
	if ($conf->global->MAIN_ADD_SALE_REP_SIGNATURE_IN_FICHINTER_NOTE == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_MAIN_ADD_SALE_REP_SIGNATURE_IN_FICHINTER_NOTE">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->MAIN_ADD_SALE_REP_SIGNATURE_IN_FICHINTER_NOTE == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_MAIN_ADD_SALE_REP_SIGNATURE_IN_FICHINTER_NOTE">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// Add line between products lines
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("ShowByDefaultDashBetweenLinesInsideUltimatepdf"), $langs->trans("ShowByDefaultDashBetweenLinesInsideUltimatepdfDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATE_FICHINTER_PDF_DASH_BETWEEN_LINES');
} else {
	if ($conf->global->ULTIMATE_FICHINTER_PDF_DASH_BETWEEN_LINES == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATE_FICHINTER_PDF_DASH_BETWEEN_LINES">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATE_FICHINTER_PDF_DASH_BETWEEN_LINES == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATE_FICHINTER_PDF_DASH_BETWEEN_LINES">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// Add specific code for Encraje
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("ShowSpecificCodeForEncrajeInsideUltimatepdf"), $langs->trans("ShowSpecificCodeForEncrajeInsideUltimatepdfDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATE_FICHINTER_SPECIFIC_CODE_FOR_ENCRAJE');
} else {
	if ($conf->global->ULTIMATE_FICHINTER_SPECIFIC_CODE_FOR_ENCRAJE == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATE_FICHINTER_SPECIFIC_CODE_FOR_ENCRAJE">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATE_FICHINTER_SPECIFIC_CODE_FOR_ENCRAJE == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATE_FICHINTER_SPECIFIC_CODE_FOR_ENCRAJE">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// do not repeat header.
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("DoNotRepeatHeadInsideUltimatepdf"), $langs->trans("DoNotRepeatHeadInsideUltimatepdfDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATE_FICHINTER_PDF_DONOTREPEAT_HEAD');
} else {
	if ($conf->global->ULTIMATE_FICHINTER_PDF_DONOTREPEAT_HEAD == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATE_FICHINTER_PDF_DONOTREPEAT_HEAD">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATE_FICHINTER_PDF_DONOTREPEAT_HEAD == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATE_FICHINTER_PDF_DONOTREPEAT_HEAD">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// display column line number
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("ShowByDefaultColumnLineNumberInsideUltimatepdf"), $langs->trans("ShowByDefaultColumnLineNumberInsideUltimatepdfDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATE_FICHINTER_WITH_LINE_NUMBER');
} else {
	if ($conf->global->ULTIMATE_FICHINTER_WITH_LINE_NUMBER == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATE_FICHINTER_WITH_LINE_NUMBER">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATE_FICHINTER_WITH_LINE_NUMBER == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATE_FICHINTER_WITH_LINE_NUMBER">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// display column date within fichinter
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("ShowByDefaultColumnDateInsideUltimatepdf"), $langs->trans("ShowByDefaultColumnDateInsideUltimatepdfDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATE_GENERATE_FICHINTER_WITH_DATE');
} else {
	if ($conf->global->ULTIMATE_GENERATE_FICHINTER_WITH_DATE == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATE_GENERATE_FICHINTER_WITH_DATE">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATE_GENERATE_FICHINTER_WITH_DATE == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATE_GENERATE_FICHINTER_WITH_DATE">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// display column duration within fichinter
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("ShowByDefaultColumnDurationInsideUltimatepdf"), $langs->trans("ShowByDefaultColumnDurationInsideUltimatepdfDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATE_GENERATE_FICHINTER_WITH_DURATION');
} else {
	if ($conf->global->ULTIMATE_GENERATE_FICHINTER_WITH_DURATION == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATE_GENERATE_FICHINTER_WITH_DURATION">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATE_GENERATE_FICHINTER_WITH_DURATION == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATE_GENERATE_FICHINTER_WITH_DURATION">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';
print '</table>';

print '<div align="center" class="info">';
print '<em><b>' . $langs->trans("SetFooterBloc") . '</em></b>';
print '</div>';
print '</td></tr>';

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Parameters") . '</td>' . "\n";
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="100">' . $langs->trans("Value") . '</td>' . "\n";
print '</tr>';

// display electronic signature within agreement bloc
/*print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("ShowByDefaultElectronicSignatureWithinAgreementBlock"), $langs->trans("ShowByDefaultElectronicSignatureWithinAgreementBlockDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATEPDF_DISPLAY_FICHINTER_ELECTRONIC_SIGNATURE');
} else {
	if ($conf->global->ULTIMATEPDF_DISPLAY_FICHINTER_ELECTRONIC_SIGNATURE == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATEPDF_DISPLAY_FICHINTER_ELECTRONIC_SIGNATURE">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATEPDF_DISPLAY_FICHINTER_ELECTRONIC_SIGNATURE == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATEPDF_DISPLAY_FICHINTER_ELECTRONIC_SIGNATURE">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';//stand-by
*/
// add Responsable signature within fichinter.
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("AddResponsableSignatureInsideFichinterUltimatepdf"), $langs->trans("AddResponsableSignatureInsideFichinterUltimatepdfDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATEPDF_ADD_RESPONSABLE_SIGNATURE_IN_FICHINTER');
} else {
	if ($conf->global->ULTIMATEPDF_ADD_RESPONSABLE_SIGNATURE_IN_FICHINTER == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATEPDF_ADD_RESPONSABLE_SIGNATURE_IN_FICHINTER">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATEPDF_ADD_RESPONSABLE_SIGNATURE_IN_FICHINTER == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATEPDF_ADD_RESPONSABLE_SIGNATURE_IN_FICHINTER">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// add merge of pdf files within fichinter.
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("ShowByDefaultMergePdfInsideFichinterUltimatepdf"), $langs->trans("ShowByDefaultMergePdfInsideFichinterUltimatepdfDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATEPDF_GENERATE_FICHINTER_WITH_MERGED_PDF');
} else {
	if ($conf->global->ULTIMATEPDF_GENERATE_FICHINTER_WITH_MERGED_PDF == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATEPDF_GENERATE_FICHINTER_WITH_MERGED_PDF">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATEPDF_GENERATE_FICHINTER_WITH_MERGED_PDF == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATEPDF_GENERATE_FICHINTER_WITH_MERGED_PDF">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// add Newline before agreement tab within FICHINTER.
/*
print '<tr class="oddeven">';
print '<td>'.$langs->trans("ShowByDefaultNewlineInsideFichinterUltimatepdf").'</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax)
{
	print ajax_constantonoff('ULTIMATEPDF_GENERATE_INTERVENTIONS_WITH_NEWLINE');
}
else
{
	if($conf->global->ULTIMATEPDF_GENERATE_INTERVENTIONS_WITH_NEWLINE == 0)
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_ULTIMATEPDF_GENERATE_INTERVENTIONS_WITH_NEWLINE">'.img_picto($langs->trans("Disabled"),'off').'</a>';
	}
	else if($conf->global->ULTIMATEPDF_GENERATE_INTERVENTIONS_WITH_NEWLINE == 1)
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=del_ULTIMATEPDF_GENERATE_INTERVENTIONS_WITH_NEWLINE">'.img_picto($langs->trans("Enabled"),'on').'</a>';
	}
}
print '</td></tr>';
*/

print '</table>';

// Footer
llxFooter();
// Close database handler
$db->close();
