<?php
require_once("class.Bootstrap.php");
require_once("SeedDMS/Preview.php");
class SeedDMS_View_CheckPrivilege extends SeedDMS_Bootstrap_Style {
    
    function js() { /* {{{ */
        $selGroup = $this->params['selGroup'];
        $selUser = $this->params['selUser'];
        $folder = $this->params['folder'];
        $orderby = $this->params['orderby'];
        $expandFolderTree = $this->params['expandFolderTree'];
        $maxItemsPerPage = $this->params['maxItemsPerPage'];
        header('Content-Type: application/javascript; charset=UTF-8');   
?>
$(document).ready(function() {
    $(".tablinks").live("click", function(evt){
        var i, tabcontent, tablinks, curr;
        tabcontent = document.getElementsByClassName("tabcontent");
        for (i = 0; i < tabcontent.length; i++) {
            tabcontent[i].style.display = "none";
        }
        tablinks = document.getElementsByClassName("tablinks");
        for (i = 0; i < tablinks.length; i++) {
           tablinks[i].className = tablinks[i].className.replace(" active", "");
        }
        curr= document.getElementById($(this).html());
        curr.style.display = "block";
        evt.currentTarget.className += " active";
    });
    $("#defaultOpen").ready(function() {
        $("#defaultOpen").click();
    });
    $(".well").addClass('clearfix');
    $(".userPerm1, .userPerm2").chosen({width: "150px"});
    $(".userPerm1").change(function(){
    $(".userPerm2").val("-1");
    $("#permissionForm").submit();
    });
    $(".userPerm2").change(function(){
    $(".userPerm1").val("-1");
    $("#permissionForm").submit();
    });
    $("[data-toggle='tooltip']").tooltip();
});

function checkForm()
{
    msg = new Array()
    if ((document.permissionForm.userid.options[document.permissionForm.userid.selectedIndex].value == -1) && 
            (document.permissionForm.folderid.options[document.permissionForm.folderid.selectedIndex].value == -1))
                    msg.push("<?php printMLText("js_select_user_or_folder");?>");
    if (msg != "") {
    noty({
            text: msg.join('<br />'),
            type: 'error',
  dismissQueue: true,
            layout: 'topRight',
            theme: 'defaultTheme',
                    _timeout: 1500,
    });
            return false;
    }
    else
            return true;
}
function folderSelected(id, name) {
    var selUser="<?php echo $selUser; ?>";
    var selGroup = "<?php echo $selGroup; ?>";
    var userDomain="&selUser="+selUser+"&selGroup="+selGroup;
    //if(selUser )
    //    userDomain="&selUser="+selUser;
    //else if(selGroup)
    //  userDomain="&selGroup="+selGroup;
    window.location = '../out/out.CheckPrivilege.php?folderid=' + id +userDomain;
}
<?php
        $this->printelib_Js(); //js handles page size change
        $this->printNewTreeNavigationJsWithColor($folder->getID(), M_READ, 0, '', $expandFolderTree == 2, $orderby, $selUser, $selGroup);
    } /* }}} */
    /*
     * Print privilege tab, $class of grant or notGrant affects the text color of the privilege.
     */
    function printTabs($priv, $class){
        $count=0;
        foreach($priv as $key1 => $value1){
            if($count===0){
                echo"<button class=\"tablinks ".$class."\" id=\"defaultOpen\">".$key1."</button>";
            }else{
                echo"<button class=\"tablinks ".$class."\">".$key1."</button>";
            }
            $count++;
        }
    }
    /*
     * Print tick or cross.
     */
    function icon($value, $default=0){
        $html="";
        if($value===0){
             $html.="<i class=\"icon-remove\"></i>   ";
        }else if($value===1){
            $html.="<i class=\"icon-ok\"></i>    ";
        }else{
            $html.=$value;
        }
        return $html;
   }
   
