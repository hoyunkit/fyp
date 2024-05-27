<?php
//    MyDMS. Document Management System
//    Copyright (C) 2002-2005 Markus Westphal
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

$tmp = explode('.', basename($_SERVER['SCRIPT_FILENAME']));
$view = UI::factory($theme, $tmp[1], array('dms' => $dms, 'user' => $user));
if (!$user->isAdmin()) {
  UI::exitError(getMLText("admin_tools"), getMLText("access_denied"));
}

// All access
$allAccess = $dms->getAllAccess('desc');
if (is_bool($allAccess)) {
  UI::exitError(getMLText("admin_tools"), getMLText("internal_error"));
}

// get access by name
if (isset($_GET['accessName']) && $_GET['accessName']) {
  $selAccess = $dms->getAccessByName($_GET['accessName']);
} else {
  $selAccess = null;
}

// Used for add access action 
$newName = '';
if (isset($_GET['name']) && $_GET['name']) {
  $newName = $_GET['name'];
  $newComment = $_GET['comment'];
} else {
  $newName = null;
}

$newMode = '';
if (isset($_GET['mode']) && $_GET['mode']) {
  $newMode = $_GET['mode'];
} else {
  $newMode = null;
}

$newComment = '';
if (isset($_GET['comment']) && $_GET['comment']) {
  $newComment = $_GET['comment'];
} else {
  $newComment = null;
}

if ($view) {
  $view->setParam('newname', $newName);
  $view->setParam('newmode', $newMode);
  $view->setParam('newcomment', $newComment);
  $view->setParam('allaccess', $allAccess);
  $view->setParam('selaccess', $selAccess);
  $view->setParam('strictformcheck', $settings->_strictFormCheck);
  $view->setParam('cachedir', $settings->_cacheDir);
  $view->setParam('previewWidthList', $settings->_previewWidthList);
  $view->setParam('workflowmode', $settings->_workflowMode);
  $view->setParam('timeout', $settings->_cmdTimeout);
  $view($_GET);
}
