<?php
/* Copyright (C) 2011-2019 Regis Houssin  <regis.houssin@capnetworks.com>
 * Copyright (C) 2011-2022 Philippe Grand <philippe.grand@atoo-net.com>
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
 */

/**
 *	\file       /ultimatepdf/class/actions_ultimatepdf.class.php
 *	\ingroup    ultimatepdf
 *	\brief      ultimatepdf designs actions class files
 */

dol_include_once('/ultimatepdf/class/dao_ultimatepdf.class.php','DaoUltimatepdf');
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/ajax.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';

/**
 *	\class      ActionsUltimatepdf
 *	\brief      Ultimatepdf designs actions class files
 */
class ActionsUltimatepdf
{
	/**
     * @var DoliDb Database handler
     */
    public $db;

	/**
     * @var object instance of class
     */
    public $dao;

	/**
     * @var string instance of class
     */
    public $mesg;

	/**
	 * @var string[] Array of error strings
	 */
	public $errors = array();

	/**
	 * @var int Error number
	 */
	public $errno = 0;

	/**
	 * @var int The object identifier
	 */
	public $id;

	/**
	 * @var	mixed template_dir
	 */
	public $template_dir;

	/**
	 * @var mixed template
	 */
	public $template;

	/**
	 * @var	string label
	 */
	public $label;

	/**
	 * @var	string description
	 */
	public $description;

	/**
	 * @var mixed value
	 */
	public $value;

	/**
	 * @var mixed cancel
	 */
	public $cancel;

	/**
	 * @var
	 */
	public $dashdotted;

	/**
	 * @var
	 */
	public $bgcolor;

	/**
	 * @var
	 */
	public $title_bgcolor;

	/**
	 * @var
	 */
	public $opacity;

	/**
	 * @var
	 */
	public $roundradius;

	/**
	 * @var
	 */
	public $bordercolor;

	/**
	 * @var
	 */
	public $receiptstyle;

	/**
	 * @var
	 */
	public $senderstyle;

	/**
	 * @var
	 */
	public $textcolor;

	/**
	 * @var
	 */
	public $footertextcolor;

	/**
	 * @var
	 */
	public $propalcolor;

	/**
	 * @var
	 */
	public $qrcodecolor;

	/**
	 * @var
	 */
	public $widthnumbering;

	/**
	 * @var
	 */
	public $widthdate;

	/**
	 * @var
	 */
	public $widthtype;

	/**
	 * @var
	 */
	public $widthproject;

	/**
	 * @var
	 */
	public $widthvat;

	/**
	 * @var
	 */
	public $widthup;

	/**
	 * @var
	 */
	public $widthqty;

	/**
	 * @var
	 */
	public $widthunit;

	/**
	 * @var
	 */
	public $widthdiscount;

	/**
	 * @var
	 */
	public $withref;

	/**
	 * @var
	 */
	public $widthref;

	/**
	 * @var
	 */
	public $withoutvat;

	/**
	 * @var
	 */
	public $showdetails;

	/**
	 * @var
	 */
	public $otherlogo;

	/**
	 * @var
	 */
	public $otherlogo_file;

	/**
	 * @var
	 */
	public $pdfbackground;

	/**
	 * @var
	 */
	public $pdfbackground_file;

	/**
	 * @var
	 */
	public $transparency;

	/**
	 * @var
	 */
	public $newfont;

	/**
	 * @var
	 */
	public $otherfont;

	/**
	 * @var
	 */
	public $heightforfreetext;

	/**
	 * @var
	 */
	public $freetextfontsize;

	/**
	 * @var
	 */
	public $background;

	/**
	 * @var
	 */
	public $background_file;

	/**
	 * @var
	 */
	public $backgroundx;

	/**
	 * @var
	 */
	public $backgroundy;

	/**
	 * @var
	 */
	public $imglinesize;

	/**
	 * @var
	 */
	public $logoheight;

	/**
	 * @var
	 */
	public $logowidth;

	/**
	 * @var
	 */
	public $otherlogoheight;

	/**
	 * @var
	 */
	public $otherlogowidth;

	/**
	 * @var
	 */
	public $invertSenderRecipient;

	/**
	 * @var
	 */
	public $widthrecbox;

	/**
	 * @var
	 */
	public $marge_gauche;

	/**
	 * @var
	 */
	public $marge_droite;

	/**
	 * @var
	 */
	public $marge_haute;

	/**
	 * @var
	 */
	public $marge_basse;

	/**
	 * @var boolean column line true or false
	 */
	public $borderleft;

	/**
	 * @var
	 */
	public $aliascompany;

	/**
	 * @var
	 */
	public $aliasaddress;

	/**
	 * @var
	 */
	public $aliaszip;

	/**
	 * @var
	 */
	public $aliastown;

	/**
	 * @var
	 */
	public $aliasphone;

	/**
	 * @var
	 */
	public $aliasfax;

	/**
	 * @var
	 */
	public $aliasemail;

	/**
	 * @var
	 */
	public $aliasurl;

	/**
	 * @var
	 */
	public $country_id;

	/**
	 * @var
	 */
	public $options = array();

	/**
	 * @var
	 */
	public $designs = array();

	/**
	 * @var
	 */
	public $tpl = array();

	/**
	 * @var string String displayed by executeHook() immediately after return
	 */
	public $resprints;


	/**
	 * 	Constructor
	 *
	 * 	@param	DoliDB	$db		Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 * Instantiation of DAO class
	 *
	 * @return	void
	 */
	private function getInstanceDao()
	{
		if (!is_object($this->dao)) {
			$this->dao = new DaoUltimatepdf($this->db);
		}
	}

