<?php

class IPF_Utils {

    public static function isValidName( $s, $max_length=50 ){
        if (!is_string($s))
            return false;
        if ( (strlen($s)==0) || (strlen($s)>$max_length) )
            return false;
        if ( is_numeric($s[0]))
            return false;
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $s ))
            return false;
        return true;
    }

    public static function isEmail($value){
        $qtext = '[^\\x0d\\x22\\x5c\\x80-\\xff]';
        $dtext = '[^\\x0d\\x5b-\\x5d\\x80-\\xff]';
        $atom = '[^\\x00-\\x20\\x22\\x28\\x29\\x2c\\x2e\\x3a-\\x3c\\x3e\\x40\\x5b-\\x5d\\x7f-\\xff]+';
        $quotedPair = '\\x5c[\\x00-\\x7f]';
        $domainLiteral = "\\x5b($dtext|$quotedPair)*\\x5d";
        $quotedString = "\\x22($qtext|$quotedPair)*\\x22";
        $domain_ref = $atom;
        $subDomain = "($domain_ref|$domainLiteral)";
        $word = "($atom|$quotedString)";
        $domain = "$subDomain(\\x2e$subDomain)+";
        $localPart = "$word(\\x2e$word)*";
        $addrSpec = "$localPart\\x40$domain";
        return (bool) preg_match("!^$addrSpec$!D", $value);
    }

    static function prettySize($size)
    {
        $mb = 1024*1024;
        if ($size > $mb) {
            $mysize = sprintf('%01.2f', $size/$mb).' '. __('MB');
        } elseif ($size >= 1024) {
            $mysize = sprintf('%01.2f', $size/1024).' '.__('KB');
        } else {
            $mysize = sprintf('%01.2f', $size/1024).' '.__('bytes');
        }
        return $mysize;
    }

    static function cleanFileName($name, $path)
    {
        $name = mb_strtolower($name, 'UTF-8');
        $name = mb_ereg_replace("/\015\012|\015|\012|\s|[^A-Za-z0-9\.\-\_]/", '_', $name);

        while(file_exists($path.$name)){
            $pathinfo = pathinfo($name);
            $filename = $pathinfo['filename'];
            $split = explode('_', $filename);

            $n = count($split);
            if ($n<2){
                $filename .= '_2';
            }
            else{
                $x = $split[$n-1];
                if (is_numeric($x)){
                    $split[$n-1] = ((int)$x)+1;
                }
                else
                    $split[] = '2';
                $filename = '';
                foreach($split as $sp){
                    if ($filename!='') $filename.='_';
                    $filename .= $sp;
                }
            }
            $name = $filename.'.'.$pathinfo['extension'];
        }
        return $name;
    }

    static function isValidUrl($url)
    {
        $ip = '(25[0-5]|2[0-4]\d|[0-1]?\d?\d)(\.'
            .'(25[0-5]|2[0-4]\d|[0-1]?\d?\d)){3}';
        $dom = '([a-z0-9\.\-]+)';
        return (preg_match('!^(http|https|ftp|gopher)\://('.$ip.'|'.$dom.')!i', $url)) ? true : false;
    }

    static function humanTitle($s){
        $s = ucfirst(str_replace('_',' ',str_replace('_id','',$s)));
    	$ns = '';
    	for ($i=0; $i<strlen($s); $i++){
    		if ( ($i>0) && (ucfirst($s[$i-1])!=$s[$i-1]) && (ucfirst($s[$i])==$s[$i]) )
    			$ns .= ' ';
    		if ($s[$i]=='_')
    			$ns .= ' ';
    		else
    			$ns .= $s[$i];
    	}
    	return $ns;
    }

    static function randomString($len=35)
    {
        $string = '';
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lchars = strlen($chars);
        $i = 0;
        while ($i<$len) {
            $string .= substr($chars, mt_rand(0, $lchars-1), 1);
            $i++;
        }
        return $string;
    }

    static function dateCompare($date1, $date2=null)
    {
        if (strlen($date1) == 10){
            $date1 .= ' 23:59:59';
        }
        if (is_null($date2)) {
            $date2 = time();
        } else {
            if (strlen($date2) == 10) {
                $date2 .= ' 23:59:59';
            }
            $date2 = strtotime(str_replace('-', '/', $date2));
        }
        $date1 = strtotime(str_replace('-', '/', $date1));
        return $date2 - $date1;
    }

    static function appLabelByModel($model){
        foreach (IPF_Project::getInstance()->appList() as $app){
            foreach($app->modelList() as $m){
                if ($model==$m)
                    return strtolower($app->getLabel());
            }
        }
        return '';
    }

    public static function makeDirectories($path, $mode = 0777){
        if ( ! $path) {
            return false;
        }
        if (is_dir($path) || is_file($path)) {
            return true;
        }
        return mkdir(trim($path), $mode, true);
    }

    public static function removeDirectories($folderPath){
        if (is_dir($folderPath)){
            foreach (scandir($folderPath) as $value){
                if ($value != '.' && $value != '..'){
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
            unlink($folderPath);
        }
    }

    public static function copyDirectory($source, $dest){
        // Simple copy for a file
        if (is_file($source)) {
            return copy($source, $dest);
        }
        // Make destination directory
        if (!is_dir($dest)) {
            mkdir($dest);
        }
        // Loop through the folder
        $dir = dir($source);
        while (false !== $entry = $dir->read()){
            // Skip pointers
            if ($entry == '.' || $entry == '..') {
                continue;
            }
            // Deep copy directories
            if ($dest !== "$source/$entry") {
                self::copyDirectory("$source/$entry", "$dest/$entry");
            }
        }
        $dir->close();
        return true;
    }

    public static function print_r($subject, $ignore = array(), $depth = 5, $refChain = array())
    {
        $s = '';
        if ($depth > 20) return;
        if (is_object($subject)) {
            foreach ($refChain as $refVal)
                if ($refVal === $subject) {
                    $s .= "*RECURSION*\n";
                    return;
                }
            array_push($refChain, $subject);
            $s .= get_class($subject) . " Object ( \n";
            $subject = (array) $subject;
            foreach ($subject as $key => $val)
                if (is_array($ignore) && !in_array($key, $ignore, 1)) {
                    $s .= str_repeat(" ", $depth * 4) . '[';
                    if ($key{0} == "\0") {
                        $keyParts = explode("\0", $key);
                        $s .= $keyParts[2] . (($keyParts[1] == '*')  ? ':protected' : ':private');
                    } else
                        $s .= $key;
                    $s .= '] => ';
                    IPF_Utils::print_r($val, $ignore, $depth + 1, $refChain);
                }
            $s .= str_repeat(" ", ($depth - 1) * 4) . ")\n";
            array_pop($refChain);
        } elseif (is_array($subject)) {
            $s .= "Array ( \n";
            foreach ($subject as $key => $val)
                if (is_array($ignore) && !in_array($key, $ignore, 1)) {
                    $s .= str_repeat(" ", $depth * 4) . '[' . $key . '] => ';
                    IPF_Utils::print_r($val, $ignore, $depth + 1, $refChain);
                }
            $s .= str_repeat(" ", ($depth - 1) * 4) . ")\n";
        } else
            $s .= $subject . "\n";
        return $s;
    }

    public static function timestamp(){
		list($f,$i) = explode(' ',microtime());
		return $i.substr((string)$f,2,6);
    }

    static function TrimP($html){
        $strL = "<p>";  $lenL = 3;
        $strR = "</p>"; $lenR = 4;
        if (0 == strcasecmp(substr($html, 0, $lenL), $strL)
        &&  0 == strcasecmp(substr($html, -$lenR), $strR)){
            return substr($html, $lenL, strlen($html) - ($lenL + $lenR));
        }
        return $html;
    }

    static function moneyFormat($val){
        return number_format((float)$val,2);
    }
    
    static function toSlug($slug){
        if ($slug){ 
            return strtolower(preg_replace('/[^A-Z^a-z^0-9^\/\_]+/', '-', $slug));
        }
        return $slug;
    }
    

}

