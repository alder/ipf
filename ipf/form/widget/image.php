<?php

class IPF_Form_Widget_Image extends IPF_Form_Widget_FileInput
{
    protected function viewCurrentValue($filename)
    {
        if ($filename) {
            $url = IPF::getUploadUrl() . $filename;
            return '&nbsp;<a target="_blank" href="'.$url.'"><img src="'.$url.'" style="max-width:64px;max-height:64px"/></a>&nbsp;';
        } else {
            return '';
        }
    }
}

