<?php

abstract class IPF_Template
{
    protected $environment;

    public function __construct(IPF_Template_Environment $environment)
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

