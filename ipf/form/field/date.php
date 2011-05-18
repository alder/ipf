<?php

class IPF_Form_Field_Date extends IPF_Form_Field
{
    public $widget = 'IPF_Form_Widget_DateInput';
    public $input_formats = array(
       '%Y-%m-%d', '%m/%d/%Y', '%m/%d/%y', // 2006-10-25, 10/25/2006, 10/25/06
       '%b %d %Y', '%b %d, %Y',      // 'Oct 25 2006', 'Oct 25, 2006'
       '%d %b %Y', '%d %b, %Y',      // '25 Oct 2006', '25 Oct, 2006'
       '%B %d %Y', '%B %d, %Y',      // 'October 25 2006', 'October 25, 2006'
       '%d %B %Y', '%d %B, %Y',      // '25 October 2006', '25 October, 2006'
                                  );

    public function clean($value)
    {
        parent::clean($value);
        foreach ($this->input_formats as $format) {
            if (false !== ($date = strptime($value, $format))) {
                $day = str_pad($date['tm_mday'], 2, '0', STR_PAD_LEFT);
                $month = str_pad($date['tm_mon']+1, 2, '0', STR_PAD_LEFT);
                $year = str_pad($date['tm_year']+1900, 4, '0', STR_PAD_LEFT);
                return $year.'-'.$month.'-'.$day;
            }
        }
        throw new IPF_Exception_Form(__('Enter a valid date.'));
    }
}
