<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2021 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\JavaScript;

use s9e\TextFormatter\Configurator\Helpers\RegexpBuilder;
use s9e\TextFormatter\Configurator\JavaScript\Code;

class StylesheetCompressor
{
	/**
	* @var string[] List of regular expressions that match strings to deduplicate
	*/
	protected $deduplicateTargets = [
		'<xsl:template match="',
		'</xsl:template>',
		'<xsl:apply-templates/>',
		'<param name="allowfullscreen" value="true"/>',
		'<xsl:value-of select="',
		'<xsl:copy-of select="@',
		'<iframe allowfullscreen="" scrolling="no"',
		'display:block;overflow:hidden;position:relative;padding-bottom:',
		'display:inline-block;width:100%;max-width:',
		' [-:\\w]++="',
		'\\{[^}]++\\}',
		'@[-\\w]{4,}+',
		'(?<=<)[-:\\w]{4,}+',
		'(?<==")[^"]{4,}+"'
	];

	/**
	* @var array Associative array of string replacements as [match => replace]
	*/
	protected $dictionary;

	/**
	* @var string Prefix used for dictionary keys
	*/
	protected $keyPrefix = '$';

	/**
	* @var integer Number of bytes each global substitution must save to be considered
	*/
	public $minSaving = 10;

	/**
	* @var array Associative array of [string => saving]
	*/
	protected $savings;

	/**
	* @var string
	*/
	protected $xsl;

	/**
	* Encode given stylesheet into a compact JavaScript representation
	*
	* @param  string $xsl Original stylesheet
	* @return string      JavaScript representation of the compressed stylesheet
	*/
	public function encode($xsl)
	{
		$this->xsl = $xsl;

		$this->estimateSavings();
		$this->filterSavings();
		$this->buildDictionary();

		$str = $this->getCompressedStylesheet();

		// Split the stylesheet's string into 2000 bytes chunks to appease Google Closure Compiler
		$js = implode("+\n", array_map('json_encode', str_split($str, 2000)));
		if (!empty($this->dictionary))
		{
			$js = '(' . $js . ').replace(' . $this->getReplacementRegexp() . ',function(k){return' . json_encode($this->dictionary) . '[k];})';
		}

		return $js;
	}

	/**
	* Build a dictionary of all cost-effective string replacements
	*
	* @return void
	*/
	protected function buildDictionary()
	{
		$keys = $this->getAvailableKeys();
		rsort($keys);

		$this->dictionary = [];
		arsort($this->savings);
		foreach (array_keys($this->savings) as $str)
		{
			$key = array_pop($keys);
			if (!$key)
			{
				break;
			}

			$this->dictionary[$key] = $str;
		}
	}

	/**
	* Estimate the savings of every possible string replacement
	*
	* @return void
	*/
	protected function estimateSavings()
	{
		$this->savings = [];
		foreach ($this->getStringsFrequency() as $str => $cnt)
		{
			$len             = strlen($str);
			$originalCost    = $cnt * $len;
			$replacementCost = $cnt * 2;
			$overhead        = $len + 6;

			$this->savings[$str] = $originalCost - ($replacementCost + $overhead);
		}
	}

	/**
	* Filter the savings according to the minSaving property
	*
	* @return void
	*/
	protected function filterSavings()
	{
		$this->savings = array_filter(
			$this->savings,
			function ($saving)
			{
				return ($saving >= $this->minSaving);
			}
		);
	}

	/**
	* Return all the possible dictionary keys that are not present in the original stylesheet
	*
	* @return string[]
	*/
	protected function getAvailableKeys()
	{
		return array_diff($this->getPossibleKeys(), $this->getUnavailableKeys());
	}

	/**
	* Return the stylesheet after dictionary replacements
	*
	* @return string
	*/
	protected function getCompressedStylesheet()
	{
		return strtr($this->xsl, array_flip($this->dictionary));
	}

	/**
	* Return a list of possible dictionary keys
	*
	* @return string[]
	*/
	protected function getPossibleKeys()
	{
		$keys = [];
		foreach (range('a', 'z') as $char)
		{
			$keys[] = $this->keyPrefix . $char;
		}

		return $keys;
	}

	/**
	* Return a regexp that matches all used dictionary keys
	*
	* @return string
	*/
	protected function getReplacementRegexp()
	{
		return '/' . RegexpBuilder::fromList(array_keys($this->dictionary)) . '/g';
	}

	/**
	* Return the frequency of all deduplicatable strings
	*
	* @return array Array of [string => frequency]
	*/
	protected function getStringsFrequency()
	{
		$regexp = '(' . implode('|', $this->deduplicateTargets) . ')S';
		preg_match_all($regexp, $this->xsl, $matches);

		return array_count_values($matches[0]);
	}

	/**
	* Return the list of possible dictionary keys that appear in the original stylesheet
	*
	* @return string[]
	*/
	protected function getUnavailableKeys()
	{
		preg_match_all('(' . preg_quote($this->keyPrefix) . '.)', $this->xsl, $matches);

		return array_unique($matches[0]);
	}
}