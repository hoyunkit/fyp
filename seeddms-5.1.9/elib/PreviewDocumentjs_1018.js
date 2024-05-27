$(document).ready(function(){
    
	//handle seeddms topnav event
    /*$('#dropdown-btn').live('click',function(){
            if($('.dropdown').hasClass('open')){
                $('.dropdown').removeClass('open');
            }else{
                $('.dropdown').addClass('open');
            }
        });*/
    $(".navtab").ready(function() {
        $('#navtab0').click();
    });
    
    $("#infobar").ready(function(){
        if($("#infobar").data("status")=="0"){
            $('#tabcontrol').click();
        }
    });
    
//    $('.btn-navbar:first').live('click',function(){
//        if($('#nav-collapse-nav-col1').height()==0){
//            $('#nav-collapse-nav-col1').css('height','auto');
//        }else{
//            $('#nav-collapse-nav-col1').css('height','0');
//        }
//        
//    }); 
	
	//handle night toggle button
	$('#night-toggle').live('click',function(){
		var tmp=$(this).attr('value');
		if($(this).attr('value')==0){
			$('#flipbook-container').css('filter','invert(1)');
		}else{
		
			$('#flipbook-container').css('filter','');

		}
		
    });
    
    $("#prevPage").live('click',function(e){
       var curpage= $('.flipbook').turn('page');
       if($('#disPage').attr("value")==1){ //single
            turnpage(curpage-1);
       }else{
           turnpage(curpage-2);
       }
    });
    $("#nextPage").live('click',function(e){
        var curpage= $('.flipbook').turn('page');
        if($('#disPage').attr("value")==1){ //single
            turnpage(curpage+1);
       }else{
           turnpage(curpage+2);
       }
    });
    
	//handle font size change
	$('#font-size-small').live('click',function(){
		var curfont=parseInt($('#pageinfocontentres').css('font-size'));
		if(curfont<=12){
			return;}
		$('#pageinfocontentres').css('font-size',curfont-1);
		$('#pageinfocontentres').css('line-height',curfont+5+'px');

	});
	$('#font-size-large').live('click',function(){
		var curfont=parseInt($('#pageinfocontentres').css('font-size'));
		if(curfont>=45){
			return;}
		$('#pageinfocontentres').css('font-size',curfont+1);
		$('#pageinfocontentres').css('line-height',curfont+7+'px');

	});
	
	
	//flipbk-control button click event
		//add bookmark btn event
		$('#addbookmarkbtn').live('click',function(){
                    var curpage= $('.flipbook').turn('page');
                    var docid=document.getElementById('docid').value;
                    var version=document.getElementById('version').value;
                    $.ajax({
                        type:'POST',
                        url:'../elib/elib_ajax.php',
                        data: {'action': 'addbookmark', 'documentid': docid, 'page': curpage, 'version': version},
                        success:function(response){
                                //alert(response)
                                if(response==1){
                                        var msg='Bookmark added';
                                        var success='success';
                                        window['bookmarktab']();
                                }else{
                                        var msg='Already in bookmark';
                                        var success='error';
                                }
                                noty({
                        text: msg,
                        type: success,
                        dismissQueue: true,
                        layout: 'topRight',
                        theme: 'defaultTheme',
                        timeout: 1500,
                                            });
                        }
                    });
		});
		//zooming btn, larger
        $('.icon-zoom-in').live('click',function(){
            if($('#turnjs').css('zoom')!=1){
                   var fac=parseFloat($('#turnjs').css('zoom'));
                   if(fac>=2.3){
                       return;
                   }
                   $('#turnjs').animate({ 'zoom': fac+0.1}, 100);

            }else{ //zoom==1
//                if($('#turnjscontain').hasClass('span12')){
//                    $('#infobar').toggle();
//                }
                $('#turnjs').animate({ 'zoom': 1.8}, 400);
                $('#turnjs').css('cursor','grab');
                if($('.flipbook').turn('display')=='double'){
                    $('.page-wrapper:even').css('left',398);
                }
//                if($('.flipbook').turn('display')=='double'){ 
//                    $('.page-wrapper:odd').css('left',0);
//                }
            }
 
        });
        //smaller
        $('.icon-zoom-out').live('click',function(){
            if($('#turnjs').css('zoom')!=1){
                var fac=parseFloat($('#turnjs').css('zoom')); 
                if(fac<=1.3){
                    $('#turnjs').animate({ 'zoom': 1, 'top':0,'left':0}, 400);
                    $('#turnjs').css('cursor','');
                    $('.page-wrapper:even').css('left','auto');
                    flipbooksize();
//                    if($('#turnjscontain').hasClass('span12')){
//                        $('#infobar').toggle();
//                    }
                    return;
                } 
                $('#turnjs').animate({ 'zoom': fac-0.1}, 100);
                
            }
        });
        //fullscreen btn
        $('.icon-fullscreen').live('click',function(){
            var ele=document.getElementById('turnjs');
            ele.requestFullscreen();
        });
        //hide flipbk-control
//        $('#flipbk-control').live('mouseleave',function(){
//        $('#flipbk-control').css('display','none');
//        });
        
       
	//single page toggle
//	$('#singlePage').live('click',function(){
//	  if($(this).attr("value")==1){
//		  $(this).attr("value", 2); //curr is single Page
//		  $(this).text("DoublePage");
//	  }else{
//		  $(this).attr("value", 1); //curr is double page
//		  $(this).text("SinglePage");
//	  }
//	  flipbooksize();
//	});

        $('#disPage').live('click',function(e){
            var disPage;
            var docid=document.getElementById('docid').value;
            if($('#disPage').attr("value")==1){ //curr is single Page
                $(this).attr("value", 2);
                $(this).attr("title", "change to single page");
                $(this).html('<i class="icon-file-alt"></i>');
                disPage=2;
            }else if($('#disPage').attr("value")==2){ //curr is double page
                $(this).attr("value", 1);
                $(this).attr("title", "change to double page");
                 $(this).html('<i class="icon-columns"></i>');
                disPage=1;
            }
            $.ajax({
                type:'POST',
                url:'../elib/elib_ajax.php',
                data: {'action': 'recordDisPage', 'documentid': docid, 'disPage':disPage}
            });
            flipbooksize();
        });
	
	$(document).live('mousemove',function(e){

        var loc = "x:"+e.pageX+",y:"+e.pageY

        document.getElementById("point-loc").innerHTML = loc;

        });
	$('#turnjs').live('mousedown',function(e){
		//grab and move book img
		if ($('#turnjs').css('zoom')!=1) { //when turnjs zoom, grab for move
//			var offset = $(this).offset();
//			 var fac=parseFloat($('#turnjs').css('zoom'));
//			 $(this).data("flipbkx", e.pageX -offset.left);
//			$(this).data("flipbky", e.pageY -offset.top);
//			$(this).css('cursor','all-scroll');
                    
//                    $('#turnjs').css('left', e.pageX - (e.pageX - moveElemRect.left));
//                    $('#turnjs').css('top', e.pageY - (e.pageY - moveElemRect.top));
            $('#turnjs').data("drag", true);
            var container=$('#flipbook-container')[0].getBoundingClientRect();
                    $(this).data("flipbkx", e.clientX - container.left);
//                    $(this).data("flipbkx", e.clientX - $('#turnjs').css('left'));
                    $(this).data("flipbky", e.clientY - container.top);
		}
            
                /*else{ //otherwise can grab pages on right to adjust position
			var offset = $(this).offset();
			if (((e.pageX - offset.left) > $('#turnjs').width() *0.5 )&&( (e.pageX - offset.left) < $('#turnjs').width() *0.8) && $('.flipbook').turn('display')=='double') {
                            $(this).css('cursor','move');
                            $('#turnjs').data('move',1);

			}
		}*/
	});

	$('#turnjs').live('mousemove',function(e){
		if( $('#turnjs').data('move')==1){
			$('.page-wrapper:even').css('left',e.pageX-$('#turnjs').offset().left);
		}
	});
	$('#turnjs').live('mouseup',function(e){
		$('#turnjs').data('move',0);
		$(this).css('cursor','');
	});

    $('#turnjs').live('click',function(e){

       if($('#turnjs').css('zoom')==1){
            var offset = $('#turnjs').offset();   
            if ((e.pageX - offset.left) < $('#turnjs').width() *0.2) {
                    if($('#turnjs').turn('display')=='double' && $('#turnjs').turn('page')%2==0){
                        turnpage($('#turnjs').turn('page')+2);
                    }else{
                        turnpage($('#turnjs').turn('page')+1);}
            } else if((e.pageX - offset.left) > $('#turnjs').width() *0.8){
                    turnpage($('#turnjs').turn('page')-1);
            }
        }
    });

       $('#flipbook-container').live('mousemove',function(e){
//        $('#maincontent-container').live('mousemove',function(e){
            var top=$('#flipbook-container')[0].getBoundingClientRect().top;
            var left=$('#flipbook-container')[0].getBoundingClientRect().left;
            var height=$('#flipbook-container')[0].getBoundingClientRect().height;
            var width=$('#flipbook-container')[0].getBoundingClientRect().width;
            if ($('#turnjs').data("flipbkx")!= null && $('#turnjs').data("flipbky")!=null && $('#turnjs').css('zoom')!=1
                    && top<=e.clientY<=top+height && left<=e.clientX<=width+left && $('#turnjs').data("drag")) {
//                var fac=parseFloat($('#turnjs').css('zoom'));
//                var y=e.pageY*fac/1.8 - $('#turnjs').data("flipbky"); //e.pageY*fac/1.8 -offset.top*1.8/fac
//                if($(window).width()>=1278){
//                    var x =e.pageX*fac/1.8 - $('#turnjs').data("flipbkx"); //e.pageX*fac/1.8 -offset.left*1.8/fac
//                }else{
//                    var x =e.pageX*fac/1.8 - $('#turnjs').data("flipbkx");
//                }
//                if(y>0){
//                    y=0;
//                }else if(y<$('#flipbook-container').height()*((1/fac)-1)){
//                    y=$('#flipbook-container').height()*((1/fac)-1);
//                }
//                if(x<($('.flipbook-viewport').width()/fac)-$('#turnjs').width()-($('.flipbook-viewport').width()-$('#flipbook-container').width())/(2*fac)){
//                    x=($('.flipbook-viewport').width()/fac)-$('#turnjs').width()-($('.flipbook-viewport').width()-$('#flipbook-container').width())/(2*fac);
//                }else if(x>($('.flipbook-viewport').width()-$('#flipbook-container').width())/(2*-fac)){
//                    x=($('.flipbook-viewport').width()-$('#flipbook-container').width())/(2*-fac);
//                }
                $('#turnjs').css('top',(e.clientY-top)-$('#turnjs').data("flipbky"));
                $('#turnjs').css('left',(e.clientX-left)-$('#turnjs').data("flipbkx"));
            }
//            if(e.pageY>$('.flipbook-viewport').offset().top && e.pageY-$('.flipbook-viewport').offset().top<25){ //&&$('#flipbk-control').css('display')=='none'
//                $('#flipbk-control').css('width',$('.flipbook-viewport').width());
//                $('#flipbk-control').css('display','block');
//
//            }
        });
        $('#flipbook-container').live('mouseup',function(e){
//            $('#maincontent-container').live('mouseup',function(e){
            $('#turnjs').data("flipbkx",null);
            $('#turnjs').data("flipbky",null);
            if ($('#turnjs').css('zoom')!=1) { 
                $('#turnjs').css('cursor','grab');
            }
        });
		
        $('#flipbook-container').live('mouseleave',function(e){
            $('#turnjs').data("drag", false);
        });


        
		
	//handle slider change
    var slider = document.getElementById('mySlider');
    slider.addEventListener('input', function() {
		
        $('#mySliderValue').innerHTML = slider.value;
        var curpage=$('.flipbook').turn('page');
        if(parseInt(this.value)-curpage>4){
            setTimeout(function(){ $('.flipbook').turn('next');},20);
            setTimeout(function(){ $('.flipbook').turn('next');},20);
        }else if(parseInt(this.value)-curpage<-4){
            setTimeout(function(){ $('.flipbook').turn('previous');},20);
            setTimeout(function(){ $('.flipbook').turn ('previous');},20);
        }
		turnpage(this.value);

    }, false); 
	

    
	//close infobar
    $('#tabcontrol').live('click',function(){
        var documentid=document.getElementById('docid').value;
        $.ajax({
            type:'POST',
            url:'../elib/elib_ajax.php',
            data: {'action': 'recordTab', 'tabStatus': 0, 'documentid': documentid}
        });
        $('#turnjscontain').addClass('span12');
        $('#turnjscontain').removeClass('span8');
        $('#turnjscontain').removeClass('span4');
        $('#turnjscontain').removeClass('span6');
        $('#infobar').removeClass('span6');
        $('#infobar').removeClass('span4');
        flipbooksize();

        //	  $('#infotab').css('left',$('#turnjs').offset().left+$('.shadow').width());
        //        $('#infotab').css('right','auto');
        $('#infotab').css('position','absolute');
	$('#infotab').css('top', 171);
        $('#infotab').css('right',0);
        $('#infotab > li > a').css('border','0px');
        $('#infotab').css('border-radius', '4px');
        $('#infotab').css('background-color', '#F5F5F5');
        
        $('#infobar .well').hide();

        $('.navtab').removeClass("active");
        var tabs = ['pageinfotab', 'searchtab', 'bookmarktab', 'notetab'];
        tabs.forEach(element => document.getElementById(element).style.display='none');
    });
    
    
    //handle tab change
    $('.navtab').live('click',function(){
        var documentid=document.getElementById('docid').value;
        if($('#turnjscontain').hasClass('span12')){
            $('#turnjscontain').addClass('span8');
            $('#infobar').addClass('span4');
            $('#turnjscontain').removeClass('span12');
            flipbooksize();
                    
            $('#infobar .well').show();
            $('#infotab').css('position','relative');
            $('#infotab').css('right','-40px');
            $('#infotab').css('left','');
            $('#infotab').css('top','20px');
            $('#infotab > li > a').css('border','1px solid black');
//            $('#infotab > li > a').css('border-radius', '0 4px 4px 0');
            $('#infotab > li > a').css('border-left', 'transparent');
            $('#infotab').css('border-radius', '0px');
            $('#infotab').css('background-color', 'transparent');
        }
        
        popuptrigger("none");
        $('.navtab').removeClass("active");
        $(this).addClass("active");
        var curtab=$(this).attr('value');

        var tabs = ['pageinfotab', 'searchtab', 'bookmarktab', 'notetab'];
        tabs.forEach(element => document.getElementById(element).style.display='none');
        document.getElementById('curtabval').value=curtab;
        document.getElementById(tabs[curtab]).style.display='block';
        if(curtab==0){
            pageinfotab($('.flipbook').turn('page'));
        }else if(curtab==1){
            if(!document.getElementById('searchcontentres').innerHTML){
                var btn=document.getElementById('searchbtn');
                btn.click();}
        }else if(curtab==2){
            bookmarktab();
        }else{
            notetab($('.flipbook').turn('page'));
        }
        $.ajax({
            type:'POST',
            url:'../elib/elib_ajax.php',
            data: {'action': 'recordTab', 'tabStatus': 1, 'documentid': documentid}
        });
    });
	
    //handle user click on search result
    $('.searchreturn').live('click',function(){
        var page=$(this).attr('value');
        turnpage(page);
    });
    //handle user search request
    $('#searchbtn').live('click',function(){
        var query=document.getElementById('searchquery').value;
        var docid=document.getElementById('docid').value;
        var version=document.getElementById('version').value;
        document.getElementById('searchquery').value="";
        $.ajax({
                type:'POST',
                url:'../elib/CallSolr.php',
                data: {'action': 'search', 'query': query, 'docid': docid,'mode':0,'version':version},
                success:function(response){
                    //alert(response);
                    var searchtab=document.getElementById('searchcontentres');
                    searchtab.innerHTML="";
                    searchtab.innerHTML=response;
                }
        });

    });
	
    
    
    //delete bookmark event
    $('.delbookmarkbtn').live('click',function(){
        //alert('CLK');
        var page=$(this).attr('value');
        var docid=document.getElementById('docid').value;
        var version=document.getElementById('version').value;
       
        $.ajax({
            type:'POST',
            url:'../elib/elib_ajax.php',
            data: {'action': 'delbookmark', 'documentid': docid, 'version': version, 'page': page},
            success:function(response){
                //alert(response)
                if(response==1){
                    var msg='Bookmark deleted';
                    var success='success';
                    window['bookmarktab']();
                }
                noty({
                    text: msg,
                    type: success,
                    dismissQueue: true,
                    layout: 'topRight',
                    theme: 'defaultTheme',
                    timeout: 1500,
                });
            }
        });
       
        
    });



    $("#pageinfotxt").live("mousedown",function(e) {
        $('#quotepage').attr('value',$(e.target).attr('class'));
    });

//chenrui
   
    $(document).live("mouseup",function(e) {

        setTimeout(function (){
            var selection = window.getSelection();
            var selected = window.getSelection().toString();
            

            if( $(e.target).hasClass("popup") && $('#popupcontainer').css('display')=="block")
            {
                return;
            }
            if( $('#pageinfotab').css('display')=="none" || $(e.target).hasClass("token") || $(e.target).hasClass("dict") ||
                    $(e.target).hasClass("like") || $(e.target).hasClass("dislike") || $(e.target).hasClass("tokenRec"))
            {
                return;
            }
 
            if($(e.target).offsetParent().hasClass("popup")&& $('#popupnav').css('display')=="block"){
            	return;
            }
             if( !$(e.target).hasClass("token") && !$(e.target).hasClass("like") && !$(e.target).hasClass("dislike") 
                    && !$(e.target).hasClass("tokenRec") && !$(e.target).hasClass("dict")
                    && !$(e.target).hasClass("pageinfocontent") && $('#popupcontainer').css('display')=="block")
            
            {
                popuptrigger("none");
                window.getSelection().empty();
                $("#navlist li").remove();
                $(".footnoteContainer").remove();
                //$(".footnoteBox").remove();
                return;
            }
            if(selected==""){
                popuptrigger("none");
                return;
            }

            $('#popupnav').css('top',e.pageY-40);
            $('#popupnav').css('left',e.pageX-127);
            $('#popupcontainer').css('top',e.pageY+20);
            $('#popupcontainer').css('left',e.pageX-127);
            popuptrigger("block");

        }, 200);
    });
   
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
        $('#popupcontainer').stop().animate({ scrollTop: 0 }, 500);
    });

     $('#navlist li').live("click",function(){
        $('#navlist li').removeClass("active");
        $(this).addClass("active");
        $("#footnoteFrame .footnoteContainer").attr("class","tab-pane fade footnoteContainer");
        $($(this).find('a').attr("href")).attr("class","tab-pane active footnoteContainer");
        $('#popupcontainer').stop().animate({ scrollTop: 0 }, 500);
    });
    
    
    
    

    $('.voting').live("click", function(e) {
        var documentid=document.getElementById('docid').value;
        var version=document.getElementById('version').value;
        var page =$('#quotepage').attr('value');
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
            url:'../elib/elib_ajax.php',
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
    //chenrui
    
    $('#popupnav a').live("click",function(){
        if($(this).data('id')==1){
            document.getElementById('searchcontentres').innerHTML="";
            document.getElementById('searchquery').value=window.getSelection().toString();
            window.getSelection().empty();
            $('#navtab1').trigger('click');
            
        }else if($(this).data('id')==2){
            if($('#quotearea').css('display')=="block"){
                $('#rmquote').trigger("click");
            }
            $('#quotebox').attr('value',window.getSelection().toString());
            $('#navtab3').trigger('click');
            $('#notecontentres').css('display', 'none');
            $('#noteFilter').css('display', 'none');
            $('#notecontentdetailres').css('display', 'none');
            $('#addnotecontentres').css('display','block');
            $('#quotearea').css('display','block');
            $('#addnotebtn').css('display','block');
            $('#addnotebtn').text("Back");
            if(document.getElementById('backbtn')){
                $("#backbtn").remove();
            }
            //alert($('#quotepage').attr('value'));
            
        }else if($(this).data('id')==3){
            //lookupDict();
            //$('#popupcontainer').css('display','block');
                
			if (/Mobi|Android/i.test(navigator.userAgent)) {
				$('#popupcontainer').css('display','block');
                lookupDict();
			}else{
				popupwin =window.open("../elib/popup.php","Popup","width=550,height=550,status=0,directories=0,menubar=0,titlebar=0,location=0");
			}
        	


        }else{

            if(window.getSelection().anchorNode.parentElement.localName=="mark"){
                var data=[document.getElementById('docid').value, document.getElementById('version').value];
                data.push(calpos(window.getSelection(),0)); //mark text pos[2]
                data.push(window.getSelection().anchorNode.parentElement.parentElement.className); //page no [3]

                window.getSelection().empty();    
                $.ajax({
                    type:'POST',
                    url:'../elib/elib_ajax.php',
                    data: {'action': 'removehighlight','data':data},
                    success:function(response){
                        pageinfotab($('.flipbook').turn('page'));
                    }
                });
            }else{
                var data=[document.getElementById('docid').value, document.getElementById('version').value];
                data.push(window.getSelection().toString().length); //select text [2]
                data.push(window.getSelection().anchorNode.parentElement.className); //page no [3]
                data.push(calpos(window.getSelection(),1)); //select text pos [4]
                window.getSelection().empty();
                $.ajax({
                    type:'POST',
                    url:'../elib/elib_ajax.php',
                    data: {'action': 'highlighttxt','data':data},
                    success:function(response){
                        pageinfotab($('.flipbook').turn('page'));
                    }
                });
            }
        }
        if($(this).data('id')!=3){
        	$("#navlist li").remove();
        	$(".footnoteContainer").remove();
        	popuptrigger("none");
        }


    });
    
    $('#rmquote').live("click",function(){
        $('#quotearea').css('display','none');
        $('#quotebox').attr('value',"");
        $('#quotepage').attr('value', "0");
        $('#parent').attr('value', "0");
        if($('#subject').attr('disabled')=="disabled"){
            $('#subject').removeAttr("disabled");
            $('#subject').attr('value',"");
        }
    });
    
    $('#addnotebtn').live("click",function(){
        if($('#addnotecontentres').css('display')=='none'){
            $('#notecontentres').css('display', 'none');
            $('#noteFilter').css('display', 'none');
            $('#addnotecontentres').css('display','block');
            $(this).text("Back");
           
        }else{
            notetab($('.flipbook').turn('page'));
            $('#notecontentres').css('display', 'block');
            $('#noteFilter').css('display', 'block');
            $('#addnotecontentres').css('display','none');
            $('#notecontentdetailres').css('display', 'none');
            $(this).text("+");
        }
    });
    
    $('#subbtn').live("click",function(){
        var data=[document.getElementById('docid').value, document.getElementById('version').value];
        data.push($('#subject').attr('value'));
        data.push($('#notebox').attr('value'));

        if($('#quotepage').attr('value')==0){
            data.push($('.flipbook').turn('page'));
            data.push("");
        }else{
            data.push($('#quotepage').attr('value'));
            data.push($('#quotebox').attr('value'));
        }
        data.push($('#parent').attr('value'));
        if($('#visible').is(':checked')){
            data.push("2");
            
        }else{
            data.push("1");

        }

        $.ajax({
                type:'POST',
                url:'../elib/elib_ajax.php',
                data: {'action': 'subnote','data':data},
                success:function(response){
                    if(response==1){
                        var msg='Notes added';
                        var success='success';
                        $('#subject').attr('value', "");
                        $('#notebox').attr('value', "");
                        $('#rmquote').trigger('click');
                        if(document.getElementById('backbtn')){
                            $("#backbtn").trigger("click");
                            $("#backbtn").remove();
                        }else{
                            $('#addnotebtn').trigger("click");
                        }
                        $( "#visible" ).prop( "checked", false );
                    }else{
                        var msg='Cannot be empty';
                        var success='error';
                    }
                    noty({
			text: msg,
			type: success,
			dismissQueue: true,
			layout: 'topRight',
			theme: 'defaultTheme',
			timeout: 1500,
                    }); 
                }
        });
        
    });
    
    $('.notespan').live("click",function(){
        var id= $(this).attr('value');
        $.ajax({
                type:'POST',
                url:'../elib/elib_ajax.php',
                data: {'action': 'shownotedetail', 'id': id, 'mode':0},
                success:function(response){
                    if(response){
                        response=JSON.parse(response);
                        $('#notecontentdetailres').css('display', 'block');
                        $('#notecontentres').css('display','none');
                        $('#noteFilter').css('display', 'none');
                        document.getElementById('notecontentdetailres').innerHTML=response[0];
                        //alert(response[1]);
                        if(document.getElementById('backbtn')){
                           $("#backbtn").remove();
                        }
                        var btn=response[1];
                        $(btn).insertAfter("#addnotebtn");
                        $('#addnotebtn').css("display", "none");
                    }
                }
        });
    });
    
    $('#backbtn').live("click",function(){
        var par=$(this).attr('value');
        if(par==0){
            notetab($('.flipbook').turn('page'));
            $('#notecontentres').css('display', 'block');
            $('#noteFilter').css('display', 'block');
            $('#notecontentdetailres').css('display', 'none');
            
            $('#addnotebtn').css("display", "block");
        }else{
            var Span='<span class="notespan" id="tmp" value="'.concat(par).concat('"></span>');
            $(Span).insertAfter($(this));
            $('#tmp').trigger('click');
            $( "#tmp").remove();
        }
        $('#addnotecontentres').css('display','none');
        $(this).remove();
    });
    
    $('.notevotingIcon').live("click",function(){
        var modi=$(this).attr('value');
        var id=$(this).parent().attr('value');
        $.ajax({
                type:'POST',
                url:'../elib/elib_ajax.php',
                data: {'action': 'notevote', 'id': id,'modi':modi},
                success:function(response){
                    if(response){
                         var span='<span class="notespan" id="tmp" value="'+id+'"></span>';
                        $(span).insertAfter('#notecontentres');
                        $('#tmp').trigger('click');
                        $( "#tmp").remove();
                    }
                }
        });
    });

    $('#ReplyNotebtn').live("click",function(){
        var id= $(this).attr('value');
        if($('#quotearea').css('display')=="block"){
            $('#rmquote').trigger("click");
        }
        $('#parent').attr('value', id);
        if(document.getElementById('backbtn')){
            $("#backbtn").attr('value', id);
        }
        $.ajax({
                type:'POST',
                url:'../elib/elib_ajax.php',
                data: {'action': 'showNoteParent', 'id': id},
                success:function(response){
                    if(response){
                        response=jQuery.parseJSON( response )
                        $('#quotebox').attr('value',response[1]);
                        $('#subject').attr('value',response[0]);
                        $('#subject').attr('disabled','disabled');
                        $('#notecontentres').css('display', 'none');
                        $('#noteFilter').css('display', 'none');
                        $('#notecontentdetailres').css('display', 'none');
                        $('#addnotecontentres').css('display','block');
                        $('#quotearea').css('display','block');
                    }
                }
        });

    });
    $('#delNote').live("click",function(){
        var id= $(this).attr('value');
        var msg = "Do you really want to delete note";
	bootbox.dialog(msg, [{
            label:" <i class='icon-remove'></i>Delete",
            class : "btn-danger",
            callback: function() {
                $.ajax({
                        type:'POST',
                        url:'../elib/elib_ajax.php',
                        data: {'action': 'delNote', 'id': id},
                        success:function(response){
                            if(response){
                                noty({
                                    text: "Note Deleted",
                                    type: "success",
                                    dismissQueue: true,
                                    layout: 'topRight',
                                    theme: 'defaultTheme',
                                    timeout: 1500,
                                }); 
                                $('#backbtn').trigger("click");
                            }
                        }
                });
            }
	}, {
		label: "cancel",
		class : "btn-cancel",
		callback: function() {
                    
		}
            }
        ]);
    });
    
    $('#editNote').live("click",function(){
        var id= $(this).attr('value');
        if($(this).text()=='edit'){
            var note=$('#notes').html();
            note = note.replace(/<br\s*[\/]?>/gi, "\n");
            $('#notes').css('display','none');
            var editbox='<textarea id="editbox" maxlength="1000" style=" width:95%; height:100px; resize: vertical;" ></textarea>';
            $(editbox).insertAfter('#notes');
            var editvisi=$("#editvisible").attr('value');
                        
            var SubEdit='<button id="subedit" value="'.concat(id).concat('">Submit</button>');
            $(SubEdit).insertAfter('#editbox');
            $('#editbox').attr('value', note);
            
            var visicheck='<div style="display:flex;"><input type="checkbox" id="visicheck"><label for="visicheck">Set Private</label></div>';
            $(visicheck).insertAfter('#editbox');
            if(editvisi==2){
                $( "#visicheck" ).prop( "checked", true );
            }
            
            $(this).text('cancel');
            $("#ReplyNotebtn").css("display", "none");
        }else{
            var Span='<span class="notespan" id="tmp" value="'.concat(id).concat('"></span>');
            $(Span).insertAfter('#visicheck');
            $('#tmp').trigger('click');
            $( "#tmp").remove();
        }
    });
    
    $('#subedit').live("click",function(){
        var id= $(this).attr('value');
        var note=$( "#editbox").attr('value');
        if($('#visicheck').is(':checked')){
            var visible=2;
        }else{
            var visible=1;
        }
        $.ajax({
            type:'POST',
            url:'../elib/elib_ajax.php',
            data: {'action': 'subedit', 'id': id, 'note': note, 'visible': visible},
            success:function(response){
                if(response){
                    var msg="Update Note";
                    var success="success";
                    $( "#editbox").remove();
                    $( "#subedit").remove();
                    $( "#visicheck").remove();
                     $('#notes').css('display','block');
                     $('#editNote').text('edit');
                     var span='<span class="notespan" id="tmp" value="'.concat(id).concat('"></span>');
                     $(span).insertAfter('#notes');
                     $('#tmp').trigger('click');
                     $( "#tmp").remove();
                }else{
                    var msg="Cannot be empty";
                    var success="error";
                }
                noty({
                        text: msg,
                        type: success,
                        dismissQueue: true,
                        layout: 'topRight',
                        theme: 'defaultTheme',
                        timeout: 1500,
                }); 
            }
        });
    });

     
      $('#noteFilterselect').on('change', function(){ 
          var select=$("#noteFilterselect").val();
          if(select==2){
              $('#userfilter').css('display','block');
          }else{
              $('#userfilter').css('display','none');
          }
        notetab($('.flipbook').turn('page'));
        
        
      });
      $('#userfilter').on('change', function(){ 
          notetab($('.flipbook').turn('page'));
      });
      
      

    $('#helppop').live('click',function(){
        $('#help').attr('value',0);
        $('#helpcontain').toggle();
        $('#help').css('margin-top',$('.flipbook').offset().top);
        $('#help').css('margin-left',$('.flipbook').offset().left);
        $('#helptxt').text('click to flip book');
        setTimeout(function(){ $('.flipbook').turn ('previous');},40);
        setTimeout(function(){ $('.flipbook').turn ('next');},60);
    });
    $('#helpnext').live('click',function(){
        var x= $('#help').attr('value');
        $('#help').attr('value',x+1);
        if(x==0){
            $('#flipbk-control').css('width',$('.flipbook-viewport').width());
//            $('#flipbk-control').css('display','block');
             $('#help').css('margin-top',$('.flipbook').offset().top+$('#flipbk-control').height());
            $('#helptxt').text('hover for bookmark, zoom and fullscreen');
        }else if(x==1){
//            $('#flipbk-control').css('display','none');
            $('#navtab0').trigger('click');
            $('#helptxt').text('You can select text for more information');
        }else if(x==2){
            $('#navtab1').trigger('click');
            $('#helptxt').text('Enter a keyword for search');
            document.getElementById('searchquery').value="辰光閣文庫";
            $('#searchbtn').trigger('click');

        }else if(x==3){
            $('#navtab2').trigger('click');
            $('#helptxt').text('Enter a keyword for search');
        }else if(x==4){
            $('#navtab3').trigger('click');
             $('#helptxt').text('View notes and add your own notes');
        }else{
           $('#helptxt').text('step'.concat(x)); 
        }
    });
    $('#helpclose').live('click',function(){
        $('#helpcontain').toggle();
//        $('#flipbk-control').css('display','none');
    });
    
    //change the width and height of div, responsive to screen size
    $('#maincontent-container').live('mousemove',function(e){
        if(e.pageY<$('#flipbook-container').offset.top || e.pageX<$('#flipbook-container').offset.left 
                || e.pageY>$('#flipbook-container').offset.top+$('#flipbook-container').height() 
                || e.pageX>$('#flipbook-container').offset.left+$('#flipbook-container').width()){
                $('#turnjs').data("flipbkx",null);
                $('#turnjs').data("flipbky",null);
                }
        if ($(window).width() >= 768){
            //obsolate
            
            if( $('#infobar').data('modifywidth')==1){
                var percent=(1-((e.pageX-$('#maincontent-container').offset().left*2)/ $('#maincontent-container').width()))*100;
                if(percent<20||percent>54){
                    return;
                }
                //$('#infobar').css('width',percent+6+'%');
                //$('#turnjscontain').css('width',$('#maincontent-container').width()-$('#infobar').width()-parseInt($('#infobar').css('margin-left'))-2);
                 if(percent>30){
                    if($('#infobar').hasClass("span4")){
                        $('.flipbook').turn('display','single');
                        $('#flipbook-container').css('width',436);
                        $('.flipbook').turn('size',436, 600);
                        pageinfotab($('.flipbook').turn('page'));
                        $('#infobar').addClass('span8');
                        $('#infobar').removeClass('span4');
                        $('#turnjscontain').addClass('span4');
                        $('#turnjscontain').removeClass('span8');
                    }   
                        
                 }else{
                    if($('#infobar').hasClass("span8")){
                        $('.flipbook').turn('display','double');
                        $('#flipbook-container').css('width',872);
                        $('.flipbook').turn('size',872, 600);
                        pageinfotab($('.flipbook').turn('page'));
                        $('#infobar').addClass('span4');
                        $('#infobar').removeClass('span8');
                        $('#turnjscontain').addClass('span8');
                        $('#turnjscontain').removeClass('span4');
                    }
                 } 
            }
            else{
            
                var offset = $('#infobar').offset().left;
                if(Math.abs(e.pageX-offset)<20 && e.pageY>$('#infobar').offset().top && e.pageY<($('#infobar').offset().top+$('#infobar').height())){
                    $(this).css('cursor','ew-resize');
                }else{
                    $(this).css('cursor','');
                }
            }
        }
    });
    
    $('#maincontent-container').live('mousedown',function(e){
        if ($(window).width() >= 768 && $('#infobar').css('display')=='block'){
            var offset = $('#infobar').offset().left;
            if(Math.abs(e.pageX-offset)<20){
                $('#infobar').data('modifywidth','1');
            }
        }
    });
    
    $('#maincontent-container').live('mouseup',function(e){
         $('#infobar').data('modifywidth','0');
    });

    $( window ).resize(function() {
        popuptrigger("none");
        flipbooksize();
    });
    $('#topnavbar').css('display','block');
    $('#maincontent-container').css('padding-top','55px');
    $('#night-toggle').trigger('click');
   //loadApp();
});