    function printTabContent($privAccess, $class, $userName){
        foreach($privAccess as $priv => $folderAccess){
            echo "<div id=".$priv." class=\"tabcontent\">";
            if($class==="grant"){
                echo "<div class=".$class."><b>Granted</b></div>";
            }else if($class==="notGrant"){
                echo "<div class=".$class."><b>Not Granted</b></div>";
            }
            foreach($folderAccess as $folder => $accessInfo){//[0]=>$highestSourceStr [1]=>sourceAccess
                $tableContent="<table class=\"comparisonTable\">\n<tr>\n".
                "<th style=\"width:40%\">Source of Privilege</th>\n".
                "<th>Group Privilege</th>\n<th>Folder Permission ".
                "<a href=\"#\" data-toggle=\"tooltip\" class=\"icon-info-sign\" data-placement=\"bottom\" title=\"".$accessInfo[0]."\"></a>".
                "</th>\n</tr>\n";
                foreach($accessInfo[1] as $sourceAccess){ //row
                    $tableContent.="<tr>\n";
                    foreach($sourceAccess as $entry){ //entry
                        $tableContent.="<td>".$this->icon($entry)."</td>\n";
                    }
                    $tableContent.="</tr>\n";
                }
                $tableContent.="</table>\n";
                $this->printAccordion($folder, $tableContent);
            }
            echo "</div>";
        }
    }
    function show() { /* {{{ */
            $dms = $this->params['dms'];
            $selGroup = $this->params['selGroup'];
            $selUser = $this->params['selUser'];
            $folder = $this->params['folder'];
            $orderby = $this->params['orderby'];
            $enableFolderTree = $this->params['enableFolderTree'];
            $enableClipboard = $this->params['enableclipboard'];
            $enableDropUpload = $this->params['enableDropUpload'];
            $expandFolderTree = $this->params['expandFolderTree'];
            $showtree = $this->params['showtree'];
            $cachedir = $this->params['cachedir'];
            $maxItemsPerPage = $this->params['maxItemsPerPage'];
            $previewwidth = $this->params['previewWidthList'];
            $previewconverters = $this->params['previewConverters'];
            $timeout = $this->params['timeout'];
            $folderid = $folder->getId();

            $this->htmlAddHeader('<script type="text/javascript" src="../styles/'.$this->theme.'/bootbox/bootbox.min.js"></script>'."\n", 'js');
            $this->htmlAddHeader('<link href="../elib/elib_project.css" rel="stylesheet"></link>'."\n", 'css');
            echo $this->callHook('startPage');
                
            $this->htmlStartPage(getMLText("folder_title", array("foldername" => htmlspecialchars($folder->getName()))));

            $this->globalNavigation($folder);
            $this->printsearchbar();
            $this->contentStart();
            $this->printmaincontentcontainer();
            $txt = $this->callHook('folderMenu', $folder);
            if(is_string($txt))
                echo $txt;
            else {
                $this->pageNavigation($this->getFolderPermPathHTML($dms, $folder,$selUser, $selGroup), "view_folder", $folder, $selUser, $selGroup);
            }     
            $previewer = new SeedDMS_Preview_Previewer($cachedir, $previewwidth, $timeout);
            $previewer->setConverters($previewconverters);

            echo $this->callHook('preContent');
            $this->printspan4Controlbtn();
            echo "<div class=\"row-fluid\">\n";
            if (!($enableFolderTree || $enableClipboard)) {
                    $LeftColumnSpan = 0;
                    $RightColumnSpan = 12;
            } else {
                    $LeftColumnSpan = 4;
                    $RightColumnSpan = 8;
            }
            if ($LeftColumnSpan > 0) {
                echo "<div class=\"span".$LeftColumnSpan."\">\n";
                if ($enableFolderTree) {
                    if ($showtree==1){
                        $this->contentContainerStart();
                        /*
                         * access expandFolderTree with $this->params because it can
                         * be changed by preContent hook.
                         */
                        $this->printNewTreeNavigationHtmlWithColor($folderid, M_READ, 0, '', $this->params['expandFolderTree'] == 2, $orderby, $selUser, $selGroup);
                        $this->contentContainerEnd();
                    } else {
                        $this->contentHeading("<a href=\"../out/out.CheckPrivilege.php?folderid=". $folderid."&showtree=1\"><i class=\"icon-plus-sign\"></i></a>", true);
                    }
                }
                echo $this->callHook('leftContent');
                if ($enableClipboard) $this->printClipboard($this->params['session']->getClipboard(), $previewer);
                echo "</div>\n";
            }
            echo "<div class=\"span".$RightColumnSpan."\" id=\"span8mainContent\">\n";
            if ($enableDropUpload && $folder->getAccessMode($user) >= M_READWRITE) {
                    echo "<div class=\"row-fluid\">";
                    echo "<div class=\"span8\">";
            }
            $this->contentHeading(getMLText("check_privilege"));
            $this->contentContainerStart();
            if($selUser==-1 && $selGroup==-1)
                echo "Please select a user or group to validate!";
            else{
                if($selUser && $selUser!=-1){ //check user privilege
                    $privAccess=$folder->checkPrivileges($dms->getUser($selUser), 1);
                }else if($selGroup && $selGroup!=-1){ //check Group privilege
                    $privAccess=$folder->checkPrivileges($dms->getGroup($selGroup), 0);
                }
                echo "<div class=\"tab\">";
                $this->printTabs($privAccess[0], "grant");
                $this->printTabs($privAccess[1], "notGrant");
                echo "</div>";
                if($selUser!=-1)
                    $userName=$dms->getUser($selUser)->getLogin();
                else if($selGroup!=-1)
                    $userName=null;
                $this->printTabContent($privAccess[0],"grant", $userName);
                $this->printTabContent($privAccess[1],"notGrant", $userName);
            }
    $this->contentContainerEnd();
?>
    </div>
    </div>

<?php
        $this->contentEnd();
        $this->htmlEndPage();
    } /* }}} */
}
?>