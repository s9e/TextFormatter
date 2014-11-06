<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2014 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Emoji;

use s9e\TextFormatter\Configurator\Items\Variant;
use s9e\TextFormatter\Configurator\JavaScript\RegExp;
use s9e\TextFormatter\Plugins\ConfiguratorBase;

class Configurator extends ConfiguratorBase
{
	protected $attrName = 'seq';

	protected $tagName = 'E1';

	protected function setUp()
	{
		if (isset($this->configurator->tags[$this->tagName]))
			return;

		$tag = $this->configurator->tags->add($this->tagName);
		$tag->attributes->add($this->attrName)->filterChain->append(
			$this->configurator->attributeFilters['#identifier']
		);
		$tag->template = '<img alt="{.}" class="emojione" src="//cdn.jsdelivr.net/emojione/assets/png/{@seq}.png"/>';
	}

	public function asConfig()
	{
		$phpRegexp = '(';
		$jsRegexp  = '';

		$phpRegexp .= '(?=[#0-9:\\xC2\\xE2\\xE3\\xF0])';

		$phpRegexp .= '(?>';
		$jsRegexp  .= '(?:';

		$phpRegexp .= ':[-+_a-z0-9]+(?=:)';
		$jsRegexp  .= ':[-+_a-z0-9]+(?=:)';

		$phpRegexp .= '|(?>';
		$jsRegexp  .= '|(?:';

		$phpRegexp .= '[#0-9](?>\\xEF\\xB8\\x8F)?\\xE2\\x83\\xA3';
		$jsRegexp  .= '[#0-9]\\uFE0F?\\u20E3';

		$phpRegexp .= '|\\xC2[\\xA9\\xAE]';
		$jsRegexp  .= '|[\\u00A9\\u00AE';

		$phpRegexp .= '|\\xE2(?>\\x80\\xBC|[\\x81-\\xAD].)';
		$jsRegexp  .= '\\u203C\\u2049\u2122-\\u2B55';

		$phpRegexp .= '|\\xE3(?>\\x80[\\xB0\\xBD]|\\x8A[\\x97\\x99])';
		$jsRegexp  .= '\\u3030\\u303D\\u3297\\u3299]';

		$phpRegexp .= '|\\xF0\\x9F(?>';
		$jsRegexp  .= '|\\uD83C';

		$phpRegexp .= '[\\x80-\\x86].';
		$jsRegexp  .= '[\\uDC04-\\uDD9A';

		$phpRegexp .= '|\\x87.\\xF0\\x9F\\x87.';
		$jsRegexp  .= '\\uDDE8-\\uDDFA';

		$phpRegexp .= '|[\\x88-\\x9B].';
		$jsRegexp  .= '\\uDE01-\\uDFFF]|\\uD83D[\\uDC00-\\uDEC5]';

		$phpRegexp .= ')';

		$phpRegexp .= ')(?>\\xEF\\xB8\\x8F)?';
		$jsRegexp  .= ')\uFE0F?';

		$phpRegexp .= ')';
		$jsRegexp  .= ')';

		$phpRegexp .= ')S';

		$regexp = new Variant($phpRegexp);
		$regexp->set('JS', new RegExp($jsRegexp, 'g'));

		return [
			'attrName' => $this->attrName,
			'regexp'   => $regexp,
			'tagName'  => $this->tagName
		];
	}
}