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

class ConsoleUtils {

	static function isarg($string) {
		$p = strpos($string,'-');
		return !($p===0);
	}
	static function getArgsOnly($args = NULL) {
		if (!$args)
			$args = $argv;
		$args = array_filter($args,"ConsoleUtils::isarg");
		return array_values($args);
	}

	static function isOption($string) {
		$p = strpos($string,'--');
		return ($p===0);
	}
	static function getOptionsOnly($args = NULL) {
		if (!$args)
			$args = $argv;
		$opts = array_filter($args,"ConsoleUtils::isOption");
		return array_values($opts);
	}

	static function split_option($option) {
		$result = array();
		preg_match_all("/--([^\s=]+)={0,1}(.*)/", $option, &$result);
		array_shift($result);
		return array($result[0][0],$result[1][0]);
	}

	static function optionsArrayToAssoc($args) {
		$opts = array();
		foreach($args as $a) {
			$kv = ConsoleUtils::split_option($a);
			$rhs = ($kv[1]==="" ? "true" : $kv[1]);
			$opts[$kv[0]] = $rhs;
		}
		return $opts;
	}
	
	/**
	 * Parses $GLOBALS['argv'] for parameters and assigns them to an array.
	 *
	 * Supports:
	 * -e
	 * -e <value>
	 * --long-param
	 * --long-param=<value>
	 * --long-param <value>
	 * <value>
	 *
	 * @param array $noopt List of parameters without values
	 */
	static function parseParameters($noopt = array()) {
			$result = array();
			$params = $GLOBALS['argv'];
			// could use getopt() here (since PHP 5.3.0), but it doesn't work relyingly
			reset($params);
			while (list($tmp, $p) = each($params)) {
					if ($p{0} == '-') {
							$pname = substr($p, 1);
							$value = true;
							if ($pname{0} == '-') {
									// long-opt (--<param>)
									$pname = substr($pname, 1);
									if (strpos($p, '=') !== false) {
											// value specified inline (--<param>=<value>)
											list($pname, $value) = explode('=', substr($p, 2), 2);
									}
							}
							// check if next parameter is a descriptor or a value
							$nextparm = current($params);
							if (!in_array($pname, $noopt) && $value === true && $nextparm !== false && $nextparm{0} != '-') list($tmp, $value) = each($params);
							$result[$pname] = $value;
					} else {
							// param doesn't belong to any option
							$result[] = $p;
					}
			}
			return $result;
	}
}

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
		//print_r($_SERVER);
		//if (!$_argv)
		//	$_argv = $_SERVER['argv'];
		//print_r($_argv);
		//$argsOnly = ConsoleUtils::getArgsOnly($_argv);
		//print_r($argsOnly);
		$pars = ConsoleUtils::parseParameters();
		print_r($pars);
		$howFile = isset($pars[2]) ? $pars[2] : 'MakeItHow.php';
		print($howFile);
		$howFile = realpath($howFile);
		require_once $howFile;
		$result = new MakeItHow();
		//$result->argsOnly = $argsOnly;
		//$result->optionsOnly = ConsoleUtils::getOptionsOnly($_argv);
		$result->howFilePath = $howFile;
		return $result;
	}

	function __construct() {
		$this->workingPath = getcwd();
		$argsandopts = ConsoleUtils::parseParameters();
		print_r($argsandopts);
		$this->argsOnly = Array();
		$this->optionsOnly = Array();
		foreach ($argsandopts as $key => $value) {
			if (is_numeric($key)) {
				$this->argsOnly[$key] = $value;
			} else {
				$this->optionsOnly[$key] = $value;
			}
		}
		if (MakeItHowBase::endswith($this->argsOnly[0],'makeitso'))	// remove makeitso if it is there in the first spot
			array_shift($this->argsOnly);
		if (isset($this->argsOnly[0]))
			$this->task = $this->argsOnly[0];
		print_r($this->argsOnly);
		print_r($this->optionsOnly);
	}

	function setSimpleItems() {
		foreach ($this->whatXml->content->simpleItems->item as $item) {
			$name = (string) $item['name'];
			$value = (string) $item[0];
			$this->{$name} = $value;
		}
		$a = $this->optionsOnly; //ConsoleUtils::optionsArrayToAssoc($this->optionsOnly);
		foreach ($a as $key => $value) {
			$this->{$key} = $value;
		}
	}

	function findXmlFile() {
		$whatFilename = count($this->argsOnly) >= 3 ? $this->argsOnly[2] : null;
		if ($whatFilename && file_exists($whatFilename = realpath($whatFilename))) {
			return $whatFilename;		// found from 3rd argument
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
		$this->{$task}();
	}
	
	function isWindows() {
		return isset($_SERVER['OS']) && ($_SERVER['OS']=='Windows_NT');
	}
	
	function isUnix() {
		return !$this->isWindows();		// ok, its a hack. Otherwise I'd have to get all the codes for Mac, Linux, Solaris etc
	}

}
?>
