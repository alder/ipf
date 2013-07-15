<?php

function IPF_Context_Auth($request)
{
    return array('user' => $request->user);
}

function IPF_Context_Media($request)
{
    return array('MEDIA_URL' => IPF::get('media_url'));
}

function IPF_Context_Upload($request)
{
    return array('UPLOAD_URL' => IPF::getUploadUrl());
}

function IPF_Context_Current($request)
{
    return array('CURRENT_URL' => $request->query);
}

