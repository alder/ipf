<?php 

class IPF_Auth_Forms_Login extends IPF_Form{
    function initFields($extra=array()){
        $this->fields['username'] = new IPF_Form_Field_Varchar(array('required'=>true));
        $this->fields['password'] = new IPF_Form_Field_Varchar(array('required'=>true,'widget'=>'IPF_Form_Widget_PasswordInput'));
        $this->fields['next'] = new IPF_Form_Field_Varchar(array('required'=>false,'widget'=>'IPF_Form_Widget_HiddenInput'));
    }
}
