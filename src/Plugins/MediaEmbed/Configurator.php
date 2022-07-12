<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2022 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\MediaEmbed;

use InvalidArgumentException;
use RuntimeException;
use s9e\TextFormatter\Configurator\Helpers\FilterHelper;
use s9e\TextFormatter\Configurator\Items\Regexp;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\JavaScript\Dictionary;
use s9e\TextFormatter\Plugins\ConfiguratorBase;
use s9e\TextFormatter\Plugins\MediaEmbed\Configurator\Collections\CachedDefinitionCollection;
use s9e\TextFormatter\Plugins\MediaEmbed\Configurator\TemplateBuilder;

class Configurator extends ConfiguratorBase
{
	/**
	* @var array List of filters that are explicitly allowed in attribute definitions
	*/
	public $allowedFilters = ['htmlspecialchars_decode', 'stripslashes', 'urldecode'];

	/**
	* @var bool Whether to create the MEDIA BBCode
	*/
	protected $createMediaBBCode = true;

	/**
	* @var Configurator\Collections\SiteDefinitionCollection Default sites
	*/
	public $defaultSites;

	/**
	* {@inheritdoc}
	*/
	protected $quickMatch = '://';

	/**
	* {@inheritdoc}
	*/
	protected $regexp = '/\\bhttps?:\\/\\/[^["\'\\s]+/Si';

	/**
	* @var array Configured sites
	*/
	protected $sites = [];

	/**
	* @var string Name of the tag used to handle embeddable URLs
	*/
	protected $tagName = 'MEDIA';

	/**
	* @var TemplateBuilder
	*/
	protected $templateBuilder;

	/**
	* {@inheritdoc}
	*/
	protected function setUp()
	{
		$this->defaultSites    = new CachedDefinitionCollection;
		$this->templateBuilder = new TemplateBuilder;

		$this->configurator->registeredVars['MediaEmbed.hosts'] = new Dictionary;
		$this->configurator->registeredVars['MediaEmbed.sites'] = new Dictionary;

		// Create a MEDIA tag
		$this->createMediaTag();

		// Create a [MEDIA] BBCode if applicable
		if ($this->createMediaBBCode)
		{
			$this->configurator->BBCodes->set($this->tagName, ['contentAttributes' => ['url']]);
		}
	}

	/**
	* {@inheritdoc}
	*/
	public function asConfig()
	{
		if (empty($this->sites))
		{
			return;
		}

		return [
			'quickMatch' => $this->quickMatch,
			'regexp'     => $this->regexp,
			'tagName'    => $this->tagName
		];
	}

	/**
	* Add a media site
	*
	* @param  string $siteId     Site's ID
	* @param  array  $siteConfig Site's config
	* @return Tag                Tag created for this site
	*/
	public function add($siteId, array $siteConfig = null)
	{
		// Normalize or retrieve the site definition
		$siteId = $this->normalizeId($siteId);
		if (isset($siteConfig))
		{
			$siteConfig = $this->defaultSites->normalizeValue($siteConfig);
		}
		else
		{
			$siteConfig = $this->defaultSites->get($siteId);
		}
		$siteConfig['extract'] = $this->convertRegexps($siteConfig['extract']);
		$siteConfig['scrape']  = $this->convertScrapes($siteConfig['scrape']);

		// Check the safety of attribute filters
		$this->checkAttributeFilters($siteConfig['attributes']);

		// Create the tag for this site
		$tag = $this->addTag($siteId, $siteConfig);

		// Update the configurator's data
		$this->sites[$siteId] = $siteConfig;
		foreach ($siteConfig['host'] as $host)
		{
			$this->configurator->registeredVars['MediaEmbed.hosts'][$host] = $siteId;
		}
		$this->configurator->registeredVars['MediaEmbed.sites'][$siteId] = [$siteConfig['extract'], $siteConfig['scrape']];

		return $tag;
	}

	/**
	* Return the list of configured sites
	*
	* @return array Site's ID as keys, site's config as values
	*/
	public function getSites()
	{
		return $this->sites;
	}

	/**
	* Create and return a tag that handles given media site
	*
	* @param  string $siteId
	* @param  array  $siteConfig
	* @return Tag
	*/
	protected function addTag($siteId, array $siteConfig)
	{
		$tag = new Tag([
			'attributes' => $this->getAttributesConfig($siteConfig),
			'rules'      => [
				'allowChild' => 'URL',
				'autoClose'  => true,
				'denyChild'  => [$siteId, $this->tagName]
			],
			'template'   => $this->templateBuilder->build($siteId, $siteConfig)
		]);

		$this->configurator->templateNormalizer->normalizeTag($tag);
		$this->configurator->templateChecker->checkTag($tag);
		$this->configurator->tags->add($siteId, $tag);

		return $tag;
	}

