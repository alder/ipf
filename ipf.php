<?php

// Lazy ClassLoader
function __autoload( $class_name ){
    $s = '';                                                                    
    $a =  explode( '_', $class_name );
    foreach( $a as &$folder ){                                                  
        if ( $s!='' )                                                           
            $s .= '/';                                                  
        $s .= strtolower( $folder );                                            
    }
    require_once($s.'.php');
}

final class IPF{
    
    private static $settings = array();
    
    private static function applySettings($settings){
        foreach($settings as $key=>$val){
            IPF::$settings[strtolower($key)] = $val;
        }
    }

    private static function loadSettings(){
        $settings_file = IPF::$settings['project_path'].DIRECTORY_SEPARATOR.'settings.php';
        IPF::$settings['settings_file'] = $settings_file;
        
        if (file_exists($settings_file))
            IPF::applySettings(require $settings_file);
        else
            throw new IPF_Exception_Settings('Configuration file does not exist: '.$settings_file);

        $settings_local_file = IPF::$settings['project_path'].DIRECTORY_SEPARATOR.'settings_local.php';
        if (file_exists($settings_local_file))
            IPF::applySettings(require $settings_local_file);
        
        if (!isset(IPF::$settings['dsn']))
            throw new IPF_Exception_Settings('Please specify DSN in settings file');
        else
            if (!is_string(IPF::$settings['dsn']))
                throw new IPF_Exception_Settings('DSN must be string');

        if (!isset(IPF::$settings['tmp']))
            IPF::$settings['tmp'] = '/tmp';
        else
            if (!is_string(IPF::$settings['tmp']))
                throw new IPF_Exception_Settings('TMP must be string');

        if (!isset(IPF::$settings['applications']))
            throw new IPF_Exception_Settings('Please specify application list');
        if (!is_array(IPF::$settings['applications']))
            throw new IPF_Exception_Settings('applications must be array of string');

        if (!isset(IPF::$settings['template_dirs'])){
            IPF::$settings['template_dirs'] = array();
            IPF::$settings['template_dirs'][] = IPF::$settings['project_path'].DIRECTORY_SEPARATOR.'templates';
            if (array_search('IPF_Admin',IPF::$settings['applications']))
                IPF::$settings['template_dirs'][] = IPF::$settings['ipf_path'].DIRECTORY_SEPARATOR.'ipf'.DIRECTORY_SEPARATOR.'admin'.DIRECTORY_SEPARATOR.'templates';
        }
        
        if (!isset(IPF::$settings['debug'])){
            IPF::$settings['debug'] = false;
        }

        if (!isset(IPF::$settings['app_base'])){
            IPF::$settings['app_base'] = '/index.php';
        }

        if (!isset(IPF::$settings['append_slash'])){
            IPF::$settings['append_slash'] = true;
        }

        if (!isset(IPF::$settings['media_url'])){
            IPF::$settings['media_url'] = '/media/';
        }

        if (!isset(IPF::$settings['admin_media_url'])){
            IPF::$settings['admin_media_url'] = '/ipf/ipf/admin/media/';
        }

        if (!isset(IPF::$settings['urls'])){
            throw new IPF_Exception_Settings('Specify site url routes');
        }

        if (!isset(IPF::$settings['session_cookie_id'])){
            IPF::$settings['session_cookie_id'] = 'sessionid';
        }

        if (!isset(IPF::$settings['dir_permission']))
            IPF::$settings['dir_permission'] = 0770;

        if (!isset(IPF::$settings['file_permission']))
            IPF::$settings['file_permission'] = 0660;

        //print_r(IPF::$settings);
    }

    public static function boot($ipf_path, $project_path)
    {
        IPF::$settings['ipf_path']=$ipf_path;
        IPF::$settings['project_path']=$project_path;
        try{
            IPF::loadSettings();
        }catch(IPF_Exception_Settings $e){
            die('Setting Error: '.$e->getMessage()."\n");
        }
    }

	private function __construct(){}
	private function __clone(){}
	
	public static function get($name,$default=null){
	    if (isset(IPF::$settings[$name]))
	        return IPF::$settings[$name];
	    return $default;
	}
	
    public static function loadFunction($function)
    {
        if (function_exists($function)) {
            return;
        }
        $elts = explode('_', $function);
        array_pop($elts);
        $file = strtolower(implode(DIRECTORY_SEPARATOR, $elts)).'.php';
        @include $file;
        if (!function_exists($function))
            throw new IPF_Exception('Impossible to load the function: '.$function.' in '.$file);
    }

    public static function factory($model, $params=null)
    {
        if ($params !== null)
            return new $model($params);
        return new $model();
    }

    public static function getUploadPath($params=array()){
        $upload_path = IPF::get('upload_path', '/tmp');
        if (isset($params['upload_path'])) {
            $upload_path = $params['upload_path'];
        }
        return $upload_path;
    }
}

function __($str)
{
    $t = trim($str);
    return $t;
}
