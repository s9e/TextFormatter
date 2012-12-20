<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\HTMLElements;

use InvalidArgumentException;
use RuntimeException;
use s9e\TextFormatter\Configurator\Helpers\RegexpBuilder;
use s9e\TextFormatter\Plugins\ConfiguratorBase;

class Configurator extends ConfiguratorBase
{
	/**
	* @var string Namespace prefix of the tags produced by this plugin's parser
	*/
	protected $prefix = 'html';

	/**
	* {@inheritdoc}
	*/
	protected $quickMatch = '<';

	/**
	* @var string Catch-all XSL, used to render all tags in the "html" namespace
	*/
	protected $xsl = '<xsl:template match="html:*"><xsl:element name="{local-name()}"><xsl:copy-of select="@*"/><xsl:apply-templates/></xsl:element></xsl:template>';

	/**
	* @var array  Default filter of a few known attributes
	*
	* It doesn't make much sense to try to declare every known HTML attribute here. Validation is
	* not the purpose of this plugin. It does make sense however to declare URL attributes as such,
	* so that they are subject to our constraints (disallowed hosts, etc...)
	*/
	protected $attributeFilters = array(
		'action'     => '#url',
		'cite'       => '#url',
		'data'       => '#url',
		'formaction' => '#url',
		'href'       => '#url',
		'icon'       => '#url',
		'manifest'   => '#url',
		'poster'     => '#url',
		'src'        => '#url'
	);

	/**
	* @var array  Blacklist of elements that are considered unsafe
	*/
	protected $unsafeElements = array(
		'base',
		'embed',
		'frame',
		'iframe',
		'meta',
		'object',
		'script'
	);

	/**
	* @var array  Blacklist of attributes that are considered unsafe, in addition of any attribute
	*             whose name starts with "on" such as "onmouseover"
	*/
	protected $unsafeAttributes = array(
		'style',
		'target'
	);

	/**
	* @var array  Hash of allowed HTML elements. Element names are lowercased and used as keys for
	*             this array
	*/
	protected $tags = array();

	/**
	* Plugin's setup
	*
	* @return void
	*/
	public function setUp()
	{
		if ($this->prefix !== 'html')
		{
			/**
			* Not terribly reliable but should work in all but the most peculiar of cases
			*/
			$this->xsl = str_replace('="html:', '="' . $this->prefix . ':', $this->xsl);
		}
	}

	/**
	* Allow an HTML element to be used
	*
	* @param  string $elName
	* @return void
	*/
	public function allowElement($elName)
	{
		$this->_allowElement($elName, false);
	}

	/**
	* Allow an unsafe HTML element to be used
	*
	* @param  string $elName
	* @return void
	*/
	public function allowUnsafeElement($elName)
	{
		$this->_allowElement($elName, true);
	}

	/**
	* Allow a (potentially unsafe) HTML element to be used
	*
	* @param  string $elName
	* @param  bool   $allowUnsafe
	* @return void
	*/
	protected function _allowElement($elName, $allowUnsafe)
	{
		$elName  = $this->normalizeElementName($elName);
		$tagName = $this->prefix . ':' . $elName;

		if (!$allowUnsafe && in_array($elName, $this->unsafeElements))
		{
			throw new RuntimeException("'" . $elName . "' elements are unsafe and are disabled by default. Please use " . __CLASS__ . '::allowUnsafeElement() to bypass this security measure');
		}

		if (!$this->configurator->tags->exists($tagName))
		{
			$this->configurator->tags->add($tagName);
		}

		$this->tags[$elName] = 1;
	}

	/**
	* Allow an attribute to be used in an HTML element
	*
	* @param  string $elName
	* @param  string $attrName
	* @return void
	*/
	public function allowAttribute($elName, $attrName)
	{
		$this->_allowAttribute($elName, $attrName, false);
	}

	/**
	* Allow an unsafe attribute to be used in an HTML element
	*
	* @param  string $elName
	* @param  string $attrName
	* @return void
	*/
	public function allowUnsafeAttribute($elName, $attrName)
	{
		$this->_allowAttribute($elName, $attrName, true);
	}

	/**
	* Allow a (potentially unsafe) attribute to be used in an HTML element
	*
	* @param  string $elName
	* @param  string $attrName
	* @param  bool   $allowUnsafe
	* @return void
	*/
	protected function _allowAttribute($elName, $attrName, $allowUnsafe)
	{
		$elName   = $this->normalizeElementName($elName);
		$attrName = $this->normalizeAttributeName($attrName);
		$tagName  = $this->prefix . ':' . $elName;

		if (!isset($this->tags[$elName]))
		{
			throw new RuntimeException("Element '" . $elName . "' has not been allowed");
		}

		if (!$allowUnsafe)
		{
			if (substr($attrName, 0, 2) === 'on'
			 || in_array($attrName, $this->unsafeAttributes))
			{
				throw new RuntimeException("'" . $elName . "' elements are unsafe and are disabled by default. Please use " . __CLASS__ . '::allowUnsafeAttribute() to bypass this security measure');
			}
		}

		$tag = $this->configurator->tags->get($tagName);
		if (!isset($tag->attributes[$attrName]))
		{
			$attribute = $tag->attributes->add($attrName);
			$attribute->required = false;

			if (isset($this->attributeFilters[$attrName]))
			{
				$attribute->filterChain->append($this->attributeFilters[$attrName]);
			}
		}
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
	* @param  string $elName    Original element name
	* @param  bool   $mustExist If TRUE, throw an exception if the element is not allowed
	* @return string            Normalized element name, in lowercase
	*/
	protected function normalizeElementName($elName)
	{
		if (!preg_match('#^[a-z][a-z0-9]*$#Di', $elName))
		{
			throw new InvalidArgumentException ("Invalid element name '" . $elName . "'");
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
		if (!preg_match('#^[a-z]\\w*$#Di', $attrName))
		{
			throw new InvalidArgumentException ("Invalid attribute name '" . $attrName . "'");
		}

		return strtolower($attrName);
	}

	/**
	* Generate this plugin's config
	*/
	public function asConfig()
	{
		if (empty($this->tags))
		{
			return false;
		}

		/**
		* Regexp used to match an attributes definition (name + value if applicable)
		*
		* @link http://dev.w3.org/html5/spec/syntax.html#attributes-0
		*/
		$attrRegexp = '[a-z][a-z\\-]*(?:\\s*=\\s*(?:"[^"]*"|\'[^\']*\'|[^\\s"\'=<>`]+))?';
		$tagRegexp  = RegexpBuilder::fromList(array_keys($this->tags));

		$endTagRegexp   = '/(' . $tagRegexp . ')';
		$startTagRegexp = '(' . $tagRegexp . ')((?:\\s+' . $attrRegexp . ')*+)\\s*/?';

		$regexp = '#<(?:' . $endTagRegexp . '|' . $startTagRegexp . ')\\s*>#i';

		return array(
			'attrRegexp' => '#' . $attrRegexp . '#i',
			'quickMatch' => $this->quickMatch,
			'prefix'     => $this->prefix,
			'regexp'     => $regexp
		);
	}

	/**
	* 
	* @todo
	* @return string
	*/
	public function getXSL()
	{
		return $this->xsl;
	}
}