<?php
/* Copyright (C) 2003       Rodolphe Quiedeville        <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010  Laurent Destailleur         <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin				<regis.houssin@capnetworks.com>
 * Copyright (C) 2011-2022  Philippe Grand              <philippe.grand@atoo-net.com>
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
 *	\file       htdocs/custom/core/modules/fichinter/doc/pdf_ultimate_inter.modules.php
 *	\ingroup    ficheinter
 *	\brief      File of Class to build interventions documents with model ultimate_inter
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/fichinter/modules_fichinter.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once(DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php');
dol_include_once("/ultimatepdf/lib/ultimatepdf.lib.php");

/**
 *	\class      pdf_ultimate_inter
 *	\brief      Class to build interventions documents with model ultimate_inter
 */
class pdf_ultimate_inter extends ModelePDFFicheinter
{
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
	 *  @param		DoliDB		$db      Database handler
	 */
	public function  __construct($db)
	{
		global $conf, $langs, $mysoc;
		$langs->load("ultimatepdf@ultimatepdf");

		$this->db = $db;
		$this->name = 'ultimate_inter';
		$this->description = $langs->trans("DocumentModelStandard");
		$this->update_main_doc_field = 1; // Save the name of generated file as the main doc when generating a doc with this template

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
		$this->option_tva = 0;                     // Gere option tva FACTURE_TVAOPTION
		$this->option_modereg = 0;                 // Affiche mode reglement
		$this->option_condreg = 0;                 // Affiche conditions reglement
		$this->option_codeproduitservice = 0;      // Affiche code produit-service
		$this->option_multilang = 1;               // Dispo en plusieurs langues
		$this->option_draft_watermark = 1;		   //Support add of a watermark on drafts

		$bordercolor =  (!empty($conf->global->ULTIMATE_BORDERCOLOR_COLOR)) ?
		html2rgb($conf->global->ULTIMATE_BORDERCOLOR_COLOR) : array(0, 63, 127);

		$dashdotted =  (!empty($conf->global->ULTIMATE_DASH_DOTTED)) ?
		$conf->global->ULTIMATE_DASH_DOTTED : 0;
		
		$this->style = array('width' => 0.2, 'cap' => 'butt', 'join' => 'miter', 'dash' => $dashdotted , 'color' => $bordercolor);

		//  Get source company
		$this->emetteur = $mysoc;
		if (! $this->emetteur->country_code) $this->emetteur->country_code = substr($langs->defaultlang, -2);    // By default, if not defined

		// Define position of columns
		//$this->posxdesc=$this->marge_gauche+1;
		
		$this->tabTitleHeight = 8; // default height
	}

