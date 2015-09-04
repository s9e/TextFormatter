<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\MediaEmbed;
use InvalidArgumentException;
use RuntimeException;
use s9e\TextFormatter\Configurator\Helpers\RegexpBuilder;
use s9e\TextFormatter\Configurator\Items\Attribute;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\RegexpFilter;
use s9e\TextFormatter\Configurator\Items\AttributePreprocessor;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Plugins\ConfiguratorBase;
use s9e\TextFormatter\Plugins\MediaEmbed\Configurator\CachedSiteDefinitionProvider;
use s9e\TextFormatter\Plugins\MediaEmbed\Configurator\MediaSiteCollection;
use s9e\TextFormatter\Plugins\MediaEmbed\Configurator\TemplateGenerators\Flash;
use s9e\TextFormatter\Plugins\MediaEmbed\Configurator\TemplateGenerators\Iframe;
class Configurator extends ConfiguratorBase
{
	public $allowedFilters = [
		'hexdec',
		'urldecode'
	];
	protected $appendTemplate = '';
	protected $captureURLs = \true;
	protected $collection;
	protected $createMediaBBCode = \true;
	public $createIndividualBBCodes = \false;
	public $defaultSites;
	protected $responsiveEmbeds = \false;
	protected $templateGenerators = [];
	protected function setUp()
	{
		$this->collection = new MediaSiteCollection;
		$this->configurator->registeredVars['mediasites'] = $this->collection;
		$tag = $this->configurator->tags->add('MEDIA');
		$tag->rules->autoClose();
		$tag->rules->ignoreTags();
		$tag->filterChain->clear();
		$tag->filterChain
		    ->append([__NAMESPACE__ . '\\Parser', 'filterTag'])
		    ->addParameterByName('parser')
		    ->addParameterByName('mediasites')
		    ->setJS(\file_get_contents(__DIR__ . '/Parser/tagFilter.js'));
		if ($this->createMediaBBCode)
			$this->configurator->BBCodes->set('MEDIA', ['contentAttributes' => ['url']]);
		if (!isset($this->defaultSites))
			$this->defaultSites = new CachedSiteDefinitionProvider;
		$this->templateGenerators['flash']  = new Flash;
		$this->templateGenerators['iframe'] = new Iframe;
	}
	public function asConfig()
	{
		if (!$this->captureURLs)
			return;
		$char = "\xEE\x80\x80";
		$hasSchemes = \false;
		$patterns   = [];
		foreach ($this->collection as $site)
		{
			if (isset($site['host']))
				foreach ((array) $site['host'] as $host)
					$patterns[] = $char . $host . '/';
			if (isset($site['scheme']))
				foreach ((array) $site['scheme'] as $scheme)
				{
					$hasSchemes = \true;
					$patterns[] = $scheme . ':';
				}
		}
		if (empty($patterns))
			return;
		$regexp = RegexpBuilder::fromList(
			$patterns,
			[
				'delimiter'    => '#',
				'specialChars' => [$char => 'https?://(?:[-.\\w]+\\.)?']
			]
		);
		$regexp = \preg_replace('(^\\(\\?:)', '(?>', $regexp);
		$regexp = '#\\b' . $regexp . '[^["\'\\s]+(?!\\S)#S';
		return [
			'quickMatch' => ($hasSchemes) ? ':' : '://',
			'regexp'     => $regexp
		];
	}
	public function add($siteId, array $siteConfig = \null)
	{
		$siteId = $this->normalizeId($siteId);
		if (!isset($siteConfig))
			$siteConfig = $this->defaultSites->get($siteId);
		$this->collection[$siteId] = $siteConfig;
		$tag = new Tag;
		$tag->rules->autoClose();
		$tag->rules->ignoreTags();
		$attributes = [
			'url' => ['type' => 'url']
		];
		if (isset($siteConfig['scrape']))
			$attributes += $this->addScrapes($tag, $siteConfig['scrape']);
		if (isset($siteConfig['extract']))
			foreach ((array) $siteConfig['extract'] as $regexp)
			{
				$attrRegexps = $tag->attributePreprocessors->add('url', $regexp)->getAttributes();
				foreach ($attrRegexps as $attrName => $attrRegexp)
					$attributes[$attrName]['regexp'] = $attrRegexp;
			}
		if (isset($siteConfig['attributes']))
			foreach ($siteConfig['attributes'] as $attrName => $attrConfig)
				foreach ($attrConfig as $configName => $configValue)
					$attributes[$attrName][$configName] = $configValue;
		$hasRequiredAttribute = \false;
		foreach ($attributes as $attrName => $attrConfig)
		{
			$attribute = $tag->attributes->add($attrName);
			if (isset($attrConfig['preFilter']))
				$this->appendFilter($attribute, $attrConfig['preFilter']);
			if (isset($attrConfig['type']))
			{
				$filter = $this->configurator->attributeFilters['#' . $attrConfig['type']];
				$attribute->filterChain->append($filter);
			}
			elseif (isset($attrConfig['regexp']))
				$attribute->filterChain->append(new RegexpFilter($attrConfig['regexp']));
			if (isset($attrConfig['required']))
				$attribute->required = $attrConfig['required'];
			else
				$attribute->required = ($attrName === 'id');
			if (isset($attrConfig['postFilter']))
				$this->appendFilter($attribute, $attrConfig['postFilter']);
			if (isset($attrConfig['defaultValue']))
				$attribute->defaultValue = $attrConfig['defaultValue'];
			$hasRequiredAttribute |= $attribute->required;
		}
		if (isset($attributes['id']['regexp']))
		{
			$attrRegexp = \preg_replace('(\\^(.*)\\$)s', "^(?'id'$1)$", $attributes['id']['regexp']);
			$tag->attributePreprocessors->add('url', $attrRegexp);
		}
		if (!$hasRequiredAttribute)
			$tag->filterChain
				->append([__NAMESPACE__ . '\\Parser', 'hasNonDefaultAttribute'])
				->setJS(\file_get_contents(__DIR__ . '/Parser/hasNonDefaultAttribute.js'));
		$tag->template = $this->getTemplate($siteConfig);
		$this->configurator->templateNormalizer->normalizeTag($tag);
		$this->configurator->templateChecker->checkTag($tag);
		$this->configurator->tags->add($siteId, $tag);
		if ($this->createIndividualBBCodes)
			$this->configurator->BBCodes->add(
				$siteId,
				[
					'defaultAttribute'  => 'url',
					'contentAttributes' => ['url']
				]
			);
		return $tag;
	}
	public function appendTemplate($template = '')
	{
		$this->appendTemplate = $this->configurator->templateNormalizer->normalizeTemplate($template);
	}
	public function disableResponsiveEmbeds()
	{
		$this->responsiveEmbeds = \false;
	}
	public function enableResponsiveEmbeds()
	{
		$this->responsiveEmbeds = \true;
	}
	protected function addScrapes(Tag $tag, array $scrapes)
	{
		if (!isset($scrapes[0]))
			$scrapes = [$scrapes];
		$attributes   = [];
		$scrapeConfig = [];
		foreach ($scrapes as $scrape)
		{
			$attrNames = [];
			foreach ((array) $scrape['extract'] as $extractRegexp)
			{
				$attributePreprocessor = new AttributePreprocessor($extractRegexp);
				foreach ($attributePreprocessor->getAttributes() as $attrName => $attrRegexp)
				{
					$attrNames[] = $attrName;
					$attributes[$attrName]['regexp'] = $attrRegexp;
				}
			}
			$attrNames = \array_unique($attrNames);
			\sort($attrNames);
			if (!isset($scrape['match']))
				$scrape['match'] = '//';
			$entry = [$scrape['match'], $scrape['extract'], $attrNames];
			if (isset($scrape['url']))
				$entry[] = $scrape['url'];
			$scrapeConfig[] = $entry;
		}
		$tag->filterChain->insert(1, __NAMESPACE__ . '\\Parser::scrape')
		                 ->addParameterByName('scrapeConfig')
		                 ->addParameterByName('cacheDir')
		                 ->setVar('scrapeConfig', $scrapeConfig)
		                 ->setJS('returnTrue');
		return $attributes;
	}
	protected function appendFilter(Attribute $attribute, $filter)
	{
		if (!\in_array($filter, $this->allowedFilters, \true))
			throw new RuntimeException("Filter '" . $filter . "' is not allowed");
		$attribute->filterChain->append($this->configurator->attributeFilters[$filter]);
	}
	protected function getTemplate(array $siteConfig)
	{
		foreach ($this->templateGenerators as $type => $generator)
			if (isset($siteConfig[$type]))
			{
				$siteConfig[$type] += ['responsive' => $this->responsiveEmbeds];
				return $generator->getTemplate($siteConfig[$type]) . $this->appendTemplate;
			}
		return '';
	}
	protected function normalizeId($siteId)
	{
		$siteId = \strtolower($siteId);
		if (!\preg_match('(^[a-z0-9]+$)', $siteId))
			throw new InvalidArgumentException('Invalid site ID');
		return $siteId;
	}
}