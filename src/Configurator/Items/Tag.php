<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Items;
use InvalidArgumentException;
use RuntimeException;
use Traversable;
use s9e\TextFormatter\Configurator\Collections\AttributeCollection;
use s9e\TextFormatter\Configurator\Collections\AttributePreprocessorCollection;
use s9e\TextFormatter\Configurator\Collections\Collection;
use s9e\TextFormatter\Configurator\Collections\NormalizedCollection;
use s9e\TextFormatter\Configurator\Collections\Ruleset;
use s9e\TextFormatter\Configurator\Collections\TagFilterChain;
use s9e\TextFormatter\Configurator\ConfigProvider;
use s9e\TextFormatter\Configurator\Helpers\ConfigHelper;
use s9e\TextFormatter\Configurator\Items\Template;
use s9e\TextFormatter\Configurator\Traits\Configurable;
class Tag implements ConfigProvider
{
	public function __get($propName)
	{
		$methodName = 'get' . \ucfirst($propName);
		if (\method_exists($this, $methodName))
			return $this->$methodName();
		if (!\property_exists($this, $propName))
			throw new RuntimeException("Property '" . $propName . "' does not exist");
		return $this->$propName;
	}
	public function __set($propName, $propValue)
	{
		$methodName = 'set' . \ucfirst($propName);
		if (\method_exists($this, $methodName))
		{
			$this->$methodName($propValue);
			return;
		}
		if (!isset($this->$propName))
		{
			$this->$propName = $propValue;
			return;
		}
		if ($this->$propName instanceof NormalizedCollection)
		{
			if (!\is_array($propValue) && !($propValue instanceof Traversable))
				throw new InvalidArgumentException("Property '" . $propName . "' expects an array or a traversable object to be passed");
			$this->$propName->clear();
			foreach ($propValue as $k => $v)
				$this->$propName->set($k, $v);
			return;
		}
		if (\is_object($this->$propName))
		{
			if (!($propValue instanceof $this->$propName))
				throw new InvalidArgumentException("Cannot replace property '" . $propName . "' of class '" . \get_class($this->$propName) . "' with instance of '" . \get_class($propValue) . "'");
		}
		else
		{
			$oldType = \gettype($this->$propName);
			$newType = \gettype($propValue);
			if ($oldType === 'boolean' && \preg_match('(^(?:fals|tru)e$)', $propValue))
			{
				$newType   = 'boolean';
				$propValue = ($propValue === 'true');
			}
			if ($oldType !== $newType)
			{
				$tmp = $propValue;
				\settype($tmp, $oldType);
				\settype($tmp, $newType);
				if ($tmp !== $propValue)
					throw new InvalidArgumentException("Cannot replace property '" . $propName . "' of type " . $oldType . ' with value of type ' . $newType);
				\settype($propValue, $oldType);
			}
		}
		$this->$propName = $propValue;
	}
	public function __isset($propName)
	{
		$methodName = 'isset' . \ucfirst($propName);
		if (\method_exists($this, $methodName))
			return $this->$methodName();
		return isset($this->$propName);
	}
	public function __unset($propName)
	{
		$methodName = 'unset' . \ucfirst($propName);
		if (\method_exists($this, $methodName))
			$this->$methodName();
		elseif (isset($this->$propName))
			if ($this->$propName instanceof Collection)
				$this->$propName->clear();
			else
				throw new RuntimeException("Property '" . $propName . "' cannot be unset");
	}
	protected $attributes;
	protected $attributePreprocessors;
	protected $filterChain;
	protected $nestingLimit = 10;
	protected $rules;
	protected $tagLimit = 5000;
	protected $template;
	public function __construct(array $options = \null)
	{
		$this->attributes             = new AttributeCollection;
		$this->attributePreprocessors = new AttributePreprocessorCollection;
		$this->filterChain            = new TagFilterChain;
		$this->rules                  = new Ruleset;
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
			\ksort($options);
			foreach ($options as $optionName => $optionValue)
				$this->__set($optionName, $optionValue);
		}
	}
	public function asConfig()
	{
		$vars = \get_object_vars($this);
		unset($vars['template']);
		if (!\count($this->attributePreprocessors))
		{
			$callback = 's9e\\TextFormatter\\Parser\\FilterProcessing::executeAttributePreprocessors';
			$filterChain = clone $vars['filterChain'];
			$i = \count($filterChain);
			while (--$i >= 0)
				if ($filterChain[$i]->getCallback() === $callback)
					unset($filterChain[$i]);
			$vars['filterChain'] = $filterChain;
		}
		return ConfigHelper::toArray($vars) + array('attributes' => array(), 'filterChain' => array());
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