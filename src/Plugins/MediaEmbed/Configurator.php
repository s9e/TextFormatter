<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2017 The s9e Authors
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
use s9e\TextFormatter\Plugins\MediaEmbed\Configurator\Collections\CachedDefinitionCollection;
use s9e\TextFormatter\Plugins\MediaEmbed\Configurator\Collections\SiteCollection;
use s9e\TextFormatter\Plugins\MediaEmbed\Configurator\TemplateBuilder;

class Configurator extends ConfiguratorBase
{
	/**
	* @var array List of filters that are explicitly allowed in attribute definitions
	*/
	public $allowedFilters = [
		'hexdec',
		'stripslashes',
		'urldecode'
	];

	/**
	* @var string String to be appended to the templates used to render media sites
	*/
	protected $appendTemplate = '';

	/**
	* @var bool Whether to replace unformatted URLs in text with embedded content
	*/
	protected $captureURLs = true;

	/**
	* @var SiteCollection Site collection
	*/
	protected $collection;

	/**
	* @var bool Whether to create the MEDIA BBCode
	*/
	protected $createMediaBBCode = true;

	/**
	* @var bool Whether to create a BBCode for each site
	*/
	public $createIndividualBBCodes = false;

	/**
	* @var Configurator\Collections\SiteDefinitionCollection Default sites
	*/
	public $defaultSites;

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
		// Create a collection to store the configured sites
		$this->collection = new SiteCollection;

		// Register the collection as a variable to be used during parsing
		$this->configurator->registeredVars['mediasites'] = $this->collection;

		// Create a MEDIA tag
		$tag = $this->configurator->tags->add($this->tagName);

		// This tag should not need to be closed and should not contain itself
		$tag->rules->autoClose();
		$tag->rules->denyChild($this->tagName);

		// Empty this tag's filter chain and add our tag filter
		$tag->filterChain->clear();
		$tag->filterChain
		    ->append([__NAMESPACE__ . '\\Parser', 'filterTag'])
		    ->addParameterByName('parser')
		    ->addParameterByName('mediasites')
		    ->setJS(file_get_contents(__DIR__ . '/Parser/tagFilter.js'));

		// Create a [MEDIA] BBCode if applicable
		if ($this->createMediaBBCode)
		{
			$this->configurator->BBCodes->set(
				$this->tagName,
				[
					'contentAttributes' => ['url'],
					'defaultAttribute'  => 'site'
				]
			);
		}

		if (!isset($this->defaultSites))
		{
			$this->defaultSites = new CachedDefinitionCollection;
		}

