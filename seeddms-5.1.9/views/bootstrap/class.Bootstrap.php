<?php
//    MyDMS. Document Management System
//    Copyright (C) 2002-2005  Markus Westphal
//    Copyright (C) 2006-2008 Malcolm Cowe
//    Copyright (C) 2010 Matteo Lucarelli
//    Copyright (C) 2009-2012 Uwe Steinmann
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



class SeedDMS_Bootstrap_Style extends SeedDMS_View_Common {
	/**
	 * @var string $extraheader extra html code inserted in the html header
	 * of the page
	 *
	 * @access protected
	 */
	protected $extraheader;

	function __construct($params, $theme='bootstrap') {
		parent::__construct($params, $theme);
		$this->extraheader = array('js'=>'', 'css'=>'');
		$this->footerjs = array();
	}

	/**
	 * Add javascript to an internal array which is output at the
	 * end of the page within a document.ready() function.
	 *
	 * @param string $script javascript to be added
	 */
	function addFooterJS($script) { /* {{{ */
		$this->footerjs[] = $script;
	} /* }}} */

	function htmlStartPage($title="", $bodyClass="", $base="", $httpheader=array()) { /* {{{ */
		if(1 || method_exists($this, 'js')) {
			/* We still need unsafe-eval, because printDocumentChooserHtml and
			 * printFolderChooserHtml will include a javascript file with ajax
			 * which is evaluated by jquery
			 * X-WebKit-CSP is deprecated, Chrome understands Content-Security-Policy
			 * since version 25+
			 * X-Content-Security-Policy is deprecated, Firefox understands
			 * Content-Security-Policy since version 23+
			 */
			$csp_rules = "script-src 'self' 'unsafe-eval';"; // style-src 'self';";
			foreach (array("X-WebKit-CSP", "X-Content-Security-Policy", "Content-Security-Policy") as $csp) {
				header($csp . ": " . $csp_rules);
			}
		}
		if($httpheader) {
			foreach($httpheader as $name=>$value) {
				header($name . ": " . $value);
			}
		}
		$hookObjs = $this->getHookObjects('SeedDMS_View_Bootstrap');
		foreach($hookObjs as $hookObj) {
			if (method_exists($hookObj, 'startPage')) {
				$hookObj->startPage($this);
			}
		}
		echo "<!DOCTYPE html>\n";
		echo "<html lang=\"en\">\n<head>\n";
		echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\n";
		echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">'."\n";
		if($base)
			echo '<base href="'.$base.'">'."\n";
		elseif($this->baseurl)
			echo '<base href="'.$this->baseurl.'">'."\n";
		$sitename = trim(strip_tags($this->params['sitename']));
		if($this->params['session'])
			echo '<link rel="search" type="application/opensearchdescription+xml" href="../out/out.OpensearchDesc.php" title="'.(strlen($sitename)>0 ? $sitename : "SeedDMS").'"/>'."\n";
		echo '<link href="../styles/'.$this->theme.'/bootstrap/css/bootstrap.css" rel="stylesheet">'."\n";
		echo '<link href="../styles/'.$this->theme.'/bootstrap/css/bootstrap-responsive.css" rel="stylesheet">'."\n";
		echo '<link href="../styles/'.$this->theme.'/font-awesome/css/font-awesome.css" rel="stylesheet">'."\n";
//		echo '<link href="../styles/'.$this->theme.'/datepicker/css/datepicker.css" rel="stylesheet">'."\n";
		echo '<link href="../styles/'.$this->theme.'/datepicker/css/bootstrap-datepicker.css" rel="stylesheet">'."\n";
		echo '<link href="../styles/'.$this->theme.'/chosen/css/chosen.css" rel="stylesheet">'."\n";
		echo '<link href="../styles/'.$this->theme.'/select2/css/select2.min.css" rel="stylesheet">'."\n";
		echo '<link href="../styles/'.$this->theme.'/select2/css/select2-bootstrap.css" rel="stylesheet">'."\n";
		echo '<link href="../styles/'.$this->theme.'/jqtree/jqtree.css" rel="stylesheet">'."\n";
		echo '<link href="../styles/'.$this->theme.'/application.css" rel="stylesheet">'."\n";
		echo '<link href="../elib/elib_project.css" rel="stylesheet">'."\n";

		if($this->extraheader['css'])
			echo $this->extraheader['css'];
		if(method_exists($this, 'css'))
			echo '<link href="../out/out.'.$this->params['class'].'.php?action=css'.(!empty($_SERVER['QUERY_STRING']) ? '&'.$_SERVER['QUERY_STRING'] : '').'" rel="stylesheet">'."\n";

		echo '<script type="text/javascript" src="../styles/'.$this->theme.'/jquery/jquery.min.js"></script>'."\n";
		if($this->extraheader['js'])
			echo $this->extraheader['js'];
		echo '<script type="text/javascript" src="../styles/'.$this->theme.'/passwordstrength/jquery.passwordstrength.js"></script>'."\n";
		echo '<script type="text/javascript" src="../styles/'.$this->theme.'/noty/jquery.noty.js"></script>'."\n";
		echo '<script type="text/javascript" src="../styles/'.$this->theme.'/noty/layouts/topRight.js"></script>'."\n";
		echo '<script type="text/javascript" src="../styles/'.$this->theme.'/noty/layouts/topCenter.js"></script>'."\n";
		echo '<script type="text/javascript" src="../styles/'.$this->theme.'/noty/themes/default.js"></script>'."\n";
		echo '<script type="text/javascript" src="../styles/'.$this->theme.'/jqtree/tree.jquery.js"></script>'."\n";
		echo '<script type="text/javascript" src="../elib/elib_project.js"></script>'."\n";
//		echo '<script type="text/javascript" src="../styles/'.$this->theme.'/jquery-cookie/jquery.cookie.js"></script>'."\n";
		echo '<link rel="shortcut icon" href="../styles/'.$this->theme.'/favicon.ico" type="image/x-icon"/>'."\n";
		if($this->params['session'] && $this->params['session']->getSu()) {
?>
<style type="text/css">
.navbar-inverse .navbar-inner {
background-image: -webkit-gradient(linear, 0 0, 0 100%, from(#882222), to(#111111));
background-image: webkit-linear-gradient(top, #882222, #111111);
background-image: linear-gradient(to bottom, #882222, #111111);;
}
</style>
<?php
		}
		echo "<title>".(strlen($sitename)>0 ? $sitename : "SeedDMS").(strlen($title)>0 ? ": " : "").htmlspecialchars($title)."</title>\n";
		echo "</head>\n";
		echo "<body".(strlen($bodyClass)>0 ? " class=\"".$bodyClass."\"" : "").">\n";
		if($this->params['session'] && $flashmsg = $this->params['session']->getSplashMsg()) {
			$this->params['session']->clearSplashMsg();
			echo "<div class=\"splash\" data-type=\"".$flashmsg['type']."\"".(!empty($flashmsg['timeout']) ? ' data-timeout="'.$flashmsg['timeout'].'"': '').">".$flashmsg['msg']."</div>\n";
		}
		foreach($hookObjs as $hookObj) {
			if (method_exists($hookObj, 'startBody')) {
				$hookObj->startBody($this);
			}
		}
	} /* }}} */

	function htmlAddHeader($head, $type='js') { /* {{{ */
		$this->extraheader[$type] .= $head;
	} /* }}} */

	function htmlEndPage($nofooter=false) { /* {{{ */
		if(!$nofooter) {
			//$this->footNote();
			if($this->params['showmissingtranslations']) {
				$this->missingLanguageKeys();
			}
		}
		echo '<script src="../styles/'.$this->theme.'/bootstrap/js/bootstrap.min.js"></script>'."\n";
		echo '<script src="../styles/'.$this->theme.'/datepicker/js/bootstrap-datepicker.js"></script>'."\n";
		foreach(array('de', 'es', 'ar', 'el', 'bg', 'ru', 'hr', 'hu', 'ko', 'pl', 'ro', 'sk', 'tr', 'uk', 'ca', 'nl', 'fi', 'cs', 'it', 'fr', 'sv', 'sl', 'pt-BR', 'zh-CN', 'zh-TW') as $lang)
			echo '<script src="../styles/'.$this->theme.'/datepicker/locales/bootstrap-datepicker.'.$lang.'.min.js"></script>'."\n";
		echo '<script src="../styles/'.$this->theme.'/chosen/js/chosen.jquery.min.js"></script>'."\n";
		echo '<script src="../styles/'.$this->theme.'/select2/js/select2.min.js"></script>'."\n";
		parse_str($_SERVER['QUERY_STRING'], $tmp);
		$tmp['action'] = 'webrootjs';
		echo '<script src="'.$this->params['absbaseprefix'].'out/out.'.$this->params['class'].'.php?'.http_build_query($tmp).'"></script>'."\n";
		echo '<script src="../styles/'.$this->theme.'/application.js"></script>'."\n";
		if($this->params['enablemenutasks'] && isset($this->params['user']) && $this->params['user'] && !$this->params['user']->isGuest()) {
			$this->addFooterJS('checkTasks();');
		}
		if($this->footerjs) {
			$jscode = "$(document).ready(function () {\n";
			foreach($this->footerjs as $script) {
				$jscode .= $script."\n";
			}
			$jscode .= "});\n";
			$hashjs = md5($jscode);
			if(!is_dir($this->params['cachedir'].'/js')) {
				SeedDMS_Core_File::makeDir($this->params['cachedir'].'/js');
			}
			if(is_dir($this->params['cachedir'].'/js')) {
				file_put_contents($this->params['cachedir'].'/js/'.$hashjs.'.js', $jscode);
			}
			$tmp['action'] = 'footerjs';
			$tmp['hash'] = $hashjs;
			echo '<script src="'.$this->params['absbaseprefix'].'out/out.'.$this->params['class'].'.php?'.http_build_query($tmp).'"></script>'."\n";
		}
		if(method_exists($this, 'js')) {
			parse_str($_SERVER['QUERY_STRING'], $tmp);
			$tmp['action'] = 'js';
			echo '<script src="'.$this->params['absbaseprefix'].'out/out.'.$this->params['class'].'.php?'.http_build_query($tmp).'"></script>'."\n";
		}
		echo "</body>\n</html>\n";
	} /* }}} */

	function webrootjs() { /* {{{ */
		header('Content-Type: application/javascript');
		echo "var seeddms_absbaseprefix=\"".$this->params['absbaseprefix']."\";\n";
		echo "var seeddms_webroot=\"".$this->params['settings']->_httpRoot."\";\n";
	} /* }}} */

	function footerjs() { /* {{{ */
		header('Content-Type: application/javascript');
		if(file_exists($this->params['cachedir'].'/js/'.$_GET['hash'].'.js')) {
			readfile($this->params['cachedir'].'/js/'.$_GET['hash'].'.js');
		}
	} /* }}} */

	function missingLanguageKeys() { /* {{{ */
		global $MISSING_LANG, $LANG;
		if($MISSING_LANG) {
			echo '<div class="container-fluid">'."\n";
			echo '<div class="row-fluid">'."\n";
			echo '<div class="alert alert-error">'."\n";
			echo "<p><strong>This page contains missing translations in the selected language. Please help to improve SeedDMS and provide the translation.</strong></p>";
			echo "</div>";
			echo "<table class=\"table table-condensed\">";
			echo "<tr><th>Key</th><th>engl. Text</th><th>Your translation</th></tr>\n";
			foreach($MISSING_LANG as $key=>$lang) {
				echo "<tr><td>".$key."</td><td>".(isset($LANG['en_GB'][$key]) ? $LANG['en_GB'][$key] : '')."</td><td><div class=\"input-append send-missing-translation\"><input name=\"missing-lang-key\" type=\"hidden\" value=\"".$key."\" /><input name=\"missing-lang-lang\" type=\"hidden\" value=\"".$lang."\" /><input type=\"text\" class=\"input-xxlarge\" name=\"missing-lang-translation\" placeholder=\"Your translation in '".$lang."'\"/><a class=\"btn\">Submit</a></div></td></tr>";
			}
			echo "</table>";
			echo "<div class=\"splash\" data-type=\"error\" data-timeout=\"5500\"><b>There are missing translations on this page!</b><br />Please check the bottom of the page.</div>\n";
			echo "</div>\n";
			echo "</div>\n";
		}
	} /* }}} */

	function footNote() { /* {{{ */
		echo "<div class=\"container-fluid\">\n";
		echo '<div class="row-fluid">'."\n";
		echo '<div class="span12">'."\n";
		echo '<div class="alert alert-info">'."\n";
		if ($this->params['printdisclaimer']){
			echo "<div class=\"disclaimer\">".getMLText("disclaimer")."</div>";
		}

		if (isset($this->params['footnote']) && strlen((string)$this->params['footnote'])>0) {
			echo "<div class=\"footNote\">".(string)$this->params['footnote']."</div>";
		}
		echo "</div>\n";
		echo "</div>\n";
		echo "</div>\n";
		echo "</div>\n";
	
		return;
	} /* }}} */

	function contentStart() { /* {{{ */
		echo "<div class=\"container-fluid\">\n";
		echo " <div class=\"row-fluid\">\n";
	} /* }}} */
        

	function contentEnd() { /* {{{ */
		echo " </div>\n";
		echo "</div>\n";
	} /* }}} */
        
	function globalBanner() { /* {{{ */
		echo "<div class=\"navbar navbar-inverse navbar-fixed-top\">\n";
		echo " <div class=\"navbar-inner\">\n";
		echo "  <div class=\"container-fluid\">\n";
		echo "   <a class=\"brand\" href=\"../op/op.Login.php?login=guest\">".(strlen($this->params['sitename'])>0 ? $this->params['sitename'] : "SeedDMS")."</a>\n";
		echo "  </div>\n";
		echo " </div>\n";
		echo "</div>\n";
	} /* }}} */

	function globalNavigation($folder=null) { /* {{{ */
		$dms = $this->params['dms'];
		echo "<div class=\"navbar navbar-inverse navbar-fixed-top\" id=\"topnavbar\">\n";
		echo " <div class=\"navbar-inner\">\n";
		echo "  <div class=\"container-fluid\">\n";
		echo "   <a class=\"btn btn-navbar\" data-toggle=\"collapse\" data-target=\".nav-col1\">\n";
		echo "     <span class=\"icon-bar\"></span>\n";
		echo "     <span class=\"icon-bar\"></span>\n";
		echo "     <span class=\"icon-bar\"></span>\n";
		echo "   </a>\n";
		echo "   <a class=\"brand\" href=\"../out/out.HomePage.php\"><b>".(strlen($this->params['sitename'])>0 ?  $this->params['sitename'] : "SeedDMS")."</b></a>\n";
		if(isset($this->params['user']) && $this->params['user']) {
			echo "   <div class=\"nav-collapse nav-col1\" id='nav-collapse-nav-col1'>\n";
			echo "   <ul id=\"main-menu-admin\" class=\"nav pull-right\">\n";
			echo "    <li class=\"dropdown\">\n";
                        if(!$this->params['user']->isGuest() || $this->params['session']->getSu()){
                            echo "     <a href=\"#\" class=\"dropdown-toggle\" data-toggle=\"dropdown\" id='dropdown-btn'>".($this->params['session']->getSu() ? getMLText("switched_to") : getMLText("signed_in_as"))." '".htmlspecialchars($this->params['user']->getFullName())."' <i class=\"icon-caret-down\"></i></a>\n";
                        }else{
                            echo "     <a href=\"#\" class=\"dropdown-toggle\" data-toggle=\"dropdown\" id='dropdown-btn'>Welcome ".htmlspecialchars($this->params['user']->getFullName())." <i class=\"icon-caret-down\"></i></a>\n";
                        }
			
			echo "     <ul class=\"dropdown-menu\" role=\"menu\">\n";
			if (!$this->params['user']->isGuest()) {
				$menuitems = array();
				$menuitems['my_documents'] = array('link'=>"../out/out.MyDocuments.php?inProcess=1", 'label'=>'my_documents');
				$menuitems['my_account'] = array('link'=>"../out/out.MyAccount.php", 'label'=>'my_account');
                                $menuitems['manage'] = array('link'=>"../out/out.ManageBookmark.php", 'label'=>'manage');
                                if ($this->params['user']->isAdmin()){
                                    $menuitems['admin_tools'] = array('link'=>"../out/out.AdminTools.php", 'label'=>'admin_tools');
                                }
				$hookObjs = $this->getHookObjects('SeedDMS_View_Bootstrap');
				foreach($hookObjs as $hookObj) {
					if (method_exists($hookObj, 'userMenuItems')) {
						$menuitems = $hookObj->userMenuItems($this, $menuitems);
					}
				}
				if($menuitems) {
					foreach($menuitems as $menuitem) {
						echo "<li><a href=\"".$menuitem['link']."\">".getMLText($menuitem['label'])."</a></li>";
					}
					echo "    <li class=\"divider\"></li>\n";
				}
			}
			$showdivider = false;
			if($this->params['enablelanguageselector']) {
				$showdivider = true;
				echo "    <li class=\"dropdown-submenu\">\n";
				echo "     <a href=\"#\" class=\"dropdown-toggle\" data-toggle=\"dropdown\">".getMLText("language")."</a>\n";
				echo "     <ul class=\"dropdown-menu\" role=\"menu\">\n";
				$languages = getLanguages();
				foreach ($languages as $currLang) {
					if($this->params['session']->getLanguage() == $currLang)
						echo "<li class=\"active\" style=\"margin:0px 10px\">";
					else
						echo "<li style=\"margin:0px 10px\">";
					echo "<a href=\"../op/op.SetLanguage.php?lang=".$currLang."&referer=".urlencode($_SERVER["REQUEST_URI"])."\">";
					echo getMLText($currLang)."</a></li>\n";
				}
				echo "     </ul>\n";
				echo "    </li>\n";
			}
			if($this->params['user']->isAdmin()) {
				$showdivider = true;
				echo "    <li><a href=\"../out/out.SubstituteUser.php\">".getMLText("substitute_user")."</a></li>\n";
			}
			if($showdivider)
				echo "    <li class=\"divider\"></li>\n";
			if($this->params['session']->getSu()) {
				echo "    <li><a href=\"../op/op.ResetSu.php\">".getMLText("sign_out_user")."</a></li>\n";
			} else {
                            if($this->params['user']->isGuest()){
                                echo "    <li><a href=\"../op/op.Logout.php\">".getMLText("sign_in")."</a></li>\n";
                            }else{
                                echo "    <li><a href=\"../op/op.Logout.php\">".getMLText("sign_out")."</a></li>\n";
                            }
			}
			echo "     </ul>\n";
			echo "    </li>\n";
			echo "   </ul>\n";

			if($this->params['enablemenutasks']) {
				echo "   <div id=\"menu-tasks\">";
				echo "   <ul id=\"main-menu-tasks\" class=\"nav pull-right\">\n";
				echo "    <li class=\"dropdown\">\n";
//				echo $this->menuTasks(array('review'=>array(), 'approval'=>array(), 'receipt'=>array(), 'revision'=>array()));
				echo "    </li>\n";
				echo "   </ul>\n";
				echo "   </div>";
				//$this->addFooterJS('checkTasks();');
			}

			if($this->params['dropfolderdir'] && $this->params['enabledropfolderlist']) {
				echo "   <div id=\"menu-dropfolder\">";
				echo "     <div class=\"ajax\" data-no-spinner=\"true\" data-view=\"DropFolderChooser\" data-action=\"menuList\"";
				if ($folder!=null && is_object($folder) && !strcasecmp(get_class($folder), $dms->getClassname('folder')))
					echo " data-query=\"folderid=".$folder->getID()."\"";
				echo "></div>";
				echo "   </div>";
			}
			if($this->params['enablesessionlist']) {
				echo "   <div id=\"menu-session\">";
				echo "     <div class=\"ajax\" data-no-spinner=\"true\" data-view=\"Session\" data-action=\"menuSessions\"></div>";
				echo "   </div>";
			}
			if($this->params['enableclipboard']) {
				echo "   <div id=\"menu-clipboard\">";
				echo "     <div class=\"ajax\" data-no-spinner=\"true\" data-view=\"Clipboard\" data-action=\"menuClipboard\"></div>";
				echo "   </div>";
			}

			echo "   <ul class=\"nav\">\n";
	//		echo "    <li id=\"first\"><a href=\"../out/out.ViewFolder.php?folderid=".$this->params['rootfolderid']."\">".getMLText("content")."</a></li>\n";
	//		echo "    <li><a href=\"../out/out.SearchForm.php?folderid=".$this->params['rootfolderid']."\">".getMLText("search")."</a></li>\n";
			if ($this->params['enablecalendar']) echo "    <li><a href=\"../out/out.Calendar.php?mode=".$this->params['calendardefaultview']."\">".getMLText("calendar")."</a></li>\n";
                        //if (!$this->params['user']->isGuest()) echo "    <li><a href='../out/out.ManageBookmark.php'>".'Manage'."</a></li>\n";
			//if ($this->params['user']->isAdmin()) echo "    <li><a href=\"../out/out.AdminTools.php\">".getMLText("admin_tools")."</a></li>\n";
			if($this->params['enablehelp']) {
			$tmp = explode('.', basename($_SERVER['SCRIPT_FILENAME']));
			echo "    <li><a href=\"../out/out.Help.php?context=".$tmp[1]."\">".getMLText("help")."</a></li>\n";
			}
			echo "   </ul>\n";
                        if($this->params['class']!="Search"){
                            echo "     <form action=\"../out/out.Search.php\" class=\"form-inline navbar-search pull-left\" autocomplete=\"off\">";
                            if ($folder!=null && is_object($folder) && !strcasecmp(get_class($folder), $dms->getClassname('folder'))) {
                                    echo "      <input type=\"hidden\" name=\"folderid\" value=\"".$folder->getID()."\" />";
                            }
                            echo "      <input type=\"hidden\" name=\"navBar\" value=\"1\" />";
                            echo "      <input name=\"query\" class=\"search-query\" ".($this->params['defaultsearchmethod'] == 'fulltext' ? "" : "id=\"searchfield\"")." data-provide=\"typeahead\" type=\"search\" style=\"width: 150px;\" placeholder=\"".getMLText("search")."\"/>";
                            if($this->params['defaultsearchmethod'] == 'fulltext')
                                    echo "      <input type=\"hidden\" name=\"fullsearch\" value=\"1\" />";
    //			if($this->params['enablefullsearch']) {
    //				echo "      <label class=\"checkbox\" style=\"color: #999999;\"><input type=\"checkbox\" name=\"fullsearch\" value=\"1\" title=\"".getMLText('fullsearch_hint')."\"/> ".getMLText('fullsearch')."</label>";
    //			}
            //		echo "      <input type=\"submit\" value=\"".getMLText("search")."\" id=\"searchButton\" class=\"btn\"/>";
                            echo "</form>\n";
                        }
			echo "    </div>\n";
		}
		echo "  </div>\n";
		echo " </div>\n";
		echo "</div>\n";
		return;
	} /* }}} */

function getFolderPathHTML($folder, $tagAll=false, $document=null, $searchform=false, $selUser=null, $selGroup=null) { /* {{{ */
    if($selUser || $selGroup){ 
        $page="out.CheckPrivilege.php";
    }
    else{
        $page="out.ViewFolder.php";
    }
    $userDomain=$this->getUserDomain($selUser, $selGroup);
	$path = $folder->getPath();
	$txtpath = "";
	$search = "";
	for ($i = 0; $i < count($path); $i++) {
		$txtpath .= "<li>";
		if ($i +1 < count($path)) {
			$txtpath .= "<a href=\"../out/".$page."?folderid=".$path[$i]->getID()."&showtree=".showtree().$userDomain."\" rel=\"folder_".$path[$i]->getID()."\" class=\"table-row-folder\" formtoken=\"".createFormKey('movefolder')."\">".
				htmlspecialchars($path[$i]->getName())."</a>";
		}
		else {
			$txtpath .= ($tagAll ? "<a href=\"../out/".$page."?folderid=".$path[$i]->getID()."&showtree=".showtree().$userDomain."\">".
									 htmlspecialchars($path[$i]->getName())."</a>" : htmlspecialchars($path[$i]->getName()));
		}
		$txtpath .= " <span class=\"divider\">/</span></li>";
	}
	if($document){
		$txtpath .= "<li><a href=\"../out/out.ViewDocument.php?documentid=".$document->getId()."\">".htmlspecialchars($document->getName())."</a></li>";
	}
            if($searchform){
	$search="<form action=\"../out/out.Search.php\" style='display: contents;'>
		<input type=\"hidden\" name=\"folderid\" value=".$folder->getID().">
			<input name=\"query\" class=\"search-query\" data-provide=\"typeahead\" type=\"search\" placeholder=\"Search in folder\"/>
        <input type=\"hidden\" name=\"fullsearch\" value=\"1\" />
         <button type=\"submit\" class=\"btn\" ><i class=\"icon-search\" ></i></button>
        </form><br>";}
	return '<ul class="breadcrumb">'.$txtpath.$search.'</ul>';
	}
        function getFolderPermPathHTML($dms, $folder, $selUser, $selGroup, $document=null) {
            $path = $folder->getPath();
            $txtpath = "";
            $userDomain=$this->getUserDomain($selUser, $selGroup);
            for ($i = 0; $i < count($path); $i++) {
                $txtpath .= "<li>";
                if ($i +1 < count($path)) {
                    $txtpath .= "<a href=\"../out/out.CheckPrivilege.php?folderid=".$path[$i]->getID()."&showtree=".showtree().$userDomain."\" rel=\"folder_".$path[$i]->getID()."\" class=\"table-row-folder\" formtoken=\"".createFormKey('movefolder')."\">".htmlspecialchars($path[$i]->getName())."</a>";
                }
                else {
                    $txtpath .= htmlspecialchars($path[$i]->getName());
                }
                $txtpath .= " <span class=\"divider\">/</span></li>";
            }
            $allUsers = $dms->getAllUsers();
            $allGroups=$dms->getAllGroups();
            $userhtml ='<form class="form-horizontal" action="../out/out.CheckPrivilege.php" id="permissionForm" name="permissionForm" style="display:contents">';
            $userhtml .= '<input type="hidden" name="folderid" value="1">'
                    . '<span class="pull-right justify-content-center" >'
                    . 'View User Only : '
                    . '<select name="selUser" class="userPerm1" style="text-align-last:center">'
                    .'<option value="-1" style="text-align:center">/</option>';
            foreach ($allUsers as $currUser) {
                if($selUser && $currUser->getID()==$selUser){
                    $userhtml.='<option value="'.$currUser->getID().'" selected>'.htmlspecialchars($currUser->getLogin()).'</option>';
                }else{
                    $userhtml.='<option value="'.$currUser->getID().'">'.htmlspecialchars($currUser->getLogin()).'</option>';
                }
            }
            $userhtml.='</select >'
            		.' View Group Only : '
            		.'<select name="selGroup" class="userPerm2" style="text-align-last:center">'
        		.'<option value="-1" style="text-align:center">/</option>';
            foreach ($allGroups as $currGroup) {
                if($selGroup && $currGroup->getID()==$selGroup){
                    $userhtml.='<option value="'.$currGroup->getID().'" selected>'.htmlspecialchars($currGroup->getName()).'</option>';
                }else{
                    $userhtml.='<option value="'.$currGroup->getID().'">'.htmlspecialchars($currGroup->getName()).'</option>';
                }
            }
            $userhtml.='</select></span></form>';
		return '<ul class="breadcrumb clearfix" >'.$txtpath.$userhtml.'</ul>';
	}
        function getUserDomain($selUser, $selGroup){
            if($selUser && $selGroup){
                return "&selUser=".$selUser."&selGroup=".$selGroup;
            }
            return "";
        }

        function pageNavigation($pageTitle, $pageType=null, $extra=null, $selUser=null, $selGroup=null) { /* {{{ */
	$userDomain=$this->getUserDomain($selUser, $selGroup);
		if ($pageType!=null && strcasecmp($pageType, "noNav")) {
			echo "<div class=\"navbar\">\n";
			echo " <div class=\"navbar-inner\">\n";
			echo "  <div class=\"container\">\n";
			echo "   <a class=\"btn btn-navbar\" data-toggle=\"collapse\" data-target=\".col2\">\n";
			echo "     <span class=\"icon-bar\" style=\"background-color: grey;\"></span>\n";
			echo " 		<span class=\"icon-bar\" style=\"background-color: grey;\"></span>\n";
			echo " 		<span class=\"icon-bar\" style=\"background-color: grey;\"></span>\n";
			echo "   </a>\n";
			switch ($pageType) {
				case "view_folder":
					$this->folderNavigationBar($extra, $userDomain); //$extra=$folders
					break;
				case "view_document":
					$this->documentNavigationBar($extra);
					break;
				case "my_documents":
					$this->myDocumentsNavigationBar();
					break;
				case "my_account":
					$this->accountNavigationBar();
					break;
				case "admin_tools":
					$this->adminToolsNavigationBar();
					break;
				case "calendarold";
					$this->calendarOldNavigationBar($extra);
					break;
				case "calendar";
					$this->calendarNavigationBar($extra);
					break;
			}
			echo " 	</div>\n";
			echo " </div>\n";
			echo "</div>\n";
			if($pageType == "view_folder" || $pageType == "view_document")
				echo $pageTitle."\n";
		} else {
			echo "<legend>".$pageTitle."</legend>\n";
		}

		return;
	} /* }}} */

	private function showNavigationBar($menuitems) { /* {{{ */
		foreach($menuitems as $menuitem) {
			if(!empty($menuitem['children'])) {
				echo "    <li class=\"dropdown\">\n";
				echo "     <a href=\"".$menuitem['link']."\" class=\"dropdown-toggle\" data-toggle=\"dropdown\">".getMLText($menuitem['label'])." <i class=\"icon-caret-down\"></i></a>\n";
				echo "     <ul class=\"dropdown-menu\" role=\"menu\">\n";
				foreach($menuitem['children'] as $submenuitem) {
					echo "      <li><a href=\"".$submenuitem['link']."\">".getMLText($submenuitem['label'])."</a></li>\n";
				}
				echo "     </ul>\n";
			} else {
				echo "<li><a href=\"".$menuitem['link']."\">".getMLText($menuitem['label'])."</a></li>";
			}
		}
	} /* }}} */
        function getGroundPerm($priv){
            $readPriv=array("FolderNotify");
            $readWritePriv=array("AddSubFolder", "AddDocument", "EditFolder", "MoveFolder", "AddFile", "EditComment", "EditDocument", "EditDocumentFile", "MoveDocument", "RemoveDocument", "RemoveDocumentFile", "UpdateDocument", "RemoveFolder", );
            $allPriv=array("FolderAccess", "CropImage");
            if(in_array($priv, $readPriv)){
               return M_READ;
            }else if(in_array($priv, $readWritePriv)){
                return M_READWRITE;
            }else if(in_array($priv, $allPriv)){
                return M_ALL;
            }else {return false;}
        }
        //glenn
       private function folderNavigationBar($folder, $userDomain)
	{ /* {{{ */
		$dms = $this->params['dms'];
		if (!is_object($folder) || strcasecmp(get_class($folder), $dms->getClassname('folder'))) {
			echo "<ul class=\"nav\">\n";
			echo "</ul>\n";
			return;
		}
		$accessMode = $folder->getAccessMode($this->params['user']);
		$folderID = $folder->getID();
		echo "<id=\"first\"><a href=\"../out/out.ViewFolder.php?folderid=" . $folderID . "&showtree=" . showtree() . "\" class=\"brand\">" . getMLText("folder") . "</a>\n";
		echo "<div class=\"nav-collapse col2\">\n";
		echo "<ul class=\"nav\">\n";
		$menuitems = [];

		// Glenn edited on 16 April, 2022
		$privTotal = array("add_document" => "AddDocument", "add_subfolder" => "AddSubFolder", "edit_folder_props" => "EditFolder", "move_folder" => "MoveFolder", "rm_folder" => "RemoveFolder", "edit_folder_access" => "FolderAccess", "edit_folder_notify" => "FolderNotify");

		$privileges = $dms->getPrivilegesByMode($accessMode);
		$privilegesObj = json_decode($privileges, true);
		$priv1 = []; // Show static content on interface
		$priv2 = []; // Print value in URL

		// Remove unselected privileges
		foreach ($privilegesObj as $priKey => $priVal) {
			if ($priVal == 0) {
				unset($privilegesObj[$priKey]);
			}
		}

		// Remove unavailable content
		foreach ($privTotal as $privKey => $privVal) {
			if (!$privilegesObj[$privKey]) {
				unset($privTotal[$privKey]);
			}
		}

		// echo $privTotal;
		foreach ($privTotal as $key => $val) {
			array_push($priv1, $key);
			array_push($priv2, $val);
		}

		// Show interface
		for ($i = 0; $i < count($priv1); $i++) {
			$menuitems[$priv1[$i]] = array('link' => "../out/out." . $priv2[$i] . ".php?folderid=" . $folderID . "&showtree=" . showtree() . $userDomain, 'label' => $priv1[$i]);
		}

		// Glenn edited on 16 April, 2022
		// Select the second highest mode
		/*
		$secondHighestVal = $dms->getSecondHighestMode();
		if ($accessMode >= (int)$secondHighestVal) {
			if (0 && $this->params['enablelargefileupload'])
				$menuitems['add_multiple_documents'] = array('link' => "../out/out.AddMultiDocument.php?folderid=" . $folderID . "&showtree=" . showtree() . $userDomain, 'label' => 'add_multiple_documents');
		}
		*/

		if ($this->params['user']->isAdmin() && $this->params['enablefullsearch']) {
			$menuitems['index_folder'] = array('link' => "../out/out.Indexer.php?folderid=" . $folderID . "&showtree=" . showtree(), 'label' => 'index_folder');
		}

		/* Check if hook exists because otherwise callHook() will override $menuitems */
		if ($this->hasHook('folderNavigationBar'))
			$menuitems = $this->callHook('folderNavigationBar', $folder, $menuitems);

		self::showNavigationBar($menuitems);

		echo "</ul>\n";
		echo "</div>\n";
		return;
	} /* }}} */
        
        // Glenn edited on 16 April, 2022
	// Right hand side action lists
	protected function docActions($document)
	{
		$dms = $this->params['dms'];
		$accessMode = $document->getAccessMode($this->params['user']);
		$docid = ".php?documentid=" . $document->getID();
		$menuitems = [];

		// Glenn edited on 16 April, 2022
		$privTotal = array("update_document" => "UpdateDocument", "lock_document" => "LockDocument", "unlock_document" => "UnlockDocument", "edit_document_props" => "EditDocument", "move_document" => "MoveDocument", "expires" => "SetExpires", "rm_document" => "RemoveDocument", "edit_document_access" => "DocumentAccess", "edit_existing_notify" => "DocumentNotify", "transfer_document" => "TransferDocument");
		$privileges = $dms->getPrivilegesByMode($accessMode);
		$privilegesObj = json_decode($privileges, true);
		$priv1 = []; // Show static content on interface
		$priv2 = []; // Print value in URL
		$highestMode = $dms->getHighestMode();

		// Remove unselected privileges
		foreach ($privilegesObj as $priKey => $priVal) {
			if ($priVal == 0) {
				unset($privilegesObj[$priKey]);
			}
		}

		// Remove unavailable content
		foreach ($privTotal as $privKey => $privVal) {
			if (!$privilegesObj[$privKey]) {
				unset($privTotal[$privKey]);
			}
		}

		// Show lock or unlock
		if (!$document->isLocked()) {
			unset($privTotal['unlock_document']);
		} else {
			$lockingUser = $document->getLockingUser();
			if (($lockingUser->getID() == $this->params['user']->getID()) || ($document->getAccessMode($this->params['user']) == $highestMode)) {
				unset($privTotal['lock_document']);
			}
		}

		// Add interface and actions in array
		foreach ($privTotal as $key => $val) {
			array_push($priv1, $key);
			array_push($priv2, $val);
		}

		// Show interface
		for ($i = 0; $i < count($priv1); $i++) {
			if ($priv2[$i] == 'LockDocument' || $priv2[$i] == 'UnlockDocument') {
				$menuitems[$priv1[$i]] = array('link' => "../op/op." . $priv2[$i] . $docid, 'label' => $priv1[$i]);
			} else {
				$menuitems[$priv1[$i]] = array('link' => "../out/out." . $priv2[$i] . $docid, 'label' => $priv1[$i]);
			}
		}

		return $menuitems;
	}
        
	// Glenn edited on 16 April, 2022
	// Navifation interface
	private function documentNavigationBar($document)
	{ /* {{{ */
		$dms = $this->params['dms'];
		// $accessMode = $document->getAccessMode($this->params['user']);
		$accessMode = $document->getAccessMode($this->params['user']);
		$docid = ".php?documentid=" . $document->getID();
		echo "<id=\"first\"><a href=\"../out/out.ViewDocument" . $docid . "\" class=\"brand\">" . getMLText("document") . "</a>\n";
		echo "<div class=\"nav-collapse col2\">\n";
		echo "<ul class=\"nav\">\n";
		$menuitems = [];

		// Glenn edited on 16 April, 2022
		// Store all privileges here
		$privTotal = array("update_document" => "UpdateDocument", "lock_document" => "LockDocument", "unlock_document" => "UnlockDocument", "edit_document_props" => "EditDocument", "move_document" => "MoveDocument", "expires" => "SetExpires", "rm_document" => "RemoveDocument", "edit_document_access" => "DocumentAccess", "edit_existing_notify" => "DocumentNotify", "transfer_document" => "TransferDocument");
		$privileges = $dms->getPrivilegesByMode($accessMode); // Change the mode manually
		$privilegesObj = json_decode($privileges, true);
		$priv1 = []; // Show static content on interface
		$priv2 = []; // Print value in URL
		$highestMode = $dms->getHighestMode();

		// Remove unselected privileges
		foreach ($privilegesObj as $priKey => $priVal) {
			if ($priVal == 0) {
				unset($privilegesObj[$priKey]);
			}
		}

		// Remove unavailable content
		foreach ($privTotal as $privKey => $privVal) {
			if (!$privilegesObj[$privKey]) {
				unset($privTotal[$privKey]);
			}
		}

		// Show lock or unlock
		if (!$document->isLocked()) {
			unset($privTotal['unlock_document']);
		} else {
			$lockingUser = $document->getLockingUser();
			if (($lockingUser->getID() == $this->params['user']->getID()) || ($document->getAccessMode($this->params['user']) == $highestMode)) {
				unset($privTotal['lock_document']);
			}
		}

		// Add interface and actions in array
		foreach ($privTotal as $key => $val) {
			array_push($priv1, $key);
			array_push($priv2, $val);
		}

		// Show interface
		for ($i = 0; $i < count($priv1); $i++) {
			if ($priv2[$i] == 'LockDocument' || $priv2[$i] == 'UnlockDocument') {
				$menuitems[$priv1[$i]] = array('link' => "../op/op." . $priv2[$i] . $docid, 'label' => $priv1[$i]);
			} else {
				$menuitems[$priv1[$i]] = array('link' => "../out/out." . $priv2[$i] . $docid, 'label' => $priv1[$i]);
			}
		}

		/* Check if hook exists because otherwise callHook() will override $menuitems */
		if ($this->hasHook('documentNavigationBar'))
			$menuitems = $this->callHook('documentNavigationBar', $document, $menuitems);

		//chenrui
		self::showNavigationBar($menuitems);

		echo "</ul>\n";
		echo "</div>\n";
		return;
	} /* }}} */

	private function accountNavigationBar() { /* {{{ */
		echo "<id=\"first\"><a href=\"../out/out.MyAccount.php\" class=\"brand\">".getMLText("my_account")."</a>\n";
		echo "<div class=\"nav-collapse col2\">\n";
		echo "<ul class=\"nav\">\n";

		$menuitems = array();
		if ($this->params['user']->isAdmin() || !$this->params['disableselfedit'])
			$menuitems['edit_user_details'] = array('link'=>"../out/out.EditUserData.php", 'label'=>'edit_user_details');
		
		if (!$this->params['user']->isAdmin()) 
			$menuitems['edit_default_keywords'] = array('link'=>"../out/out.UserDefaultKeywords.php", 'label'=>'edit_default_keywords');

		$menuitems['edit_notify'] = array('link'=>"../out/out.ManageNotify.php", 'label'=>'edit_existing_notify');

		if ($this->params['enableusersview']){
			$menuitems['users'] = array('link'=>"../out/out.UsrView.php", 'label'=>'users');
			$menuitems['users'] = array('link'=>"../out/out.GroupView.php", 'label'=>'groups');
		}		

		/* Check if hook exists because otherwise callHook() will override $menuitems */
		if($this->hasHook('accountNavigationBar'))
			$menuitems = $this->callHook('accountNavigationBar', $menuitems);

		self::showNavigationBar($menuitems);

		echo "</ul>\n";
		echo "</div>\n";
		return;
	} /* }}} */

	private function myDocumentsNavigationBar() { /* {{{ */

		echo "<id=\"first\"><a href=\"../out/out.MyDocuments.php?inProcess=1\" class=\"brand\">".getMLText("my_documents")."</a>\n";
		echo "<div class=\"nav-collapse col2\">\n";
		echo "<ul class=\"nav\">\n";

		$menuitems = array();
		$menuitems['inprocess'] = array('link'=>"../out/out.MyDocuments.php?inProcess=1", 'label'=>'documents_in_process');
		$menuitems['all_documents'] = array('link'=>"../out/out.MyDocuments.php", 'label'=>'all_documents');
		if($this->params['workflowmode'] == 'traditional' || $this->params['workflowmode'] == 'traditional_only_approval') {
			$menuitems['review_summary'] = array('link'=>"../out/out.ReviewSummary.php", 'label'=>'review_summary');
			$menuitems['approval_summary'] = array('link'=>"../out/out.ApprovalSummary.php", 'label'=>'approval_summary');
		} else {
			$menuitems['workflow_summary'] = array('link'=>"../out/out.WorkflowSummary.php", 'label'=>'workflow_summary');
		}

		/* Check if hook exists because otherwise callHook() will override $menuitems */
		if($this->hasHook('mydocumentsNavigationBar'))
			$menuitems = $this->callHook('mydocumentsNavigationBar', $menuitems);

		self::showNavigationBar($menuitems);

		echo "</ul>\n";
		echo "</div>\n";
		return;
	} /* }}} */

	private function adminToolsNavigationBar() { /* {{{ */
		echo "    <id=\"first\"><a href=\"../out/out.AdminTools.php\" class=\"brand\">".getMLText("admin_tools")."</a>\n";
		echo "<div class=\"nav-collapse col2\">\n";
		echo "   <ul class=\"nav\">\n";

		$menuitems = array();
		$menuitems['user_group_management'] = array('link'=>"#", 'label'=>'user_group_management');
		$menuitems['user_group_management']['children']['user_management'] = array('link'=>"../out/out.UsrMgr.php", 'label'=>'user_management');
		$menuitems['user_group_management']['children']['group_management'] = array('link'=>"../out/out.GroupMgr.php", 'label'=>'group_management');
		$menuitems['user_group_management']['children']['user_list'] = array('link'=>"../out/out.UserList.php", 'label'=>'user_list');
		
		$menuitems['definitions'] = array('link'=>"#", 'label'=>'definitions');
		$menuitems['definitions']['children']['default_keywords'] = array('link'=>"../out/out.DefaultKeywords.php", 'label'=>'global_default_keywords');
		$menuitems['definitions']['children']['document_categories'] = array('link'=>"../out/out.Categories.php", 'label'=>'global_document_categories');
		$menuitems['definitions']['children']['attribute_definitions'] = array('link'=>"../out/out.AttributeMgr.php", 'label'=>'global_attributedefinitions');
		if($this->params['workflowmode'] == 'advanced') {
			$menuitems['definitions']['children']['workflows'] = array('link'=>"../out/out.WorkflowMgr.php", 'label'=>'global_workflows');
			$menuitems['definitions']['children']['workflow_states'] = array('link'=>"../out/out.WorkflowStatesMgr.php", 'label'=>'global_workflow_states');
			$menuitems['definitions']['children']['workflow_actions'] = array('link'=>"../out/out.WorkflowActionsMgr.php", 'label'=>'global_workflow_actions');
		}

		if($this->params['enablefullsearch']) {
			$menuitems['fulltext'] = array('link'=>"#", 'label'=>'fullsearch');
			$menuitems['fulltext']['children']['update_fulltext_index'] = array('link'=>"../out/out.Indexer.php", 'label'=>'update_fulltext_index');
			$menuitems['fulltext']['children']['create_fulltext_index'] = array('link'=>"../out/out.CreateIndex.php", 'label'=>'create_fulltext_index');
			$menuitems['fulltext']['children']['fulltext_info'] = array('link'=>"../out/out.IndexInfo.php", 'label'=>'fulltext_info');
		}

		$menuitems['backup_log_management'] = array('link'=>"#", 'label'=>'backup_log_management');
		$menuitems['backup_log_management']['children'][] = array('link'=>"../out/out.BackupTools.php", 'label'=>'backup_tools');
		if ($this->params['logfileenable'])
			$menuitems['backup_log_management']['children'][] = array('link'=>"../out/out.LogManagement.php", 'label'=>'log_management');

		$menuitems['misc'] = array('link'=>"#", 'label'=>'misc');
		$menuitems['misc']['children']['import_fs'] = array('link'=>"../out/out.ImportFS.php", 'label'=>'import_fs');
		$menuitems['misc']['children']['folders_and_documents_statistic'] = array('link'=>"../out/out.Statistic.php", 'label'=>'folders_and_documents_statistic');
		$menuitems['misc']['children']['charts'] = array('link'=>"../out/out.Charts.php", 'label'=>'charts');
		$menuitems['misc']['children']['timeline'] = array('link'=>"../out/out.Timeline.php", 'label'=>'timeline');
		$menuitems['misc']['children']['objectcheck'] = array('link'=>"../out/out.ObjectCheck.php", 'label'=>'objectcheck');
		$menuitems['misc']['children']['documents_expired'] = array('link'=>"../out/out.ExpiredDocuments.php", 'label'=>'documents_expired');
		$menuitems['misc']['children']['extension_manager'] = array('link'=>"../out/out.ExtensionMgr.php", 'label'=>'extension_manager');
		$menuitems['misc']['children']['clear_cache'] = array('link'=>"../out/out.ClearCache.php", 'label'=>'clear_cache');
		$menuitems['misc']['children']['version_info'] = array('link'=>"../out/out.Info.php", 'label'=>'version_info');

		/* Check if hook exists because otherwise callHook() will override $menuitems */
		if($this->hasHook('admintoolsNavigationBar'))
			$menuitems = $this->callHook('admintoolsNavigationBar', $menuitems);

		self::showNavigationBar($menuitems);

		echo "   </ul>\n";
		echo "<ul class=\"nav\">\n";
		echo "</ul>\n";
		echo "</div>\n";
		return;
	} /* }}} */
	
	private function calendarOldNavigationBar($d){ /* {{{ */
		$ds="&day=".$d[0]."&month=".$d[1]."&year=".$d[2];
		echo "<id=\"first\"><a href=\"../out/out.CalendarOld.php?mode=y\" class=\"brand\">".getMLText("calendar")."</a>\n";
		echo "<div class=\"nav-collapse col2\">\n";
		echo "<ul class=\"nav\">\n";

		echo "<li><a href=\"../out/out.CalendarOld.php?mode=w".$ds."\">".getMLText("week_view")."</a></li>\n";
		echo "<li><a href=\"../out/out.CalendarOld.php?mode=m".$ds."\">".getMLText("month_view")."</a></li>\n";
		echo "<li><a href=\"../out/out.CalendarOld.php?mode=y".$ds."\">".getMLText("year_view")."</a></li>\n";
		if (!$this->params['user']->isGuest()) echo "<li><a href=\"../out/out.AddEvent.php\">".getMLText("add_event")."</a></li>\n";
		echo "</ul>\n";
		echo "</div>\n";
		return;
	
	} /* }}} */

	private function calendarNavigationBar($d){ /* {{{ */
		echo "<id=\"first\"><a href=\"../out/out.Calendar.php\" class=\"brand\">".getMLText("calendar")."</a>\n";
		echo "<div class=\"nav-collapse col2\">\n";
		echo "<ul class=\"nav\">\n";

		$menuitems = array();
		if (!$this->params['user']->isGuest())
			$menuitems['addevent'] = array('link'=>"../out/out.AddEvent.php", 'label'=>'add_event');

		/* Check if hook exists because otherwise callHook() will override $menuitems */
		if($this->hasHook('calendarNavigationBar'))
			$menuitems = $this->callHook('calendarNavigationBar', $menuitems);

		self::showNavigationBar($menuitems);

		echo "</ul>\n";
		echo "</div>\n";
		return;
	
	} /* }}} */

	function pageList($pageNumber, $totalPages, $baseURI, $params) { /* {{{ */

		$maxpages = 25; // skip pages when more than this is shown
		$range = 5; // pages left and right of current page
		if (!is_numeric($pageNumber) || !is_numeric($totalPages) || $totalPages<2) {
			return;
		}

		// Construct the basic URI based on the $_GET array. One could use a
		// regular expression to strip out the pg (page number) variable to
		// achieve the same effect. This seems to be less haphazard though...
		$resultsURI = $baseURI;
		$first=true;
		foreach ($params as $key=>$value) {
			// Don't include the page number in the basic URI. This is added in
			// during the list display loop.
			if (!strcasecmp($key, "pg")) {
				continue;
			}
			if (is_array($value)) {
				foreach ($value as $subkey=>$subvalue) {
					$resultsURI .= ($first ? "?" : "&").$key."%5B".$subkey."%5D=".urlencode($subvalue);
					$first = false;
				}
			}
			else {
					$resultsURI .= ($first ? "?" : "&").$key."=".urlencode($value);
			}
			$first = false;
		}

		echo "<div class=\"pagination pagination-small\">";
		echo "<ul>";
		if($totalPages <= $maxpages) {
			for ($i = 1; $i <= $totalPages; $i++) {
				echo "<li ".($i == $pageNumber ? 'class="active"' : "" )."><a href=\"".$resultsURI.($first ? "?" : "&")."pg=".$i."\">".$i."</a></li>";
			}
		} else {
			if($pageNumber-$range > 1)
				$start = $pageNumber-$range;
			else
				$start = 2;
			if($pageNumber+$range < $totalPages)
				$end = $pageNumber+$range;
			else
				$end = $totalPages-1;
			/* Move start or end to always show 2*$range items */
			$diff = $end-$start-2*$range;
			if($diff < 0) {
				if($start > 2)
					$start += $diff;
				if($end < $totalPages-1)
					$end -= $diff;
			}
			if($pageNumber > 1)
				echo "<li><a href=\"".$resultsURI.($first ? "?" : "&")."pg=".($pageNumber-1)."\">&laquo;</a></li>";
			echo "<li ".(1 == $pageNumber ? 'class="active"' : "" )."><a href=\"".$resultsURI.($first ? "?" : "&")."pg=1\">1</a></li>";
			if($start > 2)
				echo "<li><span>...</span></li>";
			for($j=$start; $j<=$end; $j++)
				echo "<li ".($j == $pageNumber ? 'class="active"' : "" )."><a href=\"".$resultsURI.($first ? "?" : "&")."pg=".$j."\">".$j."</a></li>";
			if($end < $totalPages-1)
				echo "<li><span>...</span></li>";
			if($end < $totalPages)
				echo "<li ".($totalPages == $pageNumber ? 'class="active"' : "" )."><a href=\"".$resultsURI.($first ? "?" : "&")."pg=".$totalPages."\">".$totalPages."</a></li>";
			if($pageNumber < $totalPages)
				echo "<li><a href=\"".$resultsURI.($first ? "?" : "&")."pg=".($pageNumber+1)."\">&raquo;</a></li>";
		}
		if ($totalPages>1) {
			echo "<li><a href=\"".$resultsURI.($first ? "?" : "&")."pg=all\">".getMLText("all_pages")."</a></li>";
		}
		echo "</ul>";
		echo "</div>";

		return;
	} /* }}} */

	function contentContainer($content) { /* {{{ */
		echo "<div class=\"well\">\n";
		echo $content;
		echo "</div>\n";
		return;
	} /* }}} */

	function contentContainerStart($class='', $id='') { /* {{{ */
		echo "<div class=\"well".($class ? " ".$class : "")."\"".($id ? " id=\"".$id."\"" : "").">\n";
		return;
	} /* }}} */

	function contentContainerEnd() { /* {{{ */

		echo "</div>\n";
		return;
	} /* }}} */

	function contentHeading($heading, $noescape=false, $toggle=false, $id='') { /* {{{ */

		if($noescape){
                    echo "<legend>".$heading."</legend>\n";}
		elseif($toggle){
                        echo "<legend>".htmlspecialchars($heading)." <span style='float:right;' id='$id' class='tglbtn icon-th-list'></span></legend>\n";}
                else{
                    echo "<legend>".htmlspecialchars($heading)."</legend>\n";}
		return;
	} /* }}} */

	function contentSubHeading($heading, $first=false) { /* {{{ */

//		echo "<div class=\"contentSubHeading\"".($first ? " id=\"first\"" : "").">".htmlspecialchars($heading)."</div>\n";
		echo "<h5>".$heading."</h5>";
		return;
	} /* }}} */

	function formField($title, $value, $params = array())
	{ /* {{{ */
		if ($title !== null) {
			echo "<div class=\"control-group\">";
			echo "	<label class=\"control-label\">" . $title . ":</label>";
			echo "	<div class=\"controls\">";
		}
		if (isset($params['field_wrap'][0]))
			echo $params['field_wrap'][0];
		if (is_string($value)) {
			echo $value;
		} elseif (is_array($value)) {
			switch ($value['element']) {
				case 'select':
					echo '<select' .
						(!empty($value['id']) ? ' id="' . $value['id'] . '"' : '') .
						(!empty($value['name']) ? ' name="' . $value['name'] . '"' : '') .
						(!empty($value['class']) ? ' class="' . $value['class'] . '"' : '') .
						(!empty($value['disabled']) ? ' disabled' : '') .
						(!empty($value['multiple']) ? ' multiple' : '') .
						(!empty($value['required']) ? ' required' : '');
					if (!empty($value['attributes']) && is_array($value['attributes']))
						foreach ($value['attributes'] as $a)
							echo ' ' . $a[0] . '="' . $a[1] . '"';
					echo ">";
					if (isset($value['options']) && is_array($value['options'])) {
						foreach ($value['options'] as $val) {
							echo '<option value="' . $val[0] . '"' . (!empty($val[2]) ? ' selected' : '');
							if (!empty($val[3]) && is_array($val[3]))
								foreach ($val[3] as $a)
									echo ' ' . $a[0] . '="' . $a[1] . '"';
							echo '>' . $val[1] . '</option>';
						}
					}
					echo '</select>';
					break;
				case 'textarea':
					echo '<textarea' .
						(!empty($value['id']) ? ' id="' . $value['id'] . '"' : '') .
						(!empty($value['name']) ? ' name="' . $value['name'] . '"' : '') .
						(!empty($value['rows']) ? ' rows="' . $value['rows'] . '"' : '') .
						(!empty($value['cols']) ? ' rows="' . $value['cols'] . '"' : '') .
						(!empty($value['required']) ? ' required' : '') . ">" . (!empty($value['value']) ? $value['value'] : '') . "</textarea>";
					break;
				case 'input':
				default:
					echo '<input' .
						(!empty($value['type']) ? ' type="' . $value['type'] . '"' : '') .
						(!empty($value['id']) ? ' id="' . $value['id'] . '"' : '') .
						(!empty($value['name']) ? ' name="' . $value['name'] . '"' : '') .
						(!empty($value['value']) ? ' value="' . $value['value'] . '"' : '') .
						(!empty($value['style']) ? ' style="' . $value['style'] . '"' : '') .
						(!empty($value['placeholder']) ? ' placeholder="' . $value['placeholder'] . '"' : '') .
						(!empty($value['pattern']) ? ' pattern="' . $value['pattern'] . '"' : '') .
						(!empty($value['title']) ? ' title="' . $value['title'] . '"' : '') .
						(!empty($value['autocomplete']) ? ' autocomplete="' . $value['autocomplete'] . '"' : '') .
						(!empty($value['checked']) ? ' checked' : '') .
						(!empty($value['disabled']) ? ' disabled' : '') .
						(!empty($value['readonly']) ? ' readonly' : '') .
						(!empty($value['required']) ? ' required' : '');
					if (!empty($value['attributes']) && is_array($value['attributes']))
						foreach ($value['attributes'] as $a)
							echo ' ' . $a[0] . '="' . $a[1] . '"';
					echo ">";
					break;
			}
		}
		if (isset($params['field_wrap'][1]))
			echo $params['field_wrap'][1];
		if ($title !== null) {
			echo "</div>";
			echo "</div>";
		}
		return;
	} /* }}} */

	function formSubmit($value, $name='') { /* {{{ */
		echo "<div class=\"controls\">\n";
		echo "<button type=\"submit\" class=\"btn\"".($name ? ' name="'.$name.'" id="'.$name.'"' : '').">".$value."</button>\n";
		echo "</div>\n";
	} /* }}} */

	function getMimeIcon($fileType) { /* {{{ */
		// for extension use LOWER CASE only
		$icons = array();
		$icons["txt"]  = "text-x-preview.svg";
		$icons["text"] = "text-x-preview.svg";
		$icons["tex"]  = "text-x-preview.svg";
		$icons["doc"]  = "office-document.svg";
		$icons["dot"]  = "office-document.svg";
		$icons["docx"] = "office-document.svg";
		$icons["dotx"] = "office-document.svg";
		$icons["rtf"]  = "office-document.svg";
		$icons["xls"]  = "office-spreadsheet.svg";
		$icons["xlt"]  = "office-spreadsheet.svg";
		$icons["xlsx"] = "office-spreadsheet.svg";
		$icons["xltx"] = "office-spreadsheet.svg";
		$icons["ppt"]  = "office-presentation.svg";
		$icons["pot"]  = "office-presentation.svg";
		$icons["pptx"] = "office-presentation.svg";
		$icons["potx"] = "office-presentation.svg";
		$icons["exe"]  = "executable.svg";
		$icons["html"] = "web.svg";
		$icons["htm"]  = "web.svg";
		$icons["gif"]  = "image.svg";
		$icons["jpg"]  = "image.svg";
		$icons["jpeg"] = "image.svg";
		$icons["bmp"]  = "image.svg";
		$icons["png"]  = "image.svg";
		$icons["tif"]  = "image.svg";
		$icons["tiff"] = "image.svg";
		$icons["log"]  = "text-x-preview.svg";
		$icons["midi"] = "audio.svg";
		$icons["pdf"]  = "gnome-mime-application-pdf.svg";
		$icons["wav"]  = "audio.svg";
		$icons["mp3"]  = "audio.svg";
		$icons["opus"]  = "audio.svg";
		$icons["c"]    = "text-x-preview.svg";
		$icons["cpp"]  = "text-x-preview.svg";
		$icons["h"]    = "text-x-preview.svg";
		$icons["java"] = "text-x-preview.svg";
		$icons["py"]   = "text-x-preview.svg";
		$icons["tar"]  = "package.svg";
		$icons["gz"]   = "package.svg";
		$icons["7z"]   = "package.svg";
		$icons["bz"]   = "package.svg";
		$icons["bz2"]  = "package.svg";
		$icons["tgz"]  = "package.svg";
		$icons["zip"]  = "package.svg";
		$icons["rar"]  = "package.svg";
		$icons["mpg"]  = "video.svg";
		$icons["avi"]  = "video.svg";
		$icons["ods"]  = "office-spreadsheet.svg";
		$icons["ots"]  = "office-spreadsheet.svg";
		$icons["sxc"]  = "office-spreadsheet.svg";
		$icons["stc"]  = "office-spreadsheet.svg";
		$icons["odt"]  = "office-document.svg";
		$icons["ott"]  = "office-document.svg";
		$icons["sxw"]  = "office-document.svg";
		$icons["stw"]  = "office-document.svg";
		$icons["odp"]  = "office-presentation.svg";
		$icons["otp"]  = "office-presentation.svg";
		$icons["sxi"]  = "office-presentation.svg";
		$icons["sti"]  = "office-presentation.svg";
		$icons["odg"]  = "office-drawing.svg";
		$icons["otg"]  = "office-drawing.svg";
		$icons["sxd"]  = "office-drawing.svg";
		$icons["std"]  = "office-drawing.svg";
		$icons["odf"]  = "ooo_formula.png";
		$icons["sxm"]  = "ooo_formula.png";
		$icons["smf"]  = "ooo_formula.png";
		$icons["mml"]  = "ooo_formula.png";

		$icons["default"] = "text-x-preview.svg"; //"default.png";

		$ext = strtolower(substr($fileType, 1));
		if (isset($icons[$ext])) {
			return $this->imgpath.$icons[$ext];
		}
		else {
			return $this->imgpath.$icons["default"];
		}
	} /* }}} */

	function printFileChooserJs() { /* {{{ */
?>
$(document).ready(function() {
	$(document).on('change', '.btn-file :file', function() {
		var input = $(this),
		numFiles = input.get(0).files ? input.get(0).files.length : 1,
		label = input.val().replace(/\\/g, '/').replace(/.*\//, '');
		input.trigger('fileselect', [numFiles, label]);
	});

	$(document).on('fileselect', '.upload-file .btn-file :file', function(event, numFiles, label) {
		var input = $(this).parents('.input-append').find(':text'),
		log = numFiles > 1 ? numFiles + ' files selected' : label;

		if( input.length ) {
			input.val(log);
		} else {
//			if( log ) alert(log);
		}
	});
});
<?php
	} /* }}} */

	function getFileChooserHtml($varname='userfile', $multiple=false, $accept='') { /* {{{ */
		$id = preg_replace('/[^A-Za-z]/', '', $varname);
		$html = '
	<div id="'.$id.'-upload-files">
		<div id="'.$id.'-upload-file" class="upload-file">
			<div class="input-append">
				<input type="text" class="form-control" readonly>
				<span class="btn btn-default btn-file">
					'.getMLText("browse").'&hellip; <input id="'.$id.'" type="file" name="'.$varname.'"'.($multiple ? " multiple /" : "").($accept ? ' accept="'.$accept.'"' : "").'">
				</span>
			</div>
		</div>
	</div>
';
		return $html;
	} /* }}} */

	function printFileChooser($varname='userfile', $multiple=false, $accept='') { /* {{{ */
		echo self::getFileChooserHtml($varname, $multiple, $accept);
	} /* }}} */

	function printDateChooser($defDate = '', $varName) { /* {{{ */
		echo self::getDateChooser($defDate, $varName);
	} /* }}} */

	function getDateChooser($defDate = '', $varName, $lang='') { /* {{{ */
		$content = '
			<span class="input-append date span12 datepicker" id="'.$varName.'date" data-date="'.$defDate.'" data-selectmenu="presetexpdate" data-date-format="yyyy-mm-dd"'.($lang ? 'data-date-language="'.str_replace('_', '-', $lang).'"' : '').'>
				<input class="span6" size="16" name="'.$varName.'" type="text" value="'.$defDate.'">
				<span class="add-on"><i class="icon-calendar"></i></span>
			</span>';
		return $content;
	} /* }}} */

	function __printDateChooser($defDate = -1, $varName) { /* {{{ */
	
		if ($defDate == -1)
			$defDate = mktime();
		$day   = date("d", $defDate);
		$month = date("m", $defDate);
		$year  = date("Y", $defDate);

		print "<select name=\"" . $varName . "day\">\n";
		for ($i = 1; $i <= 31; $i++)
		{
			print "<option value=\"" . $i . "\"";
			if (intval($day) == $i)
				print " selected";
			print ">" . $i . "</option>\n";
		}
		print "</select> \n";
		print "<select name=\"" . $varName . "month\">\n";
		for ($i = 1; $i <= 12; $i++)
		{
			print "<option value=\"" . $i . "\"";
			if (intval($month) == $i)
				print " selected";
			print ">" . $i . "</option>\n";
		}
		print "</select> \n";
		print "<select name=\"" . $varName . "year\">\n";	
		for ($i = $year-5 ; $i <= $year+5 ; $i++)
		{
			print "<option value=\"" . $i . "\"";
			if (intval($year) == $i)
				print " selected";
			print ">" . $i . "</option>\n";
		}
		print "</select>";
	} /* }}} */

	function printSequenceChooser($objArr, $keepID = -1) { /* {{{ */
		echo $this->getSequenceChooser($objArr, $keepID);
	} /* }}} */

	function getSequenceChooser($objArr, $keepID = -1) { /* {{{ */
		if (count($objArr) > 0) {
			$max = $objArr[count($objArr)-1]->getSequence() + 1;
			$min = $objArr[0]->getSequence() - 1;
		}
		else {
			$max = 1.0;
		}
		$content = "<select name=\"sequence\">\n";
		if ($keepID != -1) {
			$content .= "  <option value=\"keep\">" . getMLText("seq_keep");
		}
		if($this->params['defaultposition'] != 'start')
			$content .= "  <option value=\"".$max."\">" . getMLText("seq_end");
		if (count($objArr) > 0) {
			$content .= "  <option value=\"".$min."\">" . getMLText("seq_start");
		}
		if($this->params['defaultposition'] == 'start')
			$content .= "  <option value=\"".$max."\">" . getMLText("seq_end");
		for ($i = 0; $i < count($objArr) - 1; $i++) {
			if (($objArr[$i]->getID() == $keepID) || (($i + 1 < count($objArr)) && ($objArr[$i+1]->getID() == $keepID))) {
				continue;
			}
			$index = ($objArr[$i]->getSequence() + $objArr[$i+1]->getSequence()) / 2;
			$content .= "  <option value=\"".$index."\">" . getMLText("seq_after", array("prevname" => htmlspecialchars($objArr[$i]->getName())));
		}
		$content .= "</select>";
		return $content;
	} /* }}} */

	function getDocumentChooserHtml($formName) { /* {{{ */
		$content = '';
		$content .= "<input type=\"hidden\" id=\"docid".$formName."\" name=\"docid\" value=\"\">";
		$content .= "<div class=\"input-append\">\n";
		$content .= "<input type=\"text\" id=\"choosedocsearch".$formName."\" data-target=\"docid".$formName."\" data-provide=\"typeahead\" name=\"docname".$formName."\" placeholder=\"".getMLText('type_to_search')."\" autocomplete=\"off\" />";
		$content .= "<a data-target=\"#docChooser".$formName."\" href=\"../out/out.DocumentChooser.php?form=".$formName."&folderid=".$this->params['rootfolderid']."\" role=\"button\" class=\"btn\" data-toggle=\"modal\">".getMLText("document")."…</a>\n";
		$content .= "</div>\n";
		$content .= '
<div class="modal hide" id="docChooser'.$formName.'" tabindex="-1" role="dialog" aria-labelledby="docChooserLabel" aria-hidden="true">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
    <h3 id="docChooserLabel">'.getMLText("choose_target_document").'</h3>
  </div>
  <div class="modal-body">
		<p>'.getMLText('tree_loading').'</p>
  </div>
  <div class="modal-footer">
    <button class="btn btn-primary" data-dismiss="modal" aria-hidden="true">'.getMLText("close").'</button>
  </div>
</div>
';
		return $content;
	} /* }}} */

	function printDocumentChooserHtml($formName) { /* {{{ */
		echo self::getDocumentChooserHtml($formName);
	} /* }}} */

	function printDocumentChooserJs($formName) { /* {{{ */
?>
function documentSelected<?php echo $formName ?>(id, name) {
	$('#docid<?php echo $formName ?>').val(id);
	$('#choosedocsearch<?php echo $formName ?>').val(name);
	$('#docChooser<?php echo $formName ?>').modal('hide');
}
function folderSelected<?php echo $formName ?>(id, name) {
}
<?php
	} /* }}} */

	function printDocumentChooser($formName) { /* {{{ */
		$this->printDocumentChooserHtml($formName);
?>
		<script language="JavaScript">
<?php
		$this->printDocumentChooserJs($formName);
?>
		</script>
<?php
	} /* }}} */

	function getFolderChooserHtml($form, $accessMode, $exclude = -1, $default = false, $formname = '') { /* {{{ */
		$formid = "targetid".$form;
		if(!$formname)
			$formname = "targetid";
		$content = '';
		$content .= "<input type=\"hidden\" id=\"".$formid."\" name=\"".$formname."\" value=\"". (($default) ? $default->getID() : "") ."\">";
		$content .= "<div class=\"input-append\">\n";
		$content .= "<input type=\"text\" id=\"choosefoldersearch".$form."\" data-target=\"".$formid."\" data-provide=\"typeahead\"  name=\"targetname".$form."\" value=\"". (($default) ? htmlspecialchars($default->getName()) : "") ."\" placeholder=\"".getMLText('type_to_search')."\" autocomplete=\"off\" target=\"".$formid."\"/>";
		$content .= "<button type=\"button\" class=\"btn\" id=\"clearfolder".$form."\"><i class=\"icon-remove\"></i></button>";
		$content .= "<a data-target=\"#folderChooser".$form."\" href=\"../out/out.FolderChooser.php?form=".$form."&mode=".$accessMode."&exclude=".$exclude."\" role=\"button\" class=\"btn\" data-toggle=\"modal\">".getMLText("folder")."…</a>\n";
		$content .= "</div>\n";
		$content .= '
<div class="modal hide" id="folderChooser'.$form.'" tabindex="-1" role="dialog" aria-labelledby="folderChooser'.$form.'Label" aria-hidden="true">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
    <h3 id="folderChooser'.$form.'Label">'.getMLText("choose_target_folder").'</h3>
  </div>
  <div class="modal-body">
		<p>'.getMLText('tree_loading').'</p>
  </div>
  <div class="modal-footer">
    <button class="btn btn-primary" data-dismiss="modal" aria-hidden="true">'.getMLText("close").'</button>
  </div>
</div>
';
		return $content;
	} /* }}} */

	function printFolderChooserHtml($form, $accessMode, $exclude = -1, $default = false, $formname = '') { /* {{{ */
		echo self::getFolderChooserHtml($form, $accessMode, $exclude, $default, $formname);
	} /* }}} */

	function printFolderChooserJs($form) { /* {{{ */
?>
function folderSelected<?php echo $form ?>(id, name) {
	$('#targetid<?php echo $form ?>').val(id);
	$('#choosefoldersearch<?php echo $form ?>').val(name);
	$('#folderChooser<?php echo $form ?>').modal('hide');
}
$(document).ready(function() {
	$('#clearfolder<?php print $form ?>').click(function(ev) {
		$('#choosefoldersearch<?php echo $form ?>').val('');
		$('#targetid<?php echo $form ?>').val('');
	});
});
<?php
	} /* }}} */

	function printFolderChooser($form, $accessMode, $exclude = -1, $default = false, $formname='') { /* {{{ */
		$this->printFolderChooserHtml($form, $accessMode, $exclude, $default, $formname);
?>
		<script language="JavaScript">
<?php
		$this->printFolderChooserJs($form);
?>
		</script>
<?php
	} /* }}} */

	/**
	 * Do not use anymore. Was previously used to show the category
	 * chooser. It has been replaced by a select box
	 */
	function printCategoryChooser($formName, $categories=array()) { /* {{{ */
?>
<script language="JavaScript">
	function clearCategory<?php print $formName ?>() {
		document.<?php echo $formName ?>.categoryid<?php echo $formName ?>.value = '';
		document.<?php echo $formName ?>.categoryname<?php echo $formName ?>.value = '';
	}

	function acceptCategories() {
		var targetName = document.<?php echo $formName?>.categoryname<?php print $formName ?>;
		var targetID = document.<?php echo $formName?>.categoryid<?php print $formName ?>;
		var value = '';
		$('#keywordta option:selected').each(function(){
			value += ' ' + $(this).text();
		});
		targetName.value = value;
		targetID.value = $('#keywordta').val();
		return true;
	}
</script>
<?php
		$ids = $names = array();
		if($categories) {
			foreach($categories as $cat) {
				$ids[] = $cat->getId();
				$names[] = htmlspecialchars($cat->getName());
			}
		}
		print "<input type=\"hidden\" name=\"categoryid".$formName."\" value=\"".implode(',', $ids)."\">";
		print "<div class=\"input-append\">\n";
		print "<input type=\"text\" disabled name=\"categoryname".$formName."\" value=\"".implode(' ', $names)."\">";
		print "<button type=\"button\" class=\"btn\" onclick=\"javascript:clearCategory".$formName."();\"><i class=\"icon-remove\"></i></button>";
		print "<a data-target=\"#categoryChooser\" href=\"../out/out.CategoryChooser.php?form=form1&cats=".implode(',', $ids)."\" role=\"button\" class=\"btn\" data-toggle=\"modal\">".getMLText("category")."…</a>\n";
		print "</div>\n";
?>
<div class="modal hide" id="categoryChooser" tabindex="-1" role="dialog" aria-labelledby="categoryChooserLabel" aria-hidden="true">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
    <h3 id="categoryChooserLabel"><?php printMLText("choose_target_category") ?></h3>
  </div>
  <div class="modal-body">
		<p><?php printMLText('categories_loading') ?></p>
  </div>
  <div class="modal-footer">
    <button class="btn btn-primary" data-dismiss="modal" aria-hidden="true"><?php printMLText("close") ?></button>
    <button class="btn" data-dismiss="modal" aria-hidden="true" onClick="acceptCategories();"><i class="icon-save"></i> <?php printMLText("save") ?></button>
  </div>
</div>
<?php
	} /* }}} */

	function printKeywordChooserHtml($formName, $keywords='', $fieldname='keywords') { /* {{{ */
		echo self::getKeywordChooserHtml($formName, $keywords, $fieldname); 
	} /* }}} */

	function getKeywordChooserHtml($formName, $keywords='', $fieldname='keywords') { /* {{{ */
		$strictformcheck = $this->params['strictformcheck'];
		$content = '';
		$content .= '
		    <div class="input-append">
				<input type="text" name="'.$fieldname.'" id="'.$fieldname.'" value="'.htmlspecialchars($keywords).'"'.($strictformcheck ? ' required' : '').' />
				<a data-target="#keywordChooser" role="button" class="btn" data-toggle="modal" href="../out/out.KeywordChooser.php?target='.$formName.'">'.getMLText("keywords").'…</a>
		    </div>
<div class="modal hide" id="keywordChooser" tabindex="-1" role="dialog" aria-labelledby="keywordChooserLabel" aria-hidden="true">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
    <h3 id="keywordChooserLabel">'.getMLText("use_default_keywords").'</h3>
  </div>
  <div class="modal-body">
		<p>'.getMLText('keywords_loading').'</p>
  </div>
  <div class="modal-footer">
    <button class="btn btn-primary" data-dismiss="modal" aria-hidden="true">'. getMLText("close").'</button>
    <button class="btn" data-dismiss="modal" aria-hidden="true" id="acceptkeywords"><i class="icon-save"></i> '.getMLText("save").'</button>
  </div>
</div>';
		return $content;
	} /* }}} */

	function printKeywordChooserJs($formName) { /* {{{ */
?>
$(document).ready(function() {
	$('#acceptkeywords').click(function(ev) {
		acceptKeywords();
	});
});
<?php
	} /* }}} */

	function printKeywordChooser($formName, $keywords='', $fieldname='keywords') { /* {{{ */
		$this->printKeywordChooserHtml($formName, $keywords, $fieldname);
?>
		<script language="JavaScript">
<?php
		$this->printKeywordChooserJs($formName);
?>
		</script>
<?php
	} /* }}} */

	function printAttributeEditField($attrdef, $attribute, $fieldname='attributes', $norequire=false) { /* {{{ */
		echo self::getAttributeEditField($attrdef, $attribute, $fieldname, $norequire);
	} /* }}} */

	function getAttributeEditField($attrdef, $attribute, $fieldname='attributes', $norequire=false) { /* {{{ */
		$content = '';
		switch($attrdef->getType()) {
		case SeedDMS_Core_AttributeDefinition::type_boolean:
			$content .= "<input type=\"hidden\" name=\"".$fieldname."[".$attrdef->getId()."]\" value=\"\" />";
			$content .= "<input type=\"checkbox\" id=\"".$fieldname."_".$attrdef->getId()."\" name=\"".$fieldname."[".$attrdef->getId()."]\" value=\"1\" ".(($attribute && $attribute->getValue()) ? 'checked' : '')." />";
			break;
		case SeedDMS_Core_AttributeDefinition::type_date:
				$objvalue = $attribute ? (is_object($attribute) ? $attribute->getValue() : $attribute) : '';
        $content .= '<span class="input-append date datepicker" data-date="'.date('Y-m-d').'" data-date-format="yyyy-mm-dd" data-date-language="'.str_replace('_', '-', $this->params['session']->getLanguage()).'">
					<input id="'.$fieldname.'_'.$attrdef->getId().'" class="span9" size="16" name="'.$fieldname.'['.$attrdef->getId().']" type="text" value="'.($objvalue ? $objvalue : '').'">
          <span class="add-on"><i class="icon-calendar"></i></span>
				</span>';
			break;
		case SeedDMS_Core_AttributeDefinition::type_email:
			$objvalue = $attribute ? (is_object($attribute) ? $attribute->getValue() : $attribute) : '';
			$content .= "<input type=\"text\" name=\"".$fieldname."[".$attrdef->getId()."]\" value=\"".htmlspecialchars($objvalue)."\"".((!$norequire && $attrdef->getMinValues() > 0) ? ' required' : '').' data-rule-email="true"'." />";
			break;
		case SeedDMS_Core_AttributeDefinition::type_float:
			$objvalue = $attribute ? (is_object($attribute) ? $attribute->getValue() : $attribute) : '';
			$content .= "<input type=\"text\" id=\"".$fieldname."_".$attrdef->getId()."\" name=\"".$fieldname."[".$attrdef->getId()."]\" value=\"".htmlspecialchars($objvalue)."\"".((!$norequire && $attrdef->getMinValues() > 0) ? ' required' : '')." data-rule-number=\"true\"/>";
			break;
		default:
			if($valueset = $attrdef->getValueSetAsArray()) {
				$content .= "<input type=\"hidden\" name=\"".$fieldname."[".$attrdef->getId()."]\" value=\"\"/>";
				$content .= "<select id=\"".$fieldname."_".$attrdef->getId()."\" name=\"".$fieldname."[".$attrdef->getId()."]";
				if($attrdef->getMultipleValues()) {
					$content .= "[]\" multiple";
				} else {
					$content .= "\"";
				}
				$content .= "".((!$norequire && $attrdef->getMinValues() > 0) ? ' required' : '')." class=\"chzn-select-deselect\" data-placeholder=\"".getMLText("select_value")."\">";
				if(!$attrdef->getMultipleValues()) {
					$content .= "<option value=\"\"></option>";
				}
				$objvalue = $attribute ? (is_object($attribute) ? $attribute->getValueAsArray() : $attribute) : array();
				foreach($valueset as $value) {
					if($value) {
						$content .= "<option value=\"".htmlspecialchars($value)."\"";
						if(is_array($objvalue) && in_array($value, $objvalue))
							$content .= " selected";
						elseif($value == $objvalue)
							$content .= " selected";
						$content .= ">".htmlspecialchars($value)."</option>";
					}
				}
				$content .= "</select>";
			} else {
				$objvalue = $attribute ? (is_object($attribute) ? $attribute->getValue() : $attribute) : '';
				if(strlen($objvalue) > 80) {
					$content .= "<textarea id=\"".$fieldname."_".$attrdef->getId()."\" class=\"input-xxlarge\" name=\"".$fieldname."[".$attrdef->getId()."]\"".((!$norequire && $attrdef->getMinValues() > 0) ? ' required' : '').">".htmlspecialchars($objvalue)."</textarea>";
				} else {
					$content .= "<input type=\"text\" id=\"".$fieldname."_".$attrdef->getId()."\" name=\"".$fieldname."[".$attrdef->getId()."]\" value=\"".htmlspecialchars($objvalue)."\"".((!$norequire && $attrdef->getMinValues() > 0) ? ' required' : '').($attrdef->getType() == SeedDMS_Core_AttributeDefinition::type_int ? ' data-rule-digits="true"' : '')." />";
				}
			}
			break;
		}
		return $content;
	} /* }}} */

	function printDropFolderChooserHtml($formName, $dropfolderfile="", $showfolders=0) { /* {{{ */
		echo self::getDropFolderChooserHtml($formName, $dropfolderfile, $showfolders);
	} /* }}} */

	function getDropFolderChooserHtml($formName, $dropfolderfile="", $showfolders=0) { /* {{{ */
		$content =  "<div class=\"input-append\">\n";
		$content .= "<input readonly type=\"text\" id=\"dropfolderfile".$formName."\" name=\"dropfolderfile".$formName."\" value=\"".$dropfolderfile."\">";
		$content .= "<button type=\"button\" class=\"btn\" id=\"clearfilename".$formName."\"><i class=\"icon-remove\"></i></button>";
		$content .= "<a data-target=\"#dropfolderChooser\" href=\"../out/out.DropFolderChooser.php?form=form1&dropfolderfile=".urlencode($dropfolderfile)."&showfolders=".$showfolders."\" role=\"button\" class=\"btn\" data-toggle=\"modal\">".($showfolders ? getMLText("choose_target_folder"): getMLText("choose_target_file"))."…</a>\n";
		$content .= "</div>\n";
		$content .= '
<div class="modal hide" id="dropfolderChooser" tabindex="-1" role="dialog" aria-labelledby="dropfolderChooserLabel" aria-hidden="true">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
    <h3 id="dropfolderChooserLabel">'.($showfolders ? getMLText("choose_target_folder"): getMLText("choose_target_file")).'</h3>
  </div>
  <div class="modal-body">
		<p>'.getMLText('files_loading').'</p>
		</div>
  <div class="modal-footer">
    <button class="btn btn-primary" data-dismiss="modal" aria-hidden="true">'.getMLText("close").'</button>
  </div>
</div>
';
		return $content;
	} /* }}} */

	function printDropFolderChooserJs($formName, $showfolders=0) { /* {{{ */
?>
/* Set up a callback which is called when a folder in the tree is selected */
modalDropfolderChooser = $('#dropfolderChooser');
function fileSelected(name) {
	$('#dropfolderfile<?php echo $formName ?>').val(name);
	modalDropfolderChooser.modal('hide');
}
<?php if($showfolders) { ?>
function folderSelected(name) {
	$('#dropfolderfile<?php echo $formName ?>').val(name);
	modalDropfolderChooser.modal('hide');
}
<?php } ?>
$(document).ready(function() {
	$('#clearfilename<?php print $formName ?>').click(function(ev) {
		$('#dropfolderfile<?php echo $formName ?>').val('');
	});
});
<?php
	} /* }}} */

	function printDropFolderChooser($formName, $dropfolderfile="", $showfolders=0) { /* {{{ */
		$this->printDropFolderChooserHtml($formName, $dropfolderfile, $showfolders);
?>
		<script language="JavaScript">
<?php
		$this->printDropFolderChooserJs($formName, $showfolders);
?>
		</script>
<?php
	} /* }}} */

	function getImgPath($img) { /* {{{ */

		if ( is_file($this->imgpath.$img) ) {
			return $this->imgpath.$img;
		}
		return "";
	} /* }}} */

	function getCountryFlag($lang) { /* {{{ */
		switch($lang) {
		case "en_GB":
			return 'flags/gb.png';
			break;
		default:
			return 'flags/'.substr($lang, 0, 2).'.png';
		}
	} /* }}} */

	function printImgPath($img) { /* {{{ */
		print $this->getImgPath($img);
	} /* }}} */

	function infoMsg($msg) { /* {{{ */
		echo "<div class=\"alert alert-info\">\n";
		echo $msg;
		echo "</div>\n";
	} /* }}} */

	function warningMsg($msg) { /* {{{ */
		echo "<div class=\"alert alert-warning\">\n";
		echo $msg;
		echo "</div>\n";
	} /* }}} */

	function errorMsg($msg) { /* {{{ */
		echo "<div class=\"alert alert-error\">\n";
		echo $msg;
		echo "</div>\n";
	} /* }}} */

	function ___exitError($pagetitle, $error, $noexit=false, $plain=false) { /* {{{ */

		/* This is just a hack to prevent creation of js files in an error
		 * case, because they will contain this error page again. It would be much
		 * better, if there was extra error() function similar to show() and calling
		 * $view() after setting the action to 'error'. This would also allow to
		 * set separate error pages for each view.
		 */
		if(!$noexit && isset($_REQUEST['action'])) {
			if(in_array($_REQUEST['action'], array('js', 'footerjs'))) {
				exit;
			}

			if($_REQUEST['action'] == 'webrootjs') {
				$this->webrootjs();
				exit;
			}
		}

		if(!$plain) {	
			$this->htmlStartPage($pagetitle);
			$this->globalNavigation();
			$this->contentStart();
		}

		print "<div class=\"alert alert-error\">";
		print "<h4>".getMLText('error')."!</h4>";
		print htmlspecialchars($error);
		print "</div>";
		print "<div><button class=\"btn history-back\">".getMLText('back')."</button></div>";
		
		$this->contentEnd();
		$this->htmlEndPage();
		
		add_log_line(" UI::exitError error=".$error." pagetitle=".$pagetitle, PEAR_LOG_ERR);

		if($noexit)
			return;

		exit;	
	} /* }}} */

	function printNewTreeNavigation($folderid=0, $accessmode=M_READ, $showdocs=0, $formid='form1', $expandtree=0, $orderby='') { /* {{{ */
		$this->printNewTreeNavigationHtml($folderid, $accessmode, $showdocs, $formid, $expandtree, $orderby);
?>
		<script language="JavaScript">
<?php
		$this->printNewTreeNavigationJs($folderid, $accessmode, $showdocs, $formid, $expandtree, $orderby);
?>
	</script>
<?php
	} /* }}} */

	function printNewTreeNavigationHtml($folderid=0, $accessmode=M_READ, $showdocs=0, $formid='form1', $expandtree=0, $orderby='') { /* {{{ */    
                echo "<div id=\"jqtree".$formid."\" style=\"margin-left: 10px;\" data-url=\"../op/op.Ajax.php?command=subtree2&showdocs=".$showdocs."&orderby=".$orderby."\"></div>\n";    
        } /* }}} */
        
        function printNewTreeNavigationHtmlWithColor($folderid=0, $accessmode=M_READ, $showdocs=0, $formid='form1', $expandtree=0, $orderby='', $selUser=-1, $selGroup=-1) { /* {{{ */    
            $userDomain=$this->getUserDomain($selUser, $selGroup);
            echo "<div id=\"jqtree".$formid."\" style=\"margin-left: 10px;\" data-url=\"../op/op.Ajax.php?command=subtree1&showdocs=".$showdocs."&orderby=".$orderby.$userDomain."\"></div>\n";  
        } /* }}} */

	/**
	 * Create a tree of folders using jqtree.
	 *
	 * The tree can contain folders only or include documents.
	 *
	 * @param integer $folderid current folderid. If set the tree will be
	 *   folded out and the all folders in the path will be visible
	 * @param integer $accessmode use this access mode when retrieving folders
	 *   and documents shown in the tree
	 * @param boolean $showdocs set to true if tree shall contain documents
	 *   as well.
	 */
	function printNewTreeNavigationJs($folderid=0, $accessmode=M_READ, $showdocs=0, $formid='form1', $expandtree=0, $orderby='') { /* {{{ */
		function jqtree($path, $folder, $user, $accessmode, $showdocs=1, $expandtree=0, $orderby='', $target=1) {
                    if($path || $expandtree) {
                        if($path)
                            $pathfolder = array_shift($path);
                        $subfolders = $folder->getSubFolders($orderby);
                        $subfolders = SeedDMS_Core_DMS::filterAccess($subfolders, $user, $accessmode, $target);
                        $children = array();
                        foreach($subfolders as $subfolder) {
                            $node = array('label'=>$subfolder->getName(), 'id'=>$subfolder->getID(), 'load_on_demand'=>($subfolder->hasSubFolders() || ($subfolder->hasDocuments() && $showdocs)) ? true : false, 'is_folder'=>true);
                            if($expandtree || $pathfolder->getID() == $subfolder->getID()) {
                                if($showdocs) {
                                    $documents = $folder->getDocuments($orderby);
                                    $documents = SeedDMS_Core_DMS::filterAccess($documents, $user, $accessmode, $target);
                                    foreach($documents as $document) {
                                        $node2 = array('label'=>$document->getName(), 'id'=>$document->getID(), 'load_on_demand'=>false, 'is_folder'=>false);
                                        $children[] = $node2;
                                    }
                                }
                                $node['children'] = jqtree($path, $subfolder, $user, $accessmode, $showdocs, $expandtree, $orderby, $target);
                            }
                            $children[] = $node;
                        }
                        return $children;
                    } else {
                        $subfolders = $folder->getSubFolders($orderby);
                        $subfolders = SeedDMS_Core_DMS::filterAccess($subfolders, $user, $accessmode, $target);
                        $children = array();
                        foreach($subfolders as $subfolder) {
                            $node = array('label'=>$subfolder->getName(), 'id'=>$subfolder->getID(), 'load_on_demand'=>($subfolder->hasSubFolders() || ($subfolder->hasDocuments() && $showdocs)) ? true : false, 'is_folder'=>true);
                            $children[] = $node;
                        }
                        return $children;
                    }
                    return array();
		}

		if($folderid) {
                    $folder = $this->params['dms']->getFolder($folderid);
                    $path = $folder->getPath();
                    $folder = array_shift($path);
                    $node = array('label'=>$folder->getName(), 'id'=>$folder->getID(), 'load_on_demand'=>true, 'is_folder'=>true);
                    if(!$folder->hasSubFolders()) {
                            $node['load_on_demand'] = false;
                            $node['children'] = array();
                    } else {
                        $node['children'] = jqtree($path, $folder, $this->params['user'], $accessmode, $showdocs, $expandtree, $orderby, $target);
                        if($showdocs) {
                                $documents = $folder->getDocuments($orderby);
                                $documents = SeedDMS_Core_DMS::filterAccess($documents, $user, $accessmode, $target);
                                foreach($documents as $document) {
                                        $node2 = array('label'=>$document->getName(), 'id'=>$document->getID(), 'load_on_demand'=>false, 'is_folder'=>false);
                                        $node['children'][] = $node2;
                                }
                        }
                    }
                    /* Nasty hack to remove the highest folder */
                    if(isset($this->params['remove_root_from_tree']) && $this->params['remove_root_from_tree']) {
                            foreach($node['children'] as $n)
                                    $tree[] = $n;
                    } else {
                            $tree[] = $node; //general go through this line
                    }
			
		} else {
			$root = $this->params['dms']->getFolder($this->params['rootfolderid']);
			$tree = array(array('label'=>$root->getName(), 'id'=>$root->getID(), 'load_on_demand'=>true, 'is_folder'=>true));
		}

?>
var data = <?php echo json_encode($tree); ?>;
$(function() {
    $('#jqtree<?php echo $formid ?>').tree({
        saveState: true,
        data: data,
        saveState: 'jqtree<?php echo $formid; ?>',
        openedIcon: '<i class="icon-minus-sign"></i>',
        closedIcon: '<i class="icon-plus-sign"></i>',
        _onCanSelectNode: function(node) {
            if(node.is_folder) {
                folderSelected<?php echo $formid ?>(node.id, node.name);
            } else
                documentSelected<?php echo $formid ?>(node.id, node.name);
        },
        autoOpen: true,
        drapAndDrop: true,
        onCreateLi: function(node, $li) {
        // Add 'icon' span before title
            if(node.is_folder)
                $li.find('.jqtree-title').before('<i class="icon-folder-close-alt table-row-folder" rel="folder_' + node.id + '"></i> ').attr('rel', 'folder_' + node.id).attr('formtoken', '<?php echo createFormKey('movefolder'); ?>');
            else
                $li.find('.jqtree-title').before('<i class="icon-file"></i> ');
        }
	});
	// Unfold tree if folder is opened
	$('#jqtree<?php echo $formid ?>').tree('openNode', $('#jqtree<?php echo $formid ?>').tree('getNodeById', <?php echo $folderid ?>), false);
        $('#jqtree<?php echo $formid ?>').bind(
            'tree.click',
            function(event) {
                var node = event.node;
                $('#jqtree<?php echo $formid ?>').tree('openNode', node);
//			event.preventDefault();
                if(node.is_folder) {
                    folderSelected<?php echo $formid ?>(node.id, node.name);
                } else
                    documentSelected<?php echo $formid ?>(node.id, node.name);
            }
	);
});
 
<?php
	} /* }}} */

        function printNewTreeNavigationJsWithColor($folderid=0, $accessmode=M_READ, $showdocs=0, $formid='form1', $expandtree=0, $orderby='', $selUser=-1, $selGroup=-1) { /* {{{ */
		function jqtree2($path, $folder, $user, $accessmode, $showdocs=1, $expandtree=0, $orderby='', $target=1) {
            if($path || $expandtree) {
                if($path)
                    $pathfolder = array_shift($path);
                $subfolders = $folder->getSubFolders($orderby);
                $subfolders = SeedDMS_Core_DMS::filterAccessWithColor($subfolders, $user, $accessmode, $target);
                $children = array();
                foreach($subfolders as $subfolder) {
                    $node = array('label'=>$subfolder[0]->getName(), 'id'=>$subfolder[0]->getID(), 'load_on_demand'=>($subfolder[0]->hasSubFolders() || ($subfolder[0]->hasDocuments() && $showdocs)) ? true : false, 'is_folder'=>true);
                    if($subfolder[1])
                    	$node['color']="green";
                    else
                    	$node['color']="red";
                    if($expandtree || $pathfolder->getID() == $subfolder[0]->getID()) {
                        if($showdocs) {
                            $documents = $folder->getDocuments($orderby);
                            $documents = SeedDMS_Core_DMS::filterAccessWithColor($documents, $user, $accessmode, $target);
                            foreach($documents as $document) {
                                $node2 = array('label'=>$document[0]->getName(), 'id'=>$document[0]->getID(), 'load_on_demand'=>false, 'is_folder'=>false);
                                if($document[1])
                                    $node2['color']="green";
                                else
                                    $node2['color']="red";
                                $children[] = $node2;
                            }
                        }
                        $node['children'] = jqtree2($path, $subfolder[0], $user, $accessmode, $showdocs, $expandtree, $orderby, $target);
                    }
                    $children[] = $node;
                }
                return $children;
            } else {
                $subfolders = $folder->getSubFolders($orderby);
                $subfolders = SeedDMS_Core_DMS::filterAccessWithColor($subfolders, $user, $accessmode, $target);
                $children = array();
                foreach($subfolders as $subfolder) {
                    $node = array('label'=>$subfolder[0]->getName(), 'id'=>$subfolder[0]->getID(), 'load_on_demand'=>($subfolder[0]->hasSubFolders() || ($subfolder[0]->hasDocuments() && $showdocs)) ? true : false, 'is_folder'=>true);
                    if($subfolder[1])
                    	$node['color']="green";
                    else
                    	$node['color']="red";
                    $children[] = $node;
                }
                return $children;
            }
            return array();
		}

	if($folderid) {
            $folder = $this->params['dms']->getFolder($folderid);
            $path = $folder->getPath();
            $folder = array_shift($path);
            $node = array('label'=>$folder->getName(), 'id'=>$folder->getID(), 'load_on_demand'=>true, 'is_folder'=>true, 'color'=>"green");
            if(!$folder->hasSubFolders()) {
                    $node['load_on_demand'] = false;
                    $node['children'] = array();
            } else {
                if($selUser=-1 && $selGroup=-1){
                    $user=$this->params['user'];
                    $target=1;
                }
                else if($selUser && $selUser!=-1){
                    $user=$this->params['dms']->getUser($selUser);
                    $target=1;
                }
                else if($selGroup && $selGroup!=-1){
                    $user=$this->params['dms']->getGroup($selGroup);
                    $target=2;
                }
                $node['children'] = jqtree2($path, $folder, $user, $accessmode, $showdocs, $expandtree, $orderby, $target);
                if($showdocs) {
                        $documents = $folder->getDocuments($orderby);
                        $documents = SeedDMS_Core_DMS::filterAccessWithColor($documents, $user, $accessmode, $target);
                        foreach($documents as $document) {
                            $node2 = array('label'=>$document[0]->getName(), 'id'=>$document[0]->getID(), 'load_on_demand'=>false, 'is_folder'=>false);
                            if($document[1])
		                    	$node2['color']="green";
		                    else
		                    	$node2['color']="red";
                            $node['children'][] = $node2;
                        }
                }
            }
            /* Nasty hack to remove the highest folder */
            if(isset($this->params['remove_root_from_tree']) && $this->params['remove_root_from_tree']) {
                    foreach($node['children'] as $n)
                            $tree[] = $n;
            } else {
                    $tree[] = $node; //general go through this line
            }
			
		} else {
			$root = $this->params['dms']->getFolder($this->params['rootfolderid']);
			$tree = array(array('label'=>$root->getName(), 'id'=>$root->getID(), 'load_on_demand'=>true, 'is_folder'=>true, 'color'=>"green"));
		}

?>
var data = <?php echo json_encode($tree); ?>;
$(function() {
    $('#jqtree<?php echo $formid ?>').tree({
        saveState: true,
        data: data,
        saveState: 'jqtree<?php echo $formid; ?>',
        openedIcon: '<i class="icon-minus-sign"></i>',
        closedIcon: '<i class="icon-plus-sign"></i>',
        _onCanSelectNode: function(node) {
            if(node.is_folder) {
                folderSelected<?php echo $formid ?>(node.id, node.name);
            } else
                documentSelected<?php echo $formid ?>(node.id, node.name);
        },
        autoOpen: true,
        drapAndDrop: true,
        onCreateLi: function(node, $li) {
        // Add 'icon' span before title
            if(node.is_folder)
                $li.find('.jqtree-title').before('<i class="icon-folder-close-alt table-row-folder" rel="folder_' + node.id + '"></i> ').attr('rel', 'folder_' + node.id).attr('formtoken', '<?php echo createFormKey('movefolder'); ?>');
            else
                $li.find('.jqtree-title').before('<i class="icon-file"></i> ');
        //Add color on title
            if (node.color) {
                var $title = $li.find('.jqtree-title');
                $title.addClass(node.color);
                //class="icon-folder-close-alt table-row-folder"
                var $folderIcon=$li.find('.icon-folder-close-alt');
                $folderIcon.addClass(node.color);
            }
        }
	});
	// Unfold tree if folder is opened
	$('#jqtree<?php echo $formid ?>').tree('openNode', $('#jqtree<?php echo $formid ?>').tree('getNodeById', <?php echo $folderid ?>), false);
        $('#jqtree<?php echo $formid ?>').bind(
            'tree.click',
            function(event) {
                var node = event.node;
                $('#jqtree<?php echo $formid ?>').tree('openNode', node);
//			event.preventDefault();
                if(node.is_folder) {
                    folderSelected<?php echo $formid ?>(node.id, node.name);
                } else
                    documentSelected<?php echo $formid ?>(node.id, node.name);
            }
	);
});

<?php
	}
        
	function printTreeNavigation($folderid, $showtree){ /* {{{ */
		if ($showtree==1){
			$this->contentHeading("<a href=\"../out/out.ViewFolder.php?folderid=". $folderid."&showtree=0\"><i class=\"icon-minus-sign\"></i></a>", true);
			$this->contentContainerStart();
?>
	<script language="JavaScript">
	function folderSelected(id, name) {
		window.location = '../out/out.ViewFolder.php?folderid=' + id;
	}
	</script>
<?php
			$this->printNewTreeNavigation($folderid, M_READ, 0, '');
			$this->contentContainerEnd();
		} else {
			$this->contentHeading("<a href=\"../out/out.ViewFolder.php?folderid=". $folderid."&showtree=1\"><i class=\"icon-plus-sign\"></i></a>", true);
		}
	} /* }}} */

	/**
	 * Print clipboard in div container
	 *
	 * @param array clipboard
	 */
	function printClipboard($clipboard, $previewer){ /* {{{ */
		$this->contentHeading(getMLText("clipboard"), true);
		echo "<div id=\"main-clipboard\">\n";
?>
		<div class="ajax" data-view="Clipboard" data-action="mainClipboard"></div>
<?php
		echo "</div>\n";
	} /* }}} */

	/**
	 * Print button with link for deleting a document
	 *
	 * This button is used in document listings (e.g. on the ViewFolder page)
	 * for deleting a document. In seeddms version < 4.3.9 this was just a
	 * link to the out/out.RemoveDocument.php page which asks for confirmation
	 * an than calls op/op.RemoveDocument.php. Starting with version 4.3.9
	 * the button just opens a small popup asking for confirmation and than
	 * calls the ajax command 'deletedocument'. The ajax call is called
	 * in the click function of 'button.removedocument'. That button needs
	 * to have two attributes: 'rel' for the id of the document, and 'msg'
	 * for the message shown by notify if the document could be deleted.
	 *
	 * @param object $document document to be deleted
	 * @param string $msg message shown in case of successful deletion
	 * @param boolean $return return html instead of printing it
	 * @return string html content if $return is true, otherwise an empty string
	 */
	function printDeleteDocumentButton($document, $msg, $return=false){ /* {{{ */
		$docid = $document->getID();
		$content = '';
    $content .= '<a class="delete-document-btn" rel="'.$docid.'" msg="'.getMLText($msg).'" confirmmsg="'.htmlspecialchars(getMLText("confirm_rm_document", array ("documentname" => $document->getName())), ENT_QUOTES).'"><i class="icon-remove"></i></a>';
		if($return)
			return $content;
		else
			echo $content;
		return '';
	} /* }}} */

	function printDeleteDocumentButtonJs(){ /* {{{ */
		echo "
		$(document).ready(function () {
//			$('.delete-document-btn').click(function(ev) {
			$('body').on('click', 'a.delete-document-btn', function(ev){
				id = $(ev.currentTarget).attr('rel');
				confirmmsg = $(ev.currentTarget).attr('confirmmsg');
				msg = $(ev.currentTarget).attr('msg');
				formtoken = '".createFormKey('removedocument')."';
				bootbox.dialog(confirmmsg, [{
					\"label\" : \"<i class='icon-remove'></i> ".getMLText("rm_document")."\",
					\"class\" : \"btn-danger\",
					\"callback\": function() {
						$.get('../op/op.Ajax.php',
							{ command: 'deletedocument', id: id, formtoken: formtoken },
							function(data) {
								if(data.success) {
									$('#table-row-document-'+id).hide('slow');
									noty({
										text: msg,
										type: 'success',
										dismissQueue: true,
										layout: 'topRight',
										theme: 'defaultTheme',
										timeout: 1500,
									});
								} else {
									noty({
										text: data.message,
										type: 'error',
										dismissQueue: true,
										layout: 'topRight',
										theme: 'defaultTheme',
										timeout: 3500,
									});
								}
							},
							'json'
						);
					}
				}, {
					\"label\" : \"".getMLText("cancel")."\",
					\"class\" : \"btn-cancel\",
					\"callback\": function() {
					}
				}]);
			});
		});
		";
	} /* }}} */

	/**
	 * Print button with link for deleting a folder
	 *
	 * This button works like document delete button
	 * {@link SeedDMS_Bootstrap_Style::printDeleteDocumentButton()}
	 *
	 * @param object $folder folder to be deleted
	 * @param string $msg message shown in case of successful deletion
	 * @param boolean $return return html instead of printing it
	 * @return string html content if $return is true, otherwise an empty string
	 */
	function printDeleteFolderButton($folder, $msg, $return=false){ /* {{{ */
		$folderid = $folder->getID();
		$content = '';
		$content .= '<a class="delete-folder-btn" rel="'.$folderid.'" msg="'.getMLText($msg).'" confirmmsg="'.htmlspecialchars(getMLText("confirm_rm_folder", array ("foldername" => $folder->getName())), ENT_QUOTES).'"><i class="icon-remove"></i></a>';
		if($return)
			return $content;
		else
			echo $content;
		return '';
	} /* }}} */

	function printDeleteFolderButtonJs(){ /* {{{ */
		echo "
		$(document).ready(function () {
//			$('.delete-folder-btn').click(function(ev) {
			$('body').on('click', 'a.delete-folder-btn', function(ev){
				id = $(ev.currentTarget).attr('rel');
				confirmmsg = $(ev.currentTarget).attr('confirmmsg');
				msg = $(ev.currentTarget).attr('msg');
				formtoken = '".createFormKey('removefolder')."';
				bootbox.dialog(confirmmsg, [{
					\"label\" : \"<i class='icon-remove'></i> ".getMLText("rm_folder")."\",
					\"class\" : \"btn-danger\",
					\"callback\": function() {
						$.get('../op/op.Ajax.php',
							{ command: 'deletefolder', id: id, formtoken: formtoken },
							function(data) {
								if(data.success) {
									$('#table-row-folder-'+id).hide('slow');
									noty({
										text: msg,
										type: 'success',
										dismissQueue: true,
										layout: 'topRight',
										theme: 'defaultTheme',
										timeout: 1500,
									});
								} else {
									noty({
										text: data.message,
										type: 'error',
										dismissQueue: true,
										layout: 'topRight',
										theme: 'defaultTheme',
										timeout: 3500,
									});
								}
							},
							'json'
						);
					}
				}, {
					\"label\" : \"".getMLText("cancel")."\",
					\"class\" : \"btn-cancel\",
					\"callback\": function() {
					}
				}]);
			});
		});
		";
	} /* }}} */

	function printLockButton($document, $msglock, $msgunlock, $return=false) { /* {{{ */
		$docid = $document->getID();
		if($document->isLocked()) {
			$icon = 'unlock';
			$msg = $msgunlock;
			$title = 'unlock_document';
		} else {
			$icon = 'lock';
			$msg = $msglock;
			$title = 'lock_document';
		}
		$content = '';
    $content .= '<a class="lock-document-btn" rel="'.$docid.'" msg="'.getMLText($msg).'" title="'.getMLText($title).'"><i class="icon-'.$icon.'"></i></a>';
		if($return)
			return $content;
		else
			echo $content;
		return '';
	} /* }}} */

	/**
	 * Output left-arrow with link which takes over a number of ids into
	 * a select box.
	 *
	 * Clicking in the button will preset the comma seperated list of ids
	 * in data-ref as options in the select box with name $name
	 *
	 * @param string $name id of select box
	 * @param array $ids list of option values
	 */
	function getSelectPresetButtonHtml($name, $ids) { /* {{{ */
		return '<span id="'.$name.'_btn" class="selectpreset_btn" style="cursor: pointer;" title="'.getMLText("takeOver".$name).'" data-ref="'.$name.'" data-ids="'.implode(",", $ids).'"><i class="icon-arrow-left"></i></span>';
	} /* }}} */

	/**
	 * Output left-arrow with link which takes over a number of ids into
	 * a select box.
	 *
	 * Clicking in the button will preset the comma seperated list of ids
	 * in data-ref as options in the select box with name $name
	 *
	 * @param string $name id of select box
	 * @param array $ids list of option values
	 */
	function printSelectPresetButtonHtml($name, $ids) { /* {{{ */
		echo self::getSelectPresetButtonHtml($name, $ids);
	} /* }}} */

	/**
	 * Javascript code for select preset button
	 */
	function printSelectPresetButtonJs() { /* {{{ */
?>
$(document).ready( function() {
	$('.selectpreset_btn').click(function(ev){
		ev.preventDefault();
		if (typeof $(ev.currentTarget).data('ids') != 'undefined') {
			target = $(ev.currentTarget).data('ref');
			// Use attr() instead of data() because data() converts to int which cannot be split
			items = $(ev.currentTarget).attr('data-ids');
			arr = items.split(",");
			for(var i in arr) {
				$("#"+target+" option[value='"+arr[i]+"']").attr("selected", "selected");
			}
//			$("#"+target).trigger("chosen:updated");
			$("#"+target).trigger("change");
		}
	});
});
<?php
	} /* }}} */

	/**
	 * Get HTML for left-arrow with link which takes over a string into
	 * a input field.
	 *
	 * Clicking on the button will preset the string
	 * in data-ref the value of the input field with name $name
	 *
	 * @param string $name id of select box
	 * @param string $text text
	 */
	function getInputPresetButtonHtml($name, $text, $sep='') { /* {{{ */
		return '<span id="'.$name.'_btn" class="inputpreset_btn" style="cursor: pointer;" title="'.getMLText("takeOverAttributeValue").'" data-ref="'.$name.'" data-text="'.(is_array($text) ? implode($sep, $text) : htmlspecialchars($text)).'"'.($sep ? " data-sep=\"".$sep."\"" : "").'><i class="icon-arrow-left"></i></span>';
	} /* }}} */

	/**
	 * Output left-arrow with link which takes over a string into
	 * a input field.
	 *
	 * Clicking on the button will preset the string
	 * in data-ref the value of the input field with name $name
	 *
	 * @param string $name id of select box
	 * @param string $text text
	 */
	function printInputPresetButtonHtml($name, $text, $sep='') { /* {{{ */
		echo self::getInputPresetButtonHtml($name, $text, $sep);
	} /* }}} */

	/**
	 * Javascript code for input preset button
	 * This code workѕ for input fields and single select fields
	 */
	function printInputPresetButtonJs() { /* {{{ */
?>
$(document).ready( function() {
	$('.inputpreset_btn').click(function(ev){
		ev.preventDefault();
		if (typeof $(ev.currentTarget).data('text') != 'undefined') {
			target = $(ev.currentTarget).data('ref');
			value = $(ev.currentTarget).data('text');
			sep = $(ev.currentTarget).data('sep');
			if(sep) {
				// Use attr() instead of data() because data() converts to int which cannot be split
				arr = value.split(sep);
				for(var i in arr) {
					$("#"+target+" option[value='"+arr[i]+"']").attr("selected", "selected");
				}
			} else {
				$("#"+target).val(value);
			}
		}
	});
});
<?php
	} /* }}} */

	/**
	 * Get HTML for left-arrow with link which takes over a boolean value
	 * into a checkbox field.
	 *
	 * Clicking on the button will preset the checkbox
	 * in data-ref the value of the input field with name $name
	 *
	 * @param string $name id of select box
	 * @param string $text text
	 */
	function getCheckboxPresetButtonHtml($name, $text) { /* {{{ */
?>
		return '<span id="'.$name.'_btn" class="checkboxpreset_btn" style="cursor: pointer;" title="'.getMLText("takeOverAttributeValue").'" data-ref="'.$name.'" data-text="'.(is_array($text) ? implode($sep, $text) : htmlspecialchars($text)).'"'.($sep ? " data-sep=\"".$sep."\"" : "").'><i class="icon-arrow-left"></i></span>';
<?php
	} /* }}} */

	/**
	 * Output left-arrow with link which takes over a boolean value
	 * into a checkbox field.
	 *
	 * Clicking on the button will preset the checkbox
	 * in data-ref the value of the input field with name $name
	 *
	 * @param string $name id of select box
	 * @param string $text text
	 */
	function printCheckboxPresetButtonHtml($name, $text) { /* {{{ */
		self::getCheckboxPresetButtonHtml($name, $text);
	} /* }}} */

	/**
	 * Javascript code for checkboxt preset button
	 * This code workѕ for checkboxes
	 */
	function printCheckboxPresetButtonJs() { /* {{{ */
?>
$(document).ready( function() {
	$('.checkboxpreset_btn').click(function(ev){
		ev.preventDefault();
		if (typeof $(ev.currentTarget).data('text') != 'undefined') {
			target = $(ev.currentTarget).data('ref');
			value = $(ev.currentTarget).data('text');
			if(value) {
				$("#"+target).attr('checked', '');
			} else {
				$("#"+target).removeAttribute('checked');
			}
		}
	});
});
<?php
	} /* }}} */

	/**
	 * Print button with link for deleting an attribute value
	 *
	 * This button is used in document listings (e.g. on the ViewFolder page)
	 * for deleting a document. In seeddms version < 4.3.9 this was just a
	 * link to the out/out.RemoveDocument.php page which asks for confirmation
	 * an than calls op/op.RemoveDocument.php. Starting with version 4.3.9
	 * the button just opens a small popup asking for confirmation and than
	 * calls the ajax command 'deletedocument'. The ajax call is called
	 * in the click function of 'button.removedocument'. That button needs
	 * to have two attributes: 'rel' for the id of the document, and 'msg'
	 * for the message shown by notify if the document could be deleted.
	 *
	 * @param object $document document to be deleted
	 * @param string $msg message shown in case of successful deletion
	 * @param boolean $return return html instead of printing it
	 * @return string html content if $return is true, otherwise an empty string
	 */
	function printDeleteAttributeValueButton($attrdef, $value, $msg, $return=false){ /* {{{ */
		$content = '';
    $content .= '<a class="delete-attribute-value-btn" rel="'.$attrdef->getID().'" msg="'.getMLText($msg).'" attrvalue="'.htmlspecialchars($value, ENT_QUOTES).'" confirmmsg="'.htmlspecialchars(getMLText("confirm_rm_attr_value", array ("attrdefname" => $attrdef->getName())), ENT_QUOTES).'"><i class="icon-remove"></i></a>';
		if($return)
			return $content;
		else
			echo $content;
		return '';
	} /* }}} */

	function printDeleteAttributeValueButtonJs(){ /* {{{ */
		echo "
		$(document).ready(function () {
//			$('.delete-attribute-value-btn').click(function(ev) {
			$('body').on('click', 'a.delete-attribute-value-btn', function(ev){
				id = $(ev.currentTarget).attr('rel');
				confirmmsg = $(ev.currentTarget).attr('confirmmsg');
				attrvalue = $(ev.currentTarget).attr('attrvalue');
				msg = $(ev.currentTarget).attr('msg');
				formtoken = '".createFormKey('removeattrvalue')."';
				bootbox.dialog(confirmmsg, [{
					\"label\" : \"<i class='icon-remove'></i> ".getMLText("rm_attr_value")."\",
					\"class\" : \"btn-danger\",
					\"callback\": function() {
						$.post('../op/op.AttributeMgr.php',
							{ action: 'removeattrvalue', attrdefid: id, attrvalue: attrvalue, formtoken: formtoken },
							function(data) {
								if(data.success) {
									$('#table-row-attrvalue-'+id).hide('slow');
									noty({
										text: msg,
										type: 'success',
										dismissQueue: true,
										layout: 'topRight',
										theme: 'defaultTheme',
										timeout: 1500,
									});
								} else {
									noty({
										text: data.message,
										type: 'error',
										dismissQueue: true,
										layout: 'topRight',
										theme: 'defaultTheme',
										timeout: 3500,
									});
								}
							},
							'json'
						);
					}
				}, {
					\"label\" : \"".getMLText("cancel")."\",
					\"class\" : \"btn-cancel\",
					\"callback\": function() {
					}
				}]);
			});
		});
		";
	} /* }}} */

