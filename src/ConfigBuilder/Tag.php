<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\ConfigBuilder;

use InvalidArgumentException,
    UnexpectedValueException;

class Tag extends ConfigObject
{
	/**
	* @var bool Whether this tag must be disabled
	*/
	protected $disable = false;

	/**
	* @var bool Whether this tag is forbidden from being used without parents
	*/
	protected $disallowAsRoot = false;

	/**
	* @var integer Maximum number of this tag per message
	*/
	protected $tagLimit = 100;

	/**
	* @var integer Maximum nesting level for this tag
	*/
	protected $nestingLimit = 10;

	/**
	* @var string Default rule governing this tag's childen
	*/
	protected $defaultChildRule = 'allow';

	/**
	* @var string Default rule governing this tag's descendants
	*/
	protected $defaultDescendantRule = 'allow';

	/**
	* @var array Rules associated with this tag
	*/
	protected $rules = array();

	/**
	* @var array Array of Attribute objects
	*/
	protected $attributes = array();

	/**
	* @var array Templates associated with this tag (predicate => template)
	*/
	protected $templates = array();

	/**
	* @param array $options This tag's options
	*/
	public function __construct(array $options = array())
	{
		$this->setOptions($options);
	}

	/**
	* Return whether a string is a valid tag name
	*
	* @param  string $name
	* @return bool
	*/
	static public function isValidName($name)
	{
		return (bool) preg_match('#^[a-z_][a-z_0-9]*$#Di', $tagName);
	}

	/**
	* Validate and normalize a tag name
	*
	* Non-namespaced tags are uppercased, namespaced tags' names are left intact
	*
	* @param  string $name Original tag name
	* @return string       Normalized tag name
	*/
	static public function normalizeName($name)
	{
		if (!self::isValidTagName($name))
		{
			throw new InvalidArgumentException ("Invalid tag name '" . $name . "'");
		}

		if (strpos($name, ':') === false)
		{
			$name = strtoupper($name);
		}

		return $name;
	}

	//==========================================================================
	// Attributes
	//==========================================================================

	/**
	* Set all the attributes for this tag
	*
	* NOTE: will remove all other attributes
	*
	* @param array $attributes Associative array of attributes, using their name as key
	*/
	public function setAttributes(array $attributes)
	{
		$this->clearAttributes();

		foreach ($attributes as $attrName => $attribute)
		{
			if (!($attribute instanceof Attribute))
			{
				$attribute = new Attribute($attribute);
			}

			$this->addAttribute($attrName, $attribute);
		}
	}

	/**
	* Add an attribute to this tag
	*
	* @param  string    $attrName  Name of the attribute
	* @param  Attribute $attribute Attribute to add (if not set, a new instance will be created)
	* @return Attribute            Added attribute
	*/
	public function addAttribute($attrName, Attribute $attribute = null)
	{
		if (!isset($attribute))
		{
			$attribute = new Attribute;
		}

		$attrName = $attribute::normalizeName($attrName);

		if (isset($this->attributes[$attrName]))
		{
			throw new InvalidArgumentException("Attribute '" . $attrName . "' already exists");
		}

		$this->attributes[$attrName] = $attribute;

		return $attribute;
	}

	/**
	* Return whether an attribute exists
	*
	* @param  string    $attrName Attribute's name
	* @return bool
	*/
	public function hasAttribute($attrName)
	{
		$attrName = Attribute::normalizeName($attrName);

		return isset($this->attributes[$attrName]);
	}

	/**
	* Return an attribute
	*
	* @param  string    $attrName Attribute's name
	* @return Attribute
	*/
	public function getAttribute($attrName)
	{
		$attrName = Attribute::normalizeName($attrName);

		if (!isset($this->attributes[$attrName]))
		{
			throw new InvalidArgumentException("Attribute '" . $attrName . "' does not exist");
		}

		return $this->attributes[$attrName];
	}

	/**
	* Return all attributes
	*
	* @return array
	*/
	public function getAttributes()
	{
		return $this->attributes;
	}

	/**
	* Remove an attribute
	*
	* @param  string $attrName Attribute's name
	*/
	public function removeAttribute($attrName)
	{
		$attrName = Attribute::normalizeName($attrName);

		unset($this->attributes[$attrName]);
	}

	/**
	* Remove all attributes from this tag
	*/
	public function clearAttributes()
	{
		$this->attributes = array();
	}

	//==========================================================================
	// Attribute parsers
	//==========================================================================

	/**
	* Set all the attribute parsers for this tag
	*
	* NOTE: will remove all other attribute parsers
	*
	* @param array $attributeParsers Associative array of attribute parsers, using their name as key
	*/
	public function setAttributeParsers(array $attributeParsers)
	{
		$this->clearAttributeParsers();

		foreach ($attributeParsers as $attrName => $attributeParser)
		{
			if (!($attributeParser instanceof AttributeParser))
			{
				$attributeParser = new Attribute($attributeParser);
			}

			$this->addAttributeParser($attrName, $attributeParser);
		}
	}

