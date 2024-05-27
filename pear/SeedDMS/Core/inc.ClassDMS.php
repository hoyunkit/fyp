<?php
/**
 * Implementation of the document management system
 *
 * @category   DMS
 * @package    SeedDMS_Core
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2010, Uwe Steinmann
 * @version    Release: 5.1.9
 */

/**
 * Include some files
 */
require_once("inc.AccessUtils.php");
require_once("inc.FileUtils.php");
require_once("inc.ClassAccess.php");
require_once("inc.ClassObject.php");
require_once("inc.ClassFolder.php");
require_once("inc.ClassDocument.php");
require_once("inc.ClassGroup.php");
require_once("inc.ClassUser.php");
require_once("inc.ClassKeywords.php");
require_once("inc.ClassNotification.php");
require_once("inc.ClassAttribute.php");
/**
 * Class to represent the complete document management system.
 * This class is needed to do most of the dms operations. It needs
 * an instance of {@link SeedDMS_Core_DatabaseAccess} to access the
 * underlying database. Many methods are factory functions which create
 * objects representing the entities in the dms, like folders, documents,
 * users, or groups.
 *
 * Each dms has its own database for meta data and a data store for document
 * content. Both must be specified when creating a new instance of this class.
 * All folders and documents are organized in a hierachy like
 * a regular file system starting with a {@link $rootFolderID}
 *
 * This class does not enforce any access rights on documents and folders
 * by design. It is up to the calling application to use the methods
 * {@link SeedDMS_Core_Folder::getAccessMode()} and
 * {@link SeedDMS_Core_Document::getAccessMode()} and interpret them as desired.
 * Though, there are two convenient functions to filter a list of
 * documents/folders for which users have access rights for. See
 * {@link filterAccess()}
 * and {@link filterUsersByAccess()}
 *
 * Though, this class has a method to set the currently logged in user
 * ({@link setUser}), it does not have to be called, because
 * there is currently no class within the SeedDMS core which needs the logged
 * in user. {@link SeedDMS_Core_DMS} itself does not do any user authentication.
 * It is up to the application using this class.
 *
 * <code>
 * <?php
 * include("inc/inc.ClassDMS.php");
 * $db = new SeedDMS_Core_DatabaseAccess($type, $hostname, $user, $passwd, $name);
 * $db->connect() or die ("Could not connect to db-server");
 * $dms = new SeedDMS_Core_DMS($db, $contentDir);
 * $dms->setRootFolderID(1);
 * ...
 * ?>
 * </code>
 *
 * @category   DMS
 * @package    SeedDMS_Core
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2010, Uwe Steinmann
 * @version    Release: 5.1.9
 */
class SeedDMS_Core_DMS {
	/**
	 * @var SeedDMS_Core_DatabaseAccess $db reference to database object. This must be an instance
	 *      of {@link SeedDMS_Core_DatabaseAccess}.
	 * @access protected
	 */
	protected $db;

	/**
	 * @var array $classnames list of classnames for objects being instanciate
	 *      by the dms
	 * @access protected
	 */
	protected $classnames;

	/**
	 * @var SeedDMS_Core_User $user reference to currently logged in user. This must be
	 *      an instance of {@link SeedDMS_Core_User}. This variable is currently not
	 *      used. It is set by {@link setUser}.
	 * @access private
	 */
	private $user;

	/**
	 * @var string $contentDir location in the file system where all the
	 *      document data is located. This should be an absolute path.
	 * @access public
	 */
	public $contentDir;

	/**
	 * @var integer $rootFolderID ID of root folder
	 * @access public
	 */
	public $rootFolderID;

	/**
	 * @var integer $maxDirID maximum number of documents per folder on the
	 *      filesystem. If this variable is set to a value != 0, the content
	 *      directory will have a two level hierarchy for document storage.
	 * @access public
	 */
	public $maxDirID;

	/**
	 * @var boolean $enableConverting set to true if conversion of content
	 *      is desired
	 * @access public
	 */
	public $enableConverting;

	/**
	 * @var boolean $forceRename use renameFile() instead of copyFile() when
	 *      copying the document content into the data store. The default is
	 *      to copy the file. This parameter only affects the methods
	 *      SeedDMS_Core_Document::addDocument() and
	 *      SeedDMS_Core_Document::addDocumentFile(). Setting this to true
	 *      may save resources especially for large files.
	 * @access public
	 */
	public $forceRename;

	/**
	 * @var array $convertFileTypes list of files types that shall be converted
	 * @access public
	 */
	public $convertFileTypes;

	/**
	 * @var array $viewOnlineFileTypes list of files types that can be viewed
	 *      online
	 * @access public
	 */
	public $viewOnlineFileTypes;

	/**
	 * @var array $noReadForStatus list of status without read right
	 *      online.
	 * @access public
	 */
	public $noReadForStatus;

	/**
	 * @var string $version version of pear package
	 * @access public
	 */
	public $version;

	/**
	 * @var array $callbacks list of methods called when certain operations,
	 * like removing a document, are executed. Set a callback with
	 * {@link SeedDMS_Core_DMS::setCallback()}.
	 * The key of the array is the internal callback function name. Each
	 * array element is an array with two elements: the function name
	 * and the parameter passed to the function.
	 *
	 * Currently implemented callbacks are:
	 *
	 * onPreRemoveDocument($user_param, $document);
	 *   called before deleting a document. If this function returns false
	 *   the document will not be deleted.
	 *
	 * onPostRemoveDocument($user_param, $document_id);
	 *   called after the successful deletion of a document.
	 *
	 * @access public
	 */
	public $callbacks;

	/**
	 * @var SeedDMS_Core_DMS
	 */
	public $_dms;


	/**
	 * Checks if two objects are equal by comparing their IDs
	 *
	 * The regular php check done by '==' compares all attributes of
	 * two objects, which is often not required. The method will first check
	 * if the objects are instances of the same class and than if they
	 * have the same id.
	 *
	 * @param object $object1 first object to be compared
	 * @param object $object2 second object to be compared
	 * @return boolean true if objects are equal, otherwise false
	 */
	static function checkIfEqual($object1, $object2) { /* {{{ */
		if(get_class($object1) != get_class($object2))
			return false;
		if($object1->getID() != $object2->getID())
			return false;
		return true;
	} /* }}} */

	/**
	 * Checks if a list of objects contains a single object by comparing their IDs
	 *
	 * This function is only applicable on list containing objects which have
	 * a method getID() because it is used to check if two objects are equal.
	 * The regular php check on objects done by '==' compares all attributes of
	 * two objects, which isn't required. The method will first check
	 * if the objects are instances of the same class.
	 *
	 * The result of the function can be 0 which happens if the first element
	 * of an indexed array matches.
	 *
	 * @param object $object object to look for (needle)
	 * @param array $list list of objects (haystack)
	 * @return boolean/integer index in array if object was found, otherwise false
	 */
	static function inList($object, $list) { /* {{{ */
		foreach($list as $i=>$item) {
			if(get_class($item) == get_class($object) && $item->getID() == $object->getID())
				return $i;
		}
		return false;
	} /* }}} */

	/**
	 * Checks if date conforms to a given format
	 *
	 * @param string $date date to be checked
	 * @param string $format format of date. Will default to 'Y-m-d H:i:s' if
	 * format is not given.
	 * @return boolean true if date is in propper format, otherwise false
	 */
	static function checkDate($date, $format='Y-m-d H:i:s') { /* {{{ */
		$d = DateTime::createFromFormat($format, $date);
		return $d && $d->format($format) == $date;
	} /* }}} */

	/**
	 * Filter out objects which are not accessible in a given mode by a user.
	 *
	 * The list of objects to be checked can be of any class, but has to have
	 * a method getAccessMode($user) which checks if the given user has at
	 * least access rights to the object as passed in $minMode.
	 *
	 * @param array $objArr list of objects (either documents or folders)
	 * @param object $user user for which access is checked
	 * @param integer $minMode minimum access mode required (M_ANY, M_NONE,
	 *        M_READ, M_READWRITE, M_ALL)
	 * @return array filtered list of objects
	 */
	/*static function filterAccess($objArr, $user, $minMode) { 
		if (!is_array($objArr)) {
			return array();
		}
		$newArr = array();
		foreach ($objArr as $obj) {
			if ($obj->getAccessMode($user) >= $minMode)
				array_push($newArr, $obj);
		}
		return $newArr;
	} */
        //modified
        static function filterAccess($objArr, $user, $minMode, $target=1) { /* {{{ */
            if (!is_array($objArr)) {
                    return array();
            }
            $newArr = array();
            $func=($target==1)?"getAccessMode":"getGroupAccessMode";
            foreach ($objArr as $obj) {
                    if ($obj->$func($user) >= $minMode)
                            array_push($newArr, $obj);
            }
            return $newArr;
	}
        static function filterAccessWithColor($objArr, $user, $minMode, $target=1) { /* {{{ */
            if (!is_array($objArr)) {
                    return array();
            }
            $newArr = array();
            $func=($target==1)?"getAccessMode":"getGroupAccessMode";
            foreach ($objArr as $obj) {
                    if ($obj->$func($user) >= $minMode)
                            array_push($newArr, array($obj, true));
                    else
                            array_push($newArr, array($obj, false));
            }
            return $newArr;
	}
	/**
	 * Filter out users which cannot access an object in a given mode.
	 *
	 * The list of users to be checked can be of any class, but has to have
	 * a method getAccessMode($user) which checks if a user has at least
	 * access rights as passed in $minMode.
	 *
	 * @param object $obj object that shall be accessed
	 * @param array $users list of users which are to check for sufficient
	 *        access rights
	 * @param integer $minMode minimum access right on the object for each user
	 *        (M_ANY, M_NONE, M_READ, M_READWRITE, M_ALL)
	 * @return array filtered list of users
	 */
	static function filterUsersByAccess($obj, $users, $minMode) { /* {{{ */
		$newArr = array();
		foreach ($users as $currUser) {
			if ($obj->getAccessMode($currUser) >= $minMode)
				array_push($newArr, $currUser);
		}
		return $newArr;
	} /* }}} */

	/**
	 * Filter out document links which can not be accessed by a given user
	 *
	 * Returns a filtered list of links which are accessible by the
	 * given user. A link is only accessible, if it is publically visible,
	 * owned by the user, or the accessing user is an administrator.
	 *
	 * @param SeedDMS_Core_DocumentLink[] $links list of objects of type SeedDMS_Core_DocumentLink
	 * @param object $user user for which access is being checked
	 * @param string $access set if source or target of link shall be checked
	 * for sufficient access rights. Set to 'source' if the source document
	 * of a link is to be checked, set to 'target' for the target document.
	 * If not set, then access right aren't checked at all.
	 * @return array filtered list of links
	 */
	static function filterDocumentLinks($user, $links, $access='') { /* {{{ */
		$tmp = array();
		foreach ($links as $link) {
			if ($link->isPublic() || ($link->getUser()->getID() == $user->getID()) || $user->isAdmin()){
				if($access == 'source') {
					$obj = $link->getDocument();
					if ($obj->getAccessMode($user) >= M_READ)
						array_push($tmp, $link);
				} elseif($access == 'target') {
					$obj = $link->getTarget();
					if ($obj->getAccessMode($user) >= M_READ)
						array_push($tmp, $link);
				} else {
					array_push($tmp, $link);
				}
			}
		}
		return $tmp;
	} /* }}} */

	/**
	 * Filter out document attachments which can not be accessed by a given user
	 *
	 * Returns a filtered list of files which are accessible by the
	 * given user. A file is only accessible, if it is publically visible,
	 * owned by the user, or the accessing user is an administrator.
	 *
	 * @param array $files list of objects of type SeedDMS_Core_DocumentFile
	 * @param object $user user for which access is being checked
	 * @return array filtered list of files
	 */
	static function filterDocumentFiles($user, $files) { /* {{{ */
		$tmp = array();
		foreach ($files as $file)
			if ($file->isPublic() || ($file->getUser()->getID() == $user->getID()) || $user->isAdmin() || ($file->getDocument()->getOwner()->getID() == $user->getID()))
				array_push($tmp, $file);
		return $tmp;
	} /* }}} */
        
        static function filterDocumentWordFiles($user, $files) { /* {{{ */
            $tmp = array();
            foreach ($files as $file)
                    if ($file->isPublic() || ($file->getUser()->getID() == $user->getID()) || $user->isAdmin() || ($file->getDocument()->getOwner()->getID() == $user->getID()))
                            array_push($tmp, $file);
            return $tmp;
        } /* }}} */
	/** @noinspection PhpUndefinedClassInspection */
	/**
	 * Create a new instance of the dms
	 *
	 * @param SeedDMS_Core_DatabaseAccess $db object of class {@link SeedDMS_Core_DatabaseAccess}
	 *        to access the underlying database
	 * @param string $contentDir path in filesystem containing the data store
	 *        all document contents is stored
	 */
	function __construct($db, $contentDir) { /* {{{ */
		$this->db = $db;
		if(substr($contentDir, -1) == '/')
			$this->contentDir = $contentDir;
		else
			$this->contentDir = $contentDir.'/';
		$this->rootFolderID = 1;
		$this->maxDirID = 0; //31998;
		$this->forceRename = false;
		$this->enableConverting = false;
		$this->convertFileTypes = array();
		$this->noReadForStatus = array();
		$this->classnames = array();
		$this->classnames['folder'] = 'SeedDMS_Core_Folder';
		$this->classnames['document'] = 'SeedDMS_Core_Document';
		$this->classnames['documentcontent'] = 'SeedDMS_Core_DocumentContent';
		$this->classnames['user'] = 'SeedDMS_Core_User';
		$this->classnames['group'] = 'SeedDMS_Core_Group';
                $this->classnames['access'] = 'SeedDMS_Core_Access';
		$this->callbacks = array();
		$this->version = '5.1.9';
		if($this->version[0] == '@')
			$this->version = '5.1.9';
	} /* }}} */

	/**
	 * Return class name of instantiated objects
	 *
	 * This method returns the class name of those objects being instantiated
	 * by the dms. Each class has an internal place holder, which must be
	 * passed to function.
	 *
	 * @param string $objectname placeholder (can be one of 'folder', 'document',
	 * 'documentcontent', 'user', 'group'
	 *
	 * @return string/boolean name of class or false if placeholder is invalid
	 */
	function getClassname($objectname) { /* {{{ */
		if(isset($this->classnames[$objectname]))
			return $this->classnames[$objectname];
		else
			return false;
	} /* }}} */

	/**
	 * Set class name of instantiated objects
	 *
	 * This method sets the class name of those objects being instatiated
	 * by the dms. It is mainly used to create a new class (possible
	 * inherited from one of the available classes) implementing new
	 * features. The method should be called in the postInitDMS hook.
	 *
	 * @param string $objectname placeholder (can be one of 'folder', 'document',
	 * 'documentcontent', 'user', 'group'
	 * @param string $classname name of class
	 *
	 * @return string/boolean name of old class or false if not set
	 */
	function setClassname($objectname, $classname) { /* {{{ */
		if(isset($this->classnames[$objectname]))
			$oldclass =  $this->classnames[$objectname];
		else
			$oldclass = false;
		$this->classnames[$objectname] = $classname;
		return $oldclass;
	} /* }}} */

	/**
	 * Return database where meta data is stored
	 *
	 * This method returns the database object as it was set by the first
	 * parameter of the constructor.
	 *
	 * @return SeedDMS_Core_DatabaseAccess database
	 */
	function getDB() { /* {{{ */
		return $this->db;
	} /* }}} */

