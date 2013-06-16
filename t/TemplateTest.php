<?php

class Template_Test extends PHPUnit_Framework_TestCase
{
    public function testStringTemplate()
    {
        $environment = new IPF_Template_Environment_FileSystem(array(), IPF::get('tmp'));
        $template = new IPF_Template_String('Hello, {$name}!', $environment);

        $this->assertEquals('Hello, World!', $template->render(new IPF_Template_Context(array('name' => 'World'))));
        $this->assertEquals('Hello, (&gt;.&lt;)!', $template->render(new IPF_Template_Context(array('name' => '(>.<)'))));
    }

    public function testLimitWords()
    {
        $lipsum = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.';
        $this->assertEquals('Lorem ipsum dolor&#8230;', IPF_Utils::limitWords($lipsum, 3));
    }
}