//modify 896->872, 448->436
function flipbooksize(){
    var win = $(this); //this = window
    var disPage=$('#disPage').attr("value");
    
        if (win.width() >= 1278 && disPage==2) { //curr=double page
            if($('.flipbook').turn('display')=="single"){ //single page due small size
                $('.flipbook').turn('display','double');
                if($('#curtabval').attr('value')==0){
                        pageinfotab($('.flipbook').turn('page'));
                }else if($('#curtabval').attr('value')==3){
                        notetab($('.flipbook').turn('page'));
                }
            } 
            if($('#turnjscontain').hasClass("span12")){
                $('.flipbook').turn('size',872*1.2, 600*1.2);
                $('#flipbook-container').css('width',872*1.2);
                $('#flipbook-container').css('height',600*1.2);
                $('#infobar').removeClass('span6');
                $('#infobar').removeClass('span4');
                $('#turnjscontain').removeClass('span6');
                $('#turnjscontain').removeClass('span8');
            }else{
                $('.flipbook').turn('size',872, 600);
                $('#flipbook-container').css('width',872);
                $('#flipbook-container').css('height',600);
            }
            if($('#infobar').hasClass("span6") ){
                $('#infobar').addClass('span4');
                $('#infobar').removeClass('span6');
                $('#turnjscontain').addClass('span8');
                $('#turnjscontain').removeClass('span6');
                $('#flipbook-container').css('left','-3%');
            }  
            else { //double page && have span4
                 $('#flipbook-container').css('left','0');
            }
        }
        else{//win.width() < 1278 || disPage==1
            if($('.flipbook').turn('display')=="double"){ //disPage==2
                $('.flipbook').turn('display','single');
                $('nav a').css("height","40");
                $('nav a').css("width","40");
                $('nav i').css("font-size","20");
                if($('#curtabval').attr('value')==0){
                        pageinfotab($('.flipbook').turn('page'));
                }else if($('#curtabval').attr('value')==3){
                        notetab($('.flipbook').turn('page'));
                }
            }
            if (win.width() >= 820 && $('#turnjscontain').hasClass('span12')){ //820<=win.width() < 1278
                $('#flipbook-container').css('left','');
            }
            else{//win.width()<820
                if($('#infobar').hasClass("span4") || $('#infobar').hasClass("span8")){ 
                    $('#infobar').addClass('span6');
                    $('#infobar').removeClass('span4');
                    $('#infobar').removeClass('span8');
                    $('#turnjscontain').addClass('span6');
                    $('#turnjscontain').removeClass('span8');
                    $('#turnjscontain').removeClass('span4');
                }   
            }
            $('#flipbook-container').css('width',436); //*disPage
            $('#flipbook-container').css('height',600);
            $('.flipbook').turn('size',436, 600); //*disPage
             
            $('#turnjscontain').css('width','');
            $('#infobar').css('width','');
            
        }
        

}