	/**
     *  Function to build pdf onto disk
     *
     *  @param		Object	$object				Id of object to generate
     *  @param		object	$outputlangs		Lang output object
     *  @param		string	$srctemplatepath	Full path of source filename for generator using a template file
     *  @param		int		$hidedetails		Do not show line details
     *  @param		int		$hidedesc			Do not show desc
     *  @param		int		$hideref			Do not show ref
     *  @param		object	$hookmanager		Hookmanager object
     *  @return     int             			1=OK, 0=KO
	 */
	public function write_file($object, $outputlangs, $srctemplatepath = '', $hidedetails = 0, $hidedesc = 0, $hideref = 0)
	{
		global $user, $langs, $conf, $mysoc, $db, $hookmanager, $nblines;

		if (!is_object($outputlangs)) $outputlangs = $langs;

		$textcolor =  (!empty($conf->global->ULTIMATE_TEXTCOLOR_COLOR)) ?
		html2rgb($conf->global->ULTIMATE_TEXTCOLOR_COLOR) : array(25, 25, 25);

		$roundradius =  (!empty($conf->global->ULTIMATE_SET_RADIUS)) ?
		$conf->global->ULTIMATE_SET_RADIUS : 1;

		$bordercolor =  (!empty($conf->global->ULTIMATE_BORDERCOLOR_COLOR)) ?
		html2rgb($conf->global->ULTIMATE_BORDERCOLOR_COLOR) : array(0, 63, 127);

		$dashdotted =  (!empty($conf->global->ULTIMATE_DASH_DOTTED)) ?
		$conf->global->ULTIMATE_DASH_DOTTED : 0;

		$this->border_style = array('width' => 0.2, 'cap' => 'butt', 'join' => 'miter', 'dash' => $dashdotted, 'color' => $bordercolor);

		// Load traductions files required by page
		$outputlangs->loadLangs(array("main", "dict", "companies", "interventions", "orders", "errors",  "ultimatepdf@ultimatepdf"));

		if (!empty($conf->global->PDF_USE_ALSO_LANGUAGE_CODE) && $outputlangs->defaultlang != $conf->global->PDF_USE_ALSO_LANGUAGE_CODE) {
			global $outputlangsbis;
			$outputlangsbis = new Translate('', $conf);
			$outputlangsbis->setDefaultLang($conf->global->PDF_USE_ALSO_LANGUAGE_CODE);
			$outputlangsbis->loadLangs(array("main", "dict", "companies", "bills", "products", "propal"));
		}

		if ($conf->ficheinter->dir_output) {
			$object->fetch_thirdparty();

			// Definition of $dir and $file
			if ($object->specimen) {
				$dir = $conf->ficheinter->dir_output;
				$file = $dir . "/SPECIMEN.pdf";
			} else {
				$objectref = dol_sanitizeFileName($object->ref);
				 if ($conf->mbisignature->enabled) {
                    $resql = $db->query("SELECT * from " . MAIN_DB_PREFIX . "mbi_signature WHERE object_type = '" . $objectref . "' AND entity = " . $conf->entity);
                    $obj = $db->fetch_object($resql);
                    if ($obj->pathoffile !== 'document generated' && $obj->pathoffile) {
                        $dir = $conf->ficheinter->dir_output . "/" . $objectref;
                        $file = $dir . "/" . $objectref . "_signature.pdf";
                    } else {
                        $dir = $conf->ficheinter->dir_output . "/" . $objectref;
                        $file = $dir . "/" . $objectref . ".pdf";
                    }
                } else {
                    $dir = $conf->ficheinter->dir_output . "/" . $objectref;
                    $file = $dir . "/" . $objectref . ".pdf";
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
				$reshook = $hookmanager->executeHooks('beforePDFCreation', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks

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

				//Generation de l entete du fichier
				$pdf->SetTitle($outputlangs->convToOutputCharset($object->ref));
				$pdf->SetSubject($outputlangs->transnoentities("InterventionCard"));
				$pdf->SetCreator("Dolibarr " . DOL_VERSION);
				$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
				$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref) . " " . $outputlangs->transnoentities("InterventionCard"));
				if (!empty($conf->global->MAIN_DISABLE_PDF_COMPRESSION)) $pdf->SetCompression(false);

				$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right

				// New page
				$pdf->AddPage();
				if (!empty($tplidx)) $pdf->useTemplate($tplidx);
				$pagenb++;

				$heightforinfotot = 20;	// Height reserved to output the info and total part
				$heightforcustomercomment = 20;	// Height reserved to output the customer comment
				$heightforagreement = 40; // Height reserved to output the agreement
				$heightforfreetext = (isset($conf->global->MAIN_PDF_FREETEXT_HEIGHT) ? $conf->global->MAIN_PDF_FREETEXT_HEIGHT : 5);	// Height reserved to output the free text on last page
				$heightforfooter = $this->marge_basse + 8; // Height reserved to output the footer (value include bottom margin)
				if (!empty($conf->global->MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS)) $heightforfooter += 6;

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
				if (($conf->global->ULTIMATE_PDF_FICHINTER_ADDALSOTARGETDETAILS == 1) || (!empty($conf->global->MAIN_INFO_SOCIETE_NOTE) && !empty($this->emetteur->note_private)) || (!empty($conf->global->MAIN_INFO_TVAINTRA) && !empty($this->emetteur->tva_intra))) {
					$hautcadre = 52;
				} else {
					$hautcadre = 48;
				}

				$this->_pagehead($pdf, $object, 1, $outputlangs);

				$pdf->SetFont('', '', $default_font_size - 2);
				$pdf->MultiCell(0, 3, '');		// Set interline to 3
				$pdf->SetTextColorArray($textcolor);

				$tab_top = $this->marge_haute + $logo_height + $hautcadre + 14;

				$tab_top_newpage = (empty($conf->global->ULTIMATE_FICHINTER_PDF_DONOTREPEAT_HEAD) ? $this->marge_haute + $logo_height + 20 : 10);

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
						//To avoid issue if there is an image within signature
						$inthatstr = $salerepobj->signature;
						$thisstr = 'src=';
						$thatstr = ' style';
						$imgsignature = between($thisstr, $thatstr, $inthatstr);
						$signature = str_replace($imgsignature, '""', $inthatstr);
						if (!empty($salerepobj->signature)) $notetoshow = dol_concatdesc($notetoshow, $signature);
					}
				}

				$pagenb = $pdf->getPage();
				if ($notetoshow && empty($conf->global->MAIN_PUBLIC_NOTE_IN_ADDRESS)) {
					$pageposbeforenote = $pagenb;
					$nexY = $pdf->GetY();
					$tab_top = $nexY - 2;
					
					$substitutionarray = pdf_getSubstitutionArray($outputlangs, null, $object);
					complete_substitutions_array($substitutionarray, $outputlangs, $object);
					$notetoshow = make_substitutions($notetoshow, $substitutionarray, $outputlangs);

					$pdf->startTransaction();

					$pdf->SetFont('', '', $default_font_size - 1);   // Dans boucle pour gerer multi-page
					$pdf->writeHTMLCell($tab_width, 3, $this->marge_gauche + 1, $tab_top, dol_htmlentitiesbr($notetoshow), 0, 1);
					// Description
					$pageposafternote = $pdf->getPage();
					$posyafter = $pdf->GetY();
					$nexY = $pdf->GetY();
					$height_note = $nexY - $tab_top;

					// Rect prend une longueur en 3eme et 4eme param
					$pdf->SetDrawColor(192, 192, 192);
					$pdf->RoundedRect($this->marge_gauche, $tab_top - 1, $tab_width, $height_note + 1, $roundradius, '1111', 'S', $this->border_style, $bgcolor);

					if ($pageposafternote > $pageposbeforenote) {
						$pdf->rollbackTransaction(true);

						// prepair pages to receive notes
						while ($pagenb < $pageposafternote) {
							$pdf->AddPage();
							$pagenb++;
							if (!empty($tplidx)) $pdf->useTemplate($tplidx);
							if (empty($conf->global->ULTIMATE_FICHINTER_PDF_DONOTREPEAT_HEAD)) $this->_pagehead($pdf, $object, 0, $outputlangs);
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
								$pdf->RoundedRect($this->marge_gauche, $tab_top_newpage - 1, $tab_width, $height_note + 1, $roundradius, '1111', 'S', array());
							} else {
								$height_note = $this->page_hauteur - ($tab_top + $heightforfooter);
								$pdf->RoundedRect($this->marge_gauche, $tab_top - 1, $tab_width, $height_note + 1, $roundradius, '1111', 'S', array());
							}

							// Add footer
							$pdf->setPageOrientation('', 1, 0);	// The only function to edit the bottom margin of current page to set it.
							$this->_pagefoot($pdf, $object, $outputlangs, 1);

							$i++;
						}

						// apply note frame to last page
						$pdf->setPage($pageposafternote);
						if (!empty($tplidx)) $pdf->useTemplate($tplidx);
						if (empty($conf->global->ULTIMATE_FICHINTER_PDF_DONOTREPEAT_HEAD)) $this->_pagehead($pdf, $object, 0, $outputlangs);
						$height_note = $posyafter - $tab_top_newpage;
						$pdf->RoundedRect($this->marge_gauche, $tab_top_newpage - 1, $tab_width, $height_note + 1, $roundradius, '1111', 'S', array());
					} else // No pagebreak
					{
						$pdf->commitTransaction();
						$posyafter = $pdf->GetY();
						$height_note = $posyafter - $tab_top;
						$pdf->RoundedRect($this->marge_gauche, $tab_top - 1, $tab_width, $height_note + 1, $roundradius, '1111', 'S', array());

						if ($posyafter > ($this->page_hauteur - ($heightforfooter + $heightforfreetext + 20))) {
							// not enough space, need to add page
							$pdf->AddPage('', '', true);
							$pagenb++;
							$pageposafternote++;
							$pdf->setPage($pageposafternote);
							if (!empty($tplidx)) $pdf->useTemplate($tplidx);
							if (empty($conf->global->ULTIMATE_ORDERS_PDF_DONOTREPEAT_HEAD)) $this->_pagehead($pdf, $object, 0, $outputlangs);

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

				// Simulation de tableau pour connaitre la hauteur de la ligne de titre
				$pdf->startTransaction();
				$this->pdfTabTitles($pdf, $tab_top, $tab_height, $outputlangs, $hidetop);
				$pdf->rollbackTransaction(true);
				$nexY = $pdf->GetY();
				$tab_top = $nexY;
				if ($height_note) {
					$tab_top += $height_note + 5;
				}
				if (!$height_note) {
					$tab_top = $nexY + 6;
				}

				$iniY = $tab_top + $this->tabTitleHeight + 2;
				$curY = $tab_top + $this->tabTitleHeight + 2;
				if (empty($conf->global->MAIN_PDF_DISABLE_COL_HEAD_TITLE)) {
					$nexY = $tab_top + $this->tabTitleHeight - 8;
				} else {
					$nexY = $tab_top + $this->tabTitleHeight - 2;
				}

				// Loop on each lines
				$nblines = count($object->lines);
				$pageposbeforeprintlines = $pdf->getPage();
				$pagenb = $pageposbeforeprintlines;
				$line_number = 1;
				for ($i = 0; $i < $nblines; $i++) {
					$objectligne = $object->lines[$i];

					$valide = $objectligne->id ? $objectligne->fetch($objectligne->id) : 0;

					if ($valide > 0 || $object->specimen) {
						// Description of intervention line
						$curX = $this->getColumnContentXStart('desc');
						if ($this->getColumnStatus('date') == true) {
							$text_length = $this->getColumnContentXStart('date') - $this->getColumnContentXStart('desc');
						} else {
							$text_length = $this->getColumnContentXStart('duration') - $this->getColumnContentXStart('desc');
						}

						$curY = $nexY;
						$pdf->SetFont('', '', $default_font_size - 1);   // Into loop to work with multipage
						$pdf->SetTextColorArray($textcolor);

						$pdf->setTopMargin($tab_top_newpage);
						//If we aren't on last lines footer space needed is on $heightforfooter
						if ($i != $nblines - 1) {
							$bMargin = $heightforfooter;
						} else {
							//We are on last item, need to check all footer (freetext, ...)
							$bMargin = $heightforfooter + $heightforfreetext + $heightforinfotot + $heightforcustomercomment + $heightforagreement;
						}
						$pdf->setPageOrientation('', 1, $bMargin);	// The only function to edit the bottom margin of current page to set it.
						$pageposbefore = $pdf->getPage();

						$showpricebeforepagebreak = 1;
						$posYStartDescription = 0;
						$posYAfterDescription = 0;

						$pdf->startTransaction();

						$this->printColDescContent($pdf, $curY, 'desc', $object, $i, $outputlangs, $hideref, $hidedesc);
						$posYAfterDescription = $pdf->GetY();

						$pageposafter = $pdf->getPage();

						if ($pageposafter > $pageposbefore)	// There is a pagebreak
						{
							$pdf->rollbackTransaction(true);
							$pageposafter = $pageposbefore;

							$pdf->setPageOrientation('', 1, $heightforfooter);	// The only function to edit the bottom margin of current page to set it.
							$this->printColDescContent($pdf, $curY, 'desc', $object, $i, $outputlangs, $hideref, $hidedesc);
							$posYAfterDescription = $pdf->GetY();
							$pageposafter = $pdf->getPage();

							if ($posYAfterDescription > ($this->page_hauteur - $bMargin))	// There is no space left for total+free text
							{
								if ($i == ($nblines - 1))	// No more lines, and no space left to show total, so we create a new page
								{
									$pdf->AddPage('', '', true);
									if (!empty($tplidx)) $pdf->useTemplate($tplidx);
									if (empty($conf->global->ULTIMATE_FICHINTER_PDF_DONOTREPEAT_HEAD)) $this->_pagehead($pdf, $object, 0, $outputlangs);
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

						$nexY = $pdf->GetY() + 2;
						$pageposafter = $pdf->getPage();

						$pdf->setPage($pageposbefore);
						$pdf->setTopMargin($this->marge_haute);
						$pdf->setPageOrientation('', 1, 0);	// The only function to edit the bottom margin of current page to set it.

						// We suppose that a too long description is moved completely on next page
						if ($pageposafter > $pageposbefore && empty($showpricebeforepagebreak)) {
							$pdf->setPage($pageposafter);
							$curY = $tab_top_newpage;
						}

						$pdf->SetFont('', '', $default_font_size - 1);   // On repositionne la police par defaut

						//Line numbering
						if (!empty($conf->global->ULTIMATE_FICHINTER_WITH_LINE_NUMBER)) {
							// Numbering
							if ($this->getColumnStatus('num') && array_key_exists($i, $object->lines)) {
								$this->printStdColumnContent($pdf, $curY, 'num', $line_number);
								$nexY = max($pdf->GetY(), $nexY);
								$line_number++;
							}
						}

						// Date
						if ($this->getColumnStatus('date')) {
							if (empty($conf->global->FICHINTER_DATE_WITHOUT_HOUR)) {
								$dateinter = dol_print_date($objectligne->datei, 'dayhour', false, $outputlangs, true);
							} else {
								$dateinter = dol_print_date($objectligne->datei, 'day', false, $outputlangs, true);
							}
							$this->printStdColumnContent($pdf, $curY, 'date', $dateinter);
							$nexY = max($pdf->GetY(), $nexY);
						}

						// Duration
						if ($this->getColumnStatus('duration')) {
							if ($objectligne->duration > 0) {
								$duration = convertSecondToTime($objectligne->duration);
							}

							$this->printStdColumnContent($pdf, $curY, 'duration', $duration);
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
							'pdf' => &$pdf,
							'curY' => &$curY,
							'nexY' => &$nexY,
							'outputlangs' => $outputlangs,
							'hidedetails' => $hidedetails
						);
						$reshook = $hookmanager->executeHooks('printPDFline', $parameters, $this);    // Note that $object may have been modified by hook


						// Add line
						if (!empty($conf->global->ULTIMATE_FICHINTER_PDF_DASH_BETWEEN_LINES) && $i < ($nblines - 1)) {
							$pdf->setPage($pageposafter);
							$pdf->SetLineStyle(array('dash' => '1,1', 'color' => array(210, 210, 210)));
							$pdf->line($this->marge_gauche, $nexY + 1, $this->page_largeur - $this->marge_droite, $nexY + 1);
							$pdf->SetLineStyle(array('dash' => 0));
						}

						$nexY += 4;    // Passe espace entre les lignes

						// Detect if some page were added automatically and output _tableau for past pages
						while ($pagenb < $pageposafter) {
							$pdf->setPage($pagenb);
							if ($pagenb == $pageposbeforeprintlines) {
								$this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforfooter, 0, $outputlangs, 0, 1);
							} else {
								$this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforfooter, 0, $outputlangs, 0, 1);
							}
							$this->_pagefoot($pdf, $object, $outputlangs, 1);
							$pagenb++;
							$pdf->setPage($pagenb);
							$pdf->setPageOrientation('', 1, 0);	// The only function to edit the bottom margin of current page to set it.
							if (empty($conf->global->ULTIMATE_FICHINTER_PDF_DONOTREPEAT_HEAD)) $this->_pagehead($pdf, $object, 0, $outputlangs);
						}
						if (isset($object->lines[$i + 1]->pagebreak) && $object->lines[$i + 1]->pagebreak) {
							if ($pagenb == $pageposafter) {
								$this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforfooter, 0, $outputlangs, 0, 1);
							} else {
								$this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforfooter, 0, $outputlangs, 0, 1);
							}
							$this->_pagefoot($pdf, $object, $outputlangs, 1);
							// New page
							$pdf->AddPage();
							if (!empty($tplidx)) $pdf->useTemplate($tplidx);
							$pagenb++;
							if (empty($conf->global->ULTIMATE_FICHINTER_PDF_DONOTREPEAT_HEAD)) $this->_pagehead($pdf, $object, 0, $outputlangs);
						}
					}
				}

				// Show square
				if ($pagenb == $pageposbeforeprintlines) {
					$this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforinfotot - $heightforcustomercomment - $heightforagreement - $heightforfreetext - $heightforfooter - 4, 0, $outputlangs, 0, 0);
				} else {
					$this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforinfotot - $heightforcustomercomment - $heightforagreement - $heightforfreetext - $heightforfooter - 4, 0, $outputlangs, 0, 0);
				}
				$bottomlasttab = $this->page_hauteur - $heightforinfotot - $heightforcustomercomment - $heightforagreement - $heightforfreetext - $heightforfooter + 1;

