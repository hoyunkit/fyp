$(document).ready(function(){
function manageResize(md, sizeProp, posProp, oldTabHeight) {
	var r = md.target;

	var prev = r.previousElementSibling;
	var next = r.nextElementSibling;
	if (!prev || !next) {
		return;
	}

	md.preventDefault();

	var prevSize = prev[sizeProp];
	var nextSize = next[sizeProp];
	var sumSize = prevSize + nextSize;
	var prevGrow = Number(prev.style.flexGrow);
	var nextGrow = Number(next.style.flexGrow);
	var sumGrow = prevGrow + nextGrow;
	var lastPos = md[posProp];

	function onMouseMove(mm) {
		var pos = mm[posProp];
		var d = pos - lastPos;
		prevSize += d;
		nextSize -= d;
		if (prevSize < 0) {
			nextSize += prevSize;
			pos -= prevSize;
			prevSize = 0;
		}
		if (nextSize < 0) {
			prevSize += nextSize;
			pos += nextSize;
			nextSize = 0;
		}

		var prevGrowNew = sumGrow * (prevSize / sumSize);
		var nextGrowNew = sumGrow * (nextSize / sumSize);

		prev.style.flexGrow = prevGrowNew;
		next.style.flexGrow = nextGrowNew;

		lastPos = pos;
                
                if($('#docArea').css("flex-grow")==='0' || $('#leftTab').width()>$('#docArea').width()){
                    $('#leftTab').hide();
                }else{
                    $('#leftTab').show();
                }
	}

	function onMouseUp(mu) {
		// Change cursor to signal a state's change: stop resizing.
		const html = document.querySelector('html');
		html.style.cursor = 'default';

		if (posProp === 'pageX') {
			r.style.cursor = 'ew-resize'; 
		} else {
			r.style.cursor = 'ns-resize';
		}
		
		$(window).unbind("mousemove", onMouseMove);
		$(window).unbind("mouseup", onMouseUp);
//                if($('#turnjs').css('zoom')==1){
//                    if($('#textArea').css("flex-grow")/$('#docArea').css("flex-grow")<1 && $('#disPage').attr("value")==1
//                            || $('#docArea').css("flex-grow")/$('#textArea').css("flex-grow")<1.7 && $('#disPage').attr("value")==2){
//                        $('#disPage').click();
//                    }
//                }
                var tabOverflow = $('#infotab').height()-oldTabHeight;
                $('.tab-pane').height($('.tab-pane').height()-tabOverflow+20);
                if($('#textArea').css("flex-grow")==='0'){
                    $('#textTab').css('top', $('.h').offset().top);
                    $('#textTab').css('left', $('.h').offset().left+$('.h').width()+10);
                    $('#textTab').show();
                }
                if($('#docArea').css("flex-grow")==='0'){
                    $('#docTab').css('top', $('.h').offset().top);
                    $('#docTab').css('left', $('.h').offset().left-37);
                    $('#docTab').show();
                }
                if($('#docArea').css("flex-grow")==='0' || $('#leftTab').width()>$('#docArea').width()){
                    $('#leftTab').hide();
                }else{
                    $('#leftTab').show();
                }
	}

	$(window).bind("mousemove", onMouseMove);
	$(window).bind("mouseup", onMouseUp);
        $('#docTab').hide();
        $('#textTab').hide();
}

function setupResizerEvents() {
	$(document.body).bind("mousedown", function (md) {

		// Used to avoid cursor's flickering
		const html = document.querySelector('html');
		
		var target = md.target;
		if (target.nodeType !== 1 || target.tagName !== "FLEX-RESIZER") {
			return;
		}
		var parent = target.parentNode;
		var h = parent.classList.contains("h");
		var v = parent.classList.contains("v");
		if (h && v) {
			return;
		} else if (h) {
			// Change cursor to signal a state's change: begin resizing on H.
			target.style.cursor = 'col-resize';
			html.style.cursor = 'col-resize'; // avoid cursor's flickering
                        var lastTabHeight = $('#infotab').height();
			// use offsetWidth versus scrollWidth to avoid splitter's jump on resize when content overflow.
			manageResize(md, "offsetWidth", "pageX", lastTabHeight);
			
		} 
//                else if (v) {
//			// Change cursor to signal a state's change: begin resizing on V.
//			target.style.cursor = 'row-resize';
//			html.style.cursor = 'row-resize'; // avoid cursor's flickering
//
//			manageResize(md, "offsetHeight", "pageY");
//		}
	});
}

setupResizerEvents();
});