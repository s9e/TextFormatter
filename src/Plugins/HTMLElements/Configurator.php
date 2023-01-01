<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\HTMLElements;

use InvalidArgumentException;
use RuntimeException;
use s9e\TextFormatter\Configurator\Helpers\RegexpBuilder;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\Items\UnsafeTemplate;
use s9e\TextFormatter\Configurator\JavaScript\Dictionary;
use s9e\TextFormatter\Configurator\Validators\AttributeName;
use s9e\TextFormatter\Configurator\Validators\TagName;
use s9e\TextFormatter\Plugins\ConfiguratorBase;

class Configurator extends ConfiguratorBase
{
	/**
	* @var array 2D array using HTML element names as keys, each value being an associative array
	*            using HTML attribute names as keys and their alias as values. A special empty entry
	*            is used to store the HTML element's alias
	*/
	protected $aliases = [];

	/**
	* @var array  Default filter of a few known attributes
	*
	* It doesn't make much sense to try to declare every known HTML attribute here. Validation is
	* not the purpose of this plugin. It does make sense however to declare URL attributes as such,
	* so that they are subject to our constraints (disallowed hosts, etc...)
	*
	* @see scripts/patchHTMLElementConfigurator.php
	*/
	protected $attributeFilters = [
		'action'     => '#url',
		'cite'       => '#url',
		'data'       => '#url',
		'formaction' => '#url',
		'href'       => '#url',
		'icon'       => '#url',
		'itemtype'   => '#url',
		'longdesc'   => '#url',
		'manifest'   => '#url',
		'ping'       => '#url',
		'poster'     => '#url',
		'src'        => '#url'
	];

	/**
	* @var array  Hash of allowed HTML elements. Element names are lowercased and used as keys for
	*             this array
	*/
	protected $elements = [];

	/**
	* @var string Namespace prefix of the tags produced by this plugin's parser
	*/
	protected $prefix = 'html';

	/**
	* {@inheritdoc}
	*/
	protected $quickMatch = '<';

	/**
	* @var array  Blacklist of elements that are considered unsafe
	*/
	protected $unsafeElements = [
		'base',
		'embed',
		'frame',
		'iframe',
		'meta',
		'object',
		'script'
	];

	/**
	* @var array  Blacklist of attributes that are considered unsafe, in addition of any attribute
	*             whose name starts with "on" such as "onmouseover"
	*/
	protected $unsafeAttributes = [
		'style',
		'target'
	];

	/**
	* Alias the HTML attribute of given HTML element to a given attribute name
	*
	* NOTE: will *not* create the target attribute
	*
	* @param  string $elName   Name of the HTML element
	* @param  string $attrName Name of the HTML attribute
	* @param  string $alias    Alias
	* @return void
	*/
	public function aliasAttribute($elName, $attrName, $alias)
	{
		$elName   = $this->normalizeElementName($elName);
		$attrName = $this->normalizeAttributeName($attrName);

		$this->aliases[$elName][$attrName] = AttributeName::normalize($alias);
	}

	/**
	* Alias an HTML element to a given tag name
	*
	* NOTE: will *not* create the target tag
	*
	* @param  string $elName  Name of the HTML element
	* @param  string $tagName Name of the tag
	* @return void
	*/
	public function aliasElement($elName, $tagName)
	{
		$elName = $this->normalizeElementName($elName);

		$this->aliases[$elName][''] = TagName::normalize($tagName);
	}

	/**
	* Allow an HTML element to be used
	*
	* @param  string $elName Name of the element
	* @return Tag            Tag that represents this element
	*/
	public function allowElement($elName)
	{
		return $this->allowElementWithSafety($elName, false);
	}

	/**
	* Allow an unsafe HTML element to be used
	*
	* @param  string $elName Name of the element
	* @return Tag            Tag that represents this element
	*/
	public function allowUnsafeElement($elName)
	{
		return $this->allowElementWithSafety($elName, true);
	}

	/**
	* Allow a (potentially unsafe) HTML element to be used
	*
	* @param  string $elName      Name of the element
	* @param  bool   $allowUnsafe Whether to allow unsafe elements
	* @return Tag                 Tag that represents this element
	*/
	protected function allowElementWithSafety($elName, $allowUnsafe)
	{
		$elName  = $this->normalizeElementName($elName);
		$tagName = $this->prefix . ':' . $elName;

		if (!$allowUnsafe && in_array($elName, $this->unsafeElements))
		{
			throw new RuntimeException("'" . $elName . "' elements are unsafe and are disabled by default. Please use " . __CLASS__ . '::allowUnsafeElement() to bypass this security measure');
		}

		// Retrieve or create the tag
		$tag = ($this->configurator->tags->exists($tagName))
		     ? $this->configurator->tags->get($tagName)
		     : $this->configurator->tags->add($tagName);

		// Rebuild this tag's template
		$this->rebuildTemplate($tag, $elName, $allowUnsafe);

		// Record the element name
		$this->elements[$elName] = 1;

		return $tag;
	}

	/**
	* Allow an attribute to be used in an HTML element
	*
	* @param  string $elName   Name of the element
	* @param  string $attrName Name of the attribute
	* @return \s9e\Configurator\Items\Attribute
	*/
	public function allowAttribute($elName, $attrName)
	{
		return $this->allowAttributeWithSafety($elName, $attrName, false);
	}

