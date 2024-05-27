<?php
/**
 * Implementation of user and group access object
 *
 * @category   DMS
 * @package    SeedDMS_Core
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal, 2006-2008 Malcolm Cowe,
 *             2010 Uwe Steinmann
 * @version    Release: 5.1.9
 */

/**
 * Class to represent a user access right.
 * This class cannot be used to modify access rights.
 *
 * @category   DMS
 * @package    SeedDMS_Core
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal, 2006-2008 Malcolm Cowe,
 *             2010 Uwe Steinmann
 * @version    Release: 5.1.9
 */
class SeedDMS_Core_UserAccess { /* {{{ */

    /**
     * @var SeedDMS_Core_User
     */
	var $_user;

    /**
     * @var
     */
	var $_mode;

    /**
     * SeedDMS_Core_UserAccess constructor.
     * @param $user
     * @param $mode
     */
	function __construct($user, $mode) {
		$this->_user = $user;
		$this->_mode = $mode;
	}

    /**
     * @return int
     */
	function getUserID() { return $this->_user->getID(); }

    /**
     * @return mixed
     */
	function getMode() { return $this->_mode; }

    /**
     * @return bool
     */
	function isAdmin() {
		return ($this->_mode == SeedDMS_Core_User::role_admin);
	}

    /**
     * @return SeedDMS_Core_User
     */
	function getUser() {
		return $this->_user;
	}
} /* }}} */


/**
 * Class to represent a group access right.
 * This class cannot be used to modify access rights.
 *
 * @category   DMS
 * @package    SeedDMS_Core
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal, 2006-2008 Malcolm Cowe, 2010 Uwe Steinmann
 * @version    Release: 5.1.9
 */
class SeedDMS_Core_GroupAccess { /* {{{ */

    /**
     * @var SeedDMS_Core_Group
     */
	var $_group;

    /**
     * @var
     */
	var $_mode;

    /**
     * SeedDMS_Core_GroupAccess constructor.
     * @param $group
     * @param $mode
     */
	function __construct($group, $mode) {
		$this->_group = $group;
		$this->_mode = $mode;
	}

    /**
     * @return int
     */
	function getGroupID() { return $this->_group->getID(); }

    /**
     * @return mixed
     */
	function getMode() { return $this->_mode; }

    /**
     * @return SeedDMS_Core_Group
     */
	function getGroup() {
		return $this->_group;
	}
} /* }}} */

class SeedDMS_Core_Access
{
	protected $_name;
	protected $_mode;
	protected $_privileges;
	protected $_comment;
	protected $_dms;

	function __construct($_name, $_mode, $_privileges, $_comment, $_fix, $_dms)
	{
		$this->_name = $_name;
		$this->_mode = $_mode;
		$this->_privileges = $_privileges;
		$this->_comment = $_comment;
		$this->_fix = $_fix;
		$this->_dms = $_dms;
	}

	function getName()
	{
		return $this->_name;
	}

	function setName($newName)
	{
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE `tblAccessMode` SET `name` = " . $db->qstr($newName) . " WHERE `name` = " . "'" . $this->_name . "'";
		if (!$db->getResult($queryStr)) {
			return false;
		}

		$this->_name = $newName;
		return true;
	}

	function getMode()
	{
		return $this->_mode;
	}

	function setMode($_mode)
	{
		$this->_mode = $_mode;
	}

	function getPrivileges()
	{
		// json_decode: from json String to json Object
		return json_decode($this->_privileges); // return $jsonObj
	}

