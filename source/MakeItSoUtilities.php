<?php
/* makeitso
 *
 * The MIT License
 *
 * Copyright (c) 2010, Gary McGhee, Buzzware Solutions <contact@buzzware.com.au>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 */

require_once 'SimpleConsoleLogger.php';

class DynamicObject extends ArrayObject {

	public function __get($name) {
		if (isset($this[$name]))
			return $this[$name];
		else
			return null;
	}

	public function __set($name, $val) {
		return $this[$name] = $val;
	}
}

interface IException {

		// from http://php.net/manual/en/language.exceptions.php

    /* Protected methods inherited from Exception class */
    public function getMessage();                 // Exception message
    public function getCode();                    // User-defined Exception code
    public function getFile();                    // Source filename
    public function getLine();                    // Source line
    public function getTrace();                   // An array of the backtrace()
    public function getTraceAsString();           // Formated string of trace

    /* Overrideable methods inherited from Exception class */
    public function __toString();                 // formated string for display
    public function __construct($message = null, $code = 0);
}

class CustomException extends Exception implements IException {

    protected $message = 'Unknown exception';     // Exception message
    private   $string;                            // Unknown
    protected $code    = 0;                       // User-defined exception code
    protected $file;                              // Source filename of exception
    protected $line;                              // Source line of exception
    private   $trace;                             // Unknown

    public function __construct($message = null, $code = 0)
    {
        if (!$message) {
            throw new $this('Unknown '. get_class($this));
        }
        parent::__construct($message, $code);
    }

    public function __toString()
    {
        return get_class($this).": {$this->message} in {$this->file}({$this->line})\n\n"
                                . "{$this->getTraceAsString()}";
    }
}
/*
class ErrorAsException extends Exception {

	public static function errorHandlerCallback($code, $string, $file, $line, $context) {
		print "errorHandlerCallback";
    $exception=new ErrorAsException($string, $code);
    $exception->setLine($line);
    $exception->setFile($file);
    throw $exception;
	}

	public function setLine($line) {
			$this->line=$line;
	}

	public function setFile($file) {
			$this->file=$file;
	}
}
set_error_handler(array("ErrorAsException", "errorHandlerCallback"), E_ALL);
*/
/*
class ErrorAsException extends Exception {
    public function setLine($line) {
        $this->line=$line;
    }

    public function setFile($file) {
        $this->file=$file;
    }
}

function exceptionsHandler($code, $string, $file, $line) {
    $exception=new ErrorAsException($string, $code);
    $exception->setLine($line);
    $exception->setFile($file);
    throw $exception;
}
set_error_handler('exceptionsHandler', E_ALL);

register_shutdown_function('shutdownFunction');
function shutDownFunction() {
    $error = error_get_last();
    //if ($error['type'] == 1) {
        try {
					throw new Exception("You'll never get me!");
        } catch (Exception $e) {
					error_log(get_class($e)." thrown within the shutdown handler. Message: ".$e->getMessage(). "  in " . $e->getFile() . " on line ".$e->getLine());
					error_log('Exception trace stack: ' . print_r($e->getTrace(),1));
        }
    //}
}
*/
/* should look like this :

Exception: hello in /Users/gary/repos/decimal/projects/deployment_dev/test/MockHow.php on line 21

Call Stack:
    0.0045      73272   1. {main}() /Users/gary/repos/decimal/tools/source/makeItSo/source/makeitso:0
    0.0599     952836   2. MakeItHowBase->callDefaultTask() /Users/gary/repos/decimal/tools/source/makeItSo/source/makeitso:87
    0.0599     952940   3. MakeItHowBase->callTask() /Users/gary/repos/decimal/tools/source/makeItSo/source/MakeItHowBase.php:227
    0.0600     953584   4. MakeItHow->except() /Users/gary/repos/decimal/tools/source/makeItSo/source/MakeItHowBase.php:113

but looks like :

PHP Fatal error:  Uncaught exception 'Exception' with message 'hello' in /Users/gary/repos/decimal/projects/deployment_dev/test/MockHow.php:21
Stack trace:
#0 /Users/gary/repos/decimal/tools/source/makeItSo/source/MakeItHowBase.php(113): except()
#1 /Users/gary/repos/decimal/tools/source/makeItSo/source/MakeItHowBase.php(227): callTask(string)
#2 /Users/gary/repos/decimal/tools/source/makeItSo/source/makeitso(87): callDefaultTask()
#3 {main}
  thrown in /Users/gary/repos/decimal/projects/deployment_dev/test/MockHow.php on line 21


*/
//If you're handling sensitive data and you don't want exceptions logging details such as variable contents when you throw them, you may find yourself frustratedly looking for the bits and pieces that make up a normal stack trace output, so you can retain its legibility but just alter a few things. In that case, this may help you:
function standardExceptionHandler($exception) {
	// these are our templates
	$traceline = "  %s. %s->%s() %s:%s";
	//$msg = "PHP Fatal error:  Uncaught exception '%s' with message '%s' in %s:%s\nStack trace:\n%s\n  thrown in %s on line %s";
	$msg = "\n  %s: %s in %s on line %s\n\nCall Stack:\n%s\n";

	// alter your trace as you please, here
	$trace = $exception->getTrace();
	foreach ($trace as $key => $stackPoint) {
			// I'm converting arguments to their type
			// (prevents passwords from ever getting logged as anything other than 'string')
			$trace[$key]['args'] = array_map('gettype', $trace[$key]['args']);
	}

	// build your tracelines
	$result = array();
	foreach ($trace as $key => $stackPoint) {
			$result[] = sprintf(
					$traceline,
					$key,
					getProperty($stackPoint,'class'),
					getProperty($stackPoint,'function'),
					getProperty($stackPoint,'file'),
					getProperty($stackPoint,'line'),
					implode(', ', getProperty($stackPoint,'args'))
			);
	}
	// trace always ends with {main}
	//$result[] = '#' . ++$key . ' {main}';

	// write tracelines into main template
	$msg = sprintf(
			$msg,
			get_class($exception),
			$exception->getMessage(),
			$exception->getFile(),
			$exception->getLine(),
			implode("\n", $result),
			$exception->getFile(),
			$exception->getLine()
	);
	if (get_class($exception)=='ExecException') {
		$msg .= "\nexitCode: ".$exception->exitCode."\n";
		$msg .= "result: \n".$exception->result."\n";
	}
	$msg .= "\n";

	// log or echo as you please
	error_log($msg);
	return $msg;
}

