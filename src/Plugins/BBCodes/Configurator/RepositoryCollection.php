<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2021 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\BBCodes\Configurator;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Configurator\Collections\NormalizedCollection;

class RepositoryCollection extends NormalizedCollection
{
	/**
	* @var BBCodeMonkey Instance of BBCodeMonkey passed to new Repository instances
	*/
	protected $bbcodeMonkey;

	/**
	* Constructor
	*
	* @param  BBCodeMonkey $bbcodeMonkey Instance of BBCodeMonkey used to parse definitions
	*/
	public function __construct(BBCodeMonkey $bbcodeMonkey)
	{
		$this->bbcodeMonkey = $bbcodeMonkey;
	}

	/**
	* Normalize a value for storage
	*
	* @param  mixed      $value Original value
	* @return Repository        Normalized value
	*/
	public function normalizeValue($value)
	{
		return ($value instanceof Repository)
		     ? $value
		     : new Repository($value, $this->bbcodeMonkey);
	}
}