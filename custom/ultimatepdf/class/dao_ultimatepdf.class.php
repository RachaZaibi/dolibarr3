<?php
/* Copyright (C) 2012 Regis Houssin  	  <regis.houssin@capnetworks.com>
 * Copyright (C) 2012-2021 Philippe Grand <philippe.grand@atoo-net.com>
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
 *	\file       /ultimatepdf/class/dao_ultimatepdf.class.php
 *	\ingroup    ultimatepdf
 *	\brief      ultimatepdf DAO file class 
 */


/**
 *	\class      DaoUltimatepdf
 *	\brief      ultimatepdf DAO file class
 */
class DaoUltimatepdf
{
	/**
     * @var DoliDb Database handler
     */
    public $db;
	
	/**
	 * @var string 		Error string
     * @deprecated		Use instead the array of error strings
     * @see             errors
	 */
	public $error = '';
	
	/**
	 * @var string[] Array of error strings
	 */
	public $errors= array();

	/**
	 * @var int The object identifier
	 */
	public $id;
	
	/**
	 * @var int Entity
	 */
	public $entity;
	
	/**
	 * @var string name of design
	 */
	public $label;
	
	/**
	 * @var string description of design
	 */
	public $description;
	
	/**
	 * @var string[] options
	 */
	public $options = array();
	
	/**
	 * @var
	 */
	public $options_json;

	/**
	 * @var string[] design
	 */
	public $design = array();
	
	/**
	 * @var string[] designs
	 */
	public $designs = array();

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
	 *  Add object in database
	 *  
	 *  @param		user	User that create
	 * 	@return     int		>0 if OK, <0 if KO
	 */
	public function create($user)
	{
		global $conf;

		// Clean parameters
		$this->label 		= trim($this->label);
		$this->description	= trim($this->description);
		$this->options_json = json_encode($this->options);

		dol_syslog(get_class($this) . "::create " . $this->label);

		$this->db->begin();

		$now = dol_now();

		$sql = "INSERT INTO " . MAIN_DB_PREFIX . "ultimatepdf (";
		$sql .= "label";
		$sql .= ", description";
		$sql .= ", datec";
		$sql .= ", fk_user_creat";
		$sql .= ", options";
		$sql .= ", active";
		$sql .= ", entity";
		$sql .= ") VALUES (";
		$sql .= "'" . $this->db->escape($this->label) . "'";
		$sql .= ", '" . $this->db->escape($this->description) . "'";
		$sql .= ", '" . $this->db->idate($now) . "'";
		$sql .= ", " . $user->id;
		$sql .= ", '" . $this->db->escape($this->options_json) . "'";
		$sql .= ", 0";
		$sql .= ", " . $conf->entity;
		$sql .= ")";

		dol_syslog(get_class($this) . "::create sql=" . $sql, LOG_DEBUG);
		$result  = $this->db->query($sql);
		if ($result) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . "ultimatepdf");

