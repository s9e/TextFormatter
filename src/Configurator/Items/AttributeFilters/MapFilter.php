<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2020 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Items\AttributeFilters;

use InvalidArgumentException;
use RuntimeException;
use s9e\TextFormatter\Configurator\Helpers\ContextSafeness;
use s9e\TextFormatter\Configurator\Helpers\RegexpBuilder;
use s9e\TextFormatter\Configurator\Items\AttributeFilter;
use s9e\TextFormatter\Configurator\Items\Regexp;

class MapFilter extends AttributeFilter
{
	/**
	* Constructor
	*
	* @param  array $map           Associative array in the form [word => replacement]
	* @param  bool  $caseSensitive Whether this map is case-sensitive
	* @param  bool  $strict        Whether this map is strict (values with no match are invalid)
	*/
	public function __construct(array $map = null, $caseSensitive = false, $strict = false)
	{
		parent::__construct('s9e\\TextFormatter\\Parser\\AttributeFilters\\MapFilter::filter');

		$this->resetParameters();
		$this->addParameterByName('attrValue');
		$this->addParameterByName('map');
		$this->setJS('MapFilter.filter');

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

		// Reset the template safeness marks for the new map
		$this->resetSafeness();

		// If the map is strict, we can assess its safeness
		if ($strict)
		{
			$this->assessSafeness($map);
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
			$map[] = [new Regexp($regexp), $value];
		}

		// If the "strict" option is enabled, a catch-all regexp which replaces the value with FALSE
		// is appended to the list
		if ($strict)
		{
			$map[] = [new Regexp('//'), false];
		}

		// Record the map in this filter's variables
		$this->vars['map'] = $map;
	}

	/**
	* Assess the safeness of given map in contexts
	*
	* @param  array $map
	* @return void
	*/
	protected function assessSafeness(array $map)
	{
		// Concatenate the values so we can check them as a single string
		$values = implode('', $map);

		// Test whether the values contain any character that's disallowed in CSS
		$isSafeInCSS = true;
		foreach (ContextSafeness::getDisallowedCharactersInCSS() as $char)
		{
			if (strpos($values, $char) !== false)
			{
				$isSafeInCSS = false;
				break;
			}
		}
		if ($isSafeInCSS)
		{
			$this->markAsSafeInCSS();
		}

		// Test whether the values contain any character that's disallowed in JS
		$isSafeInJS = true;
		foreach (ContextSafeness::getDisallowedCharactersInJS() as $char)
		{
			if (strpos($values, $char) !== false)
			{
				$isSafeInJS = false;
				break;
			}
		}
		if ($isSafeInJS)
		{
			$this->markAsSafeInJS();
		}
	}
}