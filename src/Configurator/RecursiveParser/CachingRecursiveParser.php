<?php declare(strict_types=1);

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2020 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RecursiveParser;

use s9e\TextFormatter\Configurator\RecursiveParser;

class CachingRecursiveParser extends RecursiveParser
{
	/**
	* @var array
	*/
	protected $cache;

	/**
	* {@inheritdoc}
	*/
	public function parse(string $str, string $restrict = '')
	{
		if (!isset($this->cache[$restrict][$str]))
		{
			$this->cache[$restrict][$str] = parent::parse($str, $restrict);
		}

		return $this->cache[$restrict][$str];
	}

	/**
	* {@inheritdoc}
	*/
	public function setMatchers(array $matchers): void
	{
		$this->cache = [];
		parent::setMatchers($matchers);
	}
}