<?php

class IPF_Form_Field_Image extends IPF_Form_Field_File{
    public $widget = 'IPF_Form_Widget_Image';
    public $move_function = 'IPF_Form_Field_moveImageToUploadFolder';
}

function IPF_Form_Field_moveImageToUploadFolder($value, $params=array())
{
    $name = IPF_Form_Field_moveToUploadFolder($value, $params);
    $upload_path = IPF::getUploadPath($params);
    $image = $upload_path.DIRECTORY_SEPARATOR.$name;

    if(!getimagesize($image))
        throw new IPF_Exception_Form(__('An error occured when upload the image.'));

    return $name;
}

