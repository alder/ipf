<?php

class IPF_ORM_Parser_Yml extends IPF_ORM_Parser
{
    public function dumpData($array, $path = null)
    {
        $spyc = new IPF_ORM_Parser_Spyc();
        $data = $spyc->dump($array, false, false);
        return $this->doDump($data, $path);
    }

    public function loadData($path)
    {
        $contents = $this->doLoad($path);
        $spyc = new IPF_ORM_Parser_Spyc();
        $array = $spyc->load($contents);
        return $array;
    }
}
