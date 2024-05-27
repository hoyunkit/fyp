<?php
//    MyDMS. Document Management System
//    Copyright (C) 2002-2005  Markus Westphal
//    Copyright (C) 2006-2008 Malcolm Cowe
//    Copyright (C) 2010 Matteo Lucarelli
//    Copyright (C) 2010-2016 Uwe Steinmann
//
//    This program is free software; you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation; either version 2 of the License, or
//    (at your option) any later version.
//
//    This program is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You should have received a copy of the GNU General Public License
//    along with this program; if not, write to the Free Software
//    Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.

include("../inc/inc.Settings.php");
include("../inc/inc.LogInit.php");
include("../inc/inc.Language.php");
include("../inc/inc.Init.php");
include("../inc/inc.Extension.php");
include("../inc/inc.DBInit.php");
include("../inc/inc.ClassUI.php");
include("../inc/inc.Authentication.php");

if (!$user->isAdmin()) {
  UI::exitError(getMLText("admin_tools"), getMLText("access_denied"));
}

if (isset($_POST["action"])) $action = $_POST["action"];
else $action = null;

// Edit access --------------------------------------------------------
if ($action == "editaccess") {
  // Check if the form data comes from a trusted request
  if (!checkFormKey('editaccess')) {
    UI::exitError(getMLText("admin_tools"), getMLText("invalid_request_token"));
  }

  // Check if accessName is posted and it is valid string
  if (!isset($_POST["accessName"]) || !is_string($_POST["accessName"])) {
    UI::exitError(getMLText("admin_tools"), getMLText("invalid_access_name"));
  }

  $accessName = $_POST['accessName'];
  $access = $dms->getAccessByName($accessName);

  if (!is_object($access)) {
    UI::exitError(getMLText("admin_tools"), getMLText("invalid_access_name"));
  }

  // Get the input field
  $name = $_POST["name"];
  $comment = $_POST["comment"];

  if ($access->getName() !== $name) {
    $access->setName($name);
  }

  if ($access->getComment() !== $comment) {
    $access->setComment($comment);
  }

  $session->setSplashMsg(array('type' => 'success', 'msg' => getMLText('splash_edit_access')));

  add_log_line("?accessName=" . $_POST["accessName"] . "&action=editaccess");
} else if ($action == "editprivileges") {
  $privileges = $_POST['privileges'];
  $accessName = $_POST['accessName'];
  $access = $dms->getAccessByName($accessName);
  $fix = $access->getFix();
  echo $fix;
  $access->setPrivileges($privileges, $accessName, $fix);
} else if ($action == "addprivilege") {
  if (!checkFormKey('addprivilege')) {
    UI::exitError(getMLText("admin_tools"), getMLText("invalid_request_token"));
  }

  // Check if accessName is posted and it is valid string
  if (!isset($_POST["newPrivilege"]) || !is_string($_POST["newPrivilege"])) {
    UI::exitError(getMLText("admin_tools"), getMLText("invalid_new_privilege"));
  }

  $accessName = $_POST['accessName'];
  $newPrivilege = $_POST['newPrivilege'];
  $lowerNewPrivilege = strtolower($newPrivilege);
  $splitNewPrivilege = preg_split('/\s+/', $lowerNewPrivilege);
  $firstLetter = $splitNewPrivilege[0];
  $secondLetter = $splitNewPrivilege[1];
  $fullLetter = $firstLetter . '_' . $secondLetter;
  $privArr = [];
  $privArr[0] = $fullLetter;
  $access = $dms->getAccessByName($accessName);
  $access->addPrivilege($privArr);
} else if ($action == 'dropprivilege') {
  if (!checkFormKey('dropprivilege')) {
    UI::exitError(getMLText("admin_tools"), getMLText("invalid_request_token"));
  }

  // The privilege which will be droped
  $privileges = $_POST['privileges'];
  // Current access (actually, it is not used)
  $accessName = $_POST['accessName'];

  $access = $dms->getAccessByName($accessName);
  $access->dropPrivilege($privileges);
} else if ($action == 'dropaccess') {
  if (!checkFormKey('dropaccess')) {
    UI::exitError(getMLText("admin_tools"), getMLText("invalid_request_token"));
  }

  // Current access
  $accessName = $_POST['accessName'];

  // Fixed access permission, cannot remove
  // Glenn edited on 13 April, 2022
  if ($accessName === 'All access' || $accessName === 'No access' || $accessName === 'Read' || $accessName === 'Read-Write') {
    UI::exitError(getMLText("admin_tools"), getMLText("invalid_remove_access"));
  } else {
    $access = $dms->getAccessByName($accessName);
    $canDrop = $dms->dropAccessDecision($access->getMode());
    if ($canDrop) {
      $access->dropAccess($access->getName(), $access->getMode());
    } else {
      UI::exitError(getMLText("admin_tools"), getMLText("occupied_access_mode"));
    }
  }
} else if ($action == 'addaccess') {
  // Check if the form data comes from a trusted request
  if (!checkFormKey('addaccess')) {
    UI::exitError(getMLText("admin_tools"), getMLText("invalid_request_token"));
  }

  // Receive the value from form_1
  $newName = $_POST['name'];
  $newComment = $_POST['comment'];
  $newMode = $_POST['mode'];
  $privileges = $_POST['privileges'];

  $privileges = array_flip($privileges);

  if (empty($newName) || empty($newMode) || empty($privileges)) {
    UI::exitError(getMLText("admin_tools"), getMLText("empty_field_error"));
  } else {
    $dms->addNewAccess($newName, $newComment, $newMode, $privileges);
  }
}

// $accessname is undefined now
header("Location:../out/out.AccessMgr.php?accessName=" . $accessName);
