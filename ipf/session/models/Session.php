<?php

class Session extends BaseSession
{
    public $data = array();
    public $touched = false;

    function clear(){
        $this->data = array();
        $this->touched = true;
    }

    function setData($key, $value=null){
        if (is_null($value)) {
            unset($this->data[$key]);
        } else {
            $this->data[$key] = $value;
        }
        $this->touched = true;
    }

    function getData($key=null, $default=''){
        if (is_null($key)) 
            return parent::getData();

        if (isset($this->data[$key])) {
            return $this->data[$key];
        } else {
            return $default;
        }
    }

    function getNewSessionKey()
    {
        $key = md5(microtime().rand(0, 123456789).rand(0, 123456789).IPF::get('secret_key'));
        return $key;
    }

    function preSave($event)
    {
        $this->session_data = serialize($this->data);
        if ($this->session_key == '') {
            $this->session_key = $this->getNewSessionKey();
        }
        $this->expire_data = gmdate('Y-m-d H:i:s', time()+31536000);
    }
}