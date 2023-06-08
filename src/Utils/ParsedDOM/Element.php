<?php declare(strict_types=1);

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Utils\ParsedDOM;

use DOMNode;
use const SORT_STRING;
use function count, ksort, preg_match;
use s9e\SweetDOM\Element as SweetElement;

class Element extends SweetElement
{
	/**
	* Normalize this element to match what the parser would produce
	*
	* @link https://www.php.net/manual/en/domnode.normalize.php
	*/
	public function normalize(): void
	{
		parent::normalize();
		foreach ($this->query('*') as $child)
		{
			$child->normalize();
		}

		if ($this->shouldBeRemoved())
		{
			// Remove elements with no content and no attributes
			$this->remove();
		}
		elseif (!$this->attributesAreSorted())
		{
			$this->sortAttributes();
		}
	}

	/**
	* Replace this element with a new tag
	*
	* @param  string $tagName    New tag's name
	* @param  array  $attributes Attributes for the new tag
	* @return static             Element that represents the new tag
	*/
	public function replaceTag(string $tagName, array $attributes = []): static
	{
		$element = $this->ownerDocument->createTagElement($tagName, $attributes);
		while (isset($this->firstChild))
		{
			$element->appendChild($this->firstChild);
		}
		$this->replaceWith($element);

		return $element;
	}

	/**
	* Set the markup at the end of this element's content
	*/
	public function setMarkupEnd(string $markup): void
	{
		$node = $this->firstOf('e') ?? $this->appendElement('e');
		$node->textContent = $markup;
		$node->normalize();
	}

	/**
	* Set the markup at the start of this element's content
	*/
	public function setMarkupStart(string $markup): void
	{
		$node = $this->firstOf('s') ?? $this->prependElement('s');
		$node->textContent = $markup;
		$node->normalize();
	}

	/**
	* Unparse this element without removing or unparsing its descendants
	*
	* Any markup associated with this element will become plain text.
	*/
	public function unparse(): void
	{
		$this->unparseMarkupElement('s', $this->firstChild);
		$this->unparseMarkupElement('e', $this->lastChild);

		$parent = $this->parentNode;
		while (isset($this->firstChild))
		{
			$parent->insertBefore($this->firstChild, $this);
		}
		$this->remove();
	}

	protected function unparseMarkupElement(string $nodeName, ?DOMNode $node): void
	{
		if ($node instanceof self && $node->nodeName === $nodeName)
		{
			$node->replaceWith($node->textContent);
		}
	}

	protected function attributesAreSorted(): bool
	{
		$lastName = '';
		foreach ($this->attributes as $name => $attribute)
		{
			if ($name < $lastName)
			{
				return false;
			}
			$lastName = $name;
		}

		return true;
	}

	protected function shouldBeRemoved(): bool
	{
		if (preg_match('/^[ies]$/', $this->nodeName) && count($this->childNodes) === 0 && count($this->attributes) === 0)
		{
			return true;
		}

		return false;
	}

	protected function sortAttributes(): void
	{
		$attributes = [];
		foreach ($this->attributes as $name => $attribute)
		{
			$attributes[$name] = $this->removeAttributeNode($attribute);
		}

		ksort($attributes, SORT_STRING);
		foreach ($attributes as $attribute)
		{
			$this->setAttributeNode($attribute);
		}
	}
}