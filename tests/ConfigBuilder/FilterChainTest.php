<?php

namespace s9e\TextFormatter\Tests\ConfigBuilder;

use Exception,
    s9e\TextFormatter\Tests\Test,
    s9e\TextFormatter\ConfigBuilder\FilterChain;

include_once __DIR__ . '/../../src/autoloader.php';

/**
* @covers s9e\TextFormatter\ConfigBuilder\FilterChain
*/
class FilterChainTest extends Test
{
	/**
	* @testdox append('#int')
	*/
	public function testAppendDefaultFilter()
	{
		$filterChain = new FilterChain(array('attrVal' => null));
		$filterChain->append('#int');
	}

	/**
	* @testdox append('strtolower')
	*/
	public function testAppendStringCallback()
	{
		$filterChain = new FilterChain(array('attrVal' => null));
		$filterChain->append('strtolower');
	}

	/**
	* @testdox has('#int') returns true if '#int' is in the chain
	*/
	public function testHasDefaultFilterTrue()
	{
		$filterChain = new FilterChain(array('attrVal' => null));
		$filterChain->append('#int');

		$this->assertTrue($filterChain->has('#int'));
	}

	/**
	* @testdox has('#int') returns false if only '#number' is in the chain
	*/
	public function testHasDefaultFilterFalse()
	{
		$filterChain = new FilterChain(array('attrVal' => null));
		$filterChain->append('#number');

		$this->assertFalse($filterChain->has('#int'));
	}

	/**
	* @testdox has('strtolower') returns true if 'strtolower' is in the chain
	*/
	public function testHasStringCallbackTrue()
	{
		$filterChain = new FilterChain(array('attrVal' => null));
		$filterChain->append('strtolower');

		$this->assertTrue($filterChain->has('strtolower'));
	}

	/**
	* @testdox has('strtolower') returns false if 'trim' only is in the chain
	*/
	public function testHasStringCallbackFalse()
	{
		$filterChain = new FilterChain(array('attrVal' => null));
		$filterChain->append('trim');

		$this->assertFalse($filterChain->has('strtolower'));
	}
}