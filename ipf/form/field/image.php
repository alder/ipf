<?php

class IPF_Form_Field_Image extends IPF_Form_Field_File
{
    public $widget = 'IPF_Form_Widget_Image';

    public function clean($value)
    {
        $name = parent::clean($value);
        if ($name) {
            $image = IPF::getUploadPath() . DIRECTORY_SEPARATOR . $name;
            if (!getimagesize($image))
                throw new IPF_Exception_Form(__('An error occured when upload the image.'));
        }
        return $name;
    }
}

