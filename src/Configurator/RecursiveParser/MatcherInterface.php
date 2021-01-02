<?php declare(strict_types=1);

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2021 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RecursiveParser;

interface MatcherInterface
{
	/**
	* Return the matchers configuration
	*
	* Match name as key. Can be prefixed with a colon-separated list of groups, e.g. "Group:Name".
	* Value must be a string or an array with the following elements:
	*
	*  - regexp:   the regular expression used to match input.
	*  - order:    used to sort matchers. Defaults to 0.
	*  - groups:   list of groups this match belongs to. Defaults to an empty array.
	*  - callback: called with the matched strings. Defaults to [$this, "parseX"] where X is the
	*              match name.
	*
	* If the config is a string, the string is used for the "regexp" element.
	*
	* @return array
	*/
	public function getMatchers(): array;
}