	/**
	 * Overloading the doActions function : replacing the parent's function with the one below
	 *
	 * @param   array()         $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 */
	public function doActions($parameters, &$object, &$action)
	{
		global $conf, $user, $langs, $hookmanager;	

		// Translations
		$langs->loadLangs(array("companies", "dict", "ultimatepdf@ultimatepdf"));

		$error = 0; // Error counter

		$this->getInstanceDao();

		$id = GETPOST('id', 'int');
		$label = GETPOST('label', 'alpha');
		$description = GETPOST('description', 'alpha');
		$value = GETPOST('value', 'int');
		$dashdotted = GETPOST('dashdotted');
		$bgcolor = GETPOST('bgcolor');
		$title_bgcolor = GETPOST('title_bgcolor');
		$opacity = GETPOST('opacity');
		$roundradius = GETPOST('roundradius');
		$bordercolor = GETPOST('bordercolor');
		$senderstyle = GETPOST('senderstyle');
		$receiptstyle = GETPOST('receiptstyle');
		$textcolor = GETPOST('textcolor');
		$footertextcolor = GETPOST('footertextcolor');
		$propalcolor = GETPOST('propalcolor');
		$qrcodecolor = GETPOST('qrcodecolor');
		$widthnumbering = GETPOST('widthnumbering');
		$widthdate = GETPOST('widthdate');
		$widthtype = GETPOST('widthtype');
		$widthproject = GETPOST('widthproject');
		$widthvat = GETPOST('widthvat');
		$widthup = GETPOST('widthup');
		$widthunit = GETPOST('widthunit');
		$widthqty = GETPOST('widthqty');
		$widthdiscount = GETPOST('widthdiscount');
		$withref = GETPOST('withref');
		$widthref = GETPOST('widthref');
		$withoutvat = GETPOST('withoutvat');
		$showdetails = GETPOST('showdetails');
		$otherlogo = GETPOST('otherlogo');
		$otherlogo_file = GETPOST('otherlogo_file');
		$pdfbackground = GETPOST('pdfbackground');
		$pdfbackground_file = GETPOST('pdfbackground_file');
		$newfont = GETPOST('newfont');
		$otherfont = GETPOST('otherfont');
		$heightforfreetext = GETPOST('heightforfreetext');
		$freetextfontsize = GETPOST('freetextfontsize');
		$background = GETPOST('background');
		$backgroundx = GETPOST('backgroundx');
		$backgroundy = GETPOST('backgroundy');
		$background_file = GETPOST('background_file');
		$transparency = GETPOST('transparency');
		$imglinesize = GETPOST('imglinesize');
		$logoheight = GETPOST('logoheight');
		$logowidth = GETPOST('logowidth');
		$otherlogoheight = GETPOST('otherlogoheight');
		$otherlogowidth = GETPOST('otherlogowidth');
		$invertSenderRecipient = GETPOST('invertSenderRecipient');
		$widthrecbox = GETPOST('widthrecbox');
		$marge_gauche = GETPOST('marge_gauche');
		$marge_droite = GETPOST('marge_droite');
		$marge_haute = GETPOST('marge_haute');
		$marge_basse = GETPOST('marge_basse');
		$borderleft = GETPOST('borderleft');
		$aliascompany = GETPOST('aliascompany');
		$aliasaddress = GETPOST('aliasaddress');
		$aliaszip = GETPOST('aliaszip');
		$aliastown = GETPOST('aliastown');
		$aliasphone = GETPOST('aliasphone');
		$aliasfax = GETPOST('aliasfax');
		$aliasemail = GETPOST('aliasemail');
		$aliasurl = GETPOST('aliasurl');
		$country_id = GETPOST('country_id', 'int');
		$urlfile = urldecode(GETPOST('urlfile', 'alpha'));
		$confirm = GETPOST('confirm', 'alpha');
		$type = GETPOST('type', 'alpha');
		//$typelabel = GETPOST('typelabel', 'alpha');

		// Action to merge documents on each element (invoices, proposal etc...)
		if ($action == 'filemerging' && is_object($object) && !empty($object->id)
		) {
			dol_include_once('/ultimatepdf/class/documentmergedpdf.class.php');

			$filetomerge_file_array = GETPOST('filetoadd');

			//Delete all files already associated
			$filetomerge = new DocumentMergedPdf($this->db);

			$filetomerge->delete_by_element($user);

			//for each file checked add it to the $object->element
			if (is_array($filetomerge_file_array)) {
				foreach ($filetomerge_file_array as $filetomerge_file) {
					$filetomerge->fk_element = $object->id;
					$filetomerge->file_name = $filetomerge_file;
					$filetomerge->element_name = $object->element;
					$ret = $filetomerge->create($user);
					if ($ret == -1) {
						$langs->load("errors");
						setEventMessages($langs->trans("ErrorBadCreat"), null, 'errors');
					}
				}
			}
			return 0;
		}

		if (GETPOST('add') && empty($this->cancel) && $user->admin) {
			$error = 0;

		/*	if (!$label) {
				$error++;
				array_push($this->errors, $langs->trans("ErrorFieldRequired", $langs->transnoentities("Label")));
				$action = 'create';
			}*/

			// Verify if label already exist in database
			if ($label) {
				$this->dao->getDesigns();
				if (!empty($this->dao->designs)) {
					$label = strtolower(trim($label));

					foreach ($this->dao->designs as $design) {
						if (strtolower($design->label) == $label) $error++;
					}
					if ($error) {
						array_push($this->errors, $langs->trans("ErrorDesignLabelAlreadyExist"));
						$action = 'create';
					}
				}
			}

			if (!$error) {
				$this->db->begin();

				$this->dao->label = $label;
				$this->dao->description = $description;

				$this->dao->options['dashdotted'] = $dashdotted;
				$this->dao->options['bgcolor'] = $bgcolor;
				$this->dao->options['title_bgcolor'] = $title_bgcolor;
				$this->dao->options['opacity'] = $opacity;
				$this->dao->options['roundradius'] = $roundradius;
				$this->dao->options['bordercolor'] = $bordercolor;
				$this->dao->options['senderstyle'] = $senderstyle;
				$this->dao->options['receiptstyle'] = $receiptstyle;
				$this->dao->options['textcolor'] = $textcolor;
				$this->dao->options['footertextcolor'] = $footertextcolor;
				$this->dao->options['propalcolor'] = $propalcolor;
				$this->dao->options['qrcodecolor'] = $qrcodecolor;
				$this->dao->options['widthnumbering'] = $widthnumbering;
				$this->dao->options['widthdate'] = $widthdate;
				$this->dao->options['widthtype'] = $widthtype;
				$this->dao->options['widthproject'] = $widthproject;
				$this->dao->options['widthvat'] = $widthvat;
				$this->dao->options['widthup'] = $widthup;
				$this->dao->options['widthqty'] = $widthqty;
				$this->dao->options['widthunit'] = $widthunit;
				$this->dao->options['widthdiscount'] = $widthdiscount;
				$this->dao->options['withref'] = $withref;
				$this->dao->options['widthref'] = $widthref;
				$this->dao->options['withoutvat'] = $withoutvat;
				$this->dao->options['showdetails'] = $showdetails;
				$this->dao->options['otherlogo'] = $otherlogo;
				$this->dao->options['otherlogo_file'] = $otherlogo_file;
				$this->dao->options['pdfbackground'] = $pdfbackground;
				$this->dao->options['pdfbackground_file'] = $pdfbackground_file;
				$this->dao->options['newfont'] = $newfont;
				$this->dao->options['otherfont'] = $otherfont;
				$this->dao->options['heightforfreetext'] = $heightforfreetext;
				$this->dao->options['freetextfontsize'] = $freetextfontsize;
				$this->dao->options['background'] = $background;
				$this->dao->options['background_file'] = $background_file;
				$this->dao->options['backgroundx'] = $backgroundx;
				$this->dao->options['backgroundy'] = $backgroundy;
				$this->dao->options['transparency'] = $transparency;
				$this->dao->options['imglinesize'] = $imglinesize;
				$this->dao->options['logoheight'] = $logoheight;
				$this->dao->options['logowidth'] = $logowidth;
				$this->dao->options['otherlogoheight'] = $otherlogoheight;
				$this->dao->options['otherlogowidth'] = $otherlogowidth;
				$this->dao->options['invertSenderRecipient'] = $invertSenderRecipient;
				$this->dao->options['widthrecbox'] = $widthrecbox;
				$this->dao->options['marge_gauche'] = $marge_gauche;
				$this->dao->options['marge_droite'] = $marge_droite;
				$this->dao->options['marge_haute'] = $marge_haute;
				$this->dao->options['marge_basse'] = $marge_basse;
				$this->dao->options['borderleft'] = $borderleft;
				$this->dao->options['aliascompany'] = $aliascompany;
				$this->dao->options['aliasaddress'] = $aliasaddress;
				$this->dao->options['aliaszip'] = $aliaszip;
				$this->dao->options['aliastown'] = $aliastown;
				$this->dao->options['aliasphone'] = $aliasphone;
				$this->dao->options['aliasfax'] = $aliasfax;
				$this->dao->options['aliasemail'] = $aliasemail;
				$this->dao->options['aliasurl'] = $aliasurl;
				$this->dao->options['country_id'] = $country_id;
				//$this->dao->options['typelabel'] = $typelabel;

				$id = $this->dao->create($user);
				if ($id <= 0) {
					$error++;
					$errors = ($this->dao->error ? array($this->dao->error) : $this->dao->errors);
					$action = 'create';
				}

				if (!$error && $id > 0) {
					$this->db->commit();
				} else {
					$this->db->rollback();
				}
			}
		}

		if ($action == 'edit' && $user->admin) {
			$error = 0;

			if ($this->dao->fetch($id) < 0) {
				$error++;
				//array_push($this->errors, $langs->trans("ErrorDesignIsNotValid"));
				$_GET["action"] = $_POST["action"] = '';
			}
		}

		if (GETPOST('update') && $id && $user->admin) {
			$error = 0;

			$ret = $this->dao->fetch($id);
			if ($ret < 0) {
				$error++;
				array_push($this->errors, $langs->trans("ErrorDesignIsNotValid"));
				$action = '';
			} else if (!$label) {
				$error++;
				array_push($this->errors, $langs->trans("ErrorFieldRequired", $langs->transnoentities("Label")));
				$action = 'edit';
			}

			if (!$error) {
				$this->db->begin();

				$this->dao->label = $label;
				$this->dao->description	= $description;

				$this->dao->options['dashdotted'] = (GETPOST('dashdotted') ? GETPOST('dashdotted') : null);
				$this->dao->options['bgcolor'] = (GETPOST('bgcolor') ? GETPOST('bgcolor') : null);
				$this->dao->options['title_bgcolor'] = (GETPOST('title_bgcolor') ? GETPOST('title_bgcolor') : null);
				$this->dao->options['opacity'] = (GETPOST('opacity') ? GETPOST('opacity') : null);
				$this->dao->options['roundradius'] = (GETPOST('roundradius') ? GETPOST('roundradius') : null);
				$this->dao->options['bordercolor'] = (GETPOST('bordercolor') ? GETPOST('bordercolor') : null);
				$this->dao->options['senderstyle'] = (GETPOST('senderstyle') ? GETPOST('senderstyle') : null);
				$this->dao->options['receiptstyle'] = (GETPOST('receiptstyle') ? GETPOST('receiptstyle') : null);
				$this->dao->options['textcolor'] = (GETPOST('textcolor') ? GETPOST('textcolor') : null);
				$this->dao->options['footertextcolor'] = (GETPOST('footertextcolor') ? GETPOST('footertextcolor') : null);
				$this->dao->options['propalcolor'] = (GETPOST('propalcolor') ? GETPOST('propalcolor') : null);
				$this->dao->options['qrcodecolor'] = (GETPOST('qrcodecolor') ? GETPOST('qrcodecolor') : null);
				$this->dao->options['widthnumbering'] = (GETPOST('widthnumbering') ? GETPOST('widthnumbering') : null);
				$this->dao->options['widthdate'] = (GETPOST('widthdate') ? GETPOST('widthdate') : null);
				$this->dao->options['widthtype'] = (GETPOST('widthtype') ? GETPOST('widthtype') : null);
				$this->dao->options['widthproject'] = (GETPOST('widthproject') ? GETPOST('widthproject') : null);
				$this->dao->options['widthvat'] = (GETPOST('widthvat') ? GETPOST('widthvat') : null);
				$this->dao->options['widthup'] = (GETPOST('widthup') ? GETPOST('widthup') : null);
				$this->dao->options['widthqty'] = (GETPOST('widthqty') ? GETPOST('widthqty') : null);
				$this->dao->options['widthunit'] = (GETPOST('widthunit') ? GETPOST('widthunit') : null);
				$this->dao->options['widthdiscount'] = (GETPOST('widthdiscount') ? GETPOST('widthdiscount') : null);
				$this->dao->options['withref'] = (GETPOST('withref') ? GETPOST('withref') : 'no');
				$this->dao->options['widthref'] = (GETPOST('widthref') ? GETPOST('widthref') : null);
				$this->dao->options['withoutvat'] = (GETPOST('withoutvat') ? GETPOST('withoutvat') : 'no');
				$this->dao->options['showdetails'] = (GETPOST('showdetails') ? GETPOST('showdetails') : null);
				$this->dao->options['otherlogo'] = (GETPOST('otherlogo') ? GETPOST('otherlogo') : null);
				$this->dao->options['otherlogo_file'] = (GETPOST('otherlogo_file') ? GETPOST('otherlogo_file') : null);
				$this->dao->options['pdfbackground'] = (GETPOST('pdfbackground') ? GETPOST('pdfbackground') : null);
				$this->dao->options['pdfbackground_file'] = (GETPOST('pdfbackground_file') ? GETPOST('pdfbackground_file') : null);
				$this->dao->options['newfont'] = (GETPOST('newfont') ? GETPOST('newfont') : null);
				$this->dao->options['otherfont'] = (GETPOST('otherfont') ? GETPOST('otherfont') : null);
				$this->dao->options['heightforfreetext'] = (GETPOST('heightforfreetext') ? GETPOST('heightforfreetext') : null);
				$this->dao->options['freetextfontsize'] = (GETPOST('freetextfontsize') ? GETPOST('freetextfontsize') : null);
				$this->dao->options['background'] = (GETPOST('background') ? GETPOST('background') : null);
				$this->dao->options['background_file'] = (GETPOST('background_file') ? GETPOST('background_file') : null);
				$this->dao->options['backgroundx'] = (GETPOST('backgroundx') ? GETPOST('backgroundx') : null);
				$this->dao->options['backgroundy'] = (GETPOST('backgroundy') ? GETPOST('backgroundy') : null);
				$this->dao->options['transparency'] = (GETPOST('transparency') ? GETPOST('transparency') : null);
				$this->dao->options['imglinesize'] = (GETPOST('imglinesize') ? GETPOST('imglinesize') : null);
				$this->dao->options['logoheight'] = (GETPOST('logoheight') ? GETPOST('logoheight') : null);
				$this->dao->options['logowidth'] = (GETPOST('logowidth') ? GETPOST('logowidth') : null);
				$this->dao->options['otherlogoheight'] = (GETPOST('otherlogoheight') ? GETPOST('otherlogoheight') : null);
				$this->dao->options['otherlogowidth'] = (GETPOST('otherlogowidth') ? GETPOST('otherlogowidth') : null);
				$this->dao->options['invertSenderRecipient'] = (GETPOST('invertSenderRecipient') ? GETPOST('invertSenderRecipient') : 'no');
				$this->dao->options['widthrecbox'] = (GETPOST('widthrecbox') ? GETPOST('widthrecbox') : null);
				$this->dao->options['marge_gauche'] = (GETPOST('marge_gauche') ? GETPOST('marge_gauche') : null);
				$this->dao->options['marge_droite'] = (GETPOST('marge_droite') ? GETPOST('marge_droite') : null);
				$this->dao->options['marge_haute'] = (GETPOST('marge_haute') ? GETPOST('marge_haute') : null);
				$this->dao->options['marge_basse'] = (GETPOST('marge_basse') ? GETPOST('marge_basse') : null);
				$this->dao->options['borderleft'] = (GETPOST('borderleft') ? GETPOST('borderleft') : null);
				$this->dao->options['aliascompany'] = (GETPOST('aliascompany') ? GETPOST('aliascompany') : null);
				$this->dao->options['aliasaddress'] = (GETPOST('aliasaddress') ? GETPOST('aliasaddress') : null);
				$this->dao->options['aliaszip'] = (GETPOST('aliaszip') ? GETPOST('aliaszip') : null);
				$this->dao->options['aliastown'] = (GETPOST('aliastown') ? GETPOST('aliastown') : null);
				$this->dao->options['aliasphone'] = (GETPOST('aliasphone') ? GETPOST('aliasphone') : null);
				$this->dao->options['aliasfax'] = (GETPOST('aliasfax') ? GETPOST('aliasfax') : null);
				$this->dao->options['aliasemail'] = (GETPOST('aliasemail') ? GETPOST('aliasemail') : null);
				$this->dao->options['aliasurl'] = (GETPOST('aliasurl') ? GETPOST('aliasurl') : null);
				$this->dao->options['country_id'] = (GETPOST('country_id') ? GETPOST('country_id') : null);
				//$this->dao->options['typelabel'] = (GETPOST('typelabel') ? GETPOST('typelabel') : null);

				$ret = $this->dao->update($id, $user);

				if ($ret <= 0) {
					$error++;
					$errors = ($this->dao->error ? array($this->dao->error) : $this->dao->errors);
					$action = 'edit';
				}

				if (!$error && $ret > 0) {
					dolibarr_set_const($this->db, "ULTIMATE_DASH_DOTTED", $dashdotted, 'chaine', 0, '', $conf->entity);

					dolibarr_set_const($this->db, "ULTIMATE_BGCOLOR_COLOR", $bgcolor, 'chaine', 0, '', $conf->entity);

					dolibarr_set_const($this->db, "MAIN_PDF_TITLE_BACKGROUND_COLOR", $title_bgcolor, 'chaine', 0, '', $conf->entity);

					dolibarr_set_const($this->db, "ULTIMATE_BGCOLOR_OPACITY", $opacity, 'chaine', 0, '', $conf->entity);

					dolibarr_set_const($this->db, "ULTIMATE_SET_RADIUS", $roundradius, 'chaine', 0, '', $conf->entity);

					dolibarr_set_const($this->db, "ULTIMATE_BORDERCOLOR_COLOR", $bordercolor, 'chaine', 0, '', $conf->entity);

					dolibarr_set_const($this->db, "ULTIMATE_SENDER_STYLE", $senderstyle, 'chaine', 0, '', $conf->entity);

					dolibarr_set_const($this->db, "ULTIMATE_RECEIPT_STYLE", $receiptstyle, 'chaine', 0, '', $conf->entity);

					dolibarr_set_const($this->db, "ULTIMATE_TEXTCOLOR_COLOR", $textcolor, 'chaine', 0, '', $conf->entity);

					dolibarr_set_const($this->db, "ULTIMATE_FOOTERTEXTCOLOR_COLOR", $footertextcolor, 'chaine', 0, '', $conf->entity);

					dolibarr_set_const($this->db, "ULTIMATE_PROPAL_COLOR", $propalcolor, 'chaine', 0, '', $conf->entity);

					dolibarr_set_const($this->db, "ULTIMATE_QRCODECOLOR_COLOR", $qrcodecolor, 'chaine', 0, '', $conf->entity);

					dolibarr_set_const($this->db, "ULTIMATE_DOCUMENTS_WITH_NUMBERING_WIDTH", $widthnumbering, 'chaine', 0, '', $conf->entity);

					dolibarr_set_const($this->db, "ULTIMATE_DOCUMENTS_WITH_DATE_WIDTH", $widthdate, 'chaine', 0, '', $conf->entity);

					dolibarr_set_const($this->db, "ULTIMATE_DOCUMENTS_WITH_TYPE_WIDTH", $widthtype, 'chaine', 0, '', $conf->entity);

					dolibarr_set_const($this->db, "ULTIMATE_DOCUMENTS_WITH_PROJECT_WIDTH", $widthproject, 'chaine', 0, '', $conf->entity);

					dolibarr_set_const($this->db, "ULTIMATE_DOCUMENTS_WITH_TVA_WIDTH", $widthvat, 'chaine', 0, '', $conf->entity);

					dolibarr_set_const($this->db, "ULTIMATE_DOCUMENTS_WITH_UP_WIDTH", $widthup, 'chaine', 0, '', $conf->entity);

					dolibarr_set_const($this->db, "ULTIMATE_DOCUMENTS_WITH_QTY_WIDTH", $widthqty, 'chaine', 0, '', $conf->entity);

					dolibarr_set_const($this->db, "ULTIMATE_DOCUMENTS_WITH_UNIT_WIDTH", $widthunit, 'chaine', 0, '', $conf->entity);

					dolibarr_set_const($this->db, "ULTIMATE_DOCUMENTS_WITH_DISCOUNT_WIDTH", $widthdiscount, 'chaine', 0, '', $conf->entity);

					dolibarr_set_const($this->db, "ULTIMATE_DOCUMENTS_WITH_REF", $withref, 'chaine', 0, '', $conf->entity);

					dolibarr_set_const($this->db, "ULTIMATE_DOCUMENTS_WITH_REF_WIDTH", $widthref, 'chaine', 0, '', $conf->entity);

					dolibarr_set_const($this->db, "ULTIMATE_GENERATE_DOCUMENTS_WITHOUT_VAT", $withoutvat, 'chaine', 0, '', $conf->entity);

					dolibarr_set_const($this->db, "MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS", $showdetails, 'chaine', 0, '', $conf->entity);

					$id = $this->dao->id; //to get id
					$dirforimage = $conf->ultimatepdf->dir_output . '/otherlogo/' . $id . '/';
					$arrayofimages = array('otherlogo', 'otherlogo_squarred');
					foreach ($arrayofimages as $varforimage) {
						if (!empty($_FILES[$varforimage]["name"]) && !preg_match('/(\.jpeg|\.jpg|\.png)$/i', $_FILES[$varforimage]["name"])) {	// Logo can be used on a lot of different places. Only jpg and png can be supported.
							$langs->load("errors");
							setEventMessages($langs->trans("ErrorBadImageFormat"), null, 'errors');
							break;
						}
						if (!empty($_FILES[$varforimage]["tmp_name"])) {
							$reg = array();
							if (preg_match('/([^\\/:]+)$/i', $_FILES[$varforimage]["name"], $reg)) {
								$otherlogo = $reg[1];

								//$extension = pathinfo($_FILES[$varforimage]['name'], PATHINFO_EXTENSION);
								//if (GETPOST('name', 'alpha')) $otherlogoname = GETPOST('name', 'alpha') . '.' . $extension;
								//$otherlogo = $otherlogoname ? $otherlogoname : $otherlogo;

								$isimage = image_format_supported($otherlogo);
								if ($isimage >= 0) {
									dol_syslog("Move file " . $_FILES[$varforimage]["tmp_name"] . " to " . $dirforimage . $otherlogo);
									if (!is_dir($dirforimage)) {
										dol_mkdir($dirforimage);
									}
									$result = dol_move_uploaded_file($_FILES[$varforimage]["tmp_name"], $dirforimage . $otherlogo, 1, 0, $_FILES[$varforimage]['error']);

									if ($result > 0) {
										$constant = "ULTIMATE_OTHERLOGO";
										if ($varforimage == 'otherlogo_squarred') $constant = "ULTIMATE_OTHERLOGO_SQUARRED";

										dolibarr_set_const($this->db, $constant, $otherlogo, 'chaine', 0, '', $conf->entity);
										$this->dao->options['otherlogo'] = $otherlogo;
										$ret = $this->dao->update($id, $user);
										if ($ret <= 0) {
											$error++;
											$errors = ($this->dao->error ? array($this->dao->error) : $this->dao->errors);
											$action = 'edit';
										}

										// Create thumbs of logo (Note that PDF use original file and not thumbs)
										if ($isimage > 0) {
											// Create thumbs
											//$object->addThumbs($newfile);    // We can't use addThumbs here yet because we need name of generated thumbs to add them into constants. TODO Check if need such constants. We should be able to retreive value with get...

											// Create small thumb, Used on logon for example
											$imgThumbSmall = vignette($dirforimage . $otherlogo, 160, 120, '_small', 50);
											if (image_format_supported($imgThumbSmall) >= 0 && preg_match('/([^\\/:]+)$/i', $imgThumbSmall, $reg)) {
												$imgThumbSmall = $reg[1]; // Save only basename
												dolibarr_set_const($this->db, $constant . "_SMALL", $imgThumbSmall, 'chaine', 0, '', $conf->entity);
											} else dol_syslog($imgThumbSmall);
										} else dol_syslog("ErrorImageFormatNotSupported", LOG_WARNING);
									} elseif (preg_match('/^ErrorFileIsInfectedWithAVirus/', $result)) {
										$error++;
										$langs->load("errors");
										$tmparray = explode(':', $result);
										setEventMessages($langs->trans('ErrorFileIsInfectedWithAVirus', $tmparray[1]), null, 'errors');
									} else {
										$error++;
										setEventMessages($langs->trans("ErrorFailedToSaveFile"), null, 'errors');
									}
								} else {
									$error++;
									$langs->load("errors");
									setEventMessages($langs->trans("ErrorBadImageFormat"), null, 'errors');
								}
							}
						}
					}

					dolibarr_set_const($this->db, "ULTIMATE_OTHERLOGO_FILE", $otherlogo_file, 'chaine', 0, '', $conf->entity);

					if ($_FILES["pdfbackground"]["tmp_name"]) {
						if (preg_match('/([^\\/:]+)$/i', $_FILES["pdfbackground"]["name"], $reg)) {
							$id = $this->dao->id; //to get id
							$pdfbackground = $reg[1];
							if (strpos($pdfbackground, '.pdf') !== false) {
								dol_syslog("Move file " . $_FILES["pdfbackground"]["tmp_name"] . " to " . $conf->ultimatepdf->dir_output . '/backgroundpdf/' . $id . '/' . $pdfbackground);
								if (!is_dir($conf->ultimatepdf->dir_output . '/backgroundpdf/' . $id . '/')) {
									dol_mkdir($conf->ultimatepdf->dir_output . '/backgroundpdf/' . $id . '/');
								}
								$result = dol_move_uploaded_file($_FILES["pdfbackground"]["tmp_name"], $conf->ultimatepdf->dir_output . '/backgroundpdf/' . $id . '/' . $pdfbackground, 1, 0, $_FILES['pdfbackground']['error']);
								if ($result > 0) {
									dolibarr_set_const($this->db, "ULTIMATEPDF_ADD_PDF_BACKGROUND", $pdfbackground, 'chaine', 0, '', $conf->entity);
									$this->dao->options['pdfbackground'] = $pdfbackground;
								} else if (preg_match('/^ErrorFileIsInfectedWithAVirus/', $result)) {
									$langs->load("errors");
									$tmparray = explode(':', $result);
									setEventMessages($langs->trans('ErrorFileIsInfectedWithAVirus', $tmparray[1]), null, 'errors');
									$error++;
								} else {
									setEventMessages($langs->trans("ErrorFailedToSaveFile"), null, 'errors');
									$error++;
								}
							} else {
								setEventMessages($langs->trans("ErrorOnlyPDFSupported"), null, 'errors');
								$error++;
							}
						}
					}

					try {
						if (!dolibarr_set_const($this->db, "ULTIMATEPDF_ADD_PDF_BACKGROUND_FILE", $pdfbackground_file, 'chaine', 0, '', $conf->entity))
							throw new Exception('Error saving ULTIMATEPDF_ADD_PDF_BACKGROUND_FILE with value: ' . $pdfbackground_file);
					} catch (Throwable $e) {
						$error++;
						setEventMessages($e->getMessage(), null, 'errors');
					}

					dolibarr_set_const($this->db, "ULTIMATEPDF_NEW_FONT", $newfont, 'chaine', 0, '', $conf->entity);

					try {
						if (!dolibarr_set_const($this->db, "MAIN_PDF_FORCE_FONT", $otherfont, 'chaine', 0, '', $conf->entity))
							throw new Exception('Error saving MAIN_PDF_FORCE_FONT with value: ' . $otherfont);
					} catch (Throwable $e) {
						$error++;
						setEventMessages($e->getMessage(), null, 'errors');
					}

					try {
						if (!dolibarr_set_const($this->db, "MAIN_PDF_FREETEXT_HEIGHT", $heightforfreetext, 'chaine', 0, '', $conf->entity))
							throw new Exception('Error saving MAIN_PDF_FREETEXT_HEIGHT with value: ' . $heightforfreetext);
					} catch (Throwable $e) {
						$error++;
						setEventMessages($e->getMessage(), null, 'errors');
					}

					try {
						if (!dolibarr_set_const($this->db, "ULTIMATEPDF_FREETEXT_FONT_SIZE", (int)$freetextfontsize, 'chaine', 0, '', $conf->entity))
							throw new Exception('Error saving ULTIMATEPDF_FREETEXT_FONT_SIZE with value: ' . $freetextfontsize);
					} catch (Throwable $e) {
						$error++;
						setEventMessages($e->getMessage(), null, 'errors');
					}

					// Use background on pdf
					$id = $this->dao->id; //to get id
					$dirforimage = $conf->ultimatepdf->dir_output . '/background/' . $id . '/';
					$arrayofimages = array('background', 'background_small');
					foreach ($arrayofimages as $varforimage) {
						if (!empty($_FILES[$varforimage]["tmp_name"])) {
							$reg = array();
							if (preg_match('/([^\\/:]+)$/i', $_FILES[$varforimage]["name"], $reg)) {
								$background = $reg[1];

								//$extension = pathinfo($_FILES[$varforimage]['name'], PATHINFO_EXTENSION);
								//if (GETPOST('name', 'alpha')) $backgroundname = GETPOST('name', 'alpha') . '.' . $extension;		
								//$background = $backgroundname ? $backgroundname : $background;

								$isimage = image_format_supported($background);
								
								if ($isimage >= 0) {
									dol_syslog("Move file " . $_FILES[$varforimage]["tmp_name"] . " to " . $dirforimage . $background);
									if (!is_dir($dirforimage)) {
										dol_mkdir($dirforimage);
									}
									$result = dol_move_uploaded_file($_FILES[$varforimage]["tmp_name"], $dirforimage . $background, 1, 0, $_FILES[$varforimage]['error']);

									if ($result > 0) {
										dolibarr_set_const($this->db, "ULTIMATE_USE_BACKGROUND_ON_PDF", $background, 'chaine', 0, '', $conf->entity);
										$this->dao->options['background'] = $background;
										$ret = $this->dao->update($id, $user);
										if ($ret <= 0) {
											$error++;
											$errors = ($this->dao->error ? array($this->dao->error) : $this->dao->errors);
											$action = 'edit';
										}

										// Create thumbs of logo (Note that PDF use original file and not thumbs)
										if ($isimage > 0) {
											// Create thumbs
											//$object->addThumbs($newfile);    // We can't use addThumbs here yet because we need name of generated thumbs to add them into constants. TODO Check if need such constants. We should be able to retreive value with get...

											// Create small thumb, Used on logon for example
											$imgThumbSmall = vignette($dirforimage . $background, 160, 120, '_small', 50);
											if (image_format_supported($imgThumbSmall) >= 0 && preg_match('/([^\\/:]+)$/i', $imgThumbSmall, $reg)) {
												$imgThumbSmall = $reg[1]; // Save only basename
												dolibarr_set_const($this->db, $constant . "_SMALL", $imgThumbSmall, 'chaine', 0, '', $conf->entity);
											} else dol_syslog($imgThumbSmall);
										} else dol_syslog("ErrorImageFormatNotSupported", LOG_WARNING);
									} elseif (preg_match('/^ErrorFileIsInfectedWithAVirus/', $result)) {
										$langs->load("errors");
										$tmparray = explode(':', $result);
										setEventMessages($langs->trans('ErrorFileIsInfectedWithAVirus', null, $tmparray[1]), 'errors');
										$error++;
									} else {
										setEventMessages($langs->trans("ErrorFailedToSaveFile"), null, 'errors');
										$error++;
									}
								} else {
									$error++;
									$langs->load("errors");
									setEventMessages($langs->trans("ErrorBadImageFormat"), null, 'errors');
								}
							}
						}
					}

					try {
						if (!dolibarr_set_const($this->db, "ULTIMATE_USE_BACKGROUND_ON_PDF_FILE", $background_file, 'chaine', 0, '', $conf->entity))
							throw new Exception('Error saving ULTIMATE_USE_BACKGROUND_ON_PDF_FILE with value: ' . $background_file);
						} catch (Throwable $e) {
							$error++;
							setEventMessages($e->getMessage(), null, 'errors');
						}
					try {
						if (!dolibarr_set_const($this->db, "ULTIMATE_USE_BACKGROUND_ON_PDF_X", (int)$backgroundx, 'chaine', 0, '', $conf->entity))
							throw new Exception('Error saving ULTIMATE_USE_BACKGROUND_ON_PDF_X with value: ' . $backgroundx);
					} catch (Throwable $e) {
						$error++;
						setEventMessages($e->getMessage(), null, 'errors');
					}
					dolibarr_set_const($this->db, "ULTIMATE_USE_BACKGROUND_ON_PDF_Y", (int)$backgroundy, 'chaine', 0, '', $conf->entity);

					dolibarr_set_const($this->db, "BACKGROUND_IMAGE_TRANSPARENCY", $transparency, 'chaine', 0, '', $conf->entity);

					dolibarr_set_const($this->db, "MAIN_DOCUMENTS_WITH_PICTURE_WIDTH", $imglinesize, 'chaine', 0, '', $conf->entity);

					dolibarr_set_const($this->db, "ULTIMATE_LOGO_HEIGHT", $logoheight, 'chaine', 0, '', $conf->entity);

					dolibarr_set_const($this->db, "ULTIMATE_LOGO_WIDTH", $logowidth, 'chaine', 0, '', $conf->entity);

					dolibarr_set_const($this->db, "ULTIMATE_OTHERLOGO_HEIGHT", $otherlogoheight, 'chaine', 0, '', $conf->entity);

					dolibarr_set_const($this->db, "ULTIMATE_OTHERLOGO_WIDTH", $otherlogowidth, 'chaine', 0, '', $conf->entity);

					dolibarr_set_const($this->db, "ULTIMATE_INVERT_SENDER_RECIPIENT", $invertSenderRecipient, 'chaine', 0, '', $conf->entity);

					dolibarr_set_const($this->db, "ULTIMATE_WIDTH_RECBOX", $widthrecbox, 'chaine', 0, '', $conf->entity);

					dolibarr_set_const($this->db, "ULTIMATE_PDF_MARGIN_LEFT", $marge_gauche, 'chaine', 0, '', $conf->entity);

					dolibarr_set_const($this->db, "ULTIMATE_PDF_MARGIN_RIGHT", $marge_droite, 'chaine', 0, '', $conf->entity);

					dolibarr_set_const($this->db, "ULTIMATE_PDF_MARGIN_TOP", $marge_haute, 'chaine', 0, '', $conf->entity);

					dolibarr_set_const($this->db, "ULTIMATE_PDF_MARGIN_BOTTOM", $marge_basse, 'chaine', 0, '', $conf->entity);

					dolibarr_set_const($this->db, "ULTIMATE_PDF_BORDER_LEFT_STATUS", $borderleft, 'chaine', 0, '', $conf->entity);

					dolibarr_set_const($this->db, "ULTIMATE_PDF_ALIAS_COMPANY", $aliascompany, 'chaine', 0, '', $conf->entity);

					dolibarr_set_const($this->db, "ULTIMATE_PDF_ALIAS_ADDRESS_EMETTEUR", $aliasaddress, 'chaine', 0, '', $conf->entity);

					dolibarr_set_const($this->db, "ULTIMATE_PDF_ALIAS_ZIP_EMETTEUR", $aliaszip, 'chaine', 0, '', $conf->entity);

					dolibarr_set_const($this->db, "ULTIMATE_PDF_ALIAS_TOWN_EMETTEUR", $aliastown, 'chaine', 0, '', $conf->entity);

					dolibarr_set_const($this->db, "ULTIMATE_PDF_ALIAS_PHONE_EMETTEUR", $aliasphone, 'chaine', 0, '', $conf->entity);

					dolibarr_set_const($this->db, "ULTIMATE_PDF_ALIAS_FAX_EMETTEUR", $aliasfax, 'chaine', 0, '', $conf->entity);

					dolibarr_set_const($this->db, "ULTIMATE_PDF_ALIAS_EMAIL_EMETTEUR", $aliasemail, 'chaine', 0, '', $conf->entity);

					dolibarr_set_const($this->db, "ULTIMATE_PDF_ALIAS_URL_EMETTEUR", $aliasurl, 'chaine', 0, '', $conf->entity);

					$country_id = GETPOST('country_id', 'int');
					$tmparray = getCountry($country_id, 'all');
					$country_code	= $tmparray['code'];
					$country_label	= $tmparray['label'];

					dolibarr_set_const($this->db, "ULTIMATE_PDF_ALIAS_COUNTRY_EMETTEUR", $country_id . ':' . $country_code . ':' . $country_label, 'chaine', 0, '', $conf->entity);
					$this->dao->options['country_id'] = $country_id . ':' . $country_code . ':' . $country_label;

					//dolibarr_set_const($this->db, "ULTIMATE_PDF_TYPE_LABEL", $typelabel, 'chaine', 0, '', $conf->entity);

					$ret = $this->dao->update($id, $user);
					if ($ret <= 0) {
						$error++;
						$errors = ($this->dao->error ? array($this->dao->error) : $this->dao->errors);
						$action = 'edit';
					}
				}
				$this->db->commit();
			} else {
				$this->db->rollback();
			}
		}

		if ($action == 'deletefile' && $type == "otherlogo" || $type == "background") {
			//$id = $this->dao->id; //to get id
			$form = new Form($this->db);
			$formconfirm = $form->formconfirm('?urlfile=' . $urlfile, $langs->trans("DeleteFile"), $langs->trans("ConfirmDeleteFile") . ' ' . $urlfile . ' ?', 'delete_ok', '', 1, $conf->use_javascript_ajax);
			
			$this->resprints = $formconfirm;
			return 1;
		} 
		if ($action == 'deletepdf' && $type == "pdfbackground") {
			$id = $this->dao->id; //to get id
			$form = new Form($this->db);
			$formconfirm = $form->formconfirm('?urlfile=' . $urlfile, $langs->trans("DeleteFile"), $langs->trans("ConfirmDeleteFile") . ' ' . $urlfile . ' ?', 'deletepdf_ok', '', 1, (int) $conf->use_javascript_ajax);
			$this->resprints = $formconfirm;
			return 1;
		} 

		if ($action == 'delete_ok' && $confirm == 'yes') {
			$id = $this->dao->id; //to get id
			$urlfile_dirname = pathinfo($urlfile, PATHINFO_DIRNAME);
			$urlfile_filename = pathinfo($urlfile, PATHINFO_FILENAME);
			$urlfile_ext = pathinfo($urlfile, PATHINFO_EXTENSION);
			$urlfile = $urlfile_filename . '.' . $urlfile_ext;
			$urlfile_small = $urlfile_filename . '_small.' . $urlfile_ext;
			if (dol_delete_file($conf->ultimatepdf->dir_output . $urlfile_dirname . '/' . $urlfile, 1) && dol_delete_file($conf->ultimatepdf->dir_output . $urlfile_dirname . '/thumbs/' . $urlfile_small, 1)) {
				$mesg = pathinfo($urlfile, PATHINFO_FILENAME) . ' ' . $langs->trans("Deleted");
				setEventMessages($mesg, null, 'mesgs');
			}
			elseif (dol_delete_file($conf->ultimatepdf->dir_output . $urlfile_dirname . '/' . $urlfile, 1)) {
					$mesg = pathinfo($urlfile, PATHINFO_FILENAME) . ' ' . $langs->trans("Deleted");
					setEventMessages($mesg, null, 'mesgs');
			} else {
				$mesg = $langs->trans("ErrorFailToDeleteFile",  $urlfile_filename);
				setEventMessages($mesg, null, 'errors');
			}
		}

		if ($action == 'deletepdf_ok' && $confirm == 'yes') {
			$id = $this->dao->id; //to get id
			$urlfile_dirname = pathinfo($urlfile, PATHINFO_DIRNAME);
			$urlfile_filename = pathinfo($urlfile, PATHINFO_FILENAME);
			$urlfile_ext = pathinfo($urlfile, PATHINFO_EXTENSION);
			$urlfile = $urlfile_filename . '.' . $urlfile_ext;
			if (dol_delete_file($conf->ultimatepdf->dir_output . $urlfile_dirname . '/' . $urlfile, 1)) {
				$mesg = pathinfo($urlfile, PATHINFO_FILENAME) . ' ' . $langs->trans("Deleted");
				setEventMessages($mesg, null, 'mesgs');
			} else {
				$mesg = $langs->trans("ErrorFailToDeleteFile",  $urlfile_filename);
				setEventMessages($mesg, null, 'errors');
			}
		}

		if ($action == 'confirm_delete_design' && GETPOST('confirm') == 'yes' && $user->admin) {
			$error = 0;

			if ($id == 1) {
				$error++;
				array_push($this->errors, $langs->trans("ErrorNotDeleteMasterDesign"));
				$action = '';
			}

			if (!$error) {
				if ($this->dao->fetch($id) > 0) {
					if ($this->dao->delete($id) > 0) {
						$this->mesg = $langs->trans('ConfirmedDesignDeleted');
					} else {
						$this->errors = $this->dao->errors;
						$action = '';
					}
				}
			}
		}

		if ($action == 'setactive' && $user->admin) {
			$this->dao->setDesign($id, 'active', $value);
		}
	}


