document.addEventListener('DOMContentLoaded', function () {
  var __PDF_DOC,
    __PDF_DOC2,
    __CURRENT_PAGE=1,
    __TOTAL_PAGES,
    __PAGE_RENDERING_IN_PROGRESS = 0,
    __CANVAS = document.querySelector('#pdf-canvas'),
    __CANVAS_CTX = __CANVAS.getContext('2d'),
    __CANVAS2 = document.querySelector('#pdf-canvas2'),
    __CANVAS_CTX2 = __CANVAS2.getContext('2d'),
    slider = document.getElementById("myRange"),
    _DISPLAY_MODE = "single";

  var scale_required = 0.85;
  var defaultCanvasSize = [];
  var selectedBookmarks = [];
    

  var isHighlighted = false;
  var isHighlighted2 = false;
  var highlightdata = [];
  var highlightdata2 = [];

  $(".e-list").slideUp(function() {
    $(".e-button").removeClass("open");
  });
  
  $(".e-button").on("click", function() {
    if ($(this).hasClass("open")) {
      $(".e-list").slideUp(function() {
        $(".e-button").removeClass("open");
      });
    } else {
      $(this).addClass("open");
      setTimeout(function() {
        $(".e-list").stop().slideDown();
      }, 200);
    }
  });

  function showPDF(pdf_url) {
    document.querySelector("#pdf-loader").style.display = 'block';

    PDFJS.getDocument({ url: pdf_url }).then(function (pdf_doc) {
      __PDF_DOC = pdf_doc;
      __PDF_DOC2 = pdf_doc;
      __TOTAL_PAGES = __PDF_DOC.numPages;

      document.querySelector("#pdf-loader").style.display = 'none';
      document.querySelector("#pdf-contents").style.display = 'block';
      document.querySelector("#pdf-total-pages").textContent = __TOTAL_PAGES;

      getUserReadingHistory(function(page) {
        if (page !== null) {
          
          _DISPLAY_MODE=page.mode;
          __CURRENT_PAGE=parseInt(page.page);
          if (_DISPLAY_MODE == "single") {
            __CANVAS2.style.display = 'none';
          } else if (_DISPLAY_MODE == "double") {
            
            __CANVAS2.style.display = 'inline-block';
          }
          showPage(__CURRENT_PAGE);
          console.log(page);
          
        } else {
          //console.log('Failed to get the page number.');
          createUserReadingHistory();
        }
      });
     

      /*const storedPagemodeNumPage = getCookie("pagemode_numPage");

      if (storedPagemodeNumPage) {
        const values = storedPagemodeNumPage.split("_");
        if (values.length === 2) {
          //_DISPLAY_MODE = values[0];
          //__CURRENT_PAGE = parseInt(values[1]);
          showPage(parseInt(values[1]));
        }
      }else{
        showPage(1);
      }*/

    }).catch(function (error) {
      document.querySelector("#pdf-loader").style.display = 'none';
      //document.querySelector("#upload-button").style.display = 'block';

      alert(error.message);
    });
  }

  async function showPage(page_no) {
    if (__PAGE_RENDERING_IN_PROGRESS) {
      // If a rendering is in progress, wait for it to complete
      await new Promise(resolve => setTimeout(resolve, 100));
      return showPage(page_no);
    }

    __PAGE_RENDERING_IN_PROGRESS = 1;
    __CURRENT_PAGE = page_no;

    document.querySelector("#pdf-next").disabled = true;
    document.querySelector("#pdf-prev").disabled = true;

    __CANVAS.style.display = 'none';
   
    document.querySelector("#page-loader").style.display = 'block';
    document.querySelector("#pdf-current-page").textContent = page_no;

    __PDF_DOC.getPage(page_no).then(function (page) {

      var viewport = page.getViewport(scale_required);
      if(_DISPLAY_MODE=="single"){
        __CANVAS.height = viewport.height;
        __CANVAS.width = viewport.width*1.3;
      }else if(_DISPLAY_MODE=="double"){
        __CANVAS.height = viewport.height;
        __CANVAS.width = viewport.width*1.2;
      }
      if(scale_required == 0.85){
        if (_DISPLAY_MODE=="single"){
          defaultCanvasSize = { width: viewport.width*1.3, height: viewport.height};
        }else if(_DISPLAY_MODE=="double"){
          defaultCanvasSize = { width: viewport.width*1.2, height: viewport.height};
        }
      }

      var renderContext = {
        canvasContext: __CANVAS_CTX,
        viewport: viewport
      };
      
      page.render(renderContext).then(function () {
        __PAGE_RENDERING_IN_PROGRESS = 0;

        document.querySelector("#pdf-next").disabled = false;
        document.querySelector("#pdf-prev").disabled = false;

        __CANVAS.style.display = 'block';
        document.querySelector("#page-loader").style.display = 'none';

        return page.getTextContent();
      }).then(function (textContent) {
        
        if (scale_required==0.85 && _DISPLAY_MODE=="single"){
          canvas_left = 630.5;
        }else if (scale_required==0.85 && _DISPLAY_MODE=="double"){
          canvas_left = 351;
        }else{
          canvas_left = $("#pdf-canvas").offset().left;
        }

        $("#text-layer").css({ 
          left: canvas_left + 'px', 
          top: $("#pdf-canvas").offset().top + 'px', 
          height: $("#pdf-canvas").height() + 'px', 
          width: $("#pdf-canvas").width() + 'px' 
        });
        
        PDFJS.renderTextLayer({
          textContent: textContent,
          container: document.querySelector("#text-layer"),
          viewport: viewport,
          textDivs: []
        });
      }).then(function() {
        $("#annotation-layer").css({ 
          left: $("#text-layer").offset().left + 'px', 
          top: $("#text-layer").offset().top + 'px', 
          height: $("#text-layer").height() + 'px', 
          width: $("#text-layer").width() + 'px' 
        });
      });
    });

    __PDF_DOC2.getPage(page_no + 1).then(function (page2) {

      var viewport2 = page2.getViewport(scale_required);

      if(_DISPLAY_MODE=="single"){
        __CANVAS2.height = viewport2.height;
        __CANVAS2.width = viewport2.width*0.7;
      }else if(_DISPLAY_MODE=="double"){
        __CANVAS2.height = viewport2.height;
        __CANVAS2.width = viewport2.width*1.2;
      }

      if(scale_required == 0.85){
        if (_DISPLAY_MODE=="single"){
          defaultCanvasSize = { width: viewport2.width*1.3, height: viewport2.height};
        }else if(_DISPLAY_MODE=="double"){
          defaultCanvasSize = { width: viewport2.width*1.2, height: viewport2.height};
        }
      }

      var renderContext2 = {
        canvasContext: __CANVAS_CTX2,
        viewport: viewport2
      };

      page2.render(renderContext2).then(function () {
        __PAGE_RENDERING_IN_PROGRESS = 0;

        document.querySelector("#pdf-next").disabled = false;
        document.querySelector("#pdf-prev").disabled = false;

        document.querySelector("#page-loader").style.display = 'none';

        return page2.getTextContent();
      }).then(function (textContent2) {

        if (scale_required==0.85 && _DISPLAY_MODE=="double"){
          canvas_left = 960;
        }else{
          canvas_left = $("#pdf-canvas2").offset().left;
        }
        
        $("#text-layer2").css({ 
          left: canvas_left + 'px', 
          top: $("#pdf-canvas2").offset().top + 'px', 
          height: $("#pdf-canvas2").height() + 'px', 
          width: $("#pdf-canvas2").width() + 'px' 
        });

        PDFJS.renderTextLayer({
          textContent: textContent2,
          container: document.querySelector("#text-layer2"),
          viewport: viewport2,
          textDivs: []
        });
      }).then(function() {
        $("#annotation-layer2").css({ 
          left: canvas_left + 'px', 
          top: $("#text-layer2").offset().top + 'px', 
          height: $("#text-layer2").height() + 'px', 
          width: $("#text-layer2").width() + 'px' 
        });
      });
      
      updateProgressBar(__CURRENT_PAGE, __TOTAL_PAGES);
      /*sessionStorage.setItem('highlightedElements',JSON.stringify(highlightedElements));
      sessionStorage.setItem('textElements',JSON.stringify(textElements));
      if(rangeMarker[__CURRENT_PAGE-1]){
        sessionStorage.setItem('range',JSON.stringify(rangeMarker));
      }*/
      updateUserReadingHistory(__CURRENT_PAGE,_DISPLAY_MODE);
      renderHighlights();
    });
  }

  $(".text-layer").mouseup(function() {
    var selection = window.getSelection();
    var selected = [];
    var textContents = [];
    if (selection.rangeCount) {
      var range = selection.getRangeAt(0);
      var divs = range.cloneContents().querySelectorAll('div');

      for (var i = 0; i < divs.length; i++) {
        var text = divs[i].textContent;
        textContents.push(text);
      }
    }

    if(!selection.isCollapsed) {
      var rect = selection.getRangeAt(0).getBoundingClientRect();
      if (highlightdata) {
        var canvas_offset = $("#pdf-canvas").offset();
        var currentCanvasSize = { width: __CANVAS.width, height: __CANVAS.height };
        highlightdata.forEach(function(highlight) {
          var scaled_highlight = {
            left: (highlight.pos_left / defaultCanvasSize.width) * currentCanvasSize.width,
            top: (highlight.pos_top / defaultCanvasSize.height) * currentCanvasSize.height,
            width: (highlight.pos_width / defaultCanvasSize.width) * currentCanvasSize.width,
            height: (highlight.pos_height / defaultCanvasSize.height) * currentCanvasSize.height
          };

          if (Math.abs(rect.left - canvas_offset.left - scaled_highlight.left) < 10 && Math.abs(rect.top - canvas_offset.top - scaled_highlight.top) < scaled_highlight.height - 10 && Math.abs(rect.bottom - canvas_offset.top - scaled_highlight.top) > 10) {
            isHighlighted = true;
            console.log('This text is highlighted.');
            selected.push({
              left: highlight.pos_left, 
              top: highlight.pos_top, 
              width: highlight.pos_width, 
              height: highlight.pos_height
            });
            return;
          }
        });
      } 

      if (highlightdata2) {
        var canvas_offset = $("#pdf-canvas2").offset();
        var currentCanvasSize = { width: __CANVAS2.width, height: __CANVAS2.height };
        highlightdata2.forEach(function(highlight) {
          var scaled_highlight = {
            left: (highlight.pos_left / defaultCanvasSize.width) * currentCanvasSize.width,
            top: (highlight.pos_top / defaultCanvasSize.height) * currentCanvasSize.height,
            width: (highlight.pos_width / defaultCanvasSize.width) * currentCanvasSize.width,
            height: (highlight.pos_height / defaultCanvasSize.height) * currentCanvasSize.height
          };

          if (Math.abs(rect.left - canvas_offset.left - scaled_highlight.left) < 10 && Math.abs(rect.top - canvas_offset.top - scaled_highlight.top) < scaled_highlight.height - 10 && Math.abs(rect.bottom - canvas_offset.top - scaled_highlight.top) > 10) {
            isHighlighted2 = true;
            console.log('This text is highlighted.');
            selected.push({
              left: highlight.pos_left, 
              top: highlight.pos_top, 
              width: highlight.pos_width, 
              height: highlight.pos_height
            });
            return;
          }
        });
      }

      var navHighlight = document.getElementById('nav-highlight');
      navHighlight.onclick = function() {
        if (isHighlighted) {
          selected.forEach(function(item) {
            removeHighlightAnnotation(item.left, item.top, item.width, item.height, __CURRENT_PAGE);
          });
          isHighlighted = false;
        } else if (isHighlighted2) {
          selected.forEach(function(item) {
            removeHighlightAnnotation(item.left, item.top, item.width, item.height, __CURRENT_PAGE+1);
          });
          isHighlighted2 = false;
        } else {
          var rectWithScroll = {
            left: window.pageXOffset + rect.left,
            top: window.pageYOffset + rect.top,
            width: rect.width,
            height: rect.height
          };
          createHighlightAnnotation(rectWithScroll, textContents.join(''));
        }
      };

      var navTranslate = document.getElementById('nav-translate');
      navTranslate.onclick = function() {
        window.selectedText = textContents.join('');
        window.open("../elib/translatePopup.php","Popup","width=750,height=650,status=0,directories=0,menubar=0,titlebar=0,location=0");
      };

      var navSearch = document.getElementById('nav-search');
      navSearch.onclick = function() {
        window.selectedText = textContents.join('');
        window.open("../elib/searchPopup.php","Popup","width=550,height=550,status=0,directories=0,menubar=0,titlebar=0,location=0");
      };

      /*var navDict = document.getElementById('nav-dict');
      navDict.onclick = function() {
        window.selectedText = textContents.join('');
        window.open("../elib/dictPopup.php","Popup","width=550,height=550,status=0,directories=0,menubar=0,titlebar=0,location=0");
      };*/

      var popupnav = document.getElementById("popupnav");
      popupnav.style.display = 'block';
      popupnav.style.left = (window.pageXOffset + rect.left) + 'px';
      popupnav.style.top = (window.pageYOffset + rect.top - popupnav.offsetHeight) + 'px';
    };
  });

  /*$("#text-layer2").mouseup(function() {
    var selection = window.getSelection();
    var selected = [];
    var textContents = [];
    if (selection.rangeCount) {
      var range = selection.getRangeAt(0);
      var divs = range.cloneContents().querySelectorAll('div');

      for (var i = 0; i < divs.length; i++) {
        var text = divs[i].textContent;
        textContents.push(text);
      }
    }

    if(!selection.isCollapsed) {
      var rect = selection.getRangeAt(0).getBoundingClientRect();
      if (highlightdata2) {
        var canvas_offset = $("#pdf-canvas2").offset();
        var currentCanvasSize = { width: __CANVAS2.width, height: __CANVAS2.height };
        highlightdata2.forEach(function(highlight) {
          var scaled_highlight = {
            left: (highlight.pos_left / defaultCanvasSize.width) * currentCanvasSize.width,
            top: (highlight.pos_top / defaultCanvasSize.height) * currentCanvasSize.height,
            width: (highlight.pos_width / defaultCanvasSize.width) * currentCanvasSize.width,
            height: (highlight.pos_height / defaultCanvasSize.height) * currentCanvasSize.height
          };

          if (Math.abs(rect.left - canvas_offset.left - scaled_highlight.left) < 10 && Math.abs(rect.top - canvas_offset.top - scaled_highlight.top) < scaled_highlight.height - 10 && Math.abs(rect.bottom - canvas_offset.top - scaled_highlight.top) > 10) {
            isHighlighted = true;
            console.log('This text is highlighted.');
            selected.push({
              left: highlight.pos_left, 
              top: highlight.pos_top, 
              width: highlight.pos_width, 
              height: highlight.pos_height
            });
            return;
          }
        });
      }  

      var navHighlight = document.getElementById('nav-highlight');
      navHighlight.onclick = function() {
        if (isHighlighted) {
          selected.forEach(function(item) {
            removeHighlightAnnotation(item.left, item.top, item.width, item.height, __CURRENT_PAGE+1);
          });
          isHighlighted = false;
        } else {
          //createHighlightAnnotation(rect, textContents.join(''));
          var rectWithScroll = {
            left: window.pageXOffset + rect.left,
            top: window.pageYOffset + rect.top,
            width: rect.width,
            height: rect.height
          };
          createHighlightAnnotation(rectWithScroll, textContents.join(''));
        }
      };

      var navTranslate = document.getElementById('nav-translate');
      navTranslate.onclick = function() {
        window.selectedText = textContents.join('');
        window.open("../elib/translatePopup.php","Popup","width=750,height=650,status=0,directories=0,menubar=0,titlebar=0,location=0");
      };

      var navSearch = document.getElementById('nav-search');
      navSearch.onclick = function() {
        window.selectedText = textContents.join('');
        window.open("../elib/searchPopup.php","Popup","width=550,height=550,status=0,directories=0,menubar=0,titlebar=0,location=0");
      };

      /*var navDict = document.getElementById('nav-dict');
      navDict.onclick = function() {
        window.selectedText = textContents.join('');
        window.open("../elib/dictPopup.php","Popup","width=550,height=550,status=0,directories=0,menubar=0,titlebar=0,location=0");
      };*/

      /*var popupnav = document.getElementById("popupnav");
      popupnav.style.display = 'block';
      popupnav.style.left = (window.pageXOffset + rect.left) + 'px';
      popupnav.style.top = (window.pageYOffset + rect.top - popupnav.offsetHeight) + 'px';
    };
  });*/

  function toggleFullScreen() {
    var canvas = document.getElementById("pdf-canvas");
    var canvas2 = document.getElementById("canvas-wrapper");
    var textLayer = document.getElementById("text-layer");
    var annotationLayer = document.getElementById("annotation-layer");
    var textLayer2 = document.getElementById("text-layer2");
    var annotationLayer2 = document.getElementById("annotation-layer2");
  
    if (_DISPLAY_MODE == "single") {
      var targetCanvas = canvas;
  
      if (!document.fullscreenElement) {
        if (targetCanvas.requestFullscreen) {
          targetCanvas.requestFullscreen();
        } else if (targetCanvas.mozRequestFullScreen) { /* Firefox */
          targetCanvas.mozRequestFullScreen();
        } else if (targetCanvas.webkitRequestFullscreen) { /* Chrome, Safari, and Opera */
          targetCanvas.webkitRequestFullscreen();
        } else if (targetCanvas.msRequestFullscreen) { /* IE/Edge */
          targetCanvas.msRequestFullscreen();
        }
        
        // Show text-layer and annotation-layer
        textLayer.style.display = "block";
        annotationLayer.style.display = "block";
        
      } else {
        if (document.exitFullscreen) {
          document.exitFullscreen();
        } else if (document.mozCancelFullScreen) { /* Firefox */
          document.mozCancelFullScreen();
        } else if (document.webkitExitFullscreen) { /* Chrome, Safari, and Opera */
          document.webkitExitFullscreen();
        } else if (document.msExitFullscreen) { /* IE/Edge */
          document.msExitFullscreen();
        }
        
        // Hide text-layer and annotation-layer
        textLayer.style.display = "none";
        annotationLayer.style.display = "none";
        
      }
    }
  
    if (_DISPLAY_MODE == "double") {
      var targetCanvas = canvas2;
  
      if (!canvas.fullscreenElement && !canvas2.fullscreenElement) {
        if (targetCanvas.requestFullscreen) {
          targetCanvas.requestFullscreen();
        } else if (targetCanvas.mozRequestFullScreen) { /* Firefox */
          targetCanvas.mozRequestFullScreen();
        } else if (targetCanvas.webkitRequestFullscreen) { /* Chrome, Safari, and Opera */
          targetCanvas.webkitRequestFullscreen();
        } else if (targetCanvas.msRequestFullscreen) { /* IE/Edge */
          targetCanvas.msRequestFullscreen();
        }
        
        // Hide text-layer and annotation-layer
        textLayer.style.display = "none";
        annotationLayer.style.display = "none";
        textLayer2.style.display = "none";
        annotationLayer2.style.display = "none";
        
      } else {
        if (canvas.exitFullscreen) {
          canvas.exitFullscreen();
        } else if (canvas.mozCancelFullScreen) { /* Firefox */
          canvas.mozCancelFullScreen();
        } else if (canvas.webkitExitFullscreen) { /* Chrome, Safari, and Opera */
          canvas.webkitExitFullscreen();
        } else if (canvas.msExitFullscreen) { /* IE/Edge */
          canvas.msExitFullscreen();
        }
  
        if (canvas2.exitFullscreen) {
          canvas2.exitFullscreen();
        } else if (canvas2.mozCancelFullScreen) { /* Firefox */
          canvas2.mozCancelFullScreen();
        } else if (canvas2.webkitExitFullscreen) { /* Chrome, Safari, and Opera */
          canvas2.webkitExitFullscreen();
        } else if (canvas2.msExitFullscreen) { /* IE/Edge */
          canvas2.msExitFullscreen();
        }
        
        // Show text-layer and annotation-layer
        textLayer.style.display = "block";
        annotationLayer.style.display = "block";
        textLayer2.style.display = "block";
        annotationLayer2.style.display = "block";
      }
    }
  }

  document.addEventListener("keydown", function(event) {
    if (event.keyCode === 37 || event.key === "ArrowLeft") {
      if (__CURRENT_PAGE != 1)
      if (_DISPLAY_MODE == "single") {
        showPage(--__CURRENT_PAGE);
      } else if (_DISPLAY_MODE == "double") {
        __CURRENT_PAGE -= 2;
        showPage(__CURRENT_PAGE);
      }
     
    } else if (event.keyCode === 39 || event.key === "ArrowRight") {
      if (__CURRENT_PAGE != __TOTAL_PAGES)
      if (_DISPLAY_MODE == "single") {
        showPage(++__CURRENT_PAGE);
      } else if (_DISPLAY_MODE == "double") {
        __CURRENT_PAGE += 2;
        showPage(__CURRENT_PAGE);
      }
    }
  });

  document.querySelector('#full-screen').addEventListener('click',function(){
    toggleFullScreen();
  });

  // Add event listener for zoom event
  window.addEventListener('resize', function() {
    // Get canvas offset
    var canvas_offset = $("#pdf-canvas").offset();

    // Update position and size of text layer
    $("#text-layer").css({
        left: canvas_offset.left + 'px',
        top: canvas_offset.top + 'px',
        height: __CANVAS.height + 'px',
        width: __CANVAS.width + 'px'
    });

    // Update position and size of text layer
    $("#annotation-layer").css({ 
      left: $("#text-layer").offset().left + 'px', 
      top: $("#text-layer").offset().top + 'px', 
      height: $("#text-layer").height() + 'px', 
      width: $("#text-layer").width() + 'px' 
    });

    if (_DISPLAY_MODE=="double"){
      // Get canvas offset
      var canvas_offset = $("#pdf-canvas2").offset();

      // Update position and size of text layer
      $("#text-layer2").css({
          left: canvas_offset.left + 'px',
          top: canvas_offset.top + 'px',
          height: __CANVAS2.height + 'px',
          width: __CANVAS2.width + 'px'
      });

      // Update position and size of text layer
      $("#annotation-layer2").css({ 
        left: $("#text-layer2").offset().left + 'px', 
        top: $("#text-layer2").offset().top + 'px', 
        height: $("#text-layer2").height() + 'px', 
        width: $("#text-layer2").width() + 'px' 
      });
    }
  });

  $(document).mouseup(function() {
    setTimeout(function() {
      var selection = window.getSelection();
      if(selection.isCollapsed) {
        var popupnav = document.getElementById("popupnav");
        popupnav.style.display = 'none';
      }
    }, 10);
  });

  function renderHighlights() {
    $("#annotation-layer").html('');
    loadHighlights(__CANVAS, __CURRENT_PAGE, "#annotation-layer").then(highlightData => {
        highlightdata = highlightData;
    });

    if (_DISPLAY_MODE == "double") {
        $("#annotation-layer2").html('');
        loadHighlights(__CANVAS2, __CURRENT_PAGE+1, "#annotation-layer2").then(highlightData2 => {
            highlightdata2 = highlightData2;
        });
    }
  }


  function loadHighlights(canvas, page, layer) {
    var data = [document.getElementById('docid').value, document.getElementById('version').value];
    data.push(page); //page no [2]
    var highlightData = [];

    // Get the current canvas size
    var currentCanvasSize = { width: canvas.width, height: canvas.height };

    // Return a new Promise
    return new Promise((resolve, reject) => {
        // Load the highlights from the server
        $.ajax({
            type: 'POST',
            url: '../elib/elib_ajax.php', 
            data: {'action': 'gethighlights', 'data': data},
            success: function(highlights) {
                highlightData = JSON.parse(highlights);
                if (highlightData) {
                    highlightData.forEach(function(highlight) {
                        // Calculate the position and size of the highlight annotation relative to the current canvas size
                        var relativeRect = {
                            left: (highlight.pos_left / defaultCanvasSize.width) * currentCanvasSize.width,
                            top: (highlight.pos_top / defaultCanvasSize.height) * currentCanvasSize.height,
                            width: (highlight.pos_width / defaultCanvasSize.width) * currentCanvasSize.width,
                            height: (highlight.pos_height / defaultCanvasSize.height) * currentCanvasSize.height
                        };

                        var div = document.createElement('div');
                        div.style.position = 'absolute';
                        div.style.left = relativeRect.left + 'px';
                        div.style.top = relativeRect.top + 'px';
                        div.style.width = relativeRect.width + 'px';
                        div.style.height = relativeRect.height + 'px';
                        div.style.backgroundColor = 'yellow';
                        div.style.opacity = 0.5;
                        $(layer).append(div);
                    });
                    // Resolve the Promise with highlightData
                    resolve(highlightData);
                }
            },
            error: function(error) {
                // Reject the Promise if there's an error
                reject(error);
            }
        });
    });
  }



  function createHighlightAnnotation(rect, textContents) {
    if (_DISPLAY_MODE=="single"){
      // Get the current canvas size
      var currentCanvasSize = { width: __CANVAS.width, height: __CANVAS.height };
      var canvas_offset = $("#pdf-canvas").offset();

      // Calculate the position and size of the highlight annotation relative to the default canvas size
      var relativeRect = {
          left: ((rect.left - canvas_offset.left) / currentCanvasSize.width) * defaultCanvasSize.width,
          top: ((rect.top - canvas_offset.top) / currentCanvasSize.height) * defaultCanvasSize.height,
          width: (rect.width / currentCanvasSize.width) * defaultCanvasSize.width,
          height: (rect.height / currentCanvasSize.height) * defaultCanvasSize.height
      };

      // Create a new div for the highlight annotation
      var highlight = document.createElement('div');
      highlight.style.position = 'absolute';
      highlight.style.left = relativeRect.left + 'px';
      highlight.style.top = relativeRect.top + 'px';
      highlight.style.width = relativeRect.width + 'px';
      highlight.style.height = relativeRect.height + 'px';
      highlight.style.backgroundColor = 'yellow';
      highlight.style.opacity = '0.5';

      // Add the highlight annotation to the annotation layer
      $("#annotation-layer").append(highlight);

      var data=[document.getElementById('docid').value, document.getElementById('version').value];
      data.push(__CURRENT_PAGE); //page no [2]
      data.push(parseInt(highlight.style.left)); //pos_left [3]
      data.push(parseInt(highlight.style.top)); //pos_top [4]
      data.push(parseInt(highlight.style.width)); //pos_width [5]
      data.push(parseInt(highlight.style.height)); //pos_height [6]
      data.push(textContents); //text [7]

      $.ajax({
        url: '../elib/elib_ajax.php',
        type: 'POST',
        data: {'action': 'addhighlights','data': data},
        success: function(response) {
            console.log('Highlight saved.');
            renderHighlights();
        }
      });
    }else if (_DISPLAY_MODE=="double"){
      // Determine which annotation layer to add the highlight to
      var canvas2_offset_left = $("#pdf-canvas2").offset().left;
      var annotationLayerId = rect.left < canvas2_offset_left ? "annotation-layer" : "annotation-layer2";
      var currentCanvas = annotationLayerId === "annotation-layer" ? __CANVAS : __CANVAS2;
      var canvas_offset = $("#" + annotationLayerId).offset();
  
      var currentCanvasSize = { width: currentCanvas.width, height: currentCanvas.height };
      var relativeRect = {
          left: ((rect.left - canvas_offset.left) / currentCanvasSize.width) * defaultCanvasSize.width,
          top: ((rect.top - canvas_offset.top) / currentCanvasSize.height) * defaultCanvasSize.height,
          width: (rect.width / currentCanvasSize.width) * defaultCanvasSize.width,
          height: (rect.height / currentCanvasSize.height) * defaultCanvasSize.height
      };
  
      var highlight = document.createElement('div');
      highlight.style.position = 'absolute';
      highlight.style.left = relativeRect.left + 'px';
      highlight.style.top = relativeRect.top + 'px';
      highlight.style.width = relativeRect.width + 'px';
      highlight.style.height = relativeRect.height + 'px';
      highlight.style.backgroundColor = 'yellow';
      highlight.style.opacity = '0.5';
  
      $("#" + annotationLayerId).append(highlight);
  
      var data=[document.getElementById('docid').value, document.getElementById('version').value];
      // Determine the page number based on the annotation layer
      var pageNumber = annotationLayerId === "annotation-layer" ? __CURRENT_PAGE : __CURRENT_PAGE + 1;
      data.push(pageNumber); //page no [2]
      data.push(parseInt(highlight.style.left)); //pos_left [3]
      data.push(parseInt(highlight.style.top)); //pos_top [4]
      data.push(parseInt(highlight.style.width)); //pos_width [5]
      data.push(parseInt(highlight.style.height)); //pos_height [6]
      data.push(textContents); //text [7]
  
      $.ajax({
        url: '../elib/elib_ajax.php',
        type: 'POST',
        data: {'action': 'addhighlights','data': data},
        success: function(response) {
            console.log('Highlight saved.');
            renderHighlights();
        }
      });
    }
  }

  function removeHighlightAnnotation(left, top, width, height, page) { 
    var data=[document.getElementById('docid').value, document.getElementById('version').value];
    data.push(left); //pos_left [2]
    data.push(top); //pos_top [3]
    data.push(width); //pos_width [4]
    data.push(height); //pos_height [5]
    data.push(page); //page no [6]

    $.ajax({
      url: '../elib/elib_ajax.php',
      type: 'POST',
      data: {'action': 'removehighlights','data': data},
      success: function(response) {
        console.log("Highlight removed");
        renderHighlights();
      }
    });
  }

  /*function handleColorOptionClick(event) {
    //sessionStorage.setItem('highlightColor', event.target.style.backgroundColor);
    document.getElementById('color-picker').style.display = 'none';
  }*/

  var colorOptions = document.getElementsByClassName('color-option');
  for (var i = 0; i < colorOptions.length; i++) {
    colorOptions[i].addEventListener('click', handleColorOptionClick);
  }

  // Deserialize the range string to a range object
  function deserializeRange(rangeString) {
    var serializedRange = JSON.parse(rangeString);
    var startNode = getNodeFromXPath(serializedRange.startNodeXPath);
    var endNode = getNodeFromXPath(serializedRange.endNodeXPath);
    var range = document.createRange();
    range.setStart(startNode, serializedRange.startOffset);
    range.setEnd(endNode, serializedRange.endOffset);
    return range;
  }

  // Get the XPath of a given node
  function getXPathForNode(node) {
    var xpath = "";
    for (; node && node.nodeType == 1; node = node.parentNode) {
      var id = $(node.parentNode)
        .children(node.tagName)
        .index(node) + 1;
      id > 1 ? (id = "[" + id + "]") : (id = "");
      xpath = "/" + node.tagName.toLowerCase() + id + xpath;
    }
    return xpath;
  }

  // Get the node from a given XPath
  function getNodeFromXPath(xpath) {
    return document.evaluate(
      xpath,
      document,
      null,
      XPathResult.FIRST_ORDERED_NODE_TYPE,
      null
    ).singleNodeValue;
  }

  const updateUserReadingHistory = (current_page,current_mode) => {
    var xhr = new XMLHttpRequest();
  
    xhr.onreadystatechange = function() {
      if (xhr.readyState === XMLHttpRequest.DONE) {
      
      }
    };
  
    xhr.open('POST', '../elib/elib_ajax.php');
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
  
    var data = 'action=recordPage&documentid=' + document.getElementById('docid').value + '&page=' + current_page + '&mode=' + current_mode;
    xhr.send(data);
  };

  const getUserReadingHistory = (callback) => {
    var xhr = new XMLHttpRequest();
  
    xhr.onreadystatechange = function() {
      if (xhr.readyState === XMLHttpRequest.DONE) {
        if (xhr.status === 200) {
          var response = JSON.parse(xhr.responseText);
          var pageMode = {
            page: response.page,
            mode: response.mode
          };
          callback(pageMode);
        } else {
          callback(null); 
        }
      }
    };
  
    xhr.open('POST', '../elib/elib_ajax.php');
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
  
    var data = 'action=getRecordPage&documentid=' + document.getElementById('docid').value;
    xhr.send(data);
  };
  
  const createUserReadingHistory = () => {
    var xhr = new XMLHttpRequest();
  
    xhr.onreadystatechange = function() {
      if (xhr.readyState === XMLHttpRequest.DONE) {
      
      }
    };
  
    xhr.open('POST', '../elib/elib_ajax.php');
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
  
    var data = 'action=createRecordPage&documentid=' + document.getElementById('docid').value;
    xhr.send(data);
  }

  const setCookie = (cname, cvalue, exhours) => {
    const d = new Date();
    d.setTime(d.getTime() + (exhours * 60 * 60 * 1000));
    const expires = "expires=" + d.toUTCString();
    document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
  };

  const getCookie = (cname) => {
    const name = cname + "=";
    const decodedCookie = decodeURIComponent(document.cookie);
    const ca = decodedCookie.split(';');
    for (let i = 0; i < ca.length; i++) {
      let c = ca[i];
      while (c.charAt(0) === ' ') {
        c = c.substring(1);
      }
      if (c.indexOf(name) === 0) {
        return c.substring(name.length, c.length);
      }
    }
    return "";
  };
  
  const cleanCookie = (cname, cvalue) => {
    document.cookie = cname +"="+cvalue+";expires=Thu, 01 Jan 1970 00:00:01 GMT; path=/";
  }

  function zoomIn() {
    scale_required += 0.1;
    showPage(__CURRENT_PAGE);
  }

  function zoomOut() {
    scale_required -= 0.1;
    showPage(__CURRENT_PAGE);
  }

  const ModeChange = () => {
    scale_required = 0.85;
    if (_DISPLAY_MODE == "single") {
      __CANVAS2.style.display = 'inline-block';
      _DISPLAY_MODE = "double";
      $('#text-layer2').show();
      $('#annotation-layer2').show();
    } else if (_DISPLAY_MODE == "double") {
      __CANVAS2.style.display = 'none';
      _DISPLAY_MODE = "single";
      $('#text-layer2').hide();
      $('#annotation-layer2').hide();
    }
    showPage(__CURRENT_PAGE);
  }

  function updateProgressBar() {
    const progress = (__CURRENT_PAGE-1 / __TOTAL_PAGES) * 100;
    slider.value = __CURRENT_PAGE;
    slider.max = __TOTAL_PAGES;
    slider.style.background = `linear-gradient(to right, yellow ${progress}%, #d3d3d3 ${progress}%)`;
  }

  function addbookmark() {
    var note = prompt("请输入你的笔记: ", " ");
  
    if (note == null || note == "") {
      alert('已取消添加书签');
      return;
    } else {
      var xhr = new XMLHttpRequest();
  
      xhr.onreadystatechange = function() {
        if (xhr.readyState === XMLHttpRequest.DONE) {
          if (xhr.status === 200) {
            var response = JSON.parse(xhr.responseText);
            if (response === "Bookmark added successfully") {
              alert('书签已添加');
              showbookmark();
            } else if (response === "Bookmark already added") {
              alert('书签已存在');
            } else {
              alert('添加书签时出现问题');
            }
          }
        }
      };
  
      xhr.open('POST', '../elib/elib_ajax.php');
      xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
  
      var documentid = document.getElementById('docid').value;
      var page = __CURRENT_PAGE;
  
      var data = 'action=addbookmarknew&documentid=' + documentid +
        '&page=' + page +
        '&note=' + encodeURIComponent(note);
  
      xhr.send(data);
    }
  }

  function showhighlightinfo(pageno) {
    return new Promise(function(resolve, reject) {
      var documentId = document.getElementById('docid').value;
      var xhr = new XMLHttpRequest();
      xhr.open('POST', '../elib/elib_ajax.php');
      xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
      xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
          if (xhr.status === 200) {
            var response = JSON.parse(xhr.responseText);
            var highlightedTexts = [];
  
            for (var i = 0; i < response.length; i++) {
              highlightedTexts.push(response[i].text);
            }
            resolve(highlightedTexts);
          } else {
            reject('An error occurred while retrieving highlighted text');
          }
        }
      };
      var data = 'action=showhighlightinfo&documentid=' + documentId + '&page=' + pageno;
      xhr.send(data);
    });
  }
  
  function showbookmark() {
    var documentId = document.getElementById('docid').value;
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '../elib/elib_ajax.php');
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function () {
      if (xhr.readyState === 4 && xhr.status === 200) {
        var bookmarkContainer = document.getElementById('bookmark-container');
        var bookmarks = JSON.parse(xhr.response);
        var bookmarkHTML = '';
        var promiseArray = [];
  
        if (bookmarks !== null) {
          bookmarkHTML += '<li><button id="delete-all">Delete All</button></li>';
          bookmarkHTML += '<li><button id="delete-selected">Delete Selected</button></li>';
        }
  
        bookmarks.forEach(function (bookmark, index) {
          var promise = showhighlightinfo(parseInt(bookmark.pageno))
            .then(function (highlightedTexts) {
              var text = '';
  
              if (highlightedTexts.length > 0) {
                for (i = 0; i < highlightedTexts.length; i++) {
                  text += highlightedTexts[i];
                }
              } else {
                text = " ";
              }
  
              var bookmarkId = 'bookmark-' + index;
              bookmarkHTML += '<li id="' + bookmarkId + '">';
              bookmarkHTML += '<input type="checkbox" class="bookmark-checkbox" id="' + bookmark.pageno + '" style="display: none; z-index: 9999;">';
              bookmarkHTML += '<a href="#" id="' + bookmark.pageno + '">Page ' + bookmark.pageno + '<button class="delete-button" id="' + bookmarkId + '"><i class="fa-solid fa-trash"></i></button></a>';
              bookmarkHTML += '<div class="bookmark-preview">';
              bookmarkHTML += '<div class="page-number">Page: ' + bookmark.pageno + '</div>';
              bookmarkHTML += '<div class="marked-date">Marked: ' + bookmark.date + '</div>';
              bookmarkHTML += '<div class="highlighted-content">Highlighted Text: ' + text + '</div>';
              bookmarkHTML += '<div class="small-note">Note: '+ bookmark.note + '</div>';
              bookmarkHTML += '</div>';
              bookmarkHTML += '</li>';
            })
            .catch(function (error) {
              console.log(error);
            });
  
          promiseArray.push(promise);
        });
  
        Promise.all(promiseArray).then(function () {
          bookmarkContainer.innerHTML = bookmarkHTML;
          bookmarkContainer.style.display = 'block';
  
          var deleteAllButton = document.getElementById('delete-all');
          if (deleteAllButton) {
            deleteAllButton.addEventListener('click', function () {
              deleteAllBookmarks();
            });
          }
  
          var selectDeleteButton = document.getElementById('delete-selected');
          if (selectDeleteButton) {
            selectDeleteButton.addEventListener('click', function () {
              var checkboxes = document.querySelectorAll('.bookmark-checkbox');
              
              checkboxes.forEach(function (checkbox) {
                checkbox.style.display = checkbox.style.display === 'none' ? 'inline-block' : 'none';
                if (checkbox) {
                  checkbox.addEventListener('click', function () {
                    if (checkbox.checked) {
                      selectedBookmarks.push(checkbox.id); // Add the bookmark ID to the selectedBookmarks array
                    } else {
                      var index = selectedBookmarks.indexOf(checkbox.id);
                      if (index !== -1) {
                        selectedBookmarks.splice(index, 1); // Remove the bookmark ID from the selectedBookmarks array
                      }
                    }
            
                    //console.log(selectedBookmarks); // Display selected bookmark IDs in the console log
                  });
                }
              });
            
              if (selectedBookmarks.length > 0) {
                // Send AJAX request
                var xhrDelete = new XMLHttpRequest();
                xhrDelete.open('POST', '../elib/elib_ajax.php');
                xhrDelete.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhrDelete.onreadystatechange = function () {
                  if (xhrDelete.readyState === 4 && xhrDelete.status === 200) {
                    // Handle the response after deleting the selected bookmarks
                    //console.log(xhrDelete.responseText); // Log the response from the server
                    // Perform any additional actions or updates as needed
                    showbookmark();
                    selectedBookmarks = []; // Reset the selectedBookmarks array
                  }
                };
                
                var deleteData = 'action=selectdeletebookmarks&documentid=' + documentId +'&selectedbookmarks=' + JSON.stringify(selectedBookmarks);
                xhrDelete.send(deleteData);
              }
            });
          }

  
          bookmarks.forEach(function (bookmark, index) {
            var bookmarkId = 'bookmark-' + index;
            var deleteButton = document.getElementById(bookmarkId)?.querySelector('.delete-button');
            var directbookmark = document.getElementById(bookmarkId)?.querySelector('a');
  
            if (deleteButton) {
              deleteButton.addEventListener('click', function (event) {
                event.stopPropagation(); // Prevent event bubbling to the anchor element
                deleteBookmark(bookmark.pageno);
              });
            }
  
            if (directbookmark) {
              directbookmark.addEventListener('click', function (event) {
                event.preventDefault();
                var id = event.target.id;
                showPage(parseInt(id));
              });
            }
          });
        });
      }
    };
    var data = 'action=showbookmark&documentid=' + documentId + '&page=' + __CURRENT_PAGE;
    xhr.send(data);
  }
  
    function deleteBookmark(pageno) {
      var xhr = new XMLHttpRequest();
    
      xhr.onreadystatechange = function() {
        if (xhr.readyState === XMLHttpRequest.DONE) {
          if (xhr.status === 200) {
            alert('Bookmark has been deleted');
            // 刷新書籤列表
            showbookmark();
          } else {
            alert('An error occurred while deleting the bookmark');
          }
        }
      };
      xhr.open('POST', '../elib/elib_ajax.php');
      xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
      var data = 'action=delbookmark&documentid=' + document.getElementById('docid').value + '&page=' + pageno;
      xhr.send(data);
    }

  function deleteAllBookmarks() {
  
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '../elib/elib_ajax.php');
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
      if (xhr.readyState === 4) {
        if (xhr.status === 200) {
          alert('All bookmarks have been deleted');
          // Refresh the bookmark list
          showbookmark();
        } else {
          alert('Error deleting bookmarks');
        }
      }
    };
    var documentId = document.getElementById('docid').value;
    var data = 'action=deleteallbookmarks&documentid=' + documentId;
    xhr.send(data);
  }

  slider.oninput = async function() {

    var progress = ((this.value - this.min) / (this.max - this.min)) * 100;
    this.style.background = `linear-gradient(to right, yellow ${progress}%, #d3d3d3 ${progress}%)`;
  
    await new Promise(resolve => setTimeout(resolve, 500));
    __CURRENT_PAGE=parseInt(slider.value);
    showPage(__CURRENT_PAGE);
    
  };

  document.querySelector('#double-page').addEventListener('click', function () {
    ModeChange();
  });

  document.querySelector("#pdf-first").addEventListener('click', function () {
    //const storedPagemodeNumPage = getCookie("pagemode_numPage");
    //cleanCookie("pagemode_numPage", storedPagemodeNumPage);
    //location.href=location.href;
    showPage(1);
  });

  document.querySelector("#pdf-prev").addEventListener('click', function () {
    if (__CURRENT_PAGE != 1)
      if (_DISPLAY_MODE == "single") {
        showPage(--__CURRENT_PAGE);
      } else if (_DISPLAY_MODE == "double") {
        __CURRENT_PAGE -= 2;
        showPage(__CURRENT_PAGE);
      }

  });

  document.querySelector("#pdf-next").addEventListener('click', function () {
    if (__CURRENT_PAGE != __TOTAL_PAGES)
      if (_DISPLAY_MODE == "single") {
        showPage(++__CURRENT_PAGE);
      } else if (_DISPLAY_MODE == "double") {
        __CURRENT_PAGE += 2;
        showPage(__CURRENT_PAGE);
      }
  });

  document.querySelector("#pdf-last").addEventListener('click', function () {
    if (__CURRENT_PAGE != __TOTAL_PAGES)
      if (_DISPLAY_MODE == "single") {
        __CURRENT_PAGE=__TOTAL_PAGES;
        showPage(__CURRENT_PAGE);
      } else if (_DISPLAY_MODE == "double") {
        __CURRENT_PAGE=__TOTAL_PAGES;
        showPage(__CURRENT_PAGE-1);
      }
  });

  document.querySelector("#add-bookmark").addEventListener('click',function(){
    addbookmark();
    showbookmark();
  });

  document.querySelector("#show-bookmark").addEventListener('click',function(){
    showbookmark();
  });
 
  document.querySelector("#zoom-in").addEventListener('click', function () {
    zoomIn();
    showPage(__CURRENT_PAGE);
  });

  document.querySelector("#zoom-out").addEventListener('click', function () {
    zoomOut();
    showPage(__CURRENT_PAGE);
  });

 

  var documentid = document.getElementById('docid').value;
  var xhr = new XMLHttpRequest();
  xhr.open('POST', '../elib/elib_ajax.php');
  xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
  xhr.onload = function () {
    if (xhr.status === 200) {
      var response = xhr.responseText;
      if (response) {
        var pdfPath = response;
        showPDF(pdfPath);
      }
    }
  };
  xhr.send('action=renderpdf&documentid=' + documentid);
});