			dol_syslog(get_class($this) . "::Create success id=" . $this->id);
			$this->db->commit();
			return $this->id;
		} else {
			dol_syslog(get_class($this) . "::Create echec " . $this->errors);
			$this->db->rollback();
			return -1;
		}
	}

    /**
	 * 	Update object in database
	 * 
	 * 	@param		user	User that create
	 * 	@return     int		>0 if OK, <0 if KO
	 */
	public function update($id, $user)
	{
		global $conf;

		// Clean parameters
		$this->label 		= trim($this->label);
		$this->description	= trim($this->description);
		$this->options_json = json_encode($this->options);

		dol_syslog(get_class($this) . "::update id=" . $id . " label=" . $this->label);

		$this->db->begin();

		$sql = "UPDATE " . MAIN_DB_PREFIX . "ultimatepdf SET";
		$sql .= " label = '" . $this->db->escape($this->label) . "'";
		$sql .= ", description = '" . $this->db->escape($this->description) . "'";
		$sql .= ", options = '" . $this->db->escape($this->options_json) . "'";
		$sql .= " WHERE rowid = " . $id;

		dol_syslog(get_class($this) . "::update sql=" . $sql, LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			dol_syslog(get_class($this) . "::Update success id=" . $id);
			$this->db->commit();
			return 1;
		} else {
			dol_syslog(get_class($this) . "::Update echec " . $this->errors, LOG_ERR);
			$this->db->rollback();
			return -1;
		}
	}

    /**
	 * 	Delete object in database
	 * 
	 * 	@return     int		>0 if OK, <0 if KO
	 */
	public function delete($id)
	{
		$error = 0;

		$this->db->begin();

		if (!$error) {
			$sql = "DELETE FROM " . MAIN_DB_PREFIX . "ultimatepdf";
			$sql .= " WHERE rowid = " . $id;
			dol_syslog(get_class($this) . "::Delete sql=" . $sql, LOG_DEBUG);
			if ($this->db->query($sql)) {
			} else {
				$error++;
				$this->errors .= $this->db->lasterror();
				dol_syslog(get_class($this) . "::Delete erreur -1 " . $this->errors, LOG_ERR);
			}
		}

		if (!$error) {
			dol_syslog(get_class($this) . "::Delete success id=" . $id);
			$this->db->commit();
			return 1;
		} else {
			dol_syslog(get_class($this) . "::Delete echec " . $this->errors);
			$this->db->rollback();
			return -1;
		}
	}

    /**
	 * 	Fetch object from database
	 * 
	 * 	@param		id		Object id
	 * 	@return     int		>0 if OK, <0 if KO
	 */
	public function fetch($id)
	{
		global $conf, $langs, $user;

		if (empty($id))

			$this->design = array();

		$sql = "SELECT rowid, label, description, options, active";
		$sql .= " FROM " . MAIN_DB_PREFIX . "ultimatepdf";
		$sql .= " WHERE rowid = " . $id;

		$result = $this->db->query($sql);
		if ($result) {
			if ($this->db->num_rows($result)) {
				$obj = $this->db->fetch_object($result);

				$this->id			= $obj->rowid;
				$this->label		= $obj->label;
				$this->description 	= $obj->description;
				$this->options		= json_decode($obj->options, true);
				$this->active		= $obj->active;

				return 1;
			} else {
				return -2;
			}
		} else {
			return -3;
		}
	}
	
	/**
	 *    Set status of a design
	 *
	 * @param    int $id		Id of design
	 * @param    string $type	Type of status (visible or active)
	 * @param    string $value	Value of status (0: disable, 1: enable)
	 * @return 	 int
	 */
	public function setDesign($id, $type = 'active', $value)
	{
		global $conf;

		$this->db->begin();

		$sql = "UPDATE " . MAIN_DB_PREFIX . "ultimatepdf";
		$sql .= " SET " . $this->db->escape($type) . " = " . (int) $value;
		$sql .= " WHERE rowid = " . (int) $id;

		dol_syslog(get_class($this) . "::setDesign sql=" . $sql, LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			$this->db->commit();
			return 1;
		} else {
			$this->db->rollback();
			return -1;
		}
	}

    /**
	 *	List of designs
	 *
	 *	@param		int		$id		
	 *	@return		void
	 */
	public function getDesigns()
	{
		global $conf;

		$this->designs = array();

		$sql = "SELECT rowid";
		$sql .= " FROM " . MAIN_DB_PREFIX . "ultimatepdf";
		$sql .= " ORDER by rowid";

		dol_syslog(get_class($this) . "::getDesigns sql=" . $sql, LOG_DEBUG);

		$result = $this->db->query($sql);
		if ($result) {
			$num = $this->db->num_rows($result);
			$i = 0;

			while ($i < $num) {
				$obj = $this->db->fetch_object($result);

				$objectstatic = new self($this->db);
				$ret = $objectstatic->fetch($obj->rowid);

				$this->designs[$i] = $objectstatic;

				$i++;
			}
		}
	}

    /**
	 *    Verify right
	 */
	public function verifyRight($id, $userid)
	{
		global $conf;

		$sql = "SELECT count(rowid) as nb";
		$sql .= " FROM " . MAIN_DB_PREFIX . "usergroup_user";
		$sql .= " WHERE fk_user=" . $userid;
		$sql .= " AND entity=" . $conf->entity;

		dol_syslog(get_class($this) . "::verifyRight sql=" . $sql, LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			$obj = $this->db->fetch_object($result);
			return $obj->nb;
		}
	}
}
?>
