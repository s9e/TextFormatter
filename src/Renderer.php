<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2011 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter;

use DOMDocument,
    XSLTProcessor;

class Renderer
{
	/**
	* @var XSLTProcessor
	*/
	protected $proc;

	public function __construct($stylesheet)
	{
		$xsl = new DOMDocument;
		$xsl->loadXML($stylesheet);

		$this->proc = new XSLTProcessor;
		$this->proc->importStylesheet($xsl);
	}

	public function render($xml)
	{
		$dom  = new DOMDocument;
		$dom->loadXML($xml);

		return rtrim($this->proc->transformToXML($dom));
	}

	public function renderMulti(array $arr)
	{
		$uid = uniqid(mt_rand(), true);
		$xml = '<m uid="' . $uid . '">' . implode('', $arr) . '</m>';

		return array_combine(
			array_keys($arr),
			explode($uid, $this->render($xml))
		);
	}
}