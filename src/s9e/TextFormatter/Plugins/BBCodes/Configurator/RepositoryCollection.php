<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2013 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\BBCodes\Configurator;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Configurator\Collections\NormalizedCollection;

class RepositoryCollection extends NormalizedCollection
{
	/**
	* @var Configurator Instance of Configurator
	*/
	protected $configurator;

	/**
	* Constructor
	*
	* @param  Configurator $configurator Instance of Configurator
	* @return void
	*/
	public function __construct(Configurator $configurator)
	{
		$this->configurator = $configurator;
	}

	/**
	* Normalize a value for storage
	*
	* @param  mixed $value Original value
	* @return mixed        Normalized value
	*/
	public function normalizeValue($value)
	{
		return ($value instanceof Repository)
		     ? $value
		     : new Repository($value, $this->configurator);
	}
}