<?php

include("../inc/inc.Settings.php");
include("../inc/inc.LogInit.php");
include("../inc/inc.Language.php");
include("../inc/inc.Init.php");
include("../inc/inc.Extension.php");
include("../inc/inc.DBInit.php");
include("../inc/inc.ClassUI.php");
include("../inc/inc.Authentication.php");

/* Check if the form data comes from a trusted request */
if(!checkFormKey('removedocumentwordfile')) {
	UI::exitError(getMLText("document_title", array("documentname" => getMLText("invalid_request_token"))),getMLText("invalid_request_token"));
}

if (!isset($_POST["documentid"]) || !is_numeric($_POST["documentid"]) || intval($_POST["documentid"])<1) {
	UI::exitError(getMLText("document_title", array("documentname" => getMLText("invalid_doc_id"))),getMLText("invalid_doc_id"));
}

$documentid = $_POST["documentid"];
$document = $dms->getDocument($documentid);

if (!is_object($document)) {
	UI::exitError(getMLText("document_title", array("documentname" => getMLText("invalid_doc_id"))),getMLText("invalid_doc_id"));
}

if (!isset($_POST["fileid"]) || !is_numeric($_POST["fileid"]) || intval($_POST["fileid"])<1) {
	UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("invalid_file_id"));
}

$fileid = $_POST["fileid"];
$file = $document->getDocumentWordFile($fileid);

if (!is_object($file)) {
	UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("invalid_file_id"));
}


if (($document->getAccessMode($user, 'removeDocumentWordFile') < M_ALL)&&($user->getID()!=$file->getUserID())) {
	UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("access_denied"));
}

/* Remove preview image. */
require_once("SeedDMS/Preview.php");
$previewer = new SeedDMS_Preview_Previewer($settings->_cacheDir);
$previewer->deletePreview($file, $settings->_previewWidthDetail);

if (!$document->removeDocumentWordFile($fileid)) {
	UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("error_occured"));
} else {
	// Send notification to subscribers.
	if($notifier) {
		$notifyList = $document->getNotifyList();

		$subject = "removed_file_email_subject";
		$message = "removed_file_email_body";
		$params = array();
		$params['document'] = $document->getName();
		$params['username'] = $user->getFullName();
		$params['url'] = "http".((isset($_SERVER['HTTPS']) && (strcmp($_SERVER['HTTPS'],'off')!=0)) ? "s" : "")."://".$_SERVER['HTTP_HOST'].$settings->_httpRoot."out/out.ViewDocument.php?documentid=".$document->getID();
		$params['sitename'] = $settings->_siteName;
		$params['http_root'] = $settings->_httpRoot;
		$notifier->toList($user, $notifyList["users"], $subject, $message, $params);
		foreach ($notifyList["groups"] as $grp) {
			$notifier->toGroup($user, $grp, $subject, $message, $params);
		}
	}
}

add_log_line("?documentid=".$documentid."&fileid=".$fileid);

header("Location:../out/out.ViewDocument.php?documentid=".$documentid."&currenttab=wordFiles");

?>
