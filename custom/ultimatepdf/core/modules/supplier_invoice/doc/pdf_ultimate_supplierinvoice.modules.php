<?php
/* Copyright (C) 2010-2011      Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2010-2012 		Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2011-2022		Philippe Grand       <philippe.grand@atoo-net.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 *	\file       ultimatepdf/core/modules/supplier_invoice/doc/pdf_ultimate_supplierinvoice.modules.php
 *	\ingroup    fournisseur
 *	\brief      Class file to generate the supplier invoices with the pdf_ultimate_supplierinvoice model
 */

require_once DOL_DOCUMENT_ROOT . '/core/modules/supplier_invoice/modules_facturefournisseur.php';
require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.facture.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
dol_include_once("/ultimatepdf/lib/ultimatepdf.lib.php");
dol_include_once("/ultimatepdf/class/ultimateBarcode.trait.class.php");


/**
 *	Class to generate the supplier invoices PDF with the ultimate_supplierinvoice model
 */
class pdf_ultimate_supplierinvoice extends ModelePDFSuppliersInvoices
{
	use UltimateBarcode;

	/**
	 * @var DoliDb Database handler
	 */
	public $db;

	/**
	 * @var string model name
	 */
	public $name;

	/**
	 * @var string model description (short text)
	 */
	public $description;

	/**
     * @var int Save the name of generated file as the main doc when generating a doc with this template
     */
    public $update_main_doc_field;

	/**
	 * @var string document type
	 */
	public $type;

	/**
	 * @var array() Minimum version of PHP required by module.
	 * e.g.: PHP ≥ 5.6 = array(5, 6)
	 */
	public $phpmin = array(5, 6);

	/**
	 * Dolibarr version of the loaded document
	 * @public string
	 */
	public $version = 'dolibarr';

	/**
	 * @var int page_largeur
	 */
	public $page_largeur;

	/**
	 * @var int page_hauteur
	 */
	public $page_hauteur;

	/**
	 * @var array format
	 */
	public $format;

	/**
	 * @var int marge_gauche
	 */
	public $marge_gauche;

	/**
	 * @var int marge_droite
	 */
	public $marge_droite;

	/**
	 * @var int marge_haute
	 */
	public $marge_haute;

	/**
	 * @var int marge_basse
	 */
	public $marge_basse;

	/**
	 * @var array style
	 */
	public $style;

	/**
	 * @var
	 */
	public $roundradius;

	/**
	 * @var string logo_height
	 */
	public $logo_height;

	/**
	 * @var int number column width
	 */
	public $number_width;

	/**
	 * Issuer
	 * @var Societe
	 */
	public $emetteur;


	/**
	 *	Constructor
	 *
	 *  @param	DoliDB		$db     	Database handler
	 */
	public function __construct($db)
	{
		global $conf, $langs, $mysoc;

		// Translations
		$langs->loadLangs(array("main", "bills", "ultimatepdf@ultimatepdf"));

		$this->db = $db;
		$this->name = "ultimate_supplierinvoice";
		$this->description = $langs->trans('SuppliersInvoiceModel');
		$this->update_main_doc_field = 1;		// Save the name of generated file as the main doc when generating a doc with this template

		// Dimension page pour format A4
		$this->type = 'pdf';
		$formatarray = pdf_getFormat();
		$this->page_largeur = $formatarray['width'];
		$this->page_hauteur = $formatarray['height'];
		$this->format = array($this->page_largeur, $this->page_hauteur);
		$this->marge_gauche = isset($conf->global->ULTIMATE_PDF_MARGIN_LEFT) ? $conf->global->ULTIMATE_PDF_MARGIN_LEFT : 10;
		$this->marge_droite = isset($conf->global->ULTIMATE_PDF_MARGIN_RIGHT) ? $conf->global->ULTIMATE_PDF_MARGIN_RIGHT : 10;
		$this->marge_haute = isset($conf->global->ULTIMATE_PDF_MARGIN_TOP) ? $conf->global->ULTIMATE_PDF_MARGIN_TOP : 10;
		$this->marge_basse = isset($conf->global->ULTIMATE_PDF_MARGIN_BOTTOM) ? $conf->global->ULTIMATE_PDF_MARGIN_BOTTOM : 10;

		$this->option_logo = 1;                    // Affiche logo
		$this->option_tva = 1;                     // Gere option tva FACTURE_TVAOPTION
		$this->option_modereg = 1;                 // Affiche mode reglement
		$this->option_condreg = 1;                 // Affiche conditions reglement
		$this->option_codeproduitservice = 1;      // Affiche code produit-service
		$this->option_multilang = 1;               // Dispo en plusieurs langues

		$this->franchise = !$mysoc->tva_assuj;

		$dashdotted =  (!empty($conf->global->ULTIMATE_DASH_DOTTED)) ?
		$conf->global->ULTIMATE_DASH_DOTTED : 0;

		$bordercolor =  (!empty($conf->global->ULTIMATE_BORDERCOLOR_COLOR)) ?
		html2rgb($conf->global->ULTIMATE_BORDERCOLOR_COLOR) : array(0, 63, 127);	
		
		$this->border_style = array('width' => 0.2, 'cap' => 'butt', 'join' => 'miter', 'dash' => $dashdotted, 'color' => $bordercolor);

		$this->tabTitleHeight = 8; // default height

		$this->tva = array();
		$this->localtax1 = array();
		$this->localtax2 = array();
		$this->atleastoneratenotnull = 0;
		$this->atleastonediscount = 0;
		$this->atleastoneref = 0;
	}

