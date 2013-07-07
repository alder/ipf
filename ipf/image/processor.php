<?php

class IPF_Image_Processor
{
    private $calls = array();

    public static function create()
    {
        return new IPF_Image_Processor;
    }

    public function __call($name, $args)
    {
        $this->calls[] = array($name, $args);
        return $this;
    }

    private function play($image)
    {
        foreach ($this->calls as $call) {
            list($method, $args) = $call;
            $image = call_user_func_array(array($image, $method), $args);
        }
        return $image;
    }

    public function execute($sourceUrl, $directory, $root=null)
    {
        if ($root === null)
            $root = IPF::getUploadPath();

        $destinationUrl = IPF_Utils::insertDirectory($sourceUrl, $directory);
        $path = $root . $destinationUrl;
        if (!is_file($path)) {
            IPF_Utils::makeDirectories(dirname($path));
            $image = IPF_Image::load($root . $sourceUrl);
            $image = $this->play($image);
            $image->save($path);
        }
        return $destinationUrl;
    }
}