				// Affiche zone comments
				if (!empty($conf->global->ULTIMATE_FICHINTER_SPECIFIC_CODE_FOR_ENCRAJE)) {
					$this->_tableau_comment($pdf, $object, $bottomlasttab, $outputlangs);
				}

				$posy = 230;
				if ($conf->mbisignature->enabled) {
					$posy = $this->_signature_area_double($pdf, $object, $posy, $outputlangs);
				} else { // Affiche zone agreement
					$posy = $this->_agreement($pdf, $object, $bottomlasttab, $outputlangs);
				}

				// Affiche zone signature responsable
				$posy = $this->_signature($pdf, $object, $posy, $outputlangs);

				// Affiche zone totaux
				$posy = $this->_tableau_tot($pdf, $object, $deja_regle, $bottomlasttab, $outputlangs);

				// Pied de page
				$this->_pagefoot($pdf, $object, $outputlangs);
				if (method_exists($pdf, 'AliasNbPages')) $pdf->AliasNbPages();

				// Add PDF to be merged
				if (!empty($conf->global->ULTIMATEPDF_GENERATE_FICHINTER_WITH_MERGED_PDF)) {
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
									if (!empty($conf->ficheinter->enabled))
										$filetomerge_dir = $conf->ficheinter->dir_output . '/' . dol_sanitizeFileName($object->ref);
									$infile = $filetomerge_dir . '/' . $linefile->file_name;
									dol_syslog(get_class($this) . '::$upload_dir=' . $filetomerge_dir, LOG_DEBUG);
									// If file really exists
									if (is_file($infile)) {
										$count = $pdf->setSourceFile($infile);
										// import all page
										for ($i = 1; $i <= $count; $i++) {
											// New page
											$pdf->AddPage();
											$tplIdx = $pdf->importPage($i);
											$pdf->useTemplate($tplIdx, 0, 0, $this->page_largeur);
											if (method_exists($pdf, 'AliasNbPages')) $pdf->AliasNbPages();
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
				$reshook = $hookmanager->executeHooks('afterPDFCreation', $parameters, $this, $action);    // Note that $action and $object may have been modified by some hooks
				if ($reshook < 0) {
					$this->error = $hookmanager->error;
					$this->errors = $hookmanager->errors;
				}

				if (!empty($conf->global->MAIN_UMASK))
					@chmod($file, octdec($conf->global->MAIN_UMASK));

				return 1;
			} else {
				$this->error = $langs->trans("ErrorCanNotCreateDir", $dir);
				return 0;
			}
		} else {
			$this->error = $langs->trans("ErrorConstantNotDefined", "FICHEINTER_OUTPUTDIR");
			return 0;
		}
		$this->error = $langs->trans("ErrorUnknown");
		return 0;   // Erreur par defaut
	}

