<?php
/**
 * Implementation of a folder in the document management system
 *
 * @category   DMS
 * @package    SeedDMS_Core
 * @license    GPL2
 * @author     Markus Westphal, Malcolm Cowe, Matteo Lucarelli,
 *             Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal, 2006-2008 Malcolm Cowe,
 *             2010 Matteo Lucarelli, 2010 Uwe Steinmann
 * @version    Release: 5.1.9
 */

/**
 * Class to represent a folder in the document management system
 *
 * A folder in SeedDMS is equivalent to a directory in a regular file
 * system. It can contain further subfolders and documents. Each folder
 * has a single parent except for the root folder which has no parent.
 *
 * @category   DMS
 * @package    SeedDMS_Core
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal, 2006-2008 Malcolm Cowe,
 *             2010 Matteo Lucarelli, 2010 Uwe Steinmann
 * @version    Release: 5.1.9
 */
class SeedDMS_Core_Folder extends SeedDMS_Core_Object {
	/**
	 * @var string name of folder
	 */
	protected $_name;

	/**
	 * @var integer id of parent folder
	 */
	protected $_parentID;

	/**
	 * @var string comment of document
	 */
	protected $_comment;

	/**
	 * @var integer id of user who is the owner
	 */
	protected $_ownerID;

	/**
	 * @var boolean true if access is inherited, otherwise false
	 */
	protected $_inheritAccess;

	/**
	 * @var integer default access if access rights are not inherited
	 */
	protected $_defaultAccess;

	/**
	 * @var array list of notifications for users and groups
	 */
	protected $_readAccessList;

	/**
	 * @var array list of notifications for users and groups
	 */
	public $_notifyList;

	/**
	 * @var integer position of folder within the parent folder
	 */
	protected $_sequence;

	/**
	 * @var
	 */
	protected $_date;

	/**
	 * @var SeedDMS_Core_Folder
	 */
	protected $_parent;

	/**
	 * @var SeedDMS_Core_User
	 */
	protected $_owner;

	/**
	 * @var SeedDMS_Core_Folder[]
	 */
	protected $_subFolders;

	/**
	 * @var SeedDMS_Core_Document[]
	 */
	protected $_documents;

	/**
	 * @var SeedDMS_Core_UserAccess[]|SeedDMS_Core_GroupAccess[]
	 */
	protected $_accessList;

	/**
	 * SeedDMS_Core_Folder constructor.
	 * @param $id
	 * @param $name
	 * @param $parentID
	 * @param $comment
	 * @param $date
	 * @param $ownerID
	 * @param $inheritAccess
	 * @param $defaultAccess
	 * @param $sequence
	 */
	function __construct($id, $name, $parentID, $comment, $date, $ownerID, $inheritAccess, $defaultAccess, $sequence) { /* {{{ */
		parent::__construct($id);
		$this->_id = $id;
		$this->_name = $name;
		$this->_parentID = $parentID;
		$this->_comment = $comment;
		$this->_date = $date;
		$this->_ownerID = $ownerID;
		$this->_inheritAccess = $inheritAccess;
		$this->_defaultAccess = $defaultAccess;
		$this->_sequence = $sequence;
		$this->_notifyList = array();
	} /* }}} */

	/**
	 * Return an array of database fields which used for searching
	 * a term entered in the database search form
	 *
	 * @param SeedDMS_Core_DMS $dms
	 * @param array $searchin integer list of search scopes (2=name, 3=comment,
	 * 4=attributes)
	 * @return array list of database fields
	 */
	public static function getSearchFields($dms, $searchin) { /* {{{ */
		$db = $dms->getDB();

		$searchFields = array();
		if (in_array(2, $searchin)) {
			$searchFields[] = "`tblFolders`.`name`";
		}
		if (in_array(3, $searchin)) {
			$searchFields[] = "`tblFolders`.`comment`";
		}
		if (in_array(4, $searchin)) {
			$searchFields[] = "`tblFolderAttributes`.`value`";
		}
		if (in_array(5, $searchin)) {
			$searchFields[] = $db->castToText("`tblFolders`.`id`");
		}
		return $searchFields;
	} /* }}} */

	/**
	 * Return a sql statement with all tables used for searching.
	 * This must be a syntactically correct left join of all tables.
	 *
	 * @return string sql expression for left joining tables
	 */
	public static function getSearchTables() { /* {{{ */
		$sql = "`tblFolders` LEFT JOIN `tblFolderAttributes` on `tblFolders`.`id`=`tblFolderAttributes`.`folder`";
		return $sql;
	} /* }}} */

	/**
	 * Return a folder by its id
	 *
	 * @param integer $id id of folder
	 * @param SeedDMS_Core_DMS $dms
	 * @return SeedDMS_Core_Folder|bool instance of SeedDMS_Core_Folder if document exists, null
	 * if document does not exist, false in case of error
	 */
	public static function getInstance($id, $dms) { /* {{{ */
		$db = $dms->getDB();

		$queryStr = "SELECT * FROM `tblFolders` WHERE `id` = " . (int) $id;
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr == false)
			return false;
		else if (count($resArr) != 1)
			return null;

