<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2020 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\MediaEmbed\Configurator\Collections;

use InvalidArgumentException;
use RuntimeException;
use s9e\TextFormatter\Configurator\Collections\NormalizedCollection;

class SiteDefinitionCollection extends NormalizedCollection
{
	/**
	* {@inheritdoc}
	*/
	protected $onDuplicateAction = 'replace';

	/**
	* {@inheritdoc}
	*/
	protected function getAlreadyExistsException($key)
	{
		return new RuntimeException("Media site '" . $key . "' already exists");
	}

	/**
	* {@inheritdoc}
	*/
	protected function getNotExistException($key)
	{
		return new RuntimeException("Media site '" . $key . "' does not exist");
	}

	/**
	* Validate and normalize a site ID
	*
	* @param  string $siteId
	* @return string
	*/
	public function normalizeKey($siteId)
	{
		$siteId = strtolower($siteId);
		if (!preg_match('(^[a-z0-9]+$)', $siteId))
		{
			throw new InvalidArgumentException('Invalid site ID');
		}

		return $siteId;
	}

	/**
	* {@inheritdoc}
	*/
	public function normalizeValue($siteConfig)
	{
		if (!is_array($siteConfig))
		{
			throw new InvalidArgumentException('Invalid site definition type');
		}
		if (!isset($siteConfig['host']))
		{
			throw new InvalidArgumentException('Missing host from site definition');
		}

		$siteConfig           += ['attributes' => [], 'extract' => [], 'scrape' => []];
		$siteConfig['extract'] = $this->normalizeRegexp($siteConfig['extract']);
		$siteConfig['host']    = array_map('strtolower', (array) $siteConfig['host']);
		$siteConfig['scrape']  = $this->normalizeScrape($siteConfig['scrape']);

		foreach ($siteConfig['attributes'] as &$attrConfig)
		{
			if (isset($attrConfig['filterChain']))
			{
				$attrConfig['filterChain'] = (array) $attrConfig['filterChain'];
			}
		}
		unset($attrConfig);

		return $siteConfig;
	}

	/**
	* Normalize a regexp / indexed array of regexps
	*
	* @param  array|string
	* @return array
	*/
	protected function normalizeRegexp($value)
	{
		return (array) $value;
	}

	/**
	* Normalize the "scrape" value
	*
	* @param  array
	* @return array
	*/
	protected function normalizeScrape($value)
	{
		if (!empty($value) && !isset($value[0]))
		{
			$value = [$value];
		}
		foreach ($value as &$scrape)
		{
			$scrape           += ['extract' => [], 'match' => '//'];
			$scrape['extract'] = $this->normalizeRegexp($scrape['extract']);
			$scrape['match']   = $this->normalizeRegexp($scrape['match']);
		}
		unset($scrape);

		return $value;
	}
}