function errorReport($errno, $errstr, $errfile, $errline) {
	$result = '';
	switch ($errno) {
		case E_USER_ERROR:
			$result .= "E_USER_ERROR $errstr\n  on line $errline in file $errfile\n";
			break;

		case E_USER_WARNING:
			$result .= "E_USER_WARNING $errstr\n  on line $errline in file $errfile\n";
			break;

		case E_USER_NOTICE:
			$result .= "E_USER_NOTICE $errstr\n  on line $errline in file $errfile\n";
			break;

		default:
			$result .= "Unknown error type: [$errno] $errstr\n  on line $errline in file $errfile\n";
			break;
	}
	return $result;
}

function createSimpleConsoleLogger() {
	return new SimpleConsoleLogger();
}

class MakeItSo {

		//global logger access like MakeItSo::log()->info('hello');
    private static $_log;

		public static function log() {
			if (!MakeItSo::$_log)
				MakeItSo::$_log = createSimpleConsoleLogger();
			return MakeItSo::$_log;
		}

		public static function setLog($log) {
			MakeItSo::$_log = $log;
		}
		
    // A private constructor; prevents direct creation of object
    private function __construct() {
		}

    // Prevent users to clone the instance
    public function __clone() {
			trigger_error('Clone is not allowed.', E_USER_ERROR);
    }
}

function isWindows() {
	return isset($_SERVER['OS']) && ($_SERVER['OS']=='Windows_NT');
}

function isUnix() {
	return !isWindows();		// ok, its a hack. Otherwise I'd have to get all the codes for Mac, Linux, Solaris etc
}

class ExecException extends CustomException {
	public $result;
	public $exitCode;
}

function isSimple($value) {
	return (is_null($value) || is_string($value) || is_int($value) || is_bool($value) || is_float($value));
}

function to_string($value) {
	if (isSimple($value))
		return (string) $value;
	else
		return '';
}

function get_simple_object_vars($object) {
	$result = array();
	$props = get_object_vars($object);
	foreach ($props as $k => $v) {
		if (!isSimple($k) || !isSimple($v))
			continue;
		$result[$k] = $v;
	}
	return $result;
}



