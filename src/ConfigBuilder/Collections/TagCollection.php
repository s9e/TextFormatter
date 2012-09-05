<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\ConfigBuilder\Collections;

use s9e\TextFormatter\ConfigBuilder\Items\Tag;
use s9e\TextFormatter\ConfigBuilder\Validators\TagName;

class TagCollection extends NormalizedCollection
{
	public function normalizeKey($key)
	{
		return TagName::normalize($key);
	}

	public function normalizeValue($value)
	{
		return ($value instanceof Tag)
		     ? $value
		     : new Tag($value);
	}
}