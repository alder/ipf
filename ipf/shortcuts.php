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

        $tmpl = new IPF_Template_File($tplfile);
        return $tmpl->render($context);
    }

    public static function GetFormForModel($model, $data=null, $extra=array(), $label_suffix=null)
    {
        $extra['model'] = $model;
        return new IPF_Form_Model($data, $extra, $label_suffix);
    }
}

