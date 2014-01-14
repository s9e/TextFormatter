<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2014 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Renderers;

use s9e\TextFormatter\Renderer;

/**
* This renderer returns a plain text version of rich text. It is meant to be used as a last resort
* when every other renderer is unavailable
*/
class Unformatted extends Renderer
{
	/**
	* {@inheritdoc}
	*/
	public $metaElementsRegexp = '((?!))';

	/**
	* Constructor
	*
	* @param  bool Whether this renderer's stylesheet produces HTML (as opposed to XHTML)
	* @return void
	*/
	public function __construct($htmlOutput = true)
	{
		$this->htmlOutput = $htmlOutput;
	}

	/**
	* {@inheritdoc}
	*/
	protected function renderRichText($xml)
	{
		return str_replace(
			"\n",
			($this->htmlOutput) ? "<br>\n" : "<br/>\n",
			strip_tags($xml)
		);
	}
}