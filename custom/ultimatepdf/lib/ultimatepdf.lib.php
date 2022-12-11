<?php
/* Copyright (C) 2013-2022 Philippe Grand  <philippe.grand@atoo-net.com>
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
 *	    \file       /ultimatepdf/lib/ultimatepdf.lib.php
 *		\brief      Library files with common functions for ultimatepdf
 *      \ingroup    ultimatepdf
 */
//use setasign\Fpdi\Tcpdf\Fpdi;

/**
 * html2rgb
 *
 * @param  mixed $color
 * @return void
 */
function html2rgb($color)
{
	//gestion du #...
	if (substr($color, 0, 1) == "#") $color = substr($color, 1, 6);

	$tablo[0] = hexdec(substr($color, 0, 2));
	$tablo[1] = hexdec(substr($color, 2, 2));
	$tablo[2] = hexdec(substr($color, 4, 2));
	return $tablo;
}

/**
 * ultimatepdf_prepare_head
 *
 * @return void
 */
function ultimatepdf_prepare_head()
{
	global $langs, $conf;

	// Translations
	$langs->loadLangs(array("bills", "contracts", "orders", "propal", "sendings", "trips", "supplier_proposal", "ultimatepdf@ultimatepdf"));

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/ultimatepdf/admin/setup.php", 1);
	$head[$h][1] = $langs->trans("UltimatepdfDesigns");
	$head[$h][2] = 'designs';
	$h++;

	$head[$h][0] = dol_buildpath("/ultimatepdf/admin/options.php", 1);
	$head[$h][1] = $langs->trans("Options");
	$head[$h][2] = 'options';
	$h++;

	$head[$h][0] = dol_buildpath("/ultimatepdf/admin/proposals.php", 1);
	$head[$h][1] = $langs->trans("Proposals");
	$head[$h][2] = 'proposals';
	$h++;

	$head[$h][0] = dol_buildpath("/ultimatepdf/admin/orders.php", 1);
	$head[$h][1] = $langs->trans("Orders");
	$head[$h][2] = 'orders';
	$h++;

	$head[$h][0] = dol_buildpath("/ultimatepdf/admin/invoices.php", 1);
	$head[$h][1] = $langs->trans("Invoices");
	$head[$h][2] = 'invoices';
	$h++;

	$head[$h][0] = dol_buildpath("/ultimatepdf/admin/shipments.php", 1);
	$head[$h][1] = $langs->trans("Shipments");
	$head[$h][2] = 'shipments';
	$h++;

	$head[$h][0] = dol_buildpath("/ultimatepdf/admin/deliveries.php", 1);
	$head[$h][1] = $langs->trans("Deliveries");
	$head[$h][2] = 'deliveries';
	$h++;

	$head[$h][0] = dol_buildpath("/ultimatepdf/admin/supplierorders.php", 1);
	$head[$h][1] = $langs->trans("SupplierOrder");
	$head[$h][2] = 'supplierorders';
	$h++;

	$head[$h][0] = dol_buildpath("/ultimatepdf/admin/receipts.php", 1);
	$head[$h][1] = $langs->trans("Receivings");
	$head[$h][2] = 'receipts';
	$h++;

	$head[$h][0] = dol_buildpath("/ultimatepdf/admin/supplierinvoices.php", 1);
	$head[$h][1] = $langs->trans("SupplierInvoice");
	$head[$h][2] = 'supplierinvoices';
	$h++;

	$head[$h][0] = dol_buildpath("/ultimatepdf/admin/supplierproposal.php", 1);
	$head[$h][1] = $langs->trans("CommercialAsk");
	$head[$h][2] = 'supplierproposal';
	$h++;

	$head[$h][0] = dol_buildpath("/ultimatepdf/admin/contracts.php", 1);
	$head[$h][1] = $langs->trans("Contracts");
	$head[$h][2] = 'contracts';
	$h++;

	$head[$h][0] = dol_buildpath("/ultimatepdf/admin/expensereport.php", 1);
	$head[$h][1] = $langs->trans("Trips");
	$head[$h][2] = 'expensereport';
	$h++;

	$head[$h][0] = dol_buildpath("/ultimatepdf/admin/fichinter.php", 1);
	$head[$h][1] = $langs->trans("Interventions");
	$head[$h][2] = 'ficheinter';
	$h++;

	$head[$h][0] = dol_buildpath("/ultimatepdf/admin/project.php", 1);
	$head[$h][1] = $langs->trans("Projects");
	$head[$h][2] = 'project';
	$h++;

	$head[$h][0] = dol_buildpath("/ultimatepdf/admin/products.php", 1);
	$head[$h][1] = $langs->trans("Products");
	$head[$h][2] = 'product';
	$h++;

	$head[$h][0] = dol_buildpath("/ultimatepdf/admin/bom.php", 1);
	$head[$h][1] = $langs->trans("Boms");
	$head[$h][2] = 'boms';
	$h++;

	$head[$h][0] = dol_buildpath("/ultimatepdf/admin/about.php", 1);
	$head[$h][1] = $langs->trans("About");
	$head[$h][2] = 'about';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to remove a tab
	complete_head_from_modules($conf, $langs, null, $head, $h, 'ultimatepdf@ultimatepdf');

	return $head;
}

/**
 * checkUltimatepdfVersion
 *
 * @return void
 */
function checkUltimatepdfVersion()
{
	global $conf;

	if (empty($conf->global->ULTIMATEPDF_MAIN_VERSION)) return false;
	if ($conf->global->ULTIMATEPDF_MAIN_VERSION < '14.0.0') return false;

	return true;
}

/**
 *   	Return a string with full address formated for output on documents
 *
 * 		@param	Translate	$outputlangs		Output langs object
 *   	@param  Societe		$sourcecompany		Source company object
 *   	@param  Societe		$targetcompany		Target company object
 *      @param  Contact		$targetcontact		Target contact object
 * 		@param	int			$usecontact			Use contact instead of company
 * 		@param	int			$mode				Address type ('source', 'target', 'targetwithdetails', 'targetwithdetails_xxx': target but include also phone/fax/email/url)
 *      @param  Object      $object             Object we want to build document for
 * 		@return	string							String with full address
 */
