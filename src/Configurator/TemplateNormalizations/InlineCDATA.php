<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2022 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use DOMNode;

class InlineCDATA extends AbstractNormalization
{
	/**
	* {@inheritdoc}
	*/
	protected $queries = ['//text()'];

	/**
	* {@inheritdoc}
	*/
	protected function normalizeNode(DOMNode $node)
	{
		if ($node->nodeType === XML_CDATA_SECTION_NODE)
		{
			$node->parentNode->replaceChild($this->createText($node->textContent), $node);
		}
	}
}