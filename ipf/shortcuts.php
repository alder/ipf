<?php

final class IPF_Shortcuts
{
    public static function GetObjectOr404($object, $id)
    {
        $obj = IPF_ORM::getTable($object)->findOneById($id);
        if ($obj)
            return $obj;
        throw new IPF_HTTP_Error404();
    }

    public static function RenderToResponse($tplfile, $params=array(), $request=null)
    {
        return new IPF_HTTP_Response(IPF_Shortcuts::RenderToString($tplfile, $params, $request));
    }

    public static function RenderToString($tplfile, $params=array(), $request=null)
    {
        if ($request) {
            $params = array_merge(array('request' => $request), $params);
            foreach (IPF::get('template_context_processors', array()) as $proc) {
                IPF::loadFunction($proc);
                $params = array_merge($proc($request), $params);
            }
            foreach (IPF_Project::getInstance()->appList() as $app) {
                $params = array_merge($app->templateContext($request), $params);
            }
        }
        $context = new IPF_Template_Context($params);

        $tmpl = new IPF_Template_File($tplfile, self::getDefaultTemplateEnvironment());
        return $tmpl->render($context);
    }

    private static $defaultEnvironment = null;

    private static function getDefaultTemplateEnvironment()
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

            self::$defaultEnvironment->tags['url'] = 'IPF_Template_Tag_Url';
            // extra tags
            self::$defaultEnvironment->tags = array_merge(IPF::get('template_tags', array()), self::$defaultEnvironment->tags);

            // extra modifiers
            self::$defaultEnvironment->modifiers = array_merge(IPF::get('template_modifiers', array()), self::$defaultEnvironment->modifiers);
        }
        return self::$defaultEnvironment;
    }

    public static function GetFormForModel($model, $data=null, $extra=array(), $label_suffix=null)
    {
        $extra['model'] = $model;
        return new IPF_Form_Model($data, $extra, $label_suffix);
    }
}

