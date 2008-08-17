<?php

class IPF_ORM_Utils {

    public static function getConnectionStateAsString($state)
    {
        switch ($state) {
            case IPF_ORM_Transaction::STATE_SLEEP:
                return "open";
                break;
            case IPF_ORM_Transaction::STATE_BUSY:
                return "busy";
                break;
            case IPF_ORM_Transaction::STATE_ACTIVE:
                return "active";
                break;
        }
    }	

    public static function getConnectionAsString(IPF_ORM_Connection $connection)
    {
        $r[] = '<pre>';
        $r[] = 'IPF_ORM_Connection object';
        $r[] = 'State               : ' . IPF_ORM_Utils::getConnectionStateAsString($connection->transaction->getState());
        $r[] = 'Open Transactions   : ' . $connection->transaction->getTransactionLevel();
        $r[] = 'Table in memory     : ' . $connection->count();
        $r[] = 'Driver name         : ' . $connection->getAttribute(IPF_ORM::ATTR_DRIVER_NAME);
        $r[] = "</pre>";
        return implode("\n",$r)."<br>";
    }
    
    public static function getValidators()
    {
        return array(
            'country',
            'creditcard',
            'date',
            'driver',
            'email',
            'exception',
            'future',
            'htmlcolor',
            'ip',
            'minlength',
            'nospace',
            'notblank',
            'notnull',
            'past',
            'range',
            'readonly',
            'regexp',
            'time',
            'timestamp',
            'unique',
            'unsigned',
            'usstate',
        );
    }

    
    public static function arrayDeepMerge()
    {
         switch (func_num_args()) {
             case 0:
                return false;
             case 1:
                return func_get_arg(0);
             case 2:
                $args = func_get_args();
                $args[2] = array();
                
                if (is_array($args[0]) && is_array($args[1]))
                {
                    foreach (array_unique(array_merge(array_keys($args[0]),array_keys($args[1]))) as $key)
                    {
                        $isKey0 = array_key_exists($key, $args[0]);
                        $isKey1 = array_key_exists($key, $args[1]);

                        if ($isKey0 && $isKey1 && is_array($args[0][$key]) && is_array($args[1][$key]))
                        {
                            $args[2][$key] = self::arrayDeepMerge($args[0][$key], $args[1][$key]);
                        } else if ($isKey0 && $isKey1) {
                            $args[2][$key] = $args[1][$key];
                        } else if ( ! $isKey1) {
                            $args[2][$key] = $args[0][$key];
                        } else if ( ! $isKey0) {
                            $args[2][$key] = $args[1][$key];
                        }
                    }

                    return $args[2];
                } else {
                    return $args[1];
                }
            default:
                $args = func_get_args();
                $args[1] = self::arrayDeepMerge($args[0], $args[1]);
                array_shift($args);
                return call_user_func_array(array('IPF', 'arrayDeepMerge'), $args);
            break;
        }
    }        

    public static function makeDirectories($path, $mode = 0777)
    {
        if ( ! $path) {
          return false;
        }

        if (is_dir($path) || is_file($path)) {
          return true;
        }

        return mkdir(trim($path), $mode, true);
    }

    public static function removeDirectories($folderPath)
    {
        if (is_dir($folderPath))
        {
            foreach (scandir($folderPath) as $value)
            {
                if ($value != '.' && $value != '..')
                {
                    $value = $folderPath . "/" . $value;

                    if (is_dir($value)) {
                        self::removeDirectories($value);
                    } else if (is_file($value)) {
                        unlink($value);
                    }
                }
            }

            return rmdir($folderPath);
        } else {
            return false;
        }
    }

    public static function copyDirectory($source, $dest)
    {
        // Simple copy for a file
        if (is_file($source)) {
            return copy($source, $dest);
        }

        // Make destination directory
        if ( ! is_dir($dest)) {
            mkdir($dest);
        }

        // Loop through the folder
        $dir = dir($source);
        while (false !== $entry = $dir->read()) {
            // Skip pointers
            if ($entry == '.' || $entry == '..') {
                continue;
            }

            // Deep copy directories
            if ($dest !== "$source/$entry") {
                self::copyDirectory("$source/$entry", "$dest/$entry");
            }
        }

        // Clean up
        $dir->close();

        return true;
    }

    public static function getTableAsString(IPF_ORM_Table $table)
    {
        $r[] = "<pre>";
        $r[] = "Component   : ".$table->getComponentName();
        $r[] = "Table       : ".$table->getTableName();
        $r[] = "</pre>";
        
        return implode("\n",$r)."<br>";
    }

    public static function getCollectionAsString(IPF_ORM_Collection $collection)
    {
        $r[] = "<pre>";
        $r[] = get_class($collection);
        $r[] = 'data : ' . IPF_ORM::dump($collection->getData(), false);
        //$r[] = 'snapshot : ' . IPF_ORM::dump($collection->getSnapshot());
        $r[] = "</pre>";
        return implode("\n",$r);
    }


}

