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
    function getSinglePageNum($page, $max){
        for($i=1; $i<=$max; $i++){           
            $options.='<option value="'.$i.'" style="text-align:center">'.$i.'</option>';
        }
        return $options;
    }
    function getDoublePageNum($page, $max){
        $options.='<option value="1" style="text-align:center">1</option>';
        for($i=2; $i<=($max-1); $i=$i+2){
            $options.='<option value="'.$i.'" style="text-align:center">'.$i.'-'.($i+1).'</option>';
        }
        $options.=($max%2==0)?'<option value="'.$max.'" style="text-align:center">'.$max.'</option>':'';
        return $options;
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
        $tabStatus=$this->params['tabStatus'];
        $query=$this->params['query'];
        $flipDir=$this->params['flipDir'];
        $orientation=$this->params['orientation'];
        
        // Glenn edited on 10 May, 2022
        $userAccessMode = $document->getAccessMode($user);
		$privileges = $dms->getPrivilegesByMode($userAccessMode);
		$privilegesObj = json_decode($privileges, true);
        $showContent = 0;
        $showSearch = 0;
        $showBookmark = 0;
        $showNote = 0;
        $showDict = 0;
        $showCropImage = 0;
        $showHighlight = 0;

        // Glenn edited on 10 May, 2022
		// Remove unselected privileges
		foreach ($privilegesObj as $priKey => $priVal) {
			if ($priVal == 0) {
				unset($privilegesObj[$priKey]);
			}
		}

        // Glenn edited on 10 May, 2022
		foreach ($privilegesObj as $priKey => $priVal) {
			// If 'previewdocument_content' privilege exists
			if ($priKey === 'previewdocument_content') {
				$showContent = 1;
			}
			// If 'previewdocument_search' privilege exists
			if ($priKey === 'previewdocument_search') {
				$showSearch = 1;
			}
			// If 'previewdocument_bookmark' privilege exists
			if ($priKey === 'previewdocument_bookmark') {
				$showBookmark = 1;
			}
			// If 'previewdocument_note' privilege exists
			if ($priKey === 'previewdocument_note') {
				$showNote = 1;
			}
			// If 'previewdocument_dict' privilege exists
			if ($priKey === 'previewdocument_dict') {
				$showDict = 1;
			}
			// If 'previewdocument_cropimage' privilege exists
			if ($priKey === 'previewdocument_cropimage') {
				$showCropImage = 1;
			}
			// If 'previewdocument_highlight' privilege exists
			if ($priKey === 'previewdocument_highlight') {
				$showHighlight = 1;
			}
		}
        
        $this->htmlAddHeader('<script type="text/javascript" src="../elib/turnjs/extras/jquery.min.1.7.js"></script>'."\n", 'js');
        $this->htmlAddHeader('<script type="text/javascript" src="../elib/turnjs/lib/turn.js"></script>'."\n", 'js');
//        $this->htmlAddHeader('<script type="text/javascript" src="../elib/turnjs/extras/yepnope-2.0.0.js"></script>'."\n", 'js');
        $this->htmlAddHeader('<script type="text/javascript" src="../elib/turnjs/extras/modernizr.2.5.3.min.js"></script>'."\n", 'js');
        $this->htmlAddHeader('<script type="text/javascript" src="../elib/previewFrame.js"></script>'."\n", 'js');
        $this->htmlAddHeader('<link href="../elib/previewFrame.css" rel="stylesheet"></link>'."\n", 'css');
        $this->htmlAddHeader('<script type="text/javascript" src="../elib/PreviewDocumentjs.js"></script>'."\n", 'js');
        $this->htmlAddHeader('<link href="../elib/PreviewDocument.css" rel="stylesheet"></link>'."\n", 'css');
        $this->htmlAddHeader('<script src="../styles/bootstrap/chosen/js/chosen.jquery.min.js"></script>'."\n", 'js');
        $this->htmlAddHeader('<link href="../styles/bootstrap/chosen/css/chosen.css" rel="stylesheet">'."\n", 'css');
        $this->htmlAddHeader('<script src="../styles/bootstrap/bootstrap-toggle-master/js/bootstrap2-toggle.min.js"></script>'."\n", 'js');
        $this->htmlAddHeader('<link href="../styles/bootstrap/bootstrap-toggle-master/css/bootstrap2-toggle.min.css" rel="stylesheet">'."\n", 'css');
        $this->htmlAddHeader('<script type="text/javascript" src="../styles/'.$this->theme.'/bootbox/bootbox.min.js"></script>'."\n", 'js');
        $this->htmlAddHeader('<link href="../elib/elib_project.css" rel="stylesheet"></link>'."\n", 'css');

        if($user->getReadingDetail($documentid)){
            $readingDetail=$user->getReadingDetail($documentid);
            $disPage=$readingDetail['disPage'];
        }
        else {
            if(!$user->isGuest()){
                $user->addReadingHistory($documentid);
            }
            $tabStatus=0;
            $page=$disPage=1;
        }
        if($page===null){
            $page=$readingDetail["page"];
        }
        if($tabStatus===null){
            $tabStatus=$readingDetail["tabStatus"];
        }
        $this->htmlStartPage(getMLText("document_title", array("documentname" => htmlspecialchars($document->getName()))));
//        print'<div class="loader"></div>';
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

        print'<div class="modal" id="highlightReminder" tabindex="-1" role="dialog" hidden>
        <div class="modal-dialog" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Reminder</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
              <p>The highlight will be shown when you switch to the highlight mode by clicking hide note icon.</p>
            </div>
            <div class="modal-footer">
              <button type="button" id="understand" class="btn btn-primary">OK</button>
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Remind me later</button>
            </div>
          </div>
        </div>
      </div>';

        
        $this->printmaincontentcontainer();
//        print'<div class="loader"></div>';
        print '<span style="float:right;font-size:initial;line-height:initial;padding: 5px;">';

        if(!$this->params['session']->getNightmode()){
            print'<span class="icon-adjust" value="1" id="night-toggle">  </span>';
        }else{
            print'<span class="icon-adjust" value="0" id="night-toggle">  </span>';
        }
        //print'<span class="icon-font" id="font-size-small" style="font-size:15px;"></span><span class="icon-font" id="font-size-large" style="font-size:20px;"></span>';
        print'</span>';


        echo $this->getFolderPathHTML($folder, true, $document)."\n";

         
        $dir = '../elib/data/1048576/'.$documentid.'/'.$version.'/';
        $i=count(glob($dir. "*.jpg"));
        if(file_exists("../elib/data/1048576/".$documentid."/book")){
            print"<input type='hidden' id='scannedExist' value='1'>";
        }else{
            print"<input type='hidden' id='scannedExist' value='0'>";
        }
        if(file_exists($dir."e")){
            print"<input type='hidden' id='eeCrop' value='1'>"; //electronic cropped image for editor mode
        }else{
            print"<input type='hidden' id='eeCrop' value='0'>";
        }
        if(file_exists("../elib/data/1048576/".$documentid."/book/e")){
            print"<input type='hidden' id='seCrop' value='1'>"; //scanned cropped image for editor mode
        }else{
            print"<input type='hidden' id='seCrop' value='0'>";
        }
        if($orientation==="1"){
            $width=872;
            $height=600;
        }else if($orientation==="2"){
            $width=1200;
            $height=436;
        }
        print"<input type='hidden' id='width' value='".$width."'>";
        print"<input type='hidden' id='height' value='".$height."'>";
        print"<input type='hidden' id='page' value='".$page."'>";
        print"<input type='hidden' id='maxPage' value='".$i."'>";
        print'<div id="lockContainer" style="display:none"><input id="lockToggle" type="checkbox" data-toggle="toggle" data-on="Locked" data-off="Unlock" data-size="small"></div>';
        
        print '<div class="nav nav-tabs" id="docTab" hidden>
                <li><a>文<br>件<br>區<br><span class="icon-plus-sign" title="open document display area" style="font-size:15px;"></span></a></li><br>
            </div>';
//        print'<div id="mousePoint"></div>';
        print'<flex class="h" style="flex: 1;">';
        print'   <flex-item id="docArea" style="flex: 2; ">';
        print'  <span id="leftTab" style="cursor: pointer; float:left; color:black; z-index: 10; position: absolute;">'
            .'  &nbsp<span class="icon-minus-sign" title="minimize document display area" id="docClose" style="font-size:15px;"></span>'
            .'  &nbsp';
//        print'<div id="lockContainer" style="display:none"><input id="lockToggle" type="checkbox" data-toggle="toggle" data-on="Locked" data-off="Unlock" data-size="small"></div>';
        print'  </span>';
        print'<div class="flex-container" style="display:none">
              <div class="flex-item-left"><img id="flexImg" src="../elib/data/1048576/310/1/1.jpg"></div>
              <div class="flex-item-right"><img id="flexImg" src="../elib/data/1048576/310/book/5.jpg"></div>
              </div>';
        print'<div class="flipbook-viewport">'; 
//        print'  <span style="cursor: pointer; float:left; color:black; z-index: 1; position: relative;">'
//            .'  &nbsp<span class="icon-minus-sign" title="minimize document display area" id="docClose" style="font-size:15px;"></span>'
//            .'  </span>';
        print'  <div class="container" id="flipbook-container">';
        print'      <div class="flipbook" id="turnjs" style="position:absolute; width: 100%;height: 100%;" dir="'.$flipDir.'">';
                        echo "<div style='background-image:url(".$dir."1.jpg)'></div>"; 
        print'      </div>';
        if($flipDir==="ltr"){
            echo '      <div class="flip" id="left" style="left:0" title="Previous Page"><i class="icon-caret-left" hidden></i></div>';
            echo '      <div class="flip" id="right" style="right:0" title="Next Page"><i class="icon-caret-right" hidden></i></div>';
        }else{
            echo '      <div class="flip" id="left" style="left:0" title="Next Page"><i class="icon-caret-left" hidden></i></div>';
            echo '      <div class="flip" id="right" style="right:0" title="Previous Page"><i class="icon-caret-right" hidden></i></div>';
        }
        print'  </div>';
        //div class flipbook
        //print $tmp;
        print'<br>';
        print'<input type="hidden" id="docid" value="'.$documentid.'"><input type="hidden" id="version" value="'.$version.'">';
        print'</div>';
       // Glenn edited on 10 May, 2022
        print'<span class="popup" id="popupnav">';
        if ($showHighlight === 1) {
            print'<a data-id="0">Highlight</a>';
        }
        if ($showSearch === 1) {
            print'<a data-id="1">Search</a>';
        }
        if ($showNote === 1) {
            print'<a data-id="2">Notes</a>';
        }
        if ($showDict === 1) {
            print'<a data-id="3">Dict</a>';
        }
        print'</span>';
        //print'<span class="popup" id="popuptxt">Popup</span>';
            print'<div class="popup" id="popupcontainer">                              
                <div class="noteFrame" id="noteFrame"></div>
            </div>';
        print'<nav><ul>';
            if($flipDir==="ltr"){
                print'<li><a id="prevPage" title="Previous page"><i class="icon-caret-left"></i></a></li>
                <li><a id="nextPage" title="Next page"><i class="icon-caret-right"></i></a></li>';
            }else{
                print'<li><a id="nextPage" title="Next page"><i class="icon-caret-left"></i></a></li>
                <li><a id="prevPage" title="Previous page"><i class="icon-caret-right"></i></a></li>';
            }
            print'<li><a id="zoomOut" title="Zoom out"><i class="icon-zoom-out"></i></a></li>
            <li><a id="zoomIn" title="Zoom in"><i class="icon-zoom-in"></i></a></li>';
//        print'<li class="pageNumber"><a style="width: 70px;"><input type="text" id="currPage" value="'.$page.'/'.$i.'" size=7></a></li>';
        if($disPage==1){ //if single page display, show double display icon
            $disPageIcon='<li><a id="disPage" value="1" title="Change to double page"><i class="icon-columns"></i></a></li>';
            $option=$this->getSinglePageNum(intval($page), $i);
        }else if($disPage==2){ //if double page display, show single display icon
            $disPageIcon='<li><a id="disPage" value="2" title="change to single page"><i class="icon-file-alt"></i></a></li>';
            $option=$this->getDoublePageNum(intval($page), $i);
        }
        print'<li><a id="fullScreen" title="Change to fullscreen"><i class="icon-fullscreen"></i></a></li>';
        print $disPageIcon;
        // Glenn edited on 10 May, 2022
        if ($showCropImage === 1) {
            print '<li><a title="Crop image" href="../out/out.CropImage.php?documentid='.$documentid.'&version='.$version.'"><i class="icon-cut"></i></a></li>';
        }
        print'<li><a id="copy" title="copy document link"><i class="icon-copy"></i></a></li>';    
        print'<li><a id="navControl" title="hide navifation bar" value="0"><i class="icon-minus-sign-alt"></i></a></li>';
        print'<li><a id="displayMode" title="electronic version" value="0"><i class="icon-asterisk"></i></a></li>';
        // Glenn edited on 10 May, 2022
        if ($showBookmark === 1) {
            if(bookmark) {
                print'<li><a id="addbookmarkbtn" value="1" title="Add bookmark"><i class="icon-bookmark-empty"></i></a></li>';
            }
            else {
                print'<li><a id="addbookmarkbtn" value="2" title="Bookmark added"><i class="icon-bookmark"></i></a></li>&nbsp';
            }
        }
        print'</ul></nav>';
//        print'<br>';
        print'<div class="slidecontainer" id="slidercontain">';
        print'<span id="tooltipPage"></span>';
        print'<input type="range" min="1" max="'.$i.'" value="'.$page.'" class="slider" id="mySlider">
                <span>Page: <span id="mySliderValue">'.$page.'</span>/'.$i.'<span/>&nbsp';
        print'<span id="showNav" title="show navigation bar" style="font-size:15px;" hidden><i class="icon-plus-sign-alt"></i></span>';
         print'</div>';
print'</flex-item>';
        print'<flex-resizer></flex-resizer>';
    print'<flex-item id="textArea" style="flex: 1;"> ';
        
//        print'<div class="span4" id="infobar" data-status="'.$readingDetail['tabStatus'].'">'; 

        print '<input type="hidden" id="curtabval" value="'.$tabStatus.'">';
        print '<input type="hidden" id="query" value="'.$query.'">';
        
//        $this->contentContainerStart();
//        print'<div style="top: 0px;background-color: gray;color: white;">';
        print '<div class="nav nav-tabs" id="infotab" style="top: 0px;background-color: gray;color: white;">';
        // Glenn edited on 10 May, 2022
        if ($showContent === 1) {
            print '<li class="navtab" value="0" id="navtab0"><a>資訊</a></li>';
        }
        if ($showSearch === 1) {
            print '<li class="navtab" value="1" id="navtab1"><a>搜尋</a></li>';
        }
        if ($showBookmark === 1) {
            print'<li class="navtab" value="2" id="navtab2"><a>書籤</a></li>';
        }
        if ($showNote === 1) {
            print ' <li class="navtab" value="3" id="navtab3"><a>筆記</a></li>';
        }
        print ' <li class="navtab" value="4" id="navtab4"><a>字典</a></li>';
        print '<span style="cursor: pointer; float:right; color:black">'
            . '&nbsp<span class="icon-minus-sign" title="minimize text display area" id="textClose" style="font-size:15px;"></span>'
            . '</span>';
        print '<span id="tabSpan" style="cursor: pointer; float:right; line-height:38px;">'
        . '<span class="icon-eye-close" title="hide note" value="0" id="markNote" style="font-size:20px;"></span>&nbsp'
                . '<span class="icon-book" title="User Mode" value="w" id="textMode" style="font-size:20px;"></span>&nbsp'
                . '<span class="icon-text-height" title="single spacing" value="1" id="lineSpacing" style="font-size:20px;"></span>&nbsp'
                . '<span class="icon-font" title="smaller font size" id="font-size-small" style="font-size:15px;"></span>'
                . '<span class="icon-font" title="larger font size" id="font-size-large" style="font-size:20px;"></span>'
                . '</span>';
        print ' </div>';        
        print'<div class="tab-pane" id="pageinfotab" style="display:none;">';
        print'<div class="tabbar" id="pageinfocontent">';
        print'<div class="tabbarres" id="pageinfocontentres">';
        print'<div id="pageinfotxt"></div>'; //style="white-space: pre;"
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
        print'<div style="height: auto; display:inline-block">
                <button id="addnotebtn" style="height:30px; margin-bottom:10px">+</button>
                   <span id="noteFilter" >';
                        $options[]=array(-1,getMLText('view_notes_on_this_page_only'));
                        $options[]=array(0,getMLText('view_all_notes_for_this_document'));
                        $options[]=array(1,getMLText('view_all_my_notes'));
                        $options[]=array(2,getMLText('view_notes_for_specific_user'));
                        if($user->isAdmin()){
                            $options[]=array(3,getMLText('view_advice_for_admin'));
                        }
                        $this->formField(
                            null,
                            array(
                                'element'=>'select',
                                'name'=>'filter[]',
                                'id'=>'noteFilterselect',
                                'attributes'=>array(array('data-placeholder', 'Filter')),
                                'options'=>$options
                            )
                        );

                    print'</span>';
        print'<span id="noteSort" >';
                $options2[]=array(0,getMLText('date_in_descending_order'));
                $options2[]=array(1,getMLText('date_in_ascending_order'));
                $options2[]=array(2,getMLText('page_in_descending_order'));
                $options2[]=array(3,getMLText('page_in_ascending_order'));
                $this->formField(
                    null,
                    array(
                        'element'=>'select',
                        'name[]'=>'sort',
                        'id'=>'noteSortselect',
                        'attributes'=>array(array('data-placeholder', 'Filter')),
                        'options'=>$options2
                    )
                );

            print'</span>';
        print'<select id="userfilter" style="display:none;">';
        $allUsers=$dms->getAllUsers();
        foreach ($allUsers as $currUser) {
            if($currUser->getId()!=$user->getID()){
                print'<option value="'.$currUser->getId().'">'.$currUser->getFullName().'</option>';
            }

        }
        print '</select>';  
        print'</div>';

        print'<div class="tabbarres" id="notecontentres">No notes in this page yet.</div>
              <div class="tabbarres" id="notecontentdetailres" style="display:none;"></div>
              <div class="tabbarres" id="addnotecontentres" style="display:none;">
              <br>';
                $types[]=array(1,getMLText('document_note'));
                $types[]=array(2,getMLText('send_to_admin'));
                $this->formField(
                    null,
                    array(
                        'element'=>'select',
                        'name'=>'noteType[]',
                        'id'=>'noteType',
                        'attributes'=>array(array('data-placeholder', 'Filter')),
                        'options'=>$types
                    )
                );
        print'<textarea id="subject" maxlength="50" style=" width:50%; height:20px;resize: none;" placeholder="Subject"></textarea>
              <textarea id="notebox" maxlength="1000" style=" width:95%; height:100px; resize: vertical;" ></textarea><br>
              <input type="hidden" id="parent" value="0">
              <input type="hidden" id="quotepage" value="0">
              <input type="hidden" id="quoteStart" value="0">
              <input type="hidden" id="backMode" value="0">
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
        print'<div class="tab-pane" id="dictTab" style="display:none;">';
        print'<div class="tabbar" id="dictContent">'
            . '<input type="text" placeholder="Search..." id="dictQuery">
                <button id="dictBtn">Search</button>
                <div class="tabbarres" id="dictContentRes">';
        print'</div></div></div>';
//        $this->contentContainerEnd();
//        print'<br>';
        
        print'</flex-item>';
        print'</flex>';//div class row-fluid
        print '<div class="nav nav-tabs" id="textTab" hidden>
                <li><a>文<br>字<br>區<br><span class="icon-plus-sign" title="open text display area" id="docClose" style="font-size:15px;"></span></a></li><br>
            </div>';
//        print'</div>';
        $this->contentEnd();
        $this->htmlEndPage();
    } /* }}} */
}
?>

