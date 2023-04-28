<?php declare(strict_types=1);

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Utils\ParsedDOM;

use DOMNode;
use s9e\SweetDOM\Element as SweetElement;

class Element extends SweetElement
{
	/**
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

	public function setMarkupEnd(string $markup): void
	{
		$node = $this->firstOf('e') ?? $this->appendElement('e');
		$node->textContent = $markup;
		$node->normalize();
	}

	public function setMarkupStart(string $markup): void
	{
		$node = $this->firstOf('s') ?? $this->prependElement('s');
		$node->textContent = $markup;
		$node->normalize();
	}

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