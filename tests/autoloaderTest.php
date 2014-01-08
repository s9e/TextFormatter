<?php

class autoloaderTest extends PHPUnit_Framework_TestCase
{
	public function autoload($className)
	{
		if (class_exists($className, false))
		{
			$this->markTestSkipped("$className already loaded");
		}

		$this->assertTrue(class_exists($className));
	}

	/**
	* @testdox Can load s9e\TextFormatter\Configurator
	* @runInSeparateProcess
	* @preserveGlobalState disabled
	* @group no-hhvm
	*/
	public function testConfigurator()
	{
		include __DIR__ . '/bootstrap.php';
		$this->autoload('s9e\\TextFormatter\\Configurator');
	}

	/**
	* @testdox Can load s9e\TextFormatter\Parser\Logger
	* @runInSeparateProcess
	* @preserveGlobalState disabled
	* @group no-hhvm
	*/
	public function testParserTag()
	{
		include __DIR__ . '/bootstrap.php';
		$this->autoload('s9e\\TextFormatter\\Parser\\Logger');
	}

	/**
	* @testdox Can load s9e\TextFormatter\Plugins\Emoticons\Parser
	* @runInSeparateProcess
	* @preserveGlobalState disabled
	* @group no-hhvm
	*/
	public function testEmoticonsParserTag()
	{
		include __DIR__ . '/bootstrap.php';
		$this->autoload('s9e\\TextFormatter\\Plugins\\Emoticons\\Parser');
	}

	/**
	* @testdox Does not attempt to load a class whose name contains dots
	*/
	public function testBadPath()
	{
		include_once __DIR__ . '/../src/s9e/TextFormatter/autoloader.php';
		class_exists('s9e\\TextFormatter\\..\\..\\..\\tests\\error');
	}
}