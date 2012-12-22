<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Items;

use InvalidArgumentException;
use s9e\TextFormatter\Configurator\Collections\AttributeCollection;
use s9e\TextFormatter\Configurator\Collections\AttributePreprocessorCollection;
use s9e\TextFormatter\Configurator\Collections\FilterChain;
use s9e\TextFormatter\Configurator\Collections\Ruleset;
use s9e\TextFormatter\Configurator\Collections\TemplateCollection;
use s9e\TextFormatter\Configurator\ConfigProvider;
use s9e\TextFormatter\Configurator\Helpers\ConfigHelper;
use s9e\TextFormatter\Configurator\Traits\Configurable;

class Tag implements ConfigProvider
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
	* @var FilterChain
	*/
	protected $filterChain;

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
	* @var TemplateCollection Templates associated with this tag
	*/
	protected $templates;

	/**
	* @param array $options This tag's options
	*/
	public function __construct(array $options = null)
	{
		$this->attributes             = new AttributeCollection;
		$this->attributePreprocessors = new AttributePreprocessorCollection;
		$this->filterChain            = new FilterChain(array('tag' => null));
		$this->rules                  = new Ruleset;
		$this->templates              = new TemplateCollection($this);

		// Start the filterChain with the default processing
		$this->filterChain->append('#executeAttributePreprocessors');
		$this->filterChain->append('#filterAttributes');

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
	* Return this tag's default template
	*
	* @return string
	*/
	public function getDefaultTemplate()
	{
		return $this->templates->get('');
	}

	/**
	* Set this tag's attribute preprocessors
	*
	* @param array|AttributePreprocessorCollection $attributePreprocessors 2D array of [attrName=>[regexp]], or an instance of AttributePreprocessorCollection
	*/
	public function setAttributePreprocessors($attributePreprocessors)
	{
		$this->attributePreprocessors->clear();
		$this->attributePreprocessors->merge($attributePreprocessors);
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
		$this->rules->clear();
		$this->rules->merge($rules);
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
	* @param array|TemplateCollection $templates
	*/
	public function setTemplates($templates)
	{
		if (!is_array($templates)
		 && !($templates instanceof TemplateCollection))
		{
			throw new InvalidArgumentException('setTemplates() expects an array or an instance of TemplateCollection');
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

	/**
	* {@inheritdoc}
	*/
	public function asConfig()
	{
		$vars = get_object_vars($this);

		// Remove properties that are not needed during parsing
		unset($vars['defaultChildRule']);
		unset($vars['defaultDescendantRule']);
		unset($vars['templates']);

		// If there are no attribute preprocessors defined, we can remove the step from this tag's
		// filterChain
		if (!count($this->attributePreprocessors))
		{
			// We operate on a copy of the filterChain, without modifying the original
			$filterChain = clone $vars['filterChain'];

			while ($filterChain->contains('#executeAttributePreprocessors'))
			{
				$filterChain->delete($filterChain->indexOf('#executeAttributePreprocessors'));
			}

			$vars['filterChain'] = $filterChain;
		}

		return ConfigHelper::toArray($vars);
	}
}