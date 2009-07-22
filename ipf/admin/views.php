<?php

function IPF_Admin_Views_Index($request, $match){
    $ca = IPF_Admin_App::checkAdminAuth($request);
    if ($ca!==true) return $ca;

    $apps = array();
    $app_list = new IPF_Template_ContextVars();
    foreach (IPF_Project::getInstance()->appList() as $app){
        if (count($app->modelList())>0){
            $models = new IPF_Template_ContextVars();
            $models_found = false;
            foreach($app->modelList() as $m){

                $ma = IPF_Admin_Model::getModelAdmin($m);
                if ($ma!==null){
                    $perms = $ma->getPerms($request);
                    if (array_search('view', $perms)!==false){
                        $models[] = new IPF_Template_ContextVars(array(
                            'name'=>$ma->verbose_name(),
                            'path'=>strtolower($m),
                            'perms'=>$perms,
                        ));
                        $models_found = true;
                    }
                }
            }
            if ($models_found){
                $app_list[$app->getName()] = new IPF_Template_ContextVars(array(
                    'name' => $app->getTitle(),
                    'path' => $app->getSlug(),
                    'additions' => $app->getAdditions(),
                    'models' => $models,
                ));
            }
        }
    }

    $admin_log = IPF_ORM_Query::create()
        ->select("*")
        ->from('AdminLog')
        ->orderby('created_at desc')
        ->limit(10)
        ->execute();

    $context = array(
        'page_title' => __('Site Administration'),
        'app_list' => $app_list,
        'admin_log' => $admin_log,
        'admin_title' => IPF::get('admin_title'),
    );
    return IPF_Shortcuts::RenderToResponse('admin/index.html', $context, $request);
}



function IPF_Admin_Views_ListItems($request, $match){
    $ca = IPF_Admin_App::checkAdminAuth($request);
    if ($ca!==true) return $ca;

    $lapp = $match[1];
    $lmodel = $match[2];
    foreach (IPF_Project::getInstance()->appList() as $app){
        foreach($app->modelList() as $m){
            if (strtolower($m)==$lmodel){
                $ma = IPF_Admin_Model::getModelAdmin($m);
                if ($ma===null)
                    return new IPF_HTTP_Response_NotFound();
                return $ma->ListItems($request, $lapp, $lmodel);
            }
        }
    }
}

function IPF_Admin_Views_Reorder($request, $match){
    $ca = IPF_Admin_App::checkAdminAuth($request);
    if ($ca!==true) return $ca;

    if ($request->method != 'POST')
        return new IPF_HTTP_Response_NotFound();

    if (!isset($request->POST['ids']))
        return new IPF_HTTP_Response_NotFound();

        if (!isset($request->POST['prev_ids']))
        return new IPF_HTTP_Response_NotFound();

    if (!isset($request->POST['drop_id']))
        return new IPF_HTTP_Response_NotFound();

    $lapp = $match[1];
    $lmodel = $match[2];

    foreach (IPF_Project::getInstance()->appList() as $app){
        foreach($app->modelList() as $m){
            if (strtolower($m)==$lmodel){
                $ma = IPF_Admin_Model::getModelAdmin($m);
                if ($ma===null)
                    return new IPF_HTTP_Response_NotFound();

                if (method_exists($ma, 'list_order'))
                    $ord_field = $ma->list_order();
                else
                    return new IPF_HTTP_Response_NotFound();

                $ids      = split(',',(string)$request->POST['ids']);
                $prev_ids = split(',',(string)$request->POST['prev_ids']);
                $drop_id  = $request->POST['drop_id'];

                $o = new $m();
                $o->_reorder($ids, $ord_field, $drop_id, $prev_ids);
                return new IPF_HTTP_Response_Json("Ok");
            }
        }
    }
    return new IPF_HTTP_Response_Json("Cannot find model");
}

