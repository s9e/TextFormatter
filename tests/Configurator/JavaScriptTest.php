<?php

namespace s9e\TextFormatter\Tests\Configurator;

use stdClass;
use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Configurator\ConfigProvider;
use s9e\TextFormatter\Configurator\Items\ProgrammableCallback;
use s9e\TextFormatter\Configurator\Items\Regexp as RegexpObject;
use s9e\TextFormatter\Configurator\JavaScript;
use s9e\TextFormatter\Configurator\JavaScript\Code;
use s9e\TextFormatter\Configurator\JavaScript\Dictionary;
use s9e\TextFormatter\Configurator\JavaScript\Minifier;
use s9e\TextFormatter\Configurator\JavaScript\Minifiers\ClosureCompilerService;
use s9e\TextFormatter\Configurator\JavaScript\RegExp;
use s9e\TextFormatter\Plugins\ConfiguratorBase;
use s9e\TextFormatter\Tests\Test;

/**
* @requires extension json
* @covers s9e\TextFormatter\Configurator\JavaScript
*/
class JavaScriptTest extends Test
{
	/**
	* @testdox getMinifier() returns an instance of Noop by default
	*/
	public function testGetMinifier()
	{
		$javascript = new JavaScript(new Configurator);

		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\JavaScript\\Minifiers\\Noop',
			$javascript->getMinifier()
		);
	}

	/**
	* @testdox setMinifier() accepts the name of a minifier type and returns an instance
	*/
	public function testSetMinifierName()
	{
		$javascript = new JavaScript(new Configurator);

		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\JavaScript\\Minifiers\\ClosureCompilerService',
			$javascript->setMinifier('ClosureCompilerService')
		);
	}

	/**
	* @testdox setMinifier() accepts the name of a minifier type plus any number of arguments passed to the minifier's constructor
	*/
	public function testSetMinifierArguments()
	{
		$javascript = new JavaScript(new Configurator);

		$this->assertSame(
			__FILE__,
			$javascript->setMinifier('ClosureCompilerApplication', __FILE__)->closureCompilerBin
		);
	}

	/**
	* @testdox setMinifier() accepts an object that implements Minifier
	*/
	public function testSetMinifierInstance()
	{
		$javascript = new JavaScript(new Configurator);
		$minifier   = new ClosureCompilerService;

		$javascript->setMinifier($minifier);

		$this->assertSame($minifier, $javascript->getMinifier());
	}

	/**
	* @testdox setMinifier() returns the new instance
	*/
	public function testSetMinifierReturn()
	{
		$javascript  = new JavaScript(new Configurator);
		$oldMinifier = $javascript->getMinifier();

		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\JavaScript\\Minifiers\\Noop',
			$javascript->setMinifier('Noop')
		);

		$this->assertNotSame($oldMinifier, $javascript->getMinifier());
	}

	/**
	* @testdox getParser() calls the minifier and returns its result
	*/
	public function testMinifierReturn()
	{
		$mock = $this->getMock('s9e\\TextFormatter\\Configurator\\JavaScript\\Minifier');
		$mock->expects($this->once())
		     ->method('get')
		     ->will($this->returnValue('/**/'));

		$this->configurator->javascript->setMinifier($mock);

		$this->assertSame('/**/', $this->configurator->javascript->getParser());
	}

	/**
	* @testdox A plugin's quickMatch value is preserved if it's valid UTF-8
	*/
	public function testQuickMatchUTF8()
	{
		$this->configurator->plugins->load('Escaper', ['quickMatch' => 'foo']);

		$this->assertContains(
			'quickMatch:"foo"',
			$this->configurator->javascript->getParser()
		);
	}

	/**
	* @testdox A plugin's quickMatch value is discarded if it contains no valid UTF-8
	*/
	public function testQuickMatchUTF8Bad()
	{
		$this->configurator->plugins->load('Escaper', ['quickMatch' => "\xC0"]);

		$this->assertNotContains(
			'quickMatch:',
			$this->configurator->javascript->getParser()
		);
	}

	/**
	* @testdox If a plugin's quickMatch value contains bad UTF-8, only the first consecutive valid characters are kept
	*/
	public function testQuickMatchUTF8Partial()
	{
		$this->configurator->plugins->load('Escaper', ['quickMatch' => "\xC0xÿz"]);

		$this->assertContains(
			'quickMatch:"x\\u00ffz"',
			$this->configurator->javascript->getParser()
		);
	}

	/**
	* @testdox A plugin with no JS parser does not appear in the source
	*/
	public function testPluginNoParser()
	{
		$this->configurator->plugins['Foobar'] = new NoJSPluginConfigurator($this->configurator);

		$this->assertNotContains(
			'Foobar',
			$this->configurator->javascript->getParser()
		);
	}

	/**
	* @testdox getParser() throws an exception if it encounters a value that cannot be encoded into JavaScript
	* @expectedException RuntimeException
	* @expectedExceptionMessage Cannot encode non-scalar value
	*/
	public function testNonScalarConfigException()
	{
		$this->configurator->registeredVars['foo'] = new NonScalarConfigThing;
		$this->configurator->javascript->getParser();
	}

	/**
	* @testdox Attribute generators are converted
	*/
	public function testAttributeGenerator()
	{
		$js = 'function() { return "foo"; }';

		$callback = new ProgrammableCallback(function() { return 'foo'; });
		$callback->setJS($js);

		$this->configurator->tags->add('FOO')->attributes->add('bar')->generator = $callback;

		$this->assertContains(
			$js,
			$this->configurator->javascript->getParser()
		);
	}

	/**
	* @testdox Built-in attribute filters are converted
	*/
	public function testAttributeFilterBuiltIn()
	{
		$this->configurator->tags->add('FOO')->attributes->add('bar')->filterChain->append(
			$this->configurator->attributeFilters->get('#number')
		);

		$js = $this->configurator->javascript->getParser();

		$this->assertContains(
			'filterChain:[c4CA1D8BA]',
			$js
		);
		$this->assertContains(
			'function c4CA1D8BA(attrValue,attrName){return (BuiltInFilters.filterNumber)(attrValue);}',
			$js
		);
	}

	/**
	* @testdox An attribute filter that uses a built-in filter as callback is converted
	*/
	public function testAttributeFilterBuiltInCallback()
	{
		$this->configurator->tags->add('FOO')->attributes->add('bar')->filterChain->append(
			's9e\\TextFormatter\\Parser\\BuiltInFilters::filterInt'
		);

		$js = $this->configurator->javascript->getParser();

		$this->assertContains(
			'filterChain:[c2503AA6D]',
			$js
		);
		$this->assertContains(
			'function c2503AA6D(tag,tagConfig){return filterAttributes(tag,tagConfig,registeredVars,logger);}',
			$js
		);
	}

	/**
	* @testdox An attribute filter with no JS representation unconditionally returns false
	*/
	public function testAttributeFilterMissing()
	{
		$this->configurator->tags->add('FOO')->attributes->add('bar')->filterChain->append(
			function() {}
		);

		$js = $this->configurator->javascript->getParser();

		$this->assertContains(
			'filterChain:[c6FF7A507]',
			$js
		);
		$this->assertContains(
			'function c6FF7A507(attrValue,attrName){return (function(){return false;})(attrValue);}',
			$js
		);
	}

	/**
	* @testdox The name of a registered vars is expressed in quotes
	*/
	public function testRegisteredVarBracket()
	{
		$this->configurator->registeredVars = ['foo' => 'bar'];

		$this->assertContains(
			'registeredVars={"foo":"bar"}',
			$this->configurator->javascript->getParser()
		);
	}

	/**
	* @testdox "cacheDir" is removed from registered vars
	*/
	public function testRegisteredVarCacheDir()
	{
		$this->configurator->registeredVars = ['cacheDir' => '', 'foo' => 'bar'];

		$src = $this->configurator->javascript->getParser();

		$this->assertContains('registeredVars={"foo":"bar"}', $src);
		$this->assertNotContains('cacheDir', $src);
	}

	/**
	* @testdox "className" is removed from the plugins' config
	*/
	public function testOmitClassName()
	{
		$this->configurator->Autolink;

		$src = $this->configurator->javascript->getParser();

		$this->assertNotContains('className', $src);
	}

	/**
	* @testdox Callbacks use the bracket syntax to access registered vars
	*/
	public function testCallbackRegisteredVarBracket()
	{
		$this->configurator->registeredVars = ['foo' => 'bar'];

		$this->configurator->tags->add('FOO')->attributes->add('bar')->filterChain
			->append('strtolower')
			->resetParameters()
			->addParameterByName('foo');

		$this->assertContains(
			'registeredVars["foo"]',
			$this->configurator->javascript->getParser()
		);
	}

	/**
	* @testdox Callbacks correctly encode values
	*/
	public function testCallbackValue()
	{
		$this->configurator->tags->add('FOO')->attributes->add('bar')->filterChain
			->append('strtolower')
			->resetParameters()
			->addParameterByValue('foo')
			->addParameterByValue(42);

		$this->assertContains(
			'("foo",42)',
			$this->configurator->javascript->getParser()
		);
	}

	/**
	* @testdox isLegalProp() tests
	*/
	public function testLegalProps()
	{
		$legal = [
			'foo',
			'foo33',
			'G89',
			'$foo',
			'$foo$bar',
			'foo_bar'
		];

		foreach ($legal as $name)
		{
			$this->assertTrue(JavaScript::isLegalProp($name), $name);
		}

		$illegal = [
			'',
			'0foo',
			'foo bar',
			"foo\n",
			'foo-bar',
			"'foo'",
			'"foo"',
			'youtube.com',
			'with',
			'break',
			'false',
			'float'
		];

		foreach ($illegal as $name)
		{
			$this->assertFalse(JavaScript::isLegalProp($name), $name);
		}
	}

	/**
	* @testdox encode() tests
	* @dataProvider getEncodeTests
	*/
	public function testEncode($original, $expected)
	{
		$this->assertSame($expected, JavaScript::encode($original));
	}

	public function getEncodeTests()
	{
		return [
			[
				123,
				'123'
			],
			[
				'foo',
				'"foo"'
			],
			[
				// NOTE: false/true are converted to 0/1 as encode() is meant to be used for
				// configuration settings and in that case any true-ish value will do
				false,
				'0'
			],
			[
				true,
				'1'
			],
			[
				[1, 2],
				'[1,2]'
			],
			[
				['foo' => 'bar', 'baz' => 'quux'],
				'{foo:"bar",baz:"quux"}'
			],
			[
				['' => 'bar', 'baz' => 'quux'],
				'{"":"bar",baz:"quux"}'
			],
			[
				new Dictionary(['foo' => 'bar', 'baz' => 'quux']),
				'{"foo":"bar","baz":"quux"}'
			],
			[
				new Dictionary(['' => 'bar', 'baz' => 'quux']),
				'{"":"bar","baz":"quux"}'
			],
			[
				new RegExp('^foo$'),
				'/^foo$/'
			],
			[
				new RegexpObject('/^foo$/'),
				'/^foo$/'
			],
			[
				new Code('function(){return false;}'),
				'function(){return false;}'
			],
		];
	}

	/**
	* @testdox HINT.closeAncestor=0 by default
	*/
	public function testHintCloseAncestorFalse()
	{
		$this->assertContains(
			'HINT.closeAncestor=0',
			$this->configurator->javascript->getParser()
		);
	}

	/**
	* @testdox HINT.closeAncestor=1 if any tag has a closeAncestor rule
	*/
	public function testHintCloseAncestorTrue()
	{
		$this->configurator->tags->add('X')->rules->closeAncestor('X');

		$this->assertContains(
			'HINT.closeAncestor=1',
			$this->configurator->javascript->getParser()
		);
	}

	/**
	* @testdox HINT.closeParent=0 by default
	*/
	public function testHintCloseParentFalse()
	{
		$this->assertContains(
			'HINT.closeParent=0',
			$this->configurator->javascript->getParser()
		);
	}

	/**
	* @testdox HINT.closeParent=1 if any tag has a closeParent rule
	*/
	public function testHintCloseParentTrue()
	{
		$this->configurator->tags->add('X')->rules->closeParent('X');

		$this->assertContains(
			'HINT.closeParent=1',
			$this->configurator->javascript->getParser()
		);
	}

	/**
	* @testdox HINT.fosterParent=0 by default
	*/
	public function testHintFosterParentFalse()
	{
		$this->assertContains(
			'HINT.fosterParent=0',
			$this->configurator->javascript->getParser()
		);
	}

	/**
	* @testdox HINT.fosterParent=1 if any tag has a fosterParent rule
	*/
	public function testHintFosterParentTrue()
	{
		$this->configurator->tags->add('X')->rules->fosterParent('Y');
		$this->configurator->tags->add('Y');

		$this->assertContains(
			'HINT.fosterParent=1',
			$this->configurator->javascript->getParser()
		);
	}

	/**
	* @testdox HINT.postProcessing=0 by default
	*/
	public function testHintPostProcessingFalse()
	{
		$this->assertContains(
			'HINT.postProcessing=0',
			$this->configurator->javascript->getParser()
		);
	}

	/**
	* @testdox HINT.postProcessing=1 if "data-s9e-livepreview-postprocess" appears in the stylesheet
	*/
	public function testHintPostProcessingTrue()
	{
		$this->configurator->tags->add('X')->defaultTemplate
			= '<hr data-s9e-livepreview-postprocess="foo(this)"/>';

		$this->assertContains(
			'HINT.postProcessing=1',
			$this->configurator->javascript->getParser()
		);
	}

	/**
	* @testdox HINT.regexpLimitActionAbort=0 by default
	*/
	public function testHintRegexpLimitActionAbortFalse()
	{
		$this->assertContains(
			'HINT.regexpLimitActionAbort=0',
			$this->configurator->javascript->getParser()
		);
	}

	/**
	* @testdox HINT.regexpLimitActionAbort=1 if a plugin has setRegexpLimitAction('abort')
	*/
	public function testHintRegexpLimitActionAbortTrue()
	{
		$this->configurator->Autolink->setRegexpLimitAction('abort');
		$this->assertContains(
			'HINT.regexpLimitActionAbort=1',
			$this->configurator->javascript->getParser()
		);
	}

	/**
	* @testdox HINT.regexpLimitActionAbort=0 if a plugin has setRegexpLimitAction('abort') but is disabled
	*/
	public function testHintRegexpLimitActionAbortFalseDisabled()
	{
		$this->configurator->Generic->setRegexpLimitAction('abort');
		$this->assertContains(
			'HINT.regexpLimitActionAbort=0',
			$this->configurator->javascript->getParser()
		);
	}

	/**
	* @testdox HINT.regexpLimitActionIgnore=0 by default
	*/
	public function testHintRegexpLimitActionIgnoreFalse()
	{
		$this->assertContains(
			'HINT.regexpLimitActionIgnore=0',
			$this->configurator->javascript->getParser()
		);
	}

	/**
	* @testdox HINT.regexpLimitActionIgnore=1 if a plugin has setRegexpLimitAction('ignore')
	*/
	public function testHintRegexpLimitActionIgnoreTrue()
	{
		$this->configurator->Autolink->setRegexpLimitAction('ignore');
		$this->assertContains(
			'HINT.regexpLimitActionIgnore=1',
			$this->configurator->javascript->getParser()
		);
	}

	/**
	* @testdox HINT.regexpLimitActionIgnore=0 if a plugin has setRegexpLimitAction('ignore') but is disabled
	*/
	public function testHintRegexpLimitActionIgnoreFalseDisabled()
	{
		$this->configurator->Generic->setRegexpLimitAction('ignore');
		$this->assertContains(
			'HINT.regexpLimitActionIgnore=0',
			$this->configurator->javascript->getParser()
		);
	}

	/**
	* @testdox HINT.regexpLimitActionWarn=0 by default
	*/
	public function testHintRegexpLimitActionWarnFalse()
	{
		$this->assertContains(
			'HINT.regexpLimitActionWarn=0',
			$this->configurator->javascript->getParser()
		);
	}

	/**
	* @testdox HINT.regexpLimitActionWarn=1 if a plugin has setRegexpLimitAction('warn')
	*/
	public function testHintRegexpLimitActionWarnTrue()
	{
		$this->configurator->Autolink->setRegexpLimitAction('warn');
		$this->assertContains(
			'HINT.regexpLimitActionWarn=1',
			$this->configurator->javascript->getParser()
		);
	}

	/**
	* @testdox HINT.regexpLimitActionWarn=0 if a plugin has setRegexpLimitAction('warn') but is disabled
	*/
	public function testHintRegexpLimitActionWarnFalseDisabled()
	{
		$this->configurator->Generic->setRegexpLimitAction('warn');
		$this->assertContains(
			'HINT.regexpLimitActionWarn=0',
			$this->configurator->javascript->getParser()
		);
	}

	/**
	* @testdox HINT.requireAncestor=0 by default
	*/
	public function testHintRequireAncestorFalse()
	{
		$this->assertContains(
			'HINT.requireAncestor=0',
			$this->configurator->javascript->getParser()
		);
	}

	/**
	* @testdox HINT.requireAncestor=1 if any tag has a requireAncestor rule
	*/
	public function testHintRequireAncestorTrue()
	{
		$this->configurator->tags->add('X')->rules->requireAncestor('Y');

		$this->assertContains(
			'HINT.requireAncestor=1',
			$this->configurator->javascript->getParser()
		);
	}

	/**
	* @testdox HINT.RULE_AUTO_CLOSE=0 by default
	*/
	public function testHintRuleAutoCloseFalse()
	{
		$this->assertContains(
			'HINT.RULE_AUTO_CLOSE=0',
			$this->configurator->javascript->getParser()
		);
	}

	/**
	* @testdox HINT.RULE_AUTO_CLOSE=1 if any tag has an autoClose rule
	*/
	public function testHintRuleAutoCloseTrue()
	{
		$this->configurator->tags->add('X')->rules->autoClose();

		$this->assertContains(
			'HINT.RULE_AUTO_CLOSE=1',
			$this->configurator->javascript->getParser()
		);
	}

	/**
	* @testdox HINT.RULE_AUTO_REOPEN=0 by default
	*/
	public function testHintRuleAutoReopenFalse()
	{
		$this->assertContains(
			'HINT.RULE_AUTO_REOPEN=0',
			$this->configurator->javascript->getParser()
		);
	}

	/**
	* @testdox HINT.RULE_AUTO_REOPEN=1 if any tag has an autoReopen rule
	*/
	public function testHintRuleAutoReopenTrue()
	{
		$this->configurator->tags->add('X')->rules->autoReopen();

		$this->assertContains(
			'HINT.RULE_AUTO_REOPEN=1',
			$this->configurator->javascript->getParser()
		);
	}

	/**
	* @testdox HINT.RULE_BREAK_PARAGRAPH=0 by default
	*/
	public function testHintRuleBreakParagraphFalse()
	{
		$this->assertContains(
			'HINT.RULE_BREAK_PARAGRAPH=0',
			$this->configurator->javascript->getParser()
		);
	}

	/**
	* @testdox HINT.RULE_BREAK_PARAGRAPH=1 if any tag has a breakParagraph rule
	*/
	public function testHintRuleBreakParagraphsTrue()
	{
		$this->configurator->tags->add('X')->rules->breakParagraph();

		$this->assertContains(
			'HINT.RULE_BREAK_PARAGRAPH=1',
			$this->configurator->javascript->getParser()
		);
	}

	/**
	* @testdox HINT.RULE_CREATE_PARAGRAPHS=0 by default
	*/
	public function testHintRuleCreateParagraphsFalse()
	{
		$this->assertContains(
			'HINT.RULE_CREATE_PARAGRAPHS=0',
			$this->configurator->javascript->getParser()
		);
	}

	/**
	* @testdox HINT.RULE_CREATE_PARAGRAPHS=1 if any tag has a createParagraphs rule
	*/
	public function testHintRuleCreateParagraphsTrue()
	{
		$this->configurator->tags->add('X')->rules->createParagraphs();

		$this->assertContains(
			'HINT.RULE_CREATE_PARAGRAPHS=1',
			$this->configurator->javascript->getParser()
		);
	}

	/**
	* @testdox HINT.RULE_CREATE_PARAGRAPHS=1 if the root rules have a createParagraphs rule
	*/
	public function testHintRuleCreateParagraphsRoot()
	{
		$this->configurator->rootRules->createParagraphs();

		$this->assertContains(
			'HINT.RULE_CREATE_PARAGRAPHS=1',
			$this->configurator->javascript->getParser()
		);
	}

	/**
	* @testdox HINT.RULE_IGNORE_TEXT=0 by default
	*/
	public function testHintRuleIgnoreTextFalse()
	{
		$this->assertContains(
			'HINT.RULE_IGNORE_TEXT=0',
			$this->configurator->javascript->getParser()
		);
	}

	/**
	* @testdox HINT.RULE_IGNORE_TEXT=1 if any tag has an ignoreText rule
	*/
	public function testHintRuleIgnoreTextTrue()
	{
		$this->configurator->tags->add('X')->rules->ignoreText();

		$this->assertContains(
			'HINT.RULE_IGNORE_TEXT=1',
			$this->configurator->javascript->getParser()
		);
	}

	/**
	* @testdox HINT.RULE_IGNORE_TEXT=1 if the root rules have a createParagraphs rule
	*/
	public function testHintRuleIgnoreTextRoot()
	{
		$this->configurator->rootRules->ignoreText();

		$this->assertContains(
			'HINT.RULE_IGNORE_TEXT=1',
			$this->configurator->javascript->getParser()
		);
	}

	/**
	* @testdox HINT.RULE_IS_TRANSPARENT=0 by default
	*/
	public function testHintRuleIsTransparentFalse()
	{
		$this->assertContains(
			'HINT.RULE_IS_TRANSPARENT=0',
			$this->configurator->javascript->getParser()
		);
	}

	/**
	* @testdox HINT.RULE_IS_TRANSPARENT=1 if any tag has an isTransparent rule
	*/
	public function testHintRuleIsTransparentTrue()
	{
		$this->configurator->tags->add('X')->rules->isTransparent();

		$this->assertContains(
			'HINT.RULE_IS_TRANSPARENT=1',
			$this->configurator->javascript->getParser()
		);
	}

	/**
	* @testdox HINT.RULE_NO_BR_CHILD=0 by default
	*/
	public function testHintRuleNoBrChildFalse()
	{
		$this->assertContains(
			'HINT.RULE_NO_BR_CHILD=0',
			$this->configurator->javascript->getParser()
		);
	}

	/**
	* @testdox HINT.RULE_NO_BR_CHILD=1 if any tag has a noBrChild rule
	*/
	public function testHintRuleNoBrChildTrue()
	{
		$this->configurator->tags->add('X')->rules->noBrChild();

		$this->assertContains(
			'HINT.RULE_NO_BR_CHILD=1',
			$this->configurator->javascript->getParser()
		);
	}

	/**
	* @testdox HINT.RULE_NO_BR_CHILD=1 if the root rules have a createParagraphs rule
	*/
	public function testHintRuleNoBrChildRoot()
	{
		$this->configurator->rootRules->noBrChild();

		$this->assertContains(
			'HINT.RULE_NO_BR_CHILD=1',
			$this->configurator->javascript->getParser()
		);
	}

	/**
	* @testdox HINT.RULE_NO_BR_DESCENDANT=0 by default
	*/
	public function testHintRuleNoBrDescendantFalse()
	{
		$this->assertContains(
			'HINT.RULE_NO_BR_DESCENDANT=0',
			$this->configurator->javascript->getParser()
		);
	}

	/**
	* @testdox HINT.RULE_NO_BR_DESCENDANT=1 if any tag has a noBrDescendant rule
	*/
	public function testHintRuleNoBrDescendantTrue()
	{
		$this->configurator->tags->add('X')->rules->noBrDescendant();

		$this->assertContains(
			'HINT.RULE_NO_BR_DESCENDANT=1',
			$this->configurator->javascript->getParser()
		);
	}

	/**
	* @testdox HINT.RULE_NO_BR_DESCENDANT=1 if the root rules have a createParagraphs rule
	*/
	public function testHintRuleNoBrDescendantRoot()
	{
		$this->configurator->rootRules->noBrDescendant();

		$this->assertContains(
			'HINT.RULE_NO_BR_DESCENDANT=1',
			$this->configurator->javascript->getParser()
		);
	}

	/**
	* @testdox HINT.RULE_TRIM_WHITESPACE=0 by default
	*/
	public function testHintRuleIgnoreSurroundingWhitespaceFalse()
	{
		$this->assertContains(
			'HINT.RULE_TRIM_WHITESPACE=0',
			$this->configurator->javascript->getParser()
		);
	}

	/**
	* @testdox HINT.RULE_TRIM_WHITESPACE=1 if any tag has an ignoreSurroundingWhitespace rule
	*/
	public function testHintRuleIgnoreSurroundingWhitespaceTrue()
	{
		$this->configurator->tags->add('X')->rules->ignoreSurroundingWhitespace();

		$this->assertContains(
			'HINT.RULE_TRIM_WHITESPACE=1',
			$this->configurator->javascript->getParser()
		);
	}
}

class NonScalarConfigThing implements ConfigProvider
{
	public function asConfig()
	{
		return ['foo' => new stdClass];
	}
}

class NoJSPluginConfigurator extends ConfiguratorBase
{
	public function asConfig()
	{
		return ['regexp' => '//'];
	}
}