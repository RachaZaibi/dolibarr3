<?php
/* Copyright (C) 2004-2009 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2011 Regis Houssin         <regis.houssin@capnetworks.com>
 * Copyright (C) 2007      Franky Van Liedekerke <franky.van.liedekerke@telenet.be>
 * Copyright (C) 2010      Juanjo Menent         <jmenent@2byte.es>
 * Copyright (C) 2011-2022 Philippe Grand        <philippe.grand@atoo-net.com>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 *	\file       ultimatepdf/core/modules/supplier_order/doc/pdf_ultimate_supplierorder.modules.php
 *	\ingroup    fournisseur
 *	\brief      File of class to generate suppliers orders from ultimate_supplierorder model
 */

require_once DOL_DOCUMENT_ROOT . '/core/modules/supplier_order/modules_commandefournisseur.php';
require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.commande.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
dol_include_once("/ultimatepdf/lib/ultimatepdf.lib.php");
dol_include_once("/ultimatepdf/class/ultimateBarcode.trait.class.php");


/**
 *	Class to generate the supplier orders with the ultimate_supplierorder model
 */
class pdf_ultimate_supplierorder extends ModelePDFSuppliersOrders
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
	 * @var bool Barcode error message
	 */
	private $messageErrBarcodeSet;


	/**
	 *	Constructor
	 *
	 *  @param	DoliDB		$db      	Database handler
	 */
	public function __construct($db)
	{
		global $conf, $langs, $mysoc;

		// Translations
		$langs->loadLangs(array("main", "bills", "ultimatepdf@ultimatepdf"));

		$this->db = $db;
		$this->name = "ultimate_supplierorder";
		$this->description = $langs->trans('SuppliersCommandModel');
		$this->update_main_doc_field = 1;		// Save the name of generated file as the main doc when generating a doc with this template
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
			
		$this->style = array('width' => 0.2, 'cap' => 'butt', 'join' => 'miter', 'dash' => $dashdotted, 'color' => $bordercolor);

		// Get source company
		$this->emetteur = $mysoc;
		if (!$this->emetteur->country_code) $this->emetteur->country_code = substr($langs->defaultlang, -2);    // By default, if was not defined

		$this->tabTitleHeight = 8; // default height

		$this->tva = array();
		$this->localtax1 = array();
		$this->localtax2 = array();
		$this->atleastoneratenotnull = 0;
		$this->atleastonediscount = 0;
		$this->atleastoneref = 0;
		$this->atleastonephoto = false;
	}

    /**
     *  Function to build pdf onto disk
     *
     *  @param		Object	$object				Id of object to generate
     *  @param		Translate	$outputlangs	Lang output object
     *  @param		string	$srctemplatepath	Full path of source filename for generator using a template file
     *  @param		int		$hidedetails		Do not show line details
     *  @param		int		$hidedesc			Do not show desc
     *  @param		int		$hideref			Do not show ref
     *  @return     int             			1=OK, 0=KO
     */
	public function write_file($object, $outputlangs = '', $srctemplatepath = '', $hidedetails = 0, $hidedesc = 0, $hideref = 0)
	{
		global $user, $langs, $conf, $mysoc, $db, $hookmanager, $nblines;

		$textcolor =  (!empty($conf->global->ULTIMATE_TEXTCOLOR_COLOR)) ?
		html2rgb($conf->global->ULTIMATE_TEXTCOLOR_COLOR) : array(25, 25, 25);

		$bgcolor =  (!empty($conf->global->ULTIMATE_BGCOLOR_COLOR)) ?
		html2rgb($conf->global->ULTIMATE_BGCOLOR_COLOR) : array(170, 212, 255);

		$roundradius = (!empty($conf->global->ULTIMATE_SET_RADIUS)) ?
		$conf->global->ULTIMATE_SET_RADIUS : 1;

		$bordercolor =  (!empty($conf->global->ULTIMATE_BORDERCOLOR_COLOR)) ?
		html2rgb($conf->global->ULTIMATE_BORDERCOLOR_COLOR) : array(0, 63, 127);

		$dashdotted = (!empty($conf->global->ULTIMATE_DASH_DOTTED)) ?
		$conf->global->ULTIMATE_DASH_DOTTED : 0;

		$hidedesc = (!empty($conf->global->ULTIMATE_GENERATE_SUPPLIERORDERS_HIDE_PRODUCT_DESC)) ? 1 : 0;

		$this->border_style = array('width' => 0.2, 'cap' => 'butt', 'join' => 'miter', 'dash' => $dashdotted, 'color' => $bordercolor);

		if (!is_object($outputlangs)) $outputlangs = $langs;

		// Translations
		$outputlangs->loadLangs(array("main", "dict", "companies", "bills", "products", "orders",  "ultimatepdf@ultimatepdf"));

		global $outputlangsbis;
		$outputlangsbis = null;
		if (!empty($conf->global->PDF_USE_ALSO_LANGUAGE_CODE) && $outputlangs->defaultlang != $conf->global->PDF_USE_ALSO_LANGUAGE_CODE) {
			$outputlangsbis = new Translate('', $conf);
			$outputlangsbis->setDefaultLang($conf->global->PDF_USE_ALSO_LANGUAGE_CODE);
			$outputlangsbis->loadLangs(array("main", "orders", "companies", "bills", "dict", "products"));
		}

		$nblines = count($object->lines);

		$hidetop = 0;
		if (!empty($conf->global->MAIN_PDF_DISABLE_COL_HEAD_TITLE)) {
			$hidetop = $conf->global->MAIN_PDF_DISABLE_COL_HEAD_TITLE;
		}

		// Loop on each lines to detect if there is at least one image to show
		$realpatharray = array();
		if (!empty($conf->global->ULTIMATE_GENERATE_SUPPLIERORDER_WITH_PICTURE)) {

			for ($i = 0; $i < $nblines; $i++) {
				if (empty($object->lines[$i]->fk_product)) continue;

				$objphoto = new Product($this->db);
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
							$this->atleastonephoto = true;
						}
					}
				}
				if ($realpath && $arephoto) {
					$realpatharray[$i] = $realpath;
				}
			}
		}

		if ($conf->fournisseur->multidir_output[$conf->entity] . '/commande') {
			$object->fetch_thirdparty();

			$deja_regle = 0;
			$amount_credit_notes_included = 0;
			$amount_deposits_included = 0;

			// Definition de $dir et $file
			if ($object->specimen) {
				$dir = $conf->fournisseur->commande->multidir_output[$conf->entity];
				$file = $dir . "/SPECIMEN.pdf";
			} else {
				$objectref = dol_sanitizeFileName($object->ref);			
				if ($conf->mbisignature->enabled) {
					$resql = $db->query("SELECT * from " . MAIN_DB_PREFIX . "mbi_signature WHERE object_type = '" . $objectref . "' AND entity = " . $conf->entity);
					$obj = $db->fetch_object($resql);
					if ($obj->pathoffile !== 'document generated' && $obj->pathoffile) {
						$dir = $conf->fournisseur->commande->dir_output . "/" . $objectref;
						$file = $dir . "/" . $objectref . "_signature.pdf";
					} else {
						$objectrefsupplier = dol_sanitizeFileName($object->ref_supplier);
						$dir = $conf->fournisseur->commande->multidir_output[$conf->entity] . '/' . $objectref;
						$file = $dir . "/" . $objectref . ".pdf";
						if (!empty($conf->global->SUPPLIER_REF_IN_NAME)) $file = $dir . "/" . $objectref . ($objectrefsupplier ? "_" . $objectrefsupplier : "") . ".pdf";
					}
				} else {
					$objectrefsupplier = dol_sanitizeFileName($object->ref_supplier);
					$dir = $conf->fournisseur->commande->multidir_output[$conf->entity] . '/' . $objectref;
					$file = $dir . "/" . $objectref . ".pdf";
					if (!empty($conf->global->SUPPLIER_REF_IN_NAME)) $file = $dir . "/" . $objectref . ($objectrefsupplier ? "_" . $objectrefsupplier : "") . ".pdf";
				}
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
				$reshook = $hookmanager->executeHooks('afterPDFCreation', $parameters, $this, $action);    // Note that $action and $object may have been modified by some hooks

				// Create pdf instance
				$pdf = pdf_getInstance($this->format);
				$default_font_size = pdf_getPDFFontSize($outputlangs);	// Must be after pdf_getInstance

				$pdf->SetAutoPageBreak(1, 0);

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
				$pdf->SetSubject($outputlangs->transnoentities("Order"));
				$pdf->SetCreator("Dolibarr " . DOL_VERSION);
				$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
				$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref) . " " . $outputlangs->transnoentities("Order"));
				if (!empty($conf->global->MAIN_DISABLE_PDF_COMPRESSION)) $pdf->SetCompression(false);

				$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right

				// Positionne $this->atleastoneref si on a au moins une ref 
				for ($i = 0; $i < $nblines; $i++) {
					if ($object->lines[$i]->product_ref || $object->lines[$i]->ref_fourn) {
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

				// New page
				$pdf->AddPage();
				if (!empty($tplidx)) $pdf->useTemplate($tplidx);
				$pagenb++;

				if (!empty($conf->global->ULTIMATE_DISPLAY_SUPPLIERORDER_AGREEMENT_BLOCK)) {
					$heightforinfotot = 58;	// Height reserved to output the info and total part
				} else {
					$heightforinfotot = 20;
				}

				$heightforfreetext = (isset($conf->global->MAIN_PDF_FREETEXT_HEIGHT) ? $conf->global->MAIN_PDF_FREETEXT_HEIGHT : 5);	// Height reserved to output the free text on last page
				$heightforfooter = $this->marge_basse + (empty($conf->global->MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS) ? 12 : 22); // Height reserved to output the footer (value include bottom margin)

				//catch logo height
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
				if (($conf->global->ULTIMATE_PDF_SUPPLIERORDER_ADDALSOTARGETDETAILS == 1) || (!empty($conf->global->MAIN_INFO_SOCIETE_NOTE) && !empty($this->emetteur->note_private)) || (!empty($conf->global->MAIN_INFO_TVAINTRA) && !empty($this->emetteur->tva_intra))) {
					$hautcadre = 48;
				} else {
					$hautcadre = 40;
				}

				$this->_pagehead($pdf, $object, 1, $outputlangs, $titlekey = "SupplierOrder");

				$pdf->SetFont('', '', $default_font_size - 1);
				$pdf->MultiCell(0, 3, '');		// Set interline to 3
				$pdf->SetTextColorArray($textcolor);

				$tab_top = $this->marge_haute + $logo_height + $hautcadre + 14;

				$tab_top_newpage = (empty($conf->global->ULTIMATE_SUPPLIERORDERS_PDF_DONOTREPEAT_HEAD) ? $this->marge_haute + $logo_height + 20 : 10);

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
				
				if (!empty($conf->global->ULTIMATE_SALE_REP_SIGNATURE_IN_SUPPLIERORDER_NOTE)) {
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

				$tab_height = $this->page_hauteur - $tab_top_newpage - $heightforinfotot - $heightforfreetext - $heightforagreement - $heightforfooter;

				$pagenb = $pdf->getPage();
				if ($notetoshow && empty($conf->global->MAIN_PUBLIC_NOTE_IN_ADDRESS)) {
					$pageposbeforenote = $pagenb;
					if ($desc_incoterms) {
						$tab_top -= 6;
					} else {
						$tab_top = $pdf->GetY() + 4;
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
							if (empty($conf->global->ULTIMATE_SUPPLIERORDERS_PDF_DONOTREPEAT_HEAD)) $this->_pagehead($pdf, $object, 0, $outputlangs);
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
						$nexY = $pdf->GetY();

						if ($posyafter > ($this->page_hauteur - ($heightforfooter + $heightforfreetext + 20)))	// There is no space left for total+free text
						{
							$pdf->AddPage('', '', true);
							$pagenb++;
							$pageposafternote++;
							$pdf->setPage($pageposafternote);
							$pdf->setTopMargin($tab_top_newpage);
							// The only function to edit the bottom margin of current page to set it.
							$pdf->setPageOrientation('', 1, $heightforfooter + $heightforfreetext);
							//$posyafter = $tab_top_newpage;
						}

						// apply note frame to previus pages
						$i = $pageposbeforenote;
						while ($i < $pageposafternote) {
							$pdf->setPage($i);

							$pdf->SetDrawColor(128, 128, 128);
							// Draw note frame
							if ($i > $pageposbeforenote) {
								$height_note = $this->page_hauteur - ($tab_top_newpage + $heightforfooter);
								$pdf->RoundedRect($this->marge_gauche, $tab_top_newpage - 1, $tab_width, $height_note + 1 + $heightforsignature, $roundradius, '1111', 'S', $this->border_style, $bgcolor);
							} else {
								$height_note = $this->page_hauteur - ($tab_top + $heightforfooter);
								$pdf->RoundedRect($this->marge_gauche, $tab_top_newpage - 1, $tab_width, $height_note + 1 + $heightforsignature, $roundradius, '1111', 'S', $this->border_style, $bgcolor);
							}

							// Add footer
							$pdf->setPageOrientation('', 1, 0);	// The only function to edit the bottom margin of current page to set it.
							$this->_pagefoot($pdf, $object, $outputlangs, 1);

							$i++;
						}

						// apply note frame to last page
						$pdf->setPage($pageposafternote);
						if (!empty($tplidx)) $pdf->useTemplate($tplidx);
						if (empty($conf->global->ULTIMATE_SUPPLIERORDERS_PDF_DONOTREPEAT_HEAD)) $this->_pagehead($pdf, $object, 0, $outputlangs);
						$height_note = $posyafter - $tab_top_newpage;
						$pdf->RoundedRect($this->marge_gauche, $tab_top_newpage - 1, $tab_width, $height_note + 1 + $heightforsignature, $roundradius, '1111', 'S', $this->border_style, $bgcolor);
					} else // No pagebreak
					{
						$pdf->commitTransaction();
						$posyafter = $pdf->GetY();
						$height_note = max($posyafter - $tab_top, $heightforsignature);
						$pdf->RoundedRect($this->marge_gauche, $tab_top - 1, $tab_width, $height_note + 2, $roundradius, '1111', 'S', $this->border_style, $bgcolor);

						if ($posyafter > ($this->page_hauteur - ($heightforfooter + $heightforfreetext + 20))) {
							// not enough space, need to add page
							$pdf->AddPage('', '', true);
							$pagenb++;
							$pageposafternote++;
							$pdf->setPage($pageposafternote);
							if (!empty($tplidx)) $pdf->useTemplate($tplidx);
							if (empty($conf->global->ULTIMATE_SUPPLIERORDERS_PDF_DONOTREPEAT_HEAD)) $this->_pagehead($pdf, $object, 0, $outputlangs);

							$posyafter = $tab_top_newpage;
						}
					}
					if (is_readable($signature) && !empty($imgsignature)) {
						$notetoshow .= $pdf->Image($signature, $this->marge_gauche + 80, $tab_top + $height_note - $heightforsignature - 1, 0, $height_note);
						if (!empty($salerepobj->signature)) $notetoshow = dol_concatdesc($notetoshow, $salerepobj->signature);
					}

					$tab_height = $tab_height - max($height_note, $heightforsignature);
					$tab_top = $posyafter + 10;
				} else {
					//If not any note but an image of signature
					$height_note = 0;
					if (is_readable($signature) && !empty($imgsignature)) {
						$notetoshow .= $pdf->Image($signature, $this->marge_gauche + 80, $tab_top + $height_note, 0, $heightforsignature);
						if (!empty($salerepobj->signature)) $notetoshow .= dol_concatdesc($notetoshow, $salerepobj->signature);
					}
				}

				// Use new auto column system
				$this->prepareArrayColumnField($object, $outputlangs, $hidedetails, $hidedesc, $hideref);

				// Simulation de tableau pour connaitre la hauteur de la ligne de titre
				$pdf->startTransaction();
				$this->pdfTabTitles($pdf, $tab_top, $tab_height, $outputlangs, $hidetop);
				$pdf->rollbackTransaction(true);

				if (!$height_note && !$desc_incoterms) {
					$tab_top += 10;
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

					if ($this->getColumnStatus('photo')) {
						// We start with Photo of product line
						if (isset($imglinesize['width']) && isset($imglinesize['height']) && ($curY + $imglinesize['height']) > ($this->page_hauteur - $bMargin))	// If photo too high, we moved completely on new page
						{
							$pdf->AddPage('', '', true);
							if (!empty($tplidx)) {
								$pdf->useTemplate($tplidx);
							}
							$pdf->setPage($pageposbefore + 1);

							$curY = $tab_top_newpage;
							$showpricebeforepagebreak = 0;
						}

						$photo = false;

						if (isset($imglinesize['width']) && isset($imglinesize['height'])) {
							$curX = $this->getColumnContentXStart('photo') - 1;
							$pdf->Image($realpatharray[$i], $curX, $curY + 1, $imglinesize['width'], $imglinesize['height'], '', '', '', 2, 300, '', false, false, 0, false, false, true);	// Use 300 dpi
							// $pdf->Image does not increase value return by getY, so we save it manually
							$posYAfterImage = $curY + $imglinesize['height'];
							$photo = true;
						}
					}

					if (!empty($photo)) {
						$nexY = $posYAfterImage;
					}

					if ($this->getColumnStatus('vat') == true) {
						$vatorprice = $this->getColumnContentXStart('vat');
					} else {
						$xsubprice = $this->getColumnContentXStart('subprice');
						if ($xsubprice < 1) {
							$xsubprice = 0;
						}	//otherwise the value is 0.5 instead of 0									
						$xqty = $this->getColumnContentXStart('qty');
						$vatorprice = $xsubprice ? $xsubprice : $xqty;
					}

					// Description of product line
					$curX = $this->getColumnContentXStart('desc');
					$text_length = ($photo ? $this->getColumnContentXStart('photo') : $vatorprice);

					if ($this->getColumnStatus('desc')) {
						$pdf->startTransaction();
						if ($conf->global->ULTIMATE_DOCUMENTS_WITH_REF == "yes" && $this->atleastoneref == true) {
							$hideref = 1;
						} else {
							$hideref = 0;
						}

						$pageposbeforedesc = $pdf->getPage();
						$posYStartDescription = $curY;
						pdf_writelinedesc($pdf, $object, $i, $outputlangs, $text_length - $curX, 3, $curX, $curY + 0.5, $hideref, $hidedesc, 1);
						$posYAfterDescription = $pdf->GetY();
						$pageposafter = $pdf->getPage();

						if (!empty($product->barcode) && !empty($product->barcode_type_code) && $object->lines[$i]->product_type != 9 && $conf->global->ULTIMATEPDF_GENERATE_SUPPLIERORDERS_WITH_PRODUCTS_BARCODE == 1) {
							// dysplay product barcode
							$posy = $this->ultimatebarcode($pdf, $product);
							$posYAfterDescription = $pdf->GetY();
						}
						$pageposafterdesc = $pdf->getPage();

						if ($pageposafter > $pageposbefore)	// There is a pagebreak
						{
							$posYAfterImage = $tab_top_newpage + $imglinesize['height'];
							$pdf->rollbackTransaction(true);
							$pageposbeforedesc = $pdf->getPage();
							$pageposafter = $pageposbefore;
							$posYStartDescription = $curY;
							$pdf->setPageOrientation('', 1, $heightforfooter);	// The only function to edit the bottom margin of current page to set it.
							pdf_writelinedesc($pdf, $object, $i, $outputlangs, $text_length - $curX, 3, $curX + 1, $curY + 0.5, $hideref, $hidedesc, 1);
							$posYAfterDescription = $pdf->GetY();

							if (!empty($product->barcode) && !empty($product->barcode_type_code) && $object->lines[$i]->product_type != 9 && $conf->global->ULTIMATEPDF_GENERATE_SUPPLIERORDERS_WITH_PRODUCTS_BARCODE == 1) {
								// dysplay product barcode
								$posy = $this->ultimatebarcode($pdf, $product);
								$posYAfterDescription = $pdf->GetY();
							}
							$pageposafterdesc = $pdf->getPage();

							if ($posYAfterDescription > ($this->page_hauteur - $bMargin))	// There is no space left for total+free text
							{
								if ($i == ($nblines - 1))	// No more lines, and no space left to show total, so we create a new page
								{
									$pdf->AddPage('', '', true);
									if (!empty($tplidx)) $pdf->useTemplate($tplidx);
									if (empty($conf->global->ULTIMATE_SUPPLIERORDERS_PDF_DONOTREPEAT_HEAD)) $this->_pagehead($pdf, $object, 0, $outputlangs);
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
					//test extrafields on line
					/*$object->lines[$i]->fetch_optionals($object->lines[$i]->id);
					$posxdate=$object->lines[$i]->array_options['options_confirm'];
					$pdf->SetXY($this->posxdate, $curY);
					$pdf->MultiCell($this->posxtva-$this->posxdate-0.8, 3, dol_print_date($posxdate,"day",false,$outputlangs,true), 0, 'C');*/

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
					if (!empty($conf->global->ULTIMATE_SUPPLIERORDERS_WITH_LINE_NUMBER)) {
						// Numbering
						if ($this->getColumnStatus('num') && array_key_exists($i, $object->lines)) {
							$this->printStdColumnContent($pdf, $curY, 'num', $line_number);
							//$nexY = max($pdf->GetY(),$nexY);
							$line_number++;
						}
					}

					//  Column reference
					if ($conf->global->ULTIMATE_DOCUMENTS_WITH_REF == "yes" && $this->atleastoneref == true) {
						if ($this->getColumnStatus('ref')) {
							$productRef = $object->lines[$i]->ref_supplier;
							$this->printStdColumnContent($pdf, $curY, 'ref', $productRef);
							//$nexY = max($pdf->GetY(), $nexY);
						}
					}
					
					// VAT Rate
					if ($conf->global->ULTIMATE_GENERATE_DOCUMENTS_WITHOUT_VAT == "no" && empty($conf->global->ULTIMATE_SHOW_HIDE_VAT_COLUMN) && empty($conf->global->ULTIMATE_SHOW_HIDE_ALL_PRICES)) {
						// VAT Rate
						if ($this->getColumnStatus('vat')) {
							$vat_rate = pdf_getlinevatrate($object, $i, $outputlangs, $hidedetails);
							$this->printStdColumnContent($pdf, $curY, 'vat', $vat_rate);
							//$nexY = max($pdf->GetY(),$nexY);
						}
					}

					// Unit price before discount
					if (empty($conf->global->ULTIMATE_SHOW_HIDE_PUHT) && empty($conf->global->ULTIMATE_SHOW_HIDE_ALL_PRICES)) {
						if ($this->getColumnStatus('subprice')) {
							$up_excl_tax = pdf_getlineupexcltax($object, $i, $outputlangs, $hidedetails);
							$this->printStdColumnContent($pdf, $curY, 'subprice', $up_excl_tax);
							//$nexY = max($pdf->GetY(),$nexY);
						}
					}

					// Discount on line 
					if ($this->atleastonediscount && !empty($conf->global->ULTIMATE_GENERATE_SUPPLIERORDERS_WITH_DISCOUNT)) {
						if ($this->getColumnStatus('discount')) {
							$remise_percent = pdf_getlineremisepercent($object, $i, $outputlangs, $hidedetails);
							$this->printStdColumnContent($pdf, $curY, 'discount', $remise_percent);
							//$nexY = max($pdf->GetY(),$nexY);
						}
					}

					// Unit price after discount
					if ($this->atleastonediscount && !empty($conf->global->ULTIMATE_GENERATE_SUPPLIERORDERS_WITH_PUAFTER)) {
						if ($remise_percent == dol_print_reduction(100, $langs)) {
							$up_after = price(0);
							$this->printStdColumnContent($pdf, $curY, 'upafter', $up_after);
							//$nexY = max($pdf->GetY(),$nexY);
						} else {
							if ($this->getColumnStatus('upafter') && $object->lines[$i]->remise_percent > 0) {
								$up_after = price(price2num($up_excl_tax, 'MU') * price2num(1 - price2num($remise_percent, 'MU') / 100, 'MU'));
								$this->printStdColumnContent($pdf, $curY, 'upafter', $up_after);
								//$nexY = max($pdf->GetY(),$nexY);
							}
						}
					}

					// Quantity
					if (empty($conf->global->ULTIMATE_SHOW_HIDE_QTY)) {
						if ($this->getColumnStatus('qty')) {
							$qty = pdf_getlineqty($object, $i, $outputlangs, $hidedetails);
							$this->printStdColumnContent($pdf, $curY, 'qty', $qty);
							//$nexY = max($pdf->GetY(),$nexY);
						}
					}

					// Unit
					if ($conf->global->PRODUCT_USE_UNITS) {
						if ($this->getColumnStatus('unit')) {
							$unit = pdf_getlineunit($object, $i, $outputlangs, $hidedetails, $hookmanager);
							$this->printStdColumnContent($pdf, $curY, 'unit', $unit);
							//$nexY = max($pdf->GetY(),$nexY);
						}
					}

					if (!empty($conf->global->ULTIMATE_SHOW_LINE_TTTC)) {
						// Total TTC line
						if ($this->getColumnStatus('totalincltax') && empty($conf->global->ULTIMATE_SHOW_HIDE_THT) && empty($conf->global->ULTIMATE_SHOW_HIDE_ALL_PRICES)) {
							$total_incl_tax = pdf_getlinetotalwithtax($object, $i, $outputlangs, $hidedetails);
							$this->printStdColumnContent($pdf, $curY, 'totalincltax', $total_incl_tax);
							//$nexY = max($pdf->GetY(),$nexY);
						}
					} else {
						// Total HT line
						if ($this->getColumnStatus('totalexcltax') && empty($conf->global->ULTIMATE_SHOW_HIDE_THT) && empty($conf->global->ULTIMATE_SHOW_HIDE_ALL_PRICES)) {
							$total_excl_tax = pdf_getlinetotalexcltax($object, $i, $outputlangs, $hidedetails);
							$this->printStdColumnContent($pdf, $curY, 'totalexcltax', $total_excl_tax);
							//$nexY = max($pdf->GetY(),$nexY);
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
					$localtax1_rate = $object->lines[$i]->localtax1_tx;
					$localtax2_rate = $object->lines[$i]->localtax2_tx;
					$localtax1_type = $object->lines[$i]->localtax1_type;
					$localtax2_type = $object->lines[$i]->localtax2_type;

					if (!empty($object->remise_percent)) $tvaligne -= ($tvaligne * $object->remise_percent) / 100;
					if (!empty($object->remise_percent)) $localtax1ligne -= ($localtax1ligne * $object->remise_percent) / 100;
					if (!empty($object->remise_percent)) $localtax2ligne -= ($localtax2ligne * $object->remise_percent) / 100;

					$vatrate = (string) $object->lines[$i]->tva_tx;

					// Retrieve type from database for backward compatibility with old records
					if ((!isset($localtax1_type) || $localtax1_type == '' || !isset($localtax2_type) || $localtax2_type == '') // if tax type not defined
						&& (!empty($localtax1_rate) || !empty($localtax2_rate))
					) // and there is local tax
					{
						$localtaxtmp_array = getLocalTaxesFromRate($vatrate, 0, $mysoc, $object->thirdparty);
						$localtax1_type = $localtaxtmp_array[0];
						$localtax2_type = $localtaxtmp_array[2];
					}

					// retrieve global local tax
					if ($localtax1_type && $localtax1ligne != 0)
						$this->localtax1[$localtax1_type][$localtax1_rate] += $localtax1ligne;
					if ($localtax2_type && $localtax2ligne != 0)
						$this->localtax2[$localtax2_type][$localtax2_rate] += $localtax2ligne;

					if (($object->lines[$i]->info_bits & 0x01) == 0x01) $vatrate .= '*';
					if (!isset($this->tva[$vatrate])) 				$this->tva[$vatrate] = 0;
					$this->tva[$vatrate] += $tvaligne;

					if ($posYAfterImage > $posYAfterDescription) $nexY = $posYAfterImage;

					// Add line
					if (!empty($conf->global->ULTIMATE_SUPPLIERORDERS_PDF_DASH_BETWEEN_LINES) && $i < ($nblines - 1)) {
						$pdf->setPage($pageposafter);
						$pdf->SetLineStyle(array('dash' => '1,1', 'color' => array(70, 70, 70)));
						if ($conf->global->ULTIMATEPDF_GENERATE_SUPPLIERORDERS_WITH_PRODUCTS_BARCODE == 1 && !empty($product->barcode)) {
							$pdf->line($this->marge_gauche, $nexY + 4, $this->page_largeur - $this->marge_droite, $nexY + 4);
						} else {
							$pdf->line($this->marge_gauche, $nexY + 1, $this->page_largeur - $this->marge_droite, $nexY + 1);
						}
						$pdf->SetLineStyle(array('dash' => 0));
					}

					if ($conf->global->ULTIMATEPDF_GENERATE_SUPPLIERORDERS_WITH_PRODUCTS_BARCODE == 1 && !empty($product->barcode)) {
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
						if (empty($conf->global->ULTIMATE_SUPPLIERORDERS_PDF_DONOTREPEAT_HEAD)) $this->_pagehead($pdf, $object, 0, $outputlangs, $titlekey);
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
						if (empty($conf->global->ULTIMATE_SUPPLIERORDERS_PDF_DONOTREPEAT_HEAD)) $this->_pagehead($pdf, $object, 0, $outputlangs, $titlekey);
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

				if ($conf->mbisignature->enabled) {
					$posy = $this->_signature_area_simple_or_double($pdf, $object, $posy, $outputlangs);
				} else { // Affiche zone agreement
					$posy = $this->_agreement($pdf, $object, $posy, $outputlangs);
				}

				// Affiche zone versements
				if ($deja_regle || $amount_credit_notes_included || $amount_deposits_included) {
					$posy = $this->_tableau_versements($pdf, $object, $posy, $outputlangs);
				}

				// Affiche zone signature responsable
				$posy = $this->_signature($pdf, $object, $posy, $outputlangs);

				// Pied de page
				$this->_pagefoot($pdf, $object, $outputlangs);
				if (method_exists($pdf, 'AliasNbPages')) $pdf->AliasNbPages();

				// Add PDF to be merged
				if (!empty($conf->global->ULTIMATEPDF_GENERATE_SUPPLIERORDERS_WITH_MERGED_PDF)) {
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
									if (!empty($conf->fournisseur->enabled))
										$filetomerge_dir = $conf->fournisseur->dir_output . '/commande/' . dol_sanitizeFileName($object->ref);
									dol_syslog(get_class($this) . '::$upload_dir=' . $filetomerge_dir, LOG_DEBUG);
									$infile = $filetomerge_dir . '/' . $linefile->file_name;

									// If file really exists
									if (file_exists($infile) && is_readable($infile)) {
										$pagecount = $pdf->setSourceFile($infile);
										for ($i = 1; $i <= $pagecount; $i++) {
											$tplIdx = $pdf->importPage($i);
											if ($tplIdx !== false) {
												$s = $pdf->getTemplatesize($tplIdx);
												$pdf->AddPage($s['h'] > $s['w'] ? 'P' : 'L');
												$pdf->useTemplate($tplIdx);
											} else {
												setEventMessages(null, array($infile . ' cannot be added, probably protected PDF'), 'warnings');
											}
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
				if (!is_object($hookmanager)) {
					include_once(DOL_DOCUMENT_ROOT . '/core/class/hookmanager.class.php');
					$hookmanager = new HookManager($this->db);
				}
				$hookmanager->initHooks(array('pdfgeneration'));
				$parameters = array('file' => $file, 'object' => $object, 'outputlangs' => $outputlangs);
				global $action;
				$reshook = $hookmanager->executeHooks('afterPDFCreation', $parameters, $this, $action);    // Note that $action and $object may have been modified by some hooks

				if (!empty($conf->global->MAIN_UMASK))
					@chmod($file, octdec($conf->global->MAIN_UMASK));

				return 1;   // Pas d'erreur
			} else {
				$this->error = $langs->trans("ErrorCanNotCreateDir", $dir);
				return 0;
			}
		} else {
			$this->error = $langs->trans("ErrorConstantNotDefined", "SUPPLIER_OUTPUTDIR");
			return 0;
		}
		$this->error = $langs->trans("ErrorUnknown");

		unset($_SESSION['ultimatepdf_model']);

		return 0;   // Erreur par defaut
	}

	/**
	 *  Show payments table
	 *
	 *  @param	TCPDF		&$pdf     		Object PDF
	 *  @param  Object		$object			Object order
	 *	@param	int			$posy			Position y in PDF
	 *	@param	Translate	$outputlangs	Object langs for output
	 *	@return int							<0 if KO, >0 if OK
	 */
	function _tableau_versements(&$pdf, $object, $posy, $outputlangs)
	{

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

		$pdf->SetFont('', '', $default_font_size - 1);

		// If France, show VAT mention if not applicable
		if ($this->emetteur->country_code == 'FR' && $this->franchise == 1) {
			$pdf->SetFont('', 'B', $default_font_size - 2);
			$pdf->SetXY($this->marge_gauche, $posy);
			$pdf->MultiCell(100, 3, $outputlangs->transnoentities("VATIsNotUsedForInvoice"), 0, 'L', 0);

			$posy = $pdf->GetY() + 4;
		}

		$widthrecbox = ($this->page_largeur - $this->marge_gauche - $this->marge_droite - 4) / 2;

		// Show payments conditions
		if ($object->cond_reglement_code || $object->cond_reglement) {
			$pdf->SetFont('', '', $default_font_size - 2);
			$pdf->SetTextColorArray($textcolor);
			$titre = '<b>' . $outputlangs->transnoentities("PaymentConditions") . '</b>' . ' : ';
			$lib_condition_paiement = $outputlangs->transnoentities("PaymentCondition" . $object->cond_reglement_code) != ('PaymentCondition' . $object->cond_reglement_code) ? $outputlangs->transnoentities("PaymentCondition" . $object->cond_reglement_code) : $outputlangs->convToOutputCharset($object->cond_reglement_doc);
			$lib_condition_paiement = str_replace('\n', "\n", $lib_condition_paiement);
			$pdf->writeHTMLCell($widthrecbox, 4, $this->marge_gauche, $posy, $titre . ' ' . $lib_condition_paiement, 0, 0, false, true, 'L', true);

			$posy = $pdf->GetY() + 7;
		}

		// Show payment mode
		if ($object->mode_reglement_code) {
			$pdf->SetFont('', '', $default_font_size - 2);
			$titre = '<b>' . $outputlangs->transnoentities("PaymentMode") . '</b>' . ' : ';
			$lib_mode_reg = $outputlangs->transnoentities("PaymentType" . $object->mode_reglement_code) != ('PaymentType' . $object->mode_reglement_code) ? $outputlangs->transnoentities("PaymentType" . $object->mode_reglement_code) : $outputlangs->convToOutputCharset($object->mode_reglement);
			$pdf->writeHTMLCell($widthrecbox, 4, $this->marge_gauche, $posy, $titre . ' ' . $lib_mode_reg, 0, 0, false, true, 'L', true);

			$posy = $pdf->GetY() + 4;
		}

		// Example using extrafields
		$title_key = (empty($object->array_options['options_newline'])) ? '' : ($object->array_options['options_newline']);
		$extrafields = new ExtraFields($this->db);
		$extralabels = $extrafields->fetch_name_optionals_label($object->table_element, true);
		if (is_array($extralabels) && key_exists('newline', $extralabels) && !empty($title_key)) {
			$title = $extrafields->showOutputField('newline', $title_key);
			$pdf->writeHTMLCell($widthrecbox, 4, $this->marge_gauche, $posy, $title, 0, 0, false, true, 'L', true);

			$posy = $pdf->GetY() + 7;
		}

		return $posy;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
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
		// phpcs:enable
		global $conf, $mysoc;

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

		// Tableau total
		$widthrecbox = ($this->page_largeur - $this->marge_gauche - $this->marge_droite - 4) / 2;
		$deltax = $this->marge_gauche + $widthrecbox + 4;
		$col1x = $deltax + 2;
		$col2x = 150;
		if ($this->page_largeur < 210) // To work with US executive format
		{
			$col2x -= 20;
		}
		$largcol2 = ($this->page_largeur - $this->marge_droite - $col2x);
		if ($roundradius == 0) {
			$roundradius = 0.1; //RoundedRect don't support $roundradius to be 0
		}

		$useborder = 0;
		$index = 0;

		// Total HT
		if (empty($conf->global->ULTIMATE_SHOW_HIDE_ALL_PRICES)) {
			$pdf->SetXY($col1x, $posy);
			$pdf->SetAlpha($opacity);
			$pdf->RoundedRect($deltax, $tab2_top, $widthrecbox, 4, 2, '1111', 'FD', $this->border_style, $bgcolor);
			$pdf->SetAlpha(1);
			$pdf->SetFillColor(255, 255, 255);
			$pdf->MultiCell($col2x - $col1x, $tab2_hl, $outputlangs->transnoentities("TotalHT"), 0, 'L', 1);

			$total_ht = ($conf->multicurrency->enabled && $object->multicurrency_tx != 1 ? $object->multicurrency_total_ht : $object->total_ht);
			$pdf->SetXY($col2x, $tab2_top);
			$this->_setFontForMulticurrencyCode($pdf, $object, $outputlangs);
			$pdf->MultiCell($largcol2, $tab2_hl, price($total_ht + (!empty($object->remise) ? $object->remise : 0), 0, $outputlangs, 0, -1, -1, (!empty($object->multicurrency_code) ? $object->multicurrency_code : $conf->currency)), 0, 'R', 1);
			$pdf->SetFont($conf->global->MAIN_PDF_FORCE_FONT, '', $default_font_size);
		}

		// Affichage des totaux de TVA par taux (conformement a reglementation)
		$pdf->SetFillColor(248, 248, 248);

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
							$totalvat = $outputlangs->transcountrynoentities("TotalLT1", $mysoc->country_code);
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

				//Local tax 2  before VAT
				foreach ($this->localtax2 as $localtax_type => $localtax_rate) {
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
							$totalvat = $outputlangs->transcountrynoentities("TotalLT2", $mysoc->country_code);
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
				foreach ($this->tva as $tvakey => $tvaval) {
					if ($tvakey > 0)    // On affiche pas taux 0
					{
						$this->atleastoneratenotnull++;

						$index++;
						$pdf->SetXY($col1x, $tab2_top + 0.5 + $tab2_hl * $index);
						$pdf->SetFont('', '', $default_font_size - 1);
						$bgcolor2 = array(177, 177, 177);
						$pdf->SetAlpha($opacity);
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
						if (empty($conf->global->ULTIMATE_SHOW_HIDE_ALL_PRICES)) {
							$pdf->MultiCell($col2x - $col1x, $tab2_hl, $totalvat, 0, 'L', 0);

							$pdf->SetXY($col2x, $tab2_top + 0.5 + $tab2_hl * $index);
							$this->_setFontForMulticurrencyCode($pdf, $object, $outputlangs);
							$pdf->MultiCell($largcol2, $tab2_hl, price($tvaval, 0, $outputlangs, 1, -1, -1, $object->multicurrency_code), 0, 'R', 0);
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
							$totalvat = $outputlangs->transcountrynoentities("TotalLT1", $mysoc->country_code);
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

				//Local tax 2  after VAT
				foreach ($this->localtax2 as $localtax_type => $localtax_rate) {
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
							$totalvat = $outputlangs->transcountrynoentities("TotalLT2", $mysoc->country_code);
							$totalvat .= ' ';

							$totalvat .= vatrate($tvakey, 1) . $tvacompl;
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
					$pdf->MultiCell($col2x - $col1x, $tab2_hl, $outputlangs->transnoentities("TotalTTC"), $useborder, 'L', 1);

					//$total_ttc = ($conf->multicurrency->enabled && $object->multiccurency_tx != 1) ? $object->multicurrency_total_ttc : $object->total_ttc;
					$pdf->SetXY($col2x, $tab2_top + 1 + $tab2_hl * $index);
					$this->_setFontForMulticurrencyCode($pdf, $object, $outputlangs);
					$pdf->MultiCell($largcol2, $tab2_hl, price($total_ttc, 0, $outputlangs, 1, -1, -1, $object->multicurrency_code), $useborder, 'R', 1);
					$pdf->SetFont($conf->global->MAIN_PDF_FORCE_FONT, '', $default_font_size - 1);
				}
			}
		} else {
			// Total TTC without VAT
			if (empty($conf->global->ULTIMATE_SHOW_HIDE_ALL_PRICES)) {
				$index++;

				$pdf->SetXY($col1x, $tab2_top + 0.8 + $tab2_hl * $index);
				$pdf->SetTextColorArray($textcolor);
				$pdf->SetAlpha($opacity);
				$pdf->RoundedRect($deltax, $tab2_top + 1 + $tab2_hl * $index, $widthrecbox, 4, $roundradius, '1111', 'FD', $this->border_style, $bgcolor);
				$pdf->SetAlpha(1);
				$pdf->SetFont('', 'B', $default_font_size - 1);
				$pdf->MultiCell($col2x - $col1x, $tab2_hl, $outputlangs->transnoentities("TotalTTC"), $useborder, 'L', 1);

				$pdf->SetXY($col2x, $tab2_top + 0.8 + $tab2_hl * $index);
				$this->_setFontForMulticurrencyCode($pdf, $object, $outputlangs);
				$pdf->MultiCell($largcol2, $tab2_hl, price($total_ht + (!empty($object->remise) ? $object->remise : 0), 0, $outputlangs, 0, -1, -1, (!empty($object->multicurrency_code) ? $object->multicurrency_code : $conf->currency)), 0, 'R', 1);
				$pdf->SetFont($conf->global->MAIN_PDF_FORCE_FONT, '', $default_font_size - 1);
			}
		}
		$pdf->SetTextColorArray($textcolor);


		$creditnoteamount = 0;
		$depositsamount = 0;
		$resteapayer = price2num($total_ttc - $deja_regle - $creditnoteamount - $depositsamount, 'MT');
		if (!empty($object->paye)) $resteapayer = 0;

		if ($deja_regle > 0) {
			// Already paid + Deposits
			$index++;

			$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
			$pdf->MultiCell($col2x - $col1x, $tab2_hl, $outputlangs->transnoentities("AlreadyPaid"), 0, 'L', 0);
			$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
			$pdf->MultiCell($largcol2, $tab2_hl, price($deja_regle, 0, $outputlangs), 0, 'R', 0);

			$index++;
			$pdf->SetTextColor(0, 0, 60);
			$pdf->SetFillColor(224, 224, 224);
			$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
			$pdf->MultiCell($col2x - $col1x, $tab2_hl, $outputlangs->transnoentities("RemainderToPay"), $useborder, 'L', 1);

			$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
			$pdf->MultiCell($largcol2, $tab2_hl, price($resteapayer, 0, $outputlangs), $useborder, 'R', 1);

			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->SetTextColorArray($textcolor);
		}

		$index++;
		return ($tab2_top + ($tab2_hl * $index));
	}
	
	/**
	 *	Show good for agreement
	 *
	 *	@param	TCPDF		&$pdf           Object PDF
	 *  @param	Object		$object			Object to show
	 *	@param	int			$posy			Position depart
	 *	@param	Translate	$outputlangs	Objet langs
	 *	@return int							Position pour suite
	 */
	function _agreement(&$pdf, $object, $posy, $outputlangs)
	{
		global $conf;

		$outputlangs->load("propal");

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		$roundradius =  (!empty($conf->global->ULTIMATE_SET_RADIUS)) ?
		$conf->global->ULTIMATE_SET_RADIUS : 1;

		$dashdotted =  (!empty($conf->global->ULTIMATE_DASH_DOTTED)) ?
		$conf->global->ULTIMATE_DASH_DOTTED : 0;

		$bordercolor =  (!empty($conf->global->ULTIMATE_BORDERCOLOR_COLOR)) ?
		html2rgb($conf->global->ULTIMATE_BORDERCOLOR_COLOR) : array(0, 63, 127);

		$textcolor =  (!empty($conf->global->ULTIMATE_TEXTCOLOR_COLOR)) ?
		html2rgb($conf->global->ULTIMATE_TEXTCOLOR_COLOR) : array(25, 25, 25);

		if ($conf->global->ULTIMATE_DISPLAY_SUPPLIERORDER_AGREEMENT_BLOCK) {
			$heightforagreement = 40;
		}
		$this->border_style = array('width' => 0.2, 'cap' => 'butt', 'join' => 'miter', 'dash' => $dashdotted, 'color' => $bordercolor);
		$widthrecbox = ($this->page_largeur - $this->marge_gauche - $this->marge_droite - 4) / 2;

		if (!empty($conf->global->ULTIMATE_DISPLAY_SUPPLIERORDER_AGREEMENT_BLOCK)) {
			$heightforfreetext = (isset($conf->global->MAIN_PDF_FREETEXT_HEIGHT) ? $conf->global->MAIN_PDF_FREETEXT_HEIGHT : 12);	// Height reserved to output the free text on last page
			$heightforfooter = $this->marge_basse + 12;	// Height reserved to output the footer (value include bottom margin)
			$heightforinfotot = 40;	// Height reserved to output the info and total part
			$deltay = $this->page_hauteur - $heightforfreetext - $heightforfooter - $heightforinfotot;
			$posy = max($posy + 10, $deltay);
			$deltax = $this->marge_gauche + $widthrecbox + 4;
			if ($roundradius == 0) {
				$roundradius = 0.1; //RoundedRect don't support $roundradius to be 0
			}
			$pdf->RoundedRect($deltax, $posy, $widthrecbox, $heightforagreement, $roundradius, '1111', 'S', $this->style, array());
			$pdf->SetFont('', 'B', $default_font_size - 1);
			$pdf->SetTextColorArray($textcolor);
			$pdf->SetXY($deltax, $posy);
			$titre = $outputlangs->transnoentities('ProposalCustomerSignature');
			$pdf->MultiCell($widthrecbox, 5, $titre, 0, 'L', 0);
			$pdf->SetFont('', '', $default_font_size - 2);
			$pdf->SetXY($deltax, $posy + 5);
			$pdf->SetFont('', 'I', $default_font_size - 2);
			$pdf->MultiCell(90, 3, $outputlangs->transnoentities('DocORDER2'), 0, 'L', 0);
			$pdf->SetXY($deltax, $posy + 12);
			
			$posy = $pdf->GetY();
			$pdf->SetXY($deltax, $posy);
			$pdf->SetFont('', 'I', $default_font_size - 2);
			$pdf->MultiCell(80, 3, $outputlangs->transnoentities('DocORDER4'), 0, 'L', 0);
		}
		return $posy;
	}
	
	/**
	 *	Show signature block
	 *
	 *	@param	TCPDF		&$pdf           Object PDF
	 *  @param	Object		$object			Object to show
	 *	@param	int			$posy			Position depart
	 *	@param	Translate	$outputlangs	Objet langs
	 *	@return int							Position pour suite
	 */
	function _signature(&$pdf, $object, $posy, $outputlangs)
	{
		global $conf;

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		$dashdotted =  (!empty($conf->global->ULTIMATE_DASH_DOTTED)) ?
		$conf->global->ULTIMATE_DASH_DOTTED : 0;

		$bordercolor =  (!empty($conf->global->ULTIMATE_BORDERCOLOR_COLOR)) ?
		html2rgb($conf->global->ULTIMATE_BORDERCOLOR_COLOR) : array(0, 63, 127);

		$textcolor =  (!empty($conf->global->ULTIMATE_TEXTCOLOR_COLOR)) ?
		html2rgb($conf->global->ULTIMATE_TEXTCOLOR_COLOR) : array(25, 25, 25);

		$this->border_style = array('width' => 0.2, 'cap' => 'butt', 'join' => 'miter', 'dash' => $dashdotted, 'color' => $bordercolor);
		$widthrecbox = ($this->page_largeur - $this->marge_gauche - $this->marge_droite - 4) / 2;

		if (!empty($conf->global->ULTIMATEPDF_ADD_RESPONSABLE_SIGNATURE_IN_SUPPLIERORDERS)) {
			$heightforfreetext = (isset($conf->global->MAIN_PDF_FREETEXT_HEIGHT) ? $conf->global->MAIN_PDF_FREETEXT_HEIGHT : 12);	// Height reserved to output the free text on last page
			$heightforfooter = $this->marge_basse + 12;	// Height reserved to output the footer (value include bottom margin)
			$heightforinfotot = 35;	// Height reserved to output the info and total part
			$deltay = $this->page_hauteur - $heightforfreetext - $heightforfooter - $heightforinfotot + 20;
			$posy = max($posy, $deltay);
			$deltax = $this->marge_gauche + $widthrecbox / 3;
			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->SetTextColorArray($textcolor);
			$pdf->SetXY($deltax, $posy);
			$text = $outputlangs->transnoentities('MadeTo') . ' ' . $this->emetteur->town . ' ' . $outputlangs->transnoentities('On') . ' ' . dol_print_date(dol_now(), 'daytext');
			$pdf->MultiCell(80, 3, $text, 0, 'L', 0);
			$posy = $pdf->GetY() + 2;
			$pdf->SetXY($deltax, $posy);
			$pdf->SetFont('', 'I', $default_font_size - 2);
			//$pdf->MultiCell(80, 3, $outputlangs->transnoentities('DocORDER4'), 0, 'L', 0);
			$posy = $pdf->getY();
			// Example using extrafields
			$extrafields = new ExtraFields($this->db);
			$extralabels = $extrafields->fetch_name_optionals_label($object->table_element, true);
			if (!empty($this->rowid)) {
				$object->fetch($this->rowid);
			}
			$object->fetch_optionals($this->rowid, $extralabels);
			$titlekey = $outputlangs->convToOutputCharset($object->array_options['options_signature']);

			$sql = 'SELECT rowid, login, lastname, firstname';
			$sql .= ' FROM ' . MAIN_DB_PREFIX . 'user';
			$sql .= " WHERE rowid ='" . $titlekey . "'";

			if ($titlekey == 0) $titlekey = null;
			$result = $this->db->query($sql);
			if ($result) {
				if ($this->db->num_rows($result)) {
					$obj = $this->db->fetch_object($result);
					$titlekey = $obj->login;
				}
			}

			$salerepobj = new User($this->db);
			$salerepobj->fetch('', $titlekey);
			$inthatstr = $salerepobj->signature;
			$thisstr = 'file=';
			$thatstr = '" style';
			$imgsignature = between($thisstr, $thatstr, $inthatstr);			
			$signature = $conf->medias->multidir_output[$conf->entity].'/'.$imgsignature;
			$heightsignature = 18;
			if (! empty($salerepobj->signature)) {
				$pdf->Image($signature, $deltax+15, $posy, 0, $heightsignature); // width=0 (auto)
			} 
			else 
			{
				$pdf->SetTextColor(200, 0, 0);
				$pdf->SetXY($deltax, $posy);
				$pdf->SetFont('', '', $default_font_size - 2);
				$pdf->MultiCell($widthrecbox, 3, $outputlangs->transnoentities("ErrorFileNotFound", $signature), 0, 'L');
				$pdf->SetTextColorArray($textcolor);
			}
			return $posy;
		}
	}


    /**
	 *   Show table for lines
	 *
	 *   @param		TCPDF		&$pdf     		Object PDF
	 *   @param		string		$tab_top		Top position of table
	 *   @param		string		$tab_height		Height of table (rectangle)
	 *   @param		int			$nexY			Y
	 *   @param		Translate	$outputlangs	Langs object
	 *   @param		int			$hidetop		1=Hide top bar of array and title, 0=Hide nothing, -1=Hide only title
	 *   @param		int			$hidebottom		Hide bottom bar of array
	 *   @return	void
	 */
	function _tableau(&$pdf, $tab_top, $tab_height, $nexY, $outputlangs, $hidetop = 0, $hidebottom = 0)
	{
		global $conf;

		// Force to disable hidetop and hidebottom
		$hidebottom = 0;
		if ($hidetop) $hidetop = -1;

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
	 *  Show top header of page.
	 *
	 *  @param	TCPDF		$pdf     		Object PDF
	 *  @param  Object		$object     	Object to show
	 *  @param  int	    	$showaddress    0=no, 1=yes
	 *  @param  Translate	$outputlangs	Object lang for output
	 *  @param	string		$titlekey		Translation key to show as title of document
	 *  @return	void
	 */
	protected function _pagehead(&$pdf, $object, $showaddress, $outputlangs, $titlekey = "SupplierOrder")
	{
		global $langs, $conf;

		$ltrdirection = 'L';
		if ($outputlangs->trans("DIRECTION") == 'rtl') $ltrdirection = 'R';

		// Translations
		$outputlangs->loadLangs(array("main", "companies", "bills", "orders", "sendings", "ultimatepdf@ultimatepdf"));

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

		//affiche repere de pliage	
		if (!empty($conf->global->MAIN_DISPLAY_SUPPLIERORDERS_FOLD_MARK)) {
			$pdf->Line(0, ($this->page_hauteur) / 3, 3, ($this->page_hauteur) / 3);
		}

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
			$pdf->MultiCell(90, 8, $outputlangs->convToOutputCharset($text), 0, $ltrdirection);
			$logo_height = 20;
		}	

		//Display Thirdparty barcode at top				
		if (!empty($conf->global->ULTIMATEPDF_GENERATE_SUPPLIERORDERS_WITH_TOP_BARCODE)) {
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
		$posxQRcode = $this->page_largeur / 2;
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
		if (!empty($conf->global->ULTIMATEPDF_GENERATE_SUPPLIERORDERS_WITH_TOP_QRCODE)) //TODO : change for supplierorder
		{
			$code = pdf_codeContents();
			$pdf->write2DBarcode($code, 'QRCODE,L', $posxQRcode, $posy, $heightQRcode, $heightQRcode, $styleQr, 'N');
		}
		// My Company QR-code
		if (!empty($conf->global->ULTIMATEPDF_GENERATE_SUPPLIERORDERS_WITH_MYCOMP_QRCODE)) //TODO : change for supplierorder
		{
			$code = pdf_mycompCodeContents();
			$pdf->write2DBarcode($code, 'QRCODE,L', $posxQRcode, $posy, $heightQRcode, $heightQRcode, $styleQr, 'N');
		}

		if (!empty($conf->global->ULTIMATEPDF_GENERATE_SUPPLIERORDERS_WITH_TOP_QRCODE) || ($conf->global->ULTIMATEPDF_GENERATE_SUPPLIERORDERS_WITH_MYCOMP_QRCODE)) {
			$rightSideWidth = $tab_width / 2 - $heightQRcode;
			$posx = $this->marge_gauche + $tab_width / 2 + $heightQRcode;
		} else {
			$rightSideWidth = $tab_width / 2;
			$posx = $this->marge_gauche + $tab_width / 2 ;
		}

		// Example using extrafields for new title of document
		$title_key = (empty($object->array_options['options_newtitle'])) ? '' : ($object->array_options['options_newtitle']);
		$extrafields = new ExtraFields($this->db);
		$extralabels = $extrafields->fetch_name_optionals_label($object->table_element, true);
		if (is_array($extralabels) && key_exists('newtitle', $extralabels) && !empty($title_key)) {
			$titlekey = $extrafields->showOutputField('newtitle', $title_key);
		}

		//Document name
		$pdf->SetFont('', 'B', $default_font_size + 3);
		$pdf->SetXY($posx, $posy);
		$pdf->SetTextColorArray($textcolor);
		$titlekey = $outputlangs->transnoentities($titlekey);
		$pdf->MultiCell(100, 4, $titlekey, '', 'R');

		$posy = $pdf->getY();

		$pdf->SetXY($posx, $posy);
		$pdf->SetFont('', '', $default_font_size + 1);
		$pdf->SetTextColorArray($textcolor);
		$pdf->MultiCell(100, 4, $outputlangs->transnoentities("Ref") . " : " . $outputlangs->convToOutputCharset($object->ref), '', 'R');

		if ($object->ref_supplier) {
			$posy = $pdf->getY();
			$pdf->SetFont('', 'B', $default_font_size + 1);
			$pdf->SetXY($posx, $posy);
			$pdf->SetTextColorArray($textcolor);
			$pdf->MultiCell(100, 4, $outputlangs->transnoentities("RefSupplier") . " : " . $outputlangs->convToOutputCharset($object->ref_supplier), '', 'R');
		}

		$posy = $pdf->getY();

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
			// Sender properties
			$carac_emetteur = pdf_element_build_address($outputlangs, $this->emetteur, $object->thirdparty, '', 0, 'source', $object);

			// Show sender
			$posy = $logo_height + $this->marge_haute + 4;
			$posx = $this->marge_gauche;
			if (!empty($conf->global->ULTIMATE_PDF_SUPPLIERORDER_ADDALSOTARGETDETAILS) &&($conf->global->ULTIMATE_PDF_SUPPLIERORDER_ADDALSOTARGETDETAILS == 1) || (!empty($conf->global->MAIN_INFO_SOCIETE_NOTE) && !empty($this->emetteur->note_private)) || (!empty($conf->global->MAIN_INFO_TVAINTRA) && !empty($this->emetteur->tva_intra))) {
				$hautcadre = 48;
			} else {
				$hautcadre = 40;
			}
			$widthrecbox = $conf->global->ULTIMATE_WIDTH_RECBOX - 2;$widthrecboxrecipient = $tab_width - $conf->global->ULTIMATE_WIDTH_RECBOX;
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
			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->SetAlpha($opacity);
			$pdf->RoundedRect($posx, $posy, $widthrecbox, $hautcadre, $roundradius, '1111', $senderstyle, $this->border_style, $bgcolor);
			$pdf->SetAlpha(1);

			// Show sender name
			$pdf->SetXY($posx + 2, $posy + 3);
			$pdf->SetFont('', 'B', $default_font_size - 1);
			if (!empty($conf->global->ULTIMATE_PDF_ALIAS_COMPANY)) {
				$pdf->MultiCell($widthrecbox - 5, 4, $outputlangs->convToOutputCharset($conf->global->ULTIMATE_PDF_ALIAS_COMPANY), 0, $ltrdirection);
			} else {
				$pdf->MultiCell($widthrecbox - 5, 4, $outputlangs->convToOutputCharset($this->emetteur->name), 0, $ltrdirection);
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
			if ($arrayidcontact = $object->getIdContact('external', 'BILLING') && $object->getIdContact('external', 'SHIPPING')) {
				// If BILLING contact defined on proposal, we use it
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
				$pdf->MultiCell($widthrecboxrecipient, 4, dol_trunc($outputlangs->transnoentities("TypeContact_order_supplier_external_BILLING"), 30), 0, 'R');

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
				$pdf->MultiCell($widthrecboxrecipient, 4, dol_trunc($outputlangs->transnoentities("TypeContact_order_supplier_external_SHIPPING"), 30), 0, 'R');

				// Show recipient name
				$pdf->SetXY($posxbc + 2, $posybc + 2);
				$pdf->SetFont('', 'B', $default_font_size);
				$pdf->MultiCell($wreceipt - 5, 4, $carac_client_name, 0, $ltrdirection);

				$posybc = $pdf->getY();

				// Show recipient information
				$pdf->SetFont('', '', $default_font_size - 1);
				$pdf->writeHTMLCell($wreceipt - 5, 4, $posxbc + 2, $posybc, $carac_client, 0, 2, 0, true, $ltrdirection, true);
			}
			// If CUSTOMER and SHIPPING contact defined, we use it
			elseif ($object->getIdContact('external', 'CUSTOMER') && $object->getIdContact('external', 'SHIPPING')) {
				// If CUSTOMER contact defined on invoice, we use it
				$usecontact = false;
				$arrayidcontact = $object->getIdContact('external', 'CUSTOMER');
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
				$pdf->MultiCell($widthrecboxrecipient, 4, dol_trunc($outputlangs->transnoentities("TypeContact_order_supplier_external_CUSTOMER"), 30), 0, 'R');

				// Show recipient name
				$pdf->SetXY($posx + 2, $posy + 2);
				$pdf->SetFont('', 'B', $default_font_size);
				$pdf->MultiCell($wreceipt - 5, 4, $carac_client_name, 0, $ltrdirection);

				$posy = $pdf->getY();

				// Show recipient information
				$pdf->SetFont('', '', $default_font_size - 1);
				$pdf->writeHTMLCell($wreceipt - 5, 4, $posx + 2, $posy, $carac_client, 0, 2, 0, true, $ltrdirection, true);

				// If SHIPPING contact defined on supplier order, we use it
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
				$pdf->MultiCell($widthrecboxrecipient, 4, dol_trunc($outputlangs->transnoentities("TypeContact_order_supplier_external_SHIPPING"), 30), 0, 'R');

				// Show recipient name
				$pdf->SetXY($posxbc + 2, $posybc + 2);
				$pdf->SetFont('', 'B', $default_font_size);
				$pdf->MultiCell($wreceipt - 5, 4, $carac_client_name, 0, $ltrdirection);

				$posybc = $pdf->getY();

				// Show recipient information
				$pdf->SetFont('', '', $default_font_size - 1);
				$pdf->writeHTMLCell($wreceipt - 5, 4, $posxbc + 2, $posybc, $carac_client, 0, 2, 0, true, $ltrdirection, true);
			}		
			// If CUSTOMER and BILLING contact defined, we use it
			elseif ($object->getIdContact('external', 'CUSTOMER') && $object->getIdContact('external', 'BILLING')) {
				// If CUSTOMER contact defined on supplier order, we use it
				$usecontact = false;
				$arrayidcontact = $object->getIdContact('external', 'CUSTOMER');
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
				$pdf->SetAlpha($opacity);
				$pdf->RoundedRect($posx, $posy, $wreceipt, $hreceipt, $roundradius, '1111', $receiptstyle, $this->border_style, $bgcolor);
				$pdf->SetAlpha(1);
				$pdf->SetXY($posxba, $posyba);
				$pdf->MultiCell($widthrecboxrecipient, 4, dol_trunc($outputlangs->transnoentities("TypeContact_order_supplier_external_CUSTOMER"), 30), 0, 'R');

				// Show recipient name
				$pdf->SetXY($posx + 2, $posy + 2);
				$pdf->SetFont('', 'B', $default_font_size);
				$pdf->MultiCell($wreceipt - 5, 4, $carac_client_name, 0, $ltrdirection);

				$posy = $pdf->getY();

				// Show recipient information
				$pdf->SetFont('', '', $default_font_size - 1);
				$pdf->writeHTMLCell($wreceipt - 5, 4, $posx + 2, $posy, $carac_client, 0, 2, 0, true, $ltrdirection, true);

				// If BILLING contact defined on supplier order, we use it
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
				$pdf->MultiCell($widthrecboxrecipient, 4, dol_trunc($outputlangs->transnoentities("TypeContact_order_supplier_external_BILLING"), 30), 0, 'R');

				// Show recipient name
				$pdf->SetXY($posxbc + 2, $posybc + 2);
				$pdf->SetFont('', 'B', $default_font_size);
				$pdf->MultiCell($wreceipt - 5, 4, $carac_client_name, 0, $ltrdirection);

				$posybc = $pdf->getY();

				// Show recipient information
				$pdf->SetFont('', '', $default_font_size - 1);
				$pdf->writeHTMLCell($wreceipt - 5, 4, $posxbc + 2, $posybc, $carac_client, 0, 2, 0, true, $ltrdirection, true);
			} // If BILLING contact defined on supplier order, we use it
			elseif ($object->getIdContact('external', 'BILLING')) {
				$usecontact = false;
				$arrayidcontact = $object->getIdContact('external', 'BILLING');
				if (count($arrayidcontact) > 0) {
					$usecontact = true;
					$result = $object->fetch_contact($arrayidcontact[0]);
				}

				// Recipient name
				if ($usecontact && !empty($conf->global->MAIN_USE_COMPANY_NAME_OF_CONTACT)) {
					$thirdparty = $object->contact;
				} else {
					$thirdparty = $object->thirdparty;
				}

				$carac_client_name = pdfBuildThirdpartyName($thirdparty, $outputlangs);

				$carac_client = pdf_element_build_address($outputlangs, $this->emetteur, $object->thirdparty, ($usecontact ? $object->contact : ''), $usecontact, 'target', $object);

				// Show recipient
				$widthrecboxrecipient = $this->page_largeur - $this->marge_droite - $this->marge_gauche - $conf->global->ULTIMATE_WIDTH_RECBOX - 2;
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
				$pdf->SetXY($posx, $posy);
				$pdf->MultiCell($widthrecboxrecipient, 4, $outputlangs->transnoentities("TypeContact_order_supplier_external_BILLING"), 0, 'R');

				$posy = $pdf->getY();

				// Show recipient name
				$pdf->SetXY($posx + 2, $posy + 3);
				$pdf->SetFont('', 'B', $default_font_size);
				$pdf->MultiCell($widthrecboxrecipient - 5, 4, $carac_client_name, 0, $ltrdirection);

				$posy = $pdf->getY();

				// Show recipient information
				$pdf->SetFont('', '', $default_font_size - 1);
				$pdf->writeHTMLCell($widthrecboxrecipient - 5, 4, $posx + 2, $posy, $carac_client, 0, 2, 0, true, $ltrdirection, true);
			} elseif ($object->getIdContact('external', 'SHIPPING')) {
				// If SHIPPING contact defined, we use it
				$usecontact = false;
				$arrayidcontact = $object->getIdContact('external', 'SHIPPING');
				if (count($arrayidcontact) > 0) {
					$usecontact = true;
					$result = $object->fetch_contact($arrayidcontact[0]);
				}

				// Recipient name
				// On peut utiliser le nom de la societe du contact
				if ($usecontact && !empty($conf->global->MAIN_USE_COMPANY_NAME_OF_CONTACT)) {
					$thirdparty = $object->contact;
				} else {
					$thirdparty = $object->thirdparty;
				}

				$carac_client_name = pdfBuildThirdpartyName($thirdparty, $outputlangs);

				$carac_client = pdf_element_build_address($outputlangs, $this->emetteur, $object->thirdparty, $object->contact, $usecontact, 'target', $object);

				// Show recipient
				$widthrecboxrecipient = $this->page_largeur - $this->marge_droite - $this->marge_gauche - $conf->global->ULTIMATE_WIDTH_RECBOX - 2;
				$posy = $logo_height + $this->marge_haute + 4;
				$posx = $this->page_largeur - $this->marge_droite - $widthrecboxrecipient;
				if (!empty($conf->global->ULTIMATE_INVERT_SENDER_RECIPIENT) && ($conf->global->ULTIMATE_INVERT_SENDER_RECIPIENT != "no")) $posx = $this->marge_gauche;

				// Show recipient frame
				$pdf->SetTextColorArray($textcolor);
				$pdf->SetFont('', '', $default_font_size - 2);

				// Show shipping address
				$pdf->SetXY($posx, $posy);
				$pdf->SetAlpha($opacity);
				$pdf->RoundedRect($posx, $posy, $widthrecboxrecipient, $hautcadre, $roundradius, '1111', $receiptstyle, $this->border_style, $bgcolor);
				$pdf->SetAlpha(1);
				$pdf->MultiCell($widthrecboxrecipient, 4, $outputlangs->transnoentities("TypeContact_order_supplier_external_SHIPPING"), 0, 'R');

				// Show recipient name
				$pdf->SetXY($posx + 2, $posy + 3);
				$pdf->SetFont('', 'B', $default_font_size);
				$pdf->MultiCell($widthrecboxrecipient - 5, 4, $carac_client_name, 0, $ltrdirection);

				$posy = $pdf->getY();

				// Show recipient information
				$pdf->SetFont('', '', $default_font_size - 1);
				$pdf->writeHTMLCell($widthrecboxrecipient - 5, 4, $posx + 2, $posy, $carac_client, 0, 2, 0, true, $ltrdirection, true);
			} elseif ($arrayidcontact = $object->getIdContact('external', 'CUSTOMER')) {
				// If CUSTOMER contact defined, we use it
				$usecontact = false;
				$arrayidcontact = $object->getIdContact('external', 'CUSTOMER');
				if (count($arrayidcontact) > 0) {
					$usecontact = true;
					$result = $object->fetch_contact($arrayidcontact[0]);
				}

				// Recipient name
				// On peut utiliser le nom de la societe du contact
				if ($usecontact && !empty($conf->global->MAIN_USE_COMPANY_NAME_OF_CONTACT)) {
					$thirdparty = $object->contact;
				} else {
					$thirdparty = $object->thirdparty;
				}
				$carac_client_name = pdfBuildThirdpartyName($thirdparty, $outputlangs);

				// Recipient address
				$carac_client = pdf_element_build_address($outputlangs, $this->emetteur, $thirdparty, $object->contact, $usecontact, 'target', $object);

				// Show recipient
				$widthrecboxrecipient = $this->page_largeur - $this->marge_droite - $this->marge_gauche - $conf->global->ULTIMATE_WIDTH_RECBOX - 2;
				$posy = $logo_height + $this->marge_haute + 4;
				$posx = $this->page_largeur - $this->marge_droite - $widthrecboxrecipient;
				if (!empty($conf->global->ULTIMATE_INVERT_SENDER_RECIPIENT) && ($conf->global->ULTIMATE_INVERT_SENDER_RECIPIENT != "no")) $posx = $this->marge_gauche;

				// Show recipient frame
				$pdf->SetTextColorArray($textcolor);
				$pdf->SetFont('', '', $default_font_size - 2);

				// Show customer address
				$pdf->SetAlpha($opacity);
				$pdf->RoundedRect($posx, $posy, $widthrecboxrecipient, $hautcadre, $roundradius, '1111', $receiptstyle, $this->border_style, $bgcolor);
				$pdf->SetAlpha(1);
				$pdf->SetXY($posx, $posy);
				$pdf->MultiCell($widthrecboxrecipient, 4, $outputlangs->transnoentities("TypeContact_order_supplier_external_CUSTOMER"), 0, 'R');

				// Show recipient name
				$pdf->SetXY($posx + 2, $posy + 3);
				$pdf->SetFont('', 'B', $default_font_size);
				$pdf->MultiCell($widthrecboxrecipient - 5, 4, $carac_client_name, 0, $ltrdirection);

				$posy = $pdf->getY();

				// Show recipient information
				$pdf->SetFont('', '', $default_font_size - 1);
				$pdf->writeHTMLCell($widthrecboxrecipient - 5, 4, $posx + 2, $posy, $carac_client, 0, 2, 0, true, $ltrdirection, true);
			} else {
				$thirdparty = $object->thirdparty;
				// Recipient name
				$carac_client_name = pdfBuildThirdpartyName($thirdparty, $outputlangs);
				// Recipient address
				$carac_client = pdf_element_build_address($outputlangs, $this->emetteur, $object->thirdparty, '', 0, 'target', $object);

				// Show recipient
				$widthrecboxrecipient = $this->page_largeur - $this->marge_droite - $this->marge_gauche - $conf->global->ULTIMATE_WIDTH_RECBOX - 2;
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

				// Show recipient name
				$pdf->SetXY($posx + 2, $posy + 3);
				$pdf->SetFont('', 'B', $default_font_size);
				$pdf->MultiCell($widthrecboxrecipient - 5, 4, $carac_client_name, 0, $ltrdirection);

				$posy = $pdf->getY();

				// Show recipient information
				$pdf->SetFont('', '', $default_font_size - 1);
				$pdf->writeHTMLCell($widthrecboxrecipient - 5, 4, $posx + 2, $posy, $carac_client, 0, 2, 0, true, $ltrdirection, true);
			}

			// Other informations
			$pdf->SetFillColor(255, 255, 255);

			// Order date
			if (!empty($conf->global->ULTIMATE_SUPPLIERORDERS_PDF_SHOW_PROJECT) || !empty($conf->global->ULTIMATE_SUPPLIERORDERS_PDF_SHOW_PROJECT_TITLE)) {
				$width = $tab_width / 5 - 1.5;
			} else {
				$width = $tab_width / 4 - 1.5;
			}
			$RoundedRectHeight = $this->marge_haute + $logo_height + $hautcadre + 6;
			$pdf->SetAlpha($opacity);
			$pdf->RoundedRect($this->marge_gauche, $RoundedRectHeight, $width, 6, $roundradius, '1001', 'FD', $this->border_style, $bgcolor);
			$pdf->SetAlpha(1);
			$pdf->SetFont('', 'B', $default_font_size - 2);
			$pdf->SetXY($this->marge_gauche, $RoundedRectHeight);
			$pdf->SetTextColorArray($textcolor);
			$pdf->MultiCell($width, 5, $outputlangs->transnoentities("OrderDate"), 0, 'C', false);

			if (!empty($object->date_commande)) {
				$pdf->SetFont('', '', $default_font_size - 2);
				$pdf->SetXY($this->marge_gauche, $RoundedRectHeight + 6);
				$pdf->SetTextColorArray($textcolor);
				$pdf->SetFillColor(255, 255, 255);
				$pdf->MultiCell($width, 6, dol_print_date($object->date_commande, "day", false, $outputlangs, true), '0', 'C');
			} else {
				$pdf->SetFont('', '', $default_font_size - 2);
				$pdf->SetXY($this->marge_gauche, $RoundedRectHeight + 6);
				$pdf->SetTextColorArray($textcolor);
				$pdf->SetFillColor(255, 255, 255);
				$pdf->MultiCell($width, 6, $outputlangs->transnoentities("OrderToProcess"), '', 'C');
			}

			// Date Delivery Planned
			$pdf->SetAlpha($opacity);
			$pdf->RoundedRect($this->marge_gauche + $width + 2, $RoundedRectHeight, $width, 6, $roundradius, '1001', 'FD', $this->border_style, $bgcolor);
			$pdf->SetAlpha(1);
			$pdf->SetFont('', 'B', $default_font_size - 2);
			$pdf->SetXY($this->marge_gauche + $width + 2, $RoundedRectHeight);
			$pdf->SetTextColorArray($textcolor);
			$pdf->MultiCell($width, 5, $outputlangs->transnoentities("DateDeliveryPlanned"), 0, 'C', false);

			if (!empty($object->date_livraison)) {
				$pdf->SetFont('', '', $default_font_size - 2);
				$pdf->SetXY($this->marge_gauche + $width + 2, $RoundedRectHeight + 6);
				$pdf->SetTextColorArray($textcolor);
				$pdf->SetFillColor(255, 255, 255);
				$pdf->MultiCell($width, 6, dol_print_date($object->date_livraison, "day", false, $outputlangs, true), '', 'C');
			} else {
				$pdf->SetFont('', '', $default_font_size - 2);
				$pdf->SetXY($this->marge_gauche + $width + 2, $RoundedRectHeight + 6);
				$pdf->SetTextColorArray($textcolor);
				$pdf->SetFillColor(255, 255, 255);
				$pdf->MultiCell($width, 6, '', '', 'C');
			}

			// Auteur/Demandeur
			$pdf->SetAlpha($opacity);
			$pdf->RoundedRect($this->marge_gauche + $width * 2 + 4, $RoundedRectHeight, $width, 6, $roundradius, '1001', 'FD', $this->border_style, $bgcolor);
			$pdf->SetAlpha(1);
			$pdf->SetFont('', 'B', $default_font_size - 2);
			$pdf->SetXY($this->marge_gauche + $width * 2 + 4, $RoundedRectHeight);
			$pdf->SetTextColorArray($textcolor);
			$pdf->MultiCell($width, 5, $outputlangs->transnoentities("AuthorRequest"), 0, 'C', false);

			if (!empty($object->user_author_id)) {
				$object->fetch_user($object->user_author_id);
				$pdf->SetFont('', '', $default_font_size - 2);
				$pdf->SetXY($this->marge_gauche + $width * 2 + 4, $RoundedRectHeight + 6);
				$pdf->SetTextColorArray($textcolor);
				$pdf->MultiCell($width, 7, $object->user->firstname . ' ' . $object->user->lastname, 0, 'C', false);
				$pdf->SetTextColorArray($textcolor);
				$text = '<div style="line-height:90%;">' . $object->user->office_phone . '</div>';
				$pdf->writeHTMLCell($width, 5, $this->marge_gauche + $width * 2 + 4, $RoundedRectHeight + 9, $text, 0, 0, false, true, 'C', true);
			} else {
				$pdf->SetFont('', '', $default_font_size - 2);
				$pdf->SetXY($this->marge_gauche + $width * 2 + 4, $RoundedRectHeight + 6);
				$pdf->SetTextColorArray($textcolor);
				$pdf->SetFillColor(255, 255, 255);
				$pdf->MultiCell($width, 6, 'NR', '0', 'C');
			}

			// Supplier code
			$pdf->SetAlpha($opacity);
			$pdf->RoundedRect($this->marge_gauche + $width * 3 + 6, $RoundedRectHeight, $width, 6, $roundradius, '1001', 'FD', $this->border_style, $bgcolor);
			$pdf->SetAlpha(1);
			$pdf->SetFont('', 'B', $default_font_size - 2);
			$pdf->SetXY($this->marge_gauche + $width * 3 + 6, $RoundedRectHeight);
			$pdf->SetTextColorArray($textcolor);
			$pdf->MultiCell($width, 5, $outputlangs->transnoentities("SupplierCode"), 0, 'C', false);

			if (!empty($object->thirdparty->code_fournisseur)) {
				$pdf->SetFont('', '', $default_font_size - 2);
				$pdf->SetXY($this->marge_gauche + $width * 3 + 6, $RoundedRectHeight + 6);
				$pdf->SetTextColorArray($textcolor);
				$pdf->MultiCell($width, 6, $object->thirdparty->code_fournisseur, '0', 'C');
			} else {
				$pdf->SetFont('', '', $default_font_size - 2);
				$pdf->SetXY($this->marge_gauche + $width * 3 + 6, $RoundedRectHeight + 6);
				$pdf->SetTextColorArray($textcolor);
				$pdf->SetFillColor(255, 255, 255);
				$pdf->MultiCell($width, 6, 'NR', '0', 'C');
			}

			// Project ref
			if (!empty($conf->global->ULTIMATE_SUPPLIERORDERS_PDF_SHOW_PROJECT)) {
				$pdf->SetAlpha($opacity);
				$pdf->RoundedRect($this->marge_gauche + $width * 4 + 8, $RoundedRectHeight, $width, 6, $roundradius, '1001', 'FD', $this->border_style, $bgcolor);
				$pdf->SetAlpha(1);
				$pdf->SetFont('', 'B', $default_font_size - 2);
				$pdf->SetXY($this->marge_gauche + $width * 4 + 8, $RoundedRectHeight);
				$pdf->SetTextColorArray($textcolor);
				$pdf->MultiCell($width, 5, $outputlangs->transnoentities("Project"), 0, 'C', false);

				$object->fetch_projet();
				if (!empty($object->project->ref)) {
					$langs->load('projects');
					$pdf->SetTextColorArray($textcolor);
					$pdf->SetFont('', '', $default_font_size - 2);
					$pdf->SetXY($this->marge_gauche + $width * 4 + 8, $RoundedRectHeight + 6);
					$pdf->MultiCell($width, 6, (empty($object->project->ref) ? '' : $object->projet->ref), 0, 'C');
				}
			}
			elseif (!empty($conf->global->ULTIMATE_SUPPLIERORDERS_PDF_SHOW_PROJECT_TITLE)) {
				$pdf->SetAlpha($opacity);
				$pdf->RoundedRect($this->marge_gauche + $width * 4 + 8, $RoundedRectHeight, $width, 6, $roundradius, '1001', 'FD', $this->border_style, $bgcolor);
				$pdf->SetAlpha(1);
				$pdf->SetFont('', 'B', $default_font_size - 2);
				$pdf->SetXY($this->marge_gauche + $width * 4 + 8, $RoundedRectHeight);
				$pdf->SetTextColorArray($textcolor);
				$pdf->MultiCell($width, 5, $outputlangs->transnoentities("Project"), 0, 'C', false);

				$object->fetch_projet();
				if (!empty($object->project->ref)) {
					$langs->load('projects');
					$pdf->SetTextColorArray($textcolor);
					$pdf->SetFont('', '', $default_font_size - 2);
					$pdf->SetXY($this->marge_gauche + $width * 4 + 8, $RoundedRectHeight + 6);
					$pdf->MultiCell($width, 6, (empty($object->project->title) ? '' : $object->projet->title), 0, 'C');
				}
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
	 *      @return	void
	 */
	function _pagefoot(&$pdf, $object, $outputlangs, $hidefreetext = 0)
	{
		global $conf;
		$showdetails = $conf->global->MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS;
		return pdf_ultimatepagefoot($pdf, $outputlangs, 'SUPPLIER_INVOICE_FREE_TEXT', $this->emetteur, $this->marge_basse, $this->marge_gauche, $this->page_hauteur, $object, $showdetails, $hidefreetext, $this->footertextcolor);
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
			$pdf->SetFont('DejaVuSans', '', $default_font_size - 1);
		} else {
			$pdf->SetFont($conf->global->MAIN_PDF_FORCE_FONT, '', $default_font_size - 1);
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
		$this->defaultTitlesFieldsStyle = array(
			'align' => 'C', // R,C,L
			'padding' => array(0.5, 0, 0.5, 0), // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
		);

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
		if (!empty($conf->global->ULTIMATE_SUPPLIERORDERS_WITH_LINE_NUMBER)) {
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
				'align' => 'C',
			),
			'border-left' => false, // remove left line separator
		);

		if ($conf->global->ULTIMATE_DOCUMENTS_WITH_REF == "yes" && $this->atleastoneref == true) {
			$this->cols['ref']['status'] = true;
		}
		if (!empty($conf->global->ULTIMATE_SUPPLIERORDERS_WITH_LINE_NUMBER) && $conf->global->ULTIMATE_PDF_BORDER_LEFT_STATUS == 'yes') {
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
				'padding' => array(1, 0.5, 1, 1), // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
			),
			'border-left' => false, // remove left line separator
		);

		if (!empty($conf->global->ULTIMATE_SUPPLIERORDERS_WITH_LINE_NUMBER && $conf->global->ULTIMATE_PDF_BORDER_LEFT_STATUS == 'yes') || ($conf->global->ULTIMATE_DOCUMENTS_WITH_REF == "yes" && $this->atleastoneref == true &&$conf->global->ULTIMATE_PDF_BORDER_LEFT_STATUS == 'yes')) {
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
				'padding' => array(0.5, 0, 0.5, 0), // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
			),
			'border-left' => false, // remove left line separator
		);

		if ($conf->global->ULTIMATE_GENERATE_SUPPLIERORDER_WITH_PICTURE == 1 && $this->atleastonephoto = true) {
			$this->cols['photo']['status'] = true;
		}

		$rank = $rank + 10;
		$this->cols['vat'] = array(
			'rank' => $rank,
			'status' => false,
			'width' => (empty($conf->global->ULTIMATE_DOCUMENTS_WITH_TVA_WIDTH) ? 14 : $conf->global->ULTIMATE_DOCUMENTS_WITH_TVA_WIDTH), // in mm  
			'title' => array(
				'textkey' => 'VAT'
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

		if (!empty($conf->global->ULTIMATE_GENERATE_SUPPLIERORDERS_WITH_PRICEUHT)) {
			$this->cols['subprice']['status'] = true;
		}
		if ($conf->global->ULTIMATE_PDF_BORDER_LEFT_STATUS == 'yes') {
			$this->cols['subprice']['border-left'] = true;
		}

		$rank = $rank + 10;
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

		if ($this->atleastonediscount && !empty($conf->global->ULTIMATE_GENERATE_SUPPLIERORDERS_WITH_DISCOUNT)) {
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
		if ($this->atleastonediscount && !empty($conf->global->ULTIMATE_GENERATE_SUPPLIERORDERS_WITH_PUAFTER)) {
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

		if (!empty($conf->global->ULTIMATE_GENERATE_SUPPLIERORDERS_WITH_QTY)) {
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
				'align' => 'R'
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
	 // phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
    /**
     *	Show area for the customer to sign
     *
     *	@param	TCPDF		$pdf            Object PDF
     *	@param  Propal		$object         Object invoice
     *	@param	int			$posy			Position depart
     *	@param	Translate	$outputlangs	Objet langs
     *	@return int							Position pour suite
     */
	protected function _signature_area_simple_or_double(&$pdf, $object, $posy, $outputlangs)
	{
		global $db, $langs, $conf;
		$outputlangs->load("propal");
		if (!empty($conf->mbisignature->enabled)) {
			dol_include_once("/mbisignature/class/signature_area.class.php");
			$signatureArea = new SignatureArea();
			return $signatureArea->_signature_area_simple_or_double($pdf, $object, $posy, $outputlangs, $db, $object->ref, $langs, $this->page_largeur, $this->marge_droite, $this->emetteur_name);
		}
	}
}

?>