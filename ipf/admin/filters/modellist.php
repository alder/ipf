<?php

class IPF_Admin_Filters_ModelList extends ListFilter
{
    function __construct($local, $foreign, $request, $coll, $title)
    {
        $fname = 'filter_'.$local;

        $sel_id = @$request->GET[$fname];

        $choices = array(
            array(
                'id'       => null,
                'param'    => '',
                'name'     => 'All',
                'selected' => ($sel_id==''),
            ),
            array(
                'id'       => '0',
                'param'    => $fname.'=0',
                'name'     => 'None',
                'selected' => ($sel_id=='0'),
            ),        
        );

        foreach ($coll as $item)
        {
            $id = (string)$item->id;

            $choices[] = array(
                'id'       => $id,
                'param'    => $fname.'='.$id,
                'name'     => (string)$item,
                'selected' => ($sel_id == $id),
            );
        }
 
        parent::__construct($local, $foreign, $choices, $title);
    }

    function FilterQuery($request,$q)
    {
        $param_name = 'filter_'.$this->local;
        if (isset($request->GET[$param_name]))
        {
            $id = $request->GET[$param_name];
            if ($this->IsChoice($id))
            {
                if ($id == '0')
                     $q->where($this->local.' is null');
                else $q->where($this->local.'='.$id);
            }
        }
    }
}