	/**
	 *   Show miscellaneous information (payment mode, payment term, ...)
	 *
	 *   @param		TCPDF		$pdf     		Object PDF
	 *   @param		Object		$object			Object to show
	 *   @param		int			$posy			Y
	 *   @param		Translate	$outputlangs	Langs object
	 *   @return	void
	 */
	function _agreement(&$pdf, $object, $posy, $outputlangs)
	{
		global $conf;

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		$roundradius =  (!empty($conf->global->ULTIMATE_SET_RADIUS)) ?
		$conf->global->ULTIMATE_SET_RADIUS : 1;

		$dashdotted =  (!empty($conf->global->ULTIMATE_DASH_DOTTED)) ?
		$conf->global->ULTIMATE_DASH_DOTTED : 0;

		$bordercolor =  (!empty($conf->global->ULTIMATE_BORDERCOLOR_COLOR)) ?
		$conf->global->ULTIMATE_BORDERCOLOR_COLOR : array('0', '63', '127');

		$textcolor = array('25', '25', '25');
		if (!empty($conf->global->ULTIMATE_TEXTCOLOR_COLOR)) {
			$textcolor =  html2rgb($conf->global->ULTIMATE_TEXTCOLOR_COLOR);
		}
		$this->border_style = array('width' => 0.2, 'cap' => 'butt', 'join' => 'miter', 'dash' => $dashdotted, 'color' => $bordercolor);
		$widthrecbox = ($this->page_largeur - $this->marge_gauche - $this->marge_droite - 4) / 2;

		// Cadres signatures
		$heightforfreetext = (isset($conf->global->MAIN_PDF_FREETEXT_HEIGHT) ? $conf->global->MAIN_PDF_FREETEXT_HEIGHT : 8);	// Height reserved to output the free text on last page
		$heightforfooter = $this->marge_basse + 12;	// Height reserved to output the footer (value include bottom margin)
		$heightforagreement = 40;	// Height reserved to output the agreement part

		$deltay = $this->page_hauteur - $heightforfreetext - $heightforfooter - $heightforagreement + 2;
		//var_dump($deltay, $posy);exit;
		$posy = max($posy, $deltay);
		$deltax = $this->marge_gauche;
		if ($roundradius == 0) {
			$roundradius = 0.1; //RoundedRect don't support $roundradius to be 0
		}
		$pdf->RoundedRect($deltax, $posy, $widthrecbox, 40, $roundradius, '1111', 'S', $this->border_style, array());
		$pdf->SetFont('', 'B', $default_font_size - 1);
		$pdf->SetTextColorArray($textcolor);
		$pdf->SetXY($deltax, $posy);
		$titre = $outputlangs->transnoentities("NameAndSignatureOfInternalContact");
		$pdf->MultiCell($widthrecbox, 5, $titre, 0, 'L', 0);
		$pdf->SetFont('', 'I', $default_font_size - 2);
		$pdf->SetXY($deltax, $posy + 8);

		$posy = max($posy, $deltay);
		$deltax = $this->marge_gauche + $widthrecbox + 4;

		$pdf->RoundedRect($deltax, $posy, $widthrecbox, 40, $roundradius, '1111', 'S', $this->border_style, array());
		$pdf->SetFont('', 'B', $default_font_size - 1);
		$pdf->SetXY($deltax, $posy);
		$titre = $outputlangs->transnoentities("NameAndSignatureOfExternalContact");
		$pdf->MultiCell($widthrecbox, 5, $titre, 0, 'L', 0);

		$posy = $pdf->GetY();
		$pdf->SetXY($deltax, $posy);
		$pdf->SetFont('', 'I', $default_font_size - 2);
		$pdf->MultiCell($widthrecbox, 3, $outputlangs->transnoentities('DocORDER4'), 0, 'L', 0);
		$posy = $pdf->getY();

		return $posy;
	}

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
		global $conf, $langs;

		$default_font_size = pdf_getPDFFontSize($outputlangs);
		
		$dashdotted =  (!empty($conf->global->ULTIMATE_DASH_DOTTED)) ?
		$conf->global->ULTIMATE_DASH_DOTTED : 0;
		
		$bordercolor =  (!empty($conf->global->ULTIMATE_BORDERCOLOR_COLOR)) ?
		$conf->global->ULTIMATE_BORDERCOLOR_COLOR : array('0', '63', '127');

		$textcolor = array('25', '25', '25');
		if (!empty($conf->global->ULTIMATE_TEXTCOLOR_COLOR)) {
			$textcolor =  html2rgb($conf->global->ULTIMATE_TEXTCOLOR_COLOR);
		}
		$this->border_style = array('width' => 0.2, 'cap' => 'butt', 'join' => 'miter', 'dash' => $dashdotted, 'color' => $bordercolor);
		$widthrecbox = ($this->page_largeur - $this->marge_gauche - $this->marge_droite - 4) / 2;