	/**
	 * Return HTML of a single row in the document list table
	 *
	 * @param object $document
	 * @param object $previewer
	 * @param boolean $skipcont set to true if embrasing tr shall be skipped
	 */
	function documentListRow($document, $previewer, $skipcont=false, $version=0) { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$showtree = $this->params['showtree'];
		$workflowmode = $this->params['workflowmode'];
		$previewwidth = $this->params['previewWidthList'];
		$enableClipboard = $this->params['enableclipboard'];

		$content = '';

		$owner = $document->getOwner();
		$comment = $document->getComment();
		if (strlen($comment) > 150) $comment = substr($comment, 0, 147) . "...";
		$docID = $document->getID();

		if(!$skipcont)
			$content .= "<tr id=\"table-row-document-".$docID."\" class=\"table-row-document\" rel=\"document_".$docID."\" formtoken=\"".createFormKey('movedocument')."\" draggable=\"true\">";

		if($version)
			$latestContent = $document->getContentByVersion($version);
		else
			$latestContent = $document->getLatestContent();

		if($latestContent) {
			$previewer->createPreview($latestContent);
			$version = $latestContent->getVersion();
			$status = $latestContent->getStatus();
			$needwkflaction = false;
			if($workflowmode == 'advanced') {
				$workflow = $latestContent->getWorkflow();
				if($workflow) {
					$needwkflaction = $latestContent->needsWorkflowAction($user);
				}
			}
			
			/* Retrieve attacheѕ files */
			$files = $document->getDocumentFiles($latestContent->getVersion());
			$files = SeedDMS_Core_DMS::filterDocumentFiles($user, $files);

			/* Retrieve linked documents */
			$links = $document->getDocumentLinks();
			$links = SeedDMS_Core_DMS::filterDocumentLinks($user, $links);

			/* Retrieve reverse linked documents */
			$revlinks = $document->getReverseDocumentLinks();
			$revlinks = SeedDMS_Core_DMS::filterDocumentLinks($user, $revlinks);

			$content .= "<td>";
			/*if (file_exists($dms->contentDir . $latestContent->getPath())) {
				$content .= "<a draggable=\"false\" href=\"../op/op.Download.php?documentid=".$docID."&version=".$version."\">";
				if($previewer->hasPreview($latestContent)) {
					$content .= "<img draggable=\"false\" class=\"mimeicon\" width=\"".$previewwidth."\" src=\"../op/op.Preview.php?documentid=".$document->getID()."&version=".$latestContent->getVersion()."&width=".$previewwidth."\" title=\"".htmlspecialchars($latestContent->getMimeType())."\">";
				} else {
					$content .= "<img draggable=\"false\" class=\"mimeicon\" width=\"".$previewwidth."\" src=\"".$this->getMimeIcon($latestContent->getFileType())."\" ".($previewwidth ? "width=\"".$previewwidth."\"" : "")."\" title=\"".htmlspecialchars($latestContent->getMimeType())."\">";
				}
				$content .= "</a>";
			} else*/
				$content .= "<img draggable=\"false\" class=\"mimeicon\" width=\"".$previewwidth."\" src=\"".$this->getMimeIcon($latestContent->getFileType())."\" title=\"".htmlspecialchars($latestContent->getMimeType())."\">";
			$content .= "</td>";

			$content .= "<td>";	
			$content .= "<a draggable=\"false\" href=\"../out/out.ViewDocument.php?documentid=".$docID."&showtree=".$showtree."\">" . htmlspecialchars($document->getName()) . "</a>";
			$content .= "<br /><span style=\"font-size: 85%; font-style: italic; color: #666; \">".getMLText('owner').": <b>".htmlspecialchars($owner->getFullName())."</b>, ".getMLText('creation_date').": <b>".date('Y-m-d', $document->getDate())."</b>, ".getMLText('version')." <b>".$version."</b> - <b>".date('Y-m-d', $latestContent->getDate())."</b>".($document->expires() ? ", ".getMLText('expires').": <b>".getReadableDate($document->getExpires())."</b>" : "")."</span>";
			if($comment) {
				$content .= "<br /><span style=\"font-size: 85%;\">".htmlspecialchars($comment)."</span>";
			}
			$content .= "</td>\n";

			$content .= "<td nowrap>";
			$attentionstr = '';
			if ( $document->isLocked() ) {
				$attentionstr .= "<img src=\"".$this->getImgPath("lock.png")."\" title=\"". getMLText("locked_by").": ".htmlspecialchars($document->getLockingUser()->getFullName())."\"> ";
			}
			if ( $needwkflaction ) {
				$attentionstr .= "<img src=\"".$this->getImgPath("attention.gif")."\" title=\"". getMLText("workflow").": ".htmlspecialchars($workflow->getName())."\"> ";
			}
			if($attentionstr)
				$content .= $attentionstr."<br />";
			$content .= "<small>";
			if(count($files))
				$content .= count($files)." ".getMLText("linked_files")."<br />";
			if(count($links) || count($revlinks))
				$content .= count($links)."/".count($revlinks)." ".getMLText("linked_documents")."<br />";
			if($status["status"] == S_IN_WORKFLOW && $workflowmode == 'advanced') {
				$workflowstate = $latestContent->getWorkflowState();
				$content .= '<span title="'.getOverallStatusText($status["status"]).': '.$workflow->getName().'">'.$workflowstate->getName().'</span>';
			} else {
				$content .= getOverallStatusText($status["status"]);
			}
			$content .= "</small></td>";
//				$content .= "<td>".$version."</td>";
			$content .= "<td>";
			$content .= "<div class=\"list-action\">";
			if($document->getAccessMode($user) >= M_ALL) {
				$content .= $this->printDeleteDocumentButton($document, 'splash_rm_document', true);
			} else {
				$content .= '<span style="padding: 2px; color: #CCC;"><i class="icon-remove"></i></span>';
			}
			if($document->getAccessMode($user) >= M_READWRITE) {
				$content .= '<a href="../out/out.EditDocument.php?documentid='.$docID.'" title="'.getMLText("edit_document_props").'"><i class="icon-edit"></i></a>';
			} else {
				$content .= '<span style="padding: 2px; color: #CCC;"><i class="icon-edit"></i></span>';
			}
			if($document->getAccessMode($user) >= M_READWRITE) {
				$content .= $this->printLockButton($document, 'splash_document_locked', 'splash_document_unlocked', true);
			}
			if($enableClipboard) {
				$content .= '<a class="addtoclipboard" rel="D'.$docID.'" msg="'.getMLText('splash_added_to_clipboard').'" title="'.getMLText("add_to_clipboard").'"><i class="icon-copy"></i></a>';
			}
			$content .= "</div>";
			$content .= "</td>";
		}
		if(!$skipcont)
			$content .= "</tr>\n";
		return $content;
	} /* }}} */
        
