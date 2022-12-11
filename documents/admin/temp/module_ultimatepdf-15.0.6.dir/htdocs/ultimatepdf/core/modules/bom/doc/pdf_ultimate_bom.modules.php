<?php
/* Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2008      Raphael Bertrand     <raphael.bertrand@resultic.fr>
 * Copyright (C) 2010      Juanjo Menent	    <jmenent@2byte.es>
 * Copyright (C) 2011-2022 Philippe Grand		<philippe.grand@atoo-net.com>
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
 * or see https://www.gnu.org/
 */

/**
 *	\file       htdocs/custom/ultimatepdf/core/modules/bom/doc/pdf_ultimate_bom.modules.php
 *	\ingroup    bom
 *	\brief      File of Class to generate PDF orders from pdf_ultimate_bom model 
 */

use CORE\FRAMEWORK\CommonObject;

require_once DOL_DOCUMENT_ROOT . '/core/modules/bom/modules_bom.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT . '/product/stock/class/entrepot.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/cunits.class.php';
require_once DOL_DOCUMENT_ROOT . '/bom/class/bom.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
dol_include_once('/ultimatepdf/lib/ultimatepdf.lib.php');
dol_include_once("/ultimatepdf/class/ultimateBarcode.trait.class.php");


/**
 *	Class to generate PDF bom with template ultimate_bom template
 */
