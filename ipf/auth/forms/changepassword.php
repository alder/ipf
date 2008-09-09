<?php 

class IPF_Auth_Forms_ChangePassword extends IPF_Form{
    
    function initFields($extra=array())
    {
        $this->fields['password1'] = new IPF_Form_Field_Varchar(array('required'=>true,'widget'=>'IPF_Form_Widget_PasswordInput'));
        $this->fields['password2'] = new IPF_Form_Field_Varchar(array('required'=>true,'widget'=>'IPF_Form_Widget_PasswordInput','help_text'=>'Enter the same password as above, for verification.'));
    }
    
    function isValid(){
        $ok = parent::isValid();
        if ($ok===true){
            if ($this->cleaned_data['password1']!=$this->cleaned_data['password2']){
                $this->is_valid = false;
                $this->errors['password2'][] = "The two password fields didn't match.";
                $ok = false;
            }
        }
        return $ok;
    }
}
