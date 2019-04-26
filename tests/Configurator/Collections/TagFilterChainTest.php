<?php

namespace s9e\TextFormatter\Tests\Configurator\Collections;

use s9e\TextFormatter\Configurator\Collections\TagFilterChain;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\Items\TagFilter;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Collections\FilterChain
* @covers s9e\TextFormatter\Configurator\Collections\TagFilterChain
*/
class TagFilterChainTest extends Test
{
	private function privateMethod() {}
	public function doNothing() {}

	/**
	* @testdox append() throws an InvalidArgumentException on invalid callbacks
	*/
	public function testAppendInvalidCallback()
	{
		$this->expectException('InvalidArgumentException');
		$this->expectExceptionMessage("Filter '*invalid*' is neither callable nor an instance of s9e\\TextFormatter\\Configurator\\Items\\TagFilter");

		$filterChain = new TagFilterChain;
		$filterChain->append('*invalid*');
	}

	/**
	* @testdox prepend() throws an InvalidArgumentException on invalid callbacks
	*/
	public function testPrependInvalidCallback()
	{
		$this->expectException('InvalidArgumentException');
		$this->expectExceptionMessage("Filter '*invalid*' is neither callable nor an instance of s9e\\TextFormatter\\Configurator\\Items\\TagFilter");

		$filterChain = new TagFilterChain;
		$filterChain->prepend('*invalid*');
	}

	/**
	* @testdox append() throws an InvalidArgumentException on uncallable callbacks
	*/
	public function testAppendUncallableCallback()
	{
		$this->expectException('InvalidArgumentException');
		$this->expectExceptionMessage('is neither callable nor an instance of s9e\\TextFormatter\\Configurator\\Items\\TagFilter');

		$filterChain = new TagFilterChain;
		$filterChain->append([__CLASS__, 'privateMethod']);
	}

	/**
	* @testdox prepend() throws an InvalidArgumentException on uncallable callbacks
	*/
	public function testPrependUncallableCallback()
	{
		$this->expectException('InvalidArgumentException');
		$this->expectExceptionMessage('is neither callable nor an instance of s9e\\TextFormatter\\Configurator\\Items\\TagFilter');

		$filterChain = new TagFilterChain;
		$filterChain->prepend([__CLASS__, 'privateMethod']);
	}

	/**
	* @testdox PHP string callbacks are normalized to an instance of TagFilter
	*/
	public function testStringCallback()
	{
		$filterChain = new TagFilterChain;

		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\Items\\TagFilter',
			$filterChain->append('strtolower')
		);
	}

	/**
	* @testdox PHP array callbacks are normalized to an instance of TagFilter
	*/
	public function testArrayCallback()
	{
		$filterChain = new TagFilterChain;

		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\Items\\TagFilter',
			$filterChain->append([$this, 'doNothing'])
		);
	}

	/**
	* @testdox Instances of TagFilter are added as-is
	*/
	public function testTagFilterInstance()
	{
		$filterChain = new TagFilterChain;
		$filter = new TagFilter('strtolower');

		$this->assertSame(
			$filter,
			$filterChain->append($filter)
		);
	}

	/**
	* @testdox containsCallback('s9e\\TextFormatter\\Parser\\FilterProcessing::filterAttributes') returns true on default tags
	*/
	public function testContainsCallback()
	{
		$tag = new Tag;
		$this->assertTrue($tag->filterChain->containsCallback('s9e\\TextFormatter\\Parser\\FilterProcessing::filterAttributes'));
	}

	/**
	* @testdox containsCallback('s9e\\TextFormatter\\Parser\\FilterProcessing::filterAttributes') returns false on empty chains
	*/
	public function testContainsCallbackFalse()
	{
		$filterChain = new TagFilterChain;
		$this->assertFalse($filterChain->containsCallback('s9e\\TextFormatter\\Parser\\FilterProcessing::filterAttributes'));
	}

}