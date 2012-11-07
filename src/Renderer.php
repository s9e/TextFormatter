<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter;

use DOMDocument;
use Serializable;
use XSLTProcessor;

class Renderer implements Serializable
{
	/**
	* @var string
	*/
	protected $stylesheet;

	/**
	* @var XSLTProcessor
	*/
	protected $proc;

	/**
	* 
	*
	* @return void
	*/
	public function serialize()
	{
		return $this->stylesheet;
	}

	/**
	* 
	*
	* @return void
	*/
	public function unserialize($data)
	{
		$this->__construct($data);
	}

	public function __construct($stylesheet)
	{
		$xsl = new DOMDocument;
		$xsl->loadXML($stylesheet);

		$this->proc = new XSLTProcessor;
		$this->proc->importStylesheet($xsl);

		$this->stylesheet = $stylesheet;
	}

	public function render($xml)
	{
		$dom  = new DOMDocument;
		$dom->loadXML($xml);

		return rtrim($this->proc->transformToXml($dom));
	}

	public function renderMulti(array $arr)
	{
		// NOTE: the UID is hashed to prevent leaking information about the random number generators
		//       in case somebody finds a way to retrieve it
		$uid = sha1(uniqid(mt_rand(), true));
		$xml = '<m>' . implode($uid, $arr) . '</m>';

		return array_combine(
			array_keys($arr),
			explode($uid, $this->render($xml))
		);
	}
}