<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\MediaEmbed;

use InvalidArgumentException;
use RuntimeException;
use s9e\TextFormatter\Configurator\Helpers\AVTHelper;
use s9e\TextFormatter\Configurator\Helpers\RegexpBuilder;
use s9e\TextFormatter\Configurator\Items\Attribute;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\RegexpFilter;
use s9e\TextFormatter\Configurator\Items\AttributePreprocessor;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Plugins\ConfiguratorBase;
use s9e\TextFormatter\Plugins\MediaEmbed\Configurator\CachedSiteDefinitionProvider;
use s9e\TextFormatter\Plugins\MediaEmbed\Configurator\MediaSiteCollection;

class Configurator extends ConfiguratorBase
{
	/**
	* @var array List of filters that are explicitly allowed in attribute definitions
	*/
	public $allowedFilters = [
		'hexdec',
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
	* @var MediaSiteCollection MediaSite collection
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
	* @var Configurator\SiteDefinitionProvider Default sites
	*/
	public $defaultSites;

	/**
	* @var array List of rendering methods in order of preference, descending
	*/
	protected $preferredRenderingMethods = ['iframe', 'flash'];

	/**
	* @var bool Whether to enable responsive embeds
	*/
	protected $responsiveEmbeds = false;

	/**
	* {@inheritdoc}
	*/
	protected function setUp()
	{
		// Create a collection to store the configured sites
		$this->collection = new MediaSiteCollection;

		// Register the collection as a variable to be used during parsing
		$this->configurator->registeredVars['mediasites'] = $this->collection;

		// Create a MEDIA tag
		$tag = $this->configurator->tags->add('MEDIA');

		// This tag should not need to be closed, and shouldn't have any descendants
		$tag->rules->autoClose();
		$tag->rules->ignoreTags();

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
			$this->configurator->BBCodes->set('MEDIA', ['contentAttributes' => ['url']]);
		}

		if (!isset($this->defaultSites))
		{
			$this->defaultSites = new CachedSiteDefinitionProvider;
		}
	}

