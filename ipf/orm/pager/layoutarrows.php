<?php

class IPF_ORM_Pager_LayoutArrows extends IPF_ORM_Pager_Layout
{
    public function display($options = array(), $return = true)
    {
        $pager = $this->getPager();
        $str = '';

        $range = $this->getPagerRange()->rangeAroundPage();

        if ($pager->getFirstPage() != $pager->getLastPage())
        {
            $this->removeMaskReplacement('page');

            if ($range[0] > 1)
            {
                $options['page_number'] = 1;
                $str .= $this->processPage($options);

                if ($range[0] > 2)
                {
                    $options['page_number'] = 2;
                    $str .= $this->processPage($options);
                }

                if ($range[0]>3)
                    $str .= ' ... ';
            }

            // Pages listing
            $str .= parent::display($options, true);

            $range = $this->getPagerRange()->rangeAroundPage();
            $last_range = $range[count($range)-1];

            if ($last_range < $pager->getLastPage())
            {
                if ($last_range < $pager->getLastPage() - 2)
                {
                    $str .= ' ... ';
                }

                if ($last_range < $pager->getLastPage() - 1)
                {
                    $options['page_number'] = $pager->getLastPage() - 1;
                    $str .= $this->processPage($options);
                }

                if ($last_range < $pager->getLastPage())
                {
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

