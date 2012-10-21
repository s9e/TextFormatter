<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\BBCodes;

use s9e\TextFormatter\Generator\Collections\NormalizedCollection;

class BBCodeCollection extends NormalizedCollection
{
	public function normalizeKey($key)
	{
		return BBCode::normalizeName($key);
	}

	public function normalizeValue($value)
	{
		return ($value instanceof BBCode)
		     ? $value
		     : new BBCode($value);
	}
}