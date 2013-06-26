<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2013 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\MediaEmbed;

use DOMDocument;
use DOMXPath;
use RuntimeException;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\Regexp;
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
		$tag->rules->denyAll();

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

		return [
			'quickMatch' => $this->quickMatch,
			'regexp'     => $this->regexp
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
			$siteConfig = [];
			foreach ($node->childNodes as $childNode)
			{
				if ($childNode->nodeType !== XML_ELEMENT_NODE)
				{
					continue;
				}

				// Name of the configuration option
				$k = $childNode->nodeName;

				if ($childNode->attributes->length)
				{
					// Elements with attributes create a sub-array for their values
					foreach ($childNode->attributes as $attribute)
					{
						$siteConfig[$k][$attribute->name] = $attribute->value;
					}
				}
				elseif (isset($siteConfig[$k]))
				{
					// If there are multiple nodes of the same name, turn the config into an array
					if (!is_array($siteConfig[$k]))
					{
						$siteConfig[$k] = (array) $siteConfig[$k];
					}

					$siteConfig[$k][] = $childNode->textContent;
				}
				else
				{
					// Elements with no attributes get their value as text
					$siteConfig[$k] = $childNode->textContent;
				}
			}
		}

		// Add this site to the list
		$this->collection[$siteId] = $siteConfig;

		// Create the tag for this site
		$tag = $this->configurator->tags->add($siteId);

		// This tag should not need to be closed, and shouldn't have any descendants
		$tag->rules->autoClose();
		$tag->rules->denyAll();

		// We'll store the regexp used for matching IDs
		$idRegexp = false;

		// Add each "match" as an attribute preprocessor
		foreach ((array) $siteConfig['match'] as $regexp)
		{
			// Get the attributes filled by this regexp
			$attributes = $tag->attributePreprocessors->add('url', $regexp)->getAttributes();

			// For each named subpattern in the regexp, ensure that an attribute exists and create
			// it otherwise, using the subpattern as regexp filter
			foreach ($attributes as $attrName => $attrRegexp)
			{
				// Skip this attribute if it already exists
				if (isset($tag->attributes[$attrName]))
				{
					continue;
				}

				$tag->attributes->add($attrName)->filterChain->append(new Regexp($attrRegexp));

				// If this is the "id" attribute, save its regexp
				if ($attrName === 'id')
				{
					// Replace the non-capturing subpattern with a named subpattern
					$idRegexp = str_replace('^(?:', "^(?'id'", $attrRegexp);
				}
				else
				{
					// Non-id attributes are marked as optional
					$tag->attributes[$attrName]->required = false;
				}
			}
		}

		// If there is an attribute named "id" we'll append its regexp to the list of attribute
		// preprocessors in order to support both forms [site]<url>[/site] and [site]<id>[/site]
		if ($idRegexp !== false)
		{
			$tag->attributePreprocessors->add('url', $idRegexp);
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
				case 'flash':
					$tag->defaultTemplate = '<object type="application/x-shockwave-flash" width="' . $siteConfig['flash']['width'] . '" height="' . $siteConfig['flash']['height'] . '"><param name="movie" value="' . $siteConfig['flash']['src'] . '"/><param name="allowFullScreen" value="true"/><embed src="' . $siteConfig['flash']['src'] . '" type="application/x-shockwave-flash" width="' . $siteConfig['flash']['width'] . '" height="' . $siteConfig['flash']['height'] . '" allowfullscreen=""></embed></object>';
					break 2;

				case 'iframe':
					$tag->defaultTemplate = '<iframe width="' . $siteConfig['iframe']['width'] . '" height="' . $siteConfig['iframe']['height'] . '" src="' . $siteConfig['iframe']['src'] . '" allowfullscreen=""/>';
					break 2;

				case 'template':
					$tag->defaultTemplate = $siteConfig['template'];
					break 2;
			}
		}

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
}