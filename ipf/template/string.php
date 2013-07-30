<?php

class IPF_Template_String extends IPF_Template
{
    private $tpl;

    function __construct($template, IPF_Template_Environment $environment)
    {
        parent::__construct($environment);
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

