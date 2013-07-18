<?php

class IPF_Session_App extends IPF_Application
{
    public function __construct()
    {
        parent::__construct(array(
            'models' => array('Session')
        ));
    }
}

