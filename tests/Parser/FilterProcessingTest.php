<?php

namespace s9e\TextFormatter\Tests\Parser;

use s9e\TextFormatter\Configurator\Helpers\ConfigHelper;
use s9e\TextFormatter\Configurator\Items\ProgrammableCallback;
use s9e\TextFormatter\Configurator\Items\Tag as TagConfig;
use s9e\TextFormatter\Parser;
use s9e\TextFormatter\Parser\Logger;
use s9e\TextFormatter\Parser\Tag;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Parser
*/
class FilterProcessingTest extends Test
{
	/**
	* @testdox executeAttributePreprocessors() sets captured attributes on match
	*/
	public function testExecuteAttributePreprocessorsSetAttributesOnMatch()
	{
		$tagConfig = new TagConfig;
		$tagConfig->attributePreprocessors->add('foo', '/^(?<bar>[0-9])(?<baz>[a-z])$/i');
		$tagConfig = ConfigHelper::filterConfig($tagConfig->asConfig(), 'PHP');

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
		$tagConfig = ConfigHelper::filterConfig($tagConfig->asConfig(), 'PHP');

		$tag = new Tag(Tag::SELF_CLOSING_TAG, 'X', 0, 0);
		$tag->setAttribute('foo', '2x');
		$tag->setAttribute('bar', '4');

		$this->assertTrue(Parser::executeAttributePreprocessors($tag, $tagConfig));
		$this->assertSame('4', $tag->getAttribute('bar'));
		$this->assertSame('x', $tag->getAttribute('baz'));
	}

	/**
	* @testdox executeAttributePreprocessors() does not unset the source attribute on match
	*/
	public function testExecuteAttributePreprocessorsDoNotUnsetSource()
	{
		$tagConfig = new TagConfig;
		$tagConfig->attributePreprocessors->add('foo', '/^(?<bar>[0-9])(?<baz>[a-z])$/i');
		$tagConfig = ConfigHelper::filterConfig($tagConfig->asConfig(), 'PHP');

		$tag = new Tag(Tag::SELF_CLOSING_TAG, 'X', 0, 0);
		$tag->setAttribute('foo', '2x');

		$this->assertTrue(Parser::executeAttributePreprocessors($tag, $tagConfig));
		$this->assertTrue($tag->hasAttribute('foo'));
	}

	/**
	* @testdox executeAttributePreprocessors() can overwrite the source attribute on match
	*/
	public function testExecuteAttributePreprocessorsResetSource()
	{
		$tagConfig = new TagConfig;
		$tagConfig->attributePreprocessors->add('foo', '/^(?<bar>[0-9])(?<foo>(?<baz>[a-z]))$/i');
		$tagConfig = ConfigHelper::filterConfig($tagConfig->asConfig(), 'PHP');

		$tag = new Tag(Tag::SELF_CLOSING_TAG, 'X', 0, 0);
		$tag->setAttribute('foo', '2x');

		$this->assertTrue(Parser::executeAttributePreprocessors($tag, $tagConfig));
		$this->assertTrue($tag->hasAttribute('foo'));
		$this->assertSame('x', $tag->getAttribute('foo'));
	}

	/**
	* @testdox executeAttributePreprocessors() does not unset the source attribute if there's no match
	*/
	public function testExecuteAttributePreprocessorsDoesNotUnsetSourceIfNoMatch()
	{
		$tagConfig = new TagConfig;
		$tagConfig->attributePreprocessors->add('foo', '/^(?<bar>[0-9])(?<baz>[a-z])$/i');
		$tagConfig = ConfigHelper::filterConfig($tagConfig->asConfig(), 'PHP');

		$tag = new Tag(Tag::SELF_CLOSING_TAG, 'X', 0, 0);
		$tag->setAttribute('foo', 'xx');

		$this->assertTrue(Parser::executeAttributePreprocessors($tag, $tagConfig));
		$this->assertTrue($tag->hasAttribute('foo'));
	}

	/**
	* @testdox executeAttributePreprocessors() returns TRUE even if no source attribute was present
	*/
	public function testExecuteAttributePreprocessorsReturnsTrue()
	{
		$tagConfig = new TagConfig;
		$tagConfig->attributePreprocessors->add('foo', '/^(?<bar>[a-z])(?<baz>[a-z])$/i');
		$tagConfig = ConfigHelper::filterConfig($tagConfig->asConfig(), 'PHP');

		$tag = new Tag(Tag::SELF_CLOSING_TAG, 'X', 0, 0);

		$this->assertTrue(Parser::executeAttributePreprocessors($tag, $tagConfig));
	}

