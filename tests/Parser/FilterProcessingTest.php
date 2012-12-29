<?php

namespace s9e\TextFormatter\Tests\Parser;

use s9e\TextFormatter\Configurator\Items\ProgrammableCallback;
use s9e\TextFormatter\Configurator\Items\Tag as TagConfig;
use s9e\TextFormatter\Parser;
use s9e\TextFormatter\Parser\FilterProcessing;
use s9e\TextFormatter\Parser\Logger;
use s9e\TextFormatter\Parser\Tag;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Parser\FilterProcessing
*/
class FilterProcessingTest extends Test
{
	/**
	* @testdox registerVar($k, $v) sets registeredVars[$k] to $v
	*/
	public function testRegisterVar()
	{
		$dummy = new FilterProcessingDummy;
		$dummy->registerVar('foo', 'bar');

		$this->assertSame(
			array('foo' => 'bar'),
			$dummy->registeredVars
		);
	}

	/**
	* @testdox getRegisteredVars() returns all registered vars
	*/
	public function testGetRegisteredVars()
	{
		$dummy = new FilterProcessingDummy;
		$dummy->registerVar('foo', 'bar');

		$this->assertSame(
			array('foo' => 'bar'),
			$dummy->getRegisteredVars()
		);
	}

	/**
	* @testdox executeAttributePreprocessors() sets captured attributes on match
	*/
	public function testExecuteAttributePreprocessorsSetAttributesOnMatch()
	{
		$tagConfig = new TagConfig;
		$tagConfig->attributePreprocessors->add('foo', '/^(?<bar>[0-9])(?<baz>[a-z])$/i');
		$tagConfig = $tagConfig->asConfig();

		$tag = new Tag(Tag::SELF_CLOSING_TAG, 'X', 0, 0);
		$tag->setAttribute('foo', '2x');

		$this->assertTrue(Parser::executeAttributePreprocessors($tag, $tagConfig));
		$this->assertSame('2', $tag->getAttribute('bar'));
		$this->assertSame('x', $tag->getAttribute('baz'));
	}

	/**
	* @testdox executeAttributePreprocessors() does not overwrite attributes that were already set
	*/
	public function testExecuteAttributePreprocessorsDoesNotOverwrite()
	{
		$tagConfig = new TagConfig;
		$tagConfig->attributePreprocessors->add('foo', '/^(?<bar>[0-9])(?<baz>[a-z])$/i');
		$tagConfig = $tagConfig->asConfig();

		$tag = new Tag(Tag::SELF_CLOSING_TAG, 'X', 0, 0);
		$tag->setAttribute('foo', '2x');
		$tag->setAttribute('bar', '4');

		$this->assertTrue(Parser::executeAttributePreprocessors($tag, $tagConfig));
		$this->assertSame('4', $tag->getAttribute('bar'));
		$this->assertSame('x', $tag->getAttribute('baz'));
	}

	/**
	* @testdox executeAttributePreprocessors() unsets the source attribute on match
	*/
	public function testExecuteAttributePreprocessorsUnsetsSource()
	{
		$tagConfig = new TagConfig;
		$tagConfig->attributePreprocessors->add('foo', '/^(?<bar>[0-9])(?<baz>[a-z])$/i');
		$tagConfig = $tagConfig->asConfig();

		$tag = new Tag(Tag::SELF_CLOSING_TAG, 'X', 0, 0);
		$tag->setAttribute('foo', '2x');

		$this->assertTrue(Parser::executeAttributePreprocessors($tag, $tagConfig));
		$this->assertFalse($tag->hasAttribute('foo'));
	}