		if (!empty($conf->global->ULTIMATEPDF_ADD_RESPONSABLE_SIGNATURE_IN_FICHINTER)) {
			$heightforfreetext = (isset($conf->global->MAIN_PDF_FREETEXT_HEIGHT) ? $conf->global->MAIN_PDF_FREETEXT_HEIGHT : 12);	// Height reserved to output the free text on last page
			$heightforfooter = $this->marge_basse + 12;	// Height reserved to output the footer (value include bottom margin)
			$heightforinfotot = 25;	// Height reserved to output the info and total part
			$deltay = $this->page_hauteur - $heightforfreetext - $heightforfooter - $heightforinfotot + 10;
			$posy = max($posy, $deltay);
			$deltax = $this->marge_gauche + 2;

			$pdf->SetXY($deltax, $posy);
			$pdf->SetFont('', 'I', $default_font_size - 2);
			//$pdf->MultiCell(80, 3, $outputlangs->transnoentities('DocORDER4'), 0, 'L', 0);
			$posy = $pdf->getY();
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
				$pdf->SetXY($this->marge_gauche, $posy - 10);
				$pdf->SetFont('', '', $default_font_size - 1);
				$pdf->MultiCell($widthrecbox, 3, $salerepobj->getFullName($langs, 0), 0, 'C');
				$posy = $pdf->getY();
				$pdf->Image($signaturepath, $this->marge_gauche + 18, $posy + 20, 0, max(15, $imgsignsize['height'])); // width=0 (auto)
			} else {
				$pdf->SetTextColor(200, 0, 0);
				$pdf->SetXY($this->marge_gauche, $posy - 5);
				$pdf->SetFont('', '', $default_font_size - 2);
				$pdf->MultiCell($widthrecbox, 3, $outputlangs->transnoentities("ErrorUserSignatureFileNotFound") . ' ' . $outputlangs->transnoentities("ErrorSignatureFileNotFound", $signaturepath), 0, 'L');
				$pdf->SetTextColorArray($textcolor);
			}
			return $posy;
		}
	}

	/**
	 *   Show miscellaneous information (payment mode, payment term, ...)
	 *
	 *   @param		TCPDF		$pdf     		Object PDF
	 *   @param		Object		$object			Object to show
	 *   @param		int			$posy			Y
	 *   @param		Translate	$outputlangs	Langs object
	 *   @return	void
	 */
	function _tableau_comment(&$pdf, $object, $posy, $outputlangs)
	{
		global $conf;
		$outputlangs->load("ultimatepdf@ultimatepdf");
		$default_font_size = pdf_getPDFFontSize($outputlangs);
		$textcolor =  html2rgb($conf->global->ULTIMATE_TEXTCOLOR_COLOR);

		$widthrecbox = $this->page_largeur - $this->marge_gauche - $this->marge_droite;
		// Cadres signatures
		$heightforfreetext = (isset($conf->global->MAIN_PDF_FREETEXT_HEIGHT) ? $conf->global->MAIN_PDF_FREETEXT_HEIGHT : 8);	// Height reserved to output the free text on last page
		$heightforfooter = $this->marge_basse + 12;	// Height reserved to output the footer (value include bottom margin)
		$heightforagreement = 40;	// Height reserved to output the agreement part
		$heightforcustomercomment = 18;	// Height reserved to output the customer comment
		$deltay = $this->page_hauteur - $heightforfreetext - $heightforfooter - $heightforagreement - $heightforcustomercomment;
		$posy = max($posy, $deltay);
		$posx = $this->marge_gauche;
		$pdf->RoundedRect($posx, $posy, $widthrecbox, $heightforcustomercomment, 2, '1111', 'S', $this->style, '');
		
		//Authorize to show comment
		$pdf->rect($posx + 2, $posy + 2, 4, 4);
		$pdf->SetXY($posx + 2, $posy);

		// Example using extrafields
		$title_key = (empty($object->array_options['options_newyesno'])) ? '' : ($object->array_options['options_newyesno']);
		$extrafields = new ExtraFields($this->db);
		$extralabels = $extrafields->fetch_name_optionals_label($object->table_element, true);
		if (is_array($extralabels) && key_exists('newyesno', $extralabels) && !empty($title_key)) {
			$pdf->SetXY($this->marge_gauche, $posy);
			$title = $extrafields->showOutputField('newyesno', $title_key);
		}

		if ($title_key == '1') {
			$pdf->SetFont('', 'B', $default_font_size - 1);
			$pdf->SetXY($posx + 2, $posy + 2);
			$pdf->MultiCell(10, 6, $outputlangs->transnoentities("X"), 0, 'L', 0);
		}
		$pdf->SetFont('', '', $default_font_size - 1);
		$pdf->SetTextColorArray($textcolor);
		$pdf->SetXY($posx + 6, $posy + 2);
		//var_dump($this->emetteur->name);exit;
		$pdf->MultiCell($widthrecbox, 6, $outputlangs->transnoentities("AutorisationShowComment", $this->emetteur->name), 0, 'L', 0);
		$posy = $pdf->GetY() + 2;
		// Example using extrafields
		$title_key = (empty($object->array_options['options_newcomment'])) ? '' : ($object->array_options['options_newcomment']);
		$extrafields = new ExtraFields($this->db);
		$extralabels = $extrafields->fetch_name_optionals_label($object->table_element, true);
		if (is_array($extralabels) && key_exists('newcomment', $extralabels) && !empty($title_key)) {
			$pdf->SetXY($this->marge_gauche, $posy);
			$title = $extrafields->showOutputField('newcomment', $title_key);
			$pdf->writeHTMLCell($widthrecbox, 4, $this->marge_gauche, $posy, $title, 0, 0, false, true, 'L', true);

			$posy = $pdf->GetY() + 7;
		}

		return $posy;
	}

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
		global $conf, $langs;

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		$bgcolor =  (!empty($conf->global->ULTIMATE_BGCOLOR_COLOR)) ?
		html2rgb($conf->global->ULTIMATE_BGCOLOR_COLOR) : array(170, 212, 255);

		$opacity =  (!empty($conf->global->ULTIMATE_BGCOLOR_OPACITY)) ?
		$conf->global->ULTIMATE_BGCOLOR_OPACITY : 0.5;

		$textcolor =  (!empty($conf->global->ULTIMATE_TEXTCOLOR_COLOR)) ?
		html2rgb($conf->global->ULTIMATE_TEXTCOLOR_COLOR) : array(25, 25, 25);

		$pdf->SetFont('', '', $default_font_size - 1);

		// Tableau total
		$widthrecbox = ($this->page_largeur - $this->marge_gauche - $this->marge_droite);
		$posx = $this->marge_gauche;
		$col2x = $widthrecbox * 2 / 3;
		$largcol2 = $widthrecbox - $col2x;

		// Cadres signatures
		$heightforfreetext = (isset($conf->global->MAIN_PDF_FREETEXT_HEIGHT) ? $conf->global->MAIN_PDF_FREETEXT_HEIGHT : 8);	// Height reserved to output the free text on last page
		$heightforfooter = $this->marge_basse + 12;	// Height reserved to output the footer (value include bottom margin)
		$heightforagreement = 40;	// Height reserved to output the agreement part
		$heightforcustomercomment = 20;	// Height reserved to output the customer comment
		$heightforinfotot = 20;	// Height reserved to output the info total
		$deltay = $this->page_hauteur - $heightforfreetext - $heightforfooter - $heightforagreement - $heightforcustomercomment - $heightforinfotot;
		$posy = max($posy, $deltay);

		$pdf->SetAlpha($opacity);
		$pdf->RoundedRect($posx, $posy - 4, $widthrecbox, $heightforinfotot, 2, '1111', 'S', $this->style, $bgcolor);
		$pdf->SetAlpha(1);

		$index = 0;

		//Total Duration
		$text1 = $object->description;
		if ($object->duration > 0) {
			$totaltime = convertSecondToTime($object->duration, 'all', $conf->global->MAIN_DURATION_OF_WORKDAY);
			$text = ($text ? ' - ' : '') . $langs->trans("Total") . "  :  " . $totaltime;
		}
		$pdf->SetFillColor(255, 255, 255);
		$pdf->SetTextColorArray($textcolor);
		$pdf->SetXY($posx, $posy);
		$pdf->MultiCell($col2x, $heightforinfotot, $text1, 0, 'L', 1);
		$pdf->SetFont('', 'B', $default_font_size + 2);
		$pdf->SetXY($col2x, $posy);
		$pdf->MultiCell($largcol2, $heightforinfotot, $text, 0, 'R', 1);

		$posy = $pdf->GetY() - 4;

		// Example using extrafields for new_line
		$extrafields = new ExtraFields($this->db);
		$extralabels = $extrafields->fetch_name_optionals_label($object->table_element, true);
		if (isset($this->rowid)) {
			$object->fetch_optionals($this->rowid, $extralabels);
		}
		$title = $outputlangs->convToOutputCharset($object->array_options['options_newline']);
		
		$sql = 'SELECT rowid, code, label, description';
		$sql .= ' FROM ' . MAIN_DB_PREFIX . 'c_ultimatepdf_line as uline';
		$sql .= " WHERE code ='" . $title . "'";

		if ($title == 0) $title = null;
		$result = $this->db->query($sql);
		if ($result) {
			if ($this->db->num_rows($result)) {
				$obj = $this->db->fetch_object($result);
				$label = $obj->label;
				$title = $obj->description;	
			}
		}
		
		$pdf->SetXY($this->marge_gauche, $posy);
		$pdf->SetFont('', '', $default_font_size - 3);
		if ($title != null) {
			$pdf->writeHTMLCell($widthrecbox, 4, $this->marge_gauche, $posy, $label . ' : ' . $title, 0, 0, false, true, 'L', true);
		}

		return $posy;
	}

	/**
	 *   Show table for lines
	 *
	 *   @param		TCPDF		$pdf     		Object PDF
	 *   @param		string		$tab_top		Top position of table
	 *   @param		string		$tab_height		Height of table (rectangle)
	 *   @param		int			$nexY			Y
	 *   @param		Translate	$outputlangs	Langs object
	 *   @param		int			$hidetop		Hide top bar of array
	 *   @param		int			$hidebottom		Hide bottom bar of array
	 *   @return	void
	 */
	function _tableau(&$pdf, $tab_top, $tab_height, $nexY, $outputlangs, $hidetop = 0, $hidebottom = 0)
	{
		global $conf;

		// Translations
		$outputlangs->loadLangs(array("main", "interventions", "ultimatepdf@ultimatepdf"));

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

		$title_bgcolor =  (!empty($conf->global->MAIN_PDF_TITLE_BACKGROUND_COLOR)) ?
		html2rgb($conf->global->MAIN_PDF_TITLE_BACKGROUND_COLOR) : array(170, 212, 255);

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
	protected function _pagehead(&$pdf, $object, $showaddress, $outputlangs, $titlekey = "")
	{
		global $conf, $langs;

		// Translations
		$outputlangs->loadLangs(array("main", "dict", "bills", "companies", "interventions", "ultimatepdf@ultimatepdf"));

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

		pdf_new_pagehead($pdf, $outputlangs, $this->page_hauteur);

		//Affiche le filigrane brouillon - Print Draft Watermark
		if ($object->statut == 0 && (!empty($conf->global->FICHINTER_DRAFT_WATERMARK))) {
			pdf_watermark($pdf, $outputlangs, $this->page_hauteur, $this->page_largeur, 'mm', $conf->global->FICHINTER_DRAFT_WATERMARK);
		}

		//Prepare la suite
		$pdf->SetTextColorArray($textcolor);
		$pdf->SetFont('', 'B', $default_font_size - 1);

		$posx = $this->page_largeur - $this->marge_droite - 100;
		$posy = $this->marge_haute;

		$pdf->SetXY($this->marge_gauche, $posy);

		// Other Logo
		$id = $conf->global->ULTIMATE_DESIGN;
		$upload_dir	= $conf->ultimatepdf->dir_output . '/otherlogo/' . $id . '/';
		$filearray = dol_dir_list($upload_dir, "files", 0, '', '(\.meta|_preview.*\.png)$', 'name', 0, 1);
		$otherlogo = $conf->ultimatepdf->dir_output . '/otherlogo/' . $id . '/' . $filearray[0]['name'];

		if (!empty($conf->global->ULTIMATE_DESIGN) && !empty($conf->global->ULTIMATE_OTHERLOGO_FILE) && is_readable($otherlogo) && !empty($filearray)) {
			$logo_height = max(pdf_getUltimateHeightForOtherLogo($otherlogo, true), 20);
			$pdf->Image($otherlogo, $this->marge_gauche, $posy, 0, $logo_height);	// width=0 (auto)
		} else {
			// Logo					
			if (empty($conf->global->PDF_DISABLE_MYCOMPANY_LOGO)) {
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
				} else {
					$pdf->RoundedRect($this->marge_gauche, $this->marge_haute, 100, 20, $roundradius, '1111', $senderstyle, $this->border_style, $bgcolor);
					$text = $this->emetteur->name;
					$pdf->MultiCell(100, 4, $outputlangs->convToOutputCharset($text), 0, 'L');
					$logo_height = 20;
				}
			}
		}

		//Display Thirdparty barcode at top
		if (!empty($conf->global->ULTIMATEPDF_GENERATE_FICHINTER_WITH_TOP_BARCODE)) {
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
		// ThirdParty QRcode
		if (!empty($conf->global->ULTIMATEPDF_GENERATE_FICHINTER_WITH_TOP_QRCODE)) {
			$code = pdf_codeContents(); //get order link
			$pdf->write2DBarcode($code, 'QRCODE,L', $posxQRcode, $posy, $heightQRcode, $heightQRcode, $styleQr, 'N');
		}
		// My Company QR-code
		if (!empty($conf->global->ULTIMATEPDF_GENERATE_FICHINTER_WITH_MYCOMP_QRCODE)) {
			$code = pdf_mycompCodeContents();
			$pdf->write2DBarcode($code, 'QRCODE,L', $posxQRcode, $posy, $heightQRcode, $heightQRcode, $styleQr, 'N');
		}

		if (!empty($conf->global->ULTIMATEPDF_GENERATE_FICHINTER_WITH_TOP_QRCODE) || ($conf->global->ULTIMATEPDF_GENERATE_FICHINTER_WITH_MYCOMP_QRCODE)) {
			$rightSideWidth = $tab_width / 2 - $heightQRcode;
			$posx = $this->marge_gauche + $tab_width / 2 + $heightQRcode;
		} else {
			$rightSideWidth = $tab_width / 2;
			$posx = $this->marge_gauche + $tab_width / 2;
		}

		// Example using extrafields for new title of document
		$extrafields = new ExtraFields($this->db);
		$extralabels = $extrafields->fetch_name_optionals_label($object->table_element, true);
		if (!empty($this->rowid)) {
			$object->fetch($this->rowid);
		}
		$object->fetch_optionals($this->rowid, $extralabels);
		$titlekey = $outputlangs->convToOutputCharset($object->array_options['options_newtitle']);
		
		$sql = 'SELECT rowid, code, label, description';
		$sql .= ' FROM ' . MAIN_DB_PREFIX . 'c_ultimatepdf_title as utitle';
		$sql .= " WHERE code ='" . $titlekey . "'";
		
		if ($titlekey == 0) $titlekey = null;	
		$result = $this->db->query($sql);
		if ($result) {
			if ($this->db->num_rows($result)) {
				$obj = $this->db->fetch_object($result);
				$titlekey = $obj->label;
			}
		}

		// Document name
		$pdf->SetTextColorArray($textcolor);
		$pdf->SetFont('', 'B', $default_font_size + 2);
		$pdf->SetXY($posx, $posy);
		$standardtitle = $outputlangs->transnoentities("InterventionCard");
		if ($titlekey != null){
			$title = $outputlangs->transnoentities($titlekey);
		}else{
			$title = $standardtitle;
		}
		$pdf->MultiCell($rightSideWidth, 4, $title, '', 'R');

		$pdf->SetFont('', 'B', $default_font_size + 1);

		$posy = $pdf->getY();

		$pdf->SetXY($posx, $posy);
		$pdf->SetTextColorArray($textcolor);
		$pdf->MultiCell($rightSideWidth, 4, $outputlangs->transnoentities("Ref") . " : " . $outputlangs->convToOutputCharset($object->ref), '', 'R');

		$posy = $pdf->getY();

		if (!empty($conf->global->ULTIMATE_FICHINTER_PDF_SHOW_PROJECT)) {
			$proj = new Project($this->db);
			$proj->fetch($object->fk_project);
			if (!empty($object->fk_project)) {
				$langs->load('projects');
				$pdf->SetTextColorArray($textcolor);
				$pdf->SetFont('', '', $default_font_size - 1);
				$pdf->SetXY($posx, $posy);
				$pdf->MultiCell($rightSideWidth, 4, $outputlangs->transnoentities("Project") . " : " . (empty($proj->ref) ? '' : $proj->ref), 0, 'R');
			}
		}

		$posy = $pdf->getY();

		if (!empty($conf->global->ULTIMATE_FICHINTER_PDF_SHOW_PROJECT_TITLE)) {
			$object->fetch_projet();
			if (!empty($object->project->ref)) {
				$pdf->SetTextColorArray($textcolor);
				$pdf->SetFont('', '', $default_font_size - 1);
				$pdf->SetXY($posx, $posy);
				$pdf->MultiCell($rightSideWidth, 4, $outputlangs->transnoentities("Project") . " : " . (empty($object->project->title) ? '' : $object->projet->title), 0, 'R');
			}
		}

		$pdf->SetFont('', '', $default_font_size);
		$posy = $pdf->getY() - 2;

		$pdf->SetXY($posx, $posy);

		// Show list of linked objects
		$posy = pdf_writeLinkedObjects($pdf, $object, $outputlangs, $posx, $posy, $rightSideWidth, 3, 'R', $default_font_size);

		$posy = $pdf->getY();
		$delta = 4;

		if ($showaddress) {
			// Sender properties
			$carac_emetteur = '';
			// Add internal contact of proposal if defined
			$arrayidcontact = $object->getIdContact('internal', 'INTERREPFOLL');
			if (count($arrayidcontact) > 0) {
				$object->fetch_user($arrayidcontact[0]);
				$carac_emetteur .= ($carac_emetteur ? "\n" : '') . $outputlangs->transnoentities("Name") . ": " . $outputlangs->convToOutputCharset($object->user->getFullName($outputlangs)) . "\n";
			}

			// Sender properties
			$carac_emetteur = pdf_element_build_address($outputlangs, $this->emetteur, $object->thirdparty, '', 0, 'source', $object);

			// Show sender
			$posy = $logo_height + $this->marge_haute + $delta;
			$posx = $this->marge_gauche;
			if (!empty($conf->global->MAIN_INVERT_SENDER_RECIPIENT)) $posx = $this->page_largeur - $this->marge_droite - 80;
			$hautcadre = 48;
			$widthrecbox = $conf->global->ULTIMATE_WIDTH_RECBOX - 2;
			$widthrecboxrecipient = $tab_width - $conf->global->ULTIMATE_WIDTH_RECBOX;
			if (!empty($conf->global->ULTIMATE_INVERT_SENDER_RECIPIENT) && ($conf->global->ULTIMATE_INVERT_SENDER_RECIPIENT != "no")) $posx = $this->page_largeur - $this->marge_droite - $widthrecbox;
			if ($conf->global->ULTIMATE_CONTACT_ADDRESS_BLOCKS_OVER == 1) {
				$hreceipt = $hautcadre * 0.5;
				$wreceipt = $widthrecboxrecipient;
				$posxba = $this->page_largeur - $this->marge_droite - $widthrecboxrecipient;
				$posxbb = $posx - 4 + $wreceipt;
				$posxbc = $posxba;
				$posyba = $posy - 0.5;
				$posybc = $logo_height + $this->marge_haute + 4 + $hreceipt;
				$posybd = $logo_height + $this->marge_haute + 8 + $hreceipt;
			} else {
				$hreceipt = $hautcadre;
				$wreceipt = $widthrecboxrecipient * 0.5;
				$posxba = $this->page_largeur - $this->marge_droite - $widthrecboxrecipient - $wreceipt;
				$posxbb = $posx - 4 + $widthrecboxrecipient + $wreceipt;
				$posxbc = $this->page_largeur - $this->marge_droite - $widthrecboxrecipient;
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
				$pdf->MultiCell($widthrecbox - 5, 4, $outputlangs->convToOutputCharset($conf->global->ULTIMATE_PDF_ALIAS_COMPANY), 0, 'L');
			} else {
				$pdf->MultiCell($widthrecbox - 5, 4, $outputlangs->convToOutputCharset($this->emetteur->name), 0, 'L');
			}

			$posy = $pdf->getY();

			// Show sender information
			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->SetXY($posx + 2, $posy);
			$pdf->writeHTMLCell($widthrecbox - 5, 4, $posx + 2, $posy, $carac_emetteur, 0, 2, 0, true, 'L', true);
			$posy = $pdf->getY();

			// Show private note from societe
			if (!empty($conf->global->MAIN_INFO_SOCIETE_NOTE) && !empty($this->emetteur->note_private)) {
				$pdf->SetFont('', '', $default_font_size - 1);
				$pdf->SetXY($posx + 2, $posy);
				$pdf->writeHTMLCell($widthrecbox - 5, 8, $posx + 2, $posy + 2, dol_string_nohtmltag($this->emetteur->note_private), 0, 1, 0, true, 'L', true);
			}

			// If BILLING and CUSTOMER contact defined, we use it
			if ($object->getIdContact('external', 'CUSTOMER') && $object->getIdContact('external', 'BILLING')) {

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
				$pdf->MultiCell($widthrecboxrecipient, 4, $outputlangs->transnoentities("TypeContact_fichinter_external_CUSTOMER"), 0, 'R');

				// Show recipient name
				$pdf->SetXY($posx + 2, $posy + 2);
				$pdf->SetFont('', 'B', $default_font_size);
				$pdf->MultiCell($wreceipt - 5, 4, $carac_client_name, 0, 'L');

				$posy = $pdf->getY();

				// Show recipient information
				$pdf->SetFont('', '', $default_font_size - 1);
				$pdf->SetXY($posx + 2, $posy);
				$pdf->writeHTMLCell($wreceipt - 5, 4, $posx + 2, $posy, $carac_client, 0, 2, 0, true, 'L', true);

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

				// Show shipping address
				$pdf->SetAlpha($opacity);
				$pdf->RoundedRect($posxbb, $posybc, $wreceipt, $hreceipt, $roundradius, '1111', $receiptstyle, $this->border_style, $bgcolor);
				$pdf->SetAlpha(1);
				$pdf->SetXY($posxbc, $posybd - 4);
				$pdf->MultiCell($widthrecboxrecipient, 4, $outputlangs->transnoentities("BillAddress"), 0, 'R');

				// Show recipient name
				$pdf->SetXY($posxbb + 2, $posybc + 2);
				$pdf->SetFont('', 'B', $default_font_size);
				$pdf->MultiCell($wreceipt - 5, 4, $carac_client_name, 0, 'L');

				$posybc = $pdf->getY();

				// Show recipient information
				$pdf->SetFont('', '', $default_font_size - 1);
				$pdf->SetXY($posxbb + 2, $posybc);
				$pdf->writeHTMLCell($wreceipt - 5, 4, $posxbb + 2, $posybc, $carac_client, 0, 2, 0, true, 'L', true);
			} elseif ($object->getIdContact('external', 'BILLING')) {
				// If BILLING contact defined on invoice, we use it
				$usecontact = false;
				$arrayidcontact = $object->getIdContact('external', 'BILLING');
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
				$posy = $logo_height + $this->marge_haute + $delta;
				$posx = $this->page_largeur - $this->marge_droite - $widthrecboxrecipient;
				if (!empty($conf->global->ULTIMATE_INVERT_SENDER_RECIPIENT) && ($conf->global->ULTIMATE_INVERT_SENDER_RECIPIENT != "no")) $posx = $this->marge_gauche;

				// Show recipient frame
				$pdf->SetTextColorArray($textcolor);
				$pdf->SetFont('', '', $default_font_size - 2);

				// Show billing address
				$pdf->SetXY($posx, $posy);
				$pdf->SetAlpha($opacity);
				$pdf->RoundedRect($posx, $posy, $widthrecboxrecipient, $hautcadre, $roundradius, '1111', $receiptstyle, $this->border_style, $bgcolor);
				$pdf->SetAlpha(1);
				$pdf->MultiCell($widthrecboxrecipient, 4, $outputlangs->transnoentities("BillAddress"), 0, 'R');

				$posy = $pdf->getY();

				// Show recipient name
				$pdf->SetXY($posx + 2, $posy + 3);
				$pdf->SetFont('', 'B', $default_font_size);
				$pdf->MultiCell($widthrecboxrecipient - 5, 4, $carac_client_name, 0, 'L');

				$posy = $pdf->getY();

				// Show recipient information
				$pdf->SetFont('', '', $default_font_size - 1);
				$pdf->SetXY($posx + 2, $posy);
				$pdf->writeHTMLCell($widthrecboxrecipient - 5, 4, $posx + 2, $posy, $carac_client, 0, 2, 0, true, 'L', true);
			} elseif ($object->getIdContact('external', 'CUSTOMER')) {
				// If CUSTOMER contact defined on order, we use it
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
				$carac_client = pdf_element_build_address($outputlangs, $this->emetteur, $object->thirdparty, ($usecontact ? $object->contact : ''), $usecontact, 'target', $object);

				// Show recipient
				$widthrecboxrecipient = $this->page_largeur - $this->marge_droite - $this->marge_gauche - $conf->global->ULTIMATE_WIDTH_RECBOX - 2;
				$posy = $logo_height + $this->marge_haute + $delta;
				$posx = $this->page_largeur - $this->marge_droite - $widthrecboxrecipient;
				if (!empty($conf->global->ULTIMATE_INVERT_SENDER_RECIPIENT) && ($conf->global->ULTIMATE_INVERT_SENDER_RECIPIENT != "no")) $posx = $this->marge_gauche;

				// Show recipient frame
				$pdf->SetTextColorArray($textcolor);
				$pdf->SetFont('', '', $default_font_size - 2);

				// Show Contact_commande_external_CUSTOMER address
				$pdf->SetAlpha($opacity);
				$pdf->RoundedRect($posx, $posy, $widthrecboxrecipient, $hautcadre, $roundradius, '1111', $receiptstyle, $this->border_style, $bgcolor);
				$pdf->SetAlpha(1);
				$pdf->SetXY($posx, $posy);
				$pdf->MultiCell($widthrecboxrecipient, 4, $outputlangs->transnoentities("TypeContact_commande_external_CUSTOMER"), 0, 'R');

				// Show recipient name
				$pdf->SetXY($posx + 2, $posy + 3);
				$pdf->SetFont('', 'B', $default_font_size);
				$pdf->MultiCell($widthrecboxrecipient - 5, 4, $carac_client_name, 0, 'L');

				$posy = $pdf->getY();

				// Show recipient information
				$pdf->SetFont('', '', $default_font_size - 1);
				$pdf->SetXY($posx + 2, $posy);
				$pdf->writeHTMLCell($widthrecboxrecipient - 5, 4, $posx + 2, $posy, $carac_client, 0, 2, 0, true, 'L', true);
			} else {
				$thirdparty = $object->thirdparty;
				// Recipient name
				$carac_client_name = pdfBuildThirdpartyName($thirdparty, $outputlangs);
				// Recipient address
				$carac_client = pdf_element_build_address($outputlangs, $this->emetteur, $object->thirdparty, '', 0, 'target', $object);

				// Show recipient
				$widthrecboxrecipient = $this->page_largeur - $this->marge_droite - $this->marge_gauche - $conf->global->ULTIMATE_WIDTH_RECBOX - 2;
				$posy = $logo_height + $this->marge_haute + $delta;
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
				$pdf->MultiCell($widthrecboxrecipient - 5, 4, $carac_client_name, 0, 'L');

				$posy = $pdf->getY();

				// Show recipient information
				$pdf->SetFont('', '', $default_font_size - 1);
				$pdf->SetXY($posx + 2, $posy);
				$pdf->writeHTMLCell($widthrecboxrecipient - 5, 4, $posx + 2, $posy, $carac_client, 0, 2, 0, true, 'L', true);
			}

			// Other informations

			$pdf->SetFillColor(255, 255, 255);

			// Date proposition
			$width = $tab_width / 4 - 1.5;
			$RoundedRectHeight = $this->marge_haute + $logo_height + $hautcadre + $delta + 2;
			$pdf->SetAlpha($opacity);
			$pdf->RoundedRect($this->marge_gauche, $RoundedRectHeight, $width, 6, $roundradius, '1001', 'FD', $this->border_style, $bgcolor);
			$pdf->SetAlpha(1);
			$pdf->SetFont('', 'B', $default_font_size - 2);
			$pdf->SetXY($this->marge_gauche, $RoundedRectHeight);
			$pdf->SetTextColorArray($textcolor);
			$pdf->MultiCell($width, 5, $outputlangs->transnoentities("Date"), 0, 'C', false);

			$pdf->SetFont('', '', $default_font_size - 2);
			$pdf->SetXY($this->marge_gauche, $RoundedRectHeight + 6);
			$pdf->SetTextColorArray($textcolor);
			$pdf->MultiCell($width, 6, dol_print_date($object->datec, "day", false, $outputlangs, true), '0', 'C');

			// Linked project
			$outputlangs->load('projects');
			$pdf->SetAlpha($opacity);
			$pdf->RoundedRect($this->marge_gauche + $width + 2, $RoundedRectHeight, $width, 6, $roundradius, '1001', 'FD', $this->border_style, $bgcolor);
			$pdf->SetAlpha(1);
			$pdf->SetFont('', 'B', $default_font_size - 2);
			$pdf->SetXY($this->marge_gauche + $width + 2, $RoundedRectHeight);
			$pdf->SetTextColorArray($textcolor);
			$pdf->MultiCell($width, 5, $outputlangs->transnoentities("RefProject"), 0, 'C', false);

			if (!empty($conf->global->ULTIMATE_FICHINTER_PDF_SHOW_PROJECT)) {
				$object->fetch_projet();
				if (!empty($object->project->ref)) {
					$langs->load('projects');
					$pdf->SetTextColorArray($textcolor);
					$pdf->SetFont('', '', $default_font_size - 2);
					$pdf->SetXY($this->marge_gauche + $width + 2, $RoundedRectHeight + 6);
					$pdf->MultiCell($width, 6, $outputlangs->transnoentities("Project") . " : " . (empty($object->project->ref) ? '' : $object->projet->ref), 0, 'C');
				}
			} else {
				$pdf->SetFont('', '', $default_font_size - 2);
				$pdf->SetXY($this->marge_gauche + $width + 2, $RoundedRectHeight + 6);
				$pdf->SetTextColorArray($textcolor);
				$pdf->SetFillColor(255, 255, 255);
				$pdf->MultiCell($this->marge_gauche + 35, 6, '', '0', 'C');
			}

			// Commercial Interlocutor
			$outputlangs->load('contracts');
			$pdf->SetAlpha($opacity);
			$pdf->RoundedRect($this->marge_gauche + $width * 2 + 4, $RoundedRectHeight, $width, 6, $roundradius, '1001', 'FD', $this->border_style, $bgcolor);
			$pdf->SetAlpha(1);
			$pdf->SetFont('', 'B', $default_font_size - 2);
			$pdf->SetXY($this->marge_gauche + $width * 2 + 4, $RoundedRectHeight);
			$pdf->SetTextColorArray($textcolor);
			$pdf->MultiCell($width, 5, $outputlangs->transnoentities("Contract"), 0, 'C', false);

			if ($object->fk_contrat) {
				$contratstatic = new Contrat($this->db);
				$contratstatic->fetch($object->fk_contrat);
				$pdf->SetFont('', '', $default_font_size - 2);
				$pdf->SetXY($this->marge_gauche + $width * 2 + 4, $RoundedRectHeight + 6);
				$pdf->SetTextColorArray($textcolor);
				$pdf->MultiCell($width, 7, $outputlangs->transnoentities($contratstatic->ref), '0', 'C');
			} else {
				$pdf->SetFont('', '', $default_font_size - 2);
				$pdf->SetXY($this->marge_gauche + $width * 2 + 4, $RoundedRectHeight + 6);
				$pdf->SetTextColorArray($textcolor);
				$pdf->SetFillColor(255, 255, 255);
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
		}
		$pdf->SetTextColorArray($textcolor);
	}

	/**
	 *   	Show footer of page
	 *   	@param      TCPDF     		PDF factory
	 * 		@param		object			Object invoice
	 *      @param      outputlangs		Object lang for output
	 * 		@remarks	Need this->emetteur object
	 */
	function _pagefoot(&$pdf, $object, $outputlangs, $hidefreetext = 0)
	{
		global $conf;
		$showdetails = $conf->global->MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS;
		return pdf_ultimatepagefoot($pdf, $outputlangs, 'FICHINTER_FREE_TEXT', $this->emetteur, $this->marge_basse, $this->marge_gauche, $this->page_hauteur, $object, $showdetails, $hidefreetext, $this->footertextcolor);
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
		if (!empty($conf->global->ULTIMATE_FICHINTER_WITH_LINE_NUMBER)) {
			$this->cols['num']['status'] = true;
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

		if (!empty($conf->global->ULTIMATE_FICHINTER_WITH_LINE_NUMBER) && $conf->global->ULTIMATE_PDF_BORDER_LEFT_STATUS == 'yes') {
			$this->cols['desc']['border-left'] = true;
		}

		$rank = $rank + 1000;
		$this->cols['date'] = array(
			'rank' => $rank,
			'status' => false,
			'width' => 30, // in mm
			'title' => array(
				'textkey' => 'Date'
			),
			'content' => array(
				'align' => 'R',
			),
			'border-left' => false, // add left line separator
		);

		if ($conf->global->ULTIMATE_GENERATE_FICHINTER_WITH_DATE) {
			$this->cols['date']['status'] = true;
		}
		if ($conf->global->ULTIMATE_PDF_BORDER_LEFT_STATUS == 'yes') {
			$this->cols['date']['border-left'] = true;
		}

		$rank = $rank + 1010;
		$this->cols['duration'] = array(
			'rank' => $rank,
			'width' => 20, // in mm
			'status' => false,
			'title' => array(
				'textkey' => 'TotalDuration'
			),
			'content' => array(
				'align' => 'R',
			),
			'border-left' => false, // add left line separator
		);

		if ($conf->global->ULTIMATE_GENERATE_FICHINTER_WITH_DURATION) {
			$this->cols['duration']['status'] = true;
		}
		if ($conf->global->ULTIMATE_PDF_BORDER_LEFT_STATUS == 'yes') {
			$this->cols['duration']['border-left'] = true;
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
					'border-left' => false, // add left line separator
				);
				if ($conf->global->ULTIMATE_PDF_BORDER_LEFT_STATUS == 'yes' &&  in_array($extrafields->attributes[$object->table_element]['param'][$key], $def)) {
					$def['border-left'] = true;
				}
				
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
	protected function _signature_area_double(&$pdf, $object, $posy, $outputlangs)
	{
		global $db, $langs, $conf;
		if (!empty($conf->mbisignature->enabled)) {
			dol_include_once("/mbisignature/class/signature_area.class.php");
			$signatureArea = new SignatureArea();
			return $signatureArea->_signature_area_double($pdf, $object, $posy, $outputlangs, $db, $object->ref, $langs, $this->page_largeur, $this->marge_droite, $this->emetteur_name, $this->thirdparty_name);
		}
	}
}

?>