	/**
	 *	Return combo list of designs.
	 *
	 *	@param	int		$selected	Preselected design
	 *	@param	int		$htmlname	Name
	 *	@param	string	$option		Option
	 *  @param	string	$cssclass	specific css class. eg 'minwidth150imp mycssclass'
	 *  @param	bool	$ajax		Enable ajax combobox
	 *	@return	string
	 */
	function select_designs($selected = '', $htmlname = 'design', $option = '', $cssclass = 'minwidth150imp', $ajax = true)
	{
		global $user, $langs;

		$this->getInstanceDao();

		$this->dao->getDesigns();

		$out = '';

		if (is_array($this->dao->designs)) {
			$out = '<select class="flat maxwidth200onsmartphone ' . $cssclass . '" id="' . $htmlname . '" name="' . $htmlname . '"' . $option . '>';

			foreach ($this->dao->designs as $design) {
				if ($design->active == 1) {
					$out .= '<option value="' . $design->id . '" ';
					if ($selected == $design->id) {
						$out .= 'selected="selected"';
					}
					$out .= '>';
					$out .= $design->label;
					$out .= '</option>';
				}
			}

			$out .= '</select>';
		} else {
			$out .= $langs->trans('NoDesignAvailable');
		}

		// Make select dynamic
		if ($ajax) {
			include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
			$out.= ajax_combobox($htmlname);
		}
		
		return $out;
	}

