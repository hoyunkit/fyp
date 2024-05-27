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
    
//        function presize($docdir, $verID, $pageN, $x, $y) {
//            // $pageN is a string for the document page number for the test page, please adjust the file reference if you have the file name
//            // $x is a string for the reduction target in percentage for x dimension
//            // $y is a string for the reduction target in percentage for y dimension.
//            // after execution of this function a cropped image $pageN + "c.jpg" is created in the same directory
//            // please remove the cropped image as needed, the delTestImage function can do the job
//            // 
//            $currentdir = getcwd();
//            //echo $currentdir;
//            $fn = $docdir . "/" . $verID . "/" . $pageN;
//            //rita
//            $cmd="convert " . $fn . ".jpg -gravity Center -crop " . $x . "x" . $y . "% " . $fn . "c.jpg";
//            shell_exec($cmd);
//            
//            chdir($currentdir); // return to the directory of the calling module.
//        }
        function presize($docdir, $verID, $pageN, $x, $y) {
            // $pageN is a string for the document page number for the test page, please adjust the file reference if you have the file name
            // $x is a string for the border width in percentage for x dimension beyond the text area.
            // $y is a string for the border width in percentage for y dimension beyond the text area.
            // after execution of this function a cropped image $pageN + "c.jpg" is created in the same directory
            // please remove the cropped image as needed, the delTestImage function can do the job
            // 
            $currentdir = getcwd();
        //    echo $currentdir;
            $fn = $docdir . "/" . $verID . "/" . $pageN;
            //tom
            shell_exec("convert " . $fn . ".jpg -trim -bordercolor white -border " . $x . "%x" . $y . "% " . $fn . "c.jpg");
            chdir($currentdir); // return to the directory of the calling module.
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
                //unlink($fn);
            
            chdir($currentdir); // return to the directory of the calling module.
        }

        function commitResize($docdir, $verID, $x, $y) {
        // create a subdirectory in the name of the document version to keep all page images
        // $verID is also used as the directory name for individual page images
        // 
            $currentdir = getcwd();  // get the current directory
            chdir($docdir . "/" . $verID); // change the directory to where the ducument images are stored
        //    echo (glob('orig/*.jpg'));
        //    
            // Create the orig folder to store the images from the pdf document
            if (!file_exists('orig')) {
                mkdir("orig"); // make a directory for the original images under the $verID folder
        //        echo "------------------- created a new directory named orig ------------------";
            } else { // The following code is to restore the original images to the document folder to
                     // avoid consecutive croping operations done on the cropped images
                $a = glob('orig/*.jpg'); // collect all image file names to an array in the orig directory
                if (count($a) > 0) {
                    foreach ($a as $fn) {
                        rename($fn,basename($fn) );// move original images from the orig folder back to working storage
                    }
                }
            }

            foreach (glob('*.jpg') as $fn) {
                rename($fn, "orig/" . $fn);  // move an image file into the orig folder to preserve the original image
                //rita
//                shell_exec("convert " . "./orig/" . $fn . " -gravity Center -crop " . $x . "x" . $y . "% " . $fn);
                //tom
                shell_exec("convert " . "./orig/" . $fn . " -trim -bordercolor white -border " . $x . "%x" . $y . "% " . $fn);
            }
            //shell_exec("chmod 771 -R " . '*.jpg');
            chdir($currentdir); // return to the directory of the calling module.
        }
        
        function imgRecover($docdir, $verID){
            $currentdir = getcwd();
            chdir($docdir . "/" . $verID);
            $a = glob('orig/*.jpg'); // collect all image file names to an array in the orig directory
            if (count($a) > 0) {
                foreach ($a as $fn) {
                    rename($fn,basename($fn) );// move original images from the orig folder back to working storage
                }
            }
            chdir($currentdir);
        }
        
        function js(){
            header('Content-Type: application/javascript; charset=UTF-8');
            print'var Xslider = document.getElementById("Xpercentage");';
            print'var Yslider = document.getElementById("Ypercentage");';
            print'if ( window.history.replaceState ) {
                    window.history.replaceState( null, null, window.location.href );
                  }';
                print'$(document).ready(function(){'
                    .'$("#pageselect").on("change", function(){'
                        .'var dir=document.getElementById("dir").value;'
                        .'var select=document.getElementById("pageselect").selectedOptions[0].value;'
                        .'document.getElementById("origImg").src=dir+select+".jpg?=rand()";'
                        .'document.getElementById("userselpage").value=select;'
                    .'});'
                    .'Xslider.oninput = function() {'
                        . 'document.getElementById("Xperpre").innerHTML=this.value;};'
                    .'Yslider.oninput = function() {'
                        . 'document.getElementById("Yperpre").innerHTML=this.value;};'
 
                .'});';
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
                $this->delTestImage('../elib/data/1048576/'.$documentid, $version);
               
                if(isset($_POST['Commit'])&&isset($_POST['XCommit'])&&isset($_POST['YCommit'])){
                    //clearstatcache();
                    $this->commitResize('../elib/data/1048576/'.$documentid, $version, $_POST['XCommit'], $_POST['YCommit']);
                    print'Apply to all pages. Xpercentage:'.$_POST['XCommit'].' Ypercentage:'.$_POST['YCommit'].'<br>';
                    $_POST['Commit']=0;
                }
                
                if(isset($_POST['Recover'])){
                    //clearstatcache();
                    $this->imgRecover('../elib/data/1048576/'.$documentid, $version);
                    print'Recovered!<br>';
                }
                
                
                
                $dir = '../elib/data/1048576/'.$documentid.'/'.$version.'/';
                print "<input type='hidden' id='dir' value='".$dir."'>";
                $c=count(glob($dir. "*.jpg"));
                print'<div class="span6">';
                print'<select id="pageselect">';
                
                for($i=1;$i<$c;$i++){
                    print'<option value="'.$i.'">Page '.$i.'</option>';
                }
                
                print'</select>';
                print'<form name="Form" method="post" action="">';
                print'<input type="hidden" name="Recover" id="Recover"/>'
                        . '<input class="btn" type="Submit" value="Recover" />'
                        . '</form>';
                
                if(isset($_POST['Xpercentage'])&&isset($_POST['Ypercentage'])&&isset($_POST['userselpage'])){
                    $userdel=1;
                }else{
                    $userdel=0;
                }
                
                if($userdel){
                    print'<img id="origImg" src="'.$dir.$_POST['userselpage'].'.jpg?='.rand().'" width="500" height="600">';
                }else{
                    print'<img id="origImg" src="'.$dir.'1.jpg?='.rand().'" width="500" height="600">';
                }
                
                print'</div>';
                print'<div class="span4">';
                
                print'<form name="Form" method="post" action="">';
                
                if($userdel){
                    $xdef=$_POST['Xpercentage'];
                    $ydef=$_POST['Ypercentage'];
                }else{
                    $xdef=30;
                    $ydef=30;
                }
                print'Xpercentage:';
                print'<input type="range" class="form-range" name="Xpercentage" min="1" max="100" value="'.$xdef.'" class="slider" id="Xpercentage">';
                print' <span id="Xperpre">'.$xdef.'</span>%';
                print'<br>';
                print'Ypercentage:';
                print'<input type="range" class="form-range" name="Ypercentage" min="1" max="100" value="'.$ydef.'" class="slider" id="Ypercentage">';
                print' <span id="Yperpre">'.$ydef.'</span>%';
                /*print'Ypercentage:<select name="Ypercentage" id="Ypercentage">';
                                    print'<option value=100>100</option>';
                                    print'<option value=95>95</option>';
                                    print'<option value=90>90</option>';
                                    print'<option value=85>80</option>';
                                    print'<option value=80>80</option>';
                                    print'<option value=75>75</option>';
                                    print'<option value=70>70</option>';
                                    print'</select>'; */
                        //Xpercentage:<input type="text" name="Xpercentage" id="Xpercentage" />
                        //Ypercentage:<input type="text" name="Ypercentage" id="Ypercentage" />
                if($userdel){
                    print'<input type="hidden" name="userselpage" id="userselpage" value="'.$_POST['userselpage'].'">';
                    
                }else{
                    print'<input type="hidden" name="userselpage" id="userselpage" value="1">';
                }
               
                print'<br>
                    <input class="btn" type="Submit" value="Submit" /></form>';
                if($userdel){
                    
                    $x=$_POST['Xpercentage'];
                    $y=$_POST['Ypercentage'];
                    $userselpage=$_POST['userselpage'];
                    //print $x.", ".$y."<br>";
                    $this->presize('../elib/data/1048576/'.$documentid, $version, $userselpage, $x, $y);
                    print'<img src="'.$dir.$userselpage.'c.jpg" width="400" height="500">';
                    print'<form name="Form" method="post" action="">
                        <input type="hidden" name="XCommit" id="XCommit" value="'.$x.'"/>
                        <input type="hidden" name="YCommit" id="YCommit" value="'.$y.'"/>
                        <input type="hidden" name="Commit" id="Commit" value="1"/>
                    <input class="btn" type="Submit" value="Commit" /></form>';
                    //$this->delTestImage('../elib/data/1048576/'.$documentid, $version, 2);
                   
                }

                print'</div>';

		$this->contentEnd();
		$this->htmlEndPage();
                
	} /* }}} */
}





?>