	/**
	 * Return the database version
	 *
	 * @return array|bool
	 */
	function getDBVersion() { /* {{{ */
		$tbllist = $this->db->TableList();
		$tbllist = explode(',',strtolower(join(',',$tbllist)));
		if(!array_search('tblversion', $tbllist))
			return false;
		$queryStr = "SELECT * FROM `tblVersion` order by `major`,`minor`,`subminor` limit 1";
		$resArr = $this->db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr == false)
			return false;
		if (count($resArr) != 1)
			return false;
		$resArr = $resArr[0];
		return $resArr;
	} /* }}} */

	/**
	 * Check if the version in the database is the same as of this package
	 * Only the major and minor version number will be checked.
	 *
	 * @return boolean returns false if versions do not match, but returns
	 *         true if version matches or table tblVersion does not exists.
	 */
	function checkVersion() { /* {{{ */
		$tbllist = $this->db->TableList();
		$tbllist = explode(',',strtolower(join(',',$tbllist)));
		if(!array_search('tblversion', $tbllist))
			return true;
		$queryStr = "SELECT * FROM `tblVersion` order by `major`,`minor`,`subminor` limit 1";
		$resArr = $this->db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr == false)
			return false;
		if (count($resArr) != 1)
			return false;
		$resArr = $resArr[0];
		$ver = explode('.', $this->version);
		if(($resArr['major'] != $ver[0]) || ($resArr['minor'] != $ver[1]))
			return false;
		return true;
	} /* }}} */

	/**
	 * Set id of root folder
	 * This function must be called right after creating an instance of
	 * {@link SeedDMS_Core_DMS}
	 *
	 * @param integer $id id of root folder
	 */
	function setRootFolderID($id) { /* {{{ */
		$this->rootFolderID = $id;
	} /* }}} */

	/**
	 * Set maximum number of subdirectories per directory
	 *
	 * The value of maxDirID is quite crucial, because each document is
	 * stored within a directory in the filesystem. Consequently, there can be
	 * a maximum number of documents, because depending on the file system
	 * the maximum number of subdirectories is limited. Since version 3.3.0 of
	 * SeedDMS an additional directory level has been introduced, which
	 * will be created when maxDirID is not 0. All documents
	 * from 1 to maxDirID-1 will be saved in 1/<docid>, documents from maxDirID
	 * to 2*maxDirID-1 are stored in 2/<docid> and so on.
	 *
	 * Modern file systems like ext4 do not have any restrictions on the number
	 * of subdirectories anymore. Therefore it is best if this parameter is
	 * set to 0. Never change this parameter if documents has already been
	 * created.
	 *
	 * This function must be called right after creating an instance of
	 * {@link SeedDMS_Core_DMS}
	 *
	 * @param integer $id id of root folder
	 */
	function setMaxDirID($id) { /* {{{ */
		$this->maxDirID = $id;
	} /* }}} */

	/**
	 * Get root folder
	 *
	 * @return SeedDMS_Core_Folder|boolean return the object of the root folder or false if
	 *        the root folder id was not set before with {@link setRootFolderID}.
	 */
	function getRootFolder() { /* {{{ */
		if(!$this->rootFolderID) return false;
		return $this->getFolder($this->rootFolderID);
	} /* }}} */

	function setEnableConverting($enable) { /* {{{ */
		$this->enableConverting = $enable;
	} /* }}} */

	function setConvertFileTypes($types) { /* {{{ */
		$this->convertFileTypes = $types;
	} /* }}} */

	function setViewOnlineFileTypes($types) { /* {{{ */
		$this->viewOnlineFileTypes = $types;
	} /* }}} */

	function setForceRename($enable) { /* {{{ */
		$this->forceRename = $enable;
	} /* }}} */

	/**
	 * Set the logged in user
	 *
	 * If user authentication was done externally, this function can
	 * be used to tell the dms who is currently logged in.
	 *
	 * @param object $user
	 *
	 */
	function setUser($user) { /* {{{ */
		$this->user = $user;
	} /* }}} */

	/**
	 * Get the logged in user
	 *
	 * If user authentication was done externally, this function can
	 * be used to tell the dms who is currently logged in.
	 *
	 * @return SeedDMS_Core_User $user
	 *
	 */
	function getLoggedInUser() { /* {{{ */
		return $this->user;
	} /* }}} */

	/**
	 * Return a document by its id
	 *
	 * This function retrieves a document from the database by its id.
	 *
	 * @param integer $id internal id of document
	 * @return SeedDMS_Core_Document instance of {@link SeedDMS_Core_Document} or false
	 */
	function getDocument($id) { /* {{{ */
		$classname = $this->classnames['document'];
		return $classname::getInstance($id, $this);
	} /* }}} */

	/**
	 * Returns all documents of a given user
	 *
	 * @param object $user
	 * @return array list of documents
	 */
	function getDocumentsByUser($user) { /* {{{ */
		return $user->getDocuments();
	} /* }}} */

	/**
	 * Returns all documents locked by a given user
	 *
	 * @param object $user
	 * @return array list of documents
	 */
	function getDocumentsLockedByUser($user) { /* {{{ */
		return $user->getDocumentsLocked();
	} /* }}} */

	/**
	 * Returns all documents which already expired or will expire in the future
	 *
	 * @param string $date date in format YYYY-MM-DD or an integer with the number
	 *   of days. A negative value will cover the days in the past.
	 * @param SeedDMS_Core_User $user
	 * @return bool|SeedDMS_Core_Document[]
	 */
	function getDocumentsExpired($date, $user=null) { /* {{{ */
		$db = $this->getDB();

		if(is_int($date)) {
			$ts = mktime(0, 0, 0) + $date * 86400;
		} elseif(is_string($date)) {
			$tmp = explode('-', $date, 3);
			if(count($tmp) != 3)
				return false;
			$ts = mktime(0, 0, 0, $tmp[1], $tmp[2], $tmp[0]);
		} else
			return false;

		$tsnow = mktime(0, 0, 0); /* Start of today */
		if($ts < $tsnow) { /* Check for docs expired in the past */
			$startts = $ts;
			$endts = $tsnow+86400; /* Use end of day */
			$updatestatus = true;
		} else { /* Check for docs which will expire in the future */
			$startts = $tsnow;
			$endts = $ts+86400; /* Use end of day */
			$updatestatus = false;
		}

		/* Get all documents which have an expiration date. It doesn't check for
		 * the latest status which should be S_EXPIRED, but doesn't have to, because
		 * status may have not been updated after the expiration date has been reached.
		 **/
		$queryStr = "SELECT `tblDocuments`.`id`, `tblDocumentStatusLog`.`status`  FROM `tblDocuments` ".
			"LEFT JOIN `ttcontentid` ON `ttcontentid`.`document` = `tblDocuments`.`id` ".
			"LEFT JOIN `tblDocumentContent` ON `tblDocuments`.`id` = `tblDocumentContent`.`document` AND `tblDocumentContent`.`version` = `ttcontentid`.`maxVersion` ".
			"LEFT JOIN `tblDocumentStatus` ON `tblDocumentStatus`.`documentID` = `tblDocumentContent`.`document` AND `tblDocumentContent`.`version` = `tblDocumentStatus`.`version` ".
			"LEFT JOIN `ttstatid` ON `ttstatid`.`statusID` = `tblDocumentStatus`.`statusID` ".
			"LEFT JOIN `tblDocumentStatusLog` ON `tblDocumentStatusLog`.`statusLogID` = `ttstatid`.`maxLogID`";
		$queryStr .= 
			" WHERE `tblDocuments`.`expires` > ".$startts." AND `tblDocuments`.`expires` < ".$endts;
		if($user)
			$queryStr .=
				" AND `tblDocuments`.`owner` = '".$user->getID()."' ";
		$queryStr .= 
			" ORDER BY `expires` DESC";

		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && !$resArr)
			return false;

		/** @var SeedDMS_Core_Document[] $documents */
		$documents = array();
		foreach ($resArr as $row) {
			$document = $this->getDocument($row["id"]);
			if($updatestatus)
				$document->verifyLastestContentExpriry();
			$documents[] = $document;
		}
		return $documents;
	} /* }}} */

	/**
	 * Returns a document by its name
	 *
	 * This function searches a document by its name and restricts the search
	 * to given folder if passed as the second parameter.
	 *
	 * @param string $name
	 * @param object $folder
	 * @return SeedDMS_Core_Document|boolean found document or false
	 */
	function getDocumentByName($name, $folder=null) { /* {{{ */
		if (!$name) return false;

		$queryStr = "SELECT `tblDocuments`.*, `tblDocumentLocks`.`userID` as `lockUser` ".
			"FROM `tblDocuments` ".
			"LEFT JOIN `tblDocumentLocks` ON `tblDocuments`.`id`=`tblDocumentLocks`.`document` ".
			"WHERE `tblDocuments`.`name` = " . $this->db->qstr($name);
		if($folder)
			$queryStr .= " AND `tblDocuments`.`folder` = ". $folder->getID();
		$queryStr .= " LIMIT 1";

		$resArr = $this->db->getResultArray($queryStr);
		if (is_bool($resArr) && !$resArr)
			return false;

		if(!$resArr)
			return false;

		$row = $resArr[0];
		/** @var SeedDMS_Core_Document $document */
		$document = new $this->classnames['document']($row["id"], $row["name"], $row["comment"], $row["date"], $row["expires"], $row["owner"], $row["folder"], $row["inheritAccess"], $row["defaultAccess"], $row["lockUser"], $row["keywords"], $row["sequence"]);
		$document->setDMS($this);
		return $document;
	} /* }}} */

	/**
	 * Returns a document by the original file name of the last version
	 *
	 * This function searches a document by the name of the last document
	 * version and restricts the search
	 * to given folder if passed as the second parameter.
	 *
	 * @param string $name
	 * @param object $folder
	 * @return SeedDMS_Core_Document|boolean found document or false
	 */
	function getDocumentByOriginalFilename($name, $folder=null) { /* {{{ */
		if (!$name) return false;

		$queryStr = "SELECT `tblDocuments`.*, `tblDocumentLocks`.`userID` as `lockUser` ".
			"FROM `tblDocuments` ".
			"LEFT JOIN `ttcontentid` ON `ttcontentid`.`document` = `tblDocuments`.`id` ".
			"LEFT JOIN `tblDocumentContent` ON `tblDocumentContent`.`document` = `tblDocuments`.`id` AND `tblDocumentContent`.`version` = `ttcontentid`.`maxVersion` ".
			"LEFT JOIN `tblDocumentLocks` ON `tblDocuments`.`id`=`tblDocumentLocks`.`document` ".
			"WHERE `tblDocumentContent`.`orgFileName` = " . $this->db->qstr($name);
		if($folder)
			$queryStr .= " AND `tblDocuments`.`folder` = ". $folder->getID();
		$queryStr .= " LIMIT 1";

		$resArr = $this->db->getResultArray($queryStr);
		if (is_bool($resArr) && !$resArr)
			return false;

		if(!$resArr)
			return false;

		$row = $resArr[0];
		/** @var SeedDMS_Core_Document $document */
		$document = new $this->classnames['document']($row["id"], $row["name"], $row["comment"], $row["date"], $row["expires"], $row["owner"], $row["folder"], $row["inheritAccess"], $row["defaultAccess"], $row["lockUser"], $row["keywords"], $row["sequence"]);
		$document->setDMS($this);
		return $document;
	} /* }}} */

	/**
	 * Return a document content by its id
	 *
	 * This function retrieves a document content from the database by its id.
	 *
	 * @param integer $id internal id of document content
	 * @return bool|SeedDMS_Core_Document or false
	 */
	function getDocumentContent($id) { /* {{{ */
		if (!is_numeric($id)) return false;

		$queryStr = "SELECT * FROM `tblDocumentContent` WHERE `id` = ".(int) $id;
		$resArr = $this->db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr == false)
			return false;
		if (count($resArr) != 1)
			return false;
		$row = $resArr[0];

		$document = $this->getDocument($row['document']);
		$version = new $this->classnames['documentcontent']($row['id'], $document, $row['version'], $row['comment'], $row['date'], $row['createdBy'], $row['dir'], $row['orgFileName'], $row['fileType'], $row['mimeType'], $row['fileSize'], $row['checksum']);
		return $version;
	} /* }}} */

	/**
	 * Returns all documents with a predefined search criteria
	 *
	 * The records return have the following elements
	 *
	 * From Table tblDocuments
	 * [id] => id of document
	 * [name] => name of document
	 * [comment] => comment of document
	 * [date] => timestamp of creation date of document
	 * [expires] => timestamp of expiration date of document
	 * [owner] => user id of owner
	 * [folder] => id of parent folder
	 * [folderList] => column separated list of folder ids, e.g. :1:41:
	 * [inheritAccess] => 1 if access is inherited
	 * [defaultAccess] => default access mode
	 * [locked] => always -1 (TODO: is this field still used?)
	 * [keywords] => keywords of document
	 * [sequence] => sequence of document
	 *
	 * From Table tblDocumentLocks
	 * [lockUser] => id of user locking the document
	 *
	 * From Table tblDocumentStatusLog
	 * [version] => latest version of document
	 * [statusID] => id of latest status log
	 * [documentID] => id of document
	 * [status] => current status of document
	 * [statusComment] => comment of current status
	 * [statusDate] => datetime when the status was entered, e.g. 2014-04-17 21:35:51
	 * [userID] => id of user who has initiated the status change
	 *
	 * From Table tblUsers
	 * [ownerName] => name of owner of document
	 * [statusName] => name of user who has initiated the status change
	 *
	 * @param string $listtype type of document list, can be 'AppRevByMe',
	 * 'AppRevOwner', 'ReceiptByMe', 'ReviseByMe', 'LockedByMe', 'MyDocs'
	 * @param SeedDMS_Core_User $param1 user
	 * @param bool $param2 set to true
	 * if 'AppRevByMe', 'ReviseByMe', 'ReceiptByMe' shall return even documents
	 * І have already taken care of.
	 * @param string $param3 sort list by this field
	 * @param string $param4 order direction
	 * @return array|bool
	 */
	function getDocumentList($listtype, $param1=null, $param2=false, $param3='', $param4='') { /* {{{ */
		/* The following query will get all documents and lots of additional
		 * information. It requires the two temporary tables ttcontentid and
		 * ttstatid.
		 */
		if (!$this->db->createTemporaryTable("ttstatid") || !$this->db->createTemporaryTable("ttcontentid")) {
			return false;
		}
		/* The following statement retrieves the status of the last version of all
		 * documents. It must be restricted by further where clauses.
		 */
/*
		$queryStr = "SELECT `tblDocuments`.*, `tblDocumentLocks`.`userID` as `lockUser`, ".
			"`tblDocumentContent`.`version`, `tblDocumentStatus`.*, `tblDocumentStatusLog`.`status`, ".
			"`tblDocumentStatusLog`.`comment` AS `statusComment`, `tblDocumentStatusLog`.`date` as `statusDate`, ".
			"`tblDocumentStatusLog`.`userID`, `oTbl`.`fullName` AS `ownerName`, `sTbl`.`fullName` AS `statusName` ".
			"FROM `tblDocumentContent` ".
			"LEFT JOIN `tblDocuments` ON `tblDocuments`.`id` = `tblDocumentContent`.`document` ".
			"LEFT JOIN `tblDocumentStatus` ON `tblDocumentStatus`.`documentID` = `tblDocumentContent`.`document` ".
			"LEFT JOIN `tblDocumentStatusLog` ON `tblDocumentStatusLog`.`statusID` = `tblDocumentStatus`.`statusID` ".
			"LEFT JOIN `ttstatid` ON `ttstatid`.`maxLogID` = `tblDocumentStatusLog`.`statusLogID` ".
			"LEFT JOIN `ttcontentid` ON `ttcontentid`.`maxVersion` = `tblDocumentStatus`.`version` AND `ttcontentid`.`document` = `tblDocumentStatus`.`documentID` ".
			"LEFT JOIN `tblDocumentLocks` ON `tblDocuments`.`id`=`tblDocumentLocks`.`document` ".
			"LEFT JOIN `tblUsers` AS `oTbl` on `oTbl`.`id` = `tblDocuments`.`owner` ".
			"LEFT JOIN `tblUsers` AS `sTbl` on `sTbl`.`id` = `tblDocumentStatusLog`.`userID` ".
			"WHERE `ttstatid`.`maxLogID`=`tblDocumentStatusLog`.`statusLogID` ".
			"AND `ttcontentid`.`maxVersion` = `tblDocumentContent`.`version` ";
 */
		/* New sql statement which retrieves all documents, its latest version and
		 * status, the owner and user initiating the latest status.
		 * It doesn't need the where clause anymore. Hence the statement could be
		 * extended with further left joins.
		 */
		$selectStr = "SELECT `tblDocuments`.*, `tblDocumentLocks`.`userID` as `lockUser`, ".
			"`tblDocumentContent`.`version`, `tblDocumentStatus`.*, `tblDocumentStatusLog`.`status`, ".
			"`tblDocumentStatusLog`.`comment` AS `statusComment`, `tblDocumentStatusLog`.`date` as `statusDate`, ".
			"`tblDocumentStatusLog`.`userID`, `oTbl`.`fullName` AS `ownerName`, `sTbl`.`fullName` AS `statusName` ";
		$queryStr =
			"FROM `ttcontentid` ".
			"LEFT JOIN `tblDocuments` ON `tblDocuments`.`id` = `ttcontentid`.`document` ".
			"LEFT JOIN `tblDocumentContent` ON `tblDocumentContent`.`document` = `ttcontentid`.`document` AND `tblDocumentContent`.`version` = `ttcontentid`.`maxVersion` ".
			"LEFT JOIN `tblDocumentStatus` ON `tblDocumentStatus`.`documentID`=`ttcontentid`.`document` AND `tblDocumentStatus`.`version`=`ttcontentid`.`maxVersion` ".
			"LEFT JOIN `ttstatid` ON `ttstatid`.`statusID` = `tblDocumentStatus`.`statusID` ".
			"LEFT JOIN `tblDocumentStatusLog` ON `ttstatid`.`statusID` = `tblDocumentStatusLog`.`statusID` AND `ttstatid`.`maxLogID` = `tblDocumentStatusLog`.`statusLogID` ".
			"LEFT JOIN `tblDocumentLocks` ON `ttcontentid`.`document`=`tblDocumentLocks`.`document` ".
			"LEFT JOIN `tblUsers` `oTbl` ON `oTbl`.`id` = `tblDocuments`.`owner` ".
			"LEFT JOIN `tblUsers` `sTbl` ON `sTbl`.`id` = `tblDocumentStatusLog`.`userID` ";

//		echo $queryStr;

		switch($listtype) {
		case 'AppRevByMe': // Documents I have to review/approve {{{
			$queryStr .= "WHERE 1=1 ";

			$user = $param1;
			// Get document list for the current user.
			$reviewStatus = $user->getReviewStatus();
			$approvalStatus = $user->getApprovalStatus();

			// Create a comma separated list of all the documentIDs whose information is
			// required.
			// Take only those documents into account which hasn't be touched by the user
			$dList = array();
			foreach ($reviewStatus["indstatus"] as $st) {
				if (($st["status"]==0 || $param2) && !in_array($st["documentID"], $dList)) {
					$dList[] = $st["documentID"];
				}
			}
			foreach ($reviewStatus["grpstatus"] as $st) {
				if (($st["status"]==0 || $param2) && !in_array($st["documentID"], $dList)) {
					$dList[] = $st["documentID"];
				}
			}
			foreach ($approvalStatus["indstatus"] as $st) {
				if (($st["status"]==0 || $param2) && !in_array($st["documentID"], $dList)) {
					$dList[] = $st["documentID"];
				}
			}
			foreach ($approvalStatus["grpstatus"] as $st) {
				if (($st["status"]==0 || $param2) && !in_array($st["documentID"], $dList)) {
					$dList[] = $st["documentID"];
				}
			}
			$docCSV = "";
			foreach ($dList as $d) {
				$docCSV .= (strlen($docCSV)==0 ? "" : ", ")."'".$d."'";
			}

			if (strlen($docCSV)>0) {
				$queryStr .= "AND `tblDocumentStatusLog`.`status` IN (".S_DRAFT_REV.", ".S_DRAFT_APP.", ".S_EXPIRED.") ".
							"AND `tblDocuments`.`id` IN (" . $docCSV . ") ".
							"ORDER BY `statusDate` DESC";
			} else {
				$queryStr = '';
			}
			break; // }}}
		case 'ReviewByMe': // Documents I have to review {{{
			if (!$this->db->createTemporaryTable("ttreviewid")) {
				return false;
			}
			$user = $param1;
			$orderby = $param3;
			if($param4 == 'desc')
				$orderdir = 'DESC';
			else
				$orderdir = 'ASC';

			$groups = array();
			$tmp = $user->getGroups();
			foreach($tmp as $group)
				$groups[] = $group->getID();

			$selectStr .= ", `tblDocumentReviewLog`.`date` as `duedate` ";
			$queryStr .=
				"LEFT JOIN `tblDocumentReviewers` on `ttcontentid`.`document`=`tblDocumentReviewers`.`documentID` AND `ttcontentid`.`maxVersion`=`tblDocumentReviewers`.`version` ".
				"LEFT JOIN `ttreviewid` ON `ttreviewid`.`reviewID` = `tblDocumentReviewers`.`reviewID` ".
				"LEFT JOIN `tblDocumentReviewLog` ON `tblDocumentReviewLog`.`reviewLogID`=`ttreviewid`.`maxLogID` ";

			if(1) {
			$queryStr .= "WHERE (`tblDocumentReviewers`.`type` = 0 AND `tblDocumentReviewers`.`required` = ".$user->getID()." ";
			if($groups)
				$queryStr .= "OR `tblDocumentReviewers`.`type` = 1 AND `tblDocumentReviewers`.`required` IN (".implode(',', $groups).") ";
			$queryStr .= ") ";
			$queryStr .= "AND `tblDocumentStatusLog`.`status` IN (".S_DRAFT_REV.", ".S_EXPIRED.") ";
			if(!$param2)
				$queryStr .= " AND `tblDocumentReviewLog`.`status` = 0 ";
			if ($orderby=='e') $queryStr .= "ORDER BY `expires`";
			else if ($orderby=='u') $queryStr .= "ORDER BY `statusDate`";
			else if ($orderby=='s') $queryStr .= "ORDER BY `status`";
			else $queryStr .= "ORDER BY `name`";
			$queryStr .= " ".$orderdir;
			} else {
			$queryStr .= "WHERE 1=1 ";

			// Get document list for the current user.
			$reviewStatus = $user->getReviewStatus();

			// Create a comma separated list of all the documentIDs whose information is
			// required.
			// Take only those documents into account which hasn't be touched by the user
			// ($st["status"]==0)
			$dList = array();
			foreach ($reviewStatus["indstatus"] as $st) {
				if (($st["status"]==0 || $param2) && !in_array($st["documentID"], $dList)) {
					$dList[] = $st["documentID"];
				}
			}
			foreach ($reviewStatus["grpstatus"] as $st) {
				if (($st["status"]==0 || $param2) && !in_array($st["documentID"], $dList)) {
					$dList[] = $st["documentID"];
				}
			}
			$docCSV = "";
			foreach ($dList as $d) {
				$docCSV .= (strlen($docCSV)==0 ? "" : ", ")."'".$d."'";
			}

			if (strlen($docCSV)>0) {
				$queryStr .= "AND `tblDocumentStatusLog`.`status` IN (".S_DRAFT_REV.", ".S_EXPIRED.") ".
							"AND `tblDocuments`.`id` IN (" . $docCSV . ") ";
				//$queryStr .= "ORDER BY `statusDate` DESC";
				if ($orderby=='e') $queryStr .= "ORDER BY `expires`";
				else if ($orderby=='u') $queryStr .= "ORDER BY `statusDate`";
				else if ($orderby=='s') $queryStr .= "ORDER BY `status`";
				else $queryStr .= "ORDER BY `name`";
				$queryStr .= " ".$orderdir;
			} else {
				$queryStr = '';
			}
			}
			break; // }}}
		case 'ApproveByMe': // Documents I have to approve {{{
			if (!$this->db->createTemporaryTable("ttapproveid")) {
				return false;
			}
			$user = $param1;
			$orderby = $param3;
			if($param4 == 'desc')
				$orderdir = 'DESC';
			else
				$orderdir = 'ASC';

			$groups = array();
			$tmp = $user->getGroups();
			foreach($tmp as $group)
				$groups[] = $group->getID();

			$selectStr .= ", `tblDocumentApproveLog`.`date` as `duedate` ";
			$queryStr .=
				"LEFT JOIN `tblDocumentApprovers` on `ttcontentid`.`document`=`tblDocumentApprovers`.`documentID` AND `ttcontentid`.`maxVersion`=`tblDocumentApprovers`.`version` ".
				"LEFT JOIN `ttapproveid` ON `ttapproveid`.`approveID` = `tblDocumentApprovers`.`approveID` ".
				"LEFT JOIN `tblDocumentApproveLog` ON `tblDocumentApproveLog`.`approveLogID`=`ttapproveid`.`maxLogID` ";

			if(1) {
			$queryStr .= "WHERE (`tblDocumentApprovers`.`type` = 0 AND `tblDocumentApprovers`.`required` = ".$user->getID()." ";
			if($groups)
				$queryStr .= "OR `tblDocumentApprovers`.`type` = 1 AND `tblDocumentApprovers`.`required` IN (".implode(',', $groups).")";
			$queryStr .= ") ";
			$queryStr .= "AND `tblDocumentStatusLog`.`status` IN (".S_DRAFT_APP.", ".S_EXPIRED.") ";
			if(!$param2)
				$queryStr .= " AND `tblDocumentApproveLog`.`status` = 0 ";
			if ($orderby=='e') $queryStr .= "ORDER BY `expires`";
			else if ($orderby=='u') $queryStr .= "ORDER BY `statusDate`";
			else if ($orderby=='s') $queryStr .= "ORDER BY `status`";
			else $queryStr .= "ORDER BY `name`";
			$queryStr .= " ".$orderdir;
			} else {
			$queryStr .= "WHERE 1=1 ";

			// Get document list for the current user.
			$approvalStatus = $user->getApprovalStatus();

			// Create a comma separated list of all the documentIDs whose information is
			// required.
			// Take only those documents into account which hasn't be touched by the user
			// ($st["status"]==0)
			$dList = array();
			foreach ($approvalStatus["indstatus"] as $st) {
				if (($st["status"]==0 || $param2) && !in_array($st["documentID"], $dList)) {
					$dList[] = $st["documentID"];
				}
			}
			foreach ($approvalStatus["grpstatus"] as $st) {
				if (($st["status"]==0 || $param2) && !in_array($st["documentID"], $dList)) {
					$dList[] = $st["documentID"];
				}
			}
			$docCSV = "";
			foreach ($dList as $d) {
				$docCSV .= (strlen($docCSV)==0 ? "" : ", ")."'".$d."'";
			}

			if (strlen($docCSV)>0) {
				$queryStr .= "AND `tblDocumentStatusLog`.`status` IN (".S_DRAFT_APP.", ".S_EXPIRED.") ".
							"AND `tblDocuments`.`id` IN (" . $docCSV . ") ";
				//$queryStr .= "ORDER BY `statusDate` DESC";
				if ($orderby=='e') $queryStr .= "ORDER BY `expires`";
				else if ($orderby=='u') $queryStr .= "ORDER BY `statusDate`";
				else if ($orderby=='s') $queryStr .= "ORDER BY `status`";
				else $queryStr .= "ORDER BY `name`";
				$queryStr .= " ".$orderdir;
			} else {
				$queryStr = '';
			}
			}
			break; // }}}
		case 'WorkflowByMe': // Documents I to trigger in Worklflow {{{
			$queryStr .= "WHERE 1=1 ";

			$user = $param1;
			// Get document list for the current user.
			$workflowStatus = $user->getWorkflowStatus();

			// Create a comma separated list of all the documentIDs whose information is
			// required.
			$dList = array();
			foreach ($workflowStatus["u"] as $st) {
				if (!in_array($st["document"], $dList)) {
					$dList[] = $st["document"];
				}
			}
			foreach ($workflowStatus["g"] as $st) {
				if (!in_array($st["document"], $dList)) {
					$dList[] = $st["document"];
				}
			}
			$docCSV = "";
			foreach ($dList as $d) {
				$docCSV .= (strlen($docCSV)==0 ? "" : ", ")."'".$d."'";
			}

			if (strlen($docCSV)>0) {
				$queryStr .=
							//"AND `tblDocumentStatusLog`.`status` IN (".S_IN_WORKFLOW.", ".S_EXPIRED.") ".
							"AND `tblDocuments`.`id` IN (" . $docCSV . ") ".
							"ORDER BY `statusDate` DESC";
			} else {
				$queryStr = '';
			}
			break; // }}}
		case 'AppRevOwner': // Documents waiting for review/approval/revision I'm owning {{{
			$queryStr .= "WHERE 1=1 ";

			$user = $param1;
			$orderby = $param3;
			if($param4 == 'desc')
				$orderdir = 'DESC';
			else
				$orderdir = 'ASC';
			/** @noinspection PhpUndefinedConstantInspection */
			$queryStr .=	"AND `tblDocuments`.`owner` = '".$user->getID()."' ".
				"AND `tblDocumentStatusLog`.`status` IN (".S_DRAFT_REV.", ".S_DRAFT_APP.", ".S_IN_REVISION.") "; /** @todo S_IN_REVISION is not defined */
			if ($orderby=='e') $queryStr .= "ORDER BY `expires`";
			else if ($orderby=='u') $queryStr .= "ORDER BY `statusDate`";
			else if ($orderby=='s') $queryStr .= "ORDER BY `status`";
			else $queryStr .= "ORDER BY `name`";
			$queryStr .= " ".$orderdir;
//			$queryStr .= "AND `tblDocuments`.`owner` = '".$user->getID()."' ".
//				"AND `tblDocumentStatusLog`.`status` IN (".S_DRAFT_REV.", ".S_DRAFT_APP.") ".
//				"ORDER BY `statusDate` DESC";
			break; // }}}
		case 'RejectOwner': // Documents that has been rejected and I'm owning {{{
			$queryStr .= "WHERE 1=1 ";

			$user = $param1;
			$orderby = $param3;
			if($param4 == 'desc')
				$orderdir = 'DESC';
			else
				$orderdir = 'ASC';
			$queryStr .= "AND `tblDocuments`.`owner` = '".$user->getID()."' ".
				"AND `tblDocumentStatusLog`.`status` IN (".S_REJECTED.") ";
			//$queryStr .= "ORDER BY `statusDate` DESC";
			if ($orderby=='e') $queryStr .= "ORDER BY `expires`";
			else if ($orderby=='u') $queryStr .= "ORDER BY `statusDate`";
			else if ($orderby=='s') $queryStr .= "ORDER BY `status`";
			else $queryStr .= "ORDER BY `name`";
			$queryStr .= " ".$orderdir;
			break; // }}}
		case 'LockedByMe': // Documents locked by me {{{
			$queryStr .= "WHERE 1=1 ";

			$user = $param1;
			$orderby = $param3;
			if($param4 == 'desc')
				$orderdir = 'DESC';
			else
				$orderdir = 'ASC';

			$qs = 'SELECT `document` FROM `tblDocumentLocks` WHERE `userID`='.$user->getID();
			$ra = $this->db->getResultArray($qs);
			if (is_bool($ra) && !$ra) {
				return false;
			}
			$docs = array();
			foreach($ra as $d) {
				$docs[] = $d['document'];
			}

			if ($docs) {
				$queryStr .= "AND `tblDocuments`.`id` IN (" . implode(',', $docs) . ") ";
				if ($orderby=='e') $queryStr .= "ORDER BY `expires`";
				else if ($orderby=='u') $queryStr .= "ORDER BY `statusDate`";
				else if ($orderby=='s') $queryStr .= "ORDER BY `status`";
				else $queryStr .= "ORDER BY `name`";
				$queryStr .= " ".$orderdir;
			} else {
				$queryStr = '';
			}
			break; // }}}
		case 'ExpiredOwner': // Documents expired and owned by me {{{
			if(is_int($param2)) {
				$ts = mktime(0, 0, 0) + $param2 * 86400;
			} elseif(is_string($param2)) {
				$tmp = explode('-', $param2, 3);
				if(count($tmp) != 3)
					return false;
				$ts = mktime(0, 0, 0, $tmp[1], $tmp[2], $tmp[0]);
			} else
				$ts = mktime(0, 0, 0)-365*86400; /* Start of today - 1 year */

			$tsnow = mktime(0, 0, 0); /* Start of today */
			if($ts < $tsnow) { /* Check for docs expired in the past */
				$startts = $ts;
				$endts = $tsnow+86400; /* Use end of day */
			} else { /* Check for docs which will expire in the future */
				$startts = $tsnow;
				$endts = $ts+86400; /* Use end of day */
			}

			$queryStr .= 
				"WHERE `tblDocuments`.`expires` > ".$startts." AND `tblDocuments`.`expires` < ".$endts." ";

			$user = $param1;
			$orderby = $param3;
			if($param4 == 'desc')
				$orderdir = 'DESC';
			else
				$orderdir = 'ASC';
			$queryStr .=	"AND `tblDocuments`.`owner` = '".$user->getID()."' ";
			if ($orderby=='e') $queryStr .= "ORDER BY `expires`";
			else if ($orderby=='u') $queryStr .= "ORDER BY `statusDate`";
			else if ($orderby=='s') $queryStr .= "ORDER BY `status`";
			else $queryStr .= "ORDER BY `name`";
			$queryStr .= " ".$orderdir;
			break; // }}}
		case 'WorkflowOwner': // Documents waiting for workflow trigger I'm owning {{{
			$queryStr .= "WHERE 1=1 ";

			$user = $param1;
			$queryStr .= "AND `tblDocuments`.`owner` = '".$user->getID()."' ".
				"AND `tblDocumentStatusLog`.`status` IN (".S_IN_WORKFLOW.") ".
				"ORDER BY `statusDate` DESC";
			break; // }}}
		case 'MyDocs': // Documents owned by me {{{
			$queryStr .= "WHERE 1=1 ";

			$user = $param1;
			$orderby = $param3;
			if($param4 == 'desc')
				$orderdir = 'DESC';
			else
				$orderdir = 'ASC';
			$queryStr .=	"AND `tblDocuments`.`owner` = '".$user->getID()."' ";
			if ($orderby=='e') $queryStr .= "ORDER BY `expires`";
			else if ($orderby=='u') $queryStr .= "ORDER BY `statusDate`";
			else if ($orderby=='s') $queryStr .= "ORDER BY `status`";
			else $queryStr .= "ORDER BY `name`";
			$queryStr .= " ".$orderdir;
			break; // }}}
		}

		if($queryStr) {
			$resArr = $this->db->getResultArray($selectStr.$queryStr);
			if (is_bool($resArr) && !$resArr) {
				return false;
			}
			/*
			$documents = array();
			foreach($resArr as $row)
				$documents[] = $this->getDocument($row["id"]);
			 */
		} else {
			return array();
		}

		return $resArr;
	} /* }}} */

	function makeTimeStamp($hour, $min, $sec, $year, $month, $day) { /* {{{ */
		$thirtyone = array (1, 3, 5, 7, 8, 10, 12);
		$thirty = array (4, 6, 9, 11);

		// Very basic check that the terms are valid. Does not fail for illegal
		// dates such as 31 Feb.
		if (!is_numeric($hour) || !is_numeric($min) || !is_numeric($sec) || !is_numeric($year) || !is_numeric($month) || !is_numeric($day) || $month<1 || $month>12 || $day<1 || $day>31 || $hour<0 || $hour>23 || $min<0 || $min>59 || $sec<0 || $sec>59) {
			return false;
		}
		$year = (int) $year;
		$month = (int) $month;
		$day = (int) $day;

		if (array_search($month, $thirtyone)) {
			$max=31;
		}
		else if (array_search($month, $thirty)) {
			$max=30;
		}
		else {
			$max=(($year % 4 == 0) && ($year % 100 != 0 || $year % 400 == 0)) ? 29 : 28;
		}

		// If the date falls out of bounds, set it to the maximum for the given
		// month. Makes assumption about the user's intention, rather than failing
		// for absolutely everything.
		if ($day>$max) {
			$day=$max;
		}

		return mktime($hour, $min, $sec, $month, $day, $year);
	} /* }}} */

	/**
	 * Search the database for documents
	 *
	 * Note: the creation date will be used to check againts the
	 * date saved with the document
	 * or folder. The modification date will only be used for documents. It
	 * is checked against the creation date of the document content. This
	 * meanѕ that updateѕ of a document will only result in a searchable
	 * modification if a new version is uploaded.
	 *
	 * @param string $query seach query with space separated words
	 * @param integer $limit number of items in result set
	 * @param integer $offset index of first item in result set
	 * @param string $logicalmode either AND or OR
	 * @param array $searchin list of fields to search in
	 *        1 = keywords, 2=name, 3=comment, 4=attributes
	 * @param SeedDMS_Core_Folder|null $startFolder search in the folder only (null for root folder)
	 * @param SeedDMS_Core_User $owner search for documents owned by this user
	 * @param array $status list of status
	 * @param array $creationstartdate search for documents created after this date
	 * @param array $creationenddate search for documents created before this date
	 * @param array $modificationstartdate search for documents modified after this date
	 * @param array $modificationenddate search for documents modified before this date
	 * @param array $categories list of categories the documents must have assigned
	 * @param array $attributes list of attributes. The key of this array is the
	 * attribute definition id. The value of the array is the value of the
	 * attribute. If the attribute may have multiple values it must be an array.
	 * @param integer $mode decide whether to search for documents/folders
	 *        0x1 = documents only
	 *        0x2 = folders only
	 *        0x3 = both
	 * @param array $expirationstartdate search for documents expiring after this date
	 * @param array $expirationenddate search for documents expiring before this date
	 * @return array|bool
	 */
	function search($query, $limit=0, $offset=0, $logicalmode='AND', $searchin=array(), $startFolder=null, $owner=null, $status = array(), $creationstartdate=array(), $creationenddate=array(), $modificationstartdate=array(), $modificationenddate=array(), $categories=array(), $attributes=array(), $mode=0x3, $expirationstartdate=array(), $expirationenddate=array()) { /* {{{ */
		// Split the search string into constituent keywords.
		$tkeys=array();
		if (strlen($query)>0) {
			$tkeys = preg_split("/[\t\r\n ,]+/", $query);
		}

		// if none is checkd search all
		if (count($searchin)==0)
			$searchin=array(1, 2, 3, 4, 5);

		/*--------- Do it all over again for folders -------------*/
		$totalFolders = 0;
		if($mode & 0x2) {
			$searchKey = "";

			$classname = $this->classnames['folder'];
			$searchFields = $classname::getSearchFields($this, $searchin);

			if (count($searchFields)>0) {
				foreach ($tkeys as $key) {
					$key = trim($key);
					if (strlen($key)>0) {
						$searchKey = (strlen($searchKey)==0 ? "" : $searchKey." ".$logicalmode." ")."(".implode(" like ".$this->db->qstr("%".$key."%")." OR ", $searchFields)." like ".$this->db->qstr("%".$key."%").")";
					}
				}
			}

			// Check to see if the search has been restricted to a particular sub-tree in
			// the folder hierarchy.
			$searchFolder = "";
			if ($startFolder) {
				$searchFolder = "`tblFolders`.`folderList` LIKE '%:".$startFolder->getID().":%'";
			}

			// Check to see if the search has been restricted to a particular
			// document owner.
			$searchOwner = "";
			if ($owner) {
				if(is_array($owner)) {
					$ownerids = array();
					foreach($owner as $o)
						$ownerids[] = $o->getID();
					if($ownerids)
						$searchOwner = "`tblFolders`.`owner` IN (".implode(',', $ownerids).")";
				} else {
					$searchOwner = "`tblFolders`.`owner` = '".$owner->getId()."'";
				}
			}

			// Check to see if the search has been restricted to a particular
			// attribute.
			$searchAttributes = array();
			if ($attributes) {
				foreach($attributes as $attrdefid=>$attribute) {
					if($attribute) {
						$attrdef = $this->getAttributeDefinition($attrdefid);
						if($attrdef->getObjType() == SeedDMS_Core_AttributeDefinition::objtype_folder || $attrdef->getObjType() == SeedDMS_Core_AttributeDefinition::objtype_all) {
							if($valueset = $attrdef->getValueSet()) {
								if($attrdef->getMultipleValues()) {
									if(is_string($attribute))
										$attribute = array($attribute);
									$searchAttributes[] = "EXISTS (SELECT NULL FROM `tblFolderAttributes` WHERE `tblFolderAttributes`.`attrdef`=".$attrdefid." AND (`tblFolderAttributes`.`value` like '%".$valueset[0].implode("%' OR `tblFolderAttributes`.`value` like '%".$valueset[0], $attribute)."%') AND `tblFolderAttributes`.`folder`=`tblFolders`.`id`)";
								} else
									$searchAttributes[] = "EXISTS (SELECT NULL FROM `tblFolderAttributes` WHERE `tblFolderAttributes`.`attrdef`=".$attrdefid." AND `tblFolderAttributes`.`value`='".$attribute."' AND `tblFolderAttributes`.`folder`=`tblFolders`.`id`)";
							} else
								$searchAttributes[] = "EXISTS (SELECT NULL FROM `tblFolderAttributes` WHERE `tblFolderAttributes`.`attrdef`=".$attrdefid." AND `tblFolderAttributes`.`value` like '%".$attribute."%' AND `tblFolderAttributes`.`folder`=`tblFolders`.`id`)";
						}
					}
				}
			}

			// Is the search restricted to documents created between two specific dates?
			$searchCreateDate = "";
			if ($creationstartdate) {
				$startdate = SeedDMS_Core_DMS::makeTimeStamp($creationstartdate['hour'], $creationstartdate['minute'], $creationstartdate['second'], $creationstartdate['year'], $creationstartdate["month"], $creationstartdate["day"]);
				if ($startdate) {
					$searchCreateDate .= "`tblFolders`.`date` >= ".$startdate;
				}
			}
			if ($creationenddate) {
				$stopdate = SeedDMS_Core_DMS::makeTimeStamp($creationenddate['hour'], $creationstartdate['minute'], $creationstartdate['second'], $creationenddate["year"], $creationenddate["month"], $creationenddate["day"]);
				if ($stopdate) {
					/** @noinspection PhpUndefinedVariableInspection */
					if($startdate)
						$searchCreateDate .= " AND ";
					$searchCreateDate .= "`tblFolders`.`date` <= ".$stopdate;
				}
			}

			$searchQuery = "FROM ".$classname::getSearchTables()." WHERE 1=1";

			if (strlen($searchKey)>0) {
				$searchQuery .= " AND (".$searchKey.")";
			}
			if (strlen($searchFolder)>0) {
				$searchQuery .= " AND ".$searchFolder;
			}
			if (strlen($searchOwner)>0) {
				$searchQuery .= " AND (".$searchOwner.")";
			}
			if (strlen($searchCreateDate)>0) {
				$searchQuery .= " AND (".$searchCreateDate.")";
			}
			if ($searchAttributes) {
				$searchQuery .= " AND (".implode(" AND ", $searchAttributes).")";
			}

			/* Do not search for folders if not at least a search for a key,
			 * an owner, or creation date is requested.
			 */
			if($searchKey || $searchOwner || $searchCreateDate || $searchAttributes) {
				// Count the number of rows that the search will produce.
				$resArr = $this->db->getResultArray("SELECT COUNT(*) AS num FROM (SELECT DISTINCT `tblFolders`.id ".$searchQuery.") a");
				if ($resArr && isset($resArr[0]) && is_numeric($resArr[0]["num"]) && $resArr[0]["num"]>0) {
					$totalFolders = (integer)$resArr[0]["num"];
				}

				// If there are no results from the count query, then there is no real need
				// to run the full query. TODO: re-structure code to by-pass additional
				// queries when no initial results are found.

				// Only search if the offset is not beyond the number of folders
				if($totalFolders > $offset) {
					// Prepare the complete search query, including the LIMIT clause.
					$searchQuery = "SELECT DISTINCT `tblFolders`.`id` ".$searchQuery." GROUP BY `tblFolders`.`id`";

					if($limit) {
						$searchQuery .= " LIMIT ".$limit." OFFSET ".$offset;
					}

					// Send the complete search query to the database.
					$resArr = $this->db->getResultArray($searchQuery);
				} else {
					$resArr = array();
				}

				// ------------------- Ausgabe der Ergebnisse ----------------------------
				$numResults = count($resArr);
				if ($numResults == 0) {
					$folderresult = array('totalFolders'=>$totalFolders, 'folders'=>array());
				} else {
					foreach ($resArr as $folderArr) {
						$folders[] = $this->getFolder($folderArr['id']);
					}
					/** @noinspection PhpUndefinedVariableInspection */
					$folderresult = array('totalFolders'=>$totalFolders, 'folders'=>$folders);
				}
			} else {
				$folderresult = array('totalFolders'=>0, 'folders'=>array());
			}
		} else {
			$folderresult = array('totalFolders'=>0, 'folders'=>array());
		}

		/*--------- Do it all over again for documents -------------*/

		$totalDocs = 0;
		if($mode & 0x1) {
			$searchKey = "";

			$classname = $this->classnames['document'];
			$searchFields = $classname::getSearchFields($this, $searchin);

			if (count($searchFields)>0) {
				foreach ($tkeys as $key) {
					$key = trim($key);
					if (strlen($key)>0) {
						$searchKey = (strlen($searchKey)==0 ? "" : $searchKey." ".$logicalmode." ")."(".implode(" like ".$this->db->qstr("%".$key."%")." OR ", $searchFields)." like ".$this->db->qstr("%".$key."%").")";
					}
				}
			}

			// Check to see if the search has been restricted to a particular sub-tree in
			// the folder hierarchy.
			$searchFolder = "";
			if ($startFolder) {
				$searchFolder = "`tblDocuments`.`folderList` LIKE '%:".$startFolder->getID().":%'";
			}

			// Check to see if the search has been restricted to a particular
			// document owner.
			$searchOwner = "";
			if ($owner) {
				if(is_array($owner)) {
					$ownerids = array();
					foreach($owner as $o)
						$ownerids[] = $o->getID();
					if($ownerids)
						$searchOwner = "`tblDocuments`.`owner` IN (".implode(',', $ownerids).")";
				} else {
					$searchOwner = "`tblDocuments`.`owner` = '".$owner->getId()."'";
				}
			}

			// Check to see if the search has been restricted to a particular
			// document category.
			$searchCategories = "";
			if ($categories) {
				$catids = array();
				foreach($categories as $category)
					$catids[] = $category->getId();
				$searchCategories = "`tblDocumentCategory`.`categoryID` in (".implode(',', $catids).")";
			}

			// Check to see if the search has been restricted to a particular
			// attribute.
			$searchAttributes = array();
			if ($attributes) {
				foreach($attributes as $attrdefid=>$attribute) {
					if($attribute) {
						$attrdef = $this->getAttributeDefinition($attrdefid);
						if($attrdef->getObjType() == SeedDMS_Core_AttributeDefinition::objtype_document || $attrdef->getObjType() == SeedDMS_Core_AttributeDefinition::objtype_all) {
							if($valueset = $attrdef->getValueSet()) {
								if($attrdef->getMultipleValues()) {
									if(is_string($attribute))
										$attribute = array($attribute);
									$searchAttributes[] = "EXISTS (SELECT NULL FROM `tblDocumentAttributes` WHERE `tblDocumentAttributes`.`attrdef`=".$attrdefid." AND (`tblDocumentAttributes`.`value` like '%".$valueset[0].implode("%' OR `tblDocumentAttributes`.`value` like '%".$valueset[0], $attribute)."%') AND `tblDocumentAttributes`.`document` = `tblDocuments`.`id`)";
								} else
									$searchAttributes[] = "EXISTS (SELECT NULL FROM `tblDocumentAttributes` WHERE `tblDocumentAttributes`.`attrdef`=".$attrdefid." AND `tblDocumentAttributes`.`value`='".$attribute."' AND `tblDocumentAttributes`.`document` = `tblDocuments`.`id`)";
							} else
								$searchAttributes[] = "EXISTS (SELECT NULL FROM `tblDocumentAttributes` WHERE `tblDocumentAttributes`.`attrdef`=".$attrdefid." AND `tblDocumentAttributes`.`value` like '%".$attribute."%' AND `tblDocumentAttributes`.`document` = `tblDocuments`.`id`)";
						} elseif($attrdef->getObjType() == SeedDMS_Core_AttributeDefinition::objtype_documentcontent) {
							if($attrdef->getValueSet()) {
								if($attrdef->getMultipleValues()) {
									/** @noinspection PhpUndefinedVariableInspection */
									if(is_string($attribute))
										$attribute = array($attribute);
									$searchAttributes[] = "EXISTS (SELECT NULL FROM `tblDocumentContentAttributes` WHERE `tblDocumentContentAttributes`.`attrdef`=".$attrdefid." AND (`tblDocumentContentAttributes`.`value` like '%".$valueset[0].implode("%' OR `tblDocumentContentAttributes`.`value` like '%".$valueset[0], $attribute)."%') AND `tblDocumentContentAttributes`.`document` = `tblDocumentContent`.`id`)";
								} else {
									$searchAttributes[] = "EXISTS (SELECT NULL FROM `tblDocumentContentAttributes` WHERE `tblDocumentContentAttributes`.`attrdef`=".$attrdefid." AND `tblDocumentContentAttributes`.`value`='".$attribute."' AND `tblDocumentContentAttributes`.content = `tblDocumentContent`.id)";
								}
							} else
								$searchAttributes[] = "EXISTS (SELECT NULL FROM `tblDocumentContentAttributes` WHERE `tblDocumentContentAttributes`.`attrdef`=".$attrdefid." AND `tblDocumentContentAttributes`.`value` like '%".$attribute."%' AND `tblDocumentContentAttributes`.content = `tblDocumentContent`.id)";
						}
					}
				}
			}

			// Is the search restricted to documents created between two specific dates?
			$searchCreateDate = "";
			if ($creationstartdate) {
				$startdate = SeedDMS_Core_DMS::makeTimeStamp($creationstartdate['hour'], $creationstartdate['minute'], $creationstartdate['second'], $creationstartdate['year'], $creationstartdate["month"], $creationstartdate["day"]);
				if ($startdate) {
					$searchCreateDate .= "`tblDocuments`.`date` >= ".$startdate;
				}
			}
			if ($creationenddate) {
				$stopdate = SeedDMS_Core_DMS::makeTimeStamp($creationenddate['hour'], $creationenddate['minute'], $creationenddate['second'], $creationenddate["year"], $creationenddate["month"], $creationenddate["day"]);
				if ($stopdate) {
					if($searchCreateDate)
						$searchCreateDate .= " AND ";
					$searchCreateDate .= "`tblDocuments`.`date` <= ".$stopdate;
				}
			}
			if ($modificationstartdate) {
				$startdate = SeedDMS_Core_DMS::makeTimeStamp($modificationstartdate['hour'], $modificationstartdate['minute'], $modificationstartdate['second'], $modificationstartdate['year'], $modificationstartdate["month"], $modificationstartdate["day"]);
				if ($startdate) {
					if($searchCreateDate)
						$searchCreateDate .= " AND ";
					$searchCreateDate .= "`tblDocumentContent`.`date` >= ".$startdate;
				}
			}
			if ($modificationenddate) {
				$stopdate = SeedDMS_Core_DMS::makeTimeStamp($modificationenddate['hour'], $modificationenddate['minute'], $modificationenddate['second'], $modificationenddate["year"], $modificationenddate["month"], $modificationenddate["day"]);
				if ($stopdate) {
					if($searchCreateDate)
						$searchCreateDate .= " AND ";
					$searchCreateDate .= "`tblDocumentContent`.`date` <= ".$stopdate;
				}
			}
			$searchExpirationDate = '';
			if ($expirationstartdate) {
				$startdate = SeedDMS_Core_DMS::makeTimeStamp($expirationstartdate['hour'], $expirationstartdate['minute'], $expirationstartdate['second'], $expirationstartdate['year'], $expirationstartdate["month"], $expirationstartdate["day"]);
				if ($startdate) {
					if($searchExpirationDate)
						$searchExpirationDate .= " AND ";
					$searchExpirationDate .= "`tblDocuments`.`expires` >= ".$startdate;
				}
			}
			if ($expirationenddate) {
				$stopdate = SeedDMS_Core_DMS::makeTimeStamp($expirationenddate['hour'], $expirationenddate['minute'], $expirationenddate['second'], $expirationenddate["year"], $expirationenddate["month"], $expirationenddate["day"]);
				if ($stopdate) {
					if($searchExpirationDate)
						$searchExpirationDate .= " AND ";
					$searchExpirationDate .= "`tblDocuments`.`expires` <= ".$stopdate;
				}
			}

			// ---------------------- Suche starten ----------------------------------

			//
			// Construct the SQL query that will be used to search the database.
			//

			if (!$this->db->createTemporaryTable("ttcontentid") || !$this->db->createTemporaryTable("ttstatid")) {
				return false;
			}

			$searchQuery = "FROM `tblDocuments` ".
				"LEFT JOIN `tblDocumentContent` ON `tblDocuments`.`id` = `tblDocumentContent`.`document` ".
				"LEFT JOIN `tblDocumentAttributes` ON `tblDocuments`.`id` = `tblDocumentAttributes`.`document` ".
				"LEFT JOIN `tblDocumentContentAttributes` ON `tblDocumentContent`.`id` = `tblDocumentContentAttributes`.`content` ".
				"LEFT JOIN `tblDocumentStatus` ON `tblDocumentStatus`.`documentID` = `tblDocumentContent`.`document` ".
				"LEFT JOIN `ttstatid` ON `ttstatid`.`statusID` = `tblDocumentStatus`.`statusID` ".
				"LEFT JOIN `tblDocumentStatusLog` ON `tblDocumentStatusLog`.`statusLogID` = `ttstatid`.`maxLogID` ".
				"LEFT JOIN `ttcontentid` ON `ttcontentid`.`maxVersion` = `tblDocumentStatus`.`version` AND `ttcontentid`.`document` = `tblDocumentStatus`.`documentID` ".
				"LEFT JOIN `tblDocumentLocks` ON `tblDocuments`.`id`=`tblDocumentLocks`.`document` ".
				"LEFT JOIN `tblDocumentCategory` ON `tblDocuments`.`id`=`tblDocumentCategory`.`documentID` ".
				"WHERE ".
				// "`ttstatid`.`maxLogID`=`tblDocumentStatusLog`.`statusLogID` AND ".
				"`ttcontentid`.`maxVersion` = `tblDocumentContent`.`version`";

			if (strlen($searchKey)>0) {
				$searchQuery .= " AND (".$searchKey.")";
			}
			if (strlen($searchFolder)>0) {
				$searchQuery .= " AND ".$searchFolder;
			}
			if (strlen($searchOwner)>0) {
				$searchQuery .= " AND (".$searchOwner.")";
			}
			if (strlen($searchCategories)>0) {
				$searchQuery .= " AND (".$searchCategories.")";
			}
			if (strlen($searchCreateDate)>0) {
				$searchQuery .= " AND (".$searchCreateDate.")";
			}
			if (strlen($searchExpirationDate)>0) {
				$searchQuery .= " AND (".$searchExpirationDate.")";
			}
			if ($searchAttributes) {
				$searchQuery .= " AND (".implode(" AND ", $searchAttributes).")";
			}

			// status
			if ($status) {
				$searchQuery .= " AND `tblDocumentStatusLog`.`status` IN (".implode(',', $status).")";
			}

			if($searchKey || $searchOwner || $searchCategories || $searchCreateDate || $searchExpirationDate || $searchAttributes || $status) {
				// Count the number of rows that the search will produce.
				$resArr = $this->db->getResultArray("SELECT COUNT(*) AS num FROM (SELECT DISTINCT `tblDocuments`.`id` ".$searchQuery.") a");
				$totalDocs = 0;
				if (is_numeric($resArr[0]["num"]) && $resArr[0]["num"]>0) {
					$totalDocs = (integer)$resArr[0]["num"];
				}

				// If there are no results from the count query, then there is no real need
				// to run the full query. TODO: re-structure code to by-pass additional
				// queries when no initial results are found.

				// Prepare the complete search query, including the LIMIT clause.
				$searchQuery = "SELECT DISTINCT `tblDocuments`.*, ".
					"`tblDocumentContent`.`version`, ".
					"`tblDocumentStatusLog`.`status`, `tblDocumentLocks`.`userID` as `lockUser` ".$searchQuery;

				// calculate the remaining entrїes of the current page
				// If page is not full yet, get remaining entries
				if($limit) {
					$remain = $limit - count($folderresult['folders']);
					if($remain) {
						if($remain == $limit)
							$offset -= $totalFolders;
						else
							$offset = 0;
						if($limit)
							$searchQuery .= " LIMIT ".$limit." OFFSET ".$offset;

						// Send the complete search query to the database.
						$resArr = $this->db->getResultArray($searchQuery);
						if($resArr === false)
							return false;
					} else {
						$resArr = array();
					}
				} else {
					// Send the complete search query to the database.
					$resArr = $this->db->getResultArray($searchQuery);
					if($resArr === false)
						return false;
					}

				// ------------------- Ausgabe der Ergebnisse ----------------------------
				$numResults = count($resArr);
				if ($numResults == 0) {
					$docresult = array('totalDocs'=>$totalDocs, 'docs'=>array());
				} else {
					foreach ($resArr as $docArr) {
						$docs[] = $this->getDocument($docArr['id']);
					}
					/** @noinspection PhpUndefinedVariableInspection */
					$docresult = array('totalDocs'=>$totalDocs, 'docs'=>$docs);
				}
			} else {
				$docresult = array('totalDocs'=>0, 'docs'=>array());
			}
		} else {
			$docresult = array('totalDocs'=>0, 'docs'=>array());
		}

		if($limit) {
			$totalPages = (integer)(($totalDocs+$totalFolders)/$limit);
			if ((($totalDocs+$totalFolders)%$limit) > 0) {
				$totalPages++;
			}
		} else {
			$totalPages = 1;
		}

		return array_merge($docresult, $folderresult, array('totalPages'=>$totalPages));
	} /* }}} */

	/**
	 * Return a folder by its id
	 *
	 * This function retrieves a folder from the database by its id.
	 *
	 * @param integer $id internal id of folder
	 * @return SeedDMS_Core_Folder instance of SeedDMS_Core_Folder or false
	 */
	function getFolder($id) { /* {{{ */
		$classname = $this->classnames['folder'];
		return $classname::getInstance($id, $this);
	} /* }}} */
        
        function getAllFolders() {
            $classname = $this->classnames['folder'];
            $queryStr = "SELECT `id` FROM `tblFolders`";
            $resArr = $this->db->getResultArray($queryStr);
            if (is_bool($resArr) && $resArr == false)
		return false;
            for ($i = 0; $i < count($resArr); $i++) {
		$folder=$classname::getInstance($resArr[$i]['id'], $this);
		$folders[]=array($resArr[$i]['id'], $folder->getFolderPathPlain());
            }
            return $folders;
        }
        
	/**
	 * Return a folder by its name
	 *
	 * This function retrieves a folder from the database by its name. The
	 * search covers the whole database. If
	 * the parameter $folder is not null, it will search for the name
	 * only within this parent folder. It will not be done recursively.
	 *
	 * @param string $name name of the folder
	 * @param SeedDMS_Core_Folder $folder parent folder
	 * @return SeedDMS_Core_Folder|boolean found folder or false
	 */
	function getFolderByName($name, $folder=null) { /* {{{ */
		if (!$name) return false;

		$queryStr = "SELECT * FROM `tblFolders` WHERE `name` = " . $this->db->qstr($name);
		if($folder)
			$queryStr .= " AND `parent` = ". $folder->getID();
		$queryStr .= " LIMIT 1";
		$resArr = $this->db->getResultArray($queryStr);

		if (is_bool($resArr) && $resArr == false)
			return false;

		if(!$resArr)
			return false;

		$resArr = $resArr[0];
		/** @var SeedDMS_Core_Folder $folder */
		$folder = new $this->classnames['folder']($resArr["id"], $resArr["name"], $resArr["parent"], $resArr["comment"], $resArr["date"], $resArr["owner"], $resArr["inheritAccess"], $resArr["defaultAccess"], $resArr["sequence"]);
		$folder->setDMS($this);
		return $folder;
	} /* }}} */

	/**
	 * Returns a list of folders and error message not linked in the tree
	 *
	 * This function checks all folders in the database.
	 *
	 * @return array|bool
	 */
	function checkFolders() { /* {{{ */
		$queryStr = "SELECT * FROM `tblFolders`";
		$resArr = $this->db->getResultArray($queryStr);

		if (is_bool($resArr) && $resArr === false)
			return false;

		$cache = array();
		foreach($resArr as $rec) {
			$cache[$rec['id']] = array('name'=>$rec['name'], 'parent'=>$rec['parent'], 'folderList'=>$rec['folderList']);
		}
		$errors = array();
		foreach($cache as $id=>$rec) {
			if(!array_key_exists($rec['parent'], $cache) && $rec['parent'] != 0) {
				$errors[$id] = array('id'=>$id, 'name'=>$rec['name'], 'parent'=>$rec['parent'], 'msg'=>'Missing parent');
			} else {
				$tmparr = explode(':', $rec['folderList']);
				array_shift($tmparr);
				if(count($tmparr) != count(array_unique($tmparr))) {
					$errors[$id] = array('id'=>$id, 'name'=>$rec['name'], 'parent'=>$rec['parent'], 'msg'=>'Duplicate entry in folder list ('.$rec['folderList'].')');
				}
			}
		}

		return $errors;
	} /* }}} */

	/**
	 * Returns a list of documents and error message not linked in the tree
	 *
	 * This function checks all documents in the database.
	 *
	 * @return array|bool
	 */
	function checkDocuments() { /* {{{ */
		$queryStr = "SELECT * FROM `tblFolders`";
		$resArr = $this->db->getResultArray($queryStr);

		if (is_bool($resArr) && $resArr === false)
			return false;

		$fcache = array();
		foreach($resArr as $rec) {
			$fcache[$rec['id']] = array('name'=>$rec['name'], 'parent'=>$rec['parent'], 'folderList'=>$rec['folderList']);
		}

		$queryStr = "SELECT * FROM `tblDocuments`";
		$resArr = $this->db->getResultArray($queryStr);

		if (is_bool($resArr) && $resArr === false)
			return false;

		$dcache = array();
		foreach($resArr as $rec) {
			$dcache[$rec['id']] = array('name'=>$rec['name'], 'parent'=>$rec['folder'], 'folderList'=>$rec['folderList']);
		}
		$errors = array();
		foreach($dcache as $id=>$rec) {
			if(!array_key_exists($rec['parent'], $fcache) && $rec['parent'] != 0) {
				$errors[$id] = array('id'=>$id, 'name'=>$rec['name'], 'parent'=>$rec['parent'], 'msg'=>'Missing parent');
			} else {
				$tmparr = explode(':', $rec['folderList']);
				array_shift($tmparr);
				if(count($tmparr) != count(array_unique($tmparr))) {
					$errors[$id] = array('id'=>$id, 'name'=>$rec['name'], 'parent'=>$rec['parent'], 'msg'=>'Duplicate entry in folder list ('.$rec['folderList'].'');
				}
			}
		}

		return $errors;
	} /* }}} */

	/**
	 * Return a user by its id
	 *
	 * This function retrieves a user from the database by its id.
	 *
	 * @param integer $id internal id of user
	 * @return SeedDMS_Core_User|boolean instance of {@link SeedDMS_Core_User} or false
	 */
	function getUser($id) { /* {{{ */
		$classname = $this->classnames['user'];
		return $classname::getInstance($id, $this);
	} /* }}} */

	/**
	 * Return a user by its login
	 *
	 * This function retrieves a user from the database by its login.
	 * If the second optional parameter $email is not empty, the user must
	 * also have the given email.
	 *
	 * @param string $login internal login of user
	 * @param string $email email of user
	 * @return object instance of {@link SeedDMS_Core_User} or false
	 */
	function getUserByLogin($login, $email='') { /* {{{ */
		$classname = $this->classnames['user'];
		return $classname::getInstance($login, $this, 'name', $email);
	} /* }}} */

	/**
	 * Return a user by its email
	 *
	 * This function retrieves a user from the database by its email.
	 * It is needed when the user requests a new password.
	 *
	 * @param integer $email email address of user
	 * @return object instance of {@link SeedDMS_Core_User} or false
	 */
	function getUserByEmail($email) { /* {{{ */
		$classname = $this->classnames['user'];
		return $classname::getInstance($email, $this, 'email');
	} /* }}} */

	/**
	 * Return list of all users
	 *
	 * @param string $orderby
	 * @return array of instances of <a href='psi_element://SeedDMS_Core_User'>SeedDMS_Core_User</a> or false
	 * or false
	 */
	function getAllUsers($orderby = '') { /* {{{ */
		$classname = $this->classnames['user'];
		return $classname::getAllInstances($orderby, $this);
	} /* }}} */
        
        function getAllAdmins(){
            $users = $this->getAllUsers();
            foreach ($users as $key => $user) {
                if($user->isAdmin()){
                    $admins[]=$user;
                }
            }
            return $admins;
        }
	/**
	 * Add a new user
	 *
	 * @param string $login login name
	 * @param string $pwd password of new user
	 * @param $fullName
	 * @param string $email Email of new user
	 * @param string $language language of new user
	 * @param $theme
	 * @param string $comment comment of new user
	 * @param int|string $role role of new user (can be 0=normal, 1=admin, 2=guest)
	 * @param integer $isHidden hide user in all lists, if this is set login
	 *        is still allowed
	 * @param integer $isDisabled disable user and prevent login
	 * @param string $pwdexpiration
	 * @param int $quota
	 * @param null $homefolder
	 * @return bool|SeedDMS_Core_User
	 */
	function addUser($login, $pwd, $fullName, $email, $language, $theme, $comment, $role='0', $isHidden=0, $isDisabled=0, $pwdexpiration='', $quota=0, $homefolder=null) { /* {{{ */
		$db = $this->db;
		if (is_object($this->getUserByLogin($login))) {
			return false;
		}
		if($role == '')
			$role = '0';
		if(trim($pwdexpiration) == '' || trim($pwdexpiration) == 'never') {
			$pwdexpiration = 'NULL';
		} elseif(trim($pwdexpiration) == 'now') {
			$pwdexpiration = $db->qstr(date('Y-m-d H:i:s'));
		} else {
			$pwdexpiration = $db->qstr($pwdexpiration);
		}
		$queryStr = "INSERT INTO `tblUsers` (`login`, `pwd`, `fullName`, `email`, `language`, `theme`, `comment`, `role`, `hidden`, `disabled`, `pwdExpiration`, `quota`, `homefolder`) VALUES (".$db->qstr($login).", ".$db->qstr($pwd).", ".$db->qstr($fullName).", ".$db->qstr($email).", '".$language."', '".$theme."', ".$db->qstr($comment).", '".intval($role)."', '".intval($isHidden)."', '".intval($isDisabled)."', ".$pwdexpiration.", '".intval($quota)."', ".($homefolder ? intval($homefolder) : "NULL").")";
		$res = $this->db->getResult($queryStr);
		if (!$res)
			return false;

		$user = $this->getUser($this->db->getInsertID('tblUsers'));
                
                $queryStr = "INSERT INTO `tblUserReminder` (`userid`) VALUES (".$user->getID().")";
		$res1 = $this->db->getResult($queryStr);
                if (!$res1)
			return false;
                
		/* Check if 'onPostAddUser' callback is set */
		if(isset($this->_dms->callbacks['onPostAddUser'])) {
			foreach($this->_dms->callbacks['onPostUser'] as $callback) {
				/** @noinspection PhpStatementHasEmptyBodyInspection */
				if(!call_user_func($callback[0], $callback[1], $user)) {
				}
			}
		}

		return $user;
	} /* }}} */

	/**
	 * Get a group by its id
	 *
	 * @param integer $id id of group
	 * @return SeedDMS_Core_Group|boolean group or false if no group was found
	 */
	function getGroup($id) { /* {{{ */
		$classname = $this->classnames['group'];
		return $classname::getInstance($id, $this, '');
	} /* }}} */

	/**
	 * Get a group by its name
	 *
	 * @param string $name name of group
	 * @return SeedDMS_Core_Group|boolean group or false if no group was found
	 */
	function getGroupByName($name) { /* {{{ */
		$classname = $this->classnames['group'];
		return $classname::getInstance($name, $this, 'name');
	} /* }}} */

	/**
	 * Get a list of all groups
	 *
	 * @return SeedDMS_Core_Group[] array of instances of {@link SeedDMS_Core_Group}
	 */
	function getAllGroups() { /* {{{ */
		$classname = $this->classnames['group'];
		return $classname::getAllInstances('name', $this);
	} /* }}} */
        
        /**
	 * Get a list of accesses
	 *
	 * @return SeedDMS_Core_Access[] array of instances of {@link SeedDMS_Core_Access}
	 */
	// Glenn edited
	function getAllAccess($orderby = '')
	{ /* {{{ */
		$classname = $this->classnames['access'];
		return $classname::getAllInstances('name', $this);
	} /* }}} */

	/**
	 * Get a access by its name
	 *
	 * @param string $name name of access
	 * @return SeedDMS_Core_Access|boolean access or false if no access was found
	 */
	// Glenn edited
	function getAccessByName($name)
	{ /* {{{ */
		$classname = $this->classnames['access'];
		return $classname::getInstance($name, $this, 'name');
	} /* }}} */

	// Glenn edited on 13 April, 2022
	function dropAccessDecision($mode)
	{
		$query = "SELECT * FROM `tblACLs` WHERE `mode` = " . $mode;
		$result = $this->db->getResultArray($query);
		if ($result) {
			return false;
		}

		return true;
	}

	// Glenn edited - get all privileges
	function getPrivilegesByMode($mode)
	{
		$queryStr1 = "SELECT `privileges` FROM `tblAccessMode` WHERE `mode` = " . (int)$mode;
		$result1 = $this->db->getResultArray($queryStr1);
		return $result1[0]['privileges'];
	}

	// Glenn edited - Get the second highest mode
	function getSecondHighestMode()
	{
		$query = "SELECT `mode` FROM `tblAccessMode` ORDER BY `mode` DESC LIMIT 1,1";
		$result = $this->db->getResultArray($query);
		return $result[0]['mode'];
	}

	// Glenn edited - Get the highest mode
	function getHighestMode()
	{
		$query = "SELECT `mode` FROM `tblAccessMode` ORDER BY `mode` DESC LIMIT 0,1";
		$result = $this->db->getResultArray($query);
		return $result[0]['mode'];
	}

	// Glenn edited
	function getEmptyPrivileges()
	{
		// the access with mode=1 is ‘No Access’, which is defined in seedDMS system
		// the values of all privileges is 0 when the access is ‘No Access’
		$queryStr = "SELECT `privileges` FROM `tblAccessMode` WHERE `mode`=1";
		$resArr = $this->db->getResultArray($queryStr);
		if ((is_bool($resArr) && !$resArr) || (count($resArr) != 1))
			return false;
		return $resArr[0]['privileges']; // eg.'{"add_document": 0,  "add_subfolder":0}'
	}

	// Get the privileges which is the access / permission is fixed
	// Glenn edited
	function getFixPrivileges($accessName)
	{
		$queryStr = "SELECT `privileges` FROM `tblAccessMode` WHERE `name` = " . "'" . $accessName . "'";
		$resArr = $this->db->getResultArray($queryStr);
		if ((is_bool($resArr) && !$resArr) || (count($resArr) != 1))
			return false;
		return $resArr[0]['privileges'];
	}

	// Get all accesses information
	function getAllAccesses()
	{
		$queryStr = "SELECT * FROM `tblAccessMode` ORDER BY mode ASC";
		$resArr = $this->db->getResultArray($queryStr);
		if (!$resArr) {
			return false;
		}
		return $resArr;
	}

	// Get available privileges when add a new mode
	// Glenn edited
	function getAvailablePrivileges($originalMode, $newMode)
	{
		// 3 (After Read) means that the privileges that can be selected are between Read and Read-Write
		// Thus, we get privileges of Read as default for admin
		$queryStr1 = "SELECT `privileges` FROM `tblAccessMode` WHERE `mode`= " . $originalMode;
		$resArr1 = $this->db->getResultArray($queryStr1);
		if ((is_bool($resArr1) && !$resArr1) || (count($resArr1) != 1)) {
			return false;
		}
		// Array type
		$originalPrivileges = json_decode(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $resArr1[0]['privileges']), true);

		$queryStr2 = "SELECT `privileges` FROM `tblAccessMode` WHERE `mode`= " . $newMode;
		$resArr2 = $this->db->getResultArray($queryStr2);
		if ((is_bool($resArr2) && !$resArr2) || (count($resArr2) != 1)) {
			return false;
		}
		// Array type
		$nextPrivileges = json_decode(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $resArr2[0]['privileges']), true);

		// Remove unselectable privileges
		foreach ($nextPrivileges as $nextKey => $nextVal) {
			if ($nextVal == 0) {
				unset($nextPrivileges[$nextKey]);
			}
		}

		// Set all selectable privileges to 0
		foreach ($nextPrivileges as $nextKey => $nextVal) {
			$nextPrivileges[$nextKey] = 0;
		}

		// Set original mode's privileges to be 1
		foreach ($nextPrivileges as $nextKey => $nextVal) {
			foreach ($originalPrivileges as $originalKey => $originalVal) {
				if ($nextKey === $originalKey) {
					if ($originalVal === 1) {
						$nextPrivileges[$nextKey] = 1;
					}
				}
			}
		}

		return $nextPrivileges;
	}

	// Add new permission / access operation
	// Glenn edited
	function addNewAccess($newName, $newComment, $newMode, $privileges)
	{
		// Change selected privilege key-value to 1
		foreach ($privileges as &$privilegesVal) {
			$privilegesVal = 1;
		}
		unset($privilegesVal);

		// Get original privileges
		$queryStr = "SELECT `privileges` FROM `tblAccessMode` WHERE `mode` = " . $newMode;
		$originalPrivileges = $this->db->getResultArray($queryStr);
		if (!$originalPrivileges) {
			return false;
		}
		$originalPrivileges = json_decode(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $originalPrivileges[0]['privileges']), true);

		// Get the total privileges that can be selected
		foreach ($originalPrivileges as $key => $val) {
			if ($val == 0) {
				unset($originalPrivileges[$key]);
			}
		}

		// Change privileges to 1
		foreach ($originalPrivileges as $key => $val) {
			if ($val == 1) {
				$originalPrivileges[$key] = 0;
			}
		}

		// Add selected to original privileges
		foreach ($originalPrivileges as $key => $val) {
			foreach ($privileges as $priKey => $priVal) {
				if ($priVal == 1) {
					$originalPrivileges[$priKey] = 1;
				}
			}
		}

		// Update old mode value
		$queryStr1 = "UPDATE `tblAccessMode` SET `mode` = `mode` + 1 WHERE `mode` >= " . $newMode;
		$result1 = $this->db->getResult($queryStr1);
		if (!$result1) {
			return false;
		}

		// New privileges
		$newPrivilegesObj = json_encode($originalPrivileges);

		// Add new access
		$newMode = (int) $newMode;
		$newName = (string) $newName;
		$newComment = (string) $newComment;
		$fix = 0;
		$queryStr2 = "INSERT INTO `tblAccessMode` (`name`, `mode`, `privileges`, `comment`, `fix`) " .
			"VALUES (" . "'" . $newName . "'" . ", " . $newMode . ", " . $this->db->qstr($newPrivilegesObj) . ", " . "'" . $newComment . "'" . ", " . $fix . ")";
		$result2 = $this->db->getResult($queryStr2);
		if (!$result2) {
			return false;
		}
	}

	// Glenn edited
	function folderGroupNameMode($userid, $groupid, $selectedMode, $folderid, $targetType)
	{
		$groupModeArr = [];
		$groupNameArr = [];

		$queryStr1 = "SELECT * FROM `tblGroupMembers` WHERE `userID` = " . $userid;
		$result1 = $this->db->getResultArray($queryStr1);
		if ($result1) {
			foreach ($result1 as $key) {
				// Find the mode corresponding to this group in this folder
				$queryStr2 = "SELECT `mode` FROM `tblACLs` WHERE `target` = " . $folderid . " AND `targetType` = " . $targetType . " AND `userID` = " . -1 . " AND `groupID` = " . $key['groupID'];
				$result2 = $this->db->getResultArray($queryStr2)[0]['mode'];

				// Find the access name
				$queryStr3 = "SELECT `name` FROM `tblAccessMode` WHERE `mode` = " . $result2;
				$result3 = $this->db->getResultArray($queryStr3)[0]['name'];
				array_push($groupModeArr, $result2 . ' ' . '(' . $result3 . ')');
			}

			// Find the correspoding group name
			foreach ($result1 as $key) {
				$queryStr4 = "SELECT `name` FROM `tblGroups` WHERE `id` = " . $key['groupID'];
				$result4 = $this->db->getResultArray($queryStr4)[0]['name'];
				array_push($groupNameArr, $result4);
			}

			$groupResult = array_combine($groupNameArr, $groupModeArr);
			return $groupResult;
		}

		return false;
	}

	// Glenn edited
	function folderUserAccessInfo($userid, $groupid, $selectedMode, $folderid, $targetType)
	{
		$userIDArr = [];
		$userModeArr = [];

		$queryStr1 = "SELECT `userID` FROM `tblGroupMembers` WHERE `groupID` = " . $groupid;
		$result1 = $this->db->getResultArray($queryStr1);
		if ($result1) {
			foreach ($result1 as $key) {
				// Find the mode corresponding to these user in this folder
				$queryStr2 = "SELECT `mode` FROM `tblACLs` WHERE `target` = " . $folderid . " AND `targetType` = " . $targetType . " AND `groupID` = " . -1 . " AND `userID` = " . $key['userID'];
				$result2 = $this->db->getResultArray($queryStr2)[0]['mode'];

				// Find the access name
				$queryStr3 = "SELECT `name` FROM `tblAccessMode` WHERE `mode` = " . $result2;
				$result3 = $this->db->getResultArray($queryStr3)[0]['name'];

				// Find the user name
				$queryStr4 = "SELECT `login` FROM `tblUsers` WHERE `id` = " . $key['userID'];
				$result4 = $this->db->getResultArray($queryStr4)[0]['login'];

				if ($result2 == null && $result3 == null || empty($result2) && empty($result3)) {
					array_push($userModeArr, $result4 . ':' . ' ' . 'null');
				} else {
					array_push($userModeArr, $result4 . ': ' . $result2 . ' ' . '(' . $result3 . ')');
				}

				array_push($userIDArr, $key['userID']);
			}

			$userResult = array_combine($userIDArr, $userModeArr);
			return $userResult;
		}

		return false;
	}

	// Glenn edited
	function getAccessnameByMode($mode)
	{
		$queryStr = "SELECT `name` FROM `tblAccessMode` WHERE `mode` = " . $mode;
		$result = $this->db->getResultArray($queryStr)[0]['name'];
		return $result;
	}

	// Glenn edited
	function getGroupnameById($groupid)
	{
		$queryStr = "SELECT `name` FROM `tblGroups` WHERE `id` = " . $groupid;
		$result = $this->db->getResultArray($queryStr)[0]['name'];
		return $result;
	}

	// Glenn edited
	function updateUserFolderMode($userid, $groupid, $selectedMode, $folderid, $targetType)
	{
		// Determine that if this user access exists
		$queryStr1 = "SELECT * FROM `tblACLs` WHERE `target` = " . $folderid . " AND `targetType` = " . $targetType . " AND `userID` = " . $userid . " AND `groupID` = " . $groupid;
		$result1 = $this->db->getResultArray($queryStr1);
		if ($result1) {
			$queryStr2 = "UPDATE `tblACLs` SET `mode` = " . $selectedMode . " WHERE `target` = " . $folderid . " AND `targetType` = " . $targetType . " AND `userID` = " . $userid . " AND `groupID` = " . $groupid;
			$result2 = $this->db->getResult($queryStr2);
		} else {
			$queryStr3 = "INSERT INTO `tblACLs` (`target`, `targetType`, `userID`, `groupID`, `mode`) VALUES (" . $folderid . ", " . $targetType . ", " . $userid . ", " . $groupid . ", " . $selectedMode . ")";
			$result3 = $this->db->getResult($queryStr3);
		}
	}

	// Glenn edited
	function updateGroupFolderMode($userid, $groupid, $selectedMode, $folderid, $targetType, $userChecked)
	{
		// Determine that if this group access exists
		$queryStr4 = "SELECT * FROM `tblACLs` WHERE `target` = " . $folderid . " AND `targetType` = " . $targetType . " AND `userID` = " . -1 . " AND `groupID` = " . $groupid;
		$result4 = $this->db->getResultArray($queryStr4);
		if ($result4) {
			// Update Group's access
			$queryStr = "UPDATE `tblACLs` SET `mode` = " . $selectedMode . " WHERE `target` = " . $folderid . " AND `targetType` = " . $targetType . " AND `userID` = " . -1 . " AND `groupID` = " . $groupid;
			$result = $this->db->getResult($queryStr);
		} else {
			$queryStr5 = "INSERT INTO `tblACLs` (`target`, `targetType`, `userID`, `groupID`, `mode`) VALUES (" . $folderid . ", " . $targetType . ", " . -1 . ", " . $groupid . ", " . $selectedMode . ")";
			$result5 = $this->db->getResult($queryStr5);
		}

		// Update selected user's access
		foreach ($userChecked as $value) {
			$queryStr1 = "SELECT * FROM `tblACLs` WHERE `target` = " . $folderid . " AND `targetType` = " . $targetType . " AND `userID` = " . $value . " AND `groupID` = " . -1;
			$result1 = $this->db->getResultArray($queryStr1);
			if ($result1) {
				$queryStr2 = "UPDATE `tblACLs` SET `mode` = " . $selectedMode . " WHERE `target` = " . $folderid . " AND `targetType` = " . $targetType . " AND `userID` = " . $value . " AND `groupID` = " . -1;
				$result2 = $this->db->getResult($queryStr2);
			} else {
				$queryStr3 = "INSERT INTO `tblACLs` (`target`, `targetType`, `userID`, `groupID`, `mode`) VALUES (" . $folderid . ", " . $targetType . ", " . $value . ", " . -1 . ", " . $selectedMode . ")";
				$result3 = $this->db->getResult($queryStr3);
			}
		}
	}

	// Glenn edited
	function deleteGroupAccessAndMemberAccess($userid, $groupid, $selectedMode, $folderid, $targetType)
	{
		// Delete group access
		$queryStr1 = "DELETE FROM `tblACLs` WHERE `target` = " . $folderid . " AND `targetType` = " . $targetType . " AND `userID` = " . $userid . " AND `groupID` = " . $groupid . " AND `mode` = " . $selectedMode;
		$result1 = $this->db->getResult($queryStr1);

		// Delete group members' access
		$queryStr2 = "SELECT `userID` FROM `tblGroupMembers` WHERE `groupID` = " . $groupid;
		$result2 = $this->db->getResultArray($queryStr2);
		$useridArr = $result2;
		foreach ($useridArr as $key) {
			$queryStr3 = "DELETE FROM `tblACLs` WHERE `target` = " . $folderid . " AND `targetType` = " . $targetType . " AND `userID` = " . $key['userID'] . " AND `groupID` = " . -1;
			$result3 = $this->db->getResult($queryStr3);
		}
	}

	// Glenn edited
	function deleteOneGroupAccess($userid, $groupid, $selectedMode, $folderid, $targetType)
	{
		// Delete group access
		$queryStr = "DELETE FROM `tblACLs` WHERE `target` = " . $folderid . " AND `targetType` = " . $targetType . " AND `userID` = " . $userid . " AND `groupID` = " . $groupid . " AND `mode` = " . $selectedMode;
		$result = $this->db->getResult($queryStr);
	}

	// Glenn edited
	function followGroupInfo($userid)
	{
		$groupNameArr = [];

		$queryStr1 = "SELECT `groupID` FROM `tblGroupMembers` WHERE `userID` = " . $userid . " AND `followGroup` = 1";
		$result1 = $this->db->getResultArray($queryStr1);
		if ($result1) {
			foreach ($result1 as $key => $value) {
				$queryStr2 = "SELECT `name` FROM `tblGroups` WHERE `id` = " . $value['groupID'];
				$result2 = $this->db->getResultArray($queryStr2);
				$groupName = $result2[0]['name'];
				array_push($groupNameArr, $groupName);
			}
			return $groupNameArr;
		} else {
			return null;
		}
	}

	/**
	 * When add user to group, save the user's base 
	 * folder access and get the higher priority folder access from group.
	 *
	 * @param string $userid
	 * @param object $collection
	 * @return null
	 */
	// Glenn edited
	function handleUserFollowGroupFolderAccess($userid, $collection)
	{
		$userModeCollection = [];

		// Get the user base folder access
		foreach ($collection as $key => $val) {
			$query1 = "SELECT `mode` FROM `tblACLs` WHERE `target` = " . $key . " AND `targetType` = 1 AND `userID` = " . $userid . " AND `groupID` = -1";
			$result1 = $this->db->getResultArray($query1);
			if ($result1) {
				$userModeCollection[$key] = $result1[0]['mode'];
			}
		}

		// If the user has base folder access, store it in the database
		if (count($userModeCollection) > 0) {
			foreach ($userModeCollection as $key => $val) {
				$query2 = "SELECT * FROM `tblFolderAccessBase` WHERE `userID` = " . $userid . " AND `folderID` = " . $key;
				$result2 = $this->db->getResultArray($query2);
				if (!$result2) {
					$query3 = "INSERT INTO `tblFolderAccessBase` (`userID`, `folderID`, `baseMode`) VALUES (" . $userid . ", " . $key . ", " . $val . ")";
					$result3 = $this->db->getResult($query3);
				} else {
					$query4 = "UPDATE `tblFolderAccessBase` SET `baseMode` = " . $val . " WHERE `userID` = " . $userid . " AND `folderID` = " . $key;
					$result4 = $this->db->getResult($query4);
				}
			}
		}

		// Check if the user has folder access which included in the collection
		foreach ($collection as $key => $val) {
			$query4 = "SELECT * FROM `tblACLs` WHERE `target` = " . $key . " AND `targetType` = 1 AND `userID` = " . $userid . " AND `groupID` = -1";
			$result4 = $this->db->getResultArray($query4);
			// If exist this record, then update. Otherwise, insert new one
			if ($result4) {
				$query5 = "UPDATE `tblACLs` SET `mode` = " . $val . ", `followGroup` = 1" . " WHERE `target` = " . $key . " AND `targetType` = 1 AND `userID` = " . $userid . " AND `groupID` = -1";
				$result5 = $this->db->getResult($query5);
			} else {
				$query6 = "INSERT INTO `tblACLs` (`target`, `targetType`, `userID`, `groupID`, `mode`, `followGroup`) VALUES (" . $key . ", " . 1 . ", " . $userid . ", " . -1 . ", " . $val . ", " . 1 . ")";
				$result6 = $this->db->getResult($query6);
			}
		}
	}

	/**
	 * When add user to group, save the user's base 
	 * document access and get the higher priority document access from group.
	 *
	 * @param string $userid
	 * @param object $collection
	 * @return null
	 */
	// Glenn edited on 9 May, 2022
	function handleUserFollowGroupDocumentAccess($userid, $collection)
	{
		$userModeCollection = [];

		// Get the user base document access
		foreach ($collection as $key => $val) {
			$query1 = "SELECT `mode` FROM `tblACLs` WHERE `target` = " . $key . " AND `targetType` = 2 AND `userID` = " . $userid . " AND `groupID` = -1";
			$result1 = $this->db->getResultArray($query1);
			if ($result1) {
				$userModeCollection[$key] = $result1[0]['mode'];
			}
		}

		// If the user has base document access, store it in the database
		if (count($userModeCollection) > 0) {
			foreach ($userModeCollection as $key => $val) {
				$query2 = "SELECT * FROM `tblDocumentAccessBase` WHERE `userID` = " . $userid . " AND `documentID` = " . $key;
				$result2 = $this->db->getResultArray($query2);
				if (!$result2) {
					$query3 = "INSERT INTO `tblDocumentAccessBase` (`userID`, `documentID`, `baseMode`) VALUES (" . $userid . ", " . $key . ", " . $val . ")";
					$result3 = $this->db->getResult($query3);
				} else {
					$query4 = "UPDATE `tblDocumentAccessBase` SET `baseMode` = " . $val . " WHERE `userID` = " . $userid . " AND `documentID` = " . $key;
					$result4 = $this->db->getResult($query4);
				}
			}
		}

		// Check if the user has document access which included in the collection
		foreach ($collection as $key => $val) {
			$query4 = "SELECT * FROM `tblACLs` WHERE `target` = " . $key . " AND `targetType` = 2 AND `userID` = " . $userid . " AND `groupID` = -1";
			$result4 = $this->db->getResultArray($query4);
			// If exist this record, then update. Otherwise, insert new one
			if ($result4) {
				$query5 = "UPDATE `tblACLs` SET `mode` = " . $val . ", `followGroup` = 1" . " WHERE `target` = " . $key . " AND `targetType` = 2 AND `userID` = " . $userid . " AND `groupID` = -1";
				$result5 = $this->db->getResult($query5);
			} else {
				$query6 = "INSERT INTO `tblACLs` (`target`, `targetType`, `userID`, `groupID`, `mode`, `followGroup`) VALUES (" . $key . ", " . 2 . ", " . $userid . ", " . -1 . ", " . $val . ", " . 1 . ")";
				$result6 = $this->db->getResult($query6);
			}
		}
	}

	/**
	 * When the user leaves the group, look for other groups with the higher-order 
	 * folder access, and restore the base folder access.
	 *
	 * @param string $userid
	 * @param object $collection
	 * @return null
	 */
	// Glenn edited
	function handleUserLeaveGroupAccess($userid, $folderCollection, $documentCollection)
	{
		$userAvailableFolderAccessCollection = [];
		$userAvailableDocumentAccessCollection = [];

		foreach ($folderCollection as $key => $val) {
			// Check if the user has folder access which included in the collection
			$query1 = "SELECT * FROM `tblACLs` WHERE `target` = " . $key . " AND `targetType` = 1 AND `userID` = " . $userid . " AND `groupID` = -1";
			$result1 = $this->db->getResultArray($query1);
			// If exist this record, then update. Otherwise, insert new one
			if ($result1) {
				$query2 = "UPDATE `tblACLs` SET `mode` = " . $val . ", `followGroup` = 1" . " WHERE `target` = " . $key . " AND `targetType` = 1 AND `userID` = " . $userid . " AND `groupID` = -1";
				$result2 = $this->db->getResult($query2);
			} else {
				$query3 = "INSERT INTO `tblACLs` (`target`, `targetType`, `userID`, `groupID`, `mode`, `followGroup`) VALUES (" . $key . ", " . 1 . ", " . $userid . ", " . -1 . ", " . $val . ", " . 1 . ")";
				$result3 = $this->db->getResult($query3);
			}
		}

		foreach ($documentCollection as $key => $val) {
			// Check if the user has document access which included in the collection
			$query8 = "SELECT * FROM `tblACLs` WHERE `target` = " . $key . " AND `targetType` = 2 AND `userID` = " . $userid . " AND `groupID` = -1";
			$result8 = $this->db->getResultArray($query8);
			// If exist this record, then update. Otherwise, insert new one
			if ($result8) {
				$query9 = "UPDATE `tblACLs` SET `mode` = " . $val . ", `followGroup` = 1" . " WHERE `target` = " . $key . " AND `targetType` = 2 AND `userID` = " . $userid . " AND `groupID` = -1";
				$result9 = $this->db->getResult($query9);
			} else {
				$query10 = "INSERT INTO `tblACLs` (`target`, `targetType`, `userID`, `groupID`, `mode`, `followGroup`) VALUES (" . $key . ", " . 2 . ", " . $userid . ", " . -1 . ", " . $val . ", " . 1 . ")";
				$result10 = $this->db->getResult($query10);
			}
		}

		// Get the available base folder access that not exists in the table tblACLs
		$query4 = "SELECT `folderID`, `baseMode` FROM `tblFolderAccessBase` WHERE `userID` = " . $userid;
		$result4 = $this->db->getResultArray($query4);
		if ($result4) {
			foreach ($result4 as $key => $val) {
				$folderid = $val['folderID'];
				$mode = $val['baseMode'];

				if (!array_key_exists($folderid, $folderCollection)) {
					$userAvailableFolderAccessCollection[$folderid] = $mode;
				}
			}
		}

		// Get the available base document access that not exists in the table tblACLs
		$query11 = "SELECT `documentID`, `baseMode` FROM `tblDocumentAccessBase` WHERE `userID` = " . $userid;
		$result11 = $this->db->getResultArray($query11);
		if ($result11) {
			foreach ($result11 as $key => $val) {
				$documentid = $val['documentID'];
				$mode = $val['baseMode'];

				if (!array_key_exists($documentid, $documentCollection)) {
					$userAvailableDocumentAccessCollection[$documentid] = $mode;
				}
			}
		}

		// Restore the base folder access for user
		if (count($userAvailableFolderAccessCollection) > 0) {
			foreach ($userAvailableFolderAccessCollection as $key => $val) {
				$query5 = "SELECT * FROM `tblACLs` WHERE `target` = " . $key . " AND `targetType` = 1 AND `userID` = " . $userid . " AND `groupID` = -1";
				$result5 = $this->db->getResultArray($query5);
				if ($result5) {
					$query6 = "UPDATE `tblACLs` SET `mode` = " . $val . ", `followGroup` = 0" . " WHERE `target` = " . $key . " AND `targetType` = 1 AND `userID` = " . $userid . " AND `groupID` = -1";
					$result6 = $this->db->getResult($query6);
				} else {
					$query7 = "INSERT INTO `tblACLs` (`target`, `targetType`, `userID`, `groupID`, `mode`, `followGroup`) VALUES (" . $key . ", " . 1 . ", " . $userid . ", " . -1 . ", " . $val . ", " . 0 . ")";
					$result7 = $this->db->getResult($query7);
				}
			}
		}

		// Restore the base document access for user
		if (count($userAvailableDocumentAccessCollection) > 0) {
			foreach ($userAvailableDocumentAccessCollection as $key => $val) {
				$query12 = "SELECT * FROM `tblACLs` WHERE `target` = " . $key . " AND `targetType` = 2 AND `userID` = " . $userid . " AND `groupID` = -1";
				$result12 = $this->db->getResultArray($query12);
				if ($result12) {
					$query13 = "UPDATE `tblACLs` SET `mode` = " . $val . ", `followGroup` = 0" . " WHERE `target` = " . $key . " AND `targetType` = 2 AND `userID` = " . $userid . " AND `groupID` = -1";
					$result13 = $this->db->getResult($query13);
				} else {
					$query14 = "INSERT INTO `tblACLs` (`target`, `targetType`, `userID`, `groupID`, `mode`, `followGroup`) VALUES (" . $key . ", " . 2 . ", " . $userid . ", " . -1 . ", " . $val . ", " . 0 . ")";
					$result14 = $this->db->getResult($query14);
				}
			}
		}
	}

	/**
	 * Create a new user group
	 *
	 * @param string $name name of group
	 * @param string $comment comment of group
	 * @return SeedDMS_Core_Group|boolean instance of {@link SeedDMS_Core_Group} or false in
	 *         case of an error.
	 */
	function addGroup($name, $comment) { /* {{{ */
		if (is_object($this->getGroupByName($name))) {
			return false; // if group exist return false
		}

		$queryStr = "INSERT INTO `tblGroups` (`name`, `comment`) VALUES (".$this->db->qstr($name).", ".$this->db->qstr($comment).")";
		if (!$this->db->getResult($queryStr))
			return false;

		$group = $this->getGroup($this->db->getInsertID('tblGroups'));

		/* Check if 'onPostAddGroup' callback is set */
		if(isset($this->_dms->callbacks['onPostAddGroup'])) {
			foreach($this->_dms->callbacks['onPostAddGroup'] as $callback) {
				/** @noinspection PhpStatementHasEmptyBodyInspection */
				if(!call_user_func($callback[0], $callback[1], $group)) {
				}
			}
		}
		$queryStr = "INSERT INTO `tblGroupPermit` (`id`) VALUES (".$group->getID().")";
		$this->db->getResult($queryStr);

		return $group;
	} /* }}} */

	function getKeywordCategory($id) { /* {{{ */
		if (!is_numeric($id))
			return false;

		$queryStr = "SELECT * FROM `tblKeywordCategories` WHERE `id` = " . (int) $id;
		$resArr = $this->db->getResultArray($queryStr);
		if ((is_bool($resArr) && !$resArr) || (count($resArr) != 1))
			return false;

		$resArr = $resArr[0];
		$cat = new SeedDMS_Core_Keywordcategory($resArr["id"], $resArr["owner"], $resArr["name"]);
		$cat->setDMS($this);
		return $cat;
	} /* }}} */

	function getKeywordCategoryByName($name, $userID) { /* {{{ */
		$queryStr = "SELECT * FROM `tblKeywordCategories` WHERE `name` = " . $this->db->qstr($name) . " AND `owner` = " . (int) $userID;
		$resArr = $this->db->getResultArray($queryStr);
		if ((is_bool($resArr) && !$resArr) || (count($resArr) != 1))
			return false;

		$resArr = $resArr[0];
		$cat = new SeedDMS_Core_Keywordcategory($resArr["id"], $resArr["owner"], $resArr["name"]);
		$cat->setDMS($this);
		return $cat;
	} /* }}} */

	function getAllKeywordCategories($userIDs = array()) { /* {{{ */
		$queryStr = "SELECT * FROM `tblKeywordCategories`";
		if ($userIDs)
			$queryStr .= " WHERE `owner` IN (".implode(',', $userIDs).")";

		$resArr = $this->db->getResultArray($queryStr);
		if (is_bool($resArr) && !$resArr)
			return false;

		$categories = array();
		foreach ($resArr as $row) {
			$cat = new SeedDMS_Core_KeywordCategory($row["id"], $row["owner"], $row["name"]);
			$cat->setDMS($this);
			array_push($categories, $cat);
		}

		return $categories;
	} /* }}} */

	/**
	 * This function should be replaced by getAllKeywordCategories()
	 * @param $userID
	 * @return SeedDMS_Core_KeywordCategory[]|bool
	 */
	function getAllUserKeywordCategories($userID) { /* {{{ */
		$queryStr = "SELECT * FROM `tblKeywordCategories`";
		if ($userID != -1)
			$queryStr .= " WHERE `owner` = " . (int) $userID;

		$resArr = $this->db->getResultArray($queryStr);
		if (is_bool($resArr) && !$resArr)
			return false;

		$categories = array();
		foreach ($resArr as $row) {
			$cat = new SeedDMS_Core_KeywordCategory($row["id"], $row["owner"], $row["name"]);
			$cat->setDMS($this);
			array_push($categories, $cat);
		}

		return $categories;
	} /* }}} */

	function addKeywordCategory($userID, $name) { /* {{{ */
		if (is_object($this->getKeywordCategoryByName($name, $userID))) {
			return false;
		}
		$queryStr = "INSERT INTO `tblKeywordCategories` (`owner`, `name`) VALUES (".(int) $userID.", ".$this->db->qstr($name).")";
		if (!$this->db->getResult($queryStr))
			return false;

		$category = $this->getKeywordCategory($this->db->getInsertID('tblKeywordCategories'));

		/* Check if 'onPostAddKeywordCategory' callback is set */
		if(isset($this->_dms->callbacks['onPostAddKeywordCategory'])) {
			foreach($this->_dms->callbacks['onPostAddKeywordCategory'] as $callback) {
				/** @noinspection PhpStatementHasEmptyBodyInspection */
				if(!call_user_func($callback[0], $callback[1], $category)) {
				}
			}
		}

		return $category;
	} /* }}} */

	function getDocumentCategory($id) { /* {{{ */
		if (!is_numeric($id))
			return false;

		$queryStr = "SELECT * FROM `tblCategory` WHERE `id` = " . (int) $id;
		$resArr = $this->db->getResultArray($queryStr);
		if ((is_bool($resArr) && !$resArr) || (count($resArr) != 1))
			return false;

		$resArr = $resArr[0];
		$cat = new SeedDMS_Core_DocumentCategory($resArr["id"], $resArr["name"]);
		$cat->setDMS($this);
		return $cat;
	} /* }}} */

	function getDocumentCategories() { /* {{{ */
		$queryStr = "SELECT * FROM `tblCategory` order by `name`";

		$resArr = $this->db->getResultArray($queryStr);
		if (is_bool($resArr) && !$resArr)
			return false;

		$categories = array();
		foreach ($resArr as $row) {
			$cat = new SeedDMS_Core_DocumentCategory($row["id"], $row["name"]);
			$cat->setDMS($this);
			array_push($categories, $cat);
		}

		return $categories;
	} /* }}} */

	/**
	 * Get a category by its name
	 *
	 * The name of a category is by default unique.
	 *
	 * @param string $name human readable name of category
	 * @return SeedDMS_Core_DocumentCategory|boolean instance of {@link SeedDMS_Core_DocumentCategory}
	 */
	function getDocumentCategoryByName($name) { /* {{{ */
		if (!$name) return false;

		$queryStr = "SELECT * FROM `tblCategory` where `name`=".$this->db->qstr($name);
		$resArr = $this->db->getResultArray($queryStr);
		if (!$resArr)
			return false;

		$row = $resArr[0];
		$cat = new SeedDMS_Core_DocumentCategory($row["id"], $row["name"]);
		$cat->setDMS($this);

		return $cat;
	} /* }}} */

	function addDocumentCategory($name) { /* {{{ */
		if (is_object($this->getDocumentCategoryByName($name))) {
			return false;
		}
		$queryStr = "INSERT INTO `tblCategory` (`name`) VALUES (".$this->db->qstr($name).")";
		if (!$this->db->getResult($queryStr))
			return false;

		$category = $this->getDocumentCategory($this->db->getInsertID('tblCategory'));

		/* Check if 'onPostAddDocumentCategory' callback is set */
		if(isset($this->_dms->callbacks['onPostAddDocumentCategory'])) {
			foreach($this->_dms->callbacks['onPostAddDocumentCategory'] as $callback) {
				/** @noinspection PhpStatementHasEmptyBodyInspection */
				if(!call_user_func($callback[0], $callback[1], $category)) {
				}
			}
		}

		return $category;
	} /* }}} */

	/**
	 * Get all notifications for a group
	 *
	 * deprecated: User {@link SeedDMS_Core_Group::getNotifications()}
	 *
	 * @param object $group group for which notifications are to be retrieved
	 * @param integer $type type of item (T_DOCUMENT or T_FOLDER)
	 * @return array array of notifications
	 */
	function getNotificationsByGroup($group, $type=0) { /* {{{ */
		return $group->getNotifications($type);
	} /* }}} */

	/**
	 * Get all notifications for a user
	 *
	 * deprecated: User {@link SeedDMS_Core_User::getNotifications()}
	 *
	 * @param object $user user for which notifications are to be retrieved
	 * @param integer $type type of item (T_DOCUMENT or T_FOLDER)
	 * @return array array of notifications
	 */
	function getNotificationsByUser($user, $type=0) { /* {{{ */
		return $user->getNotifications($type);
	} /* }}} */

	/**
	 * Create a token to request a new password.
	 * This function will not delete the password but just creates an entry
	 * in tblUserRequestPassword indicating a password request.
	 *
	 * @param SeedDMS_Core_User $user
	 * @return string|boolean hash value of false in case of an error
	 */
	function createPasswordRequest($user) { /* {{{ */
		$hash = md5(uniqid(time()));
		$queryStr = "INSERT INTO `tblUserPasswordRequest` (`userID`, `hash`, `date`) VALUES (" . $user->getId() . ", " . $this->db->qstr($hash) .", ".$this->db->getCurrentDatetime().")";
		$resArr = $this->db->getResult($queryStr);
		if (is_bool($resArr) && !$resArr) return false;
		return $hash;

	} /* }}} */

	/**
	 * Check if hash for a password request is valid.
	 * This function searches a previously create password request and
	 * returns the user.
	 *
	 * @param string $hash
	 * @return bool|SeedDMS_Core_User
	 */
	function checkPasswordRequest($hash) { /* {{{ */
		/* Get the password request from the database */
		$queryStr = "SELECT * FROM `tblUserPasswordRequest` where `hash`=".$this->db->qstr($hash);
		$resArr = $this->db->getResultArray($queryStr);
		if (is_bool($resArr) && !$resArr)
			return false;

		if (count($resArr) != 1)
			return false;
		$resArr = $resArr[0];

		return $this->getUser($resArr['userID']);

	} /* }}} */

	/**
	 * Delete a password request
	 *
	 * @param string $hash
	 * @return bool
	 */
	function deletePasswordRequest($hash) { /* {{{ */
		/* Delete the request, so nobody can use it a second time */
		$queryStr = "DELETE FROM `tblUserPasswordRequest` WHERE `hash`=".$this->db->qstr($hash);
		if (!$this->db->getResult($queryStr))
			return false;
		return true;
	} /* }}} */

	/**
	 * Return a attribute definition by its id
	 *
	 * This function retrieves a attribute definitionr from the database by
	 * its id.
	 *
	 * @param integer $id internal id of attribute defintion
	 * @return bool|SeedDMS_Core_AttributeDefinition or false
	 */
	function getAttributeDefinition($id) { /* {{{ */
		if (!is_numeric($id))
			return false;

		$queryStr = "SELECT * FROM `tblAttributeDefinitions` WHERE `id` = " . (int) $id;
		$resArr = $this->db->getResultArray($queryStr);

		if (is_bool($resArr) && $resArr == false) return false;
		if (count($resArr) != 1) return false;

		$resArr = $resArr[0];

		$attrdef = new SeedDMS_Core_AttributeDefinition($resArr["id"], $resArr["name"], $resArr["objtype"], $resArr["type"], $resArr["multiple"], $resArr["minvalues"], $resArr["maxvalues"], $resArr["valueset"], $resArr["regex"]);
		$attrdef->setDMS($this);
		return $attrdef;
	} /* }}} */

	/**
	 * Return a attribute definition by its name
	 *
	 * This function retrieves an attribute def. from the database by its name.
	 *
	 * @param string $name internal name of attribute def.
	 * @return SeedDMS_Core_AttributeDefinition|boolean instance of {@link SeedDMS_Core_AttributeDefinition} or false
	 */
	function getAttributeDefinitionByName($name) { /* {{{ */
		if (!$name) return false;

		$queryStr = "SELECT * FROM `tblAttributeDefinitions` WHERE `name` = " . $this->db->qstr($name);
		$resArr = $this->db->getResultArray($queryStr);

		if (is_bool($resArr) && $resArr == false) return false;
		if (count($resArr) != 1) return false;

		$resArr = $resArr[0];

		$attrdef = new SeedDMS_Core_AttributeDefinition($resArr["id"], $resArr["name"], $resArr["objtype"], $resArr["type"], $resArr["multiple"], $resArr["minvalues"], $resArr["maxvalues"], $resArr["valueset"], $resArr["regex"]);
		$attrdef->setDMS($this);
		return $attrdef;
	} /* }}} */

	/**
	 * Return list of all attributes definitions
	 *
	 * @param integer $objtype select those attributes defined for an object type
	 * @return bool|SeedDMS_Core_AttributeDefinition[] of instances of <a href='psi_element://SeedDMS_Core_AttributeDefinition'>SeedDMS_Core_AttributeDefinition</a> or false
	 * or false
	 */
	function getAllAttributeDefinitions($objtype=0) { /* {{{ */
		$queryStr = "SELECT * FROM `tblAttributeDefinitions`";
		if($objtype) {
			if(is_array($objtype))
				$queryStr .= ' WHERE `objtype` in (\''.implode("','", $objtype).'\')';
			else
				$queryStr .= ' WHERE `objtype`='.intval($objtype);
		}
		$queryStr .= ' ORDER BY `name`';
		$resArr = $this->db->getResultArray($queryStr);

		if (is_bool($resArr) && $resArr == false)
			return false;

		/** @var SeedDMS_Core_AttributeDefinition[] $attrdefs */
		$attrdefs = array();

		for ($i = 0; $i < count($resArr); $i++) {
			$attrdef = new SeedDMS_Core_AttributeDefinition($resArr[$i]["id"], $resArr[$i]["name"], $resArr[$i]["objtype"], $resArr[$i]["type"], $resArr[$i]["multiple"], $resArr[$i]["minvalues"], $resArr[$i]["maxvalues"], $resArr[$i]["valueset"], $resArr[$i]["regex"]);
			$attrdef->setDMS($this);
			$attrdefs[$i] = $attrdef;
		}

		return $attrdefs;
	} /* }}} */

	/**
	 * Add a new attribute definition
	 *
	 * @param string $name name of attribute
	 * @param $objtype
	 * @param string $type type of attribute
	 * @param bool|int $multiple set to 1 if attribute has multiple attributes
	 * @param integer $minvalues minimum number of values
	 * @param integer $maxvalues maximum number of values if multiple is set
	 * @param string $valueset list of allowed values (csv format)
	 * @param string $regex
	 * @return bool|SeedDMS_Core_User
	 */
	function addAttributeDefinition($name, $objtype, $type, $multiple=0, $minvalues=0, $maxvalues=1, $valueset='', $regex='') { /* {{{ */
		if (is_object($this->getAttributeDefinitionByName($name))) {
			return false;
		}
		if(!$type)
			return false;
		if(trim($valueset)) {
			$valuesetarr = array_map('trim', explode($valueset[0], substr($valueset, 1)));
			$valueset = $valueset[0].implode($valueset[0], $valuesetarr);
		} else {
			$valueset = '';
		}
		$queryStr = "INSERT INTO `tblAttributeDefinitions` (`name`, `objtype`, `type`, `multiple`, `minvalues`, `maxvalues`, `valueset`, `regex`) VALUES (".$this->db->qstr($name).", ".intval($objtype).", ".intval($type).", ".intval($multiple).", ".intval($minvalues).", ".intval($maxvalues).", ".$this->db->qstr($valueset).", ".$this->db->qstr($regex).")";
		$res = $this->db->getResult($queryStr);
		if (!$res)
			return false;

		return $this->getAttributeDefinition($this->db->getInsertID('tblAttributeDefinitions'));
	} /* }}} */

	/**
	 * Return list of all workflows
	 *
	 * @return SeedDMS_Core_Workflow[]|bool of instances of {@link SeedDMS_Core_Workflow} or false
	 */
	function getAllWorkflows() { /* {{{ */
		$queryStr = "SELECT * FROM `tblWorkflows` ORDER BY `name`";
		$resArr = $this->db->getResultArray($queryStr);

		if (is_bool($resArr) && $resArr == false)
			return false;

		$queryStr = "SELECT * FROM `tblWorkflowStates` ORDER BY `name`";
		$ressArr = $this->db->getResultArray($queryStr);

		if (is_bool($ressArr) && $ressArr == false)
			return false;

		for ($i = 0; $i < count($ressArr); $i++) {
			$wkfstates[$ressArr[$i]["id"]] = new SeedDMS_Core_Workflow_State($ressArr[$i]["id"], $ressArr[$i]["name"], $ressArr[$i]["maxtime"], $ressArr[$i]["precondfunc"], $ressArr[$i]["documentstatus"]);
		}

		/** @var SeedDMS_Core_Workflow[] $workflows */
		$workflows = array();
		for ($i = 0; $i < count($resArr); $i++) {
			/** @noinspection PhpUndefinedVariableInspection */
			$workflow = new SeedDMS_Core_Workflow($resArr[$i]["id"], $resArr[$i]["name"], $wkfstates[$resArr[$i]["initstate"]]);
			$workflow->setDMS($this);
			$workflows[$i] = $workflow;
		}

		return $workflows;
	} /* }}} */

	/**
	 * Return workflow by its Id
	 *
	 * @param integer $id internal id of workflow
	 * @return SeedDMS_Core_Workflow|bool of instances of {@link SeedDMS_Core_Workflow} or false
	 */
	function getWorkflow($id) { /* {{{ */
		$queryStr = "SELECT * FROM `tblWorkflows` WHERE `id`=".intval($id);
		$resArr = $this->db->getResultArray($queryStr);

		if (is_bool($resArr) && $resArr == false)
			return false;

		if(!$resArr)
			return false;

		$initstate = $this->getWorkflowState($resArr[0]['initstate']);

		$workflow = new SeedDMS_Core_Workflow($resArr[0]["id"], $resArr[0]["name"], $initstate);
		$workflow->setDMS($this);

		return $workflow;
	} /* }}} */

	/**
	 * Return workflow by its name
	 *
	 * @param string $name name of workflow
	 * @return SeedDMS_Core_Workflow|bool of instances of {@link SeedDMS_Core_Workflow} or false
	 */
	function getWorkflowByName($name) { /* {{{ */
		if (!$name) return false;

		$queryStr = "SELECT * FROM `tblWorkflows` WHERE `name`=".$this->db->qstr($name);
		$resArr = $this->db->getResultArray($queryStr);

		if (is_bool($resArr) && $resArr == false)
			return false;

		if(!$resArr)
			return false;

		$initstate = $this->getWorkflowState($resArr[0]['initstate']);

		$workflow = new SeedDMS_Core_Workflow($resArr[0]["id"], $resArr[0]["name"], $initstate);
		$workflow->setDMS($this);

		return $workflow;
	} /* }}} */

	/**
	 * Add a new workflow
	 *
	 * @param string $name name of workflow
	 * @param SeedDMS_Core_Workflow_State $initstate initial state of workflow
	 * @return bool|SeedDMS_Core_Workflow
	 */
	function addWorkflow($name, $initstate) { /* {{{ */
		$db = $this->db;
		if (is_object($this->getWorkflowByName($name))) {
			return false;
		}
		$queryStr = "INSERT INTO `tblWorkflows` (`name`, `initstate`) VALUES (".$db->qstr($name).", ".$initstate->getID().")";
		$res = $db->getResult($queryStr);
		if (!$res)
			return false;

		return $this->getWorkflow($db->getInsertID('tblWorkflows'));
	} /* }}} */

	/**
	 * Return a workflow state by its id
	 *
	 * This function retrieves a workflow state from the database by its id.
	 *
	 * @param integer $id internal id of workflow state
	 * @return bool|SeedDMS_Core_Workflow_State or false
	 */
	function getWorkflowState($id) { /* {{{ */
		if (!is_numeric($id))
			return false;

		$queryStr = "SELECT * FROM `tblWorkflowStates` WHERE `id` = " . (int) $id;
		$resArr = $this->db->getResultArray($queryStr);

		if (is_bool($resArr) && $resArr == false) return false;
		if (count($resArr) != 1) return false;

		$resArr = $resArr[0];

		$state = new SeedDMS_Core_Workflow_State($resArr["id"], $resArr["name"], $resArr["maxtime"], $resArr["precondfunc"], $resArr["documentstatus"]);
		$state->setDMS($this);
		return $state;
	} /* }}} */

	/**
	 * Return workflow state by its name
	 *
	 * @param string $name name of workflow state
	 * @return bool|SeedDMS_Core_Workflow_State or false
	 */
	function getWorkflowStateByName($name) { /* {{{ */
		if (!$name) return false;

		$queryStr = "SELECT * FROM `tblWorkflowStates` WHERE `name`=".$this->db->qstr($name);
		$resArr = $this->db->getResultArray($queryStr);

		if (is_bool($resArr) && $resArr == false)
			return false;

		if(!$resArr)
			return false;

		$resArr = $resArr[0];

		$state = new SeedDMS_Core_Workflow_State($resArr["id"], $resArr["name"], $resArr["maxtime"], $resArr["precondfunc"], $resArr["documentstatus"]);
		$state->setDMS($this);

		return $state;
	} /* }}} */

	/**
	 * Return list of all workflow states
	 *
	 * @return SeedDMS_Core_Workflow_State[]|bool of instances of {@link SeedDMS_Core_Workflow_State} or false
	 */
	function getAllWorkflowStates() { /* {{{ */
		$queryStr = "SELECT * FROM `tblWorkflowStates` ORDER BY `name`";
		$ressArr = $this->db->getResultArray($queryStr);

		if (is_bool($ressArr) && $ressArr == false)
			return false;

		$wkfstates = array();
		for ($i = 0; $i < count($ressArr); $i++) {
			$wkfstate = new SeedDMS_Core_Workflow_State($ressArr[$i]["id"], $ressArr[$i]["name"], $ressArr[$i]["maxtime"], $ressArr[$i]["precondfunc"], $ressArr[$i]["documentstatus"]);
			$wkfstate->setDMS($this);
			$wkfstates[$i] = $wkfstate;
		}

		return $wkfstates;
	} /* }}} */

	/**
	 * Add new workflow state
	 *
	 * @param string $name name of workflow state
	 * @param integer $docstatus document status when this state is reached
	 * @return bool|SeedDMS_Core_Workflow_State
	 */
	function addWorkflowState($name, $docstatus) { /* {{{ */
		$db = $this->db;
		if (is_object($this->getWorkflowStateByName($name))) {
			return false;
		}
		$queryStr = "INSERT INTO `tblWorkflowStates` (`name`, `documentstatus`) VALUES (".$db->qstr($name).", ".(int) $docstatus.")";
		$res = $db->getResult($queryStr);
		if (!$res)
			return false;

		return $this->getWorkflowState($db->getInsertID('tblWorkflowStates'));
	} /* }}} */

	/**
	 * Return a workflow action by its id
	 *
	 * This function retrieves a workflow action from the database by its id.
	 *
	 * @param integer $id internal id of workflow action
	 * @return SeedDMS_Core_Workflow_Action|bool instance of {@link SeedDMS_Core_Workflow_Action} or false
	 */
	function getWorkflowAction($id) { /* {{{ */
		if (!is_numeric($id))
			return false;

		$queryStr = "SELECT * FROM `tblWorkflowActions` WHERE `id` = " . (int) $id;
		$resArr = $this->db->getResultArray($queryStr);

		if (is_bool($resArr) && $resArr == false) return false;
		if (count($resArr) != 1) return false;

		$resArr = $resArr[0];

		$action = new SeedDMS_Core_Workflow_Action($resArr["id"], $resArr["name"]);
		$action->setDMS($this);
		return $action;
	} /* }}} */

	/**
	 * Return a workflow action by its name
	 *
	 * This function retrieves a workflow action from the database by its name.
	 *
	 * @param string $name name of workflow action
	 * @return SeedDMS_Core_Workflow_Action|bool instance of {@link SeedDMS_Core_Workflow_Action} or false
	 */
	function getWorkflowActionByName($name) { /* {{{ */
		if (!$name) return false;

		$queryStr = "SELECT * FROM `tblWorkflowActions` WHERE `name` = " . $this->db->qstr($name);
		$resArr = $this->db->getResultArray($queryStr);

		if (is_bool($resArr) && $resArr == false) return false;
		if (count($resArr) != 1) return false;

		$resArr = $resArr[0];

		$action = new SeedDMS_Core_Workflow_Action($resArr["id"], $resArr["name"]);
		$action->setDMS($this);
		return $action;
	} /* }}} */

	/**
	 * Return list of workflow action
	 *
	 * @return SeedDMS_Core_Workflow_Action[]|bool list of instances of {@link SeedDMS_Core_Workflow_Action} or false
	 */
	function getAllWorkflowActions() { /* {{{ */
		$queryStr = "SELECT * FROM `tblWorkflowActions`";
		$resArr = $this->db->getResultArray($queryStr);

		if (is_bool($resArr) && $resArr == false)
			return false;

		/** @var SeedDMS_Core_Workflow_Action[] $wkfactions */
		$wkfactions = array();
		for ($i = 0; $i < count($resArr); $i++) {
			$action = new SeedDMS_Core_Workflow_Action($resArr[$i]["id"], $resArr[$i]["name"]);
			$action->setDMS($this);
			$wkfactions[$i] = $action;
		}

		return $wkfactions;
	} /* }}} */

	/**
	 * Add new workflow action
	 *
	 * @param string $name name of workflow action
	 * @return SeedDMS_Core_Workflow_Action|bool
	 */
	function addWorkflowAction($name) { /* {{{ */
		$db = $this->db;
		if (is_object($this->getWorkflowActionByName($name))) {
			return false;
		}
		$queryStr = "INSERT INTO `tblWorkflowActions` (`name`) VALUES (".$db->qstr($name).")";
		$res = $db->getResult($queryStr);
		if (!$res)
			return false;

		return $this->getWorkflowAction($db->getInsertID('tblWorkflowActions'));
	} /* }}} */

	/**
	 * Return a workflow transition by its id
	 *
	 * This function retrieves a workflow transition from the database by its id.
	 *
	 * @param integer $id internal id of workflow transition
	 * @return SeedDMS_Core_Workflow_Transition|bool instance of {@link SeedDMS_Core_Workflow_Transition} or false
	 */
	function getWorkflowTransition($id) { /* {{{ */
		if (!is_numeric($id))
			return false;

		$queryStr = "SELECT * FROM `tblWorkflowTransitions` WHERE `id` = " . (int) $id;
		$resArr = $this->db->getResultArray($queryStr);

		if (is_bool($resArr) && $resArr == false) return false;
		if (count($resArr) != 1) return false;

		$resArr = $resArr[0];

		$transition = new SeedDMS_Core_Workflow_Transition($resArr["id"], $this->getWorkflow($resArr["workflow"]), $this->getWorkflowState($resArr["state"]), $this->getWorkflowAction($resArr["action"]), $this->getWorkflowState($resArr["nextstate"]), $resArr["maxtime"]);
		$transition->setDMS($this);
		return $transition;
	} /* }}} */

	/**
	 * Returns document content which is not linked to a document
	 *
	 * This method is for finding straying document content without
	 * a parent document. In normal operation this should not happen
	 * but little checks for database consistency and possible errors
	 * in the application may have left over document content though
	 * the document is gone already.
	 *
	 * @return array|bool
	 */
	function getUnlinkedDocumentContent() { /* {{{ */
		$queryStr = "SELECT * FROM `tblDocumentContent` WHERE `document` NOT IN (SELECT id FROM `tblDocuments`)";
		$resArr = $this->db->getResultArray($queryStr);
		if ($resArr === false)
			return false;

		$versions = array();
		foreach($resArr as $row) {
			/** @var SeedDMS_Core_Document $document */
			$document = new $this->classnames['document']($row['document'], '', '', '', '', '', '', '', '', '', '', '');
			$document->setDMS($this);
			$version = new $this->classnames['documentcontent']($row['id'], $document, $row['version'], $row['comment'], $row['date'], $row['createdBy'], $row['dir'], $row['orgFileName'], $row['fileType'], $row['mimeType'], $row['fileSize'], $row['checksum']);
			$versions[] = $version;
		}
		return $versions;

	} /* }}} */

	/**
	 * Returns document content which has no file size set
	 *
	 * This method is for finding document content without a file size
	 * set in the database. The file size of a document content was introduced
	 * in version 4.0.0 of SeedDMS for implementation of user quotas.
	 *
	 * @return SeedDMS_Core_Document[]|bool
	 */
	function getNoFileSizeDocumentContent() { /* {{{ */
		$queryStr = "SELECT * FROM `tblDocumentContent` WHERE `fileSize` = 0 OR `fileSize` is null";
		$resArr = $this->db->getResultArray($queryStr);
		if ($resArr === false)
			return false;

		/** @var SeedDMS_Core_Document[] $versions */
		$versions = array();
		foreach($resArr as $row) {
			/** @var SeedDMS_Core_Document $document */
			$document = new $this->classnames['document']($row['document'], '', '', '', '', '', '', '', '', '', '', '');
			$document->setDMS($this);
			$version = new $this->classnames['documentcontent']($row['id'], $document, $row['version'], $row['comment'], $row['date'], $row['createdBy'], $row['dir'], $row['orgFileName'], $row['fileType'], $row['mimeType'], $row['fileSize'], $row['checksum'], $row['fileSize'], $row['checksum']);
			$versions[] = $version;
		}
		return $versions;

	} /* }}} */

	/**
	 * Returns document content which has no checksum set
	 *
	 * This method is for finding document content without a checksum
	 * set in the database. The checksum of a document content was introduced
	 * in version 4.0.0 of SeedDMS for finding duplicates.
	 * @return bool|SeedDMS_Core_Document[]
	 */
	function getNoChecksumDocumentContent() { /* {{{ */
		$queryStr = "SELECT * FROM `tblDocumentContent` WHERE `checksum` = '' OR `checksum` is null";
		$resArr = $this->db->getResultArray($queryStr);
		if ($resArr === false)
			return false;

		/** @var SeedDMS_Core_Document[] $versions */
		$versions = array();
		foreach($resArr as $row) {
			/** @var SeedDMS_Core_Document $document */
			$document = new $this->classnames['document']($row['document'], '', '', '', '', '', '', '', '', '', '', '');
			$document->setDMS($this);
			$version = new $this->classnames['documentcontent']($row['id'], $document, $row['version'], $row['comment'], $row['date'], $row['createdBy'], $row['dir'], $row['orgFileName'], $row['fileType'], $row['mimeType'], $row['fileSize'], $row['checksum']);
			$versions[] = $version;
		}
		return $versions;

	} /* }}} */

	/**
	 * Returns document content which is duplicated
	 *
	 * This method is for finding document content which is available twice
	 * in the database. The checksum of a document content was introduced
	 * in version 4.0.0 of SeedDMS for finding duplicates.
	 * @return array|bool
	 */
	function getDuplicateDocumentContent() { /* {{{ */
		$queryStr = "SELECT a.*, b.`id` as dupid FROM `tblDocumentContent` a LEFT JOIN `tblDocumentContent` b ON a.`checksum`=b.`checksum` where a.`id`!=b.`id` ORDER by a.`id` LIMIT 1000";
		$resArr = $this->db->getResultArray($queryStr);
		if (!$resArr)
			return false;

		/** @var SeedDMS_Core_Document[] $versions */
		$versions = array();
		foreach($resArr as $row) {
			$document = $this->getDocument($row['document']);
			$version = new $this->classnames['documentcontent']($row['id'], $document, $row['version'], $row['comment'], $row['date'], $row['createdBy'], $row['dir'], $row['orgFileName'], $row['fileType'], $row['mimeType'], $row['fileSize'], $row['checksum']);
			if(!isset($versions[$row['dupid']])) {
				$versions[$row['id']]['content'] = $version;
				$versions[$row['id']]['duplicates'] = array();
			} else
				$versions[$row['dupid']]['duplicates'][] = $version;
		}
		return $versions;

	} /* }}} */

	/**
	 * Returns a list of reviews, approvals which are not linked
	 * to a user, group anymore
	 *
	 * This method is for finding reviews or approvals whose user
	 * or group  was deleted and not just removed from the process.
	 *
	 * @param string $process
	 * @param string $usergroup
	 * @return array
	 */
	function getProcessWithoutUserGroup($process, $usergroup) { /* {{{ */
		switch($process) {
		case 'review':
			$queryStr = "SELECT a.*, b.`name` FROM `tblDocumentReviewers`";
			break;
		case 'approval':
			$queryStr = "SELECT a.*, b.`name` FROM `tblDocumentApprovers`";
			break;
		}
		/** @noinspection PhpUndefinedVariableInspection */
		$queryStr .= " a LEFT JOIN `tblDocuments` b ON a.`documentID`=b.`id` where";
		switch($usergroup) {
		case 'user':
			$queryStr .= " a.`type`=0 and a.`required` not in (select `id` from `tblUsers`) ORDER by b.`id`";
			break;
		case 'group':
			$queryStr .= " a.`type`=1 and a.`required` not in (select `id` from `tblGroups`) ORDER by b.`id`";
			break;
		}
		return $this->db->getResultArray($queryStr);
	} /* }}} */

	/**
	 * Removes all reviews, approvals which are not linked
	 * to a user, group anymore
	 *
	 * This method is for removing all reviews or approvals whose user
	 * or group  was deleted and not just removed from the process.
	 * If the optional parameter $id is set, only this user/group id is removed.
	 * @param string $process
	 * @param string $usergroup
	 * @param int $id
	 * @return array
	 */
	function removeProcessWithoutUserGroup($process, $usergroup, $id=0) { /* {{{ */
		/* Entries of tblDocumentReviewLog or tblDocumentApproveLog are deleted
		 * because of CASCADE ON
		 */
		switch($process) {
		case 'review':
			$queryStr = "DELETE FROM tblDocumentReviewers";
			break;
		case 'approval':
			$queryStr = "DELETE FROM tblDocumentApprovers";
			break;
		}
		/** @noinspection PhpUndefinedVariableInspection */
		$queryStr .= " WHERE";
		switch($usergroup) {
		case 'user':
			$queryStr .= " type=0 AND";
			if($id)
				$queryStr .= " required=".((int) $id)." AND";
			$queryStr .= " required NOT IN (SELECT id FROM tblUsers)";
			break;
		case 'group':
			$queryStr .= " type=1 AND";
			if($id)
				$queryStr .= " required=".((int) $id)." AND";
			$queryStr .= " required NOT IN (SELECT id FROM tblGroups)";
			break;
		}
		return $this->db->getResultArray($queryStr);
	} /* }}} */

	/**
	 * Returns statitical information
	 *
	 * This method returns all kind of statistical information like
	 * documents or used space per user, recent activity, etc.
	 *
	 * @param string $type type of statistic
	 * @return array|bool
	 */
	function getStatisticalData($type='') { /* {{{ */
		switch($type) {
			case 'docsperuser':
				$queryStr = "select b.`fullName` as `key`, count(`owner`) as total from `tblDocuments` a left join `tblUsers` b on a.`owner`=b.`id` group by `owner`, b.`fullName`";
				$resArr = $this->db->getResultArray($queryStr);
				if (!$resArr)
					return false;

				return $resArr;
			case 'docspermimetype':
				$queryStr = "select b.`mimeType` as `key`, count(`mimeType`) as total from `tblDocuments` a left join `tblDocumentContent` b on a.`id`=b.`document` group by b.`mimeType`";
				$resArr = $this->db->getResultArray($queryStr);
				if (!$resArr)
					return false;

				return $resArr;
			case 'docspercategory':
				$queryStr = "select b.`name` as `key`, count(a.`categoryID`) as total from `tblDocumentCategory` a left join `tblCategory` b on a.`categoryID`=b.id group by a.`categoryID`, b.`name`";
				$resArr = $this->db->getResultArray($queryStr);
				if (!$resArr)
					return false;

				return $resArr;
			case 'docsperstatus':
				/** @noinspection PhpUnusedLocalVariableInspection */
				$queryStr = "select b.`status` as `key`, count(b.`status`) as total from (select a.id, max(b.version), max(c.`statusLogID`) as maxlog from `tblDocuments` a left join `tblDocumentStatus` b on a.id=b.`documentID` left join `tblDocumentStatusLog` c on b.`statusID`=c.`statusID` group by a.`id`, b.`version` order by a.`id`, b.`statusID`) a left join `tblDocumentStatusLog` b on a.`maxlog`=b.`statusLogID` group by b.`status`";
				$queryStr = "select b.`status` as `key`, count(b.`status`) as total from (select a.`id`, max(c.`statusLogID`) as maxlog from `tblDocuments` a left join `tblDocumentStatus` b on a.id=b.`documentID` left join `tblDocumentStatusLog` c on b.`statusID`=c.`statusID` group by a.`id`  order by a.id) a left join `tblDocumentStatusLog` b on a.maxlog=b.`statusLogID` group by b.`status`";
				$resArr = $this->db->getResultArray($queryStr);
				if (!$resArr)
					return false;

				return $resArr;
			case 'docspermonth':
				$queryStr = "select *, count(`key`) as total from (select ".$this->db->getDateExtract("date", '%Y-%m')." as `key` from `tblDocuments`) a group by `key` order by `key`";
				$resArr = $this->db->getResultArray($queryStr);
				if (!$resArr)
					return false;

				return $resArr;
			case 'docsaccumulated':
				$queryStr = "select *, count(`key`) as total from (select ".$this->db->getDateExtract("date")." as `key` from `tblDocuments`) a group by `key` order by `key`";
				$resArr = $this->db->getResultArray($queryStr);
				if (!$resArr)
					return false;

				$sum = 0;
				foreach($resArr as &$res) {
					$sum += $res['total'];
					/* auxially variable $key is need because sqlite returns
					 * a key '`key`'
					 */
					$res['key'] = mktime(12, 0, 0, substr($res['key'], 5, 2), substr($res['key'], 8, 2), substr($res['key'], 0, 4)) * 1000;
					$res['total'] = $sum;
				}
				return $resArr;
			case 'sizeperuser':
				$queryStr = "select c.`fullName` as `key`, sum(`fileSize`) as total from `tblDocuments` a left join `tblDocumentContent` b on a.id=b.`document` left join `tblUsers` c on a.`owner`=c.`id` group by a.`owner`, c.`fullName`";
				$resArr = $this->db->getResultArray($queryStr);
				if (!$resArr)
					return false;

				return $resArr;
			default:
				return array();
		}
	} /* }}} */

	/**
	 * Returns changes with a period of time
	 *
	 * This method returns a list of all changes happened in the database
	 * within a given period of time. It currently just checks for
	 * entries in the database tables tblDocumentContent, tblDocumentFiles,
	 * and tblDocumentStatusLog
	 *
	 * @param string $startts
	 * @param string $endts
	 * @return array|bool
	 * @internal param string $start start date, defaults to start of current day
	 * @internal param string $end end date, defaults to end of start day
	 */
	function getTimeline($startts='', $endts='') { /* {{{ */
		if(!$startts)
			$startts = mktime(0, 0, 0);
		if(!$endts)
			$endts = $startts+86400;

		/** @var SeedDMS_Core_Document[] $timeline */
		$timeline = array();

		$queryStr = "SELECT DISTINCT document FROM `tblDocumentContent` WHERE `date` > ".$startts." AND `date` < ".$endts." UNION SELECT DISTINCT document FROM `tblDocumentFiles` WHERE `date` > ".$startts." AND `date` < ".$endts;
		$resArr = $this->db->getResultArray($queryStr);
		if ($resArr === false)
			return false;
		foreach($resArr as $rec) {
			$document = $this->getDocument($rec['document']);
			$timeline = array_merge($timeline, $document->getTimeline());
		}
		return $timeline;

	} /* }}} */

	/**
	 * Set a callback function
	 *
	 * @param string $name internal name of callback
	 * @param mixed $func function name as expected by {call_user_method}
	 * @param mixed $params parameter passed as the first argument to the
	 *        callback
	 */
	function setCallback($name, $func, $params=null) { /* {{{ */
		if($name && $func)
			$this->callbacks[$name] = array(array($func, $params));
	} /* }}} */

	/**
	 * Add a callback function
	 *
	 * @param string $name internal name of callback
	 * @param mixed $func function name as expected by {call_user_method}
	 * @param mixed $params parameter passed as the first argument to the
	 *        callback
	 */
	function addCallback($name, $func, $params=null) { /* {{{ */
		if($name && $func)
			$this->callbacks[$name][] = array($func, $params);
	} /* }}} */

	/**
	 * Create an sql dump of the complete database
	 *
	 * @param string $filename name of dump file
	 * @return bool
	 */
	function createDump($filename) { /* {{{ */
		$h = fopen($filename, "w");
		if(!$h)
			return false;

		$tables = $this->db->TableList('TABLES');
		foreach($tables as $table) {
			$query = "SELECT * FROM `".$table."`";
			$records = $this->db->getResultArray($query);
			fwrite($h,"\n-- TABLE: ".$table."--\n\n");
			foreach($records as $record) {
				$values="";
				$i = 1;
				foreach ($record as $column) {
					if (is_numeric($column)) $values .= $column;
					else $values .= $this->db->qstr($column);

					if ($i<(count($record))) $values .= ",";
					$i++;
				}

				fwrite($h, "INSERT INTO `".$table."` VALUES (".$values.");\n");
			}
		}

		fclose($h);
		return true;
	} /* }}} */

        
        
        //Below function added by elib project
        function GetDocID(){
            //function for search, return all valid doc id
                $sqlq = 'SELECT `id` FROM `tblDocuments` order by `id` ASC';
		$resArr = $this->db->getResultArray($sqlq);

		if (is_bool($resArr) && !$resArr)
			return false;

		if(!$resArr)
			return false;
                
                return $resArr;
        }
        
        function GetDocNameByid($documentid){
            $sqlq = 'SELECT `name` FROM `tblDocuments` WHERE `id`='.$documentid;
            $resArr = $this->db->getResultArray($sqlq);
            		if (is_bool($resArr) && !$resArr)
			return false;

		if(!$resArr)
			return false;
                
                return $resArr;
            
        }
       
        function DeleteReadHis($documentid){
            $sqlq="DELETE FROM `tblUserReadingHistory` WHERE `documentid`= $documentid";
            $resArr = $this->db->getResultArray($sqlq);
            return $resArr;
        }
        
        function AddBookmark($documentid, $userid, $page, $version){
            $sqlq="SELECT `id` FROM `tblBookmark` WHERE `documentid`=$documentid AND `pageno` =$page AND `version` =$version";
            $resArr = $this->db->getResultArray($sqlq);
            if ($resArr){
                $entry=$resArr[0]['id'];
                $sqlq="SELECT * FROM `tblBookmark2` WHERE `userid`=$userid AND `entry` = $entry";
                $resArr2 = $this->db->getResultArray($sqlq);
                if ($resArr2){
                    return false;
                }else{
                    $sqlq="INSERT INTO `tblBookmark2`(`userid`, `entry`, `date`) VALUES ($userid,$entry, CURDATE())";
                    //file_put_contents("/home/www-data/seeddms51x/debug.txt",$sqlq, FILE_APPEND);
                    $resArr3 = $this->db->getResultArray($sqlq);
                    return true;
                }
                
            }else{
                $sqlq="INSERT INTO `tblBookmark`(`id`, `documentid`, `pageno`, `version`) VALUES (NULL, $documentid,$page, $version)";
                $resArr2 = $this->db->getResultArray($sqlq);
                $sqlq="SELECT `id` FROM `tblBookmark` WHERE `documentid`=$documentid AND `pageno` =$page AND `version` =$version";
                $resArr2 = $this->db->getResultArray($sqlq);
                $entry=$resArr2[0]['id'];
                $sqlq="INSERT INTO `tblBookmark2`(`userid`, `entry`, `date`) VALUES ($userid,$entry, CURDATE())";
                //file_put_contents("/home/www-data/seeddms51x/debug.txt",$sqlq, FILE_APPEND);
                $resArr2 = $this->db->getResultArray($sqlq);
                return true;
            }

        }
        

        
        function GetBookmark($userid, $docid, $version, $mode){

            if($mode==1){
                $sqlq="SELECT `date`, `pageno`, `version` , `documentid`FROM tblBookmarktmp WHERE `userid`=$userid AND `documentid` =$docid AND `version` =$version";
            }else{
                $sqlq="SELECT `date`, `pageno` , `version` , `documentid` FROM tblBookmarktmp WHERE `userid`=$userid";
            }
            $resArr = $this->db->getResultArray($sqlq);
            return $resArr;
        }
        
        function DeleteBookmark($deldid, $userid, $delpage, $delver, $mode){
            if($mode == 0){
                $sqlq = "SELECT `id` FROM `tblBookmark` WHERE `documentid`=$deldid AND `pageno` =$delpage AND `version` =$delver";
                $resArr = $this->db->getResultArray($sqlq);
                $sqlq = "DELETE FROM `tblBookmark2` WHERE `userid`= $userid AND `entry` =".$resArr[0]['id'];
                //file_put_contents("/home/www-data/seeddms51x/debug.txt", $sqlq, FILE_APPEND);
                $resArr = $this->db->getResultArray($sqlq);

            }else if($mode==1){
                //del bookmark when a document deleted
                $sqlq = "SELECT `id` FROM `tblBookmark` WHERE `documentid`=$deldid";
                $resArr = $this->db->getResultArray($sqlq);

                $sqlq = "DELETE FROM `tblBookmark2` WHERE `entry` IN (";
                foreach($resArr as $row){
                    $sqlq=$sqlq.$row['id'].",";
                }
                $sqlq=substr($sqlq, 0, -1);
                $sqlq = $sqlq.")"; 
                $resArr = $this->db->getResultArray($sqlq);
                $sqlq= "DELETE FROM `tblBookmark` WHERE `documentid`=$deldid";
                $resArr = $this->db->getResultArray($sqlq);

            }
            
                
            return $resArr;
        }
        
        function GetDocumentNameList($res){
            //return all doc name where user have bookmark in
            foreach ($res as $row){
                $arr[]=$row['documentid'];
                //file_put_contents("/home/www-data/seeddms51x/debug.txt", $row['documentid'], FILE_APPEND);
            }
            $arr=array_unique($arr);

            $sqlq = 'SELECT `name`, `id` FROM `tblDocuments` WHERE `id`IN (';
            foreach($arr as $row){
                $sqlq=$sqlq.$row.",";
            }
            $sqlq=substr($sqlq, 0, -1);
            $sqlq = $sqlq.")"; 
            //file_put_contents("/home/www-data/seeddms51x/debug.txt", $sqlq, FILE_APPEND);
            $resArr = $this->db->getResultArray($sqlq);
            return $resArr;
        }
        


        function GetUserNameByID($userid){
            $sqlq = "SELECT `login` FROM `tblUsers` WHERE `id`=$userid";
            //file_put_contents("/home/www-data/seeddms51x/debug.txt", $sqlq, FILE_APPEND);
            $resArr = $this->db->getResultArray($sqlq);
                
            return $resArr;
        }

        
        
        
        function AddNote($data, $pos, $len, $userid){
            if($data[6]==0){ //no parent
                $sqlq = "SELECT `entry` FROM `tblNote` WHERE`documentid`=$data[0] AND `pageno` =$data[4] AND `version` =$data[1]";
                $resArr = $this->db->getResultArray($sqlq);
                if(sizeof($resArr)==0){
                    $sqlq="INSERT INTO `tblNote`(`entry`, `documentid`, `pageno`, `version`) VALUES (NULL, $data[0],$data[4], $data[1])";
                    $resArr = $this->db->getResultArray($sqlq);
                    $sqlq = "SELECT `entry` FROM `tblNote` WHERE `documentid`=$data[0] AND `pageno` =$data[4] AND `version` =$data[1]";
                    $resArr = $this->db->getResultArray($sqlq);
                }
            }else{
                $sqlq = "SELECT `entry` FROM `tblNote2` WHERE `id`=$data[6]";
                $resArr = $this->db->getResultArray($sqlq);
            }

            $sqlq="INSERT INTO `tblNote2`(`id`, `entry`, `userid`, `subject`, `note`, `visible`, `time`, `parent`, `txtstart`, `txtlen`, `noteType`) VALUES "
                     . "(NULL, ".$resArr[0]['entry'].", $userid,'$data[2]', '$data[3]', '$data[7]', '".date("Y-m-d H:i:s")."', $data[6], $pos, $len, $data[8])";
            //file_put_contents("/home/www-data/seeddms51x/debug.txt", $sqlq, FILE_APPEND);    
            $resArr = $this->db->getResultArray($sqlq);    
        }
        
        function GetAdvice($data){
            $sqlq = "SELECT * FROM `tblNotetmp` WHERE `documentid`=".$data['docid']." AND `version` =".$data['version']." AND `noteType`=2  AND `visible` >0";
            $resArr = $this->db->getResultArray($sqlq);
            return $resArr;
        }
        
        function GetNoteByPage($data, $markQuote=0){
            if(!$markQuote){
                $sqlq1 = "SELECT * FROM `tblNotetmp` WHERE `documentid`=".$data['docid']." AND `pageno` =".$data['page']." AND `version` =".$data['version']." AND `visible` =1 AND `parent` =0";
                $resArr1 = $this->db->getResultArray($sqlq1);
                $sqlq2 = "SELECT * FROM `tblNotetmp` WHERE `documentid`=".$data['docid']." AND `pageno` =".$data['page']." AND `version` =".$data['version']." AND `visible` =2 AND `userid`=".$data['userid']." AND `parent` =0";
                $resArr2 = $this->db->getResultArray($sqlq2);
            }else{
                $sqlq1 = "SELECT * FROM `tblNotetmp` WHERE `documentid`=".$data['docid']." AND `pageno` =".$data['page']." AND `version` =".$data['version']." AND `visible` =1 AND `txtlen`>0 AND `txtstart`>=".$data['txtstart']." AND `txtstart`+`txtlen`<=".$data['txtstart']."+".$data['txtlen']." AND `parent` =0";
                $resArr1 = $this->db->getResultArray($sqlq1);
                $sqlq2 = "SELECT * FROM `tblNotetmp` WHERE `documentid`=".$data['docid']." AND `pageno` =".$data['page']." AND `version` =".$data['version']." AND `visible` =2 AND `userid`=".$data['userid']." AND `txtlen`>0 AND `txtstart`>=".$data['txtstart']." AND `txtstart`+`txtlen`<=".$data['txtstart']."+".$data['txtlen']." AND `parent` =0";
                $resArr2 = $this->db->getResultArray($sqlq2);
            }
            return array_merge($resArr1, $resArr2);
        }
        function GetAllNote($data){
            $sqlq1 = "SELECT * FROM `tblNotetmp` WHERE `documentid`=".$data['docid']." AND `version` =".$data['version']." AND `visible` =1 AND `parent` =0";
            $resArr1 = $this->db->getResultArray($sqlq1);
            $sqlq2 = "SELECT * FROM `tblNotetmp` WHERE `documentid`=".$data['docid']." AND `version` =".$data['version']." AND `visible` =2 AND `userid`=".$data['userid']." AND `parent` =0";
            $resArr2 = $this->db->getResultArray($sqlq2);
            return array_merge($resArr1, $resArr2);
        }
        
        function GetMyNote($data){
            $sqlq = "SELECT * FROM `tblNotetmp` WHERE `documentid`=".$data['docid']." AND `version` =".$data['version']." AND `userid`=".$data['userid']." AND `visible` >0";
            $resArr = $this->db->getResultArray($sqlq);
            return $resArr;
        }
        
        function GetUserNote($data){
            $sqlq = "SELECT * FROM `tblNotetmp` WHERE `documentid`=".$data['docid']." AND `version` =".$data['version']." AND `userid`=".$data['userid']." AND `visible` =1";
            $resArr = $this->db->getResultArray($sqlq);
            return $resArr;
        }
