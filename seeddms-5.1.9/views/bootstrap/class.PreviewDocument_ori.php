<?php
/**
 * Implementation of ViewDocument view
 *
 * @category   DMS
 * @package    SeedDMS
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */

/**
 * Include parent class
 */
require_once("class.Bootstrap.php");

/**
 * Include class to preview documents
 */
require_once("SeedDMS/Preview.php");

/**
 * Class which outputs the html page for ViewDocument view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */





class SeedDMS_View_PreviewDocument extends SeedDMS_Bootstrap_Style {
    function sslencrypt($data,$key){
        $ciphering = "AES-128-CTR"; 
        $encryption_iv = '1234567891011121'; 
        $encryption = openssl_encrypt($data, $ciphering, $key, 0, $encryption_iv); 
        return rtrim( strtr( base64_encode( $encryption ), '+/', '-_'), '=');
    }



    /**
     * Output a single attribute in the document info section
     *
     * @param object $attribute attribute
     */

    function show() { /* {{{ */
        parent::show();
        $dms = $this->params['dms'];
        $user = $this->params['user'];
        $folder = $this->params['folder'];
        $document = $this->params['document'];
        $accessop = $this->params['accessobject'];
        $viewonlinefiletypes = $this->params['viewonlinefiletypes'];
        $enableownerrevapp = $this->params['enableownerrevapp'];
        $workflowmode = $this->params['workflowmode'];
        $cachedir = $this->params['cachedir'];
        $previewwidthlist = $this->params['previewWidthList'];
        $previewwidthdetail = $this->params['previewWidthDetail'];
        $previewconverters = $this->params['previewConverters'];
        $pdfconverters = $this->params['pdfConverters'];
        $documentid = $document->getId();
        //$currenttab = $this->params['currenttab'];
        $version=$this->params['version'];
        $page=$this->params['page'];
        $query=$this->params['query'];

        //$this->htmlAddHeader('<script type="text/javascript" src="../elib/turnjs/extras/jquery.min.1.7.js"></script>'."\n", 'js');
        //$this->htmlAddHeader('<script type="text/javascript" src="../elib/turnjs/lib/turn.js"></script>'."\n", 'js');
        $this->htmlAddHeader('<script type="text/javascript" src="../elib/turnjs/extras/yepnope-2.0.0.js"></script>'."\n", 'js');
        $this->htmlAddHeader('<script type="text/javascript" src="../elib/turnjs/extras/modernizr-csstransform-3.8.0.min.js"></script>'."\n", 'js');
        //$this->htmlAddHeader('<script type="text/javascript" src="../elib/turnjs/extras/modernizr.2.5.3.min.js"></script>'."\n", 'js');
        $this->htmlAddHeader('<script type="text/javascript" src="../elib/PreviewDocumentjs.js"></script>'."\n", 'js');
        $this->htmlAddHeader('<link href="../elib/PreviewDocument.css" rel="stylesheet"></link>'."\n", 'css');

        $this->htmlAddHeader('<script type="text/javascript" src="../styles/'.$this->theme.'/bootbox/bootbox.min.js"></script>'."\n", 'js');
        $this->htmlAddHeader('<link href="../elib/elib_project.css" rel="stylesheet"></link>'."\n", 'css');

        if(!$user->isGuest()){
            $user->addReadingHistory($documentid);}


        $this->htmlStartPage(getMLText("document_title", array("documentname" => htmlspecialchars($document->getName()))));
        $this->globalNavigation($folder); //this is the black topnav
        $this->contentStart();
        print'<div class="span12" id="helpcontain"  style="
                display: none;position: fixed;height: 100%;">';
        print'<div class="popup" id="help" value="0" style="
                position: fixed; display:block;
            "><span id="helpclose" style="margin-left:90%;">x</span>
            <span id="helptxt"></span><br>
            <a id="helpnext">next</a>
            </div></div>';




        $this->printmaincontentcontainer();
        print '<span style="float:right;font-size:initial;line-height:initial;padding: 5px;">';

        if(!$this->params['session']->getNightmode()){
            print'<span class="icon-adjust" value="1" id="night-toggle">  </span>';
        }else{
            print'<span class="icon-adjust" value="0" id="night-toggle">  </span>';
        }
        print'<span class="icon-font" id="font-size-small" style="font-size:15px;"></span><span class="icon-font" id="font-size-large" style="font-size:20px;"></span></span>';


        echo $this->getFolderPathHTML($folder, true, $document)."\n";

        echo '<button id="helppop">help</button>';
        if($folder->checkPrivAccess($user, M_ALL, "CropImage"))
            echo '<a class="btn" href="../out/out.CropImage.php?documentid='.$documentid.'&version='.$version.'">Crop</a>';
        //$this->pageNavigation($this->getFolderPathHTML($folder, true, $document), "view_document", $document); //this is the grey topnav

        $dir = '../elib/data/1048576/'.$documentid.'/'.$version.'/';
        $i=count(glob($dir. "*.jpg")); //Returns an array containing the matched files/directories

        /*$tmpstr="[";
        for($tmp=1;$tmp<=$i;$tmp++){
            $id="/home/www-data/seeddms51x/seeddms-5.1.9/op/../elib/data/1048576/".$documentid."/".$version."/".$tmp.".txt";
            $tmpstr.="{'id':'".$id."','access_count':{'inc':1.0}},";
        }
        $tmpstr.="]";
        //file_put_contents("/home/www-data/seeddms51x/debug.txt", $tmpstr, FILE_APPEND);
        $tmpstr="curl 'http://localhost:8983/solr/ik-Library/update?commit=true' --data-binary \"".$tmpstr."\" -H 'Content-type:application/json'";
        //file_put_contents("/home/www-data/seeddms51x/debug.txt", $tmpstr, FILE_APPEND);
        shell_exec($tmpstr);*/


        print"<input type='hidden' id='page' value='".$page."'>";
        print'<div class="row-fluid">';

       
        print'<div class="span8" id="turnjscontain" >'; //1385 or 911.48

        print'<div class="flipbook-viewport">';
            print'<div id="flipbk-control">';
                if($user->getID()!=2){
                    print' <span id="addbookmarkbtn" style="font-size:30px;"><i class="icon-bookmark"></i></span>';}
                print  '<span id="zoom-icon" style="font-size: 30px;"> <i class="icon-zoom-out"></i> <i class="icon-zoom-in"></i> <i class="icon-fullscreen"></i></span> ';
            print'</div>';  
        print'   <div class="container" id="flipbook-container">';

        print'          <div class="flipbook" id="turnjs" style="position: static;width: 100%;height: 100%;" dir="rtl">';
                        echo "<div style='background-image:url(".$dir."1.jpg)'></div>"; 
                        /*for($x=1; $x<=3; $x++){
                            echo "<div style='background-image:url(".$dir.$x.".jpg)'></div>"; 
                            //echo "<div style='background-image:url(../elib/redirect.php?h= ".$this->sslencrypt($dir.$x.".jpg",$_COOKIE['mydms_session']).")'></div>";

                        }*/
        print'      </div>
                </div>
            </div>
        ';//div class flipbook
        //print $tmp;
        //print'<br>';
        print'<br><div class="slidecontainer" id="slidercontain">';
        print'<button id="singlePage" value="1">SinglePage</button> ';
        print'<input type="range" min="1" max="'.$i.'" value="'.$page.'" class="slider" id="mySlider">
                Page: <span id="mySliderValue"></span>/'.$i;
        //print' <button id="showbtn">show</button>';
        /*if($user->getID()!=2){
            print' <span id="addbookmarkbtn" style="font-size:20px;"><i class="icon-bookmark"></i></span>';}
        print  '<span id="zoom-icon" style="font-size: 30px;"> <i class="icon-zoom-out"></i><i class="icon-zoom-in"></i><i class="icon-fullscreen"></i></span>';*/
        print'<input type="hidden" id="docid" value="'.$documentid.'"><input type="hidden" id="version" value="'.$version.'"></div>';
        print'<br>';

        print'<span class="popup" id="popupnav">
                <a data-id="0">Highlight</a>
                <a data-id="1">Search</a>
                <a data-id="2">Notes</a>
                <a data-id="3">Dict</a>
                </span>';
        //print'<span class="popup" id="popuptxt">Popup</span>';
            print'<div class="popup" id="popupcontainer">               
                    <ul class="nav nav-tabs" id="navlist"></ul>
                    <div class="tab-content footnoteFrame" id="footnoteFrame"></div>
                  </div>';
        print'</div>';


        print'<div class="span4" id="infobar">';

        print '<div class="nav nav-tabs" id="infotab">
                <li class="navtab" value="0" id="navtab0"><a>資<br>訊</a></li><br>
                <li class="navtab" value="1" id="navtab1"><a>搜<br>尋</a></li><br>';
        if($user->getID()!=2){
            print'<li class="navtab" value="2" id="navtab2"><a>書<br>籤</a></li><br>';}
        print ' <li class="navtab" value="3" id="navtab3"><a>筆<br>記</a></li><br>';
        print ' </div>';
        print '<input type="hidden" id="curtabval" value="0">';

        $this->contentContainerStart();
        print'<div style="top: 0px;background-color: gray;text-align: left;color: white;"><span id="tabcontrol" style="margin-left: 5px;">x </span></div>';

        print'<div class="tab-pane" id="pageinfotab" style="display:none;">';
        print'<div class="tabbar" id="pageinfocontent">';
        print'<div class="tabbarres" id="pageinfocontentres">';
        print'<span id="pageinfotxt"></span>';
        print'</div></div></div>';



        print'<div class="tab-pane" id="searchtab" style="display:none;">';
        print'<div class="tabbar" id="searchcontent">'
            . '<input type="text" placeholder="Search..." id="searchquery" value="'.$query.'">
                <button id="searchbtn">Search</button>
                <div class="tabbarres" id="searchcontentres">';
        print'</div></div></div>';

        print'<div class="tab-pane" id="bookmarktab" style="display:none;">';
        print'<div class="tabbar" id="bookmarkcontent">';
        print'<div class="tabbarres" id="bookmarkcontentres">';
        print'No bookmark yet. Try to press the heart button to add a bookmark';
        print'</div></div></div>';

        //getMLText('select_groups')
        print'<div class="tab-pane" id="notetab" style="display:none;">';
        print'<div class="tabbar" id="notecontent">';
        print'<div style="display:flex; height: 45px;">
                <button id="addnotebtn" style="height:30px;">+</button>
                   <div id="noteFilter" style="width:70%;">';
                        $options[]=array(-1,getMLText('view_notes_on_this_page_only'));
                        $options[]=array(0,getMLText('view_all_notes_for_this_document'));
                        $options[]=array(1,getMLText('view_all_my_notes'));
                        $options[]=array(2,getMLText('view_notes_for_specific_user'));
                        $this->formField(
                            null,
                            array(
                                'element'=>'select',
                                'name'=>'filter[]',
                                'class'=>'chzn-select',
                                'id'=>'noteFilterselect',
                                'attributes'=>array(array('data-placeholder', 'Filter')),
                                'options'=>$options
                            )
                        );

        print'</div>';
        print'<select id="userfilter" style="display:none;">';
        $allUsers=$dms->getAllUsers();
        foreach ($allUsers as $currUser) {
            if($currUser->getId()!=$user->getID()){
                print'<option value="'.$currUser->getId().'">'.$currUser->getFullName().'</option>';
            }

        }

        print '</select>';

        print'</div><br>';

        print'<div class="tabbarres" id="notecontentres">No notes in this page yet.</div>
              <div class="tabbarres" id="notecontentdetailres" style="display:none;"></div>
              <div class="tabbarres" id="addnotecontentres" style="display:none;">
              <br><textarea id="subject" maxlength="50" style=" width:50%; height:20px;resize: none;" placeholder="Subject"></textarea>
              <textarea id="notebox" maxlength="1000" style=" width:95%; height:100px; resize: vertical;" ></textarea><br>

              <input type="hidden" id="parent" value="0">
              <input type="hidden" id="quotepage" value="0">

              <div id="quotearea" style="display:none;">
              <textarea disabled id="quotebox"></textarea>&nbsp<button type="button" id="rmquote" width:15%; ">x</button>
              </div>';
        if($user->getID()!=2){
              print'<div style="display:flex;">
              <input type="checkbox" id="visible">
                <label for="visible">Set Private</label></div>';
        }
        print'<button type="button" id="subbtn">Submit</button></div>';
        print'</div></div>';

        $this->contentContainerEnd();
        print'<br>';
        print'</div></div>';//div class row-fluid

        print'</div>';

        $this->contentEnd();
        $this->htmlEndPage();
    } /* }}} */
}
?>
