<?php

// uncomment the following line for testing purposes
 pprep('./4', '1', [true, NULL, 1]);
// pprep('./4', '4', [FALSE, NULL, 1]);

/*$headerInfo[3]
 * [0] => (bool) has page number
 * [1] => (array) header array
 * [2] => (int) 1 if header is before page num, 2 if header is after page num
*/

function pprep($docdir, $verID, $headerInfo=NULL) {
    $currentdir = getcwd();  // get the current directory 
    chdir($docdir); // change the directory to where the pdf ducument is located
    if(!file_exists($verID)){
        mkdir($verID); // create a subdirectory in the name of the document version to keep all page images
    }
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
    $line2DArr=$indexArr=array();
    processPdf($verID, $headerInfo, $line2DArr, $indexArr);
    $paraArr=processWord($verID);
    $paraRes=printPara($indexArr, $paraArr, $verID);
    printIndex($paraRes, $verID);
    $para2DArr=getPara2DArr($paraRes);
    printLines($line2DArr, $paraRes, $verID);
    chdir($currentdir); // return to the directory of the calling code module.
}

function processPdf($verID, $headerInfo, &$line2DArr, &$indexArr){
    if(!file_exists($verID.'/d')){
        mkdir($verID.'/d');
    }
    if(file_exists($verID . ".pdf")){
        $pdfdata = shell_exec("java -jar ../../../seeddms-5.1.9/elib/pdfbox-2.0.24.jar " . $verID . ".pdf"); //pdfbox-2.0.24
        file_put_contents($verID . "/pdfBox.txt", $pdfdata);
        $doc = new DOMDocument();
        @$doc->loadHTML(mb_convert_encoding($pdfdata, 'HTML-ENTITIES', 'UTF-8'));
        $pages = getChildObj($doc, 'div', 'page'); // place all page objects in an array  
        foreach ($pages as $k => $page) {  // Loop thru each page which is a DOM child of the HTML Document
            $lineArr=$indexStr=null;
            $pgfs = getChildObj($page, 'p', ''); // get all paragraph objects in a page as an array
            // remove page numbers and empty paragraphs for all paragraphs in a page
            cleanPage($pgfs, $headerInfo, $lineArr, $indexStr); //the content of page
            $line2DArr[]=$lineArr;
            $indexArr[]=$indexStr;
        }
    }
}

function getPara2DArr($paraRes){
    $para2DArr=array();
    foreach ($paraRes as $page) {
        $paras= explode("\n", $page);
        $para2DArr[]=$paras;
    }
    return $para2DArr;
}

function printIndex($paraRes,$verID){
    $ovlptxt=''; // $ovlptxt is the last few characters for each page place at the beginning of the next page
    foreach ($paraRes as $k => $value) {
        $index=preg_replace('/\n/u', '', $value);
        $ovlptxt=processOvlptxt($verID, $index, $k + 1, $ovlptxt);
    }
}

function processWord($verID){
    if(!file_exists($verID.'/w')){
        mkdir($verID.'/w');
    }
    if(file_exists($verID . ".docx")){
//        $origWord = shell_exec("java -jar ../../../seeddms-5.1.9/elib/tika-app-1.23.jar -h " . $verID . ".docx");
//        $origWord=wordRegex($origWord);
//        file_put_contents($verID ."/origWord.txt",$origWord);
//        $doc1 = new DOMDocument(); // convert raw text stream to DOM objects
//        @$doc1->loadHTML(mb_convert_encoding($origWord, 'HTML-ENTITIES', 'UTF-8'));
//        $orig = getChildObj($doc1, 'p', '');
        $stripWord = shell_exec("java -jar ../../../seeddms-5.1.9/elib/tika-app-1.23.jar --config=../../../seeddms-5.1.9/elib/tika-config.xml -h " . $verID . ".docx");
        $stripWord = wordRegex($stripWord);
        file_put_contents($verID . "/stripWord.txt",$stripWord);
        $doc2 = new DOMDocument(); // convert raw text stream to DOM objects
        @$doc2->loadHTML(mb_convert_encoding($stripWord, 'HTML-ENTITIES', 'UTF-8')); 
        $strip = getChildObj($doc2, 'p', '');
//        $image=array_slice($orig, count($strip));
//        $imageArr=cleanPara($image);
        $paraArr = cleanPara($strip);
        file_put_contents($verID . "/paraArr.txt",$paraArr);
    }
    return $paraArr;
}