        function documentListPic($document, $previewer, $skipcont=false, $version=0) { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$showtree = $this->params['showtree'];
		$workflowmode = $this->params['workflowmode'];
		$previewwidth = $this->params['previewWidthList'];
		$enableClipboard = $this->params['enableclipboard'];

		$content = '';
		$docID = $document->getID();

		if(!$skipcont)
			$content .= "<span id=\"table-row-document-".$docID."\" class=\"table-row-document pic-document\" rel=\"document_".$docID."\" formtoken=\"".createFormKey('movedocument')."\" draggable=\"true\" style='width:20%;height:auto;margin:10px;'>";

		if($version)
			$latestContent = $document->getContentByVersion($version);
		else
			$latestContent = $document->getLatestContent();

		if($latestContent) {
			$version = $latestContent->getVersion();
                        $content .= "<a draggable=\"false\" href=\"../out/out.ViewDocument.php?documentid=".$docID."&showtree=".$showtree."\">";
                        $content .= "<img draggable=\"false\" src='../elib/data/1048576/$docID/$version/1.jpg' style='width:95%;height:auto;margin:5px;'><br>";
			$content .= "<span>".htmlspecialchars($document->getName());
                        $content .="</span></a>";

		}
		if(!$skipcont)
			$content .= "</span>\n";
		return $content;
	} /* }}} */

function folderListRow($subFolder) { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
//		$folder = $this->params['folder'];
		$showtree = $this->params['showtree'];
		$enableRecursiveCount = $this->params['enableRecursiveCount'];
		$maxRecursiveCount = $this->params['maxRecursiveCount'];
		$enableClipboard = $this->params['enableclipboard'];

		$owner = $subFolder->getOwner();
		$comment = $subFolder->getComment();
		if (strlen($comment) > 150) $comment = substr($comment, 0, 147) . "...";

		$content = '';
		$content .= "<tr id=\"table-row-folder-".$subFolder->getID()."\" draggable=\"true\" rel=\"folder_".$subFolder->getID()."\" class=\"folder table-row-folder\" formtoken=\"".createFormKey('movefolder')."\">";
		$content .= "<td><a _rel=\"folder_".$subFolder->getID()."\" draggable=\"false\" href=\"../out/out.ViewFolder.php?folderid=".$subFolder->getID()."&showtree=".$showtree."\"><img draggable=\"false\" src=\"".$this->imgpath."folder.svg\" width=\"24\" height=\"24\" border=0></a></td>\n";
		$content .= "<td><a draggable=\"false\" _rel=\"folder_".$subFolder->getID()."\" href=\"../out/out.ViewFolder.php?folderid=".$subFolder->getID()."&showtree=".$showtree."\">" . htmlspecialchars($subFolder->getName()) . "</a>";
		$content .= "<br /><span style=\"font-size: 85%; font-style: italic; color: #666;\">".getMLText('owner').": <b>".htmlspecialchars($owner->getFullName())."</b>, ".getMLText('creation_date').": <b>".date('Y-m-d', $subFolder->getDate())."</b></span>";
		if($comment) {
			$content .= "<br /><span style=\"font-size: 85%;\">".htmlspecialchars($comment)."</span>";
		}
		$content .= "</td>\n";
//		$content .= "<td>".htmlspecialchars($owner->getFullName())."</td>";
		$content .= "<td colspan=\"1\" nowrap><small>";
		if($enableRecursiveCount) {
			if($user->isAdmin()) {
				/* No need to check for access rights in countChildren() for
				 * admin. So pass 0 as the limit.
				 */
				$cc = $subFolder->countChildren($user, 0);
				$content .= $cc['folder_count']." ".getMLText("folders")."<br />".$cc['document_count']." ".getMLText("documents");
			} else {
				$cc = $subFolder->countChildren($user, $maxRecursiveCount);
				if($maxRecursiveCount > 5000)
					$rr = 100.0;
				else
					$rr = 10.0;
				$content .= (!$cc['folder_precise'] ? '~'.(round($cc['folder_count']/$rr)*$rr) : $cc['folder_count'])." ".getMLText("folders")."<br />".(!$cc['document_precise'] ? '~'.(round($cc['document_count']/$rr)*$rr) : $cc['document_count'])." ".getMLText("documents");
			}
		} else {
			/* FIXME: the following is very inefficient for just getting the number of
			 * subfolders and documents. Making it more efficient is difficult, because
			 * the access rights need to be checked.
			 */
			$subsub = $subFolder->getSubFolders();
			$subsub = SeedDMS_Core_DMS::filterAccess($subsub, $user, M_READ);
			$subdoc = $subFolder->getDocuments();
			$subdoc = SeedDMS_Core_DMS::filterAccess($subdoc, $user, M_READ);
			$content .= count($subsub)." ".getMLText("folders")."<br />".count($subdoc)." ".getMLText("documents");
		}
		$content .= "</small></td>";
//		$content .= "<td></td>";
		$content .= "<td>";
		$content .= "<div class=\"list-action\">";
		if($subFolder->getAccessMode($user) >= M_ALL) {
			$content .= $this->printDeleteFolderButton($subFolder, 'splash_rm_folder', true);
		} else {
			$content .= '<span style="padding: 2px; color: #CCC;"><i class="icon-remove"></i></span>';
		}
		if($subFolder->getAccessMode($user) >= M_READWRITE) {
			$content .= '<a class_="btn btn-mini" href="../out/out.EditFolder.php?folderid='.$subFolder->getID().'" title="'.getMLText("edit_folder_props").'"><i class="icon-edit"></i></a>';
		} else {
			$content .= '<span style="padding: 2px; color: #CCC;"><i class="icon-edit"></i></span>';
		}
		if($enableClipboard) {
			$content .= '<a class="addtoclipboard" rel="F'.$subFolder->getID().'" msg="'.getMLText('splash_added_to_clipboard').'" title="'.getMLText("add_to_clipboard").'"><i class="icon-copy"></i></a>';
		}
		$content .= "</div>";
		$content .= "</td>";
		$content .= "</tr>\n";
		return $content;
	} /* }}} */
        
        
    function folderListPic($subFolder) { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
//		$folder = $this->params['folder'];
		$showtree = $this->params['showtree'];
		$enableRecursiveCount = $this->params['enableRecursiveCount'];
		$maxRecursiveCount = $this->params['maxRecursiveCount'];
		$enableClipboard = $this->params['enableclipboard'];

		$content = '';
		$content .= "<span id=\"table-row-folder-".$subFolder->getID()."\" draggable=\"true\" rel=\"folder_".$subFolder->getID()."\" class=\"folder table-row-folder\" formtoken=\"".createFormKey('movefolder')."\" style='width:10%; margin:25px;'>";
		$content .= "<a _rel=\"folder_".$subFolder->getID()."\" draggable=\"false\" href=\"../out/out.ViewFolder.php?folderid=".$subFolder->getID()."&showtree=".$showtree."\"><img draggable=\"false\" src=\"".$this->imgpath."folder.svg\" width=\"24\" height=\"24\" border=0 style='padding:0 20% 0 20%;'></a><br>";
		$content .= "<a draggable=\"false\" _rel=\"folder_".$subFolder->getID()."\" href=\"../out/out.ViewFolder.php?folderid=".$subFolder->getID()."&showtree=".$showtree."\">" . htmlspecialchars($subFolder->getName()) . "</a>";
		$content .= "</span>\n";
		return $content;
	} /* }}} */

