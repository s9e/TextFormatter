<?php

namespace s9e\TextFormatter\Tests\Configurator\JavaScript\Minifiers;

use Exception;
use s9e\TextFormatter\Configurator\JavaScript\Minifiers\FirstAvailable;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\JavaScript\Minifiers\FirstAvailable
*/
class FirstAvailableTest extends Test
{
	/**
	* @testdox Constructor accepts any number of known minifiers
	*/
	public function testConstructor()
	{
		$minifier1 = $this->getMockBuilder('s9e\\TextFormatter\\Configurator\\JavaScript\\Minifier')
		                  ->getMock();
		$minifier2 = $this->getMockBuilder('s9e\\TextFormatter\\Configurator\\JavaScript\\Minifier')
		                  ->getMock();
		$minifier = new FirstAvailable($minifier1, $minifier2);

		$this->assertSame($minifier1, $minifier[0]);
		$this->assertSame($minifier2, $minifier[1]);
	}

	/**
	* @testdox getCacheDifferentiator() is constant
	*/
	public function testGetCacheDifferentiator()
	{
		$minifier = new FirstAvailable;
		$this->assertSame(
			$minifier->getCacheDifferentiator(),
			$minifier->getCacheDifferentiator()
		);
	}

	/**
	* @testdox minify() returns the result of the first minification if applicable
	*/
	public function testFirst()
	{
		$minifier1 = $this->getMockBuilder('s9e\\TextFormatter\\Configurator\\JavaScript\\Minifier')
		                  ->getMock();
		$minifier1->expects($this->once())
		          ->method('minify')
		          ->will($this->returnValue('/**/'));

		$minifier2 = $this->getMockBuilder('s9e\\TextFormatter\\Configurator\\JavaScript\\Minifier')
		                  ->getMock();
		$minifier2->expects($this->never())
		          ->method('minify')
		          ->will($this->throwException(new Exception));

		$minifier = new FirstAvailable;
		$minifier->add($minifier1);
		$minifier->add($minifier2);

		$this->assertSame('/**/', $minifier->minify(''));
	}

	/**
	* @testdox minify() returns the result of the second minification if the first throws an exception
	*/
	public function testSecond()
	{
		$minifier1 = $this->getMockBuilder('s9e\\TextFormatter\\Configurator\\JavaScript\\Minifier')
		                  ->getMock();
		$minifier1->expects($this->once())
		          ->method('minify')
		          ->will($this->throwException(new Exception));

		$minifier2 = $this->getMockBuilder('s9e\\TextFormatter\\Configurator\\JavaScript\\Minifier')
		                  ->getMock();
		$minifier2->expects($this->once())
		          ->method('minify')
		          ->will($this->returnValue('/**/'));

		$minifier = new FirstAvailable;
		$minifier->add($minifier1);
		$minifier->add($minifier2);

		$this->assertSame('/**/', $minifier->minify(''));
	}

	/**
	* @testdox minify() throws an exception if no minifier is set
	* @expectedException RuntimeException
	* @expectedExceptionMessage No minifier available
	*/
	public function testNoMinifier()
	{
		$minifier = new FirstAvailable;
		$minifier->minify('');
	}

	/**
	* @testdox minify() throws an exception if no minifier success
	* @expectedException RuntimeException
	* @expectedExceptionMessage No minifier available
	*/
	public function testAllMinifiersFail()
	{
		$minifier1 = $this->getMockBuilder('s9e\\TextFormatter\\Configurator\\JavaScript\\Minifier')
		                  ->getMock();
		$minifier1->expects($this->once())
		          ->method('minify')
		          ->will($this->throwException(new Exception));

		$minifier2 = $this->getMockBuilder('s9e\\TextFormatter\\Configurator\\JavaScript\\Minifier')
		                  ->getMock();
		$minifier2->expects($this->once())
		          ->method('minify')
		          ->will($this->throwException(new Exception));

		$minifier = new FirstAvailable;
		$minifier->add($minifier1);
		$minifier->add($minifier2);

		$minifier->minify('');
	}
}