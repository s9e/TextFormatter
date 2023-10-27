<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use s9e\SweetDOM\Element;

class Custom extends AbstractNormalization
{
	/**
	* @var callable Normalization callback
	*/
	protected $callback;

	/**
	* Constructor
	*
	* @param callable $callback Normalization callback
	*/
	public function __construct(callable $callback)
	{
		$this->callback = $callback;
	}

	/**
	* Call the user-supplied callback
	*
	* @param  Element $template <xsl:template/> node
	* @return void
	*/
	public function normalize(Element $template): void
	{
		call_user_func($this->callback, $template);
		$this->reset();
	}
}