	/**
	* Allow an unsafe attribute to be used in an HTML element
	*
	* @param  string $elName   Name of the element
	* @param  string $attrName Name of the attribute
	* @return \s9e\Configurator\Items\Attribute
	*/
	public function allowUnsafeAttribute($elName, $attrName)
	{
		return $this->allowAttributeWithSafety($elName, $attrName, true);
	}

	/**
	* Allow a (potentially unsafe) attribute to be used in an HTML element
	*
	* @param  string $elName   Name of the element
	* @param  string $attrName Name of the attribute
	* @param  bool   $allowUnsafe
	* @return \s9e\Configurator\Items\Attribute
	*/
	protected function allowAttributeWithSafety($elName, $attrName, $allowUnsafe)
	{
		$elName   = $this->normalizeElementName($elName);
		$attrName = $this->normalizeAttributeName($attrName);
		$tagName  = $this->prefix . ':' . $elName;

		if (!isset($this->elements[$elName]))
		{
			throw new RuntimeException("Element '" . $elName . "' has not been allowed");
		}

		if (!$allowUnsafe)
		{
			if (substr($attrName, 0, 2) === 'on'
			 || in_array($attrName, $this->unsafeAttributes))
			{
				throw new RuntimeException("'" . $attrName . "' attributes are unsafe and are disabled by default. Please use " . __CLASS__ . '::allowUnsafeAttribute() to bypass this security measure');
			}
		}

		$tag = $this->configurator->tags->get($tagName);
		if (!isset($tag->attributes[$attrName]))
		{
			$attribute = $tag->attributes->add($attrName);
			$attribute->required = false;

			if (isset($this->attributeFilters[$attrName]))
			{
				$filterName = $this->attributeFilters[$attrName];
				$filter = $this->configurator->attributeFilters->get($filterName);

				$attribute->filterChain->append($filter);
			}
		}

		// Rebuild this tag's template
		$this->rebuildTemplate($tag, $elName, $allowUnsafe);

		return $tag->attributes[$attrName];
	}

	/**
	* Validate and normalize an element name
	*
	* Accepts any name that would be valid, regardless of whether this element exists in HTML5.
	* Might be slightly off as the HTML5 specs don't seem to require it to start with a letter but
	* our implementation does.
	*
	* @link http://dev.w3.org/html5/spec/syntax.html#syntax-tag-name
	*
	* @param  string $elName Original element name
	* @return string         Normalized element name, in lowercase
	*/
	protected function normalizeElementName($elName)
	{
		if (!preg_match('#^[a-z][a-z0-9]*$#Di', $elName))
		{
			throw new InvalidArgumentException("Invalid element name '" . $elName . "'");
		}

		return strtolower($elName);
	}

	/**
	* Validate and normalize an attribute name
	*
	* More restrictive than the specs but allows all HTML5 attributes and more.
	*
	* @param  string $attrName Original attribute name
	* @return string           Normalized attribute name, in lowercase
	*/
	protected function normalizeAttributeName($attrName)
	{
		if (!preg_match('#^[a-z][-\\w]*$#Di', $attrName))
		{
			throw new InvalidArgumentException("Invalid attribute name '" . $attrName . "'");
		}

		return strtolower($attrName);
	}

	/**
	* Rebuild a tag's template
	*
	* @param  Tag    $tag         Source tag
	* @param  string $elName      Name of the HTML element created by the template
	* @param  bool   $allowUnsafe Whether to allow unsafe markup
	* @return void
	*/
	protected function rebuildTemplate(Tag $tag, $elName, $allowUnsafe)
	{
		$template = '<' . $elName . '>';
		foreach ($tag->attributes as $attrName => $attribute)
		{
			$template .= '<xsl:copy-of select="@' . $attrName . '"/>';
		}
		$template .= '<xsl:apply-templates/></' . $elName . '>';

		if ($allowUnsafe)
		{
			$template = new UnsafeTemplate($template);
		}

		$tag->setTemplate($template);
	}

	/**
	* Generate this plugin's config
	*
	* @return array|null
	*/
	public function asConfig()
	{
		if (empty($this->elements) && empty($this->aliases))
		{
			return;
		}

		/**
		* Regexp used to match an attributes definition (name + value if applicable)
		*
		* @link http://dev.w3.org/html5/spec/syntax.html#attributes-0
		*/
		$attrRegexp = '[a-z][-a-z0-9]*(?>\\s*=\\s*(?>"[^"]*"|\'[^\']*\'|[^\\s"\'=<>`]+))?';
		$tagRegexp  = RegexpBuilder::fromList(array_merge(
			array_keys($this->aliases),
			array_keys($this->elements)
		));

		$endTagRegexp   = '/(' . $tagRegexp . ')';
		$startTagRegexp = '(' . $tagRegexp . ')((?>\\s+' . $attrRegexp . ')*+)\\s*/?';

		$regexp = '#<(?>' . $endTagRegexp . '|' . $startTagRegexp . ')\\s*>#i';

		$config = [
			'quickMatch' => $this->quickMatch,
			'prefix'     => $this->prefix,
			'regexp'     => $regexp
		];

		if (!empty($this->aliases))
		{
			// Preserve the aliases array's keys in JavaScript
			$config['aliases'] = new Dictionary;
			foreach ($this->aliases as $elName => $aliases)
			{
				$config['aliases'][$elName] = new Dictionary($aliases);
			}
		}

		return $config;
	}

	/**
	* {@inheritdoc}
	*/
	public function getJSHints()
	{
		return ['HTMLELEMENTS_HAS_ALIASES' => (int) !empty($this->aliases)];
	}
}