<?php
/**
 * Implementation of ViewDocument view
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
 * Class which outputs the html page for ViewDocument view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */


class SeedDMS_View_ManageBookmark extends SeedDMS_Bootstrap_Style {


	/**
	 * Output a single attribute in the document info section
	 *
	 * @param object $attribute attribute
	 */
    
    
    
    function js(){
        $this->printelib_Js();

    }
    
    function printselectspan($tab){
        if($tab==0){
            $res=$this->params['dms']->GetBookmark($this->params['user']->getId(), 0, 0, 0);
            $selectid='bookmarkview';
        }else if($tab==1){
            $data=['docid'=>'`documentid`','version'=>'`version`', 'userid' => $this->params['user']->getId()];
            $res=$this->params['dms']-> GetMyNote($data);
            $selectid='noteview';
        }
        $res=$this->params['dms']->GetDocumentNameList($res);
        
        $options[] = array("-1", "Show all");
        foreach($res as $row){
            $options[] = array($row['id'],$row['name']);
        }
        $this->formField(
            null,
            array(
                'element'=>'select',
                'id'=>$selectid,
                'options'=>$options
            )
        );
        $options = array(); 
    }
    
    
	function show() { /* {{{ */
            
            
                $this->htmlAddHeader('<script type="text/javascript" src="../elib/Manage.js"></script>'."\n", 'js');
                $this->htmlAddHeader('<script type="text/javascript" src="../styles/'.$this->theme.'/bootbox/bootbox.min.js"></script>'."\n", 'js');
                $this->htmlAddHeader('<link href="../elib/Manage.css" rel="stylesheet"></link>'."\n", 'css');
                $this->htmlAddHeader('<link href="../elib/elib_project.css" rel="stylesheet"></link>'."\n", 'css');


               
                $this->htmlStartPage("Manage");
		$this->globalNavigation();
                $this->printsearchbar();
                



		$this->contentStart();
                $this->printmaincontentcontainer();
                
                //$this->contentHeading("Manage");
                print'<div class="row-fluid">';
                    print '<div class="nav nav-tabs" id="infotab">
                            <li class="navtab" value="0" id="bookmarknavtab"><a>Bookmark</a></li>
                            <li class="navtab" value="1" id="notenavtab"><a>Note</a></li>
                          </div>';

                    print'<div class="tab-pane" id="bookmarktab">';
                    print'<div class="tabbar" id="bookmarkcontent">';
                        print'<div class="span4">';
                         $this->printselectspan(0);
                        print'</div>';
                        print'<div class="span8">';
                            $this->contentContainerStart();
                                print'<div id="bookmarktblcon">';
                                print'</div>';
                            $this->contentContainerEnd();
                        print'</div>';
                    print'</div></div>';
                    
                    print'<div class="tab-pane" id="notetab" style="display:none;">';
                    print'<div class="tabbar" id="notecontent">';
                        print'<div class="span4">';
                         $this->printselectspan(1);
                        print'</div>';
                        print'<div class="span8">';
                            $this->contentContainerStart();
                                print'<div id="notetblcon">';
                                print'</div>';
                            $this->contentContainerEnd();
                        print'</div>';
                    print'</div></div>';

                print'</div>';
                $this->printsearchbarMainEnd();
		$this->contentEnd();
		$this->htmlEndPage(true);
	} /* }}} */
}





?>

