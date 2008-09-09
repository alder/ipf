<?php 

class IPF_Auth_Forms_UserCreation extends IPF_Form_Model{
    function __construct($data=null, $extra=array(), $label_suffix=null){
        $extra['model'] = new User();
        parent::__construct($data, $extra, $label_suffix);
    }
    
    function fields(){ 
        return array('username','password1','password2','email','first_name','last_name','is_active','is_staff','is_superuser'); 
    }
    
    function addDBField($name,$col){
        parent::addDBField($name, $col);
        if ($name=='username')
            $this->fields['username']->help_text = 'Required. 32 characters or less. Alphanumeric characters only (letters, digits and underscores).';
    }
    
    function add__password1__field(){
        $this->fields['password1'] = new IPF_Form_Field_Varchar(array('label'=>'Password','required'=>true,'max_length'=>32,'widget'=>'IPF_Form_Widget_PasswordInput'));
    }

    function add__password2__field(){
        $this->fields['password2'] = new IPF_Form_Field_Varchar(array('label'=>'Password (again)','required'=>true,'max_length'=>32,'widget'=>'IPF_Form_Widget_PasswordInput','help_text'=>'Enter the same password as above, for verification.'));
    }
    
    function isValid(){
        $ok = parent::isValid();
        if ($ok===true){
            if ($this->cleaned_data['password1']!=$this->cleaned_data['password2']){
                $this->is_valid = false;
                $this->errors['password2'][] = "The two password fields didn't match.";
                return false;
            }
        }
        return $ok;
    }
}
