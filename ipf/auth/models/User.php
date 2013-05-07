<?php

class IPFAuthAdminUserForm extends IPF_Form_Extra_CheckGroup
{
    function initFields($extra=array())
    {
        parent::initFields($extra);
        
        $this->fields['email']->label = 'E-mail';
        
        $this->fields['is_active']->label    = 'Active';
        $this->fields['is_staff']->label     = 'Staff status';
        $this->fields['is_superuser']->label = 'Superuser status';

        $this->fields['is_active']->help_text    = 'Designates whether this user should be treated as active. Unselect this instead of deleting accounts.';
        $this->fields['is_staff']->help_text     = 'Designates whether the user can log into this admin site.';
        $this->fields['is_superuser']->help_text = 'Designates that this user has all permissions without explicitly assigning them.';
        
        $this->fields['username']->help_text = 'Required. 32 characters or less. Alphanumeric characters only (letters, digits and underscores).';        
        
        if (!$this->model->id)
        {
            unset($this->fields['password']);
            
            $this->fields['password1'] = new IPF_Form_Field_Varchar(array(
                'label' => 'Password',
                'required' => true,
                'max_length' => 32,
                'widget' => 'IPF_Form_Widget_PasswordInput'
            ));
            
            $this->fields['password2'] = new IPF_Form_Field_Varchar(array(
                'label' => 'Password (again)',
                'required' => true,
                'max_length' => 32,
                'widget' => 'IPF_Form_Widget_PasswordInput',
                'help_text' => 'Enter the same password as above, for verification.'
            ));
            
            $account = array('username', 'password1', 'password2');
        }
        else
        {
            $this->fields['password']->help_text = "Use '[algo]$[salt]$[hexdigest]' or use the <a href=\"password/\">change password form</a>."; 
            
            $account = array('username', 'password');
        }

        $permissions = array('is_active', 'is_staff', 'is_superuser');
        if (IPF_Auth_App::ArePermissionsEnabled()) {
            $permissions[] = 'Permissions';
            $permissions[] = 'Roles';

            $this->fields['Roles']->label = 'Groups';
            $this->fields['Roles']->help_text = 'In addition to the permissions manually assigned, this user will also get all permissions granted to each group he/she is in.';

            parent::SetupForm($this);            
        } else {
            unset($this->fields['Permissions']);
            unset($this->fields['Roles']);
        }

        $this->field_groups = array(
            array('fields' => $account),
            array('fields' => array('email', 'first_name', 'last_name'), 'label' => 'Personal info'),
            array('fields' => $permissions, 'label' => 'Permissions'),
        );
    }
    
    function isValid()
    {
        $ok = parent::isValid();
        
        if ($ok===true && !$this->model->id)
        {
            if ($this->cleaned_data['password1'] != $this->cleaned_data['password2'])
            {
                $this->is_valid = false;
                $this->errors['password2'][] = "The two password fields didn't match.";
                
                return false;
            }
            
            $this->cleaned_data['password'] = User::SetPassword2($this->cleaned_data['password1']);
        }
        
        return $ok;
    }
}

class AdminUser extends IPF_Admin_Model
{
    public function list_display()
    {
        return array(
            'username',
            'email',
            'is_active',
            'is_staff',
            'is_superuser',
            'created_at',
        );
    }
    
    public function fields()
    {
        return array(
            'username',
            'password',
            'email',
            'first_name',
            'last_name',
            'is_active',
            'is_staff',
            'is_superuser',
            'Permissions',
            'Roles',
        );
    }

    function _searchFields()
    {
        return array(
            'username',
            'email',
        );
    }

    protected function _getForm($model_obj, $data, $extra)
    {
        $extra['model'] = $model_obj;
        $extra['checkgroup_fields'] = array(
            'Permissions' => array('widget'=>'IPF_Auth_Forms_Widget_Permissions'),
            'Roles' => array(),
        );
        return new IPFAuthAdminUserForm($data, $extra);
    }
}

class User extends BaseUser
{
    const UNUSABLE_PASSWORD = '!';
    public $session_key = 'IPF_User_auth';

    public function __toString() {
        $s = $this->username;
        if ($s===null)
            return 'Anonymous';
        return $s;
    }

    public function smartName() {
        $username = $this->username;
        if ($username===null)
            return __('Anonymous');
        $name = $this->first_name.' '.$this->last_name;
        if (trim($name)=='')
            return $username;
        return $name;
    }

    static function createUser($username, $password=null, $email=null, $first_name=null, $last_name=null, $is_active=false, $is_staff=false, $is_superuser=false)
    {
        $user = new User();
        $user->username = $username;

        if (trim($email)=='')
            $user->email = null;
        else
            $user->email = $email;

        $user->first_name = $first_name;
        $user->last_name = $last_name;
        $user->is_active = $is_active;
        $user->is_staff = $is_staff;
        $user->is_superuser = $is_superuser;

        if ($password!==null)
            $user->setPassword($password);
        else
            $user->setUnusablePassword();

        try {
            $user->save();
        } catch(IPF_ORM_Exception_Validator $e) {
            //print_r($e);
            // Note: you could also use $e->getInvalidRecords(). The direct way
            // used here is just more simple when you know the records you're dealing with.
            $userErrors = $user->getErrorStack();
            //$emailErrors = $user->email->getErrorStack();
            // Inspect user errors
            foreach($userErrors as $fieldName => $errorCodes) {
                echo "Error:".$fieldName;
                //print_r($errorCodes);
            }
        }
        return $user;
    }

    function setUnusablePassword(){
        $this->password = UNUSABLE_PASSWORD;
    }

    static function SetPassword2($raw_password){
        $salt = IPF_Utils::randomString(5);
        return 'sha1:'.$salt.':'.sha1($salt.$raw_password);
    }
    
    function setPassword($raw_password){
        $this->password = self::SetPassword2($raw_password);
    }

    function checkPassword($password){
        if ( ($this->password=='') || ($this->password==User::UNUSABLE_PASSWORD) )
            return false;
        list($algo, $salt, $hash) = explode(':', $this->password);
        if ($hash == $algo($salt.$password))
            return true;
        else
            return false;
    }

    function isAnonymous()
    {
        if (0===(int)$this->id)
            return true;
        return false;
    }

    function checkCreditentials($username, $password)
    {
        $user = $this->getTable()->findOneByUsername($username);
        if ($user === false) {
            return false;
        }
        if ($user->is_active and $user->checkPassword($password)) {
            return $user;
        }
        return false;
    }
}

IPF_Admin_Model::register('User','AdminUser');
