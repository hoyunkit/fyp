<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of userMarkedView
 *
 * @author sc
 */
abstract class userMarkedView {
    //put your code here
    protected $input;
    
    function __construct($input) {
        $this->input = $input;
    }
    
    abstract function displayHeader($data);

    abstract function displayContent($data);
            
    function dateCmp1($a, $b){
        return ($a['date']>$b['date']) ? -1:1;
    }
    function dateCmp2($a, $b){
        return ($a['date']<$b['date']) ? -1:1;
    }
    function timeCmp1($a, $b){
        return ($a['time']>$b['time']) ? -1:1;
    }
    function timeCmp2($a, $b){
        return ($a['time']<$b['time']) ? -1:1;
    }
    function pageCmp1($a, $b){
        if($a['pageno']==$b['pageno']) {
            if($a['txtstart']==$b['txtstart']){
                return ($a['txtlen']<$b['txtlen']) ? -1:1;
            }
            return ($a['txtstart']<$b['txtstart']) ? -1:1;
        }
        return ($a['pageno']>$b['pageno']) ? -1:1;
    }
    function cmp($a, $b){
        if($a['pageno']==$b['pageno']) {
            if($a['txtstart']==$b['txtstart']){
                return ($a['txtlen']<$b['txtlen']) ? -1:1;
            }
            return ($a['txtstart']<$b['txtstart']) ? -1:1;
        }
        return ($a['pageno']<$b['pageno']) ? -1:1;
    }
    function cmpR($a, $b){
        if($a['pageno']==$b['pageno']) {
            if($a['txtstart']==$b['txtstart']){
                return ($a['txtlen']>$b['txtlen']) ? -1:1;
            }
            return ($a['txtstart']>$b['txtstart']) ? -1:1;
        }
        return ($a['pageno']>$b['pageno']) ? -1:1;
    }
    function cmp2($a, $b){
        if($a['documentid']==$b['documentid']){
             return ($a['pageno']<$b['pageno']) ? -1:1;
        }
        return ($a['documentid']<$b['documentid']) ? -1:1;
    }
    function cmp2R($a, $b){
        if($a['documentid']==$b['documentid']){
             return ($a['pageno']>$b['pageno']) ? -1:1;
        }
        return ($a['documentid']>$b['documentid']) ? -1:1;
    }
}
