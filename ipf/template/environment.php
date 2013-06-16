<?php

class IPF_Template_Environment
{
    public $folders = array();
    public $cache = '';

    public function __construct($folders, $cache)
    {
        $this->folders = $folders;
        $this->cache = $cache;
    }

    public function getCompiledTemplateName($template)
    {
        $_tmp = var_export($this->folders, true);
        $filename = $this->cache.'/IPF_Template-'.md5($_tmp.(string)$template).'.phps';
        if (IPF::get('debug') or !file_exists($filename)) {
            $this->write($filename, $template->compile());
        }
        return $filename;
    }

    private function write($filename, $content)
    {
        $fp = @fopen($filename, 'a');
        if ($fp !== false) {
            flock($fp, LOCK_EX);
            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, $content, strlen($content));
            flock($fp, LOCK_UN);
            fclose($fp);
            @chmod($filename, 0777);
            return true;
        } else {
            throw new IPF_Exception_Template(sprintf(__('Cannot write the compiled template: %s'), $filename));
        }
        return false;
    }

    private static $defaultEnvironment = null;

    public static function getDefault()
    {
        if (!self::$defaultEnvironment)
            self::$defaultEnvironment = new IPF_Template_Environment(IPF::get('template_dirs'), IPF::get('tmp'));
        return self::$defaultEnvironment;
    }
}

