<?php
/**
 * Implementation of the group object in the document management system
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
 * Class to represent a user group in the document management system
 *
 * @category   DMS
 * @package    SeedDMS_Core
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal, 2006-2008 Malcolm Cowe, 2010 Uwe Steinmann
 * @version    Release: 5.1.9
 */
class SeedDMS_Core_Group { /* {{{ */
	/**
	 * The id of the user group
	 *
	 * @var integer
	 */
	protected $_id;

	/**
	 * The name of the user group
	 *
	 * @var string
	 */
	protected $_name;

	/**
	 * @var SeedDMS_Core_User[]
	 */
	protected $_users;

	/**
	 * The comment of the user group
	 *
	 * @var string
	 */
	protected $_comment;

	/**
	 * Back reference to DMS this user group belongs to
	 *
	 * @var SeedDMS_Core_DMS
	 */
	protected $_dms;

	function __construct($id, $name, $comment) { /* {{{ */
		$this->_id = $id;
		$this->_name = $name;
		$this->_comment = $comment;
		$this->_dms = null;
	} /* }}} */

	/**
	 * Return an instance of a group object
	 *
	 * @param string|integer $id Id, name of group, depending
	 * on the 3rd parameter.
	 * @param SeedDMS_Core_DMS $dms instance of dms
	 * @param string $by search by group name if set to 'name'. 
	 * Search by Id of group if left empty.
	 * @return SeedDMS_Core_Group|bool instance of class SeedDMS_Core_Group if group was
	 * found, null if group was not found, false in case of error
	 */
	public static function getInstance($id, $dms, $by='') { /* {{{ */
		$db = $dms->getDB();

		switch($by) {
		case 'name':
			$queryStr = "SELECT * FROM `tblGroups` WHERE `name` = ".$db->qstr($id);
			break;
		default:
			$queryStr = "SELECT * FROM `tblGroups` WHERE `id` = " . (int) $id;
		}

		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr == false)
			return false;
		else if (count($resArr) != 1) //wenn, dann wohl eher 0 als > 1 ;-)
			return null;

		$resArr = $resArr[0];

