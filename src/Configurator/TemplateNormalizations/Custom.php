<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;
use DOMElement;
use s9e\TextFormatter\Configurator\TemplateNormalization;
class Custom extends TemplateNormalization
{
	protected $callback;
	public function __construct(callable $callback)
	{
		$this->callback = $callback;
	}
	public function normalize(DOMElement $template)
	{
		\call_user_func($this->callback, $template);
	}
}