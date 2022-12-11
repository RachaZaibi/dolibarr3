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
 *  \file       ultimatepdf/admin/invoices.php
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
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';

// Translations
$langs->loadLangs(array("admin", "bills", "ultimatepdf@ultimatepdf"));

// Security check
if (!$user->rights->ultimatepdf->config) accessforbidden();

// Parameters
$action = GETPOST('action', 'alpha');
$confirm = GETPOST('confirm', 'alpha');
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

// Send file
if (GETPOST('sendit') && !empty($conf->global->MAIN_UPLOAD_DOC)) {
	$error = 0;

	if (!$error) {
		if (is_array($_FILES['userfile']['name'])) {
			$listoffiles = $_FILES['userfile']['name'];
		} else {
			$listoffiles = array($_FILES['userfile']['name']);
		}

		foreach ($listoffiles as $key => $filename) {
			if (preg_match('/\.pdf$/i', $filename)) {
				$upload_dir = $conf->ultimatepdf->dir_output . '/' . 'invoices';
				if (dol_mkdir($upload_dir) >= 0) {
					if (is_array($_FILES['userfile']['name'])) {
						$tmp_name = $_FILES['userfile']['tmp_name'][$key];
						$fileerror = $_FILES['userfile']['error'][$key];
					} else {
						$tmp_name = $_FILES['userfile']['tmp_name'];
						$fileerror = $_FILES['userfile']['error'];
					}

					$resupload = dol_move_uploaded_file($tmp_name, $upload_dir . "/" . $filename, 0, 0, $fileerror);
					if (is_numeric($resupload) && $resupload > 0) {
						setEventMessages($langs->trans("FileTransferComplete"), null, 'mesgs');
					} else {
						$langs->load("errors");
						if ($resupload < 0)	// Unknown error
						{
							setEventMessages($langs->trans("ErrorFileNotUploaded"), null, 'mesgs');
						} else if (preg_match('/ErrorFileIsInfectedWithAVirus/', $resupload))	// Files infected by a virus
						{
							setEventMessages($langs->trans("ErrorFileIsInfectedWithAVirus"), null, 'mesgs');
						} else	// Known error
						{
							setEventMessages($langs->trans($resupload), null, 'errors');
						}
					}
				} else {
					$langs->load('errors');
					setEventMessages($langs->trans("ErrorFailToCreateDir", null, $upload_dir), 'errors');
				}
			} else {
				setEventMessages($langs->trans("ErrorFileMustBeAPdf"), null, 'errors');
			}
		}
	}
}

// Delete file
if ($action == 'confirm_deletefile' && $confirm == 'yes') {
	$file = $conf->ultimatepdf->dir_output . "/" . GETPOST('urlfile');
	$ret = dol_delete_file($file);
	if ($ret) setEventMessages($langs->trans("FileWasRemoved", GETPOST('urlfile')), null, 'mesgs');
	else setEventMessages($langs->trans("ErrorFailToDeleteFile", GETPOST('urlfile')), null, 'errors');
	header('Location: ' . $_SERVER["PHP_SELF"]);
	exit;
}

/*
 * View
 */

$wikihelp = 'EN:Module_Ultimatepdf_EN#Invoices_tab|FR:Module_Ultimatepdf_FR#Onglet_Factures';
$page_name = "UltimatepdfSetup";
llxHeader('', $langs->trans($page_name), $wikihelp);

