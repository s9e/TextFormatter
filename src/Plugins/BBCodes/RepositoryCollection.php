<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\BBCodes;

use DOMDocument;
use InvalidArgumentException;
use s9e\TextFormatter\ConfigBuilder\Collections\NormalizeCollection;

class RepositoryCollection extends NormalizedCollection
{
	/**
	* Normalize a value for storage
	*
	*
	* @param  mixed $value Original value
	* @return mixed        Normalized value
	*/
	public function normalizeValue($value)
	{
		if (!($value instanceof DOMDocument))
		{
			if (!file_exists($value))
			{
				throw new InvalidArgumentException('Not a DOMDocument or the path to a repository file');
			}

			$dom = new DOMDocument;

			if (!$dom->load($value))
			{
				throw new InvalidArgumentException('Invalid repository file');
			}

			$value = $dom;
		}

		return $value;
	}
}