function getChildObj(&$parentNode, $tagName, $className) {  // if no classname, just provide an empty string
// This function extracts all child DOM element and place them in an array    
    $nodes = array();
    $childNodeList = $parentNode->getElementsByTagName($tagName);
    for ($i = 0; $i < $childNodeList->length; $i++) {
        $temp = $childNodeList->item($i); //page
        if ($className == '') {
            $nodes[] = $temp;
        } elseif
        (stripos($temp->getAttribute('class'), $className) !== false) {
            $nodes[] = $temp;
        }
    }
    return $nodes; // return an array of all child nodes, pages
}

function processOvlptxt($verID, $txt, $pageNum, $ovlptxt){
    if ($txt == "") {
        $ovlptxt = "";
    } else {
        file_put_contents($verID . "/" . strval($pageNum) . ".txt", $ovlptxt.$txt);
    }
    if ($ovlptxt != "") { // create a file name prepend.txt to record the overlapping characters for later uses
    // // write the overlap texts on each page to prepend.txt
        file_put_contents($verID . "/prepend.txt", strval($pageNum) . " " . mb_strlen($ovlptxt) . "\n");
    }
    $ovlptxt = getOvlp($txt);
    if (preg_match("/[\!\"\',\.\:;\?\[\]\{\}\(\)\“\”。、，：；（）「」？!<>《》\ \s]+$/u", $txt) === true) {
        $ovlptxt = ""; // current page end with a symbol
    }
    return $ovlptxt;
}

function getOvlp($dc) { // get the trailing overlapping characters for the next page
    $t = mb_substr($dc, -4, 4, 'utf-8');
    $a = preg_split("/[\!\"\',\.\:;\?\[\]\{\}\(\)\“\”。、，：；（）「」？!<>《》\ \s]+/u", $t);
    return end($a); // return only the last element in the split array
}

function cleanPara($paras) { // $pgfs is an array of DOM paragraph in a tika extracted page from a pdf document
// remove page numbers, empty paragraph and decode the text to UTF-8 characters
    foreach ($paras as $para) { //paragraph
        $txt = $para->nodeValue;
        if($catalogArr=catalogRegex($txt)){
            $output= $output?array_merge($output, $catalogArr): $catalogArr;
            //file_put_contents("/home/e-library/NetBeansProjects/e-library/seeddms-5.1.9/elib/5/2/indexContent.txt",$output);
        }else if(textRegex($txt) || pageNumRegex($txt)){
            continue;
        }else {
            $txt = preg_replace('/[\x{2004}\x{0020}\x{3000}\t\n]+$/u', "", $txt); //remove space in tail
            $txt = preg_replace('/\n/u', "", $txt); //remove newline in para
            $txt = preg_replace('/[\x{2004}\x{3000}\x{00a0}\t]/u', " ", $txt); //syncronize space
            $txt=recoverIndent($txt); 
            $txt=preg_replace('/([^\x{2004}\x{3000}\x{00a0}\x{0020}\t]{1,})[\x{2004}\x{3000}\x{00a0}\x{0020}\t]{2}/u', '$1 ', $txt); //reduce space
            $txt=preg_replace('/([^\x{2004}\x{3000}\x{00a0}\x{0020}\t]{1,})[\x{2004}\x{3000}\x{00a0}\x{0020}\t]{2}/u', '$1 ', $txt);
            $output[]=$txt;
        }
    }
    return $output;
}

function cleanPage($pgfs, $headerInfo, &$lineArr, &$indexStr){
    $content="";
    foreach ($pgfs as $p) { //$pgfs=page, $p=line
        $txt = $p->nodeValue; //$txt=line text
        if(textRegex($txt)){
            continue;
        }
        $txt=recoverIndent($txt);
        $txt=preg_replace('/ *(\.){7,} */s', '..........................................', $txt);
        $txt = preg_replace('/[\x{2004}\x{0020}\x{3000}\t\n]+$/u', "", $txt);
        $txt = preg_replace('/[\x{2004}\x{00a0}\t]/u', " ", $txt);
        $content .= $txt."\n";
    }
    $content=rtrim($content, "\n"); //remove the newline at the end of page
    $content=processHeader($content, $headerInfo); //remove header
    $lineArr= explode("\n", $content);
    $indexStr = preg_replace('/\n/', '', $content);
}