		$this->templateBuilder = new TemplateBuilder;
	}

	/**
	* {@inheritdoc}
	*/
	public function asConfig()
	{
		if (!$this->captureURLs || !count($this->collection))
		{
			return;
		}

		$regexp  = 'https?:\\/\\/';
		$schemes = $this->getSchemes();
		if (!empty($schemes))
		{
			$regexp = '(?>' . RegexpBuilder::fromList($schemes) . ':|' . $regexp . ')';
		}

		return [
			'quickMatch' => (empty($schemes)) ? '://' : ':',
			'regexp'     => '/\\b' . $regexp . '[^["\'\\s]+/Si',
			'tagName'    => $this->tagName
		];
	}

	//==========================================================================
	// Public API
	//==========================================================================

	/**
	* Add a media site
	*
	* @param  string $siteId     Site's ID
	* @param  array  $siteConfig Site's config
	* @return Tag                Tag created for this site
	*/
	public function add($siteId, array $siteConfig = null)
	{
		// Normalize the site ID
		$siteId = $this->normalizeId($siteId);

		// If there's no value, look into the default site definitions
		if (!isset($siteConfig))
		{
			$siteConfig = $this->defaultSites->get($siteId);
		}

		// Add this site to the list
		$this->collection[$siteId] = $siteConfig;

		// Create the tag for this site
		$tag = new Tag;

		// This tag should not need to be closed and should not contain itself or the MEDIA tag.
		// We allow URL as a child to be used as fallback
		$tag->rules->allowChild('URL');
		$tag->rules->autoClose();
		$tag->rules->denyChild($siteId);
		$tag->rules->denyChild($this->tagName);

		// Store attributes' configuration, starting with a default "url" attribute to store the
		// original URL if applicable
		$attributes = [
			'url' => ['type' => 'url']
		];

		// Process the "scrape" directives
		if (isset($siteConfig['scrape']))
		{
			$attributes += $this->addScrapes($tag, $siteConfig['scrape']);
		}

		// Add each "extract" as an attribute preprocessor
		if (isset($siteConfig['extract']))
		{
			foreach ((array) $siteConfig['extract'] as $regexp)
			{
				// Get the attributes filled by this regexp
				$attrRegexps = $tag->attributePreprocessors->add('url', $regexp)->getAttributes();

				// For each named subpattern in the regexp, ensure that an attribute exists and
				// create it otherwise, using the subpattern as regexp filter
				foreach ($attrRegexps as $attrName => $attrRegexp)
				{
					$attributes[$attrName]['regexp'] = $attrRegexp;
				}
			}
		}

		// Overwrite attribute declarations
		if (isset($siteConfig['attributes']))
		{
			foreach ($siteConfig['attributes'] as $attrName => $attrConfig)
			{
				foreach ($attrConfig as $configName => $configValue)
				{
					$attributes[$attrName][$configName] = $configValue;
				}
			}
		}

		// Create the attributes
		$hasRequiredAttribute = false;
		foreach ($attributes as $attrName => $attrConfig)
		{
			$attribute = $tag->attributes->add($attrName);

			if (isset($attrConfig['preFilter']))
			{
				$this->appendFilter($attribute, $attrConfig['preFilter']);
			}

			// Add a filter depending on the attribute's type or regexp
			if (isset($attrConfig['type']))
			{
				// If "type" is "url", get the "#url" filter
				$filter = $this->configurator->attributeFilters['#' . $attrConfig['type']];
				$attribute->filterChain->append($filter);
			}
			elseif (isset($attrConfig['regexp']))
			{
				$attribute->filterChain->append(new RegexpFilter($attrConfig['regexp']));
			}

			if (isset($attrConfig['required']))
			{
				$attribute->required = $attrConfig['required'];
			}
			else
			{
				// Non-id attributes are marked as optional
				$attribute->required = ($attrName === 'id');
			}

			if (isset($attrConfig['postFilter']))
			{
				$this->appendFilter($attribute, $attrConfig['postFilter']);
			}

			if (isset($attrConfig['defaultValue']))
			{
				$attribute->defaultValue = $attrConfig['defaultValue'];
			}

			$hasRequiredAttribute |= $attribute->required;
		}

		// If there is an attribute named "id" we'll append its regexp to the list of attribute
		// preprocessors in order to support both forms [site]<url>[/site] and [site]<id>[/site]
		if (isset($attributes['id']['regexp']))
		{
			// Add a named capture around the whole match
			$attrRegexp = preg_replace('(\\^(.*)\\$)s', "^(?'id'$1)$", $attributes['id']['regexp']);

			$tag->attributePreprocessors->add('url', $attrRegexp);
		}

		// If the tag definition does not have a required attribute, we use a filter to invalidate
		// the tag at parsing time if it does not have a non-default attribute. In other words, if
		// no attribute value is extracted, the tag is invalidated
		if (!$hasRequiredAttribute)
		{
			$tag->filterChain
				->append([__NAMESPACE__ . '\\Parser', 'hasNonDefaultAttribute'])
				->setJS(file_get_contents(__DIR__ . '/Parser/hasNonDefaultAttribute.js'));
		}

		// Create a template for this media site based on the preferred rendering method
		$tag->template = $this->templateBuilder->build($siteId, $siteConfig) . $this->appendTemplate;

		// Normalize the tag's templates
		$this->configurator->templateNormalizer->normalizeTag($tag);

		// Check the tag's safety
		$this->configurator->templateChecker->checkTag($tag);

		// Now add the tag to the list
		$this->configurator->tags->add($siteId, $tag);

		// Create a BBCode for this site if applicable
		if ($this->createIndividualBBCodes)
		{
			$this->configurator->BBCodes->add(
				$siteId,
				[
					'defaultAttribute'  => 'url',
					'contentAttributes' => ['url']
				]
			);
		}

		return $tag;
	}

	/**
	* Set a string to be appended to the templates used to render media sites
	*
	* @param  string $template
	* @return void
	*/
	public function appendTemplate($template = '')
	{
		$this->appendTemplate = $this->configurator->templateNormalizer->normalizeTemplate($template);
	}

	//==========================================================================
	// Internal methods
	//==========================================================================

	/**
	* Add the defined scrapes to given tag
	*
	* @param  array $scrapes Scraping definitions
	* @return array          Attributes created from scraped data
	*/
	protected function addScrapes(Tag $tag, array $scrapes)
	{
		// Ensure that the array is multidimensional
		if (!isset($scrapes[0]))
		{
			$scrapes = [$scrapes];
		}

		$attributes   = [];
		$scrapeConfig = [];
		foreach ($scrapes as $scrape)
		{
			// Collect the names of the attributes filled by this scrape. At runtime, we will
			// not scrape the content of the link if all of the attributes already have a value
			$attrNames = [];
			foreach ((array) $scrape['extract'] as $extractRegexp)
			{
				// Use an attribute preprocessor so we can reuse its routines
				$attributePreprocessor = new AttributePreprocessor($extractRegexp);

				foreach ($attributePreprocessor->getAttributes() as $attrName => $attrRegexp)
				{
					$attrNames[] = $attrName;
					$attributes[$attrName]['regexp'] = $attrRegexp;
				}
			}

			// Deduplicate and sort the attribute names so that they look tidy
			$attrNames = array_unique($attrNames);
			sort($attrNames);

			// Prepare the scrape config and add the URL if applicable
			if (!isset($scrape['match']))
			{
				// No "match" regexp means that all URLs should be scraped. We do need an entry
				// so we use a regexp that matches anything
				$scrape['match'] = '//';
			}
			$entry = [$scrape['match'], $scrape['extract'], $attrNames];
			if (isset($scrape['url']))
			{
				$entry[] = $scrape['url'];
			}

			// Add this scrape to the config
			$scrapeConfig[] = $entry;
		}

		// Add the scrape filter to this tag, execute it right before attributes are filtered,
		// which should be after attribute preprocessors are run. The offset is hardcoded here
		// for convenience (and because we know the filterChain is in its default state) and
		// since scraping is impossible in JavaScript without a PHP proxy, we just make it
		// return true in order to keep the tag valid
		$tag->filterChain->insert(1, __NAMESPACE__ . '\\Parser::scrape')
		                 ->addParameterByName('scrapeConfig')
		                 ->addParameterByName('cacheDir')
		                 ->setVar('scrapeConfig', $scrapeConfig)
		                 ->setJS('returnTrue');

		return $attributes;
	}

	/**
	* Append a filter to an attribute's filterChain
	*
	* @param  Attribute $attribute Target attribute
	* @param  string    $filter    Filter's name
	* @return void
	*/
	protected function appendFilter(Attribute $attribute, $filter)
	{
		if (!in_array($filter, $this->allowedFilters, true))
		{
			throw new RuntimeException("Filter '" . $filter . "' is not allowed");
		}

		$attribute->filterChain->append($this->configurator->attributeFilters[$filter]);
	}

	/**
	* Return the list of custom schemes supported via media sites
	*
	* @return string[]
	*/
	protected function getSchemes()
	{
		$schemes = [];
		foreach ($this->collection as $site)
		{
			if (isset($site['scheme']))
			{
				foreach ((array) $site['scheme'] as $scheme)
				{
					$schemes[] = $scheme;
				}
			}
		}

		return $schemes;
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