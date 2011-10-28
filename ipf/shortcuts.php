<?php

final class IPF_Shortcuts{

    static function GetObjectOr404($object, $id)
    {
        $obj = IPF_ORM::getTable($object)->findOneById($id);
        if ($obj)
            return $obj;
        throw new IPF_HTTP_Error404();
    }

    static function RenderToResponse($tplfile, $params=array(), $request=null){
        return new IPF_HTTP_Response(IPF_Shortcuts::RenderToString($tplfile, $params, $request));
    }

    static function RenderToString($tplfile, $params=array(), $request=null){
        $tmpl = new IPF_Template($tplfile);
        if (is_null($request)) {
            $context = new IPF_Template_Context($params);
        } else {
            $context = new IPF_Template_Context_Request($request, $params);
        }
        return $tmpl->render($context);
    }

    static function GetFormForModel($model, $data=null, $extra=array(), $label_suffix=null)
    {
        $extra['model'] = $model;
        return new IPF_Form_Model($data, $extra, $label_suffix);
    }
}