	/**
	* @testdox executeAttributePreprocessors() does not unset the source attribute if there's nn match
	*/
	public function testExecuteAttributePreprocessorsDoesNotUnsetSourceIfNoMatch()
	{
		$tagConfig = new TagConfig;
		$tagConfig->attributePreprocessors->add('foo', '/^(?<bar>[0-9])(?<baz>[a-z])$/i');
		$tagConfig = $tagConfig->asConfig();

		$tag = new Tag(Tag::SELF_CLOSING_TAG, 'X', 0, 0);
		$tag->setAttribute('foo', 'xx');

		$this->assertTrue(Parser::executeAttributePreprocessors($tag, $tagConfig));
		$this->assertTrue($tag->hasAttribute('foo'));
	}

	/**
	* @testdox executeAttributePreprocessors() stops after the first match
	*/
	public function testExecuteAttributePreprocessorsStopsOnFirstMatch()
	{
		$tagConfig = new TagConfig;
		$tagConfig->attributePreprocessors->add('foo', '/^(?<bar>[0-9])/i');
		$tagConfig->attributePreprocessors->add('foo', '/^(?<bar>[0-9])(?<baz>[a-z])$/i');
		$tagConfig = $tagConfig->asConfig();

		$tag = new Tag(Tag::SELF_CLOSING_TAG, 'X', 0, 0);
		$tag->setAttribute('foo', '2x');

		$this->assertTrue(Parser::executeAttributePreprocessors($tag, $tagConfig));
		$this->assertSame('2', $tag->getAttribute('bar'));
		$this->assertFalse($tag->hasAttribute('baz'));
	}

	/**
	* @testdox executeAttributePreprocessors() tries all preprocessors until there's a match
	*/
	public function testExecuteAttributePreprocessorsTriesAll()
	{
		$tagConfig = new TagConfig;
		$tagConfig->attributePreprocessors->add('foo', '/^(?<bar>[a-z])(?<baz>[a-z])$/i');
		$tagConfig->attributePreprocessors->add('foo', '/^(?<bar>[0-9])(?<baz>[a-z])$/i');
		$tagConfig = $tagConfig->asConfig();

		$tag = new Tag(Tag::SELF_CLOSING_TAG, 'X', 0, 0);
		$tag->setAttribute('foo', '2x');

		$this->assertTrue(Parser::executeAttributePreprocessors($tag, $tagConfig));
		$this->assertSame('2', $tag->getAttribute('bar'));
		$this->assertSame('x', $tag->getAttribute('baz'));
	}

	/**
	* @testdox executeAttributePreprocessors() returns TRUE even if the no source attribute was present
	*/
	public function testExecuteAttributePreprocessorsReturnsTrue()
	{
		$tagConfig = new TagConfig;
		$tagConfig->attributePreprocessors->add('foo', '/^(?<bar>[a-z])(?<baz>[a-z])$/i');
		$tagConfig = $tagConfig->asConfig();

		$tag = new Tag(Tag::SELF_CLOSING_TAG, 'X', 0, 0);

		$this->assertTrue(Parser::executeAttributePreprocessors($tag, $tagConfig));
	}

	/**
	* @testdox executeFilter() correctly passes a value to the callback
	*/
	public function testExecuteFilterByValue()
	{
		$filter = new ProgrammableCallback(
			function()
			{
				$this->assertSame(
					array(42),
					func_get_args()
				);
			}
		);
		$filter->addParameterByValue(42);

		FilterProcessingDummy::__executeFilter(
			$filter->asConfig(),
			array()
		);
	}

	/**
	* @testdox executeFilter() correctly passes a named var to the callback
	*/
	public function testExecuteFilterByName()
	{
		$filter = new ProgrammableCallback(
			function()
			{
				$this->assertSame(
					array(42),
					func_get_args()
				);
			}
		);
		$filter->addParameterByName('foo');

		FilterProcessingDummy::__executeFilter(
			$filter->asConfig(),
			array('foo' => 42)
		);
	}

	/**
	* @testdox executeFilter() correctly passes a var passed through registeredVars to the callback
	*/
	public function testExecuteFilterRegisteredVar()
	{
		$filter = new ProgrammableCallback(
			function()
			{
				$this->assertSame(
					array(42),
					func_get_args()
				);
			}
		);
		$filter->addParameterByName('foo');

		FilterProcessingDummy::__executeFilter(
			$filter->asConfig(),
			array('registeredVars' => array('foo' => 42))
		);
	}