	/**
	* @testdox executeFilter() correctly passes a value to the callback
	*/
	public function testExecuteFilterByValue()
	{
		$filter = new ProgrammableCallback(
			function ()
			{
				return func_get_args();
			}
		);
		$filter->addParameterByValue(42);

		$this->assertSame(
			[42],
			FilterProcessingDummy::__executeFilter(
				$filter->asConfig(),
				[]
			)
		);
	}

	/**
	* @testdox executeFilter() correctly passes a named var to the callback
	*/
	public function testExecuteFilterByName()
	{
		$filter = new ProgrammableCallback(
			function ()
			{
				return func_get_args();
			}
		);
		$filter->addParameterByName('foo');

		$this->assertSame(
			[42],
			FilterProcessingDummy::__executeFilter(
				$filter->asConfig(),
				['foo' => 42]
			)
		);
	}

	/**
	* @testdox executeFilter() correctly passes a var passed through registeredVars to the callback
	*/
	public function testExecuteFilterRegisteredVar()
	{
		$filter = new ProgrammableCallback(
			function ()
			{
				return func_get_args();
			}
		);
		$filter->addParameterByName('foo');

		$this->assertSame(
			[42],
			FilterProcessingDummy::__executeFilter(
				$filter->asConfig(),
			['registeredVars' => ['foo' => 42]]
			)
		);
	}

	/**
	* @testdox executeFilter() passes NULL to the callback if a variable is missing
	*/
	public function testExecuteFilterMissingVar()
	{
		$filter = new ProgrammableCallback(
			function()
			{
				return func_get_args();
			}
		);
		$filter->addParameterByValue('foo');
		$filter->addParameterByName('foo');
		$filter->addParameterByValue(42);

		$this->assertSame(
			['foo', null, 42],
			FilterProcessingDummy::__executeFilter(
				$filter->asConfig(),
				['logger' => new Logger]
			)
		);
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
		$mock = $this->getMockBuilder('stdClass')
		             ->setMethods(['foo', 'bar'])
		             ->getMock();
		$mock->expects($this->once())
		     ->method('foo')
		     ->will($this->returnValue(true));
		$mock->expects($this->once())
		     ->method('bar')
		     ->will($this->returnValue(true));

		$tag = $this->configurator->tags->add('X');
		$tag->filterChain->append([$mock, 'foo']);
		$tag->filterChain->append([$mock, 'bar']);

		$dummy = new FilterProcessingDummy($this->configurator->asConfig());
		$tag   = new Tag(Tag::SELF_CLOSING_TAG, 'X', 0, 0);

		$this->assertTrue($dummy->__filterTag($tag));
	}

	/**
	* @testdox filterTag() stops executing the tag's filterChain and returns FALSE if a filter returns FALSE
	*/
	public function testFilterTagReturnsFalse()
	{
		$mock = $this->getMockBuilder('stdClass')
		             ->setMethods(['foo', 'bar'])
		             ->getMock();
		$mock->expects($this->once())
		     ->method('foo')
		     ->will($this->returnValue(false));
		$mock->expects($this->never())
		     ->method('bar');

		$tag = $this->configurator->tags->add('X');
		$tag->filterChain->append([$mock, 'foo']);
		$tag->filterChain->append([$mock, 'bar']);

		$dummy = new FilterProcessingDummy($this->configurator->asConfig());
		$tag   = new Tag(Tag::SELF_CLOSING_TAG, 'X', 0, 0);

		$this->assertFalse($dummy->__filterTag($tag));
	}

