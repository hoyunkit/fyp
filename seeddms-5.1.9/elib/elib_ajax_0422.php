<?php

    include("../inc/inc.Settings.php");
    include("../inc/inc.LogInit.php");
    include("../inc/inc.Utils.php");
    include("../inc/inc.Language.php");
    include("../inc/inc.Init.php");
    include("../inc/inc.Extension.php");
    include("../inc/inc.DBInit.php");
    include("../inc/inc.ClassUI.php");
    include("../inc/inc.ClassAccessOperation.php");
    include("../inc/inc.Authentication.php");
    

    if (isset($_POST['action'])) {
        //file_put_contents("/home/www-data/seeddms51x/debug.txt", "Click".$_POST['action'], FILE_APPEND);
        if($_POST['action']=='addbookmark'){
            //handle add bookmark request
            $documentid=$_POST['documentid'];
            $userid=$user->getID();
            $page=$_POST['page'];
            $version=$_POST['version'];

            $add=$dms->AddBookmark($documentid, $userid, $page, $version);

            if($add){
                echo json_encode(1);

            }else{
                echo json_encode(0);
            }
        }
        else if($_POST['action']=='showbookmark'){
            //show user bookmark
            $userid=$user->getID();
            $documentid=$_POST['documentid'];
            
            if(isset($_POST['version'])){
                $version=$_POST['version'];
                $res=$dms->GetBookmark($userid, $documentid, $version,1);
                $mode=0;
                //show for preview document page
            }else{
                if($documentid<0){
                    $res=$dms->GetBookmark($userid, 0, 0, 2);
                //show for show all select
                }else{
                    $res=$dms->GetBookmark($userid, $documentid, 1, 1);
                //show for specific document
                } 
                $mode=1;
                //show for manage page
            }

            
              if($mode==0){
                $str="<table>";
                usort($res, "cmp"); //sort by pageno
              }else{
                $str="<table><tr><th style='width:65%;'>Title</th><th style='width:15%;'>Page</th><th style='width:10%;'>Date</th></tr>";
                 if(isset($_POST['sort'])){
                     $sort=$_POST['sort'];
                     if($sort=='Title'){

                         usort($res, "cmp2");
                     }elseif($sort=='Page'){
                         usort($res, "cmp");
                     }
                 }
              }
              if(sizeof($res)>0){
              // output data of each row
                foreach($res as $row){
                     $str=$str."<tbody class='searchreturn' value='".$row["pageno"]."'>";
                  if($mode==0){
                      $str=$str."<tr><td>Page: ".$row["pageno"]."</td>";
                  }else{
                      $str=$str."<tr><td><a href=\"../out/out.PreviewDocument.php?documentid=".$row["documentid"]."&version=".$row["version"]."&page=".$row["pageno"]."\"'>".$dms->GetDocNameByid($row["documentid"])[0]['name']."</a></td><td>".$row["pageno"]."</td>";
                  } 
                  $str=$str."<td>".$row["date"]."</td>";
                  $str=$str."<td>&emsp;<span class='delbookmarkbtn' data-docid='".$row["documentid"]."' data-ver='".$row["version"]."' value='".$row["pageno"]."'><i class='icon-remove'></span></td>";
                  $str=$str."</tr>";
                  $doc='../elib/data/1048576/'.$row["documentid"].'/'.$row["version"].'/'.$row["pageno"].'.txt';
                  if (file_exists($doc)) {
                    if($mode==0){
                        $data = file_get_contents($doc, FALSE, NULL, 0, 99);
                    }else{
                        $data = file_get_contents($doc, FALSE, NULL, 0, 279);
                    }
                  }else{
                      $data='';
                  }

                  $str=$str."<tr><td colspan='2'>".$data."... <br><br></td></tr>";
                  $str=$str."</tbody>";
                }

            }else{
                if(!$mode){
                    $str=$str."No bookmark yet. Try to press the heart button to add a bookmark";
                }
                    
                }
            $str=$str."</table>";
            echo $str;
        }
        else if($_POST['action']=='delbookmark'){
            //handle delete bookmark request
            $documentid=$_POST['documentid'];
            $userid=$user->getID();
            $version=$_POST['version'];
            $page=$_POST['page'];
            $res=$dms->DeleteBookmark($documentid, $userid, $page, $version, 0);
            echo json_encode(1);
        }
        else if($_POST['action']=='showpagetxt'){
        //file_put_contents("/home/www-data/seeddms51x/debug.txt", "Click".$_POST['comment'], FILE_APPEND);
            $docid=$_POST['documentid'];
            $version=$_POST['version'];
            $page=$_POST['page'];
            $dis=$_POST['dis'];
            $align=$_POST['align'];
            if($dis=="single"){
                //file_put_contents("/home/www-data/seeddms51x/debug.txt", "Click", FILE_APPEND);
                    $text = pageHighlight($docid,$version, $page,$user->getID(),$dms,$align);
                $data = "<div class=".$page.">".$text[0]."</div>";
                echo $data;
                return;
            }
            if($page%2 != 0){
                $page--;
            }
                $text = pageHighlight($docid,$version, $page,$user->getID(),$dms,$align);
                $data = "<div class=".$page.">".$text[0]."</div>";

                $page++;

                $text = pageHighlight($docid,$version, $page,$user->getID(),$dms,$align, $text[1]);
                $data = $data."<div class=".$page.">".$text[0]."</div>";
            
             echo $data;

        }
        else if($_POST['action']=='lookupdict'){
            $str=$_POST['str'];
            
            $output = shell_exec('curl -s "http://localhost:8983/solr/'.$settings->_solrcore.'/analysis?analysis.fieldtype=text_ik&q=&analysis.fieldvalue='.urlencode($str).'&verbose_output=1"');
            $decodedOutput = json_decode($output, true, 512, 0);
            $ik_output=$decodedOutput['analysis']['field_types']['text_ik']['index'][1];
            $explain="";
            foreach ($ik_output as $token){
                $res=$dms->LookupDict($token['text']);
                    if(sizeof($res)>0){
                        $explain= $explain.$token['text']."<br>";
                        foreach($res as $row){
                            $explain= $explain.$row['value']."<br>";
                        }
                    }
            }
            
            if($explain==""){
                echo $str;
            }else{
                echo $str."<hr>".$explain;
            }
        }
        else if($_POST['action']=='subnote'){
            $data=$_POST['data'];
            //data['docid','version','subject','notecontent','page','quote','parent','visible', 'noteType']
            if($data[2]==""||$data[3]==""){
                echo 0;
                return;
            }else{
                if($data[5]==""){
                    $pos=0;
                    $len=0;
                }else{
                    if(!mb_strpos($data[5], "\n")){
                        $text =pagetxt($data[0], $data[1], $data[4],$dms, 'w');
                        $pos=mb_strpos($text, $data[5]);
                        $len=mb_strlen($data[5]);
                    }else{
                        $str=explode("\n", $data[5]);
                        $text =pagetxt($data[0], $data[1], $data[4],$dms, 'w');
                        if(mb_strpos($text, $str[0])){
                            $pos=mb_strpos($text, $str[0]);
                            $len=mb_strlen($data[5]);
                        }else{
                            $text =pagetxt($data[0], $data[1], $data[4]-1,$dms, 'w');
                            $pos=mb_strpos($text, $str[0]);
                            $len=mb_strlen($data[5]);
                            $data[4]=$data[4]-1;
                        }
                    }
                }
            }
            $res=$dms->AddNote($data, $pos, $len, $user->getID());
            if($data[6]>0){
                $res=$dms->GetNoteDetail($data[6]);
                if($user->getID()!=$res[0]["userid"]){
                    $taruser=$res[0]["userid"];
                    $link="'../out/out.PreviewDocument.php?documentid=".$data[0]."&version=".$data[1]."&page=".$res[0]["pageno"]."&tabStatus=3'";
                    $dms->AddNoti(array(1,$link,"'".$user->getID()."<notibreak>".mb_substr($data[3],0,30)."'",$taruser));
                }
            }
            if($data[8]==2){
                $res=$dms->GetNoteDetail($data[6]);
                $admins=$dms->getAllAdmins();
                foreach ($admins as $key => $admin) {
                    $taruser=$admin->getID();
                    $link="'../out/out.PreviewDocument.php?documentid=".$data[0]."&version=".$data[1]."&page=".$res[0]["pageno"]."&tabStatus=3'";
                    $dms->AddNoti(array(2,$link,"'".$user->getID()."<notibreak>".mb_substr($data[3],0,30)."'",$taruser));
                }
            }
            echo 1;
        }
        else if($_POST['action']=='shownote'){
            $mode=$_POST['mode'];
            if($mode){
                $version='`version`';
                $page='`pageno`';
            }else{
                $version=$_POST['version'];
                $page=$_POST['page'];
            }
            $docid=$_POST['documentid'];
            $viewUser=$_POST['viewuser'];
            if($viewUser=='myself'){
                $viewUser=$user->getID();
            }
            $adminFlag=$_POST['adminFlag'];
            if($adminFlag){
                $res=$dms->GetAdvice($docid, $version, $page, '`userid`');
            }else{
                $res=$dms->GetNote($docid, $version, $page, '`userid`');
            }
            $res=array_reverse($res, true);
            
            if($page!="`pageno`"){
                if($page%2 == 0){
                    $res2=array_reverse($dms->GetNote($docid, $version, $page+1, '`userid`'), true);
                    $res=array_merge($res, $res2);
                }else{
                    $res2=array_reverse($dms->GetNote($docid, $version, $page-1, '`userid`'), true);
                    $res=array_merge($res2, $res);
                }
            }
            $str="<table style='table-layout: fixed;word-wrap: break-word; width:100%;'>";
            if($mode){
                if(isset($_POST['sort'])){
                     $sort=$_POST['sort'];
                     if($sort=='Title'){
                         usort($res, "cmp2");
                     }elseif($sort=='Page'){
                         usort($res, "cmp");
                     }
                }
                    $str=$str."<tr><th style='width:57%;'>Title</th><th style='width:8%;'>Page</th><th style='width:20%;'>Date</th></tr>";
            }
            if(sizeof($res)>0){
                foreach($res as $note){
                    if(($note['parent']==0 ||$mode) &&($viewUser==0 ||$note['userid']==$viewUser)&&($note['visible']==1||$note['userid']==$user->getID() || $adminFlag==1)){
                        $str=$str."<tr>";

                        if($mode){
                            $str=$str. "<td><a href=\"../out/out.PreviewDocument.php?documentid=".$note["documentid"]."&version=".$note["version"]."&page=".$note["pageno"]."\"'>".$dms->GetDocNameByid($note["documentid"])[0]['name']."</a></td><td>".$note['pageno']."</td><td>".$note['time']."</td>";
                            $str=$str. "<td><div style='display:flex;'><span id='delNote' value='".$note['id']."' ><i class='icon-remove'></i></span>"
                                     . "&emsp;<button id='editNote' value='".$note['id']."'>edit</button>"
                                    . "<input type='hidden' id='editvisible".$note['id']."' value='".$note['visible']."'>";
                            if($note['visible']==2){
                                $str=$str."&emsp;<i class='icon-lock'></i>";
                            }
                            $str=$str."</td>";
                        }else{
                             $str=$str."<td><span class='notespan' value='".$note['id']."'style='width:95%; overflow: hidden;'>";
                             $str=$str. "<b>".$dms->GetUserNameByID($note["userid"])[0]['login']."</b>&emsp;".$note['time'];
                        }
                        if($mode){
                            $str=$str. "<tr><td>";
                        }
                        else{
                            $str=$str."<br>";
                        }
                        $str=$str."<b>Subject: </b>". $note['subject'];
                        if($mode){
                            $str=$str. "</td></tr>";
                        }else{
                            $str=$str."<br>";
                        }
                        if($note['txtlen']){
                            $data=pagetxt($note['documentid'], $note['version'], $note['pageno'], $dms, 'w');
                            $quote=mb_substr($data, $note['txtstart'], $note['txtlen']);
                            //file_put_contents("/home/www-data/seeddms51x/debug.txt", mb_strlen($quote).", ".$note['txtlen']."\n", FILE_APPEND);
                            if(mb_strlen($quote)<$note['txtlen']){
                                $data=pagetxt($note['documentid'], $note['version'], $note['pageno']+1, $dms, 'w');
                                $quote=$quote.mb_substr($data, 0, $note['txtlen']-mb_strlen($quote)-2);
                            }
                            if($mode){
                                $str=$str. "<tr><td>";
                            }
                            $str=$str."Quote: <i>".$quote."</i>";
                            if($mode){
                                $str=$str. "</td></tr>";
                            }else{
                                $str=$str."<br>";
                            }
                        }
                        $content=preg_replace_callback('/(https?|http):\/\/[-A-Z0-9+\/\.]*out.PreviewDocument.php\?documentid=(\d+)&version=(\d+)&page=(\d+)/i',
                            function ($matches) use ($dms){
                                return $dms->GetDocNameByid($matches[2])[0]['name'].' Page: '.$matches[4];
                            }, $note['note']);
                        if($mode){
                            $str=$str. "<tr><td colspan='2'><span class='notespan' value='".$note['id']."' id='notespan".$note['id']."'>";
                            $str=$str. mb_substr($content, 0, 150);
                            if(mb_strlen($content)>150){
                                $str=$str."...";
                            }  
                        }else{
                            $str=$str. mb_substr($content, 0, 100);
                            if(mb_strlen($content)>100){
                                $str=$str."...";
                            }
                        }
                        $str=$str."<br><br></span></td></tr>";   
                    }
                }
            }else{
                if(!$mode){
                    $str=$str."No notes yet.";
                }
            }
            $str=$str."</table>";
            //file_put_contents("/home/www-data/seeddms51x/debug.txt", $str."\n", FILE_APPEND);
            echo $str;
        }
        else if($_POST['action']=='shownotedetail'){
            $id=$_POST['id'];
            $mode=$_POST['mode'];
            $res=$dms->GetNoteDetail($id);
            $str="<div><br><div style='width: 98%; display: flex;'><b>".$dms->GetUserNameByID($res[0]["userid"])[0]['login']."</b>&emsp;".$res[0]['time'];
            
            $tmp="<div style='position: relative; left:20%;'><span id='delNote' value='".$id."'><i class='icon-remove'></i></span>";
            $tmp=$tmp."<input type='hidden' id='editvisible' value='".$res[0]['visible']."'>";
            $tmp=$tmp." <button id='editNote' value='".$id."'>edit</button>";
            if($res[0]['visible']==2){
                $tmp=$tmp."<i class='icon-lock'></i>";
            }
            $tmp=$tmp."</div>";
            if($user->getID()==$res[0]["userid"]&&$user->getID()!=2){
                $str=$str.$tmp;
            }
            $str=$str."</div>";
            $str=$str."<b>Subject: </b>". $res[0]['subject'].'<br>';
            if(!$mode){
                $str=$str."<a class='searchreturn' value='".$res[0]['pageno']."' style='padding:0px;'><b>Page: </b>".$res[0]['pageno']."</a>";
            }


            if($res[0]['txtlen']){
                $data=pagetxt($res[0]['documentid'], $res[0]['version'], $res[0]['pageno'], $dms, 'w');
                $quote=mb_substr($data, $res[0]['txtstart'], $res[0]['txtlen']);
                if(mb_strlen($quote)<$res[0]['txtlen']){
                    $data=pagetxt($res[0]['documentid'], $res[0]['version'], $res[0]['pageno']+1, $dms, 'w');
                    $quote=$quote.mb_substr($data, 0, $res[0]['txtlen']-mb_strlen($quote)-2);
                }
                $str=$str."<b>Quote: </b><i>".$quote."</i>";
            }
            if(preg_match('/(https?|http):\/\/[-A-Z0-9+\/\.]*out.PreviewDocument.php\?documentid=(\d+)&version=(\d+)&page=(\d+)/i', $res[0]['note'])){
                $res[0]['note']=preg_replace_callback('/(https?|http):\/\/[-A-Z0-9+\/\.]*out.PreviewDocument.php\?documentid=(\d+)&version=(\d+)&page=(\d+)/i',
                    function ($matches) use ($dms){
                        return '<a href="'.$matches[0].'" style="padding:0px; display:inline"><b>'.$dms->GetDocNameByid($matches[2])[0]['name'].' Page: '.$matches[4].'</b></a>';
                    }, $res[0]['note']);
            }else{
                $res[0]['note']=preg_replace('/(https?:\/\/[^\s]+).*/i', '<a href="$1" style="padding:0px; display:inline">$1</a>', $res[0]['note']);
            }
            $res[0]['note']=str_replace("\n", "<br>", $res[0]['note']);
            $str=$str."<br><br><span id='notes'>".$res[0]['note']."</span><br><br>";
            $arr=$dms->GetNoteVote($id,$user->getID());
            $str=$str."<div class='notevoting' value='".$id."'>";
            if($arr[0]>0){
                $str=$str.'<img class="notevotingIcon" id="like2" value="2" src="../views/bootstrap/images/likeClick.png">';
            }else{
                $str=$str.'<img class="notevotingIcon" id="like1" value="1" src="../views/bootstrap/images/like.png">';
            }
            $str=$str.'<div>'.$arr[1].'</div>';
            if($arr[0]<0){
                $str=$str.'<img class="notevotingIcon" id="dislike2" value="4" src="../views/bootstrap/images/dislikeClick.png">';
            }else{
                $str=$str.'<img class="notevotingIcon" id="dislike1" value="3" src="../views/bootstrap/images/dislike.png">';
            }
            $str=$str.'<div>'.$arr[2].'</div>';
            $str=$str."</div><br><button id='ReplyNotebtn' value='".$id."'>Reply</button><br>";
            
            $str=$str."</div>";
            if($mode){
                echo $res[0]['note'];
                return;
            }
            
            
            $str2="<button id='backbtn' value='".$res[0]['parent']."'>Back</button>";
            
            array_shift($res);
            
            if(sizeof($res)>0){
                $res=array_reverse($res, true);
                foreach($res as $row){
                    //file_put_contents("/home/www-data/seeddms51x/debug.txt", "Lookfor".$row['id'], FILE_APPEND);
                    if($row['visible']==1||$row['userid']==$user->getID()){
                        $str=$str."<div style='padding-left: 20px;'>".treeReply($row['id'], $dms)."</div>";
                    }
                }
            }
            
            echo json_encode([$str, $str2]);
        }
        else if($_POST['action']=='showNoteParent'){
            $id=$_POST['id'];
            $res=$dms->GetNoteDetail($id);
            $str="Subject: ". $res[0]['subject'];
            $str=$str."\n".$res[0]["note"];
            $arr=["Re: ".$res[0]['subject'], $str];
            echo json_encode($arr);
        }
        else if($_POST['action']=='delNote'){
             $id=$_POST['id'];
             $res=$dms->DelNote($id);
        }
        else if($_POST['action']=='subedit'){
            $id=$_POST['id'];
            $note=$_POST['note'];
            $visible=$_POST['visible'];
            //file_put_contents("/home/www-data/seeddms51x/debug.txt", $note, FILE_APPEND);
            if($note==""){
                echo 0;
                return;
            }
            $res=$dms->EditNote($id, $note, $visible);
            echo 1; 
        }
        elseif ($_POST['action']=='notevote') {
            $noteid=$_POST['id'];
            $modi=$_POST['modi'];
            if($user->getID()!=2){
                $dms->modiNoteVote($noteid,$user->getID(),$modi);
            }
            echo 1;
        }
        else if($_POST['action']=='highlighttxt'){
            //[docId, version, text, pageno, pos]
            $data=$_POST['data'];
            $res=$dms->GetHighlight($data[0], $data[1], $data[3], $user->getID());
            if(sizeof($res)>0){
                foreach($res as $highlight){
                    if($highlight['pos']>$data[4] && $highlight['pos']<($data[4]+$data[2])){
                        $res=$dms->RemoveHighlight($data,$highlight['pos'],$user->getID());
                    }
                }
            }
            
            $pos=$data[4];
            $res=$dms->AddHighlight($data, $data[4], $data[2], $user->getID());
            echo 1;
        }
        else if($_POST['action']=='removehighlight'){
            $data=$_POST['data'];
            //[docId, version, pos, pageno]
            $res=$dms->RemoveHighlight($data, $data[2],$user->getID());
            echo 1;
        }
        else if($_POST['action']=='night_toggle'){
            $tmp=$_POST['tmp'];
            if($tmp){
                $session->setNightmode(0);
            }else{
                $session->setNightmode(1);
            }

        }        
        //chenrui
        else if($_POST['action']=='lookupvoting'){
            $str=$_POST['str']; //a sentence highlighted by user
            $strpos=$_POST['strstart'];
            $userStr=$_POST['userStr'];
            $userStrPos=$_POST['userStrStart'];
            $documentid=$_POST['documentid'];
            $version=$_POST['version'];
            $userid=$user->getID();
            $page=$_POST['page'];
            //file_put_contents("/home/www-data/seeddms51x/debug.txt", $str."\n", FILE_APPEND);
            $output1 = shell_exec('curl -s "http://localhost:8983/solr/'.$settings->_solrcore.'/analysis?analysis.fieldtype=text_ik&q=&analysis.fieldvalue='.urlencode($str).'&verbose_output=1"'); //json string
            $decodedOutput1 = json_decode($output1, true, 512, 0); //object
            $ik_output=$decodedOutput1['analysis']['field_types']['text_ik']['index'][1];
            $output2 = shell_exec('curl -s "http://localhost:8983/solr/'.$settings->_solrcore.'/analysis?analysis.fieldtype=text_ik&q=&analysis.fieldvalue='.urlencode($userStr).'&verbose_output=1"'); //json string
            $decodedOutput2 = json_decode($output2, true, 512, 0); //object
            $firstToken=current($decodedOutput2['analysis']['field_types']['text_ik']['index'][1])['text']; //firstToken in userStr, may be broken token
            $lastToken=end($decodedOutput2['analysis']['field_types']['text_ik']['index'][1])['text'];
            $firstTokenPos=mb_strpos($userStr,$firstToken)+$userStrPos;
            $lastTokenPos=mb_strrpos($userStr,$lastToken)+$userStrPos;
            $firstTokenLength=mb_strlen($firstToken);
            $lastTokenLength=mb_strlen($lastToken);
            $tokenExplainArr1 = array(); //array to be returned to ajax
            $tokenExplainArr2 = array();
            $lastTokenFlag=$firstTokenFlag=false;
            for($a=0; $a<count($ik_output); $a++){
                $tokentxt=$ik_output[$a]['text']; //full token in $str 
                $tokenLength=mb_strlen($tokentxt);
                $tokenpos=$strpos; //current position of token   

                //calculate the position of every token
                $splitArr=preg_split('/'.$tokentxt.'/u', $str);
                if(isset($countArr[$tokentxt])){ //$countArr records the frequency of $tokentxt in the $str
                    $countArr[$tokentxt]++;
                    for($i=0; $i<=$countArr[$tokentxt]; $i++){
                        $len=mb_strlen($splitArr[$i]);
                        $tokenpos+=mb_strlen($splitArr[$i]);
                    }
                    $tokenpos+=$countArr[$tokentxt]; 
                }else{
                    $countArr[$tokentxt]=0;
                    $tokenpos+=mb_strlen($splitArr[0]);
                } 

                //valid token range
                if($tokenpos+$tokenLength>$firstTokenPos && $tokenpos<$lastTokenPos+$lastTokenLength){
                    $hidden=false;
                    //find full tokens before $firstToken, store in $firstTokenRec
                    if($tokenpos<=$firstTokenPos && mb_strpos($userStr,$tokentxt)===false){
                        $firstTokenRec[$tokentxt]=$tokenpos;
                        $hidden=true;
                    } 
                    //find full tokens after $lastToken, store in $lastTokenRec
                    if($tokenpos+$tokenLength>$lastTokenPos && mb_strpos($userStr,$tokentxt)===false){
                        $lastTokenRec[$tokentxt]=$tokenpos;
                        $hidden=true;
                    }

                    //check if the token is in tblVotingN, N=$tokenLength
                    $tokenExplainArr1[]=fetchValue(1, $documentid, $page, $version, $userid, $tokentxt, $tokenpos, $tokenLength, 
                            $dms, $hidden, $firstToken, $lastToken, $firstTokenRec, $lastTokenRec, $firstTokenPos, $lastTokenPos);
                    $tokenExplainArr2[]=fetchValue(2, $documentid, $page, $version, $userid, $tokentxt, $tokenpos, $tokenLength, 
                            $dms, $hidden, $firstToken, $lastToken, $firstTokenRec, $lastTokenRec, $firstTokenPos, $lastTokenPos);
                    //est=mb_strpos$tokentxt, $lastToken);
                    if($tokentxt === $lastToken)
                        $lastTokenFlag=true;
                    if($tokentxt === $firstToken)
                        $firstTokenFlag=true;
                }
            }

            //addRec(1, $firstToken, $lastToken, $firstTokenFlag, $lastTokenFlag, $firstTokenRec, $lastTokenRec, $firstTokenPos, $lastTokenPos);
            //combine $lastTokenRec to $firstTokenRec if $firstToken===$lastToken
            if($firstToken===$lastToken && !$firstTokenFlag && $firstTokenRec && $lastTokenRec){ 
                $tokenExplainArr1[]= [
                    array("token"=>$firstToken,"position"=>$firstTokenPos, "footnote"=>"暫無注釋"),
                    array("token"=>$firstToken,"position"=>$firstTokenPos, "firstTokenRec"=>$firstTokenRec+$lastTokenRec)];
                $tokenExplainArr2[]= [
                    array("token"=>$firstToken,"position"=>$firstTokenPos, "footnote"=>"暫無注釋"),
                    array("token"=>$firstToken,"position"=>$firstTokenPos, "firstTokenRec"=>$firstTokenRec+$lastTokenRec)];   
            }      

            //if $firstToken and $lastToken are not full tokens, attach them to the front and end of $tokenExplainArr respectively
            if(!$firstTokenFlag && $firstToken!==$lastToken){
                array_unshift($tokenExplainArr1, [
                    array("token"=>$firstToken,"position"=>$firstTokenPos, "footnote"=>"暫無注釋"),
                    array("token"=>$firstToken, "position"=>$firstTokenPos, "firstTokenRec"=>&$firstTokenRec)]);
                array_unshift($tokenExplainArr2, [
                    array("token"=>$firstToken,"position"=>$firstTokenPos, "footnote"=>"暫無注釋"),
                    array("token"=>$firstToken, "position"=>$firstTokenPos, "firstTokenRec"=>&$firstTokenRec)]);
            }
            if(!$lastTokenFlag && $firstToken!==$lastToken){
                $tokenExplainArr1[]= [
                    array("token"=>$lastToken,"position"=>$lastTokenPos, "footnote"=>"暫無注釋"),
                    array("token"=>$lastToken,"position"=>$lastTokenPos, "lastTokenRec"=>&$lastTokenRec)];
                $tokenExplainArr2[]= [
                    array("token"=>$lastToken,"position"=>$lastTokenPos, "footnote"=>"暫無注釋"),
                    array("token"=>$lastToken,"position"=>$lastTokenPos, "lastTokenRec"=>&$lastTokenRec)];
            }

            echo json_encode([$tokenExplainArr1,$tokenExplainArr2], JSON_UNESCAPED_UNICODE);
        }
        else if($_POST['action']=='modifyvote'){
            $like=$_POST['like']; 
            $dislike=$_POST['dislike'];
            $documentid=$_POST['documentid'];
            $version=$_POST['version'];
            $page=$_POST['page'];
            $position=$_POST['position'];
            $footnote=$_POST['footnote'];
            $tokenLength=$_POST['tokenLength'];
            $userid=$user->getID();
            $dictId=$_POST['dictId'];

            $res1=$dms->Modifyvote($dictId,$documentid, $page, $version, $userid, $position, $footnote, $like, $dislike, $tokenLength);
            echo json_encode($res1, JSON_UNESCAPED_UNICODE);
        }
        else if($_POST['action']=='encoderedirect'){
            $data=$_POST['url']; 
            //file_put_contents("/home/www-data/seeddms51x/debug.txt", $data."\n", FILE_APPEND);
            $ciphering = "AES-128-CTR"; 
            //file_put_contents("/home/www-data/seeddms51x/debug.txt", $_COOKIE['mydms_session']."\n", FILE_APPEND);
            $encryption_iv = '1234567891011121'; 

            $encryption = openssl_encrypt($data, $ciphering, $_COOKIE['mydms_session'], 0, $encryption_iv); 
            //return rtrim( strtr( base64_encode( $encryption ), '+/', '-_'), '=');
            echo rtrim( strtr( base64_encode( $encryption ), '+/', '-_'), '=');
        }
        else if($_POST['action']=='readNoti'){
            $id=$_POST['id'];
            $dms->NotiStatus($user->getID(),$id,2);
            echo 1;
            
        }else if($_POST['action']=='recordPage'){
            $docid=$_POST['documentid'];
            $page=$_POST['page'];
            $user->updateReadingHistory(0, $docid, $page);
            
        }else if($_POST['action']=='recordTab'){
            $docid=$_POST['documentid'];
            $tabStatus=$_POST['tabStatus'];
            $user->updateReadingHistory(1, $docid, $tabStatus);
        }else if($_POST['action']=='recordDisPage'){
            $docid=$_POST['documentid'];
            $disPage=$_POST['disPage'];
            $user->updateReadingHistory(2, $docid, $disPage);
        }else if($_POST['action']=='agree'){
            $user->setFirstLogin();
        }
    }
    
    function cmp($a, $b){
        return ($a['pageno']<$b['pageno']) ? -1:1;

    }
    function cmp2($a, $b){
        if($a['documentid']==$b['documentid']){
             return ($a['pageno']<$b['pageno']) ? -1:1;
        }
        return ($a['documentid']<$b['documentid']) ? -1:1;
    }

    function selfNote($mode, $note, $str){
        if($mode){
            $str=$str. "<td><a href=\"../out/out.PreviewDocument.php?documentid=".$note["documentid"]."&version=".$note["version"]."&page=".$note["pageno"]."\"'>".$dms->GetDocNameByid($note["documentid"])[0]['name']."</a></td><td>".$note['pageno']."</td><td>".$note['time']."</td>";
            $str=$str. "<td><div style='display:flex;'><span id='delNote' value='".$note['id']."' ><i class='icon-remove'></i></span>"
                     . "&emsp;<button id='editNote' value='".$note['id']."'>edit</button>"
                    . "<input type='hidden' id='editvisible".$note['id']."' value='".$note['visible']."'>";
            if($note['visible']==2){
                $str=$str."&emsp;<i class='icon-lock'></i>";
            }
            $str=$str."</td>";
        }else{
             $str=$str."<td><span class='notespan' value='".$note['id']."'style='width:95%; overflow: hidden;'>";
             $str=$str. "<b>".$dms->GetUserNameByID($note["userid"])[0]['login']."</b>&emsp;".$note['time'];
        }
        if($mode){
            $str=$str. "<tr><td>";
        }
        else{
            $str=$str."<br>";
        }
        $str=$str."<b>Subject: </b>". $note['subject'];
        if($mode){
            $str=$str. "</td></tr>";
        }else{
            $str=$str."<br>";
        }
        return $str;
    }
    
    /*function pagetxt($docid, $version, $page,$dms){
        $file = fopen('../elib/data/1048576/'.$docid.'/'.$version.'/prepend.txt',"r");
        $pre=0;
        while(! feof($file))
        {
            $str= fgets($file);
            $spl = explode(" ", $str);
            if($page==$spl[0]){
                $pre= $spl[1];
            }
        }
        $data = file_get_contents('../elib/data/1048576/'.$docid.'/'.$version.'/'.$page.'.txt', TRUE, NULL, $pre*3);
            $output = shell_exec('curl "http://localhost:8983/solr/'.$settings->_solrcore.'/analysis?analysis.fieldtype=text_ik&q=&analysis.fieldvalue='.urlencode($data).'&verbose_output=1"');
            $decodedOutput = json_decode($output, true, 512, 0);
            $ik_output=$decodedOutput['analysis']['field_types']['text_ik']['index'][1];
 
            foreach ($ik_output as $token){
                $res=$dms->lookupDict($token['text']);
                    if(sizeof($res)>0){
                        $arr[]=$token['text'];
                    }
            }
                    
            $arr=array_unique($arr);
            usort($arr,'cmp');
            for($i=0;$i<sizeof($arr);$i++){
                $data = str_replace($arr[$i], "<span class='keyword'>".$arr[$i]."</span>", $data);
            }
        return $data;
    }*/
    
    
    //pagetxt($docid, $version, $page, $dms, $align, 'w');
    function pagetxt($docid, $version, $page, $dms, $align){
//        $file = fopen('../elib/data/1048576/'.$docid.'/'.$version.'/prepend.txt',"r");
//        $pre=0;
//        while($file &&! feof($file))
//        {
//            $str= fgets($file);
//            $spl = explode(" ", $str);
//            if(sizeof($spl)==2&&$page==intval($spl[0])){
//                $pre= intval($spl[1]);
//            }
//        }
//        fclose($file);
//        if (file_exists('../elib/data/1048576/'.$docid.'/'.$version.'/'.$page.'_display.txt')) {
//            $data = file_get_contents('../elib/data/1048576/'.$docid.'/'.$version.'/'.$page.'_display.txt', TRUE, NULL, $pre*3);
//        }else{
//            $data='';
//        }
        if (file_exists('../elib/data/1048576/'.$docid.'/'.$version.'/'.$align.'/'.$page.'.txt')) {
            $data = file_get_contents('../elib/data/1048576/'.$docid.'/'.$version.'/'.$align.'/'.$page.'.txt', TRUE, NULL);
        }else{
            $data='';
        }
        return $data;
    }
    
    
    function treeReply($id, $dms){
        $res=$dms->GetNoteDetail($id);
            $str="<span class='notespan' value='".$res[0]['id']."'><br><b>".$dms->GetUserNameByID($res[0]["userid"])[0]['login']."</b>&emsp;".$res[0]['time'];
            $str=$str."<br><b>Subject: </b>". $res[0]['subject'];
            $res[0]['note']=preg_replace_callback('/(https?|http):\/\/[-A-Z0-9+\/\.]*out.PreviewDocument.php\?documentid=(\d+)&version=(\d+)&page=(\d+)/i',
                function ($matches) use ($dms){
                    return $dms->GetDocNameByid($matches[2])[0]['name'].' Page: '.$matches[4].'</b></a>';
                }, $res[0]['note']);
            $str=$str."<br>". mb_substr($res[0]['note'], 0, 100);
             if(mb_strlen($res[0]['note'])>100){
                $str=$str."...";
            }
            $str=$str."<br><br></span>";
            //file_put_contents("/home/www-data/seeddms51x/debug.txt", $str, FILE_APPEND);
            array_shift($res);
        if (sizeof($res)>0){
            //file_put_contents("/home/www-data/seeddms51x/debug.txt", "Have reply", FILE_APPEND);
            $res=array_reverse($res, true);
            foreach($res as $row){
                //file_put_contents("/home/www-data/seeddms51x/debug.txt", "Lookfor".$row['id'], FILE_APPEND);
                if($row['visible']==1||$row['userid']==$user->getID()){
                    $str=$str."<div style='padding-left: 20px;'>".treeReply($row['id'], $dms)."</div>";
                }
            }
        }
        return $str;
    }
    
    function pageHighlight($docid,$version, $page,$userid,$dms,$align,$extra=0){
        $text=pagetxt($docid, $version, $page, $dms, $align);
        $res=$dms->GetHighlight($docid,$version, $page,$userid);
        if($extra>0){
            $highlighttxt=mb_substr($text,0,$extra,"UTF-8");
            $highlighttxt="<mark>". $highlighttxt."</mark>";
            $text=mb_substr_replace($text, $highlighttxt, 0, $extra);
            $count=1;
        }else{
            $count=0;}
        if(sizeof($res)>0){ //$res => pos and len of highlight
            $extra=0;
            foreach($res as $highlight){
                $highlighttxt=mb_substr($text,$highlight['pos']+13*$count,$highlight['len'],"UTF-8");
                if(mb_strlen($highlighttxt)<$highlight['len']){
                   $extra=$highlight['len']-mb_strlen($highlighttxt)-2;
                }
                $highlighttxt="<mark>". $highlighttxt."</mark>";

                $text=mb_substr_replace($text, $highlighttxt, $highlight['pos']+13*$count, $highlight['len']);
                $count++;

            }         
        }
        return array($text,$extra);
    }
    
    function mb_substr_replace($original, $replacement, $position, $length){
        $startString = mb_substr($original, 0, $position, "UTF-8");
        $endString = mb_substr($original, $position + $length, mb_strlen($original), "UTF-8");

        $out = $startString . $replacement . $endString;

        return $out;
    }
    
    
    function fetchValue($dictId, $documentid, $page, $version, $userid, $tokentxt, $tokenpos, $tokenLength, $dms, $hidden,
             $firstToken, $lastToken, $firstTokenRec, $lastTokenRec, $firstTokenPos, $lastTokenPos){
	$res1=$dms->checkToken($dictId, $documentid, $page, $version, $tokentxt, $tokenpos, $tokenLength); 
        if($res1 == false){ //fail to find the token in tblVotingN
            $res2=$dms->LookupDict($dictId, $tokentxt);
            if(sizeof($res2)>0){//the token is found in Dict
                if($dictId===1){
                    dict1_addToken($dms, $res2, $dictId, $documentid, $page, $version, $userid , $tokentxt, $tokenpos, $tokenLength);
                }
                else if($dictId===2){
                    dict2_addToken($dms, $res2, $dictId, $documentid, $page, $version, $userid , $tokentxt, $tokenpos, $tokenLength);
                }    
            }
        }else{ //find the token in tblVotingN
            foreach($res1 as $row1){//$row: explain
                $footnote=$row1['footnote'];
                //token info may be found in tblVotingN, but its user-related info may not be found in tblUserVoting
                $res3=$dms->AddVotingUser($dictId, $documentid, $page, $version, $userid , $tokentxt, $tokenpos, $footnote, $tokenLength);
            }
        }
        //fetch token and user-related info
        $res4=$dms->LookupVoting($dictId, $documentid, $page, $version, $userid, $tokentxt, $tokenpos, $tokenLength);
        $arr= array();
        if(sizeof($res4)>0){
            for($i=0; $i<count($res4); $i++){
                if($dictId===1)
                    $arr[] = array("token"=>$tokentxt,"footnote"=>$res4[$i]['footnote'], "position"=>$res4[$i]['position'], "hidden"=>$hidden,
                    "likeIcon"=>$res4[$i]['likeIcon'], "dislikeIcon"=>$res4[$i]['dislikeIcon'], "like"=>$res4[$i]['like'], "dislike"=>$res4[$i]['dislike']);
                else if($dictId===2){
                    $arr[] = array("token"=>$tokentxt,"footnote"=>$res4[$i]['footnote'], "position"=>$res4[$i]['position'], "hidden"=>$hidden,
                    "likeIcon"=>$res4[$i]['likeIcon'], "dislikeIcon"=>$res4[$i]['dislikeIcon'], "like"=>$res4[$i]['like'], "dislike"=>$res4[$i]['dislike'],
                    "attribute"=>$res4[$i]['attribute'], "zhuyin"=>$res4[$i]['zhuyin'], "pinyin"=>$res4[$i]['pinyin']);
                }
            }    
        }else{
            $arr[] =  array("token"=>$tokentxt, "hidden"=>$hidden, "position"=>$tokenpos, "footnote"=>"暫無注釋");     
        }
        //add firstTokenRec & lastTokenRec
        if($firstToken===$lastToken && $firstToken===$tokentxt && $firstTokenRec && $lastTokenRec) 
           $arr[]= array("token"=>$firstToken,"position"=>$firstTokenPos, "firstTokenRec"=>$firstTokenRec+$lastTokenRec);  
        else if($tokentxt===$firstToken && $tokenpos===$firstTokenPos && $firstTokenRec)
            $arr[]=array("token"=>$tokentxt, "firstTokenRec"=>$firstTokenRec, "position"=>$tokenpos);
        else if($tokentxt===$lastToken && $tokenpos===$lastTokenPos && $lastTokenRec)
            $arr[]=array("token"=>$tokentxt, "lastTokenRec"=>$lastTokenRec, "position"=>$tokenpos);
        return $arr; 
    }
    