function processHeader($content, $headerInfo){
    $pageNum=$headerInfo[0]?'[0-9○一二三四五六七八九十]{1,4}':'';
    if($headerInfo[1]!=null){
        $headerStr="(";
        foreach ($headerInfo[1] as $index => $value) {
            $headerStr.= $value;
            if($index<count($headerInfo[1])-1){
                $headerStr.='|';
            }else{
                $headerStr.=')';
            }
        }
    }else{
        $headerStr="";
    }
    $seq=$headerInfo[2];
    $content=headerRegex($content, $pageNum, $headerStr, $seq);
    return $content;
}

function wordRegex($txt){ 
    $txt = preg_replace('/<h([\d]+)([^>]*)>/s', '<p>', $txt); //change h tag to p tag
    $txt=preg_replace('/<\/h([\d]+)>/s', '</p>', $txt);
    $txt=preg_replace('/<p class="header">((<p>|<p class="footer">)([^>]*)<\/p>)*<\/p>/s','', $txt); //remove strange header and footer
    $txt=preg_replace('/<p class="header">([^>]*)<\/p>/u', '', $txt); //remove general header
    $txt=preg_replace('/<p class="footer">([^>]*)<\/p>/u', '', $txt); //remove general footer
    $txt=preg_replace('/(<table)(.*)(<\/table>)/s', '', $txt);
    $txt = preg_replace('/<p\/>/', '', $txt);
    return $txt;
}

function textRegex($txt){
    switch($txt){
        case '':  // empty paragraph: do nothing (remove)
            break;
        case (preg_match('/^[\x{2004}\x{0020}\x{3000}\t\n\-]+$/u', $txt)? true : false):
            break;
        default:
            return false;
    }
    return true;
}

function pageNumRegex($txt){
    switch ($txt) { // strip out empty paragraphs and page numbers
        case (preg_match('/^ {0,4}[0-9○一二三四五六七八九十]{1,4} {0,4}+$/u', $txt)?true:false):  // paragraph for page number only: 1~9999 一~九九九九, do nothing (remove)               
            break;
        case (preg_match('/^ {0,4}\- [0-9○一二三四五六七八九十]{1,4} +\- {0,4}/u', $txt)?true:false):  // paragraph for a - nnn - formatted page number only: 1~9999 一~九九九九, do nothing (remove)               
            break;
        default:
            return false;
    }
    return true;
}

function catalogRegex($txt){
    $catalog='(目錄|目次)';
    if($txt !="" && preg_match('/^(.*)'.$catalog.'\n/u', $txt)){
        $txt=preg_replace('/[\t]+/', '..........................................', $txt);
        $arr=preg_split('/\n/', $txt);
        return array_filter($arr, 'strlen');
    }else{
        return false;
    }
}

function recoverIndent($txt){
    $txt=preg_replace('/^ {2}([^ ]{1,})/u', '　　$1', $txt); //change space to indent in first line
    return $txt;
}

function findLines($lines, $paras){
    $i=$j=0;
    $text='';
    $bufArr=array();
    $line=$lines[0];
    while($paras !==''){ //&& $j<=count($lines)
        if($j===18){
            $a=1;
        }
        if(($hitBuf=checkBuf2($bufArr, $paras, $text))!==true){
            if(($hitLine=seperateStr2($line, $paras, $text))!==true && $line!==''){
                $bufArr[]=$line;
            }
        }
        if(($hitSpace=preg_match('/^[\x{2004}\x{0020}\x{3000}\t]+/u', $paras, $match))==true){ //white space lost during pdf text extraction, recover the white space according to para
            $text.=$match[0];
            $paras=preg_replace('/^'.$match[0].'+/u', '', $paras);
        }
        if(!$hitLine && !$hitSpace || $hitLine){
            $line=$lines[++$j];
            $hitLine=false;
        }
        if(preg_match('/^\n/u', $paras, $match)){
            $text.=$match[0];
            $paras=preg_replace('/^'.$match[0].'/u', '', $paras);
        }
    }
    return $text;
}

function printLines($line2DArr, $paraArr, $verID){
    foreach($line2DArr as $key => $lines ) {
        $paras=$paraArr[$key];
        if($key===23 || $key===74 || $key===81 || $key===141)
            $a=1;
        $text=findLines($lines, $paras);
        file_put_contents($verID . "/d/" . strval($key + 1) . ".txt", $text);
    }
}

