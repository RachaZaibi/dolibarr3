<?php
/* Copyright (C) 2004-2010 Laurent Destailleur    <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010 Regis Houssin          <regis.houssin@capnetworks.com>
 * Copyright (C) 2008      Raphael Bertrand       <raphael.bertrand@resultic.fr>
 * Copyright (C) 2010      Juanjo Menent		  <jmenent@2byte.es>
 * Copyright (C) 2011-2022 Philippe Grand		  <philippe.grand@atoo-net.com>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 * or see http://www.gnu.org/
 */

/**
 *	\file       htdocs/custom/ultimatepdf/core/modules/facture/doc/pdf_ultimate_invoice.modules.php
 *	\ingroup    facture
 *	\brief      File of class to generate invoices from pdf_ultimate_invoice model
 */

use NumberToWords\NumberToWords;

require_once(DOL_DOCUMENT_ROOT . "/core/modules/facture/modules_facture.php");
require_once(DOL_DOCUMENT_ROOT . "/product/class/product.class.php");
require_once(DOL_DOCUMENT_ROOT . "/core/lib/company.lib.php");
require_once(DOL_DOCUMENT_ROOT . "/core/lib/functions2.lib.php");
require_once(DOL_DOCUMENT_ROOT . "/core/lib/pdf.lib.php");
require_once(DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php');
require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
dol_include_once("/ultimatepdf/lib/ultimatepdf.lib.php");
dol_include_once("/ultimatepdf/lib/vendor/autoload.php");
dol_include_once("/ultimatepdf/class/ultimateBarcode.trait.class.php");


/**
 *	Class to manage PDF invoice with pdf_ultimate_invoice template 
 */

class pdf_ultimate_invoice extends ModelePDFFactures
{
	use UltimateBarcode;

	/**
	 * @var DoliDb Database handler
	 */
	public $db;

	/**
	 * @var int The environment ID when using a multicompany module
	 */
	public $entity;

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
	 * @var int roundradius
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
	 * @var int description column width
	 */
	public $desc_width;

	/**
	 * @var int ref column width
	 */
	public $ref_width;

	/**
	 * @var int vat column width
	 */
	public $tva_width;

	/**
	 * @var int up column width
	 */
	public $up_width;

	/**
	 * @var int up after column width
	 */
	public $upafter_width;

	/**
	 * @var int qty column width
	 */
	public $qty_width;

	/**
	 * @var int weight column width
	 */
	public $weight_width;

	/**
	 * @var int discount column width
	 */
	public $discount_width;

	/**
	 * @var int footertextcolor
	 */
	public $footertextcolor;

	/**
	 * Issuer
	 * @var Societe
	 */
	public $emetteur;

	/**
	 * @var bool Situation invoice type
	 */
	public $situationinvoice;

	/**
	 * @var float X position for the situation progress column
	 */
	public $posxprogress;

	/**
	 * @var bool Barcode error message
	 */
	private $messageErrBarcodeSet;


	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		global $conf, $langs, $mysoc;

		// Translations
		$langs->loadLangs(array("main", "perso", "bills", "ultimatepdf@ultimatepdf"));

		$this->db = $db;
		$this->name = "ultimate_invoice";
		$this->description = $langs->trans('PDFUltimate_invoice1Description');
		$this->update_main_doc_field = 1; // Save the name of generated file as the main doc when generating a doc with this template
		$_SESSION['ultimatepdf_model'] = true;

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

		$this->option_logo = 1;                    // Display logo
		$this->option_tva = 1;                     // Manage the vat option FACTURE_TVAOPTION
		$this->option_modereg = 1;                 // Display payment mode
		$this->option_condreg = 1;                 // Display payment terms
		$this->option_codeproduitservice = 1;      // Display product-service code
		$this->option_multilang = 1;               // Available in several languages
		$this->option_escompte = 1;                // Displays if there has been a discount
		$this->option_credit_note = 1;             // Support credit notes
		$this->option_freetext = 1;				   // Support add of a personalised text
		$this->option_draft_watermark = 1;		   // Support add of a watermark on drafts

		$this->franchise = !$mysoc->tva_assuj;

		$dashdotted =  (!empty($conf->global->ULTIMATE_DASH_DOTTED)) ?
		$conf->global->ULTIMATE_DASH_DOTTED : 0;

		$bordercolor =  (!empty($conf->global->ULTIMATE_BORDERCOLOR_COLOR)) ?
		html2rgb($conf->global->ULTIMATE_BORDERCOLOR_COLOR) : array(0, 63, 127);
			
		$this->border_style = array('width' => 0.2, 'cap' => 'butt', 'join' => 'miter', 'dash' => $dashdotted, 'color' => $bordercolor);

		// Get source company
		$this->emetteur = $mysoc;
		if (empty($this->emetteur->country_code)) $this->emetteur->country_code = substr($langs->defaultlang, -2); // By default, if was not defined

		$this->tabTitleHeight = 8; // default height

		$this->tva = array();
		$this->localtax1 = array();
		$this->localtax2 = array();
		$this->atleastoneratenotnull = 0;
		$this->atleastonediscount = 0;
		$this->atleastoneref = 0;
		$this->atleastoneweight = 0;
		$this->situationinvoice = false;
		$this->atleastonephoto = false;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Function to build pdf onto disk
	 *
	 *  @param		Facture		$object			Id of object to generate
	 *  @param		Translate	$outputlangs	Lang output object
	 *  @param		string	$srctemplatepath	Full path of source filename for generator using a template file
	 *  @param		int		$hidedetails		Do not show line details
	 *  @param		int		$hidedesc			Do not show desc
	 *  @param		int		$hideref			Do not show ref
	 *  @param		object	$hookmanager		Hookmanager object
	 *  @return     int             			1=OK, 0=KO
	 */
	public function write_file($object, $outputlangs, $srctemplatepath = '', $hidedetails = 0, $hidedesc = 0, $hideref = 0)
	{
		// phpcs:enable		
		global $user, $langs, $conf, $mysoc, $db, $hookmanager, $nblines;

		$textcolor =  (!empty($conf->global->ULTIMATE_TEXTCOLOR_COLOR)) ?
		html2rgb($conf->global->ULTIMATE_TEXTCOLOR_COLOR) : array(25, 25, 25);

		$roundradius =  (!empty($conf->global->ULTIMATE_SET_RADIUS)) ?
		$conf->global->ULTIMATE_SET_RADIUS : 1;

		$bordercolor =  (!empty($conf->global->ULTIMATE_BORDERCOLOR_COLOR)) ?
		html2rgb($conf->global->ULTIMATE_BORDERCOLOR_COLOR) : array(0, 63, 127);

		$dashdotted =  (!empty($conf->global->ULTIMATE_DASH_DOTTED)) ?
		$conf->global->ULTIMATE_DASH_DOTTED : 0;

		$this->border_style = array('width' => 0.2, 'cap' => 'butt', 'join' => 'miter', 'dash' => $dashdotted, 'color' => $bordercolor);

		dol_syslog("write_file outputlangs->defaultlang=" . (is_object($outputlangs) ? $outputlangs->defaultlang : 'null'));

		if (!is_object($outputlangs)) {
			$outputlangs = $langs;
		}
		// For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
		if (!empty($conf->global->MAIN_USE_FPDF)) {
			$outputlangs->charset_output = 'ISO-8859-1';
		}

		// Load translation files required by the page
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
		$this->atleastonephoto = false;
		if (!empty($conf->global->ULTIMATE_GENERATE_INVOICES_WITH_PICTURE)) {
			$objphoto = new Product($this->db);

			for ($i = 0; $i < $nblines; $i++) {
				if (empty($object->lines[$i]->fk_product)) {
					continue;
				}

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
						if ($conf->product->entity != $objphoto->entity) {
							$dir = $conf->product->multidir_output[$objphoto->entity] . '/' . $midir; //Check repertories of current entities
						} else {
							$dir = $conf->product->dir_output . '/' . $midir; //Check repertory of the current product
						}

						foreach ($objphoto->liste_photos($dir, 1) as $key => $obj) {
							if (empty($conf->global->CAT_HIGH_QUALITY_IMAGES)) { 
							// If CAT_HIGH_QUALITY_IMAGES not defined, we use thumb if defined and then original photo
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
							$this->atleastonephoto = true;
						}
					}
				}
				if ($realpath && $arephoto) {
					$realpatharray[$i] = $realpath;
				}
			}
		}

		if ($conf->facture->multidir_output[$conf->entity]) {
			$object->fetch_thirdparty();

			$deja_regle = $object->getSommePaiement(($conf->multicurrency->enabled && $object->multicurrency_tx != 1) ? 1 : 0);
			$amount_credit_notes_included = $object->getSumCreditNotesUsed(($conf->multicurrency->enabled && $object->multicurrency_tx != 1) ? 1 : 0);
			$amount_deposits_included = $object->getSumDepositsUsed(($conf->multicurrency->enabled && $object->multicurrency_tx != 1) ? 1 : 0);

			// Definition of $dir and $file
			if ($object->specimen) {
				$dir = $conf->facture->multidir_output[$conf->entity];
				$file = $dir . "/SPECIMEN.pdf";
			} else {
				$objectref = dol_sanitizeFileName($object->ref);
				$dir = $conf->facture->dir_output . "/" . $objectref;
				$file = $dir . "/" . $objectref . ".pdf";
			}
			if (!file_exists($dir)) {
				if (dol_mkdir($dir) < 0) {
					$this->error = $langs->trans("ErrorCanNotCreateDir", $dir);
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
				$default_font_size = pdf_getPDFFontSize($outputlangs); // Must be after pdf_getInstance
				$pdf->SetAutoPageBreak(1, 0);
				
				$heightforinfotot = 40 + (4 * $nbpayments);	// Height reserved to output the info and total part
				$heightforfreetext = (isset($conf->global->MAIN_PDF_FREETEXT_HEIGHT) ? $conf->global->MAIN_PDF_FREETEXT_HEIGHT : 5);	// Height reserved to output the free text on last page

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
				$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref) . " " . $outputlangs->transnoentities("PdfInvoiceTitle") . " " . $outputlangs->convToOutputCharset($object->thirdparty->name));
				if (!empty($conf->global->MAIN_DISABLE_PDF_COMPRESSION)) {
					$pdf->SetCompression(false);
				}

				 // Set certificate
				 $cert = empty($user->conf->CERTIFICATE_CRT) ? '' : $user->conf->CERTIFICATE_CRT;
				 // If use has no certificate, we try to take the company one
				 if (!$cert) {
					 $cert = empty($conf->global->CERTIFICATE_CRT) ? '' : $conf->global->CERTIFICATE_CRT;
				 }
				 // If a certificate is found
				 if ($cert) {
					 $info = array(
						 'Name' => $this->emetteur->name,
						 'Location' => getCountry($this->emetteur->country_code, 0),
						 'Reason' => 'INVOICE',
						 'ContactInfo' => $this->emetteur->email
					 );
					 $pdf->setSignature($cert, $cert, $this->emetteur->name, '', 2, $info);
				 }

				$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right

				// Set $this->atleastoneref if man have at least one ref 
				for ($i = 0; $i < $nblines; $i++) {
					if ($object->lines[$i]->product_ref) {
						$this->atleastoneref++;
					}
				}

				// Does we have at least one line with discount $this->atleastonediscount
				foreach ($object->lines as $line) {
					if ($line->remise_percent) {
						$this->atleastonediscount = true;
						break;
					}
				}

				// Situation invoice handling
				if (($object->situation_cycle_ref > 0) && $object->type == Facture::TYPE_SITUATION) {
					$this->situationinvoice = true;
				}
				if (count($object->tab_previous_situation_invoice) > 0) {
					// List of previous invoices	
					$current_situation_counter = array();
					foreach ($object->tab_previous_situation_invoice as $prev_invoice) {
						$tmptotalpaidforthisinvoice = $prev_invoice->getSommePaiement();
						$total_prev_ht += $prev_invoice->total_ht;
						$total_prev_ttc += $prev_invoice->total_ttc;
						$current_situation_counter[] = (($prev_invoice->type == Facture::TYPE_CREDIT_NOTE) ?-1 : 1) * $prev_invoice->situation_counter;
						$situation =(($prev_invoice->type == Facture::TYPE_CREDIT_NOTE) ? $langs->trans('situationInvoiceShortcode_AS') : $langs->trans('situationInvoiceShortcode_S')).$prev_invoice->situation_counter;
					}
				}
				
				// New page
				$pdf->AddPage();
				if (!empty($tplidx)) {
					$pdf->useTemplate($tplidx);
				}
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
				if (!empty($conf->global->ULTIMATE_PDF_INVOICE_ADDALSOTARGETDETAILS) &&($conf->global->ULTIMATE_PDF_INVOICE_ADDALSOTARGETDETAILS == 1) || (!empty($conf->global->MAIN_INFO_SOCIETE_NOTE) && !empty($this->emetteur->note_private)) || (!empty($conf->global->MAIN_INFO_TVAINTRA) && !empty($this->emetteur->tva_intra))) {
					$hautcadre = 48;
				} else {
					$hautcadre = 40;
				}

				$tab_top = $this->marge_haute + $logo_height + $hautcadre + 14;

				$tab_top_newpage = (empty($conf->global->ULTIMATE_INVOICES_PDF_DONOTREPEAT_HEAD) ? $this->marge_haute + $logo_height + 12 : 18);

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
						$tab_top -= 2;
						$pdf->SetFont('', '', $default_font_size - 2);
						$pdf->writeHTMLCell($tab_width, 3, $this->marge_gauche + 1, $tab_top + 1, dol_htmlentitiesbr($desc_incoterms), 0, 1);

						$nexY = max($pdf->GetY(), $nexY);
	                    $height_incoterms = $nexY - $tab_top;

						// Rect prend une longueur en 3eme param
						$pdf->SetDrawColor(192, 192, 192);
						$pdf->RoundedRect($this->marge_gauche, $tab_top, $tab_width, $height_incoterms + 1, $roundradius, '1111', 'S', $this->border_style, $bgcolor);
						$tab_top = $nexY + 8;
					}
				}

				// Display notes
				$notetoshow = empty($object->note_public) ? '' : $object->note_public;
				if (!empty($conf->global->MAIN_ADD_SALE_REP_SIGNATURE_IN_INVOICE_NOTE)) {
					// Get first sale rep
					if (is_object($object->thirdparty)) {
						$salereparray = $object->thirdparty->getSalesRepresentatives($user);
						$salerepobj = new User($this->db);
						$salerepobj->fetch($salereparray[0]['id']);
						if (!empty($salerepobj->signature)) {
							$notetoshow = dol_concatdesc($notetoshow, $salerepobj->signature);
						}
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
					// To avoid an issue when there is an image into signature
					/*$slug_tbr = substr($notetoshow, strpos($notetoshow, '<img'));
					$slug_length = strlen($slug_tbr);
					$notetoshow = substr($notetoshow, 0, -$slug_length);*/

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
							if (!empty($tplidx)) {
								$pdf->useTemplate($tplidx);
							}
							if (empty($conf->global->ULTIMATE_INVOICES_PDF_DONOTREPEAT_HEAD)) $this->_pagehead($pdf, $object, 0, $outputlangs);
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

						// apply note frame to previous pages
						$i = $pageposbeforenote;
						while ($i < $pageposafternote) {
							$pdf->setPage($i);

							$pdf->SetDrawColor(128, 128, 128);
							// Draw note frame
							if ($i > $pageposbeforenote) {
								$height_note = $this->page_hauteur - ($tab_top_newpage + $heightforfooter);
								$pdf->RoundedRect($this->marge_gauche, $tab_top_newpage - 1, $tab_width, $height_note + 1 , $roundradius, '1111', 'S', array());
							} else {
								$height_note = $this->page_hauteur - ($tab_top + $heightforfooter);
								$pdf->RoundedRect($this->marge_gauche, $tab_top - 1, $tab_width, $height_note + 1, $roundradius, '1111', 'S', $this->border_style, $bgcolor);
							}

							// Add footer
							$pdf->setPageOrientation('', 1, 0);	// The only function to edit the bottom margin of current page to set it.
							$this->_pagefoot($pdf, $object, $outputlangs, 1);

							$i++;
						}

						// apply note frame to last page
						$pdf->setPage($pageposafternote);
						if (!empty($tplidx)) {
							$pdf->useTemplate($tplidx);
						}
						if (empty($conf->global->ULTIMATE_INVOICES_PDF_DONOTREPEAT_HEAD)) {
							$this->_pagehead($pdf, $object, 0, $outputlangs);
						}
						$height_note = $posyafter - $tab_top_newpage;
						$pdf->RoundedRect($this->marge_gauche, $tab_top_newpage - 1, $tab_width, $height_note + 1, $roundradius, '1111', 'S', $this->border_style, $bgcolor);
					} else // No pagebreak
					{
						$pdf->commitTransaction();
						
						$height_note = $posyafter - $tab_top;

						$pdf->RoundedRect($this->marge_gauche, $tab_top, $tab_width, $height_note + 1, $roundradius, '1111', 'S', $this->border_style, $bgcolor);

						if ($posyafter > ($this->page_hauteur - ($heightforfooter + $heightforfreetext + 20))) {
							// not enough space, need to add page
							$pdf->AddPage('', '', true);
							$pagenb++;
							$pageposafternote++;
							$pdf->setPage($pageposafternote);
							if (!empty($tplidx)) {
								$pdf->useTemplate($tplidx);
							}
							if (empty($conf->global->ULTIMATE_INVOICES_PDF_DONOTREPEAT_HEAD)) {
								$this->_pagehead($pdf, $object, 0, $outputlangs);
							}

							$posyafter = $tab_top_newpage;
						}
					}

					$tab_height = $tab_height - $height_note;
					$tab_top = $posyafter + 10;
				} else {
					$height_note = 0;
				}

				// Use new auto column system
				$this->prepareArrayColumnField($object, $outputlangs, $hidedetails, $hidedesc, $hideref);

				// Table simulation to know the height of the title line
				$pdf->startTransaction();
				$this->pdfTabTitles($pdf, $tab_top, $tab_height, $outputlangs, $hidetop);
				$pdf->rollbackTransaction(true);

				if (empty($height_note) && empty($desc_incoterms)) {
					$tab_top += 6;
				}

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
					
					if (!empty($object->lines[$i]->fk_product)) {
						$product = new Product($db);
						$result = $product->fetch($object->lines[$i]->fk_product, '', '', '');
						$product->fetch_barcode();
					}

					// Define size of image if we need it
					$imglinesize = array();
					if (!empty($realpatharray[$i])) {
						$imglinesize = pdf_getSizeForImage($realpatharray[$i]);
					}

					$pdf->setTopMargin($tab_top_newpage);
					//If we aren't on last lines footer space needed is on $heightforfooter
					if ($i != $nblines - 1) {
						$bMargin = $heightforfooter;
					} else {	//We are on last item, need to check all footer (freetext, ...)
						$bMargin = $heightforfooter + $heightforfreetext + $heightforinfotot;
					}
					$pdf->setPageOrientation('', 1, $bMargin);	// The only function to edit the bottom margin of current page to set it.
					$pageposbefore = $pdf->getPage();

					$showpricebeforepagebreak = 1;
					$posYAfterImage = 0;
					$posYStartDescription = 0;
					$posYAfterDescription = 0;;
					$posYafterRef = 0;

					if ($this->getColumnStatus('photo')) {
						// We start with Photo of product line
						if (isset($imglinesize['width']) && isset($imglinesize['height']) && ($curY + $imglinesize['height']) > ($this->page_hauteur - $bMargin)) {	// If photo too high, we moved completely on new page
							$pdf->AddPage('', '', true);
							if (!empty($tplidx)) {
								$pdf->useTemplate($tplidx);
							}
							$pdf->setPage($pageposbefore + 1);

							$curY = $tab_top_newpage;
							// Allows data in the first page if description is long enough to break in multiples pages
							if (!empty($conf->global->MAIN_PDF_DATA_ON_FIRST_PAGE))
								$showpricebeforepagebreak = 1;
							} else {
								$showpricebeforepagebreak = 0;
						}

						$photo = false;
						if (!empty($this->cols['photo']) && isset($imglinesize['width']) && isset($imglinesize['height'])) {
							$curX = $this->getColumnContentXStart('photo') - 1;
							$pdf->Image($realpatharray[$i], $curX, $curY + 1, $imglinesize['width'], $imglinesize['height'], '', '', '', 2, 300);	// Use 300 dpi
							// $pdf->Image does not increase value return by getY, so we save it manually
							$posYAfterImage = $curY + $imglinesize['height'];
							$photo = true;
						}
					}

					if (!empty($photo)) {
						$nexY = $posYAfterImage;
					}

					// Description of product line
					if ($conf->milestone->enabled && $object->lines[$i]->product_type == 9 && $object->lines[$i]->pagebreak == true) {
						$curX = $this->getColumnContentXStart('desc') + 1.5;
						$curY = $tab_top_newpage + 1;
					} else {
						$curX = $this->getColumnContentXStart('desc');
					}		

					if ($this->getColumnStatus('desc')) {
						$pdf->startTransaction();
						if ($conf->global->ULTIMATE_DOCUMENTS_WITH_REF == "yes" && $this->atleastoneref == true) {
							$hideref = 1;
						} else {
							$hideref = (GETPOST('hideref', 'int') ? GETPOST('hideref', 'int') : (!empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_REF) ? 1 : 0));
						}
						$hidedetails = (GETPOST('hidedetails', 'int') ? GETPOST('hidedetails', 'int') : (!empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS) ? 1 : 0));
						$hidedesc = (GETPOST('hidedesc', 'int') ? GETPOST('hidedesc', 'int') : (!empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DESC) ?  1 : 0));

						$this->printColDescContent($pdf, $curY, 'desc', $object, $i, $outputlangs, $hideref, $hidedesc);
						$posYAfterDescription = $pdf->GetY();

						if (!empty($conf->global->ULTIMATE_PRODUCT_ENABLE_CUSTOMCOUNTRYCODE)  && $object->lines[$i]->product_type != 9 && $object->lines[$i]->product_type != 1) {
							// dysplay custom and country code
							$posy = $this->ultimatecustomcode($pdf, $product, $outputlangs);
							$posYAfterDescription = $pdf->GetY();
						}
						$pageposafter = $pdf->getPage();						

						if (!empty($product->barcode) && !empty($product->barcode_type_code) && $object->lines[$i]->product_type != 9 && $conf->global->ULTIMATEPDF_GENERATE_INVOICES_WITH_PRODUCTS_BARCODE == 1) {
							// dysplay product barcode
							$posy = $this->ultimatebarcode($pdf, $product);
							$posYAfterDescription = $pdf->GetY();
						}
						$pageposafter = $pdf->getPage();

						if ($pageposafter > $pageposbefore)	// There is a pagebreak
						{
							$posYAfterImage = $tab_top_newpage + $imglinesize['height'];
							$pdf->rollbackTransaction(true);
							$pageposbeforedesc = $pdf->getPage();
							$pageposafter = $pageposbefore;
							$posYStartDescription = $curY;
							$pdf->setPageOrientation('', 1, $heightforfooter);	// The only function to edit the bottom margin of current page to set it.
							$this->printColDescContent($pdf, $curY, 'desc', $object, $i, $outputlangs, $hideref, $hidedesc);
							$posYAfterDescription = $pdf->GetY();
							if (!empty($conf->global->ULTIMATE_PRODUCT_ENABLE_CUSTOMCOUNTRYCODE)  && $object->lines[$i]->product_type != 9 && $object->lines[$i]->product_type != 1) {
								// dysplay custom and country code
							$posy = $this->ultimatecustomcode($pdf, $product, $outputlangs);
							$posYAfterDescription = $pdf->GetY();
							}
							$pageposafter = $pdf->getPage();

							if (!empty($product->barcode) && !empty($product->barcode_type_code) && $object->lines[$i]->product_type != 9 && $conf->global->ULTIMATEPDF_GENERATE_INVOICES_WITH_PRODUCTS_BARCODE == 1) {
								// dysplay product barcode
								$posy = $this->ultimatebarcode($pdf, $product);
								$posYAfterDescription = $pdf->GetY();
							}
							$pageposafter = $pdf->getPage();

							if ($posYAfterDescription > ($this->page_hauteur - $bMargin))	// There is no space left for total+free text
							{
								if ($i == ($nblines - 1)) {	// No more lines, and no space left to show total, so we create a new page
									$pdf->AddPage('', '', true);
									if (!empty($tplidx)) {
										$pdf->useTemplate($tplidx);
									}
									$pdf->setPage($pageposafter + 1);
								}
							} else {
								// We found a page break
								// Allows data in the first page if description is long enough to break in multiples pages
								if (!empty($conf->global->MAIN_PDF_DATA_ON_FIRST_PAGE)) {
									$showpricebeforepagebreak = 1;
								} else {
									$showpricebeforepagebreak = 0;
								}
							}
						} else	// No pagebreak
						{
							$pdf->commitTransaction();
						}
						$posYAfterDescription = $pdf->GetY() + 2;
					}
					$nexY = max($pdf->GetY(), $posYAfterImage);

					$pageposafter = $pdf->getPage();

					$pdf->setPage($pageposbefore);
					$pdf->setTopMargin($this->marge_haute);
					$pdf->setPageOrientation('', 1, 0);	// The only function to edit the bottom margin of current page to set it.	

					// We suppose that a too long description or photo were moved completely on next page
					if ($pageposafter > $pageposbefore && empty($showpricebeforepagebreak)) {
						$pdf->setPage($pageposafter);
						$curY = $tab_top_newpage;
					}
					/*if ($pageposafterRef > $pageposbefore && $posYafterRef < $posYStartRef) {
						$pdf->setPage($pageposbefore);
						$showpricebeforepagebreak = 1;
					}
					if ($nexY > $curY && $pageposafter > $pageposbefore) {
						$pdf->setPage($pageposafter);
						$curY = $tab_top_newpage + 1;
					}
					if ($pageposbeforedesc < $pageposafterdesc) {
						$pdf->setPage($pageposbeforedesc);
						$curY = $posYStartDescription;
					}*/

					$pdf->SetFont('', '', $default_font_size - 2);   //  We reposition the default font

					if ($curY + 4 > ($this->page_hauteur - $heightforfooter)) {
						$pdf->setPage($pageposafter);
						$curY = $tab_top_newpage;
					}

					//Line numbering
					if (!empty($conf->global->ULTIMATE_INVOICES_WITH_LINE_NUMBER)) {
						// Numbering
						if ($this->getColumnStatus('num') && array_key_exists($i, $object->lines) && $object->lines[$i]->product_type != 9) {
							$this->printStdColumnContent($pdf, $curY, 'num', $line_number);
							$nexY = max($pdf->GetY(),$nexY);
							$line_number++;
						}
					}

					//  Column reference
					if ($conf->global->ULTIMATE_DOCUMENTS_WITH_REF == "yes" && $this->atleastoneref == true) {
						if ($this->getColumnStatus('ref')) {
							$productRef = pdf_getlineref($object, $i, $outputlangs, $hidedetails);
							$this->printStdColumnContent($pdf, $curY, 'ref', $productRef);
							$nexY = max($pdf->GetY(), $nexY);
						}
					}

					// VAT Rate
					if ($conf->global->ULTIMATE_GENERATE_DOCUMENTS_WITHOUT_VAT == "no" && empty($conf->global->ULTIMATE_SHOW_HIDE_VAT_COLUMN)) {
						// VAT Rate
						if ($this->getColumnStatus('vat')) {
							$vat_rate = pdf_getlinevatrate($object, $i, $outputlangs, $hidedetails);
							$this->printStdColumnContent($pdf, $curY, 'vat', $vat_rate);
							$nexY = max($pdf->GetY(),$nexY);
						}
					}

					// Unit price before discount
					if (empty($conf->global->ULTIMATE_SHOW_HIDE_PUHT)) {
						if ($this->getColumnStatus('subprice')) {
							$up_excl_tax = pdf_getlineupexcltax($object, $i, $outputlangs, $hidedetails);
							$this->printStdColumnContent($pdf, $curY, 'subprice', $up_excl_tax);
							$nexY = max($pdf->GetY(),$nexY);
						}
					}

					// Discount on line                	
					if ($this->getColumnStatus('discount') && $object->lines[$i]->remise_percent) {
						$remise_percent = pdf_getlineremisepercent($object, $i, $outputlangs, $hidedetails);
						$this->printStdColumnContent($pdf, $curY, 'discount', $remise_percent);
						$nexY = max($pdf->GetY(),$nexY);
					}

					// Unit price after discount
					if ($this->atleastonediscount && !empty($conf->global->ULTIMATE_GENERATE_INVOICES_WITH_PUAFTER)) {
						$remise_percent = pdf_getlineremisepercent($object, $i, $outputlangs, $hidedetails);
						if ($remise_percent == dol_print_reduction(100, $langs)) {
							$up_after = price(0);
							$this->printStdColumnContent($pdf, $curY, 'upafter', $up_after);
							$nexY = max($pdf->GetY(),$nexY);
						} else {
							if ($this->getColumnStatus('upafter') && $object->lines[$i]->remise_percent > 0) {
								$up_excl_tax = pdf_getlineupexcltax($object, $i, $outputlangs, $hidedetails);
								$up_after = price(price2num($up_excl_tax, 'MU') * price2num(1 - price2num($remise_percent, 'MU') / 100, 'MU'));
								$this->printStdColumnContent($pdf, $curY, 'upafter', $up_after);
								$nexY = max($pdf->GetY(),$nexY);
							}
						}
					}

					// Quantity
					if ($conf->global->ULTIMATE_SHOW_HIDE_QTY == 0) {
						if ($this->getColumnStatus('qty')) {
							$qty = pdf_getlineqty($object, $i, $outputlangs, $hidedetails);
							$this->printStdColumnContent($pdf, $curY, 'qty', $qty);
							$nexY = max($pdf->GetY(), $nexY);
						}
					}

					// Weight
					if ($conf->global->ULTIMATE_GENERATE_INVOICES_WITH_WEIGHT_COLUMN) {
						if ($this->getColumnStatus('weight')) {
							$weight = pdf_getlineweight($object, $i, $outputlangs, $hidedetails);
							$this->printStdColumnContent($pdf, $curY, 'weight', $weight);
							$nexY = max($pdf->GetY(), $nexY);
						}
					}

					// Situation in progress
					$hidedetails = 0;
					if ($conf->global->INVOICE_USE_SITUATION && $object->type == Facture::TYPE_SITUATION) {
						$progress = pdf_getlineprogress($object, $i, $outputlangs, $hidedetails);
						$this->printStdColumnContent($pdf, $curY, 'progress', $progress);
						$nexY = max($pdf->GetY(), $nexY);
					}

					// Unit
					if (empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS)) {
						if ($this->getColumnStatus('unit') && $object->lines[$i]->product_type != 9) {
							//$unit = pdf_getlineunit($object, $i, $outputlangs, $hidedetails);
							$unit = $object->lines[$i]->getLabelOfUnit('short');
							$this->printStdColumnContent($pdf, $curY, 'unit', $unit);
							$nexY = max($pdf->GetY(), $nexY);
						}
					}

					if (!empty($conf->global->ULTIMATE_SHOW_LINE_TTTC)) {
						// Total TTC line
						$total_incl_tax = pdf_getlinetotalwithtax($object, $i, $outputlangs, $hidedetails);
						if ($this->getColumnStatus('totalincltax')) {
							if ($conf->milestone->enabled  && $object->lines[$i]->product_type == 9) {
								$curY += 1;
							}
							$this->printStdColumnContent($pdf, $curY, 'totalincltax', $total_incl_tax);
							$nexY = max($pdf->GetY(), $nexY);
						}
					} else {
						// Total HT line
						if (empty($conf->global->ULTIMATE_SHOW_HIDE_THT)) {
							$total_excl_tax = pdf_getlinetotalexcltax($object, $i, $outputlangs, $hidedetails);
							if ($this->getColumnStatus('totalexcltax')) {
								if ($conf->milestone->enabled  && $object->lines[$i]->product_type == 9) {
									$curY += 1;
								}
								$this->printStdColumnContent($pdf, $curY, 'totalexcltax', $total_excl_tax);
								$nexY = max($pdf->GetY(), $nexY);
							}
						}
					}

					// Extrafields
					if (!empty($object->lines[$i]->array_options)) {
						foreach ($object->lines[$i]->array_options as $extrafieldColKey => $extrafieldValue) {
							if ($this->getColumnStatus($extrafieldColKey)) {
								$extrafieldValue = $this->getExtrafieldContent($object->lines[$i], $extrafieldColKey, $outputlangs);
								$this->printStdColumnContent($pdf, $curY, $extrafieldColKey, $extrafieldValue);
								$nexY = max($pdf->GetY(), $nexY);
							}
						}
					}

					$parameters = array(
						'object' => $object,
						'i' => $i,
						'pdf' =>& $pdf,
						'curY' =>& $curY,
						'nexY' =>& $nexY,
						'outputlangs' => $outputlangs,
						'hidedetails' => $hidedetails
					);
					$reshook = $hookmanager->executeHooks('printPDFline', $parameters, $this);    // Note that $object may have been modified by hook

					$sign = 1;
					if (isset($object->type) && $object->type == 2 && !empty($conf->global->INVOICE_POSITIVE_CREDIT_NOTE)) $sign = -1;
					// Collecte des totaux par valeur de tva dans $this->tva["taux"]=total_tva
					$prev_progress = $object->lines[$i]->get_prev_progress($object->id);
					if ($prev_progress > 0 && !empty($object->lines[$i]->situation_percent)) // Compute progress from previous situation
					{
						if ($conf->multicurrency->enabled && $object->multicurrency_tx != 1) $tvaligne = $sign * $object->lines[$i]->multicurrency_total_tva * ($object->lines[$i]->situation_percent - $prev_progress) / $object->lines[$i]->situation_percent;
						else $tvaligne = $sign * $object->lines[$i]->total_tva * ($object->lines[$i]->situation_percent - $prev_progress) / $object->lines[$i]->situation_percent;
					} else {
						if ($conf->multicurrency->enabled && $object->multicurrency_tx != 1) $tvaligne = $sign * $object->lines[$i]->multicurrency_total_tva;
						else $tvaligne = $sign * $object->lines[$i]->total_tva;
					}

					$localtax1ligne = $object->lines[$i]->total_localtax1;
					$localtax2ligne = $object->lines[$i]->total_localtax2;
					$localtax1_rate = $object->lines[$i]->localtax1_tx;
					$localtax2_rate = $object->lines[$i]->localtax2_tx;
					$localtax1_type = $object->lines[$i]->localtax1_type;
					$localtax2_type = $object->lines[$i]->localtax2_type;

					if ($object->remise_percent) {
						$tvaligne -= ($tvaligne * $object->remise_percent) / 100;
					}
					if ($object->remise_percent) {
						$localtax1ligne -= ($localtax1ligne * $object->remise_percent) / 100;
					}
					if ($object->remise_percent) {
						$localtax2ligne -= ($localtax2ligne * $object->remise_percent) / 100;
					}

					$vatrate = (string) $object->lines[$i]->tva_tx;

					// Retrieve type from database for backward compatibility with old records
					if ((!isset($localtax1_type) || $localtax1_type == '' || !isset($localtax2_type) || $localtax2_type == '') // if tax type not defined
						&& (!empty($localtax1_rate) || !empty($localtax2_rate))
					) {	// and there is local tax
						$localtaxtmp_array = getLocalTaxesFromRate($vatrate, 0, $object->thirdparty, $mysoc);
						$localtax1_type = isset($localtaxtmp_array[0]) ? $localtaxtmp_array[0] : '';
						$localtax2_type = isset($localtaxtmp_array[2]) ? $localtaxtmp_array[2] : '';
					}

					// retrieve global local tax
					if ($localtax1_type && $localtax1ligne != 0) {
						$this->localtax1[$localtax1_type][$localtax1_rate] += $localtax1ligne;
					}
					if ($localtax2_type && $localtax2ligne != 0) {
						$this->localtax2[$localtax2_type][$localtax2_rate] += $localtax2ligne;
					}

					if (($object->lines[$i]->info_bits & 0x01) == 0x01) {
						$vatrate .= '*';
					}
					if (!isset($this->tva[$vatrate])) {
						$this->tva[$vatrate] = 0;
					}
					$this->tva[$vatrate] += $tvaligne;

					if ($posYAfterImage > $posYAfterDescription) $nexY = $posYAfterImage;

					// Add line
					if (!empty($conf->global->ULTIMATE_INVOICE_PDF_DASH_BETWEEN_LINES) && $i < ($nblines - 1) && $object->lines[$i]->product_type != 9 && $object->lines[$i + 1]->product_type != 9 && !($object->lines[$i + 1]->pagebreak == true)) {
						$pdf->setPage($pageposafter);
						$pdf->SetLineStyle(array('dash' => '1, 1', 'color' => array(70, 70, 70)));
						if ($conf->global->ULTIMATEPDF_GENERATE_INVOICES_WITH_PRODUCTS_BARCODE == 1 || !empty($conf->global->ULTIMATE_PRODUCT_ENABLE_CUSTOMCOUNTRYCODE)) {
							$pdf->line($this->marge_gauche, $nexY + 6, $this->page_largeur - $this->marge_droite, $nexY + 6);
						} else {
						$pdf->line($this->marge_gauche, $nexY + 1, $this->page_largeur - $this->marge_droite, $nexY + 1);
						}
						$pdf->SetLineStyle(array('dash' => 0));
					}

					if ($conf->global->ULTIMATEPDF_GENERATE_INVOICES_WITH_PRODUCTS_BARCODE == 1 || !empty($conf->global->ULTIMATE_PRODUCT_ENABLE_CUSTOMCOUNTRYCODE)) {
						$nexY += 6;    // Passe espace entre les lignes
					} else {
						$nexY += 2;
					} 
					
					// Detect if some page were added automatically and output _tableau for past pages
					while ($pagenb < $pageposafter) {
						$pdf->setPage($pagenb);
						if ($pagenb == $pageposbeforeprintlines) {
							$this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforfooter, 0, $outputlangs, $hidetop, 1, $object->multicurrency_code);
						} else {
							$this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforfooter, 0, $outputlangs, 0, 1, $object->multicurrency_code);
						}
						$this->_pagefoot($pdf, $object, $outputlangs, 1);
						$pagenb++;
						$pdf->setPage($pagenb);
						$pdf->setPageOrientation('', 1, 0);	// The only function to edit the bottom margin of current page to set it.
						if (empty($conf->global->ULTIMATE_INVOICES_PDF_DONOTREPEAT_HEAD)) {
							$this->_pagehead($pdf, $object, 0, $outputlangs);
						}
					}

					if (isset($object->lines[$i + 1]->pagebreak) && $object->lines[$i + 1]->pagebreak) {
						if ($pagenb == $pageposafter && $pagenb != 1) {
							$this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforfooter, 0, $outputlangs, $hidetop, 1, $object->multicurrency_code);
						} else {
							$this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforfooter, 0, $outputlangs, 0, 1, $object->multicurrency_code);
						}

						$this->_pagefoot($pdf, $object, $outputlangs, 1);
						// New page
						$pdf->AddPage();
						if (!empty($tplidx)) {
							$pdf->useTemplate($tplidx);
						}
						$pagenb++;
						if (empty($conf->global->ULTIMATE_INVOICES_PDF_DONOTREPEAT_HEAD)) {
							$this->_pagehead($pdf, $object, 0, $outputlangs);
						}
					}
				}

				// Show square
				if ($pagenb == $pageposbeforeprintlines) {
					$this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforinfotot - $heightforfreetext - $heightforfooter, 0, $outputlangs, $hidetop, 0, $object->multicurrency_code);
				} else {
					$this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforinfotot - $heightforfreetext - $heightforfooter, 0, $outputlangs, 0, 0, $object->multicurrency_code);
				}
				$bottomlasttab = $this->page_hauteur - $heightforinfotot - $heightforfreetext - $heightforfooter + 1;

				// Display infos area
				$posy = $this->_tableau_info($pdf, $object, $bottomlasttab, $outputlangs);

				// Display total zone
				$posy = $this->_tableau_tot($pdf, $object, $deja_regle, $bottomlasttab, $outputlangs);

				// Affiche zone versements
				if (($deja_regle || $amount_credit_notes_included || $amount_deposits_included) && empty($conf->global->INVOICE_NO_PAYMENT_DETAILS)) {
					$posy = $this->_tableau_versements($pdf, $object, $posy, $outputlangs);
				}

				// Affiche zone numbertowords
				$posy = $this->_tableau_numbertowords($pdf, $object, $posy, $outputlangs);

				// Affiche zone signature responsable
				$posy = $this->_signature($pdf, $object, $posy, $outputlangs);

				// Pied de page
				if ($object->mode_reglement_code == 'LCR') {
					$this->_pagefoot($pdf, $object, $outputlangs);

					// New page
					$pdf->AddPage();
					$this->_pagehead($pdf, $object, 0, $outputlangs);
					pdf_ultimatepagefoot($pdf, $outputlangs, 'FACTURE_FREE_TEXT', $this->emetteur, ($this->marge_haute) + 80, $this->marge_gauche, $this->page_hauteur, $object, $showdetails, $hidefreetext, $footertextcolor);
					$this->_pagelcr($pdf, $object, 180, $outputlangs);
				} else {
					$this->_pagefoot($pdf, $object, $outputlangs);
					if (method_exists($pdf, 'AliasNbPages')) $pdf->AliasNbPages();
				}

				if ($this->situationinvoice && $object->mode_reglement_code !== 'LCR')
				{
					$pdf->AddPage();
					if (! empty($tplidx)) $pdf->useTemplate($tplidx);
					$pagenb++;
					if (empty($conf->global->ULTIMATE_INVOICES_PDF_DONOTREPEAT_HEAD)) $this->_pagehead($pdf, $object, 0, $outputlangs);
					$this->_pagefoot($pdf, $object, $outputlangs);
					if (method_exists($pdf,'AliasNbPages')) $pdf->AliasNbPages();

					$curY = $tab_top_newpage;
					$curX = $this->marge_gauche;

					$pdf->SetFont('', '', $default_font_size - 1);   // Into loop to work with multipage
					$pdf->SetTextColorArray($textcolor);

					$pdf->setTopMargin($tab_top_newpage);
					$pdf->setPageOrientation('', 1, $heightforfooter);	// The only function to edit the bottom 

					$previousinvoices = count($object->tab_previous_situation_invoice) ? $object->tab_previous_situation_invoice : array();

					$remain_to_pay = 0;

					// Add symbol of currency 
					$cursymbolbefore = $cursymbolafter = '';
					if ($conf->currency) {
						$currencyCode = (!empty($object->multicurrency_code) ? $object->multicurrency_code : $conf->currency);
						$currency_symbol = $langs->getCurrencySymbol($currencyCode);
						$listofcurrenciesbefore = array('$', 'CAD', '£', 'S/.', '¥');
						if (in_array($currency_symbol, $listofcurrenciesbefore)) $cursymbolbefore .= $currency_symbol;
						else {
							$tmpcur = $currency_symbol;
							$cursymbolafter .= ($tmpcur == $currency_symbol ? ' ' . $tmpcur : $tmpcur);
						}
					}				
					
					$posy = $curY;
					$posx = $curX;

					$width = 70;
					$width2 = $this->page_largeur - $posx - $width - $this->marge_droite;
					$useborder=0;
					$index = 0;

					$height = 4;

			        $sign=1;
			        if ($object->type == 2 && ! empty($conf->global->INVOICE_POSITIVE_CREDIT_NOTE)) $sign=-1;

					$pdf->SetTextColorArray($textcolor);
					$pdf->SetFont('', 'B', $default_font_size + 3);

					$pdf->SetXY($posx, $posy);

					$depositsamount=$object->getSumDepositsUsed();
					$deja_regle = $object->getSommePaiement();

					$tot_deja_regle = ($depositsamount + $deja_regle);

					$previousinvoices[] = $object;
					
					//$force_to_zero = false;

					$j = 0;
					while ($j < count($previousinvoices))
					{
						$situationinvoice = $previousinvoices[$j];
						
						$posy += 7;
						$index = 0;

						$pdf->SetTextColorArray($textcolor);
						$pdf->SetFont('','B', $default_font_size + 3);

						$pageposbefore=$pdf->getPage();
						$pdf->startTransaction();

						$pdf->SetXY($posx,$posy);

						$ref = $outputlangs->transnoentities("InvoiceSituation").$outputlangs->convToOutputCharset(" n°".$situationinvoice->situation_counter);

						$pdf->MultiCell($this->page_largeur-($this->marge_droite+$this->marge_gauche), 3, $ref. ' '.$situationinvoice->ref, 0, 'L', 0);

						$pdf->SetFont('','', $default_font_size - 1);

						$sign = 1;
						if ($situationinvoice->type == 2 && !empty($conf->global->INVOICE_POSITIVE_CREDIT_NOTE)) $sign = -1;

						$posy += 8;
						// Total HT
						$pdf->SetFillColor(224, 224, 224);
						$pdf->SetXY($posx, $posy);
						$pdf->MultiCell($width, $height, $outputlangs->transnoentities("TotalHT"), 0, 'L', 1);

						$total_ht = ($conf->multicurrency->enabled && $situationinvoice->multicurrency_tx != 1 ? $situationinvoice->multicurrency_total_ht : $situationinvoice->total_ht);
						$pdf->SetXY($posx + $width, $posy);
						$pdf->MultiCell($width2, $height, $cursymbolbefore . price($sign * ($total_ht + (!empty($situationinvoice->remise) ? $situationinvoice->remise : 0)), 0, $outputlangs) . $cursymbolafter, 0, 'R', 1);

						$tvas = array();
						for ($i = 0; $i < count($situationinvoice->lines); $i++) {
							$prev_progress = $situationinvoice->lines[$i]->get_prev_progress($situationinvoice->id);
							if ($prev_progress > 0 && !empty($situationinvoice->lines[$i]->situation_percent)) // Compute progress from previous situation
							{
								if ($conf->multicurrency->enabled && $situationinvoice->multicurrency_tx != 1) $tvaligne = $sign * $situationinvoice->lines[$i]->multicurrency_total_tva * ($situationinvoice->lines[$i]->situation_percent - $prev_progress) / $situationinvoice->lines[$i]->situation_percent;
								else $tvaligne = $sign * $situationinvoice->lines[$i]->total_tva * ($situationinvoice->lines[$i]->situation_percent - $prev_progress) / $situationinvoice->lines[$i]->situation_percent;
							} else {
								if ($conf->multicurrency->enabled && $situationinvoice->multicurrency_tx != 1) $tvaligne = $sign * $situationinvoice->lines[$i]->multicurrency_total_tva;
								else $tvaligne = $sign * $situationinvoice->lines[$i]->total_tva;
							}

							if ($situationinvoice->remise_percent) $tvaligne -= ($tvaligne * $situationinvoice->remise_percent) / 100;

							$tvaligne = price2num($cursymbolbefore . price($tvaligne) . $cursymbolafter, 'MT');

							$vatrate = (string) $situationinvoice->lines[$i]->tva_tx;

							if (($situationinvoice->lines[$i]->info_bits & 0x01) == 0x01) $vatrate .= '*';
							if (!isset($tvas[$vatrate])) 				$tvas[$vatrate] = 0;
							$tvas[$vatrate] += $tvaligne;
						}

						// Show VAT by rates and total
						$pdf->SetFillColor(248, 248, 248);
						foreach ($tvas as $tvakey => $tvaval) {
							if ($tvakey != 0)    // On affiche pas taux 0
							{
								$index++;
								$pdf->SetXY($posx, $posy + $height * $index);

								$tvacompl = '';
								if (preg_match('/\*/', $tvakey)) {
									$tvakey = str_replace('*', '', $tvakey);
									$tvacompl = " (" . $outputlangs->transnoentities("NonPercuRecuperable") . ")";
								}
								$totalvat = $outputlangs->transcountrynoentities("TotalVAT", $mysoc->country_code) . ' ';
								$totalvat .= vatrate($tvakey, 1) . $tvacompl;
								$pdf->MultiCell($width, $height, $totalvat, 0, 'L', 1);

								$pdf->SetXY($posx + $width, $posy + $height * $index);
								$pdf->MultiCell($width2, $height, $cursymbolbefore . price($tvaval, 0, $outputlangs) . $cursymbolafter, 0, 'R', 1);
							}
						}

						$index++;

						$total_ttc = ($conf->multicurrency->enabled && $situationinvoice->multicurrency_tx != 1) ? $situationinvoice->multicurrency_total_ttc : $situationinvoice->total_ttc;

						// Total TTC
						$pdf->SetXY($posx, $posy + $height * $index);
						$pdf->SetTextColorArray($textcolor);
						$pdf->SetFillColor(224, 224, 224);
						$pdf->MultiCell($width, $height, $outputlangs->transnoentities("TotalTTC"), $useborder, 'L', 1);

						$pdf->SetXY($posx + $width, $posy + $height * $index);
						$pdf->MultiCell($width2, $height, $cursymbolbefore . price($sign * $total_ttc, 0, $outputlangs) . $cursymbolafter, $useborder, 'R', 1);

						$index++;

						$pdf->SetTextColorArray($textcolor);

						$creditnoteamount = ($conf->multicurrency->enabled && $situationinvoice->multicurrency_tx != 1) ? $situationinvoice->getSumCreditNotesUsed(1) : $situationinvoice->getSumCreditNotesUsed();
						$depositsamount = ($conf->multicurrency->enabled && $situationinvoice->multicurrency_tx != 1) ? $situationinvoice->getSumDepositsUsed(1) : $situationinvoice->getSumDepositsUsed();
						$deja_regle = ($conf->multicurrency->enabled && $situationinvoice->multicurrency_tx != 1) ? $situationinvoice->getSommePaiement(1) : $situationinvoice->getSommePaiement();
						
						$resteapayer = price2num($total_ttc - $deja_regle - $creditnoteamount - $depositsamount, 'MT');
						if ($situationinvoice->paye) $resteapayer = 0;

						$y = 0;

						// Already paid + Deposits
						$tot_deja_regle = $deja_regle + $depositsamount;

						$pdf->SetXY($posx, $posy + $height * $index);
						$pdf->MultiCell($width, $height, $outputlangs->transnoentities("Paid"), 0, 'L', 0);
						$pdf->SetXY($posx + $width, $posy + $height * $index);
						$pdf->MultiCell($width2, $height, $cursymbolbefore . price($tot_deja_regle, 0, $outputlangs) . $cursymbolafter, 0, 'R', 0);

						// Credit note
						if ($creditnoteamount) {
							$index++;
							$pdf->SetXY($posx, $posy + $height * $index);
							$pdf->MultiCell($width, $height, $outputlangs->transnoentities("CreditNotes"), 0, 'L', 0);
							$pdf->SetXY($posx + $width, $posy + $height * $index);
							$pdf->MultiCell($width2, $height, $cursymbolbefore . price($creditnoteamount, 0, $outputlangs) . $cursymbolafter, 0, 'R', 0);
						}

						// Escompte
						if ($situationinvoice->close_code == Facture::CLOSECODE_DISCOUNTVAT) {
							$index++;
							$pdf->SetFillColor(255, 255, 255);

							$pdf->SetXY($posx, $posy + $height * $index);
							$pdf->MultiCell($width, $height, $outputlangs->transnoentities("EscompteOfferedShort"), $useborder, 'L', 1);
							$pdf->SetXY($posx + $width, $posy + $height * $index);
							$pdf->MultiCell($width2, $height, $cursymbolbefore . price($situationinvoice->total_ttc - $deja_regle - $creditnoteamount - $depositsamount, 0, $outputlangs) . $cursymbolafter, $useborder, 'R', 1);

							$resteapayer = 0;
						}

						$index++;
						$pdf->SetTextColorArray($textcolor);
						$pdf->SetFillColor(224, 224, 224);
						$pdf->SetXY($posx, $posy + $height * $index);
						$pdf->MultiCell($width, $height, $outputlangs->transnoentities("RemainderToPay"), $useborder, 'L', 1);
						$pdf->SetXY($posx + $width, $posy + $height * $index);
						$pdf->MultiCell($width2, $height, $cursymbolbefore . price($resteapayer, 0, $outputlangs) . $cursymbolafter, $useborder, 'R', 1);

						$pdf->SetFont('', '', $default_font_size - 1);
						$pdf->SetTextColorArray($textcolor);

						$index++;

						if ($deja_regle > 0) {
							$title = $outputlangs->transnoentities("PaymentsAlreadyDone");
							if ($situationinvoice->type == 2) $title = $outputlangs->transnoentities("PaymentsBackAlreadyDone");

							$pdf->SetFont('', '', $default_font_size - 3);
							$pdf->SetXY($posx, $posy + $height * $index);
							$pdf->MultiCell($width, $height, $title, 0, 'L', 0);

							$index++;

							$tab5_width = ($this->page_largeur - $this->marge_droite - $posx) / 5;

							$pdf->SetFont('', '', $default_font_size - 4);
							$pdf->SetXY($posx, $posy + $height * $index);
							$pdf->MultiCell($tab5_width, $height - 1, $outputlangs->transnoentities("Payment"), 0, 'L', 0);
							$pdf->SetXY($posx + $tab5_width, $posy + $height * $index);
							$pdf->MultiCell($tab5_width, $height - 1, $outputlangs->transnoentities("Date"), 0, 'L', 0);
							$pdf->SetXY($posx + $tab5_width * 2, $posy + $height * $index);
							$pdf->MultiCell($tab5_width, $height - 1, $outputlangs->transnoentities("Type"), 0, 'L', 0);
							$pdf->SetXY($posx + $tab5_width * 3, $posy + $height * $index);
							$pdf->MultiCell($tab5_width, $height - 1, $outputlangs->transnoentities("BankAccount"), 0, 'L', 0);
							$pdf->SetXY($posx + $tab5_width * 4, $posy + $height * $index);
							$pdf->MultiCell($tab5_width, $height - 1, $outputlangs->transnoentities("Amount"), 0, 'L', 0);

							$y = $height - 1;

							$pdf->SetFont('', '', $default_font_size - 4);
							
							require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
							$paymentstatic = new Paiement($this->db);
							require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
							$bankaccountstatic = new Account($this->db);
							
							$sql = 'SELECT p.datep as dp, p.ref, p.num_paiement as num_payment, p.rowid, p.fk_bank,';
							$sql .= ' c.code as payment_code, c.libelle as payment_label,';
							$sql .= ' pf.amount,';
							$sql .= ' ba.rowid as baid, ba.ref as baref, ba.label, ba.number as banumber, ba.account_number, ba.fk_accountancy_journal, pf.multicurrency_amount';
							$sql .= ' FROM ' . MAIN_DB_PREFIX . 'paiement_facture as pf, ' . MAIN_DB_PREFIX . 'paiement as p';
							$sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'c_paiement as c ON p.fk_paiement = c.id';
							$sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'bank as b ON p.fk_bank = b.rowid';
							$sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'bank_account as ba ON b.fk_account = ba.rowid';
							$sql .= ' WHERE pf.fk_facture = ' . $situationinvoice->id . ' AND pf.fk_paiement = p.rowid';
							$sql .= ' AND p.entity IN (' . getEntity('invoice') . ')';
							$sql .= ' ORDER BY p.datep, p.tms';
							//print_r($sql);exit;
							$result = $this->db->query($sql);
							if ($result) {
								$num = $this->db->num_rows($result);
								$i = 0;
								if ($num > 0) {
									while ($i < $num) {
										$objp = $this->db->fetch_object($result);
									
										$paymentstatic->id = $objp->rowid;
										$paymentstatic->datepaye = $db->jdate($objp->dp);
										$paymentstatic->ref = $objp->ref;
										$paymentstatic->num_payment = $objp->num_payment;
										$paymentstatic->payment_code = $objp->payment_code;

									$pdf->SetXY($posx, $posy + $height * $index + $y);
									$pdf->MultiCell($tab5_width, $height - 1, $paymentstatic->ref, 0, 'L', 0);

									$pdf->SetXY($posx + $tab5_width, $posy + $height * $index + $y);
									$pdf->MultiCell($tab5_width, $height - 1, dol_print_date($db->jdate($objp->dp), 'day'), 0, 'L', 0);
									
									$label = ($langs->trans("PaymentType".$objp->payment_code) != ("PaymentType".$objp->payment_code)) ? $langs->trans("PaymentType".$objp->payment_code) : $objp->payment_label;
									$pdf->SetXY($posx + $tab5_width * 2, $posy + $height * $index + $y);
									$pdf->MultiCell($tab5_width, $height - 1, $label, 0, 'L', 0);

										if (!empty($conf->banque->enabled)) {
											$bankaccountstatic->id = $objp->baid;
											$bankaccountstatic->ref = $objp->baref;
											$bankaccountstatic->label = $objp->baref;
										}
									$pdf->SetXY($posx + $tab5_width * 3, $posy + $height * $index + $y);
									$pdf->MultiCell($tab5_width, $height - 1, $bankaccountstatic->label, 0, 'L', 0);

									$pdf->SetXY($posx + $tab5_width * 4, $posy + $height * $index + $y);
									$pdf->MultiCell($tab5_width, $height - 1, $cursymbolbefore . price($sign * (($conf->multicurrency->enabled && $object->multicurrency_tx != 1) ? $objp->multicurrency_amount : $objp->amount), 0, $outputlangs) . $cursymbolafter, 0, 'L', 0);
									//var_dump($objp->multicurrency_amount);exit;
									$i++;

									$y += ($height - 1);
									}
								}
							}
						}

						// Output Rect
						$pdf->SetDrawColor(128, 128, 128);
						$pdf->RoundedRect($this->marge_gauche, $posy, $tab_width, $height * $index + $y, $roundradius, '1111', '', $this->border_style, array());
						$posy += $height * $index + $y;

						$pageposafter = $pdf->getPage();
						if ($pageposafter > $pageposbefore)	// There is a pagebreak
						{
							$pdf->rollbackTransaction(true);

							$pageposafter = $pageposbefore;
							$pdf->AddPage('', '', true);
							if (!empty($tplidx)) $pdf->useTemplate($tplidx);
							if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) $this->_pagehead($pdf, $object, 0, $outputlangs);
							$pdf->setPage($pageposafter + 1);
							$pdf->setPageOrientation('', 1, 0);	// The only function to edit the bottom margin of current page to set it.

							$posy = $tab_top_newpage + 1;
						} else {
							$j++;
							$remain_to_pay -= ($sign * ($total_ht + (!empty($situationinvoice->remise) ? $situationinvoice->remise : 0)));

							$pdf->commitTransaction();
						}
					}

					/*$total_prev_ht = $total_prev_ttc = 0;
					$total_global_ht = $total_global_ttc = 0;

					$posy = $pdf->GetY() + 5;
					$pdf->SetTextColorArray($textcolor);
					$pdf->SetFont('', '', $default_font_size - 1);
					if (count($object->tab_previous_situation_invoice) > 0) {
						// List of previous invoices
						$current_situation_counter = array();
						foreach ($object->tab_previous_situation_invoice as $prev_invoice) {
							$tmptotalpaidforthisinvoice = $prev_invoice->getSommePaiement();
							$total_prev_ht += $prev_invoice->total_ht;
							$total_prev_ttc += $prev_invoice->total_ttc;
							$current_situation_counter[] = (($prev_invoice->type == Facture::TYPE_CREDIT_NOTE) ?-1 : 1) * $prev_invoice->situation_counter;
						}
					}

					$total_global_ht += $total_prev_ht;
					$total_global_ttc += $total_prev_ttc;
					$total_global_ht += $object->total_ht;
					$total_global_ttc += $object->total_ttc;
					$current_situation_counter[] = (($object->type == Facture::TYPE_CREDIT_NOTE) ? -1 : 1) * $object->situation_counter;

					if (count($object->tab_next_situation_invoice) > 0) {
						$total_next_ht = $total_next_ttc = 0;

						foreach ($object->tab_next_situation_invoice as $next_invoice) {
							$totalpaye = $next_invoice->getSommePaiement();
							$total_next_ht += $next_invoice->total_ht;
							$total_next_ttc += $next_invoice->total_ttc;
						}
					}
					
					$pdf->SetXY($this->marge_gauche, $posy);
					$pdf->SetFillColor(224, 224, 224);
					$pdf->SetTextColorArray($textcolor);
					$label = $outputlangs->transnoentities("RemainderToBill");
					//$pdf->MultiCell($tab_width, 3, $label, 0, 'L', 1);
					$pdf->SetXY($this->marge_gauche, $posy);
					//$pdf->MultiCell($tab_width, 3, $cursymbolbefore . price($total_next_ht) . $cursymbolafter, 0, 'R', 0);

					$pdf->SetFont('', '', $default_font_size - 1);

					// Output Rect
					$pdf->SetDrawColor(128, 128, 128);
					//$pdf->RoundedRect($this->marge_gauche, $posy, $tab_width, $height, $roundradius, '1111', '', $this->border_style, array());*/

				}

				// Add PDF to be merged
				if (!empty($conf->global->ULTIMATEPDF_GENERATE_INVOICES_WITH_MERGED_PDF)) {
					dol_include_once('/ultimatepdf/class/documentmergedpdf.class.php');

					$already_merged = array();

					if (!empty($object->id) && !(in_array($object->id, $already_merged))) {
						// Find the desire PDF
						$filetomerge = new DocumentMergedPdf($this->db);
						$filetomerge->fetch_by_element($object);
						$already_merged[] = $object->id;

						// If PDF is selected and file is not empty
						if (count($filetomerge->lines) > 0) {
							foreach ($filetomerge->lines as $linefile) {
								if (!empty($linefile->id) && !empty($linefile->file_name)) {
									if (!empty($conf->facture->enabled))
										$filetomerge_dir = $conf->facture->dir_output . '/' . dol_sanitizeFileName($object->ref);

									$infile = $filetomerge_dir . '/' . $linefile->file_name;
									dol_syslog(get_class($this) . ':: $upload_dir=' . $filetomerge_dir, LOG_DEBUG);
									// If file really exists
									if (is_file($infile)) {
										$count = $pdf->setSourceFile($infile);
										// import all page
										for ($i = 1; $i <= $count; $i++) {
											// New page
											$pdf->AddPage();
											$tplIdx = $pdf->importPage($i);
											$pdf->useTemplate($tplIdx, 0, 0, $this->page_largeur);
											if (method_exists($pdf, 'AliasNbPages'))
												$pdf->AliasNbPages();
										}
									}
								}
							}
						}
					}
				}

				$pdf->Close();

				$pdf->Output($file, 'F');

				// Add pdfgeneration hook
				$hookmanager->initHooks(array('pdfgeneration'));
				$parameters = array('file' => $file, 'object' => $object, 'outputlangs' => $outputlangs);
				global $action;
				$reshook = $hookmanager->executeHooks('afterPDFCreation', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
				if ($reshook < 0) {
					$this->error = $hookmanager->error;
					$this->errors = $hookmanager->errors;
				}

				if (!empty($conf->global->MAIN_UMASK))
					@chmod($file, octdec($conf->global->MAIN_UMASK));

				$this->result = array('fullpath' => $file);

				return 1;   // No error
			} else {
				$this->error = $langs->transnoentities("ErrorCanNotCreateDir", $dir);
				return 0;
			}
		} else {
			$this->error = $langs->transnoentities("ErrorConstantNotDefined", "FAC_OUTPUTDIR");
			return 0;
		}
		$this->error = $langs->transnoentities("ErrorUnknown");

		unset($_SESSION['ultimatepdf_model']);

		return 0;   // Erreur par defaut
	}
	
	/**
	 *  Show numbertowords table
	 *
     *  @param	TCPDF		$pdf            Object PDF
     *  @param  Facture		$object         Object invoice
     *  @param  int			$posy           Position y in PDF
     *  @param  Translate	$outputlangs    Object langs for output
     *  @return int             			<0 if KO, >0 if OK
	 */
	function _tableau_numbertowords(&$pdf, $object, $posy, $outputlangs)
	{
		global $conf, $langs;

		$outputlangs->loadLangs(array("bills", "ultimatepdf@ultimatepdf"));

		$widthrecbox = ($this->page_largeur - $this->marge_gauche - $this->marge_droite) / 2;
		$posx = $this->marge_gauche + $widthrecbox;
		$posy = $pdf->GetY();
		$default_font_size = pdf_getPDFFontSize($outputlangs);

		if (!empty($conf->global->ULTIMATE_NUMBER_TO_WORDS) && !empty($conf->global->MAIN_MULTILANGS)) {
			$numberToWords = new NumberToWords();
			// build a new currency transformer using the RFC 3066 language identifier
			// Define lang of customer
			$outputlangs = $langs;
			$newlang = '';
			if ($object->thirdparty->default_lang !== '') {
				$newlang = $object->thirdparty->default_lang;	// Output language we want
			} else {
				setEventMessages($langs->transnoentities("YouShouldSetUpCustomerDefaultLanguage"), null, 'errors');
			}
			if ($conf->global->MAIN_MULTILANGS && empty($newlang) && !empty($_REQUEST['lang_id'])) $newlang = $_REQUEST['lang_id'];
			if (!empty($newlang)) {
				$outputlangs = new Translate("", $conf);
				$outputlangs->setDefaultLang($newlang);
			}

			$newlangarray = explode('_', $newlang, 2);
			$newlang = strtolower($newlangarray[0]);

			if ($newlang !== '') {
				$currencyTransformer = $numberToWords->getCurrencyTransformer($newlang);
			} else {
				setEventMessages($langs->transnoentities("YouShouldSetUpCustomerDefaultLanguage"), null, 'errors');
			}
			$currencyCode = (!empty($object->multicurrency_code) ? $object->multicurrency_code : $conf->currency);
			if ($currencyTransformer !== null) {
				if (!empty($conf->multicurrency->enabled) && $object->multicurrency_tx != 1) {
					$amount = $langs->transnoentities("StopTheBillAtSumOf") . "\n" . $currencyTransformer->toWords($object->multicurrency_total_ttc * 100, $currencyCode);
				} else {
					$amount = $langs->transnoentities("StopTheBillAtSumOf") . "\n" . $currencyTransformer->toWords($object->total_ttc * 100, $currencyCode);
				}
				
			} else {
				setEventMessages($langs->transnoentities("YouShouldSetUpCustomerDefaultLanguage"), null, 'errors');
			}

			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->SetXY($posx, $posy);
			$pdf->writeHTMLCell($widthrecbox, 5, $posx, $posy, '&laquo;' . $amount . '&raquo;', 0, 2, 0, true, 'L', true);
		}
	}
	
	/**
	 * Function _tableau_versements_header
	 *
	 * @param TCPDF 		$pdf				Object PDF
	 * @param Facture		$object				Object invoice
	 * @param Translate		$outputlangs		Object langs for output
	 * @param int			$default_font_size	Font size
	 * @param int			$tab3_posx			pos x
	 * @param int 			$tab3_top			pos y
	 * @param int 			$tab3_width			width
	 * @param int 			$tab3_height		height
	 * @return void
	 */
	protected function _tableau_versements_header($pdf, $object, $outputlangs, $default_font_size, $tab3_posx, $tab3_top, $tab3_width, $tab3_height)
	{
		$title = $outputlangs->transnoentities("PaymentsAlreadyDone");
		if ($object->type == 2) $title = $outputlangs->transnoentities("PaymentsBackAlreadyDone");

		$pdf->SetFont('', '', $default_font_size - 3);
		$pdf->SetXY($tab3_posx, $tab3_top - 4);
		$pdf->MultiCell(60, 3, $title, 0, 'L', 0);

		$pdf->line($tab3_posx, $tab3_top, $tab3_posx + $tab3_width, $tab3_top);

		$pdf->SetFont('', '', $default_font_size - 4);
		$pdf->SetXY($tab3_posx, $tab3_top);
		$pdf->MultiCell(20, 3, $outputlangs->transnoentities("Payment"), 0, 'L', 0);
		$pdf->SetXY($tab3_posx + 21, $tab3_top);
		$pdf->MultiCell(20, 3, $outputlangs->transnoentities("Amount"), 0, 'L', 0);
		$pdf->SetXY($tab3_posx + 40, $tab3_top);
		$pdf->MultiCell(20, 3, $outputlangs->transnoentities("Type"), 0, 'L', 0);
		$pdf->SetXY($tab3_posx + 58, $tab3_top);
		$pdf->MultiCell(20, 3, $outputlangs->transnoentities("Num"), 0, 'L', 0);
		$pdf->SetXY($tab3_posx + 80, $tab3_top);
		$pdf->MultiCell(20, 3, $outputlangs->transnoentities("Bank"), 0, 'L', 0);

		$pdf->line($tab3_posx, $tab3_top - 1 + $tab3_height, $tab3_posx + $tab3_width, $tab3_top - 1 + $tab3_height);

		$posy = $pdf->getY();
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Show payments table
	 *
     *  @param	TCPDF		$pdf            Object PDF
     *  @param  Facture		$object         Object invoice
     *  @param  int			$posy           Position y in PDF
     *  @param  Translate	$outputlangs    Object langs for output
     *  @param  int			$heightforfooter height for footer
     *  @return int             			<0 if KO, >0 if OK
	 */
	protected function _tableau_versements(&$pdf, $object, $posy, $outputlangs, $heightforfooter = 0)
	{
		global $conf, $langs;

		$outputlangs->loadLangs(array("bills", "ultimatepdf@ultimatepdf"));

		$sign = 1;
		if ($object->type == 2 && !empty($conf->global->INVOICE_POSITIVE_CREDIT_NOTE)) $sign = -1;

		$current_page = $pdf->getPage();
		$tab3_posx = $this->page_largeur / 2 + 2;
		$tab3_top = $posy + 5;
		$tab3_width = ($this->page_largeur - $this->marge_gauche - $this->marge_droite - 4) / 2;
		$tab3_height = 4;
		if ($this->page_largeur < 210) // To work with US executive format
		{
			$tab3_posx -= 20;
		}

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		$this->_tableau_versements_header($pdf, $object, $outputlangs, $default_font_size, $tab3_posx, $tab3_top, $tab3_width, $tab3_height);

		$y = 0;

		$pdf->SetFont('', '', $default_font_size - 4);

		// Loop on each discount available (deposits and credit notes and excess of payment included)
		$sql = "SELECT re.rowid, re.amount_ht, re.multicurrency_amount_ht, re.amount_tva, re.multicurrency_amount_tva,  re.amount_ttc, re.multicurrency_amount_ttc,";
		$sql .= " re.description, re.fk_facture_source,";
		$sql .= " f.type, f.datef";
		$sql .= " FROM " . MAIN_DB_PREFIX . "societe_remise_except AS re, " . MAIN_DB_PREFIX . "facture AS f";
		$sql .= " WHERE re.fk_facture_source = f.rowid AND re.fk_facture = " . $object->id;
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			$invoice = new Facture($this->db);
			while ($i < $num) {
				$y += 3;
				if ($tab3_top + $y >= ($this->page_hauteur - $heightforfooter)) {
					$y = 0;
					$current_page++;
					$pdf->AddPage('', '', true);
					if (!empty($tplidx)) $pdf->useTemplate($tplidx);
					if (empty($conf->global->ULTIMATE_INVOICES_PDF_DONOTREPEAT_HEAD)) $this->_pagehead($pdf, $object, 0, $outputlangs);
					$pdf->setPage($current_page);
					$this->_tableau_versements_header($pdf, $object, $outputlangs, $default_font_size, $tab3_posx, $tab3_top + $y - 3, $tab3_width, $tab3_height);
				}

				$obj = $this->db->fetch_object($resql);

				if ($obj->type == 2) $text = $outputlangs->transnoentities("CreditNote");
				elseif ($obj->type == 3) $text = $outputlangs->transnoentities("Deposit");
				elseif ($obj->type == 0) $text = $outputlangs->transnoentities("ExcessReceived");
				else $text = $outputlangs->transnoentities("UnknownType");

				$invoice->fetch($obj->fk_facture_source);

				$pdf->SetXY($tab3_posx, $tab3_top + $y);
				$pdf->MultiCell(20, 3, dol_print_date($this->db->jdate($obj->datef), 'day', false, $outputlangs, true), 0, 'L', 0);
				$pdf->SetXY($tab3_posx + 21, $tab3_top + $y);
				$this->_setFontForMulticurrencyCode($pdf, $object, $outputlangs);
				$pdf->SetFont($conf->global->MAIN_PDF_FORCE_FONT, '', $default_font_size - 4);
				$pdf->MultiCell(20, 3, price(($conf->multicurrency->enabled && $object->multicurrency_tx != 1) ? $obj->multicurrency_amount_ttc : $obj->amount_ttc, 0, $outputlangs, 0, -1, -1, $object->multicurrency_code), 0, 'L', 0);
				
				$pdf->SetXY($tab3_posx + 40, $tab3_top + $y);
				$pdf->MultiCell(20, 3, $text, 0, 'L', 0);
				$pdf->SetXY($tab3_posx + 58, $tab3_top + $y);
				$pdf->MultiCell(20, 3, $invoice->ref, 0, 'L', 0);

				$pdf->line($tab3_posx, $tab3_top + $y + 3, $tab3_posx + $tab3_width, $tab3_top + $y + 3);

				$i++;
			}
		} else {
			$this->error = $this->db->lasterror();
			dol_syslog($this->db, $this->error, LOG_ERR);
			return -1;
		}

		// Loop on each payment
		$sql = "SELECT p.datep as date, p.fk_paiement, p.num_paiement as num, pf.amount as amount, pf.multicurrency_amount,";
		$sql .= " cp.code,";
		$sql .= " ba.ref as refbank";
		$sql .= " FROM " . MAIN_DB_PREFIX . "paiement_facture AS pf";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "paiement as p ON  pf.fk_paiement = p.rowid";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "c_paiement AS cp ON p.fk_paiement = cp.id AND cp.entity IN (" . getEntity('c_paiement') . ")";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "bank as b ON p.fk_bank = b.rowid";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "bank_account as ba ON b.fk_account = ba.rowid";
		$sql .= " WHERE pf.fk_facture = " . $object->id;
		$sql .= " ORDER BY p.datep";
		dol_syslog(get_class($this) . ':: _tableau_versements sql=' . $sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$y += 3;
				if ($tab3_top + $y >= ($this->page_hauteur - $heightforfooter)) {
					$y = 0;
					$current_page++;
					$pdf->AddPage('', '', true);
					if (!empty($tplidx)) $pdf->useTemplate($tplidx);
					if (empty($conf->global->ULTIMATE_INVOICES_PDF_DONOTREPEAT_HEAD)) $this->_pagehead($pdf, $object, 0, $outputlangs);
					$pdf->setPage($current_page);
					$this->_tableau_versements_header($pdf, $object, $outputlangs, $default_font_size, $tab3_posx, $tab3_top + $y - 3, $tab3_width, $tab3_height);
				}

				$row = $this->db->fetch_object($resql);

				$pdf->SetXY($tab3_posx, $tab3_top + $y);
				$pdf->MultiCell(20, 3, dol_print_date($this->db->jdate($row->date), 'day', false, $outputlangs, true), 0, 'L', 0);
				$pdf->SetXY($tab3_posx + 21, $tab3_top + $y);
				$this->_setFontForMulticurrencyCode($pdf, $object, $outputlangs);
				$pdf->SetFont($conf->global->MAIN_PDF_FORCE_FONT, '', $default_font_size - 4);
				$pdf->MultiCell(20, 3, price($sign * (($conf->multicurrency->enabled && $object->multicurrency_tx != 1) ? $row->multicurrency_amount : $row->amount), 0, $outputlangs, 1, -1, -1, $object->multicurrency_code), 0, 'L', 0);
				$pdf->SetFont($conf->global->MAIN_PDF_FORCE_FONT, '', $default_font_size - 4);
				$pdf->SetXY($tab3_posx + 40, $tab3_top + $y);
				$oper = $outputlangs->transnoentitiesnoconv("PaymentTypeShort" . $row->code);

				$pdf->MultiCell(20, 3, $oper, 0, 'L', 0);
				$pdf->SetXY($tab3_posx + 58, $tab3_top + $y);
				$pdf->MultiCell(30, 3, $row->num, 0, 'L', 0);
				$pdf->SetXY($tab3_posx + 80, $tab3_top + $y);
				$pdf->MultiCell(20, 3, $row->refbank, 0, 'L', 0);

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
	 *   Show miscellaneous information (payment mode, payment term, ...)
	 *
	 *   @param		TCPDF		$pdf     		Object PDF
	 *   @param		Facture		$object			Object to show
	 *   @param		int			$posy			Y
	 *   @param		Translate	$outputlangs	Langs object
	 *   @return	int							Pos y
	 */
	function _tableau_info(&$pdf, $object, $posy, $outputlangs)
	{
		// phpcs:enable
		global $conf, $langs, $mysoc;

		$default_font_size = pdf_getPDFFontSize($outputlangs);
		
		$textcolor =  (!empty($conf->global->ULTIMATE_TEXTCOLOR_COLOR)) ?
		html2rgb($conf->global->ULTIMATE_TEXTCOLOR_COLOR) : array(25, 25, 25);

		$pdf->SetFont('', '', $default_font_size - 1);

		// If France, show VAT mention if not applicable
		if ($this->emetteur->country_code == 'FR' && empty($mysoc->tva_assuj)) {
			$pdf->SetFont('', 'B', $default_font_size - 2);
			$pdf->SetXY($this->marge_gauche, $posy);
			$pdf->MultiCell(100, 3, $outputlangs->transnoentities("VATIsNotUsedForInvoice"), 0, 'L', 0);

			$posy = $pdf->GetY();
		}

		$widthrecbox = ($this->page_largeur - $this->marge_gauche - $this->marge_droite - 4) / 2;

		$nblines = count($object->lines);
		for ($i = 0; $i < $nblines; $i++) {
			$hasProduct = false;
			$hasProduct |= ($object->lines[$i]->product_type == 0) && ($object->lines[$i]->tva_tx == 0);
			$resultP[] = $hasProduct;
			
			$hasService = false;
			$hasService |= ($object->lines[$i]->product_type == 1) && ($object->lines[$i]->tva_tx == 0);
			$resultS[] = $hasService; 
		}

		//Mention pour envoie hors France mais dans CEE et N° CEE disponible
		if ($object->thirdparty->tva_assuj == 0 && $object->thirdparty->tva_intra != '') {
			$pdf->SetFont('', 'B', $default_font_size - 2);
			$pdf->SetXY($this->marge_gauche, $posy);
			if (in_array(true, $resultS) && in_array(true, $resultP)) {
				$titre = "Exoneration TVA, art. 259 A ou 259 B, et art. 262 ter, I du CGI";
				$pdf->writeHTMLCell($widthrecbox, 3, $this->marge_gauche, $posy + 2, $titre, 0, 1, false, true, 'L', true);
			} elseif (in_array(true, $resultS)) {
				$titre = "Exoneration TVA, art. 259 B du CGI";
				$pdf->writeHTMLCell($widthrecbox, 3, $this->marge_gauche, $posy + 2, $titre, 0, 1, false, true, 'L', true);
			} elseif (in_array(true, $resultP)) {
				$titre = "Exoneration TVA, art. 262 ter, I du CGI";
				$pdf->writeHTMLCell($widthrecbox, 3, $this->marge_gauche, $posy + 2, $titre, 0, 1, false, true, 'L', true);
			}
			$posy = $pdf->GetY();
		}

		//Mention pour envoie hors CEE ou N° CEE NON disponible
		if ($object->thirdparty->tva_assuj == 0 && $object->thirdparty->tva_intra == '') {
			$pdf->SetFont('', 'B', $default_font_size - 2);
			$pdf->SetXY($this->marge_gauche, $posy);
			if (in_array(true, $resultS) && in_array(true, $resultP)) {
				$titre = "Exoneration TVA, art. 259 A ou 259 B, et  art. 262, I-1e du CGI";
				$pdf->writeHTMLCell($widthrecbox, 3, $this->marge_gauche, $posy + 2, $titre, 0, 1, false, true, 'L', true);
			} elseif (in_array(true, $resultS)) {
				$titre = "Exoneration TVA, art. 259 A ou 259 B du CGI";
				$pdf->writeHTMLCell($widthrecbox, 3, $this->marge_gauche, $posy + 2, $titre, 0, 1, false, true, 'L', true);
			} elseif (in_array(true, $resultP)) {
				$titre = "Exoneration TVA, art. 262, I-1e du CGI";
				$pdf->writeHTMLCell($widthrecbox, 3, $this->marge_gauche, $posy + 2, $titre, 0, 1, false, true, 'L', true);
			}
			$posy = $pdf->GetY();
		}

		// Show payments conditions
		if ($object->type != 2 && ($object->cond_reglement_code || $object->cond_reglement)) {
			$pdf->SetFont('', '', $default_font_size - 2);
			$pdf->SetXY($this->marge_gauche, $posy);
			$titre = '<strong>' . $outputlangs->transnoentities("PaymentConditions") . '</strong>' . ' : ';
			$lib_condition_paiement = $outputlangs->transnoentities("PaymentCondition" . $object->cond_reglement_code) != ('PaymentCondition' . $object->cond_reglement_code) ? $outputlangs->transnoentities("PaymentCondition" . $object->cond_reglement_code) : $outputlangs->convToOutputCharset($object->cond_reglement_doc);
			$lib_condition_paiement = str_replace('\n', "\n", $lib_condition_paiement);
			if ($object->cond_reglement_code == 'RESERV_20' && !$object->type == Facture::TYPE_SITUATION) {
				$lib_condition_paiement .= '<br>' . 'soit : ' . '<strong>' . price(round($object->total_ttc * 0.2), 0, $outputlangs, 0, -1, -1, $object->multicurrency_code) . '</strong>' . ' ' . $outputlangs->transnoentities("Premier acompte");
			} elseif (($object->cond_reglement_code == 'RESERV_40' || $object->cond_reglement_code == 'ACOMPTE_40') && !$object->type == Facture::TYPE_SITUATION) {
				$lib_condition_paiement .= '<br>' . 'soit : ' . '<strong>' . price(round($object->total_ttc * 0.4), 0, $outputlangs, 0, -1, -1, $object->multicurrency_code) . '</strong>' . ' ' . $outputlangs->transnoentities("Premier acompte");
			}
			$pdf->writeHTMLCell($widthrecbox, 4, $this->marge_gauche, $posy + 2, $titre . ' ' . $lib_condition_paiement, 0, 1, false, true, 'L', true);

			$posy = $pdf->GetY() + 3;
		}

		if ($object->type != 2) {
			// Check a payment mode is defined
			if (empty($object->mode_reglement_code)
			&& empty($conf->global->FACTURE_CHQ_NUMBER)
			&& empty($conf->global->FACTURE_RIB_NUMBER)) {
				$this->error = $outputlangs->transnoentities("ErrorNoPaiementModeConfigured");
			} elseif (($object->mode_reglement_code == 'CHQ' && empty($conf->global->FACTURE_CHQ_NUMBER) && empty($object->fk_account) && empty($object->fk_bank))
				|| ($object->mode_reglement_code == 'VIR' && empty($conf->global->FACTURE_RIB_NUMBER) && empty($object->fk_account) && empty($object->fk_bank))) {
				// Avoid having any valid PDF with setup that is not complete
				$outputlangs->load("errors");

				$pdf->SetXY($this->marge_gauche, $posy + 2);
				$pdf->SetTextColor(200, 0, 0);
				$pdf->SetFont('', 'B', $default_font_size - 2);
				$this->error = $outputlangs->transnoentities("ErrorPaymentModeDefinedToWithoutSetup", $object->mode_reglement_code);
				$pdf->MultiCell($widthrecbox, 3, $this->error, 0, 'L', 0);
				$pdf->SetTextColorArray($textcolor);

				$posy = $pdf->GetY();
			}

			// Show payment mode
			if ($object->mode_reglement_code
			&& $object->mode_reglement_code != 'CHQ'
			&& $object->mode_reglement_code != 'VIR') {
				$pdf->SetFont('', '', $default_font_size - 2);
				$pdf->SetXY($this->marge_gauche, $posy + 2);
				$titre = '<strong>' . $outputlangs->transnoentities("PaymentMode") . '</strong>' . ' : ';
				$lib_mode_reg = $outputlangs->transnoentities("PaymentType" . $object->mode_reglement_code) != ('PaymentType' . $object->mode_reglement_code) ? $outputlangs->transnoentities("PaymentType" . $object->mode_reglement_code) : $outputlangs->convToOutputCharset($object->mode_reglement);
				$pdf->writeHTMLCell($widthrecbox, 4, $this->marge_gauche, $posy, $titre . ' ' . $lib_mode_reg, 0, 1, false, true, 'L', true);

				// Show online payment link
				$useonlinepayment = ((!empty($conf->paypal->enabled) || !empty($conf->stripe->enabled) || !empty($conf->paybox->enabled)) && !empty($conf->global->PDF_SHOW_LINK_TO_ONLINE_PAYMENT));
				if (($object->mode_reglement_code == 'CB' || $object->mode_reglement_code == 'VAD') && $object->statut != Facture::STATUS_DRAFT && $useonlinepayment) {
					require_once DOL_DOCUMENT_ROOT . '/core/lib/payments.lib.php';
					global $langs;

					$langs->loadLangs(array('payment', 'paybox'));
					$servicename = $langs->transnoentities('Online');
					$paiement_url = getOnlinePaymentUrl('', 'invoice', $object->ref, '', '', '');
					$linktopay = $langs->trans("ToOfferALinkForOnlinePayment", $servicename) . ' <a href="' . $paiement_url . '">' . $outputlangs->transnoentities("ClickHere") . '</a>';

					$pdf->writeHTMLCell(80, 10, '', '', dol_htmlentitiesbr($linktopay), 0, 1);
				}

				$posy = $pdf->GetY();
			}

			// Auto-liquidation régime de la sous-traitance
			if (!empty($conf->global->ULTIMATEPDF_GENERATE_INVOICES_WITH_AUTO_LIQUIDATION)) {
				$pdf->SetFont('', '', $default_font_size - 2);
				$pdf->SetXY($this->marge_gauche, $posy);
				$titre1 = '<strong>' . $outputlangs->transnoentities("AutoLiquidation1") . '</strong>';
				$titre2 = $outputlangs->transnoentities("AutoLiquidation2");
				$pdf->writeHTMLCell($widthrecbox, 4, $this->marge_gauche, $posy + 2, $titre1 . ' ' . $titre2, 0, 1, false, true, 'L', true);

				$posy = $pdf->GetY();
			}

			//Display outstandingBills
			if (!empty($conf->global->ULTIMATEPDF_GENERATE_INVOICES_WITH_OUTSTANDINGBILL)) {
				$pdf->SetXY($this->marge_gauche, $posy);
				$this->_setFontForMulticurrencyCode($pdf, $object, $outputlangs);
				$pdf->SetFont('', '', $default_font_size - 2);
				$thirdparty = $object->thirdparty;
				$arrayoutstandingbills = $thirdparty->getOutstandingBills();
				$outstandingBills = $arrayoutstandingbills['opened'];
				$title = $langs->trans('CurrentOutstandingBill');
				$pdf->writeHTMLCell($widthrecbox, 4, $this->marge_gauche, $posy + 2, $title . ' : ' . '<strong>' . price($outstandingBills, 0, $outputlangs, 1, -1, -1, $object->multicurrency_code), 0, 1, false, true, 'L', true) . '</strong>';

				$pdf->SetFont($conf->global->MAIN_PDF_FORCE_FONT, '', $default_font_size - 2);

				$posy = $pdf->GetY();
			}

			// Example using extrafields for new_line
			$extrafields = new ExtraFields($this->db);
			$extralabels = $extrafields->fetch_name_optionals_label($object->table_element, true);
			$object->fetch($this->rowid);
			$object->fetch_optionals($this->rowid,
				$extralabels
			);
			$title = $outputlangs->convToOutputCharset($object->array_options['options_newline']);

			$sql = 'SELECT rowid, code, label, description';
			$sql .= ' FROM ' . MAIN_DB_PREFIX . 'c_ultimatepdf_line as uline';
			$sql .= " WHERE code ='" . $title . "'";
			if ($title == 0) $title = null;
			$result = $this->db->query($sql);
			if ($result) {
				if ($this->db->num_rows($result)) {
					$obj = $this->db->fetch_object($result);
					$title = $obj->description;
				}
			}
			$pdf->SetXY($this->marge_gauche, $posy);
			$pdf->writeHTMLCell($widthrecbox, 4, $this->marge_gauche, $posy + 2, $title, 0, 1, false, true, 'L', true);

			$posy = $pdf->GetY();

			// Show payment mode CHQ
			if (empty($object->mode_reglement_code) || $object->mode_reglement_code == 'CHQ') {
				// Si mode reglement non force ou si force a CHQ
				if (!empty($conf->global->FACTURE_CHQ_NUMBER)) {
					$diffsizetitle = (empty($conf->global->PDF_DIFFSIZE_TITLE) ? 3 : $conf->global->PDF_DIFFSIZE_TITLE);

					if ($conf->global->FACTURE_CHQ_NUMBER > 0) {
						$account = new Account($this->db);
						$account->fetch($conf->global->FACTURE_CHQ_NUMBER);

						$pdf->SetXY($this->marge_gauche, $posy + 2);
						$pdf->SetFont('', 'B', $default_font_size - $diffsizetitle);
						$pdf->MultiCell($widthrecbox, 3, $outputlangs->transnoentities('PaymentByChequeOrderedTo', $account->proprio), 0, 'L', 0);
						$posy = $pdf->GetY() + 1;

						if (empty($conf->global->MAIN_PDF_HIDE_CHQ_ADDRESS)) {
							$pdf->SetXY($this->marge_gauche, $posy + 2);
							$pdf->SetFont('', '', $default_font_size - $diffsizetitle);
							$pdf->MultiCell($widthrecbox, 3, $outputlangs->convToOutputCharset($account->owner_address), 0, 'L', 0);
							$posy = $pdf->GetY() + 2;
						}
					}
					if ($conf->global->FACTURE_CHQ_NUMBER == -1) {
						$pdf->SetXY($this->marge_gauche, $posy + 2);
						$pdf->SetFont('', 'B', $default_font_size - $diffsizetitle);
						$pdf->MultiCell($widthrecbox, 3, $outputlangs->transnoentities('PaymentByChequeOrderedTo', $this->emetteur->name), 0, 'L', 0);
						$posy = $pdf->GetY() + 1;

						if (empty($conf->global->MAIN_PDF_HIDE_CHQ_ADDRESS)) {
							$pdf->SetXY($this->marge_gauche, $posy + 2);
							$pdf->SetFont('', '', $default_font_size - $diffsizetitle);
							$pdf->MultiCell($widthrecbox, 3, $outputlangs->convToOutputCharset($this->emetteur->getFullAddress()), 0, 'L', 0);
							$posy = $pdf->GetY() + 2;
						}
					}
				}
			}

			// If payment mode not forced or forced to VIR, show payment with BAN
			if (empty($object->mode_reglement_code) || $object->mode_reglement_code == 'VIR') {
				if (!empty($object->fk_account) || !empty($object->fk_bank) || !empty($conf->global->FACTURE_RIB_NUMBER)) {
					$bankid = (empty($object->fk_account) ? $conf->global->FACTURE_RIB_NUMBER : $object->fk_account);
					if (!empty($object->fk_bank)) $bankid = $object->fk_bank;   // For backward compatibility when object->fk_account is forced with object->fk_bank
					$account = new Account($this->db);
					$account->fetch($bankid);

					$curx = $this->marge_gauche;
					$cury = $posy;

					$posy = pdf_bank($pdf, $outputlangs, $curx, $cury, $account, 0, $default_font_size);
					$posy = $pdf->GetY() - 25;
					// QR-code
					if (!empty($conf->global->ULTIMATEPDF_GENERATE_INVOICES_WITH_INVOICE_QRCODE)) {
						$posxQRcode = $this->marge_gauche + 65;
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
						$code = pdf_invoiceCodeContents();
						$pdf->write2DBarcode($code, 'QRCODE,M', $posxQRcode, $posy, 30, 30, $styleQr, 'N');
					}
				}
			}
		}
		return $posy;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Show total to pay
	 *
	 *	@param	TCPDF		$pdf            Object PDF
	 *	@param  Facture		$object         Object invoice
	 *	@param  int			$deja_regle     Montant deja regle
	 *	@param	int			$posy			Position depart
	 *	@param	Translate	$outputlangs	Objet langs
	 *	@return int							Position pour suite
	 */
	function _tableau_tot(&$pdf, $object, $deja_regle, $posy, $outputlangs)
	{
		// phpcs:enable		
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

		$tab2_top = $posy + 2;
		$tab2_hl = 4;
		$pdf->SetFont('', '', $default_font_size - 1);

		// Tableau total
		$col1x = $this->page_largeur / 2 + 2;
		$col2x = 170;
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

		$outputlangsbis = null;
		if (!empty($conf->global->PDF_USE_ALSO_LANGUAGE_CODE) && $outputlangs->defaultlang != $conf->global->PDF_USE_ALSO_LANGUAGE_CODE) {
			$outputlangsbis = new Translate('', $conf);
			$outputlangsbis->setDefaultLang($conf->global->PDF_USE_ALSO_LANGUAGE_CODE);
			$outputlangsbis->loadLangs(array("main", "dict", "companies", "bills", "products", "propal"));
		}

		if ($conf->global->ULTIMATE_GENERATE_INVOICES_WITH_WEIGHT_COLUMN == 1 && $conf->global->ULTIMATE_GENERATE_INVOICES_WITH_TOTAL_WEIGHT == 1) {
			//Total Weigth
			$totalweight = pdf_getlineweight($object, $outputlangs, 0);
			$pdf->SetXY($col1x, $tab2_top);
			$pdf->MultiCell($col2x - $col1x, $tab2_hl, $outputlangs->transnoentities("CalculatedWeight"), 0, 'L', 1);
			$pdf->SetXY($col2x, $tab2_top);
			$pdf->MultiCell($largcol2, $tab2_hl, $totalweight, 0, 'R', 1);
			$tab2_top += 8;

			//Total Qty
			/*$totalqty = pdf_getqty($object, $outputlangs, 0);
			$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
			$pdf->MultiCell($col2x - $col1x, $tab2_hl, $outputlangs->transnoentities("TotalQty"), 0, 'L', 1);
			$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
			$pdf->MultiCell($largcol2, $tab2_hl, $totalqty, 0, 'R', 1);
			$tab2_top += 8;*/
		}

		// Total HT
		$pdf->SetXY($col1x, $posy);
		$pdf->SetAlpha($opacity);
		$pdf->RoundedRect($deltax, $tab2_top, $widthrecbox, 4, 2, '1111', 'FD', $this->border_style, $bgcolor);
		$pdf->SetAlpha(1);
		$pdf->SetFont('', '', $default_font_size - 1);
		$pdf->SetXY($col1x, $tab2_top);
		$pdf->SetFillColor(255, 255, 255);
		$pdf->MultiCell($col2x - $col1x, $tab2_hl, $outputlangs->transnoentities("TotalHT"), 0, 'L', true);

		$total_ht = (($conf->multicurrency->enabled && isset($object->multicurrency_tx) && $object->multicurrency_tx != 1) ? $object->multicurrency_total_ht : $object->total_ht);
		$pdf->SetXY($col2x, $tab2_top);
		$this->_setFontForMulticurrencyCode($pdf, $object, $outputlangs);
		$pdf->MultiCell($largcol2, $tab2_hl, price($sign * ($total_ht + (!empty($object->remise) ? $object->remise : 0)), 0, $outputlangs, 1, -1, -1, (!empty($object->multicurrency_code) ? $object->multicurrency_code : $conf->currency)), 0, 'R', 1);
		$pdf->SetFont($conf->global->MAIN_PDF_FORCE_FONT, '', $default_font_size - 1);

		$posy = $pdf->getY();

		/*$transport_key = (empty($object->array_options['options_transport'])) ? '' : ($object->array_options['options_transport']);
		if ($transport_key > 0) {
			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->SetFillColor(255, 255, 255);
			$pdf->SetXY($col1x, $posy + 0.5);
			$pdf->MultiCell($col2x - $col1x, $tab2_hl, $outputlangs->transnoentities("Taxe Transport"), 0, 'L', 1);
			$transport_key = $total_ht * $transport_key / 100;
			$pdf->SetXY($col2x, $posy + 0.5);
			$pdf->MultiCell($largcol2, $tab2_hl, price($transport_key, 0, $outputlangs, 0, -1, -1, (!empty($object->multicurrency_code) ? $object->multicurrency_code : $conf->currency)), 0, 'R', 1);
		}

		$tab2_top = $pdf->GetY()-4;*/

		// Show VAT by rates and total	
		$total_ttc = ($conf->multicurrency->enabled && $object->multicurrency_tx != 1) ? $object->multicurrency_total_ttc : $object->total_ttc;

		$this->atleastoneratenotnull = 0;
		if ($conf->global->ULTIMATE_GENERATE_DOCUMENTS_WITHOUT_VAT == "no") {
			$tvaisnull = ((!empty($this->tva) && count($this->tva) == 1 && isset($this->tva['0.000']) && is_float($this->tva['0.000'])) ? true : false);
			if (!empty($conf->global->ULTIMATE_GENERATE_DOCUMENTS_WITHOUT_VAT_ISNULL) && $tvaisnull) {
				// Nothing to do
			} else {
				//Local tax 1 before VAT				
				foreach ($this->localtax1 as $localtax_type => $localtax_rate) {
					if (in_array((string) $localtax_type, array('1', '3', '5'))) continue;

					foreach ($localtax_rate as $tvakey => $tvaval) {
						if ($tvakey != 0)    // On affiche pas taux 0
						{
							$index++;
							$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);

							$tvacompl = '';
							if (preg_match('/\*/', $tvakey)) {
								$tvakey = str_replace('*', '', $tvakey);
								$tvacompl = " (" . $outputlangs->transnoentities("NonPercuRecuperable") . ")";
							}
							$totalvat = $outputlangs->transcountrynoentities("TotalLT1", $mysoc->country_code) . (is_object($outputlangsbis) ? ' / ' . $outputlangsbis->transcountrynoentities("TotalLT1", $mysoc->country_code) : '');
							$totalvat .= ' ';
							if (empty($conf->global->ULTIMATE_SHOW_HIDE_ALL_PRICES)) {
								$totalvat .= vatrate(abs($tvakey), 1) . $tvacompl;
								$pdf->MultiCell($col2x - $col1x, $tab2_hl, $totalvat, 0, 'L', 1);

								$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
								$this->_setFontForMulticurrencyCode($pdf, $object, $outputlangs);
								$pdf->MultiCell($largcol2, $tab2_hl, price($tvaval, 0, $outputlangs, 1, -1, -1, $object->multicurrency_code), 0, 'R', 1);
								$pdf->SetFont($conf->global->MAIN_PDF_FORCE_FONT, '', $default_font_size - 1);
							}
						}
					}
				}

				//Local tax 2 before VAT				
				foreach ($this->localtax2 as $localtax_type => $localtax_rate) {
					if (in_array((string) $localtax_type, array('1', '3', '5'))) continue;

					foreach ($localtax_rate as $tvakey => $tvaval) {
						if ($tvakey != 0)    // On affiche pas taux 0
						{
							$index++;
							$pdf->SetXY($col1x, $posy + $tab2_hl * $index);

							$tvacompl = '';
							if (preg_match('/\*/', $tvakey)) {
								$tvakey = str_replace('*', '', $tvakey);
								$tvacompl = " (" . $outputlangs->transnoentities("NonPercuRecuperable") . ")";
							}
							$totalvat = $outputlangs->transcountrynoentities("TotalLT2", $mysoc->country_code) . (is_object($outputlangsbis) ? ' / ' . $outputlangsbis->transcountrynoentities("TotalLT2", $mysoc->country_code) : '');
							$totalvat .= ' ';
							$totalvat .= vatrate(abs($tvakey), 1) . $tvacompl;
							if (empty($conf->global->ULTIMATE_SHOW_HIDE_ALL_PRICES)) {
								$pdf->MultiCell($col2x - $col1x, $tab2_hl, $totalvat, 0, 'L', 1);

								$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
								$this->_setFontForMulticurrencyCode($pdf, $object, $outputlangs);
								$pdf->MultiCell($largcol2, $tab2_hl, price($tvaval, 0, $outputlangs, 1, -1, -1, $object->multicurrency_code), 0, 'R', 1);
								$pdf->SetFont($conf->global->MAIN_PDF_FORCE_FONT, '', $default_font_size - 1);
							}
						}
					}
				}

				// VAT
				foreach($this->tva as $tvakey => $tvaval)
				{
					if ($tvakey > 0)    // On affiche pas taux 0
					{
						$this->atleastoneratenotnull++;

						$index++;
						$pdf->SetXY($col1x, $tab2_top + 0.5 + $tab2_hl * $index);
						$pdf->SetAlpha($opacity);
						$pdf->SetFont('', '', $default_font_size - 1);
						$bgcolor2 = array(177, 177, 177);						
						$pdf->RoundedRect($deltax, $tab2_top + 0.5 + $tab2_hl * $index, $widthrecbox, 4, 2, '1111', 'FD', $this->border_style, $bgcolor2);
						$pdf->SetAlpha(1);
						$tvacompl = '';
						if (preg_match('/\*/', $tvakey)) {
							$tvakey = str_replace('*', '', $tvakey);
							$tvacompl = " (" . $outputlangs->transnoentities("NonPercuRecuperable") . ")";
						}
						$totalvat = $outputlangs->transcountrynoentities("TotalVAT", $mysoc->country_code).(is_object($outputlangsbis) ? ' / '.$outputlangsbis->transcountrynoentities("TotalVAT", $mysoc->country_code) : '');
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

				//Local tax 1 after VAT
				foreach ($this->localtax1 as $localtax_type => $localtax_rate) {
					if (in_array((string) $localtax_type, array('2', '4', '6'))) continue;

					foreach ($localtax_rate as $tvakey => $tvaval) {
						if ($tvakey != 0)    // On affiche pas taux 0
						{
							$index++;
							$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);

							$tvacompl = '';
							if (preg_match('/\*/', $tvakey)) {
								$tvakey = str_replace('*', '', $tvakey);
								$tvacompl = " (" . $outputlangs->transnoentities("NonPercuRecuperable") . ")";
							}
							$pdf->SetXY($col1x, $tab2_top + 0.5 + $tab2_hl * $index);
							$pdf->SetFont('', '', $default_font_size - 1);
							$bgcolor2 = array(177, 177, 177);
							$pdf->SetAlpha($opacity);
							$pdf->RoundedRect($deltax, $tab2_top + 0.5 + $tab2_hl * $index, $widthrecbox, 4, 2, '1111', 'FD', $this->border_style, $bgcolor2);
							$pdf->SetAlpha(1);
							$totalvat = $outputlangs->transcountrynoentities("Taxe CSS", $mysoc->country_code) . (is_object($outputlangsbis) ? ' / ' . $outputlangsbis->transcountrynoentities("TotalLT1", $mysoc->country_code) : '');
							$totalvat .= ' ';

							$totalvat .= vatrate(abs($tvakey), 1) . $tvacompl;
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
				}

				//Local tax 2 after VAT
				foreach ($this->localtax2 as $localtax_type => $localtax_rate) {
					if (in_array((string) $localtax_type, array('2', '4', '6'))) continue;

					foreach ($localtax_rate as $tvakey => $tvaval) {
						if ($tvakey > 0)    // On affiche pas taux 0
						{
							$index++;
							$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);

							$tvacompl = '';
							if (preg_match('/\*/', $tvakey)) {
								$tvakey = str_replace('*', '', $tvakey);
								$tvacompl = " (" . $outputlangs->transnoentities("NonPercuRecuperable") . ")";
							}
							$pdf->SetXY($col1x, $tab2_top + 0.5 + $tab2_hl * $index);
							$pdf->SetFont('', '', $default_font_size - 1);
							$bgcolor2 = array(177, 177, 177);
							$pdf->SetAlpha($opacity);
							$pdf->RoundedRect($deltax, $tab2_top + 0.5 + $tab2_hl * $index, $widthrecbox, 4, 2, '1111', 'FD', $this->border_style, $bgcolor2);
							$pdf->SetAlpha(1);
							$totalvat = $outputlangs->transcountrynoentities("TPS", $mysoc->country_code) . (is_object($outputlangsbis) ? ' / ' . $outputlangsbis->transcountrynoentities("TotalLT2", $mysoc->country_code) : '');
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
				}

				// Revenue stamp
				if (price2num($object->revenuestamp) != 0) {
					$index++;
					$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
					$pdf->MultiCell($col2x - $col1x, $tab2_hl, $outputlangs->transnoentities("RevenueStamp"), $useborder, 'L', 1);

					$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
					$pdf->MultiCell($largcol2, $tab2_hl, price($sign * $object->revenuestamp), $useborder, 'R', 1);
				}

				// Total TTC
				if (empty($conf->global->ULTIMATE_SHOW_HIDE_ALL_PRICES)) {
					$index++;
					$pdf->SetXY($col1x, $tab2_top + 1 + $tab2_hl * $index);
					$pdf->SetTextColorArray($textcolor);				
					$pdf->SetAlpha($opacity);
					$pdf->RoundedRect($deltax, $tab2_top + 1 + $tab2_hl * $index, $widthrecbox, 4, 2, '1111', 'FD', $this->border_style, $bgcolor);
					$pdf->SetAlpha(1);
					$pdf->SetFillColor(255, 255, 255);
					$pdf->SetFont('', 'B', $default_font_size - 1);
					$pdf->MultiCell($col2x - $col1x, $tab2_hl, $outputlangs->transnoentities("TotalTTC") . (is_object($outputlangsbis) ? ' / ' . $outputlangsbis->transcountrynoentities("TotalTTC", $mysoc->country_code) : ''), $useborder, 'L', 1);

					//$total_ttc = ($conf->multicurrency->enabled && $object->multiccurency_tx != 1) ? $object->multicurrency_total_ttc : $object->total_ttc;
					$pdf->SetXY($col2x, $tab2_top + 1 + $tab2_hl * $index);
					$this->_setFontForMulticurrencyCode($pdf, $object, $outputlangs);
					$pdf->MultiCell($largcol2, $tab2_hl, price($total_ttc, 0, $outputlangs, 1, -1, -1, $object->multicurrency_code), $useborder, 'R', 1);
					$pdf->SetFont($conf->global->MAIN_PDF_FORCE_FONT, '', $default_font_size - 1);
				}

				// Retained warranty			
				if ($object->displayRetainedWarranty()) {
					$pdf->SetTextColor(40, 40, 40);
					$pdf->SetFillColor(255, 255, 255);

					$retainedWarranty = $object->getRetainedWarrantyAmount();
					$billedWithRetainedWarranty = $object->total_ttc - $retainedWarranty;

					// Billed - retained warranty
					$index++;
					$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
					$pdf->MultiCell($col2x - $col1x, $tab2_hl, $outputlangs->transnoentities("ToPayOn", dol_print_date($object->date_lim_reglement, 'day')), $useborder, 'L', 1);

					$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
					$this->_setFontForMulticurrencyCode($pdf, $object, $outputlangs);
					$pdf->MultiCell($largcol2, $tab2_hl, price($billedWithRetainedWarranty, 0, $outputlangs, 1, -1, -1, $object->multicurrency_code), $useborder, 'R', 1);
					$pdf->SetFont($conf->global->MAIN_PDF_FORCE_FONT, '', $default_font_size - 1);

					// retained warranty
					$index++;
					$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);

					$retainedWarrantyToPayOn = $outputlangs->transnoentities("RetainedWarranty") . ' (' . $object->retained_warranty . '%)';
					$retainedWarrantyToPayOn .= !empty($object->retained_warranty_date_limit) ? ' ' . $outputlangs->transnoentities("toPayOn", dol_print_date($object->retained_warranty_date_limit, 'day')) : '';

					$pdf->MultiCell($col2x - $col1x, $tab2_hl, $retainedWarrantyToPayOn, $useborder, 'L', 1);
					$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
					$this->_setFontForMulticurrencyCode($pdf, $object, $outputlangs);
					$pdf->MultiCell($largcol2, $tab2_hl, price($retainedWarranty, 0, $outputlangs, 1, -1, -1, $object->multicurrency_code), $useborder, 'R', 1);
					$pdf->SetFont($conf->global->MAIN_PDF_FORCE_FONT, '', $default_font_size - 1);
				}
			}
		} else {
			// Total TTC without VAT			
			$index++;
			$pdf->SetXY($col1x, $tab2_top);
			$pdf->SetTextColorArray($textcolor);
			$pdf->SetFont('', 'B', $default_font_size);
			$pdf->SetAlpha($opacity);
			//$pdf->RoundedRect($deltax, $tab2_top, $widthrecbox, 4, $roundradius, '1111', 'FD', $this->border_style, $bgcolor);
			$pdf->SetAlpha(1);
			$pdf->SetFillColor(255, 255, 255);
			//$pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("TotalHT"), 0, 'L', 1);
			$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
			$pdf->SetTextColorArray($textcolor);
			$pdf->SetFont('', 'B', $default_font_size);
			$pdf->SetAlpha($opacity);
			//$pdf->RoundedRect($deltax, $posy, $widthrecbox, 4, $roundradius, '1111', 'FD', $this->border_style, $bgcolor);
			$pdf->SetAlpha(1);
			$pdf->SetFillColor(255, 255, 255);
			//$pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("TotalTTC"), $useborder, 'L', 1);

			$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
			//$pdf->MultiCell($largcol2, $tab2_hl, $cursymbolbefore.price($total_ht + (! empty($object->remise)?$object->remise:0),0,$userlang).$cursymbolafter, 0, 'R', 1);		
		}

		$pdf->SetTextColorArray($textcolor);

		$creditnoteamount = $object->getSumCreditNotesUsed(($conf->multicurrency->enabled && $object->multicurrency_tx != 1) ? 1 : 0);	// Warning, this also includes excess received
		$depositsamount = $object->getSumDepositsUsed(($conf->multicurrency->enabled && $object->multicurrency_tx != 1) ? 1 : 0);
		$resteapayer = price2num($total_ttc - $deja_regle - $creditnoteamount - $depositsamount, 'MT');
		if (!empty($object->paye)) $resteapayer = 0;

		if (($deja_regle > 0 || $creditnoteamount > 0 || $depositsamount > 0) && empty($conf->global->INVOICE_NO_PAYMENT_DETAILS)) {
			// Already paid + Deposits
			$index++;
			$pdf->SetXY($col1x, $tab2_top + 0.8 + $tab2_hl * $index);
			$pdf->MultiCell($col2x - $col1x, $tab2_hl, $outputlangs->transnoentities("Paid"), 0, 'L', 0);
			$pdf->SetXY($col2x, $tab2_top + 0.8 + $tab2_hl * $index);
			$this->_setFontForMulticurrencyCode($pdf, $object, $outputlangs);
			$pdf->MultiCell($largcol2, $tab2_hl, price($deja_regle + $depositsamount, 0, $outputlangs, 1, -1, -1, $object->multicurrency_code), 0, 'R', 0);

			// Credit note
			if ($creditnoteamount) {
				$labeltouse = ($outputlangs->transnoentities("CreditNotesOrExcessReceived") != "CreditNotesOrExcessReceived") ? $outputlangs->transnoentities("CreditNotesOrExcessReceived") : $outputlangs->transnoentities("CreditNotes");
				$index++;
				$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
				$pdf->MultiCell($col2x - $col1x, $tab2_hl, $labeltouse, 0, 'L', 0);
				$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
				$this->_setFontForMulticurrencyCode($pdf, $object, $outputlangs);
				$pdf->MultiCell($largcol2, $tab2_hl, price($creditnoteamount, 0, $outputlangs, 1, -1, -1, $object->multicurrency_code), 0, 'R', 0);
			}

			// Escompte
			if ($object->close_code == Facture::CLOSECODE_DISCOUNTVAT) {
				$index++;
				$pdf->SetFillColor(255, 255, 255);
				$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
				$pdf->MultiCell($col2x - $col1x, $tab2_hl, $outputlangs->transnoentities("EscompteOfferedShort"), $useborder, 'L', 1);
				$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
				$this->_setFontForMulticurrencyCode($pdf, $object, $outputlangs);
				$pdf->MultiCell($largcol2, $tab2_hl, price($object->total_ttc - $deja_regle - $creditnoteamount - $depositsamount, 0, $outputlangs, 1, -1, -1, $object->multicurrency_code), $useborder, 'R', 1);

				$resteapayer = 0;
			}

			$index++;
			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->SetTextColorArray($textcolor);
			$pdf->SetFillColor(224, 224, 224);
			$pdf->SetXY($col1x, $tab2_top + 1 + $tab2_hl * $index);
			$pdf->MultiCell($col2x - $col1x, $tab2_hl, $outputlangs->transnoentities("RemainderToPay"), $useborder, 'L', 1);
			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->SetXY($col2x, $tab2_top + 1 + $tab2_hl * $index);
			$this->_setFontForMulticurrencyCode($pdf, $object, $outputlangs);
			$pdf->MultiCell($largcol2, $tab2_hl, price($resteapayer, 0, $outputlangs, 1, -1, -1, $object->multicurrency_code), $useborder, 'R', 1);
			
			// Fin
			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->SetTextColorArray($textcolor);
		}
		$pdf->SetFont($conf->global->MAIN_PDF_FORCE_FONT, '', $default_font_size);
		$index++;
		return ($tab2_top + ($tab2_hl * $index));
	}
	
	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps	
	/**
	 *	Show signature block
	 *
	 *	@param	TCPDF		$pdf            Object PDF
	 *  @param	Object		$object			Object to show
	 *	@param	int			$posy			Position depart
	 *	@param	Translate	$outputlangs	Objet langs
	 *	@return int							Position pour suite
	 */
	function _signature(&$pdf, $object, $posy, $outputlangs)
	{
		// phpcs:enable
		global $conf;

		$posy = $pdf->GetY() + 2;

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		$dashdotted =  (!empty($conf->global->ULTIMATE_DASH_DOTTED)) ?
		$conf->global->ULTIMATE_DASH_DOTTED : 0;

		$bordercolor =  (!empty($conf->global->ULTIMATE_BORDERCOLOR_COLOR)) ?
		html2rgb($conf->global->ULTIMATE_BORDERCOLOR_COLOR) : array(0, 63, 127);

		$textcolor =  (!empty($conf->global->ULTIMATE_TEXTCOLOR_COLOR)) ?
		html2rgb($conf->global->ULTIMATE_TEXTCOLOR_COLOR) : array(25, 25, 25);

		$this->border_style = array('width' => 0.2, 'cap' => 'butt', 'join' => 'miter', 'dash' => $dashdotted, 'color' => $bordercolor);
		$widthrecbox = ($this->page_largeur - $this->marge_gauche - $this->marge_droite) / 2;

		if (!empty($conf->global->ULTIMATEPDF_ADD_RESPONSABLE_SIGNATURE_IN_INVOICE)) {
			$heightforfreetext = (isset($conf->global->MAIN_PDF_FREETEXT_HEIGHT) ? $conf->global->MAIN_PDF_FREETEXT_HEIGHT : 5);	// Height reserved to output the free text on last page
			$heightforfooter = $this->marge_basse + (empty($conf->global->MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS) ? 12 : 22);	// Height reserved to output the footer (value include bottom margin)
			
			$deltay = $this->page_hauteur - $heightforfreetext - $heightforfooter;
			$posy = max($posy, $deltay);
			$deltax = $this->marge_gauche + $widthrecbox + 4;
			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->SetTextColorArray($textcolor);
			$pdf->SetXY($deltax, $posy);
			$text = $outputlangs->transnoentities('MadeTo') . ' ' . $this->emetteur->town . ' ' . $outputlangs->transnoentities('On') . ' ' . dol_print_date($object->date, "daytext", false, $outputlangs, true);
			$pdf->MultiCell(80, 3, $text, 0, 'L', 0);
			$posy = $pdf->GetY();
			$pdf->SetFont('', 'I', $default_font_size - 2);
			//$pdf->MultiCell(80, 3, $outputlangs->transnoentities('DocORDER4'), 0, 'L', 0);
			// Example using extrafields
			$title_key = (empty($object->array_options['options_signature'])) ? '' : ($object->array_options['options_signature']);
			$extrafields = new ExtraFields($this->db);
			$extralabels = $extrafields->fetch_name_optionals_label($object->table_element, true);
			if (is_array($extralabels) && key_exists('signature', $extralabels) && !empty($title_key)) {
				$responsable = $extrafields->showOutputField('signature', $title_key);
				$salerepobj = new User($this->db);
				$salerepobj->fetch($title_key, $responsable);
			}
			$inthatstr = $salerepobj->signature;
			$thisstr = 'image/';
			$thatstr = '" style';
			$imgsignature = between($thisstr, $thatstr, $inthatstr);
			$signaturepath = $conf->medias->multidir_output[$conf->entity] . '/image/' . $imgsignature;
			$imgsignsize = pdf_getSizeForImage($signaturepath);
			if (!empty($salerepobj->signature) && isset($imgsignsize['width']) && isset($imgsignsize['height'])) {
				$pdf->Image($signaturepath, $this->marge_gauche + 120, $posy, 0, max(20, $imgsignsize['height'])); // width=0 (auto)
			} else {
				$pdf->SetTextColor(200, 0, 0);
				$pdf->SetXY($this->marge_gauche + 2, $posy);
				$pdf->SetFont('', '', $default_font_size - 2);
				$pdf->MultiCell($widthrecbox, 3, $outputlangs->transnoentities("ErrorUserSignatureFileNotFound") . ' ' . $outputlangs->transnoentities("ErrorSignatureFileNotFound", $signaturepath), 0, 'L');
				$pdf->SetTextColorArray($textcolor);
			}
			return $posy;
		}
	}

	/**
	 *   Show table for lines
	 *
	 *   @param		TCPDF		$pdf     		Object PDF
	 *   @param		string		$tab_top		Top position of table
	 *   @param		string		$tab_height		Height of table (rectangle)
	 *   @param		int			$nexY			Y (not used)
	 *   @param		Translate	$outputlangs	Langs object
	 *   @param		int			$hidetop		1=Hide top bar of array and title, 0=Hide nothing, -1=Hide only title
	 *   @param		int			$hidebottom		Hide bottom bar of array
	 *   @param		string		$currency		Currency code
	 *   @return	void
	 */
	function _tableau(&$pdf, $tab_top, $tab_height, $nexY, $outputlangs, $hidetop = 0, $hidebottom = 0, $currency = '')
	{
		global $conf;

		$outputlangs->load("ultimatepdf@ultimatepdf");

		// Force to disable hidetop and hidebottom
		$hidebottom = 0;
		if ($hidetop) $hidetop = -1;

		$currency = !empty($currency) ? $currency : $conf->currency;
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
		$pdf->SetTextColorArray($textcolor);
		$pdf->SetFillColorArray($bgcolor);
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
		$pdf->RoundedRect($this->marge_gauche, $tab_top, $this->page_largeur - $this->marge_gauche - $this->marge_droite, $tab_height + 2, $roundradius, '0110', 'S', $this->border_style, $bgcolor);

		$this->pdfTabTitles($pdf, $tab_top - 8, $tab_height + 8, $outputlangs, $hidetop);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 *  Show top header of page.
	 *
	 *  @param	TCPDF		$pdf     		Object PDF
	 *  @param  Facture		$object     	Object to show
	 *  @param  int	    	$showaddress    0=no, 1=yes
	 *  @param  Translate	$outputlangs	Object lang for output
	 *  @param	string		$titlekey		Translation key to show as title of document
	 *  @return	void
	 */
	protected function _pagehead(&$pdf, $object, $showaddress, $outputlangs, $titlekey="")
	{
		global $conf, $db;

		$ltrdirection = 'L';
		if ($outputlangs->trans("DIRECTION") == 'rtl') $ltrdirection = 'R';

		// Translations
		$outputlangs->loadLangs(array("main", "companies", "bills", "propal", "deliveries", "projects", "ultimatepdf@ultimatepdf"));

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

		//affiche repere de pliage	
		if (!empty($conf->global->MAIN_DISPLAY_INVOICES_FOLD_MARK)) {
			$pdf->Line(0, ($this->page_hauteur) / 3, 3, ($this->page_hauteur) / 3);
		}

		pdf_new_pagehead($pdf, $outputlangs, $this->page_hauteur);

		// Show Draft Watermark
		if ($object->statut == Facture::STATUS_DRAFT && (!empty($conf->global->FACTURE_DRAFT_WATERMARK))) {
			pdf_watermark($pdf, $outputlangs, $this->page_hauteur, $this->page_largeur, 'mm', $conf->global->FACTURE_DRAFT_WATERMARK);
		}

		//Print content
		$pdf->SetTextColorArray($textcolor);
		$pdf->SetFont('', 'B', $default_font_size + 3);

		$posy = $this->marge_haute;
		$tab_width = $this->page_largeur - $this->marge_gauche - $this->marge_droite;

		$pdf->SetXY($this->marge_gauche, $posy);

		// Other Logo
		$id = $conf->global->ULTIMATE_DESIGN;
		$upload_dir	= $conf->ultimatepdf->dir_output . '/otherlogo/' . $id . '/';
		$filearray = dol_dir_list($upload_dir, "files", 0, '', '(\.meta|_preview.*\.png)$', 'name', 0, 1);
		if (!empty($filearray[0]['name'])) {
			$otherlogo = $conf->ultimatepdf->dir_output . '/otherlogo/' . $id . '/' . $filearray[0]['name'];
		}
		if (!empty($conf->global->ULTIMATE_DESIGN) && !empty($filearray[0]['relativename']) && is_readable($otherlogo) && !empty($filearray) && $conf->global->PDF_DISABLE_ULTIMATE_OTHERLOGO_FILE == 0) {
			$logo_height = max(pdf_getUltimateHeightForOtherLogo($otherlogo, true), 20);
			$pdf->Image($otherlogo, $this->marge_gauche, $posy, 0, $logo_height);	// width=0 (auto)
		// Logo from company
		} elseif (empty($conf->global->PDF_DISABLE_MYCOMPANY_LOGO)) {
			if ($this->emetteur->logo) {
				$logodir = $conf->mycompany->dir_output;
				if (!empty($conf->mycompany->multidir_output[$object->entity])) $logodir = $conf->mycompany->multidir_output[$object->entity];
				if (empty($conf->global->MAIN_PDF_USE_LARGE_LOGO)) {
					$logo = $logodir . '/logos/thumbs/' . $this->emetteur->logo_small;
				} else {
					$logo = $logodir . '/logos/' . $this->emetteur->logo;
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
		if (!empty($conf->global->ULTIMATEPDF_GENERATE_INVOICES_WITH_TOP_BARCODE)) {
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
				$pdf->write1DBarcode($barcode, $object->thirdparty->barcode_type_code, $posxbarcode, $posybarcode, '', 14, 0.4, $styleBc, 'R');
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
		if (!empty($conf->global->ULTIMATEPDF_GENERATE_INVOICES_WITH_TOP_QRCODE)) {
			$code = pdf_codeContents();
			$pdf->write2DBarcode($code, 'QRCODE,L', $posxQRcode, $posy, $heightQRcode, $heightQRcode, $styleQr, 'N');
		}
		// My Company QR-code
		if (!empty($conf->global->ULTIMATEPDF_GENERATE_INVOICES_WITH_MYCOMP_QRCODE)) {
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

		$pdf->SetFont('', 'B', $default_font_size + 3);
		$pdf->SetXY($posx, $posy);
		$pdf->SetTextColorArray($textcolor);

		// Example using extrafields for new title of document : complementary attribute should be named newtitle.
		$title_key = (empty($object->array_options['options_newtitle'])) ? '' : ($object->array_options['options_newtitle']);
		$extrafields = new ExtraFields($this->db);
		$extralabels = $extrafields->fetch_name_optionals_label($object->table_element, true);
		if (is_array($extralabels) && key_exists('newtitle', $extralabels) && !empty($title_key)) {
			$titlekey = $extrafields->showOutputField('newtitle', $title_key);
		}
		$title = $outputlangs->transnoentities("Invoice");
		if ($object->type == 1) $title = $outputlangs->transnoentities("InvoiceReplacement");
		if ($object->type == 2) $title = $outputlangs->transnoentities("InvoiceAvoir");
		if ($object->type == 3) $title = $outputlangs->transnoentities("InvoiceDeposit");
		if ($object->type == 4) $title = $outputlangs->transnoentities("InvoiceProFormat");
		if (count($object->tab_previous_situation_invoice) > 0) {
			// List of previous invoices
			$current_situation_counter = array();
			foreach ($object->tab_previous_situation_invoice as $prev_invoice) {
				$tmptotalpaidforthisinvoice = $prev_invoice->getSommePaiement();
				$current_situation_counter[] = (($prev_invoice->type == Facture::TYPE_CREDIT_NOTE) ?-1 : 1) * $prev_invoice->situation_counter;
				
			}
		}
		$object->situation_counter = $prev_invoice->situation_counter + 1;
		if ($this->situationinvoice) $title = $outputlangs->transnoentities("InvoiceSituation").' '.$outputlangs->convToOutputCharset(" n° ".$object->situation_counter);
		$pdf->writeHTMLCell($rightSideWidth, 3, $posx, $posy, !empty($titlekey) ? $titlekey : $title, 0, 1, false, true, 'R', true);

		$pdf->SetFont('', 'B', $default_font_size + 2);

		$posy = $pdf->getY();
		$pdf->SetXY($posx, $posy);
		$pdf->SetTextColorArray($textcolor);
		$pdf->writeHTMLCell($rightSideWidth, 3, $posx, $posy, $outputlangs->transnoentities("Ref") . " : " . $outputlangs->convToOutputCharset($object->ref), 0, 1, false, true, 'R', true);

		$posy = $pdf->getY();
		$pdf->SetFont('', '', $default_font_size - 1);

		$objectidnext = $object->getIdReplacingInvoice('validated');
		if ($object->type == 0 && $objectidnext) {
			$objectreplacing = new Facture($this->db);
			$objectreplacing->fetch($objectidnext);

			$posy = $pdf->getY();
			$pdf->SetXY($posx, $posy);
			$pdf->SetTextColorArray($textcolor);
			$pdf->writeHTMLCell($rightSideWidth, 3, $posx, $posy, $outputlangs->transnoentities("ReplacementByInvoice") . ' : ' . $outputlangs->convToOutputCharset($objectreplacing->ref), 0, 1, false, true, 'R', true);
		}
		if ($object->type == 1) {
			$objectreplaced = new Facture($this->db);
			$objectreplaced->fetch($object->fk_facture_source);

			$posy = $pdf->getY();
			$pdf->SetXY($posx, $posy);
			$pdf->SetTextColorArray($textcolor);
			$pdf->writeHTMLCell($rightSideWidth, 3, $posx, $posy, $outputlangs->transnoentities("ReplacementInvoice") . ' : ' . $outputlangs->convToOutputCharset($objectreplaced->ref), 0, 1, false, true, 'R', true);
		}
		if ($object->type == 2) {
			$objectreplaced = new Facture($this->db);
			$objectreplaced->fetch($object->fk_facture_source);

			$posy = $pdf->getY();
			$pdf->SetXY($posx, $posy);
			$pdf->SetTextColorArray($textcolor);
			$pdf->writeHTMLCell($rightSideWidth, 3, $posx, $posy, $outputlangs->transnoentities("CorrectionInvoice") . ' : ' . $outputlangs->convToOutputCharset($objectreplaced->ref), 0, 1, false, true, 'R', true);
		}

		// Example using extrafields
		$title_key = (empty($object->array_options['options_codesupplier'])) ? '' : ($object->array_options['options_codesupplier']);
		$extrafields = new ExtraFields($this->db);
		$extralabels = $extrafields->fetch_name_optionals_label($object->table_element, true);
		if (is_array($extralabels) && key_exists('codesupplier', $extralabels) && !empty($title_key)) {
			$title = $extrafields->showOutputField('codesupplier', $title_key);
			$posy = $pdf->getY();
			$pdf->SetXY($posx, $posy);
			$pdf->SetTextColorArray($textcolor);
			$pdf->writeHTMLCell($rightSideWidth, 3, $posx, $posy, $title, 0, 1, false, true, 'R', true);
		}

		$posy = $pdf->GetY();

		if (!empty($conf->global->ULTIMATE_INVOICES_PDF_SHOW_PROJECT)) {
			$object->fetch_projet();
			if (!empty($object->project->ref)) {
				$pdf->SetTextColorArray($textcolor);
				$pdf->SetFont('', 'B', $default_font_size - 1);
				$pdf->SetXY($posx, $posy);
				$pdf->writeHTMLCell($rightSideWidth, 3, $posx, $posy, $outputlangs->transnoentities("RefProject") . " : " . (empty($object->project->title) ? '' : $object->projet->title), 0, 1, false, true, 'R', true);
			}
		}
		if (!empty($conf->global->ULTIMATE_INVOICES_PDF_SHOW_PROJECT_REF)) {
			$object->fetch_projet();
			if (!empty($object->project->ref)) {
				$pdf->SetTextColorArray($textcolor);
				$pdf->SetFont('', 'B', $default_font_size - 1);
				$pdf->SetXY($posx, $posy);
				$pdf->writeHTMLCell($rightSideWidth, 3, $posx, $posy, $outputlangs->transnoentities("RefProject") . " : " . (empty($object->project->ref) ? '' : $object->projet->ref), 0, 1, false, true, 'R', true);
			}
		}

		$posy = $pdf->getY();

		$pdf->SetXY($posx, $posy);
		
		// Show list of linked objects
		if (empty($conf->global->ULTIMATEPDF_GENERATE_INVOICES_WITH_ALL_SHIPMENTS)) {
			$posy = pdf_writeLinkedObjects($pdf, $object, $outputlangs, $posx, $posy, $rightSideWidth, 3, 'R', $default_font_size);
		} else {
			$linkedobjects = pdf_getLinkedObjects($object, $outputlangs);
			
			if (!empty($linkedobjects)) {
				foreach ($linkedobjects as $linkedobject) {
					$reftoshow = $linkedobject["ref_value"];
					//$reftoshow = trim(strstr($reftoshow, '(', true));
					$datevalue = $linkedobject["date_value"];
					
					if ($reftoshow) {
						//var_dump($reftoshow);exit;
						$pdf->SetFont('', '', $default_font_size - 2);
						$pdf->writeHTMLCell($rightSideWidth, 3, $posx, $posy, $outputlangs->transnoentities("RefOrder") . " : " . $outputlangs->transnoentities($reftoshow) . ' / ' . $outputlangs->transnoentities($datevalue), 0, 1, false, true, 'R', true);

						$sql = "SELECT co.rowid, co.ref FROM " . MAIN_DB_PREFIX . "commande as co WHERE co.ref ='$reftoshow'";
						$resql = $this->db->query($sql);

						if ($resql) {
							$num = $this->db->num_rows($resql);
							$i = 0;
							$commande = new Commande($this->db);
							while ($i < $num) {
								$obj = $this->db->fetch_object($resql);
								$commande->fetch($obj->rowid);
								if ($commande > 0) {
									$posy = pdf_writeLinkedObjects($pdf, $commande, $outputlangs, $posx, $posy, $rightSideWidth, 3, 'R', $default_font_size);
								}
								$i++;
							}
						}
					}
				}
			}
		}
		
		$posy = $pdf->getY();	

		if ($showaddress) {
			// Customer and Sender properties
			$carac_emetteur = '';
			// Add internal contact of invoice if defined
			$arrayidcontact = $object->getIdContact('internal', 'SALESREPFOLL');
			if (count($arrayidcontact) > 0) {
				$object->fetch_user($arrayidcontact[0]);
				$labelbeforecontactname = ($outputlangs->transnoentities("FromContactName") != 'FromContactName' ? $outputlangs->transnoentities("FromContactName") : $outputlangs->transnoentities("Name"));
				$carac_emetteur .= ($carac_emetteur ? "\n" : '') . $labelbeforecontactname . " " . $outputlangs->convToOutputCharset($object->user->getFullName($outputlangs)) . "\n<br>" . $object->user->email . "\n<br>";
			}
			// Sender properties
			$carac_emetteur .= pdf_element_build_address($outputlangs, $this->emetteur, $object->thirdparty, '', 0, 'source', $object);

			// Show sender
			$posy = $logo_height + $this->marge_haute + 4;
			$posx = $this->marge_gauche;
			if (($conf->global->ULTIMATE_PDF_INVOICE_ADDALSOTARGETDETAILS == 1) || (!empty($conf->global->MAIN_INFO_SOCIETE_NOTE) && !empty($this->emetteur->note_private)) || (!empty($conf->global->MAIN_INFO_TVAINTRA) && !empty($this->emetteur->tva_intra))) {
				$hautcadre = 48;
			} else {
				$hautcadre = 40;
			}
			$widthrecbox = $conf->global->ULTIMATE_WIDTH_RECBOX - 2;
			$widthrecboxrecipient = $tab_width - $conf->global->ULTIMATE_WIDTH_RECBOX;
			if (!empty($conf->global->ULTIMATE_INVERT_SENDER_RECIPIENT) && ($conf->global->ULTIMATE_INVERT_SENDER_RECIPIENT != "no")) $posx = $this->page_largeur - $this->marge_droite - $widthrecbox;
			if ($conf->global->ULTIMATE_CONTACT_ADDRESS_BLOCKS_OVER == 1) {
				$hreceipt = $hautcadre * 0.5;
				$wreceipt = $widthrecboxrecipient;
				$posxba = $this->page_largeur - $this->marge_droite - $widthrecboxrecipient;
				$posxbc = $posxba;
				$posxbd = $this->page_largeur - $this->marge_droite - $widthrecboxrecipient;
				$posyba = $posy;
				$posybc = $logo_height + $this->marge_haute + 4 + $hreceipt;
				$posybd = $logo_height + $this->marge_haute + 8 + $hreceipt;
			} else {
				$hreceipt = $hautcadre;
				$wreceipt = $widthrecboxrecipient * 0.5;
				$posxba = $this->page_largeur - $this->marge_droite - $widthrecboxrecipient - $wreceipt;
				$posxbc = $this->page_largeur - $this->marge_droite - $wreceipt;
				$posxbd = $this->page_largeur - $this->marge_droite - $widthrecboxrecipient;
				$posyba = $posy - 4;
				$posybc = $posy;
				$posybd = $posybc;
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
			$pdf->writeHTMLCell($widthrecbox - 5, 4, $posx + 2, $posy, $carac_emetteur, 0, 2, 0, true, $ltrdirection, true);
			$posy = $pdf->getY();

			// Show private note from societe
			if (!empty($conf->global->MAIN_INFO_SOCIETE_NOTE) && !empty($this->emetteur->note_private)) {
				$pdf->SetFont('', '', $default_font_size - 1);
				$pdf->writeHTMLCell($widthrecbox - 5, 8, $posx + 2, $posy + 2, dol_string_nohtmltag($this->emetteur->note_private), 0, 1, 0, true, $ltrdirection, true);
			}

			// If BILLING and SHIPPING contact defined, we use it
			if ($object->getIdContact('external', 'BILLING') && $object->getIdContact('external', 'SHIPPING')) {
				// If BILLING contact defined on invoice, we use it
				$usecontact = false;
				$arrayidcontact = $object->getIdContact('external', 'BILLING');
				if (count($arrayidcontact) > 0) {
					$usecontact = true;
					$result = $object->fetch_contact($arrayidcontact[0]);
				}

				// Recipient name
				// On peut utiliser le nom de la societe du contact
				if ($usecontact && ($object->contact->fk_soc != $object->thirdparty->id && (!isset($conf->global->MAIN_USE_COMPANY_NAME_OF_CONTACT) || !empty($conf->global->MAIN_USE_COMPANY_NAME_OF_CONTACT)))) {
					$thirdparty = $object->contact;
				} else {
					$thirdparty = $object->thirdparty;
				}

				$carac_client_name = pdfBuildThirdpartyName($thirdparty, $outputlangs);

				// Recipient address
				$mode =  'target';
				$carac_client = pdf_element_build_address($outputlangs, $this->emetteur, $object->thirdparty, ($usecontact ? $object->contact : ''), $usecontact, $mode, $object, true);

				// Show recipient
				$posy = $logo_height + $this->marge_haute + 4;
				$posx = $this->page_largeur - $this->marge_droite - $widthrecboxrecipient;
				if (!empty($conf->global->ULTIMATE_INVERT_SENDER_RECIPIENT) && ($conf->global->ULTIMATE_INVERT_SENDER_RECIPIENT != "no")) $posx = $this->marge_gauche;

				// Show recipient frame
				$pdf->SetTextColorArray($textcolor);
				$pdf->SetFont('', '', $default_font_size - 2);

				// Show invoice address
				$pdf->SetAlpha($opacity);
				$pdf->RoundedRect($posx, $posy, $wreceipt, $hreceipt, $roundradius, '1111', $receiptstyle, $this->border_style, $bgcolor);
				$pdf->SetAlpha(1);
				$pdf->SetXY($posxba, $posyba);
				$pdf->MultiCell($widthrecboxrecipient, 4, $outputlangs->transnoentities("BillAddress"), 0, 'R');

				// Show recipient name
				$pdf->SetXY($posx + 2, $posy + 2);
				$pdf->SetFont('', 'B', $default_font_size);
				$pdf->MultiCell($wreceipt - 5, 4, $carac_client_name, 0, $ltrdirection);

				$posy = $pdf->getY();

				// Show recipient information
				$pdf->SetFont('', '', $default_font_size - 1);
				$pdf->writeHTMLCell($wreceipt - 5, 4, $posx + 2, $posy, $carac_client, 0, 2, 0, true, $ltrdirection, true);

				// If SHIPPING contact defined on invoice, we use it
				$usecontact = false;
				$arrayidcontact = $object->getIdContact('external', 'SHIPPING');

				if (count($arrayidcontact) > 0) {
					$usecontact = true;
					$result = $object->fetch_contact($arrayidcontact[0]);
				}

				// On peut utiliser le nom de la societe du contact
				if ($usecontact && !empty($conf->global->MAIN_USE_COMPANY_NAME_OF_CONTACT)) {
					$thirdparty = $object->contact;
				} else {
					$thirdparty = $object->thirdparty;
				}

				$carac_client_name = pdfBuildThirdpartyName($thirdparty, $outputlangs);

				$mode = 'target';
				$carac_client = pdf_element_build_address($outputlangs, $this->emetteur, $object->thirdparty, ($usecontact ? $object->contact : ''), $usecontact, $mode, $object, true);

				// Show recipient frame
				$pdf->SetTextColorArray($textcolor);
				$pdf->SetFont('', '', $default_font_size - 2);

				// Show shipping address
				$pdf->SetAlpha($opacity);
				$pdf->RoundedRect($posxbc, $posybc, $wreceipt, $hreceipt, $roundradius, '1111', $receiptstyle, $this->border_style, $bgcolor);
				$pdf->SetAlpha(1);
				$pdf->SetXY($posxbd, $posybd - 4);
				$pdf->MultiCell($widthrecboxrecipient, 4, $outputlangs->transnoentities("DeliveryAddress"), 0, 'R');

				// Show recipient name
				$pdf->SetXY($posxbc + 2, $posybc + 2);
				$pdf->SetFont('', 'B', $default_font_size);
				$pdf->MultiCell($wreceipt - 5, 4, $carac_client_name, 0, $ltrdirection);

				$posybc = $pdf->getY();

				// Show recipient information
				$pdf->SetFont('', '', $default_font_size - 1);
				$pdf->writeHTMLCell($wreceipt - 5, 4, $posxbc + 2, $posybc, $carac_client, 0, 2, 0, true, $ltrdirection, true);
			} 
			// If SERVICE and SHIPPING contact defined, we use it
			elseif ($object->getIdContact('external', 'SERVICE') && $object->getIdContact('external', 'SHIPPING')) {
				// If SERVICE contact defined on invoice, we use it
				$usecontact = false;
				$arrayidcontact = $object->getIdContact('external', 'SERVICE');
				if (count($arrayidcontact) > 0) {
					$usecontact = true;
					$result = $object->fetch_contact($arrayidcontact[0]);
				}

				// Recipient name
				// On peut utiliser le nom de la societe du contact
				if ($usecontact && ($object->contact->fk_soc != $object->thirdparty->id && (!isset($conf->global->MAIN_USE_COMPANY_NAME_OF_CONTACT) || !empty($conf->global->MAIN_USE_COMPANY_NAME_OF_CONTACT)))) {
					$thirdparty = $object->contact;
				} else {
					$thirdparty = $object->thirdparty;
				}

				$carac_client_name = pdfBuildThirdpartyName($thirdparty, $outputlangs);

				// Recipient address
				$mode = 'target';
				$carac_client = pdf_element_build_address($outputlangs, $this->emetteur, $object->thirdparty, ($usecontact ? $object->contact : ''), $usecontact, $mode, $object, true);

				// Show recipient
				$posy = $logo_height + $this->marge_haute + 4;
				$posx = $this->page_largeur - $this->marge_droite - $widthrecboxrecipient;
				if (!empty($conf->global->ULTIMATE_INVERT_SENDER_RECIPIENT) && ($conf->global->ULTIMATE_INVERT_SENDER_RECIPIENT != "no")) $posx = $this->marge_gauche;

				// Show recipient frame
				$pdf->SetTextColorArray($textcolor);
				$pdf->SetFont('', '', $default_font_size - 2);

				// Show invoice address
				$pdf->SetAlpha($opacity);
				$pdf->RoundedRect($posx, $posy, $wreceipt, $hreceipt, $roundradius, '1111', $receiptstyle, $this->border_style, $bgcolor);
				$pdf->SetAlpha(1);
				$pdf->SetXY($posxba, $posyba);
				$pdf->MultiCell($widthrecboxrecipient, 4, $outputlangs->transnoentities("TypeContact_facture_external_SERVICE"), 0, 'R');

				// Show recipient name
				$pdf->SetXY($posx + 2, $posy + 2);
				$pdf->SetFont('', 'B', $default_font_size);
				$pdf->MultiCell($wreceipt - 5, 4, $carac_client_name, 0, $ltrdirection);

				$posy = $pdf->getY();

				// Show recipient information
				$pdf->SetFont('', '', $default_font_size - 1);
				$pdf->writeHTMLCell($wreceipt - 5, 4, $posx + 2, $posy, $carac_client, 0, 2, 0, true, $ltrdirection, true);

				// If SHIPPING contact defined on invoice, we use it
				$usecontact = false;
				$arrayidcontact = $object->getIdContact('external', 'SHIPPING');

				if (count($arrayidcontact) > 0) {
					$usecontact = true;
					$result = $object->fetch_contact($arrayidcontact[0]);
				}

				// On peut utiliser le nom de la societe du contact
				if ($usecontact && !empty($conf->global->MAIN_USE_COMPANY_NAME_OF_CONTACT)) {
					$thirdparty = $object->contact;
				} else {
					$thirdparty = $object->thirdparty;
				}

				$carac_client_name = pdfBuildThirdpartyName($thirdparty, $outputlangs);

				$mode = 'target';
				$carac_client = pdf_element_build_address($outputlangs, $this->emetteur, $object->thirdparty, ($usecontact ? $object->contact : ''), $usecontact, $mode, $object, true);

				// Show recipient frame
				$pdf->SetTextColorArray($textcolor);
				$pdf->SetFont('', '', $default_font_size - 2);

				// Show shipping address
				$pdf->SetAlpha($opacity);
				$pdf->RoundedRect($posxbc, $posybc, $wreceipt, $hreceipt, $roundradius, '1111', $receiptstyle, $this->border_style, $bgcolor);
				$pdf->SetAlpha(1);
				$pdf->SetXY($posxbd, $posybd - 4);
				$pdf->MultiCell($widthrecboxrecipient, 4, $outputlangs->transnoentities("DeliveryAddress"), 0, 'R');

				// Show recipient name
				$pdf->SetXY($posxbc + 2, $posybc + 2);
				$pdf->SetFont('', 'B', $default_font_size);
				$pdf->MultiCell($wreceipt - 5, 4, $carac_client_name, 0, $ltrdirection);

				$posybc = $pdf->getY();

				// Show recipient information
				$pdf->SetFont('', '', $default_font_size - 1);
				$pdf->writeHTMLCell($wreceipt - 5, 4, $posxbc + 2, $posybc, $carac_client, 0, 2, 0, true, $ltrdirection, true);
			} 
			// If SERVICE and BILLING contact defined, we use it
			elseif ($object->getIdContact('external', 'SERVICE') && $object->getIdContact('external', 'BILLING')) {
				// If SERVICE contact defined on invoice, we use it
				$usecontact = false;
				$arrayidcontact = $object->getIdContact('external', 'SERVICE');
				if (count($arrayidcontact) > 0) {
					$usecontact = true;
					$result = $object->fetch_contact($arrayidcontact[0]);
				}

				// Recipient name
				// On peut utiliser le nom de la societe du contact
				if ($usecontact && ($object->contact->fk_soc != $object->thirdparty->id && (!isset($conf->global->MAIN_USE_COMPANY_NAME_OF_CONTACT) || !empty($conf->global->MAIN_USE_COMPANY_NAME_OF_CONTACT)))) {
					$thirdparty = $object->contact;
				} else {
					$thirdparty = $object->thirdparty;
				}

				$carac_client_name = pdfBuildThirdpartyName($thirdparty, $outputlangs);

				// Recipient address
				$mode = 'target';
				$carac_client = pdf_element_build_address($outputlangs, $this->emetteur, $object->thirdparty, ($usecontact ? $object->contact : ''), $usecontact, $mode, $object, true);

				// Show recipient
				$posy = $logo_height + $this->marge_haute + 4;
				$posx = $this->page_largeur - $this->marge_droite - $widthrecboxrecipient;
				if (!empty($conf->global->ULTIMATE_INVERT_SENDER_RECIPIENT) && ($conf->global->ULTIMATE_INVERT_SENDER_RECIPIENT != "no")) $posx = $this->marge_gauche;

				// Show recipient frame
				$pdf->SetTextColorArray($textcolor);
				$pdf->SetFont('', '', $default_font_size - 2);

				// Show invoice address
				$pdf->SetAlpha($opacity);
				$pdf->RoundedRect($posx, $posy, $wreceipt, $hreceipt, $roundradius, '1111', $receiptstyle, $this->border_style, $bgcolor);
				$pdf->SetAlpha(1);
				$pdf->SetXY($posxba, $posyba);
				$pdf->MultiCell($widthrecboxrecipient, 4, dol_trunc($outputlangs->transnoentities("TypeContact_facture_external_SERVICE"), 32), 0, 'R');

				// Show recipient name
				$pdf->SetXY($posx + 2, $posy + 2);
				$pdf->SetFont('', 'B', $default_font_size);
				$pdf->MultiCell($wreceipt - 5, 4, $carac_client_name, 0, $ltrdirection);

				$posy = $pdf->getY();

				// Show recipient information
				$pdf->SetFont('', '', $default_font_size - 1);
				$pdf->writeHTMLCell($wreceipt - 5, 4, $posx + 2, $posy, $carac_client, 0, 2, 0, true, $ltrdirection, true);

				// If BILLING contact defined on invoice, we use it
				$usecontact = false;
				$arrayidcontact = $object->getIdContact('external', 'BILLING');

				if (count($arrayidcontact) > 0) {
					$usecontact = true;
					$result = $object->fetch_contact($arrayidcontact[0]);
				}

				// On peut utiliser le nom de la societe du contact
				if ($usecontact && !empty($conf->global->MAIN_USE_COMPANY_NAME_OF_CONTACT)) {
					$thirdparty = $object->contact;
				} else {
					$thirdparty = $object->thirdparty;
				}

				$carac_client_name = pdfBuildThirdpartyName($thirdparty, $outputlangs);

				$mode = 'target';
				$carac_client = pdf_element_build_address($outputlangs, $this->emetteur, $object->thirdparty, ($usecontact ? $object->contact : ''), $usecontact, $mode, $object, true);

				// Show recipient frame
				$pdf->SetTextColorArray($textcolor);
				$pdf->SetFont('', '', $default_font_size - 2);

				// Show billing address
				$pdf->SetAlpha($opacity);
				$pdf->RoundedRect($posxbc, $posybc, $wreceipt, $hreceipt, $roundradius, '1111', $receiptstyle, $this->border_style, $bgcolor);
				$pdf->SetAlpha(1);
				$pdf->SetXY($posxbd, $posybd - 4);
				$pdf->MultiCell($widthrecboxrecipient, 4, dol_trunc($outputlangs->transnoentities("BillAddress"), 30), 0, 'R');

				// Show recipient name
				$pdf->SetXY($posxbc + 2, $posybc + 2);
				$pdf->SetFont('', 'B', $default_font_size);
				$pdf->MultiCell($wreceipt - 5, 4, $carac_client_name, 0, $ltrdirection);

				$posybc = $pdf->getY();

				// Show recipient information
				$pdf->SetFont('', '', $default_font_size - 1);
				$pdf->writeHTMLCell($wreceipt - 5, 4, $posxbc + 2, $posybc, $carac_client, 0, 2, 0, true, $ltrdirection, true);
			} 
			// If SHIPPING contact defined, we use it
			 elseif ($object->getIdContact('external', 'SHIPPING')) {
				$usecontact = false;
				$arrayidcontact = $object->getIdContact('external', 'SHIPPING');
				if (count($arrayidcontact) > 0) {
					$usecontact = true;
					$result = $object->fetch_contact($arrayidcontact[0]);
				}

				// Recipient name
				// On peut utiliser le nom de la societe du contact
				if ($usecontact && ($object->contact->fk_soc != $object->thirdparty->id && (!isset($conf->global->MAIN_USE_COMPANY_NAME_OF_CONTACT) || !empty($conf->global->MAIN_USE_COMPANY_NAME_OF_CONTACT)))) {
					$thirdparty = $object->contact;
				} else {
					$thirdparty = $object->thirdparty;
				}

				$carac_client_name = pdfBuildThirdpartyName($thirdparty, $outputlangs);

				// Recipient address
				$mode = 'target';
				$carac_client = pdf_element_build_address($outputlangs, $this->emetteur, $thirdparty, $object->contact, $usecontact, $mode, $object, false);

				// Show recipient
				$widthrecboxrecipient = $tab_width - $conf->global->ULTIMATE_WIDTH_RECBOX - 2;
				$posy = $logo_height + $this->marge_haute + 4;
				$posx = $this->page_largeur - $this->marge_droite - $widthrecboxrecipient;
				if (!empty($conf->global->ULTIMATE_INVERT_SENDER_RECIPIENT) && ($conf->global->ULTIMATE_INVERT_SENDER_RECIPIENT != "no")) $posx = $this->marge_gauche;

				// Show recipient frame
				$pdf->SetTextColorArray($textcolor);
				$pdf->SetFont('', '', $default_font_size - 2);

				// Show shipping address
				$pdf->SetXY($posx, $posy - 4);
				$pdf->SetAlpha($opacity);
				$pdf->RoundedRect($posx, $posy, $widthrecboxrecipient, $hautcadre, $roundradius, '1111', $receiptstyle, $this->border_style, $bgcolor);
				$pdf->SetAlpha(1);
				$pdf->MultiCell($widthrecboxrecipient, 4, $outputlangs->transnoentities("DeliveryAddress"), 0, 'R');

				// Show recipient name
				$pdf->SetXY($posx + 2, $posy + 3);
				$pdf->SetFont('', 'B', $default_font_size);
				$pdf->MultiCell($widthrecboxrecipient - 5, 4, $carac_client_name, 0, 'L');

				$posy = $pdf->getY();

				// Show recipient information
				$pdf->SetFont('', '', $default_font_size - 1);
				$pdf->SetXY($posx + 2, $posy);
				$pdf->writeHTMLCell($widthrecboxrecipient - 5, 4, $posx + 2, $posy, $carac_client, 0, 2, 0, true, 'L', true);
			}
			// If BILLING contact defined on invoice, we use it
			elseif ($object->getIdContact('external', 'BILLING')) {
				$usecontact = false;
				$arrayidcontact = $object->getIdContact('external', 'BILLING');
				if (count($arrayidcontact) > 0) {
					$usecontact = true;
					$result = $object->fetch_contact($arrayidcontact[0]);
				}

				// Recipient name
				// On peut utiliser le nom de la societe du contact
				if ($usecontact && ($object->contact->fk_soc != $object->thirdparty->id && (!isset($conf->global->MAIN_USE_COMPANY_NAME_OF_CONTACT) || !empty($conf->global->MAIN_USE_COMPANY_NAME_OF_CONTACT)))) {					$thirdparty = $object->contact;
				} else {
					$thirdparty = $object->thirdparty;
				}

				$carac_client_name = pdfBuildThirdpartyName($thirdparty, $outputlangs);

				// Recipient address
				$mode = 'target';
				$carac_client = pdf_element_build_address($outputlangs, $this->emetteur, $thirdparty, $object->contact, $usecontact, $mode, $object, false);

				// Show recipient
				$widthrecboxrecipient = $tab_width - $conf->global->ULTIMATE_WIDTH_RECBOX - 2;
				$posy = $logo_height + $this->marge_haute + 4;
				$posx = $this->page_largeur - $this->marge_droite - $widthrecboxrecipient;
				if (!empty($conf->global->ULTIMATE_INVERT_SENDER_RECIPIENT) && ($conf->global->ULTIMATE_INVERT_SENDER_RECIPIENT != "no")) $posx = $this->marge_gauche;

				// Show recipient frame
				$pdf->SetTextColorArray($textcolor);
				$pdf->SetFont('', '', $default_font_size - 2);

				// Show billing address
				$pdf->SetAlpha($opacity);
				$pdf->RoundedRect($posx, $posy, $widthrecboxrecipient, $hautcadre, $roundradius, '1111', $receiptstyle, $this->border_style, $bgcolor);
				$pdf->SetAlpha(1);
				$pdf->SetXY($posx, $posy - 4);
				$pdf->MultiCell($widthrecboxrecipient, 4, $outputlangs->transnoentities("BillAddress"), 0, 'R');

				$posy = $pdf->getY();

				// Show recipient name
				$pdf->SetXY($posx + 2, $posy + 3);
				$pdf->SetFont('', 'B', $default_font_size);
				$pdf->MultiCell($widthrecboxrecipient - 5, 4, $carac_client_name, 0, $ltrdirection);

				$posy = $pdf->getY();

				// Show recipient information
				$pdf->SetFont('', '', $default_font_size - 1);
				$pdf->SetXY($posx + 2, $posy);
				$pdf->writeHTMLCell($widthrecboxrecipient - 5, 4, $posx + 2, $posy, $carac_client, 0, 2, 0, true, $ltrdirection, true);
			} 
			// If SERVICE contact defined, we use it
			elseif ($object->getIdContact('external', 'SERVICE')) {
				$usecontact = false;
				$arrayidcontact = $object->getIdContact('external', 'SERVICE');
				if (count($arrayidcontact) > 0) {
					$usecontact = true;
					$result = $object->fetch_contact($arrayidcontact[0]);
				}

				// Recipient name
				// On peut utiliser le nom de la societe du contact
				if ($usecontact && ($object->contact->fk_soc != $object->thirdparty->id && (!isset($conf->global->MAIN_USE_COMPANY_NAME_OF_CONTACT) || !empty($conf->global->MAIN_USE_COMPANY_NAME_OF_CONTACT)))) {
					$thirdparty = $object->contact;
				} else {
					$thirdparty = $object->thirdparty;
				}

				$carac_client_name = pdfBuildThirdpartyName($thirdparty, $outputlangs);

				$mode = 'target';
				$carac_client = pdf_element_build_address($outputlangs, $this->emetteur, $object->thirdparty, ($usecontact ? $object->contact : ''), $usecontact, $mode, $object);

				// Show recipient
				$widthrecboxrecipient = $tab_width - $conf->global->ULTIMATE_WIDTH_RECBOX - 2;
				$posy = $logo_height + $this->marge_haute + 4;
				$posx = $this->page_largeur - $this->marge_droite - $widthrecboxrecipient;
				if (!empty($conf->global->ULTIMATE_INVERT_SENDER_RECIPIENT) && ($conf->global->ULTIMATE_INVERT_SENDER_RECIPIENT != "no")) $posx = $this->marge_gauche;

				// Show recipient frame
				$pdf->SetTextColorArray($textcolor);
				$pdf->SetFont('', '', $default_font_size - 2);

				// Show facture_external_SERVICE address	
				$pdf->SetAlpha($opacity);
				$pdf->RoundedRect($posx, $posy, $widthrecboxrecipient, $hautcadre, $roundradius, '1111', $receiptstyle, $this->border_style, $bgcolor);
				$pdf->SetAlpha(1);
				$pdf->SetXY($posx, $posy - 4);
				$pdf->MultiCell($widthrecboxrecipient, 4, $outputlangs->transnoentities("TypeContact_facture_external_SERVICE"), 0, 'R');

				// Show recipient name
				$pdf->SetXY($posx + 2, $posy + 3);
				$pdf->SetFont('', 'B', $default_font_size);
				$pdf->MultiCell($widthrecboxrecipient - 5, 4, $carac_client_name, 0, $ltrdirection);

				$posy = $pdf->getY();

				// Show recipient information
				$pdf->SetFont('', '', $default_font_size - 1);
				$pdf->SetXY($posx + 2, $posy);
				$pdf->writeHTMLCell($widthrecboxrecipient - 5, 4, $posx + 2, $posy, $carac_client, 0, 2, 0, true, $ltrdirection, true);
			} else {
				$thirdparty = $object->thirdparty;
				// Recipient name
				$carac_client_name = pdfBuildThirdpartyName($thirdparty, $outputlangs);
				// Recipient address
				$mode = 'target';
				$carac_client = pdf_element_build_address($outputlangs, $this->emetteur, $object->thirdparty, '', 0, $mode, $object);

				// Show recipient
				$widthrecboxrecipient = $tab_width - $conf->global->ULTIMATE_WIDTH_RECBOX - 2;
				$posy = $logo_height + $this->marge_haute + 4;
				$posx = $this->page_largeur - $this->marge_droite - $widthrecboxrecipient;
				if (!empty($conf->global->ULTIMATE_INVERT_SENDER_RECIPIENT) && ($conf->global->ULTIMATE_INVERT_SENDER_RECIPIENT != "no")) $posx = $this->marge_gauche;

				// Show recipient frame
				$pdf->SetTextColorArray($textcolor);
				$pdf->SetFont('', '', $default_font_size - 2);

				// Show recipient address
				$pdf->SetXY($posx, $posy - 4);
				$pdf->SetAlpha($opacity);
				$pdf->RoundedRect($posx, $posy, $widthrecboxrecipient, $hautcadre, $roundradius, '1111', $receiptstyle, $this->border_style, $bgcolor);
				$pdf->SetAlpha(1);

				// Show recipient name
				$pdf->SetXY($posx + 2, $posy + 3);
				$pdf->SetFont('', 'B', $default_font_size);
				$pdf->MultiCell($widthrecboxrecipient - 5, 4, $carac_client_name, 0, $ltrdirection);

				$posy = $pdf->getY();

				// Show recipient information
				$pdf->SetFont('', '', $default_font_size - 1);
				$pdf->SetXY($posx + 2, $posy);
				$pdf->writeHTMLCell($widthrecboxrecipient - 5, 4, $posx + 2, $posy, $carac_client, 0, 2, 0, true, $ltrdirection, true);
			}

			// Other informations
			$pdf->SetFillColor(255, 255, 255);

			// Date facturation
			$width = $tab_width / 5 - 1.5;
			$RoundedRectHeight = $this->marge_haute + $logo_height + $hautcadre + 6;
			$pdf->SetAlpha($opacity);
			$pdf->RoundedRect($this->marge_gauche, $RoundedRectHeight, $width, 6, $roundradius, '1001', 'FD', $this->border_style, $bgcolor);
			$pdf->SetAlpha(1);
			$pdf->SetFont('', 'B', $default_font_size - 2);
			$pdf->SetXY($this->marge_gauche, $RoundedRectHeight);
			$pdf->SetTextColorArray($textcolor);
			$pdf->MultiCell($width, 5, $outputlangs->transnoentities("DateInvoice"), 0, 'C', false);

			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->SetXY($this->marge_gauche, $RoundedRectHeight + 6);
			$pdf->SetTextColorArray($textcolor);
			$pdf->MultiCell($width, 6, dol_print_date($object->date, "day", false, $outputlangs, true), 0, 'C');

			// DateEcheance
			$pdf->SetAlpha($opacity);
			$pdf->RoundedRect($this->marge_gauche + $width + 2, $RoundedRectHeight, $width, 6, $roundradius, '1001', 'FD', $this->border_style, $bgcolor);
			$pdf->SetAlpha(1);
			$pdf->SetFont('', 'B', $default_font_size - 2);
			$pdf->SetXY($this->marge_gauche + $width + 2, $RoundedRectHeight);
			$pdf->SetTextColorArray($textcolor);
			$pdf->MultiCell($width, 5, $outputlangs->transnoentities("DateDue"), 0, 'C', false);

			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->SetXY($this->marge_gauche + $width + 2, $RoundedRectHeight + 6);
			$pdf->SetTextColorArray($textcolor);
			$pdf->SetFillColor(255, 255, 255);
			$pdf->MultiCell($width, 6, dol_print_date($object->date_lim_reglement, "day", false, $outputlangs, true), 0, 'C');

			// Customer ref
			$pdf->SetAlpha($opacity);
			$pdf->RoundedRect($this->marge_gauche + $width * 2 + 4, $RoundedRectHeight, $width, 6, $roundradius, '1001', 'FD', $this->border_style, $bgcolor);
			$pdf->SetAlpha(1);
			$pdf->SetFont('', 'B', $default_font_size - 2);
			$pdf->SetXY($this->marge_gauche + $width * 2 + 4, $RoundedRectHeight);
			$pdf->SetTextColorArray($textcolor);
			$pdf->MultiCell($width + 2, 5, $outputlangs->transnoentities("RefCustomer"), 0, 'C', false);

			if ($object->ref_client) {
				$pdf->SetFont('', '', $default_font_size - 2);
				$pdf->SetXY($this->marge_gauche + $width * 2 + 4, $RoundedRectHeight + 6);
				$pdf->SetTextColorArray($textcolor);
				$pdf->MultiCell($width, 6, $object->ref_client, '0', 'C');
			} else {
				$pdf->SetFont('', '', $default_font_size - 2);
				$pdf->SetXY($this->marge_gauche + $width * 2 + 4, $RoundedRectHeight + 6);
				$pdf->SetTextColorArray($textcolor);
				$pdf->MultiCell($width, 6, '', '0', 'C');
			}

			// Customer code
			$pdf->SetAlpha($opacity);
			$pdf->RoundedRect($this->marge_gauche + $width * 3 + 6, $RoundedRectHeight, $width, 6, $roundradius, '1001', 'FD', $this->border_style, $bgcolor);
			$pdf->SetAlpha(1);
			$pdf->SetFont('', 'B', $default_font_size - 2);
			$pdf->SetXY($this->marge_gauche + $width * 3 + 6, $RoundedRectHeight);
			$pdf->SetTextColorArray($textcolor);
			$pdf->MultiCell($width, 5, $outputlangs->transnoentities("CustomerCode"), 0, 'C', false);

			if ($object->thirdparty->code_client) {
				$pdf->SetFont('', '', $default_font_size - 2);
				$pdf->SetXY($this->marge_gauche + $width * 3 + 6, $RoundedRectHeight + 6);
				$pdf->SetTextColorArray($textcolor);
				$pdf->MultiCell($width, 6, $outputlangs->transnoentities($object->thirdparty->code_client), '0', 'C');
			} else {
				$pdf->SetFont('', '', $default_font_size - 2);
				$pdf->SetXY($this->marge_gauche + $width * 3 + 6, $RoundedRectHeight + 6);
				$pdf->SetTextColorArray($textcolor);
				$pdf->SetFillColor(255, 255, 255);
				$pdf->MultiCell($width, 6, '', '0', 'C');
			}

			// VAT intra 
			$pdf->SetAlpha($opacity);
			$pdf->RoundedRect($this->marge_gauche + $width * 4 + 8, $RoundedRectHeight, $width, 6, $roundradius, '1001', 'FD', $this->border_style, $bgcolor);
			$pdf->SetAlpha(1);
			$pdf->SetFont('', 'B', $default_font_size - 2);
			$pdf->SetXY($this->marge_gauche + $width * 4 + 8, $RoundedRectHeight);
			$pdf->SetTextColorArray($textcolor);
			//$text='<div style="line-height:90%;">'.$outputlangs->transnoentities("VATIntra").'</div>';
			$pdf->writeHTMLCell($width, 5, $this->marge_gauche + $width * 4 + 8, $RoundedRectHeight, $outputlangs->transnoentities("VATNumber"), 0, 0, false, true, 'C', true);
			if ($usecontact && ($object->contact->socid != $object->thirdparty->id && (!isset($conf->global->MAIN_USE_COMPANY_NAME_OF_CONTACT) || !empty($conf->global->MAIN_USE_COMPANY_NAME_OF_CONTACT)))) {
				$thirdparty = $object->contact;
				$tvaintra = array();
				$sql = "SELECT tva_intra FROM ".MAIN_DB_PREFIX."societe WHERE rowid =" .$object->contact->socid;
				$resql = $db->query($sql);
				if ($resql) {
					$tva_intra = $db->num_rows($resql);
					$i = 0;
					while ($i < $tva_intra) {
						$i++;
						$row = $db->fetch_row($resql);
						$tvaintra[$i] = $row[0];
					}
				}
				$pdf->SetFont('', '', $default_font_size - 2);
				$pdf->SetXY($this->marge_gauche + $width * 4 + 8, $RoundedRectHeight + 6);
				$pdf->SetTextColorArray($textcolor);
				$pdf->MultiCell($width, 6, $tvaintra[1], '0', 'C');
			} else {
				$thirdparty = $object->thirdparty;
				$pdf->SetFont('', '', $default_font_size - 2);
				$pdf->SetXY($this->marge_gauche + $width * 4 + 8, $RoundedRectHeight + 6);
				$pdf->SetTextColorArray($textcolor);
				$pdf->SetFillColor(255, 255, 255);
				$pdf->MultiCell($width, 6, $object->thirdparty->tva_intra, '0', 'C');
			}
		}

		$pdf->SetTextColorArray($textcolor);
	}

	/**
	 *   	Show footer of page. Need this->emetteur object
	 *
	 *   	@param	PDF			&$pdf     			PDF
	 * 		@param	Object		$object				Object to show
	 *      @param	Translate	$outputlangs		Object lang for output
	 *      @param	int			$hidefreetext		1=Hide free text
	 *      @return	int								Return height of bottom margin including footer text
	 */
	function _pagefoot(&$pdf, $object, $outputlangs, $hidefreetext = 0)
	{
		global $conf;
		$showdetails = $conf->global->MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS;
		return pdf_ultimatepagefoot($pdf, $outputlangs, 'INVOICE_FREE_TEXT', $this->emetteur, $this->marge_basse, $this->marge_gauche, $this->page_hauteur, $object, $showdetails, $hidefreetext, $this->footertextcolor);
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
		$default_font_size = pdf_getPDFFontSize($outputlangs) - 1;
		if ($object->multicurrency_code == 'SAR' || $object->multicurrency_code == 'IRR' || $object->multicurrency_code == 'OMR' || $object->multicurrency_code == 'QAR' || $object->multicurrency_code == 'YER') {
			$pdf->SetFont('MarkaziText', '', $default_font_size + 2);
		} elseif ($object->multicurrency_code == 'RUB' || $object->multicurrency_code == 'PLN' || $object->multicurrency_code == 'BYR') {
			$pdf->SetFont('DejaVuSans', '', $default_font_size);
		} else {
			$pdf->SetFont($conf->global->MAIN_PDF_FORCE_FONT, '', $default_font_size);
		}
	}

	function _pagelcr(&$pdf, $object, $posy, $outputlangs, $hidefreetext=0)
	{
		global $conf, $langs;

		if (!is_object($outputlangs)) $outputlangs = $langs;
		$outputlangs->load("ultimatepdf@ultimatepdf");

		$currency_code = $langs->getCurrencySymbol($conf->currency);

		$pdf->SetDrawColor(128, 128, 128);
		$style2 = array('width' => 0.3, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(162, 162, 162));
		$curx = $this->marge_gauche;
		$cury = $posy + 30;
		$pdf->SetFont('zapfdingbats', '', 20);
		$pdf->SetXY(40, $cury - 5.7);
		$pdf->write(3, "!");
		$pdf->SetFont("Helvetica", '', 7);
		$pdf->Line(0, $cury, 210, $cury);
		$cury += 3;
		$pdf->SetXY(90, $cury);
		$pdf->Cell(100, 3, $outputlangs->transnoentities('DocLCR1'), 0, 1, 'L', 0);
		$cury += 3;
		$pdf->SetXY(90, $cury);
		$pdf->Cell(100, 3, $outputlangs->transnoentities('DocLCR2'), 0, 1, 'L', 0);
		$cury += 3;
		$pdf->SetXY(90, $cury);
		$pdf->Cell(100, 3, $outputlangs->transnoentities('DocLCR3'), 0, 1, 'L', 0);
		$cury += 3;
		$pdf->SetXY(90, $cury);
		$pdf->Cell(100, 3, $outputlangs->transnoentities('DocLCR4'), 0, 0, 'L', 0);

		// Sender properties
		$pdf->SetFont('helvetica', 'B', 8);
		$pdf->SetXY(130, $cury - 5);
		$carac_emetteur = $outputlangs->convToOutputCharset($this->emetteur->name);
		$carac_emetteur .= ($carac_emetteur ? "\n" : '') . $outputlangs->convToOutputCharset($this->emetteur->address);
		$carac_emetteur .= ($carac_emetteur ? "\n" : '') . $outputlangs->convToOutputCharset($this->emetteur->zip) . ' ' . $outputlangs->convToOutputCharset($this->emetteur->town);
		$carac_emetteur .= "\n";
		$pdf->MultiCell(50, 4, $carac_emetteur, 0, 'C');

		//Affichage code monnaie
		$pdf->SetXY(180, $cury);
		$pdf->SetFont('helvetica', '', 7);
		$pdf->Cell(18, 0, $outputlangs->transnoentities('DocLCR5'), 0, 1, 'C');
		$pdf->SetXY(180, $cury + 2.5);
		$pdf->SetFont('helvetica', 'B', 14);
		$pdf->Cell(18, 0, $currency_code, 0, 0, 'C');

		//Affichage lieu / date
		$cury += 5;
		$pdf->SetXY(5, $cury);
		$pdf->SetFont('helvetica', '', 8);
		$pdf->Cell(2, 0, "A", 0, 1, 'C');
		$pdf->SetXY(20, $cury);
		$pdf->SetFont('helvetica', 'B', 8);
		$pdf->Cell(15, 0, $outputlangs->convToOutputCharset($this->emetteur->town), 0, 1, 'C');
		$pdf->SetXY(45, $cury);
		$pdf->SetFont('helvetica', '', 8);
		$pdf->Cell(2, 0, ", le", 0, 1, 'C');

		// Row
		$curx = 43;
		$largeur_cadre = 5;
		$pdf->Line($curx + $largeur_cadre, $cury, $curx + $largeur_cadre + 5, $cury, $style2);
		$pdf->Line($curx + $largeur_cadre + 5, $cury, $curx + $largeur_cadre + 5, $cury + 2, $style2);
		$pdf->Line($curx + $largeur_cadre + 4, $cury + 2, $curx + $largeur_cadre + 6, $cury + 2, $style2);
		$pdf->Line($curx + $largeur_cadre + 4, $cury + 2, $curx + $largeur_cadre + 5, $cury + 3, $style2);
		$pdf->Line($curx + $largeur_cadre + 6, $cury + 2, $curx + $largeur_cadre + 5, $cury + 3, $style2);

		//Affichage de toute la ligne qui commence par "montant pour controle" ...
		$curx = $this->marge_gauche;
		$cury += 5;
		$hauteur_cadre = 6;
		$largeur_cadre = 27;
		$pdf->SetXY($curx, $cury - 1);
		$pdf->SetFont('helvetica', '', 7);
		$pdf->Cell($largeur_cadre, 0, $outputlangs->transnoentities('DocLCR6'), 0, 0, 'C');
		$pdf->Line($curx, $cury, $curx, $cury + $hauteur_cadre);
		$pdf->Line($curx, $cury + $hauteur_cadre, $curx + $largeur_cadre, $cury + $hauteur_cadre);
		$pdf->Line($curx + $largeur_cadre, $cury, $curx + $largeur_cadre, $cury + $hauteur_cadre);
		$pdf->SetXY($curx, $cury + 2.5);
		$pdf->SetFont('helvetica', 'B', 8);
		$pdf->Cell($largeur_cadre, 0, price($object->total_ttc), 0, 0, 'C');

		$curx = $curx + $largeur_cadre + 5;
		$hauteur_cadre = 6;
		$largeur_cadre = 25;
		$pdf->SetXY($curx, $cury - 1);
		$pdf->SetFont('helvetica', '', 7);
		$pdf->Cell($largeur_cadre, 0, $outputlangs->transnoentities('DocLCR7'), 0, 0, 'C');
		$pdf->Line($curx, $cury, $curx, $cury + $hauteur_cadre);
		$pdf->Line($curx, $cury + $hauteur_cadre, $curx + $largeur_cadre, $cury + $hauteur_cadre);
		$pdf->Line($curx + $largeur_cadre, $cury, $curx + $largeur_cadre, $cury + $hauteur_cadre);
		$pdf->SetXY($curx, $cury + 2.5);
		$pdf->SetFont('helvetica', 'B', 8);
		$pdf->Cell($largeur_cadre, 0, dol_print_date($object->date, "day", false, $outputlangs), 0, 0, 'C');

		$curx = $curx + $largeur_cadre + 5;
		$hauteur_cadre = 6;
		$largeur_cadre = 25;
		$pdf->SetXY($curx, $cury - 1);
		$pdf->SetFont('helvetica', '', 7);
		$pdf->Cell($largeur_cadre, 0, $outputlangs->transnoentities('DocLCR8'), 0, 0, 'C');
		$pdf->Line($curx, $cury, $curx, $cury + $hauteur_cadre);
		$pdf->Line($curx, $cury + $hauteur_cadre, $curx + $largeur_cadre, $cury + $hauteur_cadre);
		$pdf->Line($curx + $largeur_cadre, $cury, $curx + $largeur_cadre, $cury + $hauteur_cadre);
		$pdf->SetXY($curx, $cury + 2.5);
		$pdf->SetFont('helvetica', 'B', 8);
		$pdf->Cell($largeur_cadre, 0, dol_print_date($object->date_lim_reglement, "day"), 0, 0, 'C');

		$curx = $curx + $largeur_cadre + 5;
		$hauteur_cadre = 6;
		$largeur_cadre = 75;
		$pdf->SetXY($curx, $cury - 1);
		$pdf->SetFont('helvetica', '', 7);
		$pdf->Cell($largeur_cadre, 0, $outputlangs->transnoentities('DocLCR9'), 0, 0, 'C');

		$largeurportioncadre = 30;
		$pdf->Line($curx, $cury, $curx, $cury + $hauteur_cadre);
		$pdf->Line($curx, $cury + $hauteur_cadre, $curx + $largeurportioncadre, $cury + $hauteur_cadre);
		$curx += $largeurportioncadre;
		$pdf->Line($curx, $cury + 2, $curx, $cury + $hauteur_cadre);

		$curx += 10;
		$largeurportioncadre = 6;
		$pdf->Line($curx, $cury + 2, $curx, $cury + $hauteur_cadre);
		$pdf->Line($curx, $cury + $hauteur_cadre, $curx + $largeurportioncadre, $cury + $hauteur_cadre);
		$curx += $largeurportioncadre;
		$pdf->Line($curx, $cury + 2, $curx, $cury + $hauteur_cadre);

		$curx += 3;
		$largeurportioncadre = 6;
		$pdf->Line($curx, $cury + 2, $curx, $cury + $hauteur_cadre);
		$pdf->Line($curx, $cury + $hauteur_cadre, $curx + $largeurportioncadre, $cury + $hauteur_cadre);
		$curx += $largeurportioncadre;
		$pdf->Line($curx, $cury + 2, $curx, $cury + $hauteur_cadre);

		$curx += 3;
		$largeurportioncadre = 12;
		$pdf->Line($curx, $cury + 2, $curx, $cury + $hauteur_cadre);
		$pdf->Line($curx, $cury + $hauteur_cadre, $curx + $largeurportioncadre, $cury + $hauteur_cadre);
		$curx += $largeurportioncadre;
		$pdf->Line($curx, $cury, $curx, $cury + $hauteur_cadre);

		$curx += 3;
		$hauteur_cadre = 6;
		$largeur_cadre = 30;
		$pdf->SetXY($curx, $cury - 1);
		$pdf->SetFont('helvetica', '', 7);
		$pdf->Cell($largeur_cadre, 0, $outputlangs->transnoentities('DocLCR10'), 0, 0, 'C');
		$pdf->Line($curx, $cury, $curx, $cury + $hauteur_cadre);
		$pdf->Line($curx, $cury + $hauteur_cadre, $curx + $largeur_cadre, $cury + $hauteur_cadre);
		$pdf->Line($curx + $largeur_cadre, $cury, $curx + $largeur_cadre, $cury + $hauteur_cadre);
		$pdf->SetXY($curx, $cury + 2.5);
		$pdf->SetFont('helvetica', 'B', 8);
		$pdf->Cell($largeur_cadre, 0, price($object->total_ttc), 0, 0, 'C');

		$cury = $cury + $hauteur_cadre + 3;
		$curx = 20;
		$hauteur_cadre = 4;
		$largeur_cadre = 70;
		$pdf->Line($curx, $cury, $curx, $cury + $hauteur_cadre);
		$pdf->Line($curx, $cury, $curx + $largeur_cadre / 5, $cury);
		$pdf->Line($curx, $cury + $hauteur_cadre, $curx + $largeur_cadre / 5, $cury + $hauteur_cadre);

		$pdf->Line($curx + $largeur_cadre, $cury, $curx + $largeur_cadre, $cury + $hauteur_cadre);
		$pdf->Line($curx + $largeur_cadre, $cury, $curx + $largeur_cadre * 4 / 5, $cury);
		$pdf->Line($curx + $largeur_cadre, $cury + $hauteur_cadre, $curx + $largeur_cadre * 4 / 5, $cury + $hauteur_cadre);
		$pdf->SetXY($curx, $cury);
		$pdf->SetFont('helvetica', 'B', 8);
		$pdf->Cell($largeur_cadre, 1, $outputlangs->convToOutputCharset($object->ref), 0, 0, 'C');

		$curx = $curx + $largeur_cadre + 15;
		$largeur_cadre = 50;
		$pdf->Line($curx, $cury, $curx, $cury + $hauteur_cadre);
		$pdf->Line($curx, $cury, $curx + $largeur_cadre / 5, $cury);
		$pdf->Line($curx, $cury + $hauteur_cadre, $curx + $largeur_cadre / 5, $cury + $hauteur_cadre);

		$pdf->Line($curx + $largeur_cadre, $cury, $curx + $largeur_cadre, $cury + $hauteur_cadre);
		$pdf->Line($curx + $largeur_cadre, $cury, $curx + $largeur_cadre * 4 / 5, $cury);
		$pdf->Line($curx + $largeur_cadre, $cury + $hauteur_cadre, $curx + $largeur_cadre * 4 / 5, $cury + $hauteur_cadre);
		$pdf->SetXY($curx, $cury + 2);
		$pdf->SetFont('helvetica', 'B', 8);
		//$pdf->Cell($largeur_cadre, 0, "R�f ",0,0,C);

		$curx = $curx + $largeur_cadre + 10;
		$largeur_cadre = 30;
		$pdf->Line($curx, $cury, $curx, $cury + $hauteur_cadre);
		$pdf->Line($curx, $cury, $curx + $largeur_cadre / 5, $cury);
		$pdf->Line($curx, $cury + $hauteur_cadre, $curx + $largeur_cadre / 5, $cury + $hauteur_cadre);

		$pdf->Line($curx + $largeur_cadre, $cury, $curx + $largeur_cadre, $cury + $hauteur_cadre);
		$pdf->Line($curx + $largeur_cadre, $cury, $curx + $largeur_cadre * 4 / 5, $cury);
		$pdf->Line($curx + $largeur_cadre, $cury + $hauteur_cadre, $curx + $largeur_cadre * 4 / 5, $cury + $hauteur_cadre);
		$pdf->SetXY($curx, $cury + 2);
		$pdf->SetFont('helvetica', 'B', 8);

		// RIB thirdparty
		$cury = $cury + $hauteur_cadre + 3;
		$largeur_cadre = 70;
		$hauteur_cadre = 6;
		$sql = "SELECT rib.fk_soc, rib.domiciliation, rib.code_banque, rib.code_guichet, rib.number, rib.cle_rib";
		$sql .= " FROM " . MAIN_DB_PREFIX . "societe_rib as rib";
		$sql .= " WHERE rib.fk_soc = " . $object->thirdparty->id;
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i <= $num) {
				$cpt = $this->db->fetch_object($resql);

				$curx = $this->marge_gauche;
				$pdf->Line($curx, $cury, $curx + $largeur_cadre, $cury);
				$pdf->Line($curx, $cury, $curx, $cury + $hauteur_cadre);
				$pdf->Line($curx + 22, $cury, $curx + 22, $cury + $hauteur_cadre - 2);
				$pdf->Line($curx + 35, $cury, $curx + 35, $cury + $hauteur_cadre - 2);
				$pdf->Line($curx + 60, $cury, $curx + 60, $cury + $hauteur_cadre - 2);
				$pdf->Line($curx + 70, $cury, $curx + 70, $cury + $hauteur_cadre);
				$pdf->SetXY($curx + 5, $cury + $hauteur_cadre - 5);
				$pdf->SetFont('helvetica', 'B', 8);
				if ($cpt->code_banque && $cpt->code_guichet && $cpt->number && $cpt->cle_rib)
					$pdf->Cell($largeur_cadre, 1, $cpt->code_banque . "             " . $cpt->code_guichet . "         " . $cpt->number . "        " . $cpt->cle_rib, 0, 0, 'L');
				$pdf->SetXY($curx, $cury + $hauteur_cadre - 1);
				$pdf->SetFont('helvetica', '', 6);
				$pdf->Cell($largeur_cadre, 1, $outputlangs->transnoentities('DocLCR11') . '    ' .    $outputlangs->transnoentities('DocLCR12') . '        ' .          $outputlangs->transnoentities('DocLCR13') . '            ' .            $outputlangs->transnoentities('DocLCR14'), 0, 0, 'L');
				$curx = 150;
				$largeur_cadre = 55;
				$pdf->SetXY($curx, $cury - 1);
				$pdf->SetFont('helvetica', '', 6);
				$pdf->Cell($largeur_cadre, 1, $outputlangs->transnoentities('DocLCR15'), 0, 0, 'C');
				$pdf->SetXY($curx, $cury + 2);
				$pdf->SetFont('helvetica', 'B', 8);
				if ($cpt->domiciliation)
					$pdf->Cell($largeur_cadre, 5, $outputlangs->convToOutputCharset($cpt->domiciliation), 1, 0, 'C');
				$i++;
			}
		}
		//
		$cury = $cury + $hauteur_cadre + 3;
		$curx = $this->marge_gauche;
		$largeur_cadre = 20;
		$pdf->SetXY($curx, $cury);
		$pdf->SetFont('helvetica', '', 6);
		$pdf->Cell($largeur_cadre, 1, $outputlangs->transnoentities('DocLCR16'), 0, 0, 'L');
		// Row
		$pdf->Line($curx + $largeur_cadre, $cury, $curx + $largeur_cadre + 5, $cury);
		$pdf->Line($curx + $largeur_cadre + 5, $cury, $curx + $largeur_cadre + 5, $cury + 2);
		$pdf->Line($curx + $largeur_cadre + 4, $cury + 2, $curx + $largeur_cadre + 6, $cury + 2);
		$pdf->Line($curx + $largeur_cadre + 4, $cury + 2, $curx + $largeur_cadre + 5, $cury + 3);
		$pdf->Line($curx + $largeur_cadre + 6, $cury + 2, $curx + $largeur_cadre + 5, $cury + 3);

		//Coordonnees du tire
		$curx += 50;
		$largeur_cadre = 20;
		$hauteur_cadre = 4;
		$pdf->SetXY($curx, $cury);
		$pdf->SetFont('helvetica', '', 6);
		$pdf->MultiCell($largeur_cadre, $hauteur_cadre, "Nom \n et Adresse \n" . $outputlangs->transnoentities('DocLCR17'), 0, 'R');
		$pdf->SetXY($curx + $largeur_cadre + 2, $cury);
		$pdf->SetFont('helvetica', 'B', 8);
		$arrayidcontact = $object->getIdContact('external', 'BILLING');
		$carac_client = $outputlangs->convToOutputCharset($object->thirdparty->name);
		$carac_client .= "\n" . $outputlangs->convToOutputCharset($object->thirdparty->address);
		$carac_client .= "\n" . $outputlangs->convToOutputCharset($object->thirdparty->zip) . " " . $outputlangs->convToOutputCharset($object->thirdparty->town) . "\n";
		$pdf->MultiCell($largeur_cadre * 2.5, $hauteur_cadre, $carac_client, 1, 'C');
		//No Siren
		$pdf->SetXY($curx, $cury + 16);
		$pdf->SetFont('helvetica', '', 6);
		$pdf->MultiCell($largeur_cadre, 4, $outputlangs->transnoentities('DocLCR18'), 0, 'R');
		$pdf->SetXY($curx + $largeur_cadre + 2, $cury + 16);
		$pdf->SetFont('helvetica', 'B', 8);
		$pdf->MultiCell($largeur_cadre * 2.5, 4, $outputlangs->convToOutputCharset($object->thirdparty->idprof1), 1, 'C');
		//signature du tireur
		$pdf->SetXY($curx + $largeur_cadre * 5, $cury);
		$pdf->SetFont('helvetica', '', 6);
		$pdf->MultiCell($largeur_cadre * 2, 4, $outputlangs->transnoentities('DocLCR19'), 0, 'C');

		$pdf->Line(0, (int)$this->page_hauteur - (int)$this->marge_basse, (int)$this->page_largeur, (int)$this->page_hauteur - (int)$this->marge_basse);
		$pdf->SetXY($this->page_largeur - 65, $this->page_hauteur - $this->marge_basse - 3);
		$pdf->SetFont('helvetica', '', 6);
		$pdf->MultiCell(50, 4, $outputlangs->transnoentities('DocLCR20'), 0, 'R');
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
		if (!empty($conf->global->ULTIMATE_INVOICES_WITH_LINE_NUMBER)) {
			$this->cols['num']['status'] = true;
		}

		$rank = $rank + 10; // do not use negative rank
		$this->cols['ref'] = array(
			'rank' => $rank,
			'width' => (empty($conf->global->ULTIMATE_DOCUMENTS_WITH_REF_WIDTH) ? 16 : $conf->global->ULTIMATE_DOCUMENTS_WITH_REF_WIDTH), // in mm 
			'status' => false,
			'title' => array(
				'textkey' => 'RefShort', // use lang key is usefull in somme case with module
				'align' => 'L',
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
		if (!empty($conf->global->ULTIMATE_INVOICES_WITH_LINE_NUMBER) && $conf->global->ULTIMATE_PDF_BORDER_LEFT_STATUS == 'yes') {
			$this->cols['ref']['border-left'] = true;
		}

		$rank = $rank + 10; // do not use negative rank
		$this->cols['desc'] = array(
			'rank' => $rank,
			'width' => false, // only for desc
			'status' => true,
			'title' => array(
				'textkey' => 'Designation', // use lang key is usefull in somme case with module
				'align' => 'L',
				// 'textkey' => 'yourLangKey', // if there is no label, yourLangKey will be translated to replace label
				// 'label' => ' ', // the final label
				'padding' => array(0.5, 0.5, 0.5, 0.5), // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
			),
			'content' => array(
				'align' => 'L',
			),
			'border-left' => false, // remove left line separator
		);

		if (!empty($conf->global->ULTIMATE_INVOICES_WITH_LINE_NUMBER &&$conf->global->ULTIMATE_PDF_BORDER_LEFT_STATUS == 'yes') || ($conf->global->ULTIMATE_DOCUMENTS_WITH_REF == "yes" && $this->atleastoneref == true  &&$conf->global->ULTIMATE_PDF_BORDER_LEFT_STATUS == 'yes')) {
			$this->cols['desc']['border-left'] = true;
		}

		$rank = $rank + 10;
		$this->cols['photo'] = array(
			'rank' => $rank,
			'width' => (empty($conf->global->MAIN_DOCUMENTS_WITH_PICTURE_WIDTH) ? 20 : $conf->global->MAIN_DOCUMENTS_WITH_PICTURE_WIDTH), // in mm
			'status' => false,
			'title' => array(
				'textkey' => 'Picture',
				'label' => ' '
			),
			'content' => array(
				'padding' => array(0.5, 0.5, 0.5, 0.5), // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
			),
			'border-left' => false, // remove left line separator
		);

		if ($conf->global->ULTIMATE_GENERATE_INVOICES_WITH_PICTURE == 1) {
			$this->cols['photo']['status'] = true;
		}

		$rank = $rank + 10; //extrafields column
		$this->cols['xtrafields'] = array(
			'rank' => $rank,
			'status' => false, // set status to true to display
			'width' => 15, // in mm
			'title' => array(
				'textkey' => 'Xtrafields'
			),
			'content' => array(
				'align' => 'R'
			),
			'border-left' => true, // add left line separator
		);

		$rank = $rank + 10;
		$this->cols['vat'] = array(
			'rank' => $rank,
			'status' => false,
			'width' => (empty($conf->global->ULTIMATE_DOCUMENTS_WITH_TVA_WIDTH) ? 14 : $conf->global->ULTIMATE_DOCUMENTS_WITH_TVA_WIDTH), // in mm  
			'title' => array(
				'textkey' => 'VAT'
			),
			'content' => array(
				'align' => 'R',
			),
			'border-left' => false, // add left line separator
		);

		if (($conf->global->ULTIMATE_GENERATE_DOCUMENTS_WITHOUT_VAT == 0) && ($conf->global->ULTIMATE_SHOW_HIDE_VAT_COLUMN == 0) && ($object->total_tva != 0)) {
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

		if (!empty($conf->global->ULTIMATE_GENERATE_INVOICES_WITH_PRICEUHT)) {
			$this->cols['subprice']['status'] = true;
		}
		if ($conf->global->ULTIMATE_PDF_BORDER_LEFT_STATUS == 'yes') {
			$this->cols['subprice']['border-left'] = true;
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
				'align' => 'R'
			),
			'border-left' => false, // add left line separator
		);

		if ($this->atleastonediscount && !empty($conf->global->ULTIMATE_GENERATE_INVOICES_WITH_PUAFTER)) {
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
				'align' => 'R'
			),
			'border-left' => false, // add left line separator
		);

		if (!empty($conf->global->ULTIMATE_GENERATE_INVOICES_WITH_QTY)) {
			$this->cols['qty']['status'] = true;
		}
		if ($conf->global->ULTIMATE_PDF_BORDER_LEFT_STATUS == 'yes') {
			$this->cols['qty']['border-left'] = true;
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
				'align' => 'L'
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

		if (!empty($conf->global->ULTIMATE_GENERATE_INVOICES_WITH_WEIGHT_COLUMN)) {
			$this->cols['weight']['status'] = true;
		}
		if ($conf->global->ULTIMATE_PDF_BORDER_LEFT_STATUS == 'yes') {
			$this->cols['weight']['border-left'] = true;
		}

		$rank + 10;
		$this->cols['discount'] = array(
			'rank' => $rank,
			'width' => (empty($conf->global->ULTIMATE_DOCUMENTS_WITH_DISCOUNT_WIDTH) ? 13 : $conf->global->ULTIMATE_DOCUMENTS_WITH_DISCOUNT_WIDTH), // in mm
			'status' => false,
			'title' => array(
				'textkey' => 'ReductionShort'
			),
			'content' => array(
				'align' => 'R',
			),
			'border-left' => false, // add left line separator
		);

		if ($this->atleastonediscount && !empty($conf->global->ULTIMATE_GENERATE_INVOICES_WITH_DISCOUNT)) {
			$this->cols['discount']['status'] = true;
		}
		if ($conf->global->ULTIMATE_PDF_BORDER_LEFT_STATUS == 'yes') {
			$this->cols['discount']['border-left'] = true;
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

		if ($this->situationinvoice && !empty($conf->global->INVOICE_USE_SITUATION)) {
			$this->cols['progress']['status'] = true;
		}
		if ($conf->global->ULTIMATE_PDF_BORDER_LEFT_STATUS == 'yes') {
			$this->cols['progress']['border-left'] = true;
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
				'align' => 'R'
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
				'align' => 'R'
			),
			'border-left' => false, // add left line separator
		);

		if ($conf->global->ULTIMATE_SHOW_LINE_TTTC) {
			$this->cols['totalincltax']['status'] = true;
		}
		if ($conf->global->ULTIMATE_PDF_BORDER_LEFT_STATUS == 'yes') {
			$this->cols['totalincltax']['border-left'] = true;
		}

		// Add extrafields cols
		if (!empty($object->lines)) {
			$line = reset($object->lines);
			$this->defineColumnExtrafield($line, $outputlangs, $hidedetails);
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

	/**
	 *  Define Array Column Field for extrafields
	 *
	 *  @param	object			$object    		common object det
	 *  @param	Translate		$outputlangs    langs
	 *  @param	int			   $hidedetails		Do not show line details
	 *  @return	null
	 */
	public function defineColumnExtrafield($object, $outputlangs, $hidedetails = 0)
	{
		global $conf;

		if (!empty($hidedetails)) {
			return;
		}

		if (empty($object->table_element)) {
			return;
		}

		// Load extrafiels if not allready does
		if (empty($this->extrafieldsCache)) {
			$this->extrafieldsCache = new ExtraFields($this->db);
		}
		if (empty($this->extrafieldsCache->attributes[$object->table_element])) {
			$this->extrafieldsCache->fetch_name_optionals_label($object->table_element);
		}
		$extrafields = $this->extrafieldsCache;


		if (!empty($extrafields->attributes[$object->table_element]) && is_array($extrafields->attributes[$object->table_element]['label'])) {
			foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $label) {
				// Dont display separator yet even is set to be displayed (not compatible yet)
				//var_dump($extrafields->attributes[$object->table_element]['label'][$key]);exit;
				if ($extrafields->attributes[$object->table_element]['type'][$key] == 'separate') {
					continue;
				}

				// Enable extrafield ?
				$enabled = 0;
				if (!empty($extrafields->attributes[$object->table_element]['printable'][$key])) {
					$printable = intval($extrafields->attributes[$object->table_element]['printable'][$key]);
					if ($printable === 1 || $printable === 2) {
						$enabled = 1;
					}
					// Note : if $printable === 3 or 4 so, it's displayed after line description not in cols
				}

				if (!$enabled) {
					continue;
				} // don't wast resourses if we don't need them...

				// Load language if required
				if (!empty($extrafields->attributes[$object->table_element]['langfile'][$key])) {
					$outputlangs->load($extrafields->attributes[$object->table_element]['langfile'][$key]);
				}

				// TODO : add more extrafield customisation capacities for PDF like width, rank...

				// set column definition
				$def = array(
					'rank' => intval($extrafields->attributes[$object->table_element]['pos'][$key]),
					'width' => 25, // in mm
					'status' => boolval($enabled),
					'title' => array(
						'label' => $outputlangs->transnoentities($label)
					),
					'content' => array(
						'align' => 'C'
					),
					'border-left' => (!empty($conf->global->ULTIMATE_PDF_BORDER_LEFT_STATUS) && $conf->global->ULTIMATE_PDF_BORDER_LEFT_STATUS == 'yes') ? true : false, // add left line separator
				);

				$alignTypeRight = array('double', 'int', 'price');
				if (in_array($extrafields->attributes[$object->table_element]['type'][$key], $alignTypeRight)) {
					$def['content']['align'] = 'R';
				}

				$alignTypeLeft = array('text', 'html');
				if (in_array($extrafields->attributes[$object->table_element]['type'][$key], $alignTypeLeft)) {
					$def['content']['align'] = 'L';
				}


				// for extrafields we use rank of extrafield to place it on PDF
				$this->insertNewColumnDef("options_".$key, $def);
			}
		}
	}
}

?>