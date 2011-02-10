<?php

require_once 'PHPUnit/Framework.php';
require_once '../../MakeItHowBase.php';

function createHow($pars,$howFilename) {
	$how = MakeItHowBase::loadClass($howFilename);
	$how->configureWhat($pars);
	return $how;
}

class DeployTest extends PHPUnit_Framework_TestCase {

	protected function setUp() {
		//$this->what = new TestWhat();
	}

	protected function tearDown() {

	}

	public function testExpandConfigTokens() {
		$input = array('a' => 'AAA', 'b' => 'BBB{{a}}BBBB');
		$output = expandConfigTokens($input);
		$this->assertEquals(
			array('a' => 'AAA', 'b' => 'BBBAAABBBB'),
			$output
		);
	}

	// array("foo" => "bar", 12 => true);
	public function testGetProjects() {
		$pars = array('what' => 'deploy/deploy.xml', 'deployment' => 'prod');
		$how = createHow($pars,'deploy/deploy.php');
		$how->preventExecCommand = true;
		$how->callTask('getProjects');
		$this->assertType('SimpleXMLElement',$how->deploymentXml);

		$this->assertEquals(
			array(
				'svn checkout "svn://svn.example.com/var/svn-repos/example/branches/releases/1.23/projects/system" "C:\Decimal\system" --username fred --password dfgdfgdfg --revision 2001',
				'svn checkout "svn://svn.example.com/var/svn-repos/example/branches/releases/1.23/projects/sqlScripts" "C:\Decimal\sqlScripts" --username fred --password dfgdfgdfg --revision 2001',
				'svn checkout "svn://svn.example.com/var/svn-repos/example/branches/releases/1.23/projects/dcwealth" "C:\Inetpub\wwwroot\dcwealth" --username fred --password dfgdfgdfg --revision 2001',
				'svn checkout "svn://svn.example.com/var/svn-repos/exampleexe/trunk" "C:\Decimal\exampleexe" --username john --password 123123 --revision HEAD'
			),
			$how->execCommands
		);
	}

	public function testInstallProjectsUnix() {
		$pars = array('what' => 'deploy/deploy.xml', 'deployment' => 'prod');
		$how = createHow($pars,'deploy/deploy.php');
		$how->preventExecCommand = true;
		$how->callTask('installProjects');
		$this->assertType('SimpleXMLElement',$how->deploymentXml);

		$this->assertEquals(
			array(
				'cd "C:\Decimal\system"; makeitso install.php',
				'cd "C:\Decimal\sqlScripts"; makeitso install.php',
				'cd "C:\Inetpub\wwwroot\dcwealth"; makeitso install.php',
				'cd "C:\Decimal\exampleexe"; makeitso install.php'
			),
			$how->execCommands
		);
	}

}
?>