//        function GetNote($docid, $version, $page, $userid, $txtstart, $txtlen){
//            if($txtstart==='txtstart' && $txtlen==='txtlen'){
//                $sqlq = "SELECT * FROM `tblNotetmp` WHERE `documentid`=$docid AND `userid`=$userid AND `pageno` =$page AND `version` =$version AND `visible` >0";
//            }else{
//                $sqlq = "SELECT * FROM `tblNotetmp` WHERE `documentid`=$docid AND `version` =$version AND `pageno`=$page AND `txtlen`>0 AND `txtstart`>=$txtstart AND `txtstart`+`txtlen`<=$txtstart+$txtlen";
//            }
//            $resArr = $this->db->getResultArray($sqlq);
//            return $resArr;
//        }
        
        function GetNoteStart($docid, $version, $page){
            $sqlq = "SELECT `txtstart`, `txtlen` FROM `tblNotetmp` WHERE `documentid`=$docid AND `pageno` =$page AND `version` =$version AND `txtlen`>0 ORDER BY `txtstart` ASC, `txtlen` ASC";
            $resArr = $this->db->getResultArray($sqlq);
            return $resArr;
        }
        
        function GetNoteDetail($id){
            $sqlq = "SELECT * FROM `tblNotetmp` WHERE `id`=$id";
            $resArr = $this->db->getResultArray($sqlq);
            $sqlq = "SELECT * FROM `tblNotetmp` WHERE `parent`=$id AND `visible` >0";
            $resArr2 = $this->db->getResultArray($sqlq);
            return array_merge($resArr, $resArr2);
        }
        function DelNote($id){
            $sqlq = "UPDATE `tblNote2` SET `visible`='0' WHERE `id`=$id";
            $resArr = $this->db->getResultArray($sqlq);
            return 1;
        }
        function EditNote($id,$note, $visible){
            $sqlq = "UPDATE `tblNote2` SET `note`='$note', `visible`='$visible' WHERE `id`=$id";
            //file_put_contents("/home/www-data/seeddms51x/debug.txt", $sqlq, FILE_APPEND);    
            $resArr = $this->db->getResultArray($sqlq);
            return 1;
        }
        
        function getDocumentIDbyCategoriesID($catids){
            $sqlq = "SELECT DISTINCT `documentID` FROM `tblDocumentCategory` WHERE `categoryID` IN (";
            foreach($catids as $catid){
                $sqlq=$sqlq.$catid.",";
            }
            $sqlq=substr($sqlq, 0, -1);
            $sqlq = $sqlq.")";
            $resArr = $this->db->getResultArray($sqlq);
            if($resArr){
                foreach($resArr as $res){
                    $docid[]=$res['documentID'];
                }
                return $docid;
            }
            return 0;
        }

        function getOwnerIDbyDocumentID($docid){
            $sqlq = "SELECT `owner` FROM `tblDocuments` WHERE `id` = $docid";
            $resArr = $this->db->getResultArray($sqlq);
            return $resArr[0]['owner'];
        }
        
        function AddHighlight($data, $pos, $len, $userid){
            $sqlq="INSERT INTO `tblHighlight`(`documentid`, `pageno`, `version`, `userid`, `pos`, `len`) VALUES ($data[0],$data[3], $data[1], $userid, $pos, $len)";
            //file_put_contents("/home/www-data/seeddms51x/debug.txt", $sqlq, FILE_APPEND);   
            $resArr = $this->db->getResultArray($sqlq);
            return;
        }
        function GetHighlight($docid, $ver, $pageno, $userid){
            $sqlq="SELECT `pos`, `len`FROM `tblHighlight` WHERE `documentid`= $docid AND `pageno`= $pageno AND`version`= $ver AND`userid`= $userid ORDER BY `pos` ASC;";
            $resArr = $this->db->getResultArray($sqlq);
            return $resArr;
        }
        function RemoveHighlight($data, $pos, $userid){
            $sqlq="DELETE FROM `tblHighlight` WHERE `documentid`= $data[0] AND `pageno`= $data[3] AND`version`= $data[1] AND`userid`= $userid AND `pos` =$pos ;";
            //file_put_contents("/home/www-data/seeddms51x/debug.txt", $sqlq, FILE_APPEND);   
            $resArr = $this->db->getResultArray($sqlq);
            return $resArr;
        }
        function UndoHighlight($data, $userid){
            $sqlq1="SELECT MAX(id) FROM `tblHighlight` WHERE `documentid`= $data[0] AND`version`= $data[1] AND`userid`= $userid";
            $resArr1 = $this->db->getResultArray($sqlq1);
            $maxId=$resArr1[0]["MAX(id)"];
            $sqlq2="DELETE FROM `tblHighlight` WHERE id= $maxId;";
            //file_put_contents("/home/www-data/seeddms51x/debug.txt", $sqlq, FILE_APPEND);   
            $resArr = $this->db->getResultArray($sqlq2);
            return $resArr;
        }

		function AddHighlights($data, $pos_left, $pos_top, $pos_width, $pos_height, $text, $userid){
            $sqlq="INSERT INTO `tblHighlights`(`documentid`, `pageno`, `version`, `userid`, `pos_left`, `pos_top`, `pos_width`, `pos_height`, `text`) VALUES ($data[0], $data[2], $data[1], $userid, $pos_left, $pos_top, $pos_width, $pos_height, '".$text."')"; 
            $resArr = $this->db->getResultArray($sqlq);
            return;
        }
        function GetHighlights($docid, $ver, $pageno, $userid){
            $sqlq="SELECT `pos_left`, `pos_top`, `pos_width`, `pos_height` FROM `tblHighlights` WHERE `documentid`= $docid AND `pageno`= $pageno AND`version`= $ver AND`userid`= $userid";
            $resArr = $this->db->getResultArray($sqlq);
            return $resArr;
        }
		function RemoveHighlights($data, $pos_left, $pos_top, $pos_width, $pos_height, $userid){
            $sqlq="DELETE FROM `tblHighlights` WHERE `documentid`= $data[0] AND `pageno`= $data[6] AND`version`= $data[1] AND`userid`= $userid AND `pos_left` = $pos_left AND `pos_top` = $pos_top AND `pos_width` = $pos_width AND `pos_height` = $pos_height ;";
            $resArr = $this->db->getResultArray($sqlq);
            return $resArr;
        }
		function AddBookmarkNew($documentid, $userid, $page, $version, $note) {
			$sqlq = "SELECT `id` FROM `tblBookmarkNew` WHERE `documentid`=$documentid AND `pageno`=$page AND `version`=$version";
			$resArr = $this->db->getResultArray($sqlq);
			
			if ($resArr) {
				// 如果书签已存在，则返回"Bookmark already added"
				$entry = $resArr[0]['id'];
				$sqlq = "SELECT * FROM `tblBookmarkNew2` WHERE `userid`=$userid AND `entry`=$entry";
				$resArr2 = $this->db->getResultArray($sqlq);
				
				if ($resArr2) {
					return "Bookmark already added";
				} else {
					// 否则，在`tblBookmarkNew2`表中插入新的书签记录，并返回"Bookmark added successfully"
					$sqlq = "INSERT INTO `tblBookmarkNew2`(`userid`, `entry`, `date`, `note`) VALUES ($userid, $entry, CURDATE(), '$note')";
					$resArr3 = $this->db->getResultArray($sqlq);
					return "Bookmark added successfully";
				}
			} else {
				// 如果书签不存在，则先在`tblBookmarkNew`表中插入新的书签记录，然后在`tblBookmarkNew2`表中插入新的书签记录，并返回"Bookmark added successfully"
				$sqlq = "INSERT INTO `tblBookmarkNew`(`id`, `documentid`, `pageno`, `version`) VALUES (NULL, $documentid, $page, $version)";
				$resArr2 = $this->db->getResultArray($sqlq);
				$sqlq = "SELECT `id` FROM `tblBookmarkNew` WHERE `documentid`=$documentid AND `pageno`=$page AND `version`=$version";
				$resArr2 = $this->db->getResultArray($sqlq);
				$entry = $resArr2[0]['id'];
				$sqlq = "INSERT INTO `tblBookmarkNew2`(`userid`, `entry`, `date`, `note`) VALUES ($userid, $entry, CURDATE(), '$note')";
				$resArr2 = $this->db->getResultArray($sqlq);
				return "Bookmark added successfully";
			}
		}

		function DeleteAllBookmarks($docid,$userid) {
				$sqlq = "SELECT `id` FROM `tblBookmarkNew` WHERE `documentid`=$docid ";
                $resArr = $this->db->getResultArray($sqlq);

                $sqlq = "DELETE FROM `tblBookmarkNew2` WHERE 'userid' = $userid AND `entry` IN (";
                foreach($resArr as $row){
                    $sqlq=$sqlq.$row['id'].",";
                }
                $sqlq=substr($sqlq, 0, -1);
                $sqlq = $sqlq.")"; 
                $resArr = $this->db->getResultArray($sqlq);
                $sqlq= "DELETE FROM `tblBookmarkNew` WHERE `documentid`=$docid ";
                $resArr = $this->db->getResultArray($sqlq);
		  }
        
        function GetBookmarkNew($docid, $userid, $version) {
			
			//$sqlq = "SELECT * FROM `tblBookmarkNew` WHERE `documentid`= $docid"; 
			$sqlq="SELECT `note`,`date`, `pageno`, `version` , `documentid`FROM tblBookmarktmpNew WHERE `userid`=$userid AND `documentid` =$docid AND `version` =$version";
			$resArr = $this->db->getResultArray($sqlq);
			return $resArr;
		}

		function DeleteBookmarkNew($docid, $delpage) {
			$sqlq = "DELETE FROM `tblBookmarkNew` WHERE `documentid` = $docid AND `pageno` = $delpage";
			$resArr1 = $this->db->getResultArray($sqlq);
		
			$sqlq = "DELETE FROM `tblBookmarkNew2` WHERE `entry` IN (SELECT `id` FROM `tblBookmarkNew` WHERE `documentid` = $docid AND `pageno` = $delpage)";
			$resArr2 = $this->db->getResultArray($sqlq);
		
			if ($resArr1 && $resArr2) {
				return "Bookmark deleted successfully";
			} else {
				return "Failed to delete bookmark";
			}
		}

		function DeleteSelectedBookmarks($docid, $pagenos, $userid) {
			if (empty($pagenos)) {
			  return false; 
			}
			
			$pagenoStr = str_replace(['[', ']', '(', ')'], '', $pagenos);

			$sqlq = "SELECT id FROM tblBookmarkNew WHERE documentid = $docid AND pageno IN ($pagenoStr)";
			$resArr = $this->db->getResultArray($sqlq);
			
			$bookmarkIds = array_column($resArr, 'id');
			foreach ($bookmarkIds as $key => $value) {
				if ($key !== array_key_last($bookmarkIds)) {
				  $bookmarkString .= $value . ",";
				} else {
				  $bookmarkString .= $value;
				}
			  }
			
			$sqlq = "DELETE FROM tblBookmarkNew2 WHERE entry IN ($bookmarkString)";
			$resArr = $this->db->getResultArray($sqlq);
			
			$sqlq = "DELETE FROM tblBookmarkNew WHERE documentid = $docid AND id IN ($bookmarkString)";
			$resArr = $this->db->getResultArray($sqlq);
			
			return !empty($resArr);
		}

		function GetHighlightInfo($documentid, $userid, $pageno) {
			$sqlq = "SELECT `text` FROM `tblHighlights` WHERE `documentid` = $documentid AND `pageno` = $pageno AND `userid` = $userid ";

			$resArr = $this->db->getResultArray($sqlq);
			return $resArr;
		}
		
