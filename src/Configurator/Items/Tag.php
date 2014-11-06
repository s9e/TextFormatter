<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2014 The s9e Authors
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

class Tag implements ConfigProvider
{
	use Configurable;

	protected $attributes;

	protected $attributePreprocessors;

	protected $filterChain;

	protected $nestingLimit = 10;

	protected $rules;

	protected $tagLimit = 1000;

	protected $template;

	public function __construct(array $options = \null)
	{
		$this->attributes             = new AttributeCollection;
		$this->attributePreprocessors = new AttributePreprocessorCollection;
		$this->filterChain            = new TagFilterChain;
		$this->rules                  = new Ruleset;

		$this->filterChain->append('s9e\\TextFormatter\\Parser::executeAttributePreprocessors')
		                  ->addParameterByName('tag')
		                  ->addParameterByName('tagConfig');

		$this->filterChain->append('s9e\\TextFormatter\\Parser::filterAttributes')
		                  ->addParameterByName('tag')
		                  ->addParameterByName('tagConfig')
		                  ->addParameterByName('registeredVars')
		                  ->addParameterByName('logger');

		if (isset($options))
		{
			\ksort($options);

			foreach ($options as $optionName => $optionValue)
				$this->__set($optionName, $optionValue);
		}
	}

	public function asConfig()
	{
		$vars = \get_object_vars($this);

		unset($vars['defaultChildRule']);
		unset($vars['defaultDescendantRule']);
		unset($vars['template']);

		if (!\count($this->attributePreprocessors))
		{
			$callback = 's9e\\TextFormatter\\Parser::executeAttributePreprocessors';

			$filterChain = clone $vars['filterChain'];

			$i = \count($filterChain);
			while (--$i >= 0)
				if ($filterChain[$i]->getCallback() === $callback)
					unset($filterChain[$i]);

			$vars['filterChain'] = $filterChain;
		}

		return ConfigHelper::toArray($vars);
	}

	public function getTemplate()
	{
		return $this->template;
	}

	public function issetTemplate()
	{
		return isset($this->template);
	}

	public function setAttributePreprocessors($attributePreprocessors)
	{
		$this->attributePreprocessors->clear();
		$this->attributePreprocessors->merge($attributePreprocessors);
	}

	public function setNestingLimit($limit)
	{
		$limit = (int) $limit;

		if ($limit < 1)
			throw new InvalidArgumentException('nestingLimit must be a number greater than 0');

		$this->nestingLimit = $limit;
	}

	public function setRules($rules)
	{
		$this->rules->clear();
		$this->rules->merge($rules);
	}

	public function setTagLimit($limit)
	{
		$limit = (int) $limit;

		if ($limit < 1)
			throw new InvalidArgumentException('tagLimit must be a number greater than 0');

		$this->tagLimit = $limit;
	}

	public function setTemplate($template)
	{
		if (!($template instanceof Template))
			$template = new Template($template);

		$this->template = $template;
	}

	public function unsetTemplate()
	{
		unset($this->template);
	}
}