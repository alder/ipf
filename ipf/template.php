<?php

class IPF_Template
{
    public $tpl = '';
    public $folders = array();
    public $cache = '';
    public $compiled_template = '';
    public $template_content = '';
    public $context = null;

    function __construct($template, $folders=null, $cache=null)
    {
        $this->tpl = $template;
        if (is_null($folders)) {
            $this->folders = IPF::get('template_dirs');
        } else {
            $this->folders = $folders;
        }
        if (is_null($cache)) {
            $this->cache = IPF::get('tmp');
        } else {
            $this->cache = $cache;
        }

    }

    function render($c=null)
    {
        $this->compiled_template = $this->getCompiledTemplateName();
        if (!file_exists($this->compiled_template) or IPF::get('debug')) {
            $compiler = new IPF_Template_Compiler($this->tpl, $this->folders);
            $this->template_content = $compiler->getCompiledTemplate();
            $this->write();
        }
        if (is_null($c)) {
            $c = new IPF_Template_Context();
        }
        $this->context = $c;
        ob_start();
        $t = $c;
        try {
            include $this->compiled_template;
        } catch (Exception $e) {
            ob_clean();
            throw $e;
        }
        $a = ob_get_contents();
        ob_end_clean();
        return $a;
    }

    function getCompiledTemplateName()
    {
        $_tmp = var_export($this->folders, true);
        return $this->cache.'/IPF_Template-'.md5($_tmp.$this->tpl).'.phps';
    }

    function write()
    {
        $fp = @fopen($this->compiled_template, 'a');
        if ($fp !== false) {
            flock($fp, LOCK_EX);
            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, $this->template_content, strlen($this->template_content));
            flock($fp, LOCK_UN);
            fclose($fp);
            @chmod($this->compiled_template, 0777);
            return true;
        } else {
            throw new IPF_Exception_Template(sprintf(__('Cannot write the compiled template: %s'), $this->compiled_template));
        }
        return false;
    }

}

function IPF_Template_unsafe($string)
{
    return new IPF_Template_SafeString($string, true);
}

function IPF_Template_htmlspecialchars($string)
{
    return htmlspecialchars((string)$string, ENT_COMPAT, 'UTF-8');
}

function IPF_Template_dateFormat($date, $format='%b %e, %Y')
{
    if (substr(PHP_OS,0,3) == 'WIN') {
        $_win_from = array ('%e',  '%T',	   '%D');
        $_win_to   = array ('%#d', '%H:%M:%S', '%m/%d/%y');
        $format	= str_replace($_win_from, $_win_to, $format);
    }
    $date = date('Y-m-d H:i:s', strtotime($date.' GMT'));
    return strftime($format, strtotime($date));
}

function IPF_Template_timeFormat($time, $format='Y-m-d H:i:s'){
    return date($format, $time);
}

function IPF_Template_floatFormat($number, $decimals=2, $dec_point='.', $thousands_sep=' '){
    return number_format($number, $decimals, $dec_point, $thousands_sep);
}

function IPF_Template_safeEcho($mixed, $echo=true)
{
    if (!is_object($mixed) or 'IPF_Template_SafeString' !== get_class($mixed)) {
        if ($echo) {
            echo htmlspecialchars((string) $mixed, ENT_COMPAT, 'UTF-8');
        } else {
            return htmlspecialchars((string) $mixed, ENT_COMPAT, 'UTF-8');
        }
    } else {
        if ($echo) {
            echo $mixed->value;
        } else {
            return $mixed->value;
        }
    }
}
