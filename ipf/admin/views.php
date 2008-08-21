<?php

function checkAdminAuth($request){
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

function IPF_Admin_Views_Index($request, $match){
    $ca = checkAdminAuth($request);
    if ($ca!==true) return $ca;
        
    $apps = array();
    $app_list = new IPF_Template_ContextVars();
    foreach (IPF_Project::getInstance()->appList() as $app){
        if (count($app->modelList())>0){
            $models = new IPF_Template_ContextVars();
            $models_found = false;
            foreach($app->modelList() as $m){
                if (IPF_Admin_Model::isModelRegister($m)){
                    $models[] = new IPF_Template_ContextVars(array('name'=>$m, 'path'=>strtolower($m)));
                    $models_found = true;
                }
            }
            if ($models_found){
                $app_list[$app->getName()] = new IPF_Template_ContextVars(array(
                    'name' => $app->getTitle(),
                    'path' => $app->getSlug(),
                    'models' => $models,
                ));
            }
        }
    }
    
    $admin_log = IPF_ORM_Query::create()->select("*")->from('AdminLog')->orderby('created_at desc')->limit(10)->execute();
    
    $context = array(
        'page_title' => __('Administration'), 
        'app_list' => $app_list,
        'admin_log' => $admin_log,
    );
    return IPF_Shortcuts::RenderToResponse('admin/index.html', $context, $request);
}

function IPF_Admin_Views_ListItems($request, $match){
    $ca = checkAdminAuth($request);
    if ($ca!==true) return $ca;

    $lmodel = $match[2];
    foreach (IPF_Project::getInstance()->appList() as $app){
        foreach($app->modelList() as $m){
            if (strtolower($m)==$lmodel){
                $ma = IPF_Admin_Model::getModelAdmin($m);
                if ($ma===null)
                    return new IPF_HTTP_Response_NotFound();
                return $ma->ListItems($request);
            }
        }
    }
}

function IPF_Admin_Views_EditItem($request, $match){
    $ca = checkAdminAuth($request);
    if ($ca!==true) return $ca;

    $lapp = $match[1];
    $lmodel = $match[2];
    $id = $match[3];
    foreach (IPF_Project::getInstance()->appList() as $app){
        foreach($app->modelList() as $m){
            if (strtolower($m)==$lmodel){
                $ma = IPF_Admin_Model::getModelAdmin($m);
                if ($ma===null)
                    return new IPF_HTTP_Response_NotFound();
                $o = new $m();
                $item = $o->getTable()->find($id);
                return $ma->EditItem($request, $lapp, $lmodel, &$item);
            }
        }
    }
}

function IPF_Admin_Views_DeleteItem($request, $match){
    $ca = checkAdminAuth($request);
    if ($ca!==true) return $ca;

    $lapp = $match[1];
    $lmodel = $match[2];
    $id = $match[3];
    foreach (IPF_Project::getInstance()->appList() as $app){
        foreach($app->modelList() as $m){
            if (strtolower($m)==$lmodel){
                $ma = IPF_Admin_Model::getModelAdmin($m);
                if ($ma===null)
                    return new IPF_HTTP_Response_NotFound();
                $o = new $m();
                $item = $o->getTable()->find($id);
                return $ma->DeleteItem($request, $lapp, $lmodel, &$item);
            }
        }
    }
}


function IPF_Admin_Views_AddItem($request, $match){
    $ca = checkAdminAuth($request);
    if ($ca!==true) return $ca;

    $lapp = $match[1];
    $lmodel = $match[2];
    foreach (IPF_Project::getInstance()->appList() as $app){
        foreach($app->modelList() as $m){
            if (strtolower($m)==$lmodel){
                $ma = IPF_Admin_Model::getModelAdmin($m);
                if ($ma===null)
                    return new IPF_HTTP_Response_NotFound();
                return $ma->AddItem($request, $lapp, $lmodel);
            }
        }
    }
}

function IPF_Admin_Views_ChangePassword($request, $match){
    $ca = checkAdminAuth($request);
    if ($ca!==true) return $ca;

    $lapp = 'auth';
    $lmodel = 'user';
    $id = $match[1];
    foreach (IPF_Project::getInstance()->appList() as $app){
        foreach($app->modelList() as $m){
            if (strtolower($m)==$lmodel){
                $ma = IPF_Admin_Model::getModelAdmin($m);
                if ($ma===null)
                    return new IPF_HTTP_Response_NotFound();
                $o = new $m();
                $user = $o->getTable()->find($id);

                if ($request->method == 'POST'){
                    $form = new IPF_Auth_Forms_ChangePassword($request->POST);
                    if ($form->isValid()) {
                        $user->setPassword($form->cleaned_data['password1']);
                        $user->save();
                        $url = IPF_HTTP_URL_urlForView('IPF_Admin_Views_ListItems', array($lapp, $lmodel));
                        return new IPF_HTTP_Response_Redirect($url);
                    }
                }
                else
                    $form = new IPF_Auth_Forms_ChangePassword();
                $context = array(
                    'page_title'=>'Change Password: '.$user->username, 
                    'classname'=>'User',
                    'object'=>$user,
                    'form'=>$form,
                    'lapp'=>$lapp,
                    'lmodel'=>$lmodel,
                );
                return IPF_Shortcuts::RenderToResponse('admin/changepassword.html', $context, $request);
            }
        }
    }
}


function IPF_Admin_Views_Login($request, $match){
    if (!empty($request->REQUEST['next']))
        $success_url = $request->REQUEST['next'];
    if (trim($success_url)=='')
        $success_url = IPF_HTTP_URL_urlForView('IPF_Admin_Views_Index');
        
    if ($request->method == 'POST') {
        $form = new IPF_Auth_Forms_Login($request->POST);
        if ($form->isValid()){
            $users = new User();
            if (false === ($user = $users->checkCreditentials($form->cleaned_data['username'], $form->cleaned_data['password']))) {
                $form->message = __('The login or the password is not valid. The login and the password are case sensitive.');
            } else {
                IPF_Auth_App::login($request, $user);
                return new IPF_HTTP_Response_Redirect($success_url);
            }
        }
    }
    else
        $form = new IPF_Auth_Forms_Login(array('next'=>$success_url));
    $context = array(
       'page_title' => __('IPF Administration'),
       'form' => $form,
    );
    return IPF_Shortcuts::RenderToResponse('admin/login.html', $context, $request);
}

function IPF_Admin_Views_Logout($request, $match){
    IPF_Auth_App::logout($request);
    $context = array(
       'page_title' => __('IPF Administration'),
    );
    return IPF_Shortcuts::RenderToResponse('admin/logout.html', $context, $request);
}
