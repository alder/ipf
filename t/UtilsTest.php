<?php

class Utils_Test extends PHPUnit_Framework_TestCase
{
    public function testInsertDirectory()
    {
        $this->assertEquals('goods/thumbs/image.gif', IPF_Utils::insertDirectory('goods/image.gif', 'thumbs'));
        $this->assertEquals('thumbs/image.gif', IPF_Utils::insertDirectory('image.gif', 'thumbs'));
    }

    public function testIsValidName()
    {
        $this->assertFalse(IPF_Utils::isValidName(3));
        $this->assertFalse(IPF_Utils::isValidName(array()));
        $this->assertFalse(IPF_Utils::isValidName((object)array()));
        $this->assertFalse(IPF_Utils::isValidName(''));
        $this->assertFalse(IPF_Utils::isValidName('abc', 2));
        $this->assertFalse(IPF_Utils::isValidName('4ever'));
        $this->assertFalse(IPF_Utils::isValidName('+'));
        $this->assertFalse(IPF_Utils::isValidName('-'));
        $this->assertFalse(IPF_Utils::isValidName('not a valid name'));
        $this->assertTrue(IPF_Utils::isValidName('AbstractFactoryBean'));
        $this->assertTrue(IPF_Utils::isValidName('th1s_1s_a_valid_nam3'));
        $this->assertTrue(IPF_Utils::isValidName('_'));
    }
}

