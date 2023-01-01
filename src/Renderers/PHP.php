<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Renderers;

use DOMNode;
use DOMXPath;
use RuntimeException;
use s9e\TextFormatter\Renderer;
use s9e\TextFormatter\Utils\XPath;

abstract class PHP extends Renderer
{
	/**
	* @var array[] Stack of dictionaries used by the Quick renderer [[attrName => attrValue]]
	*/
	protected $attributes;

	/**
	* @var array Dictionary of replacements used by the Quick renderer [id => [match, replace]]
	*/
	protected $dynamic;

	/**
	* @var bool Whether to enable the Quick renderer
	*/
	public $enableQuickRenderer = false;

	/**
	* @var string Renderer's output
	*/
	protected $out;

	/**
	* @var string Regexp that matches XML elements to be rendered by the quick renderer
	*/
	protected $quickRegexp = '((?!))';

	/**
	* @var string Regexp that matches nodes that SHOULD NOT be rendered by the quick renderer
	*/
	protected $quickRenderingTest = '((?<=<)[!?])';

	/**
	* @var array Dictionary of static replacements used by the Quick renderer [id => replacement]
	*/
	protected $static;

	/**
	* @var DOMXPath XPath object used to query the document being rendered
	*/
	protected $xpath;

	/**
	* Render given DOMNode
	*
	* @param  DOMNode $node
	* @return void
	*/
	abstract protected function renderNode(DOMNode $node);

	public function __sleep()
	{
		return ['enableQuickRenderer', 'params'];
	}

	/**
	* Render the content of given node
	*
	* Matches the behaviour of an xsl:apply-templates element
	*
	* @param  DOMNode $root  Context node
	* @param  string  $query XPath query used to filter which child nodes to render
	* @return void
	*/
	protected function at(DOMNode $root, $query = null)
	{
		if ($root->nodeType === XML_TEXT_NODE)
		{
			// Text nodes are outputted directly
			$this->out .= htmlspecialchars($root->textContent, ENT_NOQUOTES);
		}
		else
		{
			$nodes = (isset($query)) ? $this->xpath->query($query, $root) : $root->childNodes;
			foreach ($nodes as $node)
			{
				$this->renderNode($node);
			}
		}
	}

	/**
	* Test whether given XML can be rendered with the Quick renderer
	*
	* @param  string $xml
	* @return bool
	*/
	protected function canQuickRender($xml)
	{
		return ($this->enableQuickRenderer && !preg_match($this->quickRenderingTest, $xml) && substr($xml, -4) === '</r>');
	}

	/**
	* Ensure that a tag pair does not contain a start tag of itself
	*
	* Detects malformed matches such as <X><X></X>
	*
	* @param  string $id
	* @param  string $xml
	* @return void
	*/
	protected function checkTagPairContent($id, $xml)
	{
		if (strpos($xml, '<' . $id, 1) !== false)
		{
			throw new RuntimeException;
		}
	}

	/**
	* Return a parameter's value as an XPath expression
	*
	* @param  string $paramName
	* @return string
	*/
	protected function getParamAsXPath($paramName)
	{
		return (isset($this->params[$paramName])) ? XPath::export($this->params[$paramName]) : "''";
	}

	/**
	* Extract the text content from given XML
	*
	* NOTE: numeric character entities are decoded beforehand, we don't need to decode them here
	*
	* @param  string $xml Original XML
	* @return string      Text content, with special characters decoded
	*/
	protected function getQuickTextContent($xml)
	{
		return htmlspecialchars_decode(strip_tags($xml));
	}

	/**
	* Test whether given array has any non-null values
	*
	* @param  array $array
	* @return bool
	*/
	protected function hasNonNullValues(array $array)
	{
		foreach ($array as $v)
		{
			if (isset($v))
			{
				return true;
			}
		}

		return false;
	}

	/**
	* Capture and return the attributes of an XML element
	*
	* NOTE: XML character entities are left as-is
	*
	* @param  string $xml Element in XML form
	* @return array       Dictionary of [attrName => attrValue]
	*/
	protected function matchAttributes($xml)
	{
		if (strpos($xml, '="') === false)
		{
			return [];
		}

		// Match all name-value pairs until the first right bracket
		preg_match_all('(([^ =]++)="([^"]*))S', substr($xml, 0, strpos($xml, '>')), $m);

		return array_combine($m[1], $m[2]);
	}

	/**
	* Render an intermediate representation using the Quick renderer
	*
	* @param  string $xml Intermediate representation
	* @return string
	*/
	protected function renderQuick($xml)
	{
		$this->attributes = [];
		$xml = $this->decodeSMP($xml);
		$html = preg_replace_callback(
			$this->quickRegexp,
			[$this, 'renderQuickCallback'],
			substr($xml, 1 + strpos($xml, '>'), -4)
		);

		return str_replace('<br/>', '<br>', $html);
	}

	/**
	* Render a string matched by the Quick renderer
	*
	* This stub should be overwritten by generated renderers
	*
	* @param  string[] $m
	* @return string
	*/
	protected function renderQuickCallback(array $m)
	{
		if (isset($m[3]))
		{
			return $this->renderQuickSelfClosingTag($m);
		}

		if (isset($m[2]))
		{
			// Single tag
			$id = $m[2];
		}
		else
		{
			// Tag pair
			$id = $m[1];
			$this->checkTagPairContent($id, $m[0]);
		}

		if (isset($this->static[$id]))
		{
			return $this->static[$id];
		}
		if (isset($this->dynamic[$id]))
		{
			return preg_replace($this->dynamic[$id][0], $this->dynamic[$id][1], $m[0], 1);
		}

		return $this->renderQuickTemplate($id, $m[0]);
	}

	/**
	* Render a self-closing tag using the Quick renderer
	*
	* @param  string[] $m
	* @return string
	*/
	protected function renderQuickSelfClosingTag(array $m)
	{
		unset($m[3]);

		$m[0] = substr($m[0], 0, -2) . '>';
		$html = $this->renderQuickCallback($m);

		$m[0] = '</' . $m[2] . '>';
		$m[2] = '/' . $m[2];
		$html .= $this->renderQuickCallback($m);

		return $html;
	}

	/**
	* Render a string matched by the Quick renderer using a generated PHP template
	*
	* This stub should be overwritten by generated renderers
	*
	* @param  integer $id  Tag's ID (tag name optionally preceded by a slash)
	* @param  string  $xml Tag's XML or tag pair's XML including their content
	* @return string       Rendered template
	*/
	protected function renderQuickTemplate($id, $xml)
	{
		throw new RuntimeException('Not implemented');
	}

	/**
	* {@inheritdoc}
	*/
	protected function renderRichText($xml)
	{
		$this->setLocale();

		try
		{
			if ($this->canQuickRender($xml))
			{
				$html = $this->renderQuick($xml);
				$this->restoreLocale();

				return $html;
			}
		}
		catch (RuntimeException $e)
		{
			// Do nothing
		}

		$dom         = $this->loadXML($xml);
		$this->out   = '';
		$this->xpath = new DOMXPath($dom);
		$this->at($dom->documentElement);
		$html        = $this->out;
		$this->reset();
		$this->restoreLocale();

		return $html;
	}

	/**
	* Reset object properties that are populated during rendering
	*
	* @return void
	*/
	protected function reset()
	{
		unset($this->attributes);
		unset($this->out);
		unset($this->xpath);
	}
}