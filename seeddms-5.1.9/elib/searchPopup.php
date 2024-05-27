<?php

print'<html>
    <head>
    <script type="text/javascript" src="../styles/bootstrap/jquery/jquery.min.js"></script>
        <script type="text/javascript" src="../elib/searchPopup.js"></script>
        <link href="../elib/PreviewDocumentCopy.css" rel="stylesheet"></link>
         <link href="../styles/bootstrap/bootstrap/css/bootstrap.css" rel="stylesheet"></link>
        <script language="Javascript" type="text/javascript">
        
        </script>
    </head>
    <body> ';
                    print'<div id="mycontainer" style="padding:15px;">
                          <span id="tabSpan" style="cursor: pointer; float:right; line-height:38px;">
                            <span class="icon-zoom-in" title="larger font size" id="zoomIn" style="font-size:20px;"></span>
                            <span class="icon-zoom-out" title="smaller font size" id="zoomOut" style="font-size:20px;"></span>
                            <span class="icon-repeat" title="back to default" id="zoomDefault" style="font-size:20px;"></span>
                          </span>
                          <ul class="nav nav-tabs" id="navlist"></ul>
                          
                            <div class="tab-content footnoteFrame" id="footnoteFrame"></div>
                          </div>';
                print'</div>';
 print'   </body> 
</html>';
 
?>