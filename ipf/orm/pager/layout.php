<?php

class IPF_ORM_Pager_Layout
{
    private $_pager;
    private $_pagerRange;
    private $_template;
    private $_selectedTemplate;
    private $_separatorTemplate;
    private $_urlMask;
    private $_maskReplacements = array();

    public function __construct($pager, $pagerRange, $urlMask)
    {
        $this->_setPager($pager);
        $this->_setPagerRange($pagerRange);
        $this->_setUrlMask($urlMask);

        $this->setTemplate('[<a href="{%url}">{%page}</a>]');
        $this->setSelectedTemplate('');
        $this->setSeparatorTemplate('');
    }

    public function getPager()
    {
        return $this->_pager;
    }

    protected function _setPager($pager)
    {
        $this->_pager = $pager;
    }

    public function execute($params = array(), $hydrationMode = IPF_ORM::FETCH_RECORD)
    {
        return $this->getPager()->execute($params, $hydrationMode);
    }

    public function getPagerRange()
    {
        return $this->_pagerRange;
    }

    protected function _setPagerRange($pagerRange)
    {
        $this->_pagerRange = $pagerRange;
        $this->getPagerRange()->setPager($this->getPager());
    }

    public function getUrlMask()
    {
        return $this->_urlMask;
    }

    protected function _setUrlMask($urlMask)
    {
        $this->_urlMask = $urlMask;
    }

    public function getTemplate()
    {
        return $this->_template;
    }

    public function setTemplate($template)
    {
        $this->_template = $template;
    }

    public function getSelectedTemplate()
    {
        return $this->_selectedTemplate;
    }

    public function setSelectedTemplate($selectedTemplate)
    {
        $this->_selectedTemplate = $selectedTemplate;
    }

    public function getSeparatorTemplate()
    {
        return $this->_separatorTemplate;
    }

    public function setSeparatorTemplate($separatorTemplate)
    {
        $this->_separatorTemplate = $separatorTemplate;
    }

    public function addMaskReplacement($oldMask, $newMask, $asValue = false)
    {
        if (($oldMask = trim($oldMask)) != 'page_number') {
            $this->_maskReplacements[$oldMask] = array(
                'newMask' => $newMask,
                'asValue' => ($asValue === false) ? false : true
            );
        }
    }

    public function removeMaskReplacement($oldMask)
    {
        if (isset($this->_maskReplacements[$oldMask])) {
            $this->_maskReplacements[$oldMask] = null;
            unset($this->_maskReplacements[$oldMask]);
        }
    }
    
    public function cleanMaskReplacements()
    {
        $this->_maskReplacements = null;
        $this->_maskReplacements = array();
    }

    public function display($options = array(), $return = true)
    {
        $range = $this->getPagerRange()->rangeAroundPage();
        $str = '';

        // For each page in range
        for ($i = 0, $l = count($range); $i < $l; $i++) {
            // Define some optional mask values
            $options['page_number'] = $range[$i];

            $str .= $this->processPage($options);

            // Apply separator between pages
            if ($i < $l - 1) {
                $str .= $this->getSeparatorTemplate();
            }
        }

        // Possible wish to return value instead of print it on screen
        if ($return) {
            return $str;
        }

        echo $str;
    }

    public function processPage($options = array())
    {
        // Check if at least basic options are defined
        if (!isset($options['page_number'])) {
            throw new IPF_ORM_Exception(
                'Cannot process template of the given page. ' .
                'Missing at least one of needed parameters: \'page\' or \'page_number\''
            );

            // Should never reach here
            return '';
        }

        // Assign "page" options index if not defined yet
        if (!isset($this->_maskReplacements['page']) && !isset($options['page'])) {
            $options['page'] = $options['page_number'];
        }

        return $this->_parseTemplate($options);
    }

    public function __toString()
    {
      return $this->display(array(), true);
    }

    protected function _parseTemplate($options = array())
    {
        $str = $this->_parseUrlTemplate($options);
        $replacements = $this->_parseReplacementsTemplate($options);

        return strtr($str, $replacements);
    }

    protected function _parseUrlTemplate($options = array())
    {
        $str = '';

        // If given page is the current active one
        if ($options['page_number'] == $this->getPager()->getPage()) {
            $str = $this->_parseMaskReplacements($this->getSelectedTemplate());
        }

        // Possible attempt where Selected == Template
        if ($str == '') {
            $str = $this->_parseMaskReplacements($this->getTemplate());
        }

        return $str;
    }

    protected function _parseReplacementsTemplate($options = array())
    {
        // Defining "url" options index to allow {%url} mask
        $options['url'] = $this->_parseUrl($options);

        $replacements = array();

        foreach ($options as $k => $v) {
            $replacements['{%'.$k.'}'] = $v;
        }

        return $replacements;
    }

    protected function _parseUrl($options = array())
    {
        $str = $this->_parseMaskReplacements($this->getUrlMask());

        $replacements = array();

        foreach ($options as $k => $v) {
            $replacements['{%'.$k.'}'] = $v;
        }

        return strtr($str, $replacements);
    }

    protected function _parseMaskReplacements($str)
    {
        $replacements = array();

        foreach ($this->_maskReplacements as $k => $v) {
            $replacements['{%'.$k.'}'] = ($v['asValue'] === true) ? $v['newMask'] : '{%'.$v['newMask'].'}';
        }

        return strtr($str, $replacements);
    }
}
