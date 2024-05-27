$(document).ready(function() {
    var query=window.opener.selectedText;
    var docid=$(window.opener.document).find('#docid').attr('value');
    var version=$(window.opener.document).find('#version').attr('value');

    $('#navlist').append('<li>'+query+'</li>');

    console.log('query: '+query+', docid: '+docid+', version: '+version);
    $.ajax({
        type:'POST',
        url:'../elib/CallSolr.php',
        data: {'action': 'search', 'query': query, 'docid': docid,'mode':0,'version':version},
        success:function(response){
            //alert(response);
            var showResults=document.getElementById('footnoteFrame');
            showResults.innerHTML="";
            showResults.innerHTML=response;
        }
    });
});

$('#zoomIn').live("click",function(){
    var newZoom = parseFloat($('#footnoteFrame').css('zoom'))+0.1;
    $('#footnoteFrame').css('zoom', newZoom);
    document.cookie = "transZoom="+newZoom;
});
  
$('#zoomOut').live("click",function(){
    var newZoom = parseFloat($('#footnoteFrame').css('zoom'))-0.1;
    $('#footnoteFrame').css('zoom', newZoom);
    document.cookie = "transZoom="+newZoom;
});

$('#zoomDefault').live("click",function(){
    $('#footnoteFrame').css('zoom',1);
    document.cookie = "transZoom=1";
});