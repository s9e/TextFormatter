<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Items\AttributeFilters;

class FontfamilyFilter extends RegexpFilter
{
	public function __construct()
	{
		// This is more restrictive than the specs but safer
		$namechar = '[- \\w]';
		$double   = '"' . $namechar . '+"';
		$single   = "'" . $namechar . "+'";
		$name     = '(?:' . $single . '|' . $double . '|' . $namechar . ')+';
		$regexp   = '/^' . $name . '(?:, *' . $name . ')*$/';

		parent::__construct($regexp);
	}

	/**
	* {@inheritdoc}
	*/
	public function isSafeInCSS()
	{
		return true;
	}
}