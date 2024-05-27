<?php
include("BootStrap.php");
include("../inc/inc.Settings.php");
include_once("../inc/inc.Language.php");

if($_SERVER['REQUEST_METHOD']=='POST'){

    $deepseek_api_key = 'deepseek api key here'; // replace with your deepseek api key

    $output = '';

    if ($_POST['action']=='chatgpt') {
        $query=$_POST['query'];
        $output='';
        $prompt = '將此翻譯成現代漢語並詳細解釋: '.$query.'。請用繁體中文回答。'; 
        //$env_vars = getenv();
        $command = 'python3 callPoe.py beaver "'.$prompt.'"'; // GPT-4: beaver, GPT-3.5-Turbo: gpt3_5
        $output = shell_exec($command);
        echo $output;
    }

    /*if ($_POST['action']=='gemini') {
        $query=$_POST['query'];
        $output='';
        $prompt = '將此翻譯成現代漢語並詳細解釋: '.$query.'。請用繁體中文回答。'; 
        $output = shell_exec('curl \
                    -H \'Content-Type: application/json\' \
                    -d \'{"contents":[{"parts":[{"text":"'.$prompt.'"}]}]}\' \
                    -X POST https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key='.$gemini_api_key.'');
        echo $output;
    }*/

    if ($_POST['action']=='gemini') {
        $query=$_POST['query'];
        $output='';
        $prompt = '將此翻譯成現代漢語並詳細解釋: '.$query.'。請用繁體中文回答。'; 
        $command = 'python3 callPoe.py gemini_pro "'.$prompt.'"';
        $output = shell_exec($command);
        echo $output;
    }

    if ($_POST['action']=='deepseek') {
        $query=$_POST['query'];
        $output='';
        $prompt = '將此翻譯成現代漢語並詳細解釋: '.$query.'。請用繁體中文回答。'; 
        $output = shell_exec('curl https://api.deepseek.com/v1/chat/completions \
                    -H "Content-Type: application/json" \
                    -H "Authorization: Bearer '.$deepseek_api_key.'" \
                    -d \'{
                            "model": "deepseek-chat",
                            "messages": [
                            {"role": "system", "content": "You are a helpful assistant."},
                            {"role": "user", "content": "'.$prompt.'"}
                            ]
                        }\'');
        echo $output;
    }

    if ($_POST['action']=='follow_up_chatgpt') {
        $query=$_POST['query']; //query[0]=followUpQuery, query[1]=lastQuery
        $output='';
        $prompt = '對於上一次的回答，我有以下的問題或修正: '.$query[0].'。請用繁體中文回答。';
        $context = '上一次的回答是: '.$query[1].''; 
        $command = 'python3 callPoe.py beaver "'.$context.''.$prompt.'"';
        $output = shell_exec($command);
        echo $output;
    }

    /*if ($_POST['action']=='follow_up_gemini') {
        $query=$_POST['query']; //query[0]=followUpQuery, query[1]=lastQuery
        $output='';
        $prompt = '對於上一次的回答，我有以下的問題或修正: '.$query[0].'。請用繁體中文回答。';
        $context = '上一次的回答是: '.$query[1].''; 
        $output = shell_exec('curl \
                    -H \'Content-Type: application/json\' \
                    -d \'{"contents":[{"parts":[{"text":"'.$context.''.$prompt.'"}]}]}\' \
                    -X POST https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key='.$gemini_api_key.'');
        echo $output;
    }*/

    if ($_POST['action']=='follow_up_gemini') {
        $query=$_POST['query']; //query[0]=followUpQuery, query[1]=lastQuery
        $output='';
        $prompt = '對於上一次的回答，我有以下的問題或修正: '.$query[0].'。請用繁體中文回答。';
        $context = '上一次的回答是: '.$query[1].''; 
        $command = 'python3 callPoe.py gemini_pro "'.$context.''.$prompt.'"';
        $output = shell_exec($command);
        echo $output;
    }

    if ($_POST['action']=='follow_up_deepseek') {
        $query=$_POST['query']; //query[0]=followUpQuery, query[1]=lastQuery
        $output='';
        $prompt = '對於上一次的回答，我有以下的問題或修正: '.$query[0].'。請用繁體中文回答。';
        $context = '上一次的回答是: '.$query[1].''; 
        $output = shell_exec('curl https://api.deepseek.com/v1/chat/completions \
                    -H "Content-Type: application/json" \
                    -H "Authorization: Bearer '.$deepseek_api_key.'" \
                    -d \'{
                            "model": "deepseek-chat",
                            "messages": [
                            {"role": "system", "content": "You are a helpful assistant."},
                            {"role": "user", "content": "'.$context.''.$prompt.'"}
                            ]
                        }\'');
        echo $output;
    }
    
    if ($_POST['action'] == 'quiz') {
        
        $query = $_POST['query'];
        $meaning = $_POST['meaning'];
        $meaning = preg_replace('/\s+/', '', $meaning);
        $output='';
        $prompt = ' 這段古漢語:['.$query.']的翻譯是['.$meaning.']請產生四個問題來測試使用者對這段古漢語的理解程度每題必須有問題，4個選項和1個正確答案,請遵循順序先提及該問題,選項,正確答案，然後產生下一個問題,該問題應完全遵循於以下格式：問題：,選項：,正確答案：'; 
        $output = shell_exec('curl https://api.deepseek.com/v1/chat/completions \
                    -H "Content-Type: application/json" \
                    -H "Authorization: Bearer '.$deepseek_api_key.'" \
                    -d \'{
                            "model": "deepseek-chat",
                            "messages": [
                            {"role": "system", "content": "You are a helpful assistant."},
                            {"role": "user", "content": "'.$prompt.'"}
                            ]
                        }\'');
        // Parse the JSON response
        $response = json_decode($output, true);

        $text = $response['choices'][0]['message']['content'];
       

        $pattern = '/問題\d+：(.*?)\n選項：(.*?)\n正確答案：(.*?)\./s';
        preg_match_all($pattern, $text, $matches, PREG_SET_ORDER);

        $questions = [];

        foreach ($matches as $match) {
            $questionText = trim($match[1]);
            $choicesText = trim($match[2]);
            $correctAnswerText = trim($match[3]);

            $choices = [];
            preg_match_all('/[A-Z]\. (.*?)(?=\n[A-Z]\.|\z)/s', $choicesText, $choiceMatches, PREG_SET_ORDER);
            foreach ($choiceMatches as $choiceMatch) {
                $choiceText = trim($choiceMatch[1]);
                $choices[] = [
                    'text' => $choiceText,
                    'correct' => false,
                ];
            }

            $correctAnswerIndex = ord($correctAnswerText) - 65;
            $choices[$correctAnswerIndex]['correct'] = true;

            $question = [
                'question' => $questionText,
                'answer' => $choices,
            ];

            $questions[] = $question;
        }

        print_r(json_encode($questions, JSON_UNESCAPED_UNICODE));
    }
}
?>






