<?php

abstract class IPF_Template_Environment
{
    abstract public function loadTemplateFile($filename);

    abstract public function getCompiledTemplateName($template);

    // Dictionary of allowed tags (tag name => class)
    public $tags = array();

    public function isTagAllowed($name)
    {
        return isset($this->tags[$name]);
    }

    public function getTag($name)
    {
        if (isset($this->tags[$name]))
            return $this->tags[$name];
        else
            throw new IPF_Exception_Template('Tag '.$name.' is not defined.');
    }

    // Dictionary of modifiers (modifier name => function)
    public $modifiers = array(
        'upper'       => 'strtoupper',
        'lower'       => 'strtolower',
        'escxml'      => 'htmlspecialchars',
        'escape'      => 'IPF_Template_Modifier::escape',
        'strip_tags'  => 'strip_tags',
        'escurl'      => 'rawurlencode',
        'capitalize'  => 'ucwords',
        'debug'       => 'print_r', // Not var_export because of recursive issues.
        'fulldebug'   => 'var_export',
        'count'       => 'count',
        'nl2br'       => 'nl2br',
        'trim'        => 'trim',
        'unsafe'      => 'IPF_Template_SafeString::markSafe',
        'safe'        => 'IPF_Template_SafeString::markSafe',
        'date'        => 'IPF_Template_Modifier::dateFormat',
        'time'        => 'IPF_Template_Modifier::timeFormat',
        'floatformat' => 'IPF_Template_Modifier::floatFormat',
        'limit_words' => 'IPF_Template_Modifier::limitWords',
        'limit_chars' => 'IPF_Template_Modifier::limitCharacters',
    );

    public function hasModifier($name)
    {
        return isset($this->modifiers[$name]);
    }

    public function getModifier($name)
    {
        if (isset($this->modifiers[$name]))
            return $this->modifiers[$name];
        else
            throw new IPF_Exception_Template('Modifier '.$name.' is not defined.');
    }
}

