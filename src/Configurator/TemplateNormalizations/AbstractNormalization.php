<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use DOMNode;
use s9e\SweetDOM\Attr;
use s9e\SweetDOM\Comment;
use s9e\SweetDOM\CdataSection;
use s9e\SweetDOM\Document;
use s9e\SweetDOM\Element;
use s9e\SweetDOM\Text;

abstract class AbstractNormalization
{
	/**
	* XSL namespace
	*/
	const XMLNS_XSL = 'http://www.w3.org/1999/XSL/Transform';

	/**
	* @var Document Document that holds the template being normalized
	*/
	protected Document $ownerDocument;

	/**
	* @var string[] XPath queries used to retrieve nodes of interest
	*/
	protected array $queries = [];

	/**
	* Apply this normalization rule to given template
	*
	* @param Element $template <xsl:template/> node
	*/
	public function normalize(Element $template): void
	{
		$this->ownerDocument = $template->ownerDocument;
		foreach ($this->getNodes() as $node)
		{
			// Ignore nodes that have been removed from the document
			if (isset($node->parentNode))
			{
				$this->normalizeNode($node);
			}
		}
		$this->reset();
	}

	/**
	* Create an xsl:text element or a text node in current template
	*/
	protected function createPolymorphicText(string $textContent): Element|Text
	{
		return (trim($textContent) === '')
		     ? $this->ownerDocument->nodeCreator->createXslText($textContent)
		     : $this->ownerDocument->createTextNode($textContent);
	}

	/**
	* Query and return a list of nodes of interest
	*
	* @return DOMNode[]
	*/
	protected function getNodes(): array
	{
		$query = implode(' | ', $this->queries);

		return ($query === '') ? [] : iterator_to_array($this->ownerDocument->query($query));
	}

	/**
	* Test whether given node is an XSL element
	*
	* @param  DOMNode $node
	* @param  string  $localName
	* @return bool
	*/
	protected function isXsl(DOMNode $node, $localName = null): bool
	{
		return ($node->namespaceURI === self::XMLNS_XSL && (!isset($localName) || $localName === $node->localName));
	}

	/**
	* Make an ASCII string lowercase
	*
	* @param  string $str Original string
	* @return string      Lowercased string
	*/
	protected function lowercase($str)
	{
		return strtr($str, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz');
	}

	protected function normalizeAttribute(Attr $attribute): void
	{
	}

	protected function normalizeCdataSection(CdataSection $comment): void
	{
	}

	protected function normalizeComment(Comment $comment): void
	{
	}

	protected function normalizeElement(Element $element): void
	{
	}

	protected function normalizeNode(DOMNode $node): void
	{
		if ($node instanceof Element)
		{
			$this->normalizeElement($node);
		}
		elseif ($node instanceof Attr)
		{
			$this->normalizeAttribute($node);
		}
		elseif ($node instanceof Text)
		{
			$this->normalizeText($node);
		}
		elseif ($node instanceof Comment)
		{
			$this->normalizeComment($node);
		}
		elseif ($node instanceof CdataSection)
		{
			$this->normalizeCdataSection($node);
		}
	}

	protected function normalizeText(Text $node): void
	{
	}

	/**
	* Reset this instance's properties after usage
	*/
	protected function reset(): void
	{
		unset($this->ownerDocument);
	}
}