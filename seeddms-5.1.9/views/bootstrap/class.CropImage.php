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





class SeedDMS_View_CropImage extends SeedDMS_Bootstrap_Style {


	/**
	 * Output a single attribute in the document info section
	 *
	 * @param object $attribute attribute
	 */

        
        function js(){
            header('Content-Type: application/javascript; charset=UTF-8');
            ?>

            var Xslider = document.getElementById("Xpercentage");
            var Yslider = document.getElementById("Ypercentage");
            if ( window.history.replaceState ) {
                window.history.replaceState( null, null, window.location.href );
            }
            $(document).ready(function(){
            $("#bookselect").on("change", function(){
                    var bookSelect=document.getElementById("bookselect").selectedOptions[0].value;
                    var dir=document.getElementById(bookSelect+"Dir").value;
                    var pageSelect=document.getElementById("pageselect").selectedOptions[0].value;
                    var mode=$("input[name=mode]:checked").val();
                    document.getElementById("origImg").src=dir+"orig/"+pageSelect+".jpg?=rand()";
                    if(bookSelect=="electronic"){
                        $("#eleCrop").attr("hidden", false);
                        $("#scannedCrop").attr("hidden", true);
                        // added by sc
                        document.getElementById("commitBook").value="Commit to all pages of books";
                        document.getElementById("recoverBook").value="Recover to book";
                        //
                    }else{
                        $("#scannedCrop").attr("hidden", false);
                        $("#eleCrop").attr("hidden", true);
                        $("#scannedNoti").attr("hidden", false);
                        // added by sc
                        oddOrEvenPage(pageSelect);
                        //
                    }
                    $("#cropImage").css("display", "none");
                    $("#cropInfo").empty();
            });
            
            $("#pageselect").on("change", function(){
                    var bookSelect=document.getElementById("bookselect").selectedOptions[0].value;
                    var dir=document.getElementById(bookSelect+"Dir").value;
                    var pageSelect=document.getElementById("pageselect").selectedOptions[0].value;
                    $("#cropImage").css("display", "none");
                    $("#cropInfo").empty();
                    document.getElementById("origImg").src=dir+"orig/"+pageSelect+".jpg?=rand()";
                    // added by sc
                    if (bookSelect=="scanned") {
                        oddOrEvenPage(pageSelect);
                    }
                    //
                });
            $("input[name=mode]").on("change", function(){
                var bookSelect=document.getElementById("bookselect").selectedOptions[0].value;
                var mode=$("input[name=mode]:checked").val();
                $("#cropImage").css("display", "none");
                $("#cropInfo").empty();
                $(".mode").val(mode);
                });
            $("#commitBook").live("click", function(){
                var bookSelect=document.getElementById("bookselect").selectedOptions[0].value;
                var data=getData(bookSelect);
                var mode=$("input[name=mode]:checked").val();
                var documentid=$("#documentid").val();
                var version=$("#version").val();
                var odd=0;
                if ($(this).val().includes("odd"))
                {
                    odd=1;
                }
                $.ajax({
                    type:"POST",
                    url:"../op/op.CropImage.php",
                    data: {"action":"commitBook", "bookSelect":bookSelect, "mode":mode, "data":data, 
                     "documentid":documentid, "version":version, "odd":odd},
                    success:function(response){
                        if(response==1){
                            //$("#cropInfo").html("Applied Xpercentage:"+x+"% and Ypercentage:"+y+ 
                            //"% to all pages of the "+bookSelect+" version for "+mode+" mode");
                            $("#cropInfo").html("Croped all pages");
                        }else if(response==1 && bookSelect=="scanned"){
                            $("#cropInfo").html("fail to commit the book");
                        }
                        $("#status").attr("value", 1);
                        $("#status").html("This book has been cropped before for "+mode+" mode.");
                        $("#cropImage").css("display", "none");
                        $("#recoverInfo").empty();
                    }
                });
            });
            $("#commitPage").live("click", function(){
                cropImage(0);
            });
            $("#recoverBook").live("click", function(){
                var bookSelect=document.getElementById("bookselect").selectedOptions[0].value;
                var mode=$("input[name=mode]:checked").val();
                var documentid=$("#documentid").val();
                var version=$("#version").val();
                var odd=0;
                if ($(this).val().includes("odd"))
                {
                    odd=1;
                }
                else if (bookSelect == "electronic")
                {
                    odd=-1;
                }
                $.ajax({
                    type:"POST",
                    url:"../op/op.CropImage.php",
                    data: {"action":"recoverBook", "bookSelect":bookSelect, "mode":mode,
                    "documentid":documentid, "version":version, "odd":odd},
                    success:function(response){
                        if(response){
                            $("#recoverInfo").html("The book has been recoverd to original status.");
                        }
                        $("#cropImage").css("display", "none");
                        $("#cropInfo").empty();
                        $("#status").attr("value", 0);
                        $("#status").html("This book has not been cropped before for "+mode+" mode.");
                    }
                });
            });
            $("#recoverPage").live("click", function(){
                var bookSelect=document.getElementById("bookselect").selectedOptions[0].value;
                var pageSelect=document.getElementById("pageselect").selectedOptions[0].value;
                var mode=$("input[name=mode]:checked").val();
                var documentid=$("#documentid").val();
                var version=$("#version").val();
                $.ajax({
                    type:"POST",
                    url:"../op/op.CropImage.php",
                    data: {"action":"recoverPage", "bookSelect":bookSelect, "pageSelect":pageSelect,
                    "mode":mode, "documentid":documentid, "version":version},
                    success:function(response){
                        if(response){
                            $("#recoverInfo").html("The page has been recoverd to original status.");
                        }
                        $("#cropImage").css("display", "none");
                        $("#cropInfo").empty();
                    }
            });
            });
            
            // added by sc
            function oddOrEvenPage(pageNum){
                if (pageNum%2==0) {
                    document.getElementById("commitBook").value="Commit to all even pages of books";
                    document.getElementById("recoverBook").value="Recover to all even pages of book";
                }
                else {
                    document.getElementById("commitBook").value="Commit to all odd pages of books";
                    document.getElementById("recoverBook").value="Recover to all odd pages of book";
                }
            }
            
            function cropImage(test){
                var bookSelect=document.getElementById("bookselect").selectedOptions[0].value;
                var pageSelect=document.getElementById("pageselect").selectedOptions[0].value;
                var data=getData(bookSelect);
                var mode=$("input[name=mode]:checked").val();
                var documentid=$("#documentid").val();
                var version=$("#version").val();
                var dir=document.getElementById(bookSelect+"Dir").value;
                $.ajax({
                    type:"POST",
                    url:"../op/op.CropImage.php",
                    data: {"action":"commitPage", "bookSelect":bookSelect, "pageSelect":pageSelect, 
                    "mode":mode, "data":data, "documentid":documentid, "version":version, "test":test},
                    success:function(response){
                        $("#cropInfo").html("After cropped:");
                        $("#cropImage").attr("src", response+ '?' + new Date().getTime());
                        $("#cropImage").css("display", "block");
                        $("#recoverInfo").empty();
                        
                    }
                });
            }
            function getData(bookSelect){
                var data={};
                if(bookSelect=="electronic"){
                    var data={"x":$("#Xpercentage").val(), "y":$("#Ypercentage").val()};
                }else{
                    if($("#deskew").is(":checked")){
                        data["deskew"]=true;
                    }
                    if($('#sharpFlag').is(":checked")){
                        data["xi"]=$("#xSharpen").val();
                        data["yi"]=$("#ySharpen").val();
                    }
                    if($('#brightFlag').is(":checked")){
                        data["brightness"]=$("#brightPer").val();
                        data["contrast"]=$("#conPer").val();
                    }
                    if($('#cropFlag').is(":checked")){
                        data["t"]=$("#tPercentage").val();
                        data["b"]=$("#bPercentage").val();
                        data["l"]=$("#lPercentage").val();
                        data["r"]=$("#rPercentage").val();
                    }
                }
                return data;
            }
            $("#Xpercentage").live("change", function(){
                $("#Xperpre").html($(this).val());
                cropImage(1);
            });
            $("#Ypercentage").live("change", function(){
                $("#Yperpre").html($(this).val());
                cropImage(1);
            });
            $("#ySharpen").live("change", function(){
                $("#yi").html($(this).val());
                cropImage(1);
            });
            $("#xSharpen").live("change", function(){
                $("#xi").html($(this).val());
                cropImage(1);
            });
            $("#brightPer").live("change", function(){
                $("#brightness").html($(this).val());
                cropImage(1);
            });
            $("#conPer").live("change", function(){
                $("#contrast").html($(this).val());
                cropImage(1);
            });
            $("#tPercentage").live("change", function(){
                $("#t").html($(this).val());
                cropImage(1);
            });
            $("#bPercentage").live("change", function(){
                $("#b").html($(this).val());
                cropImage(1);
            });
            $("#lPercentage").live("change", function(){
                $("#l").html($(this).val());
                cropImage(1);
            });
            $("#rPercentage").live("change", function(){
                $("#r").html($(this).val());
                cropImage(1);
            });
            $("#deskew").live("change", function(){
                var val=$(this).is(":checked");
                cropImage(1);
            });
            $("#sharpFlag").live("change", function(){
                var val=$(this).is(":checked");
                if(val){
                   $("#sharpContainer").attr("hidden", false); 
                }else{
                    $("#sharpContainer").attr("hidden", true); 
                }
                cropImage(1);
            });
            $("#brightFlag").live("change", function(){
                var val=$(this).is(":checked");
                if(val){
                   $("#brightContainer").attr("hidden", false); 
                }else{
                    $("#brightContainer").attr("hidden", true); 
                }
                cropImage(1);
            });
            $("#cropFlag").live("change", function(){
                var val=$(this).is(":checked");
                if(val){
                   $("#cropContainer").attr("hidden", false); 
                }else{
                    $("#cropContainer").attr("hidden", true); 
                }
                cropImage(1);
            });
            });
            <?php
        }
        
