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
	* @group runs-in-separate-process
	*/
	public function testConfigurator()
	{
		include_once __DIR__ . '/bootstrap.php';
		$this->autoload('s9e\\TextFormatter\\Configurator');
	}

	/**
	* @testdox Can load s9e\TextFormatter\Parser\Logger
	* @runInSeparateProcess
	* @preserveGlobalState disabled
	* @group runs-in-separate-process
	*/
	public function testParserTag()
	{
		include_once __DIR__ . '/bootstrap.php';
		$this->autoload('s9e\\TextFormatter\\Parser\\Logger');
	}

	/**
	* @testdox Can load s9e\TextFormatter\Plugins\Emoticons\Parser
	* @runInSeparateProcess
	* @preserveGlobalState disabled
	* @group runs-in-separate-process
	*/
	public function testEmoticonsParserTag()
	{
		include_once __DIR__ . '/bootstrap.php';
		$this->autoload('s9e\\TextFormatter\\Plugins\\Emoticons\\Parser');
	}

	/**
	* @testdox Does not attempt to load a class whose name contains dots
	*/
	public function testBadPath()
	{
		include_once __DIR__ . '/../src/autoloader.php';
		class_exists('s9e\\TextFormatter\\..\\..\\..\\tests\\error');
	}
}