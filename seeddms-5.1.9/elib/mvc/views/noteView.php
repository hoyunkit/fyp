<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of bookmarkView
 *
 * @author sc
 */

require_once 'userMarkedView.php';

class noteView extends userMarkedView{
    //put your code here
    function displayHeader($data) {
        $res = $data['res'];
        $sort = $_POST['sort'];
        $str = '';
        if($_POST['mode']){
            $str="<table style='table-layout: fixed;word-wrap: break-word; width:100%;'><tr><th style='width:57%;' value='Title' id='hovertmp'>Title</th><th style='width:8%;' value='Page' id='hovertmp'>Page</th><th style='width:20%;' value='Date' id='hovertmp'>Date</th></tr>";
            if($sort=='Title'){
                if($_COOKIE['sort_mode']=='ascending'){
                   usort($res, "cmp2");
                   $str="<table style='table-layout: fixed;word-wrap: break-word; width:100%;'><tr><th style='width:57%;' value='Title'>Title ▲</th><th style='width:8%;' id='hovertmp' value='Page'>Page</th><th style='width:20%;' id='hovertmp' value='Date'>Date</th></tr>";
                }
                else
                {
                   usort($res, "cmp2R");
                   $str="<table style='table-layout: fixed;word-wrap: break-word; width:100%;'><tr><th style='width:57%;' value='Title'>Title ▼</th><th style='width:8%;' id='hovertmp' value='Page'>Page</th><th style='width:20%;' id='hovertmp' value='Date'>Date</th></tr>";
                }
            }elseif($sort=='Page'){
                if($_COOKIE['sort_mode']=='ascending'){
                   usort($res, "cmp");
                   $str="<table style='table-layout: fixed;word-wrap: break-word; width:100%;'><tr><th style='width:57%;' id='hovertmp' value='Title'>Title</th><th style='width:8%;' value='Page'>Page ▲</th><th style='width:20%;' id='hovertmp' value='Date'>Date</th></tr>";
                }
                else
                {
                   usort($res, "cmpR");
                   $str="<table style='table-layout: fixed;word-wrap: break-word; width:100%;'><tr><th style='width:57%;' id='hovertmp' value='Title'>Title</th><th style='width:8%;' value='Page'>Page ▼</th><th style='width:20%;' id='hovertmp' value='Date'>Date</th></tr>";
                }

             }elseif($sort=='Date'){
                if($_COOKIE['sort_mode']=='ascending'){
                   usort($res, "timeCmp2");
                   $str="<table style='table-layout: fixed;word-wrap: break-word; width:100%;'><tr><th style='width:57%;' id='hovertmp' value='Title'>Title</th><th style='width:8%;' id='hovertmp' value='Page'>Page</th><th style='width:20%;' value='Date'>Date ▲</th></tr>";
                }
                else
                {
                   usort($res, "timeCmp1");
                   $str="<table style='table-layout: fixed;word-wrap: break-word; width:100%;'><tr><th style='width:57%;' id='hovertmp' value='Title'>Title</th><th style='width:8%;' id='hovertmp' value='Page'>Page</th><th style='width:20%;' value='Date'>Date ▼</th></tr>";
                }
             }
        }else{
            if($sort==0){
                usort($res, "dateCmp1");
            }else if($sort==1){
                usort($res, "dateCmp2");
            }else if($sort==2){
                usort($res, "pageCmp1");
            }else if($sort==3){
                usort($res, "cmp");
            }
        }
        return array_merge($data, array('str'=> $str,'res'=>$res));
    }
    
    function displayContent($data) {
        $str = $data['str'];
        if(sizeof($data['res'])>0){
            foreach($data['res'] as $note){
                if($_POST['mode']){
                    $str=noteFrame1($data['dms'], $note, $str);
                }else {
                    $str=noteFrame2($data['dms'], $note, $str);
                }   
            }
        }else{
            if(!$_POST['mode']){
                $str=$str."No notes yet.";
            }
        }
        $data['str']=$str."</table>";
        //file_put_contents("/home/www-data/seeddms51x/debug.txt", $str."\n", FILE_APPEND);
        return $data;
    }
}
