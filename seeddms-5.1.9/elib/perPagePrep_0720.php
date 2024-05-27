<?php

// uncomment the following line for testing purposes
pprep('./4', '5', [true, ["道慈綱要大道篇"], 1]);

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
    // $line2DArr is an two-dimensional array, it has two indices: page and line 
    // $indexArr is an array of index pages
    $line2DArr=$indexArr=array();
    processPdf($verID, $headerInfo, $line2DArr, $indexArr);
    $paraArr=processWord($verID);
    $paraRes=printPara($indexArr, $paraArr, $verID); //get an array of structured pages with corrected paragraph texts
    printIndex($paraRes, $verID); //remove the newline in paragraphs to print corrected index page
    $para2DArr=getPara2DArr($paraRes);
    printLines($line2DArr, $para2DArr, $verID);
    chdir($currentdir); // return to the directory of the calling code module.
}

function processPdf($verID, $headerInfo, &$line2DArr, &$indexArr){
    if(!file_exists($verID.'/d')){
        mkdir($verID.'/d');
    }
    if(file_exists($verID . ".pdf")){
        $pdfdata = shell_exec("java -jar ../../../seeddms-5.1.9/elib/pdfbox-2.0.24.jar " . $verID . ".pdf");
        file_put_contents($verID . "/pdfBox.txt", $pdfdata); // text extract from pdf file
        $doc = new DOMDocument();
        @$doc->loadHTML(mb_convert_encoding($pdfdata, 'HTML-ENTITIES', 'UTF-8'));
        $pages = getChildObj($doc, 'div', 'page'); // place all page objects in an array  
        foreach ($pages as $k => $page) {  // Loop thru each page which is a DOM child of the HTML Document
            $lineArr=$indexStr=null;
            $pgfs = getChildObj($page, 'p', ''); // get all line objects in a page as an array
            // remove page numbers and empty paragraphs for all paragraphs in a page
            cleanPage($pgfs, $headerInfo, $lineArr, $indexStr);
            $line2DArr[]=$lineArr;
            $indexArr[]=$indexStr;
        }
    }
}

function getPara2DArr($paraRes){
    $para2DArr=array();
    foreach ($paraRes as $page) {
        $paras= explode("\n\n", $page);
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
        //execute with self-made config to filter the vector image txt
        $stripWord = shell_exec("java -jar ../../../seeddms-5.1.9/elib/tika-app-2.4.0.jar -h " . $verID . ".docx");
        //--config=../../../seeddms-5.1.9/elib/tika-config.xml
        // handle HTML tags
        $stripWord = wordRegex($stripWord);
        file_put_contents($verID . "/stripWord.txt",$stripWord); // text extracted from word file
        $doc2 = new DOMDocument(); // convert raw text stream to DOM objects
        @$doc2->loadHTML(mb_convert_encoding($stripWord, 'HTML-ENTITIES', 'UTF-8')); 
        $nodes = getChildObj($doc2, 'p', ''); 
        $paraArr = cleanPara($nodes);
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

//prepend the last 5 texts in previous page to next page
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
        if(($catalogArr=catalogRegex($txt)) == true){ //handle catalog in word file
            $output= $output?array_merge($output, $catalogArr): $catalogArr;
        }else if(stripRegex($txt)){ // strip out empty paragraphs and page numbers
            continue;
        }else { //handle the special cases in paragraph
            $txt = preg_replace('/[\x{2004}\x{0020}\x{3000}\x{00a0}\t\n]+$/u', "", $txt); //remove extra spaces in tail
            $txt = preg_replace('/\n/u', "", $txt); //remove newline in para (!important)
            $txt = preg_replace('/[\x{2004}\x{3000}\x{00a0}\t]/u', " ", $txt); //syncronize spaces with pdf extracted txt, \x{0020}
            $txt=recoverIndent($txt); 
//            $txt=preg_replace('/([^\x{3000}\x{0020}\t]{1,})[\x{3000}\x{0020}\t]{2}/u', '$1 ', $txt); //reduce space
//            $txt=preg_replace('/([^\x{3000}\x{0020}\t]{1,})[\x{3000}\x{0020}\t]{2}/u', '$1 ', $txt);
            $output[]=$txt;
        }
    }
    return $output;
}

