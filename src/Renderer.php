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
	* Constructor
	*
	* @param  string $stylesheet The stylesheet used to render intermediate representations
	* @return void
	*/
	public function __construct($stylesheet)
	{
		$this->stylesheet = $stylesheet;
	}

	/**
	* Serializer
	*
	* @return string This renderer's stylesheet
	*/
	public function serialize()
	{
		return $this->stylesheet;
	}

	/**
	* Unserializer
	*
	* @param  string $data Serialized data
	* @return void
	*/
	public function unserialize($data)
	{
		$this->__construct($data);
	}

	/**
	* Render an intermediate representation
	*
	* @param  string $xml Intermediate representation
	* @return string      Rendered result
	*/
	public function render($xml)
	{
		// Fast path for plain text
		if (substr($xml, 0, 4) === '<pt>')
		{
			return substr($xml, 4, -5);
		}

		$dom  = new DOMDocument;
		$dom->loadXML($xml);

		if (!isset($this->proc))
		{
			$xsl = new DOMDocument;
			$xsl->loadXML($this->stylesheet);

			$this->proc = new XSLTProcessor;
			$this->proc->importStylesheet($xsl);
		}

		// Remove the \n that XSL adds at the end of the output
		return substr($this->proc->transformToXml($dom), 0, -1);
	}

	/**
	* Render an array of intermediate representations
	*
	* @param  array $arr Array of XML strings
	* @return array      Array of render results (same keys, same order)
	*/
	public function renderMulti(array $arr)
	{
		$uid = sha1(uniqid(mt_rand(), true));
		$xml = '<m>' . implode($uid, $arr) . '</m>';

		return array_combine(
			array_keys($arr),
			explode($uid, $this->render($xml))
		);
	}
}