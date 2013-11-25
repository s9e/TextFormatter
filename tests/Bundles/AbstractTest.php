<?php

namespace s9e\TextFormatter\Tests\Bundles;

use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Bundle
*/
abstract class AbstractTest extends Test
{
	protected static function getBundleName()
	{
		$className = get_called_class();

		return substr($className, 1 + strrpos($className, '\\'), -4);
	}

	protected static function getClassName()
	{
		$bundleName = static::getBundleName();

		return 's9e\\TextFormatter\\Bundles\\' . $bundleName;
	}

	public static function setUpBeforeClass()
	{
		$className = static::getClassName();

		// Start with a clean parser and renderer
		$className::reset();
	}

	/**
	* @testdox Rendering tests
	* @dataProvider getRenderingTests
	*/
	public function testRender($text, $html, $params = [])
	{
		$className = static::getClassName();

		if (!isset($className::$parser))
		{
			$className::parse('');
		}

		$className::$parser->registeredVars['cacheDir'] = __DIR__ . '/../.cache';

		$this->assertSame($html, $className::render($className::parse($text), $params));

		// Reset the renderer if params were set
		if ($params)
		{
			$className::$renderer = null;
		}
	}

	public function getRenderingTests()
	{
		$bundleName = static::getBundleName();

		$tests = [];
		foreach (glob(__DIR__ . '/data/' . $bundleName . '/*.txt') as $filepath)
		{
			$test = [
				file_get_contents($filepath),
				file_get_contents(substr($filepath, 0, -3) . 'html')
			];

			if (file_exists(substr($filepath, 0, -3) . 'json'))
			{
				if (!extension_loaded('json'))
				{
					continue;
				}

				$test[] = json_decode(
					file_get_contents(substr($filepath, 0, -3) . 'json'),
					true
				);
			}

			$tests[] = $test;
		}

		return $tests;
	}
}