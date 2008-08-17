<?php

final class IPF_ORM {
    const ERR                       = -1;
    const ERR_SYNTAX                = -2;
    const ERR_CONSTRAINT            = -3;
    const ERR_NOT_FOUND             = -4;
    const ERR_ALREADY_EXISTS        = -5;
    const ERR_UNSUPPORTED           = -6;
    const ERR_MISMATCH              = -7;
    const ERR_INVALID               = -8;
    const ERR_NOT_CAPABLE           = -9;
    const ERR_TRUNCATED             = -10;
    const ERR_INVALID_NUMBER        = -11;
    const ERR_INVALID_DATE          = -12;
    const ERR_DIVZERO               = -13;
    const ERR_NODBSELECTED          = -14;
    const ERR_CANNOT_CREATE         = -15;
    const ERR_CANNOT_DELETE         = -16;
    const ERR_CANNOT_DROP           = -17;
    const ERR_NOSUCHTABLE           = -18;
    const ERR_NOSUCHFIELD           = -19;
    const ERR_NEED_MORE_DATA        = -20;
    const ERR_NOT_LOCKED            = -21;
    const ERR_VALUE_COUNT_ON_ROW    = -22;
    const ERR_INVALID_DSN           = -23;
    const ERR_CONNECT_FAILED        = -24;
    const ERR_EXTENSION_NOT_FOUND   = -25;
    const ERR_NOSUCHDB              = -26;
    const ERR_ACCESS_VIOLATION      = -27;
    const ERR_CANNOT_REPLACE        = -28;
    const ERR_CONSTRAINT_NOT_NULL   = -29;
    const ERR_DEADLOCK              = -30;
    const ERR_CANNOT_ALTER          = -31;
    const ERR_MANAGER               = -32;
    const ERR_MANAGER_PARSE         = -33;
    const ERR_LOADMODULE            = -34;
    const ERR_INSUFFICIENT_DATA     = -35;
    const ERR_CLASS_NAME            = -36;

    const CASE_LOWER = 2;
    const CASE_NATURAL = 0;
    const CASE_UPPER = 1;
    const CURSOR_FWDONLY = 0;
    const CURSOR_SCROLL = 1;
    const ERRMODE_EXCEPTION = 2;
    const ERRMODE_SILENT = 0;
    const ERRMODE_WARNING = 1;
    const FETCH_ASSOC = 2;
    const FETCH_BOTH = 4;
    const FETCH_BOUND = 6;
    const FETCH_CLASS = 8;
    const FETCH_CLASSTYPE = 262144;
    const FETCH_COLUMN = 7;
    const FETCH_FUNC = 10;
    const FETCH_GROUP = 65536;
    const FETCH_INTO = 9;
    const FETCH_LAZY = 1;
    const FETCH_NAMED = 11;
    const FETCH_NUM = 3;
    const FETCH_OBJ = 5;
    const FETCH_ORI_ABS = 4;
    const FETCH_ORI_FIRST = 2;
    const FETCH_ORI_LAST = 3;
    const FETCH_ORI_NEXT = 0;
    const FETCH_ORI_PRIOR = 1;
    const FETCH_ORI_REL = 5;
    const FETCH_SERIALIZE = 524288;
    const FETCH_UNIQUE = 196608;
    const NULL_EMPTY_STRING = 1;
    const NULL_NATURAL = 0;
    const NULL_TO_STRING         = NULL;
    const PARAM_BOOL = 5;
    const PARAM_INPUT_OUTPUT = -2147483648;
    const PARAM_INT = 1;
    const PARAM_LOB = 3;
    const PARAM_NULL = 0;
    const PARAM_STMT = 4;
    const PARAM_STR = 2;

    const ATTR_AUTOCOMMIT           = 0;
    const ATTR_PREFETCH             = 1;
    const ATTR_TIMEOUT              = 2;
    const ATTR_ERRMODE              = 3;
    const ATTR_SERVER_VERSION       = 4;
    const ATTR_CLIENT_VERSION       = 5;
    const ATTR_SERVER_INFO          = 6;
    const ATTR_CONNECTION_STATUS    = 7;
    const ATTR_CASE                 = 8;
    const ATTR_CURSOR_NAME          = 9;
    const ATTR_CURSOR               = 10;
    const ATTR_ORACLE_NULLS         = 11;
    const ATTR_PERSISTENT           = 12;
    const ATTR_STATEMENT_CLASS      = 13;
    const ATTR_FETCH_TABLE_NAMES    = 14;
    const ATTR_FETCH_CATALOG_NAMES  = 15;
    const ATTR_DRIVER_NAME          = 16;
    const ATTR_STRINGIFY_FETCHES    = 17;
    const ATTR_MAX_COLUMN_LEN       = 18;

