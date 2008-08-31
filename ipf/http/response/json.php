<?php

class IPF_HTTP_Response_Json extends IPF_HTTP_Response{
    function __construct($content){
        parent::__construct(json_encode($content),'application/json');
    }
}
