<?php

final class IPF_Shortcuts{

    static function GetObjectOr404($object, $id)
    {
        $item = new $object($id);
        if ((int)$id > 0 && $item->id == $id) {
            return $item;
        }
        throw new IPF_HTTP_Error404();
    }

    static function RenderToResponse($tplfile, $params, $request=null)
    {
        $tmpl = new IPF_Template($tplfile);
        if (is_null($request)) {
            $context = new IPF_Template_Context($params);
        } else {
            $context = new IPF_Template_Context_Request($request, $params);
        }
        return new IPF_HTTP_Response($tmpl->render($context));
    }

    static function GetFormForModel($model, $data=null, $extra=array(), $label_suffix=null)
    {
        $extra['model'] = $model;
        return new IPF_Form_Model($data, $extra, $label_suffix);
    }
}