<?php
//header("Content-type:application/json");
include("BootStrap.php");
include("../inc/inc.Settings.php");
include_once("../inc/inc.Language.php");

if($_SERVER['REQUEST_METHOD']=='POST'){
    //file_put_contents("/home/www-data/seeddms51x/debug.txt", "Click".$_POST['action'], FILE_APPEND);

    if($_POST['action']=='search'){
        $docid=$_POST['docid'];
        $query=$_POST['query'];
        $mode=$_POST['mode'];
        $version=$_POST['version'];
        $response='';
        $recs=callSolr($query, $docid, 1, $settings->_solrcore,$version, $settings->_contentDir);
        if($recs==NULL){
            $response=getMLText("no_match");
        }else{
            if($mode==1){
               $response="<div id='docresponse".$docid."' style='overflow-y: scroll;max-height: 300px;'>"; 
            }
            foreach ($recs as $rec) {
                //file_put_contents("/home/www-data/seeddms51x/debug.txt", $rec['hl']."\n",FILE_APPEND);
                if($mode==0){
                    $response=$response."<a class='searchreturn' value='".$rec['page']."'>"."Page: ".$rec['page']."</a>".$rec['hl']."<br><br>"; 
                }else{
//                    $response=$response."<br><a href=\"../out/out.PreviewDocument.php?documentid=".$docid."&version=".$version."&page=".$rec['page'].
//                        "&query=".$query."\"><span style=\"font-size: 85%; font-style: italic; color: #666; \">"."page:".$rec['page']."<br>".$rec['hl']." </span></a>"; 
//                    $response=$response."<br><a href=\"#\" id=\"direct\" data-docid=\"".$docid."\" data-version=\"".$version."\" data-page=\"".$rec['page'].
//                        "\" data-query=\"".$query."\"><span style=\"font-size: 85%; font-style: italic; color: #666; \">"."page:".$rec['page']."<br>".$rec['hl']." </span></a>"; 
                
                    $response=$response."<form id=\"resultForm\" method=\"post\" action=\"../out/out.PreviewDocument.php?documentid=".$docid."&version=".$version."\">"
                    . "<input type=\"hidden\" name=\"page\" value=\"".$rec['page']."\"/>"
                    . "<input type=\"hidden\" name=\"query\" value=\"".$query."\"/>"
                    . "<input type=\"hidden\" name=\"tabStatus\" value=\"0".$tabStatus."\"/>"
                    . "<span id=\"result\" style=\"font-size: 85%; font-style: italic; color: #666; \">"."page:".$rec['page']."<br>".$rec['hl']." </span>"
                    . "</form>";
                }
            }
            if($mode==1){
               $response=$response."</div>"; 
            }
        }
        echo $response;

    }
}



function cmp($a, $b){
    return ($a['page']<$b['page']) ? -1:1;
}

