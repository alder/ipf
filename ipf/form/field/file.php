<?php

class IPF_Form_Field_File extends IPF_Form_Field
{
    public $widget = 'IPF_Form_Widget_FileInput';
    public $move_function = 'IPF_Form_Field_moveToUploadFolder';
    public $remove_function = 'IPF_Form_Field_removeFile';
    public $max_size = 2097152; // 2MB
    public $move_function_params = array();

    function clean($value)
    {
        if ($value['remove']==1){
            //print_r($value);
            IPF::loadFunction($this->remove_function);
            return call_user_func($this->remove_function, $value['data']);
        }

        $value = $value['data'];

        if ($value['name']=='')
            return '';

        parent::clean($value);
        
        $errors = array();
        $no_files = false;
        switch ($value['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            throw new IPF_Exception_Form(__('The uploaded file is too large. Reduce the size of the file and send it again.'));
            break;
        case UPLOAD_ERR_PARTIAL:
            throw new IPF_Exception_Form(__('The upload did not complete. Please try to send the file again.'));
            break;
        case UPLOAD_ERR_NO_FILE:
            throw new IPF_Exception_Form(__('No files were uploaded. Please try to send the file again.'));
            break;
        case UPLOAD_ERR_NO_TMP_DIR:
        case UPLOAD_ERR_CANT_WRITE:
            throw new IPF_Exception_Form(__('The server has no temporary folder correctly configured to store the uploaded file.'));
            break;
        case UPLOAD_ERR_EXTENSION:
            throw new IPF_Exception_Form(__('The uploaded file has been stopped by an extension.'));
            break;
        default:
            throw new IPF_Exception_Form(__('An error occured when upload the file. Please try to send the file again.'));
        }
        if ($value['size'] > $this->max_size) {
            throw new IPF_Exception_Form(sprintf(__('The uploaded file is to big (%1$s). Reduce the size to less than %2$s and try again.'), 
                                        IPF_Utils::prettySize($value['size']),
                                        IPF_Utils::prettySize($this->max_size)));
        }
        IPF::loadFunction($this->move_function);
        return call_user_func($this->move_function, $value, $this->move_function_params);
    }
}


function IPF_Form_Field_moveToUploadFolder($value, $params=array())
{
    $name = IPF_Utils::cleanFileName($value['name']);
    $upload_path = IPF::get('upload_path', '/tmp');
    if (isset($params['upload_path'])) {
        $upload_path = $params['upload_path'];
    }
    $dest = $upload_path.DIRECTORY_SEPARATOR.$name;
    if (!move_uploaded_file($value['tmp_name'], $dest)) {
        throw new IPF_Exception_Form(__('An error occured when upload the file. Please try to send the file again.'));
    } 
    @chmod($dest, 0666);
    return $name;
}


function IPF_Form_Field_removeFile($value, $params=array())
{
    /*
    $name = IPF_Utils::cleanFileName($value['name']);
    $upload_path = IPF::get('upload_path', '/tmp');
    if (isset($params['upload_path'])) {
        $upload_path = $params['upload_path'];
    }
    $dest = $upload_path.DIRECTORY_SEPARATOR.$name;
    if (!move_uploaded_file($value['tmp_name'], $dest)) {
        throw new IPF_Exception_Form(__('An error occured when upload the file. Please try to send the file again.'));
    } 
    @chmod($dest, 0666);
    */
    return null;
}