	/**
	 *  Function to build pdf onto disk
	 *
	 *  @param		FactureFournisseur	$object	 object to generate
	 *  @param		Translate	$outputlangs	Lang output object
	 *  @param		string	$srctemplatepath	Full path of source filename for generator using a template file
	 *  @param		int		$hidedetails		Do not show line details
	 *  @param		int		$hidedesc			Do not show desc
	 *  @param		int		$hideref			Do not show ref
	 *  @return 	int             			1=OK, 0=KO
	 */
	function write_file($object, $outputlangs = '', $srctemplatepath = '', $hidedetails = 0, $hidedesc = 0, $hideref = 0)
	{
		global $user, $langs, $conf, $mysoc, $db, $hookmanager, $nblines; //is $db usefull?

		$textcolor =  (!empty($conf->global->ULTIMATE_TEXTCOLOR_COLOR)) ?
		html2rgb($conf->global->ULTIMATE_TEXTCOLOR_COLOR) : array(25, 25, 25);

		$bgcolor =  (!empty($conf->global->ULTIMATE_BGCOLOR_COLOR)) ?
		html2rgb($conf->global->ULTIMATE_BGCOLOR_COLOR) : array(170, 212, 255);

		$roundradius =  (!empty($conf->global->ULTIMATE_SET_RADIUS)) ?
		$conf->global->ULTIMATE_SET_RADIUS : 1;

		$bordercolor =  (!empty($conf->global->ULTIMATE_BORDERCOLOR_COLOR)) ?
		html2rgb($conf->global->ULTIMATE_BORDERCOLOR_COLOR) : array(0, 63, 127);

		$dashdotted =  (!empty($conf->global->ULTIMATE_DASH_DOTTED)) ?
		$conf->global->ULTIMATE_DASH_DOTTED : 0;

		$hidedesc = (!empty($conf->global->ULTIMATE_GENERATE_SUPPLIERINVOICES_HIDE_PRODUCT_DESC)) ? 1 : 0;

		$this->border_style = array('width' => 0.2, 'cap' => 'butt', 'join' => 'miter', 'dash' => $dashdotted, 'color' => $bordercolor);

		// Get source company
		if (!is_object($object->thirdparty)) $object->fetch_thirdparty();
		if (!is_object($object->thirdparty)) $object->thirdparty = $mysoc;	// If fetch_thirdparty fails, object has no socid (specimen)
		$this->emetteur = $object->thirdparty;
		if (!$this->emetteur->country_code) $this->emetteur->country_code = substr($langs->defaultlang, -2);    // By default, if was not defined

		dol_syslog("write_file outputlangs->defaultlang=" . (is_object($outputlangs) ? $outputlangs->defaultlang : 'null'));

		if (!is_object($outputlangs)) $outputlangs = $langs;

		// Translations
		$outputlangs->loadLangs(array("main", "dict", "companies", "bills", "products", "ultimatepdf@ultimatepdf"));

		if (!empty($conf->global->PDF_USE_ALSO_LANGUAGE_CODE) && $outputlangs->defaultlang != $conf->global->PDF_USE_ALSO_LANGUAGE_CODE) {
	    	global $outputlangsbis;
	    	$outputlangsbis = new Translate('', $conf);
	    	$outputlangsbis->setDefaultLang($conf->global->PDF_USE_ALSO_LANGUAGE_CODE);
	    	$outputlangsbis->loadLangs(array("main", "bills", "products", "dict", "companies"));
	    }

		$nblines = count($object->lines);

		$hidetop = 0;
		if (!empty($conf->global->MAIN_PDF_DISABLE_COL_HEAD_TITLE)) {
			$hidetop = $conf->global->MAIN_PDF_DISABLE_COL_HEAD_TITLE;
		}

		// Loop on each lines to detect if there is at least one image to show
		$realpatharray = array();
		if (!empty($conf->global->ULTIMATE_GENERATE_SUPPLIERINVOICES_WITH_PICTURE)) {
			$objphoto = new Product($this->db);

			for ($i = 0; $i < $nblines; $i++) {
				if (empty($object->lines[$i]->fk_product)) continue;

				$objphoto->fetch($object->lines[$i]->fk_product);

				if (!empty($conf->global->PRODUCT_USE_OLD_PATH_FOR_PHOTO)) {
					$pdir[0] = get_exdir($objphoto->id, 2, 0, 0, $objphoto, 'product') . $objphoto->id . "/photos/";
					$pdir[1] = dol_sanitizeFileName($objphoto->ref) . '/';
				} else {
					$pdir[0] = dol_sanitizeFileName($objphoto->ref) . '/';				// default
					$pdir[1] = get_exdir($objphoto->id, 2, 0, 0, $objphoto, 'product') . $objphoto->id . "/photos/";	// alternative
				}

				$arephoto = false;
				foreach ($pdir as $midir) {
					if (!$arephoto) {
						$dir = $conf->product->dir_output . '/' . $midir;

						foreach ($objphoto->liste_photos($dir, 1) as $key => $obj) {
							if (empty($conf->global->CAT_HIGH_QUALITY_IMAGES))		// If CAT_HIGH_QUALITY_IMAGES not defined, we use thumb if defined and then original photo
							{
								if ($obj['photo_vignette']) {
									$filename = $obj['photo_vignette'];
								} else {
									$filename = $obj['photo'];
								}
							} else {
								$filename = $obj['photo'];
							}

							$realpath = $dir . $filename;
							$arephoto = true;
						}
					}
				}
				if ($realpath && $arephoto) $realpatharray[$i] = $realpath;
			}
		}
		if (count($realpatharray) == 0) $this->posxpicture = $this->posxtva;

		if ($conf->fournisseur->facture->dir_output) {
			$object->fetch_thirdparty();

			$deja_regle = $object->getSommePaiement(
			($conf->multicurrency->enabled && $object->multicurrency_tx != 1) ? 1 : 0);
			$amount_credit_notes_included = $object->getSumCreditNotesUsed(($conf->multicurrency->enabled && $object->multicurrency_tx != 1) ? 1 : 0);
			$amount_deposits_included = $object->getSumDepositsUsed(($conf->multicurrency->enabled && $object->multicurrency_tx != 1) ? 1 : 0);

			// Definition of $dir and $file
			if ($object->specimen) {
				$dir = $conf->fournisseur->facture->dir_output;
				$file = $dir . "/SPECIMEN.pdf";
			} else {
				$objectref = dol_sanitizeFileName($object->ref);
				$objectrefsupplier = dol_sanitizeFileName($object->ref_supplier);
				$dir = $conf->fournisseur->facture->dir_output . '/' . get_exdir($object->id, 2, 0, 0, $object, 'invoice_supplier') . $objectref;
				$file = $dir . "/" . $objectref . ".pdf";
				if (!empty($conf->global->SUPPLIER_REF_IN_NAME)) $file = $dir . "/" . $objectref . ($objectrefsupplier ? "_" . $objectrefsupplier : "") . ".pdf";
			}

			if (!file_exists($dir)) {
				if (dol_mkdir($dir) < 0) {
					$this->error = $langs->transnoentities("ErrorCanNotCreateDir", $dir);
					return 0;
				}
			}

			if (file_exists($dir)) {
				// Add pdfgeneration hook
				if (!is_object($hookmanager)) {
					include_once DOL_DOCUMENT_ROOT . '/core/class/hookmanager.class.php';
					$hookmanager = new HookManager($this->db);
				}
				$hookmanager->initHooks(array('pdfgeneration'));
				$parameters = array('file' => $file, 'object' => $object, 'outputlangs' => $outputlangs);
				global $action;
				$reshook = $hookmanager->executeHooks('beforePDFCreation', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks

				// Set nblines with the new facture lines content after hook
				$nblines = count($object->lines);
				$nbpayments = count($object->getListOfPayments());

				// Create pdf instance
				$pdf = pdf_getInstance($this->format);
				$default_font_size = pdf_getPDFFontSize($outputlangs);  // Must be after pdf_getInstance
				$pdf->SetAutoPageBreak(1, 0);

				$heightforinfotot = 40 + (4 * $nbpayments);	// Height reserved to output the info and total part and payment part
				$heightforfreetext = (isset($conf->global->MAIN_PDF_FREETEXT_HEIGHT) ? $conf->global->MAIN_PDF_FREETEXT_HEIGHT : 12);	// Height reserved to output the free text on last page

				$heightforfooter = (int)$this->marge_basse + (empty($conf->global->MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS) ? 12 : 22);	// Height reserved to output the footer (value include bottom margin)

				if (class_exists('TCPDF')) {
					$pdf->setPrintHeader(false);
					$pdf->setPrintFooter(false);
				}
				$pdf->SetFont(pdf_getPDFFont($outputlangs));

				// Set path to the background PDF File
				if (($conf->global->ULTIMATEPDF_ADD_PDF_BACKGROUND) && ($conf->global->ULTIMATE_DESIGN)) {
					$id = $conf->global->ULTIMATE_DESIGN;
					if (file_exists($conf->ultimatepdf->dir_output . '/backgroundpdf/' . $id . '/' . $conf->global->ULTIMATEPDF_ADD_PDF_BACKGROUND)) {
						$pagecount = $pdf->setSourceFile($conf->ultimatepdf->dir_output . '/backgroundpdf/' . $id . '/' . $conf->global->ULTIMATEPDF_ADD_PDF_BACKGROUND);
						$tplidx = $pdf->importPage(1);
					}
				}

				$pdf->Open();
				$pagenb = 0;
				$pdf->SetDrawColor(128, 128, 128);

				$pdf->SetTitle($outputlangs->convToOutputCharset($object->ref));
				$pdf->SetSubject($outputlangs->transnoentities("PdfInvoiceTitle"));
				$pdf->SetCreator("Dolibarr " . DOL_VERSION);
				$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
				$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref) . " " . $outputlangs->transnoentities("PdfInvoiceTitle"));
				if (!empty($conf->global->MAIN_DISABLE_PDF_COMPRESSION)) $pdf->SetCompression(false);

				$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right

				// Set $this->atleastoneref if you have at least one ref
				for ($i = 0; $i < $nblines; $i++) {
					if ($object->lines[$i]->product_ref) {
						$this->atleastoneref++;
					}
				}

				// Set $this->atleastonediscount if you have at least one discount
				for ($i = 0; $i < $nblines; $i++) {
					if ($object->lines[$i]->remise_percent) {
						$this->atleastonediscount++;
					}
				}

				// New page
				$pdf->AddPage();
				if (!empty($tplidx)) $pdf->useTemplate($tplidx);
				$pagenb++;

				$this->_pagehead($pdf, $object, 1, $outputlangs);
				$pdf->SetFont('', '', $default_font_size - 2);
				$pdf->MultiCell(0, 3, '');		// Set interline to 3
				$pdf->SetTextColorArray($textcolor);

				//catch logo height
				// Other Logo
				$id = $conf->global->ULTIMATE_DESIGN;
				$upload_dir	= $conf->ultimatepdf->dir_output . '/otherlogo/' . $id . '/';
				$filearray = dol_dir_list($upload_dir, "files");
				$filename = $filearray[0]['name'];
				if (!empty($filename)) {
					$otherlogo = $conf->ultimatepdf->dir_output . '/otherlogo/' . $id . '/' . $filename;
					if (is_readable($otherlogo)) {
						$logo_height = max(pdf_getUltimateHeightForOtherLogo($otherlogo, true), 20);
					}
				} else {
					// MyCompany logo
					$logo = $conf->mycompany->dir_output . '/logos/' . $mysoc->logo;
					if (is_readable($logo)) {
						$logo_height = max(pdf_getUltimateHeightForLogo($logo, true), 20);
					}
				}

				//Set $hautcadre
				if (($conf->global->ULTIMATE_PDF_SUPPLIERINVOICE_ADDALSOTARGETDETAILS == 1) || (!empty($conf->global->MAIN_INFO_SOCIETE_NOTE) && !empty($this->emetteur->note_private)) || (!empty($conf->global->MAIN_INFO_TVAINTRA) && !empty($this->emetteur->tva_intra))) {
					$hautcadre = 48;
				} else {
					$hautcadre = 40;
				}

				$tab_top = $this->marge_haute + $logo_height + $hautcadre + 15;

				$tab_top_newpage = (empty($conf->global->ULTIMATE_SUPPLIERINVOICES_PDF_DONOTREPEAT_HEAD) ? $this->marge_haute + $logo_height + 20 : 10);

				$tab_width = $this->page_largeur - $this->marge_gauche - $this->marge_droite;
				if ($roundradius == 0) {
					$roundradius = 0.1; //RoundedRect don't support $roundradius to be 0
				}

				// Incoterm
				$height_incoterms = 0;
				$tab_top = $pdf->GetY();
				if ($conf->incoterm->enabled) {
					$desc_incoterms = $object->getIncotermsForPDF();
					if ($desc_incoterms) {
						$pdf->SetFont('', '', $default_font_size - 2);
						$pdf->writeHTMLCell($tab_width, 3, $this->marge_gauche + 1, $tab_top + 1, dol_htmlentitiesbr($desc_incoterms), 0, 1);

						$nexY = $pdf->GetY();
	                    $height_incoterms = $nexY - $tab_top;

						// Rect prend une longueur en 3eme param
						$pdf->SetDrawColor(192, 192, 192);
						$pdf->RoundedRect($this->marge_gauche, $tab_top, $tab_width, $height_incoterms + 1, $roundradius, '1111', 'S', $this->border_style, $bgcolor);
						$tab_top = $nexY + 10;
					}
				}

				// Display notes
				$notetoshow = empty($object->note_public) ? '' : $object->note_public;
				if (!empty($conf->global->MAIN_ADD_SALE_REP_SIGNATURE_IN_SUPPLIERINVOICES_NOTE)) {
					// Get first sale rep
					if (is_object($object->thirdparty)) {
						$salereparray = $object->thirdparty->getSalesRepresentatives($user);
						$salerepobj = new User($this->db);
						$salerepobj->fetch($salereparray[0]['id']);
						if (!empty($salerepobj->signature)) $notetoshow = dol_concatdesc($notetoshow, $salerepobj->signature);
					}
				}

				// Extrafields in note
				$extranote = $this->getExtrafieldsInHtml($object, $outputlangs);
				if (!empty($extranote)) {
					$notetoshow = dol_concatdesc($notetoshow, $extranote);
				}

				$tab_height = $this->page_hauteur - $tab_top_newpage - $heightforinfotot - $heightforfreetext - $heightforfooter;

				$pagenb = $pdf->getPage();
				if ($notetoshow && empty($conf->global->MAIN_PUBLIC_NOTE_IN_ADDRESS)) {
					$pageposbeforenote = $pagenb;
					if ($desc_incoterms) {
						$tab_top -= 6;
					} else {
						$tab_top = $pdf->GetY();
					}

					$substitutionarray = pdf_getSubstitutionArray($outputlangs, null, $object);
					complete_substitutions_array($substitutionarray, $outputlangs, $object);
					$notetoshow = make_substitutions($notetoshow, $substitutionarray, $outputlangs);
					$notetoshow = convertBackOfficeMediasLinksToPublicLinks($notetoshow);

					$pdf->startTransaction();

					$pdf->SetFont('', '', $default_font_size - 1);   // Dans boucle pour gerer multi-page
					$pdf->writeHTMLCell($tab_width, 3, $this->marge_gauche + 1, $tab_top, dol_htmlentitiesbr($notetoshow), 0, 1);
					// Description
					$pageposafternote = $pdf->getPage();
					$posyafter = $pdf->GetY();

					if ($pageposafternote > $pageposbeforenote) {
						$pdf->rollbackTransaction(true);

						// prepair pages to receive notes
						while ($pagenb < $pageposafternote) {
							$pdf->AddPage();
							$pagenb++;
							if (!empty($tplidx)) $pdf->useTemplate($tplidx);
							if (empty($conf->global->ULTIMATE_SUPPLIERINVOICES_PDF_DONOTREPEAT_HEAD)) $this->_pagehead($pdf, $object, 0, $outputlangs);
							// $this->_pagefoot($pdf,$object,$outputlangs,1);
							$pdf->setTopMargin($tab_top_newpage);
							// The only function to edit the bottom margin of current page to set it.
							$pdf->setPageOrientation('', 1, $heightforfooter + $heightforfreetext);
						}

						// back to start
						$pdf->setPage($pageposbeforenote);
						$pdf->setPageOrientation('', 1, $heightforfooter + $heightforfreetext);
						$pdf->SetFont('', '', $default_font_size - 1);
						$pdf->writeHTMLCell($tab_width, 3, $this->marge_gauche + 1, $tab_top, dol_htmlentitiesbr($notetoshow), 0, 1);
						$pageposafternote = $pdf->getPage();

						$posyafter = $pdf->GetY();

						if ($posyafter > ($this->page_hauteur - ($heightforfooter + $heightforfreetext + 20)))	// There is no space left for total+free text
						{
							$pdf->AddPage('', '', true);
							$pagenb++;
							$pageposafternote++;
							$pdf->setPage($pageposafternote);
							$pdf->setTopMargin($tab_top_newpage);
							// The only function to edit the bottom margin of current page to set it.
							$pdf->setPageOrientation('', 1, $heightforfooter + $heightforfreetext);
							$posyafter = $tab_top_newpage;
						}

						// apply note frame to previus pages
						$i = $pageposbeforenote;
						while ($i < $pageposafternote) {
							$pdf->setPage($i);

							$pdf->SetDrawColor(128, 128, 128);
							// Draw note frame
							if ($i > $pageposbeforenote) {
								$height_note = $this->page_hauteur - ($tab_top_newpage + $heightforfooter);
								$pdf->RoundedRect($this->marge_gauche, $tab_top_newpage - 1, $tab_width, $height_note + 1 + $heightsignature, $roundradius, '1111', 'S', $this->border_style, $bgcolor);
							} else {
								$height_note = $this->page_hauteur - ($tab_top + $heightforfooter);
								$pdf->RoundedRect($this->marge_gauche, $tab_top + 1, $tab_width, $height_note + 1 + $heightsignature, $roundradius, '1111', 'S', $this->border_style, $bgcolor);
							}

							// Add footer
							$pdf->setPageOrientation('', 1, 0);	// The only function to edit the bottom margin of current page to set it.
							$this->_pagefoot($pdf, $object, $outputlangs, 1);

							$i++;
						}

						// apply note frame to last page
						$pdf->setPage($pageposafternote);
						if (!empty($tplidx)) $pdf->useTemplate($tplidx);
						if (empty($conf->global->ULTIMATE_SUPPLIERINVOICES_PDF_DONOTREPEAT_HEAD)) $this->_pagehead($pdf, $object, 0, $outputlangs);
						$height_note = $posyafter - $tab_top_newpage;
						$pdf->RoundedRect($this->marge_gauche, $tab_top_newpage - 1, $tab_width, $height_note + 1 + $heightsignature, $roundradius, '1111', 'S', $this->border_style, $bgcolor);
					} else // No pagebreak
					{
						$pdf->commitTransaction();
						$posyafter = $pdf->GetY();
						$height_note = $posyafter - $tab_top;
						$pdf->RoundedRect($this->marge_gauche, $tab_top - 1, $tab_width, $height_note + 1 + $heightsignature, $roundradius, '1111', 'S', $this->border_style, $bgcolor);

						if ($posyafter > ($this->page_hauteur - ($heightforfooter + $heightforfreetext + 20))) {
							// not enough space, need to add page
							$pdf->AddPage('', '', true);
							$pagenb++;
							$pageposafternote++;
							$pdf->setPage($pageposafternote);
							if (!empty($tplidx)) $pdf->useTemplate($tplidx);
							if (empty($conf->global->ULTIMATE_SUPPLIERINVOICES_PDF_DONOTREPEAT_HEAD)) $this->_pagehead($pdf, $object, 0, $outputlangs);

							$posyafter = $tab_top_newpage;
						}
					}
					if (is_readable($signature) && !empty($imgsignature)) {
						$notetoshow .= $pdf->Image($signature, $this->marge_gauche + 80, $tab_top + $height_note + 1, 0, $heightsignature);
						if (!empty($salerepobj->signature)) $notetoshow = dol_concatdesc($notetoshow, $salerepobj->signature);
					}

					$tab_height = $tab_height - $height_note - $heightsignature;
					$tab_top = $posyafter + $heightsignature + 10;
				} else {
					$height_note = 0;
					if (is_readable($signature) && !empty($imgsignature)) {
						$notetoshow .= $pdf->Image($signature, $this->marge_gauche + 80, $tab_top + $height_note + 1, 0, $heightsignature);
						if (!empty($salerepobj->signature)) $notetoshow = dol_concatdesc($notetoshow, $salerepobj->signature);
					}
				}

				// Use new auto column system
				$this->prepareArrayColumnField($object, $outputlangs, $hidedetails, $hidedesc, $hideref);

				// Simulation de tableau pour connaitre la hauteur de la ligne de titre
				$pdf->startTransaction();
				$this->pdfTabTitles($pdf, $tab_top, $tab_height, $outputlangs, $hidetop);
				$pdf->rollbackTransaction(true);
				
				if (!$height_note && !$desc_incoterms) {
					$tab_top += 6;
				}

				$nexY = $tab_top + $this->tabTitleHeight + 2;
				$curY = $tab_top + $this->tabTitleHeight + 2;
				if (empty($conf->global->MAIN_PDF_DISABLE_COL_HEAD_TITLE)) {
					$nexY = $tab_top + $this->tabTitleHeight - 8;
				} else {
					$nexY = $tab_top + $this->tabTitleHeight - 2;
				}

				// Loop on each lines
				$pageposbeforeprintlines = $pdf->getPage();
				$pagenb = $pageposbeforeprintlines;
				$line_number = 1;
				for ($i = 0; $i < $nblines; $i++) {
					$curY = $nexY;
					$pdf->SetFont('', '', $default_font_size - 2);   // Into loop to work with multipage
					$pdf->SetTextColorArray($textcolor);
					$barcode = null;
					if (!empty($object->lines[$i]->fk_product)) {
						$product = new Product($db);
						$result = $product->fetch($object->lines[$i]->fk_product, '', '', '');
						$product->fetch_barcode();
					}

					// Define size of image if we need it
					$imglinesize = array();
					if (!empty($realpatharray[$i])) $imglinesize = pdf_getSizeForImage($realpatharray[$i]);

					$pdf->setTopMargin($tab_top_newpage);
					//If we aren't on last lines footer space needed is on $heightforfooter
					if ($i != $nblines - 1) {
						$bMargin = $heightforfooter;
					} else {
						//We are on last item, need to check all footer (freetext, ...)
						$bMargin = $heightforfooter + $heightforfreetext + $heightforinfotot;
					}
					$pdf->setPageOrientation('', 1, $bMargin);	// The only function to edit the bottom margin of current page to set it.
					$pageposbefore = $pdf->getPage();

					$showpricebeforepagebreak = 1;
					$posYAfterImage = 0;
					$posYStartDescription = 0;
					$posYAfterDescription = 0;
					$posYafterRef = 0;

					if ($this->getColumnStatus('picture')) {
						// We start with Photo of product line
						if (isset($imglinesize['width']) && isset($imglinesize['height']) && ($curY + $imglinesize['height']) > ($this->page_hauteur - $bMargin))	// If photo too high, we moved completely on new page
						{
							$pdf->AddPage('', '', true);
							if (!empty($tplidx)) $pdf->useTemplate($tplidx);
							if (empty($conf->global->ULTIMATE_SUPPLIERINVOICES_PDF_DONOTREPEAT_HEAD)) $this->_pagehead($pdf, $object, 0, $outputlangs);
							$pdf->setPage($pageposbefore + 1);

							$curY = $tab_top_newpage + 1;
							$showpricebeforepagebreak = 0;
						}

						$picture = false;

						if (isset($imglinesize['width']) && isset($imglinesize['height'])) {
							$curX = $this->getColumnContentXStart('picture') - 1;
							$pdf->Image($realpatharray[$i], $curX, $curY, $imglinesize['width'], $imglinesize['height'], '', '', '', 2, 300);	// Use 300 dpi
							// $pdf->Image does not increase value return by getY, so we save it manually
							$posYAfterImage = $curY + $imglinesize['height'];
							$picture = true;
						}
					}

					if ($picture) {
						$nexY = $posYAfterImage;
					}

					if ($this->getColumnStatus('vat') == true) {
						$vatorprice = $this->getColumnContentXStart('vat');
					} else {
						$vatorprice = $this->getColumnContentXStart('subprice');
					}

					// Description of product line
					$curX = $this->getColumnContentXStart('desc');
					$text_length = ($picture ? $this->getColumnContentXStart('picture') : $vatorprice);

					if ($this->getColumnStatus('desc')) {
						$pdf->startTransaction();
						if ($conf->global->ULTIMATE_DOCUMENTS_WITH_REF == "yes" && $this->atleastoneref == true) {
							$hideref = 1;
						} else {
							$hideref = (GETPOST('hideref', 'int') ? GETPOST('hideref', 'int') : (!empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_REF) ? 1 : 0));
						}
						$hidedetails = (GETPOST('hidedetails', 'int') ? GETPOST('hidedetails', 'int') : (!empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS) ? 1 : 0));
						$hidedesc = (GETPOST('hidedesc', 'int') ? GETPOST('hidedesc', 'int') : (!empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DESC) ?  1 : 0));

						$pageposbeforedesc = $pdf->getPage();
						$posYStartDescription = $curY;
						pdf_writelinedesc($pdf, $object, $i, $outputlangs, $text_length - $curX, 3, $curX + 1, $curY, $hideref, $hidedesc);
						$posYAfterDescription = $pdf->GetY();
						$pageposafter = $pdf->getPage();

						if (!empty($product->barcode) && !empty($product->barcode_type_code) && $object->lines[$i]->product_type != 9 && $conf->global->ULTIMATEPDF_GENERATE_SUPPLIERINVOICES_WITH_PRODUCTS_BARCODE == 1) {
							// dysplay product barcode
							$posy = $this->ultimatebarcode($pdf, $product);
							$posYAfterDescription = $pdf->GetY();
						}

						if ($pageposafter > $pageposbefore)	// There is a pagebreak
						{
							$posYAfterImage = $tab_top_newpage + $imglinesize['height'];
							$pdf->rollbackTransaction(true);
							$pageposbeforedesc = $pdf->getPage();
							$pageposafter = $pageposbefore;
							$posYStartDescription = $curY;
							$pdf->setPageOrientation('', 1, $heightforfooter);	// The only function to edit the bottom margin of current page to set it.
							pdf_writelinedesc($pdf, $object, $i, $outputlangs, $text_length - $curX, 3, $curX + 1, $curY, $hideref, $hidedesc);
							$posYAfterDescription = $pdf->GetY();
							$pageposafter = $pdf->getPage();

							if (!empty($product->barcode) && !empty($product->barcode_type_code) && $object->lines[$i]->product_type != 9 && $conf->global->ULTIMATEPDF_GENERATE_SUPPLIERINVOICES_WITH_PRODUCTS_BARCODE == 1) {
								// dysplay product barcode
								$posy = $this->ultimatebarcode($pdf, $product);
								$posYAfterDescription = $pdf->GetY();
							}

							if ($posYAfterDescription > ($this->page_hauteur - $bMargin))	// There is no space left for total+free text
							{
								if ($i == ($nblines - 1))	// No more lines, and no space left to show total, so we create a new page
								{
									$pdf->AddPage('', '', true);
									if (!empty($tplidx)) $pdf->useTemplate($tplidx);
									if (empty($conf->global->ULTIMATE_SUPPLIERINVOICES_PDF_DONOTREPEAT_HEAD)) $this->_pagehead($pdf, $object, 0, $outputlangs);
									$pdf->setPage($pageposafter + 1);
								}
							} else {
								// We found a page break
								$showpricebeforepagebreak = 1;
							}
						} else	// No pagebreak
						{
							$pdf->commitTransaction();
						}
						$posYAfterDescription = $pdf->GetY();
					}
					$nexY = max($pdf->GetY(), $posYAfterImage);

