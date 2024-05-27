<?php

print'<html>
    <head>
    <script type="text/javascript" src="../styles/bootstrap/jquery/jquery.min.js"></script>
        <script type="text/javascript" src="../elib/translatePopup.js"></script>
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
                          <ul class="nav nav-tabs" id="navlist">
                            <li id="li1"><a data-toggle="tab" id="gemini" class="api" href="#footnoteContainer1">ChatGPT</a></li>
                            <li id="li2"><a data-toggle="tab" id="chatgpt" class="api" href="#footnoteContainer2">Gemini</a></li>
                            <li id="li3"><a data-toggle="tab" id="deepseek" class="api" href="#footnoteContainer3">DeepSeek</a></li>
                          </ul>
                            <div class="tab-content footnoteFrame" id="footnoteFrame"></div>
                              <div id="footnoteContainer1" class="tabcontent active"></div>
                              <div id="footnoteContainer2" class="tabcontent"></div>
                              <div id="footnoteContainer3" class="tabcontent"></div>
                          </div>';
                print'</div>';
 print'   </body> 
</html>';
 
?>