	/**
	* Check the safety of given attributes
	*
	* @param  array $attributes
	* @return void
	*/
	protected function checkAttributeFilters(array $attributes)
	{
		foreach ($attributes as $attrConfig)
		{
			if (empty($attrConfig['filterChain']))
			{
				continue;
			}
			foreach ($attrConfig['filterChain'] as $filter)
			{
				if (!FilterHelper::isAllowed($filter, $this->allowedFilters))
				{
					throw new RuntimeException("Filter '$filter' is not allowed in media sites");
				}
			}
		}
	}

	/**
	* Convert given regexp to a [regexp, map] pair
	*
	* @param  string $regexp Original regexp
	* @return array          [regexp, [list of captures' names]]
	*/
	protected function convertRegexp($regexp)
	{
		$regexp = new Regexp($regexp);

		return [$regexp, $regexp->getCaptureNames()];
	}

	/**
	* Convert a list of regexps
	*
	* @param  string[] $regexps Original list
	* @return array[]           Converted list
	*/
	protected function convertRegexps(array $regexps)
	{
		return array_map([$this, 'convertRegexp'], $regexps);
	}

	/**
	* Convert all regexps in a scraping config
	*
	* @param  array $config Original config
	* @return array         Converted config
	*/
	protected function convertScrapeConfig(array $config)
	{
		$config['extract'] = $this->convertRegexps($config['extract']);
		$config['match']   = $this->convertRegexps($config['match']);

		return $config;
	}

	/**
	* Convert all regexps in a list of scraping configs
	*
	* @param  array[] $scrapes Original config
	* @return array[]          Converted config
	*/
	protected function convertScrapes(array $scrapes)
	{
		return array_map([$this, 'convertScrapeConfig'], $scrapes);
	}

	/**
	* Create the default MEDIA tag
	*
	* @return void
	*/
	protected function createMediaTag()
	{
		$tag = $this->configurator->tags->add($this->tagName);

		// This tag should not need to be closed and should not contain itself
		$tag->rules->autoClose();
		$tag->rules->denyChild($this->tagName);

		// Empty this tag's filter chain and add our tag filter
		$tag->filterChain->clear();
		$tag->filterChain
		    ->append(__NAMESPACE__ . '\\Parser::filterTag')
		    ->resetParameters()
		    ->addParameterByName('tag')
		    ->addParameterByName('parser')
		    ->addParameterByName('MediaEmbed.hosts')
		    ->addParameterByName('MediaEmbed.sites')
		    ->addParameterByName('cacheDir')
		    ->setJS(file_get_contents(__DIR__ . '/Parser/tagFilter.js'));
	}

	/**
	* Return the list of named captures from a list of [regexp, map] pairs
	*
	* @param  array[] $regexps List of [regexp, map] pairs
	* @return string[]
	*/
	protected function getAttributeNamesFromRegexps(array $regexps)
	{
		$attrNames = [];
		foreach ($regexps as list($regexp, $map))
		{
			$attrNames += array_flip(array_filter($map));
		}

		return $attrNames;
	}

	/**
	* Get the attributes config for given site config
	*
	* @param  array $siteConfig Site's config
	* @return array             Map of [attrName => attrConfig]
	*/
	protected function getAttributesConfig(array $siteConfig)
	{
		$attrNames = $this->getAttributeNamesFromRegexps($siteConfig['extract']);
		foreach ($siteConfig['scrape'] as $scrapeConfig)
		{
			$attrNames += $this->getAttributeNamesFromRegexps($scrapeConfig['extract']);
		}

		$attributes = $siteConfig['attributes'] + array_fill_keys(array_keys($attrNames), []);
		foreach ($attributes as &$attrConfig)
		{
			$attrConfig += ['required' => false];
		}
		unset($attrConfig);

		return $attributes;
	}

	/**
	* Validate and normalize a site ID
	*
	* @param  string $siteId
	* @return string
	*/
	protected function normalizeId($siteId)
	{
		$siteId = strtolower($siteId);

		if (!preg_match('(^[a-z0-9]+$)', $siteId))
		{
			throw new InvalidArgumentException('Invalid site ID');
		}

		return $siteId;
	}
}