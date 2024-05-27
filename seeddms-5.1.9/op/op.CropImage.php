<?php
include("../inc/inc.Settings.php");
include("../inc/inc.LogInit.php");
include("../inc/inc.Utils.php");
include("../inc/inc.Language.php");
include("../inc/inc.Init.php");
include("../inc/inc.Extension.php");
include("../inc/inc.DBInit.php");
include("../inc/inc.ClassUI.php");
include("../inc/inc.ClassAccessOperation.php");
include("../inc/inc.Authentication.php");


$bookSelect = (isset($_POST["bookSelect"]))?$_POST["bookSelect"]:null;
$pageSelect = (isset($_POST["pageSelect"]))?$_POST["pageSelect"]:null;
$mode = (isset($_POST["mode"]))?$_POST["mode"]:null;
$x = (isset($_POST["x"]))?$_POST["x"]:null;
$y = (isset($_POST["y"]))?$_POST["y"]:null;
$documentid=$_POST["documentid"];
$version=$_POST["version"];
$test=$_POST["test"];
$data=$_POST["data"];

if($bookSelect=="electronic"){
    $dir = '../elib/data/1048576/'.$documentid.'/'.$version;
}else{
    $dir = '../elib/data/1048576/'.$documentid.'/book';
}
delTestImage($dir);
if($_POST["action"]=="commitBook"){
    echo commitResize($bookSelect, $dir, $mode, $data);
}else if($_POST["action"]=="commitPage"){
    echo commitPage($bookSelect, $dir, $pageSelect, $mode, $data, $test);
}else if($_POST["action"]=="recoverPage"){
    echo pageRecover($dir, $pageSelect, $mode);
}else if($_POST["action"]=="recoverBook"){
    echo bookRecover($dir, $mode);
}
function commitPage($bookSelect, $dir, $pageN, $mode, $data, $test){
    $pageIn = $dir . "/orig/" . $pageN . ".jpg";
    if($mode=="user" && !$test){
        $pageOut = $dir . "/" .$pageN . ".jpg";
    }else if($mode=="user" && $test){
        $pageOut = $dir . "/" . $pageN . "c.jpg";
    }else if($mode=="editor" && !$test){
        $pageOut = $dir . "/e/" . $pageN . ".jpg"; 
    }else if($mode=="editor" && $test){
        $pageOut = $dir . "/" . $pageN . "e.jpg";
    }
    if($bookSelect==="electronic"){
        presize($pageIn, $pageOut, $data["x"], $data["y"]);
    }else{
        precrop($pageIn, $pageOut, $data);
    }
    return $pageOut;
}
    function deskew($fin, $fout) { // correct the text alignment in scanned images
        // $fin is input file name
        // $fout is the deskewed output file name
        // $fin in can be the same as $fout
        // we are using the default setting of this command
        // Make sue that you place "deskew" executable in elib directory and assign
        // proper Ownership and file Mode to the executable
        $command="../elib/deskew -o " . $fout . " " . $fin;
//        shell_exec($command);
        shell_exec($command);
        // for debugging purposes:
        // return var_dump(shell_exec("./deskew -o " . $fout . " " . $fin . " 2>&1"));
    }
    function presize($from, $to, $x, $y) {
        // $x is a string for the border width in percentage for x dimension beyond the text area.
        // $y is a string for the border width in percentage for y dimension beyond the text area.
        // after execution of this function a cropped image $pageN + "c.jpg" is created in the same directory
        // please remove the cropped image as needed, the delTestImage function can do the job
        shell_exec("convert " . $from . " -trim -bordercolor white -border " . $x . "%x" . $y . "% +repage " . $to);
    }
    function precrop($pageIn, $pageOut, $data){
        if(isset($data["deskew"])){
            deskew($pageIn, $pageOut);
            $pageIn=$pageOut; //use the output for next input
        }
        $cropCommand=$brightCommand=$sharpCommand="";
        if(isset($data["t"]) && isset($data["b"]) && isset($data["l"]) && isset($data["r"])){
            $cropCommand=scannedCrop($pageIn, $data["t"], $data["b"], $data["l"], $data["r"]);
        }
        if(isset($data["brightness"]) && isset($data["contrast"]) ){
            $brightCommand=brghtCntrst($data["brightness"], $data["contrast"]);
        }
        if(isset($data["xi"]) && isset($data["yi"])){
            $sharpCommand=sharpen($data["xi"], $data["yi"]);
        }
        //convert -crop " . $xp . "%x" . $yp . "%+" . $ox . "+" . $oy . " +repage " . $fin . " " . $fout
        $command="convert " . $cropCommand . $sharpCommand . $brightCommand . $pageIn . " " . $pageOut;
        shell_exec($command);
    }
    function sharpen($xi = '0.2', $yi = '0.9') {
    // adjust brightness and contrast for an image
    // $fin is input file name
    // $fout is the created output file name with the adjusted brightness and contrast
    // $xi a number from 0.1 to 2, default 0 
    // $yi a number as a string from 0.1 to 2 default 0.2x0.9
    //shell_exec("convert -sharpen " . $xi . "x" . $yi . " " . $fin . " " . $fout);
    // for debugging purposes:
    // return var_dump(shell_exec("convert -sharpen ". $xi. "x" . $yi ." " . $fin . " " . $fout. " 2>&1"));
        return " -sharpen " . $xi . "x" . $yi . " ";
    }
    
    function brghtCntrst($fin, $fout, $brightness = '12', $contrast = '18') {
        // adjust brightness and contrast for an image
        // $fin is input file name
        // $fout is the created output file name with the adjusted brightness and contrast
        // $contrast an interger as string from 1 to 100, default 12
        // $brightness an interger as string from 1 to 100, default 18
        // shell_exec("convert -brightness-contrast " . $brightness . "x" . $contrast . " " . $fin . " " . $fout);
        // for debugging purposes:
        // return var_dump(shell_exec("convert -brightness-contrast " . $brightness . "x" . $contrast . " " . $fin . " " . $fout . " 2>&1"));
        return " -brightness-contrast " . $brightness . "x" . $contrast . " ";
    }
    