/*main function for finding para*/
function printPara($indexArr, $paraArr, $verID){
    $i=$j=0;
    $index=$indexArr[0];
    $para=$paraArr[0];
    $text='';
    $bufArr=null;
    while($i<count($paraArr) && $j<count($indexArr)){
        if(($hitbuf=checkBuf($bufArr, $index, $text)) !== true){
            if($j===142)
                $a=1;
            if(($hitPara=seperateStr($para, $index, $text)) !== true){
                $bufArr= outOrder($bufArr, $paraArr, $i, $para, $text, $index);
            }
        }
        if($para===''){
            $para=$paraArr[++$i];
        }
        if($index===''){
            $paraRes[]=$text;
            file_put_contents($verID . "/w/" . strval($j + 1) . ".txt", $text);
            $index=$indexArr[++$j];
            $text='';
        }
    }
    return $paraRes;
}

function checkBuf2(&$bufArr, &$para, &$text){
    foreach ($bufArr as $k => $buf) {
        if((seperateStr2($buf, $para, $text))===true){
            array_splice($bufArr, $k, 1);
            return true;
        }
    }
    return false;
}

function checkBuf(&$bufArr, &$index, &$text){
    foreach ($bufArr as $k => $buf) {
        if((seperateStr($buf, $index, $text))===true){
            if($buf!==''){
               $bufArr[$k]=$buf; 
            }else{
                array_splice($bufArr, $k, 1);
            }
            return true;
        }
    }
    return false;
}

function seperateStr2(&$line, &$para, &$text){
    if($para!==''){
        $tail=delimiter($line, $para, -1);
        $head=delimiter($line, $para, 0);
        if(preg_match('/^'.$head.'/u', $para)){ //correct order
            $tmp=preg_split('/'.$tail.'/u', $para, 2);
            $text.=$tmp[0].$tail."\n";
            $para=$tmp[1];
            $line='';
            return true;
        }else if(!preg_match('/^'.$head.'/u', $para) && $head){
            return false;
        }else { //image txt
            $line='';
            return false;
        }
    }
}

function seperateStr(&$para, &$index, &$text){
    if($index!==''){
        $tail=delimiter($para, $index, -1);
        $head=delimiter($para, $index, 0);
        if($head){
            $tmp=preg_split('/'.$head.'/u', $index, 2);
            $preIndex=$tmp[0];
            $index=$head.$tmp[1];
        }
        if($head && $tail){ //find match
            $tmp=preg_split('/'.$tail.'/u', $index, 2);
            $text.=$para."\n";
            $index=$preIndex.$tmp[1];
            $para='';
            return true;
        }else if($head && !$tail){ // para seperated
            $indexTail=delimiter($index, $para, -1);
            if($indexTail){
                $tmp=preg_split('/'.$indexTail.'/u', $para, 2);
                $text.=$tmp[0].$indexTail;
                $para=$tmp[1];
                $index='';
                return true;
            }else { //para is seperated by footnote or image txt
                //find the real area that para seperated and put the rest of para to the buf
                return false;
            }
        }else { //image txt
            return false;
        }
    }
}

function outOrder($bufArr, $paraArr, &$i, &$para, &$text, &$index){
    $count=0;
    $newBufArr=$bufArr;
    while($count<5){ //jumping 5 para
        $paraTmp=$paraArr[$count+$i];
        if(seperateStr($paraTmp, $index, $text)){
            $para=$paraTmp;
            $i+=$count;
            return $newBufArr;
        }else{
            $newBufArr[]=$paraTmp;
        }
        $count++;
    }
    removeImage($index, $para); // not out of order, may be image txt
    return $bufArr; 
}

function removeImage(&$index, $para){
    $head=delimiter($para, $index, 0);
    if($head===false){
        $index='';
    }else{
        $tmp=preg_split('/'.$head.'/u', $index, 2);
        $index=$head.$tmp[1];
    }
}

/* if $flag=0, find head
 * if $flag=-1, find tail */
