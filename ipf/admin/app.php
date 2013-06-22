<?php

class IPF_Admin_App extends IPF_Application
{
    public function __construct()
    {
        parent::__construct(array(
           'models'=>array('AdminLog')
        ));
    }

    public static function urls()
    {
        return array(
            array('regex'=>'fb_rename/$#', 'func'=>'IPF_Admin_Views_FileBrowserRename'),
            array('regex'=>'filebrowser(.+)#', 'func'=>'IPF_Admin_Views_FileBrowser'),
            array('regex'=>'$#', 'func'=>'IPF_Admin_Views_Index'),
            array('regex'=>'([\w\_\-]+)/([\w\_\-]+)/$#i', 'func'=>'IPF_Admin_Views_ListItems'),
            array('regex'=>'([\w\_\-]+)/([\w\_\-]+)/reorder/$#i', 'func'=>'IPF_Admin_Views_Reorder'),
            array('regex'=>'([\w\_\-]+)/([\w\_\-]+)/add/$#i', 'func'=>'IPF_Admin_Views_AddItem'),
            array('regex'=>'([\w\_\-]+)/([\w\_\-]+)/([\w\_\-]+)/$#i', 'func'=>'IPF_Admin_Views_EditItem'),
            array('regex'=>'([\w\_\-]+)/([\w\_\-]+)/([\w\_\-]+)/delete/$#i', 'func'=>'IPF_Admin_Views_DeleteItem'),
            array('regex'=>'auth/user/([\w\_\-]+)/password/$#i', 'func'=>'IPF_Admin_Views_ChangePassword'),
            array('regex'=>'login/$#i', 'func'=>'IPF_Admin_Views_Login'),
            array('regex'=>'logout/$#i', 'func'=>'IPF_Admin_Views_Logout'),
        );
    }

    static function checkAdminAuth($request)
    {
        $ok = true;
        if ($request->user->isAnonymous())
            $ok = false;
        elseif ( (!$request->user->is_staff) && (!$request->user->is_superuser) )
            $ok = false;

        if ($ok)
            return true;
        else
            return new IPF_HTTP_Response_Redirect(IPF_HTTP_URL::urlForView('IPF_Admin_Views_Login'));
    }

    static function GetAppModelFromSlugs($lapp, $lmodel)
    {
        foreach (IPF_Project::getInstance()->appList() as $app) {
            if ($app->getSlug() == $lapp) {
                foreach($app->modelList() as $m) {
                    if (strtolower($m) == $lmodel)
                        return array('app' => $app, 'modelname' => $m);
                }
            }
        }
        return null;
    }
    
    static function GetAdminModelPermissions($adminModel, $request, $lapp, $lmodel)
    {
        $adminPerms = $adminModel->getPerms($request);
    
        if (!count($adminPerms))
            return false;

        $am = self::GetAppModelFromSlugs($lapp, $lmodel);
        
        if ($am === null)
            return false;
            
        $app = $am['app'];
        $m   = $am['modelname'];

        if ($m !== $adminModel->modelName)
            return false;
            
        $perms = array();
        
        foreach (IPF_Auth_App::checkPermissions($request, $app, $m, $adminPerms) as $permName=>$permValue) {
            if ($permValue)
                $perms[] = $permName;
        }
        
        return $perms;
    }

    public static function renderForm($form)
    {
        return $form->htmlOutput(
            '<div class="form-row"><div>%2$s %1$s%3$s%4$s</div></div>',
            '<div>%s</div>',
            '</div>',
            '<p class="help">%s</p>',
            true,
            '<div class="form-group-title">%s</div>',
            false);
    }
}