//    function precrop($docdir, $verID, $pageN, $t, $b, $l, $r) {
//    // $pageN is a string for the document page number for the test page, please adjust the file reference if you have the file name
//    // $t is a integer for the percentage of the top margin beyond the text area relative to the entire height.
//    // $b is a integer for the percentage of the bottom margin beyond the text area relative to the entire height.
//    // $l is a integer for the percentage of the left margin beyond the text area relative to the entire width.
//    // $r is a integer for the percentage of the right margin beyond the text area relative to the entire width.  
//    // after execution of this function a cropped image $pageN + "c.jpg" is created in the same directory
//    // please remove the cropped image as needed, the delTestImage function can do the job
//    // 
//        $currentdir = getcwd();
//        $fn = $docdir . "/" . $verID . "/" . $pageN;
//        //    list($w, $h, $tp, $a) = getimagesize($fn); // get teh width, height, image type, and attributes of the image
//        //    $yp = 100 - $t - $b;
//        //    $xp = 100 - $r - $l;
//        //    $ox = round((100 - $l) * $w / 100);
//        //    $oy = round((100 - $t) * $h / 100);
//        //    shell_exec("convert -crop " . $xp . "%x" . $yp . "%+" . $ox . "+" . $oy . " +repage " . $fn . ".jpg " . $fn . "c.jpg");
//        // for debugging purposes:
//        // return var_dump(shell_exec("convert -crop " . $xp . "%x" . $yp . "%+" . $ox . "+" . $oy . " +repage ". $fn .".jpg ". $fn . "c.jpg");
//        scannedCrop($fn, $fn."c.jpg", $t, $b, $l, $r);
//        chdir($currentdir); // return to the directory of the calling module.
//        return " -crop " . $xp . "%x" . $yp . "%+" . $ox . "+" . $oy . " +repage ";
//    }
    
    function scannedCrop($fin, $t, $b, $l, $r){
        // $fin is input file name
        // $fout is the deskewed output file name
        // $t is a integer for the percentage of the top margin beyond the text area relative to the entire height.
        // $b is a integer for the percentage of the bottom margin beyond the text area relative to the entire height.
        // $l is a integer for the percentage of the left margin beyond the text area relative to the entire width.
        // $r is a integer for the percentage of the right margin beyond the text area relative to the entire width.  
        // after execution of this function a cropped image $pageN + "c.jpg" is created in the same directory
        // please remove the cropped image as needed, the delTestImage function can do the job
        list($w, $h, $tp, $a) = getimagesize($fin); // get teh width, height, image type, and attributes of the image
        $yp = 100 - $t - $b;
        $xp = 100 - $r - $l;
        $ox = round($l * $w / 100);
        $oy = round($t * $h / 100);

        //shell_exec("convert -crop " . $xp . "%x" . $yp . "%+" . $ox . "+" . $oy . " +repage " . $fin . " " . $fout);
        // for debugging purposes:
        // return var_dump(shell_exec("convert -crop " . $xp . "%x" . $yp . "%+" . $ox . "+" . $oy . " +repage ". $fin . " " . $fout . " 2>&1");    
        return " -crop " . $xp . "%x" . $yp . "%+" . $ox . "+" . $oy . " +repage ";
    }
    function delTestImage($dir) {
        //   This function deltes the test image created in the previous image cropping function
        //    $pageN is a string for the document page number for the test image page
        //    remove the cropped image after use
        $currentdir = getcwd();
        $cmd = "rm ".$dir . "/*c.jpg";
        shell_exec($cmd);
        $cmd = "rm ".$dir. "/*e.jpg";
        shell_exec($cmd);
        //unlink($fn);
        chdir($currentdir); // return to the directory of the calling module.
    }
        
    function commitResize($bookSelect, $dir, $mode, $data) {
    // create a subdirectory in the name of the document version to keep all page images
    // $verID is also used as the directory name for individual page images
        $currentdir = getcwd();// get the current directory
        $from=$dir . "/orig/*.jpg";
        $arr=array();
        if($mode=="user"){
            $to = $dir . "/";
        }else{
            if(!file_exists($dir . "/e")){
                mkdir($dir . "/e");
            }
            $to = $dir . "/e/";
        }
        if($bookSelect=="electronic"){
            foreach (glob($from) as $fn) {
                presize($fn, $to . basename($fn), $data["x"], $data["y"]);
//                shell_exec("convert " . $fn . " -trim -bordercolor white -border " . $x . "%x" . $y . "% " . $to. "/". $fn);
            }
        }else{
            foreach (glob($from) as $fn) {
                array_push($arr, $fn);
                $res=explode(".",end(explode("/",$fn))); // $fn => {"xx","jpg"}
                if ($res[0]%2==1 && $_POST["odd"]) // check whether page num is odd
                {
                   precrop($fn, $to . basename($fn), $data);
                }
                if ($res[0]%2==0 && !$_POST["odd"]) // check whether page num is odd
                {
                   precrop($fn, $to . basename($fn), $data);
                } 
            }
        }
        chdir($currentdir); // return to the directory of the calling module.
        return true;
    }
        
        function bookRecover($dir, $mode){
            try{
                $currentdir = getcwd();
                chdir($dir);
                $a = glob('orig/*.jpg');
                if($mode=="user") {
                    foreach ($a as $fn) {
                        $res=explode(".",end(explode("/",$fn))); // $fn => {"xx","jpg"}
                        if ($_POST["odd"]==-1)
                        {
                            copy($fn, basename($fn));
                            continue;
                        }
                        if ($res[0]%2==1 && $_POST["odd"]) // check whether page num is odd
                        {
                            copy($fn, basename($fn));
                        }
                        if ($res[0]%2==0 && !$_POST["odd"]) // check whether page num is odd
                        {
                            copy($fn, basename($fn));
                        }
                    }
                }else if($mode=="editor"){
                    foreach ($a as $fn) {
                        $res=explode(".",end(explode("/",$fn))); // $fn => {"xx","jpg"}
                        if ($_POST["odd"]==-1)
                        {
                            copy($fn, basename($fn));
                            continue;
                        }
                        if ($res[0]%2==1 && $_POST["odd"]) // check whether page num is odd
                        {
                            copy($fn, "e/".basename($fn));
                        }
                        if ($res[0]%2==0 && !$_POST["odd"]) // check whether page num is odd
                        {
                            copy($fn, "e/".basename($fn));
                        }
                    }
                }
                chdir($currentdir);
            }catch(Exception $e){
                return false;
            }
            return true;
        }
        
        function pageRecover($dir, $pageN, $mode){
            try{
                $currentdir = getcwd();
                chdir($dir);
                if($mode=="user"){ //have original copies
                    copy("orig/" . $pageN . ".jpg", $pageN . ".jpg");
                }else if($mode=="editor"){
                    copy("orig/" . $pageN . ".jpg", "e/".$pageN . ".jpg");
                }
                chdir($currentdir);
            }catch(Exception $e){
                return false;
            }
            return true;
        }


       

function testDeskew($fin) { // a sample function showing how to use deskew(), can be removed from production system
    $currentdir = getcwd();  // get the current directory 

    $fout = "." . strtok($fin, '.') . "skew.jpg";
    $msg = deskew($fin, $fout);
    echo $fin . "<br>";
    echo $fout . "<br>";
    echo "processing end<br>";
    echo $msg;
    echo "<br>";

    chdir($currentdir); // return to the directory of the calling code module.
}


