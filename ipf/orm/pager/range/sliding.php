<?php

class IPF_ORM_Pager_Range_Sliding extends IPF_ORM_Pager_Range
{
    private $_chunkLength;

    protected function _initialize()
    {
        if (isset($this->_options['chunk'])) {
            $this->_setChunkLength($this->_options['chunk']);
        } else {
            throw new IPF_ORM_Exception('Missing parameter \'chunk\' that must be defined in options.');
        }
    }

    public function getChunkLength()
    {
        return $this->_chunkLength;
    }

    protected function _setChunkLength($chunkLength)
    {
        $chunkLength = (int) $chunkLength;
        if (!$chunkLength) {
            $chunkLength = 1;
        } else {
            $this->_chunkLength = $chunkLength;
        }
    }

    public function rangeAroundPage()
    {
        $pager = $this->getPager();

        if ($pager->getExecuted()) {
            $page  = $pager->getPage();
            $pages = $pager->getLastPage();

            $chunk = $this->getChunkLength();

            if ($chunk > $pages) {
                $chunk = $pages;
            }

            $chunkStart = $page - (floor($chunk / 2));
            $chunkEnd   = $page + (ceil($chunk / 2)-1);

            if ($chunkStart < 1) {
                $adjust = 1 - $chunkStart;
                $chunkStart = 1;
                $chunkEnd = $chunkEnd + $adjust;
            }

            if ($chunkEnd > $pages) {
                $adjust = $chunkEnd - $pages;
                $chunkStart = $chunkStart - $adjust;
                $chunkEnd = $pages;
            }

            return range($chunkStart, $chunkEnd);
        }

        throw new IPF_ORM_Exception(
            'Cannot retrieve the range around the page of a not yet executed Pager query'
        );
    }
}