    const ATTR_LISTENER             = 100;
    const ATTR_QUOTE_IDENTIFIER     = 101;
    const ATTR_FIELD_CASE           = 102;
    const ATTR_IDXNAME_FORMAT       = 103;
    const ATTR_SEQNAME_FORMAT       = 104;
    const ATTR_SEQCOL_NAME          = 105;
    const ATTR_CMPNAME_FORMAT       = 118;
    const ATTR_DBNAME_FORMAT        = 117;
    const ATTR_TBLCLASS_FORMAT      = 119;
    const ATTR_TBLNAME_FORMAT       = 120;
    const ATTR_EXPORT               = 140;
    const ATTR_DECIMAL_PLACES       = 141;

    const ATTR_PORTABILITY          = 106;
    const ATTR_VALIDATE             = 107;
    const ATTR_COLL_KEY             = 108;
    const ATTR_QUERY_LIMIT          = 109;
    const ATTR_DEFAULT_TABLE_TYPE   = 112;
    const ATTR_DEF_TEXT_LENGTH      = 113;
    const ATTR_DEF_VARCHAR_LENGTH   = 114;
    const ATTR_DEF_TABLESPACE       = 115;
    const ATTR_EMULATE_DATABASE     = 116;
    const ATTR_USE_NATIVE_ENUM      = 117;
    const ATTR_DEFAULT_SEQUENCE     = 133;

    //const ATTR_FETCHMODE                = 118;
    const ATTR_NAME_PREFIX              = 121;
    const ATTR_CREATE_TABLES            = 122;
    const ATTR_COLL_LIMIT               = 123;

    const ATTR_CACHE                    = 150;
    const ATTR_RESULT_CACHE             = 150;
    const ATTR_CACHE_LIFESPAN           = 151;
    const ATTR_RESULT_CACHE_LIFESPAN    = 151;
    const ATTR_LOAD_REFERENCES          = 153;
    const ATTR_RECORD_LISTENER          = 154;
    const ATTR_THROW_EXCEPTIONS         = 155;
    const ATTR_DEFAULT_PARAM_NAMESPACE  = 156;
    const ATTR_QUERY_CACHE              = 157;
    const ATTR_QUERY_CACHE_LIFESPAN     = 158;
    const ATTR_AUTOLOAD_TABLE_CLASSES   = 160;
    const ATTR_MODEL_LOADING            = 161;
    const ATTR_RECURSIVE_MERGE_FIXTURES = 162;
    const ATTR_SINGULARIZE_IMPORT       = 163;
    const ATTR_USE_DQL_CALLBACKS        = 164;

    const LIMIT_ROWS       = 1;
    const QUERY_LIMIT_ROWS = 1;
    const LIMIT_RECORDS       = 2;
    const QUERY_LIMIT_RECORDS = 2;

    const FETCH_IMMEDIATE       = 0;
    const FETCH_BATCH           = 1;
    const FETCH_OFFSET          = 3;
    const FETCH_LAZY_OFFSET     = 4;
    const FETCH_VHOLDER         = 1;
    const FETCH_RECORD          = 2;
    const FETCH_ARRAY           = 3;

    const PORTABILITY_NONE          = 0;
    const PORTABILITY_FIX_CASE      = 1;
    const PORTABILITY_RTRIM         = 2;
    const PORTABILITY_DELETE_COUNT  = 4;
    const PORTABILITY_EMPTY_TO_NULL = 8;
    const PORTABILITY_FIX_ASSOC_FIELD_NAMES = 16;
    const PORTABILITY_EXPR          = 32;
    const PORTABILITY_ALL           = 63;

    const LOCK_OPTIMISTIC       = 0;
    const LOCK_PESSIMISTIC      = 1;

    const EXPORT_NONE               = 0;
    const EXPORT_TABLES             = 1;
    const EXPORT_CONSTRAINTS        = 2;
    const EXPORT_PLUGINS            = 4;
    const EXPORT_ALL                = 7;
    const HYDRATE_RECORD            = 2;
    const HYDRATE_ARRAY             = 3;

