$(document).ready(function(){
    
    // added by sc
    $('#zoomIn').live("click",function(){
        var newZoom = parseFloat($('.dictFrame').css('zoom'))+0.1;
        $('.dictFrame').css('zoom', newZoom);
        document.cookie = "dictZoom="+newZoom;
    });
    $('#zoomOut').live("click",function(){
        var newZoom = parseFloat($('.dictFrame').css('zoom'))-0.1;
        $('.dictFrame').css('zoom', newZoom);
        document.cookie = "dictZoom="+newZoom;
    });
    $('#zoomDefault').live("click",function(){
        $('.dictFrame').css('zoom',1);
        document.cookie = "dictZoom=1";
    });
    // 

    $('.recommendBox').live("click",function(e){
        for(var i=1; i<=$("#navlist").children().length; i++){
            if($("#token"+i).html()===$(e.target).html() && 
               $("#footnoteContainer"+i+" #dictFrame #dictContainer"+i+"_"+1+" .footnoteBox .position").html()===$(e.target).attr("id")){
                $("#navlist li").removeClass("active");
                $("#navlist li").eq(i-1).addClass("active");  
                $("#footnoteFrame .footnoteContainer").attr("class","tab-pane fade footnoteContainer");
                $("#footnoteFrame .footnoteContainer").eq(i-1).attr("class","tab-pane active footnoteContainer");
                $("#token"+i).show();
                $("#footnoteContainer"+i).show();
            }
        }  
    });
    
    $('#dictList li').live("click",function(){
        $(this).siblings("li").removeClass("active");
        $(this).addClass("active");
        $($($(this).find('a').attr("href")).siblings()).attr("class","tab-pane fade dictContainer");
        $($(this).find('a').attr("href")).attr("class","tab-pane active dictContainer");
        setTimeout(function(){window.scrollTo(0, 0);},100);
    });

    $('#navlist li').live("click",function(){
        $('#navlist li').removeClass("active");
        $(this).addClass("active");
        $("#footnoteFrame .footnoteContainer").attr("class","tab-pane fade footnoteContainer");
        $($(this).find('a').attr("href")).attr("class","tab-pane active footnoteContainer");
        setTimeout(function(){window.scrollTo(0, 0);},100);
    });
      

    $('.voting').live("click", function(e) {
        var documentid=$(window.opener.document).find('#docid').attr('value');
        var version=$(window.opener.document).find('#version').attr('value');
        var page =$(window.opener.document).find('#quotepage').attr('value');
        var likeOffset=0, dislikeOffset=0, likeNum, dislikeNum, likeIcon, dislikeIcon;
        var position=$(this).siblings(".position").html();
        var footnote=$(this).siblings(".footnote").html();
        var tokenLength=$(this).siblings(".tokenLength").html();
        if($($($(this).parents(".footnoteContainer")).find("#dictList .active")).hasClass("1"))
            var dictId=1;
        else if($($($(this).parents(".footnoteContainer")).find("#dictList .active")).hasClass("2"))
            dictId=2;
        if($(e.target).attr("id")==="like1"){ //click grey like
            if($(this).find("#dislike2").css("display")!=="none"){
                dislikeOffset--;
            }
            likeOffset++;
            //$(this).find('.like').toggle();
        }else if($(e.target).attr("id")==="like2"){
            likeOffset--;
        }else if($(e.target).attr("id")==="dislike1"){
            if($(this).find("#like2").css("display")!=="none"){
                likeOffset--;
            }
            dislikeOffset++;
        }else if($(e.target).attr("id")==="dislike2"){
            dislikeOffset--;
        }
        $.ajax({
            async:false,
            type:'POST',
            url:'elib_ajax.php',
            data: {'action': 'modifyvote', 'documentid': documentid, 'version': version, 'page': page, 'position': position, 
                'footnote': footnote, 'like': likeOffset, 'dislike': dislikeOffset, 'tokenLength':tokenLength, 'dictId':dictId},
            success:function(response){
                var result = $.parseJSON(response);
                likeNum=(result[0]['like']==="0")?"":result[0]['like'];
                dislikeNum=(result[0]['dislike']==="0")?"":result[0]['dislike'];
                likeIcon=result[0]['likeIcon'];
                dislikeIcon=result[0]['dislikeIcon'];
            },
        });
        $(this).children('.likeNum').html(likeNum);
        $(this).children('.dislikeNum').html(dislikeNum);
        modifyicon(likeIcon, dislikeIcon, likeNum, dislikeNum, this);
    });
    

        var selection = window.opener.getSelection();
        var selected =window.opener.getSelection().toString();
        var userStrStart=selection.anchorOffset;
        var userStrEnd=selection.focusOffset;
        var focusNode = selection.focusNode;
        var anchorNode = selection.anchorNode;
        if(userStrStart>userStrEnd && anchorNode===focusNode //inverse selection in the same node
                || anchorNode.parentNode.className>focusNode.parentNode.className){ //inverse selection in two nodes
            var tmp1=userStrStart;
            userStrStart=userStrEnd;
            userStrEnd=tmp1;
            var tmp2=anchorNode;
            anchorNode=focusNode;
            focusNode=tmp2;
        } 

        var pageEnd=focusNode.length;
        var range=document.createRange();
        var strstart=(userStrStart-4>0)?userStrStart-4:0;
        var strEnd=(userStrEnd+4<pageEnd)?userStrEnd+4:pageEnd;
        range.setStart(anchorNode, strstart);
        range.setEnd(focusNode, strEnd);
        var str=range.toString();
        var documentid=$(window.opener.document).find('#docid').attr('value');
        var version=$(window.opener.document).find('#version').attr('value');
        var page =$(window.opener.document).find('#quotepage').attr('value');

        str = str.replace(/[\r\n]/g,""); 
        selected = selected.replace(/[\r\n]/g,""); 
            
        $.ajax({
            type:'POST',
            url:'../elib/elib_ajax.php',
            data: {'action': 'lookupvoting', 'str': str, 'strstart' : strstart, 'userStr' : selected, 'userStrStart' : userStrStart, 
                'documentid' : documentid, 'version' : version, 'page' : page},
            success:function(response){

                var result = $.parseJSON(response);
                for(var i=0; i<result.length; i++){ //dict
                    var dictId = parseInt(i)+1;
                    var tokenNum=0;
                    for(var j=0; j<result[i].length; j++){ //token
                        tokenNum++;
                        var footnoteNum=0;
                        var token=result[i][j][0].token;
                        var tokenLength=token.length;
                        if(i===0){
                            createtab(tokenNum, token);
                        }
                        var pinyin=undefined; 
                        var attribute=undefined;
                        var active = false;
                        var createPronounce;
                        var createAttribute;
                        for(var k=0; k<result[i][j].length; k++){ //footnote or recommend 
                            if(result[i][j][0].footnote && result[i][j][0].footnote!="暫無注釋"){
                                active = true;
                            }
                            if(result[i][j][k].pinyin && pinyin!=result[i][j][k].pinyin){
                                pinyin=result[i][j][k].pinyin;
                                createPronounce=true;
                            }else createPronounce=false;
                            if(result[i][j][k].attribute && attribute!=result[i][j][k].attribute){
                                attribute=result[i][j][k].attribute;
                                createAttribute=true;
                            }else createAttribute=false;
                            createFootnote(dictId, tokenNum, ++footnoteNum, tokenLength, result[i][j][k], createPronounce, createAttribute, active);
                        }
                        //active dict tab and container
                        if($("#footnoteContainer"+tokenNum+" #dictList").find(".active").length===0){
                            $("#footnoteContainer"+tokenNum+" #dictFrame .dictContainer").attr("class","tab-pane fade dictContainer");
                            $("footnoteContainer"+tokenNum+" #dictList li").removeClass("active");
                            $("#footnoteContainer"+tokenNum+" #dictList ."+dictId).addClass("active");  
                            $("#footnoteContainer"+tokenNum+" #dictFrame #dictContainer"+tokenNum+"_"+dictId).attr("class","tab-pane active dictContainer");
                        }
                    }
                }
                //sc
                var zoomLevel=getCookie('dictZoom');
                if (zoomLevel !== null)
                {
                    $('.dictFrame').css('zoom', zoomLevel);
                }
                //
            }
        });


});  

