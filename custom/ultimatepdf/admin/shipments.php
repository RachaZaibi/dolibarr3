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
 *  \file       ultimatepdf/admin/shipments.php
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
$langs->loadLangs(array("admin", "bills", "sendings", "deliveries", "ultimatepdf@ultimatepdf"));

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

$wikihelp = 'EN:Module_Ultimatepdf_EN#Shipments_tab|FR:Module_Ultimatepdf_FR#Onglet_Exp&eacute;ditions';
$page_name = "UltimatepdfSetup";
llxHeader('', $langs->trans($page_name), $wikihelp);

// Subheader
$linkback = '<a href="' . ($backtopage ? $backtopage : DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1') . '">' . $langs->trans("BackToModuleList") . '</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'ultimatepdf@ultimatepdf');

// Configuration header
$head = ultimatepdf_prepare_head();
print dol_get_fiche_head($head, 'shipments', $langs->trans("ModuleSetup"), 0, "ultimatepdf@ultimatepdf");

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
	print ajax_constantonoff('ULTIMATE_PDF_SHIPPING_ADDALSOTARGETDETAILS');
} else {
	if ($conf->global->ULTIMATE_PDF_SHIPPING_ADDALSOTARGETDETAILS == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATE_PDF_SHIPPING_ADDALSOTARGETDETAILS">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATE_PDF_SHIPPING_ADDALSOTARGETDETAILS == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATE_PDF_SHIPPING_ADDALSOTARGETDETAILS">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// add also details for client address.
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("ShowAlsoClientDetails"), $langs->trans("ShowAlsoClientDetailsDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATE_PDF_SHIPPING_ADDALSOCLIENTDETAILS');
} else {
	if ($conf->global->ULTIMATE_PDF_SHIPPING_ADDALSOCLIENTDETAILS == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATE_PDF_SHIPPING_ADDALSOCLIENTDETAILS">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATE_PDF_SHIPPING_ADDALSOCLIENTDETAILS == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATE_PDF_SHIPPING_ADDALSOCLIENTDETAILS">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// Hide details from source within address block.
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("HideSourceDetails"), $langs->trans("HideSourceDetailsDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATE_PDF_SHIPPING_DISABLESOURCEDETAILS');
} else {
	if ($conf->global->ULTIMATE_PDF_SHIPPING_DISABLESOURCEDETAILS == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATE_PDF_SHIPPING_DISABLESOURCEDETAILS">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATE_PDF_SHIPPING_DISABLESOURCEDETAILS == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATE_PDF_SHIPPING_DISABLESOURCEDETAILS">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// hide TVA intra within address.
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("HideTvaIntraWithinAddress"), $langs->trans("HideTvaIntraWithinAddressDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATE_TVAINTRA_NOT_IN_SHIPPING_ADDRESS');
} else {
	if ($conf->global->ULTIMATE_TVAINTRA_NOT_IN_SHIPPING_ADDRESS == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATE_TVAINTRA_NOT_IN_SHIPPING_ADDRESS">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATE_TVAINTRA_NOT_IN_SHIPPING_ADDRESS == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATE_TVAINTRA_NOT_IN_SHIPPING_ADDRESS">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';
print '</table>';

print load_fiche_titre($langs->trans("UltimatepdfMiscellaneous"), '', '') . '<br>';
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Parameters") . '</td>' . "\n";
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="100">' . $langs->trans("Value") . '</td>' . "\n";
print '</tr>';

// Add Product barcode under product's description within shipments.
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("ShowProductsBarcodeInsideShippingUltimatepdf"), $langs->trans("ShowProductsBarcodeInsideShippingUltimatepdfDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATEPDF_GENERATE_SHIPPING_WITH_PRODUCTS_BARCODE');
} else {
	if ($conf->global->ULTIMATEPDF_GENERATE_SHIPPING_WITH_PRODUCTS_BARCODE == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATEPDF_GENERATE_SHIPPING_WITH_PRODUCTS_BARCODE">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATEPDF_GENERATE_SHIPPING_WITH_PRODUCTS_BARCODE == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATEPDF_GENERATE_SHIPPING_WITH_PRODUCTS_BARCODE">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// add barcode at top within shipments.
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("ShowByDefaultBarcodeAtTopInsideShipmentsUltimatepdf"), $langs->trans("ShowByDefaultBarcodeAtTopInsideShipmentsUltimatepdfDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATEPDF_GENERATE_SHIPMENTS_WITH_TOP_BARCODE');
} else {
	if ($conf->global->ULTIMATEPDF_GENERATE_SHIPMENTS_WITH_TOP_BARCODE == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATEPDF_GENERATE_SHIPMENTS_WITH_TOP_BARCODE">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATEPDF_GENERATE_SHIPMENTS_WITH_TOP_BARCODE == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATEPDF_GENERATE_SHIPMENTS_WITH_TOP_BARCODE">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// add thirdparty QRcode at top within Shipments.
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("ShowByDefaultQRcodeAtTopInsideShipmentsUltimatepdf"), $langs->trans("ShowByDefaultQRcodeAtTopInsideShipmentsUltimatepdfDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATEPDF_GENERATE_SHIPMENTS_WITH_TOP_QRCODE');
} else {
	if ($conf->global->ULTIMATEPDF_GENERATE_SHIPMENTS_WITH_TOP_QRCODE == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATEPDF_GENERATE_SHIPMENTS_WITH_TOP_QRCODE">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATEPDF_GENERATE_SHIPMENTS_WITH_TOP_QRCODE == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATEPDF_GENERATE_SHIPMENTS_WITH_TOP_QRCODE">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// add order link QRcode at top within orders.
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("ShowByDefaultOrderLinkQRcodeAtTopInsideShipmentsUltimatepdf"), $langs->trans("ShowByDefaultOrderLinkQRcodeAtTopInsideShipmentsUltimatepdfDescription")) . '</td>';
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
print '<td>' . $form->textwithpicto($langs->trans("ShowByDefaultMycompQRcodeAtTopInsideShipmentsUltimatepdf"), $langs->trans("ShowByDefaultMycompQRcodeAtTopInsideShipmentsUltimatepdfDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATEPDF_GENERATE_SHIPMENTS_WITH_MYCOMP_QRCODE');
} else {
	if ($conf->global->ULTIMATEPDF_GENERATE_SHIPMENTS_WITH_MYCOMP_QRCODE == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATEPDF_GENERATE_SHIPMENTS_WITH_MYCOMP_QRCODE">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATEPDF_GENERATE_SHIPMENTS_WITH_MYCOMP_QRCODE == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATEPDF_GENERATE_SHIPMENTS_WITH_MYCOMP_QRCODE">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// do not repeat header.
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("DoNotRepeatHeadInsideUltimatepdf"), $langs->trans("DoNotRepeatHeadInsideUltimatepdfDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATE_SHIPMENT_PDF_DONOTREPEAT_HEAD');
} else {
	if ($conf->global->ULTIMATE_SHIPMENT_PDF_DONOTREPEAT_HEAD == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATE_SHIPMENT_PDF_DONOTREPEAT_HEAD">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATE_SHIPMENT_PDF_DONOTREPEAT_HEAD == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATE_SHIPMENT_PDF_DONOTREPEAT_HEAD">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// display project reference.
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("DisplayRefProjectInsideShipmentUltimatepdf"), $langs->trans("DisplayRefProjectInsideShipmentUltimatepdfDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATE_SHIPMENTS_PDF_SHOW_PROJECT');
} else {
	if ($conf->global->ULTIMATE_SHIPMENTS_PDF_SHOW_PROJECT == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATE_SHIPMENTS_PDF_SHOW_PROJECT">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATE_SHIPMENTS_PDF_SHOW_PROJECT == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATE_SHIPMENTS_PDF_SHOW_PROJECT">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// display project title.
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("DisplayTitleProjectInsideShipmentUltimatepdf"), $langs->trans("DisplayTitleProjectInsideShipmentUltimatepdfDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATE_SHIPMENTS_PDF_SHOW_PROJECT_TITLE');
} else {
	if ($conf->global->ULTIMATE_SHIPMENTS_PDF_SHOW_PROJECT_TITLE == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATE_SHIPMENTS_PDF_SHOW_PROJECT_TITLE">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATE_SHIPMENTS_PDF_SHOW_PROJECT_TITLE == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATE_SHIPMENTS_PDF_SHOW_PROJECT_TITLE">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';
print '</table>';

print '<div align="center" class="info">';
print '<em><b>' . $langs->trans("SetCoreBloc") . '</em></b>';
print '</div>';
print '</td></tr>';

//Formulaire parametres divers
print load_fiche_titre($langs->trans("UltimatepdfSpecificShipments"), '', '') . '<br>';
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Parameters") . '</td>' . "\n";
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="100">' . $langs->trans("Value") . '</td>' . "\n";
print '</tr>';

// Hide product description
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("HideByDefaultProductDescInsideUltimatepdf"), $langs->trans("HideByDefaultProductDescInsideUltimatepdfDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATE_GENERATE_SHIPMENT_HIDE_DESC');
} else {
	if ($conf->global->ULTIMATE_GENERATE_SHIPMENT_HIDE_DESC == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATE_GENERATE_SHIPMENT_HIDE_DESC">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATE_GENERATE_SHIPMENT_HIDE_DESC == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATE_GENERATE_SHIPMENT_HIDE_DESC">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// Add line between products lines
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("ShowByDefaultDashBetweenLinesInsideUltimatepdf"), $langs->trans("ShowByDefaultDashBetweenLinesInsideUltimatepdfDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATE_SHIPMENT_PDF_DASH_BETWEEN_LINES');
} else {
	if ($conf->global->ULTIMATE_SHIPMENT_PDF_DASH_BETWEEN_LINES == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATE_SHIPMENT_PDF_DASH_BETWEEN_LINES">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATE_SHIPMENT_PDF_DASH_BETWEEN_LINES == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATE_SHIPMENT_PDF_DASH_BETWEEN_LINES">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// display sale representative signature within shipments note .
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("ShowByDefaultSaleRepSignatureInsideUltimatepdf"), $langs->trans("ShowByDefaultSaleRepSignatureInsideUltimatepdfDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('MAIN_ADD_SALE_REP_SIGNATURE_IN_SHIPMENT_NOTE');
} else {
	if ($conf->global->MAIN_ADD_SALE_REP_SIGNATURE_IN_SHIPMENT_NOTE == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_MAIN_ADD_SALE_REP_SIGNATURE_IN_SHIPMENT_NOTE">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->MAIN_ADD_SALE_REP_SIGNATURE_IN_SHIPMENT_NOTE == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_MAIN_ADD_SALE_REP_SIGNATURE_IN_SHIPMENT_NOTE">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// display column line number
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("ShowByDefaultColumnLineNumberInsideUltimatepdf"), $langs->trans("ShowByDefaultColumnLineNumberInsideUltimatepdfDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATE_SHIPMENTS_WITH_LINE_NUMBER');
} else {
	if ($conf->global->ULTIMATE_SHIPMENTS_WITH_LINE_NUMBER == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATE_SHIPMENTS_WITH_LINE_NUMBER">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATE_SHIPMENTS_WITH_LINE_NUMBER == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATE_SHIPMENTS_WITH_LINE_NUMBER">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// add photos within shipments.
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("ShowByDefaultPhotosInsideShipmentsUltimatepdf"), $langs->trans("ShowByDefaultPhotosInsideShipmentsUltimatepdfDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATE_GENERATE_SHIPMENTS_WITH_PICTURE');
} else {
	if ($conf->global->ULTIMATE_GENERATE_SHIPMENTS_WITH_PICTURE == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATE_GENERATE_SHIPMENTS_WITH_PICTURE">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATE_GENERATE_SHIPMENTS_WITH_PICTURE == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATE_GENERATE_SHIPMENTS_WITH_PICTURE">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// display weight column 
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("UltimateGenerateShipmentsWithWeightVolume"), $langs->trans("UltimateGenerateShipmentsWithWeightVolumeDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATE_GENERATE_SHIPMENTS_WITH_WEIGHT_COLUMN');
} else {
	if ($conf->global->ULTIMATE_GENERATE_SHIPMENTS_WITH_WEIGHT_COLUMN == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATE_GENERATE_SHIPMENTS_WITH_WEIGHT_COLUMN">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATE_GENERATE_SHIPMENTS_WITH_WEIGHT_COLUMN == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATE_GENERATE_SHIPMENTS_WITH_WEIGHT_COLUMN">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// display vat column 
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("UltimateGenerateShipmentsWithVatColumn"), $langs->trans("UltimateGenerateShipmentsWithVatColumnDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATE_GENERATE_SHIPMENTS_WITH_VAT_COLUMN');
} else {
	if ($conf->global->ULTIMATE_GENERATE_SHIPMENTS_WITH_VAT_COLUMN == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATE_GENERATE_SHIPMENTS_WITH_VAT_COLUMN">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATE_GENERATE_SHIPMENTS_WITH_VAT_COLUMN == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATE_GENERATE_SHIPMENTS_WITH_VAT_COLUMN">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// display subprice column 
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("UltimateGenerateShipmentsWithSubpriceColumn"), $langs->trans("UltimateGenerateShipmentsWithSubpriceColumnDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATE_GENERATE_SHIPMENTS_WITH_PRICEUHT');
} else {
	if ($conf->global->ULTIMATE_GENERATE_SHIPMENTS_WITH_PRICEUHT == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATE_GENERATE_SHIPMENTS_WITH_PRICEUHT">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATE_GENERATE_SHIPMENTS_WITH_PRICEUHT == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATE_GENERATE_SHIPMENTS_WITH_PRICEUHT">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// display discount column 
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("UltimateGenerateShipmentsWithDiscountColumn"), $langs->trans("UltimateGenerateShipmentsWithDiscountColumnDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATE_GENERATE_SHIPMENTS_WITH_DISCOUNT');
} else {
	if ($conf->global->ULTIMATE_GENERATE_SHIPMENTS_WITH_DISCOUNT == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATE_GENERATE_SHIPMENTS_WITH_DISCOUNT">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATE_GENERATE_SHIPMENTS_WITH_DISCOUNT == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATE_GENERATE_SHIPMENTS_WITH_DISCOUNT">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// display upafter column 
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("UltimateGenerateShipmentsWithUpafterColumn"), $langs->trans("UltimateGenerateShipmentsWithUpafterColumnDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATE_GENERATE_SHIPMENTS_WITH_PUAFTER');
} else {
	if ($conf->global->ULTIMATE_GENERATE_SHIPMENTS_WITH_PUAFTER == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATE_GENERATE_SHIPMENTS_WITH_PUAFTER">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATE_GENERATE_SHIPMENTS_WITH_PUAFTER == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATE_GENERATE_SHIPMENTS_WITH_PUAFTER">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// display totalexcltax column 
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("UltimateGenerateShipmentsWithTotalexcltaxColumn"), $langs->trans("UltimateGenerateShipmentsWithTotalexcltaxColumnDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATE_GENERATE_SHIPMENTS_WITH_TOTALEXCLTAX');
} else {
	if ($conf->global->ULTIMATE_GENERATE_SHIPMENTS_WITH_TOTALEXCLTAX == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATE_GENERATE_SHIPMENTS_WITH_TOTALEXCLTAX">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATE_GENERATE_SHIPMENTS_WITH_TOTALEXCLTAX == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATE_GENERATE_SHIPMENTS_WITH_TOTALEXCLTAX">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// display totalincltax column 
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("UltimateGenerateShipmentsWithTotalincltaxColumn"), $langs->trans("UltimateGenerateShipmentsWithTotalincltaxColumnDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATE_GENERATE_SHIPMENTS_WITH_TOTALINCLTAX');
} else {
	if ($conf->global->ULTIMATE_GENERATE_SHIPMENTS_WITH_TOTALINCLTAX == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATE_GENERATE_SHIPMENTS_WITH_TOTALINCLTAX">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATE_GENERATE_SHIPMENTS_WITH_TOTALINCLTAX == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATE_GENERATE_SHIPMENTS_WITH_TOTALINCLTAX">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// display column with qty_asked
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("UltimateGenerateShipmentsWithQtyAsked"), $langs->trans("UltimateGenerateShipmentsWithQtyAskedDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATE_GENERATE_SHIPMENTS_WITH_QTYASKED');
} else {
	if ($conf->global->ULTIMATE_GENERATE_SHIPMENTS_WITH_QTYASKED == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATE_GENERATE_SHIPMENTS_WITH_QTYASKED">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATE_GENERATE_SHIPMENTS_WITH_QTYASKED == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATE_GENERATE_SHIPMENTS_WITH_QTYASKED">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// display column with qty_shipped
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("UltimateGenerateShipmentsWithQtyShipped"), $langs->trans("UltimateGenerateShipmentsWithQtyShippedDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATE_GENERATE_SHIPMENTS_WITH_QTYSHIPPED');
} else {
	if ($conf->global->ULTIMATE_GENERATE_SHIPMENTS_WITH_QTYSHIPPED == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATE_GENERATE_SHIPMENTS_WITH_QTYSHIPPED">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATE_GENERATE_SHIPMENTS_WITH_QTYSHIPPED == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATE_GENERATE_SHIPMENTS_WITH_QTYSHIPPED">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// display column with reliquat
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("UltimateGenerateShipmentsWithReliquat"), $langs->trans("UltimateGenerateShipmentsWithReliquatDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATE_GENERATE_SHIPMENTS_WITH_RELIQUAT');
} else {
	if ($conf->global->ULTIMATE_GENERATE_SHIPMENTS_WITH_RELIQUAT == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATE_GENERATE_SHIPMENTS_WITH_RELIQUAT">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATE_GENERATE_SHIPMENTS_WITH_RELIQUAT == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATE_GENERATE_SHIPMENTS_WITH_RELIQUAT">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
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

// display weight zone
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("ShowByDefaultTotalBlockInsideUltimatepdf"), $langs->trans("ShowByDefaultTotalBlockInsideUltimatepdfDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATE_SHIPPING_PDF_HIDE_ORDERED');
} else {
	if ($conf->global->ULTIMATE_SHIPPING_PDF_HIDE_ORDERED == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATE_SHIPPING_PDF_HIDE_ORDERED">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATE_SHIPPING_PDF_HIDE_ORDERED == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATE_SHIPPING_PDF_HIDE_ORDERED">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// display total zone
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("ShowByDefaultTableauTotalInsideShipment"), $langs->trans("ShowByDefaultTableauTotalInsideShipmentDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATE_GENERATE_SHIPMENTS_WITH_TABLEAU_TOT');
} else {
	if ($conf->global->ULTIMATE_GENERATE_SHIPMENTS_WITH_TABLEAU_TOT == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATE_GENERATE_SHIPMENTS_WITH_TABLEAU_TOT">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATE_GENERATE_SHIPMENTS_WITH_TABLEAU_TOT == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATE_GENERATE_SHIPMENTS_WITH_TABLEAU_TOT">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// display agreement bloc
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("ShowByDefaultAgreementBlockInsideUltimatepdf"), $langs->trans("ShowByDefaultAgreementBlockInsideUltimatepdfDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATE_DISPLAY_SHIPMENTS_AGREEMENT_BLOCK');
} else {
	if ($conf->global->ULTIMATE_DISPLAY_SHIPMENTS_AGREEMENT_BLOCK == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATE_DISPLAY_SHIPMENTS_AGREEMENT_BLOCK">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATE_DISPLAY_SHIPMENTS_AGREEMENT_BLOCK == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATE_DISPLAY_SHIPMENTS_AGREEMENT_BLOCK">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';
print '</table>';

// Footer
llxFooter();
// Close database handler
$db->close();
?>
