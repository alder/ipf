<?php

class IPF_XmlRpc_Server_Cache
{
    public static function save($filename, IPF_XmlRpc_Server $server)
    {
        if (!is_string($filename)
            || (!file_exists($filename) && !is_writable(dirname($filename))))
        {
            return false;
        }

        // Remove system.* methods
        $methods = $server->getFunctions();
        foreach ($methods as $name => $method) {
            if ($method->system) {
                unset($methods[$name]);
            }
        }

        // Store
        if (0 === @file_put_contents($filename, serialize($methods))) {
            return false;
        }

        return true;
    }

    public static function get($filename, IPF_XmlRpc_Server $server)
    {
        if (!is_string($filename)
            || !file_exists($filename)
            || !is_readable($filename))
        {
            return false;
        }

        if (false === ($dispatch = @file_get_contents($filename))) {
            return false;
        }

        if (false === ($dispatchArray = @unserialize($dispatch))) {
            return false;
        }

        $server->loadFunctions($dispatchArray);

        return true;
    }

    public static function delete($filename)
    {
        if (is_string($filename) && file_exists($filename)) {
            unlink($filename);
            return true;
        }

        return false;
    }
}
