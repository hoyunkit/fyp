<?php
//    MyDMS. Document Management System
//    Copyright (C) 2010 Matteo Lucarelli
//    Copyright (C) 2010-2106 Uwe Steinmann
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
include("../inc/inc.Utils.php");
include("../inc/inc.Language.php");
include("../inc/inc.Init.php");
include("../inc/inc.Extension.php");
include("../inc/inc.DBInit.php");
include("../inc/inc.ClassUI.php");
include("../inc/inc.Authentication.php");
include("../elib/perPagePrep.php");

if (!isset($_POST["documentid"]) || !is_numeric($_POST["documentid"]) || intval($_POST["documentid"])<1) {
	UI::exitError(getMLText("document_title", array("documentname" => getMLText("invalid_doc_id"))),getMLText("invalid_doc_id"));
}

$documentid = $_POST["documentid"];
$document = $dms->getDocument($documentid);

if (!is_object($document)) {
	UI::exitError(getMLText("document_title", array("documentname" => getMLText("invalid_doc_id"))),getMLText("invalid_doc_id"));
}

$folder = $document->getFolder();

//if ($document->getAccessMode($user, 'addDocumentFile') < M_READWRITE) {
//	UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("access_denied"));
//}

$prefix = 'userfile';
if(isset($_POST[$prefix.'-fine-uploader-uuids']) && $_POST[$prefix.'-fine-uploader-uuids']) {
	$uuids = explode(';', $_POST[$prefix.'-fine-uploader-uuids']);
	$names = explode(';', $_POST[$prefix.'-fine-uploader-names']);
	foreach($uuids as $i=>$uuid) {
		$fullfile = $settings->_stagingDir.'/'.utf8_basename($uuid);
		if(file_exists($fullfile)) {
			$finfo = finfo_open(FILEINFO_MIME_TYPE);
			$mimetype = finfo_file($finfo, $fullfile);
			$_FILES["userfile"]['tmp_name'][] = $fullfile;
			$_FILES["userfile"]['type'][] = $mimetype;
			$_FILES["userfile"]['name'][] = isset($names[$i]) ? $names[$i] : $uuid;
			$_FILES["userfile"]['size'][] = filesize($fullfile);
			$_FILES["userfile"]['error'][] = 0;
		}
	}
}

for ($file_num=0;$file_num<count($_FILES["userfile"]["tmp_name"]);$file_num++){
//	if ($_FILES["userfile"]["size"][$file_num]==0) {
//		UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("uploading_zerosize"));
//	}
        if ($_FILES["userfile"]["size"][0]==0) {
		UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("uploading_zerosize"));
	}
	if (is_uploaded_file($_FILES["userfile"]["tmp_name"][$file_num]) && $_FILES['userfile']['error'][$file_num] != 0){
		UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("uploading_failed"));
	};
	if($_FILES["userfile"]["error"][$file_num]) {
		UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("error_occured"));
	}

	if(count($_FILES["userfile"]["tmp_name"]) == 1 && !empty($_POST['name']))
		$name     = $_POST["name"];
	else
		$name = $_FILES["userfile"]['name'][$file_num];
//	$comment  = $_POST["comment"];
//	$version  = (int) $_POST["version"];
//	$public  = (isset($_POST["public"]) && $_POST["public"] == 'true') ? 1 : 0;

//	if($version) {
//		$v = $document->getContentByVersion($version);
//		if(!$v) {
//			UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("error_occured"));
//		}
//	}

	$userfiletmp = $_FILES["userfile"]["tmp_name"][$file_num];
	$userfiletype = $_FILES["userfile"]["type"][$file_num];
	$userfilename = $_FILES["userfile"]["name"][$file_num];

	$fileType = ".".pathinfo($userfilename, PATHINFO_EXTENSION);

	if($settings->_overrideMimeType) {
		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		$userfiletype = finfo_file($finfo, $userfiletmp);
	}

	SeedDMS_Core_File::copyFile($userfiletmp, "../../data/1048576/".$document->getDir()."/book.pdf");
            $currentdir = getcwd();
            chdir("../../data/1048576/".$document->getDir());
            if(!file_exists("book")){
                mkdir("book"); // create a subdirectory in the name of the document version to keep all page images
            }
            shell_exec("pdftoppm -jpeg -r 80 -scale-to 880 book.pdf p && rename 's/p-//' *.jpg ");
            $a = glob('*.jpg'); // collect all image file names to an array
            foreach ($a as $fn) {
            // for each of the file name, remove leading zeros, and move to the destination directory
                $b = str_replace('.jpg', '', $fn);
                $c = 'book/' . (string) ((int) ($b)) . '.jpg';
                rename($fn, $c);
            }
            chdir($currentdir);
}

add_log_line("?name=".$name."&documentid=".$documentid);

header("Location:../out/out.ViewDocument.php?documentid=".$documentid."&currenttab=scannedFiles");


?>
