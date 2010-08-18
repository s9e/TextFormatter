<?php

namespace s9e\Toolkit\Acl\Tests;

use s9e\Toolkit\Acl\Builder;

include_once __DIR__ . '/../Builder.php';

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

	protected function assertExampleIsCorrect($method)
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

	public function testBuilderGeneratesTheACLReaderReadsIt()
	{
		//======================================================================
		$builder = new Builder;
		$builder->allow('read');

		$reader = $builder->getReader();
		var_dump($reader->isAllowed('read'));  // bool(true)
		var_dump($reader->isAllowed('write')); // bool(false)
		//======================================================================

		$this->assertExampleIsCorrect(__METHOD__);
	}

	public function testScoping()
	{
		//======================================================================
		$builder = new Builder;
		$builder->allow('read', array('category' => 1))
				->allow('read', array('category' => 2));

		$reader = $builder->getReader();
		var_dump($reader->isAllowed('read')); // bool(false) because "read" is not allowed globally
		var_dump($reader->isAllowed('read', array('category' => 1)));   // bool(true)
		var_dump($reader->isAllowed('read', array('category' => "1"))); // bool(true)
		var_dump($reader->isAllowed('read', array('category' => 3)));   // bool(false)

		var_dump($reader->isAllowed('read', $reader->any));                        // bool(true)
		var_dump($reader->isAllowed('read', array('category' => $reader->any)));   // bool(true)
		//======================================================================

		$this->assertExampleIsCorrect(__METHOD__);
	}

	public function testPrecedence()
	{
		//======================================================================
		$builder = new Builder;
		$builder->allow('read')
				->deny('read');

		$reader = $builder->getReader();
		var_dump($reader->isAllowed('read')); // bool(false)
		//======================================================================

		$this->assertExampleIsCorrect(__METHOD__);
	}

	public function testRules()
	{
		//======================================================================
		$builder = new Builder;
		$builder->addRule('post', 'grant', 'read')
				->allow('post', array('category' => 1));

		$reader = $builder->getReader();
		var_dump($reader->isAllowed('post', array('category' => 1))); // bool(true)
		var_dump($reader->isAllowed('read', array('category' => 1))); // bool(true)

		$builder = new Builder;
		$builder->addRule('post', 'grant', 'read')
				->allow('post', array('category' => 1));

		$reader = $builder->getReader();
		var_dump($reader->isAllowed('post', array('category' => 1))); // bool(true)
		var_dump($reader->isAllowed('read', array('category' => 1))); // bool(true)

		$builder = new Builder;
		$builder->addRule('post', 'grant', 'read')
				->addRule('post', 'require', 'read')
				->allow('post', array('category' => 1))
				->allow('post', array('category' => 2))
				->deny('read', array('category' => 2));

		$reader = $builder->getReader();
		var_dump($reader->isAllowed('read', array('category' => 1))); // bool(true)
		var_dump($reader->isAllowed('post', array('category' => 1))); // bool(true)
		var_dump($reader->isAllowed('read', array('category' => 2))); // bool(false) - explicitly denied
		var_dump($reader->isAllowed('post', array('category' => 2))); // bool(false) - you can't post if you can't read
		//======================================================================

		$this->assertExampleIsCorrect(__METHOD__);
	}
}