<?php

class IPF_Chart{

    // $chartSWF - SWF File Name (and Path) of the chart which you intend to plot
	// $strURL - If you intend to use dataURL method for this chart, pass the URL as this parameter. Else, set it to "" (in case of dataXML method)
	// $strXML - If you intend to use dataXML method for this chart, pass the XML data as this parameter. Else, set it to "" (in case of dataURL method)
	// $chartId - Id for the chart, using which it will be recognized in the HTML page. Each chart on the page needs to have a unique Id.
	// $chartWidth - Intended width for the chart (in pixels)
	// $chartHeight - Intended height for the chart (in pixels)
	// $debugMode - Whether to start the chart in debug mode
	// $registerWithJS - Whether to ask chart to register itself with JavaScript
	static function RenderChart($chartSWF, $strURL, $strXML, $chartId, $chartWidth, $chartHeight, $debugMode=false, $registerWithJS=false, $setTransparent="") {
		if ($strXML=="")
	        $tempData = "//Set the dataURL of the chart\n\t\tchart_$chartId.setDataURL(\"$strURL\")";
	    else
	        $tempData = "//Provide entire XML data using dataXML method\n\t\tchart_$chartId.setDataXML(\"$strXML\")";

	    $chartIdDiv = $chartId . "Div";
	    $ndebugMode = IPF_Chart::BoolToNum($debugMode);
	    $nregisterWithJS = IPF_Chart::BoolToNum($registerWithJS);
		$nsetTransparent=($setTransparent?"true":"false");
		$render_chart =
		"<div id=\"$chartIdDiv\">Chart</div>\n".
		"<script type=\"text/javascript\">var chart_$chartId = new FusionCharts('$chartSWF', '$chartId', '$chartWidth', '$chartHeight', '$ndebugMode', '$nregisterWithJS'); chart_$chartId.setTransparent('$nsetTransparent');	$tempData;	chart_$chartId.render('$chartIdDiv');</script>";
	  	return $render_chart;
	}

	static function BoolToNum($bVal) {
    	return (($bVal==true) ? 1 : 0);
	}
}