	/**
	* Add an attribute parser to this tag
	*
	* @param  string          $attrName        Name of the attribute parser
	* @param  AttributeParser $attributeParser AttributeParser to add (if not set, a new instance
	*                                          will be created)
	* @return AttributeParser                  Added attribute parser
	*/
	public function addAttributeParser($attrName, AttributeParser $attributeParser = null)
	{
		if (!isset($attributeParser))
		{
			$attributeParser = new AttributeParser;
		}

		$attributeParser = $attributeParser::normalizeName($attrName);

		if (isset($this->attributeParsers[$attrName]))
		{
			throw new InvalidArgumentException("Attribute parser '" . $attrName . "' already exists");
		}

		return $this->attributeParsers[$attrName] = $attributeParser;
	}

	/**
	* Return whether an attribute parser exists
	*
	* @param  string    $attrName Attribute parser's name
	* @return bool
	*/
	public function hasAttributeParser($attrName)
	{
		$attrName = AttributeParser::normalizeName($attrName);

		return isset($this->attributeParsers[$attrName]);
	}

	/**
	* Return an attribute parser
	*
	* @param  string          $attrName Attribute parser's name
	* @return AttributeParser
	*/
	public function getAttributeParser($attrName)
	{
		$attrName = AttributeParser::normalizeName($attrName);

		if (!isset($this->attributeParsers[$attrName]))
		{
			throw new InvalidArgumentException("Attribute parser '" . $attrName . "' does not exist");
		}

		return $this->attributeParsers[$attrName];
	}

	/**
	* Return all attribute parsers
	*
	* @return array
	*/
	public function getAttributeParsers()
	{
		return $this->attributeParsers;
	}

	/**
	* Remove an attribute parser
	*
	* @param  string $attrName Attribute parser's name
	*/
	public function removeAttributeParser($attrName)
	{
		$attrName = AttributeParser::normalizeName($attrName);

		unset($this->attributeParsers[$attrName]);
	}

	//==========================================================================
	// Rules-related methods
	//==========================================================================

	/**
	* Define a rule
	*
	* The target tag doesn't have to exist at the time of creation.
	*
	* @param string $action Rule action
	* @param string $target Name of the target tag
	*/
	public function addRule($action, $target)
	{
		if (!in_array($action, array(
			'allowChild',
			'allowDescendant',
			'closeParent',
			'closeAncestor',
			'denyChild',
			'denyDescendant',
			'reopenChild',
			'requireParent',
			'requireAncestor'
		), true))
		{
			throw new UnexpectedValueException("Unknown rule action '" . $action . "'");
		}

		$target = self::normalizeName($target);
		$this->rules[$action][$target] = $target;
	}

	/**
	* Return all the Rules
	*
	* @return array
	*/
	public function getRules()
	{
		return $this->rules;
	}

	/**
	* Remove a rule
	*
	* @param string $action
	* @param string $target
	*/
	public function removeRule($tagName, $action, $target)
	{
		$target = self::normalizeName($target);

		unset($this->rules[$action][$target]);
	}

	//==========================================================================
	// Templates-related methods
	//==========================================================================

	/**
	* Remove all templates associated with this tag
	*/
	public function clearTemplates()
	{
		$this->templates = array();
	}

	/**
	* Set all templates associated with this tag
	*
	* NOTE: will remove all other templates
	*
	* @param array $templates
	*/
	public function setTemplates(array $templates)
	{
		$this->clearTemplates();

		foreach ($templates as $predicate => $template)
		{
			$this->setTemplate($template, $predicate);
		}
	}

	/**
	* Set a template for this tag
	*
	* @param string $template  XSL template
	* @param string $predicate Predicate under which this template applies
	*/
	public function setTemplate($template, $predicate = null)
	{
		$this->templates[$predicate] = $this->normalizeTemplate($template, true);
	}

	/**
	* Set a potentially unsafe template for this tag
	*
	* @param string $template  XSL template
	* @param string $predicate Predicate under which this template applies
	*/
	public function setUnsafeTemplate($template, $predicate = null)
	{
		$this->templates[$predicate] = $this->normalizeTemplate($template, false);
	}

	/**
	* Normalize the content of a template
	*
	* Will optimize the template's content and optionally check for unsafe markup.
	*
	* @param  string $template    Original template
	* @param  bool   $checkUnsafe Whether to check the template for unsafe markup
	* @return string              Normalized template
	*/
	protected function normalizeTemplate($template, $checkUnsafe)
	{
		$template = TemplateHelper::optimizeTemplate($template);

		if ($checkUnsafe)
		{
			$isUnsafe = TemplateHelper::checkUnsafe($template, $this);

			if ($isUnsafe)
			{
				throw new RuntimeException($isUnsafe);
			}
		}
	}
}