	/**
	* @testdox filterTag() calls the logger's setTag() and unsetTag() methods
	*/
	public function testFilterTagCallsLoggerSetTag()
	{
		$mock = $this->getMockBuilder('stdClass')
		             ->setMethods(['foo'])
		             ->getMock();
		$mock->expects($this->once())
		     ->method('foo')
		     ->will($this->returnValue(false));

		$tag = $this->configurator->tags->add('X');
		$tag->filterChain->append([$mock, 'foo']);

		$dummy = new FilterProcessingDummy($this->configurator->asConfig());
		$tag   = new Tag(Tag::SELF_CLOSING_TAG, 'X', 0, 0);


		$dummy->logger = $this->getMockBuilder('s9e\\TextFormatter\\Parser\\Logger')
		                      ->setMethods(['setTag', 'unsetTag'])
		                      ->getMock();
		$dummy->logger->expects($this->once())
		              ->method('setTag')
		              ->with($this->identicalTo($tag));
		$dummy->logger->expects($this->once())
		              ->method('unsetTag');

		$dummy->__filterTag($tag);
	}

	/**
	* @testdox filterTag() can pass its own instance to tag filters via the 'parser' parameter
	*/
	public function testFilterTagPassesParser()
	{
		$mock = $this->getMockBuilder('stdClass')
		             ->setMethods(['foo'])
		             ->getMock();

		$tag    = $this->configurator->tags->add('X');
		$filter = $tag->filterChain->append([$mock, 'foo']);
		$filter->resetParameters();
		$filter->addParameterByName('parser');

		$dummy = new FilterProcessingDummy($this->configurator->asConfig());
		$tag   = new Tag(Tag::SELF_CLOSING_TAG, 'X', 0, 0);

		$mock->expects($this->once())
		     ->method('foo')
		     ->with($this->identicalTo($dummy))
		     ->will($this->returnValue(true));

		$this->assertTrue($dummy->__filterTag($tag));
	}

	/**
	* @testdox filterTag() can pass the list of open tags to tag filters via the 'openTags' parameter
	*/
	public function testFilterTagPassesOpenTags()
	{
		$mock = $this->getMockBuilder('stdClass')
		             ->setMethods(['foo'])
		             ->getMock();
		$mock->expects($this->at(0))
		     ->method('foo')
		     ->with([])
		     ->will($this->returnValue(true));
		$mock->expects($this->at(1))
		     ->method('foo')
		     ->with([new Tag(Tag::START_TAG, 'X', 0, 0)])
		     ->will($this->returnValue(true));

		$this->configurator->rulesGenerator->clear();
		$filterChain = $this->configurator->tags->add('X')->filterChain;
		$filter = $filterChain->append([$mock, 'foo']);
		$filter->resetParameters();
		$filter->addParameterByName('openTags');

		$parser = $this->getParser();
		$parser->registerParser(
			'Test',
			function () use ($parser)
			{
				$parser->addStartTag('X', 0, 0);
				$parser->addSelfClosingTag('X', 1, 0);
				$parser->addEndTag('X', 2, 0);
			}
		);

		$parser->parse('...');
	}

	/**
	* @testdox filterTag() can pass the text being parsed via the 'text' parameter
	*/
	public function testFilterTagPassesText()
	{
		$mock = $this->getMockBuilder('stdClass')
		             ->setMethods(['foo'])
		             ->getMock();
		$mock->expects($this->once())
			     ->method('foo')
			     ->with('...');

		$tag    = $this->configurator->tags->add('X');
		$filter = $tag->filterChain->append([$mock, 'foo']);
		$filter->resetParameters();
		$filter->addParameterByName('text');

		$parser = $this->getParser();
		$parser->registerParser(
			'Test',
			function () use ($parser)
			{
				$parser->addSelfClosingTag('X', 0, 0);
			}
		);

		$parser->parse('...');
	}

