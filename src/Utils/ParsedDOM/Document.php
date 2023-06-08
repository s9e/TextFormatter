<?php declare(strict_types=1);

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Utils\ParsedDOM;

use const LIBXML_NSCLEAN, SORT_STRING, false;
use function ksort, substr, strpos;
use s9e\SweetDOM\Document as SweetDocument;
use s9e\TextFormatter\Configurator\Validators\TagName;
use s9e\TextFormatter\Configurator\Validators\AttributeName;
use s9e\TextFormatter\Utils;

class Document extends SweetDocument
{
	/**
	* @link https://www.php.net/manual/domdocument.construct.php
	*
	* @param string $version  Version number of the document
	* @param string $encoding Encoding of the document
	*/
	public function __construct(string $version = '1.0', string $encoding = 'utf-8')
	{
		parent::__construct($version, $encoding);

		$this->registerNodeClass('DOMElement', Element::class);
	}

	public function __toString(): string
	{
		$this->formatOutput = false;
		$this->normalizeDocument();

		$xml = $this->saveXML($this->documentElement, LIBXML_NSCLEAN);
		$xml = Utils::encodeUnicodeSupplementaryCharacters($xml);

		return ($xml === '<t/>') ? '<t></t>' : $xml;
	}

	/**
	* @link https://www.php.net/manual/en/domdocument.normalizedocument.php
	*/
	public function normalizeDocument(): void
	{
		parent::normalizeDocument();
		$this->documentElement->normalize();

		$nodeName = $this->documentElement->firstOf('.//*[name() != "br"][name() != "p"]') ? 'r' : 't';

		$root = $this->createElement($nodeName);
		while (isset($this->documentElement->firstChild))
		{
			$root->appendChild($this->documentElement->firstChild);
		}
		$this->documentElement->replaceWith($root);
	}

	/**
	* Create an element that represents a tag
	*
	* @param  string                $tagName
	* @param  array<string, string> $attributes
	* @return Element
	*/
	public function createTagElement(string $tagName, array $attributes = []): Element
	{
		$tagName = TagName::normalize($tagName);
		$pos     = strpos($tagName, ':');

		if ($pos === false)
		{
			$element = $this->createElement($tagName);
		}
		else
		{
			$prefix       = substr($tagName, 0, $pos);
			$namespaceURI = 'urn:s9e:TextFormatter:' . $prefix;
			$this->documentElement->setAttributeNS(
				'http://www.w3.org/2000/xmlns/',
				'xmlns:' . $prefix,
				$namespaceURI
			);

			$element = $this->createElementNS($namespaceURI, $tagName);
		}

		foreach ($this->normalizeAttributeMap($attributes) as $attrName => $attrValue)
		{
			$element->setAttribute($attrName, $attrValue);
		}

		return $element;
	}

	/**
	* @param  array<string, string> $attributes
	* @return array<string, string> $attributes
	*/
	protected function normalizeAttributeMap(array $attributes): array
	{
		$map = [];
		foreach ($attributes as $attrName => $attrValue)
		{
			$attrName       = AttributeName::normalize($attrName);
			$map[$attrName] = (string) $attrValue;

		}
		ksort($map, SORT_STRING);

		return $map;
	}
}