<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2013 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\MediaEmbed;

use DOMDocument;
use DOMElement;
use DOMXPath;
use RuntimeException;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\Regexp;
use s9e\TextFormatter\Configurator\Items\AttributePreprocessor;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\Helpers\RegexpBuilder;
use s9e\TextFormatter\Configurator\Helpers\TemplateHelper;
use s9e\TextFormatter\Plugins\ConfiguratorBase;
use s9e\TextFormatter\Plugins\MediaEmbed\Configurator\MediaSiteCollection;

class Configurator extends ConfiguratorBase
{
	/**
	* @var bool Whether to replace unformatted URLs in text with embedded content
	*/
	protected $captureURLs = true;

	/**
	* @var MediaSiteCollection MediaSite collection
	*/
	protected $collection;

	/**
	* @var bool Whether to create BBCodes
	*/
	protected $createBBCodes = true;

	/**
	* {@inheritdoc}
	*/
	protected $quickMatch = '://';

	/**
	* @var array List of rendering methods in order of preference, descending
	*/
	protected $preferredRenderingMethods = ['template', 'iframe', 'flash'];

	/**
	* {@inheritdoc}
	*/
	protected $regexp = '#(https?://\\S+)#';

	/**
	* @var DOMXPath XPath engine pointing to the document containing the predefined sites
	*/
	protected $sites;

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
		    ->setJS(file_get_contents(__DIR__ . '/Parser/TagFilter.js'));

		// Create a [MEDIA] BBCode if applicable
		if ($this->createBBCodes)
		{
			$this->configurator->BBCodes->set('MEDIA', ['contentAttributes' => ['url']]);
		}

