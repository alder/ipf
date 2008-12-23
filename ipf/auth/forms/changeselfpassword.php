<?php

class IPF_Auth_Forms_ChangeSelfPassword extends IPF_Form{

    function initFields($extra=array())
    {
        $this->fields['oldpassword'] = new IPF_Form_Field_Varchar(array('label'=>'Current Password', 'required'=>true, 'widget'=>'IPF_Form_Widget_PasswordInput'));
        $this->fields['password1'] = new IPF_Form_Field_Varchar(array('label'=>'New Password', 'required'=>true,'widget'=>'IPF_Form_Widget_PasswordInput'));
        $this->fields['password2'] = new IPF_Form_Field_Varchar(array('label'=>'New Password (repeat)','required'=>true,'widget'=>'IPF_Form_Widget_PasswordInput','help_text'=>'Enter the same password as above, for verification.'));
    }

    function isValid($request){
        $ok = parent::isValid();
        if ($ok===true){
            if ($this->cleaned_data['password1']!=$this->cleaned_data['password2']){
                $this->is_valid = false;
                $this->errors['password2'][] = "The two password fields didn't match.";
                $ok = false;
            }
            $u = new User();
            if ($u->checkCreditentials($request->user->username, $this->cleaned_data['oldpassword'])===false){
                $this->is_valid = false;
                $this->errors['oldpassword'][] = "Incorrect old password";
                $ok = false;
            }
        }
        return $ok;
    }
}
