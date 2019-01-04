<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Keywords;

use s9e\TextFormatter\Configurator\Collections\NormalizedList;
use s9e\TextFormatter\Configurator\Helpers\RegexpBuilder;
use s9e\TextFormatter\Configurator\Items\Regexp;
use s9e\TextFormatter\Configurator\Traits\CollectionProxy;
use s9e\TextFormatter\Plugins\ConfiguratorBase;

/**
* @method mixed   add(string $key, mixed $value)
* @method array   asConfig()
* @method void    clear()
* @method bool    contains(mixed $value)
* @method integer count()
* @method mixed   current()
* @method void    delete(string $key)
* @method bool    exists(string $key)
* @method mixed   get(string $key)
* @method mixed   indexOf(mixed $value)
* @method integer|string key()
* @method mixed   next()
* @method string  normalizeKey(string $key)
* @method mixed   normalizeValue(mixed $value)
* @method bool    offsetExists(string|integer $offset)
* @method mixed   offsetGet(string|integer $offset)
* @method void    offsetSet(string|integer $offset, mixed $value)
* @method void    offsetUnset(string|integer $offset)
* @method string  onDuplicate(string|null $action)
* @method void    rewind()
* @method mixed   set(string $key, mixed $value)
* @method bool    valid()
*/
class Configurator extends ConfiguratorBase
{
	use CollectionProxy;

	/**
	* @var string Name of the attribute used by this plugin
	*/
	protected $attrName = 'value';

	/**
	* @var bool Whether keywords are case-sensitive
	*/
	public $caseSensitive = true;

	/**
	* @var \s9e\TextFormatter\Configurator\Collections\NormalizedCollection List of [keyword => value]
	*/
	protected $collection;

	/**
	* @var boolean Whether to capture only the first occurence of each keyword
	*/
	public $onlyFirst = false;

	/**
	* @var string Name of the tag used by this plugin
	*/
	protected $tagName = 'KEYWORD';

	/**
	* {@inheritdoc}
	*/
	protected function setUp()
	{
		$this->collection = new NormalizedList;

		$this->configurator->tags->add($this->tagName)->attributes->add($this->attrName);
	}

	/**
	* {@inheritdoc}
	*/
	public function asConfig()
	{
		if (!count($this->collection))
		{
			return;
		}

		$config = [
			'attrName' => $this->attrName,
			'tagName'  => $this->tagName
		];

		if (!empty($this->onlyFirst))
		{
			$config['onlyFirst'] = $this->onlyFirst;
		}

		// Sort keywords in order to keep keywords that start with the same characters together. We
		// also remove duplicates that would otherwise skew the length computation done below
		$keywords = array_unique(iterator_to_array($this->collection));
		sort($keywords);

		// Group keywords by chunks of ~30KB to remain below PCRE's limit
		$groups   = [];
		$groupKey = 0;
		$groupLen = 0;
		foreach ($keywords as $keyword)
		{
			// NOTE: the value 4 is a guesstimate for the cost of each alternation
			$keywordLen  = 4 + strlen($keyword);
			$groupLen   += $keywordLen;

			if ($groupLen > 30000)
			{
				$groupLen = $keywordLen;
				++$groupKey;
			}

			$groups[$groupKey][] = $keyword;
		}

		foreach ($groups as $keywords)
		{
			$regexp = RegexpBuilder::fromList(
				$keywords,
				['caseInsensitive' => !$this->caseSensitive]
			);

			$regexp = '/\\b' . $regexp . '\\b/S';

			if (!$this->caseSensitive)
			{
				$regexp .= 'i';
			}

			if (preg_match('/[^[:ascii:]]/', $regexp))
			{
				$regexp .= 'u';
			}

			$config['regexps'][] = new Regexp($regexp, true);
		}

		return $config;
	}
}