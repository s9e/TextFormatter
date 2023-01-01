<?php declare(strict_types=1);

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Items\AttributeFilters;

use RuntimeException;
use s9e\TextFormatter\Configurator\Helpers\ContextSafeness;
use s9e\TextFormatter\Configurator\Items\AttributeFilter;

abstract class AbstractMapFilter extends AttributeFilter
{
	/**
	* {@inheritdoc}
	*/
	public function asConfig()
	{
		if (!isset($this->vars['map']))
		{
			$name = preg_replace('(.*\\\\|Filter$)', '', get_class($this));

			throw new RuntimeException($name . " filter is missing a 'map' value");
		}

		return parent::asConfig();
	}

	/**
	* Assess the safeness of this attribute filter based on given list of strings
	*
	* @param  string[] $strings
	* @return void
	*/
	protected function assessSafeness(array $strings): void
	{
		$str = implode('', $strings);
		foreach (['AsURL', 'InCSS', 'InJS'] as $context)
		{
			$callback = ContextSafeness::class . '::getDisallowedCharacters' . $context;
			foreach ($callback() as $char)
			{
				if (strpos($str, $char) !== false)
				{
					continue 2;
				}
			}

			$methodName = 'markAsSafe' . $context;
			$this->$methodName();
		}
	}
}