		$resArr = $resArr[0];
		$classname = $dms->getClassname('folder');
		/** @var SeedDMS_Core_Folder $folder */
		$folder = new $classname($resArr["id"], $resArr["name"], $resArr["parent"], $resArr["comment"], $resArr["date"], $resArr["owner"], $resArr["inheritAccess"], $resArr["defaultAccess"], $resArr["sequence"]);
		$folder->setDMS($dms);
		return $folder;
	} /* }}} */

	/**
	 * Get the name of the folder.
	 *
	 * @return string name of folder
	 */
	public function getName() { return $this->_name; }

	/**
	 * Set the name of the folder.
	 *
	 * @param string $newName set a new name of the folder
	 * @return bool
	 */
	public function setName($newName) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE `tblFolders` SET `name` = " . $db->qstr($newName) . " WHERE `id` = ". $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		$this->_name = $newName;

		return true;
	} /* }}} */

	/**
	 * @return string
	 */
	public function getComment() { return $this->_comment; }

	/**
	 * @param $newComment
	 * @return bool
	 */
	public function setComment($newComment) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE `tblFolders` SET `comment` = " . $db->qstr($newComment) . " WHERE `id` = ". $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		$this->_comment = $newComment;
		return true;
	} /* }}} */

	/**
	 * Return creation date of folder
	 *
	 * @return integer unix timestamp of creation date
	 */
	public function getDate() { /* {{{ */
		return $this->_date;
	} /* }}} */

	/**
	 * Set creation date of the document
	 *
	 * @param integer $date timestamp of creation date. If false then set it
	 * to the current timestamp
	 * @return boolean true on success
	 */
	function setDate($date) { /* {{{ */
		$db = $this->_dms->getDB();

		if(!$date)
			$date = time();
		else {
			if(!is_numeric($date))
				return false;
		}

		$queryStr = "UPDATE `tblFolders` SET `date` = " . (int) $date . " WHERE `id` = ". $this->_id;
		if (!$db->getResult($queryStr))
			return false;
		$this->_date = $date;
		return true;
	} /* }}} */

	/**
	 * Returns the parent
	 *
	 * @return bool|SeedDMS_Core_Folder
	 */
	public function getParent() { /* {{{ */
		if ($this->_id == $this->_dms->rootFolderID || empty($this->_parentID)) {
			return false;
		}

		if (!isset($this->_parent)) {
			$this->_parent = $this->_dms->getFolder($this->_parentID);
		}
		return $this->_parent;
	} /* }}} */

	/**
	 * Check if the folder is subfolder
	 *
	 * This function checks if the passed folder is a subfolder of the current
	 * folder.
	 *
	 * @param SeedDMS_Core_Folder $subfolder
	 * @return bool true if passes folder is a subfolder
	 */
	function isSubFolder($subfolder) { /* {{{ */
		$target_path = $subfolder->getPath();
		foreach($target_path as $next_folder) {
			// the target folder contains this instance in the parent path
			if($this->getID() == $next_folder->getID()) return true;
		}
		return false;
	} /* }}} */

	/**
	 * Set a new folder
	 *
	 * This function moves a folder from one parent folder into another parent
	 * folder. It will fail if the root folder is moved.
	 *
	 * @param SeedDMS_Core_Folder $newParent new parent folder
	 * @return boolean true if operation was successful otherwise false
	 */
	public function setParent($newParent) { /* {{{ */
		$db = $this->_dms->getDB();

		if ($this->_id == $this->_dms->rootFolderID || empty($this->_parentID)) {
			return false;
		}

		/* Check if the new parent is the folder to be moved or even
		 * a subfolder of that folder
		 */
		if($this->isSubFolder($newParent)) {
			return false;
		}

		// Update the folderList of the folder
		$pathPrefix="";
		$path = $newParent->getPath();
		foreach ($path as $f) {
			$pathPrefix .= ":".$f->getID();
		}
		if (strlen($pathPrefix)>1) {
			$pathPrefix .= ":";
		}
		$queryStr = "UPDATE `tblFolders` SET `parent` = ".$newParent->getID().", `folderList`='".$pathPrefix."' WHERE `id` = ". $this->_id;
		$res = $db->getResult($queryStr);
		if (!$res)
			return false;

		$this->_parentID = $newParent->getID();
		$this->_parent = $newParent;

		// Must also ensure that any documents in this folder tree have their
		// folderLists updated.
		$pathPrefix="";
		$path = $this->getPath();
		foreach ($path as $f) {
			$pathPrefix .= ":".$f->getID();
		}
		if (strlen($pathPrefix)>1) {
			$pathPrefix .= ":";
		}

		/* Update path in folderList for all documents */
		$queryStr = "SELECT `tblDocuments`.`id`, `tblDocuments`.`folderList` FROM `tblDocuments` WHERE `folderList` LIKE '%:".$this->_id.":%'";
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr == false)
			return false;

		foreach ($resArr as $row) {
			$newPath = preg_replace("/^.*:".$this->_id.":(.*$)/", $pathPrefix."\\1", $row["folderList"]);
			$queryStr="UPDATE `tblDocuments` SET `folderList` = '".$newPath."' WHERE `tblDocuments`.`id` = '".$row["id"]."'";
			/** @noinspection PhpUnusedLocalVariableInspection */
			$res = $db->getResult($queryStr);
		}

		/* Update path in folderList for all documents */
		$queryStr = "SELECT `tblFolders`.`id`, `tblFolders`.`folderList` FROM `tblFolders` WHERE `folderList` LIKE '%:".$this->_id.":%'";
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr == false)
			return false;

		foreach ($resArr as $row) {
			$newPath = preg_replace("/^.*:".$this->_id.":(.*$)/", $pathPrefix."\\1", $row["folderList"]);
			$queryStr="UPDATE `tblFolders` SET `folderList` = '".$newPath."' WHERE `tblFolders`.`id` = '".$row["id"]."'";
			/** @noinspection PhpUnusedLocalVariableInspection */
			$res = $db->getResult($queryStr);
		}

		return true;
	} /* }}} */

	/**
	 * Returns the owner
	 *
	 * @return object owner of the folder
	 */
	public function getOwner() { /* {{{ */
		if (!isset($this->_owner))
			$this->_owner = $this->_dms->getUser($this->_ownerID);
		return $this->_owner;
	} /* }}} */

	/**
	 * Set the owner
	 *
	 * @param SeedDMS_Core_User $newOwner of the folder
	 * @return boolean true if successful otherwise false
	 */
	function setOwner($newOwner) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE `tblFolders` set `owner` = " . $newOwner->getID() . " WHERE `id` = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		$this->_ownerID = $newOwner->getID();
		$this->_owner = $newOwner;
		return true;
	} /* }}} */

	/**
	 * @return bool|int
	 */
	function getDefaultAccess() { /* {{{ */
		if ($this->inheritsAccess()) {
			$res = $this->getParent();
			if (!$res) return false;
			return $this->_parent->getDefaultAccess();
		}

		return $this->_defaultAccess;
	} /* }}} */

	/**
	 * Set default access mode
	 *
	 * This method sets the default access mode and also removes all notifiers which
	 * will not have read access anymore.
	 *
	 * @param integer $mode access mode
	 * @param boolean $noclean set to true if notifier list shall not be clean up
	 * @return bool
	 */
	function setDefaultAccess($mode, $noclean=false) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE `tblFolders` set `defaultAccess` = " . (int) $mode . " WHERE `id` = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		$this->_defaultAccess = $mode;

		if(!$noclean)
			self::cleanNotifyList();

		return true;
	} /* }}} */

	function inheritsAccess() { return $this->_inheritAccess; }

	/**
	 * Set inherited access mode
	 * Setting inherited access mode will set or unset the internal flag which
	 * controls if the access mode is inherited from the parent folder or not.
	 * It will not modify the
	 * access control list for the current object. It will remove all
	 * notifications of users which do not even have read access anymore
	 * after setting or unsetting inherited access.
	 *
	 * @param boolean $inheritAccess set to true for setting and false for
	 *        unsetting inherited access mode
	 * @param boolean $noclean set to true if notifier list shall not be clean up
	 * @return boolean true if operation was successful otherwise false
	 */
	function setInheritAccess($inheritAccess, $noclean=false) { /* {{{ */
		$db = $this->_dms->getDB();

		$inheritAccess = ($inheritAccess) ? "1" : "0";

		$queryStr = "UPDATE `tblFolders` SET `inheritAccess` = " . (int) $inheritAccess . " WHERE `id` = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		$this->_inheritAccess = $inheritAccess;

		if(!$noclean)
			self::cleanNotifyList();

		return true;
	} /* }}} */

	function getSequence() { return $this->_sequence; }

	function setSequence($seq) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE `tblFolders` SET `sequence` = " . $seq . " WHERE `id` = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		$this->_sequence = $seq;
		return true;
	} /* }}} */

	/**
	 * Check if folder has subfolders
	 * This function just checks if a folder has subfolders disregarding
	 * any access rights.
	 *
	 * @return int number of subfolders or false in case of an error
	 */
	function hasSubFolders() { /* {{{ */
		$db = $this->_dms->getDB();
		if (isset($this->_subFolders)) {
			/** @noinspection PhpUndefinedFieldInspection */
			return count($this->subFolders); /** @todo not $this->_subFolders? */
		}
		$queryStr = "SELECT count(*) as c FROM `tblFolders` WHERE `parent` = " . $this->_id;
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && !$resArr)
			return false;

		return $resArr[0]['c'];
	} /* }}} */

	/**
	 * Returns a list of subfolders
	 * This function does not check for access rights. Use
	 * {@link SeedDMS_Core_DMS::filterAccess} for checking each folder against
	 * the currently logged in user and the access rights.
	 *
	 * @param string $orderby if set to 'n' the list is ordered by name, otherwise
	 *        it will be ordered by sequence
	 * @param string $dir direction of sorting (asc or desc)
	 * @param integer $limit limit number of subfolders
	 * @param integer $offset offset in retrieved list of subfolders
	 * @return SeedDMS_Core_Folder[]|bool list of folder objects or false in case of an error
	 */
	function getSubFolders($orderby="", $dir="asc", $limit=0, $offset=0) { /* {{{ */
		$db = $this->_dms->getDB();

		if (!isset($this->_subFolders)) {
			$queryStr = "SELECT * FROM `tblFolders` WHERE `parent` = " . $this->_id;

			if ($orderby=="n") $queryStr .= " ORDER BY `name`";
			elseif ($orderby=="s") $queryStr .= " ORDER BY `sequence`";
			elseif ($orderby=="d") $queryStr .= " ORDER BY `date`";
			if($dir == 'desc')
				$queryStr .= " DESC";
			if(is_int($limit) && $limit > 0) {
				$queryStr .= " LIMIT ".$limit;
				if(is_int($offset) && $offset > 0)
					$queryStr .= " OFFSET ".$offset;
			}

			$resArr = $db->getResultArray($queryStr);
			if (is_bool($resArr) && $resArr == false)
				return false;

			$this->_subFolders = array();
			for ($i = 0; $i < count($resArr); $i++)
				$this->_subFolders[$i] = $this->_dms->getFolder($resArr[$i]["id"]);
		}

		return $this->_subFolders;
	} /* }}} */

	/**
	 * Add a new subfolder
	 *
	 * @param string $name name of folder
	 * @param string $comment comment of folder
	 * @param object $owner owner of folder
	 * @param integer $sequence position of folder in list of sub folders.
	 * @param array $attributes list of document attributes. The element key
	 *        must be the id of the attribute definition.
	 * @return bool|SeedDMS_Core_Folder
	 *         an error.
	 */
	function addSubFolder($name, $comment, $owner, $sequence, $attributes=array()) { /* {{{ */
		$db = $this->_dms->getDB();

		// Set the folderList of the folder
		$pathPrefix="";
		$path = $this->getPath();
		foreach ($path as $f) {
			$pathPrefix .= ":".$f->getID();
		}
		if (strlen($pathPrefix)>1) {
			$pathPrefix .= ":";
		}

		$db->startTransaction();

		//inheritAccess = true, defaultAccess = M_READ
		$queryStr = "INSERT INTO `tblFolders` (`name`, `parent`, `folderList`, `comment`, `date`, `owner`, `inheritAccess`, `defaultAccess`, `sequence`) ".
					"VALUES (".$db->qstr($name).", ".$this->_id.", ".$db->qstr($pathPrefix).", ".$db->qstr($comment).", ".$db->getCurrentTimestamp().", ".$owner->getID().", 1, ".M_READ.", ". $sequence.")";
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}
		$newFolder = $this->_dms->getFolder($db->getInsertID('tblFolders'));
		unset($this->_subFolders);

		if($attributes) {
			foreach($attributes as $attrdefid=>$attribute) {
				if($attribute)
					if(!$newFolder->setAttributeValue($this->_dms->getAttributeDefinition($attrdefid), $attribute)) {
						$db->rollbackTransaction();
						return false;
					}
			}
		}

		$db->commitTransaction();

		/* Check if 'onPostAddSubFolder' callback is set */
		if(isset($this->_dms->callbacks['onPostAddSubFolder'])) {
			foreach($this->_dms->callbacks['onPostAddSubFolder'] as $callback) {
					/** @noinspection PhpStatementHasEmptyBodyInspection */
					if(!call_user_func($callback[0], $callback[1], $newFolder)) {
				}
			}
		}

		return $newFolder;
	} /* }}} */

	/**
	 * Returns an array of all parents, grand parent, etc. up to root folder.
	 * The folder itself is the last element of the array.
	 *
	 * @return array|bool
	 */
	function getPath() { /* {{{ */
		if (!isset($this->_parentID) || ($this->_parentID == "") || ($this->_parentID == 0) || ($this->_id == $this->_dms->rootFolderID)) {
			return array($this);
		}
		else {
			$res = $this->getParent();
			if (!$res) return false;

			$path = $this->_parent->getPath();
			if (!$path) return false;

			array_push($path, $this);
			return $path;
		}
	} /* }}} */

	/**
	 * Returns a file system path
	 *
	 * This path contains spaces around the slashes for better readability.
	 * Run str_replace(' / ', '/', $path) on it to get a valid unix
	 * file system path.
	 *
	 * @return string path separated with ' / '
	 */
	function getFolderPathPlain() { /* {{{ */
		$path="";
		$folderPath = $this->getPath();
		for ($i = 0; $i  < count($folderPath); $i++) {
			$path .= $folderPath[$i]->getName();
			if ($i +1 < count($folderPath))
				$path .= " / ";
		}
		return $path;
	} /* }}} */

	/**
	 * Check, if this folder is a subfolder of a given folder
	 *
	 * @param object $folder parent folder
	 * @return boolean true if folder is a subfolder
	 */
	function isDescendant($folder) { /* {{{ */
		if ($this->_parentID == $folder->getID())
			return true;
		elseif (isset($this->_parentID)) {
			$res = $this->getParent();
			if (!$res) return false;

			return $this->_parent->isDescendant($folder);
		} else
			return false;
	} /* }}} */

	/**
	 * Check if folder has documents
	 * This function just checks if a folder has documents diregarding
	 * any access rights.
	 *
	 * @return int number of documents or false in case of an error
	 */
	function hasDocuments() { /* {{{ */
		$db = $this->_dms->getDB();
		if (isset($this->_documents)) {
			/** @noinspection PhpUndefinedFieldInspection */
			return count($this->documents); /** @todo not $this->_documents? */
		}
		$queryStr = "SELECT count(*) as c FROM `tblDocuments` WHERE `folder` = " . $this->_id;
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && !$resArr)
			return false;

		return $resArr[0]['c'];
	} /* }}} */

	/**
	 * Check if folder has document with given name
	 *
	 * @param string $name
	 * @return bool true if document exists, false if not or in case
	 * of an error
	 */
	function hasDocumentByName($name) { /* {{{ */
		$db = $this->_dms->getDB();
		if (isset($this->_documents)) {
			/** @noinspection PhpUndefinedFieldInspection */ /** @todo not $this->_documents? */
			return count($this->documents);
		}
		$queryStr = "SELECT count(*) as c FROM `tblDocuments` WHERE `folder` = " . $this->_id . " AND `name` = ".$db->qstr($name);
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && !$resArr)
			return false;

		return ($resArr[0]['c'] > 0);
	} /* }}} */

	/**
	 * Get all documents of the folder
	 * This function does not check for access rights. Use
	 * {@link SeedDMS_Core_DMS::filterAccess} for checking each document against
	 * the currently logged in user and the access rights.
	 *
	 * @param string $orderby if set to 'n' the list is ordered by name, otherwise
	 *        it will be ordered by sequence
	 * @param string $dir direction of sorting (asc or desc)
	 * @param integer $limit limit number of documents
	 * @param integer $offset offset in retrieved list of documents
	 * @return SeedDMS_Core_Document[]|bool list of documents or false in case of an error
	 */
	function getDocuments($orderby="", $dir="asc", $limit=0, $offset=0) { /* {{{ */
		$db = $this->_dms->getDB();

		if (!isset($this->_documents)) {
			$queryStr = "SELECT * FROM `tblDocuments` WHERE `folder` = " . $this->_id;
			if ($orderby=="n") $queryStr .= " ORDER BY `name`";
			elseif($orderby=="s") $queryStr .= " ORDER BY `sequence`";
			elseif($orderby=="d") $queryStr .= " ORDER BY `date`";
			if($dir == 'desc')
				$queryStr .= " DESC";
			if(is_int($limit) && $limit > 0) {
				$queryStr .= " LIMIT ".$limit;
				if(is_int($offset) && $offset > 0)
					$queryStr .= " OFFSET ".$offset;
			}

			$resArr = $db->getResultArray($queryStr);
			if (is_bool($resArr) && !$resArr)
				return false;

			$this->_documents = array();
			foreach ($resArr as $row) {
//				array_push($this->_documents, new SeedDMS_Core_Document($row["id"], $row["name"], $row["comment"], $row["date"], $row["expires"], $row["owner"], $row["folder"], $row["inheritAccess"], $row["defaultAccess"], isset($row["lockUser"])?$row["lockUser"]:NULL, $row["keywords"], $row["sequence"]));
				array_push($this->_documents, $this->_dms->getDocument($row["id"]));
			}
		}
		return $this->_documents;
	} /* }}} */

	/**
	 * Count all documents and subfolders of the folder
	 *
	 * This function also counts documents and folders of subfolders, so
	 * basically it works like recursively counting children.
	 *
	 * This function checks for access rights up the given limit. If more
	 * documents or folders are found, the returned value will be the number
	 * of objects available and the precise flag in the return array will be
	 * set to false. This number should not be revelead to the
	 * user, because it allows to gain information about the existens of
	 * objects without access right.
	 * Setting the parameter $limit to 0 will turn off access right checking
	 * which is reasonable if the $user is an administrator.
	 *
	 * @param SeedDMS_Core_User $user
	 * @param integer $limit maximum number of folders and documents that will
	 *        be precisly counted by taken the access rights into account
	 * @return array|bool with four elements 'document_count', 'folder_count'
	 *        'document_precise', 'folder_precise' holding
	 * the counted number and a flag if the number is precise.
	 * @internal param string $orderby if set to 'n' the list is ordered by name, otherwise
	 *        it will be ordered by sequence
	 */
	function countChildren($user, $limit=10000) { /* {{{ */
		$db = $this->_dms->getDB();

		$pathPrefix="";
		$path = $this->getPath();
		foreach ($path as $f) {
			$pathPrefix .= ":".$f->getID();
		}
		if (strlen($pathPrefix)>1) {
			$pathPrefix .= ":";
		}

		$queryStr = "SELECT id FROM `tblFolders` WHERE `folderList` like '".$pathPrefix. "%'";
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && !$resArr)
			return false;

		$result = array();

		$folders = array();
		$folderids = array($this->_id);
		$cfolders = count($resArr);
		if($cfolders < $limit) {
			foreach ($resArr as $row) {
				$folder = $this->_dms->getFolder($row["id"]);
				if ($folder->getAccessMode($user) >= M_READ) {
					array_push($folders, $folder);
					array_push($folderids, $row['id']);
				}
			}
			$result['folder_count'] = count($folders);
			$result['folder_precise'] = true;
		} else {
			foreach ($resArr as $row) {
				array_push($folderids, $row['id']);
			}
			$result['folder_count'] = $cfolders;
			$result['folder_precise'] = false;
		}

		$documents = array();
		if($folderids) {
			$queryStr = "SELECT id FROM `tblDocuments` WHERE `folder` in (".implode(',', $folderids). ")";
			$resArr = $db->getResultArray($queryStr);
			if (is_bool($resArr) && !$resArr)
				return false;

			$cdocs = count($resArr);
			if($cdocs < $limit) {
				foreach ($resArr as $row) {
					$document = $this->_dms->getDocument($row["id"]);
					if ($document->getAccessMode($user) >= M_READ)
						array_push($documents, $document);
				}
				$result['document_count'] = count($documents);
				$result['document_precise'] = true;
			} else {
				$result['document_count'] = $cdocs;
				$result['document_precise'] = false;
			}
		}

		return $result;
	} /* }}} */

	// $comment will be used for both document and version leaving empty the version_comment 
	/**
	 * Add a new document to the folder
	 * This function will add a new document and its content from a given file.
	 * It does not check for access rights on the folder. The new documents
	 * default access right is read only and the access right is inherited.
	 *
	 * @param string $name name of new document
	 * @param string $comment comment of new document
	 * @param integer $expires expiration date as a unix timestamp or 0 for no
	 *        expiration date
	 * @param object $owner owner of the new document
	 * @param SeedDMS_Core_User $keywords keywords of new document
	 * @param SeedDMS_Core_DocumentCategory[] $categories list of category objects
	 * @param string $tmpFile the path of the file containing the content
	 * @param string $orgFileName the original file name
	 * @param string $fileType usually the extension of the filename
	 * @param string $mimeType mime type of the content
	 * @param float $sequence position of new document within the folder
	 * @param array $reviewers list of users who must review this document
	 * @param array $approvers list of users who must approve this document
	 * @param int|string $reqversion version number of the content
	 * @param string $version_comment comment of the content. If left empty
	 *        the $comment will be used.
	 * @param array $attributes list of document attributes. The element key
	 *        must be the id of the attribute definition.
	 * @param array $version_attributes list of document version attributes.
	 *        The element key must be the id of the attribute definition.
	 * @param SeedDMS_Core_Workflow $workflow
	 * @return array|bool false in case of error, otherwise an array
	 *        containing two elements. The first one is the new document, the
	 * second one is the result set returned when inserting the content.
	 */
	function addDocument($name, $type, $comment, $expires, $owner, $keywords, $categories, $tmpFile, $orgFileName, $fileType, $mimeType, $flipDir, $orientation, $sequence, $reviewers=array(), $approvers=array(),$reqversion=0,$version_comment="", $attributes=array(), $version_attributes=array(), $workflow=null) { /* {{{ */
		$db = $this->_dms->getDB();

		$expires = (!$expires) ? 0 : $expires;

		// Must also ensure that the document has a valid folderList.
		$pathPrefix="";
		$path = $this->getPath();
		foreach ($path as $f) {
			$pathPrefix .= ":".$f->getID();
		}
		if (strlen($pathPrefix)>1) {
			$pathPrefix .= ":";
		}

		$db->startTransaction();

		$queryStr = "INSERT INTO `tblDocuments` (`name`, `type`, `comment`, `date`, `expires`, `owner`, `folder`, `folderList`, `inheritAccess`, `defaultAccess`, `locked`, `keywords`, `sequence`) VALUES ".
					"(".$db->qstr($name).",".(int)$type.", ".$db->qstr($comment).", ".$db->getCurrentTimestamp().", ".(int) $expires.", ".$owner->getID().", ".$this->_id.",".$db->qstr($pathPrefix).", 1, ".M_READ.", -1, ".$db->qstr($keywords).", " . $sequence . ")";
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		$document = $this->_dms->getDocument($db->getInsertID('tblDocuments'));

//		if ($version_comment!="")
			$res = $document->addContent($version_comment, $owner, $tmpFile, $orgFileName, $fileType, $mimeType, $flipDir, $orientation, $reviewers, $approvers, $reqversion, $version_attributes, $workflow);
//		else $res = $document->addContent($comment, $owner, $tmpFile, $orgFileName, $fileType, $mimeType, $reviewers, $approvers,$reqversion, $version_attributes, $workflow);

		if (is_bool($res) && !$res) {
			$db->rollbackTransaction();
			return false;
		}

		if($categories) {
			$document->setCategories($categories);
		}

		if($attributes) {
			foreach($attributes as $attrdefid=>$attribute) {
				/* $attribute can be a string or an array */
				if($attribute)
					if(!$document->setAttributeValue($this->_dms->getAttributeDefinition($attrdefid), $attribute)) {
						$document->remove();
						$db->rollbackTransaction();
						return false;
					}
			}
		}

		$db->commitTransaction();

		/* Check if 'onPostAddDocument' callback is set */
		if(isset($this->_dms->callbacks['onPostAddDocument'])) {
			foreach($this->_dms->callbacks['onPostAddDocument'] as $callback) {
					/** @noinspection PhpStatementHasEmptyBodyInspection */
					if(!call_user_func($callback[0], $callback[1], $document)) {
				}
			}
		}

		return array($document, $res);
	} /* }}} */

	/**
	 * Remove a single folder
	 *
	 * Removes just a single folder, but not its subfolders or documents
	 * This function will fail if the folder has subfolders or documents
	 * because of referencial integrity errors.
	 *
	 * @return boolean true on success, false in case of an error
	 */
	protected function removeFromDatabase() { /* {{{ */
		$db = $this->_dms->getDB();

		/* Check if 'onPreRemoveFolder' callback is set */
		if(isset($this->_dms->callbacks['onPreRemoveFromDatabaseFolder'])) {
			foreach($this->_dms->callbacks['onPreRemoveFromDatabaseFolder'] as $callback) {
				$ret = call_user_func($callback[0], $callback[1], $this);
				if(is_bool($ret))
					return $ret;
			}
		}

		$db->startTransaction();
		// unset homefolder as it will no longer exist
		$queryStr = "UPDATE `tblUsers` SET `homefolder`=NULL WHERE `homefolder` =  " . $this->_id;
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		// Remove database entries
		$queryStr = "DELETE FROM `tblFolders` WHERE `id` =  " . $this->_id;
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}
		$queryStr = "DELETE FROM `tblFolderAttributes` WHERE `folder` =  " . $this->_id;
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}
		$queryStr = "DELETE FROM `tblACLs` WHERE `target` = ". $this->_id. " AND `targetType` = " . T_FOLDER;
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		$queryStr = "DELETE FROM `tblNotify` WHERE `target` = ". $this->_id. " AND `targetType` = " . T_FOLDER;
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}
		$db->commitTransaction();

		/* Check if 'onPostRemoveFolder' callback is set */
		if(isset($this->_dms->callbacks['onPostRemoveFromDatabaseFolder'])) {
			foreach($this->_dms->callbacks['onPostRemoveFromDatabaseFolder'] as $callback) {
				/** @noinspection PhpStatementHasEmptyBodyInspection */
				if(!call_user_func($callback[0], $callback[1], $this->_id)) {
				}
			}
		}

		return true;
	} /* }}} */

	/**
	 * Remove recursively a folder
	 *
	 * Removes a folder, all its subfolders and documents
	 *
	 * @return boolean true on success, false in case of an error
	 */
	function remove() { /* {{{ */
		/** @noinspection PhpUnusedLocalVariableInspection */
		$db = $this->_dms->getDB();

		// Do not delete the root folder.
		if ($this->_id == $this->_dms->rootFolderID || !isset($this->_parentID) || ($this->_parentID == null) || ($this->_parentID == "") || ($this->_parentID == 0)) {
			return false;
		}

		/* Check if 'onPreRemoveFolder' callback is set */
		if(isset($this->_dms->callbacks['onPreRemoveFolder'])) {
			foreach($this->_dms->callbacks['onPreRemoveFolder'] as $callback) {
				$ret = call_user_func($callback[0], $callback[1], $this);
				if(is_bool($ret))
					return $ret;
			}
		}

		//Entfernen der Unterordner und Dateien
		$res = $this->getSubFolders();
		if (is_bool($res) && !$res) return false;
		$res = $this->getDocuments();
		if (is_bool($res) && !$res) return false;

		foreach ($this->_subFolders as $subFolder) {
			$res = $subFolder->remove();
			if (!$res) {
				return false;
			}
		}

		foreach ($this->_documents as $document) {
			$res = $document->remove();
			if (!$res) {
				return false;
			}
		}

		$ret = $this->removeFromDatabase();
		if(!$ret)
			return $ret;

		/* Check if 'onPostRemoveFolder' callback is set */
		if(isset($this->_dms->callbacks['onPostRemoveFolder'])) {
			foreach($this->_dms->callbacks['onPostRemoveFolder'] as $callback) {
				call_user_func($callback[0], $callback[1], $this);
			}
		}

		return $ret;
	} /* }}} */

	/**
	 * Returns a list of access privileges
	 *
	 * If the folder inherits the access privileges from the parent folder
	 * those will be returned.
	 * $mode and $op can be set to restrict the list of returned access
	 * privileges. If $mode is set to M_ANY no restriction will apply
	 * regardless of the value of $op. The returned array contains a list
	 * of {@link SeedDMS_Core_UserAccess} and
	 * {@link SeedDMS_Core_GroupAccess} objects. Even if the document
	 * has no access list the returned array contains the two elements
	 * 'users' and 'groups' which are than empty. The methode returns false
	 * if the function fails.
	 *
	 * @param int $mode access mode (defaults to M_ANY)
	 * @param int|string $op operation (defaults to O_EQ)
	 * @return bool|SeedDMS_Core_GroupAccess|SeedDMS_Core_UserAccess
	 */
	function getAccessList($mode = M_ANY, $op = O_EQ, $findParent=true) { /* {{{ */
		$db = $this->_dms->getDB(); //$this=folder object

		if ($this->inheritsAccess() && $findParent) {
			$res = $this->getParent();
			if (!$res) return false;
			return $this->_parent->getAccessList($mode, $op);
		}

		if (!isset($this->_accessList[$mode])) {
			if ($op!=O_GTEQ && $op!=O_LTEQ && $op!=O_EQ) {
				return false;
			}
			$modeStr = "";
			if ($mode!=M_ANY) {
				$modeStr = " AND mode".$op.(int)$mode;
			}
			$queryStr = "SELECT * FROM `tblACLs` WHERE `targetType` = ".T_FOLDER.
				" AND `target` = " . $this->_id .	$modeStr . " ORDER BY `targetType`";
			$resArr = $db->getResultArray($queryStr);
			if (is_bool($resArr) && !$resArr)
				return false;

			$this->_accessList[$mode] = array("groups" => array(), "users" => array());
			foreach ($resArr as $row) {
				if ($row["userID"] != -1)
					array_push($this->_accessList[$mode]["users"], new SeedDMS_Core_UserAccess($this->_dms->getUser($row["userID"]), $row["mode"]));
				else //if ($row["groupID"] != -1)
					array_push($this->_accessList[$mode]["groups"], new SeedDMS_Core_GroupAccess($this->_dms->getGroup($row["groupID"]), $row["mode"]));
			}
		}

		return $this->_accessList[$mode];
	} /* }}} */

	/**
	 * Delete all entries for this folder from the access control list
	 *
	 * @param boolean $noclean set to true if notifier list shall not be clean up
	 * @return boolean true if operation was successful otherwise false
	 */
	function clearAccessList($noclean=false) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "DELETE FROM `tblACLs` WHERE `targetType` = " . T_FOLDER . " AND `target` = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		unset($this->_accessList);

		if(!$noclean)
			self::cleanNotifyList();

		return true;
	} /* }}} */

	/**
	 * Add access right to folder
	 * This function may change in the future. Instead of passing the a flag
	 * and a user/group id a user or group object will be expected.
	 *
	 * @param integer $mode access mode
	 * @param integer $userOrGroupID id of user or group
	 * @param integer $isUser set to 1 if $userOrGroupID is the id of a
	 *        user
	 * @return bool
	 */