	/**
	 *    Switch to another design.
	 *    @param	id		Id of the destination design
	 */
	function switchDesign($id)
	{
		global $conf, $user;

		$this->getInstanceDao();

		if ($this->dao->fetch($id) > 0) {
			// Controle des droits sur le changement
			if ($this->dao->verifyRight($id, $user->id) || $user->admin || $user->rights->ultimatepdf->write) {
				dolibarr_set_const($this->db, "ULTIMATE_DESIGN", $id, 'chaine', 0, '', $conf->entity);

				dolibarr_set_const($this->db, "ULTIMATE_DASH_DOTTED", $this->dao->options['dashdotted'], 'chaine', 0, '', $conf->entity);

				dolibarr_set_const($this->db, "ULTIMATE_BGCOLOR_COLOR", $this->dao->options['bgcolor'], 'chaine', 0, '', $conf->entity);

				dolibarr_set_const($this->db, "MAIN_PDF_TITLE_BACKGROUND_COLOR", $this->dao->options['title_bgcolor'], 'chaine', 0, '', $conf->entity);

				dolibarr_set_const($this->db, "ULTIMATE_BGCOLOR_OPACITY", $this->dao->options['opacity'], 'chaine', 0, '', $conf->entity);

				dolibarr_set_const($this->db, "ULTIMATE_SET_RADIUS", $this->dao->options['roundradius'], 'chaine', 0, '', $conf->entity);

				dolibarr_set_const($this->db, "ULTIMATE_BORDERCOLOR_COLOR", $this->dao->options['bordercolor'], 'chaine', 0, '', $conf->entity);

				dolibarr_set_const($this->db, "ULTIMATE_SENDER_STYLE", $this->dao->options['senderstyle'], 'chaine', 0, '', $conf->entity);

				dolibarr_set_const($this->db, "ULTIMATE_RECEIPT_STYLE", $this->dao->options['receiptstyle'], 'chaine', 0, '', $conf->entity);

				dolibarr_set_const($this->db, "ULTIMATE_TEXTCOLOR_COLOR", $this->dao->options['textcolor'], 'chaine', 0, '', $conf->entity);

				dolibarr_set_const($this->db, "ULTIMATE_FOOTERTEXTCOLOR_COLOR", $this->dao->options['footertextcolor'], 'chaine', 0, '', $conf->entity);

				dolibarr_set_const($this->db, "ULTIMATE_PROPAL_COLOR", $this->dao->options['propalcolor'], 'chaine', 0, '', $conf->entity);

				dolibarr_set_const($this->db, "ULTIMATE_QRCODECOLOR_COLOR", $this->dao->options['qrcodecolor'], 'chaine', 0, '', $conf->entity);

				dolibarr_set_const($this->db, "ULTIMATE_DOCUMENTS_WITH_NUMBERING_WIDTH", $this->dao->options['widthnumbering'], 'chaine', 0, '', $conf->entity);

				dolibarr_set_const($this->db, "ULTIMATE_DOCUMENTS_WITH_DATE_WIDTH", $this->dao->options['widthdate'], 'chaine', 0, '', $conf->entity);

				dolibarr_set_const($this->db, "ULTIMATE_DOCUMENTS_WITH_TYPE_WIDTH", $this->dao->options['widthtype'], 'chaine', 0, '', $conf->entity);

				dolibarr_set_const($this->db, "ULTIMATE_DOCUMENTS_WITH_PROJECT_WIDTH", $this->dao->options['widthproject'], 'chaine', 0, '', $conf->entity);

				dolibarr_set_const($this->db, "ULTIMATE_DOCUMENTS_WITH_TVA_WIDTH", $this->dao->options['widthvat'], 'chaine', 0, '', $conf->entity);

				dolibarr_set_const($this->db, "ULTIMATE_DOCUMENTS_WITH_UP_WIDTH", $this->dao->options['widthup'], 'chaine', 0, '', $conf->entity);

				dolibarr_set_const($this->db, "ULTIMATE_DOCUMENTS_WITH_QTY_WIDTH", $this->dao->options['widthqty'], 'chaine', 0, '', $conf->entity);

				dolibarr_set_const($this->db, "ULTIMATE_DOCUMENTS_WITH_UNIT_WIDTH", $this->dao->options['widthunit'], 'chaine', 0, '', $conf->entity);

				dolibarr_set_const($this->db, "ULTIMATE_DOCUMENTS_WITH_DISCOUNT_WIDTH", $this->dao->options['widthdiscount'], 'chaine', 0, '', $conf->entity);

				dolibarr_set_const($this->db, "ULTIMATE_DOCUMENTS_WITH_REF", $this->dao->options['withref'], 'chaine', 0, '', $conf->entity);

				dolibarr_set_const($this->db, "ULTIMATE_DOCUMENTS_WITH_REF_WIDTH", $this->dao->options['widthref'], 'chaine', 0, '', $conf->entity);

				dolibarr_set_const($this->db, "ULTIMATE_GENERATE_DOCUMENTS_WITHOUT_VAT", $this->dao->options['withoutvat'], 'chaine', 0, '', $conf->entity);

				dolibarr_set_const($this->db, "MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS", $this->dao->options['showdetails'], 'chaine', 0, '', $conf->entity);

				dolibarr_set_const($this->db, "ULTIMATE_OTHERLOGO", $this->dao->options['otherlogo'], 'chaine', 0, '', $conf->entity);

				dolibarr_set_const($this->db, "ULTIMATE_OTHERLOGO_FILE", $this->dao->options['otherlogo_file'], 'chaine', 0, '', $conf->entity);

				dolibarr_set_const($this->db, "ULTIMATEPDF_ADD_PDF_BACKGROUND", $this->dao->options['pdfbackground'], 'chaine', 0, '', $conf->entity);

				dolibarr_set_const($this->db, "ULTIMATEPDF_ADD_PDF_BACKGROUND_FILE", $this->dao->options['pdfbackground_file'], 'chaine', 0, '', $conf->entity);

				dolibarr_set_const($this->db, "ULTIMATEPDF_NEW_FONT", $this->dao->options['newfont'], 'chaine', 0, '', $conf->entity);

				dolibarr_set_const($this->db, "MAIN_PDF_FORCE_FONT", $this->dao->options['otherfont'], 'chaine', 0, '', $conf->entity);

				dolibarr_set_const($this->db, "MAIN_PDF_FREETEXT_HEIGHT", $this->dao->options['heightforfreetext'], 'chaine', 0, '', $conf->entity);

				dolibarr_set_const($this->db, "ULTIMATEPDF_FREETEXT_FONT_SIZE", $this->dao->options['freetextfontsize'], 'chaine', 0, '', $conf->entity);

				dolibarr_set_const($this->db, "ULTIMATE_USE_BACKGROUND_ON_PDF", $this->dao->options['background'], 'chaine', 0, '', $conf->entity);
				
				/*dolibarr_set_const($this->db, "ULTIMATE_USE_BACKGROUND_ON_PDF_FILE", $this->dao->options['background_file'], 'chaine', 0, '', $conf->entity);*/

				dolibarr_set_const($this->db, "ULTIMATE_USE_BACKGROUND_ON_PDF_X", $this->dao->options['backgroundx'], 'chaine', 0, '', $conf->entity);

				dolibarr_set_const($this->db, "ULTIMATE_USE_BACKGROUND_ON_PDF_Y", $this->dao->options['backgroundy'], 'chaine', 0, '', $conf->entity);
				
				dolibarr_set_const($this->db, "BACKGROUND_IMAGE_TRANSPARENCY", $this->dao->options['transparency'], 'chaine', 0, '', $conf->entity);

				dolibarr_set_const($this->db, "MAIN_DOCUMENTS_WITH_PICTURE_WIDTH", $this->dao->options['imglinesize'], 'chaine', 0, '', $conf->entity);

				dolibarr_set_const($this->db, "ULTIMATE_LOGO_HEIGHT", $this->dao->options['logoheight'], 'chaine', 0, '', $conf->entity);

				dolibarr_set_const($this->db, "ULTIMATE_LOGO_WIDTH", $this->dao->options['logowidth'], 'chaine', 0, '', $conf->entity);

				dolibarr_set_const($this->db, "ULTIMATE_OTHERLOGO_HEIGHT", $this->dao->options['otherlogoheight'], 'chaine', 0, '', $conf->entity);

				dolibarr_set_const($this->db, "ULTIMATE_OTHERLOGO_WIDTH", $this->dao->options['otherlogowidth'], 'chaine', 0, '', $conf->entity);

				dolibarr_set_const($this->db, "ULTIMATE_INVERT_SENDER_RECIPIENT", $this->dao->options['invertSenderRecipient'], 'chaine', 0, '', $conf->entity);

				dolibarr_set_const($this->db, "ULTIMATE_WIDTH_RECBOX", $this->dao->options['widthrecbox'], 'chaine', 0, '', $conf->entity);

				dolibarr_set_const($this->db, "ULTIMATE_PDF_MARGIN_LEFT", $this->dao->options['marge_gauche'], 'chaine', 0, '', $conf->entity);

				dolibarr_set_const($this->db, "ULTIMATE_PDF_MARGIN_RIGHT", $this->dao->options['marge_droite'], 'chaine', 0, '', $conf->entity);

				dolibarr_set_const($this->db, "ULTIMATE_PDF_MARGIN_TOP", $this->dao->options['marge_haute'], 'chaine', 0, '', $conf->entity);

				dolibarr_set_const($this->db, "ULTIMATE_PDF_MARGIN_BOTTOM", $this->dao->options['marge_basse'], 'chaine', 0, '', $conf->entity);

				dolibarr_set_const($this->db, "ULTIMATE_PDF_BORDER_LEFT_STATUS", $this->dao->options['borderleft'], 'chaine', 0, '', $conf->entity);

				dolibarr_set_const($this->db, "ULTIMATE_PDF_ALIAS_COMPANY", $this->dao->options['aliascompany'], 'chaine', 0, '', $conf->entity);

				dolibarr_set_const($this->db, "ULTIMATE_PDF_ALIAS_ADDRESS_EMETTEUR", $this->dao->options['aliasaddress'], 'chaine', 0, '', $conf->entity);

				dolibarr_set_const($this->db, "ULTIMATE_PDF_ALIAS_ZIP_EMETTEUR", $this->dao->options['aliaszip'], 'chaine', 0, '', $conf->entity);

				dolibarr_set_const($this->db, "ULTIMATE_PDF_ALIAS_TOWN_EMETTEUR", $this->dao->options['aliastown'], 'chaine', 0, '', $conf->entity);

				dolibarr_set_const($this->db, "ULTIMATE_PDF_ALIAS_PHONE_EMETTEUR", $this->dao->options['aliasphone'], 'chaine', 0, '', $conf->entity);

				dolibarr_set_const($this->db, "ULTIMATE_PDF_ALIAS_FAX_EMETTEUR", $this->dao->options['aliasfax'], 'chaine', 0, '', $conf->entity);

				dolibarr_set_const($this->db, "ULTIMATE_PDF_ALIAS_EMAIL_EMETTEUR", $this->dao->options['aliasemail'], 'chaine', 0, '', $conf->entity);

				dolibarr_set_const($this->db, "ULTIMATE_PDF_ALIAS_URL_EMETTEUR", $this->dao->options['aliasurl'], 'chaine', 0, '', $conf->entity);

				dolibarr_set_const($this->db, "ULTIMATE_PDF_ALIAS_COUNTRY_EMETTEUR", $this->dao->options['country_id'], 'chaine', 0, '', $conf->entity);

				//dolibarr_set_const($this->db, "ULTIMATE_PDF_TYPE_LABEL", $this->dao->options['typelabel'], 'chaine', 0, '', $conf->entity);

				return 1;
			} else {
				return -2;
			}
		} else {
			return -1;
		}
	}

