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

function isWindows() {
	return isset($_SERVER['OS']) && ($_SERVER['OS']=='Windows_NT');
}

function isUnix() {
	return !isWindows();		// ok, its a hack. Otherwise I'd have to get all the codes for Mac, Linux, Solaris etc
}

// Executes the given command and returns the result as a string, even if returned as array
// If the exit code of the command is non-zero, it will die with the result as a message
function execSafe($command) {
	exec($command,$result,$retcode);
	if (is_array($result))
		$result = join("\n", $result);
	if ($retcode) {
		if (!$result)
			$result = $command." failed.";
		die($result);
	}
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
	$fh = fopen($filename,"rb") or die("can't open file");
	$fs = filesize($filename);
	if ($fs==0)
		die('file is zero size');
	$result = fread($fh,$fs);
	fclose($fh);
	return $result;
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
	if ($path=='.')
		return NULL;
	$path = dirname($path);
	if ($path=='.')
		return NULL;
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

function setProperty($object, $property, $value) {
	if (is_array($object))
		$object[$property] = $value;
	else
		$object->{$property} = $value;
	return $value;
}

function getProperty($object, $property) {
	if (is_array($object))
		return $object[$property];
	else
		return $object->{$property};
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
		if ($sepPos!=false) {							// subproperty
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


/**
 *
 * PHP versions 5.1.4
 *
 * George A. Papayiannis
 *
 * This class provides the magic functions needed to create
 * a dynamic object.  Subclasses would extend this object
 * and call the constructor with a parsed array.  See
 * g_url_decode.class.php for an example of creating a
 * dynamic object from the URL query string.
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
/*
class DynamicObject implements IteratorAggregate {
	private $param = array();

	public function __construct($init) {
			$this->param = get_object_vars($init);
	}

	private function __get($name) {
		if (isset($this->param[$name]))
			return $this->param[$name];
		else
			return NULL;
	}

	private function __set($name, $val) {
		$this->param[$name] = $val;
	}

	private function __isset($name) {
		return isset($this->param[$name]);
	}

	private function __unset($name) {
		unset($this->param[$name]);
	}

	private function __call($name, $var) {
			// add code to simulate function call
			// return TRUE for success
	}

	// for IteratorAggregate interface
	public function getIterator() {
		return $this->param->getIterator();
	}
}
*/

?>
