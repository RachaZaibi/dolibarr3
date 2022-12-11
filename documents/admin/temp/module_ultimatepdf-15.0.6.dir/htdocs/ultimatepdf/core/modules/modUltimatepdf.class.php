<?php
/* Copyright (C) 2004-2018 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2010-2017 Regis Houssin  <regis.houssin@capnetworks.com>
 * Copyright (C) 2011-2022 Philippe Grand <philippe.grand@atoo-net.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * 	\defgroup   ultimatepdf     Module ultimatepdf
 *  \brief      ultimatepdf module descriptor.
 *
 *  \file       htdocs/ultimatepdf/core/modules/modultimatepdf.class.php
 *  \ingroup    ultimatepdf
 *  \brief      Description and activation file for module ultimatepdf
 */
include_once DOL_DOCUMENT_ROOT .'/core/modules/DolibarrModules.class.php';


// The class name should start with a lower case mod for Dolibarr to pick it up
// so we ignore the Squiz.Classes.ValidClassName.NotCamelCaps rule.
// @codingStandardsIgnoreStart
/**
 *  Description and activation class for module ultimatepdf
 */
class modultimatepdf extends DolibarrModules
{
	// @codingStandardsIgnoreEnd
	/**
	 * Constructor. Define names, constants, directories, boxes, permissions
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
        global $langs, $conf;

        $this->db = $db;

		// Id for module (must be unique).
		// Use here a free id (See in Home -> System information -> Dolibarr for list of used modules id).
		$this->numero = 300100;		// TODO Go on page https://wiki.dolibarr.org/index.php/List_of_modules_id to reserve id number for your module
		// Key text used to identify module (for permissions, menus, etc...)
		$this->rights_class = 'ultimatepdf';
		// Gives the possibility to the module, to provide his own family info and position of this family (Overwrite $this->family and $this->module_position. Avoid this)
		//$this->familyinfo = array('myownfamily' => array('position' => '01', 'label' => $langs->trans("MyOwnFamily")));
		$this->familyinfo = array(
            'atoonet' => array(
                'position' => '001',
                'label' => $langs->trans("AtooNet")
            )
        );
		// Family can be 'crm','financial','hr','projects','products','ecm','technic','interface','other'
		// It is used to group modules by family in module setup page
		$this->family = "Atoo.Net";
		// Module position in the family on 2 digits ('01', '10', '20', ...)
		$this->module_position = '01';
		// Module label (no space allowed), used if translation string 'ModuleultimatepdfName' not found (MyModue is name of module).
		$this->name = preg_replace('/^mod/i','',get_class($this));
		// Module description, used if translation string 'ModuleultimatepdfDesc' not found (MyModue is name of module).
		$this->description = $langs->trans("Module300100Desc");
		// Used only if file README.md and README-LL.md not found.
		$this->descriptionlong = $langs->trans("Module300100DescLong");

		$this->editor_name = 'philippe.grand@atoo-net.com';
		$this->editor_url = 'https://atoo-net.com/';

		// Possible values for version are: 'development', 'experimental', 'dolibarr', 'dolibarr_deprecated' or a version string like 'x.y.z'
		$this->version = '15.0.6';
		// Url to the file with your last numberversion of this module
		$this->url_last_version = 'https://atoo-net.com/versionmodules/ultimatepdf/v15/ultimatepdf.txt';
		// Key used in llx_const table to save module status enabled/disabled (where ULTIMATEPDF is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		// Name of image file used for this module.
		// If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
		// If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
		$this->picto = 'ultimatepdf@ultimatepdf';

		// Defined all module parts (triggers, login, substitutions, menus, css, etc...)
		// for default path (eg: /ultimatepdf/core/xxxxx) (0=disable, 1=enable)
		// for specific path of parts (eg: /ultimatepdf/core/modules/barcode)
		// for specific css file (eg: /ultimatepdf/css/ultimatepdf.css.php)
		$this->module_parts = array(
		                        	'triggers' => 1,                                 	// Set this to 1 if module has its own trigger directory (core/triggers)
									'login' => 0,                                    	// Set this to 1 if module has its own login method file (core/login)
									'substitutions' => 1,                            	// Set this to 1 if module has its own substitution function file (core/substitutions)
									'menus' => 0,                                    	// Set this to 1 if module has its own menus handler directory (core/menus)
									'theme' => 0,                                    	// Set this to 1 if module has its own theme directory (theme)
		                        	'tpl' => 1,                                      	// Set this to 1 if module overwrite template dir (core/tpl)
									'barcode' => 0,                                  	// Set this to 1 if module has its own barcode directory (core/modules/barcode)
									'models' => 1,                                   	// Set this to 1 if module has its own models directory (core/modules/xxx)
									'css' => array('/ultimatepdf/css/ultimatepdf.css.php'),	// Set this to relative path of css file if module has its own css file
	 								'js' => array(//   '/mymodule/js/mymodule.js.php',
									),          // Set this to relative path of js file if module must load a js on all pages
								'hooks' => array('data'=>array('propalcard', 'ordercard', 'invoicecard', 'contractcard', 'invoicesuppliercard', 'ordersuppliercard', 'interventioncard', 'toprightmenu', 'pdfgeneration', 'supplier_proposalcard', 'warehousecard'), 'entity'=>'0') 	// Set here all hooks context managed by module. To find available hook context, make a "grep -r '>initHooks(' *" on source code. You can also set hook context 'all'
		                        );

		// Data directories to create when module is enabled.
		// Example: this->dirs = array("/ultimatepdf/temp","/ultimatepdf/subdir");
		$this->dirs = array('/ultimatepdf/temp', '/ultimatepdf/otherlogo', '/ultimatepdf/proposals', '/ultimatepdf/orders', '/ultimatepdf/fichinter', '/ultimatepdf/invoices', '/ultimatepdf/supplier_proposals', '/ultimatepdf/supplier_orders', '/ultimatepdf/supplier_invoices', '/ultimatepdf/contracts', '/ultimatepdf/background', '/ultimatepdf/backgroundpdf');

		// Config pages. Put here list of php page, stored into ultimatepdf/admin directory, to use to setup module.
		$this->config_page_url = array("setup.php@ultimatepdf");

		// Dependencies
		$this->hidden = false;			// A condition to hide module
		$this->depends = array();		// List of module class names as string that must be enabled if this module is enabled
		$this->requiredby = array();	// List of module ids to disable if this one is disabled
		$this->conflictwith = array();	// List of module class names as string this module is in conflict with
		$this->langfiles = array("ultimatepdf@ultimatepdf");
		$this->phpmin = array(5, 6);					// Minimum version of PHP required by module
		$this->need_dolibarr_version = array(14, 0, 0);	// Minimum version of Dolibarr required by module
		$this->warnings_activation = array();                     // Warning to show when we activate module. array('always'='text') or array('FR'='textfr','ES'='textes'...)
		$this->warnings_activation_ext = array();                 // Warning to show when we activate an external module. array('always'='text') or array('FR'='textfr','ES'='textes'...)
		//$this->automatic_activation = array('FR'=>'ultimatepdfWasAutomaticallyActivatedBecauseOfYourCountryChoice');
		//$this->always_enabled = true;								// If true, can't be disabled

		// Constants
		// List of particular constants to add when module is enabled (key, 'chaine', value, desc, visible, 'current' or 'allentities', deleteonunactive)
		// Example: $this->const=array(0=>array('ULTIMATEPDF_MYNEWCONST1','chaine','myvalue','This is a constant to add',1),
		//                             1=>array('ULTIMATEPDF_MYNEWCONST2','chaine','myvalue','This is another constant to add',0, 'current', 1)
		// );
		// Constants
		$this->const = array();			// List of particular constants to add when module is enabled (key, 'chaine', value, desc, visible, 0 or 'allentities')
		$r = 0;

		$this->const[$r][0] = "MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS";
		$this->const[$r][1] = "yesno";
		$this->const[$r][2] = '0';
		$this->const[$r][3] = 'Hide product details within documents';
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 0;
		$r++;

		$this->const[$r][0] = "MAIN_GENERATE_DOCUMENTS_HIDE_DESC";
		$this->const[$r][1] = "yesno";
		$this->const[$r][2] = '0';
		$this->const[$r][3] = 'Hide product description within documents';
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 0;
		$r++;

		$this->const[$r][0] = "MAIN_GENERATE_DOCUMENTS_HIDE_REF";
		$this->const[$r][1] = "yesno";
		$this->const[$r][2] = '0';
		$this->const[$r][3] = 'Hide reference within documents';
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 0;
		$r++;

		$this->const[$r][0] = "MAIN_TVAINTRA_NOT_IN_ADDRESS";
		$this->const[$r][1] = "yesno";
		$this->const[$r][2] = '0';
		$this->const[$r][3] = 'Hide tva within documents';
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 0;
		$r++;

		$this->const[$r][0] = "MAIN_DISPLAY_FOLD_MARK";
		$this->const[$r][1] = "yesno";
		$this->const[$r][2] = '0';
		$this->const[$r][3] = 'Show by default fold mark within documents';
		$this->const[$r][4] = 1;
		$this->const[$r][5] = 0;
		$r++;

		$this->const[$r][0] = "MAIN_PDF_ADDALSOTARGETDETAILS";
		$this->const[$r][1] = "yesno";
		$this->const[$r][2] = '0';
		$this->const[$r][3] = 'Add address details within documents';
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 0;
		$r++;

		$this->const[$r][0] = "MAIN_PDF_FORCE_FONT";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = '0';
		$this->const[$r][3] = 'Add choice of font';
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 0;
		$r++;

		$this->const[$r][0] = "MAIN_PDF_FREETEXT_HEIGHT";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = '0';
		$this->const[$r][3] = 'Add set of freetext height';
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 0;
		$r++;

		$this->const[$r][0] = "MAIN_USE_COMPANY_NAME_OF_CONTACT";
		$this->const[$r][1] = "yesno";
		$this->const[$r][2] = '0';
		$this->const[$r][3] = 'Use company name of contact';
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 0;
		$r++;

		$this->const[$r][0] = "MAIN_VIEW_LINE_NUMBER";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = '1';
		$this->const[$r][3] = 'Use view line number';
		$this->const[$r][4] = 1;
		$this->const[$r][5] = 0;
		$r++;

		$this->const[$r][0] = "MAIN_FORCE_RELOAD_PAGE";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = '1';
		$this->const[$r][3] = 'Main force to reload page';
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 0;
		$r++;

		$this->const[$r][0] = "ULTIMATEPDF_MAIN_VERSION";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = $this->version;
		$this->const[$r][3] = 'Ultimatepdf main version';
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 'current';
		$this->const[$r][6] = 1;
		$r++;

		if (!isset($conf->ultimatepdf) || !isset($conf->ultimatepdf->enabled)) {
			$conf->ultimatepdf = new stdClass();
			$conf->ultimatepdf->enabled = 0;
		}


		// Array to add new pages in new tabs
        $this->tabs = array();
		// Example:
		// $this->tabs[] = array('data'=>'objecttype:+tabname1:Title1:mylangfile@ultimatepdf:$user->rights->ultimatepdf->read:/ultimatepdf/mynewtab1.php?id=__ID__');  					// To add a new tab identified by code tabname1
		// $this->tabs[] = array('data'=>'objecttype:+tabname2:SUBSTITUTION_Title2:mylangfile@ultimatepdf:$user->rights->othermodule->read:/ultimatepdf/mynewtab2.php?id=__ID__',  	// To add another new tab identified by code tabname2. Label will be result of calling all substitution functions on 'Title2' key.
		// $this->tabs[] = array('data'=>'objecttype:-tabname:NU:conditiontoremove');                                                     										// To remove an existing tab identified by code tabname
		//
		// Where objecttype can be
		// 'categories_x'	  to add a tab in category view (replace 'x' by type of category (0=product, 1=supplier, 2=customer, 3=member)
		// 'contact'          to add a tab in contact view
		// 'contract'         to add a tab in contract view
		// 'group'            to add a tab in group view
		// 'intervention'     to add a tab in intervention view
		// 'invoice'          to add a tab in customer invoice view
		// 'invoice_supplier' to add a tab in supplier invoice view
		// 'member'           to add a tab in fundation member view
		// 'opensurveypoll'	  to add a tab in opensurvey poll view
		// 'order'            to add a tab in customer order view
		// 'order_supplier'   to add a tab in supplier order view
		// 'payment'		  to add a tab in payment view
		// 'payment_supplier' to add a tab in supplier payment view
		// 'product'          to add a tab in product view
		// 'propal'           to add a tab in propal view
		// 'project'          to add a tab in project view
		// 'stock'            to add a tab in stock view
		// 'thirdparty'       to add a tab in third party view
		// 'user'             to add a tab in user view


		// Dictionaries
		$this->dictionaries = array(
			'langs' => 'ultimatepdf@ultimatepdf',
			'tabname' => array(
				MAIN_DB_PREFIX . "c_ultimatepdf_line",
				MAIN_DB_PREFIX . "c_ultimatepdf_title"
			),
			'tablib' => array(
				"DictionaryUltimatepdfLine",
				"DictionaryUltimatepdfTitle"
			),

			// Request to select fields
			'tabsql' => array(
				'SELECT ul.rowid as rowid, ul.code, ul.label, ul.description, ul.active FROM ' . MAIN_DB_PREFIX . 'c_ultimatepdf_line as ul',
				'SELECT ut.rowid as rowid, ut.code, ut.label, ut.description, ut.active FROM ' . MAIN_DB_PREFIX . 'c_ultimatepdf_title as ut'
			),
			// Sort order
			'tabsqlsort' => array("code ASC", "code ASC"),
			// List of fields (result of select to show dictionnary)
			'tabfield' => array("code,label,description", "code,label,description"),
			// List of fields (list of fields to edit a record)
			'tabfieldvalue' => array("code,label,description", "code,label,description"),
			// List of fields (list of fields for insert)
			'tabfieldinsert' => array("code,label,description", "code,label,description"),
			// Name of columns with primary key (try to always name it 'rowid')
			'tabrowid' => array("rowid", "rowid"),
			// Condition to show each dictionnary
			'tabcond' => array(!empty($conf->ultimatepdf->enabled), !empty($conf->ultimatepdf->enabled))
		);

		// Boxes/Widgets
		// Add here list of php file(s) stored in ultimatepdf/core/boxes that contains class to show a widget.
		$this->boxes = array(
			/*0=>array('file'=>'ultimatepdfwidget1.php@ultimatepdf','note'=>'Widget provided by ultimatepdf','enabledbydefaulton'=>'Home'),*/
			//1=>array('file'=>'ultimatepdfwidget2.php@ultimatepdf','note'=>'Widget provided by ultimatepdf'),
			//2=>array('file'=>'ultimatepdfwidget3.php@ultimatepdf','note'=>'Widget provided by ultimatepdf')
		);


		// Cronjobs (List of cron jobs entries to add when module is enabled)
		// unit_frequency must be 60 for minute, 3600 for hour, 86400 for day, 604800 for week
		$this->cronjobs = array(
			/*0=>array('label'=>'MyJob label', 'jobtype'=>'method', 'class'=>'/ultimatepdf/class/dao_ultimatepdf.class.php', 'objectname'=>'dao_ultimatepdf', 'method'=>'doScheduledJob', 'parameters'=>'', 'comment'=>'Comment', 'frequency'=>2, 'unitfrequency'=>3600, 'status'=>0, 'test'=>true)*/
		);
		// Example: $this->cronjobs=array(0=>array('label'=>'My label', 'jobtype'=>'method', 'class'=>'/dir/class/file.class.php', 'objectname'=>'MyClass', 'method'=>'myMethod', 'parameters'=>'param1, param2', 'comment'=>'Comment', 'frequency'=>2, 'unitfrequency'=>3600, 'status'=>0, 'test'=>true),
		//                                1=>array('label'=>'My label', 'jobtype'=>'command', 'command'=>'', 'parameters'=>'param1, param2', 'comment'=>'Comment', 'frequency'=>1, 'unitfrequency'=>3600*24, 'status'=>0, 'test'=>true)
		// );


		// Permissions
		$this->rights = array();		// Permission array used by this module

		$r=0;
		$this->rights[$r][0] = $this->numero + $r;	// Permission id (must not be already used)
		$this->rights[$r][1] = 'Read infos du modele';	// Permission label
		$this->rights[$r][3] = 1; 					// Permission by default for new user (0/1)
		$this->rights[$r][4] = 'read';				// In php code, permission will be checked by test if ($user->rights->ultimatepdf->level1->level2)
		$this->rights[$r][5] = '';				    // In php code, permission will be checked by test if ($user->rights->ultimatepdf->level1->level2)

		$r++;
		$this->rights[$r][0] = $this->numero + $r;	// Permission id (must not be already used)
		$this->rights[$r][1] = 'Create/Update fiche du modele';	// Permission label
		$this->rights[$r][3] = 1; 					// Permission by default for new user (0/1)
		$this->rights[$r][4] = 'write';				// In php code, permission will be checked by test if ($user->rights->ultimatepdf->level1->level2)
		$this->rights[$r][5] = '';				    // In php code, permission will be checked by test if ($user->rights->ultimatepdf->level1->level2)

		$r++;
		$this->rights[$r][0] = $this->numero + $r;	// Permission id (must not be already used)
		$this->rights[$r][1] = 'Delete fiche du modele';	// Permission label
		$this->rights[$r][3] = 1; 					// Permission by default for new user (0/1)
		$this->rights[$r][4] = 'delete';				// In php code, permission will be checked by test if ($user->rights->ultimatepdf->level1->level2)
		$this->rights[$r][5] = '';				    // In php code, permission will be checked by test if ($user->rights->ultimatepdf->level1->level2)
			
		$r++;
		$this->rights[$r][0] = $this->numero + $r;	// Permission id (must not be already used)
		$this->rights[$r][1] = 'Configuration du module';	// Permission label
		$this->rights[$r][3] = 0; 					// Permission by default for new user (0/1)
		$this->rights[$r][4] = 'config';				// In php code, permission will be checked by test if ($user->rights->ultimatepdf->level1->level2)
		$this->rights[$r][5] = '';				    // In php code, permission will be checked by test if ($user->rights->ultimatepdf->level1->level2)


		// Main menu entries
		$this->menu = array();			// List of menus to add
		$r=0;

		// Add here entries to declare new menus

		/* BEGIN MODULEBUILDER TOPMENU */
		$this->menu[$r++]=array('fk_menu'=>'',			                // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
								'type'=>'top',			                // This is a Top menu entry
								'titre'=>'ultimatepdf',
								'mainmenu'=>'ultimatepdf',
								'leftmenu'=>'',
								'url'=>'/custom/ultimatepdf/admin/setup.php',
								'langs'=>'ultimatepdf@ultimatepdf',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
								'position'=>1000+$r,
								'enabled'=>'$conf->global->USE_TOP_MENU_ACCESS_FOR_ULTIMATEPDF_SETUP',	// Define condition to show or hide menu entry. Use '$conf->ultimatepdf->enabled' if entry must be visible if module is enabled.
								'perms'=>'1',			                // Use 'perms'=>'$user->rights->ultimatepdf->level1->level2' if you want your menu with a permission rules
								'target'=>'',
								'user'=>2);				                // 0=Menu for internal users, 1=external users, 2=both

		/* END MODULEBUILDER TOPMENU */

		// Exports
		$r=1;

		/* BEGIN MODULEBUILDER EXPORT MYOBJECT */
		/*
		$langs->load("ultimatepdf@ultimatepdf");
		$this->export_code[$r]=$this->rights_class.'_'.$r;
		$this->export_label[$r]='dao_ultimatepdfLines';	// Translation key (used only if key ExportDataset_xxx_z not found)
		$this->export_icon[$r]='dao_ultimatepdf@ultimatepdf';
		$keyforclass = 'dao_ultimatepdf'; $keyforclassfile='/mymobule/class/dao_ultimatepdf.class.php'; $keyforelement='dao_ultimatepdf';
		include DOL_DOCUMENT_ROOT.'/core/commonfieldsinexport.inc.php';
		$keyforselect='dao_ultimatepdf'; $keyforaliasextra='extra'; $keyforelement='dao_ultimatepdf';
		include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
		//$this->export_dependencies_array[$r]=array('mysubobject'=>'ts.rowid', 't.myfield'=>array('t.myfield2','t.myfield3')); // To force to activate one or several fields if we select some fields that need same (like to select a unique key if we ask a field of a child to avoid the DISTINCT to discard them, or for computed field than need several other fields)
		$this->export_sql_start[$r]='SELECT DISTINCT ';
		$this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'dao_ultimatepdf as t';
		$this->export_sql_end[$r] .=' WHERE 1 = 1';
		$this->export_sql_end[$r] .=' AND t.entity IN ('.getEntity('dao_ultimatepdf').')';
		$r++; */
		/* END MODULEBUILDER EXPORT MYOBJECT */
	}

	/**
	 *	Function called when module is enabled.
	 *	The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *	It also creates data directories
	 *
     *	@param      string	$options    Options when enabling module ('', 'noboxes')
	 *	@return     int             	1 if OK, 0 if KO
	 */
	public function init($options='')
	{
		$result=$this->_load_tables('/ultimatepdf/sql/');
		if ($result < 0) return -1; // Do not activate module if not allowed errors found on module SQL queries (the _load_table run sql with run_sql with error allowed parameter to 'default')

		// Create extrafields
		include_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
		$extrafields = new ExtraFields($this->db);

		//$result1=$extrafields->addExtraField('myattr1', "New Attr 1 label", 'boolean', 1,  3, 'thirdparty',   0, 0, '', '', 1, '', 0, 0, '', '', 'ultimatepdf@ultimatepdf', '$conf->ultimatepdf->enabled');
		//$result2=$extrafields->addExtraField('myattr2', "New Attr 2 label", 'varchar', 1, 10, 'project',      0, 0, '', '', 1, '', 0, 0, '', '', 'ultimatepdf@ultimatepdf', '$conf->ultimatepdf->enabled');
		//$result3=$extrafields->addExtraField('myattr3', "New Attr 3 label", 'varchar', 1, 10, 'bank_account', 0, 0, '', '', 1, '', 0, 0, '', '', 'ultimatepdf@ultimatepdf', '$conf->ultimatepdf->enabled');
		//$result4=$extrafields->addExtraField('myattr4', "New Attr 4 label", 'select',  1,  3, 'thirdparty',   0, 1, '', array('options'=>array('code1'=>'Val1','code2'=>'Val2','code3'=>'Val3')), 1 '', 0, 0, '', '', 'ultimatepdf@ultimatepdf', '$conf->ultimatepdf->enabled');
		//$result5=$extrafields->addExtraField('myattr5', "New Attr 5 label", 'text',    1, 10, 'user',         0, 0, '', '', 1, '', 0, 0, '', '', 'ultimatepdf@ultimatepdf', '$conf->ultimatepdf->enabled');

		dol_include_once('/ultimatepdf/lib/ultimatepdf.lib.php');

		global $db, $conf;
		
		$path = dol_buildpath($this->name, 0);
		$pathfonts = $path.'/fonts';
		dolCopyDir($pathfonts, DOL_DOCUMENT_ROOT.'/includes/tecnickcom/tcpdf/fonts', 0, 1);	

		$sql = array(
				"INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, entity, type) VALUES
					('ultimate_contract', '".$conf->entity."', 'contract'),
					('ultimate_expensereport', '".$conf->entity."', 'expensereport'),
					('ultimate_inter', '".$conf->entity."', 'ficheinter'),
					('ultimate_invoice', '".$conf->entity."', 'invoice'),
					('ultimate_order', '".$conf->entity."', 'order'),
					('ultimate_project', '".$conf->entity."', 'project'),
					('ultimate_propal', '".$conf->entity."', 'propal'),					
					('ultimate_shipment', '".$conf->entity."', 'shipping'),
					('ultimate_delivery', '".$conf->entity."', 'delivery'),
					('ultimate_product', '".$conf->entity."', 'product'),
					('ultimate_bom', '".$conf->entity."', 'bom'),
					('ultimate_supplierorder', '".$conf->entity."', 'order_supplier'),
					('ultimate_supplierinvoice', '".$conf->entity."', 'invoice_supplier'),					
					('ultimate_supplierproposal', '".$conf->entity."', 'supplier_proposal');",
					
				"UPDATE ".MAIN_DB_PREFIX."facture SET `model_pdf` = 'ultimate_invoice' WHERE ".MAIN_DB_PREFIX."facture.`model_pdf` = 'ultimate_invoice1' AND ".MAIN_DB_PREFIX."facture.`model_pdf` = 'ultimate_invoice2';",
				"UPDATE ".MAIN_DB_PREFIX."propal SET `model_pdf` = 'ultimate_propal' WHERE ".MAIN_DB_PREFIX."propal.`model_pdf` = 'ultimate_propal1' AND ".MAIN_DB_PREFIX."propal.`model_pdf` = 'ultimate_propal2';",
				"UPDATE ".MAIN_DB_PREFIX."commande SET `model_pdf` = 'ultimate_order' WHERE ".MAIN_DB_PREFIX."commande.`model_pdf` = 'ultimate_order1' AND ".MAIN_DB_PREFIX."commande.`model_pdf` = 'ultimate_order2' ;"				
		);


		$result = $this->load_tables();

		$result = $this->setFirstDesign();

		// Check current version
		/*if (!checkUltimatepdfVersion())
		{

		}*/

		dolibarr_set_const($this->db, "MAIN_VIEW_LINE_NUMBER", '1', 'chaine', 0, '', $conf->entity);
		dolibarr_set_const($this->db, "CHECKLASTVERSION_EXTERNALMODULE", '1', 'int', 0, '', $conf->entity);

		return $this->_init($sql, $options);
	}

	/**
	 *	Function called when module is disabled.
	 *	Remove from database constants, boxes and permissions from Dolibarr database.
	 *	Data directories are not deleted
	 *
	 *	@param      string	$options    Options when enabling module ('', 'noboxes')
	 *	@return     int             	1 if OK, 0 if KO
	 */
	public function remove($options = '')
	{
		global $conf;

		$sql = array(
			"DELETE FROM " . MAIN_DB_PREFIX . "document_model WHERE entity = '" . $conf->entity . "' AND
				(nom = 'ultimate_invoice' OR nom = 'ultimate_invoice1' OR nom = 'ultimate_invoice2'	OR nom = 'ultimate_weight_invoice1'
				OR nom = 'ultimate_propal' OR nom = 'ultimate_propal1' OR nom = 'ultimate_propal2' OR nom = 'ultimate_weight_propal1'
				OR nom = 'ultimate_order' OR nom = 'ultimate_order1' OR nom = 'ultimate_order2' OR nom = 'ultimate_weight_order1'
				OR nom = 'ultimate_proforma1' OR nom = 'ultimate_proforma2'
				OR nom = 'ultimate_inter' OR nom = 'best_inter'
				OR nom = 'ultimate_shipment' OR nom = 'ultimate_receipt' OR nom = 'ultimate_product'
				OR nom = 'ultimate_supplierorder' OR nom = 'ultimate_supplierinvoice'  OR nom = 'ultimate_supplierproposal' OR nom = 'ultimate_delivery'
				OR nom = 'ultimate_project' OR nom = 'ultimatecontract' OR nom = 'ultimate_contract' OR nom = 'ultimate_expensereport' OR nom = 'ultimate_bom');",
			"DELETE FROM " . MAIN_DB_PREFIX . "extrafields WHERE entity = '" . $conf->entity . "'
				AND (name = 'newline' OR name = 'newtitle' OR name = 'newprice' OR name = 'newrdv');"
		);

		return $this->_remove($sql, $options);
	}
	
	/**
	 *		Create tables and keys required by module
	 *		This function is called by this->init.
	 *
	 * 		@return		int		<=0 if KO, >0 if OK
	 */
	function load_tables()
	{
		return $this->_load_tables('/ultimatepdf/sql/');
	}

	/**
	 *	Set the first design
	 *
	 *	@return void
	 */
	function setFirstDesign()
	{
		global $user, $langs, $conf;

		$langs->load('ultimatepdf@ultimatepdf');

		$sql = 'SELECT count(rowid) FROM '.MAIN_DB_PREFIX.'ultimatepdf';
		$res = $this->db->query($sql);
		if ($res) $num = $this->db->fetch_array($res);
		else dol_print_error($this->db);

		if (empty($num[0]))
		{
			$this->db->begin();

			$now = dol_now();
			$optionarray =  json_encode(array(
					'dashdotted'=>'8, 2',
					'bgcolor'=>'aad4ff',
					'opacity'=>'0.5',
					'roundradius'=>'2',
					'bordercolor'=>'003f7f',
					'senderstyle'=>'FD',
					'receiptstyle'=>'S',
					'textcolor'=>'191919',
					'footertextcolor'=>'191919',
					'qrcodecolor'=>'191919',
					'widthnumbering'=>'10',
					'widthdate'=>'20',
					'widthtype'=>'20',
					'widthproject'=>'20',
					'widthvat'=>'10',
					'widthup'=>'14',
					'widthqty'=>'12',
					'widthunit'=>'12',
					'widthdiscount'=>'10',
					'widthref'=>'20',
					'withref'=>'no',
					'withoutvat'=>'no',
					'showdetails'=>'',
					'otherlogo'=>'',
					'otherlogo_file'=>'',
					'otherfont'=>'Helvetica',
					'heightforfreetext'=>'20',
					'freetextfontsize'=>'7',
					'usebackground'=>'',
					'imglinesize'=>'20',
					'logoheight'=>'20',
					'logowidth'=>'40',
					'invertSenderRecipient'=>'no',
					'widthrecbox'=>'93',
					'otherlogoheight'=>'20',
					'otherlogowidth'=>'40',
					'marge_gauche'=>'10',
					'marge_droite'=>'10',
					'marge_haute'=>'10',
					'marge_basse'=>'10',
					'otherlogoname'=>'',
					'otherlogosmall'=>'',
					'aliascompany'=>'',
					'aliasaddress'=>'',
					'aliastown'=>'',
					'aliaszip'=>'',
					'country_id'=>'',
					'aliasphone'=>'',
					'aliasfax'=>'',
					'aliasemail'=>'',
					'aliasurl'=>''
			));

			$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'ultimatepdf (';
			$sql.= 'label';
			$sql.= ', description';
			$sql.= ', options';
			$sql.= ', datec';
			$sql.= ', fk_user_creat';
			$sql.= ') VALUES (';
			$sql.= '"'.$langs->trans("MasterDesign").'"';
			$sql.= ', "'.$langs->trans("MasterDesignDesc").'"';
			$sql.= ", '".$optionarray."'";
			$sql.= ', "'.$this->db->idate($now).'"';
			$sql.= ', '.$user->id;
			$sql.= ')';

			if ($this->db->query($sql))
			{
				// par défaut le premier design est sélectionné
				dolibarr_set_const($this->db, "ULTIMATE_DESIGN", 1,'chaine',0,'',$conf->entity);
				$this->db->commit();
				return 1;
			}
			else
			{
				$this->db->rollback();
				return -1;
			}
		}
		else
		{
			return 0;
		}
	}

}
