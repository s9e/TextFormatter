<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use DOMText;

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
	protected function normalizeText(DOMText $node): void
	{
		$node->parentNode->removeChild($node);
	}
}