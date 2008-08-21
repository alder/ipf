<?php

class AdminUser extends IPF_Admin_Model{
    public function list_display(){return array('username', 'email', 'is_active', 'is_staff', 'is_superuser', 'created_at');}
    public function fields(){return array('username','password','email', 'first_name', 'last_name', 'is_active', 'is_staff', 'is_superuser');}
    
    protected function _setupForm(&$form){
        $form->fields['username']->help_text = 'Required. 32 characters or fewer. Alphanumeric characters only (letters, digits and underscores).';
        $form->fields['password']->help_text = "Use '[algo]$[salt]$[hexdigest]' or use the <a href=\"password/\">change password form</a>.";
    }

    public function AddItem($request, $lapp, $lmodel){
        $model = new $this->modelName();
        if ($request->method == 'POST'){
            $form = new IPF_Auth_Forms_UserCreation($request->POST);
            if ($form->isValid()) {
                $user = User::createUser(
                    $form->cleaned_data['username'],
                    $form->cleaned_data['password1'],
                    $form->cleaned_data['email'],
                    $form->cleaned_data['first_name'],
                    $form->cleaned_data['last_name'],
                    $form->cleaned_data['is_active'],
                    $form->cleaned_data['is_staff'],
                    $form->cleaned_data['is_superuser']
                );
                $url = IPF_HTTP_URL_urlForView('IPF_Admin_Views_ListItems', array($lapp, $lmodel));
                return new IPF_HTTP_Response_Redirect($url);
            }
        }
        else
            $form = new IPF_Auth_Forms_UserCreation();
        $context = array(
            'page_title'=>'Add '.$this->modelName, 
            'classname'=>$this->modelName,
            'form'=>$form,
            'lapp'=>$lapp,
            'lmodel'=>$lmodel,
        );
        return IPF_Shortcuts::RenderToResponse('admin/add.html', $context, $request);
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
    
    static function createUser($username, $password=null, $email=null, $first_name=null, $last_name=null, $is_active=false, $is_staff=false, $is_superuser=false){
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
                print_r($errorCodes);
            }
        }
        return $user;
    }

    function setUnusablePassword(){
        $this->password = UNUSABLE_PASSWORD;
    }
    
    function setPassword($raw_password){
        $salt = IPF_Utils::randomString(5);
        $this->password = 'sha1:'.$salt.':'.sha1($salt.$raw_password);
    }

    function checkPassword($password){
        if ( ($this->password=='') || ($this->password==User::UNUSABLE_PASSWORD) )
            return false;
        list($algo, $salt, $hash) = split(':', $this->password);
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
