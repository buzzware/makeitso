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

	var $how;							// full path of MakeItHow.php
	var $howDir;					// directory containing MakeItHow.php
	var $workingPath;			// current working path
	var $task = 'main';		// task to execute
	var $pars = array();
	var $whatXml;					// most recent xml loaded by loadWhatXml
	var $what = 'MakeItWhat.xml';
	var $preventExecCommand = false;
	var $execCommands = array();
	var $logger;

	static function loadClass($how,$logger=null) {
		$howPath = realpath($how);
		if ($logger)
			$logger->info("Loading How file ".$howPath." ...");
		else
			print("Loading How file ".$howPath." ...\n");
		require_once $howPath;
			$result = new MakeItHow();
		if (!$result->logger)					// if no logger set, use given one or create a default one (MakeItHowBase doesn't set logger, but subclasses may, which will take precedence)
			$result->logger = ($logger ? $logger : MakeItSo::log());
		$result->how = $howPath;
		$result->howDir = dirname($howPath);
			return $result;
	}

	// just for overriding by other classes
	function __construct() {
	}

	public function __get($name) {
		if (isset($this->{$name}))
			return $this->{$name};
		else
			return null;
	}
	
	function command($cmd,$workingFolder=null) {
		if (!$workingFolder)
			$workingFolder = getcwd();
		$this->logger->debug("Executing command \"".$cmd."\" in ".$workingFolder."\n");
		$result = '';
		if ($this->preventExecCommand) {
			$this->execCommands[] = $cmd;
		} else {
			$result = execSafe($cmd,$workingFolder);
		}
		$this->logger->debug("Command completed succesfully (exit code 0)");
		$this->logger->debug($result);		
		return $result;
	}

	function importEnvVars() {
		$varnames = func_get_args();
		foreach ($varnames as $name)
			setProperty($this,$name,envVar($name));
	}

	function loadWhatXmlFile($whatname) {
		$this->logger->info("Loading what file ".$whatname." ...\n\n");
		$filestring = file_get_contents($whatname); // load $whatname to $filestring
		$whatXml = new SimpleXMLElement($filestring);
		setPropertiesFromXmlItems($this,$whatXml->simpleItems);
		$this->whatXml = $whatXml;
		return $whatXml;
	}

	function callTask($task) {
		$result = null;
		if ($task && method_exists($this,$task)) {
			$this->logger->info("\n>>> Calling task ".$task." ...");
			//try {
			$result = $this->{$task}();
			//} catch(CustomException $e) {
			//	$code = $e->getCode();
			//	if ($code===0)
			//		$code = 1;
			//	exit((integer) $code);
			//} catch(Exception $e) {
			//	exit(1);
			//}
		} else {
			$this->logger->err("\n>>> Failed calling task ".$task." - task doesn't exist\n");
			exit(1);			
		}
		$this->logger->info("\n>>> Completed task ".$task);
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
	function interpretNumericPars($pars) {
		$how = null;
		$what = null;
		$task = null;
		for ($i=1; $i<=3; $i++) {
			if (isset($pars[$i])) {
				$option = $pars[$i];
				unset($pars[$i]);
			} else {
				continue;
			}
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

	function getSystemPars($pars) {
		$pars['workingPath'] = getcwd();
		return $pars;
	}

	// if what file given on command line and file doesn't exist, raise exception
	// if default what file doesn't exist, set result['what'] to null
	// otherwise set result['what'] to full path of a file that exists
	function handleConsolePars($consolePars) {
		if ($consolePars===null)
			$consolePars = Console_Getargs_Combined::getArgs();
		$this->pars = $consolePars;															// store unmodified pars
		$consolePars = $this->interpretNumericPars($consolePars);		// modify $consolePars for use below	

		$whatFileGiven = getProperty($consolePars,'what')!==null;
		if (!$whatFileGiven)
			$consolePars['what'] = $this->what;
		if (!file_exists($fullpath = realpath($consolePars['what'])))
			$fullpath = ensureSlash(dirname($this->how)).$consolePars['what'];
		$whatExists = file_exists($fullpath);
		if ($whatExists) {
			$consolePars['what'] = $fullpath;
		} else {
			if ($whatFileGiven) {
				$msg = "Unable to find what file ".$fullpath."\n";
				$this->logger->err($msg);
			throw new Exception($msg);
			} else {
				$consolePars['what'] = null;
			}
		}
		return $consolePars;
	}
	
	function loadWhatFilePars($pars) {
		if (!$pars['what'])
			return $pars;
		$filestring = file_get_contents($pars['what']); // load $whatname to $filestring
		$whatXml = new SimpleXMLElement($filestring);
		$pars = setPropertiesFromXmlItems($pars,$whatXml->simpleItems);
		$this->whatXml = $whatXml;
		return $pars;
	}


	// override this to configure differently
	function configureWhat($consolePars=null) {
		$cookedConsolePars = $this->handleConsolePars($consolePars);
		$pars = new DynamicObject($this);
		copy_properties($cookedConsolePars,$pars);
		$pars = $this->getSystemPars($pars);
		$pars = $this->loadWhatFilePars($pars);
		copy_properties($cookedConsolePars,$pars);
		$pars = expandConfigTokens($pars);
		copy_properties($pars,$this);
	}
		
	// override this to call (or not call) default task differently
	function callDefaultTask() {
		if ($this->task)
			$this->callTask($this->task);
	}

	// override this if you want to do it differently
	function configReport() {
		$result = "\n  MakeItSo Configuration :\n";
		$result .= "\n";
		$props = get_object_vars($this);
		foreach ($props as $key => $value) {
			$str = '';
			$type = gettype($value);
			switch($type) {
				case "boolean":
				case "integer":
				case "double":
				case "string":
				case "array":
					$str = ((String) $value);
				break;
				case "NULL":
					$str = 'null';
				break;
				case "object":
					$str = 'Object';
				break;
				case "resource":
					$str = $type;
				break;
				case "unknown type":
					$str = 'unknown';
				break;
			}
			$result .= '  '.str_pad($key,22,' ',STR_PAD_LEFT).' => '.$str."\n";
		}
		$result .= "\n";
		return $result;
	}

	function dumpConfig() {
		$this->logger->info($this->configReport());
	}
}
?>
