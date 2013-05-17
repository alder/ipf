<?php

function IPF_Autoload($class_name)
{
    $filename = strtolower(str_replace('_', '/', $class_name)) . '.php';
    if (file_exists($filename)) {
        require_once $filename;
        return;
    }
    foreach (explode(PATH_SEPARATOR, get_include_path()) as $dir) {
        $path = $dir . DIRECTORY_SEPARATOR . $filename;
        if (file_exists($path)) {
            require_once($path);
            break;
        }
    }
}

spl_autoload_register('IPF_Autoload');

final class IPF
{
    private static $settings = array();

    private static function applySettings($settings)
    {
        foreach($settings as $key=>$val)
            IPF::$settings[strtolower($key)] = $val;
    }

    private static function loadSettings()
    {
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

        if (!isset(IPF::$settings['admin_title'])){
            IPF::$settings['admin_title'] = 'IPF Administration';
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

        if (!isset(IPF::$settings['tiny_mce_url'])){
            IPF::$settings['tiny_mce_url'] = '/media/tiny_mce/';
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
            IPF::$settings['dir_permission'] = 0777;

        if (!isset(IPF::$settings['file_permission']))
            IPF::$settings['file_permission'] = 0666;

        if (!isset(IPF::$settings['time_zone'])){
            IPF::$settings['time_zone'] = 'America/Toronto';
        }
    }

    public static function boot($ipf_path, $project_path)
    {
        IPF::$settings['ipf_path']=$ipf_path;
        IPF::$settings['project_path']=$project_path;
        try {
            IPF::loadSettings();
            date_default_timezone_set(IPF::$settings['time_zone']);            
        } catch(IPF_Exception_Settings $e) {
            die('Setting Error: '.$e->getMessage()."\n");
        }
    }

    private function __construct() {}
    private function __clone() {}

    public static function get($name, $default=null)
    {
        if (isset(IPF::$settings[$name]))
            return IPF::$settings[$name];
        return $default;
    }

    public static function loadFunction($function)
    {
        if (function_exists($function))
            return;
        if (preg_match('/^(\w+)::\w+$/', $function, $m)) {
            IPF_Autoload($m[1]);
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

    public static function getUploadPath()
    {
        return IPF::get('upload_path', '/tmp');
    }

    public static function getUploadUrl()
    {
        return IPF::get('upload_url', '/media/upload');
    }
}

function __($str)
{
    $t = trim($str);
    return $t;
}