// Executes the given command and returns the result as a string, even if returned as array
// If the exit code of the command is non-zero, it will die with the result as a message
function execSafe($command,$workingFolder=null) {
	$dir_before = getcwd();
	if ($workingFolder)
		chdir($workingFolder);
	exec($command,$result,$retcode);
	chdir($dir_before);
	if (is_array($result))
		$result = join("\n", $result);
	//print $result;
	if ($retcode) {
		$e = new ExecException($command." failed (exit code ".$retcode.")",$retcode);
		$e->result = $result;
		$e->exitCode = $retcode;
		throw $e;
	}
	return $result;
}

function svn_cmd($command,$sourceServer,$sourceUrl,$destPath,$options = null) {
	if (!$options)
		$options = '';
	$cmd = "svn ".$command.' "'.$sourceServer.$sourceUrl.'" "'.$destPath.'" '.$options;
	return $cmd;
}

function svn($command,$sourceServer,$sourceUrl,$destPath,$options = null) {
	if (!$options)
		$options = '';
	print("svn ".$command." ".$options." ".$sourceUrl." => ".$destPath);
	$cmd = svn_cmd($command,$sourceServer,$sourceUrl,$destPath,$options);
	$result = execSafe($cmd);
	print($result);
	return $result;
}

function svnGetInfo($path) {
	$cmd = 'svn info "'.$path.'"';
	$cmdresult = execSafe($cmd);
	$cmdresult = explode("\n", $cmdresult);
	$result = array();

	foreach ($cmdresult as $line) {
		$parts = explode(": ", $line);
		if (sizeof($parts)!=2 || !$parts[0] || !$parts[1])
			continue;
		$result[$parts[0]] = $parts[1];
	}
	if (($url = $result['URL']) && ($root = $result['Repository Root']) && startsWith($url,$root)) {
		$result['Short URL'] = substr($url,strlen($root));
	}
	return $result;
}

function svnCheckoutCmd($config,$command='checkout') {
	if (is_array($config))
		$config = (object) $config;

	$options = array();
	if ($config->svnUsername)
		$options[] = '--username '.$config->svnUsername;
	if ($config->svnPassword)
		$options[] = '--password '.$config->svnPassword;
	if ($config->revision)
		$options[] = '--revision '.$config->revision;
	$options = join(' ',$options);

	$rep = rtrim($config->repository,'/');
	$url = $config->branch;
	if (!startsWith($url,'/'))
		$url = '/'.$url;
	if ($config->source)
		$url = $url.'/'.$config->source;
	$url = rtrim($url,'/');

	return svn_cmd(
		$command,
		$rep,
		$url,
		$config->destination,
		$options
	);
}

function svnBranchName($svnInfo) {
	$shortUrl = $svnInfo["Short URL"];
	if (startsWith($shortUrl, '/trunk')) {
		return 'trunk';
	} else {
		$branchesPos = strpos($shortUrl,'/branches/');
		if ($branchesPos<0)
			return $shortUrl;
		//$urlAfterBranches = substr($shortUrl,$branchesPos+10);
		$branchParts = explode('/',$shortUrl);
		return implode('/',array($branchParts[0],$branchParts[1],$branchParts[2]));
	}
	return $shortUrl;
}

// Don't use - for backwards compatibility
function get_svn_info($path) {
	return svnGetInfo($path);
}

function rmWildcard($mask) {
	array_map( "unlink", glob( $mask ) );
}

function fileToString($filename) {
	/*
	$fh = fopen($filename,"rb") or die("can't open file");
	$fs = filesize($filename);
	if ($fs==0)
		die('file is zero size');
	$result = fread($fh,$fs);
	fclose($fh);
	return $result;
	*/
	return file_get_contents($filename);
}

function ensurePath($path) {
	if (pathExists($path))
		return;
	if (!mkdir($path,0,true))
		throw new Exception('Failed creating '.$path);
}

function fileFromString($filename,$content) {
	$fh = fopen($filename, 'wb') or die("can't open file");
	fwrite($fh, $content);
	fclose($fh);
}

function rglob($pattern='*', $path='', $flags = 0) {
		$paths=glob($path.'*', GLOB_MARK|GLOB_ONLYDIR|GLOB_NOSORT);
		$files=glob($path.$pattern, $flags);
		foreach ($paths as $path) { $files=array_merge($files,rglob($pattern, $path, $flags)); }
		return $files;
}

function searchReplaceFiles($find,$replace,$filepattern) {
	print "searchReplaceFiles ".$find." WITH ".$replace." in ".$filepattern."\n";
	$files = glob($filepattern);
	foreach ($files as $file) {
			$content = fileToString($file);
			$content = str_replace($find,$replace,$content);
			fileFromString($file,$content);
	}
}

