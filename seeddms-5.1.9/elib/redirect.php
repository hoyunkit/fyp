<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
$encrypted=$_REQUEST['h'];
$encrypted=base64_decode( strtr( $encrypted, '-_', '+/') . str_repeat('=', 3 - ( 3 + strlen( $encrypted )) % 4 ));
$decryption_iv = '1234567891011121'; 
$key=$_COOKIE['mydms_session'];
$ciphering = "AES-128-CTR"; 
$decryption=openssl_decrypt ($encrypted, $ciphering,  
        $key, 0, $decryption_iv); 

header("Content-Type: image/jpeg");
readfile($decryption);
