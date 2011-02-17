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
require_once 'MakeItSoUtilities.php';

/**
 * Description of MakeItHow
 *
 * @author gary
 */
class MakeItHowBase {

	var $how;							// path to MakeItHow.php
	var $workingPath;			// current working path
	var $task = 'main';		// task to execute
	var $pars = array();
	var $whatXml;					// most recent xml loaded by loadWhatXml
	
	static function loadClass($how) {
		if (file_exists($how = realpath($how))) {
			print("Loading How file ".$how." ...\n");
			require_once $how;
			$result = new MakeItHow();
			return $result;
		} else {
			print("Error! How file ".$how." doesn't exist\n");
			exit(1);
		}
	}

	function getXpathNodes($path,$xml=NULL) {
		if (!$xml)
			$xml = $this->whatXml;
		if (!$xml)
			return NULL;
		return $xml->xpath($path);		// get matching nodes
	}

	function getXpathNode($path,$xml=NULL) {
		$nodes = $this->getXpathNodes($path,$xml);
		if (count($nodes)==0)
			return null;
		return $nodes[0];						// get first node
	}

	function getXpathValue($path,$xml=NULL) {
		$node = $this->getXpathNode($path,$xml);
		if (!$node)
			return NULL;
		$result = (string) $node[0];	// get text of node
		return $result;								// return text
	}

	function setXmlSimpleItems($whatXml) {
		setPropertiesFromXmlItems($this,$whatXml->simpleItems);
	}
	
	function setCommandLineSimpleItems($pars) {
		foreach ($pars as $key => $value) {
			if (!is_numeric($key))
				$this->{$key} = $value;
		}
	}	

	function findXmlFile($whatname) {
		if ($whatname && file_exists($whatname = realpath($whatname))) {
			return $whatname;
		}
		$whatname = realpath($this->workingPath . DIRECTORY_SEPARATOR . 'MakeItWhat.xml');
		if (file_exists($whatname))
			return $whatname;		// found in working path
		return null;							// not found
	}

	function loadWhatXml($whatname) {
		print "Loading what file ".$whatname." ...\n\n";
		$filestring = file_get_contents($whatname); // load $whatname to $filestring
		$whatXml = new SimpleXMLElement($filestring);
		$this->setXmlSimpleItems($whatXml);
		$this->whatXml = $whatXml;
		return $whatXml;
	}

	function callTask($task) {
		$result = NULL;
		if ($task && method_exists($this,$task)) {
			print "Calling task ".$task." ...\n\n";
			$result = $this->{$task}();
		} else {
			print "Failed calling task ".$task." - task doesn't exist\n";
			exit(1);			
		}
		return $result;
	}

	// In $pars, there are values indexed by options like 
	// 'what' => 'config.xml' 
	// AND arguments indexed by their position eg.
	// 1 => 'sometaskname'
	// This method implements a pattern of
	// [how.php] [what.xml] [task]
	// where all are optional but if given they should be in the order listed.
	// It also assumes how files end in .php (case insensitive so .PHP is supported) and
	// what files end in xml.
	// This method can be overriden for implementing a custom strategy
	function setOptionsFromArguments($pars) {
		$how = NULL;
		$what = NULL;
		$task = NULL;
		for ($i=1; $i<=3; $i++) {
			$option = getProperty($pars,$i);
			if (!$option)
				continue;
			if (!$how && endsWith($option,'.php',false)) {
				$how = $option;
			} else if (!$what && endsWith($option,'.xml',false)) {
				$what = $option;
			} else if (!$task) {
				$task = $option;
			}
		}
		if ($how && (!isset($pars['how']) || !$pars['how']))
			$pars['how'] = $how;
		if ($what && (!isset($pars['what']) || !$pars['what']))
			$pars['what'] = $what;
		if ($task && (!isset($pars['task']) || !$pars['task']))
			$pars['task'] = $task;
		return $pars;
	}


	// override this to configure differently
	function configureWhat($pars) {
		$this->workingPath = getcwd();

		if ($pars===NULL)
			$pars = Console_Getargs_Combined::getArgs();
		$this->pars = $pars;															// store unmodified pars
		
		$pars = $this->setOptionsFromArguments($pars);		// modify $pars for use below

		$what = NULL;
		if ($whatname = isset($pars['what']) ? $pars['what'] : null)
			$what = $this->findXmlFile($whatname);
		if ($what)
			$this->loadWhatXml($what);
		else if ($whatname)	{							// given name of file to load but not found
			$msg = "Unable to find what file ".$whatname."\n";
			print $msg;
			throw new Exception($msg);
		}

		$this->setCommandLineSimpleItems($pars);					// set wuth $pars
	}
	
	// override this to call (or not call) default task differently
	function callDefaultTask() {
		if ($this->task)
			$this->callTask($this->task);
	}

}
?>
