<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use DOMNode;

/**
* Convert comments into xsl:comment elements
*/
class TransposeComments extends AbstractNormalization
{
	/**
	* {@inheritdoc}
	*/
	protected array $queries = ['//comment()'];

	/**
	* {@inheritdoc}
	*/
	protected function normalizeNode(DOMNode $node)
	{
		$xslComment = $this->ownerDocument->createXslComment($node->nodeValue);
		$node->replaceWith($xslComment);
	}
}