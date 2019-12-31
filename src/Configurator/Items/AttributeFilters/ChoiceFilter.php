<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2020 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Items\AttributeFilters;

use InvalidArgumentException;
use s9e\TextFormatter\Configurator\Helpers\RegexpBuilder;

class ChoiceFilter extends RegexpFilter
{
	/**
	* Constructor
	*
	* @param  array $values        List of allowed values
	* @param  bool  $caseSensitive Whether the choice is case-sensitive
	*/
	public function __construct(array $values = null, $caseSensitive = false)
	{
		parent::__construct();

		if (isset($values))
		{
			$this->setValues($values, $caseSensitive);
		}
	}

	/**
	* Set the list of allowed values
	*
	* @param  array $values        List of allowed values
	* @param  bool  $caseSensitive Whether the choice is case-sensitive
	* @return void
	*/
	public function setValues(array $values, $caseSensitive = false)
	{
		if (!is_bool($caseSensitive))
		{
			throw new InvalidArgumentException('Argument 2 passed to ' . __METHOD__ . ' must be a boolean');
		}

		// Create a regexp based on the list of allowed values
		$regexp = RegexpBuilder::fromList($values, ['delimiter' => '/']);
		$regexp = '/^' . $regexp . '$/D';

		// Add the case-insensitive flag if applicable
		if (!$caseSensitive)
		{
			$regexp .= 'i';
		}

		// Add the Unicode flag if the regexp isn't purely ASCII
		if (!preg_match('#^[[:ascii:]]*$#D', $regexp))
		{
			$regexp .= 'u';
		}

		// Set the regexp associated with this list of values
		$this->setRegexp($regexp);
	}
}