		if (!isset($this->sites))
		{
			$dom = new DOMDocument;
			$dom->load(__DIR__ . '/Configurator/sites.xml');

			$this->sites = new DOMXPath($dom);
		}
	}

	/**
	* {@inheritdoc}
	*/
	public function asConfig()
	{
		if (!$this->captureURLs)
		{
			return false;
		}

		$hosts = [];
		foreach ($this->collection as $site)
		{
			foreach ((array) $site['host'] as $host)
			{
				$hosts[] = $host;
			}
		}

		if (empty($hosts))
		{
			return false;
		}

		return [
			'quickMatch' => $this->quickMatch,
			'regexp'     => '!https?://(?:\\w+\\.)*' . RegexpBuilder::fromList($hosts) . '/\\S+!'
		];
	}

	//==========================================================================
	// Collection stuff
	//==========================================================================

	/**
	* Add a media site
	*
	* @param  string    $siteId     Site's ID
	* @param  array     $siteConfig Site's config
	* @return Tag                   T
	*/
	public function add($siteId, array $siteConfig = null)
	{
		// Normalize the site ID to lowercase
		$siteId = strtolower($siteId);

		if (!isset($siteConfig))
		{
			// If there's no value, look for a match in the predefined sites document
			$query = '//site[@id="' . htmlspecialchars($siteId) . '"]';
			$node  = $this->sites->query($query)->item(0);

			if (!$node)
			{
				throw new RuntimeException("Unknown media site '" . $siteId . "'");
			}

			// Extract the site info from the node and put it into an array
			$siteConfig = $this->getElementConfig($node);
		}

		// Add this site to the list
		$this->collection[$siteId] = $siteConfig;

		// Create the tag for this site
		$tag = new Tag;

		// This tag should not need to be closed, and shouldn't have any descendants
		$tag->rules->autoClose();
		$tag->rules->ignoreTags();

		// Store the regexp used in extracted attributes
		$attrRegexps = [];

		// Process the "scrape" directives
		if (isset($siteConfig['scrape']))
		{
			// Ensure that the array is multidimensional
			if (!isset($siteConfig['scrape'][0]))
			{
				$siteConfig['scrape'] = [$siteConfig['scrape']];
			}

			$scrapeConfig = [];
			foreach ($siteConfig['scrape'] as $scrape)
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
						$attrNames[]            = $attrName;
						$attrRegexps[$attrName] = $attrRegexp;
					}
				}

				// Deduplicate and sort the attribute names so that they look tidy
				$attrNames = array_unique($attrNames);
				sort($attrNames);

				// Add this scrape to the config
				$scrapeConfig[] = [$scrape['match'], $scrape['extract'], $attrNames];
			}

			// Add the scrape filter to this tag, execute it right before attributes are filtered,
			// which should be after attribute preprocessors are run. The offset is hardcoded here
			// for convenience (and because we know the filterChain is in its default state) and
			// since scraping is impossible in JavaScript without a PHP proxy, we just make it
			// return true in order to keep the tag valid
			$tag->filterChain->insert(1, __NAMESPACE__ . '\\Parser::scrape')
			                 ->addParameterByName('scrapeConfig')
			                 ->setVar('scrapeConfig', $scrapeConfig)
			                 ->setJS('function(){return true;}');
		}

		// Add each "extract" as an attribute preprocessor
		if (isset($siteConfig['extract']))
		{
			foreach ((array) $siteConfig['extract'] as $regexp)
			{
				// Get the attributes filled by this regexp
				$attributes = $tag->attributePreprocessors->add('url', $regexp)->getAttributes();

				// For each named subpattern in the regexp, ensure that an attribute exists and
				// create it otherwise, using the subpattern as regexp filter
				foreach ($attributes as $attrName => $attrRegexp)
				{
					$attrRegexps[$attrName] = $attrRegexp;
				}
			}
		}

		// Create the attributes filled by the "extract" regexps
		foreach ($attrRegexps as $attrName => $attrRegexp)
		{
			$tag->attributes->add($attrName)->filterChain->append(new Regexp($attrRegexp));

			// Non-id attributes are marked as optional
			if ($attrName !== 'id')
			{
				$tag->attributes[$attrName]->required = false;
			}
		}

		// If there is an attribute named "id" we'll append its regexp to the list of attribute
		// preprocessors in order to support both forms [site]<url>[/site] and [site]<id>[/site]
		if (isset($attrRegexps['id']))
		{
			// Replace the non-capturing subpattern with a named subpattern
			$attrRegexp = preg_replace('/\\^\\(\\?[:>]/', "^(?'id'", $attrRegexps['id']);
			$tag->attributePreprocessors->add('url', $attrRegexp);
		}

		// Create a template for this media site based on the preferred rendering method
		foreach ($this->preferredRenderingMethods as $renderingMethod)
		{
			if (!isset($siteConfig[$renderingMethod]))
			{
				continue;
			}

			switch ($renderingMethod)
			{
				/**
				* @link http://www.whatwg.org/specs/web-apps/current-work/multipage/the-iframe-element.html#the-object-element
				*/
				case 'flash':
					$template = '<object type="application/x-shockwave-flash" typemustmatch="">';
					$template .= $this->generateAttributes([
						'width'  => $siteConfig['flash']['width'],
						'height' => $siteConfig['flash']['height'],
						'data'   => $siteConfig['flash']['src']
					]);
					$template .= '<param name="allowFullScreen" value="true"/>';
					$template .= '<embed type="application/x-shockwave-flash">';
					$template .= $this->generateAttributes([
						'src'    => $siteConfig['flash']['src'],
						'width'  => $siteConfig['flash']['width'],
						'height' => $siteConfig['flash']['height'],
						'allowfullscreen' => ''
					]);
					$template .= '</embed></object>';

					$tag->defaultTemplate = $template;
					break 2;

				case 'iframe':
					$attributes = $siteConfig['iframe'];
					$attributes['allowfullscreen'] = '';
					$attributes['frameborder']     = '0';
					$attributes['scrolling']       = 'no';

					$tag->defaultTemplate = '<iframe>' . $this->generateAttributes($attributes) . '</iframe>';
					break 2;

				case 'template':
					$tag->defaultTemplate = $siteConfig['template'];
					break 2;
			}
		}

		// Normalize the tag's templates
		$this->configurator->templateNormalizer->normalizeTag($tag);

		// Check the tag's safety
		$this->configurator->templateChecker->checkTag($tag);

		// Now add the tag to the list
		$this->configurator->tags->add($siteId, $tag);

		// Create a BBCode for this site if applicable
		if ($this->createBBCodes)
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
	* Generate xsl:attributes elements from an array
	*
	* @param  array  $attributes Array of [name => value] where value can be XSL code
	* @return string             XSL source
	*/
	protected function generateAttributes(array $attributes)
	{
		$xsl = '';
		foreach ($attributes as $attrName => $attrValue)
		{
			// If the value does not look like XSL, we reconstruct it as XSL
			if (strpos($attrValue, '<') === false)
			{
				$tokens    = TemplateHelper::parseAttributeValueTemplate($attrValue);
				$attrValue = '';
				foreach ($tokens as list($type, $content))
				{
					if ($type === 'literal')
					{
						$attrValue .= htmlspecialchars($content);
					}
					else
					{
						$attrValue .= '<xsl:value-of select="' . htmlspecialchars($content) . '"/>';
					}
				}
			}

			$xsl .= '<xsl:attribute name="' . htmlspecialchars($attrName) . '">' . $attrValue . '</xsl:attribute>';
		}

		return $xsl;
	}

	/**
	* Extract a site's config from its XML representation
	*
	* @param  DOMElement $element Current node
	* @return mixed
	*/
	protected function getElementConfig(DOMElement $element)
	{
		$config = [];
		foreach ($element->attributes as $attribute)
		{
			$config[$attribute->name] = $attribute->value;
		}

		// Group child nodes by name
		$childNodes = [];
		foreach ($element->childNodes as $childNode)
		{
			if ($childNode->nodeType !== XML_ELEMENT_NODE)
			{
				continue;
			}

			if (!$childNode->attributes->length && $childNode->childNodes->length === 1)
			{
				$value = $childNode->nodeValue;
			}
			else
			{
				$value = $this->getElementConfig($childNode);
			}

			$childNodes[$childNode->nodeName][] = $value;
		}

		foreach ($childNodes as $nodeName => $childNodes)
		{
			if (count($childNodes) === 1)
			{
				$config[$nodeName] = end($childNodes);
			}
			else
			{
				$config[$nodeName] = $childNodes;
			}
		}

		return $config;
	}
}