//	function addAccess($mode, $userOrGroupID, $isUser) { /* {{{ */
//		$db = $this->_dms->getDB();
//
//		$userOrGroup = ($isUser) ? "`userID`" : "`groupID`";
//
//		$queryStr = "INSERT INTO `tblACLs` (`target`, `targetType`, ".$userOrGroup.", `mode`) VALUES 
//					(".$this->_id.", ".T_FOLDER.", " . (int) $userOrGroupID . ", " .(int) $mode. ")";
//		if (!$db->getResult($queryStr))
//			return false;
//
//		unset($this->_accessList);
//
//		// Update the notify list, if necessary.
//		if ($mode == M_NONE) {
//			$this->removeNotify($userOrGroupID, $isUser);
//		}
//
//		return true;
//	} /* }}} */
        
        // Glenn edited
	function addAccess($mode, $userOrGroupID, $isUser)
	{ /* {{{ */
		$db = $this->_dms->getDB();

		$userOrGroup = ($isUser) ? "`userID`" : "`groupID`";

		// Handle user's folder access
		if ($isUser) {
			$queryStr1 = "SELECT `followGroup` FROM `tblACLs` WHERE `target` = " . $this->_id . " AND `targetType` = " . T_FOLDER . " AND `userID` = " . (int) $userOrGroupID . " AND `groupID` = -1";
			$result1 = $db->getResultArray($queryStr1);
			if ($result1) {
				$followGroup = $result1[0]['followGroup'];
				// If user's folder access follow group
				if ($followGroup == 1) {
					// Save the selected folder access in base folder access
					$queryStr2 = "SELECT * FROM `tblFolderAccessBase` WHERE `userID` = " . $userOrGroupID . " AND `folderID` = " . $this->_id;
					$result2 = $db->getResultArray($queryStr2);
					if ($result2) {
						$queryStr3 = "UPDATE `tblFolderAccessBase` SET `baseMode` = " . $mode . " WHERE `userID` = " . $userOrGroupID . " AND `folderID` = " . $this->_id;
						$result3 = $db->getResult($queryStr3);
					} else {
						$queryStr4 = "INSERT INTO `tblFolderAccessBase` (`userID`, `folderID`, `baseMode`) VALUES (" . $userOrGroupID . ", " . $this->_id . ", " . $mode . ")";
						$result4 = $db->getResult($queryStr4);
					}
				} else {
					// If user's folder access does not follow group
					$queryStr5 = "UPDATE `tblACLs` SET `mode` = " . $mode . " WHERE `target` = " . $this->_id . " AND `targetType` = " . T_FOLDER . " AND `userID` = " . $userOrGroupID . " AND `groupID` = -1";
					$result5 = $db->getResult($queryStr5);

					// Save the selected folder access in base folder access
					$queryStr6 = "SELECT * FROM `tblFolderAccessBase` WHERE `userID` = " . $userOrGroupID . " AND `folderID` = " . $this->_id;
					$result6 = $db->getResultArray($queryStr6);
					if ($result6) {
						$queryStr7 = "UPDATE `tblFolderAccessBase` SET `baseMode` = " . $mode . " WHERE `userID` = " . $userOrGroupID . " AND `folderID` = " . $this->_id;
						$result7 = $db->getResult($queryStr7);
					} else {
						$queryStr8 = "INSERT INTO `tblFolderAccessBase` (`userID`, `folderID`, `baseMode`) VALUES (" . $userOrGroupID . ", " . $this->_id . ", " . $mode . ")";
						$result8 = $db->getResult($queryStr8);
					}
				}
			} else {
				// Get the selected folder access directly
				$queryStr9 = "INSERT INTO `tblACLs` (`target`, `targetType`, `userID`, `groupID`, `mode`, `followGroup`) VALUES (" . $this->_id . ", " . T_FOLDER . ", " . $userOrGroupID . ", " . -1 . ", " . $mode . ", " . 0 . ")";
				$result9 = $db->getResult($queryStr9);

				// Save the selected folder access in base folder access
				$queryStr10 = "SELECT * FROM `tblFolderAccessBase` WHERE `userID` = " . $userOrGroupID . " AND `folderID` = " . $this->_id;
				$result10 = $db->getResultArray($queryStr10);
				if ($result10) {
					$queryStr11 = "UPDATE `tblFolderAccessBase` SET `baseMode` = " . $mode . " WHERE `userID` = " . $userOrGroupID . " AND `folderID` = " . $this->_id;
					$result11 = $db->getResult($queryStr11);
				} else {
					$queryStr12 = "INSERT INTO `tblFolderAccessBase` (`userID`, `folderID`, `baseMode`) VALUES (" . $userOrGroupID . ", " . $this->_id . ", " . $mode . ")";
					$result12 = $db->getResult($queryStr12);
				}
			}
		} else {
			// Hanlde group's folder access
			$groupMemberCollection = []; // group members collection

			// Select all group members
			$queryStr13 = "SELECT `userID` FROM `tblGroupMembers` WHERE `groupID` = " . $userOrGroupID . " AND `followGroup` = 1";
			$result13 = $db->getResultArray($queryStr13);
			foreach ($result13 as $key => $value) {
				array_push($groupMemberCollection, $value['userID']);
			}

			// Determine whether the folder access mode currently owned by the user is larger than the folder access mode assigned to the group and update it
			foreach ($groupMemberCollection as $key => $value) {
				$newMode = $mode;
				// Select all groups which this user follow it
				$queryStr14 = "SELECT `groupID` FROM `tblGroupMembers` WHERE `followGroup` = 1 AND `userID` = " . $value . " AND `groupID` != " . $userOrGroupID;
				$result14 = $db->getResultArray($queryStr14);
				if ($result14) {
					foreach ($result14 as $key => $val) {
						$groupid = $val['groupID'];
						$queryStr15 = "SELECT `mode` FROM `tblACLs` WHERE `target` = " . $this->_id . " AND `targetType` = " . T_FOLDER . " AND `userID` = -1 AND `groupID` = " . $groupid . " AND `followGroup` = 0";
						$result15 = $db->getResultArray($queryStr15);
						if ($result15) {
							foreach ($result15 as $key => $val) {
								if ($val['mode'] > $newMode) {
									// Replace new mode with higher priority mode
									$newMode = $val['mode'];
								}
							}
						}
					}
					// Update group member's folder access
					$queryStr16 = "SELECT `mode` FROM `tblACLs` WHERE `target` = " . $this->_id . " AND `targetType` = " . T_FOLDER . " AND `userID` = " . $value . " AND `groupID` = -1";
					$result16 = $db->getResultArray($queryStr16);
					if ($result16) {
						$queryStr17 = "UPDATE `tblACLs` SET `followGroup` = 1, `mode` = " . $newMode . " WHERE `target` = " . $this->_id . " AND `targetType` = " . T_FOLDER . " AND `userID` = " . $value . " AND `groupID` = -1";
						$result17 = $db->getResult($queryStr17);
					} else {
						$queryStr18 = "INSERT INTO `tblACLs` (`target`, `targetType`, `userID`, `groupID`, `mode`, `followGroup`) VALUES (" . $this->_id . ", " . T_FOLDER . ", " . $value . ", " . -1 . ", " . $newMode . ", " . 1 . ")";
						$result18 = $db->getResult($queryStr18);
					}
				} else {
					// Update group member's folder access
					$queryStr19 = "SELECT `mode` FROM `tblACLs` WHERE `target` = " . $this->_id . " AND `targetType` = " . T_FOLDER . " AND `userID` = " . $value . " AND `groupID` = -1";
					$result19 = $db->getResultArray($queryStr19);
					if ($result19) {
						$queryStr20 = "UPDATE `tblACLs` SET `followGroup` = 1, `mode` = " . $newMode . " WHERE `target` = " . $this->_id . " AND `targetType` = " . T_FOLDER . " AND `userID` = " . $value . " AND `groupID` = -1";
						$result20 = $db->getResult($queryStr20);
					} else {
						$queryStr21 = "INSERT INTO `tblACLs` (`target`, `targetType`, `userID`, `groupID`, `mode`, `followGroup`) VALUES (" . $this->_id . ", " . T_FOLDER . ", " . $value . ", " . -1 . ", " . $newMode . ", " . 1 . ")";
						$result21 = $db->getResult($queryStr21);
					}
				}
			}

			// Update group folder access
			$queryStr22 = "SELECT `mode` FROM `tblACLs` WHERE `target` = " . $this->_id . " AND `targetType` = " . T_FOLDER . " AND `userID` = -1 AND `groupID` = " . $userOrGroupID . " AND `followGroup` = 0";
			$result22 = $db->getResultArray($queryStr22);
			if ($result22) {
				$queryStr23 = "UPDATE `tblACLs` SET `mode` = " . $mode . " WHERE `target` = " . $this->_id . " AND `targetType` = " . T_FOLDER . " AND `userID` = -1 AND `groupID` = " . $userOrGroupID . " AND `followGroup` = 0";
				$result23 = $db->getResult($queryStr23);
			} else {
				$queryStr24 = "INSERT INTO `tblACLs` (`target`, `targetType`, `userID`, `groupID`, `mode`, `followGroup`) VALUES (" . $this->_id . ", " . T_FOLDER . ", " . -1 . ", " . $userOrGroupID . ", " . $mode . ", " . 0 . ")";
				$result24 = $db->getResult($queryStr24);
			}
		}

		// Update the notify list, if necessary.
		// Edited on 8 March, 2022
		// '1' means that no access / permission which is defined in tblAccessMode table.
		// For the system's default setting, it uses M_NONE stands for the no access mode.
		if ($mode == 1) {
			$this->removeNotify($userOrGroupID, $isUser);
		}

		return true;
	} /* }}} */
        
	/**
	 * Change access right of folder
	 * This function may change in the future. Instead of passing the a flag
	 * and a user/group id a user or group object will be expected.
	 *
	 * @param integer $newMode access mode
	 * @param integer $userOrGroupID id of user or group
	 * @param integer $isUser set to 1 if $userOrGroupID is the id of a
	 *        user
	 * @return bool
	 */
	function changeAccess($newMode, $userOrGroupID, $isUser) { /* {{{ */
		$db = $this->_dms->getDB();

		$userOrGroup = ($isUser) ? "`userID`" : "`groupID`";

		$queryStr = "UPDATE `tblACLs` SET `mode` = " . (int) $newMode . " WHERE `targetType` = ".T_FOLDER." AND `target` = " . $this->_id . " AND " . $userOrGroup . " = " . (int) $userOrGroupID;
		if (!$db->getResult($queryStr))
			return false;

		unset($this->_accessList);

		// Update the notify list, if necessary.
		if ($newMode == M_NONE) {
			$this->removeNotify($userOrGroupID, $isUser);
		}

		return true;
	} /* }}} */

	/**
	 * @param $userOrGroupID
	 * @param $isUser
	 * @return bool
	 */