function callSolr($querySolr, $docid, $mode,$solrcore,$version, $prePath){
    //file_put_contents("../elib/data/searchlog.txt", $user->getID()."|".$query."|".date("Y-m-d")."|".date("H:i:s")."\n", FILE_APPEND);

    if($version==0){
        $version='[0-9]+'; //*
    }
    if($querySolr==NULL){
        return false;
    }
    $options = array
    (
        'hostname' => 'localhost',
        'port'     => '8983',
        'path'     => 'solr/'.$solrcore,
    
    
    );
    $client = new SolrClient($options);

    $query = new SolrQuery();
    //id:/\/home\/e-library\/NetBeansProjects\/e-library\/data\/1048576\/274\/1\/[0-9]+.txt/  && 易經
//    $docid="id:/\/home\/e-library\/NetBeansProjects\/e-library\/data\/1048576\/$docid\/$version\/[0-9]+.txt/ && ";
    $prePath= str_replace('/', '\/', $prePath);
    $docid="id:/".$prePath."1048576\/$docid\/$version\/[0-9]+.txt/ && ";
    $term = $docid. $querySolr ;
    $num = 1;

    $query->setQuery($term);
    $query->setStart(0);
    if($mode==1){
        $query->setRows(300);
    }else{
        $query->setRows(1);
    }
    
    $query->setHighlight(true);
    $query->addHighlightField('id');
    $query->addHighlightField('text');
    $query->setHighlightFragsize(75,'text');
    $query->setHighlightSnippets($num, 'text');
    $query->setHighlightSimplePost('</mark>');
    $query->setHighlightSimplePre('<mark>');
    $query->addField('id');
    //$query->addSortField('id', 0);
    //file_put_contents("/home/www-data/seeddms51x/debug.txt", "query: $query\n",FILE_APPEND);

    $query_response = $client->query($query);

    $query_response->setParseMode(SolrQueryResponse::PARSE_SOLR_DOC);
    $response = $query_response->getResponse();

    //print_r($response);
    $array = $response->highlighting->getPropertyNames();
    
    
    //$user = "solr";
    //$psword = "SolrRocks";
    //$output = shell_exec('curl "http://'.$user.':'.$psword.
    //        '@localhost:8983/solr/cjkCore_SeedDMS/test-point?df=text&hl=on&q='
    //       .urlencode($term).'&tv.positions=true&wt=json"');
    //$decodedOutput = json_decode($output, true, 512, 0);
    //print_r($decodedOutput);
    
    $recs = array();
   
    if(!$array){
        //var_dump("No documents match your search!");
        return;
    }
    for ($count=0; $count<count($array); $count++){
        //file_put_contents("/home/ritawcf/testing.txt", "response: $array[$count]\n",FILE_APPEND);
        #filter name only
        //var_dump($array[$count]);
        $split= explode('/', $array[$count]);
        //var_dump($split);
        $tmp=sizeof($split);
        $ver=$split[$tmp-2];
        //var_dump($ver);  
        $id=$split[$tmp-3];
        //var_dump($id);
        $page=basename($split[$tmp-1], '.txt');
        //var_dump($page);

        /*#call function in .../pear/SeedDMS/Core/ClassDMS.php
        //$id = $dms->GetIDByName($name);
        //$recs[] = array('document_id'=>$id);*/
        
        $offset=$response->offsetGet('highlighting');
        $prop = $offset->getPropertyNames();
        //$max = count($offset[$prop[$count]]['text']);

        $recs[] = array('document_id'=>$id, 'hl'=>$offset[$prop[$count]]['text'][0], 'page'=>$page, 'ver'=>$ver);
        //for($k=0; $k<count($recs); $k++){
          //  file_put_contents("/home/www-data/seeddms51x/debug.txt", $recs[$k]['document_id']."_".$recs[$k]['page']."\n",FILE_APPEND);
        //}
        
        
        //$recs[] = array('document_id'=>$id, 'hl'=>$offset[$prop[$count]]['text'][0], 'page'=>$page, 'ver'=>$ver);

        /*if($max<$num){
            $listN =$max;}
        else {
            $listN =$num;}
        //var_dump($decodedOutput["highlighting"][$array[$count]]["text"]);
        for($x = 0; $x <= $listN-1; $x++) {
            //var_dump($decodedOutput["highlighting"][$array[$count]]["_text_"][$x]);
            //var_dump($offset[$prop[$count]]['_text_'][$x]);


            //$recs[] = array('document_id'=>$id, 'hl'=>$offset[$prop[$count]]['text'][$x], 
                //'position'=>$decodedOutput["highlighting"][$array[$count]]["text"][$x], 
                //'positionarr'=>$decodedOutput["highlighting"][$array[$count]]["text"]);
             $recs[] = array('document_id'=>$id, 'hl'=>$offset[$prop[$count]]['text'][$x], 'page'=>$page);

            //var_dump("id: " . $recs[$count+$x]['document_id']);
            //var_dump($recs[$count+$x]['hl']);
            //var_dump("position:".$recs[$count+$x]['position']);

        }*/
    }
    usort($recs, "cmp");
    /*for($k=0; $k<count($recs); $k++){
        file_put_contents("/home/www-data/seeddms51x/debug.txt", $recs[$k]['document_id']."_".$recs[$k]['page']."\n",FILE_APPEND);
    }*/

    //$recs=array_merge($recs, $sort);
    //var_dump($recs);

    return $recs;

}

