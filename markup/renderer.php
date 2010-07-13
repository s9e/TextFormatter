<?php

/**
* @package   s9e\toolkit
* @copyright Copyright (c) 2010 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\toolkit\markup;

class renderer
{
	public function __construct($stylesheet)
	{
		$xsl = new \DOMDocument;
		$xsl->loadXML($stylesheet);

		$this->proc = new \XSLTProcessor;
		$this->proc->importStylesheet($xsl);
	}

	public function render($xml)
	{
		$dom  = new \DOMDocument;
		$dom->loadXML($xml);

		return trim(strpbrk($this->proc->transformToXML($dom), "\n"));
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