<?php

class IPF_Template_File extends IPF_Template
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
        return $this->environment->loadTemplateFile($this->tpl);
    }
}

