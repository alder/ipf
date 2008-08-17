<?php

interface IPF_ORM_Overloadable {
    public function __call($m, $a);
}