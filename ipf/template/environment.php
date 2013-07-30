<?php

abstract class IPF_Template_Environment
{
    abstract public function loadTemplateFile($filename);

    abstract public function getCompiledTemplateName($template);

    public $allowedTags = array();

    public function isTagAllowed($name)
    {
        return isset($this->allowedTags[$name]);
    }
}