        function delTestImage($docdir, $verID) {
            //   This function deltes the test image created in the previous image cropping function
            //    $pageN is a string for the document page number for the test image page
            //    remove the cropped image after use
            //    
            $currentdir = getcwd();
            //echo $currentdir;
            //$fn = $docdir . "/" . $verID . "/*c.jpg";
            $cmd = "rm ".$docdir . "/" . $verID . "/*c.jpg";
            shell_exec($cmd);
            $cmd = "rm ".$docdir . "/" . $verID . "/*e.jpg";
            shell_exec($cmd);
            //unlink($fn);
            chdir($currentdir); // return to the directory of the calling module.
        }
        
	function show() { /* {{{ */
            parent::show();
            $dms = $this->params['dms'];
            $user = $this->params['user'];
            $folder = $this->params['folder'];
            $document = $this->params['document'];
            $documentid = $document->getId();
            $version=$this->params['version'];
            $this->htmlStartPage(getMLText("document_title", array("documentname" => htmlspecialchars($document->getName()))));
            $this->globalNavigation($folder); //this is the black topnav
            $this->contentStart();
            $this->printmaincontentcontainer();
            $this->delTestImage('../elib/data/1048576/'.$documentid, $version); //delete all test images when loading the page

            if(isset($_POST['Commit'])&&isset($_POST['XCommit'])&&isset($_POST['YCommit'])){
                $this->commitResize('../elib/data/1048576/'.$documentid, $version, $_POST['XCommit'], $_POST['YCommit']);
                print'Apply to all pages. Xpercentage:'.$_POST['XCommit'].' Ypercentage:'.$_POST['YCommit'].'<br>';
                $_POST['Commit']=0;
            }
            //recover all pages and specific page
            if(isset($_POST['Recover'])){
                $this->imgRecover('../elib/data/1048576/'.$documentid, $version);
                print'Recovered!<br>';
            }
            if(isset($_POST['Xpercentage'])&&isset($_POST['Ypercentage'])&&isset($_POST['userselpage'])){
                $userdel=1;
            }else{
                $userdel=0;
            }
            ?>
            <div class="row-fluid">
            <div class="span6">
            <select id="bookselect">
                <option value="electronic" selected>Electronic book</option>
                <option value="scanned" >Scanned book</option>
            </select><br>
            
            <?php
            print "<input type='hidden' id='documentid' value='".$documentid."'>"
                ."<input type='hidden' id='version' value='".$version."'>";
            //Electronic book dir
            $dir1 = '../elib/data/1048576/'.$documentid.'/'.$version.'/';
            print "<input type='hidden' id='electronicDir' value='".$dir1."'>";
            //Scanned book dir
            $dir2 = '../elib/data/1048576/'.$documentid.'/book/';
            print "<input type='hidden' id='scannedDir' value='".$dir2."'>";
            $c=count(glob($dir1. "*.jpg"));
            print'<select id="pageselect">';
            for($i=1;$i<$c;$i++){
                print'<option value="'.$i.'">Page '.$i.'</option>';
            }
            print'</select><br>';
            
            print'<label class="radio" style="display: inline-block;">
                <input type="radio" name="mode" id="userMode" value="user" checked>User Mode
                </label>&nbsp&nbsp
                <label class="radio" style="display: inline-block;">
                  <input type="radio" name="mode" id="editMode" value="editor"> Editor Mode
                </label>';
            if (!file_exists($dir1.'orig')){
                mkdir($dir1."orig"); 
            }
            if(count(glob($dir1.'orig/*.jpg'))==0){
                foreach (glob($dir1.'*.jpg') as $fn) {
                    copy($fn, $dir1."orig/" . basename($fn));
                }
            }
            if (!file_exists($dir2.'orig')){
                mkdir($dir2."orig"); 
            }
            if(count(glob($dir2.'orig/*.jpg'))==0){
                foreach (glob($dir2.'*.jpg') as $fn) {
                    copy($fn, $dir2."orig/" . basename($fn));
                }
            }
            print'<br><br><div><strong>Original Image</strong></div><br>';
            print'<img id="origImg" src="'.$dir1.'orig/1.jpg?='.rand().'" width="500" height="600">';
            print'</div>';
            
            print'<div class="span6">';
            ?>
            <ul class="nav nav-tabs" id="actionTab">
                <li class="active" ><a data-target="#crop" data-toggle="tab">crop</a></li>
                <li><a data-target="#recover" data-toggle="tab">recover</a></li>
            </ul>
            
            <div class="tab-content">
                <div class="tab-pane active" id="crop">
                    <div class="well">
            <?php
            print'<div id=eleCrop>';
            print'Xpercentage:';
            print'<input type="range" class="form-range" name="Xpercentage" min="1" max="100" value="30" class="slider" id="Xpercentage">';
            print' <span id="Xperpre">30</span>%';
            print'<br>';
            print'Ypercentage:';
            print'<input type="range" class="form-range" name="Ypercentage" min="1" max="100" value="30" class="slider" id="Ypercentage">';
            print' <span id="Yperpre">30</span>%';
            print'</div>';
            print'<div id=scannedCrop hidden>'
            . '<input type="checkbox" id="deskew" value="0" style="margin:0px"> Correct text alignment<br><br>'
            . '<input type="checkbox" id="cropFlag" value="0" style="margin:0px"> Crop Size<br>'
            . '<div id="cropContainer" style="margin-left:18px" hidden>'
            .   'top: <input type="range" class="form-range" min="1" max="100" value="10" class="slider" id="tPercentage">'
            .   '<span id="t">10</span>%<br>'
            .   'bottom: <input type="range" class="form-range" min="1" max="100" value="8" class="slider" id="bPercentage">'
            .   '<span id="b">8</span>%<br>'
            .   'left: <input type="range" class="form-range" min="1" max="100" value="3" class="slider" id="lPercentage">'
            .   '<span id="t">3</span>%<br>'
            .   'right: <input type="range" class="form-range" min="1" max="100" value="3" class="slider" id="rPercentage">'
            .   '<span id="r">3</span>%<br>'
            . '</div><br>'
            . '<input type="checkbox" id="sharpFlag" value="0" style="margin:0px"> Sharpness<br>'
            . '<div id="sharpContainer" style="margin-left:18px" hidden>'
            .   'Xpercentage: <input type="range" class="form-range" step="0.1" min="0.1" max="2" value="0.2" class="slider" id="xSharpen">'
            .   '<span id="xi">0.2</span>%<br>'
            .   'Ypercentage: <input type="range" class="form-range" step="0.1" min="0.1" max="2" value="0.9" class="slider" id="ySharpen">'
            .   '<span id="yi">0.9</span>%<br>'       
            . '</div><br>'
            . '<input type="checkbox" id="brightFlag" value="0" style="margin:0px"> Brightness<br>'
            . '<div id="brightContainer" style="margin-left:18px" hidden>'
            .   'Brightness: <input type="range" class="form-range" min="1" max="100" value="12" class="slider" id="brightPer">'
            .   '<span id="brightness">12</span>%<br>'
            .   'Contrast: <input type="range" class="form-range" min="1" max="100" value="18" class="slider" id="conPer">'
            .   '<span id="contrast">18</span>%<br>'   
            . '</div>'
            . '</div>';
            print'<br><div>Notice: Cropping is a CPU intensive task. Please wait a second befor initializing another crop.</div>';
            print'<div id="scannedNoti" hidden>If the quality of the scanned page is great in some aspects, you may uncheck the these options to maintain the high quality of the page.</div>';
            print'<br>
                <input class="btn" type="Submit" id="commitPage" value="Commit to page" />
                <input class="btn" type="Submit" id="commitBook" value="Commit to all pages of book" />';
            
            print'</div>';
            print'<div id="cropInfo"></div>';
            print'<img id="cropImage" style="display:none" width="500" height="600">';
            print'</div>';
            
            print'<div class="tab-pane" id="recover">';
            print'<div class="well">';
            print'<input class="btn" type="Submit" id="recoverPage" value="Recover to page" />
                <input class="btn" type="Submit" id="recoverBook" value="Recover to book" />';
            print'</div>';
            print'<div id="recoverInfo"></div>';
            print'</div>';
            print'</div></div>';

            $this->contentEnd();
            $this->htmlEndPage();
                
	} /* }}} */
}
?>