	/**
	 * 	Get design info
	 * 	@param	id	Object id
	 */
	function getInfo($id)
	{
		$this->getInstanceDao();
		$this->dao->fetch($id);

		$this->id			= $this->dao->id;
		$this->label		= $this->dao->label;
		$this->description	= $this->dao->description;
		$this->active		= $this->dao->active;
	}

	/**
	 * 	Get action title
	 * 	@param	action	Type of action
	 */
	function getTitle($action = '')
	{
		global $langs;

		if ($action == 'create') return $langs->trans("AddDesign");
		else if ($action == 'edit') return $langs->trans("EditDesign");
		else return $langs->trans("DesignsManagement");
	}

	/**
	 *    Assigne les valeurs pour les templates
	 *    @param      action     Type of action
	 */
	function assign_values(&$action = 'view')
	{
		global $conf, $langs, $user;
		global $form, $formother, $formadmin;

		$this->getInstanceDao();

		$this->template_dir = dol_buildpath('/ultimatepdf/tpl/');

		if ($action == 'create') {
			$this->template = 'ultimatepdf_create.tpl.php';
		} else if ($action == 'edit') {
			$this->template = 'ultimatepdf_edit.tpl.php';

			if (!empty($id)) $ret = $this->dao->fetch($id);
		}

		if ($action == 'create' || $action == 'edit') {
			// Label			
			$this->tpl['label'] = ($this->label ? $this->label : $this->dao->label);

			// Description
			$this->tpl['description'] = ($this->description ? $this->description : $this->dao->description);

			// Dash dotted
			$ddvalue = array('0' => $langs->trans('ContinuousLine'), '8, 2' => $langs->trans('DottedLine'));
			$this->tpl['select_dashdotted'] = $form->selectarray('dashdotted', $ddvalue, ($this->dashdotted ? $this->dashdotted : $this->dao->options['dashdotted']));

			// Bgcolor
			$this->tpl['select_bgcolor'] = $formother->selectColor(($this->bgcolor ? $this->bgcolor : $this->dao->options['bgcolor']), 'bgcolor', '', 1);

			// title_bgcolor
			$this->tpl['select_title_bgcolor'] = $formother->selectColor(($this->title_bgcolor ? $this->title_bgcolor : $this->dao->options['title_bgcolor']), 'title_bgcolor', '', 1);

			// Bgcolor opacity
			$this->tpl['select_opacity'] = ($this->opacity ? $this->opacity : $this->dao->options['opacity']);

			// Set roundradius
			$this->tpl['select_roundradius'] = ($this->roundradius ? $this->roundradius : $this->dao->options['roundradius']);

			// Bordercolor
			$this->tpl['select_bordercolor'] = $formother->selectColor(($this->bordercolor ? $this->bordercolor : $this->dao->options['bordercolor']), 'bordercolor', '', 1);

			// Senderstyle
			$stylevalue = array('S' => $langs->trans('WhiteBackground'), 'FD' => $langs->trans('ColoredBackground'));
			$this->tpl['select_senderstyle'] = $form->selectarray('senderstyle', $stylevalue, ($this->senderstyle ? $this->senderstyle : $this->dao->options['senderstyle']));

			// receiptstyle
			$receiptstylevalue = array('S' => $langs->trans('WhiteBackground'), 'FD' => $langs->trans('ColoredBackground'));
			$this->tpl['select_receiptstyle'] = $form->selectarray('receiptstyle', $receiptstylevalue, ($this->receiptstyle ? $this->receiptstyle : $this->dao->options['receiptstyle']));

			// Textcolor
			$this->tpl['select_textcolor'] = $formother->selectColor(($this->textcolor ? $this->textcolor : $this->dao->options['textcolor']), 'textcolor', '', 1);

			// FooterTextcolor
			$this->tpl['select_footertextcolor'] = $formother->selectColor(($this->footertextcolor ? $this->footertextcolor : $this->dao->options['footertextcolor']), 'footertextcolor', '', 1);

			// showdetails
			$arraycolorsforpropal = array(
				0 => 'first_page_blue.jpg',
				1 => 'first_page_cyan.jpg',
				2 => 'first_page_green.jpg',
				3 => 'first_page_orange.jpg'
			);
			$this->tpl['select_propalcolor'] = $form->selectarray('propalcolor', $arraycolorsforpropal, ($this->propalcolor ? $this->propalcolor : $this->dao->options['propalcolor']));

			// QRcodecolor
			$this->tpl['select_qrcodecolor'] = $formother->selectColor(($this->qrcodecolor ? $this->qrcodecolor : $this->dao->options['qrcodecolor']), 'qrcodecolor', '', 1);

			// widthnumbering
			$this->tpl['widthnumbering'] = ($this->widthnumbering ? $this->widthnumbering : $this->dao->options['widthnumbering']);

			// widthdate
			$this->tpl['widthdate'] = ($this->widthdate ? $this->widthdate : $this->dao->options['widthdate']);

			// widthtype
			$this->tpl['widthtype'] = ($this->widthtype ? $this->widthtype : $this->dao->options['widthtype']);

			// widthproject
			$this->tpl['widthproject'] = ($this->widthproject ? $this->widthproject : $this->dao->options['widthproject']);

			// widthvat
			$this->tpl['widthvat'] = ($this->widthvat ? $this->widthvat : $this->dao->options['widthvat']);

			// widthup
			$this->tpl['widthup'] = ($this->widthup ? $this->widthup : $this->dao->options['widthup']);

			// widthqty
			$this->tpl['widthqty'] = ($this->widthqty ? $this->widthqty : $this->dao->options['widthqty']);

			// widthunit
			$this->tpl['widthunit'] = ($this->widthunit ? $this->widthunit : $this->dao->options['widthunit']);

			// widthdiscount
			$this->tpl['widthdiscount'] = ($this->widthdiscount ? $this->widthdiscount : $this->dao->options['widthdiscount']);

			// withref
			$this->tpl['select_withref'] = $form->selectyesno('withref', ($this->withref ? $this->withref : $this->dao->options['withref']), 0, false);

			// Ref width			
			$this->tpl['widthref'] = ($this->widthref ? $this->widthref : $this->dao->options['widthref']);

			// withoutvat
			$this->tpl['select_withoutvat'] = $form->selectyesno('withoutvat', ($this->withoutvat ? $this->withoutvat : $this->dao->options['withoutvat']), 0, false);

			// showdetails
			$arraydetailsforpdffoot = array(
				0 => $langs->trans('NoDetails'),
				1 => $langs->trans('DisplayCompanyInfo'),
				2 => $langs->trans('DisplayManagersInfo'),
				3 => $langs->trans('DisplayCompanyInfoAndManagers')
			);
			$this->tpl['select_showdetails'] = $form->selectarray('showdetails', $arraydetailsforpdffoot, ($this->showdetails ? $this->showdetails : $this->dao->options['showdetails']));

			// Set Otherlogo
			$id = $this->dao->id; //to get id			
			if (!empty($conf->global->ULTIMATE_OTHERLOGO)) {
				$other_file = urlencode('/otherlogo/' . $id . '/' . $conf->global->ULTIMATE_OTHERLOGO);
				$this->otherlogo = DOL_URL_ROOT . '/viewimage.php?modulepart=ultimatepdf&amp;file=' . $other_file;
			}
			$this->tpl['select_otherlogo'] = ($this->otherlogo ? $this->otherlogo : $this->dao->options['otherlogo']);

			// Set otherlogo file
			$this->tpl['select_otherlogo_file'] = ($this->otherlogo_file ? $this->otherlogo_file : $this->dao->options['otherlogo_file']);

			// Set pdfbackground name
			if (!empty($conf->global->ULTIMATEPDF_ADD_PDF_BACKGROUND)) {
				$id = $conf->global->ULTIMATE_DESIGN; //to get id
				if (file_exists($conf->ultimatepdf->dir_output . '/backgroundpdf/' .  $id . '/' . $conf->global->ULTIMATEPDF_ADD_PDF_BACKGROUND)) {
					$pdfbackground_file = urlencode('/backgroundpdf/' .  $id . '/' . $conf->global->ULTIMATEPDF_ADD_PDF_BACKGROUND);
					$pdfbackground = DOL_URL_ROOT . '/viewimage.php?modulepart=ultimatepdf&amp;file=' . $pdfbackground_file;
				}
			}
			$this->tpl['select_pdfbackground'] = ($this->pdfbackground ? $this->pdfbackground : $this->dao->options['pdfbackground']);

			$this->tpl['select_pdfbackground_file'] = ($this->pdfbackground_file ? $this->pdfbackground_file : $this->dao->options['pdfbackground_file']);

			// NewFont
			if (!empty($conf->global->ULTIMATEPDF_NEW_FONT)) {
				$extension = pathinfo($_FILES['fontfile']['name'], PATHINFO_EXTENSION);
				require_once DOL_DOCUMENT_ROOT . '/includes/tecnickcom/tcpdf/include/tcpdf_fonts.php';
				$newfonts = new TCPDF_FONTS;

				if ($extension == 'ttf' || $extension == 'TTF') {
					$srcfile	= dol_buildpath('ultimatepdf', 0) . '/newfont/';
					$fontfile	= $_FILES['fontfile']['tmp_name'];
					$finalfile	= $_FILES['fontfile']['name'];
					$targetpath = DOL_DOCUMENT_ROOT . '/includes/tecnickcom/tcpdf/fonts/';
					$newfont = $newfonts->addTTFfont('TrueTypeUnicode', '', 32, $targetpath, 3, 1, true, false, $srcfile . $finalfile);
					dolCopyDir($targetpath, DOL_DOCUMENT_ROOT . '/includes/tecnickcom/tcpdf/fonts/', 0, 1);
					$moved		= dol_move_uploaded_file($fontfile, $srcfile . $finalfile, 1, 0, $_FILES['fontfile']['error']);
					if ($moved > 0) {
						$targetpath = DOL_DOCUMENT_ROOT . '/includes/tecnickcom/tcpdf/fonts/';
						$newfont = $newfonts->addTTFfont('TrueTypeUnicode', '', 32, $targetpath, 3, 1, true, false, $srcfile . $finalfile);
						if ($newfont === false)	setEventMessages($langs->trans("ParamAddFontKo", $newfont), null, 'errors');
						else {
							dolCopyDir($targetpath, DOL_DOCUMENT_ROOT . '/includes/tecnickcom/tcpdf/fonts/', 0, 1);
							dolCopyDir($targetpath, TCPDF_PATH . 'fonts', 0, 1);
							array_map('unlink', glob($targetpath . '*'));
							setEventMessages($langs->trans("ParamAddFontOk", $newfont), null, 'mesgs');
						}
					}
				} else	setEventMessages($langs->trans("ParamAddTTFKo", $_FILES['fontfile']['name']), null, 'errors');
			}

			// Other font
			$dirfonts	= DOL_DOCUMENT_ROOT . '/includes/tecnickcom/tcpdf/fonts/';
			$listfonts	= dol_dir_list($dirfonts, 'files');
			$fontvalue	= array();
			foreach ($listfonts as $font) {
				$extension	= pathinfo($font['name'], PATHINFO_EXTENSION);
				if ($extension == 'php') {
					$fontname = pathinfo($font['name'], PATHINFO_FILENAME);

					include_once($font['fullname']);
					if ($name != '') {
						$fontvalue[$fontname] = $name;
					}
				}
			}
			$this->tpl['select_otherfont'] = $form->selectarray('otherfont', $fontvalue, ($this->dao->options['otherfont'] ? $this->dao->options['otherfont'] : 'helvetica'), 1);

			// heightforfreetext
			$this->tpl['select_heightforfreetext'] = ($this->heightforfreetext ? $this->heightforfreetext : $this->dao->options['heightforfreetext']);

			// freetextfontsize
			$this->tpl['select_freetextfontsize'] = ($this->freetextfontsize ? $this->freetextfontsize : $this->dao->options['freetextfontsize']);

			// Use background on pdf
			$id = $this->dao->id; //to get id
			if (!empty($conf->global->ULTIMATE_USE_BACKGROUND_ON_PDF)) {
				if (file_exists($conf->ultimatepdf->dir_output . '/background/' . $id . '/' . $conf->global->ULTIMATE_USE_BACKGROUND_ON_PDF)) {
					$this->background_file = urlencode('/background/' . $id . '/' . $conf->global->ULTIMATE_USE_BACKGROUND_ON_PDF);
					$this->background = DOL_URL_ROOT . '/viewimage.php?modulepart=ultimatepdf&amp;file=' . $this->background_file;
				}
			} else {
				$nophoto = '/public/theme/common/nophoto.png';
				$this->background = DOL_URL_ROOT . $nophoto;
			}
			$this->tpl['select_background'] = ($this->background ? $this->background : $this->dao->options['background']);

			$this->tpl['select_background_file'] = ($this->background_file ? $this->background_file : $this->dao->options['background_file']);

			$this->tpl['backgroundx'] = ($this->backgroundx ? $this->backgroundx : $this->dao->options['backgroundx']);

			$this->tpl['backgroundy'] = ($this->backgroundy ? $this->backgroundy : $this->dao->options['backgroundy']);
			
			$this->tpl['transparency'] = ($this->transparency ? $this->transparency : $this->dao->options['transparency']);

			// Set image width
			$this->tpl['imglinesize'] = ($this->imglinesize ? $this->imglinesize : $this->dao->options['imglinesize']);

			// Set logo height
			$this->tpl['logoheight'] = ($this->logoheight ? $this->logoheight : $this->dao->options['logoheight']);

			// Set logo width
			$this->tpl['logowidth'] = ($this->logowidth ? $this->logowidth : $this->dao->options['logowidth']);

			// Set otherlogo height
			$this->tpl['otherlogoheight'] = ($this->otherlogoheight ? $this->otherlogoheight : $this->dao->options['otherlogoheight']);

			// Set otherlogo width
			$this->tpl['otherlogowidth'] = ($this->otherlogowidth ? $this->otherlogowidth : $this->dao->options['otherlogowidth']);

			// Invert sender and recipient
			$this->tpl['invertSenderRecipient'] = $form->selectyesno('invertSenderRecipient', ($this->invertSenderRecipient ? $this->invertSenderRecipient : $this->dao->options['invertSenderRecipient']), 0, false);

			// Set widthrecbox
			$this->tpl['widthrecbox'] = ($this->widthrecbox ? $this->widthrecbox : $this->dao->options['widthrecbox']);

			// Set marge_gauche
			$this->tpl['marge_gauche'] = ($this->marge_gauche ? $this->marge_gauche : $this->dao->options['marge_gauche']);

			// Set marge_droite
			$this->tpl['marge_droite'] = ($this->marge_droite ? $this->marge_droite : $this->dao->options['marge_droite']);

			// Set marge_haute
			$this->tpl['marge_haute'] = ($this->marge_haute ? $this->marge_haute : $this->dao->options['marge_haute']);

			// Set marge_basse
			$this->tpl['marge_basse'] = ($this->marge_basse ? $this->marge_basse : $this->dao->options['marge_basse']);
			
			// Set borderleft
			$this->tpl['borderleft']=$form->selectyesno('borderleft', ($this->borderleft ? $this->borderleft : $this->dao->options['borderleft']), 0, false);

			// Set alias name sender
			$this->tpl['aliascompany'] = ($this->aliascompany ? $this->aliascompany : $this->dao->options['aliascompany']);

			// Set alias sender address
			$this->tpl['aliasaddress'] = ($this->aliasaddress ? $this->aliasaddress : $this->dao->options['aliasaddress']);

			// Set alias sender zip
			$this->tpl['aliaszip'] = ($this->aliaszip ? $this->aliaszip : $this->dao->options['aliaszip']);

			// Set alias sender town
			$this->tpl['aliastown'] = ($this->aliastown ? $this->aliastown : $this->dao->options['aliastown']);

			// Set alias sender phone
			$this->tpl['aliasphone'] = ($this->aliasphone ? $this->aliasphone : $this->dao->options['aliasphone']);

			// Set alias sender phone
			$this->tpl['aliasfax'] = ($this->aliasfax ? $this->aliasfax : $this->dao->options['aliasfax']);

			// Set alias sender email
			$this->tpl['aliasemail'] = ($this->aliasemail ? $this->aliasemail : $this->dao->options['aliasemail']);

			// Set alias sender url
			$this->tpl['aliasurl'] = ($this->aliasurl ? $this->aliasurl : $this->dao->options['aliasurl']);

			//$this->tpl['typelabel'] = ($typelabel ? $typelabel : $this->dao->options['typelabel']);

			// Set alias sender country
			// We define country_id
			if (!empty($conf->global->ULTIMATE_PDF_ALIAS_COUNTRY_EMETTEUR)) {
				$tmp = explode(':', $conf->global->ULTIMATE_PDF_ALIAS_COUNTRY_EMETTEUR);
				$country_id = $tmp[2] ? $tmp[2] : $tmp[0];
			} else {
				$aliascountry = 0;
			}

			$this->tpl['select_country'] = $form->select_country($country_id ? $country_id : $this->dao->options['country_id'], 'country_id');
		} else {
			$this->dao->getDesigns();

			$this->tpl['designs']		= $this->dao->designs;
			$this->tpl['img_on'] 		= img_picto($langs->trans("Activated"), 'on');
			$this->tpl['img_off'] 		= img_picto($langs->trans("Disabled"), 'off');
			$this->tpl['img_modify'] 	= img_edit();
			$this->tpl['img_delete'] 	= img_delete();

			// Confirm delete 
			if (GETPOST("action") == 'delete') {
				if (GETPOST('type') == 'otherlogo' || GETPOST('type') ==  'background') {
					$urlfile = GETPOST('urlfile');
					$this->tpl['action_delete'] = $form->formconfirm($_SERVER["PHP_SELF"] . '?urlfile=' . $urlfile, $langs->trans("DeleteFile"), $langs->trans("ConfirmDeleteFile") . ' ' . $urlfile . ' ?', 'delete_ok', '', 1, (int) $conf->use_javascript_ajax);
					$this->template = 'ultimatepdf_edit.tpl.php';
				} else {
					$this->tpl['action_delete'] = $form->formconfirm($_SERVER["PHP_SELF"] . "?id=" . GETPOST('id'), $langs->trans("DeleteDesign"), $langs->trans("ConfirmDeleteDesign"), "confirm_delete_design", '', 0, 1);
					$this->template = 'ultimatepdf_view.tpl.php';
				}
			} else {
				$this->template = 'ultimatepdf_view.tpl.php';
			}

			// Confirm deletepdf 
			if (GETPOST("action") == 'deletepdf') {
				if (GETPOST('type') == 'pdfbackground') {
					$id = $conf->global->ULTIMATE_DESIGN; //to get id
					$urlfile = ('/backgroundpdf/' . $id . '/' . $conf->global->ULTIMATEPDF_ADD_PDF_BACKGROUND);
					$this->tpl['action_deletepdf'] = $form->formconfirm($_SERVER["PHP_SELF"] . '?urlfile=' . $urlfile, $langs->trans("DeleteFile"), $langs->trans("ConfirmDeleteFile") . ' ' . $urlfile . ' ?', 'deletepdf_ok', '', 1, (int) $conf->use_javascript_ajax);
					$this->template = 'ultimatepdf_edit.tpl.php';
				}
			} else {
				$this->template = 'ultimatepdf_view.tpl.php';
			}
		}
	}

