<?php

abstract class IPF_Template_Environment
{
    abstract public function loadTemplateFile($filename);

    abstract public function getCompiledTemplateName($template);

    private static $defaultEnvironment = null;

    public static function getDefault()
    {
        if (!self::$defaultEnvironment)
            self::$defaultEnvironment = new IPF_Template_Environment_FileSystem(IPF::get('template_dirs'), IPF::get('tmp'));
        return self::$defaultEnvironment;
    }
}

