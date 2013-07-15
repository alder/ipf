<?php

class IPF_Form_Field_Datetime extends IPF_Form_Field
{
    public $widget = 'IPF_Form_Widget_DatetimeInput';
    public $input_formats = array(
             '%Y-%m-%d %H:%M:%S',     // '2006-10-25 14:30:59'
             '%Y-%m-%d %H:%M',        // '2006-10-25 14:30'
             '%Y-%m-%d',              // '2006-10-25'
             '%m/%d/%Y %H:%M:%S',     // '10/25/2006 14:30:59'
             '%m/%d/%Y %H:%M',        // '10/25/2006 14:30'
             '%m/%d/%Y',              // '10/25/2006'
             '%m/%d/%y %H:%M:%S',     // '10/25/06 14:30:59'
             '%m/%d/%y %H:%M',        // '10/25/06 14:30'
             '%m/%d/%y',              // '10/25/06'
                                  );

    public function clean($value)
    {
        parent::clean($value);
        $out = null;
        foreach ($this->input_formats as $format) {
            if (false !== ($date = strptime($value, $format))) {
                $day = str_pad($date['tm_mday'], 2, '0', STR_PAD_LEFT);
                $month = str_pad($date['tm_mon']+1, 2, '0', STR_PAD_LEFT);
                $year = str_pad($date['tm_year']+1900, 4, '0', STR_PAD_LEFT);
                $h = str_pad($date['tm_hour'], 2, '0', STR_PAD_LEFT);
                $m = str_pad($date['tm_min'], 2, '0', STR_PAD_LEFT);
                $s = $date['tm_sec'];
                if ($s > 59) $s=59;
                $s = str_pad($s, 2, '0', STR_PAD_LEFT);
                $out = $year.'-'.$month.'-'.$day.' '.$h.':'.$m.':'.$s;
                break;
            }
        }
        if ($out !== null) {
            // We internally use GMT, so we convert it to a GMT date.
            return gmdate('Y-m-d H:i:s', strtotime($out));
        }
        throw new IPF_Exception_Form(__('Enter a valid date/time.'));
    }
}