function calpos(selection, mode){
    var offset = 0;
    var range = selection.getRangeAt(0);
    if(mode==0){
        var start=0;
    }else{
        var start = range.startOffset;
    }

    if ( selection.baseNode.parentNode.hasChildNodes() ) { 
        if(mode==0){
            var par=selection.baseNode.parentNode;
        }else{
             var par=selection.baseNode;
        }
        
        for ( var i = 0 ; par.parentNode.childNodes.length > i ; i++ ) { 
            var cnode = par.parentNode.childNodes[i];
            if (cnode.nodeType == document.TEXT_NODE) {
                if ( cnode==par) {
                    break; 
                }   
                offset = offset + cnode.length;
            }   
            if (cnode.nodeType == document.ELEMENT_NODE) {
                if ( cnode==par) {
                    break;
                }   
                offset = offset + cnode.textContent.length;
            }   
        }   
    }   

    return (start+offset);
}

//sc
function getCookie(cookieName){
    var cookieStr = document.cookie;
    var cookieArr = cookieStr.split(";");
    var res;
    cookieArr.forEach(eleStr => 
    {
        if(eleStr.indexOf(cookieName+'=') === 0)
        {
            res = eleStr.substring(cookieName.length+1); 
        }
    });
    return res;
}
//

function createtab(tokenNum, token) {  
        var $tab=$('<li id="li'+tokenNum+'"><a data-toggle="tab" id="token'+tokenNum+'" class="token" href="#footnoteContainer'+tokenNum+'">'+token+'</a></li>');
        $("#navlist").append($tab);
        var $footnoteContainer=$('<div id="footnoteContainer'+tokenNum+'" class="tab-pane fade footnoteContainer"></div>');
        $("#footnoteFrame").append($footnoteContainer);
        var $dictTab=$('<ul class="nav nav-pills" id="dictList">\n\
            <li class="2 active"><a data-toggle="tab" id="dict2" class="dict" href="#dictContainer'+tokenNum+'_2">國語字典</a></li>\n\
            <li class="1"><a data-toggle="tab" id="dict1" class="dict" href="#dictContainer'+tokenNum+'_1">道院辭典</a></li>\n\
        </ul>');
        var $dictFrame=$('<div class="tab-content dictFrame" id="dictFrame">');
        var $dictContainer=$('<div id="dictContainer'+tokenNum+'_2" class="tab-pane fade in active dictContainer"></div>\n\
                <div id="dictContainer'+tokenNum+'_1" class="tab-pane fade dictContainer"></div>');
        $("#footnoteContainer"+tokenNum).append($dictTab);
        $("#footnoteContainer"+tokenNum).append($dictFrame);
        $("#footnoteContainer"+tokenNum+" #dictFrame").append($dictContainer);
        $(".dictContainer").css('padding','0px 15px');
        $(".footnoteContainer").css('padding','4px 4px');
        if($("#navlist").find(".active").length===0)
            $("#li"+tokenNum).addClass("active");
        if($("#footnoteFrame").children(".active").length===0)
            $("#footnoteContainer"+tokenNum).attr("class","tab-pane active footnoteContainer");
    }
    
    
