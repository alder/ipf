<?php

class IPF_Form_Field_File extends IPF_Form_Field
{
    public $widget = 'IPF_Form_Widget_FileInput';
    public $max_size =  20971520; // 20MB
    public $uploadTo = '';

    protected function removeFile($data)
    {
        return null;
    }

    protected function getRelativePath($filename)
    {
        if ($this->uploadTo)
            return $this->uploadTo . DIRECTORY_SEPARATOR . $filename;
        else
            return $filename;
    }

    protected function getAbsolutePath($filename)
    {
        $upload_root = IPF::getUploadPath() . DIRECTORY_SEPARATOR;
        if ($this->uploadTo)
            return $upload_root . $this->uploadTo . DIRECTORY_SEPARATOR . $filename;
        else
            return $upload_root . $filename;
    }

    protected function renameFile($old_name, $new_name)
    {
        @rename(getAbsolutePath($old_name), getAbsolutePath($new_name));
        return getRelativePath($new_name);
    }

    public function clean($value)
    {
        IPF_Utils::makeDirectories($this->getAbsolutePath(''));

        if (@$value['remove'] === true)
            return $this->removeFile($value['data']);

        if (@$value['name'] != @$value['rename'])
            return $this->renameFile(@$value['name'], @$value['rename']);

        $value = @$value['data'];

        if (@$value['name'] == '')
            return '';

        parent::clean($value);

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

        $name = IPF_Utils::cleanFileName($value['name'], $this->getAbsolutePath(''));
        $dest = $this->getAbsolutePath($name);
        if (!move_uploaded_file($value['tmp_name'], $dest))
            throw new IPF_Exception_Form(__('An error occured when upload the file. Please try to send the file again.'));
        @chmod($dest, IPF::get('file_permission'));
        return $this->getRelativePath($name);
    }
}

