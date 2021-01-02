<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2021 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Items;

use InvalidArgumentException;
use s9e\TextFormatter\Configurator\Collections\AttributeCollection;
use s9e\TextFormatter\Configurator\Collections\AttributePreprocessorCollection;
use s9e\TextFormatter\Configurator\Collections\Ruleset;
use s9e\TextFormatter\Configurator\Collections\TagFilterChain;
use s9e\TextFormatter\Configurator\ConfigProvider;
use s9e\TextFormatter\Configurator\Helpers\ConfigHelper;
use s9e\TextFormatter\Configurator\Items\Template;
use s9e\TextFormatter\Configurator\Traits\Configurable;

/**
* @property AttributeCollection $attributes This tag's attributes
* @property AttributePreprocessorCollection $attributePreprocessors This tag's attribute parsers
* @property TagFilterChain $filterChain This tag's filter chain
* @property integer $nestingLimit Maximum nesting level for this tag
* @property Ruleset $rules Rules associated with this tag
* @property integer $tagLimit Maximum number of this tag per message
* @property-read Template $template Template associated with this tag
* @property-write string|Template $template Template associated with this tag
*/
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
	* @var TagFilterChain This tag's filter chain
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
	protected $tagLimit = 5000;

	/**
	* @var Template Template associated with this tag
	*/
	protected $template;

	/**
	* Constructor
	*
	* @param  array $options This tag's options
	*/
	public function __construct(array $options = null)
	{
		$this->attributes             = new AttributeCollection;
		$this->attributePreprocessors = new AttributePreprocessorCollection;
		$this->filterChain            = new TagFilterChain;
		$this->rules                  = new Ruleset;

		// Start the filterChain with the default processing
		$this->filterChain->append('s9e\\TextFormatter\\Parser\\FilterProcessing::executeAttributePreprocessors')
		                  ->addParameterByName('tagConfig')
		                  ->setJS('executeAttributePreprocessors');

		$this->filterChain->append('s9e\\TextFormatter\\Parser\\FilterProcessing::filterAttributes')
		                  ->addParameterByName('tagConfig')
		                  ->addParameterByName('registeredVars')
		                  ->addParameterByName('logger')
		                  ->setJS('filterAttributes');

		if (isset($options))
		{
			// Sort the options by name so that attributes are set before the template, which is
			// necessary to evaluate whether the template is safe
			ksort($options);

			foreach ($options as $optionName => $optionValue)
			{
				$this->__set($optionName, $optionValue);
			}
		}
	}

	/**
	* {@inheritdoc}
	*/
	public function asConfig()
	{
		$vars = get_object_vars($this);

		// Remove properties that are not needed during parsing
		unset($vars['template']);

		// If there are no attribute preprocessors defined, we can remove the step from this tag's
		// filterChain
		if (!count($this->attributePreprocessors))
		{
			$callback = 's9e\\TextFormatter\\Parser\\FilterProcessing::executeAttributePreprocessors';

			// We operate on a copy of the filterChain, without modifying the original
			$filterChain = clone $vars['filterChain'];

			// Process the chain in reverse order so that we don't skip indices
			$i = count($filterChain);
			while (--$i >= 0)
			{
				if ($filterChain[$i]->getCallback() === $callback)
				{
					unset($filterChain[$i]);
				}
			}

			$vars['filterChain'] = $filterChain;
		}

		return ConfigHelper::toArray($vars) + ['attributes' => [], 'filterChain' => []];
	}

	/**
	* Return this tag's template
	*
	* @return Template
	*/
	public function getTemplate()
	{
		return $this->template;
	}

	/**
	* Test whether this tag has a template
	*
	* @return bool
	*/
	public function issetTemplate()
	{
		return isset($this->template);
	}

	/**
	* Set this tag's attribute preprocessors
	*
	* @param  array|AttributePreprocessorCollection $attributePreprocessors 2D array of [attrName=>[regexp]], or an instance of AttributePreprocessorCollection
	* @return void
	*/
	public function setAttributePreprocessors($attributePreprocessors)
	{
		$this->attributePreprocessors->clear();
		$this->attributePreprocessors->merge($attributePreprocessors);
	}

	/**
	* Set this tag's nestingLimit
	*
	* @param  integer $limit
	* @return void
	*/
	public function setNestingLimit($limit)
	{
		$limit = (int) $limit;

		if ($limit < 1)
		{
			throw new InvalidArgumentException('nestingLimit must be a number greater than 0');
		}

		$this->nestingLimit = $limit;
	}

	/**
	* Set this tag's rules
	*
	* @param  array|Ruleset $rules 2D array of rule definitions, or instance of Ruleset
	* @return void
	*/
	public function setRules($rules)
	{
		$this->rules->clear();
		$this->rules->merge($rules);
	}

	/**
	* Set this tag's tagLimit
	*
	* @param  integer $limit
	* @return void
	*/
	public function setTagLimit($limit)
	{
		$limit = (int) $limit;

		if ($limit < 1)
		{
			throw new InvalidArgumentException('tagLimit must be a number greater than 0');
		}

		$this->tagLimit = $limit;
	}

	/**
	* Set the template associated with this tag
	*
	* @param  string|Template $template
	* @return void
	*/
	public function setTemplate($template)
	{
		if (!($template instanceof Template))
		{
			$template = new Template($template);
		}

		$this->template = $template;
	}

	/**
	* Unset this tag's template
	*
	* @return void
	*/
	public function unsetTemplate()
	{
		unset($this->template);
	}
}