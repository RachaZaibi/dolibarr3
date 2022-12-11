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

global $db, $langs, $user, $conf, $mysoc;

// Libraries
require_once(DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php');
require_once(DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php');
require_once(DOL_DOCUMENT_ROOT . '/core/class/html.formbarcode.class.php');
require_once(DOL_DOCUMENT_ROOT . '/core/class/html.formadmin.class.php');
require_once(DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php');
require_once('../lib/ultimatepdf.lib.php');

// Translations
$langs->loadLangs(array("admin", "ultimatepdf@ultimatepdf"));

// Access control
if (!$user->rights->ultimatepdf->config) accessforbidden();

// Parameters
$action = GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');
$confirm = GETPOST('confirm', 'alpha');

$modules = array();
if ($conf->propal->enabled) $modules['proposals'] = 'Proposals';
if ($conf->commande->enabled) $modules['orders'] = 'Orders';
if ($conf->facture->enabled) $modules['invoices'] = 'Invoices';
if ($conf->fournisseur->enabled) $modules['supplier_orders'] = 'SuppliersOrders';
if ($conf->contract->enabled) $modules['contracts']='Contracts';


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

if ($action == 'GENBARCODE_BARCODETYPE_THIRDPARTY') {
	$coder_id = GETPOST('coder_id', 'alpha');
	$res = dolibarr_set_const($db, "GENBARCODE_BARCODETYPE_THIRDPARTY", $coder_id, 'chaine', 0, '', $conf->entity);
}

if ($action == 'update') {
	dolibarr_set_const($db, "MAIN_PDF_FORMAT", $_POST["MAIN_PDF_FORMAT"], 'chaine', 0, '', $conf->entity);

	dolibarr_set_const($db, "MAIN_PROFID1_IN_SOURCE_ADDRESS", $_POST["MAIN_PROFID1_IN_SOURCE_ADDRESS"], 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_PROFID2_IN_ADDRESS",    $_POST["MAIN_PROFID2_IN_ADDRESS"], 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_PROFID3_IN_ADDRESS",    $_POST["MAIN_PROFID3_IN_ADDRESS"], 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_PROFID4_IN_ADDRESS",    $_POST["MAIN_PROFID4_IN_ADDRESS"], 'chaine', 0, '', $conf->entity);

	header("Location: " . $_SERVER["PHP_SELF"] . "?mainmenu=home&leftmenu=setup");
	exit;
}

// Send file
if (GETPOST('sendit') && !empty($conf->global->MAIN_UPLOAD_DOC)) {
	$error = 0;
	if (!GETPOST('module', 'alpha') || is_numeric(GETPOST('module', 'alpha'))) {
		$error++;
		setEventMessage($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Type")), 'warnings');
	}

	if (!$error) {
		if (is_array($_FILES['userfile']['name'])) {
			$listoffiles = $_FILES['userfile']['name'];
		} else {
			$listoffiles = array($_FILES['userfile']['name']);
		}

		foreach ($listoffiles as $key => $filename) {
			if (preg_match('/\.pdf$/i', $filename)) {
				$upload_dir = $conf->ultimatepdf->dir_output . '/' . GETPOST('module', 'alpha');
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
							setEventMessages($langs->trans("ErrorFileNotUploaded"), null, 'errors');
						} else if (preg_match('/ErrorFileIsInfectedWithAVirus/', $resupload))	// Files infected by a virus
						{
							setEventMessages($langs->trans("ErrorFileIsInfectedWithAVirus"), null, 'errors');
						} else	// Known error
						{
							setEventMessages($langs->trans($resupload), null, 'errors');
						}
					}
				} else {
					// Echec transfert (fichier depassant la limite ?)
					$langs->load('errors');
					setEventMessages($langs->trans("ErrorFailToCreateDir", $upload_dir), null, 'errors');
				}
			} else {
				setEventMessages($langs->trans("ErrorFileMustBeAPdf"), null, 'errors');
			}
		}
	}
}

// Delete file
if ($action == 'confirm_deletefile' && $confirm == 'yes') {
	$file = $conf->ultimatepdf->dir_output . "/" . GETPOST('urlfile');	// Do not use urldecode here ($_GET and $_REQUEST are already decoded by PHP).

	$ret = dol_delete_file($file);
	if ($ret) setEventMessage($langs->trans("FileWasRemoved", GETPOST('urlfile')));
	else setEventMessage($langs->trans("ErrorFailToDeleteFile", GETPOST('urlfile')), 'errors');
	header('Location: ' . $_SERVER["PHP_SELF"]);
	exit;
}

/*
 * View
 */

$wikihelp = 'EN:Module_Ultimatepdf_EN#Options_tab|FR:Module_Ultimatepdf_FR#Onglet_Options';
$page_name = "UltimatepdfSetup";
llxHeader('', $langs->trans($page_name), $wikihelp);

$formbarcode = new FormBarCode($db);
$formadmin = new FormAdmin($db);
$form = new Form($db);
$formfile = new FormFile($db);

// Subheader
$linkback = '<a href="' . ($backtopage ? $backtopage : DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1') . '">' . $langs->trans("BackToModuleList") . '</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'ultimatepdf@ultimatepdf');

// Configuration header
$head = ultimatepdf_prepare_head();
print dol_get_fiche_head($head, 'options', $langs->trans("ModuleSetup"), 0, "ultimatepdf@ultimatepdf");

print $langs->trans("PDFDesc") . "<br>\n";
print "<br>\n";

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

// Hide the other logo.
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("HideOtherLogo"), $langs->trans("HideOtherLogoDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('PDF_DISABLE_ULTIMATE_OTHERLOGO_FILE');
} else {
	if ($conf->global->PDF_DISABLE_ULTIMATE_OTHERLOGO_FILE == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_PDF_DISABLE_ULTIMATE_OTHERLOGO_FILE">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->PDF_DISABLE_ULTIMATE_OTHERLOGO_FILE == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_PDF_DISABLE_ULTIMATE_OTHERLOGO_FILE">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}

// Hide the company logo.
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("HideMyCompanyLogo"), $langs->trans("HideMyCompanyLogoDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('PDF_DISABLE_MYCOMPANY_LOGO');
} else {
	if ($conf->global->PDF_DISABLE_MYCOMPANY_LOGO == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_PDF_DISABLE_MYCOMPANY_LOGO">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->PDF_DISABLE_MYCOMPANY_LOGO == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_PDF_DISABLE_MYCOMPANY_LOGO">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}

// Can use contact company name In recipient Address.
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("UseCompanyNameOfContactInrecipientAddress"), $langs->trans("UseCompanyNameOfContactInrecipientAddressDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('MAIN_USE_COMPANY_NAME_OF_CONTACT');
} else {
	if ($conf->global->MAIN_USE_COMPANY_NAME_OF_CONTACT == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_MAIN_USE_COMPANY_NAME_OF_CONTACT">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->MAIN_USE_COMPANY_NAME_OF_CONTACT == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_MAIN_USE_COMPANY_NAME_OF_CONTACT">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}

// Display Public Note In Source Address.
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("ShowAlsoSocieteNoteInSourceAddress"), $langs->trans("ShowAlsoSocieteNoteInSourceAddressDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('MAIN_INFO_SOCIETE_NOTE');
} else {
	if ($conf->global->MAIN_INFO_SOCIETE_NOTE == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_MAIN_INFO_SOCIETE_NOTE">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->MAIN_INFO_SOCIETE_NOTE == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_MAIN_INFO_SOCIETE_NOTE">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}

// Display Public Note In Source Address.
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("ShowAlsoPublicNoteInSourceAddress"), $langs->trans("ShowAlsoPublicNoteInSourceAddressDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('MAIN_PUBLIC_NOTE_IN_ADDRESS');
} else {
	if ($conf->global->MAIN_PUBLIC_NOTE_IN_ADDRESS == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_MAIN_PUBLIC_NOTE_IN_ADDRESS">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->MAIN_PUBLIC_NOTE_IN_ADDRESS == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_MAIN_PUBLIC_NOTE_IN_ADDRESS">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}

// Display contact address blocks one above the other.
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("ShowContactAddressBlocksOneAboveTheOther"), $langs->trans("ShowContactAddressBlocksOneAboveTheOtherDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATE_CONTACT_ADDRESS_BLOCKS_OVER');
} else {
	if ($conf->global->ULTIMATE_CONTACT_ADDRESS_BLOCKS_OVER == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATE_CONTACT_ADDRESS_BLOCKS_OVER">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATE_CONTACT_ADDRESS_BLOCKS_OVER == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATE_CONTACT_ADDRESS_BLOCKS_OVER">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}


$noCountryCode = (empty($mysoc->country_code) ? true : false);

// Show prof id 1 in address into pdf
if (!$noCountryCode) {
	$pid1 = $langs->transcountry("ProfId1", $mysoc->country_code);
	if ($pid1 == '-') $pid1 = false;
} else {
	$pid1 = img_warning() . ' <font class="error">' . $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("CompanyCountry")) . '</font>';
}
if ($pid1) {
	print '<tr class="oddeven">';
	print '<td>' . $langs->trans("ShowProfIdInAddress") . ' - ' . $pid1 . '</td>';
	print '<td align="center" width="20">&nbsp;</td>';
}

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('MAIN_PROFID1_IN_SOURCE_ADDRESS');
} else {
	if ($conf->global->MAIN_PROFID1_IN_SOURCE_ADDRESS == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_MAIN_PROFID1_IN_SOURCE_ADDRESS">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->MAIN_PROFID1_IN_SOURCE_ADDRESS == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_MAIN_PROFID1_IN_SOURCE_ADDRESS">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// Show prof id 2 in address into pdf
if (!$noCountryCode) {
	$pid2 = $langs->transcountry("ProfId2", $mysoc->country_code);
	if ($pid2 == '-') $pid2 = false;
} else {
	$pid2 = img_warning() . ' <font class="error">' . $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("CompanyCountry")) . '</font>';
}
if ($pid2) {
	print '<tr class="oddeven">';
	print '<td>' . $langs->trans("ShowProfIdInAddress") . ' - ' . $pid2 . '</td>';
	print '<td align="center" width="20">&nbsp;</td>';
}

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('MAIN_PROFID2_IN_ADDRESS');
} else {
	if ($conf->global->MAIN_PROFID2_IN_ADDRESS == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_MAIN_PROFID2_IN_ADDRESS">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->MAIN_PROFID2_IN_ADDRESS == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_MAIN_PROFID2_IN_ADDRESS">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// Show prof id 3 in address into pdf
if (!$noCountryCode) {
	$pid3 = $langs->transcountry("ProfId3", $mysoc->country_code);
	if ($pid3 == '-') $pid3 = false;
} else {
	$pid3 = img_warning() . ' <font class="error">' . $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("CompanyCountry")) . '</font>';
}
if ($pid3) {
	print '<tr class="oddeven">';
	print '<td>' . $langs->trans("ShowProfIdInAddress") . ' - ' . $pid3 . '</td>';
	print '<td align="center" width="20">&nbsp;</td>';
}

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('MAIN_PROFID3_IN_ADDRESS');
} else {
	if ($conf->global->MAIN_PROFID3_IN_ADDRESS == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_MAIN_PROFID3_IN_ADDRESS">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->MAIN_PROFID3_IN_ADDRESS == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_MAIN_PROFID3_IN_ADDRESS">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// Show prof id 4 in address into pdf
if (!$noCountryCode) {
	$pid4 = $langs->transcountry("ProfId4", $mysoc->country_code);
	if ($pid4 == '-') $pid4 = false;
} else {
	$pid4 = img_warning() . ' <font class="error">' . $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("CompanyCountry")) . '</font>';
}
if ($pid4) {
	print '<tr class="oddeven">';
	print '<td>' . $langs->trans("ShowProfIdInAddress") . ' - ' . $pid4 . '</td>';
	print '<td align="center" width="20">&nbsp;</td>';
}

if ($langs->transcountry("ProfId4", $mysoc->country_code) != '-') {
	print '<td align="center" width="100">';
	if ($conf->use_javascript_ajax) {
		print ajax_constantonoff('MAIN_PROFID4_IN_ADDRESS');
	} else {
		if ($conf->global->MAIN_PROFID4_IN_ADDRESS == 0) {
			print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_MAIN_PROFID4_IN_ADDRESS">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
		} else if ($conf->global->MAIN_PROFID4_IN_ADDRESS == 1) {
			print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_MAIN_PROFID4_IN_ADDRESS">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
		}
	}
}

print '</td></tr>';

// Show intra vat in address into pdf
if (!$noCountryCode) {
	$tva_intra = $langs->transcountry("VATIntra", $mysoc->country_code);
	if ($tva_intra == '-') $tva_intra = false;
} else {
	$tva_intra = img_warning() . ' <font class="error">' . $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("CompanyCountry")) . '</font>';
}
if ($tva_intra) {
	print '<tr class="oddeven">';
	print '<td>' . $langs->trans("ShowIntraVatInAddress") . ' - ' . $tva_intra . '</td>';
	print '<td align="center" width="20">&nbsp;</td>';
}

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('MAIN_INFO_TVAINTRA');
} else {
	if ($conf->global->MAIN_INFO_TVAINTRA == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_MAIN_INFO_TVAINTRA">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->MAIN_INFO_TVAINTRA == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_MAIN_INFO_TVAINTRA">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';
print '</table>';

print '<div align="center" class="info">';
print '<em><b>' . $langs->trans("SetCoreBloc") . '</em></b>';
print '</div>';
print '</td></tr>';

load_fiche_titre($langs->trans("PDFColumnForging"), '', '') . '<br>';
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Parameters") . '</td>' . "\n";
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="100">' . $langs->trans("Value") . '</td>' . "\n";
print '</tr>';

/*
 * Formulaire parametres fabrication des colonnes
 */

// Display background image on pdf
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("UltimatepdfUseBackgroundImageOnPdf"), $langs->trans("UltimatepdfUseBackgroundImageOnPdfDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('MAIN_USE_BACKGROUND_ON_PDF');
} else {
	if ($conf->global->MAIN_USE_BACKGROUND_ON_PDF == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_MAIN_USE_BACKGROUND_ON_PDF">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->MAIN_USE_BACKGROUND_ON_PDF == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_MAIN_USE_BACKGROUND_ON_PDF">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// Display backgroundpdf on pdf
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("UltimatepdfUseBackgroundPdfOnPdf"), $langs->trans("UltimatepdfUseBackgroundPdfOnPdfDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATEPDF_ADD_PDF_BACKGROUND');
} else {
	if ($conf->global->ULTIMATEPDF_ADD_PDF_BACKGROUND == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATEPDF_ADD_PDF_BACKGROUND">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATEPDF_ADD_PDF_BACKGROUND == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATEPDF_ADD_PDF_BACKGROUND">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// Hide product description
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("HideByDefaultProductDescInsideUltimatepdf"), $langs->trans("HideByDefaultProductDescInsideUltimatepdfDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('MAIN_GENERATE_DOCUMENTS_HIDE_DESC');
} else {
	if ($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DESC == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_MAIN_GENERATE_DOCUMENTS_HIDE_DESC">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DESC == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_MAIN_GENERATE_DOCUMENTS_HIDE_DESC">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// Hide product reference
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("HideByDefaultProductRefInsideUltimatepdf"), $langs->trans("HideByDefaultProductRefInsideUltimatepdfDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('MAIN_GENERATE_DOCUMENTS_HIDE_REF');
} else {
	if ($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_REF == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_MAIN_GENERATE_DOCUMENTS_HIDE_REF">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_REF == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_MAIN_GENERATE_DOCUMENTS_HIDE_REF">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// Hide product details
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("HideBydefaultProductDetailsInsideUltimatepdf"), $langs->trans("HideBydefaultProductDetailsInsideUltimatepdfDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS');
} else {
	if ($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// Show line total with TTC
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("ShowBydefaultLineWithTotalTTCInsideUltimatepdf"), $langs->trans("ShowBydefaultLineWithTotalTTCInsideUltimatepdfDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATE_SHOW_LINE_TTTC');
} else {
	if ($conf->global->ULTIMATE_SHOW_LINE_TTTC == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATE_SHOW_LINE_TTTC">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATE_SHOW_LINE_TTTC == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATE_SHOW_LINE_TTTC">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// Hide product VAT column
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("HideBydefaultProductVATColumnInsideUltimatepdf"), $langs->trans("HideBydefaultProductVATColumnInsideUltimatepdfDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATE_SHOW_HIDE_VAT_COLUMN');
} else {
	if ($conf->global->ULTIMATE_SHOW_HIDE_VAT_COLUMN == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATE_SHOW_HIDE_VAT_COLUMN">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATE_SHOW_HIDE_VAT_COLUMN == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATE_SHOW_HIDE_VAT_COLUMN">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// Hide product PUHT
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("HideBydefaultProductPUHTInsideUltimatepdf"), $langs->trans("HideBydefaultProductPUHTInsideUltimatepdfDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATE_SHOW_HIDE_PUHT');
} else {
	if ($conf->global->ULTIMATE_SHOW_HIDE_PUHT == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATE_SHOW_HIDE_PUHT">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATE_SHOW_HIDE_PUHT == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATE_SHOW_HIDE_PUHT">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// Hide product QTY
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("HideBydefaultProductQtyInsideUltimatepdf"), $langs->trans("HideBydefaultProductQtyInsideUltimatepdfDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATE_SHOW_HIDE_QTY');
} else {
	if ($conf->global->ULTIMATE_SHOW_HIDE_QTY == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATE_SHOW_HIDE_QTY">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATE_SHOW_HIDE_QTY == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATE_SHOW_HIDE_QTY">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// Hide product Total HT
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("HideBydefaultProductTHTInsideUltimatepdf"), $langs->trans("HideBydefaultProductTHTInsideUltimatepdfDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATE_SHOW_HIDE_THT');
} else {
	if ($conf->global->ULTIMATE_SHOW_HIDE_THT == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATE_SHOW_HIDE_THT">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATE_SHOW_HIDE_THT == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATE_SHOW_HIDE_THT">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// Activate Unit column
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("ActivateProductUnitcolumn"), $langs->trans("ActivateProductUnitcolumnDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('PRODUCT_USE_UNITS');
} else {
	if ($conf->global->PRODUCT_USE_UNITS == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_PRODUCT_USE_UNITS">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->PRODUCT_USE_UNITS == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_PRODUCT_USE_UNITS">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';
print '</table>';

print '<div align="center" class="info">';
print '<em><b>' . $langs->trans("SetFooterBloc") . '</em></b>';
print '</div>';
print '</td></tr>';

/*
 * Formulaire parametres divers
 */

load_fiche_titre($langs->trans("UltimatepdfMiscellaneous"), '', '') . '<br>';
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Parameters") . '</td>' . "\n";
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="100">' . $langs->trans("Value") . '</td>' . "\n";
print '</tr>';

//Add CGV to some documents
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("AddCgvToDocumentsInsideUltimatepdf"), $langs->trans("AddCgvToDocumentsInsideUltimatepdfDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

//Remove file
if ($action == 'remove_file') {
	print $form->formconfirm($_SERVER["PHP_SELF"] . '?&urlfile=' . urlencode(GETPOST("file")), $langs->trans('DeleteFile'), $langs->trans('ConfirmDeleteFile'), 'confirm_deletefile', '', 0, 1);
}

print '<td align="center" width="100">';
$select_module = $form->selectarray('module', $modules, GETPOST('module'), 1, 0, 0, '', 1);
$formfile->form_attach_new_file($_SERVER['PHP_SELF'], '', 0, 0, 1, 50, '', $select_module, false, '', 0);

foreach ($modules as $module => $moduletrans) {
	$outputdir = $conf->ultimatepdf->dir_output . '/' . $module;
	$listoffiles = dol_dir_list($outputdir, 'files', 0, '', array('^SPECIMEN\.pdf$'));
	if (count($listoffiles)) {
		print $formfile->showdocuments('ultimatepdf', $module, $outputdir, $_SERVER["PHP_SELF"] . '?module=' . $module, 0, $user->admin, '', 0, 0, 0, 0, 0, '', $langs->trans("PathDirectory") . ' ' . $outputdir);
	} else {
		print '<div class="titre">' . $langs->trans("PathDirectory") . ' ' . $outputdir . ' :</div>';
		print $langs->trans("NoPDFFileFound") . '<br>';
	}
	//print split_pdf($filename, $outputdir.'/'); //test pdf split
	print '<br><br>';
}
print '</td></tr>';

// PDF_BANK_HIDE_NUMBER_SHOW_ONLY_BICIBAN.
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("HideByDefaultBankDetailsInsideUltimatepdf"), $langs->trans("HideByDefaultBankDetailsInsideUltimatepdfDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('PDF_BANK_HIDE_NUMBER_SHOW_ONLY_BICIBAN');
} else {
	if ($conf->global->PDF_BANK_HIDE_NUMBER_SHOW_ONLY_BICIBAN == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_PDF_BANK_HIDE_NUMBER_SHOW_ONLY_BICIBAN">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->PDF_BANK_HIDE_NUMBER_SHOW_ONLY_BICIBAN == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_PDF_BANK_HIDE_NUMBER_SHOW_ONLY_BICIBAN">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// use autowrap on free text.
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("UseAutowrapOnFreeTextInsideUltimatepdf"), $langs->trans("UseAutowrapOnFreeTextInsideUltimatepdfDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('MAIN_USE_AUTOWRAP_ON_FREETEXT');
} else {
	if ($conf->global->MAIN_USE_AUTOWRAP_ON_FREETEXT == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_MAIN_USE_AUTOWRAP_ON_FREETEXT">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->MAIN_USE_AUTOWRAP_ON_FREETEXT == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_MAIN_USE_AUTOWRAP_ON_FREETEXT">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// use autowrap on free text.
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("DisableClassicalCountrycode"), $langs->trans("DisableClassicalCountrycodeDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('MAIN_PRODUCT_DISABLE_CUSTOMCOUNTRYCODE');
} else {
	if ($conf->global->MAIN_PRODUCT_DISABLE_CUSTOMCOUNTRYCODE == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_MAIN_PRODUCT_DISABLE_CUSTOMCOUNTRYCODE">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->MAIN_PRODUCT_DISABLE_CUSTOMCOUNTRYCODE == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_MAIN_PRODUCT_DISABLE_CUSTOMCOUNTRYCODE">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// use autowrap on free text.
print '<tr class="oddeven">';
print '<td>' . $form->textwithpicto($langs->trans("EnableCustomCountrycodeInsideUltimatepdf"), $langs->trans("EnableCustomCountrycodeInsideUltimatepdfDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATE_PRODUCT_ENABLE_CUSTOMCOUNTRYCODE');
} else {
	if ($conf->global->ULTIMATE_PRODUCT_ENABLE_CUSTOMCOUNTRYCODE == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATE_PRODUCT_ENABLE_CUSTOMCOUNTRYCODE">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATE_PRODUCT_ENABLE_CUSTOMCOUNTRYCODE == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATE_PRODUCT_ENABLE_CUSTOMCOUNTRYCODE">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// use top menu access for ultimatepdf setup.
if ($user->admin) {
	print '<tr class="oddeven">';
	print '<td>' . $form->textwithpicto($langs->trans("UseTopMenuAccessForUltimatepdfSetup"), $langs->trans("UseTopMenuAccessForUltimatepdfSetupDescription")) . '</td>';
	print '<td align="center" width="20">&nbsp;</td>';

	print '<td align="center" width="100">';
	if ($conf->use_javascript_ajax) {
		print ajax_constantonoff('USE_TOP_MENU_ACCESS_FOR_ULTIMATEPDF_SETUP');
	} else {
		if ($conf->global->USE_TOP_MENU_ACCESS_FOR_ULTIMATEPDF_SETUP == 0) {
			print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_USE_TOP_MENU_ACCESS_FOR_ULTIMATEPDF_SETUP">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
		} else if ($conf->global->USE_TOP_MENU_ACCESS_FOR_ULTIMATEPDF_SETUP == 1) {
			print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_USE_TOP_MENU_ACCESS_FOR_ULTIMATEPDF_SETUP">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
		}
	}
	print '</td></tr>';
}

print '</table>';

print load_fiche_titre($langs->trans("OtherOptions"), '', '');

print "<form method=\"post\" action=\"" . $_SERVER["PHP_SELF"] . "\">";
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print "<input type=\"hidden\" name=\"action\" value=\"update\">";

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Parameters") . '</td>';
print '<td width="60" align="center">' . $langs->trans("Value") . '</td>';
print '<td>&nbsp;</td>';
print '</tr>';

// add barcode at bottom within documents.
print '<tr class="oddeven">';
print '<td>' .  $form->textwithpicto($langs->trans("ShowByDefaultBarcodeAtBottomInsideUltimatepdf"), $langs->trans("ShowByDefaultBarcodeAtBottomInsideUltimatepdfDescription")) . '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ULTIMATEPDF_GENERATE_DOCUMENTS_WITH_BOTTOM_BARCODE');
} else {
	if ($conf->global->ULTIMATEPDF_GENERATE_DOCUMENTS_WITH_BOTTOM_BARCODE == 0) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_ULTIMATEPDF_GENERATE_DOCUMENTS_WITH_BOTTOM_BARCODE">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
	} else if ($conf->global->ULTIMATEPDF_GENERATE_DOCUMENTS_WITH_BOTTOM_BARCODE == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_ULTIMATEPDF_GENERATE_DOCUMENTS_WITH_BOTTOM_BARCODE">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
	}
}
print '</td></tr>';

// Module thirdparty
if (!empty($conf->societe->enabled)) {
	print '<tr class="oddeven">';
	print '<td>' . $form->textwithpicto($langs->trans("SetDefaultBarcodeTypeThirdParties"), $langs->trans("SetDefaultBarcodeTypeThirdPartiesDescription")) . '</td>';
	print '<td width="60" align="right">';
	print $formbarcode->selectBarcodeType($conf->global->GENBARCODE_BARCODETYPE_THIRDPARTY, "GENBARCODE_BARCODETYPE_THIRDPARTY", 1);
	print '</td><td align="right">';
	print '<input type="submit" class="button" name="submit_GENBARCODE_BARCODETYPE_THIRDPARTY" value="' . $langs->trans("Modify") . '">';
	print "</td>";
	print '</tr>';
}

print "</table>\n";
print '</form>';

print '<br>';

if ($action == 'edit')	// Edit
{
	print '<form method="post" action="' . $_SERVER["PHP_SELF"] . '">';
	print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
	print '<input type="hidden" name="action" value="update">';

	clearstatcache();

	// Misc options
	print load_fiche_titre($langs->trans("DictionaryPaperFormat"), '', '') . '<br>';
	print '<table summary="more" class="noborder" width="100%">';
	print '<tr class="liste_titre"><td>' . $langs->trans("Parameter") . '</td><td width="200px">' . $langs->trans("Value") . '</td></tr>';

	$selected = $conf->global->MAIN_PDF_FORMAT;
	if (empty($selected)) $selected = dol_getDefaultFormat();

	// Show pdf format
	print '<tr class="oddeven"><td>' . $langs->trans("DictionaryPaperFormat") . '</td><td>';
	print $formadmin->select_paper_format($selected, 'MAIN_PDF_FORMAT');
	print '</td></tr>';

	print '</table>';

	print '<br><div class="center">';
	print '<input class="button" type="submit" value="' . $langs->trans("Save") . '">';
	print '</div>';

	print '</form>';
	print '<br>';
} else	// Show
{
	// Misc options
	print load_fiche_titre($langs->trans("DictionaryPaperFormat"), '', '');
	print '<table summary="more" class="noborder" width="100%">';
	print '<tr class="liste_titre"><td>' . $langs->trans("Parameter") . '</td><td width="200px">' . $langs->trans("Value") . '</td></tr>';

	// Show pdf format
	print '<tr class="oddeven"><td>' . $langs->trans("DictionaryPaperFormat") . '</td><td>';

	$pdfformatlabel = '';
	if (empty($conf->global->MAIN_PDF_FORMAT)) {
		include_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
		$pdfformatlabel = dol_getDefaultFormat();
	} else $pdfformatlabel = $conf->global->MAIN_PDF_FORMAT;
	if (!empty($pdfformatlabel)) {
		$sql = "SELECT code, label, width, height, unit FROM " . MAIN_DB_PREFIX . "c_paper_format";
		$sql .= " WHERE code LIKE '%" . $db->escape($pdfformatlabel) . "%'";

		$resql = $db->query($sql);
		if ($resql) {
			$obj = $db->fetch_object($resql);
			$paperKey = $langs->trans('PaperFormat' . $obj->code);
			$unitKey = $langs->trans('SizeUnit' . $obj->unit);
			$pdfformatlabel = ($paperKey == 'PaperFormat' . $obj->code ? $obj->label : $paperKey) . ' - ' . round($obj->width) . 'x' . round($obj->height) . ' ' . ($unitKey == 'SizeUnit' . $obj->unit ? $obj->unit : $unitKey);
		}
	}
	print $pdfformatlabel;
	print '</td></tr>';

	print '</table>';

	print '<div class="tabsAction">';
	print '<a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?action=edit">' . $langs->trans("Modify") . '</a>';
	print '</div>';
	print '<br>';
}

// Footer
llxFooter();
// Close database handler
$db->close();
?>
