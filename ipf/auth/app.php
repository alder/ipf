<?php

class IPF_Auth_App extends IPF_Application{
    public function __construct(){
        parent::__construct(array(
            'models'=>array('User','Role')
        ));
    }
}