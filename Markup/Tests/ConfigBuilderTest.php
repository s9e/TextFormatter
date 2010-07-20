<?php

namespace s9e\Toolkit\Markup;

include_once __DIR__ . '/../ConfigBuilder.php';

class ConfigBuilderTest extends \PHPUnit_Framework_TestCase
{
	/**
	* @expectedException InvalidArgumentException
	*/
	public function testAddPassThrowsAnExceptionIfPassAlreadyExists()
	{
		try
		{
			$this->cb->addPass('BBCode', array());
		}
		catch (\InvalidArgumentException $e)
		{
			$this->assertContains('There is already a pass', $e->getMessage());
			throw $e;
		}
	}

	/**
	* @expectedException InvalidArgumentException
	*/
	public function testAddPassThrowsAnExceptionIfNoParserIsGiven()
	{
		try
		{
			$this->cb->addPass('Foo', array());
		}
		catch (\InvalidArgumentException $e)
		{
			$this->assertContains('You must specify a parser', $e->getMessage());
			throw $e;
		}
	}

	/**
	* @expectedException InvalidArgumentException
	*/
	public function testAddPassThrowsAnExceptionIfTheParserIsNotAValidCallback()
	{
		try
		{
			$this->cb->addPass('Foo', array('parser' => 'XYZ'));
		}
		catch (\InvalidArgumentException $e)
		{
			$this->assertContains('must be a valid callback', $e->getMessage());
			throw $e;
		}
	}

	/**
	* @expectedException InvalidArgumentException
	*/
	public function testAddBBCodeThrowsAnExceptionIfTheBBCodeIdIsNotValid()
	{
		try
		{
			$this->cb->addBBCode('foo:bar');
		}
		catch (\InvalidArgumentException $e)
		{
			$this->assertContains('Invalid BBCode name', $e->getMessage());
			throw $e;
		}
	}

	/**
	* @expectedException InvalidArgumentException
	*/
	public function testAddBBCodeThrowsAnExceptionIfTheBBCodeAlreadyExists()
	{
		try
		{
			$this->cb->addBBCode('b');
		}
		catch (\InvalidArgumentException $e)
		{
			$this->assertContains('already exists', $e->getMessage());
			throw $e;
		}
	}

	/**
	* @expectedException InvalidArgumentException
	*/
	public function testAddBBCodeAliasThrowsAnExceptionIfTheBBCodeDoesNotExist()
	{
		try
		{
			$this->cb->addBBCodeAlias('X', 'Y');
		}
		catch (\InvalidArgumentException $e)
		{
			$this->assertContains('Unknown BBCode', $e->getMessage());
			throw $e;
		}
	}

	/**
	* @expectedException InvalidArgumentException
	*/
	public function testAddBBCodeAliasThrowsAnExceptionIfTheAliasHasTheSameNameAsABBCode()
	{
		try
		{
			$this->cb->addBBCodeAlias('b', 'a');
		}
		catch (\InvalidArgumentException $e)
		{
			$this->assertContains('BBCode using that name already exists', $e->getMessage());
			throw $e;
		}
	}

	/**
	* @expectedException InvalidArgumentException
	*/
	public function testAddBBCodeAliasThrowsAnExceptionIfTheAliasNameIsNotValid()
	{
		try
		{
			$this->cb->addBBCodeAlias('b', '[a]');
		}
		catch (\InvalidArgumentException $e)
		{
			$this->assertContains('Invalid alias name', $e->getMessage());
			throw $e;
		}
	}

	/**
	* @expectedException InvalidArgumentException
	*/
	public function testAddBBCodeParamThrowsAnExceptionIfTheBBCodeDoesNotExist()
	{
		try
		{
			$this->cb->addBBCodeParam('X', 'Y', 'text');
		}
		catch (\InvalidArgumentException $e)
		{
			$this->assertContains('Unknown BBCode', $e->getMessage());
			throw $e;
		}
	}

	/**
	* @expectedException InvalidArgumentException
	*/
	public function testAddBBCodeParamThrowsAnExceptionIfTheParamNameIsNotValid()
	{
		try
		{
			$this->cb->addBBCodeParam('b', '[a]', 'text');
		}
		catch (\InvalidArgumentException $e)
		{
			$this->assertContains('Invalid param name', $e->getMessage());
			throw $e;
		}
	}

	/**
	* @expectedException InvalidArgumentException
	*/
	public function testAddBBCodeParamThrowsAnExceptionIfTheParamAlreadyExists()
	{
		try
		{
			$this->cb->addBBCodeParam('b', 'b', 'text');
		}
		catch (\InvalidArgumentException $e)
		{
			$this->assertContains('already exists', $e->getMessage());
			throw $e;
		}
	}

	/**
	* @expectedException UnexpectedValueException
	*/
	public function testAddBBCodeRuleThrowsAnExceptionIfTheActionIsNotValid()
	{
		try
		{
			$this->cb->addBBCodeRule('b', 'fail', 'b');
		}
		catch (\UnexpectedValueException $e)
		{
			$this->assertContains('Unknown rule action', $e->getMessage());
			throw $e;
		}
	}

	public function testAddBBCodeParamDoesNotThrowsAnExceptionIfWeTryToCreateMultipleIdenticalRequireParentRules()
	{
		$this->cb->addBBCodeRule('b', 'require_parent', 'a');
	}

	/**
	* @expectedException RuntimeException
	*/
	public function testAddBBCodeParamThrowsAnExceptionIfWeTryToCreateMultipleDifferentRequireParentRules()
	{
		try
		{
			$this->cb->addBBCodeRule('b', 'require_parent', 'b');
		}
		catch (\RuntimeException $e)
		{
			$this->assertContains('already has a require_parent rule', $e->getMessage());
			throw $e;
		}
	}

	/**
	* @expectedException PHPUnit_Framework_Error
	*/
	public function testGetBBCodeConfigGeneratesANoticeIfDefaultParamRefersToAnUnknownParam()
	{
		try
		{
			$this->cb->getBBCodeConfig();
		}
		catch (\PHPUnit_Framework_Error $e)
		{
			$this->assertContains('unknown BBCode param', $e->getMessage());
			throw $e;
		}
	}

	/**
	* @expectedException InvalidArgumentException
	*/
	public function testSetBBCodeTemplateThrowsAnExceptionIfTheBBCodeDoesNotExist()
	{
		try
		{
			$this->cb->setBBCodeTemplate('foo', '');
		}
		catch (\InvalidArgumentException $e)
		{
			$this->assertContains('Unknown BBCode', $e->getMessage());
			throw $e;
		}
	}

	public function setUp()
	{
		$this->cb = new ConfigBuilder;
		$this->cb->addBBCode('a');
		$this->cb->addBBCode('b', array('default_param' => 'undefined'));
		$this->cb->addBBCodeParam('b', 'b', 'text');
		$this->cb->addBBCodeRule('b', 'require_parent', 'a');
	}
}