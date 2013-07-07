<?php

abstract class IPF_Template_Environment
{
    abstract public function loadTemplateFile($filename);

    abstract public function getCompiledTemplateName($template);

    private static $defaultEnvironment = null;

    public static function getDefault()
    {
        if (!self::$defaultEnvironment) {
            $dirs = array();

            $projectTemplates = IPF::get('project_path') . '/templates';
            if (is_dir($projectTemplates))
                $dirs[] = $projectTemplates;

            foreach (IPF_Project::getInstance()->appList() as $app) {
                $applicationTemplates = $app->getPath() . 'templates';
                if (is_dir($applicationTemplates))
                    $dirs[] = $applicationTemplates;
            }

            self::$defaultEnvironment = new IPF_Template_Environment_FileSystem($dirs, IPF::get('tmp'));
        }
        return self::$defaultEnvironment;
    }
}

