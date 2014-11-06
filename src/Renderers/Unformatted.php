<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2014 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Renderers;

use s9e\TextFormatter\Renderer;

class Unformatted extends Renderer
{
	public $metaElementsRegexp = '((?!))';

	public function __construct($htmlOutput = \true)
	{
		$this->htmlOutput = $htmlOutput;
	}

	protected function renderRichText($xml)
	{
		return \str_replace(
			"\n",
			($this->htmlOutput) ? "<br>\n" : "<br/>\n",
			\strip_tags($xml)
		);
	}
}