var popupwin;
function popuptrigger(i){
    //var popuptxt = document.getElementById("popupcontainer");
    var popupnav = document.getElementById("popupnav");
    //popuptxt.style.display=i;
    popupnav.style.display=i;
	if (i=="none"&&popupwin != null ) {
    	popupwin.close();
    }
	if(i=="none"){
		$('#popupcontainer').css('display','none');
	}
}



function bookmarktab(){
    //alert('success');
    var docid=document.getElementById('docid').value;
    var version=document.getElementById('version').value;
    $.ajax({
        type:'POST',
        url:'../elib/elib_ajax.php',
        data: {'action': 'showbookmark', 'documentid': docid, 'version': version},
        success:function(response){
            var bookmarktab=document.getElementById('bookmarkcontentres');
            if(response){
                bookmarktab.innerHTML=response;}

        }
    });
    
}

function pageinfotab(page){
    //alert('success');
        var dis=$('.flipbook').turn('display').toString();
        var start_time = new Date().getTime();
        var docid=document.getElementById('docid').value;
        var version=document.getElementById('version').value;
        $.ajax({
                type:'POST',
                url:'../elib/elib_ajax.php',
                data: {'action': 'showpagetxt', 'documentid': docid, 'version': version, 'page': page, 'dis': dis},
                success:function(response){
                    var request_time = new Date().getTime() - start_time;
                    var pageinfotxt=document.getElementById('pageinfotxt');
                    if(response){
//                        alert(response);
//                    response=response.replace(/\n/g, "<br>");
                        pageinfotxt.innerHTML=request_time.toString().concat("ms <br>").concat(response);
                        //pageinfotxt.innerHTML=response;
                    }
                }
        });
    
}

