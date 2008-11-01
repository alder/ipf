<?php

class IPF_ORM_Pager_LayoutArrows extends IPF_ORM_Pager_Layout
{
    public function display($options = array(), $return = true)
    {
        $pager = $this->getPager();
        $str = '';

		if ($pager->getFirstPage()!=$pager->getLastPage()){
	        // First page
	        if ($pager->getFirstPage()!=$pager->getPage()){
		        $this->addMaskReplacement('page', '&laquo;', true);
		        $options['page_number'] = $pager->getFirstPage();
		        $str .= $this->processPage($options);
	        }

	        // Previous page
			/*
	        $this->addMaskReplacement('page', '&lsaquo;', true);
	        $options['page_number'] = $pager->getPreviousPage();
	        $str .= $this->processPage($options);
	        */

	        // Pages listing
	        $this->removeMaskReplacement('page');
	        $str .= parent::display($options, true);


	        // Next page
			/*
	        $this->addMaskReplacement('page', '&rsaquo;', true);
	        $options['page_number'] = $pager->getNextPage();
	        $str .= $this->processPage($options);
	        */

	        // Last page
	        if ($pager->getLastPage()!=$pager->getPage()){
		        $this->addMaskReplacement('page', '&raquo;', true);
		        $options['page_number'] = $pager->getLastPage();
		        $str .= $this->processPage($options);
	        }
		}

        if ($return)
            return $str;

        echo $str;
    }
}
