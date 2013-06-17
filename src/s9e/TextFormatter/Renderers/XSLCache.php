<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2013 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Renderers;

use DOMDocument;
use XSLTCache;
use s9e\TextFormatter\Renderer;

/**
* This renderer uses the xslcache PECL extension
*
* @link http://pecl.php.net/package/xslcache
* @link http://michaelsanford.com/compiling-xslcache-0-7-1-for-php-5-4/
*/
class XSLCache extends Renderer
{
	/**
	* @var string Path to the stylesheet used by this renderer
	*/
	protected $filepath;

	/**
	* @var XSLCache The lazy-loaded XSLCache instance used by this renderer
	*/
	protected $proc;

	/**
	* Constructor
	*
	* @param  string $filepath Path to the stylesheet used by this renderer
	* @return void
	*/
	public function __construct($filepath)
	{
		$this->filepath = $filepath;

		// Test whether we output HTML or XML
		$this->htmlOutput = (strpos(file_get_contents($this->filepath), '<xsl:output method="html') !== false);
	}

	/**
	* Serializer
	*
	* @return array List of properties to serialize
	*/
	public function __sleep()
	{
		$props = get_object_vars($this);
		unset($props['proc']);

		return array_keys($props);
	}

	/**
	* Return the path to the stylesheet used by this renderer
	*
	* @return void
	*/
	public function getFilepath()
	{
		return $this->filepath;
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
		$dom = $this->loadXML($xml);

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
	* Cache the XSLCache instance used by this renderer if it does not exist
	*
	* @return void
	*/
	protected function load()
	{
		if (!isset($this->proc))
		{
			$this->proc = new XSLTCache;
			$this->proc->importStylesheet($this->filepath);
		}
	}
}