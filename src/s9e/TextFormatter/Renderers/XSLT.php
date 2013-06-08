<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2013 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Renderers;

use DOMDocument;
use XSLTProcessor;
use s9e\TextFormatter\Renderer;

class XSLT extends Renderer
{
	/**
	* @var bool Whether this renderer's stylesheet produces HTML (as opposed to XHTML)
	*/
	protected $htmlOutput;

	/**
	* @var XSLTProcessor The lazy-loaded XSLTProcessor instance used by this renderer
	*/
	protected $proc;

	/**
	* @var string The stylesheet used by this renderer
	*/
	protected $stylesheet;

	/**
	* Constructor
	*
	* @param  string $stylesheet The stylesheet used to render intermediate representations
	* @return void
	*/
	public function __construct($stylesheet)
	{
		$this->stylesheet = $stylesheet;

		// Test whether we output HTML or XML
		$this->htmlOutput = (strpos($this->stylesheet, '<xsl:output method="html') !== false);
	}

	/**
	* Serializer
	*
	* @return array List of properties to serialize
	*/
	public function __sleep()
	{
		return ['htmlOutput', 'stylesheet'];
	}

	/**
	* {@inheritdoc}
	*/
	public function setParameter($paramName, $paramValue)
	{
		/**
		* @link https://bugs.php.net/64137
		*/
		if (strpos($paramValue, '"') !== false
		 && strpos($paramValue, "'") !== false)
		{
			$paramValue = str_replace('"', "\xEF\xBC\x82", $paramValue);
		}

		$this->load();
		$this->proc->setParameter('', $paramName, $paramValue);
	}

	/**
	* {@inheritdoc}
	*/
	protected function renderRichText($xml)
	{
		// Load the intermediate representation
		$dom  = new DOMDocument;
		$dom->loadXML($xml);

		// Load the stylesheet
		$this->load();

		// Perform the transformation and cast it as a string because it may return NULL if the
		// transformation didn't output anything
		$output = (string) $this->proc->transformToXml($dom);

		// Remove the \n that XSL adds at the end of the output, if applicable
		if (substr($output, -1) === "\n")
		{
			$output = substr($output, 0, -1);
		}

		return $output;
	}

	/**
	* Create an XSLTProcessor and load the stylesheet
	*
	* @return void
	*/
	protected function load()
	{
		if (!isset($this->proc))
		{
			$xsl = new DOMDocument;
			$xsl->loadXML($this->stylesheet);

			$this->proc = new XSLTProcessor;
			$this->proc->importStylesheet($xsl);
		}
	}
}