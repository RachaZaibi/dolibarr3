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
 *  \file       htdocs/custom/ultimatepdf/admin/supplierproposal.php
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
$langs->loadLangs(array("admin", "orders", "ultimatepdf@ultimatepdf"));

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

$wikihelp = 'EN:Module_Ultimatepdf_EN#Supplier_proposal_tab|FR:Module_Ultimatepdf_FR#Onglet_Demande_de_Prix';
$page_name = "UltimatepdfSetup";
llxHeader('', $langs->trans($page_name), $wikihelp);

// Subheader
$linkback = '<a href="' . ($backtopage ? $backtopage : DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1') . '">' . $langs->trans("BackToModuleList") . '</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'ultimatepdf@ultimatepdf');

// Configuration header
$head = ultimatepdf_prepare_head();
print dol_get_fiche_head($head, 'supplierproposal', $langs->trans("ModuleSetup"), 0, "ultimatepdf@ultimatepdf");

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
	print ajax_constantonoff('ULTIMATE_PDF_SUPPLIERPROPOSAL_ADDALSOTARGETDETAILS');
} else {
	if ($conf->global->ULTIMATE_PDF_SUPPLIERPROPOSAL_ADDALSOTARGETDETAILS == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATE_PDF_SUPPLIERPROPOSAL_ADDALSOTARGETDETAILS">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATE_PDF_SUPPLIERPROPOSAL_ADDALSOTARGETDETAILS == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATE_PDF_SUPPLIERPROPOSAL_ADDALSOTARGETDETAILS">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// add also details for client address.
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("ShowAlsoClientDetails"), $langs->trans("ShowAlsoClientDetailsDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATE_PDF_SUPPLIERPROPOSAL_ADDALSOCLIENTDETAILS');
} else {
	if ($conf->global->ULTIMATE_PDF_SUPPLIERPROPOSAL_ADDALSOCLIENTDETAILS == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATE_PDF_SUPPLIERPROPOSAL_ADDALSOCLIENTDETAILS">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATE_PDF_SUPPLIERPROPOSAL_ADDALSOCLIENTDETAILS == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATE_PDF_SUPPLIERPROPOSAL_ADDALSOCLIENTDETAILS">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// Hide details from source within address block.
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("HideSourceDetails"), $langs->trans("HideSourceDetailsDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATE_PDF_SUPPLIERPROPOSAL_DISABLESOURCEDETAILS');
} else {
	if ($conf->global->ULTIMATE_PDF_SUPPLIERPROPOSAL_DISABLESOURCEDETAILS == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATE_PDF_SUPPLIERPROPOSAL_DISABLESOURCEDETAILS">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATE_PDF_SUPPLIERPROPOSAL_DISABLESOURCEDETAILS == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATE_PDF_SUPPLIERPROPOSAL_DISABLESOURCEDETAILS">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// hide TVA intra within address.
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("HideTvaIntraWithinAddress"), $langs->trans("HideTvaIntraWithinAddressDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATE_TVAINTRA_NOT_IN_SUPPLIERPROPOSAL_ADDRESS');
} else {
	if ($conf->global->ULTIMATE_TVAINTRA_NOT_IN_SUPPLIERPROPOSAL_ADDRESS == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATE_TVAINTRA_NOT_IN_SUPPLIERPROPOSAL_ADDRESS">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATE_TVAINTRA_NOT_IN_SUPPLIERPROPOSAL_ADDRESS == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATE_TVAINTRA_NOT_IN_SUPPLIERPROPOSAL_ADDRESS">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

/*
 * Formulaire parametres divers
 */

print load_fiche_titre($langs->trans("UltimatepdfSpecificSupplierProposals"), '', '') . '<br>';
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Parameters") . '</td>' . "\n";
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="100">' . $langs->trans("Value") . '</td>' . "\n";
print '</tr>';

// Hide verbose description within Supplier Proposal.
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("UltimatepdfHideProductDescWithinSupplierProposal"), $langs->trans("UltimatepdfHideProductDescWithinSupplierProposalDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATE_GENERATE_SUPPLIERPROPOSAL_HIDE_PRODUCT_DESC');
} else {
	if ($conf->global->ULTIMATE_GENERATE_SUPPLIERPROPOSAL_HIDE_PRODUCT_DESC == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATE_GENERATE_SUPPLIERPROPOSAL_HIDE_PRODUCT_DESC">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATE_GENERATE_SUPPLIERPROPOSAL_HIDE_PRODUCT_DESC == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATE_GENERATE_SUPPLIERPROPOSAL_HIDE_PRODUCT_DESC">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// display sale representative signature within Supplier Proposals note .
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("ShowByDefaultSaleRepSignatureInsideUltimatepdf"), $langs->trans("ShowByDefaultSaleRepSignatureInsideUltimatepdfDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('MAIN_ADD_SALE_REP_SIGNATURE_IN_SUPPLIER_PROPOSAL_NOTE');
} else {
	if ($conf->global->MAIN_ADD_SALE_REP_SIGNATURE_IN_SUPPLIER_PROPOSAL_NOTE == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_MAIN_ADD_SALE_REP_SIGNATURE_IN_SUPPLIER_PROPOSAL_NOTE">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->MAIN_ADD_SALE_REP_SIGNATURE_IN_SUPPLIER_PROPOSAL_NOTE == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_MAIN_ADD_SALE_REP_SIGNATURE_IN_SUPPLIER_PROPOSAL_NOTE">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
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

// Add Product barcode under product's description within supplier proposal.
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("ShowProductsBarcodeInsideSupplierProposalUltimatepdf"), $langs->trans("ShowProductsBarcodeInsideSupplierProposalUltimatepdfDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATEPDF_GENERATE_SUPPLIERPROPOSAL_WITH_PRODUCTS_BARCODE');
} else {
	if ($conf->global->ULTIMATEPDF_GENERATE_SUPPLIERPROPOSAL_WITH_PRODUCTS_BARCODE == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATEPDF_GENERATE_SUPPLIERPROPOSAL_WITH_PRODUCTS_BARCODE">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATEPDF_GENERATE_SUPPLIERPROPOSAL_WITH_PRODUCTS_BARCODE == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATEPDF_GENERATE_SUPPLIERPROPOSAL_WITH_PRODUCTS_BARCODE">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// do not repeat header.
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("DoNotRepeatHeadInsideUltimatepdf"), $langs->trans("DoNotRepeatHeadInsideUltimatepdfDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATE_GENERATE_SUPPLIER_PROPOSAL_DONOTREPEAT_HEAD');
} else {
	if ($conf->global->ULTIMATE_GENERATE_SUPPLIER_PROPOSAL_DONOTREPEAT_HEAD == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATE_GENERATE_SUPPLIER_PROPOSAL_DONOTREPEAT_HEAD">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATE_GENERATE_SUPPLIER_PROPOSAL_DONOTREPEAT_HEAD == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATE_GENERATE_SUPPLIER_PROPOSAL_DONOTREPEAT_HEAD">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';
print '</table>';

print '<div align="center" class="info">';
print '<em><b>' . $langs->trans("SetCoreBloc") . '</em></b>';
print '</div>';
print '</td></tr>';

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
	print ajax_constantonoff('ULTIMATE_SUPPLIERPROPOSAL_PDF_DASH_BETWEEN_LINES');
} else {
	if ($conf->global->ULTIMATE_SUPPLIERPROPOSAL_PDF_DASH_BETWEEN_LINES == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATE_SUPPLIERPROPOSAL_PDF_DASH_BETWEEN_LINES">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATE_SUPPLIERPROPOSAL_PDF_DASH_BETWEEN_LINES == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATE_SUPPLIERPROPOSAL_PDF_DASH_BETWEEN_LINES">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// Hide Payment Term Condition
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("HidePaymentTermCondInsideUltimatepdf"), $langs->trans("HidePaymentTermCondInsideUltimatepdfDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('SUPPLIER_PROPOSAL_PDF_HIDE_PAYMENTTERMCOND');
} else {
	if ($conf->global->SUPPLIER_PROPOSAL_PDF_HIDE_PAYMENTTERMCOND == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_SUPPLIER_PROPOSAL_PDF_HIDE_PAYMENTTERMCOND">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->SUPPLIER_PROPOSAL_PDF_HIDE_PAYMENTTERMCOND == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_SUPPLIER_PROPOSAL_PDF_HIDE_PAYMENTTERMCOND">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// display column line number
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("ShowByDefaultColumnLineNumberInsideUltimatepdf"), $langs->trans("ShowByDefaultColumnLineNumberInsideUltimatepdfDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATE_SUPPLIERPROPOSAL_WITH_LINE_NUMBER');
} else {
	if ($conf->global->ULTIMATE_SUPPLIERPROPOSAL_WITH_LINE_NUMBER == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATE_SUPPLIERPROPOSAL_WITH_LINE_NUMBER">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATE_SUPPLIERPROPOSAL_WITH_LINE_NUMBER == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATE_SUPPLIERPROPOSAL_WITH_LINE_NUMBER">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// add photos within Supplier Proposal.
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("ShowByDefaultPhotosInsideSupplierProposalUltimatepdf"), $langs->trans("ShowByDefaultPhotosInsideSupplierProposalUltimatepdfDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATE_GENERATE_SUPPLIER_PROPOSAL_WITH_PICTURE');
} else {
	if ($conf->global->ULTIMATE_GENERATE_SUPPLIER_PROPOSAL_WITH_PICTURE == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATE_GENERATE_SUPPLIER_PROPOSAL_WITH_PICTURE">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATE_GENERATE_SUPPLIER_PROPOSAL_WITH_PICTURE == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATE_GENERATE_SUPPLIER_PROPOSAL_WITH_PICTURE">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// display subprice column 
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("UltimateGenerateSupplierProposalWithPriceUht"), $langs->trans("UltimateGenerateSupplierProposalWithPriceUhtDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATE_GENERATE_SUPPLIER_PROPOSAL_WITH_PRICEUHT');
} else {
	if ($conf->global->ULTIMATE_GENERATE_SUPPLIER_PROPOSAL_WITH_PRICEUHT == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATE_GENERATE_SUPPLIER_PROPOSAL_WITH_PRICEUHT">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATE_GENERATE_SUPPLIER_PROPOSAL_WITH_PRICEUHT == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATE_GENERATE_SUPPLIER_PROPOSAL_WITH_PRICEUHT">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// display discount column 
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("UltimateGenerateSupplierProposalWithDiscount"), $langs->trans("UltimateGenerateSupplierProposalWithDiscountDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATE_GENERATE_SUPPLIER_PROPOSAL_WITH_DISCOUNT');
} else {
	if ($conf->global->ULTIMATE_GENERATE_SUPPLIER_PROPOSAL_WITH_DISCOUNT == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATE_GENERATE_SUPPLIER_PROPOSAL_WITH_DISCOUNT">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATE_GENERATE_SUPPLIER_PROPOSAL_WITH_DISCOUNT == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATE_GENERATE_SUPPLIER_PROPOSAL_WITH_DISCOUNT">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// display PuAfter column 
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("UltimateGenerateSupplierProposalWithPuAfter"), $langs->trans("UltimateGenerateSupplierProposalWithPuAfterDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATE_GENERATE_SUPPLIER_PROPOSAL_WITH_PUAFTER');
} else {
	if ($conf->global->ULTIMATE_GENERATE_SUPPLIER_PROPOSAL_WITH_PUAFTER == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATE_GENERATE_SUPPLIER_PROPOSAL_WITH_PUAFTER">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATE_GENERATE_SUPPLIER_PROPOSAL_WITH_PUAFTER == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATE_GENERATE_SUPPLIER_PROPOSAL_WITH_PUAFTER">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// display Qty column 
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("UltimateGenerateSupplierProposalWithQty"), $langs->trans("UltimateGenerateSupplierProposalWithQtyDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATE_GENERATE_SUPPLIER_PROPOSAL_WITH_QTY');
} else {
	if ($conf->global->ULTIMATE_GENERATE_SUPPLIER_PROPOSAL_WITH_QTY == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATE_GENERATE_SUPPLIER_PROPOSAL_WITH_QTY">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATE_GENERATE_SUPPLIER_PROPOSAL_WITH_QTY == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATE_GENERATE_SUPPLIER_PROPOSAL_WITH_QTY">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// display weight column 
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("UltimateGenerateSupplierProposalWithWeightColumn"), $langs->trans("UltimateGenerateSupplierProposalWithWeightColumnfDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATE_GENERATE_SUPPLIER_PROPOSAL_WITH_WEIGHT_COLUMN');
} else {
	if ($conf->global->ULTIMATE_GENERATE_SUPPLIER_PROPOSAL_WITH_WEIGHT_COLUMN == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATE_GENERATE_SUPPLIER_PROPOSAL_WITH_WEIGHT_COLUMN">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATE_GENERATE_SUPPLIER_PROPOSAL_WITH_WEIGHT_COLUMN == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATE_GENERATE_SUPPLIER_PROPOSAL_WITH_WEIGHT_COLUMN">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// display weight column 
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("UltimateGenerateSupplierProposalWithPriceColumn"), $langs->trans("UltimateGenerateSupplierProposalWithPriceColumnDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATE_GENERATE_SUPPLIER_PROPOSAL_WITH_PRICE_COLUMN');
} else {
	if ($conf->global->ULTIMATE_GENERATE_SUPPLIER_PROPOSAL_WITH_PRICE_COLUMN == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATE_GENERATE_SUPPLIER_PROPOSAL_WITH_PRICE_COLUMN">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATE_GENERATE_SUPPLIER_PROPOSAL_WITH_PRICE_COLUMN == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATE_GENERATE_SUPPLIER_PROPOSAL_WITH_PRICE_COLUMN">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
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

// add merge of pdf files within Supplier proposal.
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("ShowByDefaultMergePdfInsideSupplierProposalUltimatepdf"), $langs->trans("ShowByDefaultMergePdfInsideSupplierProposalUltimatepdfDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATEPDF_GENERATE_SUPPLIERPROPOSAL_WITH_MERGED_PDF');
} else {
	if ($conf->global->ULTIMATEPDF_GENERATE_SUPPLIERPROPOSAL_WITH_MERGED_PDF == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATEPDF_GENERATE_SUPPLIERPROPOSAL_WITH_MERGED_PDF">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATEPDF_GENERATE_SUPPLIERPROPOSAL_WITH_MERGED_PDF == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATEPDF_GENERATE_SUPPLIERPROPOSAL_WITH_MERGED_PDF">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';
print '</table>';

// Footer
llxFooter();
// Close database handler
$db->close();
?>
