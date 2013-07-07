<?php

class Image_Processor_Test extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->root = IPF::getUploadPath();
        IPF_Utils::removeDirectories($this->root . '/thumbs');
    }

    protected function tearDown()
    {
        IPF_Utils::removeDirectories($this->root . '/thumbs');
    }

    public function testRecording()
    {
        $url = IPF_Image_Processor::create()
            ->thumbnailCrop(250, 125, 0.2, 0.2)
            ->fit(200, 200)
            ->execute('ipflogo.gif', 'thumbs');
        $this->assertEquals('thumbs/ipflogo.gif', $url);
        $this->assertFileExists($this->root . '/thumbs/ipflogo.gif');
    }

    public function testBackground()
    {
        $url = IPF_Image_Processor::create()
            ->thumbnailFill(250, 125, 0x0F0000FF)
            ->execute('ipflogo.gif', 'thumbs');
        $this->assertEquals('thumbs/ipflogo.gif', $url);
        $this->assertFileExists($this->root . '/thumbs/ipflogo.gif');
    }
}

