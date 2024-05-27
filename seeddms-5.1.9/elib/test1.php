<?php

$path1='../elib/data/1048576/310/1/ocr_crop';
newLine($path1, 1);
$path2='../elib/data/1048576/310/1/scanned_crop';
newLine($path2, 2);

function newLine($docdir, $mode){
    chdir($docdir); // change the directory to where the pdf ducument is located
    $a = glob('*jpg'); // collect all image file names to an array
    $i=0;
    foreach ($a as $fn) {
        $i++;
        if($mode===1){
            rename($fn, preg_replace('/濟南佈道團蒞港\(formatted\)_Page_0([0-9]\.jpg)/u', '$1', $fn));
            rename($fn, preg_replace('/濟南佈道團蒞港\(formatted\)_Page_([0-9]+\.jpg)/u', '$1', $fn));
        }else{
            rename($fn, preg_replace('/59\) 濟南佈道團蒞港 特科訓文 \(cropped\)_Page_0([0-9]\.jpg)/u', '$1', $fn));
            rename($fn, preg_replace('/59\) 濟南佈道團蒞港 特科訓文 \(cropped\)_Page_([0-9]+\.jpg)/u', '$1', $fn));
        }
    }
}