function notetab(page){

    var select=$("#noteFilterselect").val();
        var viewuser=0;
        if(select=='0'){
            page='`pageno`';
        }
        if(select=='1'){
            viewuser='myself';
            page='`pageno`';
        }
        if(select=='2'){
            viewuser=$("#userfilter").val();
            page='`pageno`';
        }
        var docid=document.getElementById('docid').value;
        var version=document.getElementById('version').value;
        $.ajax({
                type:'POST',
                url:'../elib/elib_ajax.php',
                data: {'action': 'shownote', 'documentid': docid, 'version': version, 'page': page, 'viewuser':viewuser, 'mode':0},
                success:function(response){
                    if(response){
                        document.getElementById('notecontentres').innerHTML=response;
                    }else{
                        document.getElementById('notecontentres').innerHTML="No notes in this page yet.";
                    }
                }
        });
}

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

function encodeurl(i){

    $.ajax({
        type:'POST',
        url:'../elib/elib_ajax.php',
        data: {'action': 'encoderedirect', 'url': '../elib/data/1048576/'+document.getElementById('docid').value+'/'+document.getElementById('version').value+'/'+i+'.jpg'},
        success:function(response){
            $('.flipbook .p' + i).css('background-image','url("../elib/redirect.php?h='+response+'")');
        }
    })
}
function addPage(page, book) {
    var curpage=$('.flipbook').turn('page');
    for(var i=(curpage+1); i<(page+2); i++){
        if(i<=$('#mySlider').attr('max')){
            /*book.turn('addPage', '<div />', i);
                if(book.turn('addPage', '<div />', i)){
                     encodeurl(i);
                }*/
            if(book.turn('addPage', '<div />', i)){
                 encodeurl(i);
            }
        }
    }
}

