<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\MediaEmbed\Configurator;

use DOMDocument;
use DOMElement;
use DOMXPath;
use InvalidArgumentException;

class LiveSiteDefinitionProvider extends SiteDefinitionProvider
{
	/**
	* @var string Path to site definitions' dir
	*/
	protected $path;

	/**
	* Constructor
	*
	* @param  string $path Path to site definitions' dir
	*/
	public function __construct($path)
	{
		if (!file_exists($path) || !is_dir($path))
		{
			throw new InvalidArgumentException('Invalid site directory');
		}
		$this->path = $path;
	}

	/**
	* {@inheritdoc}
	*/
	public function getIds()
	{
		$siteIds = [];
		foreach (glob($this->path . '/*.xml') as $filepath)
		{
			$siteIds[] = basename($filepath, '.xml');
		}

		return $siteIds;
	}

	/**
	* Extract a site's config from its XML file
	*
	* @param  string $filepath Path to the XML file
	* @return mixed
	*/
	protected function getConfigFromXmlFile($filepath)
	{
		$dom = new DOMDocument;
		$dom->load($filepath);

		return $this->getElementConfig($dom->documentElement);
	}

	/**
	* Extract a site's config from its XML representation
	*
	* @param  DOMElement $element Current node
	* @return mixed
	*/
	protected function getElementConfig(DOMElement $element)
	{
		$config = [];
		foreach ($element->attributes as $attribute)
		{
			$config[$attribute->name] = $attribute->value;
		}

		// Group child nodes by name
		$childNodes = [];
		foreach ($element->childNodes as $childNode)
		{
			if ($childNode instanceof DOMElement)
			{
				$childNodes[$childNode->nodeName][] = $this->getValueFromElement($childNode);
			}
		}

		foreach ($childNodes as $nodeName => $values)
		{
			$config[$nodeName] = (count($values) === 1) ? end($values) : $values;
		}

		return $config;
	}

	/**
	* Return the path that corresponds to given siteId
	*
	* @param  string $siteId
	* @return string
	*/
	protected function getFilePath($siteId)
	{
		return $this->path . '/' . $siteId . '.xml';
	}

	/**
	* Extract a value from given element
	*
	* @param  DOMElement $element
	* @return mixed
	*/
	protected function getValueFromElement(DOMElement $element)
	{
		return (!$element->attributes->length && $element->childNodes->length === 1)
		     ? $element->nodeValue
		     : $this->getElementConfig($element);
	}

	/**
	* {@inheritdoc}
	*/
	protected function getSiteConfig($siteId)
	{
		// Extract the site info from the node and put it into an array
		return $this->getConfigFromXmlFile($this->getFilePath($siteId));
	}

	/**
	* {@inheritdoc}
	*/
	protected function hasSiteConfig($siteId)
	{
		return file_exists($this->getFilePath($siteId));
	}
}