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

function svn($command,$sourceServer,$sourceUrl,$destPath,$options = NULL) {
	if (!$options)
		$options = '';
	print("svn ".$command." ".$options." ".$sourceUrl." => ".$destPath."\n");
	$options = $options.' --username prod --password kkfgt';
	$cmd = "svn ".$command." ".$sourceServer.$sourceUrl." \"".$destPath."\" ".$options;
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
		return strcmp($ss,$needle);
	else
		return strcasecmp($ss,$needle);
}

function endsWith($haystack, $needle, $case=true) {
	$length = strlen($needle);
	$start =  $length *-1; //negative
	$ss = substr($haystack, $start, $length);
	if ($case)
		return strcmp($ss,$needle);
	else
		return strcasecmp($ss,$needle);
}

?>
