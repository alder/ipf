<?php

class Utils_Test extends PHPUnit_Framework_TestCase
{
    public function testInsertDirectory()
    {
        $this->assertEquals('goods/thumbs/image.gif', IPF_Utils::insertDirectory('goods/image.gif', 'thumbs'));
        $this->assertEquals('thumbs/image.gif', IPF_Utils::insertDirectory('image.gif', 'thumbs'));
    }
}