function pdf_element_build_address($outputlangs, $sourcecompany, $targetcompany = '', $targetcontact = '', $usecontact = 0, $mode = 'source', $object = null, $modeconcat = false)
{
	global $conf, $hookmanager;

	$elementAddress = array(
		'facture' => array(
			'lowerconst' => 'invoice',
			'upperconst' => 'INVOICE'
		),
		'commande' => array(
			'lowerconst' => 'order',
			'upperconst' => 'ORDER'
		),
		'propal' => array(
			'lowerconst' => 'propal',
			'upperconst' => 'PROPAL'
		),
		'order_supplier' => array(
			'lowerconst' => 'supplierorder',
			'upperconst' => 'SUPPLIERORDER'
		),
		'invoice_supplier' => array(
			'lowerconst' => 'supplierinvoice',
			'upperconst' => 'SUPPLIERINVOICE'
		),
		'supplier_proposal' => array(
			'lowerconst' => 'supplierproposal',
			'upperconst' => 'SUPPLIERPROPOSAL'
		),
		'contrat' => array(
			'lowerconst' => 'contract',
			'upperconst' => 'CONTRACT'
		),
		'shipping' => array(
			'lowerconst' => 'shipping',
			'upperconst' => 'SHIPPING'
		),
		'expensereport' => array(
			'lowerconst' => 'expensereport',
			'upperconst' => 'EXPENSEREPORT'
		),
		'fichinter' => array(
			'lowerconst' => 'fichinter',
			'upperconst' => 'FICHINTER'
		),
		'delivery' => array(
			'lowerconst' => 'delivery',
			'upperconst' => 'DELIVERY'
		),
		'project' => array(
			'lowerconst' => 'project',
			'upperconst' => 'PROJECT'
		)
	);

	if (is_object($object) && array_key_exists($object->element, $elementAddress)) {
		if ($mode == 'source' && !is_object($sourcecompany)) return -1;
		if ($mode == 'target' && !is_object($targetcompany)) return -1;
		if (!empty($sourcecompany->state_id) && empty($sourcecompany->state))       $sourcecompany->state = getState($sourcecompany->state_id);
		if (!empty($targetcompany->state_id) && empty($targetcompany->state))       $targetcompany->state = getState($targetcompany->state_id);

		$reshook = 0;
		$stringaddress = '';
		$upperConst = $elementAddress[$object->element]['upperconst'];
		$ultimateDisablesourcedetails = 'ULTIMATE_PDF_' . $upperConst . '_DISABLESOURCEDETAILS';
		$ultimateAddalsotargetdetails = 'ULTIMATE_PDF_' . $upperConst . '_ADDALSOTARGETDETAILS';
		$ultimateAddalsoclientdetails = 'ULTIMATE_PDF_' . $upperConst . '_ADDALSOCLIENTDETAILS';
		$ultimateTvaintranotinaddress = 'ULTIMATE_TVAINTRA_NOT_IN_' . $upperConst . '_ADDRESS';

		if (is_object($hookmanager)) {
			$parameters = array('sourcecompany' => &$sourcecompany, 'targetcompany' => &$targetcompany, 'targetcontact' => $targetcontact, 'outputlangs' => $outputlangs, 'mode' => $mode, 'usecontact' => $usecontact);
			$action = '';
			$reshook = $hookmanager->executeHooks('pdf_element_build_address', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks
			$stringaddress .= $hookmanager->resPrint;
		}
		if (empty($reshook)) {
			if ($mode == 'source') {
				$withCountry = 0;

				if (!empty($conf->global->ULTIMATE_PDF_ALIAS_COUNTRY_EMETTEUR) && $conf->global->ULTIMATE_PDF_ALIAS_COUNTRY_EMETTEUR == '::') {
					$tmp = explode(':', $conf->global->MAIN_INFO_SOCIETE_COUNTRY);
					$country_code = $tmp[1] ? $tmp[1] : $tmp[0];
					$sourcecompany->country_code = $country_code;
				} else {
					if (isset($conf->global->ULTIMATE_PDF_ALIAS_COUNTRY_EMETTEUR)) {
						$tmp = explode(':', $conf->global->ULTIMATE_PDF_ALIAS_COUNTRY_EMETTEUR);
					}
					if (isset($tmp)) {
						$country_code = $tmp[1] ? $tmp[1] : $tmp[0];
						$sourcecompany->country_code = $country_code;
					}
				}
				if (!empty($sourcecompany->country_code) && ($targetcompany->country_code != $sourcecompany->country_code)) $withCountry = 1;

				if (!empty($conf->global->ULTIMATE_PDF_ALIAS_ADDRESS_EMETTEUR)) {
					$sourcecompany->address = $conf->global->ULTIMATE_PDF_ALIAS_ADDRESS_EMETTEUR;
				}
				if (!empty($conf->global->ULTIMATE_PDF_ALIAS_ZIP_EMETTEUR)) {
					$sourcecompany->zip = $conf->global->ULTIMATE_PDF_ALIAS_ZIP_EMETTEUR;
				}
				if (!empty($conf->global->ULTIMATE_PDF_ALIAS_TOWN_EMETTEUR)) {
					$sourcecompany->town = $conf->global->ULTIMATE_PDF_ALIAS_TOWN_EMETTEUR;
				}
				$stringaddress .= ($stringaddress ? "\n" : '') . $outputlangs->convToOutputCharset(dol_format_address($sourcecompany, $withCountry, "\n", $outputlangs)) . "\n";
				$stringaddress = nl2br($stringaddress);
				if (empty($conf->global->$ultimateDisablesourcedetails)) {
					// Phone
					$htmlphone = '<img src="' . DOL_DOCUMENT_ROOT . '/theme/eldy/img/object_phoning.png' . '" width="10" height="10" />';
					if (!empty($conf->global->ULTIMATE_PDF_ALIAS_PHONE_EMETTEUR)) {
						$sourcecompany->phone = $conf->global->ULTIMATE_PDF_ALIAS_PHONE_EMETTEUR;
					}
					if ($sourcecompany->phone) $stringaddress .= ($stringaddress ? "\n<br>" : '') . $htmlphone . "&nbsp;" . $outputlangs->convToOutputCharset($sourcecompany->phone);
					// Fax 
					$htmlfax = '<img src="' . DOL_DOCUMENT_ROOT . '/theme/eldy/img/object_phoning_fax.png' . '" width="10" height="10" />';
					if (!empty($conf->global->ULTIMATE_PDF_ALIAS_FAX_EMETTEUR)) {
						$sourcecompany->fax = $conf->global->ULTIMATE_PDF_ALIAS_FAX_EMETTEUR;
					}
					if ($sourcecompany->fax) $stringaddress .= ($stringaddress ? ($sourcecompany->phone ? " - " : "\n<br>") : '') . $htmlfax . "&nbsp;" . $outputlangs->convToOutputCharset($sourcecompany->fax);
					// EMail
					$htmlemail = '<img src="' . DOL_DOCUMENT_ROOT . '/theme/eldy/img/object_email.png' . '" width="10" height="10" />';
					if (!empty($conf->global->ULTIMATE_PDF_ALIAS_EMAIL_EMETTEUR)) {
						$sourcecompany->email = $conf->global->ULTIMATE_PDF_ALIAS_EMAIL_EMETTEUR;
					}
					if ($sourcecompany->email) $stringaddress .= ($stringaddress ? "\n<br>" : '') . $htmlemail . "&nbsp;" . $outputlangs->convToOutputCharset($sourcecompany->email);
					// Web
					$htmlurl = '<img src="' . DOL_DOCUMENT_ROOT . '/theme/eldy/img/object_website.png' . '" width="10" height="10" />';
					if (!empty($conf->global->ULTIMATE_PDF_ALIAS_URL_EMETTEUR)) {
						$sourcecompany->url = $conf->global->ULTIMATE_PDF_ALIAS_URL_EMETTEUR;
					}
					if ($sourcecompany->url) $stringaddress .= ($stringaddress ? "\n" : '') . $htmlurl . "&nbsp;" . $outputlangs->convToOutputCharset($sourcecompany->url);

					// Professionnal Ids
					$reg = array();
					if (!empty($conf->global->MAIN_PROFID1_IN_SOURCE_ADDRESS) && !empty($sourcecompany->idprof1)) {
						$tmp = $outputlangs->transcountrynoentities("ProfId1", $sourcecompany->country_code);
						if (preg_match('/\((.+)\)/', $tmp, $reg)) $tmp = $reg[1];
						$stringaddress .= "\n<br>" . $tmp . ': ' . $outputlangs->convToOutputCharset($sourcecompany->idprof1);
					}
					if (!empty($conf->global->MAIN_PROFID2_IN_SOURCE_ADDRESS) && !empty($sourcecompany->idprof2)) {
						$tmp = $outputlangs->transcountrynoentities("ProfId2", $sourcecompany->country_code);
						if (preg_match('/\((.+)\)/', $tmp, $reg)) $tmp = $reg[1];
						$stringaddress .= "\n<br>" . $tmp . ': ' . $outputlangs->convToOutputCharset($sourcecompany->idprof2);
					}
					if (!empty($conf->global->MAIN_PROFID3_IN_SOURCE_ADDRESS) && !empty($sourcecompany->idprof3)) {
						$tmp = $outputlangs->transcountrynoentities("ProfId3", $sourcecompany->country_code);
						if (preg_match('/\((.+)\)/', $tmp, $reg)) $tmp = $reg[1];
						$stringaddress .= "\n<br>" . $tmp . ': ' . $outputlangs->convToOutputCharset($sourcecompany->idprof3);
					}
					if (!empty($conf->global->MAIN_PROFID4_IN_SOURCE_ADDRESS) && !empty($sourcecompany->idprof4)) {
						$tmp = $outputlangs->transcountrynoentities("ProfId4", $sourcecompany->country_code);
						if (preg_match('/\((.+)\)/', $tmp, $reg)) $tmp = $reg[1];
						$stringaddress .= "\n<br>" . $tmp . ': ' . $outputlangs->convToOutputCharset($sourcecompany->idprof4);
					}
					if (!empty($conf->global->MAIN_PROFID5_IN_SOURCE_ADDRESS) && !empty($sourcecompany->idprof5)) {
						$tmp = $outputlangs->transcountrynoentities("ProfId5", $sourcecompany->country_code);
						if (preg_match('/\((.+)\)/', $tmp, $reg)) $tmp = $reg[1];
						$stringaddress .= ($stringaddress ? "\n<br>" : '') . $tmp . ': ' . $outputlangs->convToOutputCharset($sourcecompany->idprof5);
					}
					if (!empty($conf->global->MAIN_INFO_TVAINTRA) && !empty($sourcecompany->tva_intra)) {
						$tmp = $outputlangs->transcountrynoentities("VATIntra", $sourcecompany->country_code);
						if (preg_match('/\((.+)\)/', $tmp, $reg)) $tmp = $reg[1];
						$stringaddress .= "\n<br>" . $tmp . ': ' . $outputlangs->convToOutputCharset($sourcecompany->tva_intra);
					}
					if (!empty($conf->global->MAIN_PROFID6_IN_SOURCE_ADDRESS) && !empty($sourcecompany->idprof6)) {
						$tmp = $outputlangs->transcountrynoentities("ProfId6", $sourcecompany->country_code);
						if (preg_match('/\((.+)\)/', $tmp, $reg)) $tmp = $reg[1];
						$stringaddress .= "\n<br>" . $tmp . ': ' . $outputlangs->convToOutputCharset($sourcecompany->idprof6);
					}
					if (!empty($conf->global->PDF_ADD_MORE_AFTER_SOURCE_ADDRESS)) {
						$stringaddress .= ($stringaddress ? "\n<br>" : '') . $conf->global->PDF_ADD_MORE_AFTER_SOURCE_ADDRESS;
					}
				}
			}

			if ($mode == 'target' || preg_match('/targetwithdetails/', $mode)) {
				if ($usecontact) {
					if ($conf->global->ULTIMATE_CONTACT_ADDRESS_BLOCKS_OVER == 1) {
						$stringaddress .= $outputlangs->convToOutputCharset($targetcontact->getFullName($outputlangs, 1));
					} else {
						$stringaddress .= ($stringaddress ? "\n<br>" : '') . $outputlangs->convToOutputCharset($targetcontact->getFullName($outputlangs, 1));
					}
					
					if (!empty($targetcontact->address)) {
						if ($conf->global->ULTIMATE_CONTACT_ADDRESS_BLOCKS_OVER == 1 && $modeconcat) {
							$stringaddress .= ' : ' . $outputlangs->convToOutputCharset(dol_format_address($targetcontact, 1, "\n", $outputlangs));
						} else {
							$stringaddress .= ($stringaddress ? "\n<br>" : '') . $outputlangs->convToOutputCharset(dol_format_address($targetcontact, 1, "\n", $outputlangs));
							$stringaddress = nl2br($stringaddress);
						}
					} else {
						$companytouseforaddress = $targetcompany;

						// Contact on a thirdparty that is a different thirdparty than the thirdparty of object
						if ($targetcontact->socid > 0 && $targetcontact->socid != $targetcompany->id) {
							$targetcontact->fetch_thirdparty();
							$companytouseforaddress = $targetcontact->thirdparty;
						}
						if ($conf->global->ULTIMATE_CONTACT_ADDRESS_BLOCKS_OVER == 1 && $modeconcat) {
							$stringaddress .=  ' : ' . $outputlangs->convToOutputCharset(dol_format_address($companytouseforaddress));
						} else {
							$stringaddress .= ($stringaddress ? "\n<br>" : '') . $outputlangs->convToOutputCharset(dol_format_address($companytouseforaddress));
							$stringaddress = nl2br($stringaddress);
						}
					}
					// Country
					/*if (!empty($targetcontact->country_code) && $targetcontact->country_code != $sourcecompany->country_code) {
						$stringaddress .= ($stringaddress ? "\n<br>" : '') . $outputlangs->convToOutputCharset($outputlangs->transnoentitiesnoconv("Country" . $targetcontact->country_code));
					} else if (empty($targetcontact->country_code) && !empty($targetcompany->country_code) && ($targetcompany->country_code != $sourcecompany->country_code)) {
						$stringaddress .= ($stringaddress ? "\n<br>" : '') . $outputlangs->convToOutputCharset($outputlangs->transnoentitiesnoconv("Country" . $targetcompany->country_code));
					}*/

					if (!empty($conf->global->$ultimateAddalsotargetdetails) || preg_match('/targetwithdetails/', $mode)) {
						// Phone
						if (!empty($conf->global->$ultimateAddalsotargetdetails) || $mode == 'targetwithdetails' || preg_match('/targetwithdetails_phone/', $mode)) {
							$htmlphone = '<img src="' . DOL_DOCUMENT_ROOT . '/theme/eldy/img/object_phoning.png' . '" width="10" height="10" />';
							$htmlmobile = '<img src="' . DOL_DOCUMENT_ROOT . '/theme/eldy/img/object_phoning_mobile.png' . '" width="10" height="10" />';
							if (!empty($targetcontact->phone_pro) || !empty($targetcontact->phone_mobile)) $stringaddress .= ($stringaddress ? "\n<br>" : '');
							if (!empty($targetcontact->phone_pro)) $stringaddress .= $htmlphone . "&nbsp;" . $outputlangs->convToOutputCharset($targetcontact->phone_pro);
							if (!empty($targetcontact->phone_pro) && !empty($targetcontact->phone_mobile)) $stringaddress .= " / ";
							if (!empty($targetcontact->phone_mobile)) $stringaddress .= $htmlmobile . "&nbsp;" . $outputlangs->convToOutputCharset($targetcontact->phone_mobile);
						}
						// Fax
						$htmlfax = '<img src="' . DOL_DOCUMENT_ROOT . '/theme/eldy/img/object_phoning_fax.png' . '" width="10" height="10" />';
						if (!empty($conf->global->$ultimateAddalsotargetdetails) || $mode == 'targetwithdetails' || preg_match('/targetwithdetails_fax/', $mode)) {
							if ($targetcontact->fax) $stringaddress .= ($stringaddress ? "\n<br>" : '') . $htmlfax . "&nbsp;" . $outputlangs->convToOutputCharset($targetcontact->fax);
						}
						// EMail
						$htmlemail = '<img src="' . DOL_DOCUMENT_ROOT . '/theme/eldy/img/object_email.png' . '" width="10" height="10" />';
						if (!empty($conf->global->$ultimateAddalsotargetdetails) || $mode == 'targetwithdetails' || preg_match('/targetwithdetails_email/', $mode)) {
							if ($targetcontact->email) $stringaddress .= ($stringaddress ? "\n<br>" : '') . $htmlemail . "&nbsp;" . $outputlangs->convToOutputCharset($targetcontact->email);
						}
						// Web
						$htmlurl = '<img src="' . DOL_DOCUMENT_ROOT . '/theme/eldy/img/object_website.png' . '" width="10" height="10" />';
						if (!empty($conf->global->$ultimateAddalsotargetdetails) || $mode == 'targetwithdetails' || preg_match('/targetwithdetails_url/', $mode)) {
							if ($targetcontact->url) $stringaddress .= ($stringaddress ? "\n<br>" : '') . $htmlurl . "&nbsp;" . $outputlangs->convToOutputCharset($targetcontact->url);
						}
					}
				} else {
					$stringaddress .= dol_nl2br(($stringaddress ? "\n" : '') . $outputlangs->convToOutputCharset(dol_format_address($targetcompany, isset($withCountry), "\n", $outputlangs)), 1, true);
					// Country
					if (!empty($targetcompany->country_code) && $targetcompany->country_code != $sourcecompany->country_code) $stringaddress .= ($stringaddress ? "\n<br>" : '') . $outputlangs->convToOutputCharset($outputlangs->transnoentitiesnoconv("Country" . $targetcompany->country_code)) . "\n<br>";

					if (!empty($conf->global->$ultimateAddalsoclientdetails) || preg_match('/targetwithdetails/', $mode)) {
						// Phone
						$htmlphone = '<img src="' . DOL_DOCUMENT_ROOT . '/theme/eldy/img/object_phoning.png' . '" width="10" height="10" />';
						$htmlmobile = '<img src="' . DOL_DOCUMENT_ROOT . '/theme/eldy/img/object_phoning_mobile.png' . '" width="10" height="10" />';
						if (!empty($conf->global->$ultimateAddalsoclientdetails) || $mode == 'targetwithdetails' || preg_match('/targetwithdetails_phone/', $mode)) {
							if (!empty($targetcompany->phone) || !empty($targetcompany->phone_mobile)) $stringaddress .= ($stringaddress ? "\n<br>" : '');
							if (!empty($targetcompany->phone)) $stringaddress .= $htmlphone . "&nbsp;" . $outputlangs->convToOutputCharset($targetcompany->phone);
							if (!empty($targetcompany->phone) && !empty($targetcompany->phone_mobile)) $stringaddress .= " / ";
							if (!empty($targetcompany->phone_mobile)) $stringaddress .= $htmlmobile . "&nbsp;" . $outputlangs->convToOutputCharset($targetcompany->phone_mobile);
						}
						// Fax
						$htmlfax = '<img src="' . DOL_DOCUMENT_ROOT . '/theme/eldy/img/object_phoning_fax.png' . '" width="10" height="10" />';
						if (!empty($conf->global->$ultimateAddalsoclientdetails) || $mode == 'targetwithdetails' || preg_match('/targetwithdetails_fax/', $mode)) {
							if ($targetcompany->fax) $stringaddress .= ($stringaddress ? "\n<br>" : '') . $htmlfax . "&nbsp;" . $outputlangs->convToOutputCharset($targetcompany->fax);
						}
						// EMail
						$htmlemail = '<img src="' . DOL_DOCUMENT_ROOT . '/theme/eldy/img/object_email.png' . '" width="10" height="10" />';
						if (!empty($conf->global->$ultimateAddalsoclientdetails) || $mode == 'targetwithdetails' || preg_match('/targetwithdetails_email/', $mode)) {
							if ($targetcompany->email) $stringaddress .= ($stringaddress ? "\n<br>" : '') . $htmlemail . "&nbsp;" . $outputlangs->convToOutputCharset($targetcompany->email);
						}
						// Web
						$htmlurl = '<img src="' . DOL_DOCUMENT_ROOT . '/theme/eldy/img/object_website.png' . '" width="10" height="10" />';
						if (!empty($conf->global->$ultimateAddalsoclientdetails) || $mode == 'targetwithdetails' || preg_match('/targetwithdetails_url/', $mode)) {
							if ($targetcompany->url) $stringaddress .= ($stringaddress ? "\n<br>" : '') . $htmlurl . "&nbsp;" . $outputlangs->convToOutputCharset($targetcompany->url);
						}
					}
				}

				if (empty($conf->global->$ultimateTvaintranotinaddress) && $conf->global->$ultimateTvaintranotinaddress == 0) {
					if ($targetcompany->tva_intra) {
						$stringaddress .= ($stringaddress ? "\n<br>" : '') . $outputlangs->transnoentities("VATNumber") . ': ' . $outputlangs->convToOutputCharset($targetcompany->tva_intra);
					}
				}

				// Professionnal Ids
				if (!empty($conf->global->MAIN_PROFID1_IN_ADDRESS) && !empty($targetcompany->idprof1)) {
					$tmp = $outputlangs->transcountrynoentities("ProfId1", $targetcompany->country_code);
					if (preg_match('/\((.+)\)/', $tmp, $reg)) $tmp = $reg[1];
					$stringaddress .= ($stringaddress ? "\n<br>" : '') . $tmp . ': ' . $outputlangs->convToOutputCharset($targetcompany->idprof1);
				}
				if (!empty($conf->global->MAIN_PROFID2_IN_ADDRESS) && !empty($targetcompany->idprof2)) {
					$tmp = $outputlangs->transcountrynoentities("ProfId2", $targetcompany->country_code);
					if (preg_match('/\((.+)\)/', $tmp, $reg)) $tmp = $reg[1];
					$stringaddress .= ($stringaddress ? "\n<br>" : '') . $tmp . ': ' . $outputlangs->convToOutputCharset($targetcompany->idprof2);
				}
				if (!empty($conf->global->MAIN_PROFID3_IN_ADDRESS) && !empty($targetcompany->idprof3)) {
					$tmp = $outputlangs->transcountrynoentities("ProfId3", $targetcompany->country_code);
					if (preg_match('/\((.+)\)/', $tmp, $reg)) $tmp = $reg[1];
					$stringaddress .= ($stringaddress ? "\n<br>" : '') . $tmp . ': ' . $outputlangs->convToOutputCharset($targetcompany->idprof3);
				}
				if (!empty($conf->global->MAIN_PROFID4_IN_ADDRESS) && !empty($targetcompany->idprof4)) {
					$tmp = $outputlangs->transcountrynoentities("ProfId4", $targetcompany->country_code);
					if (preg_match('/\((.+)\)/', $tmp, $reg)) $tmp = $reg[1];
					$stringaddress .= ($stringaddress ? "\n<br>" : '') . $tmp . ': ' . $outputlangs->convToOutputCharset($targetcompany->idprof4);
				}
				if (!empty($conf->global->MAIN_PROFID5_IN_ADDRESS) && !empty($targetcompany->idprof5)) {
					$tmp = $outputlangs->transcountrynoentities("ProfId5", $targetcompany->country_code);
					if (preg_match('/\((.+)\)/', $tmp, $reg)) $tmp = $reg[1];
					$stringaddress .= ($stringaddress ? "\n<br>" : '') . $tmp . ': ' . $outputlangs->convToOutputCharset($targetcompany->idprof5);
				}
				if (!empty($conf->global->MAIN_PROFID6_IN_ADDRESS) && !empty($targetcompany->idprof6)) {
					$tmp = $outputlangs->transcountrynoentities("ProfId6", $targetcompany->country_code);
					if (preg_match('/\((.+)\)/', $tmp, $reg)) $tmp = $reg[1];
					$stringaddress .= ($stringaddress ? "\n<br>" : '') . $tmp . ': ' . $outputlangs->convToOutputCharset($targetcompany->idprof6);
				}

				// Public note
				if (!empty($conf->global->MAIN_PUBLIC_NOTE_IN_ADDRESS)) {
					if ($mode == 'source' && !empty($sourcecompany->note_public)) {
						$stringaddress .= "\n<br>" . dol_string_nohtmltag($sourcecompany->note_public);
					}
					if (($mode == 'target' || preg_match('/targetwithdetails/', $mode)) && !empty($targetcompany->note_public)) {
						$stringaddress .= "\n<br>" . dol_string_nohtmltag($targetcompany->note_public);
					}
				}
			}
		}
		return $stringaddress;
	}
}

/**
 *  Show footer of page for PDF generation
 *
 *	@param	TCPDF		$pdf     		The PDF factory
 *  @param  Translate	$outputlangs	Object lang for output
 * 	@param	string		$paramfreetext	Constant name of free text
 * 	@param	Societe		$fromcompany	Object company
 * 	@param	int			$marge_basse	Margin bottom we use for the autobreak
 * 	@param	int			$marge_gauche	Margin left (no more used)
 * 	@param	int			$page_hauteur	Page height (no more used)
 * 	@param	Object		$object			Object shown in PDF
 * 	@param	int			$showdetails	Show company adress details into footer (0=Nothing, 1=Show address, 2=Show managers, 3=Both)
 *  @param	int			$hidefreetext	1=Hide free text, 0=Show free text
 *  @param	int			$footertextcolor	footer text color
 * 	@return	int							Return height of bottom margin including footer text
 */
function pdf_ultimatepagefoot(&$pdf, $outputlangs, $paramfreetext, $fromcompany, $marge_basse, $marge_gauche, $page_hauteur, $object, $showdetails = 0, $hidefreetext = 0, $footertextcolor)
{
	global $conf, $user, $mysoc;

	$outputlangs->loadLangs(array("dict", "companies"));
	$line = '';

	$dims = $pdf->getPageDimensions();
	
	if (!empty($conf->global->ULTIMATE_PDF_ALIAS_COMPANY)) {
		$fromcompany->name = $conf->global->ULTIMATE_PDF_ALIAS_COMPANY;
	} else {
		$fromcompany->name = $fromcompany->name;
	}

	// Line of free text
	if (empty($hidefreetext) && !empty($conf->global->$paramfreetext)) {
		// Make substitution
		$substitutionarray = pdf_getSubstitutionArray($outputlangs, null, $object);
		// More substitution keys
		$substitutionarray['__FROM_NAME__'] = $fromcompany->name;
		$substitutionarray['__FROM_EMAIL__'] = $fromcompany->email;
		complete_substitutions_array($substitutionarray, $outputlangs, $object);
		$newfreetext = make_substitutions($conf->global->$paramfreetext, $substitutionarray, $outputlangs);

		// Make a change into HTML code to allow to include images from medias directory.
		// <img alt="" src="/dolibarr_dev/htdocs/viewimage.php?modulepart=medias&amp;entity=1&amp;file=image/ldestailleur_166x166.jpg" style="height:166px; width:166px" />
		// become
		// <img alt="" src="'.DOL_DATA_ROOT.'/medias/image/ldestailleur_166x166.jpg" style="height:166px; width:166px" />
		$newfreetext = preg_replace('/(<img.*src=")[^\"]*viewimage\.php[^\"]*modulepart=medias[^\"]*file=([^\"]*)("[^\/]*\/>)/', '\1' . DOL_DATA_ROOT . '/medias/\2\3', $newfreetext);

		$line .= $outputlangs->convToOutputCharset($newfreetext);
	}

	// First line of company infos
	$line1 = "";
	$line2 = "";
	$line3 = "";
	$line4 = "";

	if ($showdetails == 1 || $showdetails == 3) {
		// Company name
		if ($fromcompany->name) {
			$line1 .= ($line1 ? " - " : "") . $outputlangs->transnoentities("RegisteredOffice") . ": " . $fromcompany->name;
		}
		// Address
		if ($fromcompany->address) {
			$line1 .= ($line1 ? " - " : "") . str_replace("\n", ", ", $fromcompany->address);
		}
		// Zip code
		if ($fromcompany->zip) {
			$line1 .= ($line1 ? " - " : "") . $fromcompany->zip;
		}
		// Town
		if ($fromcompany->town) {
			$line1 .= ($line1 ? " " : "") . $fromcompany->town;
		}
		// Country
		if ($fromcompany->country) {
			$line1 .= ($line1 ? ", " : "") . $fromcompany->country;
		}
		// Phone
		if ($fromcompany->phone) {
			$line2 .= ($line2 ? " - " : "") . $outputlangs->transnoentities("Phone") . ": " . $fromcompany->phone;
		}
		// Fax
		if ($fromcompany->fax) {
			$line2 .= ($line2 ? " - " : "") . $outputlangs->transnoentities("Fax") . ": " . $fromcompany->fax;
		}

		// URL
		if ($fromcompany->url) {
			$line2 .= ($line2 ? " - " : "") . $fromcompany->url;
		}
		// Email
		if ($fromcompany->email) {
			$line2 .= ($line2 ? " - " : "") . $fromcompany->email;
		}
	}
	if ($showdetails == 2 || $showdetails == 3 || ($fromcompany->country_code == 'DE')) {
		// Managers
		if ($fromcompany->managers) {
			$line2 .= ($line2 ? " - " : "") . $fromcompany->managers;
		}
	}

	// Line 3 of company infos
	// Juridical status
	if ($fromcompany->forme_juridique_code) {
		$line3 .= ($line3 ? " - " : "") . $outputlangs->convToOutputCharset(getFormeJuridiqueLabel($fromcompany->forme_juridique_code));
	}
	// Capital
	if ($fromcompany->capital) {
		$tmpamounttoshow = price2num($fromcompany->capital); // This field is a free string
		if (is_numeric($tmpamounttoshow) && $tmpamounttoshow > 0) $line3 .= ($line3 ? " - " : "") . $outputlangs->transnoentities("CapitalOf", price($tmpamounttoshow, 0, $outputlangs, 0, 0, 0, $conf->currency));
		else $line3 .= ($line3 ? " - " : "") . $outputlangs->transnoentities("CapitalOf", $tmpamounttoshow, $outputlangs);
	}
	// Prof Id 1
	if ($fromcompany->idprof1 && ($fromcompany->country_code != 'FR' || !$fromcompany->idprof2)) {
		$field = $outputlangs->transcountrynoentities("ProfId1", $fromcompany->country_code);
		if (preg_match('/\((.*)\)/i', $field, $reg)) $field = $reg[1];
		$line3 .= ($line3 ? " - " : "") . $field . ": " . $outputlangs->convToOutputCharset($fromcompany->idprof1);
	}
	// Prof Id 2
	if ($fromcompany->idprof2) {
		$field = $outputlangs->transcountrynoentities("ProfId2", $fromcompany->country_code);
		if (preg_match('/\((.*)\)/i', $field, $reg)) $field = $reg[1];
		$line3 .= ($line3 ? " - " : "") . $field . ": " . $outputlangs->convToOutputCharset($fromcompany->idprof2);
	}

	// Line 4 of company infos
	// Prof Id 3
	if ($fromcompany->idprof3) {
		$field = $outputlangs->transcountrynoentities("ProfId3", $fromcompany->country_code);
		if (preg_match('/\((.*)\)/i', $field, $reg)) $field = $reg[1];
		$line4 .= ($line4 ? " - " : "") . $field . ": " . $outputlangs->convToOutputCharset($fromcompany->idprof3);
	}
	// Prof Id 4
	if ($fromcompany->idprof4) {
		$field = $outputlangs->transcountrynoentities("ProfId4", $fromcompany->country_code);
		if (preg_match('/\((.*)\)/i', $field, $reg)) $field = $reg[1];
		$line4 .= ($line4 ? " - " : "") . $field . ": " . $outputlangs->convToOutputCharset($fromcompany->idprof4);
	}
	// Prof Id 5
	if ($fromcompany->idprof5) {
		$field = $outputlangs->transcountrynoentities("ProfId5", $fromcompany->country_code);
		if (preg_match('/\((.*)\)/i', $field, $reg)) $field = $reg[1];
		$line4 .= ($line4 ? " - " : "") . $field . ": " . $outputlangs->convToOutputCharset($fromcompany->idprof5);
	}
	// Prof Id 6
	if ($fromcompany->idprof6) {
		$field = $outputlangs->transcountrynoentities("ProfId6", $fromcompany->country_code);
		if (preg_match('/\((.*)\)/i', $field, $reg)) $field = $reg[1];
		$line4 .= ($line4 ? " - " : "") . $field . ": " . $outputlangs->convToOutputCharset($fromcompany->idprof6);
	}
	// IntraCommunautary VAT
	if ($fromcompany->tva_intra != '') {
		$line4 .= ($line4 ? " - " : "") . $outputlangs->transnoentities("VATIntraShort") . ": " . $outputlangs->convToOutputCharset($fromcompany->tva_intra);
	}

	// Set free text font size
	if (!empty($conf->global->ULTIMATEPDF_FREETEXT_FONT_SIZE)) {
		$freetextfontsize = $conf->global->ULTIMATEPDF_FREETEXT_FONT_SIZE;
	}
	$pdf->SetFont('', '', $freetextfontsize);
	$pdf->SetDrawColor(224, 224, 224);

	// The start of the bottom of this page footer is positioned according to # of lines
	$freetextheight = 0;
	if ($line)	// Free text
	{
		//$line="sample text<br>\nfd<strong>sf</strong>sdf<br>\nghfghg<br>";
	    if (empty($conf->global->PDF_ALLOW_HTML_FOR_FREE_TEXT))
		{
			$width = 20000; $align = 'L'; // By default, ask a manual break: We use a large value 20000, to not have automatic wrap. This make user understand, he need to add CR on its text.
    		if (!empty($conf->global->MAIN_USE_AUTOWRAP_ON_FREETEXT)) {
				$width = $pdf->page_largeur - $pdf->marge_gauche - $pdf->marge_droite; 
				$align = 'C';
    		}
		    $freetextheight = $pdf->getStringHeight($width, $line);
		} else {
            $freetextheight = pdfGetHeightForHtmlContent($pdf, dol_htmlentitiesbr($line, 1, 'UTF-8', 0)); // New method (works for HTML content)
            //print '<br>'.$freetextheight;exit;
		}
	}
	
	$marginwithfooter = $marge_basse + $freetextheight + (!empty($line1) ? 3 : 0) + (!empty($line2) ? 3 : 0) + (!empty($line3) ? 3 : 0) + (!empty($line4) ? 3 : 0);
	$posy = $marginwithfooter + 0;

	if ($line)	// Free text
	{
		$pdf->SetXY($dims['lm'], -$posy);
		if (empty($conf->global->PDF_ALLOW_HTML_FOR_FREE_TEXT))   // by default
		{
            $pdf->MultiCell(0, 3, $line, 0, $align, 0);
		} else {
            $pdf->writeHTMLCell($pdf->page_largeur - $pdf->margin_left - $pdf->margin_right, $freetextheight, $dims['lm'], $dims['hk'] - $marginwithfooter, dol_htmlentitiesbr($line, 1, 'UTF-8', 0));
		}
		$posy -= $freetextheight;
	}
	
	$pdf->SetY(-$posy);
	$pdf->line($dims['lm'], $dims['hk'] - $posy, $dims['wk'] - $dims['rm'], $dims['hk'] - $posy);
	$posy--;
	// Setting your personal footer color
	if (!empty($conf->global->ULTIMATE_FOOTERTEXTCOLOR_COLOR)) {
		$footertextcolor =  html2rgb($conf->global->ULTIMATE_FOOTERTEXTCOLOR_COLOR);
	}
	$pdf->SetTextColorArray($footertextcolor);

	if (!empty($line1)) {
		$pdf->SetFont('', 'B', 7);
		$pdf->SetXY($dims['lm'], -$posy);
		$pdf->MultiCell($dims['wk'] - $dims['rm'] - $dims['lm'], 2, $line1, 0, 'C', 0);
		$posy -= 3;
		$pdf->SetFont('', '', 7);
	}

	if (!empty($line2)) {
		$pdf->SetFont('', 'B', 7);
		$pdf->SetXY($dims['lm'], -$posy);
		$pdf->MultiCell($dims['wk'] - $dims['rm'] - $dims['lm'], 2, $line2, 0, 'C', 0);
		$posy -= 3;
		$pdf->SetFont('', '', 7);
	}

	if (!empty($line3)) {
		$pdf->SetXY($dims['lm'], -$posy);
		$pdf->MultiCell($dims['wk'] - $dims['rm'] - $dims['lm'], 2, $line3, 0, 'C', 0);
	}

	if (!empty($line4)) {
		$posy -= 3;
		$pdf->SetXY($dims['lm'], -$posy);
		$pdf->MultiCell($dims['wk'] - $dims['rm'] - $dims['lm'], 2, $line4, 0, 'C', 0);
	}

	//$posy -= 7;
	$pdf->SetXY($dims['lm'], -$posy);
	//Display Thirdparty barcode at top
	if (!empty($object->thirdparty->barcode)) {
		$barcode = $object->thirdparty->barcode;
		$object->thirdparty->fetch_barcode();
		$styleBc = array(
			'position' => '',
			'align' => 'L',
			'stretch' => false,
			'fitwidth' => true,
			'cellfitalign' => '',
			'border' => false,
			'hpadding' => 'auto',
			'vpadding' => 'auto',
			'fgcolor' => array(0, 0, 0),
			'bgcolor' => false, //array(255,255,255),
			'text' => true,
			'font' => 'helvetica',
			'fontsize' => 7,
			'stretchtext' => 4
		);
		// thirdparty barcode
		if (!empty($conf->global->ULTIMATEPDF_GENERATE_DOCUMENTS_WITH_BOTTOM_BARCODE)) {
			if ($barcode)
				$pdf->write1DBarcode($barcode, $object->thirdparty->barcode_type_code, $dims['lm'], -$posy + 285, $dims['wk'] - $dims['rm'], 12, 0.4, $styleBc, 'L');
		}
	}

	// Show page nb only on iso languages (so default Helvetica font)
	if (!empty($conf->global->ULTIMATE_TEXTCOLOR_COLOR)) {
		$textcolor =  html2rgb($conf->global->ULTIMATE_TEXTCOLOR_COLOR);
	}
	$pdf->SetTextColorArray($textcolor);
	$pdf->SetFont('helvetica', '', 7);
	$pdf->SetXY($dims['wk'] - $dims['rm'] - 15, -$posy);
	$pdf->MultiCell(18, 4, $pdf->PageNo() . '/' . $pdf->getAliasNbPages(), 0, 'R', 0);
	$pdf->SetFont($conf->global->MAIN_PDF_FORCE_FONT, '', '');

	return $marginwithfooter;
}

/**
 * pdf_codeContents
 *
 * @return void
 */
function pdf_codeContents()
{
	global $object, $langs;
	$addressLabel = $langs->trans("ThirdParty");
	$codeContents  = 'BEGIN:VCARD' . "\n";
	$codeContents .= 'FN:' . $object->thirdparty->name . "\n";
	$codeContents .= 'TEL;WORK;VOICE:' . $object->thirdparty->phone . "\n";
	$codeContents .= 'ADR;TYPE=work;' .
		'LABEL="' . $addressLabel . '":'
		. $object->thirdparty->address . ';'
		. $object->thirdparty->town . ';'
		. $object->thirdparty->zip . ';'
		. $object->thirdparty->country
		. "\n";
	$codeContents .= 'EMAIL:' . $object->thirdparty->email . "\n";
	$codeContents .= 'END:VCARD';

	return $codeContents;
}

/**
 * pdf_mycompCodeContents
 *
 * @return void
 */
function pdf_mycompCodeContents()
{
	global $mysoc, $langs;
	$addressLabel = $langs->trans("Company");
	$mycompCodeContents  = 'BEGIN:VCARD' . "\n";
	$mycompCodeContents .= 'FN:' . $mysoc->name . "\n";
	$mycompCodeContents .= 'TEL;WORK;VOICE:' . $mysoc->phone . "\n";
	$mycompCodeContents .= 'ADR;TYPE=work;' .
		'LABEL="' . $addressLabel . '":'
		. $mysoc->address . ';'
		. $mysoc->town . ';'
		. $mysoc->zip . ';'
		. $mysoc->country
		. "\n";
	$mycompCodeContents .= 'EMAIL:' . $mysoc->email . "\n";
	$mycompCodeContents .= 'END:VCARD';

	return $mycompCodeContents;
}

/**
 * pdf_codeOrderLink
 *
 * @return void
 */
function pdf_codeOrderLink()
{
	global $object;

	$urlwithroot = DOL_MAIN_URL_ROOT;
	$codeOrderLink  = $urlwithroot . '/commande/card.php?id=' . $object->id;

	return $codeOrderLink;
}

/**
 * pdf_invoiceCodeContents
 *
 * @return void
 */
function pdf_invoiceCodeContents()
{
	global $object, $mysoc, $db, $langs;

	$deja_regle = $object->getSommePaiement();
	$resteapayer = price2num($object->total_ttc - $deja_regle, 'MT');

	$staticAccount = new Account($db);
	$staticAccount->id = $object->fk_account;
	$staticAccount->fetch($object->fk_account);
	$codeContents  = 'BCD' . "\n"; //Service Tag
	$codeContents .= '001' . "\n"; //Version
	$codeContents .= '1' . "\n"; //Character set
	$codeContents .= 'SCT' . "\n"; //Identification
	$codeContents .=  $staticAccount->bic . "\n"; //Bic
	$codeContents .=  $mysoc->name . "\n"; //Name
	$codeContents .=  $staticAccount->iban . "\n"; //Iban
	$codeContents .=  $object->multicurrency_code . $resteapayer . "\n"; //Amount
	$codeContents .=  $langs->transnoentities('SepaCreditTransfer') . "\n"; //Purpose
	$codeContents .=  '' . "\n"; //Remittance (Text) 
	$codeContents .=  $object->ref  . "\n"; //Information 

	return $codeContents;
}

/**
 * pdf_invoiceCodeContents
 *
 * @return void
 */
function pdf_supplierinvoiceCodeContents()
{
	global $object, $langs;

	$codeContents = 'Seller:' . $object->thirdparty->name . "\n";
	$codeContents .= 'VAT Num:' . $object->thirdparty->tva_intra . "\n";
	$codeContents .= 'Time:' . dol_print_date($object->datec, "dayhour") . "\n";
	$codeContents .= 'Invoice Amt:' . price($object->total_ttc) . "\n";
	$codeContents .= 'VAT Amt:' . price($object->total_tva) . "\n";

	return $codeContents;
}


/**
 * Return height to use for Logo onto PDF
 *
 * @param	string		$logo		Full path to logo file to use
 * @param	bool		$url		Image with url (true or false)
 * @return	number
 */
function pdf_getUltimateHeightForLogo($logo, $url = false)
{
	global $conf;

	include_once DOL_DOCUMENT_ROOT . "/core/lib/pdf.lib.php";
	$formatarray = pdf_getFormat();
	$page_largeur = $formatarray['width'];
	$marge_gauche = isset($conf->global->ULTIMATE_PDF_MARGIN_LEFT) ? $conf->global->ULTIMATE_PDF_MARGIN_LEFT : 10;
	$marge_droite = isset($conf->global->ULTIMATE_PDF_MARGIN_RIGHT) ? $conf->global->ULTIMATE_PDF_MARGIN_RIGHT : 10;
	$logo_height = isset($conf->global->ULTIMATE_LOGO_HEIGHT) ? $conf->global->ULTIMATE_LOGO_HEIGHT : 30;
	$maxwidth = ($page_largeur - $marge_gauche - $marge_droite) / 2;

	include_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';
	$tmp = dol_getImageSize($logo, $url);
	if ($tmp['height']) {
		$width = round($logo_height * $tmp['width'] / $tmp['height']);
		if ($width > $maxwidth) $logo_height = $logo_height * $maxwidth / $width;
	}
	//print $tmp['width'].' '.$tmp['height'].' '.$width; exit;
	if ($logo_height > 40) $logo_height = 40;
	return $logo_height;
}

/**
 * Return height to use for OtherLogo onto PDF
 *
 * @param	string		$logo		Full path to logo file to use
 * @param	bool		$url		Image with url (true or false)
 * @return	number
 */
function pdf_getUltimateHeightForOtherLogo($otherlogo, $url = false)
{
	global $conf;

	include_once DOL_DOCUMENT_ROOT . "/core/lib/pdf.lib.php";
	$formatarray = pdf_getFormat();
	$page_largeur = $formatarray['width'];
	$marge_gauche = isset($conf->global->ULTIMATE_PDF_MARGIN_LEFT) ? $conf->global->ULTIMATE_PDF_MARGIN_LEFT : 10;
	$marge_droite = isset($conf->global->ULTIMATE_PDF_MARGIN_RIGHT) ? $conf->global->ULTIMATE_PDF_MARGIN_RIGHT : 10;
	$logo_height = isset($conf->global->ULTIMATE_OTHERLOGO_HEIGHT) ? $conf->global->ULTIMATE_OTHERLOGO_HEIGHT : 30;
	$maxwidth = ($page_largeur - $marge_gauche - $marge_droite) / 2;

	include_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';
	$tmp = dol_getImageSize($otherlogo, $url);
	if ($tmp['height']) {
		$width = round($logo_height * $tmp['width'] / $tmp['height']);
		if ($width > $maxwidth) $logo_height = $logo_height * $maxwidth / $width;
	}
	if ($logo_height > 40) $logo_height = 40;
	return $logo_height;
}

/**
 *	Return invoice line weight
 *
 *	@param	Object		$object				Object
 *	@param	int			$i					Current line number
 *  @param  Translate	$outputlangs		Object langs for output
 *  @param	int			$hidedetails		Hide details (0=no, 1=yes, 2=just special lines)
 * 	@return	void
 */
function pdf_getlineweight($object, $i, $outputlangs, $hidedetails = 0)
{
	global $db, $langs, $hookmanager;

	if ($object->ref == 'SPECIMEN') {
		$weight = '1,5 Kg';
		return $weight;
	}

	$reshook = 0;
	if (is_object($hookmanager) && ($object->lines[$i]->product_type == 9)) {
		$special_code = $object->lines[$i]->special_code;
		if (!empty($object->lines[$i]->fk_parent_line)) $special_code = $object->getSpecialCode($object->lines[$i]->fk_parent_line);
		$parameters = array('i' => $i, 'outputlangs' => $outputlangs, 'hidedetails' => $hidedetails, 'special_code' => $special_code);
		$action = '';
		$reshook = $hookmanager->executeHooks('pdf_getlineweight', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks

		if (!empty($hookmanager->resPrint)) $result = $hookmanager->resPrint;
	} else {

		if (empty($hidedetails) || $hidedetails > 1) {
			$langs->load('other');

			include_once(DOL_DOCUMENT_ROOT . '/core/lib/product.lib.php');

			$sql = 'SELECT p.weight,p.weight_units';
			$sql .= ' FROM ' . MAIN_DB_PREFIX . $object->table_element_line . ' as l';
			$sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'product as p ON l.fk_product = p.rowid';
			$sql .= ' WHERE l.rowid = ' . $object->lines[$i]->rowid;

			dol_syslog('ultimatepdf.lib.php::pdf_getlineweight sql=' . $sql, LOG_DEBUG);
			$result = $db->query($sql);
			if ($result) {
				$objw = $db->fetch_object($result);
				$weight = ($objw->weight * $object->lines[$i]->qty) . " " . measuring_units_string($objw->weight_units, "weight", 0, 1);
			} else {
				$error = $db->lasterror();
				dol_syslog('ultimatepdf.lib.php::pdf_getlineweight ' . $error, LOG_ERR);
			}

			return $weight;
		}
	}
}

/**
 *	Return total Qty
 *
 *	@param	Object		$object				Object
 *  @param  Translate	$outputlangs		Object langs for output
 *  @param	int			$hidedetails		Hide details (0=no, 1=yes, 2=just special lines)
 * 	@return	void
 */
function pdf_getqty($object, $outputlangs, $hidedetails = 0)
{
	global $db, $langs, $hookmanager;

	if (is_object($hookmanager)) {
		$parameters = array('outputlangs' => $outputlangs, 'hidedetails' => $hidedetails);
		$action = '';
		$returnhook = $hookmanager->executeHooks('pdf_getqty', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks
	}
	if ($returnhook == 0) {

		if (empty($hidedetails) || $hidedetails > 1) {
			$langs->load('other');

			include_once(DOL_DOCUMENT_ROOT . '/core/lib/product.lib.php');

			$sql = 'SELECT sum(l.qty) as totalqty';
			$sql .= ' FROM ' . MAIN_DB_PREFIX . $object->table_element_line . ' as l';
			$sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'product as p ON l.fk_product = p.rowid';
			$sql .= ' WHERE l.' . $object->fk_element . ' = ' . $object->id;

			$qty = 0;

			dol_syslog('ultimatepdf.lib.php::pdf_getqty sql=' . $sql, LOG_DEBUG);
			$result = $db->query($sql);
			if ($result) {
				$objqty = $db->fetch_object($result);
				$qty = $objqty->totalqty;
			} else {
				$error = $db->lasterror();
				dol_syslog('ultimatepdf.lib.php::pdf_getqty ' . $error, LOG_ERR);
			}
			return $qty;
		}
	} else {
		return $returnhook;
	}
}

/**
 * qtyAlreadyShipped
 *
 * @param  mixed $object
 * @param  mixed $fk_product
 * @return void
 */
function qtyAlreadyShipped($object, $fk_product)
{
	global $db, $conf;

	$origin 	= $object->origin;
	$origin_id 	= $object->origin_id;
	if (!empty($conf->$origin->enabled)) {
		$classname = ucfirst($origin);
		$linkedobject = new $classname($db);
		$result = $linkedobject->fetch($origin_id);
		if ($result >= 0) {
			$text = $linkedobject->ref;
		}
	}

	$staticOrder = new Commande($db);

	if ($staticOrder->fetch($origin_id) > 0) {
		$staticOrder->fk_product = $fk_product;
		$staticOrder->loadExpeditions(1);
		$qtyAlreadyShipped = implode(",", $staticOrder->expeditions);

		return $qtyAlreadyShipped;
	} else {
		/* Commande non trouvee */
		print "Commande inexistante";
	}
}

/**
 * Return if pdf file is protected/encrypted
 *
 * @param   string		$pathoffile		Path of file
 * @return  boolean     			    True or false
 */
function updf_getEncryption($pathoffile)
{
	require_once TCPDF_PATH . 'tcpdf_parser.php';

	$isencrypted = false;

	$content = file_get_contents($pathoffile);

	ob_start();
	@($parser = new \TCPDF_PARSER(ltrim($content)));
	list($xref, $data) = $parser->getParsedData();
	unset($parser);
	ob_end_clean();

	if (isset($xref['trailer']['encrypt'])) {
		$isencrypted = true;	// Secured pdf file are currently not supported
	}

	if (empty($data)) {
		$isencrypted = true;	// Object list not found. Possible secured file
	}

	return $isencrypted;
}

/**
 *	Return signature file
 *
 *	@param	string		$thisstr			after this string
 *	@param	string		$inthatstr			to find in that string
 *  @return	string							Return signature file
 */
function after($thisstr, $inthatstr)
{
	if (!is_bool(strpos($inthatstr, $thisstr)))
		return substr($inthatstr, strpos($inthatstr, $thisstr) + strlen($thisstr));
}

/**
 *	Return from the first occurrence of '$thatstr'
 *
 *	@param	string		$thatstr			before this string
 *	@param	string		$inthatstr			to find in that string
 *  @return	string							Return signature file
 */
function before($thatstr, $inthatstr)
{
	return substr($inthatstr, 0, strpos($inthatstr, $thatstr));
}

/**
 *	Return signature file
 *
 *	@param	string		$thisstr			after this string
 *	@param	string		$thatstr			before that string
 *	@param	string		$inthatstr			to find in that string
 *  @return	string							Return signature file
 */
function between($thisstr, $thatstr, $inthatstr)
{
	return before($thatstr, after($thisstr, $inthatstr));
}

/**
 * get_ean13_key
 *
 * @param  mixed $digits
 *
 * @return void
 */
function get_ean13_key($digits)
{
	if(strlen($digits)!=12){
		return FALSE;
	}
	// 1. Add the values of the digits in the even-numbered positions: 2, 4, 6, etc.
	$even_sum       = $digits[1] + $digits[3] + $digits[5] + $digits[7] + $digits[9] + $digits[11];
	// 2. Multiply this result by 3.
	$even_sum_three = $even_sum * 3;
	// 3. Add the values of the digits in the odd-numbered positions: 1, 3, 5, etc.
	$odd_sum        = $digits[0] + $digits[2] + $digits[4] + $digits[6] + $digits[8] + $digits[10];
	// 4. Sum the results of steps 2 and 3.
	$total_sum      = $even_sum_three + $odd_sum;
	// 5. The check character is the smallest number which, when added to the result in step 4,  produces a multiple of 10.
	$next_ten       = (ceil($total_sum / 10)) * 10;
	return $next_ten - $total_sum;
}

/**
 *   	Show header of page for PDF generation
 *
 *   	@param      TCPDF		$pdf     		Object PDF
 *      @param      Translate	$outputlangs	Object lang for output
 * 		@param		int			$page_height	Height of page
 *      @return		void
 */
function pdf_new_pagehead(&$pdf, $outputlangs, $page_height)
{
	global $conf;

	$id = $conf->global->ULTIMATE_DESIGN;
	// Add a background image on document
	if (!empty($conf->global->MAIN_USE_BACKGROUND_ON_PDF))		// Warning, this option make TCPDF generation being crazy and some content disappeared behind the image
	{
		$pdf->SetAutoPageBreak(0, 0); // Disable auto pagebreak before adding image
		//var_dump($conf->ultimatepdf->dir_output . $conf->global->ULTIMATE_USE_BACKGROUND_ON_PDF_FILE);exit;
		// set alpha to semi-transparency
		$pdf->SetAlpha($conf->global->BACKGROUND_IMAGE_TRANSPARENCY);
		$pdf->Image($conf->ultimatepdf->dir_output . '/background/'. $id . '/' . $conf->global->ULTIMATE_USE_BACKGROUND_ON_PDF, (isset($conf->global->ULTIMATE_USE_BACKGROUND_ON_PDF_X) ? $conf->global->ULTIMATE_USE_BACKGROUND_ON_PDF_X : 0), (isset($conf->global->ULTIMATE_USE_BACKGROUND_ON_PDF_Y) ? $conf->global->ULTIMATE_USE_BACKGROUND_ON_PDF_Y : 0), 210, $page_height, '', '', '', false, 300, '', false, false, 0);
		// restore full opacity
		$pdf->SetAlpha(1);
		$pdf->SetAutoPageBreak(1, 0); // Restore pagebreak
		$pdf->setPageMark();
	}
}

function get_barcode(&$pdf, $object, $curX, $curY)
{
	global $db;

	$pdf->SetFont('helvetica', '', 10);
	$nblines = count($object->lines);
	//Barcode style
	$styleBc = array(
		'position' => '',
		'align' => '',
		'stretch' => false,
		'fitwidth' => true,
		'cellfitalign' => '',
		'border' => false,
		'hpadding' => 'auto',
		'vpadding' => 'auto',
		'fgcolor' => array(33, 33, 33),
		'bgcolor' => false, //array(255,255,255),
		'text' => true,
		'font' => 'helvetica',
		'fontsize' => 7,
		'stretchtext' => 4
	);
	for ($i = 0; $i < $nblines; $i++) {
		if (!empty($object->lines[$i]->fk_product)) {
			$product = new Product($db);
			$result = $product->fetch($object->lines[$i]->fk_product, '', '', '');
			$product->fetch_barcode();
		}
		//function get_ean13_key(string $digits)
		$digits = $product->barcode;
		if ($product->barcode_type_code == 'EAN13') {
			$code = get_ean13_key($digits);
			$pdf->write1DBarcode((int)$product->barcode . $code, $product->barcode_type_code, $curX, $curY, '', 12, 0.4, $styleBc, 'L');
		} else {
			$pdf->write1DBarcode((int)$product->barcode, $product->barcode_type_code, $curX, $curY, '', 12, 0.4, $styleBc, 'L');
		};
	}
}

/**
 * Split all of the pages from a larger PDF file into single-page PDF files.
 *
 * @param string $filename The filename of the PDF to split
 * @param string $directory The output directory for the new PDF files
 *
 * @return void
 */
function split_pdf(string $filename, string $directory)
{
	require_once DOL_DOCUMENT_ROOT.'/includes/tecnickcom/tcpdf/tcpdf.php';
	require_once('vendor/setasign/Fpdi/src/autoload.php');
	$filename = $directory.$filename;
    $tcpdf = new setasign\Fpdi\Tcpdf\Fpdi();
	if (file_exists($filename)) {
		$pageCount = $tcpdf->setSourceFile($filename);
		$file = pathinfo($filename, PATHINFO_FILENAME);
	}

	// Split each page into a new PDF
	for ($i = 1; $i <= $pageCount; $i++) {
		$newPdf = new \setasign\Fpdi\Tcpdf\Fpdi();
		$newPdf->addPage();
		$newPdf->setSourceFile($filename);
		$newPdf->useTemplate($newPdf->importPage($i));
		$newFilename = sprintf('%s/%s_%s.pdf', $directory, $file, $i);
		$newPdf->output($newFilename, 'F');
	}
}

?>