function expandTokensInFiles($tokenValues,$filepattern) {
	print "expandTokensInFiles in ".$filepattern."\n";
	$files = glob($filepattern);
	foreach ($files as $file) {
		$content = fileToString($file);
		foreach ($tokenValues as $key => $value) {
			$content = str_replace('{{'.$key.'}}',$value,$content);
		}
		fileFromString($file,$content);
	}
}

function renderTemplateFromSampleFile($sampleFile, $destFile, $tokens = null) {
	if (file_exists($destFile . '.bak'))
		unlink($destFile . '.bak');
	if (file_exists($destFile))
		rename($destFile,$destFile . '.bak');
	if (file_exists($sampleFile)) {
		copy($sampleFile, $destFile);
	} else {
		throw new Exception("The sample file ($sampleFile) does not exist");
	}
	if($tokens) {
		fileReplaceTokens($destFile, $tokens);
	}
}

function rmGlob($pattern) {
	foreach (glob($pattern) as $file)
		unlink($file);
}

function fileReplaceTokens($filename,$tokens) {
	$content = fileToString($filename);
	
	foreach ($tokens as $key => $value) {
		$content = str_replace('{{'.$key.'}}',$value,$content);
	}
	fileFromString($filename,$content);
}

function copy_properties($source,&$dest) {
	foreach ($source as $key => $value) {
		setProperty($dest,$key,getProperty($source,$key));
	}
	return $dest;
}

function ensureSlash($path){
	if (!$path)
		return $path;
	$slash_type = (strpos($path, '\\')===0) ? 'win' : 'unix'; 
	$last_char = substr($path, strlen($path)-1, 1);
	if ($last_char != '/' and $last_char != '\\') {
			// no slash:
			$path .= ($slash_type == 'win') ? '\\' : '/';
	}
	return $path;
}

function pathParent($path) {
	if ($path=='/')
		return null;
	if (preg_match('/^[a-z]:\x5C$/i', $path))
		return null;
	if ($path=='.')
		return null;
	$path = dirname($path);
	if ($path=='.')
		return null;
	return $path;	
}

/**
 * Takes one or more file names and combines them, using the correct path separator for the 
 *         current platform and then return the result.
 * Example: joinPath('/var','www/html/','try.php'); // returns '/var/www/html/try.php'
 * Link: http://www.bin-co.com/php//scripts/filesystem/join_path/
 */
function joinPaths() {
    $path = '';
    $arguments = func_get_args();
    $args = array();
    foreach($arguments as $a) {   //Removes the empty elements
	    if($a == '')
		    continue;
			$args[] = $a;
    }
    
    $arg_count = count($args);
    for($i=0; $i<$arg_count; $i++) {
        $folder = $args[$i];
        
        if($i != 0 and $folder[0] == DIRECTORY_SEPARATOR) $folder = substr($folder,1); //Remove the first char if it is a '/' - and its not in the first argument
        if($i != $arg_count-1 and substr($folder,-1) == DIRECTORY_SEPARATOR) $folder = substr($folder,0,-1); //Remove the last char - if its not in the last argument
        
        $path .= $folder;
        if($i != $arg_count-1) $path .= DIRECTORY_SEPARATOR; //Add the '/' if its not the last element.
    }
    return $path;
}

function joinUrl() {
    $path = '';
    $arguments = func_get_args();
    $args = array();
    foreach($arguments as $a) {   //Removes the empty elements
	    if($a == '')
		    continue;
			$args[] = $a;
    }

    $arg_count = count($args);
    for($i=0; $i<$arg_count; $i++) {
        $folder = $args[$i];

        if($i != 0 and $folder[0] == '/') $folder = substr($folder,1); //Remove the first char if it is a '/' - and its not in the first argument
        if($i != $arg_count-1 and substr($folder,-1) == '/') $folder = substr($folder,0,-1); //Remove the last char - if its not in the last argument

        $path .= $folder;
        if($i != $arg_count-1) $path .= '/'; //Add the '/' if its not the last element.
    }
    return $path;
}

function getDirContents($path) {
	$result = array();
	$dp = opendir($path);
	While($item = readdir($dp)) {
		if ($item[0] != '.') {
			$result[] = $item;
		}
	}
	return $result;
}