					$pageposafter = $pdf->getPage();

					$pdf->setPage($pageposbefore);
					$pdf->setTopMargin($this->marge_haute);
					$pdf->setPageOrientation('', 1, 0);	// The only function to edit the bottom margin of current page to set it.

					// We suppose that a too long description is moved completely on next page
					if ($pageposafter > $pageposbefore && empty($showpricebeforepagebreak)) {
						$pdf->setPage($pageposafter);
						$curY = $tab_top_newpage;
					}
					if ($pageposafterRef > $pageposbefore && $posYafterRef < $posYStartRef) {
						$pdf->setPage($pageposbefore);
						$showpricebeforepagebreak = 1;
					}
					if ($nexY + 2 > $curY && $pageposafter > $pageposbefore) {
						$pdf->setPage($pageposafter);
						$curY = $tab_top_newpage + 1;
					}
					if ($pageposbeforedesc < $pageposafterdesc) {
						$pdf->setPage($pageposbeforedesc);
						$curY = $posYStartDescription; //
					}

					$pdf->SetFont('', '', $default_font_size - 2);   // On repositionne la police par defaut

					if (($pageposafter > $pageposbefore) && ($pageposbeforedesc < $pageposafterdesc)) {
						$pdf->setPage($pageposbefore);
						$curY = $posYStartDescription; //
					}
					if ($posYStartDescription > $posYAfterDescription && $pageposafter > $pageposbefore) {
						$pdf->setPage($pageposbefore);
						$curY = $posYStartDescription;
					}
					if (($barcode == true) ? ($curY + 8 > ($this->page_hauteur - $heightforfooter)) : ($curY + 4 > ($this->page_hauteur - $heightforfooter))) {
						$pdf->setPage($pageposafter);
						$curY = $tab_top_newpage; //
					}

					//Line numbering
					if (!empty($conf->global->ULTIMATE_SUPPLIERINVOICES_WITH_LINE_NUMBER)) {
						// Numbering
						if ($this->getColumnStatus('num') && array_key_exists($i, $object->lines)) {
							$this->printStdColumnContent($pdf, $curY, 'num', $line_number);
							$line_number++;
						}
					}

					//  Column reference
					if ($conf->global->ULTIMATE_DOCUMENTS_WITH_REF == "yes" && $this->atleastoneref == true) {
						if ($this->getColumnStatus('ref')) {
							$productRef = pdf_getlineref($object, $i, $outputlangs, $hidedetails);
							$this->printStdColumnContent($pdf, $curY, 'ref', $productRef);
						}
					}

					// VAT Rate
					if ($conf->global->ULTIMATE_GENERATE_DOCUMENTS_WITHOUT_VAT == "no" && empty($conf->global->ULTIMATE_SHOW_HIDE_VAT_COLUMN) && empty($conf->global->ULTIMATE_SHOW_HIDE_ALL_PRICES)) {
						// VAT Rate
						if ($this->getColumnStatus('vat')) {
							$vat_rate = pdf_getlinevatrate($object, $i, $outputlangs, $hidedetails);
							$this->printStdColumnContent($pdf, $curY, 'vat', $vat_rate);
						}
					}

					// Unit price before discount
					if (empty($conf->global->ULTIMATE_SHOW_HIDE_PUHT) && empty($conf->global->ULTIMATE_SHOW_HIDE_ALL_PRICES)) {
						if ($this->getColumnStatus('subprice')) {
							$up_excl_tax = pdf_getlineupexcltax($object, $i, $outputlangs, $hidedetails);
							$this->printStdColumnContent($pdf, $curY, 'subprice', $up_excl_tax);
						}
					}

					// Discount on line                	
					if ($this->getColumnStatus('discount') && $object->lines[$i]->remise_percent) {
						$remise_percent = pdf_getlineremisepercent($object, $i, $outputlangs, $hidedetails);
						$this->printStdColumnContent($pdf, $curY, 'discount', $remise_percent);
					}

					// Unit price after discount					
					if ($remise_percent == dol_print_reduction(100, $langs)) {
						$up_after = price(0);
						$this->printStdColumnContent($pdf, $curY, 'upafter', $up_after);
					} else {
						if ($object->lines[$i]->remise_percent > 0) {
							$up_after = price2num($up_excl_tax) * (1 - price2num($remise_percent) / 100);
							$this->printStdColumnContent($pdf, $curY, 'upafter', $up_after);
						}
					}

					// Quantity
					if (empty($conf->global->ULTIMATE_SHOW_HIDE_QTY)) {
						if ($this->getColumnStatus('qty')) {
							$qty = pdf_getlineqty($object, $i, $outputlangs, $hidedetails);
							$this->printStdColumnContent($pdf, $curY, 'qty', $qty);
						}
					}

					// Unit
					if ($conf->global->PRODUCT_USE_UNITS) {
						if ($this->getColumnStatus('unit')) {
							$unit = pdf_getlineunit($object, $i, $outputlangs, $hidedetails, $hookmanager);
							$this->printStdColumnContent($pdf, $curY, 'unit', $unit);
						}
					}

					if (!empty($conf->global->ULTIMATE_SHOW_LINE_TTTC)) {
						// Total TTC line
						if ($this->getColumnStatus('totalincltax') && empty($conf->global->ULTIMATE_SHOW_HIDE_THT) && empty($conf->global->ULTIMATE_SHOW_HIDE_ALL_PRICES)) {
							$total_incl_tax = pdf_getlinetotalwithtax($object, $i, $outputlangs, $hidedetails);
							$this->printStdColumnContent($pdf, $curY, 'totalincltax', $total_incl_tax);
						}
					} else {
						// Total HT line
						if ($this->getColumnStatus('totalexcltax') && empty($conf->global->ULTIMATE_SHOW_HIDE_THT) && empty($conf->global->ULTIMATE_SHOW_HIDE_ALL_PRICES)) {
							$total_excl_tax = pdf_getlinetotalexcltax($object, $i, $outputlangs, $hidedetails);
							$this->printStdColumnContent($pdf, $curY, 'totalexcltax', $total_excl_tax);
						}
					}

					$parameters = array(
						'object' => $object,
						'i' => $i,
						'pdf' => &$pdf,
						'curY' => &$curY,
						'nexY' => &$nexY,
						'outputlangs' => $outputlangs,
						'hidedetails' => $hidedetails
					);
					$reshook = $hookmanager->executeHooks('printPDFline', $parameters, $this);    // Note that $object may have been modified by hook

					// Collecte des totaux par valeur de tva dans $this->tva["taux"]=total_tva
					if ($conf->multicurrency->enabled && $object->multicurrency_tx != 1) $tvaligne = $object->lines[$i]->multicurrency_total_tva;
					else $tvaligne = $object->lines[$i]->total_tva;

					$localtax1ligne = $object->lines[$i]->total_localtax1;
					$localtax2ligne = $object->lines[$i]->total_localtax2;

					if (!empty($object->remise_percent)) $tvaligne -= ($tvaligne * $object->remise_percent) / 100;

					$vatrate = (string) $object->lines[$i]->tva_tx;
					$localtax1rate = (string) $object->lines[$i]->localtax1_tx;
					$localtax2rate = (string) $object->lines[$i]->localtax2_tx;

					if (($object->lines[$i]->info_bits & 0x01) == 0x01) $vatrate .= '*';
					if (empty($this->tva[$vatrate])) $this->tva[$vatrate] = 0;
					if (empty($this->localtax1[$localtax1rate])) $this->localtax1[$localtax1rate] = 0;
					if (empty($this->localtax2[$localtax2rate])) $this->localtax2[$localtax2rate] = 0;
					$this->tva[$vatrate] += $tvaligne;
					$this->localtax1[$localtax1rate] += $localtax1ligne;
					$this->localtax2[$localtax2rate] += $localtax2ligne;

					if ($posYAfterImage > $posYAfterDescription) $nexY = $posYAfterImage;

					// Add line
					if (!empty($conf->global->ULTIMATE_SUPPLIERINVOICES_PDF_DASH_BETWEEN_LINES) && $i < ($nblines - 1)) {
						$pdf->setPage($pageposafter);
						$pdf->SetLineStyle(array('dash' => '1,1', 'color' => array(70, 70, 70)));
						if ($conf->global->ULTIMATEPDF_GENERATE_SUPPLIERINVOICES_WITH_PRODUCTS_BARCODE == 1 && !empty($product->barcode)) {
							$pdf->line($this->marge_gauche, $nexY + 4, $this->page_largeur - $this->marge_droite, $nexY + 4);
						} else {
							$pdf->line($this->marge_gauche, $nexY + 1, $this->page_largeur - $this->marge_droite, $nexY + 1);
						}
						$pdf->SetLineStyle(array('dash' => 0));
					}

