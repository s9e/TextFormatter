<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Helpers;

class CharacterClassBuilder
{
	/**
	* @var string[] List of characters in the class
	*/
	protected $chars;

	/**
	* @var string Delimiter used in regexps
	*/
	public $delimiter = '/';

	/**
	* @var array[] Array of [start, end] pairs where start and end are keys from $this->chars
	*/
	protected $ranges;

	/**
	* Create a character class that matches the given list of characters
	*
	* @param  string[] $chars
	* @return string
	*/
	public function fromList(array $chars)
	{
		$this->chars = $chars;

		$this->unescapeLiterals();
		sort($this->chars);
		$this->storeRanges();
		$this->reorderDash();
		$this->fixCaret();
		$this->escapeSpecialChars();

		return $this->buildCharacterClass();
	}

	/**
	* Build the character class based
	*
	* @return string
	*/
	protected function buildCharacterClass()
	{
		$str = '[';
		foreach ($this->ranges as list($start, $end))
		{
			if ($end > $start + 2)
			{
				$str .= $this->chars[$start] . '-' . $this->chars[$end];
			}
			else
			{
				$str .= implode('', array_slice($this->chars, $start, $end + 1 - $start));
			}
		}
		$str .= ']';

		return $str;
	}

	/**
	* Escape special characters in stored chars
	*
	* @return void
	*/
	protected function escapeSpecialChars()
	{
		$specialChars = ['\\', ']', $this->delimiter];
		foreach (array_intersect($this->chars, $specialChars) as $k => $v)
		{
			$this->chars[$k] = '\\' . $v;
		}
	}

	/**
	* Reorder or escape the caret character so that the regexp doesn't start with it
	*
	* @return void
	*/
	protected function fixCaret()
	{
		// Test whether the character class starts with a caret
		$k = array_search('^', $this->chars, true);
		if ($this->ranges[0][0] !== $k)
		{
			return;
		}

		// We swap the first two ranges if applicable, otherwise we escape the caret
		if (isset($this->ranges[1]))
		{
			$range           = $this->ranges[0];
			$this->ranges[0] = $this->ranges[1];
			$this->ranges[1] = $range;
		}
		else
		{
			$this->chars[$k] = '\\^';
		}
	}

	/**
	* Reorder the characters so that a literal dash isn't mistaken for a range
	*
	* @return void
	*/
	protected function reorderDash()
	{
		$dashIndex = array_search('-', $this->chars, true);
		if ($dashIndex === false)
		{
			return;
		}

		// Look for a single dash and move it to the start of the character class
		$k = array_search([$dashIndex, $dashIndex], $this->ranges, true);
		if ($k > 0)
		{
			unset($this->ranges[$k]);
			array_unshift($this->ranges, [$dashIndex, $dashIndex]);
		}

		// Look for a comma-dash (0x2C..0x2D) range
		$commaIndex = array_search(',', $this->chars);
		$range      = [$commaIndex, $dashIndex];
		$k          = array_search($range, $this->ranges, true);
		if ($k !== false)
		{
			// Replace with a single comma and prepend a single dash
			$this->ranges[$k] = [$commaIndex, $commaIndex];
			array_unshift($this->ranges, [$dashIndex, $dashIndex]);
		}
	}

	/**
	* Store the character ranges from the list of characters
	*
	* @return void
	*/
	protected function storeRanges()
	{
		$values = [];
		foreach ($this->chars as $char)
		{
			if (strlen($char) === 1)
			{
				$values[] = ord($char);
			}
			else
			{
				$values[] = false;
			}
		}

		$i = count($values) - 1;
		$ranges = [];
		while ($i >= 0)
		{
			$start = $i;
			$end   = $i;
			while ($start > 0 && $values[$start - 1] === $values[$end] - ($end + 1 - $start))
			{
				--$start;
			}
			$ranges[] = [$start, $end];
			$i = $start - 1;
		}

		$this->ranges = array_reverse($ranges);
	}

	/**
	* Unescape literals in stored chars
	*
	* @return void
	*/
	protected function unescapeLiterals()
	{
		foreach ($this->chars as $k => $char)
		{
			if ($char[0] === '\\' && preg_match('(^\\\\[^a-z]$)Di', $char))
			{
				$this->chars[$k] = substr($char, 1);
			}
		}
	}
}