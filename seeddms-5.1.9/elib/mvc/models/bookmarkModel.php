<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of bookmarkModel
 *
 * @author sc
 */

require_once 'userMarkedModel.php';

class bookmarkModel extends userMarkedModel {
    //put your code here        
    function get() {
        return $this->getBookmark();
    }
    
    function getBookmark() {
        if(isset($_POST['version'])){
            $version=$_POST['version'];
            $res=$this->dms->GetBookmark($this->userid, $this->documentid, $version,1);
            $mode=0;
            //show for preview document page
        }else{
            if($this->documentid<0){
                $res=$this->dms->GetBookmark($this->userid, 0, 0, 2);
            //show for show all select
            }else{
                $res=$this->dms->GetBookmark($this->userid, $this->documentid, 1, 1);
            //show for specific document
            } 
            $mode=1;
            //show for manage page
        }
        return array_merge($this->data, array('mode'=>$mode,'res'=>$res,'dms'=>$this->dms));
    }
}