class pdf_ultimate_bom extends ModelePDFBom
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
	 * @var boolean is there a message or not
	 */
	private $messageErrBarcodeSet;

	/**
	 * @var array of document table columns
	 */
	public $cols;


	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		global $conf, $langs, $mysoc;

		// Translations
		$langs->loadLangs(array("main", "bom", "products", "ultimatepdf@ultimatepdf"));

		$this->db = $db;
		$this->name = "ultimate_bom";
		$this->description = $langs->trans('PDFUltimate_bomDescription');
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
		$this->option_codeproduitservice = 1;      // Display product-service code
		$this->option_multilang = 1;               // Available in several languages
		$this->option_freetext = 1;				   // Support add of a personalised text

		// Get source company
		$this->emetteur = $mysoc;
		if (empty($this->emetteur->country_code)) {
			$this->emetteur->country_code = substr($langs->defaultlang, -2); // By default, if was not defined
		}

		$dashdotted =  (!empty($conf->global->ULTIMATE_DASH_DOTTED)) ?
		$conf->global->ULTIMATE_DASH_DOTTED : 0;

		$bordercolor =  (!empty($conf->global->ULTIMATE_BORDERCOLOR_COLOR)) ?
		html2rgb($conf->global->ULTIMATE_BORDERCOLOR_COLOR) : array(0, 63, 127);
			
		$this->border_style = array('width' => 0.2, 'cap' => 'butt', 'join' => 'miter', 'dash' => $dashdotted, 'color' => $bordercolor);

		$this->tabTitleHeight = 8; // default height
		//  Use new system for position of columns, view $this->defineColumnField()
		$this->atleastoneref = 0;
		$this->atleastonephoto = false;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Function to build pdf onto disk
	 *
	 *  @param		BOM			$object				Object to generate
	 *  @param		Translate	$outputlangs		Lang output object
	 *  @param		string		$srctemplatepath	Full path of source filename for generator using a template file
	 *  @param		int			$hidedetails		Do not show line details
	 *  @param		int			$hidedesc			Do not show desc
	 *  @param		int			$hideref			Do not show ref
	 *  @return     int             				1=OK, 0=KO
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
		$outputlangs->loadLangs(array("main", "companies", "bom", "products", "errors", "ultimatepdf@ultimatepdf"));

		$nblines = count($object->lines);

		$hidetop = 0;
		if (!empty($conf->global->MAIN_PDF_DISABLE_COL_HEAD_TITLE)) {
			$hidetop = $conf->global->MAIN_PDF_DISABLE_COL_HEAD_TITLE;
		}

		// Loop on each lines to detect if there is at least one image to show
		$realpatharray = array();
		$this->atleastonephoto = false;
		if (!empty($conf->global->ULTIMATE_GENERATE_BOMS_WITH_PICTURE)) {
			$objphoto = new Product($this->db);

			for ($i = 0; $i < $nblines; $i++) {
				if (empty($object->lines[$i]->fk_product)) {
					continue;
				}

				$objphoto->fetch($object->lines[$i]->fk_product);

				if (!empty($conf->global->PRODUCT_USE_OLD_PATH_FOR_PHOTO)) {
					$pdir[0] = get_exdir($objphoto->id, 2, 0, 0, $objphoto, 'product') . $objphoto->id . "/photos/";
					$pdir[1] = get_exdir(0, 0, 0, 0, $objphoto, 'product').dol_sanitizeFileName($objphoto->ref).'/';
				} else {
					$pdir[0] = get_exdir(0, 0, 0, 0, $objphoto, 'product'); // default
					$pdir[1] = get_exdir($objphoto->id, 2, 0, 0, $objphoto, 'product') . $objphoto->id . "/photos/"; // alternative
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
							if (empty($conf->global->CAT_HIGH_QUALITY_IMAGES)) {		// If CAT_HIGH_QUALITY_IMAGES not defined, we use thumb if defined and then original photo
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

		if ($conf->bom->multidir_output[$conf->entity]) {

			// Definition of $dir and $file
			if ($object->specimen) {
				$dir = $conf->bom->multidir_output[$conf->entity];
				$file = $dir . "/SPECIMEN.pdf";
			} else {
				$objectref = dol_sanitizeFileName($object->ref);
				$dir = $conf->bom->multidir_output[$conf->entity] . "/" . $objectref;
				$file = $dir . "/" . $objectref . ".pdf";
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
				// Set nblines with the new command lines content after hook
				$nblines = count($object->lines);
				// Create pdf instance
				$pdf = pdf_getInstance($this->format);
				$default_font_size = pdf_getPDFFontSize($outputlangs);  // Must be after pdf_getInstance

				$pdf->SetAutoPageBreak(1, 0);

				$heightforinfotot = 40;   // Height reserved to output the info and total part

				$heightforfreetext = (isset($conf->global->MAIN_PDF_FREETEXT_HEIGHT) ? $conf->global->MAIN_PDF_FREETEXT_HEIGHT : 5);	// Height reserved to output the free text on last page
				$heightforfooter = $this->marge_basse + (empty($conf->global->MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS) ? 12 : 22); // Height reserved to output the footer (value include bottom margin)
				if (class_exists('TCPDF')) {
					$pdf->setPrintHeader(false);
					$pdf->setPrintFooter(false);
				}
				$pdf->SetFont(pdf_getPDFFont($outputlangs));

				// Set path to the background PDF File
				if (!empty($conf->global->ULTIMATEPDF_ADD_PDF_BACKGROUND) && ($conf->global->ULTIMATE_DESIGN)) {
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
				$pdf->SetSubject($outputlangs->transnoentities("BillOfMaterials"));
				$pdf->SetCreator("Dolibarr " . DOL_VERSION);
				$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
				$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref) . " " . $outputlangs->transnoentities("BillOfMaterials") . " " . $outputlangs->convToOutputCharset($object->thirdparty->name));
				if (!empty($conf->global->MAIN_DISABLE_PDF_COMPRESSION)) {
					$pdf->SetCompression(false);
				}

				$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right

				// Set $this->atleastoneref if man have at least one ref 
				for ($i = 0; $i < $nblines; $i++) {
					if ($object->lines[$i]->product_ref) {
						$this->atleastoneref++;
					}
				}

				// New page
				$pdf->AddPage();
				if (!empty($tplidx)) {
					$pdf->useTemplate($tplidx);
				}

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

				$this->_pagehead($pdf, $object, 1, $outputlangs, $titlekey);

				$pdf->SetFont('', '', $default_font_size - 2);
				$pdf->MultiCell(0, 3, '');		// Set interline to 3
				$pdf->SetTextColorArray($textcolor);

				$tab_top = $this->marge_haute + $logo_height + 14;

				$tab_top_newpage = (empty($conf->global->ULTIMATE_BOMS_PDF_DONOTREPEAT_HEAD) ? $this->marge_haute + $logo_height + 10 : 18);

				$tab_width = $this->page_largeur - $this->marge_gauche - $this->marge_droite;
				if ($roundradius == 0) {
					$roundradius = 0.1; //RoundedRect don't support $roundradius to be 0
				}

				// Display notes
				$notetoshow = empty($object->note_public) ? '' : $object->note_public;
				if (!empty($conf->global->MAIN_ADD_SALE_REP_SIGNATURE_IN_ORDER_NOTE)) {
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

				if (!empty($conf->global->MAIN_ADD_CREATOR_IN_NOTE) && $object->user_author_id > 0) {
					$tmpuser = new User($this->db);
					$tmpuser->fetch($object->user_author_id);
					$notetoshow .= '<br>' . $langs->trans("CaseFollowedBy") . ' ' . $tmpuser->getFullName($langs);
					if ($tmpuser->email) {
						$notetoshow .= ',  Mail: '.$tmpuser->email;
					}
					if ($tmpuser->office_phone) {
						$notetoshow .= ', Tel: '.$tmpuser->office_phone;
					}
				}

				$tab_height = $this->page_hauteur - $tab_top_newpage - $heightforinfotot - $heightforfreetext - $heightforfooter;

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

					// Description
					$description = $object->description;
					$notetoshow = dol_concatdesc($notetoshow, $description);
					$pdf->writeHTMLCell($tab_width, 3, $this->marge_gauche + 1, $tab_top, dol_htmlentitiesbr($notetoshow), 0, 1);
					// Description
					$pageposafternote = $pdf->getPage();
					$posyafter = $pdf->GetY();

					$pdf->commitTransaction();

					$height_note = $posyafter - $tab_top;

					$pdf->RoundedRect($this->marge_gauche, $tab_top, $tab_width, $height_note + 1, $roundradius, '1111', 'S', $this->border_style, $bgcolor);

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
					$tab_top += 10;
				}

				$curY = $tab_top + $this->tabTitleHeight + 2;
				if (empty($conf->global->MAIN_PDF_DISABLE_COL_HEAD_TITLE)) {
					$nexY = $tab_top + $this->tabTitleHeight - 8;
				} else {
					$nexY = $tab_top + $this->tabTitleHeight;
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
					$posYAfterDescription = 0;

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
							if (!empty($conf->global->MAIN_PDF_DATA_ON_FIRST_PAGE)) {
								$showpricebeforepagebreak = 1;
							} else {
								$showpricebeforepagebreak = 0;
							}
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
					} else {
						$curX = $this->getColumnContentXStart('desc');
					}
					if ($conf->milestone->enabled && $object->lines[$i]->product_type == 9 && $object->lines[$i]->pagebreak == true) {
						$curY = $tab_top_newpage + 1;
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

						$pdf->writeHTMLCell($this->getColumnContentXStart('desc'), 4, $curX, $curY, $product->label, 0, 1, false, true, 'L', true);
						$posYAfterDescription = $pdf->GetY();

						if (!empty($conf->global->ULTIMATE_PRODUCT_ENABLE_CUSTOMCOUNTRYCODE) && $object->lines[$i]->product_type != 9 && $object->lines[$i]->product_type != 1) {
							// dysplay custom and country code
							$posy = $this->ultimatecustomcode($pdf, $product, $outputlangs);
							$posYAfterDescription = $pdf->GetY();
						}
						$pageposafter = $pdf->getPage();

						if (!empty($product->barcode) && !empty($product->barcode_type_code) && $object->lines[$i]->product_type != 9 && $conf->global->ULTIMATEPDF_GENERATE_BOMS_WITH_PRODUCTS_BARCODE == 1) {
							// dysplay product barcode
							$posy = $this->ultimatebarcode($pdf, $product);
							$posYAfterDescription = $pdf->GetY();
						}
						$pageposafter = $pdf->getPage();
						
						if ($pageposafter > $pageposbefore)	// There is a pagebreak
						{
							$posYAfterImage = $tab_top_newpage + $imglinesize['height'];
							$pdf->rollbackTransaction(true);
							
							$pageposafter = $pageposbefore;
							$pdf->setPageOrientation('', 1, $heightforfooter);	// The only function to edit the bottom margin of current page to set it.
							$pdf->writeHTMLCell($this->getColumnContentXStart('desc'), 4, $curX, $curY, $product->label, 0, 1, false, true, 'L', true);
							$posYAfterDescription = $pdf->GetY();
							if (!empty($conf->global->ULTIMATE_PRODUCT_ENABLE_CUSTOMCOUNTRYCODE) && $object->lines[$i]->product_type != 9 && $object->lines[$i]->product_type != 1) {
								// dysplay custom and country code
							$posy = $this->ultimatecustomcode($pdf, $product, $outputlangs);
							$posYAfterDescription = $pdf->GetY();
							}
							$pageposafter = $pdf->getPage();

							if (!empty($product->barcode) && !empty($product->barcode_type_code) && $object->lines[$i]->product_type != 9 && $conf->global->ULTIMATEPDF_GENERATE_BOMS_WITH_PRODUCTS_BARCODE == 1) {
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
						$posYAfterDescription = $pdf->GetY();
					}
					$nexY = max($pdf->GetY(), $posYAfterImage, $posYAfterBarcode);

					$pageposafter = $pdf->getPage();

					$pdf->setPage($pageposbefore);
					$pdf->setTopMargin($this->marge_haute);
					$pdf->setPageOrientation('', 1, 0);	// The only function to edit the bottom margin of current page to set it.

					// We suppose that a too long description or photo were moved completely on next page
					if ($pageposafter > $pageposbefore && empty($showpricebeforepagebreak)) {
						$pdf->setPage($pageposafter);
						$curY = $tab_top_newpage;
					}

					$pdf->SetFont('', '', $default_font_size - 2);   // We reposition the default font

					/*if ($curY + 4 > ($this->page_hauteur - $heightforfooter)) {
						$pdf->setPage($pageposafter);
						$curY = $tab_top_newpage;
					}*/

					//Line numbering
					if (!empty($conf->global->ULTIMATE_BOMS_WITH_LINE_NUMBER)) {
						// Numbering
						if ($this->getColumnStatus('num') && array_key_exists($i, $object->lines) && $object->lines[$i]->product_type != 9) {
							$this->printStdColumnContent($pdf, $curY, 'num', $line_number);
							$nexY = max($pdf->GetY(), $nexY);
							$line_number++;
						}
					}

					//  Column reference
					if ($conf->global->ULTIMATE_DOCUMENTS_WITH_REF == "yes") {
						if ($this->getColumnStatus('ref')) {
							$productRef = $product->ref;
							$this->printStdColumnContent($pdf, $curY, 'ref', $productRef);
							$nexY = max($pdf->GetY(), $nexY);
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
					if ($conf->global->ULTIMATE_GENERATE_BOMS_WITH_WEIGHT_COLUMN) {
						if ($this->getColumnStatus('weight')) {
							$weight = pdf_getlineweight($object, $i, $outputlangs, $hidedetails);
							$this->printStdColumnContent($pdf, $curY, 'weight', $weight);
							$nexY = max($pdf->GetY(), $nexY);
						}
					}

					// Unit
					if ($this->getColumnStatus('unit') && $object->lines[$i]->product_type != 9) {
						$unit = new CUnits($db);
						$unit->fetch($product->fk_unit);
						$unit = $unit->short_label;
						$this->printStdColumnContent($pdf, $curY, 'unit', $unit);
						$nexY = max($pdf->GetY(), $nexY);					
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

					if ($posYAfterImage > $posYAfterDescription) $nexY = $posYAfterImage;

					// Add line
					if (!empty($conf->global->ULTIMATE_BOMS_PDF_DASH_BETWEEN_LINES) && $i < ($nblines - 1) && $object->lines[$i]->product_type != 9 && $object->lines[$i + 1]->product_type != 9 && !($object->lines[$i + 1]->pagebreak == true)) {
						$pdf->setPage($pageposafter);
						$pdf->SetLineStyle(array('dash' => '1, 1', 'color' => array(70, 70, 70)));
						if ($conf->global->ULTIMATEPDF_GENERATE_BOMS_WITH_PRODUCTS_BARCODE == 1 || !empty($conf->global->ULTIMATE_PRODUCT_ENABLE_CUSTOMCOUNTRYCODE)) {
							$pdf->line($this->marge_gauche, $nexY + 6, $this->page_largeur - $this->marge_droite, $nexY + 6);
						} else {
						$pdf->line($this->marge_gauche, $nexY + 1, $this->page_largeur - $this->marge_droite, $nexY + 1);
						}
						$pdf->SetLineStyle(array('dash' => 0));
					}

					if (!empty($conf->global->ULTIMATEPDF_GENERATE_BOMS_WITH_PRODUCTS_BARCODE == 1 || $conf->global->ULTIMATE_PRODUCT_ENABLE_CUSTOMCOUNTRYCODE)) {
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
						if (empty($conf->global->ULTIMATE_BOMS_PDF_DONOTREPEAT_HEAD)) {
							$this->_pagehead($pdf, $object, 0, $outputlangs, $titlekey);
						}
					}

					if (isset($object->lines[$i + 1]->pagebreak) && $object->lines[$i + 1]->pagebreak) {
						if ($pagenb == $pageposafter && $pagenb != $pageposbeforeprintlines) {
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
						if (empty($conf->global->ULTIMATE_BOMS_PDF_DONOTREPEAT_HEAD)) {
							$this->_pagehead($pdf, $object, 0, $outputlangs, $titlekey);
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

				// Pied de page
				$this->_pagefoot($pdf, $object, $outputlangs);
				if (method_exists($pdf, 'AliasNbPages')) {
					$pdf->AliasNbPages();
				}

				$pdf->Close();

				$pdf->Output($file, 'F');

				//	Add pdfgeneration hook
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
			$this->error = $langs->transnoentities("ErrorConstantNotDefined", "BOM_OUTPUTDIR");
			return 0;
		}
		$this->error = $langs->transnoentities("ErrorUnknown");

		unset($_SESSION['ultimatepdf_model']);

		return 0;   // Erreur par defaut
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *   Show miscellaneous information (payment mode, payment term, ...)
	 *
	 *   @param		TCPDF		$pdf     		Object PDF
	 *   @param		Commande	$object			Object to show
	 *   @param		int			$posy			Y
	 *   @param		Translate	$outputlangs	Langs object
	 *   @return	int							Pos y
	 */
	function _tableau_info(&$pdf, $object, $posy, $outputlangs)
	{
		// phpcs:enable
		global $conf, $mysoc;

		$default_font_size = pdf_getPDFFontSize($outputlangs);
		
		$textcolor =  (!empty($conf->global->ULTIMATE_TEXTCOLOR_COLOR)) ?
		html2rgb($conf->global->ULTIMATE_TEXTCOLOR_COLOR) : array(25, 25, 25);

		$pdf->SetFont('', '', $default_font_size - 1);

		$widthrecbox = ($this->page_largeur - $this->marge_gauche - $this->marge_droite - 4) / 2;
		
		// Example using extrafields for new_line
		$extrafields = new ExtraFields($this->db);
		$extralabels = $extrafields->fetch_name_optionals_label($object->table_element, true);
		if (!empty($this->rowid)) {
			$object->fetch($this->rowid);
		}
		$object->fetch_optionals(!empty($this->rowid), $extralabels);
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
		$pdf->SetFont('', '', $default_font_size - 3);
		$pdf->SetTextColorArray($textcolor);
		$pdf->writeHTMLCell($widthrecbox, 4, $this->marge_gauche, $posy, $title, 0, 0, false, true, 'L', true);

		$posy = $pdf->GetY() + 7;
		return $posy;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
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
	 *   @return	void
	 */
	function _tableau(&$pdf, $tab_top, $tab_height, $nexY, $outputlangs, $hidetop = 0, $hidebottom = 0)
	{
		global $conf;

		$outputlangs->load("ultimatepdf@ultimatepdf");

		// Force to disable hidetop
		if ($hidetop) {
			$hidetop = -1;
		}

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
        $pdf->SetFont($conf->global->MAIN_PDF_FORCE_FONT, '', $default_font_size - 2);
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

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
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
	protected function _pagehead(&$pdf, $object, $showaddress, $outputlangs, $titlekey)
	{
		global $conf;

		$ltrdirection = 'L';
		if ($outputlangs->trans("DIRECTION") == 'rtl') $ltrdirection = 'R';

		// Translations
		$outputlangs->loadLangs(array("main", "dict", "companies", "other", "propal", "orders", "mrp", "commercial", "projects", "ultimatepdf@ultimatepdf"));

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		$dashdotted =  (!empty($conf->global->ULTIMATE_DASH_DOTTED)) ?
		$conf->global->ULTIMATE_DASH_DOTTED : 0;

		$bgcolor =  (!empty($conf->global->ULTIMATE_BGCOLOR_COLOR)) ?
		html2rgb($conf->global->ULTIMATE_BGCOLOR_COLOR) : array(170, 212, 255);

		$senderstyle =  (!empty($conf->global->ULTIMATE_SENDER_STYLE)) ?
		$conf->global->ULTIMATE_SENDER_STYLE : 'S';

		$bordercolor =  (!empty($conf->global->ULTIMATE_BORDERCOLOR_COLOR)) ?
		html2rgb($conf->global->ULTIMATE_BORDERCOLOR_COLOR) : array(0, 63, 127);
		
		$opacity =  (!empty($conf->global->ULTIMATE_BGCOLOR_OPACITY)) ?
		$conf->global->ULTIMATE_BGCOLOR_OPACITY : 0.5;

		$roundradius =  (!empty($conf->global->ULTIMATE_SET_RADIUS)) ?
		$conf->global->ULTIMATE_SET_RADIUS : 1;

		$textcolor =  (!empty($conf->global->ULTIMATE_TEXTCOLOR_COLOR)) ?
		html2rgb($conf->global->ULTIMATE_TEXTCOLOR_COLOR) : array(25, 25, 25);

		if (!empty($conf->global->ULTIMATE_QRCODECOLOR_COLOR)) {
			$qrcodecolor = html2rgb($conf->global->ULTIMATE_QRCODECOLOR_COLOR);
		}
		$this->border_style = array('width' => 0.2, 'cap' => 'butt', 'join' => 'miter', 'dash' => $dashdotted, 'color' => $bordercolor);
		$tab_width = $this->page_largeur - $this->marge_gauche - $this->marge_droite;
		$posy = $this->marge_haute;
		//affiche repere de pliage	
		if (!empty($conf->global->MAIN_DISPLAY_ORDERS_FOLD_MARK)) {
			$pdf->Line(0, ($this->page_hauteur) / 3, 3, ($this->page_hauteur) / 3);
		}

		pdf_new_pagehead($pdf, $outputlangs, $this->page_hauteur);

		//Show Draft Watermark
		if ($object->statut == 0 && (!empty($conf->global->COMMANDE_DRAFT_WATERMARK))) {
			pdf_watermark($pdf, $outputlangs, $this->page_hauteur, $this->page_largeur, 'mm', $conf->global->COMMANDE_DRAFT_WATERMARK);
		}

		//Prepare la suite
		$pdf->SetTextColorArray($textcolor);
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
		if (!empty($conf->global->ULTIMATEPDF_GENERATE_BOMS_WITH_TOP_BARCODE)) {
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
		$posxQRcode = $tab_width / 2;
		// set style for QRcode
		$styleQr = array(
			'border' => false,
			'vpadding' => 'auto',
			'hpadding' => 'auto',
			'fgcolor' => $qrcodecolor,
			'bgcolor' => false, //array(255,255,255)
			'module_width' => 1, // width of a single module in points
			'module_height' => 1 // height of a single module in points
		);
		// Order link QRcode
		if (!empty($conf->global->ULTIMATEPDF_GENERATE_ORDERLINK_WITH_TOP_QRCODE)) {
			$code = pdf_codeOrderLink(); //get order link
			$pdf->write2DBarcode($code, 'QRCODE,L', $posxQRcode, $posy, $heightQRcode, $heightQRcode, $styleQr, 'N');
		}

		// My Company QR-code
		if (!empty($conf->global->ULTIMATEPDF_GENERATE_BOMS_WITH_MYCOMP_QRCODE)) {
			$code = pdf_mycompCodeContents();
			$pdf->write2DBarcode($code, 'QRCODE,L', $posxQRcode, $posy, $heightQRcode, $heightQRcode, $styleQr, 'N');
		}

		if (!empty($conf->global->ULTIMATEPDF_GENERATE_BOMS_WITH_MYCOMP_QRCODE)) {
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
		$standardtitle = $outputlangs->transnoentities("Nomenclature BOM");
		$pdf->MultiCell($rightSideWidth, 4, $standardtitle, '', 'R');

		$pdf->SetFont('', 'B', $default_font_size + 2);

		$posy = $pdf->getY();
		$pdf->SetXY($posx, $posy);
		$pdf->SetTextColorArray($textcolor);
		$textref = $outputlangs->transnoentities("Ref")." : ".$outputlangs->convToOutputCharset($object->ref);
		if ($object->statut == $object::STATUS_DRAFT) {
			$pdf->SetTextColor(128, 0, 0);
			$textref .= ' - '.$outputlangs->transnoentities("NotValidated");
		}
		$pdf->MultiCell($rightSideWidth, 4, $textref, '', 'R');

		$posy = $pdf->getY();
		$pdf->SetFont('', '', $default_font_size - 1);
		$pdf->SetXY($posx, $posy);
		$pdf->SetTextColorArray($textcolor);
		//$pdf->MultiCell($rightSideWidth, 3, $outputlangs->transnoentities("OrderDate") . " : " . dol_print_date($object->date, "%d %b %Y", false, $outputlangs, true), '', 'R');

		$posy = $pdf->getY();

		if (!empty($conf->global->ULTIMATE_ORDERS_PDF_SHOW_PROJECT_REF)) {
			$object->fetch_projet();
			if (!empty($object->project->ref)) {
				$pdf->SetTextColorArray($textcolor);
				$pdf->SetFont('', '', $default_font_size - 1);
				$pdf->SetXY($posx, $posy);
				$pdf->writeHTMLCell($rightSideWidth, 3, $posx, $posy, $outputlangs->transnoentities("RefProject") . " : " . (empty($object->project->ref) ? '' : $object->projet->ref), 0, 1, false, true, 'R', true);
			}
		}

		$posy = $pdf->getY();

		if (!empty($conf->global->ULTIMATE_ORDERS_PDF_SHOW_PROJECT_TITLE)) {
			$object->fetch_projet();
			if (!empty($object->project->ref)) {
				$pdf->SetTextColorArray($textcolor);
				$pdf->SetFont('', '', $default_font_size - 1);
				$pdf->SetXY($posx, $posy);
				$pdf->MultiCell($rightSideWidth, 4, $outputlangs->transnoentities("Project") . " : " . (empty($object->project->title) ? '' : $object->projet->title), 0, 'R');
			}
		}

		$posy = $pdf->getY();

		// Example using extrafields
		$title_key = (empty($object->array_options['options_codesupplier'])) ? '' : ($object->array_options['options_codesupplier']);
		$extrafields = new ExtraFields($this->db);
		$extralabels = $extrafields->fetch_name_optionals_label($object->table_element, true);
		if (is_array($extralabels) && key_exists('codesupplier', $extralabels) && !empty($title_key)) {
			$title = $extrafields->showOutputField('codesupplier', $title_key);
			$posy = $pdf->getY();
			$pdf->SetXY($posx, $posy);
			$pdf->SetTextColorArray($textcolor);
			$pdf->MultiCell($rightSideWidth, 5, $title, 0, 'R');
		}

		$posy = $pdf->getY();

		$pdf->SetXY($posx, $posy);
	
		// Show list of linked objects
		$posy = pdf_writeLinkedObjects($pdf, $object, $outputlangs, $posx, $posy-3, $rightSideWidth, 3, 'R', $default_font_size);
		
		$posy = $pdf->getY();

		// Other informations
		$pdf->SetFillColor(255, 255, 255);

		// Label
		$width = $tab_width / 5 - 1.5;
		$RoundedRectHeight = $this->marge_haute + $logo_height + 6;
		$pdf->SetAlpha($opacity);
		$pdf->RoundedRect($this->marge_gauche, $RoundedRectHeight, $width, 6, $roundradius, '1001', 'FD', $this->border_style, $bgcolor);
		$pdf->SetAlpha(1);
		$pdf->SetFont('', 'B', $default_font_size - 2);
		$pdf->SetXY($this->marge_gauche, $RoundedRectHeight + 0.5);
		$pdf->SetTextColorArray($textcolor);
		$pdf->writeHTMLCell($width, 5, $this->marge_gauche, $RoundedRectHeight + 0.5, $outputlangs->transnoentities("Label"), 0, 0, false, true, 'C', true);

		if (!empty($object->label)) {
			$form = new Form($this->db);
			$form->load_cache_availability();
			$pdf->SetFont('', '', $default_font_size - 2);
			$pdf->SetXY($this->marge_gauche, $RoundedRectHeight + 6);
			$pdf->SetTextColorArray($textcolor);
			$pdf->writeHTMLCell($width, 6, $this->marge_gauche, $RoundedRectHeight + 6, $object->label, 0, 0, false, true, 'C', true);
		} else {
			$pdf->MultiCell($width, 6, '', '0', 'C');
		}

		// Type
		$pdf->SetAlpha($opacity);
		$pdf->RoundedRect($this->marge_gauche + $width + 2, $RoundedRectHeight, $width, 6, $roundradius, '1001', 'FD', $this->border_style, $bgcolor);
		$pdf->SetAlpha(1);
		$pdf->SetFont('', 'B', $default_font_size - 2);
		$pdf->SetXY($this->marge_gauche + $width + 2, $RoundedRectHeight);
		$pdf->SetTextColorArray($textcolor);
		$pdf->MultiCell($width, 5, $outputlangs->transnoentities("Type"), 0, 'C', false);

		$pdf->SetFont('', '', $default_font_size - 2);
		$pdf->SetXY($this->marge_gauche + $width + 2, $RoundedRectHeight + 6);
		$pdf->SetTextColorArray($textcolor);
		$pdf->SetFillColor(255, 255, 255);
		$pdf->MultiCell($width, 6, $outputlangs->convToOutputCharset($object->bomtype == 0 ? "Manufacturing" : "Disassemble"), '0', 'C');

		// Product
		$pdf->SetAlpha($opacity);
		$pdf->RoundedRect($this->marge_gauche + $width * 2 + 4, $RoundedRectHeight, $width, 6, $roundradius, '1001', 'FD', $this->border_style, $bgcolor);
		$pdf->SetAlpha(1);
		$pdf->SetFont('', 'B', $default_font_size - 2);
		$pdf->SetXY($this->marge_gauche + $width * 2 + 4, $RoundedRectHeight + 0.5);
		$pdf->SetTextColorArray($textcolor);
		$text = $outputlangs->transnoentities("Product");
		$pdf->writeHTMLCell($width, 5, $this->marge_gauche + $width * 2 + 4, $RoundedRectHeight + 0.5, $text, 0, 0, false, true, 'C', true);

		$staticObject = new Product($this->db);
		$staticObject->fetch($object->fk_product);
		$pdf->SetFont('', '', $default_font_size - 2);
		$pdf->SetXY($this->marge_gauche + $width * 2 + 4, $RoundedRectHeight + 6);
		$pdf->SetTextColorArray($textcolor);
		$pdf->writeHTMLCell($width, 5, $this->marge_gauche + $width * 2 + 4, $RoundedRectHeight + 6, $staticObject->ref. '<br>' .$outputlangs->convToOutputCharset($staticObject->label), 0, 0, false, true, 'C', true);

		// Quantity
		$pdf->SetAlpha($opacity);
		$pdf->RoundedRect($this->marge_gauche + $width * 3 + 6, $RoundedRectHeight, $width, 6, $roundradius, '1001', 'FD', $this->border_style, $bgcolor);
		$pdf->SetAlpha(1);
		$pdf->SetFont('', 'B', $default_font_size - 2);
		$pdf->SetXY($this->marge_gauche + $width * 3 + 6, $RoundedRectHeight);
		$pdf->SetTextColorArray($textcolor);
		$pdf->MultiCell($width, 5, $outputlangs->transnoentities("Quantity"), 0, 'C', false);

		if ($object->qty) {
			$pdf->SetFont('', '', $default_font_size - 2);
			$pdf->SetXY($this->marge_gauche + $width * 3 + 6, $RoundedRectHeight + 6);
			$pdf->SetTextColorArray($textcolor);
			$pdf->MultiCell($width, 6, $outputlangs->convToOutputCharset($object->qty), '0', 'C');
		} else {
			$pdf->SetFont('', '', $default_font_size - 2);
			$pdf->SetXY($this->marge_gauche + $width * 3 + 6, $RoundedRectHeight + 6);
			$pdf->SetTextColorArray($textcolor);
			$pdf->SetFillColor(255, 255, 255);
			$pdf->MultiCell($width, 6, '', '0', 'C');
		}

		// Warehouse
		$pdf->SetAlpha($opacity);
		$pdf->RoundedRect($this->marge_gauche + $width * 4 + 8, $RoundedRectHeight, $width, 6, $roundradius, '1001', 'FD', $this->border_style, $bgcolor);
		$pdf->SetAlpha(1);
		$pdf->SetFont('', 'B', $default_font_size - 2);
		$pdf->SetXY($this->marge_gauche + $width * 4 + 8, $RoundedRectHeight);
		$pdf->SetTextColorArray($textcolor);
		$text = '<div style="line-height:90%;">' . $outputlangs->transnoentities("WarehouseForProduction") . '</div>';
		$pdf->writeHTMLCell($width, 5, $this->marge_gauche + $width * 4 + 8, $RoundedRectHeight + 0.5, $text, 0, 0, false, true, 'C', true);

		$staticWarehouse = new Entrepot($this->db);
		$staticWarehouse->fetch($object->fk_warehouse);
		if ($staticWarehouse->ref) {
			$pdf->SetFont('', '', $default_font_size - 2);
			$pdf->SetXY($this->marge_gauche + $width * 4 + 8, $RoundedRectHeight + 6);
			$pdf->SetTextColorArray($textcolor);
			$pdf->MultiCell($width, 6, $staticWarehouse->ref, '0', 'C');
		}
	
		$pdf->SetTextColorArray($textcolor);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 *   	Show footer of page. Need this->emetteur object
	 *
	 *   	@param	TCPDF		$pdf     			PDF
	 * 		@param	Commande	$object				Object to show
	 *      @param	Translate	$outputlangs		Object lang for output
	 *      @param	int			$hidefreetext		1=Hide free text
	 *      @return	int								Return height of bottom margin including footer text
	 */
	protected function _pagefoot(&$pdf, $object, $outputlangs, $hidefreetext = 0)
	{
		// phpcs:enable
		global $conf;
		$showdetails = $conf->global->MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS;
		if (!empty($conf->global->ULTIMATE_FOOTERTEXTCOLOR_COLOR)) {
			$footertextcolor = $conf->global->ULTIMATE_FOOTERTEXTCOLOR_COLOR;
		} else {
			$footertextcolor = array('25', '25', '25');
		}
		return pdf_ultimatepagefoot($pdf, $outputlangs, 'BOM_FREE_TEXT', $this->emetteur, $this->marge_basse, $this->marge_gauche, $this->page_hauteur, $object, $showdetails, $hidefreetext, $footertextcolor);
	}
	
	/**
	 *   	Define Array Column Field
	 *
	 *   	@param	Commande		$object    		common object
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
			'padding' => array(0, 0.5, 0.5, 0.5), // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
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
			'width' => empty($conf->global->ULTIMATE_DOCUMENTS_WITH_NUMBERING_WIDTH) ? 10 : $conf->global->ULTIMATE_DOCUMENTS_WITH_NUMBERING_WIDTH, // in mm 
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
		if (!empty($conf->global->ULTIMATE_BOMS_WITH_LINE_NUMBER)) {
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

		if ($conf->global->ULTIMATE_DOCUMENTS_WITH_REF == "yes") {
			$this->cols['ref']['status'] = true;
		}
		if (!empty($conf->global->ULTIMATE_BOMS_WITH_LINE_NUMBER) && $conf->global->ULTIMATE_PDF_BORDER_LEFT_STATUS == 'yes') {
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

		if (!empty($conf->global->ULTIMATE_BOMS_WITH_LINE_NUMBER) && ($conf->global->ULTIMATE_PDF_BORDER_LEFT_STATUS == 'yes') || !empty($conf->global->ULTIMATE_DOCUMENTS_WITH_REF) && $this->atleastoneref == true && $conf->global->ULTIMATE_PDF_BORDER_LEFT_STATUS == 'yes') {
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

		if (!empty($conf->global->ULTIMATE_GENERATE_BOMS_WITH_PICTURE)) {
			$this->cols['photo']['status'] = true;
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
		if (!empty($conf->global->ULTIMATE_GENERATE_BOMS_WITH_QTY)) {
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
				'align' => 'C',
			),
			'border-left' => false, // add left line separator
		);

		if (!empty($conf->global->PRODUCT_USE_UNITS)) {
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

		if (!empty($conf->global->ULTIMATE_GENERATE_BOMS_WITH_WEIGHT_COLUMN)) {
			$this->cols['weight']['status'] = true;
		}
		if ($conf->global->ULTIMATE_PDF_BORDER_LEFT_STATUS == 'yes') {
			$this->cols['weight']['border-left'] = true;
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
}

?>