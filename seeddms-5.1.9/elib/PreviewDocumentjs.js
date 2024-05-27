$(document).ready(function(){
    
    //for turnjs loading
    yepnope({
        test: Modernizr.csstransforms,
        complete: loadApp
    });

    //enable specific navtab when loading
    $('#curtabval').ready(function(){
        var tab=$("#curtabval").val();
        $('#navtab'+tab).trigger('click');
    });
    
    //scrollbar synchronization for proofreading
    $(".flex-item-right").scroll(function () { 
        $(".flex-item-left").scrollTop($(".flex-item-right").scrollTop());
        $(".flex-item-left").scrollLeft($(".flex-item-right").scrollLeft());
    });
    $(".flex-item-left").scroll(function () { 
        $(".flex-item-right").scrollTop($(".flex-item-left").scrollTop());
        $(".flex-item-right").scrollLeft($(".flex-item-left").scrollLeft());
    });
    
    //stop going back to firtst page when displayMode==2
    //displayMode==2 means that turnjs displays electronic-scanned versions
    $('.flipbook').bind('start', function(event, pageObject, corner){
        if (pageObject.next==1 && $('#displayMode').attr('value')==2) 
            event.preventDefault();
    });
    $('.flipbook').bind('turning', function(event, page, view){
        if (page==1 && $('#displayMode').attr('value')==2)
              event.preventDefault();
    });
    
	//handle night toggle button
    $('#night-toggle').live('click',function(){
        var tmp=$(this).attr('value');
        if($(this).attr('value')==0){
//                $('#flipbook-container').css('filter','invert(1)');
            $('.flipbook').css('filter','invert(1)');
        }else{
//                $('#flipbook-container').css('filter','');
            $('.flipbook').css('filter','');
        }
		
    });
    
    
    $("#prevPage").live('click',function(e){
        prevFlip();
    });
    $("#nextPage").live('click',function(e){
        nextFlip();
    });
    
    $('#textMode').live('click',function(){
        var page=$('#page').val();
        if($(this).attr('value')=='d'){
            $(this).attr('value', 'w');
            $(this).attr('class','icon-book');
            $(this).attr('title','User Mode');
            $('#pageinfotxt').css("white-space","pre-wrap");
            pageinfotab(page);
        }else if($(this).attr('value')=='w'){
            $(this).attr('value', 'd');
            $(this).attr('class','icon-align-left');
            $(this).attr('title','Editor Mode');
            $('#pageinfotxt').css("white-space","pre");
            //Sequences of white space are preserved. Lines are broken at newline characters
            //pre-wrap fills line boxes, pre will not.
            pageinfotab(page);
        }
    });
    
    $('#displayMode').bind('click',function(){
        if($('#scannedExist').attr('value')==0){
            noty({
                text: "Scanned version does not exist",
                type: "error",
                dismissQueue: true,
                layout: 'topRight',
                theme: 'defaultTheme',
                timeout: 1500
                                    });
            return;
        }
        var displayMode=parseInt($('#displayMode').attr('value'));
        var page=$('#page').val();
        displayMode=(displayMode+1)%3;
        $(this).attr('value', displayMode.toString());
        if(displayMode===0){
            $(this).attr('title', 'electronic version');
            $('#disPage').parent().show();
            $('#mySlider').attr('max', $('#maxPage').val());
            $('#mySlider').attr('min', "1");
            $('#lockContainer').css('display','none');
        }else if(displayMode===1){
            $(this).attr('title', 'scanned version');
            $('#disPage').parent().show();
            $('#mySlider').attr('max', $('#maxPage').val());
            $('#mySlider').attr('min', "1");
            $('#lockContainer').css('display','none');
        }else{
            page*=2;
            if($('#disPage').attr('value')==1){
                $('#disPage').click();
            }
            $('#disPage').parent().hide();
            $('#mySlider').attr('max', getMaxPage());
            $('#mySlider').attr('min', "2");
            $(this).attr('title', 'electronic-scanned version');
            $('#lockContainer').css('display','inline');
        }
        for(var i=0; i<$('.page').length;i++){
            var tmp=$('.page')[i].parentElement.parentElement.getAttribute('page');
            if(tmp!==null){
                encodeurl(tmp);
            }
        }
        turnpage(page);
    });
    
    
    $('#docClose').live('click', function(){
        var tabHeight=$('#infotab').height();
        $('#docTab').css('top', $('.h').offset().top);
        $('#docTab').css('left', $('.h').offset().left-37);
        $('#docTab').show();
        $('#textTab').hide();
        $('#leftTab').hide();
        $('#docArea').css("flex","0");
        $('#textArea').css("flex","1");
        updateTabHeight(tabHeight);
    });
    $('#textClose').live('click', function(){
        var tabHeight=$('#infotab').height();
        $('#textTab').css('top', $('.h').offset().top);
        $('#textTab').css('left', $('.h').offset().left+$('.h').width()+10);
        $('#textTab').show();
        $('#docTab').hide();
        $('#leftTab').hide();
        $('#leftTab').show();
        $('#docArea').css("flex","1");
        $('#textArea').css("flex","0");
        updateTabHeight(tabHeight);
    });
    $('#docTab').live('click', function(){
        var disPage=$('#disPage').attr("value");
        $('#docTab').hide();
        $('#leftTab').show();
        if(disPage==1){
            $('#docArea').css("flex","1");
            $('#textArea').css("flex","2");
        }else{
            $('#docArea').css("flex","2");
            $('#textArea').css("flex","1");
        }
        $('#infotab').height('auto');
        $('.tab-pane').height('600px');
    });
    $('#textTab').live('click', function(){
        $('#docTab').hide();
        $('#textTab').hide();
        $('#leftTab').show();
        $('#docArea').css("flex","2");
        $('#textArea').css("flex","1");
        $('#infotab').height('auto');
        $('.tab-pane').height('600px');
    });
	//handle font size change
	$('#font-size-small').live('click',function(){
            var curfont=parseInt($('#pageinfocontentres').css('font-size'));
            var value = $('#lineSpacing').attr('value');
            if(curfont<=12){
                    return;}
//		$('#pageinfocontentres').css('font-size',curfont-1);
//		$('#pageinfocontentres').css('line-height',curfont+5+'px');
            $('.tabbarres, textarea').css('font-size',curfont-1);
            $('.tabbarres, textarea').css('line-height',value*(curfont+5)+'px');
	});
	$('#font-size-large').live('click',function(){
            var curfont=parseInt($('#pageinfocontentres').css('font-size'));
            var value = $('#lineSpacing').attr('value');
            if(curfont>=45){
                    return;}
//		$('#pageinfocontentres').css('font-size',curfont+1);
//		$('#pageinfocontentres').css('line-height',curfont+7+'px');
            $('.tabbarres, textarea').css('font-size',curfont+1);
            $('.tabbarres, textarea').css('line-height',value*(curfont+7)+'px');
	});
	
        $('#lineSpacing').live('click',function(){
            var value = $('#lineSpacing').attr('value');
            var curSpacing = parseInt($('.tabbarres').css('line-height'));
            if(value==1){
                curSpacing*=1.5;
                $('#lineSpacing').attr('title', '1.5 spacing');
                $('#lineSpacing').attr('value', "1.5");
            }else if(value==1.5){
                curSpacing=curSpacing/1.5*2;
                $('#lineSpacing').attr('title', 'double-spacing');
                $('#lineSpacing').attr('value', "2");
            }else if(value==2){
                curSpacing/=2;
                $('#lineSpacing').attr('title', 'single-spacing');
                $('#lineSpacing').attr('value', "1");
            }
            $('.tabbarres').css('line-height',curSpacing+'px');
        });
	
        $('#navControl').live('click',function(){
            $("nav").hide();
            $("#showNav").show();
        });
        
        $("#showNav").live('click',function(){
            $("nav").show();
            $(this).hide();
        });
        
        $("#markNote").live('click', function(){
            var markNote=$(this).attr('value');
            if(markNote==1){
                $(this).attr('value',0);
                $(this).attr('title','hide note');
                $(this).attr('class', 'icon-eye-close');
            }else{
                $(this).attr('value',1);
                $(this).attr('title','show note');
                $(this).attr('class', 'icon-eye-open');
            }
            pageinfotab(getDPage($('.flipbook').turn('page'))); 
            //use dPage to display info,
        });
        $("u, #popupcontainer").live("mouseenter", function(e){
            var tmp=pageElement(this);
            var par=tmp[0];
            var child=tmp[1];
            selectText(this);
            var mode=0;
            var documentid=document.getElementById('docid').value;
            var version=document.getElementById('version').value;
            var page=par.id;
            var txtstart=calpos(window.getSelection(), par, child); //mark text pos[2]
            var text=this.innerHTML;
            var disPage=$('#disPage').attr("value");
            text=text.replace(/\n/g, ""); 
            $('#popupcontainer').css('display','block');
            $('#popupcontainer').css('top',$(this).offset().top+parseInt($(this).css('line-height'))+1);
            $('#popupcontainer').css('left',$(this).offset().left);
            $.ajax({
                type:'POST',
                url:'../elib/elib_ajax.php',
                data: {'action': 'shownote','mode':mode, 'documentid':documentid, 'disPage':disPage, 'markQuote':1,
                    'version':version, 'page':page, 'txtstart':txtstart, 'text':text, 'select':'-1' },
                success:function(response){
                    if(response){
                        document.getElementById('noteFrame').innerHTML=response;
                    }
                }
            });
        }).live("mouseleave", function(e){
            setTimeout(function (){
                if ($('#popupcontainer:hover').length == 0) {
                    $('#popupcontainer').css('display','none');
                    window.getSelection().empty();
                }
            },200);
        });
        
//        $("#popupcontainer").live("mouseleave", function(e){
//            $('#popupcontainer').css('display','none');
//        });
	//flipbk-control button click event
		//add bookmark btn event
		$('#addbookmarkbtn').live('click',function(){
                    var curpage= getDPage($('.flipbook').turn('page'));
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
	function flipArea(){
            if($('#turnjs').css('zoom')!=1){
                $('.flip').hide();
            }else{
                $('.flip').show();
            }
        }
        //zooming btn, larger
        $('.icon-zoom-in').live('click',function(){
            if($('#turnjs').css('zoom')!=1){
                   var fac=parseFloat($('#turnjs').css('zoom'));
                   if(fac>=1.5){
                       return;
                   }
                   $('#turnjs').animate({ 'zoom': fac+0.1}, 100);
            }else{ //zoom==1 
                $('#turnjs').animate({ 'zoom': 1.3}, 400);
                $('#turnjs').css('cursor','grab');
//                if($('.flipbook').turn('display')=='double'){
//                    $('.page-wrapper:even').css('left',398);
//                }
            }
            flipArea();
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
            flipArea();
        });
        $('#copy').live('click', function(){
            var url=window.location.href;
            var url= url.match(/(https?|http):\/\/[^\s]+out.PreviewDocument.php/gi, url);
            var page=$('#page').val();
            var documentid=document.getElementById('docid').value;
            var version=document.getElementById('version').value;
            navigator.clipboard.writeText(url+'?documentid='+documentid+'&version='+version+'&page='+page);
            noty({
                text: 'Copied document link',
                type: 'success',
                dismissQueue: true,
                layout: 'topRight',
                theme: 'defaultTheme',
                timeout: 1500,
            });
        });
        
        //fullscreen btn
        $('.icon-fullscreen').live('click',function(){
            var ele=document.getElementById('turnjs');
            ele.requestFullscreen();
//            var ele=document.getElementsByClassName('container');
//            ele[0].requestFullscreen();
        });
        
        document.addEventListener('fullscreenchange', exitHandler, false);
        document.addEventListener('mozfullscreenchange', exitHandler, false);
        document.addEventListener('MSFullscreenChange', exitHandler, false);
        document.addEventListener('webkitfullscreenchange', exitHandler, false);

        function exitHandler(){
            if (!document.webkitIsFullScreen && !document.mozFullScreen && !document.msFullscreenElement){ //exit fullscreen
                if($('#disPage').attr("value")==1){
                    $('.flipbook').turn('display','single');
                }
            }
            if (document.webkitIsFullScreen || document.mozFullScreen || document.msFullscreenElement){ //trigger fullscreen
                if($('#disPage').attr("value")==1){
                    $('.flipbook').turn('display','double');
                }
            }
        }
        //hide flipbk-control
//        $('#flipbk-control').live('mouseleave',function(){
//        $('#flipbk-control').css('display','none');
//        });
//        
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

    function replaceOptions(options){
        $('.pageNumber').empty();
        $.each(options, function(index, option) {
          $option = $("<option></option>")
            .attr("value", option.value)
            .text(option.text);
          $('.pageNumber').append($option);
        });
    }
    
    function getSingleOptions(max){
        var options=[];
        for(var i=1; i<=max; i++){
//            options.push({text: i+'/'+max, value: i});
            options.push({text: i, value: i});
        }
        return options;
    }
    
    function getDoubleOptions(max){
        var options=[];
        options.push({text: '1', value: 1});
        for(var i=2; i<=max-1; i+=2){
            options.push({text: i+'-'+(i+1), value: i});
        }
        if(max%2==0){
            options.push({text: max, value: max});
        }
        return options;
    }
        $('#disPage').live('click',function(e){
            var disPage;
            var docid=document.getElementById('docid').value;
            var width=getWidth();
            var singleWidth=width/2;
            var height=getHeight();
            var max= $('#maxPage').val();
            var page=$('#turnjs').turn('page'); //$('#page').val()
            if($('#disPage').attr("value")==1){ //curr is single Page
                $(this).attr("value", 2);
                $(this).attr("title", "change to single page");
                $(this).html('<i class="icon-file-alt"></i>');
                $('.flipbook').turn('display','double');
                $('.flipbook').turn('size',width, height);
                $('#flipbook-container').css('width',width);
                $('#flipbook-container').css('height',height);
                var options = getDoubleOptions(max);
                if(page%2!=0){page--;}
                disPage=2;
            }else if($('#disPage').attr("value")==2){ //curr is double page
                $(this).attr("value", 1);
                $(this).attr("title", "change to double page");
                 $(this).html('<i class="icon-columns"></i>');
                 $('.flipbook').turn('display','single');
                $('#flipbook-container').css('width',singleWidth); 
                $('#flipbook-container').css('height',height);
                $('.flipbook').turn('size',singleWidth, height);
                var options = getSingleOptions(max);
                disPage=1;
            }
            replaceOptions(options);
            if(disPage==2 && ($('#turnjs').turn('page') == 1 || $('#turnjs').turn('page') == max)){
                $('#right').css('right', 218);
                $('#left').css('left', 218);
            }else{
                $('#right').css('right', '0');
                $('#left').css('left', '0');
            }
            $('.pageNumber').val(page);
            if($('#curtabval').attr('value')==0){
                pageinfotab(getDPage($('.flipbook').turn('page')));
            }else if($('#curtabval').attr('value')==3){
                notetab(getDPage($('.flipbook').turn('page')));
            }   
            $.ajax({
                type:'POST',
                url:'../elib/elib_ajax.php',
                data: {'action': 'recordDisPage', 'documentid': docid, 'disPage':disPage}
            });
//            flipbooksize();
        });
	
        //mouse point
//	$(document).live('mousemove',function(e){
//            var loc = "pageX:"+e.pageX+", pageY:"+e.pageY+"\n"+"clientX:"+e.clientX+", clientY:"+e.clientY;
//            document.getElementById("mousePoint").innerHTML = loc;
//        });
	$('#turnjs').live('mousemove',function(e){
		if( $('#turnjs').data('move')==1){
			$('.page-wrapper:even').css('left',e.pageX-$('#turnjs').offset().left);
		}
	});
	$('#turnjs').live('mouseup',function(e){
		$('#turnjs').data('move',0);
		$(this).css('cursor','');
	});
    function pageNumDisplay(page){
        if($('#turnjs').turn('display')=='double' && page%2!=0){
            $('.pageNumber').val(page-1).change();
        }else{
            $('.pageNumber').val(page).change();
        }
    }
    function prevFlip(){
        var page= $('#turnjs').turn('page');// parseInt($('#page').val()); 
        if($('#turnjs').turn('display')=='double' && page%2!=0){  
            var newPage=page-2;
        }else{
             var newPage=page-1;
        } 
        if(!checkPage(newPage)){
            return;
        };
        turnpage(newPage);
    }

    function nextFlip(){
        var page= $('#turnjs').turn('page');
        if($('#turnjs').turn('display')=='double' && page%2==0){
            var newPage=page+2;
        }else {
            newPage=page+1;
        }
        if(!checkPage(newPage)){
            return;
        };
        turnpage(newPage);
    }
    function rightFlipbook(e){
        var left = turnLeft(); 
        var width=turnWidth();
        if ((e.pageX - left) < width *0.2) {
            nextFlip();
        } else if((e.pageX - left) > width *0.8){
            prevFlip();
        }
    }
    function leftFlipbook(e){
        var left = turnLeft();  
        var width=turnWidth();
        if ((e.pageX - left) > width *0.8) {
            nextFlip();
        } else if((e.pageX - left) < width *0.2){
            prevFlip();
        }
    }
    $('#turnjs, .flip').live('click',function(e){
       if($('#turnjs').css('zoom')==1){
            if($(".flipbook").turn('direction')=='rtl'){
                rightFlipbook(e);
            }else{
                leftFlipbook(e);
            }
        } 
    });
    
    function turnLeft(){
        var currPage=$('#page').val();
        var max=$('#maxPage').val();
        var left=$('#turnjs').offset().left;
        if($('#turnjs').turn('display')=='double' && parseInt($('#turnjs').css('margin-left'))<0){
            return left+436;
        }
        return left;
    }
    
    function turnWidth(){
        var currPage=$('#page').val();
        var max=$('#maxPage').val();
        if($('#turnjs').turn('display')=='double' && (currPage==1 || currPage==max)){
            return $('#turnjs').width()/2;
        }
        return $('#turnjs').width();
    }
    function turnjsDown(e){
        var zoom = $('#turnjs').css('zoom');
        if (zoom !=1) { //when turnjs zoom, grab for move
            $('#turnjs').data("drag", true);
            $('#turnjs').data("oldMouseX", e.pageX); // - container.left*zoom
            $('#turnjs').data("oldMouseY", e.pageY); // - container.top*zoom
            var top = parseInt($('#turnjs').css('top'), 10);
            var left = parseInt($('#turnjs').css('left'), 10);
            $('#turnjs').data("oldTop", top);
            $('#turnjs').data("oldLeft", left);
        }      
    }
    
    function turnjsMove(e){
        var zoom = $('#turnjs').css('zoom');
        if($('#turnjs').data("oldMouseX")!= null && $('#turnjs').data("oldMouseY")!=null && $('#turnjs').data("drag")){
            var top=$('#turnjs').data("oldTop")+(e.pageY-$('#turnjs').data("oldMouseY"))/zoom;
            var left=$('#turnjs').data("oldLeft")+(e.pageX-$('#turnjs').data("oldMouseX"))/zoom;
            $('#turnjs').css('top', top);
            $('#turnjs').css('left', left);
        }
    }
    function conBound(zoom){
        var currPage=$('#page').val();
        var max=$('#maxPage').val();
        if($('#turnjs').turn('display')=='double' && parseInt($('#turnjs').css('margin-left'))!=0){ //&& (currPage==1 || currPage==max)
           return -1*Math.abs(parseInt($('#turnjs').css('margin-left')));
        }else{
           return 218/(-zoom); 
        }
    }
    function turnjsUp(){
        var zoom = $('#turnjs').css('zoom');
        var viewWidth=$('.flipbook-viewport').width();
        var viewHeight=$('.flipbook-viewport').height();
//        var turnjsWidth=$('#turnjs').width()*zoom;
        var turnjsWidth=turnWidth()*zoom;
        var turnjsHeight=$('#turnjs').height()*zoom;
        var viewLeft=$('.flipbook-viewport').offset().left;
        var viewRight=viewLeft+viewWidth;
        var viewTop=$('.flipbook-viewport').offset().top;
        var viewBottom=viewTop+viewHeight;
//        var turnjsLeft=$('#turnjs').offset().left*zoom;
        var turnjsLeft=turnLeft()*zoom;
        var turnjsRight=turnjsLeft+turnjsWidth;
        var turnjsTop=$('#turnjs').offset().top*zoom;
        var turnjsBottom=turnjsTop+turnjsHeight;
        var dis=$('#flipbook-container').offset().left-viewLeft;
        var oldLeft=$('#turnjs').data("oldLeft");
        var oldTop=$('#turnjs').data("oldTop");
        var left = $('#turnjs').css('left');
        var top = $('#turnjs').css('top');
        
        if(viewWidth>turnjsWidth){
            if(viewLeft-turnjsLeft > 20){
                var newLeft=conBound(zoom);
            }else if(turnjsRight-viewRight >20){
                newLeft= turnjsLeft-(turnjsRight-viewRight)-210; //viewWidth-turnjsWidth;
            }
        }else if(viewWidth<turnjsWidth){
            if(turnjsLeft>viewLeft){
                newLeft=-dis;
            }else if(turnjsRight<viewRight){
                newLeft=viewWidth-turnjsWidth;
            }
        }
        
        if(viewHeight>turnjsHeight){
            if(viewTop-turnjsTop > 20 || turnjsBottom-viewBottom >20){
                var newTop=oldTop;
            } 
        }else if(viewHeight<turnjsHeight){
            if(turnjsTop>viewTop){
                newTop=0;
            }else if(turnjsBottom<viewBottom){
                newTop=viewHeight-turnjsHeight;
            }
        }
        if(newLeft!=null && newTop!=null){
            $("#turnjs").animate({top:newTop, left:newLeft}, 'slow');
        }else if(newLeft!=null){
            $("#turnjs").animate({left:newLeft}, 'slow');
        }if(newTop!=null){
            $("#turnjs").animate({top:newTop}, 'slow');
        }
        
        $('#turnjs').data("oldMouseX",null);
        $('#turnjs').data("oldMouseY",null);
        $('#turnjs').data("oldTop",null);
        $('#turnjs').data("oldLeft",null);
        if($('#turnjs').css('zoom')!=1) { 
            $('#turnjs').css('cursor','grab');
        }
        
    }
    
    document.getElementById('right').addEventListener('mouseover', function(){if($('#turnjs').css('zoom')==1){$("#right > i").show();}});
    document.getElementById('left').addEventListener('mouseover', function(){if($('#turnjs').css('zoom')==1){$("#left > i").show();}});
    document.getElementById('right').addEventListener('mouseleave', function(){$("#right > i").hide();});
    document.getElementById('left').addEventListener('mouseleave', function(){$("#left > i").hide();});
        
    document.getElementById('turnjs').addEventListener('mousedown', turnjsDown);
    document.getElementById('right').addEventListener('mousedown', turnjsDown);
    document.getElementById('left').addEventListener('mousedown', turnjsDown);
    
    document.getElementById('turnjs').addEventListener('mousemove', turnjsMove);
    document.getElementById('right').addEventListener('mousemove', turnjsMove);
    document.getElementById('left').addEventListener('mousemove', turnjsMove);
    
    document.getElementById('turnjs').addEventListener('mouseup', turnjsUp);
    document.getElementById('right').addEventListener('mouseup', turnjsUp);
    document.getElementById('left').addEventListener('mouseup', turnjsUp);

		
        $('#flipbook-container').live('mouseleave',function(e){
            $('#turnjs').data("drag", false);
        });

//        $(".pageNumber").on('change', function(event) {
//            var newPage=$(this).attr('value');
//            turnpage(newPage);
//        });

	//handle slider change
    var slider = document.getElementById('mySlider');
    slider.addEventListener('input', function() {
//        var tPage=getTPage(this.value);
//        turnpage(tPage);
        turnpage(this.value);
//        pageNumDisplay(this.value);
    }, false); 
    slider.addEventListener('mousedown', function(e) {
        $('#tooltipPage').html(getDPage($(this).val()));
        var tipWidth=$('#tooltipPage').width();
        var tipPadding=parseInt($('#tooltipPage').css('padding-left'),10)*2; //right and left
        var tipDis=(tipWidth+tipPadding)/2;
        $('#tooltipPage').offset({left:e.pageX-tipDis});
        $('#tooltipPage').css({visibility:"visible", opacity: 1});
    }, false); 
    slider.addEventListener('mousemove', function(e) {
        var sliderLeft=$(this).offset().left;
        var sliderWidth=$(this).width();
        $('#tooltipPage').html(getDPage($(this).val()));
        var tipWidth=$('#tooltipPage').width();
        var tipPadding=parseInt($('#tooltipPage').css('padding-left'),10)*2; //right and left
        var tipDis=(tipWidth+tipPadding)/2;
        if(sliderLeft<e.pageX && e.pageX<sliderLeft+sliderWidth){ //$(this).data('down')==1 && 
            $('#tooltipPage').offset({left:e.pageX-tipDis});
        }else if(sliderLeft>e.pageX){
            $('#tooltipPage').offset({left:sliderLeft});
        }else if(e.pageX>sliderLeft+sliderWidth){
            $('#tooltipPage').offset({left:sliderLeft+sliderWidth});
        }
    }, false);
    slider.addEventListener('mouseup', function(e) {
        $('#tooltipPage').css({visibility:"hidden", opacity: 0});
    }, false); 
    /*
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
   */
    
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
//            $('#infotab > li > a').css('border','1px solid black');
//            $('#infotab > li > a').css('border-radius', '0 4px 4px 0');
//            $('#infotab > li > a').css('border-left', 'transparent');
//            $('#infotab').css('border-radius', '0px');
//            $('#infotab').css('background-color', 'transparent');
        }
        
        popuptrigger("none");
        $('.navtab').removeClass("active");
        $(this).addClass("active");
        var curtab=$(this).attr('value');

        var tabs = ['pageinfotab', 'searchtab', 'bookmarktab', 'notetab', 'dictTab'];
        tabs.forEach(element => document.getElementById(element).style.display='none');
        document.getElementById('curtabval').value=curtab;
        document.getElementById(tabs[curtab]).style.display='block';
        if(curtab==0){
            pageinfotab(getDPage($('.flipbook').turn('page')));
        }else if(curtab==1){
            if(!document.getElementById('searchcontentres').innerHTML){
                var btn=document.getElementById('searchbtn');
                btn.click();}
        }else if(curtab==2){
            bookmarktab();
        }else if(curtab==3){
            notetab(getDPage($('.flipbook').turn('page')));
        }
        $.ajax({
            type:'POST',
            url:'../elib/elib_ajax.php',
            data: {'action': 'recordTab', 'tabStatus': curtab, 'documentid': documentid}
        });
    });
	
    //handle user click on search result
    $('.searchreturn').live('click',function(){
        var page=getTPage($(this).attr('value'));
        turnpage(page);
//        pageNumDisplay(page);
    });
    //handle user search request
    $('#searchbtn').live('click',function(){
        var query=document.getElementById('searchquery').value;
        var docid=document.getElementById('docid').value;
        var version=document.getElementById('version').value;
//        document.getElementById('searchquery').value="";
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
	
    $('#dictBtn').live('click',function(){
        var text=document.getElementById('dictQuery').value;
        $.ajax({
                type:'POST',
                url:'../elib/elib_ajax.php',
                data: {'action': 'lookupdict', 'text': text},
                success:function(response){
                    //alert(response);
                    var dictTab=document.getElementById('dictContentRes');
                    dictTab.innerHTML="";
                    dictTab.innerHTML=response;
                    $( "#dictTab li:first-child").addClass("active");
                    $( "#dictTabContent div:first-child").addClass("in active");
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
        $('#quotepage').attr('value',e.target.id);
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
//            $('#popupcontainer').css('top',e.pageY+20);
//            $('#popupcontainer').css('left',e.pageX-127);
//            if($('#markNote').attr('value')==0){
                popuptrigger("block");
//            }
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
    
    $('#lockToggle').live("change", function(){
        var checked=$('#lockToggle').is(':checked');
        if(checked){
            $('.flipbook-viewport').css("display", "none");
            $('.flex-container').css("display", "flex");
            $('#zoomOut, #zoomIn, #disPage, #fullScreen, #displayMode').hide();
        }else{
            $('.flipbook-viewport').css("display", "block");
            $('.flex-container').css("display", "none");
            $('#zoomOut, #zoomIn, #disPage, #fullScreen, #displayMode').show();
        }
        var page= $('#turnjs').turn('page');
        turnpage(page);
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
            $('#quotebox').attr('value','“'+window.getSelection().toString()+'”');
            var tmp=pageElement(window.getSelection().anchorNode);
            var par=tmp[0];
            var child=tmp[1];
            $('#quotepage').attr('value', par.id);
            $('#quoteStart').attr('value', calpos(window.getSelection(), par, child));
            $('#navtab3').trigger('click');
            $('#notecontentres').css('display', 'none');
            $('#noteFilter').css('display', 'none');
            $('#noteSort').css('display', 'none');
            $('#notecontentdetailres').css('display', 'none');
            $('#addnotecontentres').css('display','block');
            $('#quotearea').css('display','block');
            $('#addnotebtn').css('display','inline');
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
            var docid=document.getElementById('docid').value;
            var version = document.getElementById('version').value;
            var tmp=pageElement(window.getSelection().anchorNode);
            var par=tmp[0];
            var child=tmp[1];
            var pos=calpos(window.getSelection(), par, child);
            var text=window.getSelection().toString();
            text=text.replace(/\n/g, "");
//            $.ajax({
//                type:'POST',
//                url:'../elib/elib_ajax.php',
//                data: {'action': 'checkHighlight', 'docid': docid, 'version':version, 'pos':pos, 'text':text},
//                success:function(response){
//                    if(response=='1'){
//                        $('#highlightReminder').attr("hidden", false);
//                        $('#highlightReminder').modal('show');
//                    }
//                }
//            });
            if(window.getSelection().anchorNode.parentElement.localName==="mark" || 
                (window.getSelection().anchorNode.parentElement.localName==="font" && window.getSelection().anchorNode.parentElement.parentElement.localName==="mark")){
                var data=[document.getElementById('docid').value, document.getElementById('version').value];
                data.push(pos); //mark text pos[2]
                data.push(par.id); //page no [3]
                window.getSelection().empty();    
                $.ajax({
                    type:'POST',
                    url:'../elib/elib_ajax.php',
                    data: {'action': 'removehighlight','data':data},
                    success:function(response){
                        pageinfotab(getDPage($('.flipbook').turn('page')));
                    }
                });
            }else{
                if($('#markNote').attr('value')==1){
                    $.ajax({
                        type:'POST',
                        url:'../elib/elib_ajax.php',
                        data: {'action': 'highlightReminder'},
                        success:function(response){
                            if(response=='1'){
                                $('#highlightReminder').attr("hidden", false);
                                $('#highlightReminder').modal('show');
                            }
                        }
                    });
                }
                var data=[document.getElementById('docid').value, document.getElementById('version').value];
                data.push(text); //select text [2]
                data.push(par.id); //page no [3]
                data.push(pos);
                window.getSelection().empty();
                $.ajax({
                    type:'POST',
                    url:'../elib/elib_ajax.php',
                    data: {'action': 'highlighttxt','data':data},
                    success:function(response){
                        pageinfotab(getDPage($('.flipbook').turn('page')));
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
    $('#understand').live('click',function(){
        $('#disclaimer').modal({ show: false});
        $.ajax({
            type:'POST',
            url:'../elib/elib_ajax.php',
            data: {'action': 'updateHighlightReminder'}
        });
    });
    $(document).keydown(function(e){
        if( e.which === 90 && e.ctrlKey && $('#curtabval').val()==0){
           var data=[document.getElementById('docid').value, document.getElementById('version').value];
            $.ajax({
                type:'POST',
                url:'../elib/elib_ajax.php',
                data: {'action': 'undoHighlight','data':data},
                success:function(response){
                    pageinfotab(getDPage($('.flipbook').turn('page')));
                }
            });
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
            $('#userfilter').css('display','none');
            $('#noteSort').css('display','none');
            $('#addnotecontentres').css('display','block');
            $('textarea').val('');
            $(this).text("Back");
        }else{
            notetab(getDPage($('.flipbook').turn('page')));
            $('#notecontentres').css('display', 'block');
            $('#noteFilter').css('display', 'inline');
            $('#noteSort').css('display', 'inline');
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
            data.push(getDPage($('.flipbook').turn('page')));
            data.push("");
        }else{
            data.push($('#quotepage').attr('value'));
            var quote = $('#quotebox').attr('value');
            data.push(quote.slice(1, -1));
        }
        data.push($('#parent').attr('value'));
        if($('#visible').is(':checked')){
            data.push("2");
            
        }else{
            data.push("1");

        }
        data.push($('#noteType').val());
        data.push($('#quoteStart').attr('value'));
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
                        if($('#subject').attr('value')==0){
                            var msg='Subject cannot be empty';
                        }else if($('#notebox').attr('value')==0){
                            var msg='Note content cannot be empty';
                        }
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
        $('#navtab0').trigger('click'); //professor ask go back to info area after submitting the note
    });

    $('.notespan').live("click",function(){
        var id= $(this).attr('value');
        if($('#popupcontainer').css('display')=='block'){
            $('#backMode').attr('value', 2);
        }
        $('#navtab3').trigger('click');
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
                        $('#noteSort').css('display', 'none');
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
    function noteDisplay(){
        $('#notecontentres').css('display', 'block');
        $('#noteFilter').css('display', 'inline');
        $('#notecontentdetailres').css('display', 'none');
        $('#addnotebtn').css("display", "inline");
        $('#noteSort').css('display','inline');
        $('#addnotecontentres').css('display','none');
    }
    $('#backbtn').live("click",function(){
        var par=$(this).attr('value');
        var backMode=$('#backMode').attr('value');
        // backMode==0 means back to parent directory
        if(par==0 && backMode==0){ //back to the main directory of note
            noteDisplay();
        }else if(par!=0 && backMode==0){ //back to parent note
            var Span='<span class="notespan" id="tmp" value="'.concat(par).concat('"></span>');
            $(Span).insertAfter($(this));
            $('#tmp').trigger('click');
            $( "#tmp").remove();
            $('#addnotecontentres').css('display','none');
        }else if(backMode==1){ //exit edit panel, back to this note
            $('#editNote').css("display", 'inline');
            var id= $('#editNote').attr('value');
            var Span='<span class="notespan" id="tmp" value="'.concat(id).concat('"></span>');
            $(Span).insertAfter($(this));
            $('#tmp').trigger('click');
            $( "#tmp").remove();
        }else if(backMode==2){ //back to info area
            noteDisplay();
            $('#navtab0').trigger('click');
        }
        $('#backMode').attr('value', 0);
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
                        response=jQuery.parseJSON( response );
                        $('#quotebox').attr('value','“'+response[1]+'”');
                        $('#subject').attr('value',response[0]);
                        $('#subject').attr('disabled','disabled');
                        $('#notecontentres').css('display', 'none');
                        $('#noteFilter').css('display', 'none');
                        $('#noteSort').css('display', 'none');
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
                                    timeout: 1500
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
        if($(this).is("i")){
            var note=$('#notes').html();
            note = note.replace(/<br\s*[\/]?>/gi, "\n");
            $('#notes').css('display','none');
            var editbox='<textarea id="editbox" maxlength="1000" style=" width:95%; height:100px; resize: vertical;" ></textarea>';
            $(editbox).insertAfter('#notes');
            var editvisi=$("#editvisible").attr('value');            
            var SubEdit='<button id="subedit" value="'.concat(id).concat('">Submit</button>');
            $(SubEdit).insertAfter('#editbox');
            note=note.replace(/&amp;/g, '&');
            note=note.replace(/<a.*(https?:\/\/[^\s]+(\d+)).*<\/a>/i, '$1');
            $('#editbox').attr('value', note);
            var visicheck='<div style="display:flex;"><input type="checkbox" id="visicheck"><label for="visicheck">Set Private</label></div>';
            $(visicheck).insertAfter('#editbox');
            if(editvisi==2){
                $( "#visicheck" ).prop( "checked", true );
            }
            $('#backMode').attr('value', 1);
//            $(this).text('cancel');
            $("#ReplyNotebtn").css("display", "none");
            $('#editNote').css("display", "none");
        }
//        else{
//            var Span='<span class="notespan" id="tmp" value="'.concat(id).concat('"></span>');
//            $(Span).insertAfter('#visicheck');
//            $('#tmp').trigger('click');
//            $( "#tmp").remove();
//        }
    });
    
    $('#subedit').live("click",function(){
        var id= $(this).attr('value');
        var note=$( "#editbox").attr('value');
        if($('#visicheck').is(':checked')){
            var visible=2;
        }else{
            var visible=1;
        }
        $('#backMode').attr('value', 0);
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
                    if($('#subject').attr('value')==0){
                        var msg='Subject cannot be empty';
                    }else if($('#notebox').attr('value')==0){
                        var msg='Note content cannot be empty';
                    }
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
        notetab(getDPage($('.flipbook').turn('page')));
        
        
      });
      $('#userfilter').on('change', function(){ 
          notetab(getDPage($('.flipbook').turn('page')));
      });
      $('#noteSortselect').on('change', function(){ 
          notetab(getDPage($('.flipbook').turn('page')));
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
    
    
    $( window ).resize(function() {
        popuptrigger("none");
        flipbooksize();
    });
    $('#topnavbar').css('display','block');
    $('#maincontent-container').css('padding-top','55px');
//    $('#night-toggle').trigger('click');
   //loadApp();
});

function updateTabHeight(oldTabHeight){
    var tabOverflow = $('#infotab').height()-oldTabHeight;
    $('.tab-pane').outerWidth($('.tab-pane').outerWidth()-tabOverflow);
}

//modify 896->872, 448->436
function flipbooksize(){
    var win = $(this); //this = window
    var disPage=$('#disPage').attr("value");
    var width = getWidth();
    var height = getHeight();
    var singleWidth = width/2;
    if($('.flipbook').length){
        if (win.width() >= 1278 && disPage==2) { //win.width() >= 1278 &&
            if($('.flipbook').turn('display')=="single"){ //single page due small size
                $('.flipbook').turn('display','double');
                if($('#curtabval').attr('value')==0){
                        pageinfotab(getDPage($('.flipbook').turn('page')));
                }else if($('#curtabval').attr('value')==3){
                        notetab(getDPage($('.flipbook').turn('page')));
                }
            } 
            if($('#turnjscontain').hasClass("span12")){
                $('.flipbook').turn('size',width*1.2, height*1.2);
                $('#flipbook-container').css('width',width*1.2);
                $('#flipbook-container').css('height',height*1.2);
                $('#infobar').removeClass('span6');
                $('#infobar').removeClass('span4');
                $('#turnjscontain').removeClass('span6');
                $('#turnjscontain').removeClass('span8');
            }else{
                $('.flipbook').turn('size',width, height);
                $('#flipbook-container').css('width',width);
                $('#flipbook-container').css('height',height);
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
            if($('.flipbook').turn('display')=="double"){ //disPage==2 adapt to small window
                $('.flipbook').turn('display','single');
                $('nav a').css("height","40");
                $('nav a').css("width","40");
                $('nav i').css("font-size","20");
                $('#turnjs').turn('page', $('#page').val());
                if($('#curtabval').attr('value')==0){
                        pageinfotab(getDPage($('#page').val()));
                }else if($('#curtabval').attr('value')==3){
                        notetab($('#page').val());
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
//            $('#flipbook-container').css('width',436); //*disPage
//            $('#flipbook-container').css('height',600);
//            $('.flipbook').turn('size',436, 600); //*disPage
             
             $('#flipbook-container').css('width',singleWidth); //*disPage
            $('#flipbook-container').css('height',height);
            $('.flipbook').turn('size',singleWidth, height); //*disPage
            $('#turnjscontain').css('width','');
            $('#infobar').css('width','');
            
        } 
    }
}

var popupwin;
function popuptrigger(i){
//    var popuptxt = document.getElementById("popupcontainer");
    var popupnav = document.getElementById("popupnav");
//    popuptxt.style.display=i;
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
        var displayMode=$('#displayMode').attr('value');
        dis=(dis==='double' && displayMode==2)?'single':dis;
        var start_time = new Date().getTime();
        var docid=document.getElementById('docid').value;
        var version=document.getElementById('version').value;
        var align=$('#textMode').attr('value');
        var markNote=$('#markNote').attr('value');
        var query=$('#query').attr('value');
        $.ajax({
                type:'POST',
                url:'../elib/elib_ajax.php',
                data: {'action': 'showpagetxt', 'documentid': docid, 'version': version, 'page': page, 'dis': dis, 'align':align, 'markNote':markNote},
                success:function(response){
                    var request_time = new Date().getTime() - start_time;
                    var pageinfotxt=document.getElementById('pageinfotxt');
                    if(response){
                        response=response.replace(/\n/g, "<br>");
                        response=response.replace(query, "<span id=\"queryText\" style=\"background-color:yellow\">"+query+"</span>");
                        pageinfotxt.innerHTML=request_time.toString().concat("ms <br>").concat(response);
                        //pageinfotxt.innerHTML=response;
                    }
                }
        });
}

setTimeout(
    function() {
        $("#query").attr('value', '');
        $("#queryText").replaceWith(function() { return $(this).contents(); });
}, 3000);

function notetab(page){
    var select=$("#noteFilterselect").val();
    var docid=document.getElementById('docid').value;
    var version=document.getElementById('version').value;
    var disPage=$('#disPage').attr("value");
    var displayMode=$('#displayMode').attr('value');
    disPage=(disPage==2 && displayMode==2)?'1':disPage;
    var sort=$("#noteSortselect").val();
    if(select=='2'){
        var viewuser=$("#userfilter").val();
    }
    $.ajax({
            type:'POST',
            url:'../elib/elib_ajax.php',
            data: {'action': 'shownote', 'documentid': docid, 'version': version, 'page': page, 'viewuser':viewuser, 'mode':0, 'select':select, 'sort':sort, 'disPage':disPage},
            success:function(response){
                if(response){
                    document.getElementById('notecontentres').innerHTML=response;
                }else{
                    document.getElementById('notecontentres').innerHTML="No notes in this page yet.";
                }
            }
    });
}

//function calpos(selection, mode){
//    var offset = 0;
//    var range = selection.getRangeAt(0);
//    if(mode==0){
//        var start=0;
//    }else{
//        var start = range.startOffset;
//    }
//    if ( selection.baseNode.parentNode.hasChildNodes() ) { 
//        if(mode==0){ //have mark tag
//            var par=selection.baseNode.parentNode;
//        }else{
//             var par=selection.baseNode;
//        }
//        
//        for ( var i = 0 ; par.parentNode.childNodes.length > i ; i++ ) { //page content seperated by mark
//            var cnode = par.parentNode.childNodes[i];
//            if (cnode.nodeType == document.TEXT_NODE) { //text
//                if ( cnode==par) {
//                    break; 
//                }   
//                offset = offset + cnode.length;
//            }   
//            if (cnode.nodeType == document.ELEMENT_NODE) { //text
//                if ( cnode==par) {
//                    break;
//                }   
//                offset = offset + cnode.textContent.length;
//            }   
//        }   
//    }   
//    return (start+offset);
//}

function selectText(element) {
    var doc = document
        , text = element
        , range, selection
    ;    
    if (doc.body.createTextRange) {
        range = document.body.createTextRange();
        range.moveToElementText(text);
        range.select();
    } else if (window.getSelection) {
        selection = window.getSelection();        
        range = document.createRange();
        range.selectNodeContents(text);
        selection.removeAllRanges();
        selection.addRange(range);
    }
}

function calpos(selection, par, selectNode){
    var offset = 0;
    var range = selection.getRangeAt(0);
    var start = range.startOffset;
    for (var i = 0 ; par.childNodes.length > i; i++) { //page content seperated by mark, font
        var cnode = par.childNodes[i];
        if (cnode.nodeType == document.TEXT_NODE) { //text
            if ( cnode==selectNode) {
                break; 
            }   
            offset = offset + cnode.length;
        }
        else if (cnode.nodeType == document.ELEMENT_NODE) { 
            if ( cnode==selectNode) {
                break;
            }   
            offset = offset + cnode.textContent.length;
        }   
    }      
    return (start+offset);
}

function pageElement(selection){
    var par=selection;
    while(par.nodeType == document.TEXT_NODE || 
            par.nodeType == document.ELEMENT_NODE && !par.classList.contains('page')){
        var child=par;
        par=par.parentElement;
    }
    return [par, child];
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

function encodeurl(i){ //i for tPage
    var displayMode=$('#displayMode').attr('value');
    var dir=$(".flipbook").turn('direction');
    switch(displayMode){
        case '0': //if displayMode==0 && 1, i and dPage are equal
            var imagePath=document.getElementById('version').value+'/'+i+'.jpg';
            break;
        case '1':
            imagePath='book/'+i+'.jpg';
            break;
        case '2':
            var dPage = Math.floor(i/2); //dPage for display page num
            if(dPage===0) return;
            if(i%2===1 && dir==='rtl' || i%2===0 && dir==='ltr'){
                imagePath='book/'+dPage+'.jpg';
            }else{
                imagePath=document.getElementById('version').value+'/'+dPage+'.jpg';
            }
    }
    $.ajax({
        type:'POST',
        url:'../elib/elib_ajax.php',
        data: {'action': 'encoderedirect', 'url': '../elib/data/1048576/'+document.getElementById('docid').value+'/'+imagePath},
        success:function(response){
            var checked=$("#lockToggle").is(':checked');
            $('.flipbook .p' + i).css('background-image','url("../elib/data/1048576/'+document.getElementById('docid').value+'/'+imagePath+'?'+new Date().getTime()+'")');
        }
    });
}

//function encodeurl(i){
//
//    $.ajax({
//        type:'POST',
//        url:'../elib/elib_ajax.php',
//        data: {'action': 'encoderedirect', 'url': '../elib/data/1048576/'+document.getElementById('docid').value+'/'+document.getElementById('version').value+'/'+i+'.jpg'},
//        success:function(response){
//            $('.flipbook .p' + i).css('background-image','url("../elib/redirect.php?h='+response+'")');
//        }
//    })
//}

function addPage(tPage, book) {
    tPage=parseInt(tPage);
    var curpage=$('.flipbook').turn('page');
    for(var i=(curpage); i<tPage+2; i++){
        if(book.turn('addPage', '<div />', i)){
             encodeurl(i);
        }
    }
}

function addFlexPage(tPage){
    var dPage = Math.floor(tPage/2); //dPage for display page num
    if(dPage===0) return;
    if($("#seCrop").val()==1){ //electronic cropped image for editor mode exists
        var sePath="e/";
    }else{
        sePath="";
    }
    $('.flex-item-left > img').attr("src",'../elib/data/1048576/'+document.getElementById('docid').value+'/book/'+sePath+dPage+'.jpg?'+new Date().getTime());
    if($("#eeCrop").val()==1){ //scanned cropped image for editor mode exists
        var eePath="e/";
    }else{
        eePath="";
    }
    $('.flex-item-right > img').attr("src",'../elib/data/1048576/'+document.getElementById('docid').value+'/'+document.getElementById('version').value+'/'+eePath+dPage+'.jpg?'+new Date().getTime());
}

function getMaxPage(){
    var max = $('#maxPage').val();
    var displayMode = $('#displayMode').attr('value');
    if(displayMode==2){
        return parseInt(max)*2;
    }else{
        return parseInt(max);
    }
}

//dPage is page num shown behind the slider bar, tPage is the page num recorded by turnjs
//dPage = tPage when the display mode is in electronic or scanned version
//dPage = tPage/2 when the display mode is in electronic-scanned version
function getDPage(tPage){ //convert from tPage to dPage
    var displayMode = $('#displayMode').attr('value');
    if(displayMode==2){
        return Math.floor(tPage/2);
    }else{
        return tPage;
    }
}

function getTPage(dPage){
    var displayMode = $('#displayMode').attr('value');
    if(displayMode==2){
        return dPage*2;
    }else{
        return dPage;
    }
}

function checkPage(page){
    var max=getMaxPage();
    var displayMode = $('#displayMode').attr('value');
    if(parseInt(page)>max){
        noty({
            text: 'already on the last page',
            type: 'error',
            dismissQueue: true,
            layout: 'topRight',
            theme: 'defaultTheme',
            timeout: 1500,
        });
        return false;
    }
    if(parseInt(page)<1 || displayMode==2 && parseInt(page)<2){
        noty({
            text: 'already on the first page',
            type: 'error',
            dismissQueue: true,
            layout: 'topRight',
            theme: 'defaultTheme',
            timeout: 1500,
        });
        return false;
    }
    return true;
}

function turnpage(tPage){ //tPage for turnjs page
    var documentid=document.getElementById('docid').value;
    var maxDPage=$('#maxPage').val();
    var disPage=$('#disPage').attr('value'); //single or double
    var displayMode=$('#displayMode').attr('value');
    var checked=$('#lockToggle').is(':checked');
    if(!checkPage(tPage)){ //check if page exists
        return;
    }
    if(checked){
        addFlexPage(tPage);
    }
    if(!$('.flipbook').turn('hasPage', tPage)) {
	addPage(tPage, $('.flipbook'));
    }
    $('.flipbook').turn('page', tPage);
    var dPage = getDPage(tPage);
    $.ajax({
        type:'POST',
        url:'../elib/elib_ajax.php',
        data: {'action': 'recordPage', 'documentid' : documentid, 'page' : dPage}
    });
    //need to add code check all page class
    document.getElementById('mySlider').value=tPage;
    document.getElementById('mySliderValue').innerHTML=dPage;
    $('#page').val(dPage);
    if($('#curtabval').attr('value')==0){
        pageinfotab(dPage);         
    }else if($('#curtabval').attr('value')==3){
        notetab(dPage);
    }
    //ajdust flipArea(class="flip") position
    if(displayMode!=2 && disPage==2 && (dPage == 1 || dPage == maxDPage)){
        $('#right').css('right', 218);
        $('#left').css('left', 218);
    }else{
        $('#right').css('right', '0');
        $('#left').css('left', '0');
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
                    document.getElementById('popuptxt').innerHTML=response;
                    $('#popupnav').css('top',e.pageY-17);
                    $('#popupcontainer').css('top',e.pageY+16);
                    
                    

            }
        });
}

function getWidth(){
    return parseInt($('#width').val());
}

function getHeight(){
    return parseInt($('#height').val());
}
        
function loadApp() {
        //alert('yepnope success');

//        document.getElementById('currPage').innerHTML=page+'/'+maxPage;
        var width=getWidth();
        var height=getHeight();
        var max=getMaxPage();
        //var tmp=document.getElementById('turnjs');
		//alert(tmp.classList);
		//alert('try to call flipbook turn function');
	$('.flipbook').turn({
            width:width,
            height:height,
            elevation: 50,
            gradients: true,
            autoCenter: true,
            totalPages:max
	});
        
//        $('.flipbook').bind('start', function(event, pageObject, corner){
//            if (pageObject.next==1) 
//                event.preventDefault();
//        });
//        $('.flipbook').bind('turning', function(event, page, view){
//            if (page==1)
//                  event.preventDefault();
//        });
        
        $('.flipbook').bind('turned', function(event, page, obj){
            var curpage= $('.flipbook').turn('page');
            for(var i=0; i<$('.page').length;i++){
                var tmp=$('.page')[i].parentElement.parentElement.getAttribute('page');
                encodeurl(tmp);
            }
//            document.getElementById('mySlider').value=curpage;
//            document.getElementById('mySliderValue').innerHTML=curpage;
//            if($('#turnjscontain').hasClass('span8') && $('#curtabval').attr('value')==0){
//                    pageinfotab(page);
//            }else if($('#turnjscontain').hasClass('span8') &&$('#curtabval').attr('value')==3){
//                    notetab(page);
//            }
        });
        var page =$('#page').val();
        turnpage(page);
        flipbooksize();
         $('.loader').hide();
        $('#night-toggle').trigger('click');

}

//yepnope({
//	test : Modernizr.csstransforms,
//	yep: ['../elib/turnjs/lib/turn.js'],
//	nope: ['../elib/turnjs/lib/turn.html4.min.js'],
//	both: ['../elib/PreviewDocument.css'],
//	complete: loadApp
//});
//yepnope('../elib/turnjs/lib/turn.js',Modernizr.csstransforms,function(){
//    loadApp();
//});



