<?php

abstract class IPF_Template
{
    protected $environment;

    public function __construct($environment)
    {
        $this->environment = $environment;
    }

    abstract public function __toString();

    abstract protected function content();

    public function compile()
    {
        $compiler = new IPF_Template_Compiler($this->content(), $this->environment);
        return $compiler->getCompiledTemplate();
    }

    public function render($c=null)
    {
        $compiled_template = $this->environment->getCompiledTemplateName($this);
        ob_start();
        $t = $c;
        try {
            include $compiled_template;
        } catch (Exception $e) {
            ob_clean();
            throw $e;
        }
        $a = ob_get_contents();
        ob_end_clean();
        return $a;
    }
}

function IPF_Template_dateFormat($date, $format='%b %e, %Y')
{
    if (substr(PHP_OS,0,3) == 'WIN') {
        $_win_from = array ('%e',  '%T',       '%D');
        $_win_to   = array ('%#d', '%H:%M:%S', '%m/%d/%y');
        $format	= str_replace($_win_from, $_win_to, $format);
    }
    $date = date('Y-m-d H:i:s', strtotime($date.' GMT'));
    return strftime($format, strtotime($date));
}

function IPF_Template_timeFormat($time, $format='Y-m-d H:i:s')
{
    return date($format, $time);
}

function IPF_Template_floatFormat($number, $decimals=2, $dec_point='.', $thousands_sep=',')
{
    return number_format($number, $decimals, $dec_point, $thousands_sep);
}