function delimiter($para, $index, $flag){
    $para=preg_replace('/^ {1,}([^ ]{1,})/u', '$1', $para);
    $para=preg_replace('/([^ ]{1,}) {1,}$/u', '$1', $para);
    if($flag===0){
        $border= mb_strlen($para)>5?mb_substr($para, 0, 5):mb_substr($para, 0, mb_strlen($para));
    }else{
        $border= mb_strlen($para)>5?mb_substr($para, -5):mb_substr($para, -1*mb_strlen($para));
    }
    $borderLen=mb_strlen($border);
    while(count(preg_split('/'.$border.'/u', $index))>2){ //may have more than 1 match
        if(mb_strlen($para)> $borderLen){
            $border=($flag===0)?$border.mb_substr($para, $borderLen, 1)
                :mb_substr($para, -1*$borderLen-1, 1).$border; //mb_substr($str, $start, $len)
            $borderLen++;
        }else {
            break;
        }
    } //if 5 char cannot uniquely match para
    if(preg_match('/'.preg_quote($border).'/u', $index)){ 
        return preg_quote($border);
    }else{
        return compare($para, $index, $flag);
    }    
}

function compare($para, $index, $flag){
    $randomLen=0;
    $borderLen=1;
    $para=preg_replace('/^ {1,}([^ ]{1,})/u', '$1', $para);
    $para=preg_replace('/([^ ]{1,}) {1,}$/u', '$1', $para);
    $border=($flag===0)?mb_substr($para, 0, 1):mb_substr($para, $flag, 1);
    while($borderLen<=5){
        if(count(preg_split('/'.$border.'/u', $index))>=2){ //may have more than 1 match
            if(mb_strlen($para)> $borderLen){
                $border=($flag===0)?$border.mb_substr($para, $borderLen, 1)
                    :mb_substr($para, -1*$borderLen-1, 1).$border; //mb_substr($str, $start, $len)
                $borderLen++;
            }else {
                break;
            }
        }
        if(count(preg_split('/'.$border.'/u', $index))==1){ //cannot find match
            $border=($flag===0)?mb_substr($border, 0, mb_strlen($border)-1).'.{0,2}' 
                :'.{0,2}'.mb_substr($border, -1*mb_strlen($border)+1, mb_strlen($border)-1); //.{0,1}
            $randomLen++;
        }
    }
    if($randomLen/$borderLen>0.4){
        return false;
    }else {
        preg_match('/'.$border.'/u', $index, $match); 
        return preg_quote($match[0]);
    }
}

function writeChar($paraCh, $pageCh){
    $spacePattern='[\x{2004}\x{0020}\x{3000}\t]';
    if(preg_match('/^'.$spacePattern.'$/u', $paraCh) && preg_match('/^'.$spacePattern.'$/u', $pageCh)
       || preg_match('/ *(\.){2,} */s', $paraCh) && preg_match('/ *(\.){2,} */s', $pageCh)
       || $paraCh==$pageCh){
        return true;
    }else{
        return false;
    }
}

function headerRegex($txt, $pageNum, $headerStr, $seq){
    switch ($txt){
        case ($seq==1 && (preg_match('/(^|\n) {0,4}'.$headerStr.' {0,4}\n{0,1} {0,4}'.$pageNum.' {0,4}($|\n)/u', $txt, $output)==true)):
            $regex='/(^|\n) {0,4}'.$headerStr.' {0,4}\n{0,1} {0,4}'.$pageNum.' {0,4}($|\n)/u';
            break;
        case ($seq==2 && preg_match('/(^|\n) {0,4}'.$pageNum.' {0,4}\n{0,1} {0,4}'.$headerStr.' {0,4}($|\n)/u', $txt, $output)==true):
            $regex='/(^|\n) {0,4}'.$pageNum.' {0,4}\n{0,1} {0,4}'.$headerStr.' {0,4}($|\n)/u';
            break;
        case ($seq==1 && preg_match('/(^|\n) {0,4}'.$headerStr.' {0,4}\n{0,1} {0,4}\- '.$pageNum.' \- {0,4}($|\n)/u', $txt, $output)==true):
            $regex='/(^|\n) {0,4}'.$headerStr.' {0,4}\n{0,1} {0,4}\- '.$pageNum.' \- {0,4}($|\n)/u';
            break;
        case (preg_match('/(^|\n) {0,4}'.$pageNum.' {0,4}($|\n)/u', $txt, $output)==true):
            $regex='/(^|\n) {0,4}'.$pageNum.' {0,4}($|\n)/u';
            break;
        case (preg_match('/(^|\n) {0,4}\- '.$pageNum.' \- {0,4}($|\n)/u', $txt, $output)==true):
            $regex='/(^|\n) {0,4}\- '.$pageNum.' \- {0,4}($|\n)/u';
            break;
        default:
            return $txt;
    }
    return preg_replace($regex, '', $txt, 1);
}

