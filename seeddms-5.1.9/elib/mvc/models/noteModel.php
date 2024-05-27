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

class noteModel extends userMarkedModel {
    //put your code here        
    function get() {
        return $this->getNote();
    }
    
    function getNote() {
        $dms= $this->dms;
        $_data=["docid" => $_POST['documentid'], "version" => $_POST['version'], "userid" => $this->input['user']->getID()];
        $res = '';
        switch($_POST['select']){
            case('-1'): //view this page only
                $_data["page"]=$_POST['page'];
                $_data["txtstart"]=$_POST['txtstart'];
                $_data["txtlen"]=mb_strlen($_POST['text']);
                $markQuote=$_POST['markQuote'];
                $res=$dms->GetNoteByPage($_data, $markQuote);
                if($_POST['disPage']==2 && !$markQuote){
                    if($page%2 == 0){
                        $_data["page"]++;
                        $res2=array_reverse($dms->GetNoteByPage($_data), true);
                        $res=array_merge($res, $res2);
                    }else{
                        $_data["page"]--;
                        $res2=array_reverse($dms->GetNoteByPage($_data), true);
                        $res=array_merge($res2, $res);
                    }
                }
            break;
            case('0'): //view all notes in this doc
                $res=$dms->GetAllNote($_data);
            break;
            case('1'): //view my note
                $res=$dms->GetMyNote($_data);
            break;
            case('2'): //view specific user note
                $data['userid']=$_POST['viewuser'];
                $res=$dms->GetUserNote($_data);
            break;
            case('3'):
                $res=$dms->GetAdvice($_data);
            break;
        }
        return array_merge($this->data, array('res'=>$res,'dms'=>$this->dms));
    }
}
