<?php
    print'<html>
        <head>
            <script type="text/javascript" src="../styles/bootstrap/jquery/jquery.min.js"></script>
            <script type="text/javascript" src="../elib/turnjs/extras/yepnope-2.0.0.js"></script>
            <script type="text/javascript" src="../elib/turnjs/extras/modernizr-csstransform-3.8.0.min.js"></script>
            <script type="text/javascript" src="../elib/turnjs/lib/turn.js"></script>
            <script type="text/javascript" src="../elib/PreviewDocumentjs_rui.js"></script>
            <link href="../elib/PreviewDocument.css" rel="stylesheet"></link>
            <link href="../styles/bootstrap/bootstrap/css/bootstrap.css" rel="stylesheet"></link>
            <script language="Javascript" type="text/javascript"></script>
        </head>
        <body> ';
        $documentid=$_GET['documentid'];
        $version=$_GET['version'];
        $page=$_GET['page'];
        $disPage=$_GET['disPage'];
        $privAccess=true;
        $dir = '../elib/data/1048576/'.$documentid.'/'.$version.'/';
        $i=count(glob($dir. "*.jpg"));
        print"<input type='hidden' id='page' value='".$page."'>";
        print'<div class="flipbook-viewport">';  
        print'   <div class="container" id="flipbook-container">';
        print'      <div class="flipbook" id="turnjs" style="position:absolute; width: 100%;height: 100%;" dir="rtl">';
                   echo "<div style='background-image:url(".$dir."1.jpg)'></div>"; 
        print'      </div>
                </div>';
        print'<nav><ul>
            <li><a id="prevPage" title="Previous page"><i class="icon-caret-left"></i></a></li>
            <li><a id="nextPage" title="Next page"><i class="icon-caret-right"></i></a></li>
            <li><a id="zoomOut" title="Zoom out"><i class="icon-zoom-out"></i></a></li>
            <li><a id="zoomIn" title="Zoom in"><i class="icon-zoom-in"></i></a></li>
            <li><a id="fullScreen" title="Change to fullscreen"><i class="icon-fullscreen"></i></a></li>';
        if($disPage==1) //if single page display, show double display icon
            print'<li><a id="disPage" value="1" title="Change to double page"><i class="icon-columns"></i></a></li>';
        else if($disPage==2) //if double page display, show single display icon
            print'<li><a id="disPage" value="2" title="change to single page"><i class="icon-file-alt"></i></a></li>';
//        if(bookmark)
            print'<li><a id="addbookmarkbtn" value="1" title="Add bookmark"><i class="icon-bookmark-empty"></i></a></li>';
//        else
//            print'<li><a id="addbookmarkbtn" value="2" title="Bookmark added"><i class="icon-bookmark"></i></a></li>';
        if($privAccess)
            print '<li><a title="Crop image" href="../out/out.CropImage.php?documentid='.$documentid.'&version='.$version.'"><i class="icon-cut"></i></a></li>';
            print'</ul></nav>';
        print'<br><div class="slidecontainer" id="slidercontain">';
        print'<input type="range" min="1" max="'.$i.'" value="'.$page.'" class="slider" id="mySlider">
                Page: <span id="mySliderValue"></span>/'.$i;
        print'<input type="hidden" id="docid" value="'.$documentid.'"><input type="hidden" id="version" value="'.$version.'"></div>';
        print'<br>';
        print'</div>';
        print'</div>';
     print'   </body> 
    </html>';

?>
