let urlParams = new URLSearchParams(window.location.search);
let query = urlParams.get('query');
let translate = urlParams.get('translate');
console.log(query);
console.log(translate);
let questions = [];

 questions = [
  {
    question: "法国的首都是哪里？",
    answer: {
      "1": {
        text: "巴黎",
        correct: false,
      },
      "2": {
        text: "伦敦",
        correct: true,
      },
      "3": {
        text: "柏林",
        correct: false,
      },
      "4": {
        text: "罗马",
        correct: false,
      },
    },
  },
  {
    question: "谁画了《蒙娜丽莎》？",
    answer: {
      "1": {
        text: "列奥纳多·达·芬奇",
        correct: true,
      },
      "2": {
        text: "巴勃罗·毕加索",
        correct: false,
      },
      "3": {
        text: "文森特·梵高",
        correct: false,
      },
      "4": {
        text: "米开朗基罗",
        correct: false,
      },
    },
  },
  {
    question: "太阳系中最大的行星是哪颗？",
    answer: {
      "1": {
        text: "木星",
        correct: true,
      },
      "2": {
        text: "火星",
        correct: false,
      },
      "3": {
        text: "土星",
        correct: false,
      },
      "4": {
        text: "地球",
        correct: false,
      },
    },
  },
  {
    question: "哪个国家被称为“日本，东方之国”？",
    answer: {
      "1": {
        text: "日本",
        correct: false,
      },
      "2": {
        text: "中国",
        correct: false,
      },
      "3": {
        text: "韩国",
        correct: false,
      },
      "4": {
        text: "泰国",
        correct: false,
      },
    },
  },
];

function makeQuizRequest() {
  showLoading();

  $.ajax({
    type: 'POST',
    url: '../elib/CallAPI.php',
    data: { 'action': 'quiz', 'questions': JSON.stringify(questions), 'query': query, 'meaning': translate },
    success: function(response) {
      if (response.trim() === '') {
        // 重新请求
        console.log("重新请求");
        makeQuizRequest();
      } else {
        // 开始测验
        console.log(response);
        questions = JSON.parse(response);
        console.log(questions);
        startQuiz();
      }
    },
    complete: function() {
      $(".app").show();
      hideLoading();
    }
  });
}

function showLoading() {

  var loadingElement = $('<div class="loading" style="display: flex; flex-direction: column; justify-content: center; align-items: center; height: 100vh;">' +
    '<div class="loading-spinner" style="border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite;"></div>' +
    '<div class="loading-text" style="margin-top: 10px; font-size: 16px; color: white;">加載中...</div>' +
    '</div>');
  $('body').append(loadingElement);
}
function hideLoading() {

  $('.loading').remove();
}

makeQuizRequest();

const questionElement = document.getElementById("question");
const answerButtons = document.getElementById("answer-buttons");
const nextButton = document.getElementById("next-btn");
const scoreElement = document.getElementById("score");
const topicElement = document.getElementById("topic");

let currentQuestionIndex = 0;
let score = 0;
let num_rq = 0;
let userChoices = [];

function startQuiz() {
  currentQuestionIndex = 0;
  score = 0;
  num_rq = 0;
  userChoices = [];
  nextButton.style.display = "none";
  scoreElement.style.display = "none"; // Hide score initially
  scoreElement.innerText = ""; // Clear any previous score
  showQuestion();
}

function showQuestion() {
  const currentQuestion = questions[currentQuestionIndex];

  // Check if at least one correct answer exists
  const hasCorrectAnswer = Object.values(currentQuestion.answer).some((answer) => answer.correct);

  if (!hasCorrectAnswer) {
    console.error(`Invalid question: Question ${currentQuestionIndex + 1} does not have a correct answer.`);
    num_rq++;
    nextQuestion(); // Skip to the next question
    return;
  }

  questionElement.innerText = currentQuestion.question;

  answerButtons.innerHTML = "";
  topicElement.innerHTML = "論題: 「" + query + "」";

  Object.values(currentQuestion.answer).forEach((answer) => {
    const button = document.createElement("button");
    button.innerText = answer.text;
    button.classList.add("quizbtn");
    button.addEventListener("click", () => {
      
      selectAnswer(answer);
    });
    answerButtons.appendChild(button);
  });
}

