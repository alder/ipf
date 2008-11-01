<?php

class IPF_ORM_Pager_LayoutArrows extends IPF_ORM_Pager_Layout
{
    public function display($options = array(), $return = true)
    {
        $pager = $this->getPager();
        $str = '';

		if ($pager->getFirstPage()!=$pager->getLastPage()){

	        $this->removeMaskReplacement('page');

	        if (($pager->getPage()-2)>$pager->getFirstPage()){

		        if (($pager->getPage()-2)>$pager->getFirstPage()){
			        $options['page_number'] = $pager->getFirstPage();
			        $str .= $this->processPage($options);
		        }
		        if (($pager->getPage()-3)>$pager->getFirstPage()){
			        $options['page_number'] = $pager->getFirstPage()+1;
			        $str .= $this->processPage($options);
		        }
		        if (($pager->getPage()-4)>$pager->getFirstPage()){
			        $str .= ' ... ';
		        }
	        }

	        // Pages listing
	        $str .= parent::display(&$options, true);
	        $last_range = $options['page_number'];
	        if (($last_range)<$pager->getLastPage()){

		        if (($last_range+2)<$pager->getLastPage()){
		        	$str .= ' ... ';
		        }
		        if (($last_range+1)<$pager->getLastPage()){
		        	$options['page_number'] = $pager->getLastPage()-1;
			        $str .= $this->processPage($options);
		        }
		        if (($last_range)<$pager->getLastPage()){
			        $options['page_number'] = $pager->getLastPage();
			        $str .= $this->processPage($options);
		        }
	        }
		}

        if ($return)
            return $str;

        echo $str;
    }
}
