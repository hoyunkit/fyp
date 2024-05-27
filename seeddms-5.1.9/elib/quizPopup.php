<?php
print '
<html>
<head>
    <link href="../elib/PreviewDocument.css" rel="stylesheet">
    <link href="../elib/quizPopup.css" rel="stylesheet">
    <link href="../styles/bootstrap/bootstrap/css/bootstrap.css" rel="stylesheet">
</head>
<body>
    <div class="app" style="display: none">
        <h1 id="topic"></h1>
        <p id="score"></p>
        <div class="quiz">
            <h2 id="question">Question goes here</h2>
            <div id="answer-buttons">
                <button class="quizbtn">Answer 1</button>
                <button class="quizbtn">Answer 2</button>
                <button class="quizbtn">Answer 3</button>
                <button class="quizbtn">Answer 4</button>
            </div>
        </div>
        <button id="next-btn" class="quizbtn">下一個問題</button>
        <div class="wave"></div>
        <div class="wave"></div>
        <div class="wave"></div>
        
    </div>

    <script src="../styles/bootstrap/jquery/jquery.min.js"></script>
    <script type="text/javascript" src="../elib/quizPopup.js"></script>
  
</body>
</html>';
?>