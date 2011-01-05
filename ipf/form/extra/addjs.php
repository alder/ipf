<?php

class IPF_Form_Extra_AddJs extends IPF_Form_Model
{
    protected $add_js = array();

    function initFields($extra=array())
    {
        parent::initFields($extra);

        $this->add_js[] = '<script language="javascript" type="text/javascript" src="'.IPF::get('admin_media_url').'js/extra/jquery.js"></script>';

        if (array_key_exists('add_js', $extra) && is_array($extra['add_js']))
            $this->add_js = array_merge($this->add_js, $extra['add_js']);
    }

    public function render_commonjs()
    {
        return implode('', $this->add_js);
    }
}