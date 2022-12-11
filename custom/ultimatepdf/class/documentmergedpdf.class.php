<?php
/* Copyright (C) 2017  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2011-2020 Philippe Grand <philippe.grand@atoo-net.com>
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
 * \file        class/documentmergedpdf.class.php
 * \ingroup     ultimatepdf
 * \brief       This file is a CRUD class file for DocumentMergedPdf (Create/Read/Update/Delete)
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';
//require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
//require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

/**
 * Class for DocumentMergedPdf
 */
class DocumentMergedPdf extends CommonObject
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'documentmergedpdf';
	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'ultimatepdf_documentmergedpdf';
	/**
	 * @var string Name of table without prefix where object is stored
	 */
	//public $fk_element='fk_element';
	/**
	 * @var int  Does documentmergedpdf support multicompany module ? 0=No test on entity, 1=Test with field entity, 2=Test with link by societe
	 */
	public $ismultientitymanaged = 0;
	/**
	 * @var int  Does documentmergedpdf support extrafields ? 0=No, 1=Yes
	 */
	public $isextrafieldmanaged = 1;
	/**
	 * @var string String with name of icon for documentmergedpdf. Must be the part after the 'object_' into object_documentmergedpdf.png
	 */
	public $picto = 'documentmergedpdf@ultimatepdf';


	/**
	 *  'type' if the field format.
	 *  'label' the translation key.
	 *  'enabled' is a condition when the field must be managed.
	 *  'visible' says if field is visible in list (Examples: 0=Not visible, 1=Visible on list and create/update/view forms, 2=Visible on list only. Using a negative value means field is not shown by default on list but can be selected for viewing)
	 *  'notnull' is set to 1 if not null in database. Set to -1 if we must set data to null if empty ('' or 0).
	 *  'default' is a default value for creation (can still be replaced by the global setup of default values)
	 *  'index' if we want an index in database.
	 *  'foreignkey'=>'tablename.field' if the field is a foreign key (it is recommanded to name the field fk_...).
	 *  'position' is the sort order of field.
	 *  'searchall' is 1 if we want to search in this field when making a search from the quick search button.
	 *  'isameasure' must be set to 1 if you want to have a total on list for this field. Field type must be summable like integer or double(24,8).
	 *  'help' is a string visible as a tooltip on field
	 *  'comment' is not used. You can store here any text of your choice. It is not used by application.
	 *  'showoncombobox' if value of the field must be visible into the label of the combobox that list record
	 *  'arraykeyval' to set list of value if type is a list of predefined values. For example: array("0"=>"Draft","1"=>"Active","-1"=>"Cancel")
	 */

	// BEGIN MODULEBUILDER PROPERTIES
	/**
	 * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields = array(
		'rowid' => array('type' => 'integer', 'label' => 'TechnicalID', 'enabled' => 1, 'visible' => -1, 'position' => 1, 'notnull' => 1, 'index' => 1, 'comment' => "Id",),
		'entity' => array('type' => 'integer', 'label' => 'Entity', 'visible' => -1, 'enabled' => 1, 'position' => 20, 'notnull' => 1, 'index' => 1,),
		'fk_element' => array('type' => 'integer', 'label' => 'Element_id', 'enabled' => 1, 'visible' => 1, 'position' => 30, 'notnull' => -1,),
		'file_name' => array('type' => 'varchar(255)', 'label' => 'FileName', 'enabled' => 1, 'visible' => 1, 'position' => 50, 'notnull' => -1,),
		'element_name' => array('type' => 'varchar(255)', 'label' => 'Element_name', 'enabled' => 1, 'visible' => 1, 'position' => 60, 'notnull' => 1,),
		'date_creation' => array('type' => 'datetime', 'label' => 'DateCreation', 'enabled' => 1, 'visible' => -2, 'position' => 500, 'notnull' => 1,),
		'tms' => array('type' => 'timestamp', 'label' => 'DateModification', 'enabled' => 1, 'visible' => -2, 'position' => 501, 'notnull' => 1,),
		'fk_user_creat' => array('type' => 'integer', 'label' => 'UserAuthor', 'enabled' => 1, 'visible' => -2, 'position' => 510, 'notnull' => 1, 'foreignkey' => 'llx_user.rowid',),
		'fk_user_modif' => array('type' => 'integer', 'label' => 'UserModif', 'enabled' => 1, 'visible' => -2, 'position' => 511, 'notnull' => -1,),
	);
	public $rowid;
	public $entity;
	public $fk_element;
	public $file_name;
	public $element_name;
	public $date_creation;
	public $tms;
	public $fk_user_creat;
	public $fk_user_modif;

	/**
	 * @var DocumentMergedPdfLine[]
	 */
	public $lines = array();

	// END MODULEBUILDER PROPERTIES



	// If this object has a subtable with lines

	/**
	 * @var int    Name of subtable line
	 */
	//public $table_element_line = 'documentmergedpdfdet';
	/**
	 * @var int    Field with ID of parent key if this field has a parent
	 */
	//public $fk_element = 'fk_documentmergedpdf';
	/**
	 * @var int    Name of subtable class that manage subtable lines
	 */
	//public $class_element_line = 'DocumentMergedPdfline';
	/**
	 * @var array  Array of child tables (child tables to delete before deleting a record)
	 */
	//protected $childtables=array('documentmergedpdfdet');
	/**
	 * @var DocumentMergedPdfLine[]     Array of subtable lines
	 */
	//public $lines = array();



	/**
	 * Constructor
	 *
	 * @param DoliDb $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		global $conf, $user;

		$this->db = $db;

		if (empty($conf->global->MAIN_SHOW_TECHNICAL_ID) && isset($this->fields['rowid'])) $this->fields['rowid']['visible'] = 0;
		if (empty($conf->multicompany->enabled) && isset($this->fields['entity'])) $this->fields['entity']['enabled'] = 0;

		// Unset fields that are disabled
		foreach ($this->fields as $key => $val) {
			if (isset($val['enabled']) && empty($val['enabled'])) {
				unset($this->fields[$key]);
			}
		}
	}

	/**
	 * Create object into database
	 *
	 * @param  User $user      User that creates
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, Id of created object if OK
	 */
	public function create(User $user, $notrigger = false)
	{
		return $this->createCommon($user, $notrigger);
	}

	/**
	 * Load object in memory from the database
	 *
	 * @param int    $id   Id object
	 * @param string $ref  Ref
	 * @return int         <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id, $ref = null)
	{
		$result = $this->fetchCommon($id, $ref);
		if ($result > 0 && !empty($this->table_element_line)) $this->fetchLines();
		return $result;
	}

	/**
	 * Load object lines in memory from the database
	 *
	 * @return int         <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetchLines()
	{
		$this->lines = array();

		// Load lines with object DocumentMergedPdfLine

		return count($this->lines) ? 1 : 0;
	}

	/**
	 *  Load object in memory from the database
	 *
	 *  @param	int		$id    Id object
	 *  @param	string	$lang  lang string id
	 *  @return int          	<0 if KO, >0 if OK
	 */
	public function fetch_by_element($object)
	{
		global $langs, $conf;

		$sql = "SELECT";
		$sql .= " t.rowid,";

		$sql .= " t.fk_element,";
		$sql .= " t.file_name,";
		$sql .= " t.element_name,";
		$sql .= " t.fk_user_creat,";
		$sql .= " t.fk_user_modif,";
		$sql .= " t.date_creation,";
		$sql .= " t.tms";

		$sql .= " FROM " . MAIN_DB_PREFIX . "ultimatepdf_documentmergedpdf as t";
		$sql .= " WHERE t.fk_element = " . $object->id;
		$sql .= " AND t.element_name = '" . $object->element . "'";

		dol_syslog(__METHOD__ . ' sql=' . $sql, LOG_DEBUG);
		$resql = $this->db->query($sql);

		if ($resql) {

			if ($this->db->num_rows($resql)) {
				while ($obj = $this->db->fetch_object($resql)) {
					$line = new DocumentMergedPdfLine();

					$line->id = $obj->rowid;

					$line->fk_element = $obj->fk_element;
					$line->file_name = $obj->file_name;
					$line->element_name = $obj->element_name;
					$line->fk_user_creat = $obj->fk_user_creat;
					$line->fk_user_modif = $obj->fk_user_modif;
					$line->date_creation = $this->db->jdate($obj->date_creation);
					$line->tms = $this->db->jdate($obj->tms);
					$this->lines[$obj->file_name] = $line;
				}
			}
			$this->db->free($resql);

			return 1;
		} else {
			dol_print_error($this->db, 'Failed to get document to merge');;
			return -1;
		}
	}

	/**
	 * Update object into database
	 *
	 * @param  User $user      User that modifies
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, >0 if OK
	 */
	public function update(User $user, $notrigger = false)
	{
		return $this->updateCommon($user, $notrigger);
	}

	/**
	 * Delete object in database
	 *
	 * @param User $user       User that deletes
	 * @param bool $notrigger  false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, >0 if OK
	 */
	public function delete(User $user, $notrigger = false)
	{
		return $this->deleteCommon($user, $notrigger);
	}

	/**
	 *  Delete object in database
	 *
	 *	@param  User	$user        User that deletes
	 *  @param  string	$lang_id	 language
	 *  @param  int		$notrigger	 0=launch triggers after, 1=disable triggers
	 *  @return	int					 <0 if KO, >0 if OK
	 */
	public function delete_by_element($user, $lang_id = '', $notrigger = 0)
	{
		global $conf, $langs, $object;
		$error = 0;

		$this->db->begin();

		if (!$error) {
			$sql = "DELETE FROM " . MAIN_DB_PREFIX . "ultimatepdf_documentmergedpdf";
			$sql .= " WHERE fk_element='" . $object->id . "'";
			$sql .= " AND element_name = '" . $object->element . "'";

			dol_syslog(__METHOD__ . ' sql=' . $sql, LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				$error++;
				$this->errors[] = "Error " . $this->db->lasterror();
			}
		}

		// Commit or rollback
		if ($error) {
			foreach ($this->errors as $errmsg) {
				dol_syslog(__METHOD__ . 'errmsg=' . $errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
			}
			$this->db->rollback();
			return -1 * $error;
		} else {
			$this->db->commit();
			return 1;
		}
	}
}

/**
 * Class DocumentMergedPdfLine. You can also remove this and generate a CRUD class for lines objects.
 */

class DocumentMergedPdfLine
{
	// @var int ID
	public $id;
	// @var mixed Sample line fk_element
	public $fk_element;
	// @var mixed Sample line file_name
	public $file_name;
	// @var mixed Sample line element_name
	public $element_name;
	// @var mixed Sample line date_creation
	public $date_creation;
	// @var mixed Sample line tms
	public $tms;
	// @var mixed Sample line fk_user_creat
	public $fk_user_creat;
	// @var mixed Sample line fk_user_modif
	public $fk_user_modif;

	function __construct()
	{
		return 1;
	}
}
