<?php

class Template_Test extends PHPUnit_Framework_TestCase
{
    public function testLimitWords()
    {
        $lipsum = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.';
        $this->assertEquals('Lorem ipsum dolor&#8230;', IPF_Utils::limitWords($lipsum, 3));
    }
}
