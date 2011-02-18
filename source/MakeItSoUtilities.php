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


class DynamicObject extends ArrayObject {

	public function __get($name) {
		if ($name=='revision')
			print('revision');
		if (isset($this[$name]))
			return $this[$name];
		else
			return NULL;
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

abstract class CustomException extends Exception implements IException {

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
        return get_class($this) . " '{$this->message}' in {$this->file}({$this->line})\n"
                                . "{$this->getTraceAsString()}";
    }
}


function isWindows() {
	return isset($_SERVER['OS']) && ($_SERVER['OS']=='Windows_NT');
}

function isUnix() {
	return !isWindows();		// ok, its a hack. Otherwise I'd have to get all the codes for Mac, Linux, Solaris etc
}

class ExecException extends CustomException {}

// Executes the given command and returns the result as a string, even if returned as array
// If the exit code of the command is non-zero, it will die with the result as a message
function execSafe($command,$workingFolder=NULL) {
	$dir_before = getcwd();
	if ($workingFolder)
		chdir($workingFolder);
	exec($command,$result,$retcode);
	chdir($dir_before);
	if (is_array($result))
		$result = join("\n", $result);
	print $result;
	if ($retcode)
		throw new ExecException($command." failed (exit code ".$retcode.")",$retcode);
	return $result;
}

function svn_cmd($command,$sourceServer,$sourceUrl,$destPath,$options = NULL) {
	if (!$options)
		$options = '';
	$cmd = "svn ".$command.' "'.$sourceServer.$sourceUrl.'" "'.$destPath.'" '.$options;
	return $cmd;
}

function svn($command,$sourceServer,$sourceUrl,$destPath,$options = NULL) {
	if (!$options)
		$options = '';
	print("svn ".$command." ".$options." ".$sourceUrl." => ".$destPath."\n");
	$cmd = svn_cmd($command,$sourceServer,$sourceUrl,$destPath,$options);
	$result = execSafe($cmd);
	print($result."\n\n");
	return $result;
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

function rmGlob($pattern) {
	foreach (glob($pattern) as $file)
		unlink($file);
}

function fileReplaceTokens($filename,$tokens) {
	$content = fileToString($filename);
	
	foreach ($tokens as $key => $value) {
		$content = str_replace('{{'.$key.'}}',$value,$content);
	}
	fileFromString($file,$content);
}

function copy_properties($source,$dest) {
	foreach ($source as $key => $value)
		$dest->{$key} = $source->{$key};
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
		return NULL;
	if (preg_match('/^[a-z]:\x5C$/i', $path))
		return NULL;
	if ($path=='.')
		return NULL;
	$path = dirname($path);
	if ($path=='.')
		return NULL;
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
    foreach($arguments as $a) if($a !== '') $args[] = $a;//Removes the empty elements
    
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


function insertSubExtension($filename,$subext) {
	$dotPos = strrpos($filename,'.');
	$basename = substr($filename,0,$dotPos);
	$ext = substr($filename,$dotPos);
	$result = $basename.'.'.$subext.$ext;
	return $result;
}

function pathExists($path) {
	return file_exists($path) || is_dir($path);
}

function findFileUpwards($findPath,$startPath=NULL) {
	if (!$startPath)
		$startPath = getcwd();
	$currPath = ensureSlash(realpath($startPath));
	
	while ($currPath && !($testPathExists = pathExists($testPath = $currPath.$findPath))) {
		$currPath = ensureSlash(pathParent($currPath));
	}
	return $currPath && $testPathExists ? $testPath : NULL;
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

function getProperty($object, $property) {
	if (is_array($object)) {
		if (isset($object[$property]))
			return $object[$property];
		else
			return NULL;
	} else {
		if (isset($object->{$property}))
			return $object->{$property};
		else
			return NULL;
	}
}

function getEnvVar($name) {
	$result = getenv($name);
	return $result===false ? NULL : $result;
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

function setPropertiesFromXmlItems($object,$xmlNode,$selectedProperty=NULL,$includeFlatProperties=true) {
	foreach ($xmlNode->item as $item) {
		$name = (string) $item['name'];
		$value = (string) $item[0];
		$sepPos = strpos($name,'/');
		if ($sepPos!==false) {							// subproperty
			$topLevel = substr($name,0,$sepPos);
			$name = substr($name,$sepPos+1);
			if ($topLevel==$selectedProperty)
				$object->{$name} = $value;	//setProperty ($object,$name,$value);
		} else {													// flat property
			if ($includeFlatProperties)
				$object->{$name} = $value;	//setProperty ($object,$name,$value);
		}
	}
	return $object;
}

function loadSimpleItems($filename,$object=NULL) {
	$filestring = file_get_contents($filename); // load $whatname to $filestring
	$fileXml = new SimpleXMLElement($filestring);
	if (!$object)
		$object = new DynamicObject();
	setPropertiesFromXmlItems($object,$fileXml->simpleItems);
	return $object;
}

function loadCascadingXmlFileItems($object,$filename,$machine_name = NULL) {
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




?>
