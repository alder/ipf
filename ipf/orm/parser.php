<?php

abstract class IPF_ORM_Parser
{
    abstract public function loadData($array);
    abstract public function dumpData($array, $path = null);

    static public function getParser($type)
    {
        $class = 'IPF_ORM_Parser_'.ucfirst($type);
        return new $class;
    }

    static public function load($path, $type = 'xml')
    {
        $parser = self::getParser($type);
        return $parser->loadData($path);
    }

    static public function dump($array, $type = 'xml', $path = null)
    {
        $parser = self::getParser($type);
        return $parser->dumpData($array, $path);
    }

    public function doLoad($path)
    {
        ob_start();
        if ( ! file_exists($path)) {
            $contents = $path;
            $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'dparser_' . microtime();

            file_put_contents($path, $contents);
        }
        include($path);
        $contents = iconv("UTF-8", "UTF-8", ob_get_clean());
        return $contents;
    }

    public function doDump($data, $path = null)
    {
      if ($path !== null) {
            return file_put_contents($path, $data);
        } else {
            return $data;
        }
    }
}