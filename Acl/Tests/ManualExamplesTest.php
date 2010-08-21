<?php

namespace s9e\Toolkit\Acl\Tests;

use s9e\Toolkit\Acl\Acl;
use s9e\Toolkit\Acl\Resource;
use s9e\Toolkit\Acl\Role;

include_once __DIR__ . '/../Acl.php';
include_once __DIR__ . '/../Resource.php';
include_once __DIR__ . '/../Role.php';

class ManualExamplesTest extends \PHPUnit_Framework_TestCase
{
	public function setUp()
	{
		ob_start();
	}

	public function tearDown()
	{
		ob_end_clean();
	}

	protected function assertThatExampleIsCorrect($method)
	{
		$method = substr($method, 1 + strrpos($method, ':'));
		$regexp = '#public function ' . $method . '\\(\\)\\s*\\{\\s*//={70}(.*?)//={70}#s';

		if (!preg_match($regexp, file_get_contents(__FILE__), $m))
		{
			throw new \Exception('Could not find example in method');
		}

		preg_match_all('#;\\s+// (bool\\((true|false)\\))#', $m[1], $m);

		$expected = implode("\n", $m[1]);
		$actual   = trim(ob_get_contents());

		$this->assertSame($expected, $actual);
	}

	public function testScoping()
	{
		//======================================================================
		$acl = new Acl;
		$acl->allow('read', array('category' => 1))
			->allow('read', array('category' => 2));

		var_dump($acl->isAllowed('read')); // bool(false) because "read" is not allowed globally
		var_dump($acl->isAllowed('read', array('category' => 1)));   // bool(true)
		var_dump($acl->isAllowed('read', array('category' => "1"))); // bool(true)
		var_dump($acl->isAllowed('read', array('category' => 3)));   // bool(false)

		var_dump($acl->isAllowed('read', $acl->wildcard()));                      // bool(true)
		var_dump($acl->isAllowed('read', array('category' => $acl->wildcard()))); // bool(true)
		//======================================================================

		$this->assertThatExampleIsCorrect(__METHOD__);
	}

	public function testPrecedence()
	{
		//======================================================================
		$acl = new Acl;
		$acl->allow('read')
			->deny('read');

		var_dump($acl->isAllowed('read')); // bool(false)
		//======================================================================

		$this->assertThatExampleIsCorrect(__METHOD__);
	}

	public function testRules()
	{
		//======================================================================
		$acl = new Acl;
		$acl->addRule('post', 'grant', 'read')
			->allow('post', array('category' => 1));

		var_dump($acl->isAllowed('post', array('category' => 1))); // bool(true)
		var_dump($acl->isAllowed('read', array('category' => 1))); // bool(true)

		$acl = new Acl;
		$acl->addRule('post', 'grant', 'read')
			->addRule('post', 'require', 'read')
			->allow('post', array('category' => 1))
			->allow('post', array('category' => 2))
			->deny('read', array('category' => 2));

		var_dump($acl->isAllowed('read', array('category' => 1))); // bool(true)
		var_dump($acl->isAllowed('post', array('category' => 1))); // bool(true)
		var_dump($acl->isAllowed('read', array('category' => 2))); // bool(false) - explicitly denied
		var_dump($acl->isAllowed('post', array('category' => 2))); // bool(false) - you can't post if you can't read
		//======================================================================

		$this->assertThatExampleIsCorrect(__METHOD__);
	}

	public function testRoles()
	{
		//======================================================================
		// let's create an "editor" role
		$editor = new Role('editor');
		$editor->allow('post');
		$editor->allow('read');
		$editor->allow('edit');

		// now build a user's ACL
		$acl = new Acl;
		$acl->addParent($editor);

		var_dump($acl->isAllowed('edit')); // bool(true)
		//======================================================================

		$this->assertThatExampleIsCorrect(__METHOD__);
	}

	public function testResources()
	{
		//======================================================================
		$greenPickup     = new Car(1, 'green', 'pickup');
		$redFerrari      = new Car(2, 'red',   'sports');
		$whiteSedan      = new Car(3, 'white', 'sedan');
		$anotherSedan    = new Car(4, 'white', 'sedan');
		$yetAnotherSedan = new Car(5, 'white', 'sedan');

		$acl = new Acl;
		$acl->allow('drive', array('color' => 'green'))
		     ->allow('drive', array('color' => 'red', 'type' => 'sports'))
		     ->allow('drive', array('car' => 3));

		var_dump($acl->isAllowed('drive', $greenPickup));     // bool(true)
		var_dump($acl->isAllowed('drive', $redFerrari));      // bool(true)
		var_dump($acl->isAllowed('drive', $whiteSedan));      // bool(true)
		var_dump($acl->isAllowed('drive', $anotherSedan));    // bool(false)
		var_dump($acl->isAllowed('drive', $yetAnotherSedan)); // bool(false)

		var_dump($acl->isAllowed('drive', array('color' => 'green'))); // bool(true)
		var_dump($acl->isAllowed('drive', array('car' => 3)));         // bool(true)

		// let's specifically allow $anotherSedan
		$acl->allow('drive', $anotherSedan);

		var_dump($acl->isAllowed('drive', $anotherSedan));    // bool(true)  - specifically allowed
		var_dump($acl->isAllowed('drive', $yetAnotherSedan)); // bool(false) - still no rules covering this one
		//======================================================================

		$this->assertThatExampleIsCorrect(__METHOD__);
	}
}

class Car implements Resource
{
	public function __construct($id, $color, $type)
	{
		$this->id    = $id;
		$this->type  = $type;
		$this->color = $color;
	}

	public function getAclBuilderScope()
	{
		return array('car' => $this->id);
	}

	public function getAclReaderScope()
	{
		return array(
			'car'   => $this->id,
			'type'  => $this->type,
			'color' => $this->color
		);
	}
}