<?php

class IPF_Template_Tag_Sql extends IPF_Template_Tag
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