    const HYDRATE_NONE              = 4;
    const VALIDATE_NONE             = 0;
    const VALIDATE_LENGTHS          = 1;
    const VALIDATE_TYPES            = 2;
    const VALIDATE_CONSTRAINTS      = 4;
    const VALIDATE_ALL              = 7;
    const IDENTIFIER_AUTOINC        = 1;
    const IDENTIFIER_SEQUENCE       = 2;
    const IDENTIFIER_NATURAL        = 3;
    const IDENTIFIER_COMPOSITE      = 4;
    const MODEL_LOADING_AGGRESSIVE   = 1;
    const MODEL_LOADING_CONSERVATIVE= 2;

    private static $_loadedModelFiles = array();

    public function __construct(){
        throw new IPF_Exception_Base('IPF_Const is static class. No instances can be created.');
    }    

    public static function getTable($componentName)
    {
        return IPF_ORM_Manager::getInstance()->getConnectionForComponent($componentName)->getTable($componentName);
    }
    
    public static function generateModelsFromYaml($yamlPath, $directory, $options = array())
    {
        $import = new IPF_ORM_Import_Schema();
        $import->setOptions($options);
        $import->importSchema($yamlPath, 'yml', $directory);
    }
    
    public static function createTablesFromModels($directory)
    {
        return IPF_ORM_Manager::connection()->export->exportSchema($directory);
    }
    
    public static function generateSqlFromModels($directory = null)
    {
        $sql = IPF_ORM_Manager::connection()->export->exportSql($directory);
        $build = '';
        foreach ($sql as $query) {
            $build .= $query.";\n";
        }
        return $build;
    }    
    
    public static function loadModel($className, $path = null)
    {
        self::$_loadedModelFiles[$className] = $path;
    }

    public static function filterInvalidModels($classes)
    {
        $validModels = array();
        foreach ((array) $classes as $name) {
            if (self::isValidModelClass($name) && ! in_array($name, $validModels)) {
                $validModels[] = $name;
            }
        }

        return $validModels;
    }

    public static function loadModels($directory, $modelLoading = null)
    {
        $manager = IPF_ORM_Manager::getInstance();
        
        $modelLoading = $modelLoading === null ? $manager->getAttribute(IPF_ORM::ATTR_MODEL_LOADING):$modelLoading;
        $loadedModels = array();
        if ($directory !== null) {
            foreach ((array) $directory as $dir) {
                if ( ! is_dir($dir)) {
                    throw new IPF_ORM_Exception('You must pass a valid path to a directory containing IPF_ORM models');
                }
                $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir), RecursiveIteratorIterator::LEAVES_ONLY);
                foreach ($it as $file) {
                    $e = explode('.', $file->getFileName());
                    if (end($e) === 'php' && strpos($file->getFileName(), '.inc') === false) {
                        $className = $e[0];
                        if ($modelLoading == IPF_ORM::MODEL_LOADING_CONSERVATIVE) {
                            self::loadModel($className, $file->getPathName());

                            $loadedModels[$className] = $className;
                        } else {
                            //$declaredBefore = get_declared_classes();
                            require_once($file->getPathName());
                            $loadedModels[$className] = $className; // !!!

                            /*
                            //$declaredAfter = get_declared_classes();
                            //$foundClasses = array_slice($declaredAfter, count($declaredBefore) - 1);
                            if ($foundClasses) {
                                foreach ($foundClasses as $className) {
                                    if (self::isValidModelClass($className)) {
                                        $loadedModels[$className] = $className;

                                        self::loadModel($className, $file->getPathName());
                                    }
                                }
                            }
                            */
                        }
                    }
                }
            }
        }
        return $loadedModels;
    }


    public static function isValidModelClass($class)
    {
        if ($class instanceof IPF_ORM_Record) {
            $class = get_class($class);
        }
        if (is_string($class) && class_exists($class)) {
            $class = new ReflectionClass($class);
        }
        if ($class instanceof ReflectionClass) {
            if ( ! $class->isAbstract() && $class->isSubClassOf('IPF_ORM_Record')) {
                return true;
            }
        }
        return false;
    }

    public static function dump($var, $output = true, $indent = "")
    {
        $ret = array();
        switch (gettype($var)) {
            case 'array':
                $ret[] = 'Array(';
                $indent .= "    ";
                foreach ($var as $k => $v) {

                    $ret[] = $indent . $k . ' : ' . self::dump($v, false, $indent);
                }
                $indent = substr($indent,0, -4);
                $ret[] = $indent . ")";
                break;
            case 'object':
                $ret[] = 'Object(' . get_class($var) . ')';
                break;
            default:
                $ret[] = var_export($var, true);
        }

        if ($output) {
            print implode("\n", $ret);
        }

        return implode("\n", $ret);
    }
}
