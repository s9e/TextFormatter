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
use s9e\TextFormatter\Configurator\Items\Variant;
use s9e\TextFormatter\Configurator\JavaScript\RegexpConvertor;

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

		// Create a JS variant for the map
		$jsMap = array();
		foreach ($this->vars['map'] as $entry)
		{
			list($regexp, $replacement) = $entry;

			$jsMap[] = array(
				RegexpConvertor::toJS($regexp),
				$replacement
			);
		}

		$variant = new Variant($this->vars['map']);
		$variant->set('JS', $jsMap);

		// Temporarily replace the map with variant
		$map = $this->vars['map'];
		$this->vars['map'] = $variant;

		// Generate the config
		$config = parent::asConfig();

		// Restore the map
		$this->vars['map'] = $map;

		return $config;
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
		$valueKeys = array();
		foreach ($map as $key => $value)
		{
			// Lowercase latin letters if the map is not case-sensitive
			if (!$caseSensitive)
			{
				$value = strtr(
					$value,
					'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
					'abcdefghijklmnopqrstuvwxyz'
				);
			}

			$valueKeys[$value][] = $key;
		}

		// Now create a regexp and an entry in the map for each group
		$map = array();
		foreach ($valueKeys as $value => $keys)
		{
			$regexp = RegexpBuilder::fromList($keys, array('delimiter' => '/'));
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
			$map[] = array($regexp, $value);
		}

		// If the "strict" option is enabled, a catch-all regexp which replaces the value with FALSE
		// is appended to the list
		if ($strict)
		{
			$map[] = array('//', false);
		}

		// Record the map in this filter's variables
		$this->vars['map'] = $map;
	}
}