	/**
	* {@inheritdoc}
	*/
	public function asConfig()
	{
		if (!$this->captureURLs)
		{
			return;
		}

		// Unicode char used as a placeholder for the regular expression that marks the beginning of
		// a URL
		$char = "\xEE\x80\x80";

		$hasSchemes = false;
		$patterns   = [];
		foreach ($this->collection as $site)
		{
			if (isset($site['host']))
			{
				foreach ((array) $site['host'] as $host)
				{
					$patterns[] = $char . $host . '/';
				}
			}

			if (isset($site['scheme']))
			{
				foreach ((array) $site['scheme'] as $scheme)
				{
					$hasSchemes = true;
					$patterns[] = $scheme . ':';
				}
			}
		}

		if (empty($patterns))
		{
			return;
		}

		// Merge all the patterns
		$regexp = RegexpBuilder::fromList(
			$patterns,
			[
				'delimiter'    => '#',
				'specialChars' => [$char => 'https?://(?:[-.\\w]+\\.)?']
			]
		);

		// Replace the non-capturing subpattern at the start with an atomic group
		$regexp = preg_replace('(^\\(\\?:)', '(?>', $regexp);

		// Build the final regexp
		$regexp = '#\\b' . $regexp . '[^["\'\\s]+' . '(?!\\S)' . '#S';

		return [
			'quickMatch' => ($hasSchemes) ? ':' : '://',
			'regexp'     => $regexp
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

		// This tag should not need to be closed, and shouldn't have any descendants
		$tag->rules->autoClose();
		$tag->rules->ignoreTags();

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
			// Replace the non-capturing subpattern with a named subpattern
			$attrRegexp = preg_replace('/\\^\\(\\?[:>]/', "^(?'id'", $attributes['id']['regexp']);

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
		foreach ($this->preferredRenderingMethods as $renderingMethod)
		{
			if (!isset($siteConfig[$renderingMethod]))
			{
				continue;
			}

			// 'flash' => 'buildFlash'
			$methodName = 'build' . ucfirst($renderingMethod);

			// Set the tag's default template then exit the loop
			$tag->template = $this->$methodName($siteConfig) . $this->appendTemplate;

			break;
		}

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

	/**
	* Disable responsive embeds
	*
	* @return void
	*/
	public function disableResponsiveEmbeds()
	{
		$this->responsiveEmbeds = false;
	}

	/**
	* Enable responsive embeds
	*
	* @return void
	*/
	public function enableResponsiveEmbeds()
	{
		$this->responsiveEmbeds = true;
	}

	//==========================================================================
	// Internal methods
	//==========================================================================

	/**
	* Add the attributes required for responsive embeds
	*
	* @param  array $attributes Array of [name => value] where value can be XSL code
	* @return array             Modified attributes
	*/
	protected function addResponsiveStyle(array $attributes)
	{
		$css = 'position:absolute;top:0;left:0;width:100%;height:100%';
		if (isset($attributes['style']))
		{
			$attributes['style'] .= ';' . $css;
		}
		else
		{
			$attributes['style'] = $css;
		}

		return $attributes;
	}

	/**
	* Add the attributes required for responsive embeds
	*
	* @param  string $template   Original template
	* @param  array  $attributes Array of [name => value] where value can be XSL code
	* @return string             Modified template
	*/
	protected function addResponsiveWrapper($template, array $attributes)
	{
		// Remove braces from the values
		$height = trim($attributes['height'], '{}');
		$width  = trim($attributes['width'], '{}');

		$isFixedHeight = (bool) preg_match('(^\\d+$)D', $height);
		$isFixedWidth  = (bool) preg_match('(^\\d+$)D', $width);

		if ($isFixedHeight && $isFixedWidth)
		{
			$padding = round(100 * $height / $width, 2);
		}
		else
		{
			if (!preg_match('(^[@$]?[-\\w]+$)D', $height))
			{
				$height = '(' . $height . ')';
			}
			if (!preg_match('(^[@$]?[-\\w]+$)D', $width))
			{
				$width = '(' . $width . ')';
			}

			$padding = '<xsl:value-of select="100*' . $height . ' div'. $width . '"/>';
		}

		return '<div><xsl:attribute name="style">display:inline-block;width:100%;max-width:' . $width . 'px</xsl:attribute><div><xsl:attribute name="style">height:0;position:relative;padding-top:' . $padding . '%</xsl:attribute>' . $template . '</div></div>';
	}

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
						 ->setJS('function(){return true;}');

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
	* Build a tag's template based on its flash config
	*
	* @param  array  $siteConfig
	* @return string
	*/
	protected function buildFlash(array $siteConfig)
	{
		// Gather the attributes for the object element
		$attributes = [
			'width'  => $siteConfig['flash']['width'],
			'height' => $siteConfig['flash']['height'],
			'data'   => $siteConfig['flash']['src']
		];

		if (isset($siteConfig['flash']['base']))
		{
			$attributes['base'] = $siteConfig['flash']['base'];
		}
		if (isset($siteConfig['flash']['style']))
		{
			$attributes['style'] = $siteConfig['flash']['style'];
		}

		$isResponsive = $this->responsiveEmbeds && empty($siteConfig['unresponsive']) && $this->canBeResponsive($attributes);
		if ($isResponsive)
		{
			$attributes = $this->addResponsiveStyle($attributes);
		}

		/**
		* @link http://www.whatwg.org/specs/web-apps/current-work/multipage/the-iframe-element.html#the-object-element
		*/
		$template = '<object type="application/x-shockwave-flash" typemustmatch="">';
		$template .= $this->generateAttributes($attributes, $isResponsive);
		$template .= '<param name="allowfullscreen" value="true"/>';
		if (isset($siteConfig['flash']['flashvars']))
		{
			/**
			* @link http://helpx.adobe.com/flash/kb/pass-variables-swfs-flashvars.html
			*/
			$template .= '<param name="flashvars">';
			$template .= $this->generateAttributes([
				'value' => $siteConfig['flash']['flashvars']
			]);
			$template .= '</param>';
		}
		$template .= '<embed type="application/x-shockwave-flash">';

		// Update the attributes for the embed element
		$attributes['src'] = $attributes['data'];
		$attributes['allowfullscreen'] = '';
		unset($attributes['data']);
		if (isset($siteConfig['flash']['flashvars']))
		{
			$attributes['flashvars'] = $siteConfig['flash']['flashvars'];
		}
		$template .= $this->generateAttributes($attributes);
		$template .= '</embed></object>';

		if ($isResponsive)
		{
			$template = $this->addResponsiveWrapper($template, $attributes);
		}

		return $template;
	}

	/**
	* Build a tag's template based on its iframe config
	*
	* @param  array  $siteConfig
	* @return string
	*/
	protected function buildIframe(array $siteConfig)
	{
		// Get attributes from the original definition
		$attributes = $siteConfig['iframe'];

		// Add the default attributes
		$attributes += [
			'allowfullscreen' => '',
			'frameborder'     => '0',
			'scrolling'       => 'no'
		];

		// Build the template
		$isResponsive = $this->responsiveEmbeds && empty($siteConfig['unresponsive']) && $this->canBeResponsive($attributes);
		$template = '<iframe>' . $this->generateAttributes($attributes, $isResponsive) . '</iframe>';

		if ($isResponsive)
		{
			$template = $this->addResponsiveWrapper($template, $attributes);
		}

		return $template;
	}

	/**
	* Test whether given dimensions can be made repsonsive
	*
	* @param  array $attributes Array of [name => value] where value can be XSL code
	* @return bool
	*/
	protected function canBeResponsive(array $attributes)
	{
		// Cannot be responsive if dimensions contain a percentage of an XSL element
		return !preg_match('([%<])', $attributes['width'] . $attributes['height']);
	}

	/**
	* Generate xsl:attributes elements from an array
	*
	* @param  array  $attributes    Array of [name => value] where value can be XSL code
	* @param  bool   $addResponsive Whether to add the responsive style attributes
	* @return string                XSL source
	*/
	protected function generateAttributes(array $attributes, $addResponsive = false)
	{
		if ($addResponsive)
		{
			$attributes = $this->addResponsiveStyle($attributes);
		}

		$xsl = '';
		foreach ($attributes as $attrName => $innerXML)
		{
			// If the value does not look like XSL, we reconstruct it as XSL
			if (strpos($innerXML, '<') === false)
			{
				$tokens   = AVTHelper::parse($innerXML);
				$innerXML = '';
				foreach ($tokens as list($type, $content))
				{
					if ($type === 'literal')
					{
						$innerXML .= htmlspecialchars($content, ENT_NOQUOTES, 'UTF-8');
					}
					else
					{
						$innerXML .= '<xsl:value-of select="' . htmlspecialchars($content, ENT_QUOTES, 'UTF-8') . '"/>';
					}
				}
			}

			$xsl .= '<xsl:attribute name="' . htmlspecialchars($attrName, ENT_QUOTES, 'UTF-8') . '">' . $innerXML . '</xsl:attribute>';
		}

		return $xsl;
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