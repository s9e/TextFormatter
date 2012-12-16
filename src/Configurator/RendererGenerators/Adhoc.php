<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RendererGenerators;

use DOMDocument;
use DOMNode;
use DOMText;
use DOMXPath;
use RuntimeException;

abstract class Adhoc
{
	/**
	* 
	*
	* @return void
	*/
	public static function generate($xsl)
	{
		$dom = new DOMDocument;
		$dom->loadXML($xsl);

		$php = '{protected $inElement;protected $xpath;protected function closeElement(){if($this->inElement){$this->inElement=false?>><?php }}public function render($xml){$dom=new DOMDocument;$dom->loadXML($xml);$this->xpath=new DOMXPath($dom);$this->inElement=false;$this->at($dom->documentElement);}protected function at($root){if($root->nodeType===3){$this->closeElement();echo $root->textContent;}else foreach($root->childNodes as $node)';

		$else = '';

		foreach ($dom->getElementsByTagNameNS('http://www.w3.org/1999/XSL/Transform', 'template') as $node)
		{
			$match = $node->getAttribute('match');

			$php .= $else . "if(\$node->localName==='" . $match . "'){?>";
			$php .= self::convertChildren($node);
			$php .= '<?php }?>';

			$else = '<?php else';
		}

		$php .= '<?php else $this->at($node);}}';

		$php = str_replace('{?><?php ', '{', $php);
		$php = str_replace('{?><?=', '{echo ', $php);
		$php = str_replace('}?><?php ', '}', $php);
		$php = str_replace('}?><?=', '}echo ', $php);
		$php = str_replace('?><?php ', ';', $php);
		$php = str_replace('?><?=', ';echo ', $php);

		$className = sprintf('Renderer%08X', crc32($php));
		$php = '<?php class ' . $className . $php . 'return new ' . $className . ';';

		return $php;
	}

	/**
	* 
	*
	* @return void
	*/
	protected static function convertChildren(DOMNode $parent)
	{
		$php = '';

		foreach ($parent->childNodes as $child)
		{
			if ($child instanceof DOMText)
			{
				$php .= self::text($child->textContent);
			}
			elseif ($child->namespaceURI === 'http://www.w3.org/1999/XSL/Transform')
			{
				$methodName = 'element' . str_replace(' ', '', ucwords(str_replace('-', ' ', $child->localName)));

				$php .= self::$methodName($child);
			}
			else
			{
				$php .= self::writeElement($child);
			}
		}

		return $php;
	}

	/**
	* 
	*
	* @return void
	*/
	protected static function export($str)
	{
		$single = "'" . addcslashes($str, "'") . "'";
		$double = '"' . addcslashes($str, '"\\$') . '"';

		return (strlen($single) > strlen($double)) ? $double : $single;
	}

	/**
	* 
	*
	* @return void
	*/
	protected static function writeElement(DOMNode $node)
	{
		$php = '<?php if($this->inElement){?>><?php }?><' . $node->localName;

		foreach ($node->attributes as $attribute)
		{
			$attrName  = $attribute->name;
			$attrValue = $attribute->value;

			$php .= ' ' . $attrName . '="';
			$php .= preg_replace_callback(
				'#(?<!\\{)((?:\\{\\{)*\\{)([^}]+)#',
				function ($m)
				{
					return $m[1] . '<?=$this->xpath->evaluate(' . self::export($m[2]) . ',$node)?>';
				},
				$attribute->value
			);
			$php .= '"';
		}

		$php .= '<?php $this->inElement=true?>' . self::convertChildren($node) . '<?php $this->inElement=false?></' . $node->localName . '>';

		return $php;
	}

	/**
	* 
	*
	* @return void
	*/
	protected static function elementApplyTemplates(DOMNode $node)
	{
		return '<?php $this->at($node)?>';
	}

	/**
	* 
	*
	* @return void
	*/
	protected static function elementIf(DOMNode $node)
	{
		$condition = self::convertCondition($node->getAttribute('test'));

		return '<?php if(' . $condition . '){?>' . self::convertChildren($node) . '<?php }?>';
	}

	/**
	* 
	*
	* @return void
	*/
	protected static function elementValueOf(DOMNode $node)
	{
		$expr = $node->getAttribute('select');

		$php = '<?php $this->closeElement();echo';

		if (preg_match('#^\\s*@([-a-z0-9_]+)\\s*$#D', $expr, $m))
		{
			$php .= "\$node->getAttribute('" . $m[1] . "')";
		}
		elseif ($expr === '.')
		{
			$php .= '$node->textContent';
		}
		else
		{
			$php .= '$this->xpath->evaluate(' . self::export($expr) . ',$node)';
		}

		return $php . '?>';
	}

	/**
	* 
	*
	* @return void
	*/
	protected static function elementCopyOf(DOMNode $node)
	{
		$expr = $node->getAttribute('select');

		$php = '<?php $this->closeElement();echo';

		// <xsl:copy-of select="@foo"/>
		if (preg_match('#^\\s*@([-a-z0-9_]+)\\s*$#D', $expr, $m))
		{
			return "<?php if \$node->hasAttribute('" . $m[1] . "'){?> " . $m[1] . '="<?=$node->getAttribute(\'' . $m[1] . "')?>\"";
		}

		return '<?php $this->closeElement();echo $node->ownerDocument->saveXML($node);?>';
	}

	/**
	* 
	*
	* @return void
	*/
	protected static function elementAttribute(DOMNode $node)
	{
		return ' ' . $node->getAttribute('name') . '="' . self::convertChildren($node) . '"';
	}

	/**
	* 
	*
	* @return void
	*/
	protected static function elementWhen(DOMNode $node)
	{
		$condition = self::convertCondition($node->getAttribute('test'));
		$control   = (self::evaluate('preceding-sibling::xsl:when', $node))
		           ? 'elseif'
		           : 'if';

		return '<?php ' . $control . '(' . $condition . '){' . self::convertChildren($node) . '}?>';
	}

	/**
	* 
	*
	* @return void
	*/
	protected static function elementOtherwise(DOMNode $node)
	{
		return '<?php else{' . self::convertChildren($node) . '}?>';
	}

	/**
	* 
	*
	* @return void
	*/
	protected static function convertCondition($condition)
	{
		// <xsl:test when="@foo">
		// if ($node->hasAttribute('foo'))
		if (preg_match('#^\\s*@([-a-z0-9_]+)\\s*$#D', $condition, $m))
		{
			return "\$node->hasAttribute('" . $m[1] . "')";
		}

		// <xsl:test when="not(@foo)">
		// if (!$node->hasAttribute('foo'))
		if (preg_match('#^\\s*not\\(\\s*@([-a-z0-9_]+)\\s*\\)\\s*$#D', $condition, $m))
		{
			return "!\$node->hasAttribute('" . $m[1] . "')";
		}

		// <xsl:test when="@foo='bar'">
		// if ($this->xpath->evaluate("boolean(@foo='bar')",$node))
		return '$this->xpath->evaluate(' . self::export('boolean(' . $condition . ')') . ',$node)';
	}

	/**
	* 
	*
	* @return void
	*/
	protected static function evaluate($query, DOMNode $node)
	{
		$xpath = new DOMXPath($node->ownerDocument);

		return $xpath->evaluate($query, $node);
	}

	/**
	* 
	*
	* @return void
	*/
	protected static function text($str)
	{
		return htmlspecialchars($str, ENT_NOQUOTES, 'UTF-8');
	}
}