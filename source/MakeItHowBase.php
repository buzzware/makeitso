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

	var $how;		// path to MakeItHow.php
	var $what;	// path to MakeItWhat.xml
	var $workingPath;		// current working path

	var $whatXml;				// MakeItWhat.xml loaded root node
	var $task = 'main';	// task to execute
	var $pars = array();	
	
	static function endswith($string, $test) {
			$strlen = strlen($string);
			$testlen = strlen($test);
			if ($testlen > $strlen) return false;
			return substr_compare($string, $test, -$testlen) === 0;
	}	
	
	static function loadClass($pars = NULL) {
		if (!$pars) {
			$pars = Console_Getargs_Combined::getArgs();
		}
		$how = isset($pars['how']) ? $pars['how'] : 'MakeItHow.php';
		if (file_exists($how = realpath($how))) {
			print("Loading How file ".$how." ...\n");
			require_once $how;
			$result = new MakeItHow($pars);
			return $result;
		} else {
			print("Error! How file ".$how." doesn't exist\n");
			exit(1);
		}
	}

	function __construct($pars = NULL) {
		$this->pars = $pars ? $pars : Console_Getargs_Combined::getArgs();
		$this->workingPath = getcwd();
		$this->setSimpleItems(NULL,$pars);
	}

	function setSimpleItems($whatXml = NULL,$pars = NULL) {
		if ($whatXml) {
			foreach ($whatXml->content->simpleItems->item as $item) {
				$name = (string) $item['name'];
				$value = (string) $item[0];
				$this->{$name} = $value;
			}
		}
		if ($pars) {
			foreach ($pars as $key => $value) {
				if (!is_numeric($key))
					$this->{$key} = $value;
			}
			if (isset($this->pars[1]))
				$this->task = $this->pars[1];		
		}
	}

	function findXmlFile() {
		$whatname = isset($this->pars['what']) ? $this->pars['what'] : null;
		if ($whatname && file_exists($whatname = realpath($whatname))) {
			return $whatname;
		}
		$whatname = realpath($this->workingPath . DIRECTORY_SEPARATOR . 'MakeItWhat.xml');
		if (file_exists($whatname))
			return $whatname;		// found in working path
		return null;							// not found
	}

	function loadWhat($whatname = NULL) {
		$this->what = $whatname ? $whatname : $this->findXmlFile();
		if ($this->what) {
			print "Loading what file ".$this->what." ...\n\n";
			$filestring = file_get_contents($this->what); // load $whatname to $filestring
			$this->whatXml = new SimpleXMLElement($filestring);
			$this->setSimpleItems($this->whatXml,$this->pars);
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
			exit(1);			
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