	function show(){ /* {{{ */
		parent::show();
	} /* }}} */

	function error(){ /* {{{ */
		parent::error();
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$pagetitle = $this->params['pagetitle'];
		$errormsg = $this->params['errormsg'];
		$plain = $this->params['plain'];
		$noexit = $this->params['noexit'];

		if(!$plain) {	
			$this->htmlStartPage($pagetitle);
			$this->globalNavigation();
			$this->contentStart();
		}

		print "<div class=\"alert alert-error\">";
		print "<h4>".getMLText('error')."!</h4>";
		print htmlspecialchars($errormsg);
		print "</div>";
		print "<div><button class=\"btn history-back\">".getMLText('back')."</button></div>";
		
		$this->contentEnd();
		$this->htmlEndPage();
		
		add_log_line(" UI::exitError error=".$errormsg." pagetitle=".$pagetitle, PEAR_LOG_ERR);

		if($noexit)
			return;

		exit;	
	} /* }}} */

	/**
	 * Return HTML Template for jumploader
	 *
	 * @param string $uploadurl URL where post data is send
	 * @param integer $folderid id of folder where document is saved
	 * @param integer $maxfiles maximum number of files allowed to upload
	 * @param array $fields list of post fields
	 */
	function getFineUploaderTemplate() { /* {{{ */
		return '
<script type="text/template" id="qq-template">
<div class="qq-uploader-selector qq-uploader" qq-drop-area-text="'.getMLText('drop_files_here').'">
	<div class="qq-total-progress-bar-container-selector qq-total-progress-bar-container">
		<div role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" class="qq-total-progress-bar-selector qq-progress-bar qq-total-progress-bar"></div>
		</div>
	<div class="input-append">
	<div class="qq-upload-drop-area-selector qq-upload-drop-area" _qq-hide-dropzone>
		<span class="qq-upload-drop-area-text-selector"></span>
	</div>
	<span class="btn qq-upload-button-selector qq-upload-button">'.getMLText('browse').'&hellip;</span>
	</div>
	<span class="qq-drop-processing-selector qq-drop-processing">
		<span class="qq-drop-processing-spinner-selector qq-drop-processing-spinner"></span>
	</span>
	<ul class="qq-upload-list-selector qq-upload-list unstyled" aria-live="polite" aria-relevant="additions removals">
		<li>
			<div class="progress qq-progress-bar-container-selector">
				<div class="bar qq-progress-bar-selector qq-progress-bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
			</div>
			<span class="qq-upload-spinner-selector qq-upload-spinner"></span>
			<img class="qq-thumbnail-selector" qq-max-size="100" qq-server-scale>
			<span class="qq-upload-file-selector qq-upload-file"></span>
			<span class="qq-upload-size-selector qq-upload-size"></span>
			<button class="btn btn-mini qq-btn qq-upload-cancel-selector qq-upload-cancel">Cancel</button>
			<span role="status" class="qq-upload-status-text-selector qq-upload-status-text"></span>
		</li>
	</ul>
	<dialog class="qq-alert-dialog-selector">
		<div class="qq-dialog-message-selector"></div>
		<div class="qq-dialog-buttons">
			<button class="btn qq-cancel-button-selector">Cancel</button>
		</div>
	</dialog>

	<dialog class="qq-confirm-dialog-selector">
		<div class="qq-dialog-message-selector"></div>
		<div class="qq-dialog-buttons">
			<button class="btn qq-cancel-button-selector">Cancel</button>
			<button class="btn qq-ok-button-selector">Ok</button>
		</div>
	</dialog>

	<dialog class="qq-prompt-dialog-selector">
		<div class="qq-dialog-message-selector"></div>
		<input type="text">
		<div class="qq-dialog-buttons">
			<button class="btn qq-cancel-button-selector">Cancel</button>
			<button class="btn qq-ok-button-selector">Ok</button>
		</div>
	</dialog>
</div>
</script>
';
	} /* }}} */