//get 2 kinds of page contents
function cleanPage($pgfs, $headerInfo, &$lineArr, &$indexStr){
    $content="";
    foreach ($pgfs as $p) { //$pgfs=line array, $p=line
        $txt = $p->nodeValue; //$txt=line text
        if($txt==''|| preg_match('/^[\x{2004}\x{0020}\x{3000}\t\n\-]+$/u', $txt)){
            continue;
        }
        $txt=recoverIndent($txt);
        //synchronize points in catalog for the sake of comparisom
        $txt = preg_replace('/[\x{2004}\x{0020}\x{3000}\t\n]+$/u', "", $txt);
        $txt = preg_replace('/[\x{2004}\x{00a0}\t]/u', " ", $txt);
        $txt=preg_replace('/ *(\.){7,} */s', ' .......................................... ', $txt); 
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
    $txt=preg_replace('/<\/h([\d]+)>/s', '</p>', $txt); //</h1>
    $txt=preg_replace('/<p class="header">((<p>|<p class="footer">)([^>]*)<\/p>)*<\/p>/s','', $txt); //remove strange header and footer
    $txt=preg_replace('/<p class="header">([^>]*)<\/p>/u', '', $txt); //remove general header
    $txt=preg_replace('/<p class="footer">([^>]*)<\/p>/u', '', $txt); //remove general footer
    $txt=preg_replace('/(<table)(.*)(<\/table>)/s', '', $txt); //remove table tag
    $txt = preg_replace('/<p\/>/', '', $txt); //remove strange p tag 
    return $txt;
}

// strip out empty paragraphs and page numbers
function stripRegex($txt){
    switch ($txt) { 
        case '':  // empty paragraph: do nothing (remove)
            break;
        case (preg_match('/^[\x{2004}\x{0020}\x{3000}\t\n\-]+$/u', $txt)? true : false): // paragraph for all white spaces or newlines (remove)
            break;
        case (preg_match('/^ {0,4}[0-9○一二三四五六七八九十]{1,4} {0,4}+$/u', $txt)?true:false):  // paragraph for page number only: 1~9999 一~九九九九, do nothing (remove)               
            break;
        case (preg_match('/^ {0,4}\- [0-9○一二三四五六七八九十]{1,4} +\- {0,4}/u', $txt)?true:false):  // paragraph for a - nnn - formatted page number only: 1~9999 一~九九九九, do nothing (remove)               
            break;
        default:
            return false;
    }
    return true;
}

// There is no points listed between chapter and pageNum in the text extracted from word
// Recover the points in the catalog
function catalogRegex($txt){
    $catalog='(目錄|目次)';
    if($txt !="" && preg_match('/^(.*)'.$catalog.'\n/u', $txt)){
        $txt=preg_replace('/ {0,}[\t]+ {0,}/', ' .......................................... ', $txt);
        $arr=preg_split('/\n/', $txt);
        return array_filter($arr, 'strlen');
    }else{
        return false;
    }
}

//recover the indentation at the beginning of paragraph
function recoverIndent($txt){
    $txt=preg_replace('/^ {2}([^ ]{1,})/u', '　　$1', $txt); 
    return $txt;
}

function findLines($lines, $paras){ //$lines means the line array in a page
    $i=$j=0;
    $text='';
    $para=$paras[0];
    $line=$lines[0];
    while($i<count($paras) && $j<count($lines)){
        if(($hit=seperateStr2($line, $para, $text)) !== true){
            $hit=extraWord($line, $para, $text);
        }
        if(!$hit || $line==''){
            $line=$lines[++$j];
        }
        if($para==''){
           $para=$paras[++$i]; 
           $text.="\n";
        }
    }
    return $text;
}
/*line2DArray = array( //all pages
 * [0]=>array( //the first-class elements means a page
 *  [0]=line //the second-class elements means a line
 * )
 *)
*/
/*para2DArray = array( //all pages
 * [0]=>array( //the first-class elements means a page
 *  [0]=line //the second-class elements means a para
 * )
 *)
*/
function printLines($line2DArr, $para2DArr, $verID){
    foreach($line2DArr as $key => $lines ) { //the keys for $line2DArr and $para2DArr are the same, which means the page num
        $paras=$para2DArr[$key];
        $text=findLines($lines, $paras);
        file_put_contents($verID . "/d/" . strval($key + 1) . ".txt", $text);
    }
}

function printPara($indexArr, $paraArr, $verID){
    $i=$j=0;
    $index=$indexArr[$j]; 
    $nextIndex=$indexArr[$j+1];
    $para=$paraArr[$i];
    $text=''; //$text is a page with structured and corrected paragraphs 
    $bufArr=null; //para buffer which stores the out-order-paragraphs
//    $indexPages=implode($indexArr); //prepare for checking the extra words in word file
    while($i<count($paraArr) && $j<count($indexArr)){
        if($para!='' && $index!=''){
            if(($hitbuf=checkBuf($bufArr, $index, $text, $nextIndex))===false){ //check para buffer
                if(($hitPara=seperateStr($para, $index, $text, $nextIndex, 1, $bufArr))===false){ // use current para to match index page
                    extraWord($index, $para, $text); 
                    //if you cannot find the paragraph in index page, the para can be extra words (missing texts in pdf file)
                    //Or the current index texts is image txt (extra texts in pdf)
                }
            }
        }
        if($para==''){ //point to next para
            $para=$paraArr[++$i];
        }
        if($index==''){ //the current index page is completely matched
            //$text is a page with structured and corrected paragraphs 
            $paraRes[]=$text;
            file_put_contents($verID . "/w/" . strval($j + 1) . ".txt", $text);
            $text='';
            $index=$indexArr[++$j]; //point to next index page
            $nextIndex=$indexArr[$j+1];
        }
    }
    return $paraRes;
}

function outOrder($bufArr, $paraArr, &$i, &$para, &$text, &$index, $nextIndex){
    $count=1;
    $newBufArr=$bufArr;
    $newBufArr[]=$para;
    while($count<=5){ //jumping 5 para
        $paraTmp=$paraArr[$count+$i];
        if(seperateStr($paraTmp, $index, $text, $nextIndex)){
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

//check para buf
function checkBuf(&$bufArr, &$index, &$text, $nextIndex){
    foreach ($bufArr as $k => $buf) {
        if((seperateStr($buf, $index, $text, $nextIndex, 0))===1){
            if($buf!=''){
               $bufArr[$k]=$buf;
            }else{
                array_splice($bufArr, $k, 1);
            }
            return true;
        }
    }
    return false;
}

//missing words in pdf extracted text, recover these word in the page
function extraWord(&$index, &$para, &$text){
    $tail=delimiter($para, $index, -1);
    $head=delimiter($para, $index, 0);
    if($head){ //not extra text in para, it is extra text in pdf //&& tail
        removeImage($index, $para);
        return;
    }
    $text.=$para."\n";
    $para='';
}

function seperateStr2(&$line, &$para, &$text){ //find the head and tail of line to match para
    if($para!=''){
        $tail=delimiter($line, $para, -1);
        $head=delimiter($line, $para, 0);
        if($head && $tail){ //find match
            $tmp=preg_split('/'.$tail.'/u', $para, 2);
            $text.=$tmp[0].stripslashes($tail)."\n";
            $para=$tmp[1];
            $Tmp=preg_split('/'.$tail.'/u', $line, 2);
            $line=$Tmp[1];
            return true;
        } if($head && !$tail){ //line seperated, the head and tail of the line in two different paras
            $paraTail=delimiter($para, $line, -1);
            if($paraTail){
                $tmp=preg_split('/'.$paraTail.'/u', $line, 2);
                $text.=$para;
                $line=$tmp[1];
                $para='';
                return true;
            }
        }
    }
    return false; //image txt or extra word
}

//compare strings in index file and paragraph file
function seperateStr(&$para, &$index, &$text, $nextIndex, $flag, &$buf=null){
    //$flag => if flag==1, allow to insert out-order para to buf. else prohibit inserting out-order para to buf 
    //in ordrt to prevent repeatly buf insert.
    $paraTail=delimiter($para, $index, -1);
    $paraHead=delimiter($para, $index, 0);
    if($paraHead && preg_match('/^ *'.$paraHead.'/u', $index) && $paraTail){ //find match
        $indexSplit=preg_split('/'.$paraTail.'/u', $index, 2);
        $text.=$para."\n\n"; 
        $index=$indexSplit[1];
        $para='';
        return 1;
    }else if($paraHead && preg_match('/^ *'.$paraHead.'/u', $index) && !$paraTail){ // para seperated
        $indexTail=delimiter($index, $para, -1);
        if($indexTail){ 
            //the para is longer than index, need to seperate para
            $paraSplit=preg_split('/'.$indexTail.'/u', $para, 2);
            //remove the white spaces at the tail of seperated paragraph
            $text.=preg_replace("/ {1,}$/u", '', $paraSplit[0]).stripslashes($indexTail);
            $para=$paraSplit[1];
            $index='';
            return 1;
        }else { //para is seperated by footnote or image txt
            //find the exact position that para seperated and put the rest of para in the buf
            $nextIndexHead=delimiter($nextIndex, $para, 0);
            if($nextIndexHead){
                $paraSplit=preg_split('/'.$nextIndexHead.'/u', $para, 2);
                $text.=preg_replace("/ {1,}$/u", '', $paraSplit[0])."\n\n";
                $para=stripslashes($nextIndexHead).$paraSplit[1];
                $exactTail=delimiter($paraSplit[0], $index, -1); //find the exact end of page, except footnote
                $indexSplit=preg_split('/'.$exactTail.'/u', $index, 2);
                $index=$indexSplit[1];
                return 1;
            }
            exit("Para tail does not exist in pdf file. The reason may be delimiter function cannot return correct para tail.");
        }
    }else if($paraHead && $flag && !preg_match('/^ *'.$paraHead.'/u', $index)){ //out of order para, insert it in buf
        //The reason of checking the existence of $buf in if-statement is that preventing repeatly adding $para to $buf if the caller is checkBuf()
        $buf[]=$para;
        $para='';
        return 2;
    }
    //image txt or out of order
    return false;
}

function removeImage(&$index, $para){
    $head=delimiter($para, $index, 0);
    if($head==false){ //|| preg_match('/^'.$head.'/u', $index, second condition avoid infinite loop
        $index='';
    }else{
        $tmp=preg_split('/'.$head.'/u', $index, 2);
        $index=stripslashes($head).$tmp[1];
    }
}

/* if $flag=0, find head
 * if $flag=-1, find tail */
function delimiter($para, $index, $flag){
    if($para !='' && $index !=''){
//        $para=preg_replace('/^ {1,}([^ ]{1,})/u', '$1', $para); //remove space
//        $para=preg_replace('/([^ ]{1,}) {1,}$/u', '$1', $para);
        if($flag===0){
            $border= mb_strlen($para)>5?mb_substr($para, 0, 5):mb_substr($para, 0, mb_strlen($para));
        }else{
            $border= mb_strlen($para)>5?mb_substr($para, -5):mb_substr($para, -1*mb_strlen($para));
        }
        $borderLen=mb_strlen($border);
        while(count(preg_split('/'.preg_quote($border).'/u', $index))>2){ //may have more than 1 match
            if(mb_strlen($para)> $borderLen){
                $border=($flag===0)?$border.mb_substr($para, $borderLen, 1)
                    :mb_substr($para, -1*$borderLen-1, 1).$border; //mb_substr($str, $start, $len)
                $borderLen++;
            }else {
                break;
            }
        } 
        //special treatment for catalog points
        if(preg_match('/ *(\.){7,} */u', $border)){
            $border=preg_replace('/ *(\.){7,} */s', ' .......................................... ', $border); 
        }
        if(preg_match('/'.preg_quote($border).'/u', $index)){ 
            //preg_quote() means adding backslash in front of every character that is part of the regular expression syntax
            return preg_quote($border);
        }else{ //cannot find match
            return compare($para, $index, $flag);
        }
    }   
    return false;
}

//get the unique delimeter if 5 char in the front and tail of para cannot find match
function compare($para, $index, $flag){
    //$randomLen describe the length of characters that can match any one character
    //$validLen describe the length of valid words
    //$next means the position of next char in para to be extracted
    $randomLen=$validLen=$next=0;
    $border="";
    while($validLen<5 && mb_strlen($para)>$next){
        //may have more than 1 match
        $newChar=getNewChar($para, $next, $flag);
        $validLen=checkSymbol($newChar, $validLen);
        $border=checkSpace($border, $newChar, $index, $flag);
        $next++;
        if(count(preg_split('/'.preg_quote($border).'/u', $index))==1){ //cannot find match
            $tmp=($flag===0)?mb_substr($border, 0, mb_strlen($border)-1).'.{1}'. mb_substr($border, -1)
                :mb_substr($border, 0, 1).' *.{1}'.mb_substr($border, 1); //find delimeter from page to para, supplement one char which is missing
            if(count(preg_split('/'.preg_quote($tmp).'/u', $index))>=2){ //if supplement is correct
                $border=$tmp;
            }else{
                $border=($flag===0)?mb_substr($border, 0, mb_strlen($border)-1).'.{0,1}' 
                    :'.{0,1}'.mb_substr($border, -1*mb_strlen($border)+1, mb_strlen($border)-1); 
            }
            $randomLen++;
        }
    }
    if($randomLen<=1){
        preg_match('/'.preg_quote($border).'/u', $index, $match); 
        return preg_quote($match[0]);
    }else {
       return false;
    }
}

function checkSpace($border, $newChar, $index, $flag){
    if(preg_match('/[\x{0020}\x{2004}\x{3000}\x{00a0}\t]/u', $newChar)){
        if($flag==0 && count(preg_split('/'.$border.$newChar.'/u', $index))==1 
            || $flag==-1 && count(preg_split('/'.$newChar.$border.'/u', $index))==1){ //if para has extra space, return the original border
            return $border;
        }
    }
    if($flag===0){ // if para and index has white space in same position, append/prepend newChar to border
        return $border.$newChar;
    }else{
        return $newChar.$border;
    }
}

function checkSymbol($newChar, $validLen){
    if(!preg_match("/[\!\"\',\.\:;\?\[\]\{\}\(\)\“\”。、，：；（）「」？!<>《》\ \s]+$/u", $newChar)){
        return ++$validLen;
    }
    return $validLen;
}

function getNewChar($para, $next, $flag){
    if($flag===0){ //head
        return mb_substr($para, $next, 1);
    }else{ //tail
        return mb_substr($para, -1*$next-1, 1);
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
    if(preg_match('/(^) {0,4}'.$headerStr.'/u', $txt)){
        if(preg_match('/(^|\n) {0,4}'.$headerStr.' {0,4}\n{0,1} {0,4}\- '.$pageNum.' \- {0,4}/u', $txt)){
            $txt=preg_replace('/(^|\n) {0,4}'.$headerStr.' {0,4}\n{0,1} {0,4}\- '.$pageNum.' \- {0,4}/u', '', $txt, 1);
        }else{
            $txt=preg_replace('/(^|\n) {0,4}'.$headerStr.' {0,4}\n{0,1} {0,4}'.$pageNum.' {0,4}/u', '', $txt, 1);
        }
    }else if(preg_match('/(^) {0,4}'.$pageNum.'/u', $txt)){
        $txt=preg_replace('/(^|\n) {0,4}'.$pageNum.' {0,4}\n{0,1} {0,4}'.$headerStr.' {0,4}/u', '', $txt, 1);
    }
    return $txt;
}
