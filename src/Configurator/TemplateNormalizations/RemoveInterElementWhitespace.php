<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2022 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use DOMNode;

/**
* Remove all inter-element whitespace except for single space characters
*/
class RemoveInterElementWhitespace extends AbstractNormalization
{
	/**
	* {@inheritdoc}
	*/
	protected $queries = ['//text()[normalize-space() = ""][. != " "][not(parent::xsl:text)]'];

	/**
	* {@inheritdoc}
	*/
	protected function normalizeNode(DOMNode $node)
	{
		$node->parentNode->removeChild($node);
	}
}