	function setPrivileges($privileges, $accessName, $fix)
	{
		$dms = $this->_dms;
		if ($fix == 0) {
			$privStr = $dms->getFixPrivileges($accessName);
			$privObj = json_decode(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $privStr), true);

			foreach ($privObj as &$privObjVal) {
				$privObjVal = 0;
			}
			unset($privObjVal);

			$privileges = array_flip($privileges);

			foreach ($privObj as $privObjKey => $privObjVal) {
				foreach ($privileges as $privilegesKey => $privilegesVal) {
					if ($privObjKey == $privilegesKey) {
						$privObj[$privilegesKey] = 1;
					}
				}
			}

			$newPrivStr = json_encode($privObj);
			$db = $this->_dms->getDB();
			$queryStr = "UPDATE `tblAccessMode` SET `privileges` = " . $db->qstr($newPrivStr) . " WHERE `name` = " . "'" . $this->_name . "'";
			if (!$db->getResult($queryStr))
				return false;
			$this->_privileges = $newPrivStr;
			return true;
		} else {
			$privStr = $dms->getEmptyPrivileges();
			$privObj = json_decode(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $privStr), true);
			foreach ($privileges as $privilege) {
				$privObj[$privilege] = 1;
			}
			$newPrivStr = json_encode($privObj);
			$db = $this->_dms->getDB();
			$queryStr = "UPDATE `tblAccessMode` SET `privileges` = " . $db->qstr($newPrivStr) . " WHERE `name` = " . "'" . $this->_name . "'";
			if (!$db->getResult($queryStr))
				return false;
			$this->_privileges = $newPrivStr;
			return true;
		}
	}

	function addPrivilege($newPrivilege)
	{
		$dms = $this->_dms;
		$allAccesses = $dms->getAllAccesses();
		foreach ($allAccesses as $selaccess) {
			$accessName = $selaccess['name'];
			$selPrivileges = $selaccess['privileges'];
			$privilegeObj = json_decode(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $selPrivileges), true);
			foreach ($newPrivilege as $privilege) {
				if ($accessName === 'All access') {
					$privilegeObj[$privilege] = 1;
				} else {
					$privilegeObj[$privilege] = 0;
				}
			}
			$newPrivilegeStr = json_encode($privilegeObj);

			$db = $this->_dms->getDB();
			$query = "UPDATE `tblAccessMode` SET `privileges` = " . $db->qstr($newPrivilegeStr) . " WHERE `name` = " . "'" . $accessName . "'" . "AND `fix` = " . 1;
			if (!$db->getResult($query)) {
				return false;
			}
		}

		return true;
	}

	function dropPrivilege($dropPrivilege)
	{
		$dms = $this->_dms;
		$allAccesses = $dms->getAllAccesses();
		$privArr = [];
		foreach ($allAccesses as $selaccess) {
			$accessName = $selaccess['name'];
			$selPrivileges = $selaccess['privileges'];
			$privArr = json_decode($selPrivileges, true);
			if (array_key_exists($dropPrivilege[0], $privArr)) {
				// Remove privilege from array
				unset($privArr[$dropPrivilege[0]]);
			}
			$db = $this->_dms->getDB();
			$newPrivilegeStr = json_encode($privArr);
			$query = "UPDATE `tblAccessMode` SET `privileges` = " . $db->qstr($newPrivilegeStr) . " WHERE `name` = " . "'" . $accessName . "'" . "AND `fix` = " . 1;
			if (!$db->getResult($query)) {
				return false;
			}
		}
		return true;
	}

	function dropAccess($accessName, $mode)
	{
		$db = $this->_dms->getDB();

		// Update mode
		$query1 = "UPDATE `tblAccessMode` SET `mode` = `mode` - 1 WHERE `mode` > " . $mode;
		if (!$db->getResult($query1)) {
			return false;
		}

		// Delete mode
		$query2 = "DELETE FROM `tblAccessMode` WHERE `name` = " . "'" . $accessName . "'" . "AND `mode` = " . $mode;
		if (!$db->getResult($query2)) {
			return false;
		}

		return true;
	}

	function getAllAccessesInfo()
	{
		$dms = $this->_dms;
		$allAccesses = $dms->getAllAccesses();
		return $allAccesses;
	}

	function getComment()
	{
		return $this->_comment;
	}

	function setComment($newComment)
	{
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE `tblAccessMode` SET `comment` = " . $db->qstr($newComment) . " WHERE `name` = " . "'" . $this->_name . "'";

		if (!$db->getResult($queryStr)) {
			return false;
		}

		$this->_comment = $newComment;
		return true;
	}

	function getFix()
	{
		return $this->_fix;
	}

	function setDMS($dms)
	{
		$this->_dms = $dms;
	}

	// get a specific object details (i.e specific name)
	public static function getInstance($id, $dms, $by = '')
	{
		$db = $dms->getDB();

		switch ($by) {
			case 'name':
				$queryStr = "SELECT * FROM `tblAccessMode` WHERE `name` = " . $db->qstr($id);
				break;
			default:
				$queryStr = "SELECT * FROM `tblAccessMode` WHERE `name` = " . $db->qstr($id);
				break;
		}

		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr === false) {
			return false;
		} else if (count($resArr) !== 1) {
			return null;
		}

		$resArr = $resArr[0];

		$access = new self($resArr["name"], $resArr["mode"], $resArr["privileges"], $resArr["comment"], $resArr["fix"], $dms);
		$access->setDMS($dms);
		return $access;
	}

	public static function getAllInstances($orderby, $dms)
	{
		$db = $dms->getDB();
		switch ($orderby) {
			default:
				$queryStr = "SELECT * FROM `tblAccessMode` ORDER BY `mode`";
		}
		$resArr = $db->getResultArray($queryStr);

		if (is_bool($resArr) && $resArr === false) {
			return false;
		}

		$accesses = array();
		for ($i = 0; $i < count($resArr); $i++) {
			$access = new self($resArr[$i]["name"], $resArr[$i]["mode"], $resArr[$i]["privileges"], $resArr[$i]["comment"], $resArr[$i]["fix"], $dms);
			$access->setDMS($dms);
			$accesses[$i] = $access;
		}

		return $accesses;
	}
}
