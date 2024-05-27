<?php
/**
 * Implementation of ViewFolder view
 *
 * @category   DMS
 * @package    SeedDMS
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */

/**
 * Include parent class
 */
require_once("class.Bootstrap.php");

/**
 * Include class to preview documents
 */
require_once("SeedDMS/Preview.php");

/**
 * Class which outputs the html page for ViewFolder view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_HomePage extends SeedDMS_Bootstrap_Style {
	function js() { /* {{{ */
		$user = $this->params['user'];
		$expandFolderTree = $this->params['expandFolderTree'];
		$enableDropUpload = $this->params['enableDropUpload'];
                $firstLogin=$this->params['firstLogin'];
                header('Content-Type: application/javascript; charset=UTF-8');

    ?>
		function folderSelected(id, name) {
                        window.location = '../out/out.ViewFolder.php?folderid=' + id;
                }
                $(document).ready(function(){
                    $('#notitbl > tbody > tr').live('click',function(){
                        var href=$(this).data('href');
                        var id=$(this).data('id');
                        $.ajax({
                                type:'POST',
                                url:'../elib/elib_ajax.php',
                                data: {'action': 'readNoti', 'id':id},
                                success:function(response){
                                    window.location = href;
                                }
                        });
                    });
                    $('#agree').live('click',function(){
                        $.ajax({
                            type:'POST',
                            url:'../elib/elib_ajax.php',
                            data: {'action': 'agree'},
                        });
                    });
                    $('#disagree').live('click',function(){
                        window.location = "../op/op.Logout.php";
                    });
                    $('#disclaimer').modal({ show: false});
                    <?php
                    $now=time();
                    if ($firstLogin==1) {
                    ?>
                        $('#disclaimer').attr("hidden", false);
                        $('#disclaimer').modal('show');
                    <?php
                    }
                    ?>
                        
                });
    <?php
                $this->printelib_Js();
		$this->printNewTreeNavigationJs("1", M_READ, 0, '', $expandFolderTree == 2, "u");

	} /* }}} */
        
        function listdoc($documents, $title, $previewer){
            print "<table class=\"table table-condensed table-hover\" id='".$title."Row' style='display:none;'>";
		print "<thead>\n<tr>\n";
		print "<th></th>\n";	
		print "<th>".getMLText("name")."</th>\n";
		print "<th>".getMLText("status")."</th>\n";
		print "<th>".getMLText("action")."</th>\n";
		print "</tr>\n</thead>\n<tbody>\n";
                $documents = array_slice($documents, 0, 5, true);
                foreach($documents as $document) {
                    $document->verifyLastestContentExpriry();
                    echo $this->documentListRow($document, $previewer);
		}
                echo "</tbody>\n</table>\n";
                        
                print '<div style="display:inline-flex;" id="'.$title.'Pic">';
                foreach($documents as $document) {
                    $document->verifyLastestContentExpriry();
                    echo $this->documentListPic($document, $previewer);
                }
                print'</div>';
        }
        function GetSumAccess($core){
            $output = shell_exec('curl -s "http://localhost:8983/solr/'.$core.'/select?fl=id&q=id:*1/1.txt&stats.field=access_count&stats=true&wt=json"');
            $decodedOutput = json_decode($output, true, 512, 0);
            return $decodedOutput["stats"]["stats_fields"]["access_count"]["sum"];
        }
        function GetRank($docid,$core,$sum){
            //$sum=$this->GetSumAccess($core);
            //$sum=100;
            $url="http://localhost:8983/solr/".$core."/select?fl=id%2Cscore%2C%5Bfeatures%5D&q=id:*/1048576/".$docid."/*&rq=%7B!ltr%20model%3DmyModel%20reRankDocs%3D100%20efi.avgsumaf%3D".$sum."%20efi.term1%3D%E7%BE%8A%20efi.term2%3D%E6%95%99%20efi.term3%3D%E8%81%96%E4%BA%BA%7D";
            //var_dump($url);
            $output = file_get_contents($url);
            $decodedOutput = json_decode($output, true, 512, 0);
            //print_r($url);
            //print "<br>";
            if(isset($decodedOutput["response"]["docs"][0])){
                return $decodedOutput["response"]["docs"][0]["score"];
            }else{
                return 0;
            }
            
        }
      
	function show() { /* {{{ */
            function cmp($a, $b){
                return ($a['score']>$b['score']) ? -1:1;

            }  

		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$enableFolderTree = $this->params['enableFolderTree'];
		$enableDropUpload = $this->params['enableDropUpload'];
		$expandFolderTree = $this->params['expandFolderTree'];
		$cachedir = $this->params['cachedir'];
		$previewwidth = $this->params['previewWidthList'];
		$previewconverters = $this->params['previewConverters'];
		$timeout = $this->params['timeout'];
                $settings=$this->params['settings'];
                
                $previewer = new SeedDMS_Preview_Previewer($cachedir, $previewwidth, $timeout);
		$previewer->setConverters($previewconverters);
                
                
                
		
                
                $this->htmlStartPage();
                $this->globalNavigation(); 
                $this->printsearchbar();
		$this->contentStart();
                print '<div class="row-fluid" >';
                
                
                $this->printmaincontentcontainer();
                $this->printspan4Controlbtn();
                
                print '<div class="span4" style="margin-left:0px;">';
                if(!$user->isGuest()){
                    
                    $this->contentContainerStart();
                    $this->contentHeading(getMLText("notification"));
                        $res=$user->getUserNoti();
                        print '<div style="max-height:150px;overflow-y:scroll;">';
                        print $res;
                        print '</div>';
                    $this->contentContainerEnd();
                    
                    $this->contentContainerStart();
                    $this->contentHeading(getMLText("folders"));
                    $this->printNewTreeNavigationHtml("1", M_READ, 0, '',$expandFolderTree == 2, 's'); //'u'
                    $this->contentContainerEnd();
                }else{
                    $this->contentContainerStart();
                    $this->contentHeading("Menu");
                    
                    print'<li class="menuHref"><a href="#緣起">緣起</a></li><br>';
                    print'<li class="menuHref"><a href="#成立歷史">成立歷史</a></li><br>';
                    print'<li class="menuHref"><a href="#聯絡">聯絡</a></li><br>';
                    $this->contentContainerEnd();
                }
                print'</div>';
                
                print '<div class="span8" id="span8mainContent">';
                if(!$user->isGuest()){
                    
                    $this->contentHeading(getMLText("RecommendedForYou"), false, true,"RecommendedForYou");
                    $this->contentContainerStart();
                        $docarr=$dms->GetDocID();
                        $rankdoc = array();
                        $sum=$this->GetSumAccess($settings->_solrcore);
                        foreach($docarr as $row){
                            array_push($rankdoc, array('id'=> $row['id'], 'score' => $this->GetRank($row['id'],$settings->_solrcore,$sum)));
                        }
                        usort($rankdoc, 'cmp');
                        $documents=array();
                        foreach ($rankdoc as $row) {
                            array_push($documents, $dms->getDocument($row["id"]));
                        }
                        $documents = SeedDMS_Core_DMS::filterAccess($documents, $user, M_READ);
                        $this->listdoc($documents, "RecommendedForYou", $previewer);
                    //print 'Debugging';

                    $this->contentContainerEnd();
                
                
                
                   
                    $this->contentHeading(getMLText("ReadingHistory"), false, true, "ReadingHistory");
                    $this->contentContainerStart();
                    if($user->getReadingHistory()){
                        $his=array_reverse($user->getReadingHistory());
                        $documents=array();

                            foreach ($his as $row) {
                                if($dms->getDocument($row)){
                                    array_push($documents, $dms->getDocument($row));
                                }
                                 
                            }
                            $documents = SeedDMS_Core_DMS::filterAccess($documents, $user, M_READ);
                            $this->listdoc($documents, "ReadingHistory", $previewer);

                    }else{
                            print 'Empty!';
                    }
                    $this->contentContainerEnd();
                
                    
                    $this->contentHeading(getMLText("NewReleases"), false, true,"NewReleases");
                    $this->contentContainerStart();
                        $res=array_reverse($dms->GetDocID());
                        $documents=array();
                        foreach ($res as $row) {
                            array_push($documents, $dms->getDocument($row["id"]));
                        }

                        $this->listdoc($documents, "NewReleases", $previewer);
                    $this->contentContainerEnd();
                }else{
                    $this->contentHeading("緣起");
                    $this->contentContainerStart('','緣起');
                    print'道院暨紅卍字會是起源於中國山東省的國際性宗教慈善組織。​
                          <br><br>世界紅卍字會成立日定於1923年2月5日的壬戌立春日。';
                    $this->contentContainerEnd();
                    
                    $this->contentHeading("香港紅卍字會成立歷史");
                    $this->contentContainerStart('','成立歷史');
                    print'香港道院、香港紅卍會成立於1931年農曆辛未十月初一日。成立之初，由於資金緊絀，未能自置院舍，只租賃港島中區半山鐵崗一幢樓房為暫時院址。其後因向政府註冊未獲批准，被迫搬至西環的太白台，至1933年又再遷回鐵崗。​
                           <br><br>關於院舍的建構，據1938年十月十一日訓文所錄：“將來院宇動工，須以三層，按三才也。層分十二，以取妙化也。三之極，造一亭，以為設壇之需。師命名於亭，曰南光二字也。”這就是現時所見香港紅卍字會院舍的設計和建構之依據。​
                           <br><br>1938年的夏天，院會同寅向港府提出以“香港紅卍字會”名義正式登記注冊，隨即獲港府准予通過立案。一經正名，香港院會的道慈事務，迅即開展。​
                           <br><br>公曆1940年4月23日，香港紅卍字會新會所落成，下午三時，港督夫人親臨，並主持啟門開幕禮。當日到賀嘉賓，有政府的高官及華人代表，紳商名流及社會賢達，包括顏惠慶、杜月笙、王曉籟、葉蘭泉、顏成坤、香港大學教授許地山……中西名流仕女數百人，濟濟一堂，盛極一時。​';
                    $this->contentContainerEnd();
                    
                    $this->contentHeading("聯絡");
                    $this->contentContainerStart('','聯絡');
                    print'電話：  （852）2570-0965​
                          <br><br>傳真：  （852）2571-9253​
                          <br><br>地址  ：  香港銅鑼灣金龍台25號​';
                    $this->contentContainerEnd();
                }
                
                print'</div>';
                print'</div>';
                print'</div>';
                print'<div class="modal fade" id="disclaimer" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true" data-backdrop="static" data-keyboard="false" hidden>
                <div class="modal-dialog modal-dialog-centered" role="document">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title">Disclaimer</h5>
                    </div>
                    <div class="modal-body" style="font-size:14px">
                     <p> Thanks for participating this e-library system test. By accepting the invitation to use this e-library system (termed as "the system in the following), the user agrees to the following terms:</p>
                     <p> 1. The user understands that the system is still under development and may be unstable at some point; </p>
                     <p> 2. A user\'s access is for the authorized individual only and the user should not release the account access information to another person. </p>
                     <p> 3. The user will not tamper with the system to download the book database or other internal system information;</p>
                     <p> 4. To ensure the system\'s performance and service, the system operator may suspend or terminate the user\'s access (in part or whole) at some point. </p>
                     <p> 5. Users are welcome to report the system errors and improvements to tydocspreservation@gmail.com.  
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-primary" data-dismiss="modal" id="agree">Agree</button>
                      <button type="button" class="btn btn-secondary" id="disagree">Disagree</button>
                    </div>
                  </div>
                </div>
              </div>';
                
		$this->contentEnd();
                
		$this->htmlEndPage(true);
	} /* }}} */
}

?>
