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

	// array("foo" => "bar", 12 => true);
	public function testSmoke() {
		$this->assertEquals(true,true);
		$this->assertEquals(true,true);

		
		$pars = array('what' => 'deploy/deploy.xml', 'deployment' => 'prod');
		$how = createHow($pars,'deploy/deploy.php');
		$how->preventExecCommand = true;
		$how->callTask('deploy');
		$this->assertType('SimpleXMLElement',$how->deploymentXml);

		//$this->assertEquals(
		//	'svn checkout "svn://sdsds/branches/oscar/110202-SomeNewThing/projects/system" "{{EXAMPLE_PRIVATE}}/system"  --username fred --password dfgdfgdfg -r 2001',
		//	$how->execCommands[0]
		//);

		$this->assertEquals(
			array(
				'svn checkout "svn://svn.example.com/var/svn-repos/example/branches/releases/1.23/projects/system" "{{EXAMPLE_PRIVATE}}/system" --username fred --password dfgdfgdfg --revision 2001',
				'svn checkout "svn://svn.example.com/var/svn-repos/example/branches/releases/1.23/projects/sqlScripts" "{{EXAMPLE_PRIVATE}}/sqlScripts" --username fred --password dfgdfgdfg --revision 2001',
				'svn checkout "svn://svn.example.com/var/svn-repos/example/branches/releases/1.23/projects/dcwealth" "{{EXAMPLE_WEB}}/dcwealth" --username fred --password dfgdfgdfg --revision 2001',
				'svn checkout "svn://svn.example.com/var/svn-repos/exampleexe/trunk" "{{EXAMPLE_PRIVATE}}/exampleexe" --username john --password 123123 --revision HEAD'
			),
			$how->execCommands
		);

		/*
		$this->what->load('whatExample1.xml');
		$this->assertType('SimpleXMLElement',$this->what->xml);
		$this->assertEquals('/var/www/mysite',$this->what->getXpathValue('website/path'));
		$this->assertEquals('red',$this->what->colour);
		$this->assertEquals('medium',$this->what->size);
		$this->assertEquals(null,$this->what->shape);
		$this->assertEquals(null,$this->what->getXpathValue('somepath'));
		$this->assertEquals(null,$this->what->getXpathValue('some/wrong/path'));
		*/
	}

}
?>
