<?php

class IPF_Template_String extends IPF_Template
{
    private $tpl;

    function __construct($template, $environment=null)
    {
        parent::__construct($environment ? $environment : IPF_Template_Environment::getDefault());
        $this->tpl = $template;
    }

    public function __toString()
    {
        return $this->tpl;
    }

    protected function content()
    {
        return $this->tpl;
    }
}

