<?php
header("Content-Type: text/plain; charset='UTF-8'");
header("Content-Type: text/html; charset='UTF-8'");
catolog('./data/1048576/109/1/12.txt');
function catolog($doc){
    $ah = fopen($doc, 'r') or die("unable");
    while(!feof($ah))
    {
        echo fread($ah, 1)."\n";
      
    }
    fclose($ah);

}
?>

