<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\MediaEmbed;
use InvalidArgumentException;
use RuntimeException;
use s9e\TextFormatter\Configurator\Items\Regexp;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\JavaScript\Dictionary;
use s9e\TextFormatter\Plugins\ConfiguratorBase;
use s9e\TextFormatter\Plugins\MediaEmbed\Configurator\Collections\CachedDefinitionCollection;
use s9e\TextFormatter\Plugins\MediaEmbed\Configurator\TemplateBuilder;
class Configurator extends ConfiguratorBase
{
	public $allowedFilters = ['stripslashes', 'urldecode'];
	protected $createMediaBBCode = \true;
	public $defaultSites;
	protected $quickMatch = '://';
	protected $regexp = '/\\bhttps?:\\/\\/[^["\'\\s]+/Si';
	protected $sites = [];
	protected $tagName = 'MEDIA';
	protected $templateBuilder;
	protected function setUp()
	{
		$this->defaultSites    = new CachedDefinitionCollection;
		$this->templateBuilder = new TemplateBuilder;
		$this->configurator->registeredVars['MediaEmbed.hosts'] = new Dictionary;
		$this->configurator->registeredVars['MediaEmbed.sites'] = new Dictionary;
		$this->createMediaTag();
		if ($this->createMediaBBCode)
			$this->configurator->BBCodes->set($this->tagName, ['contentAttributes' => ['url']]);
	}
	public function asConfig()
	{
		if (empty($this->sites))
			return;
		return [
			'quickMatch' => $this->quickMatch,
			'regexp'     => $this->regexp,
			'tagName'    => $this->tagName
		];
	}
	public function add($siteId, array $siteConfig = \null)
	{
		$siteId = $this->normalizeId($siteId);
		if (isset($siteConfig))
			$siteConfig = $this->defaultSites->normalizeValue($siteConfig);
		else
			$siteConfig = $this->defaultSites->get($siteId);
		$siteConfig['extract'] = $this->convertRegexps($siteConfig['extract']);
		$siteConfig['scrape']  = $this->convertScrapes($siteConfig['scrape']);
		$this->checkAttributeFilters($siteConfig['attributes']);
		$tag = $this->addTag($siteId, $siteConfig);
		$this->sites[$siteId] = $siteConfig;
		foreach ($siteConfig['host'] as $host)
			$this->configurator->registeredVars['MediaEmbed.hosts'][$host] = $siteId;
		$this->configurator->registeredVars['MediaEmbed.sites'][$siteId] = [$siteConfig['extract'], $siteConfig['scrape']];
		return $tag;
	}
	public function getSites()
	{
		return $this->sites;
	}
	protected function addTag($siteId, array $siteConfig)
	{
		$tag = new Tag([
			'attributes' => $this->getAttributesConfig($siteConfig),
			'rules'      => [
				'allowChild' => 'URL',
				'autoClose'  => \true,
				'denyChild'  => [$siteId, $this->tagName]
			],
			'template'   => $this->templateBuilder->build($siteId, $siteConfig)
		]);
		$this->configurator->templateNormalizer->normalizeTag($tag);
		$this->configurator->templateChecker->checkTag($tag);
		$this->configurator->tags->add($siteId, $tag);
		return $tag;
	}
	protected function checkAttributeFilters(array $attributes)
	{
		foreach ($attributes as $attrConfig)
		{
			if (empty($attrConfig['filterChain']))
				continue;
			foreach ($attrConfig['filterChain'] as $filter)
				if (\substr($filter, 0, 1) !== '#' && !\in_array($filter, $this->allowedFilters, \true))
					throw new RuntimeException("Filter '$filter' is not allowed in media sites");
		}
	}
	protected function convertRegexp($regexp)
	{
		$regexp = new Regexp($regexp);
		return [$regexp, $regexp->getCaptureNames()];
	}
	protected function convertRegexps(array $regexps)
	{
		return \array_map([$this, 'convertRegexp'], $regexps);
	}
	protected function convertScrapeConfig(array $config)
	{
		$config['extract'] = $this->convertRegexps($config['extract']);
		$config['match']   = $this->convertRegexps($config['match']);
		return $config;
	}
	protected function convertScrapes(array $scrapes)
	{
		return \array_map([$this, 'convertScrapeConfig'], $scrapes);
	}
	protected function createMediaTag()
	{
		$tag = $this->configurator->tags->add($this->tagName);
		$tag->rules->autoClose();
		$tag->rules->denyChild($this->tagName);
		$tag->filterChain->clear();
		$tag->filterChain
		    ->append(__NAMESPACE__ . '\\Parser::filterTag')
		    ->resetParameters()
		    ->addParameterByName('tag')
		    ->addParameterByName('parser')
		    ->addParameterByName('MediaEmbed.hosts')
		    ->addParameterByName('MediaEmbed.sites')
		    ->addParameterByName('cacheDir')
		    ->setJS(\file_get_contents(__DIR__ . '/Parser/tagFilter.js'));
	}
	protected function getAttributeNamesFromRegexps(array $regexps)
	{
		$attrNames = [];
		foreach ($regexps as $_53d26d37)
		{
			list($regexp, $map) = $_53d26d37;
			$attrNames += \array_flip(\array_filter($map));
		}
		return $attrNames;
	}
	protected function getAttributesConfig(array $siteConfig)
	{
		$attrNames = $this->getAttributeNamesFromRegexps($siteConfig['extract']);
		foreach ($siteConfig['scrape'] as $scrapeConfig)
			$attrNames += $this->getAttributeNamesFromRegexps($scrapeConfig['extract']);
		$attributes = $siteConfig['attributes'] + \array_fill_keys(\array_keys($attrNames), []);
		foreach ($attributes as &$attrConfig)
			$attrConfig += ['required' => \false];
		unset($attrConfig);
		return $attributes;
	}
	protected function normalizeId($siteId)
	{
		$siteId = \strtolower($siteId);
		if (!\preg_match('(^[a-z0-9]+$)', $siteId))
			throw new InvalidArgumentException('Invalid site ID');
		return $siteId;
	}
}