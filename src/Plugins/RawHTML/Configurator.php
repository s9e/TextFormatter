<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\RawHTML;

use InvalidArgumentException;
use RuntimeException;
use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Plugins\ConfiguratorBase;

class Configurator extends ConfiguratorBase
{
	/**
	* Flag used to allow unsafe elements such as <script> in allowElement()
	*/
	const ALLOW_UNSAFE_ELEMENTS = 1;

	/**
	* Flag used to allow unsafe attributes such as "onmouseover" in allowAttribute()
	*/
	const ALLOW_UNSAFE_ATTRIBUTES = 2;

	/**
	* @var string Namespace prefix of the tags produced by this plugin's parser
	*/
	protected $prefix = 'html';

	/**
	* @var string Catch-all XSL, used to render all tags in the html namespace
	*/
	protected $xsl = '<xsl:template match="html:*"><xsl:element name="{local-name()}"><xsl:copy-of select="@*"/><xsl:apply-templates/></xsl:element></xsl:template>';

	/**
	* @var array  Default attribute types of a few known attributes
	*
	* It doesn't make much sense to try to declare every known HTML attribute here. Validation is
	* not the purpose of this plugin. It does make sense however to declare URL attributes as such,
	* so that they are subject to our constraints (disallowed hosts, etc...)
	*/
	protected $attrFilter = array(
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

	public function setUp()
	{
		if ($this->prefix !== 'html')
		{
			/**
			* Not terribly reliable but should work in all but the most peculiar of cases
			*/
			$this->xsl = str_replace('="html:', '="' . $this->namespacePrefix . ':', $this->xsl);
		}
	}

	/**
	* Allow an HTML element to be used
	*
	* @param string  $elName
	* @param integer $flags
	*/
	public function allowElement($elName, $flags = 0)
	{
		$elName  = $this->normalizeElementName($elName, false);
		$tagName = $this->namespacePrefix . ':' . $elName;

		if (!($flags & self::ALLOW_UNSAFE_ELEMENTS)
		 && in_array($elName, $this->unsafeElements))
		{
			throw new RuntimeException('<' . $elName . '> elements are unsafe and are disabled by default. Please use the ' . __CLASS__ . '::ALLOW_UNSAFE_ELEMENTS flag to bypass this security measure');
		}

		if (!$this->configurator->tagExists($tagName))
		{
			$this->configurator->addTag($tagName);
		}

		$this->tags[$elName] = 1;
	}

	/**
	* Allow an attribute to be used in an HTML element
	*
	* @param string  $elName
	* @param string  $attrName
	* @param integer $flags
	*/
	public function allowAttribute($elName, $attrName, $flags = 0)
	{
		$elName   = $this->normalizeElementName($elName, true);
		$attrName = $this->normalizeAttributeName($attrName, true);
		$tagName  = $this->namespacePrefix . ':' . $elName;

		if (!($flags & self::ALLOW_UNSAFE_ATTRIBUTES))
		{
			if (substr($attrName, 0, 2) === 'on'
			 || in_array($attrName, $this->unsafeAttributes))
			{
				throw new RuntimeException("'" . $attrName . "' attributes are considered unsafe and are disabled by default. Please use the " . __CLASS__ . '::ALLOW_UNSAFE_ATTRIBUTES flag to bypass this security measure');
			}
		}

		if (!$this->configurator->attributeExists($tagName, $attrName))
		{
			$attrConf = array('required' => false);

			if (isset($this->attrFilter[$attrName]))
			{
				$attrConf['filter'] = $this->attrFilter[$attrName];
			}

			$this->configurator->addAttribute(
				$tagName,
				$attrName,
				$attrConf
			);
		}
	}

	/**
	* Return whether a name could be a valid HTML5 element name
	*
	* Does not tell whether a name is the name of a valid HTML5 element, it only checks its syntax.
	* Also, it might be slightly off as the HTML5 specs don't seem to require it to start with a
	* letter but our implementation does.
	*
	* @link http://dev.w3.org/html5/spec/syntax.html#syntax-tag-name
	*
	* @param  string $elName
	* @return bool
	*/
	protected function isValidElementName($elName)
	{
		return (bool) preg_match('#^[a-z][a-z0-9]*$#Di', $elName);
	}

	/**
	* Validate and normalize an element name
	*
	* @param  string $elName    Original element name
	* @param  bool   $mustExist If TRUE, throw an exception if the element is not allowed
	* @return string            Normalized element name, in lowercase
	*/
	protected function normalizeElementName($elName, $mustExist = true)
	{
		if (!$this->isValidElementName($elName))
		{
			throw new InvalidArgumentException ("Invalid element name '" . $elName . "'");
		}

		$elName = strtolower($elName);

		if ($mustExist && !isset($this->tags[$elName]))
		{
			throw new InvalidArgumentException("Element '" . $elName . "' does not exist");
		}

		return $elName;
	}

	/**
	* Return whether a string is a valid attribute name
	*
	* @param  string $attrName
	* @return bool
	*/
	public function isValidAttributeName($attrName)
	{
		return (bool) preg_match('#^[a-z][a-z\\-]*$#Di', $attrName);
	}

	/**
	* Validate and normalize an attribute name
	*
	* @param  string $attrName Original attribute name
	* @return string           Normalized attribute name, in lowercase
	*/
	protected function normalizeAttributeName($attrName)
	{
		if (!$this->isValidAttributeName($attrName))
		{
			throw new InvalidArgumentException ("Invalid attribute name '" . $attrName . "'");
		}

		return strtolower($attrName);
	}

	public function getConfig()
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
		$tagRegexp  = $this->configurator->getRegexpHelper()->buildRegexpFromList(array_keys($this->tags));

		$endTagRegexp   = '/(' . $tagRegexp . ')';
		$startTagRegexp = '(' . $tagRegexp . ')((?:\\s+' . $attrRegexp . ')*+)/?';

		$regexp = '#<(?:' . $endTagRegexp . '|' . $startTagRegexp . ')\\s*>#i';

		return array(
			'regexp'     => $regexp,
			'attrRegexp' => '#' . $attrRegexp . '#i',
			'prefix'     => $this->namespacePrefix,
			'uri'        => $this->namespaceURI
		);
	}

	public function getJSParser()
	{
		return file_get_contents(__DIR__ . '/RawHTMLParser.js');
	}

	public function getJSConfigMeta()
	{
		return array(
			'isGlobalRegexp' => array(
				array('attrRegexp')
			)
		);
	}
}