function insertSubExtension($filename,$subext) {
	$dotPos = strrpos($filename,'.');
	$basename = substr($filename,0,$dotPos);
	$ext = substr($filename,$dotPos);
	$result = $basename.'.'.$subext.$ext;
	return $result;
}

function splitFilename($filename) { 
	$pos = strrpos($filename, '.'); 
	if ($pos === false) { // dot is not found in the filename 
		return array($filename, ''); // no extension 
	} else { 
		$basename = substr($filename, 0, $pos); 
		$extension = substr($filename, $pos+1); 
		return array($basename, $extension); 
	} 
}

function pathExists($path) {
	return file_exists($path) || is_dir($path);
}

function findFileUpwards($findPath,$startPath=null) {
	if (!$startPath)
		$startPath = getcwd();
	$currPath = ensureSlash(realpath($startPath));
	
	while ($currPath && !($testPathExists = pathExists($testPath = $currPath.$findPath))) {
		$currPath = ensureSlash(pathParent($currPath));
	}
	return $currPath && $testPathExists ? $testPath : null;
}

function hostName() {
	return gethostbyaddr('127.0.0.1');
}

//function endsWith($string, $test) {
//    $strlen = strlen($string);
//    $testlen = strlen($test);
//    if ($testlen > $strlen) return false;
//    return substr_compare($string, $test, -$testlen) === 0;
//}

function startsWith($haystack, $needle, $case=true) {
	$length = strlen($needle);
	$ss = substr($haystack, 0, $length);
	if ($case)
		return strcmp($ss,$needle)==0;
	else
		return strcasecmp($ss,$needle)==0;
}

function endsWith($haystack, $needle, $case=true) {
	$length = strlen($needle);
	$start =  $length *-1; //negative
	$ss = substr($haystack, $start, $length);
	if ($case)
		return strcmp($ss,$needle)==0;
	else
		return strcasecmp($ss,$needle)==0;
}

function setProperty(&$object, $property, $value) {
	if (is_array($object))
		$object[$property] = $value;
	else
		$object->{$property} = $value;
	return $value;
}

function getProperty($object, $property, $default=null) {
	if (is_array($object) || is_a($object,'ArrayObject')) {
		if (isset($object[$property]))
			return $object[$property];
		else
			return $default;
	} else {
		if (property_exists($object,$property)) {
			return $object->{$property};
		} else {
			return $default;
		}
	}
}

function envVar($name) {
	$result = getenv($name);
	return $result===false ? null : $result;
}

/*
This is not a complete solution as it won't recurse forever expanding tokens that contain tokens.

A proper solution will probably involve iterating through token instances, replacing, watching for none to replace,
and tokens that aren't defined.
*/
function expandConfigTokens($config) {
	$result = is_array($config) ? $config : get_object_vars($config);

	foreach ($result as $configKey => $configValue) {
		if (!is_string($configValue))
			continue;
		// if $configValue contains {{configKey}} then exception
		// for all properties besides this one, replace {{key}} with $value
		foreach ($result as $tokenKey => $tokenValue) {
			if ($tokenKey==$configKey)
				continue;
			if (!(is_string($tokenValue) || is_int($tokenValue) || is_bool($tokenValue) || is_float($tokenValue)))
				continue;
			$result[$configKey] = str_replace('{{'.$tokenKey.'}}',(string) $tokenValue,(string) $result[$configKey]);
		}
	}
	if (!is_array($config))
		$result = new DynamicObject($result);
	return $result;
}

function setPropertiesFromXmlItems($object,$xmlNode,$selectedProperty=null,$includeFlatProperties=true) {
	foreach ($xmlNode->item as $item) {
		$name = (string) $item['name'];
		$value = (string) $item[0];
		$sepPos = strpos($name,'/');
		if ($sepPos!==false) {							// subproperty
			$topLevel = substr($name,0,$sepPos);
			$name = substr($name,$sepPos+1);
			if ($topLevel==$selectedProperty)
				setProperty($object,$name,$value);
		} else {													// flat property
			if ($includeFlatProperties)
				setProperty($object,$name,$value);
		}
	}
	return $object;
}

function loadSimpleItems($filename,$object=null) {
	$filestring = file_get_contents($filename); // load $whatname to $filestring
	$fileXml = new SimpleXMLElement($filestring);
	if (!$object)
		$object = new DynamicObject();
	setPropertiesFromXmlItems($object,$fileXml->simpleItems);
	return $object;
}