//chenrui
        /*function lookupDict($str){
            $sqlq = "SELECT `value` FROM `tblDict` WHERE `vocab`='".$str."'";
            $resArr = $this->db->getResultArray($sqlq);
            return $resArr;
        }*/

	function LookupDict($dictId,$str){
            $sqlq = "SELECT * FROM `tblDict".$dictId."` WHERE `vocab`='".$str."'";
            $resArr = $this->db->getResultArray($sqlq);
            return $resArr;
        }
        
        function checkToken($dictId, $documentid, $page, $version, $tokentxt, $tokenpos, $tokenLength){
            $sqlq = "SELECT * FROM `tblVoting".$dictId."_".$tokenLength."` WHERE `documentid`=$documentid AND `pageno` =$page AND `version` =$version "
                    . "AND `token` ='$tokentxt' AND `position` =$tokenpos";
            $resArr1 = $this->db->getResultArray($sqlq);
            return $resArr1;  
        }
        
        /*function LookupVoting($dictId, $documentid, $page, $version, $userid, $tokentxt, $tokenpos, $tokenLength){
            $sqlq = "SELECT * FROM `tblVotingtmp".$dictId."_".$tokenLength
                    . "` WHERE `userid`=$userid AND `documentid`=$documentid AND `pageno` =$page AND `version` =$version "
                    . "AND `token` ='$tokentxt' AND `position` =$tokenpos";
            $resArr1 = $this->db->getResultArray($sqlq);
            return $resArr1;  
        }*/

		function LookupVoting($dictId, $documentid, $page, $version, $userid, $tokentxt, $tokenpos, $tokenLength){
            $sqlq = "SELECT * FROM `tblVotingtmp".$dictId."_".$tokenLength
                    . "` WHERE `userid`=$userid AND `documentid`=$documentid AND `pageno` =$page AND `version` =$version "
                    . "AND `token` ='$tokentxt' AND `position` =$tokenpos";
            $resArr1 = $this->db->getResultArray($sqlq);
            return $resArr1;  
        }

        function Modifyvote($dictId, $documentid, $page, $version, $userid, $tokenpos, $footnote, $like, $dislike, $tokenLength){ 
            $sqlq = "SELECT `id` FROM `tblVoting".$dictId."_".$tokenLength."` WHERE`documentid`=$documentid "
                    . "AND `pageno` =$page AND `version` =$version AND `position` =$tokenpos AND `footnote` ='$footnote'";
            $resArr1 = $this->db->getResultArray($sqlq);
            $entry=$resArr1[0]['id'];
            $sqlq = "UPDATE `tblVoting".$dictId."_".$tokenLength."` SET `like`=`like`+$like,`dislike`=`dislike`+$dislike WHERE`id`=$entry"; //update total like and dislike
            $resArr2 = $this->db->getResultArray($sqlq);
            if($like==0){ //likeIcon keeps the same
                $str1="`likeIcon`=`likeIcon`";
            }else{
                $str1="`likeIcon`=$like";
            }
            if($dislike==0){
                $str2="`dislikeIcon`=`dislikeIcon`";
            }else{
                $str2="`dislikeIcon`=$dislike";
            }
            $sqlq = "UPDATE `tblUserVoting".$dictId."` SET $str1,$str2 WHERE `userid`=$userid AND `entry`=$entry AND `tokenLength`=$tokenLength";
            $resArr3 = $this->db->getResultArray($sqlq);
            $sqlq = "SELECT `like`, `dislike`, `likeIcon`, `dislikeIcon` FROM `tblVotingtmp".$dictId."_".$tokenLength."` "
                    . "WHERE `userid`=$userid AND `entry`=$entry";
            $resArr4 = $this->db->getResultArray($sqlq);
            return $resArr4;
        }
        
        function AddToken($dictId, $documentid, $page, $version, $tokentxt, $tokenpos, $footnote, $tokenLength, ...$args){
            if($dictId===1)
                $sqlq = "INSERT INTO `tblVoting".$dictId."_".$tokenLength."`(`documentid`, `pageno`, `position`, `token`, `footnote`, `version`) VALUES "
                    . "($documentid, $page, $tokenpos, '$tokentxt', '$footnote', $version)";
            else if($dictId===2)
                $sqlq = "INSERT INTO `tblVoting".$dictId."_".$tokenLength."`(`documentid`, `pageno`, `position`, `token`, `footnote`, `version`, `zhuyin`, `pinyin`, `attribute`) VALUES "
                    . "($documentid, $page, $tokenpos, '$tokentxt', '$footnote', $version, '$args[0]', '$args[1]', '$args[2]')";    
            $resArr1 = $this->db->getResultArray($sqlq);
            return $resArr1;
        }
        
        function AddVotingUser($dictId, $documentid, $page, $version, $userid, $tokentxt, $tokenpos, $footnote, $tokenLength){
            $sqlq = "SELECT `id` FROM `tblVoting".$dictId."_".$tokenLength."` WHERE`documentid`=$documentid "
                    . "AND `pageno` =$page AND `version` =$version AND `position` =$tokenpos AND `footnote` ='$footnote'";
            $resArr2 = $this->db->getResultArray($sqlq);
            $entry=$resArr2[0]['id'];
            $sqlq="SELECT * FROM `tblUserVoting".$dictId."` WHERE `userid`=$userid AND `entry`=$entry AND `tokenLength`=$tokenLength";
            $resArr3 = $this->db->getResultArray($sqlq);
            if(!$resArr3){
                $sqlq="INSERT INTO `tblUserVoting".$dictId."`(`userid`, `entry`, `tokenLength`) VALUES ($userid, $entry, $tokenLength)";
                $resArr4 = $this->db->getResultArray($sqlq);
                return $resArr4;
            }
            return $resArr3;
        }
        

        function getfileinfolder($folderid){

				$sqlq = "SELECT `id` FROM `tblDocuments` WHERE `folderList` LIKE '%:".$folderid.":%'";
				$resArr = $this->db->getResultArray($sqlq);

				$arr= array();
				foreach ($resArr as $res) {
					$arr[]=$res['id'];
					//file_put_contents("/home/www-data/seeddms51x/debug.txt", $res['id'], FILE_APPEND);

				}
            	return $arr;
        }

        function GetNoteVote($noteid,$userid){
        	$arr= array();
        	$sqlq = "SELECT * FROM `tblNoteVote` WHERE `userid` = $userid AND `noteid`=$noteid";
        	$resArr = $this->db->getResultArray($sqlq);
        	if(sizeof($resArr)==0){
        		$arr[]=0;
        	}elseif($resArr[0]['likeIcon']==1){
				$arr[]=1;
        	}else{
        		$arr[]=-1;
        	}
        	$sqlq = "SELECT * FROM `tblNoteVote` WHERE `likeIcon` = 1 AND `noteid`=$noteid";
        	$resArr = $this->db->getResultArray($sqlq);
        	if(sizeof($resArr)==0){
        		$arr[]=0;
        	}else{
        		$arr[]=sizeof($resArr);
        	}
        	$sqlq = "SELECT * FROM `tblNoteVote` WHERE `dislikeIcon` = 1 AND `noteid`=$noteid";
        	$resArr = $this->db->getResultArray($sqlq);
        	if(sizeof($resArr)==0){
        		$arr[]=0;
        	}else{
        		$arr[]=sizeof($resArr);
        	}
        	return $arr;
        }
        function modiNoteVote($noteid,$userid,$modi){
            $sqlq="DELETE FROM `tblNoteVote` WHERE `userid` = $userid AND `noteid`=$noteid";
            $resArr = $this->db->getResultArray($sqlq);
            if($modi==2 || $modi==4){
                    return;
            }elseif($modi==1){
                    $sqlq="INSERT INTO `tblNoteVote`(`noteid`, `userid`, `likeIcon`, `dislikeIcon`) VALUES ($noteid, $userid, 1, 0)";
            }else{
                    $sqlq="INSERT INTO `tblNoteVote`(`noteid`, `userid`, `likeIcon`, `dislikeIcon`) VALUES ($noteid, $userid, 0, 1)";
            }
            $resArr = $this->db->getResultArray($sqlq);
            return;

        }
        function AddNoti($data){
            $sqlq = "INSERT INTO `tblUserNoti` (`id`, `type`, `link`, `msg`, `userid`, `status`, `time`) VALUES "
                    . "(NULL, $data[0],$data[1],$data[2],$data[3], 1,'".date("Y-m-d H:i:s")."')";
            //file_put_contents("/home/www-data/seeddms51x/debug.txt", $sqlq, FILE_APPEND);
            $resArr = $this->db->getResultArray($sqlq);
            return;
        }
        function NotiStatus($userid,$id,$newstat){
            $sqlq = "UPDATE `tblUserNoti` SET `status`=".$newstat." WHERE `id`=".$id;
            //file_put_contents("/home/www-data/seeddms51x/debug.txt", $sqlq, FILE_APPEND);
            $resArr = $this->db->getResultArray($sqlq);
             
            $sqlq="SELECT * FROM `tblUserNoti` WHERE `userid` = $userid AND `status`=2";
            $resArr = $this->db->getResultArray($sqlq);
            if(sizeof($resArr)>0){
                $arr=array();
                foreach($resArr as $row){
                    $arr[]=$row['id'];
                }
                if(sizeof($arr)>4){
                    for($i=0;$i<sizeof($arr)-3;$i++){
                        $sqlq="DELETE FROM `tblUserNoti` WHERE `id` = ".$arr[$i];
                        $resArr = $this->db->getResultArray($sqlq);
                    }
                }
            }
            return;
        }
        function getHighlightReminder($userid){
            $sqlq="SELECT `highlight` FROM `tblUserReminder` WHERE `userid` = $userid";
            $resArr = $this->db->getResultArray($sqlq);
            return $resArr[0]['highlight'];
        }
        function UpdateHighlightReminder($userid){
            $sqlq="UPDATE `tblUserReminder` SET `highlight`=0 WHERE `userid` = $userid";
            $res = $this->db->getResultArray($sqlq);
            return $res;
        }
//        function CheckHighlight($docid, $version, $pageno, $pos, $len, $userid){
//            $sqlq="SELECT * FROM `tblHighlight` WHERE (`documentid`= $docid AND `pageno`= $pageno AND`version`= $version AND`userid`= $userid AND `pos`>=".$pos." AND `pos`<=".$pos."+".$len.")"
//                    . "OR (`documentid`= $docid AND `pageno`= $pageno AND`version`= $verion AND`userid`= $userid AND `txtstart`<=".$pos." AND `txtstart`+`txtlen`>=".$pos;
//            $res = $this->db->getResultArray($sqlq);
//            if(sizeof($res)>0){
//                return 
//            }
//        }
}
