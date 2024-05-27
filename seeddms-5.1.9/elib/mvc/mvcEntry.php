<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

    function Model($name, $input) {
        return init('Model', $name, $input);
    }

    function View($name, $input) {        
        return init('View', $name, $input);
    }
    
    function Controller($name, $input) {
        return init('Controller', $name, $input);
    }
    
    function init($component, $name, $input){
        require_once(__DIR__.'/models/'.$name.'Model.php');
        require_once(__DIR__.'/views/'.$name.'View.php');
        require_once(__DIR__.'/controllers/'.$name.'Controller.php');
        $class = $name.$component;
        $input['name']=$name;
        $obj = new $class($input);
        return $obj;
    }
?>