// Subheader
$linkback = '<a href="' . ($backtopage ? $backtopage : DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1') . '">' . $langs->trans("BackToModuleList") . '</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'ultimatepdf@ultimatepdf');

clearstatcache();

// Configuration header
$head = ultimatepdf_prepare_head();
print dol_get_fiche_head($head, 'invoices', $langs->trans("ModuleSetup"), 0, "ultimatepdf@ultimatepdf");

/*
 * Confirmation suppression fichier
 */
if ($action == 'remove_file') {
	$ret = $form->form_confirm($_SERVER["PHP_SELF"] . '?&urlfile=' . urlencode(GETPOST("file")), $langs->trans('DeleteFile'), $langs->trans('ConfirmDeleteFile'), 'confirm_deletefile', '', 0, 1);
	if ($ret == 'html') print '<br>';
}

print '<div align="center" class="info">';
print '<em><b>' . $langs->trans("SetUpHeader") . '</em></b>';
print '</div>';

/*
 * Formulaire parametres divers
 */

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
	print ajax_constantonoff('ULTIMATE_PDF_INVOICE_ADDALSOTARGETDETAILS');
} else {
	if ($conf->global->ULTIMATE_PDF_INVOICE_ADDALSOTARGETDETAILS == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATE_PDF_INVOICE_ADDALSOTARGETDETAILS">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATE_PDF_INVOICE_ADDALSOTARGETDETAILS == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATE_PDF_INVOICE_ADDALSOTARGETDETAILS">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// add also details for client address.
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("ShowAlsoClientDetails"), $langs->trans("ShowAlsoClientDetailsDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATE_PDF_INVOICE_ADDALSOCLIENTDETAILS');
} else {
	if ($conf->global->ULTIMATE_PDF_INVOICE_ADDALSOCLIENTDETAILS == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATE_PDF_INVOICE_ADDALSOCLIENTDETAILS">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATE_PDF_INVOICE_ADDALSOCLIENTDETAILS == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATE_PDF_INVOICE_ADDALSOCLIENTDETAILS">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// Hide details from source within address block.
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("HideSourceDetails"), $langs->trans("HideSourceDetailsDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATE_PDF_INVOICE_DISABLESOURCEDETAILS');
} else {
	if ($conf->global->ULTIMATE_PDF_INVOICE_DISABLESOURCEDETAILS == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATE_PDF_INVOICE_DISABLESOURCEDETAILS">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATE_PDF_INVOICE_DISABLESOURCEDETAILS == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATE_PDF_INVOICE_DISABLESOURCEDETAILS">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// hide TVA intra within address.
print '<tr class="oddeven">';
print '<td>' .  $form->textwithpicto($langs->trans("HideTvaIntraWithinAddress"), $langs->trans("HideTvaIntraWithinAddressDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATE_TVAINTRA_NOT_IN_INVOICE_ADDRESS');
} else {
	if ($conf->global->ULTIMATE_TVAINTRA_NOT_IN_INVOICE_ADDRESS == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATE_TVAINTRA_NOT_IN_INVOICE_ADDRESS">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATE_TVAINTRA_NOT_IN_INVOICE_ADDRESS == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATE_TVAINTRA_NOT_IN_INVOICE_ADDRESS">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
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

// Show notes from order or proposal within invoices.
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("ShowNotesInsideInvoiceUltimatepdfFrom"), $langs->trans("ShowNotesInsideInvoiceUltimatepdfFromDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('FACTURE_REUSE_NOTES_ON_CREATE_FROM');
} else {
	if ($conf->global->FACTURE_REUSE_NOTES_ON_CREATE_FROM == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_FACTURE_REUSE_NOTES_ON_CREATE_FROM">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->FACTURE_REUSE_NOTES_ON_CREATE_FROM == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_FACTURE_REUSE_NOTES_ON_CREATE_FROM">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// Add Product barcode under product's description within invoices.
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("ShowProductsBarcodeInsideInvoiceUltimatepdf"), $langs->trans("ShowProductsBarcodeInsideInvoiceUltimatepdfDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATEPDF_GENERATE_INVOICES_WITH_PRODUCTS_BARCODE');
} else {
	if ($conf->global->ULTIMATEPDF_GENERATE_INVOICES_WITH_PRODUCTS_BARCODE == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATEPDF_GENERATE_INVOICES_WITH_PRODUCTS_BARCODE">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATEPDF_GENERATE_INVOICES_WITH_PRODUCTS_BARCODE == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATEPDF_GENERATE_INVOICES_WITH_PRODUCTS_BARCODE">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// add barcode at top within invoices.
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("ShowByDefaultBarcodeAtTopInsideInvoicesUltimatepdf"), $langs->trans("ShowByDefaultBarcodeAtTopInsideInvoicesUltimatepdfDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATEPDF_GENERATE_INVOICES_WITH_TOP_BARCODE');
} else {
	if ($conf->global->ULTIMATEPDF_GENERATE_INVOICES_WITH_TOP_BARCODE == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATEPDF_GENERATE_INVOICES_WITH_TOP_BARCODE">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATEPDF_GENERATE_INVOICES_WITH_TOP_BARCODE == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATEPDF_GENERATE_INVOICES_WITH_TOP_BARCODE">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// add QRcode at top within invoices.
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("ShowByDefaultQRcodeAtTopInsideInvoicesUltimatepdf"), $langs->trans("ShowByDefaultQRcodeAtTopInsideInvoicesUltimatepdfDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATEPDF_GENERATE_INVOICES_WITH_TOP_QRCODE');
} else {
	if ($conf->global->ULTIMATEPDF_GENERATE_INVOICES_WITH_TOP_QRCODE == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATEPDF_GENERATE_INVOICES_WITH_TOP_QRCODE">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATEPDF_GENERATE_INVOICES_WITH_TOP_QRCODE == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATEPDF_GENERATE_INVOICES_WITH_TOP_QRCODE">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// add my Comp QRcode at top within invoices.
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("ShowByDefaultMycompQRcodeAtTopInsideInvoicesUltimatepdf"), $langs->trans("ShowByDefaultMycompQRcodeAtTopInsideInvoicesUltimatepdfDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATEPDF_GENERATE_INVOICES_WITH_MYCOMP_QRCODE');
} else {
	if ($conf->global->ULTIMATEPDF_GENERATE_INVOICES_WITH_MYCOMP_QRCODE == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATEPDF_GENERATE_INVOICES_WITH_MYCOMP_QRCODE">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATEPDF_GENERATE_INVOICES_WITH_MYCOMP_QRCODE == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATEPDF_GENERATE_INVOICES_WITH_MYCOMP_QRCODE">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// add invoice QRcode to be paid within invoices.
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("ShowByDefaultInvoiceQRcodeAtBottomInsideInvoicesUltimatepdf"), $langs->trans("ShowByDefaultInvoiceQRcodeAtBottomInsideInvoicesUltimatepdfDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATEPDF_GENERATE_INVOICES_WITH_INVOICE_QRCODE');
} else {
	if ($conf->global->ULTIMATEPDF_GENERATE_INVOICES_WITH_INVOICE_QRCODE == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATEPDF_GENERATE_INVOICES_WITH_INVOICE_QRCODE">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATEPDF_GENERATE_INVOICES_WITH_INVOICE_QRCODE == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATEPDF_GENERATE_INVOICES_WITH_INVOICE_QRCODE">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// display project title.
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("DisplayProjectTitleInsideInvoicesUltimatepdf"), $langs->trans("DisplayProjectTitleInsideInvoicesUltimatepdfDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATE_INVOICES_PDF_SHOW_PROJECT');
} else {
	if ($conf->global->ULTIMATE_INVOICES_PDF_SHOW_PROJECT == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATE_INVOICES_PDF_SHOW_PROJECT">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATE_INVOICES_PDF_SHOW_PROJECT == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATE_INVOICES_PDF_SHOW_PROJECT">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// display project reference.
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("DisplayRefProjectInsideInvoicesUltimatepdf"), $langs->trans("DisplayRefProjectInsideInvoicesUltimatepdfDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATE_INVOICES_PDF_SHOW_PROJECT_REF');
} else {
	if ($conf->global->ULTIMATE_INVOICES_PDF_SHOW_PROJECT_REF == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATE_INVOICES_PDF_SHOW_PROJECT_REF">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATE_INVOICES_PDF_SHOW_PROJECT_REF == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATE_INVOICES_PDF_SHOW_PROJECT_REF">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// do not repeat header.
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("DoNotRepeatHeadInsideUltimatepdf"), $langs->trans("DoNotRepeatHeadInsideUltimatepdfDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATE_INVOICES_PDF_DONOTREPEAT_HEAD');
} else {
	if ($conf->global->ULTIMATE_INVOICES_PDF_DONOTREPEAT_HEAD == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATE_INVOICES_PDF_DONOTREPEAT_HEAD">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATE_INVOICES_PDF_DONOTREPEAT_HEAD == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATE_INVOICES_PDF_DONOTREPEAT_HEAD">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// Show all linked shipments.
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("ShowAllLinkedShipmentsInsideUltimatepdf"), $langs->trans("ShowAllLinkedShipmentsInsideUltimatepdfDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATEPDF_GENERATE_INVOICES_WITH_ALL_SHIPMENTS');
} else {
	if ($conf->global->ULTIMATEPDF_GENERATE_INVOICES_WITH_ALL_SHIPMENTS == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATEPDF_GENERATE_INVOICES_WITH_ALL_SHIPMENTS">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATEPDF_GENERATE_INVOICES_WITH_ALL_SHIPMENTS == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATEPDF_GENERATE_INVOICES_WITH_ALL_SHIPMENTS">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

print '</table>';

print '<div align="center" class="info">';
print '<em><b>' . $langs->trans("SetCoreBloc") . '</em></b>';
print '</div>';
print '</td></tr>';

print load_fiche_titre($langs->trans("UltimatepdfSpecificInvoices"), '', '') . '<br>';
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
	print ajax_constantonoff('ULTIMATE_INVOICE_PDF_DASH_BETWEEN_LINES');
} else {
	if ($conf->global->ULTIMATE_INVOICE_PDF_DASH_BETWEEN_LINES == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATE_INVOICE_PDF_DASH_BETWEEN_LINES">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATE_INVOICE_PDF_DASH_BETWEEN_LINES == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATE_INVOICE_PDF_DASH_BETWEEN_LINES">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// display fold mark.
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("ShowByDefaultFoldMarkInsideUltimatepdf"), $langs->trans("ShowByDefaultFoldMarkInsideUltimatepdfDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('MAIN_DISPLAY_INVOICES_FOLD_MARK');
} else {
	if ($conf->global->MAIN_DISPLAY_INVOICES_FOLD_MARK == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_MAIN_DISPLAY_INVOICES_FOLD_MARK">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->MAIN_DISPLAY_INVOICES_FOLD_MARK == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_MAIN_DISPLAY_INVOICES_FOLD_MARK">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// display sale representative signature within invoices note .
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("ShowByDefaultSaleRepSignatureInsideUltimatepdf"), $langs->trans("ShowByDefaultSaleRepSignatureInsideUltimatepdfDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('MAIN_ADD_SALE_REP_SIGNATURE_IN_INVOICE_NOTE');
} else {
	if ($conf->global->MAIN_ADD_SALE_REP_SIGNATURE_IN_INVOICE_NOTE == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_MAIN_ADD_SALE_REP_SIGNATURE_IN_INVOICE_NOTE">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->MAIN_ADD_SALE_REP_SIGNATURE_IN_INVOICE_NOTE == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_MAIN_ADD_SALE_REP_SIGNATURE_IN_INVOICE_NOTE">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// display column line number
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("ShowByDefaultColumnLineNumberInsideUltimatepdf"), $langs->trans("ShowByDefaultColumnLineNumberInsideUltimatepdfDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATE_INVOICES_WITH_LINE_NUMBER');
} else {
	if ($conf->global->ULTIMATE_INVOICES_WITH_LINE_NUMBER == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATE_INVOICES_WITH_LINE_NUMBER">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATE_INVOICES_WITH_LINE_NUMBER == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATE_INVOICES_WITH_LINE_NUMBER">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// add photos within invoices.
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("ShowByDefaultPhotosInsideInvoiceUltimatepdf"), $langs->trans("ShowByDefaultPhotosInsideInvoiceUltimatepdfDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATE_GENERATE_INVOICES_WITH_PICTURE');
} else {
	if ($conf->global->ULTIMATE_GENERATE_INVOICES_WITH_PICTURE == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATE_GENERATE_INVOICES_WITH_PICTURE">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATE_GENERATE_INVOICES_WITH_PICTURE == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATE_GENERATE_INVOICES_WITH_PICTURE">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// display subprice column 
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("UltimateGenerateInvoicesWithPriceUht"), $langs->trans("UltimateGenerateInvoicesWithPriceUhtDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATE_GENERATE_INVOICES_WITH_PRICEUHT');
} else {
	if ($conf->global->ULTIMATE_GENERATE_INVOICES_WITH_PRICEUHT == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATE_GENERATE_INVOICES_WITH_PRICEUHT">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATE_GENERATE_INVOICES_WITH_PRICEUHT == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATE_GENERATE_INVOICES_WITH_PRICEUHT">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// display discount column 
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("UltimateGenerateInvoicesWithDiscount"), $langs->trans("UltimateGenerateInvoicesWithDiscountDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATE_GENERATE_INVOICES_WITH_DISCOUNT');
} else {
	if ($conf->global->ULTIMATE_GENERATE_INVOICES_WITH_DISCOUNT == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATE_GENERATE_INVOICES_WITH_DISCOUNT">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATE_GENERATE_INVOICES_WITH_DISCOUNT == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATE_GENERATE_INVOICES_WITH_DISCOUNT">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// display PuAfter column 
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("UltimateGenerateInvoicesWithPuAfter"), $langs->trans("UltimateGenerateInvoicesWithPuAfterDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATE_GENERATE_INVOICES_WITH_PUAFTER');
} else {
	if ($conf->global->ULTIMATE_GENERATE_INVOICES_WITH_PUAFTER == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATE_GENERATE_INVOICES_WITH_PUAFTER">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATE_GENERATE_INVOICES_WITH_PUAFTER == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATE_GENERATE_INVOICES_WITH_PUAFTER">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// display Qty column 
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("UltimateGenerateInvoicesWithQty"), $langs->trans("UltimateGenerateInvoicesWithQtyDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATE_GENERATE_INVOICES_WITH_QTY');
} else {
	if ($conf->global->ULTIMATE_GENERATE_INVOICES_WITH_QTY == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATE_GENERATE_INVOICES_WITH_QTY">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATE_GENERATE_INVOICES_WITH_QTY == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATE_GENERATE_INVOICES_WITH_QTY">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// display weight column 
print '<tr class="oddeven">';
print '<td>' .  $form->textwithpicto($langs->trans("UltimateGenerateInvoicesWithWeightColumn"), $langs->trans("UltimateGenerateInvoicesWithWeightColumnDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATE_GENERATE_INVOICES_WITH_WEIGHT_COLUMN');
} else {
	if ($conf->global->ULTIMATE_GENERATE_INVOICES_WITH_WEIGHT_COLUMN == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATE_GENERATE_INVOICES_WITH_WEIGHT_COLUMN">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATE_GENERATE_INVOICES_WITH_WEIGHT_COLUMN == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATE_GENERATE_INVOICES_WITH_WEIGHT_COLUMN">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

print '</table>';

print '<div align="center" class="info">';
print '<em><b>' . $langs->trans("SetFooterBloc") . '</em></b>';
print '</div>';
print '</td></tr>';

print load_fiche_titre($langs->trans("UltimatepdfSpecificInvoices"), '', '') . '<br>';
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Parameters") . '</td>' . "\n";
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="100">' . $langs->trans("Value") . '</td>' . "\n";
print '</tr>';

// display total weight  
print '<tr class="oddeven">';
print '<td>' .  $form->textwithpicto($langs->trans("UltimateGenerateInvoicesWithTotalWeight"), $langs->trans("UltimateGenerateInvoicesWithTotalWeightDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATE_GENERATE_INVOICES_WITH_TOTAL_WEIGHT');
} else {
	if ($conf->global->ULTIMATE_GENERATE_INVOICES_WITH_TOTAL_WEIGHT == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATE_GENERATE_INVOICES_WITH_TOTAL_WEIGHT">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATE_GENERATE_INVOICES_WITH_TOTAL_WEIGHT == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATE_GENERATE_INVOICES_WITH_TOTAL_WEIGHT">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';


// add "Auto-liquidation régime de la sous-traitance" within invoices.
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("ShowByDefaultAutoLiquidationInsideInvoicesUltimatepdf"), $langs->trans("ShowByDefaultAutoLiquidationInsideInvoicesUltimatepdfDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATEPDF_GENERATE_INVOICES_WITH_AUTO_LIQUIDATION');
} else {
	if ($conf->global->ULTIMATEPDF_GENERATE_INVOICES_WITH_AUTO_LIQUIDATION == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATEPDF_GENERATE_INVOICES_WITH_AUTO_LIQUIDATION">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATEPDF_GENERATE_INVOICES_WITH_AUTO_LIQUIDATION == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATEPDF_GENERATE_INVOICES_WITH_AUTO_LIQUIDATION">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// add Situation invoices within invoices.
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("ActivateSituationInvoicesInsideInvoicesUltimatepdf"), $langs->trans("ActivateSituationInvoicesInsideInvoicesUltimatepdf")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('INVOICE_USE_SITUATION');
} else {
	if ($conf->global->INVOICE_USE_SITUATION == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_INVOICE_USE_SITUATION">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->INVOICE_USE_SITUATION == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_INVOICE_USE_SITUATION">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// add deposit invoice are just payment within invoices.
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("DepositInvoiceAreJustPaymentInsideInvoicesUltimatepdf"), $langs->trans("DepositInvoiceAreJustPaymentInsideInvoicesUltimatepdfDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('FACTURE_DEPOSITS_ARE_JUST_PAYMENTS');
} else {
	if ($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS  == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_FACTURE_DEPOSITS_ARE_JUST_PAYMENTS">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS  == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_FACTURE_DEPOSITS_ARE_JUST_PAYMENTS">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// Hide payment invoice within invoices.
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("HideInvoicePaymentInsideInvoicesUltimatepdf"), $langs->trans("HideInvoicePaymentInsideInvoicesUltimatepdfDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('INVOICE_NO_PAYMENT_DETAILS');
} else {
	if ($conf->global->INVOICE_NO_PAYMENT_DETAILS == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_INVOICE_NO_PAYMENT_DETAILS">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->INVOICE_NO_PAYMENT_DETAILS == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_INVOICE_NO_PAYMENT_DETAILS">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// add Responsable signature within invoices.
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("AddResponsableSignatureInsideInvoicesUltimatepdf"), $langs->trans("AddResponsableSignatureInsideInvoicesUltimatepdfDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATEPDF_ADD_RESPONSABLE_SIGNATURE_IN_INVOICE');
} else {
	if ($conf->global->ULTIMATEPDF_ADD_RESPONSABLE_SIGNATURE_IN_INVOICE == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATEPDF_ADD_RESPONSABLE_SIGNATURE_IN_INVOICE">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATEPDF_ADD_RESPONSABLE_SIGNATURE_IN_INVOICE == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATEPDF_ADD_RESPONSABLE_SIGNATURE_IN_INVOICE">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// add Display outstandingBills within invoices.
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("ShowOutstandingBillsInsideInvoicesUltimatepdf"), $langs->trans("ShowOutstandingBillsInsideInvoicesUltimatepdfDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATEPDF_GENERATE_INVOICES_WITH_OUTSTANDINGBILL');
} else {
	if ($conf->global->ULTIMATEPDF_GENERATE_INVOICES_WITH_OUTSTANDINGBILL == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATEPDF_GENERATE_INVOICES_WITH_OUTSTANDINGBILL">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATEPDF_GENERATE_INVOICES_WITH_OUTSTANDINGBILL == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATEPDF_GENERATE_INVOICES_WITH_OUTSTANDINGBILL">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// Add capabilities to convert amounts and numbers into full text within invoices.
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("AddCapabilitiesToConvertAmountsIntoFullTextInsideInvoicesUltimatepdf"), $langs->trans("AddCapabilitiesToConvertAmountsIntoFullTextInsideInvoicesUltimatepdfDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATE_NUMBER_TO_WORDS');
} else {
	if ($conf->global->ULTIMATE_NUMBER_TO_WORDS == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATE_NUMBER_TO_WORDS">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATE_NUMBER_TO_WORDS == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATE_NUMBER_TO_WORDS">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// add merge of pdf files within invoices.
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("ShowByDefaultMergePdfInsideInvoicesUltimatepdf"), $langs->trans("ShowByDefaultMergePdfInsideInvoicesUltimatepdfDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATEPDF_GENERATE_INVOICES_WITH_MERGED_PDF');
} else {
	if ($conf->global->ULTIMATEPDF_GENERATE_INVOICES_WITH_MERGED_PDF == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATEPDF_GENERATE_INVOICES_WITH_MERGED_PDF">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATEPDF_GENERATE_INVOICES_WITH_MERGED_PDF == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATEPDF_GENERATE_INVOICES_WITH_MERGED_PDF">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

/*// add CGV within invoices.
print '<tr class="oddeven">';
print '<td>'.$langs->trans("ShowByDefaultMergeCGVInsideInvoicesUltimatepdf").'</td>';
print '<td align="center" width="20">&nbsp;</td>';

$formfile=new FormFile($db);
$formfile->form_attach_new_file($_SERVER['PHP_SELF'], '', 0, 0, 1, 50, '', '', false, '', 0);


	// List of document
	$outputdir=$conf->ultimatepdf->dir_output.'/'.'invoices';
	$listoffiles=dol_dir_list($outputdir,'files',0,'',array('^SPECIMEN\.pdf$'));

	if (count($listoffiles))
	{
	    print $formfile->showdocuments('ultimatepdf','invoices',$outputdir,$_SERVER["PHP_SELF"].'?module='.'invoices',0,$user->admin,'',0,0,0,0,0,'',$langs->trans("PathDirectory").' '.$outputdir);
	}
	else
	{
		print '<div class="titre">'.$langs->trans("PathDirectory").' '.$outputdir.' :</div>';
		print $langs->trans("NoPDFFileFound").'<br>';
	}
print '</td></tr>';
*/
print '</table>';

// Footer
llxFooter();
// Close database handler
$db->close();
?>
