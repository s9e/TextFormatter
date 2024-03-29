<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Items\AttributeFilters;

use InvalidArgumentException;
use RuntimeException;
use s9e\TextFormatter\Configurator\Items\AttributeFilter;

class RangeFilter extends AttributeFilter
{
	/**
	* Constructor
	*
	* @param  integer $min Minimum value for this range
	* @param  integer $max Maximum value for this range
	*/
	public function __construct($min = null, $max = null)
	{
		parent::__construct('s9e\\TextFormatter\\Parser\\AttributeFilters\\NumericFilter::filterRange');

		$this->resetParameters();
		$this->addParameterByName('attrValue');
		$this->addParameterByName('min');
		$this->addParameterByName('max');
		$this->addParameterByName('logger');
		$this->setJS('NumericFilter.filterRange');
		$this->markAsSafeAsURL();
		$this->markAsSafeInCSS();
		$this->markAsSafeInJS();

		if (isset($min))
		{
			$this->setRange($min, $max);
		}
	}

	/**
	* {@inheritdoc}
	*/
	public function asConfig()
	{
		if (!isset($this->vars['min']))
		{
			throw new RuntimeException("Range filter is missing a 'min' value");
		}

		if (!isset($this->vars['max']))
		{
			throw new RuntimeException("Range filter is missing a 'max' value");
		}

		return parent::asConfig();
	}

	/**
	* Set the allowed range of values
	*
	* @param  integer $min Minimum value
	* @param  integer $max Maximum value
	* @return void
	*/
	public function setRange($min, $max)
	{
		$min = filter_var($min, FILTER_VALIDATE_INT);
		$max = filter_var($max, FILTER_VALIDATE_INT);

		if ($min === false)
		{
			throw new InvalidArgumentException('Argument 1 passed to ' . __METHOD__ . ' must be an integer');
		}

		if ($max === false)
		{
			throw new InvalidArgumentException('Argument 2 passed to ' . __METHOD__ . ' must be an integer');
		}

		if ($min > $max)
		{
			throw new InvalidArgumentException('Invalid range: min (' . $min . ') > max (' . $max . ')');
		}

		$this->vars['min'] = $min;
		$this->vars['max'] = $max;
	}
}