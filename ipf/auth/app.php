<?php

class IPF_Auth_App extends IPF_Application{

    public function __construct(){
        parent::__construct(array(
            'models'=>array('User','Role')
        ));
    }
    
    static function login($request, $user){
        $request->user = $user;
        $request->session->clear();
        $request->session->setData('login_time', gmdate('Y-m-d H:i:s'));
        $user->last_login = gmdate('Y-m-d H:i:s');
        $user->save();
    }

    static function logout($request){
        $request->user = new User();
        $request->session->clear();
        $request->session->setData('logout_time', gmdate('Y-m-d H:i:s'));
    }
}