	/**
	 *    Display the template
	 */
	function display()
	{
		global $conf, $langs;

		include($this->template_dir . $this->template);
	}
	
	/**
	 * printTopRightMenu
	 *
	 * @return void
	 */
	function printTopRightMenu()
	{
		return $this->getTopRightMenu();
	}

	/**
	 * getTopRightMenu	Show design info
	 *
	 * @return void
	 */
	private function getTopRightMenu()
	{
		global $conf, $langs;

		$langs->loadLangs(array('admin','ultimatepdf@ultimatepdf'));

		$out = '';

		if (($conf->global->MAIN_THEME === 'eldy' && empty($conf->global->ULTIMATEPDF_DROPDOWN_MENU_DISABLED) && !GETPOSTISSET('theme')) || (GETPOSTISSET('theme') && GETPOST('theme', 'aZ', 1) === 'eldy')) {
			$out .= $this->getDropdownMenu();
		} else {
			$form = new Form($this->db);

			$this->getInstanceDao();

			$this->dao->getDesigns();

			$SelectDesigns = $this->select_designs($conf->global->ULTIMATE_DESIGN, 'design', '', 'minwidth200imp', true);

			if (is_array($this->dao->designs)) {
				$htmltext = '<u>' . $langs->trans("Design") . '</u>' . "\n";
				foreach ($this->dao->designs as $design) {
					if ($design->active == 1) {
						if ($conf->global->ULTIMATE_DESIGN == $design->id) {
							$htmltext .= '<br><b>' . $langs->trans("Label") . '</b>: ' . $design->label . "\n";
							$htmltext .= '<br><b>' . $langs->trans("Description") . '</b>: ' . $design->description . "\n";
						}
					}
				}
			}

			$text = img_picto('', 'object_ultimatepdf@ultimatepdf', 'id="switchdesign" class="design linkobject"');

			$out .= $form->textwithtooltip('', $htmltext, 2, 1, $text, 'login_block_elem', 2);
			if (!empty($SelectDesigns)) {
				$out .= '<script type="text/javascript">
			$("#switchdesign").click(function() {
				$("#dialog-switchdesign").dialog({
					modal: true,
					width: "' . ($conf->dol_optimize_smallscreen ? 300 : 400) . '",
					buttons: {
						 "' . $langs->trans('Ok') . '": function() {
							choice= "ok";
							$.get( "' . dol_buildpath('/ultimatepdf/core/ajax/functions.php', 1) . '", {
								action: "switchdesign",
								design: $( "#design" ).val()
							},
							function(content) {
								$( "#dialog-switchdesign" ).dialog( "close" );
								location.href="' . $_SERVER["REQUEST_URI"] . '";
							});
						},
						"' . $langs->trans('Cancel') . '": function() {
							choice= "ko";
							$(this).dialog( "close" );
						}
					},
					close: function(event, ui) {
						if (choice == "ok") {
							location.href="' . DOL_URL_ROOT . '";
						}
					}
				});
			});
			</script>';

				$out .= '<div id="dialog-switchdesign" class="hideobject" title="' . $langs->trans('SwitchToAnotherDesign') . '">' . "\n";
				$out .= '<br>' . $langs->trans('SelectADesign') . ': ';
				$out .= ajax_combobox('design');
				$out .= $SelectDesigns;
				$out .= '</div>' . "\n";
			}
		}

		$this->resprints = $out;
		return 0;
	}
	