	/**
	 * Output HTML Code for Fine Uploader
	 *
	 * @param string $uploadurl URL where post data is send
	 * @param integer $folderid id of folder where document is saved
	 * @param integer $maxfiles maximum number of files allowed to upload
	 * @param array $fields list of post fields
	 */
	function printFineUploaderHtml($prefix='userfile') { /* {{{ */
		echo self::getFineUploaderHtml($prefix);
	} /* }}} */

	/**
	 * Get HTML Code for Fine Uploader
	 *
	 * @param string $uploadurl URL where post data is send
	 * @param integer $folderid id of folder where document is saved
	 * @param integer $maxfiles maximum number of files allowed to upload
	 * @param array $fields list of post fields
	 */
	function getFineUploaderHtml($prefix='userfile') { /* {{{ */
		$html = '<div id="'.$prefix.'-fine-uploader"></div>
		<input type="hidden" '.($prefix=='userfile' ? 'class="do_validate" ' : '').'id="'.$prefix.'-fine-uploader-uuids" name="'.$prefix.'-fine-uploader-uuids" value="" />
		<input type="hidden" id="'.$prefix.'-fine-uploader-names" name="'.$prefix.'-fine-uploader-names" value="" />';
		return $html;
	} /* }}} */

	/**
	 * Output Javascript Code for fine uploader
	 *
	 * @param string $uploadurl URL where post data is send
	 * @param integer $folderid id of folder where document is saved
	 * @param integer $maxfiles maximum number of files allowed to upload
	 * @param array $fields list of post fields
	 */
	function printFineUploaderJs($uploadurl, $partsize=0, $maxuploadsize=0, $multiple=true, $prefix='userfile') { /* {{{ */
?>
$(document).ready(function() {
	<?php echo $prefix; ?>uploader = new qq.FineUploader({
		debug: false,
		autoUpload: false,
		multiple: <?php echo ($multiple ? 'true' : 'false'); ?>,
		element: $('#<?php echo $prefix; ?>-fine-uploader')[0],
		template: 'qq-template',
		request: {
			endpoint: '<?php echo $uploadurl; ?>'
		},
<?php echo ($maxuploadsize > 0 ? '
		validation: {
			sizeLimit: '.$maxuploadsize.'
		},
' : ''); ?>
		chunking: {
			enabled: true,
			<?php echo $partsize ? 'partSize: '.(int)$partsize.",\n" : ''; ?>
			mandatory: true
		},
		messages: {
			sizeError: '{file} is too large, maximum file size is {sizeLimit}.'
		},
		callbacks: {
			onComplete: function(id, name, json, xhr) {
			},
			onAllComplete: function(succeeded, failed) {
				var uuids = Array();
				var names = Array();
				for (var i = 0; i < succeeded.length; i++) {
					uuids.push(this.getUuid(succeeded[i]))
					names.push(this.getName(succeeded[i]))
				}
				$('#<?php echo $prefix; ?>-fine-uploader-uuids').val(uuids.join(';'));
				$('#<?php echo $prefix; ?>-fine-uploader-names').val(names.join(';'));
				/* Run upload only if all files could be uploaded */
				if(succeeded.length > 0 && failed.length == 0)
					document.getElementById('form1').submit();
			},
			onError: function(id, name, reason, xhr) {
				noty({
					text: reason,
					type: 'error',
					dismissQueue: true,
					layout: 'topRight',
					theme: 'defaultTheme',
					timeout: 3500,
				});
			}
		}
	});
});
<?php
	} /* }}} */

	/**
	 * Output a protocol
	 *
	 * @param object $attribute attribute
	 */
	protected function printProtocol($latestContent, $type="") { /* {{{ */
		$dms = $this->params['dms'];
		$document = $latestContent->getDocument();
?>
		<legend><?php printMLText($type.'_log'); ?></legend>
		<table class="table condensed">
			<tr><th><?php printMLText('name'); ?></th><th><?php printMLText('last_update'); ?>, <?php printMLText('comment'); ?></th><th><?php printMLText('status'); ?></th></tr>
<?php
		switch($type) {
		case "review":
			$statusList = $latestContent->getReviewStatus(10);
			break;
		case "approval":
			$statusList = $latestContent->getApprovalStatus(10);
			break;
		default:
			$statusList = array();
		}
		foreach($statusList as $rec) {
			echo "<tr>";
			echo "<td>";
			switch ($rec["type"]) {
				case 0: // individual.
					$required = $dms->getUser($rec["required"]);
					if (!is_object($required)) {
						$reqName = getMLText("unknown_user")." '".$rec["required"]."'";
					} else {
						$reqName = htmlspecialchars($required->getFullName()." (".$required->getLogin().")");
					}
					break;
				case 1: // Approver is a group.
					$required = $dms->getGroup($rec["required"]);
					if (!is_object($required)) {
						$reqName = getMLText("unknown_group")." '".$rec["required"]."'";
					}
					else {
						$reqName = "<i>".htmlspecialchars($required->getName())."</i>";
					}
					break;
			}
			echo $reqName;
			echo "</td>";
			echo "<td>";
			echo "<i style=\"font-size: 80%;\">".$rec['date']." - ";
			$updateuser = $dms->getUser($rec["userID"]);
			if(!is_object($required))
				echo getMLText("unknown_user");
			else
				echo htmlspecialchars($updateuser->getFullName()." (".$updateuser->getLogin().")");
			echo "</i>";
			if($rec['comment'])
				echo "<br />".htmlspecialchars($rec['comment']);
			switch($type) {
			case "review":
				if($rec['file']) {
					echo "<br />";
					echo "<a href=\"../op/op.Download.php?documentid=".$document->getID()."&reviewlogid=".$rec['reviewLogID']."\" class=\"btn btn-mini\"><i class=\"icon-download\"></i> ".getMLText('download')."</a>";
				}
				break;
			case "approval":
				if($rec['file']) {
					echo "<br />";
					echo "<a href=\"../op/op.Download.php?documentid=".$document->getID()."&approvelogid=".$rec['approveLogID']."\" class=\"btn btn-mini\"><i class=\"icon-download\"></i> ".getMLText('download')."</a>";
				}
				break;
			}
			echo "</td>";
			echo "<td>";
			switch($type) {
			case "review":
				echo getReviewStatusText($rec["status"]);
				break;
			case "approval":
				echo getApprovalStatusText($rec["status"]);
				break;
			default:
			}
			echo "</td>";
			echo "</tr>";
		}
?>
				</table>
<?php
	} /* }}} */

	/**
	 * Show progressbar
	 *
	 * @param double $value value
	 * @param double $max 100% value
	 */
	protected function getProgressBar($value, $max=100.0) { /* {{{ */
		if($max > $value) {
			$used = (int) ($value/$max*100.0+0.5);
			$free = 100-$used;
		} else {
			$free = 0;
			$used = 100;
		}
		$html = '
		<div class="progress">
			<div class="bar bar-danger" style="width: '.$used.'%;"></div>
		  <div class="bar bar-success" style="width: '.$free.'%;"></div>
		</div>';
		return $html;
	} /* }}} */

	/**
	 * Output a timeline for a document
	 *
	 * @param object $document document
	 */
	protected function printTimelineJs($timelineurl, $height=300, $start='', $end='', $skip=array()) { /* {{{ */
		if(!$timelineurl)
			return;
?>
		var timeline;
		var data;

		// specify options
		var options = {
			'width':  '100%',
			'height': '100%',
<?php
		if($start) {
			$tmp = explode('-', $start);
			echo "\t\t\t'min': new Date(".$tmp[0].", ".($tmp[1]-1).", ".$tmp[2]."),\n";
		}
		if($end) {
			$tmp = explode('-', $end);
			echo "'\t\t\tmax': new Date(".$tmp[0].", ".($tmp[1]-1).", ".$tmp[2]."),\n";
		}
?>
			'editable': false,
			'selectable': true,
			'style': 'box',
			'locale': '<?php echo $this->params['session']->getLanguage() ?>'
		};

		function onselect() {
			var sel = timeline.getSelection();
			if (sel.length) {
				if (sel[0].row != undefined) {
					var row = sel[0].row;
					console.log(timeline.getItem(sel[0].row));
					item = timeline.getItem(sel[0].row);
					$('div.ajax').trigger('update', {documentid: item.docid, version: item.version, statusid: item.statusid, statuslogid: item.statuslogid, fileid: item.fileid});
				}
			}
		}
		$(document).ready(function () {
		// Instantiate our timeline object.
		timeline = new links.Timeline(document.getElementById('timeline'), options);
		links.events.addListener(timeline, 'select', onselect);
		$.getJSON(
			'<?php echo $timelineurl ?>', 
			function(data) {
				$.each( data, function( key, val ) {
					val.start = new Date(val.start);
				});
				timeline.draw(data);
			}
		);
		});
<?php
	} /* }}} */

	protected function printTimelineHtml($height) { /* {{{ */
?>
	<div id="timeline" style="height: <?php echo $height ?>px;"></div>
<?php
	} /* }}} */

	protected function printTimeline($timelineurl, $height=300, $start='', $end='', $skip=array()) { /* {{{ */
		echo "<script type=\"text/javascript\">\n";
		$this->printTimelineJs($timelineurl, $height, $start, $end, $skip);
		echo "</script>";
		$this->printTimelineHtml($height);
	} /* }}} */

	protected function printPopupBox($title, $content, $ret = false)
	{ /* {{{ */
		$id = md5(uniqid());
		/*
		$this->addFooterJS('
		$("body").on("click", "span.openpopupbox", function(e) {
			$(""+$(e.target).data("href")).toggle();
		//	$("div.popupbox").toggle();
		});
		');
		*/
		$html = '
		<span style="padding: 8px; background-color: #f6f6f6; border-radius: 3px; user-select: none;" class="openpopupbox" data-href="#' . $id . '">' . $title .  ' ' . '<i class="icon-chevron-down"></i>' . '</span>
		<div id="' . $id . '" class="popupbox" style="display: none; background-color: #f6f6f6;">
		' . $content . '
			<span class="closepopupbox" style="cursor: pointer;"><i class="icon-remove"></i></span>
		</div>';
		if ($ret)
			return $html;
		else
			echo $html;
	} /* }}} */
        
        	// Glenn edited on 11 April, 2022
	protected function printFollowGroupPopupBox($groupNameArr)
	{
		$id = md5(uniqid());

		if (count($groupNameArr) > 0) {
			echo '<span style="cursor: pointer; user-select: none;" class="openpopupbox" data-href="#' . $id . '"><i class="icon-question"></i></span>';
			echo '<div id="' . $id . '" class="popupbox" style="text-align: center;">';
			echo '<h5>Follow Group(s) Record:</h5>';
			foreach ($groupNameArr as $key => $val) {
				echo '<p>';
				echo $val;
				echo '</p>';
			}
			echo '<span class="closepopupbox" style="cursor: pointer;"><i class="icon-remove"></i></span>';
			echo '</div>';
		} else {
			echo '
				<span style="cursor: pointer; user-select: none;" class="openpopupbox" data-href="#' . $id . '"><i class="icon-question"></i></span>
				<div id="' . $id . '" class="popupbox">
					Does not follow any groups :)
					<span class="closepopupbox" style="cursor: pointer;"><i class="icon-remove"></i></span>
				</div>
		';
		}
	}
        
