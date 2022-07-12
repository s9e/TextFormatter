<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2022 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\JavaScript;

class ConfigValue
{
	/**
	* @var bool
	*/
	protected $isDeduplicated = false;

	/**
	* @var string Name of the variable that holds this value
	*/
	protected $name;

	/**
	* @var integer Number of times this value is used or referenced
	*/
	protected $useCount = 0;

	/**
	* @var array|Code|Dictionary Original value
	*/
	protected $value;

	/**
	* @var string
	*/
	protected $varName;

	/**
	* Constructor
	*
	* @param  array|Code|Dictionary $value   Original value
	* @param  string                $varName
	*/
	public function __construct($value, $varName)
	{
		$this->value   = $value;
		$this->varName = $varName;
	}

	/**
	* Mark this value as deduplicated if it's been used more than once
	*
	* @return void
	*/
	public function deduplicate()
	{
		if ($this->useCount > 1)
		{
			$this->isDeduplicated = true;
			$this->decrementUseCount($this->useCount - 1);
		}
	}

	/**
	* Return the number of times this value has been used or referenced
	*
	* @return integer
	*/
	public function getUseCount()
	{
		return $this->useCount;
	}

	/**
	* Return the PHP value held by this instance
	*
	* @return array|Code|Dictionary
	*/
	public function getValue()
	{
		return $this->value;
	}

	/**
	* Return the variable name assigned to this value
	*
	* @return string
	*/
	public function getVarName()
	{
		return $this->varName;
	}

	/**
	* Increment the use counter
	*
	* @return void
	*/
	public function incrementUseCount()
	{
		++$this->useCount;
	}

	/**
	* Return whether this value is marked as deduplicated
	*
	* @return bool
	*/
	public function isDeduplicated()
	{
		return $this->isDeduplicated;
	}

	/**
	* Decrement the use counter of this value as well as the values it contains
	*
	* @param  integer $step How much to remove from the counter
	* @return void
	*/
	protected function decrementUseCount($step = 1)
	{
		$this->useCount -= $step;
		foreach ($this->value as $value)
		{
			if ($value instanceof ConfigValue)
			{
				$value->decrementUseCount($step);
			}
		}
	}
}