	/**
	 * getDropdownMenu
	 *
	 * @return void
	 */
	private function getDropdownMenu()
	{
		global $conf, $user, $langs;

		$this->getInstanceDao();

		$this->dao->getDesigns();

		$dropdownBody = '';
		$dropdownBody = '<span id="topmenuupdfmoreinfo-btn"><i class="fa fa-caret-right"></i> ' . $langs->trans("ShowMoreInfos") . '</span>';
		$dropdownBody .= '<div id="topmenuupdfmoreinfo" >';

		if (is_array($this->dao->designs)) {
			$dropdownBody .= '<br><u>' . $langs->trans("Design") . '</u>' . "\n";
			foreach ($this->dao->designs as $design) {
				if ($design->active == 1) {
					if ($conf->global->ULTIMATE_DESIGN == $design->id) {
						$dropdownBody .= '<br><b>' . $langs->trans("Label") . '</b>: ' . $design->label . "\n";
						$dropdownBody .= '<br><b>' . $langs->trans("Description") . '</b>: ' . $design->description . "\n";
					}
				}
			}
		}

		$dropdownBody .= '</div>';

		$updfSwitchLink = '<div id="switchmodel-menu" class="button-top-menu-dropdown"><i class="fa fa-random"></i> ' . $langs->trans("Select") . '</div>';
		$updfConfigLink = '<a class="button-top-menu-dropdown" href="' . dol_buildpath('ultimatepdf/admin/setup.php', 1) . '?action=edit&id=' . $design->id . '"><i class="fa fa-cogs"></i>  ' . $langs->trans("Setup") . '</a>';

		$out = '<div class="inline-block nowrap">';
		$out .= '<div class="inline-block login_block_elem login_block_elem_name float-left" style="padding: 0px;">';

		$out .= '<div id="topmenu-updf-dropdown" class="atoplogin updfdropdown updf-menu">';
		$out .= '<span class="fa updficon atoplogin updf-dropdown-toggle" data-toggle="updfdropdown" id="updf-dropdown-icon">';
		$out .= '<span class="fa fa-chevron-down padding-left20" id="updf-dropdown-icon-down"></span>';
		$out .= '<span class="fa fa-chevron-up padding-left20 hidden" id="updf-dropdown-icon-up"></span>';
		$out .= '</span>';

		$out .= '<div class="updf-dropdown-menu">';

		$out .= '<div class="updf-header">';
		$out .= '<span class="fa updficon-large dropdown-updf-image"></span>';
		$out .= '<br><br>' . $langs->trans('SwitchToAnotherDesign') . ': ';
		$out .= $this->select_designs($conf->global->ULTIMATE_DESIGN);
		$out .= '</div>';
	
		$out .= '<div class="updf-body">' . $dropdownBody . '</div>';

		$out .= '<div class="updf-footer">';
		$out .= '<div class="pull-left">';
		if (!empty($user->admin)) {
			$out .= $updfConfigLink;
		}
		$out .= '</div>';

		$out .= '<div class="pull-right">';
		$out .= $updfSwitchLink;
		$out .= '</div>';

		$out .= '<div style="clear:both;"></div>';

		$out .= '</div>';
		$out .= '</div>';
		$out .= '</div>';

		$out .= '</div></div>';

		$out .= '
		<script type="text/javascript">
		$(document).ready(function() {
			$(document).on("click", function(event) {
				if (!$(event.target).closest("#topmenu-updf-dropdown").length) {
					// Hide the menus.
					$("#topmenu-updf-dropdown").removeClass("open");
					$("#updf-dropdown-icon-down").show();
					$("#updf-dropdown-icon-up").hide();
				}
			});
			$("#topmenu-updf-dropdown .updf-dropdown-toggle").on("click", function(event) {
				$("#topmenu-updf-dropdown").toggleClass("open");
				$("#updf-dropdown-icon-down").toggle();
				$("#updf-dropdown-icon-up").toggle();
			});
			$("#topmenuupdfmoreinfo-btn").on("click", function() {
				$("#topmenuupdfmoreinfo").slideToggle();
			});
			$("#switchmodel-menu").on("click",function() {
				$.get("' . dol_buildpath('/ultimatepdf/core/ajax/functions.php', 1) . '", {
					action: "switchdesign",
					design: $("#design").val()
				},
				function(content) {
					location.href="' . $_SERVER["REQUEST_URI"] . '";
				});
			});
		';
		$out .= '
		});
		</script>';

		return $out;
	}

	/**
	 * Complete doc forms (set this->resprint).
	 *
	 * @param	array	$parameters		Array of parameters
	 * @param	object	$object			Object
	 * @return  int 		        	<0 if KO,
	 *                          		=0 if OK but we want to process standard actions too,
	 *  	                            >0 if OK and we want to replace standard actions.
	 */
	function formBuilddocOptions($parameters, &$object)
	{
		global $langs, $user, $conf, $form;

		$langs->load("ultimatepdf@ultimatepdf");

		$out = '';

		$morefiles = array();

		if ($parameters['modulepart'] == 'propal') {
			$staticpdf = glob($conf->ultimatepdf->dir_output . "/proposals/*.pdf");
			$modelpdf = glob($conf->ultimatepdf->dir_output . "/proposals/pdf_*.modules.php");
		}
		if ($parameters['modulepart'] == 'order'   || $parameters['modulepart'] == 'commande') {
			$staticpdf = glob($conf->ultimatepdf->dir_output . "/orders/*.pdf");
			$modelpdf = glob($conf->ultimatepdf->dir_output . "/orders/pdf_*.modules.php");
		}
		if ($parameters['modulepart'] == 'invoice' || $parameters['modulepart'] == 'facture') {
			$staticpdf = glob($conf->ultimatepdf->dir_output . "/invoices/*.pdf");
			$modelpdf = glob($conf->ultimatepdf->dir_output . "/invoices/pdf_*.modules.php");
		}
		if ($parameters['modulepart'] == 'supplier_order' || $parameters['modulepart'] == 'commande_fournisseur') {
			$staticpdf = glob($conf->ultimatepdf->dir_output . "/supplier_orders/*.pdf");
			$modelpdf = glob($conf->ultimatepdf->dir_output . "/supplier_orders/pdf_*.modules.php");
		}
		if ($parameters['modulepart'] == 'contract' || $parameters['modulepart'] == 'contract') {
			$staticpdf = glob($conf->ultimatepdf->dir_output . "/contracts/*.[pP][dD][fF]");
			$modelpdf = glob($conf->ultimatepdf->dir_output . "/contracts/pdf_*.modules.php");
		}

		if (!empty($staticpdf)) {
			foreach ($staticpdf as $filename) {
				$morefiles[] = basename($filename, ".pdf");
				$morefiles[] .= ''; //To have an empty line in second position
			}
		}
		if (!empty($modelpdf)) {
			foreach ($modelpdf as $filename) {
				$morefiles[] = basename($filename, ".php");
			}
		}
		if (!empty($morefiles)) {
			$out .= '<tr class="liste_titre">';
			$out .= '<td align="left" colspan="4" valign="top" class="formdoc">';
			$out .= $langs->trans("ConcatenateFile") . ' ';
			$out .= $form->selectarray('ultimatepdffile', $morefiles, -1, 0, 0, 1);
		}
		$out .= '</td></tr>';

		$this->resprints = $out;

		return 0;
	}