		$group = new self($resArr["id"], $resArr["name"], $resArr["comment"]);
		$group->setDMS($dms);
		return $group;
	} /* }}} */

	/**
	 * @param $orderby
	 * @param SeedDMS_Core_DMS $dms
	 * @return array|bool
	 */
	public static function getAllInstances($orderby, $dms) { /* {{{ */
		$db = $dms->getDB();

		switch($orderby) {
		default:
			$queryStr = "SELECT * FROM `tblGroups` ORDER BY `name`";
		}
		$resArr = $db->getResultArray($queryStr);

		if (is_bool($resArr) && $resArr == false)
			return false;

		$groups = array();
		for ($i = 0; $i < count($resArr); $i++) {
			$group = new self($resArr[$i]["id"], $resArr[$i]["name"], $resArr[$i]["comment"]);
			$group->setDMS($dms);
			$groups[$i] = $group;
		}

		return $groups;
	} /* }}} */

	/**
	 * @param SeedDMS_Core_DMS $dms
	 */
	function setDMS($dms) { /* {{{ */
		$this->_dms = $dms;
	} /* }}} */

	/**
	 * @return int
	 */
	function getID() { return $this->_id; }

	/**
	 * @return string
	 */
	function getName() { return $this->_name; }

	/**
	 * @param $newName
	 * @return bool
	 */
	function setName($newName) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE `tblGroups` SET `name` = ".$db->qstr($newName)." WHERE `id` = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		$this->_name = $newName;
		return true;
	} /* }}} */

	/**
	 * @return string
	 */
	function getComment() { return $this->_comment; }

	/**
	 * @param $newComment
	 * @return bool
	 */
	function setComment($newComment) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE `tblGroups` SET `comment` = ".$db->qstr($newComment)." WHERE `id` = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;

		$this->_comment = $newComment;
		return true;
	} /* }}} */

	/**
	 * @return SeedDMS_Core_User[]|bool
	 */
	function getUsers() { /* {{{ */
		$db = $this->_dms->getDB();

		if (!isset($this->_users)) {
			$queryStr = "SELECT `tblUsers`.* FROM `tblUsers` ".
				"LEFT JOIN `tblGroupMembers` ON `tblGroupMembers`.`userID`=`tblUsers`.`id` ".
				"WHERE `tblGroupMembers`.`groupID` = '". $this->_id ."'";
			$resArr = $db->getResultArray($queryStr);
			if (is_bool($resArr) && $resArr == false)
				return false;

			$this->_users = array();

			$classname = $this->_dms->getClassname('user');
			foreach ($resArr as $row) {
				/** @var SeedDMS_Core_User $user */
				$user = new $classname($row["id"], $row["login"], $row["pwd"], $row["fullName"], $row["email"], $row["language"], $row["theme"], $row["comment"], $row["role"], $row['hidden']);
				array_push($this->_users, $user);
			}
		}
		return $this->_users;
	} /* }}} */

	/**
	 * @return SeedDMS_Core_User[]|bool
	 */
	function getManagers() { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "SELECT `tblUsers`.* FROM `tblUsers` ".
			"LEFT JOIN `tblGroupMembers` ON `tblGroupMembers`.`userID`=`tblUsers`.`id` ".
			"WHERE `tblGroupMembers`.`groupID` = '". $this->_id ."' AND `tblGroupMembers`.`manager` = 1";
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr == false)
			return false;

		$managers = array();

		$classname = $this->_dms->getClassname('user');
		foreach ($resArr as $row) {
			/** @var SeedDMS_Core_User $user */
			$user = new $classname($row["id"], $row["login"], $row["pwd"], $row["fullName"], $row["email"], $row["language"], $row["theme"], $row["comment"], $row["role"], $row['hidden']);
			array_push($managers, $user);
		}
		return $managers;
	} /* }}} */

	/**
	 * @param SeedDMS_Core_User $user
	 * @param bool $asManager
	 * @return bool
	 */
	function addUser($user,$asManager=false) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "INSERT INTO `tblGroupMembers` (`groupID`, `userID`, `manager`) VALUES (".$this->_id.", ".$user->getID(). ", " . ($asManager?"1":"0") ." )";
		$res = $db->getResult($queryStr);

		if (!$res) return false;

		unset($this->_users);
		return true;
	} /* }}} */

	/**
	 * @param SeedDMS_Core_User $user
	 * @return bool
	 */
	function removeUser($user) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "DELETE FROM `tblGroupMembers` WHERE `groupID` = ".$this->_id." AND `userID` = ".$user->getID();
		$res = $db->getResult($queryStr);

		if (!$res) return false;
		unset($this->_users);
		return true;
	} /* }}} */

	/**
	 * Check if user is member of group
	 *
	 * @param SeedDMS_Core_User $user user to be checked
	 * @param boolean $asManager also check whether user is manager of group if
	 * set to true, otherwise does not care about manager status
	 * @return boolean true if user is member, otherwise false
	 */
	function isMember($user,$asManager=false) { /* {{{ */
		if (isset($this->_users)&&!$asManager) {
			foreach ($this->_users as $usr)
				if ($usr->getID() == $user->getID())
					return true;
			return false;
		}

		$db = $this->_dms->getDB();
		if ($asManager) $queryStr = "SELECT * FROM `tblGroupMembers` WHERE `groupID` = " . $this->_id . " AND `userID` = " . $user->getID() . " AND `manager` = 1";
		else $queryStr = "SELECT * FROM `tblGroupMembers` WHERE `groupID` = " . $this->_id . " AND `userID` = " . $user->getID();

		$resArr = $db->getResultArray($queryStr);

		if (is_bool($resArr) && $resArr == false) return false;
		if (count($resArr) != 1) return false;

		return true;
	} /* }}} */

	/**
	 * Toggle manager status of user
	 *
	 * @param SeedDMS_Core_User $user
	 * @return boolean true if operation was successful, otherwise false
	 */
	function toggleManager($user) { /* {{{ */
		$db = $this->_dms->getDB();

		if (!$this->isMember($user)) return false;

		if ($this->isMember($user,true)) $queryStr = "UPDATE `tblGroupMembers` SET `manager` = 0 WHERE `groupID` = ".$this->_id." AND `userID` = ".$user->getID();
		else $queryStr = "UPDATE `tblGroupMembers` SET `manager` = 1 WHERE `groupID` = ".$this->_id." AND `userID` = ".$user->getID();

		if (!$db->getResult($queryStr)) return false;
		return true;
	} /* }}} */
        
        /**
	 * Group member follow group access.
	 *
	 * @param SeedDMS_Core_User $user
	 * @return boolean true if operation was successful, otherwise false
	 */
	function followGroupAction($user)
	{
		$db = $this->_dms->getDB();

		// At this moment, user already added to group
		if (!$this->isMember($user)) return false;

		$query = "UPDATE `tblGroupMembers` SET `followGroup` = 1 WHERE `groupID` = " . $this->_id . " AND `userID` = " . $user->getID();
		if (!$db->getResult($query)) return false;
		return true;
	}

	// Glenn edited on 10 May, 2022
	function removeUserFromGroupAction($user)
	{
		$db = $this->_dms->getDB();
		$groupFolderAccessRecord = []; // key is folderid, value is mode
		$groupDocumentAccessRecord = []; // key is documentid, value is mode
		$folderCollection = [];
		$documentCollection = [];

		// Determine if this selected user follows this group
		$query1 = "SELECT * FROM `tblGroupMembers` WHERE `userID` = " . $user->getID() . " AND `groupID` = " . $this->_id;
		$result1 = $db->getResultArray($query1);
		if ($result1) {
			// Find all the records in tblACLs table for this group, we only need the target(folderid, documentid) and mode...
			// ...save these records in $groupFolderAccessRecord and $groupDocumentAccessRecord
			$query2 = "SELECT `target`, `mode` FROM `tblACLs` WHERE `targetType` = 1 AND `userID` = -1 AND `groupID` = " . $this->_id;
			$result2 = $db->getResultArray($query2);
			$query9 = "SELECT `target`, `mode` FROM `tblACLs` WHERE `targetType` = 2 AND `userID` = -1 AND `groupID` = " . $this->_id;
			$result9 = $db->getResultArray($query9);

			// Save group's folder access record
			foreach ($result2 as $key) {
				$groupFolderAccessRecord[$key['target']] = $key['mode'];
			}

			// Save group's document access record
			foreach ($result9 as $key) {
				$groupDocumentAccessRecord[$key['target']] = $key['mode'];
			}

			// Delete this user's folder access following this group
			foreach ($groupFolderAccessRecord as $key => $val) {
				$query3 = "SELECT * FROM `tblACLs` WHERE `target` = " . $key . " AND `targetType` = 1 AND `userID` = " . $user->getID() . " AND `groupID` = -1 AND `mode` = " . $val . " AND `followGroup` = 1";
				$result3 = $db->getResultArray($query3);
				if ($result3) {
					$query4 = "DELETE FROM `tblACLs` WHERE `target` = " . $key . " AND `targetType` = 1 AND `userID` = " . $user->getID() . " AND `groupID` = -1 AND `mode` = " . $val . " AND `followGroup` = 1";
					$result4 = $db->getResult($query4);
				}
			}

			// Delete this user's document access following this group
			foreach ($groupDocumentAccessRecord as $key => $val) {
				$query10 = "SELECT * FROM `tblACLs` WHERE `target` = " . $key . " AND `targetType` = 2 AND `userID` = " . $user->getID() . " AND `groupID` = -1 AND `mode` = " . $val . " AND `followGroup` = 1";
				$result10 = $db->getResultArray($query10);
				if ($result10) {
					$query11 = "DELETE FROM `tblACLs` WHERE `target` = " . $key . " AND `targetType` = 2 AND `userID` = " . $user->getID() . " AND `groupID` = -1 AND `mode` = " . $val . " AND `followGroup` = 1";
					$result11 = $db->getResult($query11);
				}
			}

			// Remove user from group
			$query5 = "DELETE FROM `tblGroupMembers` WHERE `groupID` = " . $this->_id . " AND `userID` = " . $user->getID();
			$result5 = $db->getResult($query5);

			if ($result5) {
				// Find the group this user follows
				$query6 = "SELECT `groupID` FROM `tblGroupMembers` WHERE `followGroup` = 1 AND `userID` = " . $user->getID();
				$result6 = $db->getResultArray($query6);
				if ($result6) {
					foreach ($result6 as $key => $val) {
						$groupid = $val['groupID'];

						// Determine if the group has any accesses in folders (targetType = 1 means that folder)
						$query7 = "SELECT * FROM `tblACLs` WHERE `targetType` = 1 AND `userID` = -1 AND `groupID` = " . $groupid;
						$result7 = $db->getResultArray($query7);

						// If exists
						if ($result7) {
							$query8 = "SELECT * FROM `tblACLs` WHERE `targetType` = 1 AND `userID` = -1 AND `groupID` = " . $groupid;
							$result8 = $db->getResultArray($query8);
							// Append folder and mode as the key-value pairs
							foreach ($result8 as $key => $val) {
								$folderid = $val['target'];
								$mode = $val['mode'];

								// Compare the mode if the same folderid exists, choose the higher priority mode and replace the old one
								if (array_key_exists($folderid, $folderCollection)) {
									$oldMode = $folderCollection[$folderid];
									if ($mode > $oldMode) {
										$folderCollection[$folderid] = $mode;
									}
								} else {
									$folderCollection[$folderid] = $mode;
								}
							}
						}

						// Determine if the group has any accesses in documents (targetType = 2 means that document)
						$query12 = "SELECT * FROM `tblACLs` WHERE `targetType` = 2 AND `userID` = -1 AND `groupID` = " . $groupid;
						$result12 = $db->getResultArray($query12);

						// If exists
						if ($result12) {
							$query13 = "SELECT * FROM `tblACLs` WHERE `targetType` = 2 AND `userID` = -1 AND `groupID` = " . $groupid;
							$result13 = $db->getResultArray($query13);
							// Append document and mode as the key-value pairs
							foreach ($result13 as $key => $val) {
								$documentid = $val['target'];
								$mode = $val['mode'];

								// Compare the mode if the same documentid exists, choose the higher priority mode and replace the old one
								if (array_key_exists($documentid, $documentCollection)) {
									$oldMode = $documentCollection[$documentid];
									if ($mode > $oldMode) {
										$documentCollection[$documentid] = $mode;
									}
								} else {
									$documentCollection[$documentid] = $mode;
								}
							}
						}
					}
				}
			}

			$this->_dms->handleUserLeaveGroupAccess($user->getID(), $folderCollection, $documentCollection);
		}
	}

	// Glenn edited on 9 May, 2022
	function followGroupFolderAccessOperation($user)
	{
		// 1. 尋找此user所有存在的groupID，並且followGroup爲1的情況 ✅
		// 2. 根據groupID在table tblACLs尋找對應的mode ✅
		// 3. 比較mode的大小，選取最大的mode ✅
		// 4. 根據userID在table tblACLs尋找對應的mode，如果存在就將此mode存入table tblFolderAccessBase ✅
		$db = $this->_dms->getDB();
		$collection = []; // Highest priority folder access

		// Select all groups whcih this user follow it
		$query1 = "SELECT `groupID` FROM `tblGroupMembers` WHERE `followGroup` = 1 AND `userID` = " . $user->getID();
		$result1 = $db->getResultArray($query1);
		// 存取值 key-value pairs之後 對比
		if ($result1) {
			foreach ($result1 as $key => $val) {
				$groupid = $val['groupID'];

				// Determine if the group has any accesses in folders (targetType = 1 means that folder)
				$query2 = "SELECT * FROM `tblACLs` WHERE `targetType` = 1 AND `userID` = -1 AND `groupID` = " . $groupid;
				$result2 = $db->getResultArray($query2);

				// If exists
				if ($result2) {
					$query3 = "SELECT * FROM `tblACLs` WHERE `targetType` = 1 AND `userID` = -1 AND `groupID` = " . $groupid;
					$result3 = $db->getResultArray($query3);
					// Append folder and mode as the key-value pairs
					foreach ($result3 as $key => $val) {
						$folderid = $val['target'];
						$mode = $val['mode'];

						// Compare the mode if the same folderid exists, choose the higher priority mode and replace the old one
						if (array_key_exists($folderid, $collection)) {
							$oldMode = $collection[$folderid];
							if ($mode > $oldMode) {
								$collection[$folderid] = $mode;
							}
						} else {
							$collection[$folderid] = $mode;
						}
					}
				}
			}

			$this->_dms->handleUserFollowGroupFolderAccess($user->getID(), $collection);
		}
	}

	// Glenn edited on 9 May, 2022
	function followGroupDocumentAccessOperation($user)
	{
		$db = $this->_dms->getDB();
		$collection = []; // Highest priority document access

		// Select all groups whcih this user follow it
		$query1 = "SELECT `groupID` FROM `tblGroupMembers` WHERE `followGroup` = 1 AND `userID` = " . $user->getID();
		$result1 = $db->getResultArray($query1);
		// 存取值 key-value pairs之後 對比
		if ($result1) {
			foreach ($result1 as $key => $val) {
				$groupid = $val['groupID'];

				// Determine if the group has any accesses in folders (targetType = 1 means that folder)
				$query2 = "SELECT * FROM `tblACLs` WHERE `targetType` = 2 AND `userID` = -1 AND `groupID` = " . $groupid;
				$result2 = $db->getResultArray($query2);

				// If exists
				if ($result2) {
					$query3 = "SELECT * FROM `tblACLs` WHERE `targetType` = 2 AND `userID` = -1 AND `groupID` = " . $groupid;
					$result3 = $db->getResultArray($query3);
					// Append document and mode as the key-value pairs
					foreach ($result3 as $key => $val) {
						$documentid = $val['target'];
						$mode = $val['mode'];

						// Compare the mode if the same documentid exists, choose the higher priority mode and replace the old one
						if (array_key_exists($documentid, $collection)) {
							$oldMode = $collection[$documentid];
							if ($mode > $oldMode) {
								$collection[$documentid] = $mode;
							}
						} else {
							$collection[$documentid] = $mode;
						}
					}
				}
			}

			$this->_dms->handleUserFollowGroupDocumentAccess($user->getID(), $collection);
		}
	}
        
	/**
	 * Delete user group
	 * This function deletes the user group and all it references, like access
	 * control lists, notifications, as a child of other groups, etc.
	 *
	 * @param SeedDMS_Core_User $user the user doing the removal (needed for entry in
	 *        review log.
	 * @return boolean true on success or false in case of an error
	 */
	function remove($user) { /* {{{ */
		$db = $this->_dms->getDB();

		$db->startTransaction();

		$queryStr = "DELETE FROM `tblGroupMembers` WHERE `groupID` = " . $this->_id;
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}
		$queryStr = "DELETE FROM `tblACLs` WHERE `groupID` = " . $this->_id;
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}
		$queryStr = "DELETE FROM `tblNotify` WHERE `groupID` = " . $this->_id;
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}
		$queryStr = "DELETE FROM `tblMandatoryReviewers` WHERE `reviewerGroupID` = " . $this->_id;
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}
		$queryStr = "DELETE FROM `tblMandatoryApprovers` WHERE `approverGroupID` = " . $this->_id;
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}
		$queryStr = "DELETE FROM `tblWorkflowTransitionGroups` WHERE `groupid` = " . $this->_id;
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}
		$queryStr = "DELETE FROM `tblGroups` WHERE `id` = " . $this->_id;
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		// TODO : update document status if reviewer/approver has been deleted


		$reviewStatus = $this->getReviewStatus();
		foreach ($reviewStatus as $r) {
			$queryStr = "INSERT INTO `tblDocumentReviewLog` (`reviewID`, `status`, `comment`, `date`, `userID`) ".
				"VALUES ('". $r["reviewID"] ."', '-2', 'Review group removed from process', ".$db->getCurrentDatetime().", '". $user->getID() ."')";
			$res=$db->getResult($queryStr);
			if(!$res) {
				$db->rollbackTransaction();
				return false;
			}
		}

		$approvalStatus = $this->getApprovalStatus();
		foreach ($approvalStatus as $a) {
			$queryStr = "INSERT INTO `tblDocumentApproveLog` (`approveID`, `status`, `comment`, `date`, `userID`) ".
				"VALUES ('". $a["approveID"] ."', '-2', 'Approval group removed from process', ".$db->getCurrentDatetime().", '". $user->getID() ."')";
			$res=$db->getResult($queryStr);
			if(!$res) {
				$db->rollbackTransaction();
				return false;
			}
		}

		$db->commitTransaction();

		return true;
	} /* }}} */

	function getReviewStatus($documentID=null, $version=null) { /* {{{ */
		$db = $this->_dms->getDB();

		if (!$db->createTemporaryTable("ttreviewid")) {
			return false;
		}

		$status = array();

		// See if the group is assigned as a reviewer.
		$queryStr = "SELECT `tblDocumentReviewers`.*, `tblDocumentReviewLog`.`status`, ".
			"`tblDocumentReviewLog`.`comment`, `tblDocumentReviewLog`.`date`, ".
			"`tblDocumentReviewLog`.`userID` ".
			"FROM `tblDocumentReviewers` ".
			"LEFT JOIN `tblDocumentReviewLog` USING (`reviewID`) ".
			"LEFT JOIN `ttreviewid` on `ttreviewid`.`maxLogID` = `tblDocumentReviewLog`.`reviewLogID` ".
			"WHERE `ttreviewid`.`maxLogID`=`tblDocumentReviewLog`.`reviewLogID` ".
			($documentID==null ? "" : "AND `tblDocumentReviewers`.`documentID` = '". (int) $documentID ."' ").
			($version==null ? "" : "AND `tblDocumentReviewers`.`version` = '". (int) $version ."' ").
			"AND `tblDocumentReviewers`.`type`='1' ".
			"AND `tblDocumentReviewers`.`required`='". $this->_id ."' ";
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr == false)
			return false;
		if (count($resArr)>0) {
			foreach ($resArr as $res)
				$status[] = $res;
		}
		return $status;
	} /* }}} */

	function getApprovalStatus($documentID=null, $version=null) { /* {{{ */
		$db = $this->_dms->getDB();

		if (!$db->createTemporaryTable("ttapproveid")) {
			return false;
		}

		$status = array();

		// See if the group is assigned as an approver.
		$queryStr = "SELECT `tblDocumentApprovers`.*, `tblDocumentApproveLog`.`status`, ".
			"`tblDocumentApproveLog`.`comment`, `tblDocumentApproveLog`.`date`, ".
			"`tblDocumentApproveLog`.`userID` ".
			"FROM `tblDocumentApprovers` ".
			"LEFT JOIN `tblDocumentApproveLog` USING (`approveID`) ".
			"LEFT JOIN `ttapproveid` on `ttapproveid`.`maxLogID` = `tblDocumentApproveLog`.`approveLogID` ".
			"WHERE `ttapproveid`.`maxLogID`=`tblDocumentApproveLog`.`approveLogID` ".
			($documentID==null ? "" : "AND `tblDocumentApprovers`.`documentID` = '". (int) $documentID ."' ").
			($version==null ? "" : "AND `tblDocumentApprovers`.`version` = '". (int) $version ."' ").
			"AND `tblDocumentApprovers`.`type`='1' ".
			"AND `tblDocumentApprovers`.`required`='". $this->_id ."' ";
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr == false)
			return false;
		if (count($resArr)>0) {
			foreach ($resArr as $res)
				$status[] = $res;
		}

		return $status;
	} /* }}} */

	/**
	 * Get a list of documents with a workflow
	 *
	 * @param int $documentID optional document id for which to retrieve the
	 *        reviews
	 * @param int $version optional version of the document
	 * @return bool|array list of all workflows
	 */
	function getWorkflowStatus($documentID=null, $version=null) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = 'select distinct d.*, c.`groupid` from `tblWorkflowTransitions` a left join `tblWorkflows` b on a.`workflow`=b.`id` left join `tblWorkflowTransitionGroups` c on a.`id`=c.`transition` left join `tblWorkflowDocumentContent` d on b.`id`=d.`workflow` where d.`document` is not null and a.`state`=d.`state` and c.`groupid`='.$this->_id;
		if($documentID) {
			$queryStr .= ' AND d.`document`='.(int) $documentID;
			if($version)
				$queryStr .= ' AND d.`version`='.(int) $version;
		}
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr == false)
			return false;
		$result = array();
		if (count($resArr)>0) {
			foreach ($resArr as $res) {
				$result[] = $res;
			}
		}
		return $result;
	} /* }}} */

	/**
	 * Get all notifications of group
	 *
	 * @param integer $type type of item (T_DOCUMENT or T_FOLDER)
	 * @return SeedDMS_Core_Notification[]|bool array of notifications
	 */
	function getNotifications($type=0) { /* {{{ */
		$db = $this->_dms->getDB();
		$queryStr = "SELECT `tblNotify`.* FROM `tblNotify` ".
		 "WHERE `tblNotify`.`groupID` = ". $this->_id;
		if($type) {
			$queryStr .= " AND `tblNotify`.`targetType` = ". (int) $type;
		}

		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && !$resArr)
			return false;

		$notifications = array();
		foreach ($resArr as $row) {
			$not = new SeedDMS_Core_Notification($row["target"], $row["targetType"], $row["userID"], $row["groupID"]);
			$not->setDMS($this);
			array_push($notifications, $not);
		}

		return $notifications;
	} /* }}} */

	function getGroupPermit($col="*"){
		$db = $this->_dms->getDB();
		$queryStr = "SELECT ".$col." FROM `tblGroupPermit` WHERE  `id`= ". $this->_id;
		
		$resArr = $db->getResultArray($queryStr);
		//file_put_contents("/home/www-data/seeddms51x/debug.txt", $queryStr, FILE_APPEND);
		return $resArr;
	}
	function updateGroupPermit($permit){
		$db = $this->_dms->getDB();
		$queryStr = "SELECT * FROM `tblGroupPermit` WHERE  `id`= ". $this->_id;
		$resArr = $db->getResultArray($queryStr);
		array_shift($resArr[0]);
		$queryStr ="UPDATE `tblGroupPermit` SET";

		foreach($resArr[0] as $key => $val) {
			$queryStr .= "`".$key."` =";
			if(in_array($key, $permit)){
				$queryStr .="1,";
			}else{
				$queryStr .="0,";
			}
		}
		$queryStr=substr($queryStr, 0, -1);
		$queryStr .= " WHERE `id`=". $this->_id;
		//file_put_contents("/home/www-data/seeddms51x/debug.txt", $queryStr, FILE_APPEND);
		$resArr = $db->getResultArray($queryStr);
		return true;
	}

} /* }}} */
