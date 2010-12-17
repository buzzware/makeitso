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

require_once 'Console_Getargs_Combined.php';

/**
 * Description of MakeItHow
 *
 * @author gary
 */
class MakeItHowBase {

	var $howFilePath;		// path to MakeItHow.php
	var $whatFilePath;	// path to MakeItWhat.xml
	var $workingPath;		// current working path

	var $whatXml;				// MakeItWhat.xml loaded root node
	var $task = 'main';	// task to execute
	
	static function endswith($string, $test) {
			$strlen = strlen($string);
			$testlen = strlen($test);
			if ($testlen > $strlen) return false;
			return substr_compare($string, $test, -$testlen) === 0;
	}	
	
	static function loadClass($_argv = NULL) {
		$pars = Console_Getargs_Combined::getArgs();
		//print_r($pars);
		$howFile = isset($pars[2]) ? $pars[2] : 'MakeItHow.php';
		//print($howFile);
		if (file_exists($howFile = realpath($howFile))) {
			print("Loading How file ".$howFile." ...\n");
			require_once $howFile;
			$result = new MakeItHow();
			$result->howFilePath = $howFile;
			return $result;
		} else {
			print("Error! How file ".$howFile." doesn't exist\n");
		}
	}

	function __construct() {
		$this->workingPath = getcwd();
		$this->argsAndOptions = Console_Getargs_Combined::getArgs();
		//print_r($this->argsAndOptions);
		if (isset($this->argsAndOptions[1]))
			$this->task = $this->argsAndOptions[1];
	}

	function setSimpleItems() {
		foreach ($this->whatXml->content->simpleItems->item as $item) {
			$name = (string) $item['name'];
			$value = (string) $item[0];
			$this->{$name} = $value;
		}
		foreach ($this->argsAndOptions as $key => $value) {
			if (!is_numeric($key))
				$this->{$key} = $value;
		}
	}

	function findXmlFile() {
		$whatFilename = isset($this->argsAndOptions[3]) ? $this->argsAndOptions[3] : null;
		if ($whatFilename && file_exists($whatFilename = realpath($whatFilename))) {
			return $whatFilename;
		}
		$whatFilename = realpath($this->workingPath . DIRECTORY_SEPARATOR . 'MakeItWhat.xml');
		if (file_exists($whatFilename))
			return $whatFilename;		// found in working path
		return null;							// not found
	}

	function loadWhat($whatFilename = NULL) {
		if(!$whatFilename)
			$whatFilename = $this->findXmlFile();
		if ($whatFilename) {
			$this->whatFilePath = $whatFilename;
			$filestring = file_get_contents($whatFilename); // load $whatFilename to $filestring
			$this->whatXml = new SimpleXMLElement($filestring);
			$this->setSimpleItems();
			return $this->whatXml;
		}
	}

	function getXpathValue($path) {
		$nodes = $this->whatXml->content->xpath($path);		// get matching nodes
		if (count($nodes)==0)
			return null;
		$node = $nodes[0];						// get first node
		$result = (string) $node[0];	// get text of node
		return $result;								// return text
	}

	function callTask($task) {
		if ($task && method_exists($this,$task)) {
			print "Calling task ".$task." ...\n\n";
			$this->{$task}();
		} else {
			print "Failed calling task ".$task." - task doesn't exist\n";
		}
	}
	
	function isWindows() {
		return isset($_SERVER['OS']) && ($_SERVER['OS']=='Windows_NT');
	}
	
	function isUnix() {
		return !$this->isWindows();		// ok, its a hack. Otherwise I'd have to get all the codes for Mac, Linux, Solaris etc
	}

}
?>