	/**
	 * formObjectOptions Method Hook Call
	 *
	 * @param array $parameters parameters
	 * @param Object	&$object			Object to use hooks on
	 * @param string	&$action			Action code on calling page ('create', 'edit', 'view', 'add', 'update', 'delete'...)
	 * @param object $hookmanager class instance
	 * @return void
	 */
	function formObjectOptions($parameters, &$object, &$action)
	{
		global $langs, $conf, $user;

		$langs->load('ultimatepdf@ultimatepdf');

		dol_syslog(__METHOD__, LOG_DEBUG);
		$html = '';
		$mergedpdf = array(
			'propal' => array(
				'upperconst' => 'PROPOSALS',
				'classpath' => 'propal',
				'rights' => 'propal',
				'subdir' => '/'
			),
			'facture' => array(
				'upperconst' => 'INVOICES',
				'classpath' => 'invoice',
				'rights' => 'invoice',
				'subdir' => '/'
			),
			'commande' => array(
				'upperconst' => 'ORDERS',
				'classpath' => 'order',
				'rights' => 'order',
				'subdir' => '/'
			),
			'contrat' => array(
				'upperconst' => 'CONTRACTS',
				'classpath' => 'contract',
				'rights' => 'contract',
				'subdir' => '/'
			),
			'order_supplier' => array(
				'upperconst' => 'SUPPLIERORDERS',
				'classpath' => 'supplierorder',
				'rights' => 'fournisseur',
				'subdir' => '/commande/'
			),
			'supplier_proposal' => array(
				'upperconst' => 'SUPPLIERPROPOSAL',
				'classpath' => 'supplierproposal',
				'rights' => 'supplier_proposal',
				'subdir' => '/'
			),
			'fichinter' => array(
				'upperconst' => 'FICHINTER',
				'classpath' => 'fichinter',
				'rights' => 'ficheinter',
				'subdir' => '/'
			),
			'product' => array(
				'upperconst' => 'PRODUCTS',
				'classpath' => 'produit',
				'rights' => 'product',
				'subdir' => '/'
			)
		);

		// Add javascript Jquery to add button Select doc form
		$upperconst = "ULTIMATEPDF_GENERATE_" . $mergedpdf[$object->element]['upperconst'] . "_WITH_MERGED_PDF";
		if (array_key_exists($object->element, $mergedpdf) && !empty($object->id) && !empty($conf->global->$upperconst)) {
			require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
			$classpath = $mergedpdf[$object->element]['classpath'];
			$ucfClasspath = ucfirst($classpath);
			dol_include_once('/ultimatepdf/class/documentmergedpdf.class.php');
			$filetomerge = new DocumentMergedPdf($this->db);
			
			$result = $filetomerge->fetch_by_element($object);
			$rights = $mergedpdf[$object->element]['rights'];
			$subdir = $mergedpdf[$object->element]['subdir'];

			if (!empty($conf->$rights->enabled))
				$upload_dir = $conf->$rights->dir_output . $subdir . dol_sanitizeFileName($object->ref);				
			$filearray = dol_dir_list($upload_dir, "files", 0, '', '\.meta$', 'name', SORT_ASC, 1);
			
			// For each file build select list with PDF extention
			if (count($filearray) > 0) {
				$html .= '<BR><BR>';
				// Actual file to merge is :
				if (count($filetomerge->lines) > 0) {
					$html .= '<div class=\"fichecenter\">';
					$html .= '<div class=\"fichehalfleft\">';
					$html .= '<div class=\"titre\">';
					$html .= '<br>';
					$html .= $langs->trans($ucfClasspath . 'MergePdf' . $ucfClasspath . 'ActualFile');
					$html .= '</div>';
				}
				
				$html .= '<form name=\"filemerging\" action=\"' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '\" method=\"post\">';
				$html .= '<input type=\"hidden\" name=\"token\" value=\"' . $_SESSION['newtoken'] . '\">';
				$html .= '<input type=\"hidden\" name=\"action\" value=\"filemerging\">';

				if (count($filetomerge->lines) == 0) {
					$html .= '<div class=\"fichecenter\">';
					$html .= '<div class=\"fichehalfleft\">';
					$html .= '<div class=\"titre\">';
					$html .= '<br>';
					$html .= $langs->trans($ucfClasspath . 'MergePdf' . $ucfClasspath . 'ChooseFile');
					$html .= '</div>';
				}
				
				$html .= '<table class=\"noborder\" width=\"100%\">';
				$html .= '<tbody>';
				$html .= '<tr class=\"liste_titre\">';
				$html .= '<th>' . $langs->trans('Documents') . '';
				$html .= '</th></tr>';
				$html .= '</tbody>';
				
				$hasfile = false;
				foreach ($filearray as $filetoadd) {
					if (($ext = pathinfo($filetoadd['name'], PATHINFO_EXTENSION) == 'pdf') && ($filename = pathinfo($filetoadd['name'], PATHINFO_FILENAME) != $object->ref)) {
						$checked = '';
						$filename = $filetoadd['name'];
						
						if (array_key_exists($filetoadd['name'], $filetomerge->lines)) {
							$checked = ' checked=\"checked\" ';
						}

						$hasfile = true;
						$icon = '<img border=\"0\" title=\"Fichier: ' . $filename . '\" alt=\"Fichier: ' . $filename . '\" src=\"' . DOL_URL_ROOT . '/theme/common/mime/pdf.png\">';
						$html .= '<tr class=\"oddeven\"><td class=\"nowrap\" style=\"font-weight:bold\">';

						$html .= '<input type=\"checkbox\" ' . $checked . ' name=\"filetoadd[]\" id=\"filetoadd\" value=\"' . $filetoadd['name'] . '\"> ' . $icon . ' ' . $filename . '</input>';
						$html .= '</td></tr>';
					}
				}

				if (!$hasfile) {
					$html .= '<tr><td>';
					$warning = '<img border=\"0\" src=\"' . DOL_URL_ROOT . '/theme/eldy/img/warning.png\">';
					$html .= $warning . ' ' . $langs->trans('GotoDocumentsTab');
					$html .= '</td></tr>';
				}

				if ($hasfile) {
					$html .= '<tr><td>';
					$html .= '<input type=\"submit\" class=\"button\" name=\"save\" value=\"' . $langs->trans('Save') . '\">';
					$html .= '<br><br>';
					$html .= '</td></tr>';
				}

				$html .= '</table>';
				$html .= '</form>';
				$html .= '</div>';
				$html .= '</div>';

				if ($conf->use_javascript_ajax) {
					print "\n" . '<script type="text/javascript" language="javascript">';
					print 'jQuery(document).ready(function () {
					jQuery(function() {jQuery(".fiche").append("' . $html . '");});
					})';
					print '</script>' . "\n";
				}
			}
		}
		//$this->resprints = $html;
		return 0;
	}

	/**
	 * Return action of hook
	 *
	 * @param array $parameters
	 * @param object $object
	 * @param string $action
	 * @param object $hookmanager class instance
	 * @return void
	 */
	function afterPDFCreation($parameters, &$object, &$action = '', $hookmanager)
	{
		global $langs, $conf;
		global $hookmanager;

		$outputlangs = $langs;

		//var_dump($parameters['object']);

		$ret = 0;
		$deltemp = array();
		dol_syslog(get_class($this) . '::executeHooks action=' . $action);

		$check = 'alpha';
		if (!empty($conf->global->ULTIMATEPDF_MULTIPLE_CONCATENATION_ENABLED)) {
			$check = 'array';
		}
		$ultimatepdffile = GETPOST('ultimatepdffile', $check);
		if (!is_array($ultimatepdffile) && !empty($ultimatepdffile)) $ultimatepdffile = array($ultimatepdffile);

		$element = '';
		if ($parameters['object']->element == 'propal')  $element = 'proposals';
		if ($parameters['object']->element == 'order'   || $parameters['object']->element == 'commande') $element = 'orders';
		if ($parameters['object']->element == 'invoice' || $parameters['object']->element == 'facture')  $element = 'invoices';
		if ($parameters['object']->element == 'order_supplier' || $parameters['object']->element == 'commande_fournisseur')  $element = 'supplier_orders';
		if ($parameters['object']->element == 'contract' || $parameters['object']->element == 'contrat')  $element='contracts';

		$filetoconcat1 = array($parameters['file']);
		$filetoconcat2 = array();

		if (!empty($ultimatepdffile) && $ultimatepdffile[0] != -1) {
			foreach ($ultimatepdffile as $ultimatefile) {
				if (preg_match('/^pdf_(.*)+\.modules/', $ultimatefile)) {
					require_once(DOL_DOCUMENT_ROOT . "/core/lib/files.lib.php");

					$file = $conf->ultimatepdf->dir_output . '/' . $element . '/' . $ultimatefile . '.php';
					$classname = str_replace('.modules', '', $ultimatefile);
					require_once($file);
					$obj = new $classname($this->db);

					// We save charset_output to restore it because write_file can change it if needed for
					// output format that does not support UTF8.
					$sav_charset_output = $outputlangs->charset_output;
					// Change the output dir
					$srctemplatepath = $conf->ultimatepdf->dir_output->dir_temp;
					// Generate pdf
					$obj->write_file($parameters['object'], $outputlangs, $srctemplatepath, $hidedetails, $hidedesc, $hideref, $hookmanager);
					// Restore charset output
					$outputlangs->charset_output = $sav_charset_output;

					$objectref = dol_sanitizeFileName($parameters['object']->ref);
					$dir = $conf->ultimatepdf->dir_output->dir_temp . "/" . $objectref;

					$filetoconcat2[] = $dir . "/" . $objectref . ".pdf";

					$deltemp[] = $dir;
				} else {
					$filetoconcat2[] = $conf->ultimatepdf->dir_output . '/' . $element . '/' . $ultimatefile . '.pdf';
				}
			}

			dol_syslog(get_class($this) . '::afterPDFCreation ' . $filetoconcat1 . ' - ' . $filetoconcat2);

			if (!empty($filetoconcat2) && !empty($ultimatepdffile) && $ultimatepdffile != '-1') {
				$filetoconcat = array_merge($filetoconcat1, $filetoconcat2);

				// Create empty PDF
				$pdf = pdf_getInstance();
				if (class_exists('TCPDF')) {
					$pdf->setPrintHeader(false);
					$pdf->setPrintFooter(false);
				}
				$pdf->SetFont(pdf_getPDFFont($outputlangs));

				if ($conf->global->MAIN_DISABLE_PDF_COMPRESSION) $pdf->SetCompression(false);
				//$pdf->SetCompression(false);

				$pagecount = $this->concat($pdf, $filetoconcat);

				if ($pagecount) {
					$pdf->Output($filetoconcat1[0], 'F');
					if (!empty($conf->global->MAIN_UMASK)) {
						@chmod($file, octdec($conf->global->MAIN_UMASK));
					}
					if (!empty($deltemp)) {
						// Delete temp files
						foreach ($deltemp as $dirtemp) {
							dol_delete_dir_recursive($dirtemp);
						}
					}
				}

				// Save selected files and order
				$params['ultimatepdf'] = $ultimatepdffile;
				$parameters['object']->extraparams = array_merge($parameters['object']->extraparams, $params);
			}
		} else {
			// Remove extraparams for ultimatepdf
			if (isset($parameters['object']->extraparams['concatpdf'])) unset($parameters['object']->extraparams['ultimatepdf']);
		}

		if (is_object($parameters['object']) && method_exists($parameters['object'], 'setExtraParameters')) $result = $parameters['object']->setExtraParameters();

		return $ret;
	}

	/**
	 * concat
	 * @param unknown_type $pdf    Pdf
	 * @param unknown_type $files  Files
	 */
	function concat(&$pdf, $files)
	{
		foreach ($files as $file) {
			$pagecount = $pdf->setSourceFile($file);
			for ($i = 1; $i <= $pagecount; $i++) {
				$tplidx = $pdf->ImportPage($i);
				$s = $pdf->getTemplatesize($tplidx);
				$pdf->AddPage($s['h'] > $s['w'] ? 'P' : 'L');
				$pdf->useTemplate($tplidx);
				if (method_exists($pdf, 'AliasNbPages')) $pdf->AliasNbPages();
			}
		}

		return $pagecount;
	}
	
	/**
	 * addMoreActionsButtons
	 *
	 * @param  mixed $parameters
	 * @param  mixed $object
	 * @param  mixed $action
	 * @param  mixed $hookmanager
	 * @return void
	 */
	function addMoreActionsButtons($parameters, &$object, &$action, $hookmanager)
	{
		global $langs, $conf;

		$langs->load("ultimatepdf@ultimatepdf");

		$out = '';

		if (is_array($parameters) && !empty($parameters)) {
			foreach ($parameters as $key => $value) {
				$$key = $value;
			}
		}

		$currentcontext = explode(':', $parameters['context']);

		return 0;
	}
}
