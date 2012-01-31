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
	* @var Collection This tag's attributes
	*/
	protected $attributes;

	/**
	* @var Collection This tag's attribute parsers
	*/
	protected $attributeParsers;

	/**
	* @var Ruleset Rules associated with this tag
	*/
	protected $rules;

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
	* @var array Templates associated with this tag (predicate => template)
	*/
	protected $templates = array();

	/**
	* @param array $options This tag's options
	*/
	public function __construct(array $options = array())
	{
		$this->attributes       = new Collection(__NAMESPACE__ . '\\Attribute');
		$this->attributeParsers = new Collection(__NAMESPACE__ . '\\AttributeParser');
		$this->rules            = new Ruleset;

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
		return (bool) preg_match('#^(?:[a-z_][a-z_0-9]*:)?[a-z_][a-z_0-9]*$#Di', $name);
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
		if (!self::isValidName($name))
		{
			throw new InvalidArgumentException ("Invalid tag name '" . $name . "'");
		}

		if (strpos($name, ':') === false)
		{
			$name = strtoupper($name);
		}

		return $name;
	}

	/**
	* Set the default child rule
	*
	* @param string $rule Either "allow" or "deny"
	*/
	public function setDefaultChildRule($rule)
	{
		if ($rule !== 'allow' && $rule !== 'deny')
		{
			throw new InvalidArgumentException("defaultChildRule must be either 'allow' or 'deny'");
		}

		$this->defaultChildRule = $rule;
	}

	/**
	* Set the default descendant rule
	*
	* @param string $rule Either "allow" or "deny"
	*/
	public function setDefaultDescendantRule($rule)
	{
		if ($rule !== 'allow' && $rule !== 'deny')
		{
			throw new InvalidArgumentException("defaultDescendantRule must be either 'allow' or 'deny'");
		}

		$this->defaultDescendantRule = $rule;
	}

	/**
	* Set this tag's nestingLimit
	*
	* @param integer $limit
	*/
	public function setNestingLimit($limit)
	{
		$limit = filter_var($limit, FILTER_VALIDATE_INT, array(
			'options' => array('min_range' => 0)
		));

		if (!$limit)
		{
			throw new InvalidArgumentException('nestingLimit must be a number greater than 0');
		}

		$this->nestingLimit = $limit;
	}

	/**
	* Set this tag's tagLimit
	*
	* @param integer $limit
	*/
	public function setTagLimit($limit)
	{
		$limit = filter_var($limit, FILTER_VALIDATE_INT, array(
			'options' => array('min_range' => 0)
		));

		if (!$limit)
		{
			throw new InvalidArgumentException('tagLimit must be a number greater than 0');
		}

		$this->tagLimit = $limit;
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
			$unsafeMsg = TemplateHelper::checkUnsafe($template, $this);

			if ($unsafeMsg)
			{
				throw new RuntimeException($unsafeMsg);
			}
		}
	}
}