	/**
	* @testdox filterAttributes() removes the tag's attributes if none were configured
	*/
	public function testFilterAttributesNukesAttributes()
	{
		$tag = new Tag(Tag::SELF_CLOSING_TAG, 'X', 0, 0);
		$tag->setAttribute('foo', 'foo');

		Parser::filterAttributes($tag, [], [], new Logger);

		$this->assertSame(
			[],
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

		Parser::filterAttributes($tag, $tagConfig->asConfig(), [], new Logger);

		$this->assertSame(
			['foo' => 42],
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

		Parser::filterAttributes($tag, $tagConfig->asConfig(), [], new Logger);

		$this->assertSame(
			['foo' => 'foo'],
			$tag->getAttributes()
		);
	}

	/**
	* @testdox filterAttributes() executes every filter of an attribute's filterChain and returns the value
	*/
	public function testFilterAttributesExecutesFilterChain()
	{
		$mock = $this->getMockBuilder('stdClass')
		             ->setMethods(['foo', 'bar'])
		             ->getMock();
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
		$attribute->filterChain->append([$mock, 'foo']);
		$attribute->filterChain->append([$mock, 'bar']);

		$tag = new Tag(Tag::SELF_CLOSING_TAG, 'X', 0, 0);
		$tag->setAttribute('x', 'xxx');

		Parser::filterAttributes($tag, $tagConfig->asConfig(), [], new Logger);

		$this->assertSame(
			['x' => 'bar'],
			$tag->getAttributes()
		);
	}

	/**
	* @testdox filterAttributes() stops executing the attribute's filterChain and returns FALSE if a filter returns FALSE
	*/
	public function testFilterAttributesReturnsFalse()
	{
		$mock = $this->getMockBuilder('stdClass')
		             ->setMethods(['foo', 'bar'])
		             ->getMock();
		$mock->expects($this->once())
		     ->method('foo')
		     ->with('xxx')
		     ->will($this->returnValue(false));
		$mock->expects($this->never())
		     ->method('bar');

		$tagConfig = new TagConfig;
		$attribute = $tagConfig->attributes->add('x');
		$attribute->filterChain->append([$mock, 'foo']);
		$attribute->filterChain->append([$mock, 'bar']);

		$tag = new Tag(Tag::SELF_CLOSING_TAG, 'X', 0, 0);
		$tag->setAttribute('x', 'xxx');

		$this->assertFalse(Parser::filterAttributes($tag, $tagConfig->asConfig(), [], new Logger));
	}

	/**
	* @testdox filterAttributes() removes invalid attributes
	*/
	public function testFilterAttributesRemovesInvalid()
	{
		$mock = $this->getMockBuilder('stdClass')
		             ->setMethods(['foo'])
		             ->getMock();
		$mock->expects($this->once())
		     ->method('foo')
		     ->with('xxx')
		     ->will($this->returnValue(false));

		$tagConfig = new TagConfig;
		$attribute = $tagConfig->attributes->add('x');
		$attribute->filterChain->append([$mock, 'foo']);

		$tag = new Tag(Tag::SELF_CLOSING_TAG, 'X', 0, 0);
		$tag->setAttribute('x', 'xxx');

		Parser::filterAttributes($tag, $tagConfig->asConfig(), [], new Logger);

		$this->assertSame(
			[],
			$tag->getAttributes()
		);
	}

	/**
	* @testdox filterAttributes() replaces invalid attributes with their defaultValue if applicable
	*/
	public function testFilterAttributesReplacesInvalid()
	{
		$mock = $this->getMockBuilder('stdClass')
		             ->setMethods(['foo'])
		             ->getMock();
		$mock->expects($this->once())
		     ->method('foo')
		     ->with('xxx')
		     ->will($this->returnValue(false));

		$tagConfig = new TagConfig;
		$attribute = $tagConfig->attributes->add('x');
		$attribute->filterChain->append([$mock, 'foo']);
		$attribute->defaultValue = 'default';

		$tag = new Tag(Tag::SELF_CLOSING_TAG, 'X', 0, 0);
		$tag->setAttribute('x', 'xxx');

		Parser::filterAttributes($tag, $tagConfig->asConfig(), [], new Logger);

		$this->assertSame(
			['x' => 'default'],
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

		Parser::filterAttributes($tag, $tagConfig->asConfig(), [], new Logger);

		$this->assertSame(
			['x' => 'default'],
			$tag->getAttributes()
		);
	}

	/**
	* @testdox filterAttributes() calls the logger's setAttribute() and unsetAttribute() methods for each attribute with a filterChain
	*/
	public function testFilterAttributesCallsLoggerSetAttribute()
	{
		$logger = $this->getMockBuilder('s9e\\TextFormatter\\Parser\\Logger')
		               ->setMethods(['setAttribute', 'unsetAttribute'])
		               ->getMock();
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

		Parser::filterAttributes($tag, $tagConfig->asConfig(), [], $logger);
	}
}

class FilterProcessingDummy extends Parser
{
	public $registeredVars;
	public $tagsConfig = [
		'X' => []
	];
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
		return call_user_func_array([$this, 'filterTag'], func_get_args());
	}

	public static function __executeFilter()
	{
		return call_user_func_array('parent::executeFilter', func_get_args());
	}
}