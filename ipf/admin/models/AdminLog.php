<?php

class AdminLog extends BaseAdminLog
{
    const ADDITION  = 1;
    const CHANGE    = 2;
    const DELETION  = 3;
    
    public static function logAction($request, $object, $action_flag, $message=''){
        $log = new AdminLog();
        $log->username = $request->user->username;
        $log->user_id = $request->user->id;
        $log->object_id = $object->id;
        $log->object_class = get_class($object);
        $log->object_repr = (string)$object;
        $log->action_flag = $action_flag;
        $log->change_message = $message;
        $log->save();
    }
    
    public function is_addition(){
        if ($this->action_flag==AdminLog::ADDITION)
            return true;
        return false;
    }

    public function is_change(){
        if ($this->action_flag==AdminLog::CHANGE)
            return true;
        return false;
    }

    public function is_deletion(){
        if ($this->action_flag==AdminLog::DELETION)
            return true;
        return false;
    }
    
    public function GetAdminUrl(){
        return IPF_HTTP_URL_urlForView('IPF_Admin_Views_Index').IPF_Utils::appLabelByModel($this->object_class).'/'.strtolower($this->object_class).'/'.$this->object_id.'/';
    }
    
}

