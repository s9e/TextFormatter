<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\ConfigBuilder\Filters;

use InvalidArgumentException;
use RuntimeException;

class Range
{
	/**
	* @var float
	*/
	protected $min;

	/**
	* @var float
	*/
	protected $max;

	/**
	* @var float
	*/
	protected $step;

	public function check()
	{
		if (!isset($this->min, $this->max))
		{
			throw new RuntimeException("Both 'min' and 'max' must be set");
		}

		if ($this->min > $this->max)
		{
			throw new RuntimeException("The 'min' value cannot be greater than the 'max' value");
		}
	}

	public function setMin($min)
	{
		if (filter_var($min, FILTER_VALIDATE_FLOAT) === false)
		{
			throw new InvalidArgumentException;
		}

		$this->min = $min;
	}

	public function setMax($max)
	{
		if (filter_var($max, FILTER_VALIDATE_FLOAT) === false)
		{
			throw new InvalidArgumentException;
		}

		$this->max = $max;
	}

	public function setStep($step)
	{
		if (filter_var($step, FILTER_VALIDATE_FLOAT) === false
		 || $step <= 0)
		{
			throw new InvalidArgumentException;
		}

		$this->step = $step;
	}
}