	protected function printAccordion($title, $content, $ret=false, $margin=0) { /* {{{ */
		$id = substr(md5(uniqid()), 0, 4);
?>
		<div class="accordion" id="accordion<?php echo $id; ?>" style="margin:<?php echo $margin; ?>px">
      <div class="accordion-group" style="border-radius: 5px">
        <div class="accordion-heading">
					<a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion<?php echo $id; ?>" href="#collapse<?php echo $id; ?>">
						<?php echo $title; ?>
          </a>
        </div>
				<div id="collapse<?php echo $id; ?>" class="accordion-body collapse" style="height: 0px;">
          <div class="accordion-inner">
<?php
            if($ret)
                return $content;
            else
		echo $content;
?>
          </div>
        </div>
      </div>
    </div>
   

        
<?php
	} /* }}} */
        
        
        function printsearchbar(){
            print '<div id="searchcontainer" class="span12">';
                if($this->params['user']->isGuest()){
                    print'<span id="menutogglebtn" value="0" style="color:white; top: 10px; position: absolute; right: 35px;">Welcome '.htmlspecialchars($this->params['user']->getFullName()).' <i class="icon-caret-down" ></i></span><br>';
                }
                else{
                    print'<span id="menutogglebtn" value="0" style="color:white; top: 10px; position: absolute; right: 35px;">'.($this->params['session']->getSu() ? getMLText("switched_to") : getMLText("signed_in_as"))." '".htmlspecialchars($this->params['user']->getFullName()).'\' <i class="icon-caret-down" ></i></span><br>';
                }               
                print '<div style="float:right;margin-top: 15px;">';
                echo '<form action="../out/out.Search.php" style="display: flex;margin:0;float:right;width:90%;">';
                echo "<input type=\"hidden\" name=\"navBar\" value=\"1\" />";
                echo "<input name=\"query\" class=\"search-query\" data-provide=\"typeahead\" type=\"search\" style='width: 55%;' placeholder=\"".getMLText("search")."\"/>";
                echo "<input type=\"hidden\" name=\"fullsearch\" value=\"1\" />";
                echo ' <button type="submit" class="btn" ><i class="icon-search" ></i></button>'; 
                echo "</form>";
                print'<br><a href="../out/out.Search.php" style="display:inline-block; color:white;float: right;margin-right: 13%;">Advanced Search</a>';
                
                print '<br><span style="float:right;color:white;font-size:initial;line-height:initial;margin: 0px 13%;">';

                if(!$this->params['session']->getNightmode()){
                    print'<span class="icon-adjust" value="1" id="night-toggle">  </span>';
                }else{
                    print'<span class="icon-adjust" value="0" id="night-toggle">  </span>';
                }
                print'<span class="icon-font" id="font-size-small" style="font-size:15px;"></span><span class="icon-font" id="font-size-large" style="font-size:20px;"></span></span>';
               
                print '</div>';
                
                print '<div style="position: relative;display: block;width: 480px;margin: -20px auto 25px auto;">';
                print '<a href="../index.php" style="display: block; top: 10px;position: relative;"><img style="height: 92px; filter: invert(1);" src="../elib/image/title.png"></a>';
                print '</div>';
                
                
                            
                print '</div>';
   
        }
        function printmaincontentcontainer(){
                
                print '<div class="span12" id="maincontent-container">';
                /*print '<span style="float:right;font-size:initial;line-height:initial;padding:5px;">';

                if(!$this->params['session']->getNightmode()){
                    print'<span class="icon-adjust" value="1" id="night-toggle"></span>';
                }else{
                    print'<span class="icon-adjust" value="0" id="night-toggle"></span>';
                }
                print'<span class="icon-font" id="font-size-small" style="font-size:15px;"></span><span class="icon-font" id="font-size-large" style="font-size:20px;"></span></span>';
                */
                //print '<br>';
                print '<br>';
        }
        function printspan4Controlbtn(){
            print '<span id="span4control" class="icon-minus-sign" value="1"  style="font-size:15px;"></span>';
            print '<br>';
        }
        function printspan8Controlbtn(){
            print '<span id="span8control" class="icon-minus-sign" value="1"  style="font-size:15px;"></span>';
//            print '<br>';
        }
        
        function printsearchbarMainEnd(){
            echo "</div>\n";
        }
        function printelib_Js(){
            header('Content-Type: application/javascript; charset=UTF-8');
          
                print"$(document).ready( function() {

                    window.onscroll = function() {
                        if (window.pageYOffset> '80') {
                          $('#topnavbar').css('display', 'block');
                        } else if(window.pageYOffset<= '105' && window.innerWidth>979){
                            $('#topnavbar').css('display', 'none');
                            $('#searchcontainer').css('display', 'block');
                        }
                        if($('#menutogglebtn').attr('value')==1){
                            $('#menutogglebtn').click();
                        }

                    }
                    
                        
                    $(window).scroll();

                    if(window.innerWidth<=979){
                        $('.btn-navbar:first').click();
                        $('.btn-navbar:first').click();
                    }
                });";
        }
}


?>