	/**
	* @testdox executeFilter() does not execute the callback and returns FALSE if a variable is missing
	*/
	public function testExecuteFilterMissingVar()
	{
		$filter = new ProgrammableCallback(
			function()
			{
				$this->fail('The callback should not have been executed');
			}
		);
		$filter->addParameterByName('foo');

		$this->assertFalse(FilterProcessingDummy::__executeFilter(
			$filter->asConfig(),
			array('logger' => new Logger)
		));
	}

	/**
	* @testdox executeFilter() logs an error if a variable is missing
	*/
	public function testExecuteFilterMissingVarLog()
	{
		$filter = new ProgrammableCallback(
			function()
			{
				$this->fail('The callback should not have been executed');
			}
		);
		$filter->addParameterByName('foo');

		$logger = $this->getMock('stdClass', array('err'));
		$logger->expects($this->once())
		       ->method('err')
		       ->with('Unknown callback parameter', array('paramName' => 'foo'));

		$this->assertFalse(FilterProcessingDummy::__executeFilter(
			$filter->asConfig(),
			array('registeredVars' => array('logger' => $logger))
		));
	}

	/**
	* @testdox filterTag() returns TRUE if the tag has an empty filterChain
	*/
	public function testFilterTagNoFilterChain()
	{
		$dummy = new FilterProcessingDummy;
		$tag   = new Tag(Tag::SELF_CLOSING_TAG, 'X', 0, 0);

		$this->assertTrue($dummy->__filterTag($tag));
	}

	/**
	* @testdox filterTag() executes the tag's filterChain and returns TRUE
	*/
	public function testFilterTag()
	{
		$mock = $this->getMock('stdClass', array('foo', 'bar'));
		$mock->expects($this->once())
		     ->method('foo')
		     ->will($this->returnValue(true));
		$mock->expects($this->once())
		     ->method('bar')
		     ->will($this->returnValue(true));

		$tag = $this->configurator->tags->add('X');
		$tag->filterChain->append(array($mock, 'foo'));
		$tag->filterChain->append(array($mock, 'bar'));

		$dummy = new FilterProcessingDummy($this->configurator->asConfig());
		$tag   = new Tag(Tag::SELF_CLOSING_TAG, 'X', 0, 0);

		$this->assertTrue($dummy->__filterTag($tag));
	}

	/**
	* @testdox filterTag() stops executing the tag's filterChain and returns FALSE if a filter returns FALSE
	*/
	public function testFilterTagReturnsFalse()
	{
		$mock = $this->getMock('stdClass', array('foo', 'bar'));
		$mock->expects($this->once())
		     ->method('foo')
		     ->will($this->returnValue(false));
		$mock->expects($this->never())
		     ->method('bar');

		$tag = $this->configurator->tags->add('X');
		$tag->filterChain->append(array($mock, 'foo'));
		$tag->filterChain->append(array($mock, 'bar'));

		$dummy = new FilterProcessingDummy($this->configurator->asConfig());
		$tag   = new Tag(Tag::SELF_CLOSING_TAG, 'X', 0, 0);

		$this->assertFalse($dummy->__filterTag($tag));
	}

	/**
	* @testdox filterTag() calls the logger's setTag() and unsetTag() methods
	*/
	public function testFilterTagCallsLoggerSetTag()
	{
		$mock = $this->getMock('stdClass', array('foo'));
		$mock->expects($this->once())
		     ->method('foo')
		     ->will($this->returnValue(false));

		$tag = $this->configurator->tags->add('X');
		$tag->filterChain->append(array($mock, 'foo'));

		$dummy = new FilterProcessingDummy($this->configurator->asConfig());
		$tag   = new Tag(Tag::SELF_CLOSING_TAG, 'X', 0, 0);

		$dummy->logger = $this->getMock(
			's9e\\TextFormatter\\Parser\\Logger',
			array('setTag', 'unsetTag')
		);
		$dummy->logger->expects($this->once())
		              ->method('setTag')
		              ->with($this->identicalTo($tag));
		$dummy->logger->expects($this->once())
		              ->method('unsetTag');

		$dummy->__filterTag($tag);
	}

