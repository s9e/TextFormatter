<?php

namespace s9e\Toolkit\TextFormatter\Tests;

use s9e\Toolkit\TextFormatter\ConfigBuilder,
    s9e\Toolkit\TextFormatter\Parser,
    s9e\Toolkit\TextFormatter\Renderer;

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
			$this->cb->addBBCode('foo', array('default_param' => 'undefined'));
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

	/**
	* @expectedException InvalidArgumentException
	*/
	public function testAddBBCodeFromExampleThrowsAnExceptionIfTheDefinitionIsMalformed()
	{
		try
		{
			$this->cb->addBBCodeFromExample('[foo==]{TEXT}[/foo]', '');
		}
		catch (\InvalidArgumentException $e)
		{
			$this->assertContains('Cannot interpret the BBCode definition', $e->getMessage());
			throw $e;
		}
	}

	/**
	* @expectedException InvalidArgumentException
	*/
	public function testAddBBCodeFromExampleDoesNotAllowInvalidXMLInTemplates()
	{
		try
		{
			$this->cb->addBBCodeFromExample('[foo]{TEXT}[/foo]', '<b><a></b>');
		}
		catch (\InvalidArgumentException $e)
		{
			$this->assertContains('Invalid XML', $e->getMessage());
			throw $e;
		}
	}

	/**
	* @expectedException InvalidArgumentException
	*/
	public function testAddBBCodeFromExampleThrowsAnExceptionOnDuplicateParams()
	{
		try
		{
			$this->cb->addBBCodeFromExample('[foo={URL1} FOO={URL2}]{TEXT}[/foo]', '<b/>');
		}
		catch (\InvalidArgumentException $e)
		{
			$this->assertContains('defined twice', $e->getMessage());
			throw $e;
		}
	}

	/**
	* @expectedException InvalidArgumentException
	*/
	public function testAddBBCodeFromExampleThrowsAnExceptionOnDuplicatePlaceholders()
	{
		try
		{
			$this->cb->addBBCodeFromExample('[foo={URL} bar={URL}]{TEXT}[/foo]', '<b/>');
		}
		catch (\InvalidArgumentException $e)
		{
			$this->assertContains('used twice', $e->getMessage());
			throw $e;
		}
	}

	/**
	* @expectedException InvalidArgumentException
	*/
	public function testAddBBCodeFromExampleThrowsAnExceptionOnDefaultParamAndNonTextContent()
	{
		try
		{
			$this->cb->addBBCodeFromExample('[foo={URL}]{COLOR}[/foo]', '<b>{COLOR}</b>');
		}
		catch (\InvalidArgumentException $e)
		{
			$this->assertSame("Param foo is defined twice", $e->getMessage());
			throw $e;
		}
	}

	/**
	* @expectedException InvalidArgumentException
	*/
	public function testAddBBCodeFromExampleThrowsAnExceptionOnUnknownPlaceholdersUsedInAttributes()
	{
		try
		{
			$this->cb->addBBCodeFromExample(
				'[foo={URL}]{TEXT}[/foo]',
				'<b style="color:{COLOR}">{TEXT}</b>'
			);
		}
		catch (\InvalidArgumentException $e)
		{
			$this->assertContains('Unknown placeholder', $e->getMessage());
			throw $e;
		}
	}

	/**
	* @expectedException InvalidArgumentException
	*/
	public function testAddBBCodeFromExampleThrowsAnExceptionOnUnknownPlaceholdersUsedInContent()
	{
		try
		{
			$this->cb->addBBCodeFromExample('[foo={URL}]{TEXT}[/foo]', '<b>{TEXT2}</b>');
		}
		catch (\InvalidArgumentException $e)
		{
			$this->assertContains('Unknown placeholder', $e->getMessage());
			throw $e;
		}
	}

	public function testAddInternalBBCodeUsesSuffixToAvoidDupes()
	{
		$method = new \ReflectionMethod($this->cb, 'addInternalBBCode');
		$method->setAccessible(true);

		$this->assertSame('B0', $method->invokeArgs($this->cb, array('b')));
	}

	public function testGetCensorConfigAutomaticallyCreatesAnInternalBBCodeIfNeeded()
	{
		$this->cb->addCensor('foo');
		$config = $this->cb->getParserConfig();

		$this->assertArrayHasKey('bbcode', $config['passes']['Censor']);
		$this->assertArrayHasKey('param', $config['passes']['Censor']);
		$this->assertArrayHasKey(
			$config['passes']['Censor']['bbcode'],
			$config['passes']['BBCode']['bbcodes']
		);
	}

	/**
	* @expectedException InvalidArgumentException
	*/
	public function testSetFilterThrowsAnExceptionOnInvalidCallback()
	{
		try
		{
			$this->cb->setFilter('foo', 'bar');
		}
		catch (\InvalidArgumentException $e)
		{
			$this->assertContains('valid callback', $e->getMessage());
			throw $e;
		}
	}

	/**
	* @expectedException InvalidArgumentException
	*/
	public function testSetOptionThrowsAnExceptionOnInvalidBBCodeId()
	{
		try
		{
			$this->cb->setOption('Censor', 'bbcode', 'a:b');
		}
		catch (\InvalidArgumentException $e)
		{
			$this->assertContains('Invalid bbcode name', $e->getMessage());
			throw $e;
		}
	}

	/**
	* @expectedException InvalidArgumentException
	*/
	public function testSetOptionThrowsAnExceptionOnInvalidParamId()
	{
		try
		{
			$this->cb->setOption('Censor', 'param', 'a:b');
		}
		catch (\InvalidArgumentException $e)
		{
			$this->assertContains('Invalid param name', $e->getMessage());
			throw $e;
		}
	}

	/**
	* @expectedException PHPUnit_Framework_Error
	*/
	public function testSetOptionGeneratesAPHPNoticeOnUnknownBBCode()
	{
		try
		{
			$this->cb->setOption('Censor', 'bbcode', 'Z');
		}
		catch (\PHPUnit_Framework_Error $e)
		{
			$this->assertContains('Unknown BBCode Z', $e->getMessage());
			throw $e;
		}
	}

	/**
	* @expectedException PHPUnit_Framework_Error
	*/
	public function testSetOptionGeneratesAPHPNoticeOnUnknownBBCodeParam()
	{
		try
		{
			$this->cb->addBBCode('z');
			$this->cb->setOption('Censor', 'bbcode', 'Z');
			$this->cb->setOption('Censor', 'param', 'Z');
		}
		catch (\PHPUnit_Framework_Error $e)
		{
			$this->assertContains('Unknown BBCode param z', $e->getMessage());
			throw $e;
		}
	}

	/**
	* @expectedException InvalidArgumentException
	*/
	public function testAddXSLThrowsAnExceptionOnMalformedXML()
	{
		try
		{
			$this->cb->addXSL('<b><a></b>');
		}
		catch (\InvalidArgumentException $e)
		{
			$this->assertContains('Malformed', $e->getMessage());
			throw $e;
		}
	}

	public function testTemplatesAreStrippedOffBBCodeConfig()
	{
		$this->cb->setBBCodeTemplate('b', '<b></b>');
		$config = $this->cb->getParserConfig();
		$this->assertArrayNotHasKey('tpl', $config['passes']['BBCode']['bbcodes']['B']);
	}

	public function testDefaultReplacementIsStrippedOffCensorConfig()
	{
		$this->cb->addCensor('foo');
		$config = $this->cb->getParserConfig();
		$this->assertArrayNotHasKey('default_replacement', $config['passes']['Censor']);
	}

	public function testJavascriptConfig()
	{
		$config = $this->cb->getParserConfig();
		$jsConf = json_decode($this->cb->getJavascriptParserConfig());

		$this->assertObjectHasAttribute('passes', $jsConf);
		$this->assertObjectHasAttribute('xsl', $jsConf);
	}

	/**
	* @dataProvider getSpecialPlaceholdersData
	*/
	public function testParseSpecialPlaceholders($def, $expected)
	{
		$cb  = new ConfigBuilder;
		$def = $cb->parseBBCodeDefinition($def, '');

		$this->assertArrayHasKey('x', $def['params']);

		foreach ($expected as $k => $v)
		{
			$this->assertArrayHasKey($k, $def['params']['x']);
			$this->assertSame($v, $def['params']['x'][$k], "Incorrect value for '$k'");
		}
	}

	/**
	* @expectedException RuntimeException allowed
	*/
	public function testAddBBCodeFromExampleRejectsUnauthorizedCallbacks()
	{
		$this->cb->addBBCodeFromExample('[B={TEXT;POST_FILTER=eval}][/B]', 'LOL HAX');
	}

	public function getSpecialPlaceholdersData()
	{
		return array(
			array(
				'[x={REGEXP=/^(?:left|center|right)$/i}]{TEXT}[/x]',
				array(
					'type'   => 'regexp',
					'regexp' => '/^(?:left|center|right)$/i'
				)
			),
			array(
				'[X={IDENTIFIER;PRE_FILTER=strtolower,rtrim} foo={TEXT2}]{TEXT}[/X]',
				array(
					'pre_filter' => array('strtolower', 'rtrim')
				)
			),
			array(
				'[X={RANGE=-2,99}][/X]',
				array(
					'type' => 'range',
					'min'  => -2,
					'max'  => 99
				)
			),
			array(
				'[X={REGEXP=/(FOO)(BAR)/;REPLACE=$2$1}][/X]',
				array(
					'type'    => 'regexp',
					'regexp'  => '/(FOO)(BAR)/',
					'replace' => '$2$1'
				)
			),
			array(
				'[X={CHOICE=foo,bar}][/X]',
				array(
					'type'   => 'regexp',
					'regexp' => '/^(?:foo|bar)$/iD'
				)
			),
			array(
				'[X={CHOICE=foo,bar;DEFAULT=foo}][/X]',
				array(
					'type'   => 'regexp',
					'regexp' => '/^(?:foo|bar)$/iD',
					'default' => 'foo'
				)
			),
			array(
				'[X={CHOICE=pokémon,digimon}][/X]',
				array(
					'type'   => 'regexp',
					'regexp' => '/^(?:pokémon|digimon)$/iDu'
				)
			)
		);
	}

	public function testAddBBCodeFromExampleAllowsPostFilter()
	{
		$this->cb->addBBCodeFromExample(
			'[X={IDENTIFIER;POST_FILTER=strtolower,rtrim} foo={TEXT2}]{TEXT}[/X]',
			'<div style="align:{IDENTIFIER}">{TEXT}</div>'
		);
		$config = $this->cb->getParserConfig();

		$this->assertTrue(
			isset($config['passes']['BBCode']['bbcodes']['X']['params']['x']['post_filter'])
		);
		$this->assertSame(
			array('strtolower', 'rtrim'),
			$config['passes']['BBCode']['bbcodes']['X']['params']['x']['post_filter']
		);
	}

	public function setUp()
	{
		$this->cb = new ConfigBuilder;
		$this->cb->addBBCode('a');
		$this->cb->addBBCode('b');
		$this->cb->addBBCodeParam('b', 'b', 'text');
		$this->cb->addBBCodeRule('b', 'require_parent', 'a');
	}
}