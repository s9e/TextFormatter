<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\ConfigBuilder\Items;

use InvalidArgumentException;
use s9e\TextFormatter\ConfigBuilder\Collections\AttributeCollection;
use s9e\TextFormatter\ConfigBuilder\Collections\AttributePreprocessorCollection;
use s9e\TextFormatter\ConfigBuilder\Collections\Ruleset;
use s9e\TextFormatter\ConfigBuilder\Collections\Templateset;
use s9e\TextFormatter\ConfigBuilder\Traits\Configurable;

class Tag
{
	use Configurable;

	/**
	* @var AttributeCollection This tag's attributes
	*/
	protected $attributes;

	/**
	* @var AttributePreprocessorCollection This tag's attribute parsers
	*/
	protected $attributePreprocessors;

	/**
	* @var string Default rule governing this tag's childen
	*/
	protected $defaultChildRule = 'allow';

	/**
	* @var string Default rule governing this tag's descendants
	*/
	protected $defaultDescendantRule = 'allow';

	/**
	* @var 
	* @todo implement that better. (note: needed to cover HTML5 rules with a non-div root, e.g. <a>)
	*/
	protected $disallowAsRoot = false;

	/**
	* @var integer Maximum nesting level for this tag
	*/
	protected $nestingLimit = 10;

	/**
	* @var Ruleset Rules associated with this tag
	*/
	protected $rules;

	/**
	* @var integer Maximum number of this tag per message
	*/
	protected $tagLimit = 100;

	/**
	* @var Templateset Templates associated with this tag
	*/
	protected $templates;

	/**
	* @param array $options This tag's options
	*/
	public function __construct(array $options = null)
	{
		$this->attributes             = new AttributeCollection;
		$this->attributePreprocessors = new AttributePreprocessorCollection;
		$this->rules                  = new Ruleset;
		$this->templates              = new Templateset($this);

		if (isset($options))
		{
			// Sort the options by name so that attributes are set before templates, which is
			// necessary to evaluate whether the templates are safe
			ksort($options);

			foreach ($options as $optionName => $optionValue)
			{
				$this->__set($optionName, $optionValue);
			}
		}
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
	* Set this tag's rules
	*
	* @param array|Ruleset $rules 2D array of rule definitions, or instance of Ruleset
	*/
	public function setRules($rules)
	{
		if (!is_array($rules)
		 && !($rules instanceof Ruleset))
		{
			throw new InvalidArgumentException('setRules() expects an array or an instance of Ruleset');
		}

		$this->rules->clear();

		foreach ($rules as $action => $tagNames)
		{
			foreach ($tagNames as $tagName)
			{
				$this->rules->$action($tagName);
			}
		}
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

	/**
	* Set all templates associated with this tag
	*
	* @param array|Templateset $templates
	*/
	public function setTemplates($templates)
	{
		if (!is_array($templates)
		 && !($templates instanceof Templateset))
		{
			throw new InvalidArgumentException('setTemplates() expects an array or an instance of Templateset');
		}

		$this->templates->clear();

		foreach ($templates as $predicate => $template)
		{
			$this->templates->set($predicate, $template);
		}
	}

	/**
	* Set the default template for this tag
	*
	* @param string $template
	*/
	public function setDefaultTemplate($template)
	{
		$this->templates->set('', $template);
	}
}