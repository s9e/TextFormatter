<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2013 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Items\AttributeFilters;

use InvalidArgumentException;
use RuntimeException;
use s9e\TextFormatter\Configurator\Helpers\RegexpBuilder;
use s9e\TextFormatter\Configurator\Items\AttributeFilter;
use s9e\TextFormatter\Configurator\Items\Regexp as RegexpObject;

class Map extends AttributeFilter
{
	/**
	* Constructor
	*
	* @param  array $map           Associative array in the form [word => replacement]
	* @param  bool  $caseSensitive Whether this map is case-sensitive
	* @param  bool  $strict        Whether this map is strict (values with no match are invalid)
	* @return void
	*/
	public function __construct(array $map = null, $caseSensitive = false, $strict = false)
	{
		parent::__construct('s9e\\TextFormatter\\Parser\\BuiltInFilters::filterMap');

		$this->resetParameters();
		$this->addParameterByName('attrValue');
		$this->addParameterByName('map');
		$this->setJS('BuiltInFilters.filterMap');

		if (isset($map))
		{
			$this->setMap($map, $caseSensitive, $strict);
		}
	}

	/**
	* {@inheritdoc}
	*/
	public function asConfig()
	{
		if (!isset($this->vars['map']))
		{
			throw new RuntimeException("Map filter is missing a 'map' value");
		}

		return parent::asConfig();
	}

	/**
	* Set the content of this map
	*
	* @param  array $map           Associative array in the form [word => replacement]
	* @param  bool  $caseSensitive Whether this map is case-sensitive
	* @param  bool  $strict        Whether this map is strict (values with no match are invalid)
	* @return void
	*/
	public function setMap(array $map, $caseSensitive = false, $strict = false)
	{
		if (!is_bool($caseSensitive))
		{
			throw new InvalidArgumentException('Argument 2 passed to ' . __METHOD__ . ' must be a boolean');
		}

		if (!is_bool($strict))
		{
			throw new InvalidArgumentException('Argument 3 passed to ' . __METHOD__ . ' must be a boolean');
		}

		// Group values by keys
		$valueKeys = [];
		foreach ($map as $key => $value)
		{
			$valueKeys[$value][] = $key;
		}

		// Now create a regexp and an entry in the map for each group
		$map = [];
		foreach ($valueKeys as $value => $keys)
		{
			$regexp = RegexpBuilder::fromList(
				$keys,
				[
					'delimiter'       => '/',
					'caseInsensitive' => !$caseSensitive
				]
			);
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

			// Add the [regexp,value] pair to the map
			$map[] = [new RegexpObject($regexp), $value];
		}

		// If the "strict" option is enabled, a catch-all regexp which replaces the value with FALSE
		// is appended to the list
		if ($strict)
		{
			$map[] = ['//', false];
		}

		// Record the map in this filter's variables
		$this->vars['map'] = $map;
	}
}