//	function removeAccess($userOrGroupID, $isUser) { /* {{{ */
//		$db = $this->_dms->getDB();
//
//		$userOrGroup = ($isUser) ? "`userID`" : "`groupID`";
//
//		$queryStr = "DELETE FROM `tblACLs` WHERE `targetType` = ".T_FOLDER." AND `target` = ".$this->_id." AND ".$userOrGroup." = " . (int) $userOrGroupID;
//		if (!$db->getResult($queryStr))
//			return false;
//
//		unset($this->_accessList);
//
//		// Update the notify list, if necessary.
//		$mode = ($isUser ? $this->getAccessMode($this->_dms->getUser($userOrGroupID)) : $this->getGroupAccessMode($this->_dms->getGroup($userOrGroupID)));
//		if ($mode == M_NONE) {
//			$this->removeNotify($userOrGroupID, $isUser);
//		}
//
//		return true;
//	} /* }}} */
        
        //glenn
        function removeAccess($userOrGroupID, $isUser)
	{ /* {{{ */
		$db = $this->_dms->getDB();

		$userOrGroup = ($isUser) ? "`userID`" : "`groupID`";

		// Handle user
		if ($isUser) {
			// Clear the base folder access
			$queryStr1 = "DELETE FROM `tblFolderAccessBase` WHERE `userID` = " . (int)$userOrGroupID . " AND `folderID` = " . $this->_id;
			$result1 = $db->getResult($queryStr1);

			// Delete user's folder access in table tblACLs
			$queryStr2 = "DELETE FROM `tblACLs` WHERE `targetType` = " . T_FOLDER . " AND `target` = " . $this->_id . " AND " . $userOrGroup . " = " . (int) $userOrGroupID;
			$result2 = $db->getResult($queryStr2);
		} else {
			// Handle group
			// 1. 尋找此group的成員 
			// 2. 尋找這些成員除了此group以外followGroup爲1的group
			// 3. 如果不存在，則從base folder access裏尋找是否有backup。 如果存在，則尋找這些group在此folder的access mode
			// 4. 選取最高等級的folder access mode給對應的user
			// 5. 在tblACLs刪除所選擇group的folder access mode
			$queryStr3 = "SELECT `userID` FROM `tblGroupMembers` WHERE `followGroup` = 1 AND `groupID` = " . $userOrGroupID;
			$result3 = $db->getResultArray($queryStr3);
			if ($result3) {
				// Loop each userID
				foreach ($result3 as $key => $val) {
					$newMode = 0; // 0 means that no any folder access mode
					$getUserId = $val['userID'];
					$queryStr4 = "SELECT `groupID` FROM `tblGroupMembers` WHERE `followGroup` = 1 AND `groupID` != " . $userOrGroupID . " AND `userID` = " . $getUserId;
					$result4 = $db->getResultArray($queryStr4);
					if ($result4) {
						// Get the higher order folder access mode
						foreach ($result4 as $key => $val) {
							$getGroupId = $val['groupID'];
							$queryStr5 = "SELECT `mode` FROM `tblACLs` WHERE `groupID` = " . $getGroupId . " AND `userID` = -1 AND `followGroup` = 0 AND `targetType` = " . T_FOLDER . " AND `target` = " . $this->_id;
							$result5 = $db->getResultArray($queryStr5);
							if ($result5) {
								foreach ($result5 as $key => $val) {
									if ($val['mode'] > $newMode) {
										$newMode = $val['mode'];
									}
								}
							}
						}
						// 0 means that this user already follow group, but group does not defind any folder access
						if ($newMode == 0) {
							$queryStr6 = "SELECT `baseMode` FROM `tblFolderAccessBase` WHERE `userID` = " . $getUserId . " AND `folderID` = " . $this->_id;
							$result6 = $db->getResultArray($queryStr6);
							if ($result6) {
								// Restore base folder access
								foreach ($result6 as $key => $val) {
									$queryStr7 = "UPDATE `tblACLs` SET `followGroup` = 0, `mode` = " . (int)$val['baseMode'] . " WHERE `target` = " . $this->_id . " AND `targetType` = " . T_FOLDER . " AND `userID` = " . $getUserId . " AND `groupID` = -1";
									$result7 = $db->getResult($queryStr7);
								}
							} else {
								// No base folder access to restored, so delete folder access record in tblACLs table
								$queryStr8 = "DELETE FROM `tblACLs` WHERE `targetType` = " . T_FOLDER . " AND `target` = " . $this->_id . " AND `userID` = " . $getUserId . " AND `groupID` = -1";
								$result8 = $db->getResult($queryStr8);
							}
						} else {
							// Update user's folder access with new mode
							$queryStr9 = "UPDATE `tblACLs` SET `mode` = " . $newMode . " WHERE `target` = " . $this->_id . " AND `targetType` = " . T_FOLDER . " AND `userID` = " . $getUserId . " AND `groupID` = -1 AND `followGroup` = 1";
							$result9 = $db->getResult($queryStr9);
						}
					} else {
						// No other groups, so find the base folder access
						$queryStr10 = "SELECT `baseMode` FROM `tblFolderAccessBase` WHERE `userID` = " . $getUserId . " AND `folderID` = " . $this->_id;
						$result10 = $db->getResultArray($queryStr10);
						if ($result10) {
							foreach ($result10 as $key => $val) {
								$queryStr11 = "UPDATE `tblACLs` SET `followGroup` = 0, `mode` = " . (int)$val['baseMode'] . " WHERE `target` = " . $this->_id . " AND `targetType` = " . T_FOLDER . " AND `userID` = " . $getUserId . " AND `groupID` = -1";
								$result11 = $db->getResult($queryStr11);
							}
						} else {
							$queryStr12 = "DELETE FROM `tblACLs` WHERE `targetType` = " . T_FOLDER . " AND `target` = " . $this->_id . " AND `userID` = " . $getUserId . " AND `groupID` = -1";
							$result12 = $db->getResult($queryStr12);
						}
					}
				}
			}

			// Delete group's folder access in table tblACLs
			$queryStr13 = "DELETE FROM `tblACLs` WHERE `targetType` = " . T_FOLDER . " AND `target` = " . $this->_id . " AND " . $userOrGroup . " = " . (int) $userOrGroupID;
			$result13 = $db->getResult($queryStr13);
		}


		// Update the notify list, if necessary.
		$mode = ($isUser ? $this->getAccessMode($this->_dms->getUser($userOrGroupID)) : $this->getGroupAccessMode($this->_dms->getGroup($userOrGroupID)));
		if ($mode == 1) {
			$this->removeNotify($userOrGroupID, $isUser);
		}

		return true;
	} /* }}} */
        
	/**
	 * Get the access mode of a user on the folder
	 *
	 * This function returns the access mode for a given user. An administrator
	 * and the owner of the folder has unrestricted access. A guest user has
	 * read only access or no access if access rights are further limited
	 * by access control lists. All other users have access rights according
	 * to the access control lists or the default access. This function will
	 * recursive check for access rights of parent folders if access rights
	 * are inherited.
	 *
	 * This function returns the access mode for a given user. An administrator
	 * and the owner of the folder has unrestricted access. A guest user has
	 * read only access or no access if access rights are further limited
	 * by access control lists. All other users have access rights according
	 * to the access control lists or the default access. This function will
	 * recursive check for access rights of parent folders if access rights
	 * are inherited.
	 *
	 * Before checking the access in the method itself a callback 'onCheckAccessFolder'
	 * is called. If it returns a value > 0, then this will be returned by this
	 * method without any further checks. The optional paramater $context
	 * will be passed as a third parameter to the callback. It contains
	 * the operation for which the access mode is retrieved. It is for example
	 * set to 'removeDocument' if the access mode is used to check for sufficient
	 * permission on deleting a document.
	 *
	 * @param object $user user for which access shall be checked
	 * @param string $context context in which the access mode is requested
	 * @return integer access mode
	 */
	function getAccessMode($user, $context='') { /* {{{ */
            if(!$user)
                return M_NONE;

            /* Check if 'onCheckAccessFolder' callback is set */
            if(isset($this->_dms->callbacks['onCheckAccessFolder'])) {
                foreach($this->_dms->callbacks['onCheckAccessFolder'] as $callback) {
                    if(($ret = call_user_func($callback[0], $callback[1], $this, $user, $context)) > 0) {
                            return $ret;
                    }
                }
            }

            /* Administrators have unrestricted access */
            //if ($user->isAdmin()) return M_ALL;

            /* The owner of the document has unrestricted access */
            //if ($user->getID() == $this->_ownerID) return M_ALL;
            
            // Glenn edited on 9 May, 2022
		$highestMode = $this->_dms->getHighestMode();
		// Administrators have unrestricted access
		// Glenn edited on 9 May, 2022
		if ($user->isAdmin()) return (int)$highestMode;

		// The owner of the document has unrestricted access /
		// Glenn edited on 9 May, 2022
		if ($user->getID() == $this->_ownerID) return (int)$highestMode;
                
            /* Check ACLs */
            $accessList = $this->getAccessList();
            if (!$accessList) return false;

            /** @var SeedDMS_Core_UserAccess $userAccess */
            foreach ($accessList["users"] as $userAccess) { //user related to this folder in ACL
                if ($userAccess->getUserID() == $user->getID()) {
                    $mode = $userAccess->getMode();
                    if ($user->isGuest()) {
                            if ($mode >= M_READ) $mode = M_READ;
                    }
                    return $mode;
                }
            }
            /* Get the highest permission defined by a group */
            if($accessList['groups']) {
                $mode = 0;
                /** @var SeedDMS_Core_GroupAccess $groupAccess */
                foreach ($accessList["groups"] as $groupAccess) {
                    if ($user->isMemberOfGroup($groupAccess->getGroup())) {
                        if ($groupAccess->getMode() > $mode){ //must larger than previous group mode
                            if($groupAccess->getMode()>=M_READWRITE){ 
                                $backfiles=debug_backtrace();
                                $file_called_from=basename($backfiles[0]['file']); //eg. out.ViewFolder.php include inc.ClassFolder.php
                                $caller = explode(".", $file_called_from);
                                if($caller[0]=='out'){ //check addDocument in current group
                                    $res=$groupAccess->getGroup()->getGroupPermit('`'.$caller[1].'`'); 
                                    if($res!=NULL){ //!null
                                        $tmp=$res[0][$caller[1]];
                                        if($tmp){
                                           $mode = $groupAccess->getMode();
                                        }
                                    }else{ //$res==false
                                        $mode = $groupAccess->getMode(); 
                                    }
                                }else{ //$caller[0]!='out'
                                    $mode = $groupAccess->getMode();
                                }
                            }else{ //$groupAccess->getMode()<M_READWRITE
                                $mode = $groupAccess->getMode();
                            }
                        }
                    }
                }
                if($mode) { //if group mode exists (!=0), No need to check default mode
                    if ($user->isGuest()) { //if user is Guest, change mode to Read permission
                        if ($mode >= M_READ) $mode = M_READ;
                    }
                    return $mode;
                }
            }
            //if group mode does not exist, check default mode
            $mode = $this->getDefaultAccess(); //if mode==0
            if ($user->isGuest()) {
                    if ($mode >= M_READ) $mode = M_READ;
            }
            return $mode;
	} /* }}} */
        
    /*
     * According to the given $user/$group and $folder, get the authorization of 
     * privileges and the sources causing the authorization by calling 
     * checkFolders($object, $privilege, $isUser).
     * 
     * This function returns two arrays, which are granted privilege data $privAccess1 
     * and notGranted privilege data $privAccess2 for a given user/group object. This 
     * function will recursively check the all privileges, folder path starting from $this folder, user or groups 
     * that user or group belongs to.
     * 
     * @param object $object user/group to be validated
     * @param bool $isUser 1=user, 0=group
     * @return array [$privAccess1, $privAccess2]
     */
    function checkPrivileges($object, $isUser){
        $privileges=array("AddDocument", "AddFile", "AddSubFolder", "CropImage", "EditComment", "EditDocument", "EditDocumentFile", "EditFolder",
            "MoveDocument", "MoveFolder", "RemoveDocument", "RemoveDocumentFile", "RemoveFolder", "UpdateDocument", "FolderAccess", "FolderNotify");
        $privAccess1=$privAccess2=array();
        foreach($privileges as $privilege){
            $tmp=$this->checkFolders($object, $privilege, $isUser);
            $grant=$tmp[0];
            $folderAccess=$tmp[1];
            if($grant){ //grant privilege
                $privAccess1[$privilege]=$folderAccess;
            }else{
                $privAccess2[$privilege]=$folderAccess;
            }
        }
         return [$privAccess1, $privAccess2];   
    }
    /*
     * Get the (bool)privilege authorization of $this folder and folder access data.
     * 
     * According to the parameter $privilege, check the privilege access of the folders.
     * This function will recursively loop the folder path, get source access data by 
     * calling checkSources($user, $privilege, $isUser). Especially, if the folder in loop equals 
     * to $this folder, use $grant to record the authorization of the given $privilege 
     * of $this folder.
     * 
     * @param object $object user/group for which access shall be checked
     * @param string $privilege privilege to be checked
     * @param bool $isUser 1=user, 0=group
     * @return array [$grant, $folderAccess]
     */
    function checkFolders($object, $privilege, $isUser){
        $grant=0;
        $folders=array_reverse($this->getPath()); //folder object array
        $folderAccess=array();
        foreach($folders as $i => $folder){
            $tmp=$folder->checkSources($object, $privilege, $isUser);
            $sourceAccess=$tmp[1];
            $folderStr=($folder->inheritsAccess())?"<b>".$folder->getName()."</b> inherits <b>".$folders[$i+1]->getName()."</b>"
                :"<b>".$folder->getName()."</b>";
            if($folder === $this && $sourceAccess[0][1] && $sourceAccess[0][2]){ 
                $grant=1;
            }
            $folderAccess[$folderStr]=$tmp;
        }
        return [$grant, $folderAccess];
    }

    /*
     * Get the group authorization and folder authorization of sources
     * of the sources for $privilege.
     * 
     * First, get the highest permission of the folder, the sources of the highest 
     * permission(default or from user or group) and the source that involve $user.
     * Second, get (bool)$folderPriv by calling getFolderPriv($highestPerm, $privilege) and 
     * (bool)$groupPriv by calling getGroupPermit($privilege). $folderPriv defined in EditAccess 
     * page, $groupPriv defined in Group Managements Page. Both are bool type, represent 
     * whether $privilege is granted or not.
     * Third, assign the source access data according to $groupPriv && $folderPriv, 
     * the sources with authorization ranks ahead.
     * 
     * @param object $object user/group for which access shall be checked
     * @param string $privilege privilege to be checked
     * @param bool $isUser 1=user, 0=group
     * @return array [$highestSourceStr, $sourceAccess]
     */
    function checkSources($object, $privilege, $isUser){
        $tmp=($isUser)?$this->getHighestPermByUser($object):$this->getHighestPermByGroup($object);
        $highestPerm=$tmp[0];
        $highestSourceStr=$this->getHighestSourceStr($highestPerm, $tmp[1]);
        $sources=$tmp[2];
        $sourceAccess1=$sourceAccess2=array();
        $folderPriv=$this->getFolderPriv($highestPerm, $privilege);
        foreach($sources as $source){
            if($source instanceof SeedDMS_Core_Group){
                $res=$source->getGroupPermit('`'.$privilege.'`');
                $sourceStr=$source->getName();
                $groupPriv=($res[0][$privilege]==="1")?1:0;
            }else if($source instanceof SeedDMS_Core_User){ //isUser
                $sourceStr=$source->getLogin();
                $groupPriv="N/A";
            }
            if($groupPriv && $folderPriv){
                $sourceAccess1[]=array($sourceStr, $groupPriv, $folderPriv); 
            }
            else {// not granted source
                $sourceAccess2[]=array($sourceStr, $groupPriv, $folderPriv);
            }
        }
        $sourceAccess=array_merge($sourceAccess1, $sourceAccess2);
        return [$highestSourceStr, $sourceAccess];
    }

    /*
     * Get the highest permission, source of the highest permission (0 represents default, 
     * groups otherwise), source which is $group. 
     * 
     * @param object $group group for which access shall be checked
     * @return array [(int) the highest permission, (bool / object) source of the highest 
     * permission, (object) $group]
     */
    function getHighestPermByGroup($group){
        $accessList = $this->getAccessList();
        if (!$accessList) return false;
        $mode = 0;
        /** @var SeedDMS_Core_GroupAccess $groupAccess */
        foreach ($accessList["groups"] as $groupAccess) {
            if ($group == $groupAccess->getGroup()) {
                if($groupAccess->getMode() > $mode){
                    $mode = $groupAccess->getMode();
                    $highestSource[] = $groupAccess->getGroup();
                    $source[]=$groupAccess->getGroup();
                }
            }
        }
         if(!$highestSource && !$source && !$mode){ //if mode==0
            $mode= $this->getDefaultAccess(); 
            $highestSource=0;
            $source[]=$group;
        }
        return [$mode, $highestSource, $source];
    }

    /*
     * Get the highest permission, source of the highest permission (0 represents default, 
     * user/groups otherwise), source that involved $user.
     * 
     * An administrator and the owner of the folder has all privileges. All Other users 
     * have privileges according to ACL / default Access of the folder and Group Permit.
     * check the ACLs, if $user exist in ACL, the source of the highest permission
     * should be $user. If the highest permission is from user or group, we retrieve 
     * the sources from Access Control List. If the highest permission is default access,
     * the sources are the given user and all groups that user belongs to. 
     * 
     * @param object $user user for which access shall be checked
     * @return array [(int)the highest permission, (bool / object / array of object)
     * source of the highest permission, (array of object) source that involved $user]
     */
    function getHighestPermByUser($user) { /* {{{ */
        $highestSource=$source=array();
        if ($user->isAdmin() || $user->getID() == $this->_ownerID){ 
            $highestSource[]=$user;
            $source[]=$user;
            return [M_ALL, $highestSource, $source];
        }

        /* Check ACLs */
        $mode = 0;
        $accessList = $this->getAccessList();
        if (!$accessList) return false;
        /** @var SeedDMS_Core_UserAccess $userAccess */
        foreach ($accessList["users"] as $userAccess) { //user related to this folder in ACL
            if ($userAccess->getUserID() == $user->getID()) {
                $mode = $userAccess->getMode();
                if ($user->isGuest()) {
                    if ($mode >= M_READ) 
                        $mode = M_READ;
                }
                $highestSource[]=$user;
                $source[]=$user;
            }
        }

        /** @var SeedDMS_Core_GroupAccess $groupAccess */
        foreach ($accessList["groups"] as $groupAccess) {
            if ($user->isMemberOfGroup($groupAccess->getGroup())) {
                /* $user does not exist in ACL */
                if(!($highestSource[0] instanceof SeedDMS_Core_User)){
                    if($groupAccess->getMode() > $mode){
                        $mode = $groupAccess->getMode();
                        $highestSource = array($groupAccess->getGroup());
                    }else if($groupAccess->getMode() === $mode){
                        $highestSource[] = $groupAccess->getGroup();
                    }
                }
                $source[]=$groupAccess->getGroup();
            }
        }

        /* default access of folder */
        if(!$highestSource && !$source && !$mode){
            $mode= $this->getDefaultAccess(); 
            $highestSource=0;
            $source[]=$user;
            foreach($groups=$user->getGroups() as $group){
                $source[]=$group;
            }
        }
        return [$mode, $highestSource, $source];
    }
    /*
     * Get $str describing the highest Permission and the source of highest Permission.
     * 
     * @param int $highestPerm the highest Permission
     * @param (bool / object / Array of object) source of the highest permission
     * @return string $str
     */
    function getHighestSourceStr($highestPerm, $highestSources){
        $str=$this->getPermByValue($highestPerm)." from ";
        if($highestSources){
            foreach($highestSources as $highestSource){
                if($highestSource instanceof SeedDMS_Core_Group){
                    $str.=$highestSource->getName().",";
                }else if($highestSource instanceof SeedDMS_Core_User){
                    $str.=$highestSource->getLogin().",";
                }
            }
            $str=substr_replace($str, ".", -1, 1);
        }else{
            $str.="the default access of folder.";
        }
        return $str;
    }
    /*
     * Get readable Permission by number
     */
    function getPermByValue($perm){
        switch($perm){
            case "1":
                return "No Access";
            case "2":
                return "Read Permissions";
            case "3":
                return "Read-Write Permissions";
            case "4":
                return "All Permissions";
        }
    }
                
    /*
     * Get (bool)$folderPriv by comparing the given privilege $currPriv with the 
     * privileges of given permission. If the given privilege exists in the given
     * permission, it means the folder grants user $currPriv.
     * 
     * @param int $permission the highest permission of the folder
     * @param string $currPriv privilege to be validated
     * @return bool 1=grant, 0=not grant
     */
     function getFolderPriv($permission, $currPriv){
        $readPriv=array("FolderNotify");
        $readWritePriv=array_merge($readPriv,array("AddSubFolder", "AddDocument", "EditFolder","MoveFolder", "AddFile", "EditComment", "EditDocument", "EditDocumentFile", "MoveDocument", "RemoveDocumentFile", "UpdateDocument", "RemoveFolder", "RemoveDocument"));
        $allPriv=array_merge($readWritePriv, array("FolderAccess", "CropImage"));
        switch($permission){
            case M_NONE:
                $privs=null;
                break;
            case M_READ:
                $privs=$readPriv;
                break;
            case M_READWRITE:
                $privs=$readWritePriv;
                break;
            case M_ALL:
                $privs=$allPriv;
                break;
            }
        foreach($privs as $priv){
            if ($priv===$currPriv){ 
                return 1;
            }
        }
        return 0; //privilege not defined in permissions
    }
    /*
     * validate whether the given user can access the privilege or not
     * 
     * @param object $user user for which access shall be checked
     * @param int $perm threshold permission of the given privilege
     * @param string $privilege privilege to be validated
     * @return bool 1=grant, 0=not grant
     */
    function checkPrivAccess($user, $perm, $privilege) { /* {{{ */
        $tmp=$this->getHighestPermByUser($user);
        $highestPerm=$tmp[0];
        $highestPermSource=$tmp[1];
        $sources=$tmp[2];
        foreach($sources as $source){
            if($source instanceof SeedDMS_Core_Group){
                $res=$source->getGroupPermit('`'.$privilege.'`');
                if(!$groupPriv)
                    $groupPriv= ($res[0][$privilege]==="1")?1:0;
            }else if($source instanceof SeedDMS_Core_User){ //isUser
                $groupPriv="N/A";
            }
        } 
        if(!$highestPermSource && $highestPerm >= $perm || $sources[0] instanceof SeedDMS_Core_User){ //default access
            $groupPriv=1;
        }
        
        if ($user->isGuest() || $highestPerm < $perm || !$groupPriv) {
           return 0;
        }
        return 1;
    }
    
	/**
	 * Get the access mode for a group on the folder
	 * This function returns the access mode for a given group. The algorithmn
	 * applied to get the access mode is the same as describe at
	 * {@link getAccessMode}
	 *
	 * @param SeedDMS_Core_Group $group group for which access shall be checked
	 * @return integer access mode
	 */
	function getGroupAccessMode($group) { /* {{{ */
		$highestPrivileged = M_NONE;
		$foundInACL = false;
		$accessList = $this->getAccessList();
		if (!$accessList)
			return false;

		/** @var SeedDMS_Core_GroupAccess $groupAccess */
		foreach ($accessList["groups"] as $groupAccess) {
			if ($groupAccess->getGroupID() == $group->getID()) {
				$foundInACL = true;
				if ($groupAccess->getMode() > $highestPrivileged) //can access folder
					$highestPrivileged = $groupAccess->getMode();
				if ($highestPrivileged == M_ALL) /* no need to check further */
					return $highestPrivileged;
			}
		}
		if ($foundInACL)
			return $highestPrivileged;

		/* Take default access */
		return $this->getDefaultAccess();
	} /* }}} */

	/** @noinspection PhpUnusedParameterInspection */
	/**
	 * Get a list of all notification
	 * This function returns all users and groups that have registerd a
	 * notification for the folder
	 *
	 * @param integer $type type of notification (not yet used)
	 * @return SeedDMS_Core_User[]|SeedDMS_Core_Group[]|bool array with a the elements 'users' and 'groups' which
	 *        contain a list of users and groups.
	 */
	function getNotifyList($type=0) { /* {{{ */
		if (empty($this->_notifyList)) {
			$db = $this->_dms->getDB();

			$queryStr ="SELECT * FROM `tblNotify` WHERE `targetType` = " . T_FOLDER . " AND `target` = " . $this->_id;
			$resArr = $db->getResultArray($queryStr);
			if (is_bool($resArr) && $resArr == false)
				return false;

			$this->_notifyList = array("groups" => array(), "users" => array());
			foreach ($resArr as $row)
			{
				if ($row["userID"] != -1) {
					$u = $this->_dms->getUser($row["userID"]);
					if($u && !$u->isDisabled())
						array_push($this->_notifyList["users"], $u);
				} else {//if ($row["groupID"] != -1)
					$g = $this->_dms->getGroup($row["groupID"]);
					if($g)
						array_push($this->_notifyList["groups"], $g);
				}
			}
		}
		return $this->_notifyList;
	} /* }}} */

	/**
	 * Make sure only users/groups with read access are in the notify list
	 *
	 */
	function cleanNotifyList() { /* {{{ */
		// If any of the notification subscribers no longer have read access,
		// remove their subscription.
		if (empty($this->_notifyList))
			$this->getNotifyList();

		/* Make a copy of both notifier lists because removeNotify will empty
		 * $this->_notifyList and the second foreach will not work anymore.
		 */
		/** @var SeedDMS_Core_User[] $nusers */
		$nusers = $this->_notifyList["users"];
		$ngroups = $this->_notifyList["groups"];
		foreach ($nusers as $u) {
			if ($this->getAccessMode($u) < M_READ) {
				$this->removeNotify($u->getID(), true);
			}
		}

		/** @var SeedDMS_Core_Group[] $ngroups */
		foreach ($ngroups as $g) {
			if ($this->getGroupAccessMode($g) < M_READ) {
				$this->removeNotify($g->getID(), false);
			}
		}
	} /* }}} */

	/**
	 * Add a user/group to the notification list
	 * This function does not check if the currently logged in user
	 * is allowed to add a notification. This must be checked by the calling
	 * application.
	 *
	 * @param integer $userOrGroupID
	 * @param boolean $isUser true if $userOrGroupID is a user id otherwise false
	 * @return integer error code
	 *    -1: Invalid User/Group ID.
	 *    -2: Target User / Group does not have read access.
	 *    -3: User is already subscribed.
	 *    -4: Database / internal error.
	 *     0: Update successful.
	 */
	function addNotify($userOrGroupID, $isUser) { /* {{{ */
		$db = $this->_dms->getDB();

		$userOrGroup = ($isUser) ? "`userID`" : "`groupID`";

		/* Verify that user / group exists */
		/** @var SeedDMS_Core_User|SeedDMS_Core_Group $obj */
		$obj = ($isUser ? $this->_dms->getUser($userOrGroupID) : $this->_dms->getGroup($userOrGroupID));
		if (!is_object($obj)) {
			return -1;
		}

		/* Verify that the requesting user has permission to add the target to
		 * the notification system.
		 */
		/*
		 * The calling application should enforce the policy on who is allowed
		 * to add someone to the notification system. If is shall remain here
		 * the currently logged in user should be passed to this function
		 *
		GLOBAL $user;
		if ($user->isGuest()) {
			return -2;
		}
		if (!$user->isAdmin()) {
			if ($isUser) {
				if ($user->getID() != $obj->getID()) {
					return -2;
				}
			}
			else {
				if (!$obj->isMember($user)) {
					return -2;
				}
			}
		}
		*/

		//
		// Verify that user / group has read access to the document.
		//
		if ($isUser) {
			// Users are straightforward to check.
			if ($this->getAccessMode($obj) < M_READ) {
				return -2;
			}
		}
		else {
			// FIXME: Why not check the access list first and if this returns
			// not result, then use the default access?
			// Groups are a little more complex.
			if ($this->getDefaultAccess() >= M_READ) {
				// If the default access is at least READ-ONLY, then just make sure
				// that the current group has not been explicitly excluded.
				$acl = $this->getAccessList(M_NONE, O_EQ);
				$found = false;
				/** @var SeedDMS_Core_GroupAccess $group */
				foreach ($acl["groups"] as $group) {
					if ($group->getGroupID() == $userOrGroupID) {
						$found = true;
						break;
					}
				}
				if ($found) {
					return -2;
				}
			}
			else {
				// The default access is restricted. Make sure that the group has
				// been explicitly allocated access to the document.
				$acl = $this->getAccessList(M_READ, O_GTEQ);
				if (is_bool($acl)) {
					return -4;
				}
				$found = false;
				/** @var SeedDMS_Core_GroupAccess $group */
				foreach ($acl["groups"] as $group) {
					if ($group->getGroupID() == $userOrGroupID) {
						$found = true;
						break;
					}
				}
				if (!$found) {
					return -2;
				}
			}
		}
		//
		// Check to see if user/group is already on the list.
		//
		$queryStr = "SELECT * FROM `tblNotify` WHERE `tblNotify`.`target` = '".$this->_id."' ".
			"AND `tblNotify`.`targetType` = '".T_FOLDER."' ".
			"AND `tblNotify`.".$userOrGroup." = '". (int) $userOrGroupID."'";
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr)) {
			return -4;
		}
		if (count($resArr)>0) {
			return -3;
		}

		$queryStr = "INSERT INTO `tblNotify` (`target`, `targetType`, " . $userOrGroup . ") VALUES (" . $this->_id . ", " . T_FOLDER . ", " .  (int) $userOrGroupID . ")";
		if (!$db->getResult($queryStr))
			return -4;

		unset($this->_notifyList);
		return 0;
	} /* }}} */

	/**
	 * Removes notify for a user or group to folder
	 * This function does not check if the currently logged in user
	 * is allowed to remove a notification. This must be checked by the calling
	 * application.
	 *
	 * @param integer $userOrGroupID
	 * @param boolean $isUser true if $userOrGroupID is a user id otherwise false
	 * @param int $type type of notification (0 will delete all) Not used yet!
	 * @return int error code
	 *    -1: Invalid User/Group ID.
	 * -3: User is not subscribed.
	 * -4: Database / internal error.
	 * 0: Update successful.
	 */
	function removeNotify($userOrGroupID, $isUser, $type=0) { /* {{{ */
		$db = $this->_dms->getDB();

		/* Verify that user / group exists. */
		$obj = ($isUser ? $this->_dms->getUser($userOrGroupID) : $this->_dms->getGroup($userOrGroupID));
		if (!is_object($obj)) {
			return -1;
		}

		$userOrGroup = ($isUser) ? "`userID`" : "`groupID`";

		/* Verify that the requesting user has permission to add the target to
		 * the notification system.
		 */
		/*
		 * The calling application should enforce the policy on who is allowed
		 * to add someone to the notification system. If is shall remain here
		 * the currently logged in user should be passed to this function
		 *
		GLOBAL  $user;
		if ($user->isGuest()) {
			return -2;
		}
		if (!$user->isAdmin()) {
			if ($isUser) {
				if ($user->getID() != $obj->getID()) {
					return -2;
				}
			}
			else {
				if (!$obj->isMember($user)) {
					return -2;
				}
			}
		}
		*/

		//
		// Check to see if the target is in the database.
		//
		$queryStr = "SELECT * FROM `tblNotify` WHERE `tblNotify`.`target` = '".$this->_id."' ".
			"AND `tblNotify`.`targetType` = '".T_FOLDER."' ".
			"AND `tblNotify`.".$userOrGroup." = '". (int) $userOrGroupID."'";
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr)) {
			return -4;
		}
		if (count($resArr)==0) {
			return -3;
		}

		$queryStr = "DELETE FROM `tblNotify` WHERE `target` = " . $this->_id . " AND `targetType` = " . T_FOLDER . " AND " . $userOrGroup . " = " .  (int) $userOrGroupID;
		/* If type is given then delete only those notifications */
		if($type)
			$queryStr .= " AND `type` = ".(int) $type;
		if (!$db->getResult($queryStr))
			return -4;

		unset($this->_notifyList);
		return 0;
	} /* }}} */

	/**
	 * Get List of users and groups which have read access on the document
	 *
	 * This function is deprecated. Use
	 * {@see SeedDMS_Core_Folder::getReadAccessList()} instead.
	 */
	function getApproversList() { /* {{{ */
		return $this->getReadAccessList(0, 0);
	} /* }}} */

	/**
	 * Returns a list of groups and users with read access on the folder
	 * The list will not include any guest users,
	 * administrators and the owner of the folder unless $listadmin resp.
	 * $listowner is set to true.
	 *
	 * @param bool|int $listadmin if set to true any admin will be listed too
	 * @param bool|int $listowner if set to true the owner will be listed too
	 * @return array list of users and groups
	 */
	function getReadAccessList($listadmin=0, $listowner=0) { /* {{{ */
		$db = $this->_dms->getDB();

		if (!isset($this->_readAccessList)) {
			$this->_readAccessList = array("groups" => array(), "users" => array());
			$userIDs = "";
			$groupIDs = "";
			$defAccess  = $this->getDefaultAccess();

			/* Check if the default access is < read access or >= read access.
			 * If default access is less than read access, then create a list
			 * of users and groups with read access.
			 * If default access is equal or greater then read access, then
			 * create a list of users and groups without read access.
			 */
			if ($defAccess<M_READ) {
				// Get the list of all users and groups that are listed in the ACL as
				// having read access to the folder.
				$tmpList = $this->getAccessList(M_READ, O_GTEQ);
			}
			else {
				// Get the list of all users and groups that DO NOT have read access
				// to the folder.
				$tmpList = $this->getAccessList(M_NONE, O_LTEQ);
			}
			/** @var SeedDMS_Core_GroupAccess $groupAccess */
			foreach ($tmpList["groups"] as $groupAccess) {
				$groupIDs .= (strlen($groupIDs)==0 ? "" : ", ") . $groupAccess->getGroupID();
			}

			/** @var SeedDMS_Core_UserAccess $userAccess */
			foreach ($tmpList["users"] as $userAccess) {
				$user = $userAccess->getUser();
				if (!$listadmin && $user->isAdmin()) continue;
				if (!$listowner && $user->getID() == $this->_ownerID) continue;
				if ($user->isGuest()) continue;
				$userIDs .= (strlen($userIDs)==0 ? "" : ", ") . $userAccess->getUserID();
			}

			// Construct a query against the users table to identify those users
			// that have read access to this folder, either directly through an
			// ACL entry, by virtue of ownership or by having administrative rights
			// on the database.
			$queryStr="";
			/* If default access is less then read, $userIDs and $groupIDs contains
			 * a list of user with read access
			 */
			if ($defAccess < M_READ) {
				if (strlen($groupIDs)>0) {
					$queryStr = "SELECT `tblUsers`.* FROM `tblUsers` ".
						"LEFT JOIN `tblGroupMembers` ON `tblGroupMembers`.`userID`=`tblUsers`.`id` ".
						"WHERE `tblGroupMembers`.`groupID` IN (". $groupIDs .") ".
						"AND `tblUsers`.`role` != ".SeedDMS_Core_User::role_guest." UNION ";
				}
				$queryStr .=
					"SELECT `tblUsers`.* FROM `tblUsers` ".
					"WHERE (`tblUsers`.`role` != ".SeedDMS_Core_User::role_guest.") ".
					"AND ((`tblUsers`.`id` = ". $this->_ownerID . ") ".
					"OR (`tblUsers`.`role` = ".SeedDMS_Core_User::role_admin.")".
					(strlen($userIDs) == 0 ? "" : " OR (`tblUsers`.`id` IN (". $userIDs ."))").
					") ORDER BY `login`";
			}
			/* If default access is equal or greate then read, $userIDs and
			 * $groupIDs contains a list of user without read access
			 */
			else {
				if (strlen($groupIDs)>0) {
					$queryStr = "SELECT `tblUsers`.* FROM `tblUsers` ".
						"LEFT JOIN `tblGroupMembers` ON `tblGroupMembers`.`userID`=`tblUsers`.`id` ".
						"WHERE `tblGroupMembers`.`groupID` NOT IN (". $groupIDs .")".
						"AND `tblUsers`.`role` != ".SeedDMS_Core_User::role_guest." ".
						(strlen($userIDs) == 0 ? "" : " AND (`tblUsers`.`id` NOT IN (". $userIDs ."))")." UNION ";
				}
				$queryStr .=
					"SELECT `tblUsers`.* FROM `tblUsers` ".
					"WHERE (`tblUsers`.`id` = ". $this->_ownerID . ") ".
					"OR (`tblUsers`.`role` = ".SeedDMS_Core_User::role_admin.") ".
					"UNION ".
					"SELECT `tblUsers`.* FROM `tblUsers` ".
					"WHERE `tblUsers`.`role` != ".SeedDMS_Core_User::role_guest." ".
					(strlen($userIDs) == 0 ? "" : " AND (`tblUsers`.`id` NOT IN (". $userIDs ."))").
					" ORDER BY `login`";
			}
			$resArr = $db->getResultArray($queryStr);
			if (!is_bool($resArr)) {
				foreach ($resArr as $row) {
					$user = $this->_dms->getUser($row['id']);
					if (!$listadmin && $user->isAdmin()) continue;
					if (!$listowner && $user->getID() == $this->_ownerID) continue;
					$this->_readAccessList["users"][] = $user;
				}
			}

			// Assemble the list of groups that have read access to the folder.
			$queryStr="";
			if ($defAccess < M_READ) {
				if (strlen($groupIDs)>0) {
					$queryStr = "SELECT `tblGroups`.* FROM `tblGroups` ".
						"WHERE `tblGroups`.`id` IN (". $groupIDs .") ORDER BY `name`";
				}
			}
			else {
				if (strlen($groupIDs)>0) {
					$queryStr = "SELECT `tblGroups`.* FROM `tblGroups` ".
						"WHERE `tblGroups`.`id` NOT IN (". $groupIDs .") ORDER BY `name`";
				}
				else {
					$queryStr = "SELECT `tblGroups`.* FROM `tblGroups` ORDER BY `name`";
				}
			}
			if (strlen($queryStr)>0) {
				$resArr = $db->getResultArray($queryStr);
				if (!is_bool($resArr)) {
					foreach ($resArr as $row) {
						$group = $this->_dms->getGroup($row["id"]);
						$this->_readAccessList["groups"][] = $group;
					}
				}
			}
		}
		return $this->_readAccessList;
	} /* }}} */

	/**
	 * Get the internally used folderList which stores the ids of folders from
	 * the root folder to the parent folder.
	 *
	 * @return string column separated list of folder ids
	 */
	function getFolderList() { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "SELECT `folderList` FROM `tblFolders` where `id` = ".$this->_id;
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && !$resArr)
			return false;
		return $resArr[0]['folderList'];
	} /* }}} */

	/**
	 * Checks the internal data of the folder and repairs it.
	 * Currently, this function only repairs an incorrect folderList
	 *
	 * @return boolean true on success, otherwise false
	 */
	function repair() { /* {{{ */
		$db = $this->_dms->getDB();

		$curfolderlist = $this->getFolderList();

		// calculate the folderList of the folder
		$parent = $this->getParent();
		$pathPrefix="";
		$path = $parent->getPath();
		foreach ($path as $f) {
			$pathPrefix .= ":".$f->getID();
		}
		if (strlen($pathPrefix)>1) {
			$pathPrefix .= ":";
		}
		if($curfolderlist != $pathPrefix) {
			$queryStr = "UPDATE `tblFolders` SET `folderList`='".$pathPrefix."' WHERE `id` = ". $this->_id;
			$res = $db->getResult($queryStr);
			if (!$res)
				return false;
		}
		return true;
	} /* }}} */

	/**
	 * Get the min and max sequence value for documents
	 *
	 * @return bool|array array with keys 'min' and 'max', false in case of an error
	 */
	function getDocumentsMinMax() { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "SELECT min(`sequence`) AS `min`, max(`sequence`) AS `max` FROM `tblDocuments` WHERE `folder` = " . (int) $this->_id;
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr == false)
			return false;

		return $resArr[0];
	} /* }}} */

}

?>