function saveSimpleItems($object,$filename,$rootTag) {
	$result = array('<?xml version="1.0" encoding="UTF-8"?>');
	$result[] = '<'.$rootTag.'>';
	$result[] = '<simpleItems>';
	$props = get_simple_object_vars($object);
	foreach ($props as $k => $v) {
		$result[] = '<item name="'.to_string($k).'">'.to_string($v).'</item>';
	}
	$result[] = '</simpleItems>';
	$result[] = '</'.$rootTag.'>';
	$result = implode("\n",$result);  // turn into a string
	fileFromString($filename,$result);
}


function loadCascadingXmlFileItems($object,$filename,$machine_name = null) {
	$fn = $filename;
	if (file_exists($fn))
		loadSimpleItems($fn,$object);
	if ($machine_name) {
		$fn = insertSubExtension($filename,$machine_name);
		if (file_exists($fn))
			loadSimpleItems($fn,$object);
	}
	$fn = insertSubExtension($filename,'local');
	if (file_exists($fn))
		loadSimpleItems($fn,$object);
	return $object;
}

function getXpathNodes($path,$xml) {
	if (!$xml)
		return null;
	return $xml->xpath($path);		// get matching nodes
}

function getXpathNode($path,$xml) {
	$nodes = getXpathNodes($path,$xml);
	if (count($nodes)==0)
		return null;
	return $nodes[0];						// get first node
}

function getXpathValue($path,$xml) {
	$node = getXpathNode($path,$xml);
	if (!$node)
		return null;
	$result = (string) $node[0];	// get text of node
	return $result;								// return text
}

function dateTimeNumerical() {
	$d = new DateTime();
	return $d->format('Ymd-His');
}

function dateTimeString($d = null) {
	if (!$d)
		$d = new DateTime();
	return $d->format('Y-m-d H:i:s');
}

// from http://nadeausoftware.com/articles/2007/07/php_tip_how_get_web_page_using_fopen_wrappers
//The returned array contains:
//
//"http_code"	the page status code (e.g. "200" on success)
//"header"	the header as an array with one entry per header line
//"content"	the page content (e.g. HTML text, image bytes, etc.)
//On success, "http_code" is 200, and "content" contains the web page.
//
//On an error with a bad URL, unknown host, a timeout, or a redirect loop, a null is returned.
//
//On an error with the web site, such as a missing page or no permissions, "http_code" has a non-200 HTTP status code, and "content" contains the sit�s error message page (see Wikipedia�s List of HTTP status codes).
//
// The fopen wrappers are available in PHP 4.0.4 and later. They must be enabled in the php.ini file by setting allow_url_fopen to TRUE. For most installations, this is the default.
//
// Example
//Read a web page and check for errors:
//$result = httpGet( $url );
//if ( $result == null )
//    ... error: bad url, timeout, redirect loop ...
//if ( $result['http_code'] != 200 )
//    ... error: no page, no permissions, no service ...
//$page = $result['content'];
function httpGet($url) {
	$options = array( 'http' => array(
		'user_agent'		=> 'spider',		// who am i
		'max_redirects' => 10,					// stop after 10 redirects
		'timeout' 			=> 120, 				// timeout on response
	) );
	$context = stream_context_create( $options );
	$page 	 = @file_get_contents( $url, false, $context );

	$result  = array( );
	if ( $page != false )
		$result['content'] = $page;
	else if ( !isset( $http_response_header ) )
		return null;		// Bad url, timeout

	// Save the header
	$result['header'] = $http_response_header;

	// Get the *last* HTTP status code
	$nLines = count( $http_response_header );
	for ( $i = $nLines-1; $i >= 0; $i-- ) {
		$line = $http_response_header[$i];
		if ( strncasecmp( "HTTP", $line, 4 ) == 0 ) {
			$response = explode( ' ', $line );
			$result['http_code'] = $response[1];
			break;
		}
	}
	return $result;
}

function httpGetSimple($sourceUrl,$destFile,$options=null) {
	$result = httpGet( $sourceUrl );
	if ( $result == null )
		return null;
	if ( $result['http_code'] != 200 )
		return null;
	$page = $result['content'];
	file_put_contents($destFile,$page);
	MakeItSo::log()->info('Downloaded '.$sourceUrl.' to '.$destFile);
	return $result;
}

function httpGetString($sourceUrl,$options=null) {
	$result = httpGet( $sourceUrl);
	if ( $result == null )
		return null;
	if ( $result['http_code'] != 200 )
		return null;
	return $result['content'];
}

?>
