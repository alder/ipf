<?php

class IPF_Admin_App extends IPF_Application{
    public function __construct(){
        parent::__construct(array(
           'models'=>array('AdminLog')
        ));
    }
    public static function urls(){
        return array(
            array('regex'=>'$#', 'func'=>'IPF_Admin_Views_Index'),
            array('regex'=>'([\w\_\-]+)/([\w\_\-]+)/$#i', 'func'=>'IPF_Admin_Views_ListItems'),
            array('regex'=>'([\w\_\-]+)/([\w\_\-]+)/add/$#i', 'func'=>'IPF_Admin_Views_AddItem'),
            array('regex'=>'([\w\_\-]+)/([\w\_\-]+)/([\w\_\-]+)/$#i', 'func'=>'IPF_Admin_Views_EditItem'),
            array('regex'=>'([\w\_\-]+)/([\w\_\-]+)/([\w\_\-]+)/delete/$#i', 'func'=>'IPF_Admin_Views_DeleteItem'),
            array('regex'=>'auth/user/([\w\_\-]+)/password/$#i', 'func'=>'IPF_Admin_Views_ChangePassword'),
            array('regex'=>'login/$#i', 'func'=>'IPF_Admin_Views_Login'),
            array('regex'=>'logout/$#i', 'func'=>'IPF_Admin_Views_Logout'),
        );
    }

	static function checkAdminAuth($request){
	    $ok = true;
	    if ($request->user->isAnonymous())
	        $ok = false;
	    elseif ( (!$request->user->is_staff) && (!$request->user->is_superuser) )
	        $ok = false;

	    if ($ok)
	        return true;
	    else
	        return new IPF_HTTP_Response_Redirect(IPF_HTTP_URL_urlForView('IPF_Admin_Views_Login'));
	}

}