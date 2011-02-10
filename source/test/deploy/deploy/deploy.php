<?php

class MakeItHow extends MakeItHowBase {

	var $preventExecCommand = false;
	var $svnServer;

	var $deploymentXml;
	var $execCommands = array();

	function loadDeployment($name) {

	}

	function main() {
		$this->deploy();
	}
	
	function execCommand($cmd) {
		print($cmd);
		if ($this->preventExecCommand) {
			$this->execCommands[] = $cmd;
			return '';
		} else {
			return execSafe($cmd);
		}
	}
	
	/*
		* The config overriding goes : simpleItems, project, deployment
		so projects prob won't define branch or revision - thats best left to the deployment. The deployment settings 			will override project ones anyway, but specific project settings can be overridden selectively without affecting the rest.
		* if the source begins with /trunk or /branch or svn: or http: or svn+ssh:, then it is an absolute path and will not be adjusted to the value of the branch item, but the revision item will still apply. Normally source would not begin with a slash.
		* The deployment can override a specific projects settings like this :
			<item name="sqlScripts.revision">2001</item>

	Implementation :

		* read simpleItems and store
		* load selected deployment
		* clone config
		* read items from first project in projects item
		* read flat items from deployment, and any dotted items beginning with project_name.
		* svn checkout repository+branch+source@revision to destination
	*/

	/*
	 * $config->repository eg. svn://svn.example.com/var/svn-repos/example			// no trailing slash
	 * $config->branch eg. /branches/gary/somebranch or /trunk										// leading but no trailing slash
	 * $config->source eg. projects/dcwealth																			// no leading or trailing slash
	 */


	function svnCheckoutCmd($config) {
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
			'checkout',
			$rep,
			$url,
			$config->destination,
			$options
		);
	}

	function getProjects() {
		$this->deploymentXml = $this->getXpathNode("deployments/deployment[@name='".$this->pars['deployment']."']");

		$projects = $this->getXpathValue("item[@name='projects']",$this->deploymentXml);
		if (array_key_exists('projects',$this->pars))
			$projects = $this->pars['projects'];

		$arrProjects = split(',',$projects);
		foreach ($arrProjects as $project_name) {
			$projectXml = $this->getXpathNode("projects/project[@name='".$project_name."']");
			$config = new DynamicObject($this);
			$this->setPropertiesFromXmlItems($config,$projectXml);
			$this->setPropertiesFromXmlItems($config,$this->deploymentXml,$project_name);
			// $config should have all values now to extract from svn

			$config = expandConfigTokens($config);

			$this->execCommand($this->svnCheckoutCmd($config));
		}
	}

	function stopExes() {
		print('!!! stopExes() is not implemented yet');
	}

	function maintenancePageOn() {
		print('!!! maintenancePageOn() is not implemented yet');
	}

	function maintenancePageOff() {
		print('!!! maintenancePageOff() is not implemented yet');
	}

	protected function installProjectCmd($config) {
		$result = isWindows() ? 'cd /d' : 'cd';
		$result = $result.' "'.$config['destination'].'"; makeitso install.php';
		return $result;
	}

	function installProjects() {
		$this->deploymentXml = $this->getXpathNode("deployments/deployment[@name='".$this->pars['deployment']."']");

		$projects = $this->getXpathValue("item[@name='projects']",$this->deploymentXml);
		if (array_key_exists('projects',$this->pars))
			$projects = $this->pars['projects'];

		$arrProjects = split(',',$projects);
		foreach ($arrProjects as $project_name) {
			$projectXml = $this->getXpathNode("projects/project[@name='".$project_name."']");
			$config = new DynamicObject($this);
			$this->setPropertiesFromXmlItems($config,$projectXml);
			$this->setPropertiesFromXmlItems($config,$this->deploymentXml,$project_name);
			// $config should have all values now to extract from svn

			$config = expandConfigTokens($config);

			$this->execCommand($this->installProjectCmd($config));
		}
	}

	function restartWebserver() {
		print('!!! restartWebserver() is not implemented yet');
	}

	function deploy() {
		stopExes();
		maintenancePageOn();
		getProjects();
		installProjects();
		maintenancePageOff();
		restartWebserver();
	}
	 
}
?>
