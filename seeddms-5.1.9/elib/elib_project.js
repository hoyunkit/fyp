/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

var prevHeight = 0;
var prevWidth = 0;

function tgl(id) {
        $('#'.concat(id).concat('Row')).toggle();
        $('#'.concat(id).concat('Pic')).toggle();

};
$(document).ready( function() {
//    if($('.controls > select').hasClass('chzn-select')){
//        $('.controls').width('300px');
//    }
    $('.tglbtn').live('click',function(){
        var id=$(this).attr('id');
        tgl(id);
        if($(this).hasClass('icon-th-large')){
            $(this).addClass('icon-th-list');
            $(this).removeClass('icon-th-large');
        }else{
            $(this).addClass('icon-th-large');
            $(this).removeClass('icon-th-list');
        }
    });
//    $('#font-size-small').live('click',function(){
//        var curfont=parseInt($('#maincontent-container').css('font-size'));
//        if(curfont<=12){
//            return;}
//        $('#maincontent-container').css('font-size',curfont-1);
//        $('#maincontent-container').css('line-height',curfont+5+'px');
//
//    });
//    $('#font-size-large').live('click',function(){
//        var curfont=parseInt($('#maincontent-container').css('font-size'));
//        if(curfont>=45){
//            return;}
//        $('#maincontent-container').css('font-size',curfont+1);
//        $('#maincontent-container').css('line-height',curfont+7+'px');
//
//    });
    $('#menutogglebtn').live('click',function(){
        if($(this).attr('value')==0){
            $('#topnavbar').css('display', 'contents');
            setTimeout(function(){ $('#dropdown-btn').click()}, 100);
            $(this).attr('value','1');
        }else{
            $('#topnavbar').css('display', 'none');
            $('#dropdown-btn').click();
            $(this).attr('value','0');
        }
    });


    $('#span4control').live('click',function(){
        $('.span4').toggle();
        if($(this).attr('value')==1){
            $(this).attr('value', '0');
            $('#span8mainContent').addClass('span12');
            $('#span8mainContent').removeClass('span8');
            $('#span8mainContent').css('margin-left', '0px');
            $(this).addClass('icon-plus-sign');
            $(this).removeClass('icon-minus-sign');
            $(this).text('Menu');
        }else{
            $(this).attr('value', '1');
            $('#span8mainContent').addClass('span8');
            $('#span8mainContent').removeClass('span12');
            $('#span8mainContent').css('margin-left', '');
            $(this).addClass('icon-minus-sign');
            $(this).removeClass('icon-plus-sign');
            $(this).text(' ');
        }
    });

        $('#night-toggle').live('click',function(){
            var tmp=$(this).attr('value');
            if($(this).attr('value')==0){
                $('body').addClass('night');
                $('.well').addClass('night');
                $('legend').addClass('night');
                $('tr').addClass('night');
                $('.navbar-inner').addClass('night');
                $('.breadcrumb').addClass('night');
                $('.nav-tabs').addClass('night');
                $('#maincontent-container').addClass('night');
                $('#searchcontainer').addClass('night');
                $('.h').addClass('night');
                $('a').addClass('night');
                $('.jqtree-element').addClass('night');
                $('.pagination').addClass('night');
                $('.input-append').addClass('night');
                $('.table-condensed').addClass('night');
                $(this).attr('value','1');
            }else{
                $('body').removeClass('night');
                $('.well').removeClass('night');
                $('legend').removeClass('night');
                $('tr').removeClass('night');
                $('.navbar-inner').removeClass('night');
                $('.breadcrumb').removeClass('night');
                $('.nav-tabs').removeClass('night');
                $('#maincontent-container').removeClass('night');
                $('#searchcontainer').removeClass('night');
                $('.h').removeClass('night');
                $('a').removeClass('night');
                $('.jqtree-element').removeClass('night');
                $('.pagination').removeClass('night');
                $('.input-append').removeClass('night');
                $('.table-condensed').removeClass('night');
                $(this).attr('value','0');
            }
            $.ajax({
                type:'POST',
                url:'../elib/elib_ajax.php',
                data: {'action': 'night_toggle', 'tmp': tmp},
                success:function(response){

                }
            });
        });
    

//prevHeight = $(window).height();
//prevWidth = $(window).width();
//
//$(window).resize(function() {
//    var currentWidth = $(window).width();
//    var ratio = currentWidth/prevWidth;
//    $("div.span4").find("input").each(function() {
//        $( this ).width($( this ).width()*ratio);
//    });
//    $("div.span4").find("select").each(function() {
//        $( this ).width($( this ).width()*ratio);
//    });
//    $("div.span4").find("select2").each(function() {
//        $( this ).width($( this ).width()*ratio);
//    });
//    prevWidth = currentWidth;
//});

    $( window ).resize(function() {
        if(window.innerWidth<=979){
            $('#topnavbar').css('display', 'block');
            $('#searchcontainer').css('display', 'none');
//            $('.btn-navbar').addClass('collapsed');
//            $('.nav-collapse.nav-col1').removeClass('in collapse');
//            $('.nav-collapse.nav-col1').addClass('collapse');
//            $('.nav-collapse.nav-col1').css('height', '0px');
//            if(!$('.btn-navbar').hasClass("collapsed")){
//                $('.btn-navbar').click();
//            }
        }else if(window.innerWidth>979 && window.pageYOffset< '200'&& $('#searchcontainer').length>0){
            $('#topnavbar').css('display', 'none');
            $('#searchcontainer').css('display', 'block');
        }
        $('#maincontent-container').css('min-height', window.innerHeight);
    });



    $(window).resize();
    $('#topnavbar').css('display', 'block');
    $('#night-toggle').trigger('click');
});           


               
                    
                    
                  

                            
                   