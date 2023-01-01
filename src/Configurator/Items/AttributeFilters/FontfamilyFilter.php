<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Items\AttributeFilters;

class FontfamilyFilter extends RegexpFilter
{
	public function __construct()
	{
		// This is more restrictive than the specs but safer
		$namechars = '[- \\w]+';
		$double    = '"' . $namechars . '"';
		$single    = "'" . $namechars . "'";
		$name      = '(?:' . $single . '|' . $double . '|' . $namechars . ')';
		$regexp    = '/^' . $name . '(?:, *' . $name . ')*$/';

		parent::__construct($regexp);
		$this->markAsSafeInCSS();
	}
}