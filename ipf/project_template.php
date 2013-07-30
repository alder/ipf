<?php

final class IPF_Project_Template
{
    private static $defaultEnvironment = null;

    public static function getDefaultTemplateEnvironment()
    {
        if (!self::$defaultEnvironment)
            self::$defaultEnvironment = self::createEnvironment();
        return self::$defaultEnvironment;
    }

    private static function createEnvironment()
    {
        $e = new IPF_Template_Environment_FileSystem;

        $e->cache = IPF::get('tmp');
        $e->debug = IPF::get('debug');

        // folders
        $projectTemplates = IPF::get('project_path') . '/templates';
        if (is_dir($projectTemplates))
            $e->folders[] = $projectTemplates;

        foreach (IPF_Project::getInstance()->appList() as $app) {
            $applicationTemplates = $app->getPath() . 'templates';
            if (is_dir($applicationTemplates))
                $e->folders[] = $applicationTemplates;
        }

        $e->tags['url'] = 'IPF_Project_Template_Tag_Url';
        $e->tags['sql'] = 'IPF_Project_Template_Tag_Sql';
        // extra tags
        $e->tags = array_merge(IPF::get('template_tags', array()), $e->tags);

        // extra modifiers
        $e->modifiers = array_merge(IPF::get('template_modifiers', array()), $e->modifiers);

        return $e;
    }

    public static function context($params=array(), $request=null)
    {
        if ($request) {
            $params = array_merge(array('request' => $request), $params);
            foreach (IPF::get('template_context_processors', array()) as $proc) {
                IPF::loadFunction($proc);
                $params = array_merge($proc($request), $params);
            }
            foreach (IPF_Project::getInstance()->appList() as $app) {
                $params = array_merge($app->templateContext($request), $params);
            }
        }
        return new IPF_Template_Context($params);
    }
}

class IPF_Project_Template_Tag_Url extends IPF_Template_Tag
{
    function start()
    {
        $args = func_get_args();
        $count = count($args);
        if ($count === 0)
            throw new IPF_Exception('No view specified');

        $view = array_shift($args);

        if ($count === 2 && is_array($args[0])) {
            echo IPF_HTTP_URL::urlForView($view, $args[0]);
        } elseif ($count === 3 && is_array($args[0]) && is_array($args[1])) {
            echo IPF_HTTP_URL::urlForView($view, $args[0], $args[1]);
        } else {
            echo IPF_HTTP_URL::urlForView($view, $args);
        }
    }
}

class IPF_Project_Template_Tag_Sql extends IPF_Template_Tag
{
    function start()
    {
        $profiler = IPF_Project::getInstance()->sqlProfiler;
        if ($profiler !== null) {
            echo '<div style="padding:10px; margin:10px; background:#eee; border:1px dashed #888;"><h3>Sql Debug</h3><div style="color:#888">set <i>debug</i> to false in settings project for disable sql profiler</div>';
            $time = 0;
            foreach ($profiler->events as $event) {
                $time += $event->getElapsedSecs();
                $name = $event->getName();
                if ($name=='fetch' || $name=='prepare' || $name=='connect')
                    continue;
                echo "<br>\n<b>" . $name . "</b> " . sprintf("%f", $event->getElapsedSecs()) . "<br>\n";
                echo $event->getQuery() . "<br>\n";
                $params = $event->getParams();
                if( ! empty($params)) {
                    var_dump($params);
                    print "<br>\n";
                }
            }
            echo "<br>\n<b>Total time:</b> " . $time  . " (without prepare and fetch event)<br>\n";
            echo '</div>';
        }
    }
}