	/**
	* @testdox filterAttributes() removes the tag's attributes if none were configured
	*/
	public function testFilterAttributesNukesAttributes()
	{
		$tag = new Tag(Tag::SELF_CLOSING_TAG, 'X', 0, 0);
		$tag->setAttribute('foo', 'foo');

		Parser::filterAttributes($tag, array(), array(), new Logger);

		$this->assertSame(
			array(),
			$tag->getAttributes()
		);
	}

	/**
	* @testdox filterAttributes() calls the attribute's generator and uses its return value as attribute's value
	*/
	public function testFilterAttributesCallsAttributeGenerator()
	{
		$tagConfig = new TagConfig;
		$tagConfig->attributes->add('foo')->generator = function() { return 42; };

		$tag = new Tag(Tag::SELF_CLOSING_TAG, 'X', 0, 0);

		Parser::filterAttributes($tag, $tagConfig->asConfig(), array(), new Logger);

		$this->assertSame(
			array('foo' => 42),
			$tag->getAttributes()
		);
	}

	/**
	* @testdox filterAttributes() removes undefined attributes
	*/
	public function testFilterAttributesRemovesUndefinedAttributes()
	{
		$tagConfig = new TagConfig;
		$tagConfig->attributes->add('foo');

		$tag = new Tag(Tag::SELF_CLOSING_TAG, 'X', 0, 0);
		$tag->setAttribute('foo', 'foo');
		$tag->setAttribute('bar', 'bar');

		Parser::filterAttributes($tag, $tagConfig->asConfig(), array(), new Logger);

		$this->assertSame(
			array('foo' => 'foo'),
			$tag->getAttributes()
		);
	}

	/**
	* @testdox filterAttributes() executes every filter of an attribute's filterChain and returns the value
	*/
	public function testFilterAttributesExecutesFilterChain()
	{
		$mock = $this->getMock('stdClass', array('foo', 'bar'));
		$mock->expects($this->once())
		     ->method('foo')
		     ->with('xxx')
		     ->will($this->returnValue('foo'));
		$mock->expects($this->once())
		     ->method('bar')
		     ->with('foo')
		     ->will($this->returnValue('bar'));

		$tagConfig = new TagConfig;
		$attribute = $tagConfig->attributes->add('x');
		$attribute->filterChain->append(array($mock, 'foo'));
		$attribute->filterChain->append(array($mock, 'bar'));

		$tag = new Tag(Tag::SELF_CLOSING_TAG, 'X', 0, 0);
		$tag->setAttribute('x', 'xxx');

		Parser::filterAttributes($tag, $tagConfig->asConfig(), array(), new Logger);

		$this->assertSame(
			array('x' => 'bar'),
			$tag->getAttributes()
		);
	}

	/**
	* @testdox filterAttributes() stops executing the attribute's filterChain and returns FALSE if a filter returns FALSE
	*/
	public function testFilterAttributesReturnsFalse()
	{
		$mock = $this->getMock('stdClass', array('foo', 'bar'));
		$mock->expects($this->once())
		     ->method('foo')
		     ->with('xxx')
		     ->will($this->returnValue(false));
		$mock->expects($this->never())
		     ->method('bar');

		$tagConfig = new TagConfig;
		$attribute = $tagConfig->attributes->add('x');
		$attribute->filterChain->append(array($mock, 'foo'));
		$attribute->filterChain->append(array($mock, 'bar'));

		$tag = new Tag(Tag::SELF_CLOSING_TAG, 'X', 0, 0);
		$tag->setAttribute('x', 'xxx');

		$this->assertFalse(Parser::filterAttributes($tag, $tagConfig->asConfig(), array(), new Logger));
	}

