<?php

final class IPF
{
    private static $settings = array(
        'app_base'          => '',
        'debug'             => false,
        'media_url'         => '/media/',
        'upload_url'        => '/media/upload/',
        'static_url'        => '/static/',
        'session_cookie_id' => 'sessionid',
        'dir_permission'    => 0777,
        'file_permission'   => 0666,
        'time_zone'         => 'America/Toronto',
    );

    private static function applySettings($settings)
    {
        foreach ($settings as $key => $val)
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

        if (!isset(IPF::$settings['dsn']) && !isset(IPF::$settings['database']))
            throw new IPF_Exception_Settings('Please specify database parameters or DSN in settings file');

        if (isset(IPF::$settings['database']) && !is_array(IPF::$settings['database']))
            throw new IPF_Exception_Settings('Database must be array with keys: driver, host, port (optional), database, username, password, options (optional)');

        if (isset(IPF::$settings['dsn']) && !is_string(IPF::$settings['dsn']))
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

        if (!isset(IPF::$settings['admin_title'])){
            IPF::$settings['admin_title'] = 'IPF Administration';
        }

        if (!isset(IPF::$settings['tiny_mce_url'])){
            IPF::$settings['tiny_mce_url'] =  IPF::$settings['static_url'] . 'admin/tiny_mce/';
        }

        if (!isset(IPF::$settings['urls'])){
            throw new IPF_Exception_Settings('Specify site url routes');
        }
    }

    private static function requestedFileExists()
    {
        $parts = explode('?', $_SERVER['REQUEST_URI'], 2);
        $path = $_SERVER['DOCUMENT_ROOT'] . urldecode($parts[0]);
        return is_file($path);
    }

    public static function setUp($project_path, $document_root, $vendor_path=null)
    {
        if (php_sapi_name() === 'cli-server' && IPF::requestedFileExists())
            return false;

        if (!$vendor_path)
              $vendor_path = $project_path . '/vendor';
        IPF_ClassLoader::getInstance($vendor_path);

        IPF::$settings['ipf_path'] = dirname(__FILE__);
        IPF::$settings['project_path'] = $project_path;
        IPF::$settings['document_root'] = $document_root;

        try {
            IPF::loadSettings();
            date_default_timezone_set(IPF::$settings['time_zone']);
        } catch (IPF_Exception_Settings $e) {
            die('Setting Error: '.$e->getMessage()."\n");
        }
        return true;
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

        if (preg_match('/^(\w+)::\w+$/', $function, $m))
            return; // nothing to do. autoloader will load a class.

        $elts = explode('_', $function);
        array_pop($elts);
        $file = '/' . strtolower(implode(DIRECTORY_SEPARATOR, $elts)).'.php';
        @include_once IPF::$settings['ipf_path'] . $file;
        @include_once IPF::$settings['project_path'] . $file;
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
        return IPF::get('document_root') . IPF::getUploadUrl();
    }

    public static function getUploadUrl()
    {
        return IPF::get('upload_url');
    }
}

class IPF_ClassLoader
{
    private $classMap;

    private static $loader = null;

    public static function getInstance($vendor_path)
    {
        if (self::$loader == null)
            self::$loader = new IPF_ClassLoader($vendor_path);
        return self::$loader;
    }

    private function __construct($vendor_path)
    {
        $includePathsFile = $vendor_path . '/composer/include_paths.php';
        if (is_file($includePathsFile)) {
            $includePaths = require $includePathsFile;
            array_push($includePaths, get_include_path());
            set_include_path(join(PATH_SEPARATOR, $includePaths));
        }

        $this->classMap = require $vendor_path . '/composer/autoload_classmap.php';

        spl_autoload_register(array($this, 'load'), true, true);
    }

    public function load($class)
    {
        if (isset($this->classMap[$class])) {
            require $this->classMap[$class];
        }
    }
}

function __($str)
{
    $t = trim($str);
    $tr = IPF::get('translations');
    if ($tr && array_key_exists($t, $tr))
        $t = $tr[$t];
    return $t;
}

