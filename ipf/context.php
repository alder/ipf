<?php

function IPF_Context_Auth($request)
{
    return array('user' => $request->user);
}

function IPF_Context_Media($request)
{
    return array('MEDIA_URL' => IPF::get('media_url'));
}

function IPF_Context_AdminMedia($request)
{
    return array('ADMIN_MEDIA_URL' => IPF::get('admin_media_url'));
}

function IPF_Context_Version($request)
{
    return array('IPF_VER' => IPF_Version::$name);
}

function IPF_Context_Upload($request)
{
    return array('UPLOAD_URL' => IPF::get('upload_url'));
}

function IPF_Context_Current($request)
{
    return array('CURRENT_URL' => IPF_HTTP_URL::getAction());
}