	/**
	* @testdox filterAttributes() removes invalid attributes
	*/
	public function testFilterAttributesRemovesInvalid()
	{
		$mock = $this->getMock('stdClass', array('foo'));
		$mock->expects($this->once())
		     ->method('foo')
		     ->with('xxx')
		     ->will($this->returnValue(false));

		$tagConfig = new TagConfig;
		$attribute = $tagConfig->attributes->add('x');
		$attribute->filterChain->append(array($mock, 'foo'));

		$tag = new Tag(Tag::SELF_CLOSING_TAG, 'X', 0, 0);
		$tag->setAttribute('x', 'xxx');

		Parser::filterAttributes($tag, $tagConfig->asConfig(), array(), new Logger);

		$this->assertSame(
			array(),
			$tag->getAttributes()
		);
	}

	/**
	* @testdox filterAttributes() replaces invalid attributes with their defaultValue if applicable
	*/
	public function testFilterAttributesReplacesInvalid()
	{
		$mock = $this->getMock('stdClass', array('foo'));
		$mock->expects($this->once())
		     ->method('foo')
		     ->with('xxx')
		     ->will($this->returnValue(false));

		$tagConfig = new TagConfig;
		$attribute = $tagConfig->attributes->add('x');
		$attribute->filterChain->append(array($mock, 'foo'));
		$attribute->defaultValue = 'default';

		$tag = new Tag(Tag::SELF_CLOSING_TAG, 'X', 0, 0);
		$tag->setAttribute('x', 'xxx');

		Parser::filterAttributes($tag, $tagConfig->asConfig(), array(), new Logger);

		$this->assertSame(
			array('x' => 'default'),
			$tag->getAttributes()
		);
	}

	/**
	* @testdox filterAttributes() adds missing attributes with their defaultValue if applicable
	*/
	public function testFilterAttributesReplacesMissing()
	{
		$tagConfig = new TagConfig;
		$attribute = $tagConfig->attributes->add('x');
		$attribute->defaultValue = 'default';

		$tag = new Tag(Tag::SELF_CLOSING_TAG, 'X', 0, 0);

		Parser::filterAttributes($tag, $tagConfig->asConfig(), array(), new Logger);

		$this->assertSame(
			array('x' => 'default'),
			$tag->getAttributes()
		);
	}

	/**
	* @testdox filterAttributes() calls the logger's setAttribute() and unsetAttribute() methods for each attribute with a filterChain
	*/
	public function testFilterAttributesCallsLoggerSetAttribute()
	{
		$logger = $this->getMock(
			's9e\\TextFormatter\\Parser\\Logger',
			array('setAttribute', 'unsetAttribute')
		);
		$logger->expects($this->at(0))
		       ->method('setAttribute')
		       ->with('foo');
		$logger->expects($this->at(2))
		       ->method('setAttribute')
		       ->with('bar');
		$logger->expects($this->exactly(2))
		       ->method('unsetAttribute');

		$tagConfig = new TagConfig;
		$tagConfig->attributes->add('foo')->filterChain->append(function(){});
		$tagConfig->attributes->add('bar')->filterChain->append(function(){});

		$tag = new Tag(Tag::SELF_CLOSING_TAG, 'X', 0, 0);
		$tag->setAttribute('foo', 'foo');
		$tag->setAttribute('bar', 'bar');

		Parser::filterAttributes($tag, $tagConfig->asConfig(), array(), $logger);
	}
}

class FilterProcessingDummy extends Parser
{
	public $registeredVars;
	public $tagsConfig = array(
		'X' => array()
	);
	public $logger;

	public function __construct(array $config = null)
	{
		if (isset($config))
		{
			parent::__construct($config);
		}
	}

	public function __filterTag()
	{
		return call_user_func_array(array($this, 'filterTag'), func_get_args());
	}

	public static function __executeFilter()
	{
		return call_user_func_array('parent::executeFilter', func_get_args());
	}
}