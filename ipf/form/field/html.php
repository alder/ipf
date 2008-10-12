<?php

class IPF_Form_Field_Html extends IPF_Form_Field_Varchar{
    protected function getWidget(){
        return 'IPF_Form_Widget_HTMLInput';
    }
}