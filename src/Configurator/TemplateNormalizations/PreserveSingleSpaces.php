<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;
use DOMNode;
class PreserveSingleSpaces extends AbstractNormalization
{
	protected $queries = array('//text()[. = " "][not(parent::xsl:text)]');
	protected function normalizeNode(DOMNode $node)
	{
		$node->parentNode->replaceChild($this->createElement('xsl:text', ' '), $node);
	}
}