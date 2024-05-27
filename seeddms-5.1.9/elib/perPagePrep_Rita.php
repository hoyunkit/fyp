<?php

// uncomment the following line for testing purposes
 pprep('./4', '1');

function pprep($docdir, $verID) {

//
// Please make sure that www-data has both xw privilleges for creating files in
// the current directory and the destination directory
// 
// All directory and files created by this module has the file ownership www-data:www-data.
//
// Improved for PHP operation efficiency 
//
//========================================
    $currentdir = getcwd();  // get the current directory 
    chdir($docdir); // change the directory to where the pdf ducument is located
    mkdir($verID); // create a subdirectory in the name of the document version to keep all page images
// $verID is also used as the directory name for individual page images
    shell_exec("pdftoppm -jpeg -r 80 -scale-to 880 " . $verID . ".pdf p && rename 's/p-//' *.jpg ");

    $a = glob('*.jpg'); // collect all image file names to an array
    foreach ($a as $fn) {
// for each of the file name, remove leading zeros, and move to the destination directory
        $b = str_replace('.jpg', '', $fn);
        $c = $verID . '/' . (string) ((int) ($b)) . '.jpg';
        rename($fn, $c);
    }

// if you like to delete the pdf file uncomment the next statement
// shell_exec("rm *.pdf");
// create single-page text files for the pdf document
// set locale and encoding for text processing - required by Tika

    $locale = 'en_US.UTF-8';
    setlocale(LC_ALL, $locale);
    putenv('LC_ALL=' . $locale);

//***  need to make sure that tika is in the specified location
    $data = shell_exec("java -jar ../../../seeddms-5.1.9/elib/tika-app-1.23.jar -h " . $verID . ".pdf");
//    $data = shell_exec("java -jar ../../../seeddms-5.1.9/elib/pdfbox-2.0.24.jar " . $verID . ".docx");
    file_put_contents($verID . "/pdfboxOut.txt", $data, FILE_APPEND);
    $rawdata = preg_replace('/<p\/>/', '', $data); //strip out outdated <p/> tags
    file_put_contents($verID . "/pdfboxRaw.txt", $rawdata, FILE_APPEND);
    $htmldata = preg_replace('/[\n\t]+/', '', $rawdata);  //strip out all tabs, line feeds, and carriage returns
    file_put_contents($verID . "/pdfboxHtml.txt", $htmldata, FILE_APPEND);

    $doc = new DOMDocument(); // convert raw text stream to DOM objects
    $doc->loadHTML($htmldata);
    $pages = getChildObj($doc, 'div', 'page'); // place all page objects in an array

    $ovlptxt = ''; // $ovlptxt is the last few characters for each page place at the beginning of the next page  

    foreach ($pages as $k => $page) {  // Loop thru each page which is a DOM child of the HTML Document
        $pgfs = getChildObj($page, 'p', ''); // get all paragraph objects in a page as an array
        // remove page numbers and empty paragraphs for all paragraphs in a page
        $dc = cleanPage($pgfs);
        if (($dc == "")) {
            $ovlptxt = "";
        } else {
            $dc = $ovlptxt . preg_replace('/^ +/u', "", $dc);
            file_put_contents($verID . "/" . strval($k + 1) . ".txt", $dc, FILE_APPEND);
        }
        if ($ovlptxt != "") { // create a file name prepend.txt to record the overlapping characters for later uses
        // // write the overlap texts on each page to prepend.txt
//            file_put_contents($verID . "/prepend.txt", strval($k + 1) . " " . $ovlptxt . "\n", FILE_APPEND); 
            file_put_contents($verID . "/prepend.txt", strval($k + 1) . " " . mb_strlen ($ovlptxt) . "\n", FILE_APPEND);
        }
        $ovlptxt = getOvlp($dc);
        if (preg_match("/[\!\"\',\.\:;\?\[\]\{\}\(\)\“\”。、，：；（）「」？!<>《》\ \s]+$/u", $dc) === true) {
            $ovlptxt = ""; // current page end with a symbol
        }
    }
    chdir($currentdir); // return to the directory of the calling code module.
}

function getOvlp($dc) { // get the trailing overlapping characters for the next page
    $t = mb_substr($dc, -4, 4, 'utf-8');
    $a = preg_split("/[\!\"\',\.\:;\?\[\]\{\}\(\)\“\”。、，：；（）「」？!<>《》\ \s]+/u", $t);
    return end($a); // return only the last element in the split array
}

// The following functions are needed for text extraction processing
function getChildObj(&$parentNode, $tagName, $className) {  // if no classname, just provide an empty string
// This function extracts all child DOM element and place them in an array    
    $nodes = array();
    $childNodeList = $parentNode->getElementsByTagName($tagName);
    for ($i = 0; $i < $childNodeList->length; $i++) {
        $temp = $childNodeList->item($i);
        if ($className == '') {
            $nodes[] = $temp;
        } elseif
        (stripos($temp->getAttribute('class'), $className) !== false) {
            $nodes[] = $temp;
        }
    }
    return $nodes; // return an array of all child nodes
}

function cleanPage($pgfs) { // $pgfs is an array of DOM paragraph in a tika extracted page from a pdf document
// remove page numbers, empty paragraph and decode the text to UTF-8 characters
    $output = '';
    foreach ($pgfs as $p) {
        $txt = utf8_decode($p->nodeValue);
        switch ($txt) { // strip out empty paragraphs and page numbers
            case '':  // empty paragraph: do nothing (remove)
                break;
            case (preg_match('/^[0-9○一二三四五六七八九十]{1,4} +$/u', $txt) ? true : false):  // paragraph for page number only: 1~9999 一~九九九九, do nothing (remove)               
                break;
            case (preg_match('/^ \- [0-9○一二三四五六七八九十]{1,4} +\- +/u', $txt) ? true : false):  // paragraph for a - nnn - formatted page number only: 1~9999 一~九九九九, do nothing (remove)               
                break;
            default:  // parse each paragraph. Please beware that some paragraph may have only one character.
                $txt = preg_replace('/ +$/u', "", $txt); // remove trailing space
                $output = $output . $txt; // return the text string of all paragraphs in a page
        }
    }
    return $output;
}
