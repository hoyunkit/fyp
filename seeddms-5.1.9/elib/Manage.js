$(document).ready(function(){
    $('#bookmarkview').on('change', function(){
        //alert('select');
        var docid=$("#bookmarkview option:selected").val();
        //alert(docid);
        $.ajax({
                type:'POST',
                url:'../elib/elib_ajax.php',
                data: {'action': 'showbookmark', 'documentid': docid},
                success:function(response){
                    var bookmarktab=document.getElementById('bookmarktblcon');
                    if(response){
                        bookmarktab.innerHTML=response;}
                    else{
                        bookmarktab.innerHTML='none';
                    }
                    
                }
        });
        
    });
    
    $('#noteview').on('change', function(){
        var docid=$("#noteview option:selected").val();
        if(docid<0){
            docid='`documentid`';
        }
        var version='`version`';
//        var sort=$("#noteSortselect").val();
        $.ajax({
                type:'POST',
                url:'../elib/elib_ajax.php',
                data: {'action': 'shownote', 'documentid': docid, 'version':version, 'select':'1', 'mode':'1'},
                success:function(response){
                    //alert(response);
                    var notetab=document.getElementById('notetblcon');
                    if(response){
                        notetab.innerHTML=response;}
                    else{
                        notetab.innerHTML='none';
                    }
                    
                }
        });
        
    });
    
    $('.notespan').live("click",function(){
        var id= $(this).attr('value');
        if(document.getElementById("notespanfull".concat(id))){
            $("#notespan".concat(id)).css("display", "none");
            $("#notespanfull".concat(id)).css("display", "block");

        }else{
            $.ajax({
                    type:'POST',
                    url:'../elib/elib_ajax.php',
                    data: {'action': 'shownotedetail', 'id': id, 'mode':1},
                    success:function(response){
                        if(response){
                            var tmp="<span class='notespanfull' value='".concat(id).concat("' id='notespanfull").concat(id).concat("' style='margin:0px;'>");
                            tmp=tmp.concat(response).concat("</span>");
                            $(tmp).insertAfter("#notespan".concat(id));
                            $("#notespan".concat(id)).css("display", "none");
                        }
                    }
            });
        }
    });
    
    $('.notespanfull').live("click",function(){
        var id= $(this).attr('value');
        $("#notespan".concat(id)).css("display", "block");
        $("#notespanfull".concat(id)).css("display", "none");

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
                                $('#noteview').trigger('change');
                            }
                        }
                });
            }
	}, {
		label: "cancel",
		class : "btn-cancel",
		callback: function() {
		}
	}]);
    });
    
    $('#editNote').live("click",function(){
        var id= $(this).attr('value');
        if($(this).text()=='edit'){
            if(document.getElementById("editbox")){
                var preid=$('#subedit').attr('value');
                $("#notespan".concat(preid)).css("display", "block");
                $("#editNote").text("edit");
                $('#editbox').remove();
                $('#subedit').remove();
                $('#visicheck').remove();
                $('label[for=visicheck').remove();
            }
            $("#notespan".concat(id)).trigger('click');
            setTimeout(function (){
                var note=$("#notespanfull".concat(id)).html();
                note = note.replace(/<br\s*[\/]?>/gi, "\n");
                $("#notespanfull".concat(id)).css("display", "none");
                
                var editbox='<textarea id="editbox" maxlength="1000" style=" width:95%; height:100px; resize: vertical;" ></textarea>';
                $(editbox).insertAfter("#notespanfull".concat(id));
                $('#editbox').attr('value', note);
                     
                var SubEdit='<button id="subedit" value="'.concat(id).concat('">Submit</button>');
                $(SubEdit).insertAfter('#editbox');
                
                var editvisi=$("#editvisible".concat(id)).attr('value');   
                var visicheck='<div style="display:flex;"><input type="checkbox" id="visicheck"><label for="visicheck">Set Private</label></div>';
                $(visicheck).insertAfter('#editbox');
                if(editvisi==2){
                    $( "#visicheck" ).prop( "checked", true );
                }
                
            }, 100);
            $(this).text("cancel");
        }else{
                $('#editbox').remove();
                $('#subedit').remove();
                $('#visicheck').remove();
                $('label[for=visicheck').remove();
                $("#notespan".concat(id)).css("display", "block");
                $(this).text("edit");
                
        }
        
    });
    
    $("#subedit").live('click',function(){
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
                         $('#noteview').trigger('change');
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
    
    
    $('.delbookmarkbtn').live('click',function(){
        var page=$(this).attr('value');
        var docid=$(this).attr('data-docid');
        var version=$(this).attr('data-ver');
//        var msg = "Do you really want to delete bookmark";
        var msg = "<div id='night-adjust'>Do you really want to delete bookmark?</div>";
	bootbox.dialog(msg, [{
            label:" <i class='icon-remove'></i>Delete",
            class : "btn-danger",
            callback: function() {
                $.ajax({
                    type:'POST',
                    url:'../elib/elib_ajax.php',
                    data: {'action': 'delbookmark', 'documentid': docid, 'version': version, 'page': page},
                    success:function(response){
                        //alert(response)
                        if(response==1){
                            var msg='Bookmark deleted';
                            var success='success';
                            $('#bookmarkview').trigger('change');
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
            }
	}, {
		label: "cancel",
		class : "btn-cancel",
		callback: function() {
		}
	}]);
        
    });
    
    $('.navtab').live('click',function(){
        var curtab=$(this).attr('value');
        window.curtab =curtab;
        $('.navtab').removeClass("active");
        $(this).addClass("active");
        var tabs = ['bookmarktab', 'notetab'];
        tabs.forEach(element => document.getElementById(element).style.display='none');
        document.getElementById(tabs[curtab]).style.display='block';
        if(curtab==0){
            $('#bookmarkview').trigger('change');
        }else if(curtab==1){
            $('#noteview').trigger('change');
        }
    });
    
    $('th').live('click',function(){
        
        //alert($(this).text());
        if(window.curtab==0){
//            var sort=$(this).text();
            var sort=$(this).attr('value');
            var docid=$("#bookmarkview option:selected").val();
            $.ajax({
                type:'POST',
                url:'../elib/elib_ajax.php',
                data: {'action': 'showbookmark', 'documentid': docid, 'sort':sort},
                success:function(response){
                    var bookmarktab=document.getElementById('bookmarktblcon');
                    if(response){
                        bookmarktab.innerHTML=response;}
                    else{
                        bookmarktab.innerHTML='none';
                    }
                    
                }
            });
        }else if(window.curtab==1){
            
            var sort=$(this).text();
            var docid=$("#noteview option:selected").val();
            var version='`version`';
            if(docid<0){
                docid='`documentid`';
            }
            $.ajax({
                    type:'POST',
                    url:'../elib/elib_ajax.php',
                    data: {'action': 'shownote', 'documentid': docid, 'version':version, 'select':'1', 'mode':'1', 'sort':sort},
                    success:function(response){
                        //alert(response);
                        var notetab=document.getElementById('notetblcon');
                        if(response){
                            notetab.innerHTML=response;}
                        else{
                            notetab.innerHTML='none';
                        }

                    }
            });
            
        }
        
    });

});

$(window).load(function(){
    $('#bookmarknavtab').trigger('click');
    window.curtab =0;
    //$('#notenavtab').trigger('click');
    //window.curtab =1;
});