function createFootnote(dictId, tokenNum, footnoteNum, tokenLength, arr, createPronounce, createAttribute, active){
    var recommend=(arr.firstTokenRec)?arr.firstTokenRec:false;
    recommend=(arr.lastTokenRec)?arr.lastTokenRec:recommend;
    if(createPronounce){
        var $pronounceBox=$('<div class="pronounce" id="zhuyin">'+"注音 "+arr.zhuyin+'</div>\n\
            <div class="pronounce" id="pinyin">'+" 拼音 "+arr.pinyin+'</div>');
        $("#footnoteContainer"+tokenNum+" #dictFrame #dictContainer"+tokenNum+"_"+dictId).append($pronounceBox);
    }
    if(createAttribute && arr.attribute!==""){
        var $attributeBox=$('<div class="attribute">'+arr.attribute+'</div>');
        $("#footnoteContainer"+tokenNum+" #dictFrame #dictContainer"+tokenNum+"_"+dictId).append($attributeBox);
    }
    
    if(arr.footnote){
    var $footnoteBox=$('<div id="footnoteBox'+footnoteNum+'" class="footnoteBox">\n\
        <div class="tokenLength">'+tokenLength+'</div>\n\
        <div class="position">'+arr.position+'</div>\n\
        <div class="voting">\n\
            <img class="like" id="like1" src="../views/bootstrap/images/like.png">\n\
            <img class="like" id="like2" src="../views/bootstrap/images/likeClick.png" style="display:none">\n\
            <div class="likeNum"></div>\n\
            <img class="dislike" id="dislike1" src="../views/bootstrap/images/dislike.png">\n\
            <img class="dislike" id="dislike2" src="../views/bootstrap/images/dislikeClick.png" style="display:none">\n\
            <div class="dislikeNum"></div>\n\
        </div>\n\
        <p class="footnote">'+arr.footnote+'</p>\n\
    </div>');
    $("#footnoteContainer"+tokenNum+" #dictFrame #dictContainer"+tokenNum+"_"+dictId).append($footnoteBox);
    }
    if(arr.footnote==="暫無注釋"){
        $("#footnoteContainer"+tokenNum+" #dictFrame #dictContainer"+tokenNum+"_"+dictId+" #footnoteBox"+footnoteNum+" .voting").hide();
    }
    var likeNum=(arr.like==="0")?" ":arr.like;
    var dislikeNum=(arr.dislike==="0")?" ":arr.dislike;
    modifyicon(arr.likeIcon, arr.dislikeIcon, likeNum, dislikeNum, tokenNum, dictId, footnoteNum);
    if(!(arr.zhuyin & arr.pinyin)){
        $("#footnoteContainer"+tokenNum+" #dictFrame #dictContainer"+tokenNum+"_"+dictId+" #footnoteBox"+footnoteNum+" .zhuyin").hide();
        $("#footnoteContainer"+tokenNum+" #dictFrame #dictContainer"+tokenNum+"_"+dictId+" #footnoteBox"+footnoteNum+" .pinyin").hide();
    }
    if(arr.attribute==="")
        $("#footnoteContainer"+tokenNum+" #dictFrame #dictContainer"+tokenNum+"_"+dictId+" #footnoteBox"+footnoteNum+" .attribute").hide();
    if(arr.hidden){ //if the token is in the recommendation list
        if($("#li"+tokenNum).hasClass("active")){
            $("#navlist li").removeClass("active");
            $("#footnoteFrame .footnoteContainer").attr("class","tab-pane fade footnoteContainer");
        }
        $("#token"+tokenNum).hide();
        $("#footnoteContainer"+tokenNum).hide();
    }
    if(recommend){ //if the token has reccomendation list
        var $recommendBox=$('<div class="recommendBox"><div>“'+arr.token+'”的上下文搭配</div><ol></ol></div>');
        $("#footnoteContainer"+tokenNum+" #dictFrame #dictContainer"+tokenNum+"_"+dictId).append($recommendBox);
        for(var key in recommend){
            var $tokenRec=$('<li id="'+recommend[key]+'" class="tokenRec">'+key+'</li>');
            $("#footnoteContainer"+tokenNum+" #dictFrame #dictContainer"+tokenNum+"_"+ dictId+" .recommendBox ol").append($tokenRec);
        }
    }
    if(!active){
        $("#footnoteContainer"+tokenNum+" #dictList ."+dictId).remove();
    }
}

function modifyicon(likeIcon, dislikeIcon, likeNum, dislikeNum, ...data){
    var str="";
    if($.isNumeric(data[0])){
        str="#footnoteContainer"+data[0]+" #dictFrame #dictContainer"+data[0]+"_"+data[1]+" #footnoteBox"+data[2]+" .voting";
    }else{ //data[0]=this
        str=data[0];
    }   
    if(likeIcon==="1" || dislikeIcon==="1"){ //hide likeNum and dislikeNum if the user has not click any of them
        $(str).children(".likeNum").html(likeNum);
        $(str).children(".dislikeNum").html(dislikeNum);
    }
    if(likeIcon==="1"){
        $(str).children("#like2").removeAttr("style");
        $(str).children("#like1").attr("style","display:none");
    }else if(likeIcon==="-1"){
        $(str).children("#like1").removeAttr("style");
        $(str).children("#like2").attr("style","display:none");
    }
     if(dislikeIcon==="1"){
        $(str).children("#dislike2").removeAttr("style");
        $(str).children("#dislike1").attr("style","display:none");
    }else if(dislikeIcon==="-1"){
        $(str).children("#dislike1").removeAttr("style");
        $(str).children("#dislike2").attr("style","display:none");
    }
}