					if ($conf->global->ULTIMATEPDF_GENERATE_SUPPLIERINVOICES_WITH_PRODUCTS_BARCODE == 1) {
						$nexY += 5;    // Passe espace entre les lignes
					} else {
						$nexY += 2;    // Passe espace entre les lignes
					}

					// Detect if some page were added automatically and output _tableau for past pages
					while ($pagenb < $pageposafter) {
						$pdf->setPage($pagenb);
						if ($pagenb == $pageposbeforeprintlines) {
							$this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforfooter, 0, $outputlangs, 0, 1, $object->multicurrency_code);
						} else {
							$this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforfooter, 0, $outputlangs, 0, 1, $object->multicurrency_code);
						}

						$this->_pagefoot($pdf, $object, $outputlangs, 1);
						$pagenb++;
						$pdf->setPage($pagenb);
						$pdf->setPageOrientation('', 1, 0);	// The only function to edit the bottom margin of current page to set it.
						if (empty($conf->global->ULTIMATE_SUPPLIERINVOICES_PDF_DONOTREPEAT_HEAD)) $this->_pagehead($pdf, $object, 0, $outputlangs);
					}
					if (isset($object->lines[$i + 1]->pagebreak) && $object->lines[$i + 1]->pagebreak) {
						if ($pagenb == $pageposafter) {
							$this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforfooter, 0, $outputlangs, 0, 1, $object->multicurrency_code);
						} else {
							$this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforfooter, 0, $outputlangs, 0, 1, $object->multicurrency_code);
						}

						$this->_pagefoot($pdf, $object, $outputlangs, 1);
						// New page
						$pdf->AddPage();
						if (!empty($tplidx)) $pdf->useTemplate($tplidx);
						$pagenb++;
						if (empty($conf->global->ULTIMATE_SUPPLIERINVOICES_PDF_DONOTREPEAT_HEAD)) $this->_pagehead($pdf, $object, 0, $outputlangs);
					}
				}

				// Show square
				if ($pagenb == $pageposbeforeprintlines) {
					$this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforinfotot - $heightforfreetext - $heightforfooter, 0, $outputlangs, 0, 0, $object->multicurrency_code);
				} else {
					$this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforinfotot - $heightforfreetext - $heightforfooter, 0, $outputlangs, 0, 0, $object->multicurrency_code);
				}
				$bottomlasttab = $this->page_hauteur - $heightforinfotot - $heightforfreetext - $heightforfooter + 1;

				// Affiche zone infos
				$posy = $this->_tableau_info($pdf, $object, $bottomlasttab, $outputlangs);

				// Affiche zone totaux
				$posy = $this->_tableau_tot($pdf, $object, $deja_regle, $bottomlasttab, $outputlangs);

				$amount_credit_notes_included = 0;
				$amount_deposits_included = 0;

				if ($deja_regle || $amount_credit_notes_included || $amount_deposits_included) {
					$posy = $this->_tableau_versements($pdf, $object, $posy, $outputlangs);
				}

				// Pied de page
				$this->_pagefoot($pdf, $object, $outputlangs);
				if (method_exists($pdf, 'AliasNbPages')) $pdf->AliasNbPages();

				$pdf->Close();

				$pdf->Output($file, 'F');

				// Add pdfgeneration hook
				$hookmanager->initHooks(array('pdfgeneration'));
				$parameters = array('file' => $file, 'object' => $object, 'outputlangs' => $outputlangs);
				global $action;
				$reshook = $hookmanager->executeHooks('afterPDFCreation', $parameters, $this, $action);    // Note that $action and $object may have been modified by some hooks
				if ($reshook < 0)
				{
				    $this->error = $hookmanager->error;
				    $this->errors = $hookmanager->errors;
				}

				if (!empty($conf->global->MAIN_UMASK))
					@chmod($file, octdec($conf->global->MAIN_UMASK));

				return 1;   // Pas d'erreur
			} else {
				$this->error = $langs->transnoentities("ErrorCanNotCreateDir", $dir);
				return 0;
			}
		} else {
			$this->error = $langs->transnoentities("ErrorConstantNotDefined", "SUPPLIER_OUTPUTDIR");
			return 0;
		}
		$this->error = $langs->transnoentities("ErrorUnknown");
		return 0;   // Erreur par defaut
	}

	/**
	 *   Show miscellaneous information (payment mode, payment term, ...)
	 *
	 *   @param		TCPDF		&$pdf     		Object PDF
	 *   @param		Object		$object			Object to show
	 *   @param		int			$posy			Y
	 *   @param		Translate	$outputlangs	Langs object
	 *   @return	void
	 */
	function _tableau_info(&$pdf, $object, $posy, $outputlangs)
	{
		global $conf;

		$default_font_size = pdf_getPDFFontSize($outputlangs);
		
		$textcolor =  (!empty($conf->global->ULTIMATE_TEXTCOLOR_COLOR)) ?
		html2rgb($conf->global->ULTIMATE_TEXTCOLOR_COLOR) : array(25, 25, 25);

		$qrcodecolor =  (!empty($conf->global->ULTIMATE_QRCODECOLOR_COLOR)) ?
		html2rgb($conf->global->ULTIMATE_QRCODECOLOR_COLOR) : array(25, 25, 25);

		$pdf->SetFont('', '', $default_font_size - 1);

		// If France, show VAT mention if not applicable
		if ($this->emetteur->country_code == 'FR' && $this->franchise == 1) {
			$pdf->SetFont('', 'B', $default_font_size - 2);
			$pdf->SetXY($this->marge_gauche, $posy);
			$pdf->MultiCell(100, 3, $outputlangs->transnoentities("VATIsNotUsedForInvoice"), 0, 'L', 0);

			$posy = $pdf->GetY() + 4;
		}

		$widthrecbox = ($this->page_largeur - $this->marge_gauche - $this->marge_droite - 4) / 2;

		// Payment conditions
		if ($object->cond_reglement_code || $object->cond_reglement) {
			$pdf->SetFont('', '', $default_font_size - 2);
			$titre = '<b>' . $outputlangs->transnoentities("PaymentConditions") . '</b>' . ' : ';
			$lib_condition_paiement = $outputlangs->transnoentities("PaymentCondition" . $object->cond_reglement_code) != ('PaymentCondition' . $object->cond_reglement_code) ? $outputlangs->transnoentities("PaymentCondition" . $object->cond_reglement_code) : $outputlangs->convToOutputCharset($object->cond_reglement_doc);
			$lib_condition_paiement = str_replace('\n', "\n", $lib_condition_paiement);
			$pdf->writeHTMLCell($widthrecbox, 5, $this->marge_gauche, $posy, $titre . ' ' . $lib_condition_paiement, 0, 0, false, true, 'L', true);
			$posy = $pdf->GetY() + 3;
		} else {
			$pdf->SetFont('', '', $default_font_size - 2);
			$pdf->SetXY($this->marge_gauche, $posy);
			$pdf->SetTextColorArray($textcolor);
			$pdf->SetFillColor(255, 255, 255);
			$pdf->MultiCell($widthrecbox, 6, '', '0', 'C');
			$posy = $pdf->GetY() + 4;
		}

		// Show payment mode
		if (
			$object->mode_reglement_code
			&& $object->mode_reglement_code != 'CHQ'
			&& $object->mode_reglement_code != 'VIR'
		) {
			$pdf->SetFont('', '', $default_font_size - 2);
			$titre = '<b>' . $outputlangs->transnoentities("PaymentMode") . '</b>' . ' : ';
			$lib_mode_reg = $outputlangs->transnoentities("PaymentType" . $object->mode_reglement_code) != ('PaymentType' . $object->mode_reglement_code) ? $outputlangs->transnoentities("PaymentType" . $object->mode_reglement_code) : $outputlangs->convToOutputCharset($object->mode_reglement);
			$pdf->writeHTMLCell($widthrecbox, 5, $this->marge_gauche, $posy, $titre . ' ' . $lib_mode_reg, 0, 0, false, true, 'L', true);
			$posy = $pdf->GetY() + 4;
		}
		// QR-code
		if (!empty($conf->global->ULTIMATEPDF_GENERATE_SUPPLIERINVOICES_WITH_INVOICE_QRCODE)) {
			$posxQRcode = $this->marge_gauche + $widthrecbox / 1.5;
			// set style for QR-code
			$styleQr = array(
				'border' => false,
				'vpadding' => 'auto',
				'hpadding' => 'auto',
				'fgcolor' => $qrcodecolor,
				'bgcolor' => false, //array(255,255,255)
				'module_width' => 1, // width of a single module in points
				'module_height' => 1 // height of a single module in points
			);
			$code = pdf_supplierinvoiceCodeContents();
			$pdf->write2DBarcode($code, 'QRCODE,M', $posxQRcode, $posy, 30, 30, $styleQr, 'N');
		}
	}

	/**
	 *	Show total to pay
	 *
	 *	@param	TCPDF		&$pdf           Object PDF
	 *	@param  Facture		$object         Object invoice
	 *	@param  int			$deja_regle     Montant deja regle
	 *	@param	int			$posy			Position depart
	 *	@param	Translate	$outputlangs	Objet langs
	 *	@return int							Position pour suite
	 */
	function _tableau_tot(&$pdf, $object, $deja_regle, $posy, $outputlangs)
	{
		global $conf, $mysoc;

		$sign = 1;
		if ($object->type == 2 && !empty($conf->global->INVOICE_POSITIVE_CREDIT_NOTE)) $sign = -1;

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		$dashdotted =  (!empty($conf->global->ULTIMATE_DASH_DOTTED)) ?
		$conf->global->ULTIMATE_DASH_DOTTED : 0;

		$bordercolor =  (!empty($conf->global->ULTIMATE_BORDERCOLOR_COLOR)) ?
		html2rgb($conf->global->ULTIMATE_BORDERCOLOR_COLOR) : array(0, 63, 127);

		$bgcolor =  (!empty($conf->global->ULTIMATE_BGCOLOR_COLOR)) ?
		html2rgb($conf->global->ULTIMATE_BGCOLOR_COLOR) : array(170, 212, 255);

		$opacity =  (!empty($conf->global->ULTIMATE_BGCOLOR_OPACITY)) ?
		$conf->global->ULTIMATE_BGCOLOR_OPACITY : 0.5;

		$roundradius =  (!empty($conf->global->ULTIMATE_SET_RADIUS)) ?
		$conf->global->ULTIMATE_SET_RADIUS : 1;

		$textcolor =  (!empty($conf->global->ULTIMATE_TEXTCOLOR_COLOR)) ?
		html2rgb($conf->global->ULTIMATE_TEXTCOLOR_COLOR) : array(25, 25, 25);

		$this->border_style = array('width' => 0.2, 'cap' => 'butt', 'join' => 'miter', 'dash' => $dashdotted, 'color' => $bordercolor);

		$tab2_top = $posy;
		$tab2_hl = 4;
		$pdf->SetFont('', '', $default_font_size - 1);

		$pdf->SetXY($this->marge_gauche, $tab2_top);
		// If France, show VAT mention if not applicable
		if ($this->emetteur->country_code == 'FR' && $this->franchise == 1) {
			$pdf->MultiCell(100, $tab2_hl, $outputlangs->transnoentities("VATIsNotUsedForInvoice"), 0, 'L', 0);
		}

		// Tableau total
		$col1x = $this->page_largeur / 2 + 2;
		$col2x = 150;
		if ($this->page_largeur < 210) // To work with US executive format
		{
			$col2x -= 20;
		}
		$largcol2 = ($this->page_largeur - $this->marge_droite - $col2x);
		if ($roundradius == 0) {
			$roundradius = 0.1; //RoundedRect don't support $roundradius to be 0
		}
		$widthrecbox = ($this->page_largeur - $this->marge_gauche - $this->marge_droite - 4) / 2;
		$deltax = $this->marge_gauche + $widthrecbox + 4;

		$useborder = 0;
		$index = 0;

		// Total HT
		$pdf->SetXY($col1x, $posy);
		$pdf->SetAlpha($opacity);
		$pdf->RoundedRect($deltax, $posy, $widthrecbox, 4, $roundradius, '1111', 'FD', $this->border_style, $bgcolor);
		$pdf->SetAlpha(1);
		$pdf->SetFont('', '', $default_font_size - 1);
		$pdf->SetXY($col1x, $tab2_top);
		$pdf->SetFillColor(255, 255, 255);
		$pdf->MultiCell($col2x - $col1x, $tab2_hl, $outputlangs->transnoentities("TotalHT"), 0, 'L', 1);

		$total_ht = (($conf->multicurrency->enabled && isset($object->multicurrency_tx) && $object->multicurrency_tx != 1) ? $object->multicurrency_total_ht : $object->total_ht);
		$pdf->SetXY($col2x, $tab2_top);
		$this->_setFontForMulticurrencyCode($pdf, $object, $outputlangs);
		$pdf->MultiCell($largcol2, $tab2_hl, price($sign * ($total_ht + (!empty($object->remise) ? $object->remise : 0)), 0, $outputlangs, 1, -1, -1, (!empty($object->multicurrency_code) ? $object->multicurrency_code : $conf->currency)), 0, 'R', 1);
		$pdf->SetFont($conf->global->MAIN_PDF_FORCE_FONT, '', $default_font_size - 1);

		$posy = $pdf->getY();

		// Show VAT by rates and total
		$pdf->SetFillColor(248, 248, 248);

		if ($conf->global->ULTIMATE_GENERATE_DOCUMENTS_WITHOUT_VAT == "no") {
			foreach ($this->tva as $tvakey => $tvaval) {
				if ($tvakey > 0) {	// We do not display rate 0
					$this->atleastoneratenotnull++;

					$index++;
					$pdf->SetXY($col1x, $tab2_top + 0.5 + $tab2_hl * $index);
					$pdf->SetAlpha($opacity);
					$pdf->SetFont('', '', $default_font_size - 1);
					$bgcolor2 = array(177, 177, 177
					);
					$pdf->RoundedRect($deltax, $tab2_top + 0.5 + $tab2_hl * $index, $widthrecbox, 4, 2, '1111', 'FD', $this->border_style, $bgcolor2);
					$pdf->SetAlpha(1);

					$tvacompl = '';

					if (preg_match('/\*/', $tvakey)) {
						$tvakey = str_replace('*', '', $tvakey);
						$tvacompl = " (" . $outputlangs->transnoentities("NonPercuRecuperable") . ")";
					}

					$totalvat = $outputlangs->transcountrynoentities("TotalVAT", $mysoc->country_code);
					$totalvat .= ' ';
					$totalvat .= vatrate($tvakey, 1) . $tvacompl;
					$pdf->SetFillColor(255, 255, 255);
					if (empty($conf->global->ULTIMATE_SHOW_HIDE_ALL_PRICES)) {
						$pdf->MultiCell($col2x - $col1x, $tab2_hl, $totalvat, 0, 'L', 1);

						$pdf->SetXY($col2x, $tab2_top + 0.5 + $tab2_hl * $index);
						$this->_setFontForMulticurrencyCode($pdf, $object, $outputlangs);
						$pdf->MultiCell($largcol2, $tab2_hl, price($tvaval, 0, $outputlangs, 1, -1, -1, $object->multicurrency_code), 0, 'R', 1);
						$pdf->SetFont($conf->global->MAIN_PDF_FORCE_FONT, '', $default_font_size - 1);
					}
				}
			}
			if (!$this->atleastoneratenotnull) {	// If no vat at all
				$index++;
				$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
				$pdf->MultiCell($col2x - $col1x, $tab2_hl, $outputlangs->transcountrynoentities("TotalVAT", $mysoc->country_code), 0, 'L', 1);

				$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
				$pdf->MultiCell($largcol2, $tab2_hl, price($object->total_tva, 0, $outputlangs), 0, 'R', 1);

				// Total LocalTax1
				if (!empty($conf->global->FACTURE_LOCAL_TAX1_OPTION) && $conf->global->FACTURE_LOCAL_TAX1_OPTION == 'localtax1on' && $object->total_localtax1 > 0) {
					$index++;
					$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
					$pdf->MultiCell($col2x - $col1x, $tab2_hl, $outputlangs->transnoentities("TotalLT1" . $mysoc->country_code), 0, 'L', 1);
					$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
					$pdf->MultiCell($largcol2, $tab2_hl, price($object->total_localtax1, 0, $outputlangs), $useborder, 'R', 1);
				}

				// Total LocalTax2
				if (!empty($conf->global->FACTURE_LOCAL_TAX2_OPTION) && $conf->global->FACTURE_LOCAL_TAX2_OPTION == 'localtax2on' && $object->total_localtax2 > 0) {
					$index++;
					$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
					$pdf->MultiCell($col2x - $col1x, $tab2_hl, $outputlangs->transnoentities("TotalLT2" . $mysoc->country_code), 0, 'L', 1);
					$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
					$pdf->MultiCell($largcol2, $tab2_hl, price($object->total_localtax2, 0, $outputlangs), $useborder, 'R', 1);
				}
			} else {
				//Local tax 1
				foreach ($this->localtax1 as $tvakey => $tvaval) {
					if ($tvakey != 0)    // On affiche pas taux 0
					{
						$index++;
						$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);

						$tvacompl = '';
						if (preg_match('/\*/', $tvakey)) {
							$tvakey = str_replace('*', '', $tvakey);
							$tvacompl = " (" . $outputlangs->transnoentities("NonPercuRecuperable") . ")";
						}
						$totalvat = $outputlangs->transnoentities("TotalLT1" . $mysoc->country_code) . ' ';
						$totalvat .= vatrate($tvakey, 1) . $tvacompl;
						$pdf->MultiCell($col2x - $col1x, $tab2_hl, $totalvat, 0, 'L', 1);

						$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
						$pdf->MultiCell($largcol2, $tab2_hl, price($tvaval, 0, $outputlangs), 0, 'R', 1);
					}
				}

				//Local tax 2
				foreach ($this->localtax2 as $tvakey => $tvaval) {
					if ($tvakey != 0)    // On affiche pas taux 0
					{
						$index++;
						$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);

						$tvacompl = '';
						if (preg_match('/\*/', $tvakey)) {
							$tvakey = str_replace('*', '', $tvakey);
							$tvacompl = " (" . $outputlangs->transnoentities("NonPercuRecuperable") . ")";
						}
						$totalvat = $outputlangs->transnoentities("TotalLT2" . $mysoc->pays_code) . ' ';
						$totalvat .= vatrate($tvakey, 1) . $tvacompl;
						$pdf->MultiCell($col2x - $col1x, $tab2_hl, $totalvat, 0, 'L', 1);

						$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
						$pdf->MultiCell($largcol2, $tab2_hl, price($tvaval, 0, $outputlangs), 0, 'R', 1);
					}
				}
			}

			$useborder = 0;

			// Total TTC
			$index++;
			$pdf->SetXY($col1x, $tab2_top + 0.8 + $tab2_hl * $index);
			$pdf->SetTextColorArray($textcolor);

			$pdf->SetAlpha($opacity);
			$pdf->RoundedRect($deltax, $tab2_top + 1 + $tab2_hl * $index, $widthrecbox, 4, $roundradius, '1111', 'FD', $this->border_style, $bgcolor);
			$pdf->SetAlpha(1);
			$pdf->SetFillColor(255, 255, 255);
			$pdf->SetFont('', 'B', $default_font_size - 1);
			$pdf->MultiCell($col2x - $col1x, $tab2_hl, $outputlangs->transnoentities("TotalTTC"), $useborder, 'L', 1);

			$total_ttc = ($conf->multicurrency->enabled && $object->multicurrency_tx != 1) ? $object->multicurrency_total_ttc : $object->total_ttc;
			$pdf->SetXY($col2x, $tab2_top + 0.8 + $tab2_hl * $index);
			$this->_setFontForMulticurrencyCode($pdf, $object, $outputlangs);
			$pdf->MultiCell($largcol2, $tab2_hl, price($total_ttc, 0, $outputlangs, 1, -1, -1, $object->multicurrency_code), $useborder, 'R', 1);
			$pdf->SetFont($conf->global->MAIN_PDF_FORCE_FONT, '', $default_font_size - 1);

			$creditnoteamount = 0;
			$depositsamount = 0;
			$resteapayer = price2num($total_ttc - $deja_regle - $creditnoteamount - $depositsamount, 'MT');
			if (!empty($object->paye)) $resteapayer = 0;
		} else {
			// Total TTC without VAT			
			$index++;
			$pdf->SetXY($col1x, $tab2_top + 0);
			$pdf->MultiCell($col2x - $col1x, $tab2_hl, $outputlangs->transnoentities("TotalHT"), 0, 'L', 1);
			$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
			$pdf->SetTextColorArray($textcolor);
			$pdf->SetFont('', 'B', $default_font_size);
			$pdf->MultiCell($col2x - $col1x, $tab2_hl, $outputlangs->transnoentities("TotalTTC"), $useborder, 'L', 1);

			$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
			$pdf->MultiCell($largcol2, $tab2_hl, price($object->total_ht + (!empty($object->remise) ? $object->remise : 0), 0, $outputlangs), 0, 'R', 1);
		}

		$creditnoteamount = $object->getSumCreditNotesUsed(($conf->multicurrency->enabled && $object->multicurrency_tx != 1) ? 1 : 0); // Warning, this also include excess received
		$depositsamount = $object->getSumDepositsUsed(($conf->multicurrency->enabled && $object->multicurrency_tx != 1) ? 1 : 0);
		//print "x".$creditnoteamount."-".$depositsamount;exit;
		$resteapayer = price2num($total_ttc - $deja_regle - $creditnoteamount - $depositsamount, 'MT');
		if (!empty($object->paye)) $resteapayer = 0;

		if (($deja_regle > 0 || $creditnoteamount > 0 || $depositsamount > 0) && empty($conf->global->INVOICE_NO_PAYMENT_DETAILS)) {
			// Already paid + Deposits
			$index++;

			$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
			$pdf->MultiCell($col2x - $col1x, $tab2_hl, $outputlangs->transnoentities("Paid"), 0, 'L', 0);
			$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
			$pdf->MultiCell($largcol2, $tab2_hl, price($deja_regle + $depositsamount, 0, $outputlangs), 0, 'R', 0);

			// Credit note
			if ($creditnoteamount) {
				$labeltouse = ($outputlangs->transnoentities("CreditNotesOrExcessReceived") != "CreditNotesOrExcessReceived") ? $outputlangs->transnoentities("CreditNotesOrExcessReceived") : $outputlangs->transnoentities("CreditNotes");
				$index++;
				$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
				$pdf->MultiCell($col2x - $col1x, $tab2_hl, $labeltouse, 0, 'L', 0);
				$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
				$pdf->MultiCell($largcol2, $tab2_hl, price($creditnoteamount, 0, $outputlangs), 0, 'R', 0);
			}

			// Escompte
			if ($object->close_code == FactureFournisseur::CLOSECODE_DISCOUNTVAT) {
				$index++;
				$pdf->SetFillColor(255, 255, 255);

				$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
				$pdf->MultiCell($col2x - $col1x, $tab2_hl, $outputlangs->transnoentities("EscompteOfferedShort"), $useborder, 'L', 1);
				$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
				$pdf->MultiCell($largcol2, $tab2_hl, price($object->total_ttc - $deja_regle - $creditnoteamount - $depositsamount, 0, $outputlangs), $useborder, 'R', 1);

				$resteapayer = 0;
			}

			$index++;
			$pdf->SetTextColorArray($textcolor);
			$pdf->SetFillColor(224, 224, 224);
			$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
			$pdf->MultiCell($col2x - $col1x, $tab2_hl, $outputlangs->transnoentities("RemainderToPay"), $useborder, 'L', 1);

			$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
			$pdf->MultiCell($largcol2, $tab2_hl, price($resteapayer, 0, $outputlangs) , $useborder, 'R', 1);

			// Fin
			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->SetTextColorArray($textcolor);
		}

		$index++;
		return ($tab2_top + ($tab2_hl * $index));
	}

	/**
	 *   Show table for lines
	 *
	 *   @param		TCPDF		$pdf     		Object PDF
	 *   @param		string		$tab_top		Top position of table
	 *   @param		string		$tab_height		Height of table (rectangle)
	 *   @param		int			$nexY			Y (not used)
	 *   @param		Translate	$outputlangs	Langs object
	 *   @param		int			$hidetop		Hide top bar of array
	 *   @param		int			$hidebottom		Hide bottom bar of array
	 *   @param		string		$currency		Currency code
	 *   @return	void
	 */
	function _tableau(&$pdf, $tab_top, $tab_height, $nexY, $outputlangs, $hidetop = 0, $hidebottom = 0, $currency = '')
	{
		global $conf;

		// Force to disable hidetop and hidebottom
		$hidebottom = 0;
		if ($hidetop) $hidetop = -1;

		$currency = !empty($currency) ? $currency : $conf->currency;
		$default_font_size = pdf_getPDFFontSize($outputlangs);

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		$opacity =  (!empty($conf->global->ULTIMATE_BGCOLOR_OPACITY)) ?
		$conf->global->ULTIMATE_BGCOLOR_OPACITY : 0.5;

		$roundradius =  (!empty($conf->global->ULTIMATE_SET_RADIUS)) ?
		$conf->global->ULTIMATE_SET_RADIUS : 1;
		
		$dashdotted =  (!empty($conf->global->ULTIMATE_DASH_DOTTED)) ?
		$conf->global->ULTIMATE_DASH_DOTTED : 0;

		$bgcolor =  (!empty($conf->global->ULTIMATE_BGCOLOR_COLOR)) ?
		html2rgb($conf->global->ULTIMATE_BGCOLOR_COLOR) : array(170, 212, 255);

		$bordercolor =  (!empty($conf->global->ULTIMATE_BORDERCOLOR_COLOR)) ?
		html2rgb($conf->global->ULTIMATE_BORDERCOLOR_COLOR) : array(0, 63, 127);

		$textcolor =  (!empty($conf->global->ULTIMATE_TEXTCOLOR_COLOR)) ?
		html2rgb($conf->global->ULTIMATE_TEXTCOLOR_COLOR) : array(25, 25, 25);

		if (!empty($conf->global->MAIN_PDF_TITLE_BACKGROUND_COLOR)) {
			$title_bgcolor =  html2rgb($conf->global->MAIN_PDF_TITLE_BACKGROUND_COLOR);
		}
		$this->border_style = array('width' => 0.2, 'cap' => 'butt', 'join' => 'miter', 'dash' => $dashdotted, 'color' => $bordercolor);

		// Amount in (at tab_top - 1)
		$pdf->SetFillColorArray($bgcolor);
		$pdf->SetTextColorArray($textcolor);
		$pdf->SetFont('', '', $default_font_size - 2);

		if ($roundradius == 0) {
			$roundradius = 0.1; //RoundedRect don't support $roundradius to be 0
		}
		// Output RoundedRect
		$pdf->SetAlpha($opacity);
		if (!empty($conf->global->MAIN_PDF_TITLE_BACKGROUND_COLOR)) {
			$pdf->RoundedRect($this->marge_gauche, $tab_top - 8, $this->page_largeur - $this->marge_gauche - $this->marge_droite, 8, $roundradius, '1001', 'FD', $this->border_style, $title_bgcolor);
		} else {
			$pdf->RoundedRect($this->marge_gauche, $tab_top - 8, $this->page_largeur - $this->marge_gauche - $this->marge_droite, 8, $roundradius, '1001', 'FD', $this->border_style, $bgcolor);
		}
		$pdf->SetAlpha(1);
		//title line
		$pdf->RoundedRect($this->marge_gauche, $tab_top, $this->page_largeur - $this->marge_gauche - $this->marge_droite, $tab_height, $roundradius, '0110', 'S', $this->border_style, $bgcolor);

		$this->pdfTabTitles($pdf, $tab_top - 8, $tab_height + 8, $outputlangs, $hidetop);
	}

	/**
	 *  Show payments table
	 *
	 *  @param	TCPDF		&$pdf     		Object PDF
	 *  @param  Object		$object     	Object invoice
	 *	@param	int			$posy			Position y in PDF
	 *	@param	Translate	$outputlangs	Object langs for output
	 *	@return int							<0 if KO, >0 if OK
	 */
	function _tableau_versements(&$pdf, $object, $posy, $outputlangs)
	{
		global $conf;

		$sign = 1;
		if ($object->type == 2 && !empty($conf->global->INVOICE_POSITIVE_CREDIT_NOTE)) $sign = -1;

		$tab3_posx = 120;
		$tab3_top = $posy + 8;
		$tab3_width = 80;
		$tab3_height = 4;
		if ($this->page_largeur < 210) // To work with US executive format
		{
			$tab3_posx -= 20;
		}

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		$pdf->SetFont('', '', $default_font_size - 2);
		$pdf->SetXY($tab3_posx, $tab3_top - 5);
		$pdf->MultiCell(60, 5, $outputlangs->transnoentities("PaymentsAlreadyDone"), 0, 'L', 0);

		$pdf->line($tab3_posx, $tab3_top - 1 + $tab3_height, $tab3_posx + $tab3_width, $tab3_top - 1 + $tab3_height);

		$pdf->SetFont('', '', $default_font_size - 4);
		$pdf->SetXY($tab3_posx, $tab3_top);
		$pdf->MultiCell(20, 3, $outputlangs->transnoentities("Payment"), 0, 'L', 0);
		$pdf->SetXY($tab3_posx + 21, $tab3_top);
		$pdf->MultiCell(20, 3, $outputlangs->transnoentities("Amount"), 0, 'L', 0);
		$pdf->SetXY($tab3_posx + 40, $tab3_top);
		$pdf->MultiCell(20, 3, $outputlangs->transnoentities("Type"), 0, 'L', 0);
		$pdf->SetXY($tab3_posx + 58, $tab3_top);
		$pdf->MultiCell(20, 3, $outputlangs->transnoentities("Num"), 0, 'L', 0);

		$pdf->line($tab3_posx, $tab3_top - 1 + $tab3_height, $tab3_posx + $tab3_width, $tab3_top - 1 + $tab3_height);

		$y = 0;

		$pdf->SetFont('', '', $default_font_size - 4);

		// Loop on each payment
		$sql = "SELECT p.datep as date, p.fk_paiement as type, p.num_paiement as num, pf.amount as amount, pf.multicurrency_amount,";
		$sql .= " cp.code";
		$sql .= " FROM " . MAIN_DB_PREFIX . "paiementfourn_facturefourn as pf, " . MAIN_DB_PREFIX . "paiementfourn as p";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_paiement as cp ON p.fk_paiement = cp.id";
		$sql .= " WHERE pf.fk_paiementfourn = p.rowid and pf.fk_facturefourn = " . $object->id;
		$sql .= " ORDER BY p.datep";
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$y += 3;
				$row = $this->db->fetch_object($resql);

				$pdf->SetXY($tab3_posx, $tab3_top + $y);
				$pdf->MultiCell(20, 3, dol_print_date($this->db->jdate($row->date), 'day', false, $outputlangs, true), 0, 'L', 0);
				$pdf->SetXY($tab3_posx + 21, $tab3_top + $y);
				$pdf->MultiCell(20, 3, price($sign * (($conf->multicurrency->enabled && $object->multicurrency_tx != 1) ? $row->multicurrency_amount : $row->amount)), 0, 'L', 0);
				$pdf->SetXY($tab3_posx + 40, $tab3_top + $y);
				$oper = $outputlangs->transnoentitiesnoconv("PaymentTypeShort" . $row->code);

				$pdf->MultiCell(20, 3, $oper, 0, 'L', 0);
				$pdf->SetXY($tab3_posx + 58, $tab3_top + $y);
				$pdf->MultiCell(30, 3, $row->num, 0, 'L', 0);

				$pdf->line($tab3_posx, $tab3_top + $y + 3, $tab3_posx + $tab3_width, $tab3_top + $y + 3);

				$i++;
			}
		} else {
			$this->error = $this->db->lasterror();
			dol_syslog($this->db, $this->error, LOG_ERR);
			return -1;
		}
	}

	/**
	 *  Show top header of page.
	 *
	 *  @param	TCPDF		&$pdf     		Object PDF
	 *  @param  Object		$object     	Object to show
	 *  @param  int	    	$showaddress    0=no, 1=yes
	 *  @param  Translate	$outputlangs	Object lang for output
	 *  @return	void
	 */
	function _pagehead(&$pdf, $object, $showaddress, $outputlangs)
	{
		global $conf, $mysoc;

		// Translations
		$outputlangs->loadLangs(array("main", "commercial", "companies", "bills", "orders", "ultimatepdf@ultimatepdf"));

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		$dashdotted =  (!empty($conf->global->ULTIMATE_DASH_DOTTED)) ?
		$conf->global->ULTIMATE_DASH_DOTTED : 0;

		$bgcolor =  (!empty($conf->global->ULTIMATE_BGCOLOR_COLOR)) ?
		html2rgb($conf->global->ULTIMATE_BGCOLOR_COLOR) : array(170, 212, 255);
		
		$senderstyle =  (!empty($conf->global->ULTIMATE_SENDER_STYLE)) ?
		$conf->global->ULTIMATE_SENDER_STYLE : 'S';

		$receiptstyle =  (!empty($conf->global->ULTIMATE_RECEIPT_STYLE)) ?
		$conf->global->ULTIMATE_RECEIPT_STYLE : 'S';

		$bordercolor =  (!empty($conf->global->ULTIMATE_BORDERCOLOR_COLOR)) ?
		html2rgb($conf->global->ULTIMATE_BORDERCOLOR_COLOR) : array(0, 63, 127);

		$opacity =  (!empty($conf->global->ULTIMATE_BGCOLOR_OPACITY)) ?
		$conf->global->ULTIMATE_BGCOLOR_OPACITY : 0.5;

		$roundradius =  (!empty($conf->global->ULTIMATE_SET_RADIUS)) ?
		$conf->global->ULTIMATE_SET_RADIUS : 1;

		$textcolor =  (!empty($conf->global->ULTIMATE_TEXTCOLOR_COLOR)) ?
		html2rgb($conf->global->ULTIMATE_TEXTCOLOR_COLOR) : array(25, 25, 25);

		$qrcodecolor =  (!empty($conf->global->ULTIMATE_QRCODECOLOR_COLOR)) ?
		html2rgb($conf->global->ULTIMATE_QRCODECOLOR_COLOR) : array(25, 25, 25);

		$tab_width = $this->page_largeur - $this->marge_gauche - $this->marge_droite;
		$posy = $this->marge_haute;
		$posx = $this->marge_gauche + $tab_width / 2 ;
		$widthrecbox = $conf->global->ULTIMATE_WIDTH_RECBOX;

		//affiche repere de pliage //TODO
		/*if (!empty($conf->global->MAIN_DISPLAY_PROPOSALS_FOLD_MARK)) {
			$pdf->Line(0, ($this->page_hauteur) / 3, 3, ($this->page_hauteur) / 3);
		}*/

		// Do not add the BACKGROUND as this is for suppliers
		pdf_new_pagehead($pdf, $outputlangs, $this->page_hauteur);

		$pdf->SetTextColorArray($textcolor);
		$pdf->SetFont('', 'B', $default_font_size + 3);

		$pdf->SetXY($this->marge_gauche, $posy);

		// Other Logo
		$id = $conf->global->ULTIMATE_DESIGN;
		$upload_dir	= $conf->ultimatepdf->dir_output . '/otherlogo/' . $id . '/';
		$filearray = dol_dir_list($upload_dir, "files", 0, '', '(\.meta|_preview.*\.png)$', 'name', 0, 1);
		$otherlogo = $conf->ultimatepdf->dir_output . '/otherlogo/' . $id . '/' . $filearray[0]['name'];

		if (!empty($conf->global->ULTIMATE_DESIGN) && !empty($filearray[0]['relativename']) && is_readable($otherlogo) && $conf->global->PDF_DISABLE_ULTIMATE_OTHERLOGO_FILE == 0) {
			$logo_height = max(pdf_getUltimateHeightForOtherLogo($otherlogo, true), 20);
			$pdf->Image($otherlogo, $this->marge_gauche, $posy, 0, $logo_height);	// width=0 (auto)
		// Logo from company
		} elseif (empty($conf->global->PDF_DISABLE_MYCOMPANY_LOGO)) {
			if ($this->emetteur->logo) {
				$logodir = $conf->mycompany->dir_output;
				if (!empty($conf->mycompany->multidir_output[$object->entity])) $logodir = $conf->mycompany->multidir_output[$object->entity];
				if (empty($conf->global->MAIN_PDF_USE_LARGE_LOGO)) {
					$logo = $logodir . '/logos/thumbs/' . $mysoc->logo_small;
				} else {
					$logo = $logodir . '/logos/' . $mysoc->logo;
				}
				if (is_readable($logo)) {
					$logo_height = max(pdf_getUltimateHeightForLogo($logo, true), 20);
					$pdf->Image($logo, $this->marge_gauche, $posy, 0, $logo_height);	// width=0 (auto)
				} else {
					$pdf->SetTextColor(200, 0, 0);
					$pdf->SetFont('', 'B', $default_font_size - 2);
					$pdf->RoundedRect($this->marge_gauche, $this->marge_haute, 100, 20, $roundradius, '1111', $senderstyle, $this->border_style, $bgcolor);
					$pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorLogoFileNotFound", $logo), 0, 'L');
					$pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorGoToGlobalSetup"), 0, 'L');
				}
			}
		} else {
			$pdf->RoundedRect($this->marge_gauche, $this->marge_haute, 90, 20, $roundradius, '1111', $senderstyle, $this->border_style, $bgcolor);
			$pdf->SetFont('', 'B', $default_font_size + 3);
			$text =  !empty($conf->global->ULTIMATE_PDF_ALIAS_COMPANY) ? $conf->global->ULTIMATE_PDF_ALIAS_COMPANY : $this->emetteur->name;
			$pdf->MultiCell(90, 8, $outputlangs->convToOutputCharset($text), 0, 'C');
			$logo_height = 20;
		}	
		
		//Display Thirdparty barcode at top			
		if (!empty($conf->global->ULTIMATEPDF_GENERATE_SUPPLIERINVOICES_WITH_TOP_BARCODE)) {
			$result = $object->thirdparty->fetch_barcode();
			$barcode = $object->thirdparty->barcode;
			$posxbarcode = $this->page_largeur * 2 / 3;
			$posybarcode = $posy - $this->marge_haute;
			$pdf->SetXY($posxbarcode, $posy - $this->marge_haute);
			$styleBc = array(
				'position' => '',
				'align' => 'R',
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
				'fontsize' => 8,
				'stretchtext' => 4
			);
			if ($barcode <= 0) {
				if (empty($this->messageErrBarcodeSet)) {
					setEventMessages($outputlangs->trans("BarCodeDataForThirdpartyMissing"), null, 'errors');
					$this->messageErrBarcodeSet = true;
				}
			} else {
				// barcode_type_code
				$pdf->write1DBarcode($barcode, $object->thirdparty->barcode_type_code, $posxbarcode, $posybarcode, '', 12, 0.4, $styleBc, 'R');
			}
		}

		if ($logo_height <= 30) {
			$heightQRcode = $logo_height;
		} else {
			$heightQRcode = 30;
		}
		$posxQRcode = $this->marge_gauche + $tab_width / 2;
		// set style for QR-code
		$styleQr = array(
			'border' => false,
			'vpadding' => 'auto',
			'hpadding' => 'auto',
			'fgcolor' => $qrcodecolor,
			'bgcolor' => false, //array(255,255,255)
			'module_width' => 1, // width of a single module in points
			'module_height' => 1 // height of a single module in points
		);

		// QR-code
		if (!empty($conf->global->ULTIMATEPDF_GENERATE_SUPPLIERINVOICES_WITH_TOP_QRCODE)) {
			$code = pdf_codeContents();
			$pdf->write2DBarcode($code, 'QRCODE,L', $posxQRcode, $posy, $heightQRcode, $heightQRcode, $styleQr, 'N');
		}
		// My Company QR-code
		if (!empty($conf->global->ULTIMATEPDF_GENERATE_SUPPLIERINVOICES_WITH_MYCOMP_QRCODE)) {
			$code = pdf_mycompCodeContents();
			$pdf->write2DBarcode($code, 'QRCODE,L', $posxQRcode, $posy, $heightQRcode, $heightQRcode, $styleQr, 'N');
		}

		if (!empty($conf->global->ULTIMATEPDF_GENERATE_INVOICES_WITH_TOP_QRCODE) || ($conf->global->ULTIMATEPDF_GENERATE_INVOICES_WITH_MYCOMP_QRCODE)) {
			$rightSideWidth = $tab_width / 2 - $heightQRcode;
			$posx = $this->marge_gauche + $tab_width / 2 + $heightQRcode;
		} else {
			$rightSideWidth = $tab_width / 2;
			$posx = $this->marge_gauche + $tab_width / 2 ;
		}

		//Document name
		$pdf->SetFont('', 'B', $default_font_size + 3);
		$pdf->SetXY($posx, $posy);
		$pdf->SetTextColorArray($textcolor);
		$pdf->MultiCell($rightSideWidth, 3, $outputlangs->transnoentities("SupplierInvoice") . " " . $outputlangs->convToOutputCharset($object->ref), '', 'R');

		$posy = $pdf->getY();
		$pdf->SetFont('', '', $default_font_size - 1);

		if (!empty($object->fk_project)) {
			$outputlangs->load('projects');
			$pdf->SetFont('', '', $default_font_size - 2);
			$proj = new Project($this->db);
			$proj->fetch($object->fk_project);
			$pdf->SetXY($posx, $posy);
			$pdf->SetTextColorArray($textcolor);
			$pdf->SetFont('', 'B', $default_font_size);
			$pdf->MultiCell($rightSideWidth, 4, $outputlangs->transnoentities("RefProject") . " : " . $outputlangs->transnoentities($proj->ref), 0, 'R');
		}

		$posy = $pdf->getY();
		$pdf->SetTextColorArray($textcolor);

		$pdf->SetXY($posx, $posy);

		// Show list of linked objects
		$posy = pdf_writeLinkedObjects($pdf, $object, $outputlangs, $posx, $posy, $rightSideWidth, 3, 'R', $default_font_size);

		$posy = $pdf->getY();

		if ($showaddress) {
			// Sender properties
			$carac_emetteur = '';
			// Add internal contact of proposal if defined
			$arrayidcontact = $object->getIdContact('internal', 'SALESREPFOLL');
			if (count($arrayidcontact) > 0) {
				$object->fetch_user($arrayidcontact[0]);
				$carac_emetteur .= ($carac_emetteur ? "\n" : '') . $outputlangs->convToOutputCharset($object->user->getFullName($outputlangs)) . "\n";
			}

			$carac_emetteur .= pdf_element_build_address($outputlangs, $this->emetteur, $object->thirdparty, '', 0, 'source', $object);

			// Show sender
			$posy = $logo_height + $this->marge_haute + 4;
			$posx = $this->marge_gauche;
			
			if (!empty($conf->global->MAIN_INVERT_SENDER_RECIPIENT)) $posx = $this->page_largeur - $this->marge_droite - $widthrecbox;
			if (($conf->global->ULTIMATE_PDF_SUPPLIERINVOICE_ADDALSOTARGETDETAILS == 1) || (!empty($conf->global->MAIN_INFO_SOCIETE_NOTE) && !empty($this->emetteur->note_private)) || (!empty($conf->global->MAIN_INFO_TVAINTRA) && !empty($this->emetteur->tva_intra))) {
				$hautcadre = 48;
			} else {
				$hautcadre = 48;
			}

			if ($roundradius == 0) {
				$roundradius = 0.1; //RoundedRect don't support $roundradius to be 0
			}
			$this->border_style = array('width' => 0.2, 'cap' => 'butt', 'join' => 'miter', 'dash' => $dashdotted, 'color' => $bordercolor);
			// Show sender frame
			$pdf->SetTextColorArray($textcolor);
			$pdf->SetFont('', '', $default_font_size - 2);
			$pdf->SetAlpha($opacity);
			$pdf->RoundedRect($posx, $posy, $widthrecbox, $hautcadre, $roundradius, '1111', $senderstyle, $this->border_style, $bgcolor);
			$pdf->SetAlpha(1);
			$pdf->SetXY($posx + 2, $posy);
			$pdf->MultiCell($widthrecbox - 4, 5, $outputlangs->transnoentities("BillFrom") . ":", 0, 'R');

			// Show sender name
			$pdf->SetXY($posx + 2, $posy + 3);
			$pdf->SetFont('', 'B', $default_font_size - 1);
			if (!empty($conf->global->ULTIMATE_PDF_ALIAS_COMPANY)) {
				$pdf->MultiCell($widthrecbox - 5, 4, $outputlangs->convToOutputCharset($conf->global->ULTIMATE_PDF_ALIAS_COMPANY), 0, 'L');
			} else {
				$pdf->MultiCell($widthrecbox - 5, 4, $outputlangs->convToOutputCharset($this->emetteur->name), 0, 'L');
			}

			$posy = $pdf->getY();

			// Show sender information
			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->writeHTMLCell($widthrecbox - 5, 4, $posx + 2, $posy, $carac_emetteur, 0, 2, 0, true, 'L', true);

			// If BILLING contact defined on invoice, we use it
			$usecontact = false;
			$arrayidcontact = $object->getIdContact('internal', 'BILLING');
			if (count($arrayidcontact) > 0) {
				$usecontact = true;
				$result = $object->fetch_contact($arrayidcontact[0]);
			}

			//Recipient name
			// On peut utiliser le nom de la societe du contact
			if ($usecontact && !empty($conf->global->MAIN_USE_COMPANY_NAME_OF_CONTACT)) {
				$thirdparty = $object->contact;
			} else {
				$thirdparty = $mysoc;
			}

			$carac_client_name = pdfBuildThirdpartyName($thirdparty, $outputlangs);

			$carac_client = pdf_build_address($outputlangs, $this->emetteur, $mysoc, ((!empty($object->contact)) ? $object->contact : null), $usecontact, 'target', $object);

			// Show recipient
			$widthrecboxrecipient = $this->page_largeur - $this->marge_droite - $this->marge_gauche - $conf->global->ULTIMATE_WIDTH_RECBOX - 2;
			$posy = $logo_height + $this->marge_haute + 4;
			$posx = $this->page_largeur - $this->marge_droite - $widthrecboxrecipient;
			if (!empty($conf->global->ULTIMATE_INVERT_SENDER_RECIPIENT) && ($conf->global->ULTIMATE_INVERT_SENDER_RECIPIENT != "no")) $posx = $this->marge_gauche;

			// Show recipient frame
			$pdf->SetTextColor(0, 0, 0);
			$pdf->SetFont('', '', $default_font_size - 2);

			// Show billing address
			$pdf->SetXY($posx + 2, $posy);
			$pdf->MultiCell($widthrecboxrecipient - 4, 5, $outputlangs->transnoentities("BillTo") . ":", 0, 'R');
			$pdf->SetAlpha($opacity);
			$pdf->RoundedRect($posx, $posy, $widthrecboxrecipient, $hautcadre, $roundradius, '1111', $receiptstyle, $this->border_style, $bgcolor);
			$pdf->SetAlpha(1);

			// Show recipient name
			$pdf->SetXY($posx + 2, $posy + 3);
			$pdf->SetFont('', 'B', $default_font_size);
			$pdf->MultiCell($widthrecboxrecipient, 4, $carac_client_name, 0, 'L');

			$posy = $pdf->getY();

			// Show recipient information
			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->SetXY($posx + 2, $posy);
			$pdf->MultiCell($widthrecboxrecipient, 4, $carac_client, 0, 'L');

			// Other informations
			$pdf->SetFillColor(255, 255, 255);

			// Date
			$width = $tab_width / 4 - 1.5;
			$RoundedRectHeight = $this->marge_haute + $logo_height + $hautcadre + 6;
			$pdf->SetAlpha($opacity);
			$pdf->RoundedRect($this->marge_gauche, $RoundedRectHeight, $width, 6, $roundradius, '1001', 'FD', $this->border_style, $bgcolor);
			$pdf->SetAlpha(1);
			$pdf->SetFont('', 'B', $default_font_size - 2);
			$pdf->SetXY($this->marge_gauche, $RoundedRectHeight);
			$pdf->SetTextColorArray($textcolor);
			$pdf->MultiCell($width, 5, $outputlangs->transnoentities("Date"), 0, 'C', false);

			if (!empty($object->date)) {
				$pdf->SetFont('', '', $default_font_size - 2);
				$pdf->SetXY($this->marge_gauche, $RoundedRectHeight + 6);
				$pdf->SetTextColorArray($textcolor);
				$pdf->SetFillColor(255, 255, 255);
				$pdf->MultiCell($width, 6, dol_print_date($object->date, "day", false, $outputlangs, true), '0', 'C');
			} else {
				$pdf->SetFont('', '', $default_font_size - 2);
				$pdf->SetXY($this->marge_gauche, $RoundedRectHeight + 6);
				$pdf->SetTextColorArray($textcolor);
				$pdf->SetFillColor(255, 255, 255);
				$pdf->MultiCell($width, 6, '', '0', 'C');
			}

			// Commercial Interlocutor
			$pdf->SetAlpha($opacity);
			$pdf->RoundedRect($this->marge_gauche + $width + 2, $RoundedRectHeight, $width, 6, $roundradius, '1001', 'FD', $this->border_style, $bgcolor);
			$pdf->SetAlpha(1);
			$pdf->SetFont('', 'B', $default_font_size - 2);
			$pdf->SetTextColorArray($textcolor);
			$text = '<div style="line-height:90%;">' . $outputlangs->transnoentities("SalesRepresentative") . '</div>';
			$pdf->writeHTMLCell($width, 5, $this->marge_gauche + $width + 2, $RoundedRectHeight + 0.5, $text, 0, 0, false, true, 'C', true);

			$contact_id = $object->getIdContact('internal', 'SALESREPFOLL');

			if (!empty($contact_id)) {
				$object->fetch_user($contact_id[0]);
				$pdf->SetFont('', '', $default_font_size - 2);
				$pdf->SetXY($this->marge_gauche + $width + 2, $RoundedRectHeight + 6);
				$pdf->SetTextColorArray($textcolor);
				$pdf->MultiCell($width, 5, $object->user->firstname . ' ' . $object->user->lastname, 0, 'C', false);
				$pdf->SetXY($this->marge_gauche + $width + 2, $RoundedRectHeight + 9);
				$pdf->SetTextColorArray($textcolor);
				$pdf->MultiCell($width, 7, $object->user->office_phone, '0', 'C');
			} else if ($object->user_author_id) {
				$object->fetch_user($object->user_author_id);			
				$pdf->SetFont('', '', $default_font_size - 2);
				$pdf->SetXY($this->marge_gauche + $width + 2, $RoundedRectHeight + 6);
				$pdf->SetTextColorArray($textcolor);
				$pdf->MultiCell($width, 6, $object->user->firstname . ' ' . $object->user->lastname, '0', 'C');
				$pdf->SetXY($this->marge_gauche + $width + 2, $RoundedRectHeight + 9);
				$pdf->SetTextColorArray($textcolor);
				$pdf->MultiCell($width, 7, $object->user->office_phone, '0', 'C');
			} else {
				$pdf->SetFont('', '', $default_font_size - 2);
				$pdf->SetXY($this->marge_gauche + $width + 2, $RoundedRectHeight + 6);				
				$pdf->SetTextColorArray($textcolor);
				$pdf->SetFillColor(255, 255, 255);
				$pdf->MultiCell($width, 6, '', '0', 'C');
			}

			// Reference Supplier
			$pdf->SetAlpha($opacity);
			$pdf->RoundedRect($this->marge_gauche + $width * 2 + 4, $RoundedRectHeight, $width, 6, $roundradius, '1001', 'FD', $this->border_style, $bgcolor);
			$pdf->SetAlpha(1);
			$pdf->SetFont('', 'B', $default_font_size - 2);
			$pdf->SetXY($this->marge_gauche + $width * 2 + 4, $RoundedRectHeight);
			$pdf->SetTextColorArray($textcolor);
			$pdf->MultiCell($width, 5, $outputlangs->transnoentities("RefSupplier"), 0, 'C', false);

			if ($object->ref_supplier) {
				$pdf->SetFont('', '', $default_font_size - 2);
				$pdf->SetXY($this->marge_gauche + $width * 2 + 4, $RoundedRectHeight + 6);
				$pdf->SetTextColorArray($textcolor);
				$pdf->MultiCell($width, 6, $outputlangs->transnoentities($object->ref_supplier), '0', 'C');
			} else {
				$pdf->SetFont('', '', $default_font_size - 2);
				$pdf->SetXY($this->marge_gauche + $width * 2 + 4, $RoundedRectHeight + 6);
				$pdf->SetTextColorArray($textcolor);
				$pdf->SetFillColor(255, 255, 255);
				$pdf->MultiCell($width, 6, '', '0', 'C');
			}

			// Supplier Code
			$pdf->SetAlpha($opacity);
			$pdf->RoundedRect($this->marge_gauche + $width * 3 + 6, $RoundedRectHeight, $width, 6, $roundradius, '1001', 'FD', $this->border_style, $bgcolor);
			$pdf->SetAlpha(1);
			$pdf->SetFont('', 'B', $default_font_size - 2);
			$pdf->SetXY($this->marge_gauche + $width * 3 + 6, $RoundedRectHeight);
			$pdf->SetTextColorArray($textcolor);
			$pdf->MultiCell($width, 5, $outputlangs->transnoentities("SupplierCode"), 0, 'C', false);

			if ($object->thirdparty->code_fournisseur) {
				$pdf->SetFont('', '', $default_font_size - 2);
				$pdf->SetXY($this->marge_gauche + $width * 3 + 6, $RoundedRectHeight + 6);
				$pdf->SetTextColorArray($textcolor);
				$pdf->MultiCell($width, 6, $outputlangs->transnoentities($object->thirdparty->code_fournisseur), '0', 'C');
			}
		}
		$pdf->SetTextColorArray($textcolor);
	}

	/**
	 *   	Show footer of page. Need this->emetteur object
	 *
	 *   	@param	TCPDF		&$pdf     			PDF
	 * 		@param	Object		$object				Object to show
	 *      @param	Translate	$outputlangs		Object lang for output
	 * 		@param	int			$hidefreetext		1=Hide free text
	 *      @return	int								Return height of bottom margin including footer text
	 */
	function _pagefoot(&$pdf, $object, $outputlangs, $hidefreetext = 0)
	{
		global $conf, $mysoc;
		$showdetails = $conf->global->MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS;
		return pdf_ultimatepagefoot($pdf, $outputlangs, 'SUPPLIER_INVOICE_FREE_TEXT', $mysoc, $this->marge_basse, $this->marge_gauche, $this->page_hauteur, $object, $showdetails, $this->hidefreetext, $this->footertextcolor);
	}

	/**
	 * _setFontForMulticurrencyCode
	 *
	 * @param  TCPDF		$pdf     			PDF
	 * @param  Object		$object				Object to show
	 * @param  Translate	$outputlangs		Object lang for output
	 * @return int 								Return size of font
	 */
	function _setFontForMulticurrencyCode(&$pdf, $object, $outputlangs)
	{
		global $conf;
		$default_font_size = pdf_getPDFFontSize($outputlangs);
		if ($object->multicurrency_code == 'SAR' || $object->multicurrency_code == 'IRR' || $object->multicurrency_code == 'OMR' || $object->multicurrency_code == 'QAR' || $object->multicurrency_code == 'YER') {
			$pdf->SetFont('MarkaziText', '', $default_font_size + 2);
		} elseif ($object->multicurrency_code == 'RUB' || $object->multicurrency_code == 'PLN' || $object->multicurrency_code == 'BYR') {
			$pdf->SetFont('DejaVuSans', '', $default_font_size);
		} else {
			$pdf->SetFont($conf->global->MAIN_PDF_FORCE_FONT, '', $default_font_size);
		}
	}

	/**
	 *   	Define Array Column Field
	 *
	 *   	@param	object			$object    		common object
	 *   	@param	Translate		$outputlangs    langs
	 *      @param	int				$hidedetails	Do not show line details
	 *      @param	int				$hidedesc		Do not show desc
	 *      @param	int				$hideref		Do not show ref
	 *      @return	null
	 */
	public function defineColumnField($object, $outputlangs, $hidedetails = 0, $hidedesc = 0, $hideref = 0)
	{
		global $conf, $hookmanager;

		// Default field style for content
		$this->defaultContentsFieldsStyle = array(
			'align' => 'R', // R,C,L
			'padding' => array(0.5, 0.5, 0.5, 0.5), // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
		);

		// Default field style for content
		if ($conf->global->ULTIMATE_PDF_BORDER_LEFT_STATUS == 'yes') {
			$this->defaultTitlesFieldsStyle = array(
				'align' => 'C', // R,C,L
				'padding' => array(0.5, 0.5, 0.5, 0.5), // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
			);
		} else { 
				$this->defaultTitlesFieldsStyle = array(
					'align' => 'R', // R,C,L
					'padding' => array(0.5, 0.5, 0.5, 0.5), // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
				);
			}


		/*
	     * For exemple
	     $this->cols['theColKey'] = array(
	     'rank' => $rank, // int : use for ordering columns
	     'width' => 20, // the column width in mm
	     'title' => array(
	     'textkey' => 'yourLangKey', // if there is no label, yourLangKey will be translated to replace label
	     'label' => ' ', // the final label : used fore final generated text
	     'align' => 'L', // text alignement :  R,C,L
	     'padding' => array(0.5,0.5,0.5,0.5), // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
	     ),
	     'content' => array(
	     'align' => 'L', // text alignement :  R,C,L
	     'padding' => array(0.5,0.5,0.5,0.5), // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
	     ),
	     );
	     */

		$rank = 0; // do not use negative rank
		$this->cols['num'] = array(
			'rank' => $rank,
			'width' => (empty($conf->global->ULTIMATE_DOCUMENTS_WITH_NUMBERING_WIDTH) ? 10 : $conf->global->ULTIMATE_DOCUMENTS_WITH_NUMBERING_WIDTH), // in mm 
			'status' => false,
			'title' => array(
				'textkey' => 'Numbering', // use lang key is usefull in somme case with module
				'align' => 'C',
				// 'textkey' => 'yourLangKey', // if there is no label, yourLangKey will be translated to replace label
				// 'label' => ' ', // the final label
				'padding' => array(0.5, 0.5, 0.5, 0.5), // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
			),
			'content' => array(
				'align' => 'C',
			),
		);
		if (!empty($conf->global->ULTIMATE_SUPPLIERINVOICES_WITH_LINE_NUMBER)) {
			$this->cols['num']['status'] = true;
		}

		$rank = $rank + 10; // do not use negative rank
		$this->cols['ref'] = array(
			'rank' => $rank,
			'width' => (empty($conf->global->ULTIMATE_DOCUMENTS_WITH_REF_WIDTH) ? 16 : $conf->global->ULTIMATE_DOCUMENTS_WITH_REF_WIDTH), // in mm 
			'status' => false,
			'title' => array(
				'textkey' => 'RefShort', // use lang key is usefull in somme case with module
				'align' => 'C',
				// 'textkey' => 'yourLangKey', // if there is no label, yourLangKey will be translated to replace label
				// 'label' => ' ', // the final label
				'padding' => array(0.5, 0.5, 0.5, 0.5), // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
			),
			'content' => array(
				'align' => 'L',
			),
			'border-left' => false, // remove left line separator
		);

		if ($conf->global->ULTIMATE_DOCUMENTS_WITH_REF == "yes" && $this->atleastoneref == true) {
			$this->cols['ref']['status'] = true;
		}
		if (!empty($conf->global->ULTIMATE_SUPPLIERINVOICES_WITH_LINE_NUMBER) && $conf->global->ULTIMATE_PDF_BORDER_LEFT_STATUS == 'yes') {
			$this->cols['ref']['border-left'] = true;
		}

		$rank = $rank + 10; // do not use negative rank
		$this->cols['desc'] = array(
			'rank' => $rank,
			'width' => false, // only for desc
			'status' => true,
			'title' => array(
				'textkey' => 'Designation', // use lang key is usefull in somme case with module
				'align' => 'C',
				// 'textkey' => 'yourLangKey', // if there is no label, yourLangKey will be translated to replace label
				// 'label' => ' ', // the final label
				'padding' => array(0.5, 0.5, 0.5, 0.5), // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
			),
			'content' => array(
				'align' => 'L',
			),
			'border-left' => false, // remove left line separator
		);

		if (!empty($conf->global->ULTIMATE_SUPPLIERINVOICES_WITH_LINE_NUMBER) && $conf->global->ULTIMATE_PDF_BORDER_LEFT_STATUS == 'yes' || ($conf->global->ULTIMATE_DOCUMENTS_WITH_REF == "yes" && $this->atleastoneref == true) && $conf->global->ULTIMATE_PDF_BORDER_LEFT_STATUS == 'yes') {
			$this->cols['desc']['border-left'] = true;
		}

		$rank = $rank + 10;
		$this->cols['picture'] = array(
			'rank' => $rank,
			'width' => (empty($conf->global->MAIN_DOCUMENTS_WITH_PICTURE_WIDTH) ? 20 : $conf->global->MAIN_DOCUMENTS_WITH_PICTURE_WIDTH), // in mm
			'status' => false,
			'title' => array(
				'textkey' => 'Picture',
				'label' => ' '
			),
			'content' => array(
				'padding' => array(0, 0, 0, 0), // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
			),
			'border-left' => false, // remove left line separator
		);

		if ($conf->global->ULTIMATE_GENERATE_SUPPLIERINVOICES_WITH_PICTURE == 1) {
			$this->cols['picture']['status'] = true;
		}

		$rank = $rank + 10;
		$this->cols['vat'] = array(
			'rank' => $rank,
			'status' => false,
			'width' => (empty($conf->global->ULTIMATE_DOCUMENTS_WITH_TVA_WIDTH) ? 12 : $conf->global->ULTIMATE_DOCUMENTS_WITH_TVA_WIDTH), // in mm
			'title' => array(
				'textkey' => 'VAT'
			),
			'content' => array(
				'align' => 'R'
			),
			'border-left' => false, // add left line separator
		);

		if (($conf->global->ULTIMATE_GENERATE_DOCUMENTS_WITHOUT_VAT == "no") && empty($conf->global->ULTIMATE_SHOW_HIDE_VAT_COLUMN)) {
			$this->cols['vat']['status'] = true;
		}
		if ($conf->global->ULTIMATE_PDF_BORDER_LEFT_STATUS == 'yes') {
			$this->cols['vat']['border-left'] = true;
		}

		$rank = $rank + 10;
		$this->cols['subprice'] = array(
			'rank' => $rank,
			'width' => (empty($conf->global->ULTIMATE_DOCUMENTS_WITH_UP_WIDTH) ? 19 : $conf->global->ULTIMATE_DOCUMENTS_WITH_UP_WIDTH), // in mm 
			'status' => false,
			'title' => array(
				'textkey' => 'PriceUHT'
			),
			'content' => array(
				'align' => 'R',
			),
			'border-left' => false, // add left line separator
		);
		if (!empty($conf->global->ULTIMATE_GENERATE_SUPPLIERINVOICES_WITH_PRICEUHT)) {
			$this->cols['subprice']['status'] = true;
		}
		if ($conf->global->ULTIMATE_PDF_BORDER_LEFT_STATUS == 'yes') {
			$this->cols['subprice']['border-left'] = true;
		}

		$rank = $rank + 10;
		$this->cols['discount'] = array(
			'rank' => $rank,
			'width' => (empty($conf->global->ULTIMATE_DOCUMENTS_WITH_DISCOUNT_WIDTH) ? 12 : $conf->global->ULTIMATE_DOCUMENTS_WITH_DISCOUNT_WIDTH), // in mm
			'status' => false,
			'title' => array(
				'textkey' => 'ReductionShort'
			),
			'content' => array(
				'align' => 'R',
			),
			'border-left' => false, // add left line separator
		);

		if ($this->atleastonediscount && !empty($conf->global->ULTIMATE_GENERATE_SUPPLIERINVOICES_WITH_DISCOUNT)) {
			$this->cols['discount']['status'] = true;
		}
		if ($conf->global->ULTIMATE_PDF_BORDER_LEFT_STATUS == 'yes') {
			$this->cols['discount']['border-left'] = true;
		}

		$rank = $rank + 10;
		$this->cols['upafter'] = array(
			'rank' => $rank,
			'width' => (empty($conf->global->ULTIMATE_DOCUMENTS_WITH_UP_WIDTH) ? 19 : $conf->global->ULTIMATE_DOCUMENTS_WITH_UP_WIDTH), // in mm 
			'status' => false,
			'title' => array(
				'textkey' => 'PuAfter'
			),
			'content' => array(
				'align' => 'R',
			),
			'border-left' => false, // add left line separator
		);
		if (!empty($conf->global->ULTIMATE_GENERATE_SUPPLIERINVOICES_WITH_PUAFTER) && $this->atleastonediscount) {
			$this->cols['upafter']['status'] = true;
		}
		if ($conf->global->ULTIMATE_PDF_BORDER_LEFT_STATUS == 'yes') {
			$this->cols['upafter']['border-left'] = true;
		}

		$rank = $rank + 10;
		$this->cols['qty'] = array(
			'rank' => $rank,
			'width' => (empty($conf->global->ULTIMATE_DOCUMENTS_WITH_QTY_WIDTH) ? 16 : $conf->global->ULTIMATE_DOCUMENTS_WITH_QTY_WIDTH), // in mm 
			'status' => false,
			'title' => array(
				'textkey' => 'Qty'
			),
			'content' => array(
				'align' => 'R',
			),
			'border-left' => false, // add left line separator
		);

		if (!empty($conf->global->ULTIMATE_GENERATE_SUPPLIERINVOICES_WITH_QTY)) {
			$this->cols['qty']['status'] = true;
		}
		if ($conf->global->ULTIMATE_PDF_BORDER_LEFT_STATUS == 'yes') {
			$this->cols['qty']['border-left'] = true;
		}

		$rank = $rank + 10;
		$this->cols['weight'] = array(
			'rank' => $rank,
			'width' => 12, // in mm
			'status' => false,
			'title' => array(
				'textkey' => 'Weight'
			),
			'content' => array(
				'align' => 'R',
			),
			'border-left' => false, // add left line separator
		);

		if (!empty($conf->global->ULTIMATE_GENERATE_SUPPLIERINVOICES_WITH_WEIGHT_COLUMN)) {
			$this->cols['weight']['status'] = true;
		}
		if ($conf->global->ULTIMATE_PDF_BORDER_LEFT_STATUS == 'yes') {
			$this->cols['weight']['border-left'] = true;
		}

		$rank = $rank + 10;
		$this->cols['progress'] = array(
			'rank' => $rank,
			'width' => 19, // in mm
			'status' => false,
			'title' => array(
				'textkey' => 'Progress'
			),
			'content' => array(
				'align' => 'R',
			),
			'border-left' => false, // add left line separator
		);

		if ($this->situationinvoice) {
			$this->cols['progress']['status'] = true;
		}
		if ($conf->global->ULTIMATE_PDF_BORDER_LEFT_STATUS == 'yes') {
			$this->cols['progress']['border-left'] = true;
		}

		$rank = $rank + 10;
		$this->cols['unit'] = array(
			'rank' => $rank,
			'width' => (empty($conf->global->ULTIMATE_DOCUMENTS_WITH_UNIT_WIDTH) ? 11 : $conf->global->ULTIMATE_DOCUMENTS_WITH_UNIT_WIDTH), // in mm 
			'status' => false,
			'title' => array(
				'textkey' => 'Unit'
			),
			'content' => array(
				'align' => 'R',
			),
			'border-left' => false, // add left line separator
		);

		if ($conf->global->PRODUCT_USE_UNITS) {
			$this->cols['unit']['status'] = true;
		}
		if ($conf->global->ULTIMATE_PDF_BORDER_LEFT_STATUS == 'yes') {
			$this->cols['unit']['border-left'] = true;
		}

		$rank = $rank + 10;
		$this->cols['totalexcltax'] = array(
			'rank' => $rank,
			'width' => 26, // in mm
			'status' => false,
			'title' => array(
				'textkey' => 'TotalHT'
			),
			'content' => array(
				'align' => 'R',
			),
			'border-left' => false, // add left line separator
		);

		if (!$conf->global->ULTIMATE_SHOW_LINE_TTTC) {
			$this->cols['totalexcltax']['status'] = true;
		}
		if ($conf->global->ULTIMATE_PDF_BORDER_LEFT_STATUS == 'yes') {
			$this->cols['totalexcltax']['border-left'] = true;
		}

		$rank = $rank + 10;
		$this->cols['totalincltax'] = array(
			'rank' => $rank,
			'width' => 26, // in mm
			'status' => false,
			'title' => array(
				'textkey' => 'TotalTTC'
			),
			'content' => array(
				'align' => 'R',
			),
			'border-left' => false, // add left line separator
		);

		if ($conf->global->ULTIMATE_SHOW_LINE_TTTC) {
			$this->cols['totalincltax']['status'] = true;
		}
		if ($conf->global->ULTIMATE_PDF_BORDER_LEFT_STATUS == 'yes') {
			$this->cols['totalincltax']['border-left'] = true;
		}

		$parameters = array(
			'object' => $object,
			'outputlangs' => $outputlangs,
			'hidedetails' => $hidedetails,
			'hidedesc' => $hidedesc,
			'hideref' => $hideref
		);

		$reshook = $hookmanager->executeHooks('defineColumnField', $parameters, $this);    // Note that $object may have been modified by hook
		if ($reshook < 0) {
			setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
		} elseif (empty($reshook)) {
			$this->cols = array_replace($this->cols, $hookmanager->resArray); // array_replace is used to preserve keys
		} else {
			$this->cols = $hookmanager->resArray;
		}
	}
}

?>