function turnpage(page){
    //alert(page);
    var documentid=document.getElementById('docid').value;
    if(parseInt(page)>parseInt($('#mySlider').attr('max'))){
        alert("already on the last page");
        return;
    }
    if (!$('.flipbook').turn('hasPage', page)) {
	addPage(page, $('.flipbook'));
    }
    $('.flipbook').turn('page', page);
    $.ajax({
        type:'POST',
        url:'../elib/elib_ajax.php',
        data: {'action': 'recordPage', 'documentid' : documentid, 'page' : page}
        });
    //need to add code check all page class
    document.getElementById('mySlider').value=page;
    document.getElementById('mySliderValue').innerHTML=page;
        if($('#turnjs').css('zoom')!=1 && $('.flipbook').turn('display')=='double'){
            $('.page-wrapper:even').css('left',398);
        }
        
        if(!$('#turnjscontain').hasClass('span12') && $('#curtabval').attr('value')==0){
            pageinfotab(page);         
        }else if(!$('#turnjscontain').hasClass('span12') &&$('#curtabval').attr('value')==3){
            notetab(page);
        }
}


function lookupDict(){
    var selection = window.getSelection();
    var selected = window.getSelection().toString();
    height=$(document).height()-78;
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
        var documentid=document.getElementById('docid').value;
        var version=document.getElementById('version').value;
        var page =$('#quotepage').attr('value');

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
                    //document.getElementById('popuptxt').innerHTML=response;
                    //$('#popupnav').css('top',e.pageY-17);
                    //$('#popupcontainer').css('top',e.pageY+16);
                    
                    

            }
        });
}


