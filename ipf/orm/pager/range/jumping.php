<?php

class IPF_ORM_Pager_Range_Jumping extends IPF_ORM_Pager_Range
{
    private $_chunkLength;

    protected function _initialize()
    {
        if (isset($this->_options['chunk'])) {
            $this->_setChunkLength($this->_options['chunk']);
        } else {
            throw new IPF_ORM_Exception('Missing parameter \'chunk\' that must be define in options.');
        }
    }

    public function getChunkLength()
    {
        return $this->_chunkLength;
    }

    protected function _setChunkLength($chunkLength)
    {
        $this->_chunkLength = $chunkLength;
    }

    public function rangeAroundPage()
    {
        $pager = $this->getPager();

        if ($pager->getExecuted()) {
            $page = $pager->getPage();

            // Define initial assignments for StartPage and EndPage
            $startPage = $page - ($page - 1) % $this->getChunkLength();
            $endPage = ($startPage + $this->getChunkLength()) - 1;

            // Check for EndPage out-range
            if ($endPage > $pager->getLastPage()) {
                $endPage = $pager->getLastPage();
            }

            // No need to check for out-range in start, it will never happens

            return range($startPage, $endPage);
        }

        throw new IPF_ORM_Exception(
            'Cannot retrieve the range around the page of a not yet executed Pager query'
        );
    }
}