//new function
    function dict1_addToken($dms, $res, $dictId, $documentid, $page, $version, $userid , $tokentxt, $tokenpos, $tokenLength){
        foreach($res as $row){//$row: explain
            $footnote=$row['value'];
            $addToken=$dms->AddToken($dictId, $documentid, $page, $version, $tokentxt, $tokenpos, $footnote, $tokenLength);
            $res3=$dms->AddVotingUser($dictId, $documentid, $page, $version, $userid , $tokentxt, $tokenpos, $footnote, $tokenLength);
        }
    }
    
//new function
    function dict2_addToken($dms, $res, $dictId, $documentid, $page, $version, $userid , $tokentxt, $tokenpos, $tokenLength){
        foreach($res as $row){
            $zhuyin=$row['zhuyin'];
            $pinyin=$row['pinyin'];
            $rawData=preg_split('/\n/u', $row['value']);
            if(!strpos($rawData[0],"]"))
                  $attribute="";  
            foreach($rawData as $footnote){
                if(strpos($footnote, "]")!==false){
                    $attribute=$footnote;
                }else{
                    $addToken=$dms->AddToken($dictId, $documentid, $page, $version, $tokentxt, $tokenpos, $footnote, $tokenLength, $zhuyin, $pinyin, $attribute);
                    $res3=$dms->AddVotingUser($dictId, $documentid, $page, $version, $userid , $tokentxt, $tokenpos, $footnote, $tokenLength);
                }
            }
        }
    }

?>