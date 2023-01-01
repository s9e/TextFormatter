<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Items;

class TagFilter extends Filter
{
	/**
	* Constructor
	*
	* @param callable $callback
	*/
	public function __construct($callback)
	{
		parent::__construct($callback);

		// Set the default signature
		$this->resetParameters();
		$this->addParameterByName('tag');
	}
}