function IPF_Admin_Views_EditItem($request, $match){
    $ca = IPF_Admin_App::checkAdminAuth($request);
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
    $ca = IPF_Admin_App::checkAdminAuth($request);
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
    $ca = IPF_Admin_App::checkAdminAuth($request);
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
    $ca = IPF_Admin_App::checkAdminAuth($request);
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
                    'admin_title' => IPF::get('admin_title'),

                );
                return IPF_Shortcuts::RenderToResponse('admin/changepassword.html', $context, $request);
            }
        }
    }
}


function IPF_Admin_Views_Login($request, $match){
    $success_url = '';
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
       'page_title' => IPF::get('admin_title'),
       'form' => $form,
       'admin_title' => IPF::get('admin_title'),
    );
    return IPF_Shortcuts::RenderToResponse('admin/login.html', $context, $request);
}

function IPF_Admin_Views_Logout($request, $match){
    IPF_Auth_App::logout($request);
    $context = array(
       'page_title' => IPF::get('admin_title'),
       'admin_title' => IPF::get('admin_title'),
    );
    return IPF_Shortcuts::RenderToResponse('admin/logout.html', $context, $request);
}

function IPF_Admin_Views_FileBrowser($request, $match){
    $ca = IPF_Admin_App::checkAdminAuth($request);
    if ($ca!==true) return $ca;
    
    $curr_dir = urldecode(substr($match[1],1));
    $upload_path = IPF::get('editor_upload_path','');
    if ($upload_path=='')
        $upload_path = IPF::get('upload_path','');
    $upload_url = IPF::get('editor_upload_url','');
    if ($upload_url=='')
        $upload_url = IPF::get('upload_url','');

    $dir = $upload_path.$curr_dir;

    if ($request->method=="GET"){
    	if (@$request->GET['delete']){
    		$del = $dir.$request->GET['delete'];
    		@IPF_Utils::removeDirectories($del);
    	}
    }
    
    
    if ($request->method=="POST"){
    	if (@$request->POST['new_folder']!='')
    		@mkdir($dir.$request->POST['new_folder']);

    	if (@$_FILES['file']){
			$uploadfile = $dir . basename($_FILES['file']['name']);
			@move_uploaded_file($_FILES['file']['tmp_name'], $uploadfile); 
    	}
    }
    
    $dirs = array();
    $files = array();
    if ($dh = @opendir($dir)) {
        while (($file = readdir($dh)) !== false) {
            if ($file=='.')
        		continue;
        	if (($curr_dir=='') && ($file=='..'))
        		continue;
        	if (filetype($dir . $file)=='dir')
        		$dirs[] = array('name'=>$file);
        	else{
        		
        		$sx = getimagesize($dir.$file);
        		if ($sx){
        			$image = '1';
        			$type = str_replace('image/','',$sx['mime']).' '.$sx[0].'x'.$sx[1];
        			if ($sx[0]<=200){
	        			$zw = $sx[0];
	        			$zh = $sx[1];
        			}
        			else {
	        			$zw = 200;
	        			$prop = (float)$sx[1] / (float)$sx[0];
	        			$zh = (int)(200.0 * $prop);
        			}
        		}
        		else {
        			$image = '0';
        			$type = 'binary';
        			$zw = 200;
        			$zw = 150;
        		}
        		$files[] = array('name'=>$file, 'image'=>$image, 'type'=>$type, 'zw'=>$zw, 'zh'=>$zh, 'size'=>filesize($dir . $file));
        	}
        }
        closedir($dh);
    }
    
    $pth = split(DIRECTORY_SEPARATOR,$curr_dir);
    $path = array();
    $cd = '/admin/filebrowser/';
    foreach($pth as $p){
    	$cd.=$p.DIRECTORY_SEPARATOR;
    	$path[] = array('cd'=>$cd, 'name'=>$p);
    }
        
    $context = array(
        'page_title' => __('File Browser'),
        'dirs' => $dirs,
        'files' => $files,
    	'path' => $path,
        'upload_url' => $upload_url,
    	'curr_dir' => $curr_dir,
    );
    return IPF_Shortcuts::RenderToResponse('admin/filebrowser.html', $context, $request);
}
