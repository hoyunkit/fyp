<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of userMarkedModel
 *
 * @author sc
 */

abstract class userMarkedModel {
    //put your code here
    protected $input;
    protected $dms;
    protected $userid;
    protected $documentid;
    
    protected $data = [];
    
    function __construct($input) {
        $this->input = $input;        
        $this->dms = $input['dms'];
        $this->userid = $input['userid'];
        $this->documentid = $input['documentid'];        
    }
    
    abstract function get();
}
