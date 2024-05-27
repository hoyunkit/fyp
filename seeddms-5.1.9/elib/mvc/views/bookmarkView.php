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

class bookmarkView extends userMarkedView{
    //put your code here
    function displayHeader($data) {
        $res = $data['res'];
        $str = '';
        if($data['mode']==0){
            $str="<table>";
            usort($res, "cmp"); //sort by pageno
        }else{
            $str="<table><tr><th style='width:65%;' value='Title' id='hovertmp'>Title</th><th style='width:15%;' value='Page' id='hovertmp'>Page</th><th style='width:10%;' value='Date' id='hovertmp'>Date</th></tr>";
            if(isset($_POST['sort'])){
                $sort=$_POST['sort'];
                if($sort=='Title'){
                    if($_COOKIE['sort_mode']=='ascending'){
                       usort($res, "cmp2");
                       $str="<table><tr><th style='width:65%;' value='Title'>Title ▲</th><th style='width:15%;' value='Page' id='hovertmp'>Page</th><th style='width:10%;' value='Date' id='hovertmp'>Date</th></tr>";
                    }
                    else
                    {
                       usort($res, "cmp2R");
                       $str="<table><tr><th style='width:65%;' value='Title'>Title ▼</th><th style='width:15%;' value='Page' id='hovertmp'>Page</th><th style='width:10%;' value='Date' id='hovertmp'>Date</th></tr>";
                    }
                }elseif($sort=='Page'){
                    if($_COOKIE['sort_mode']=='ascending'){
                       usort($res, "cmp");
                       $str="<table><tr><th style='width:65%;' value='Title' id='hovertmp'>Title</th><th style='width:15%;' value='Page'>Page ▲</th><th style='width:10%;' value='Date' id='hovertmp'>Date</th></tr>";
                    }
                    else
                    {
                       usort($res, "cmpR");
                       $str="<table><tr><th style='width:65%;' value='Title' id='hovertmp'>Title</th><th style='width:15%;' value='Page'>Page ▼</th><th style='width:10%;' value='Date' id='hovertmp'>Date</th></tr>";
                    }
                }else{
                    if($_COOKIE['sort_mode']=='ascending'){
                       usort($res, "dateCmp2");
                       $str="<table><tr><th style='width:65%;' value='Title' id='hovertmp'>Title</th><th style='width:15%;' value='Page' id='hovertmp'>Page</th><th style='width:10%;' value='Date'>Date ▲</th></tr>";
                    }
                    else
                    {
                       usort($res, "dateCmp1");
                       $str="<table><tr><th style='width:65%;' value='Title' id='hovertmp'>Title</th><th style='width:15%;' value='Page' id='hovertmp'>Page</th><th style='width:10%;' value='Date'>Date ▼</th></tr>";
                    }
                }
            }
        }
        return array_merge($data, array('str'=>$str,'res'=>$res));
    }
    
    function displayContent($data) {
        $mode = $data['mode'];
        $str = '';
        if(sizeof($data['res'])>0){
        // output data of each row
        // end at 4
            foreach($data['res'] as $row){
                $str=$str."<tbody class='searchreturn' value='".$row["pageno"]."'>";
                if($mode==0){
                    $str=$str."<tr><td>Page: ".$row["pageno"]."</td>";
                }else{
                    if (!$data['dms']->GetDocNameByid($row["documentid"])){
                        $str=$str."<tr><td><a href=\"../out/out.PreviewDocument.php?documentid=".$row["documentid"]."&version=".$row["version"]."&page=".$row["pageno"]."\"'>"."This bookmark is no longer available, please remove it</a></td><td>".$row["pageno"]."</td>";
                    }
                    else{
                        $str=$str."<tr><td><a href=\"../out/out.PreviewDocument.php?documentid=".$row["documentid"]."&version=".$row["version"]."&page=".$row["pageno"]."\"'>".$data['dms']->GetDocNameByid($row["documentid"])[0]['name']."</a></td><td>".$row["pageno"]."</td>";
                    }
                }
                $str=$str."<td>".$row["date"]."</td>"; 
                $str=$str."<td>&emsp;<span class='delbookmarkbtn' data-docid='".$row["documentid"]."' data-ver='".$row["version"]."' value='".$row["pageno"]."'><i class='icon-remove' title='Delete the bookmark' style='font-size:20px;'></span></td>";
                $str=$str."</tr>";
                $doc='../elib/data/1048576/'.$row["documentid"].'/'.$row["version"].'/'.$row["pageno"].'.txt';
                $content='';
                if (file_exists($doc)) {
                    if($mode==0){
                        $content = file_get_contents($doc, FALSE, NULL, 0, 99);
                    }else{
                        $content = file_get_contents($doc, FALSE, NULL, 0, 279);
                    }
                }else{
                    // $content='';
                }
                $str=$str."<tr><td colspan='2'>".$content."... <br><br></td></tr>";
                $str=$str."</tbody>";
            }
        }else{
            if(!$mode){
                $str=$str."No bookmark yet. Try to press the heart button to add a bookmark";
            }
        }
        $str=$str."</table>";
        $data['str'] .= $str;
        return $data;
    }
       
}