function loadApp() {
        //alert('yepnope success');
		
        var page =document.getElementById('page').value;
        document.getElementById('mySliderValue').innerHTML=page;

        //var tmp=document.getElementById('turnjs');
		//alert(tmp.classList);
		//alert('try to call flipbook turn function');
	$('.flipbook').turn({
			width:872,
			height:600,
			elevation: 50,
			gradients: true,
			autoCenter: true
	});
        $('.flipbook').bind('turned', function(event, page, obj){
            var curpage= $('.flipbook').turn('page');
            for(var i=0; i<$('.page').length;i++){
                var tmp=$('.page')[i].parentElement.parentElement.getAttribute('page');
                encodeurl(tmp);
            }
            document.getElementById('mySlider').value=curpage;
            document.getElementById('mySliderValue').innerHTML=curpage;
            if($('#turnjscontain').hasClass('span8') && $('#curtabval').attr('value')==0){
                    pageinfotab($('.flipbook').turn('page'));
            }else if($('#turnjscontain').hasClass('span8') &&$('#curtabval').attr('value')==3){
                    notetab($('.flipbook').turn('page'));
            }
            //flipbooksize();
        });
        
        turnpage(page);
        flipbooksize();
        
        
        $('#navtab0').trigger('click');
        

}

/*yepnope({
	test : Modernizr.csstransforms,
	yep: ['../elib/turnjs/lib/turn.js'],
	nope: ['../elib/turnjs/lib/turn.html4.min.js'],
	both: ['../elib/PreviewDocument.css'],
	complete: loadApp
});*/
yepnope('../elib/turnjs/lib/turn.js',Modernizr.csstransforms,function(){
    loadApp();
});


