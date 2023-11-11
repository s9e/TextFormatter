<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use s9e\SweetDOM\Comment;

/**
* Remove all comments
*/
class RemoveComments extends AbstractNormalization
{
	/**
	* {@inheritdoc}
	*/
	protected array $queries = ['//comment()'];

	/**
	* {@inheritdoc}
	*/
	protected function normalizeComment(Comment $comment): void
	{
		$comment->remove();
	}
}