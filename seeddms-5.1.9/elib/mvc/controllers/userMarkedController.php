<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of userMarkedController
 *
 * @author sc
 */

abstract class userMarkedController {
    //put your code here
    protected $input;
    protected $data;
    
    function __construct($input) {
        $this->input = $input;
    }
    
    function show() {
        $modelClass = $this->input['name'].'Model';
        $model = new $modelClass($this->input);
        $this->data = $model->get();
        
        $sortMode = $this->sortMode();
        
        $viewClass = $this->input['name'].'View';
        $view = new $viewClass($this->input);
        $this->data = $view->displayHeader($this->data);
        $this->data = $view->displayContent($this->data);
        
        echo $this->data['str'];
    }
    
    function sortMode() {
        if(!isset($_COOKIE['sort_mode'])){
            setcookie('sort_mode','ascending');
        }
        else{
            if($_COOKIE['sort_mode']=='ascending'){
                setcookie('sort_mode','descending');
            }
            else{
                setcookie('sort_mode','ascending');
            }
        }
    }
}
