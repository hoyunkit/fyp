<?php
    include("../inc/inc.Settings.php");
    include("../inc/inc.LogInit.php");
    include("../inc/inc.Utils.php");
    include("../inc/inc.Language.php");
    include("../inc/inc.Init.php");
    include("../inc/inc.Extension.php");
    include("../inc/inc.DBInit.php");
    include("../inc/inc.ClassUI.php");
    include("../inc/inc.Authentication.php");

    $tmp = explode('.', basename($_SERVER['SCRIPT_FILENAME']));
    $view = UI::factory($theme, $tmp[1], array('dms'=>$dms, 'user'=>$user));
    if (!$user->isAdmin()) {
            UI::exitError(getMLText("admin_tools"),getMLText("access_denied"));
    }
    if (!isset($_GET["folderid"]) || !is_numeric($_GET["folderid"]) || intval($_GET["folderid"])<1) {
            $folderid = $settings->_rootFolderID;
    }
    else {
            $folderid = intval($_GET["folderid"]);
    }
    if(isset($_GET["selUser"])){ //&&  $_GET["selUser"]!= -1
        $selUser = $_GET["selUser"];
    }
    else 
        $selUser = null; 
    if(isset($_GET["selGroup"])) //&&  $_GET["selGroup"]!= -1
            $selGroup=$_GET["selGroup"];
    else 
            $selGroup=null;
    $folder = $dms->getFolder($folderid);
    if (!is_object($folder)) {
            UI::exitError(getMLText("folder_title", array("foldername" => getMLText("invalid_folder_id"))), getMLText("invalid_folder_id"));
    }
    if (isset($_GET["orderby"]) && strlen($_GET["orderby"])==1 ) {
            $orderby=$_GET["orderby"];
    } else $orderby=$settings->_sortFoldersDefault;

    if (!empty($_GET["offset"])) {
            $offset=(int) $_GET["offset"];
    } else $offset = 0;
    if (!empty($_GET["limit"])) {
            $limit=(int) $_GET["limit"];
    } else $limit = 10;
//    if ($folder->getAccessMode($user) < M_READ) {
//            UI::exitError(getMLText("folder_title", array("foldername" => htmlspecialchars($folder->getName()))),getMLText("access_denied"));
//    }
    if($view) {
            $view->setParam('folder', $folder);
            $view->setParam('selGroup', $selGroup);
            $view->setParam('selUser', $selUser);
            $view->setParam('orderby', $orderby);
            $view->setParam('enableFolderTree', $settings->_enableFolderTree);
            $view->setParam('enableDropUpload', $settings->_enableDropUpload);
            $view->setParam('expandFolderTree', $settings->_expandFolderTree);
            $view->setParam('showtree', showtree());
            $view->setParam('settings', $settings);
            $view->setParam('cachedir', $settings->_cacheDir);
            $view->setParam('previewWidthList', $settings->_previewWidthList);
            $view->setParam('previewConverters', isset($settings->_converters['preview']) ? $settings->_converters['preview'] : array());
            $view->setParam('timeout', $settings->_cmdTimeout);
            $view->setParam('maxItemsPerPage', $settings->_maxItemsPerPage);
            $view->setParam('offset', $offset);
            $view->setParam('limit', $limit);
            $view($_GET);
            exit;
    }
?>