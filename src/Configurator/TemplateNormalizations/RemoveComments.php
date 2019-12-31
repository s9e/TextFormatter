<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2020 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use DOMNode;

/**
* Remove all comments
*/
class RemoveComments extends AbstractNormalization
{
	/**
	* {@inheritdoc}
	*/
	protected $queries = ['//comment()'];

	/**
	* {@inheritdoc}
	*/
	protected function normalizeNode(DOMNode $node)
	{
		$node->parentNode->removeChild($node);
	}
}