function selectAnswer(answer) {
  const previousAnswerIndex = Object.values(questions[currentQuestionIndex].answer)
    .findIndex((ans) => userChoices[currentQuestionIndex] === ans);

  if (previousAnswerIndex !== -1) {
    answerButtons.children[previousAnswerIndex].style.backgroundColor = ""; // 重置上一次选择的背景颜色
  }

  userChoices[currentQuestionIndex] = answer;

  const buttons = answerButtons.getElementsByTagName("button");
  for (let i = 0; i < buttons.length; i++) {
    buttons[i].disabled = false;
    buttons[i].style.backgroundColor = ""; // 重置所有选项按钮的背景颜色
  }

  const correctAnswerIndices = Object.values(questions[currentQuestionIndex].answer)
    .map((ans, index) => (ans.correct ? index : -1))
    .filter((index) => index !== -1);

  const selectedAnswerIndex = Object.values(questions[currentQuestionIndex].answer)
    .findIndex((ans) => ans === answer);

  if (selectedAnswerIndex !== -1) {
    answerButtons.children[selectedAnswerIndex].style.backgroundColor = "#008B8B"; // 设置所选答案的背景颜色
  }

  correctAnswerIndices.forEach((index) => {
    answerButtons.children[index].classList.add("correct-answer");

  });

  nextButton.style.display = "block";
}

function nextQuestion() {
  const buttons = answerButtons.getElementsByTagName("button");
  for (let i = 0; i < buttons.length; i++) {
    buttons[i].classList.remove("correct-answer");
    buttons[i].disabled = false; // Re-enable answer buttons
  }

  currentQuestionIndex++;
  if (currentQuestionIndex < questions.length) {
    showQuestion();
    nextButton.style.display = "none";
  } else {
    
    questions.forEach((question, index) => {
      const userChoice = userChoices[index];
      const correctAnswer = Object.values(question.answer).find((ans) => ans.correct);
  
      if (!userChoice && !correctAnswer) {
        return;
      }
      if(userChoice==correctAnswer){score++;}
    });

    questionElement.innerText = "測驗完成！!";
    answerButtons.innerHTML = "";
    scoreElement.innerText = `總得分: ${score}/${questions.length - num_rq}`;
    scoreElement.classList.add("score-display");
    scoreElement.style.display = "block";
    displayResults();
    nextButton.style.display = "none";
  }
}

function displayResults() {
  const resultsContainer = document.createElement("div");

  questions.forEach((question, index) => {
    const userChoice = userChoices[index];
    const correctAnswer = Object.values(question.answer).find((ans) => ans.correct);

    if (!userChoice && !correctAnswer) {
      return;
    }

    const questionResult = document.createElement("div");
    questionResult.classList.add("question-result", "mini-card"); // Add the "mini-card" class

    const questionText = document.createElement("p");
    questionText.innerText = question.question;
    questionResult.appendChild(questionText);

    const detailsContainer = document.createElement("div");
    detailsContainer.classList.add("details-container");

    const userChoiceText = document.createElement("p");
    userChoiceText.innerText = `你的選擇: ${userChoice ? userChoice.text : "No choice"}`;
    detailsContainer.appendChild(userChoiceText);

    const correctAnswerText = document.createElement("p");
    correctAnswerText.innerText = `正確答案: ${correctAnswer ? correctAnswer.text : "None"}`;
    detailsContainer.appendChild(correctAnswerText);

    questionResult.appendChild(detailsContainer);

    resultsContainer.appendChild(questionResult);
  });

  answerButtons.appendChild(resultsContainer);
}

function fetchData() {
  $.ajax({
    url: "/data",
    method: "GET",
    dataType: "json",
    success: function(response) {
      console.log(response);
    },
    error: function() {
      console.log("Request failed");
    }
  });
}

nextButton.addEventListener("click", nextQuestion);

