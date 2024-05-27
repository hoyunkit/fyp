var geminiContent = '';
var deepseekContent = '';
var chatgptContent = '';

$(document).ready(function() {
  var query = window.opener.selectedText;
  console.log('query: ' + query);

  $('#zoomIn').live("click",function(){
    var newZoom = parseFloat($('.tabcontent').css('zoom'))+0.1;
    $('.tabcontent').css('zoom', newZoom);
    document.cookie = "transZoom="+newZoom;
  });
  $('#zoomOut').live("click",function(){
      var newZoom = parseFloat($('.tabcontent').css('zoom'))-0.1;
      $('.tabcontent').css('zoom', newZoom);
      document.cookie = "transZoom="+newZoom;
  });
  $('#zoomDefault').live("click",function(){
      $('.tabcontent').css('zoom',1);
      document.cookie = "transZoom=1";
  });

  callAPI('deepseek', query, 'footnoteContainer3');
  callAPI('chatgpt', query, 'footnoteContainer1');
  callAPI('gemini', query, 'footnoteContainer2');

  $('#li1').addClass('active');
  $('#footnoteContainer2').hide();
  $('#footnoteContainer3').hide();

  $('#gemini').click(function() {
    $('#li1').addClass('active');
    $('#li2').removeClass('active');
    $('#li3').removeClass('active');
    $('#footnoteContainer1').show();
    $('#footnoteContainer2').hide();
    $('#footnoteContainer3').hide();
  });

  $('#chatgpt').click(function() {
    $('#li2').addClass('active');
    $('#li1').removeClass('active');
    $('#li3').removeClass('active');
    $('#footnoteContainer2').show();
    $('#footnoteContainer1').hide();
    $('#footnoteContainer3').hide();
  });

  $('#deepseek').click(function() {
    $('#li3').addClass('active');
    $('#li1').removeClass('active');
    $('#li2').removeClass('active');
    $('#footnoteContainer3').show();
    $('#footnoteContainer1').hide();
    $('#footnoteContainer2').hide();
  });
});

function callAPI(action, query, containerId) {
  $('#'+containerId).append('<div class="loader" id="'+action+'-loader"><div class="spinner" id="'+action+'-spinner"></div><p>Loading...Please wait, this may take around 20 seconds</p></div>');
  $.ajax({
    type: 'POST',
    url:'../elib/CallAPI.php',
    data: {'action': action, 'query': query},
    success:function(response){
      console.log(response);
      let data = response;
      if (action.includes('deepseek')){
        data = JSON.parse(response);
      }
      handleResponse(action, data, query, containerId);
    }
  });
}

function handleResponse(action, data, query, containerId) {
  if (action == 'gemini' || action == 'deepseek' || action == 'chatgpt'){
    displayParsedText('<p><b>翻譯句子: ' + query + '</b></p>', containerId);
  }
  let text = '';
  if (action.includes('deepseek')) {
    text = data.choices[0].message.content;
    deepseekContent += text;
  }else if (action.includes('chatgpt') || action.includes('gemini')) {
    text = data;
    chatgptContent += text;
  }
  displayParsedText(text, containerId);
  createFollowUpForm(action, containerId);
  $('#'+action+'-spinner').remove();
  $('#'+action+'-loader').remove();

  if(action == 'deepseek'){
    var navQuiz = $('<span class="icon-pencil" title="quiz" id="nav-quiz" style="font-size: 16px; color: red;"></span>');
    $('#footnoteContainer3').append(navQuiz);
  
    navQuiz.on('click', quizpopup);
  
    function quizpopup() {
      var query = window.opener.selectedText;
      var translate = text;
      var encodedQuery = encodeURIComponent(query);
      var encodedTranslate = encodeURIComponent(translate);       
      var url = "../elib/quizPopup.php?query=" + encodedQuery + "&translate=" + encodedTranslate ;
      window.open(url, "Popup", "width=550,height=550,status=0,directories=0,menubar=0,titlebar=0,location=0");
    }
  }
}


function createFollowUpForm(action, containerId) {
  var form = document.createElement('form');
  form.id = 'follow-up-form';
  var input = document.createElement('input');
  input.id = 'follow-up-input';
  input.type = 'text';
  input.placeholder = 'Enter your follow-up question here...';
  var submit = document.createElement('input');
  input.style.width = '70%';
  input.style.height = '30px';
  submit.type = 'submit';
  submit.value = 'Submit';
  submit.style.height = '30px';
  submit.style.marginBottom = '10px';

  form.appendChild(input);
  form.appendChild(submit);

  var container = document.getElementById(containerId);
  container.appendChild(form);
  form.addEventListener('submit', function(e) {
    e.preventDefault();
    var followUpQuery = [input.value];
    var showResults = document.getElementById(containerId);
    let paragraph = document.createElement('p');
    paragraph.style.color = 'blue';
    paragraph.style.fontWeight = 'bold';
    showResults.appendChild(paragraph);
    paragraph.innerHTML = 'Follow-up question: ' + followUpQuery;
    if (action === 'gemini' || action === 'follow_up_gemini'){ 
      followUpQuery.push(geminiContent);
      callAPI('follow_up_gemini', followUpQuery, containerId);
    } else if (action === 'deepseek' || action === 'follow_up_deepseek'){
      validContent = removeInvalidChars(deepseekContent);
      followUpQuery.push(validContent);
      console.log(followUpQuery);
      callAPI('follow_up_deepseek', followUpQuery, containerId);
    } else if (action === 'chatgpt' || action === 'follow_up_chatgpt'){
      validContent = removeInvalidChars(chatgptContent);
      followUpQuery.push(validContent);
      console.log(followUpQuery);
      callAPI('follow_up_chatgpt', followUpQuery, containerId);
    }
    form.remove();
  });
  
}

function removeInvalidChars(str) {
  const controlChars = /[\x00-\x1F\x7F]/g;
  const validJsonString = str.replace(controlChars, '');
  return validJsonString;
}


function displayParsedText(text, containerId) {
  var showResults = document.getElementById(containerId);
  let sections = text.split('\n\n');

  sections.forEach(section => {
      let paragraph = document.createElement('p');
      let lines = section.split('\n');
      lines = lines.map(line => {
        if ((line.startsWith('**') && line.endsWith('**')) || (line.startsWith('**') && line.endsWith('**:'))) {
          return '<b>' + line.slice(2, -2) + '</b>';
        }else if (line.startsWith('* **') && line.includes('**')) {
            let parts = line.split('**');
            return '* <b>' + parts[1] + '</b>' + parts[2];
        } else if (line.startsWith('* ')) {
            return line.slice(2);
        } else {
            return line;
        }
      });
      paragraph.innerHTML = lines.join('<br>');
      showResults.appendChild(paragraph);
  });
}








