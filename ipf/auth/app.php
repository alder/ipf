<?php

// preload permission model
require_once(dirname(__FILE__) . '/models/_generated/BasePermission.php');
require_once(dirname(__FILE__) . '/models/Permission.php');

class IPF_Auth_App extends IPF_Application
{
    public function __construct()
    {
        parent::__construct(array(
            'models' => self::ArePermissionsEnabled() ? array('User', 'Role') : array('User'),
        ));
    }
    
    static function login($request, $user){
        $request->user = $user;
        $request->session->clear();
        $request->session->setData('login_time', gmdate('Y-m-d H:i:s'));
        $user->save();
    }

    public function getTitle(){
        return 'User Accounts';
    }

    static function logout($request){
        $request->user = new User();
        $request->session->clear();
        $request->session->setData('logout_time', gmdate('Y-m-d H:i:s'));
    }
    
    static function createPermissionsFromModels(array $pathesToModels)
    {
        $baseAdmin  = new IPF_Admin_Model();
        $basePerms  = $baseAdmin->getPerms();
        $permsTable = IPF_ORM::getTable('Permission');
        $appList    = IPF_Project::getInstance()->appList();
        
        $permissions = array();

        foreach ($pathesToModels as $path)
        {
            foreach (IPF_ORM::filterInvalidModels(IPF_ORM::loadModels($path)) as $modelName)
            {
                $adminModel = IPF_Admin_Model::getModelAdmin($modelName);
                
                if ($adminModel)
                {
                    $perms = method_exists($adminModel, 'getPerms') ? $adminModel->getPerms(null) : $basePerms;

                    foreach ($appList as $app)
                    {
                        if (in_array($modelName, $app->modelList()) && (!method_exists($app, 'NoPermsFor') || !in_array($modelName, $app->NoPermsFor())))
                        {
                            foreach ($perms as $permName)
                                $permissions[] = get_class($app).'|'.$modelName.'|'.$permName;
                        }
                    }
                }
            }
        }
        
        print "COLLECTED PERMS:\n----\n".implode("\n", $permissions)."\n----\n";

        if (count($permissions))
        {
            $existingPerms = array();
                   
            foreach ($permsTable->findAll() as $model)
                $existingPerms[] = $model->name;
                
            print "EXISTING PERMS:\n----\n".implode("\n",$existingPerms)."\n----\n";

            if (count($existingPerms))
            {
                $toDel = array_diff($existingPerms, $permissions);
                
                print "2DEL:\n----\n".implode("\n",$toDel)."\n----\n";

                if (count($toDel))
                {
                    $permsTable->createQuery()
                        ->delete()
                        ->where("name in ('".implode("','", $toDel)."')")
                        ->execute();
                }

                $toAdd = array_diff($permissions, $existingPerms);
            }
            else    // first time
            {
                // *** FIX: previously, the following models haven't "onDelete: CASCADE" constrain ***
                print "DROP RolePermission, UserRole, UserPermission\n";
                $export = IPF_ORM_Manager::connection()->export;
                $export->dropTable(IPF_ORM::getTable('RolePermission')->getTableName());
                $export->dropTable(IPF_ORM::getTable('UserRole')->getTableName());
                $export->dropTable(IPF_ORM::getTable('UserPermission')->getTableName());
                $auth_app = new IPF_Auth_App();
                $auth_app->createTablesFromModels();
                // *** FIX ***

                $toAdd = $permissions;
            }
            
            print "2ADD:\n----\n".implode("\n",$toAdd)."\n----\n";
            
            foreach ($toAdd as $name)
            {
                $model = new Permission();
                $model->name = $name;
                $model->save();
            }
        }
        else
        {
            print "REMOVE ALL\n";
        
            $permsTable->createQuery()->delete()->execute();   // no women, no cry...
        }
    }
    
    static function ArePermissionsEnabled()
    {
        return IPF_ORM_Query::create()->from('Permission')->count() ? true : false;
    }
    
    static function checkPermissions($request, $app, $modelName, array $perms)
    {
        $count = count($perms);
        
        if (!$count)
            return array();
  
        $permissions = array_combine($perms, array_fill(0, $count, false));
  
        if ($request->user->isAnonymous() || !($request->user->is_staff || $request->user->is_superuser))
            return $permissions;
            
        if ($request->user->is_superuser || !self::ArePermissionsEnabled())
            return array_combine($perms, array_fill(0, $count, true));
            
        $user_permissions = array();
        
        foreach ($request->user->Permissions as $up)
            $user_permissions[] = $up->name;
            
        foreach ($request->user->Roles as $role)
        {
            foreach ($role->Permissions as $rp)
            {
                if (!in_array($rp->name, $user_permissions))
                    $user_permissions[] = $rp->name;
            }
        }
        
        if (!count($user_permissions))
            return $permissions;
            
        $prefix = get_class($app).'|'.$modelName.'|';
            
        foreach ($permissions as $permName=>&$permValue)
        {
            $permissionValue = $prefix.$permName;
            
            foreach ($user_permissions as $user_permission_value)
            {
                if ($permissionValue == $user_permission_value)
                {
                    $permValue = true;
                    break;
                }
            }
        }

        return $permissions;
    }
    
    static function GetHumanNameOfPermission($permissionName)
    {
        $parts   = explode('|', $permissionName);
        $appName = $parts[0];
        $app     = new $appName();
        $admin   = IPF_Admin_Model::getModelAdmin($parts[1]);
        
        return $app->getTitle().' | '.$admin->verbose_name().' | '.ucfirst($parts[2]);
    }    
}
