<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2013 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use DOMNode;
use DOMXPath;
use s9e\TextFormatter\Configurator\TemplateNormalization;

class RemoveComments extends TemplateNormalization
{
	/**
	* Remove all comments
	*
	* @param  DOMNode $template <xsl:template/> node
	* @return void
	*/
	public function normalize(DOMNode $template)
	{
		$xpath = new DOMXPath($template->ownerDocument);

		foreach ($xpath->query('//comment()') as $comment)
		{
			$comment->parentNode->removeChild($comment);
		}
	}
}