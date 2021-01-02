<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2021 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter;

use DOMDocument;
use InvalidArgumentException;

abstract class Renderer
{
	/**
	* @var array Associative array of [paramName => paramValue]
	*/
	protected $params = [];

	/**
	* @var string Saved locale
	*/
	protected $savedLocale = '0';

	/**
	* Create a return a new DOMDocument loaded with given XML
	*
	* @param  string      $xml Source XML
	* @return DOMDocument
	*/
	protected function loadXML($xml)
	{
		$this->checkUnsupported($xml);

		// Activate small nodes allocation and relax LibXML's hardcoded limits if applicable. Limits
		// on tags can be set during configuration
		$flags = (LIBXML_VERSION >= 20700) ? LIBXML_COMPACT | LIBXML_PARSEHUGE : 0;

		$useErrors = libxml_use_internal_errors(true);
		$dom       = new DOMDocument;
		$success   = $dom->loadXML($xml, $flags);
		libxml_use_internal_errors($useErrors);

		if (!$success)
		{
			throw new InvalidArgumentException('Cannot load XML: ' . libxml_get_last_error()->message);
		}

		return $dom;
	}

	/**
	* Render an intermediate representation
	*
	* @param  string $xml Intermediate representation
	* @return string      Rendered result
	*/
	public function render($xml)
	{
		if (substr($xml, 0, 3) === '<t>' && substr($xml, -4) === '</t>')
		{
			return $this->renderPlainText($xml);
		}
		else
		{
			return $this->renderRichText(preg_replace('(<[eis]>[^<]*</[eis]>)', '', $xml));
		}
	}

	/**
	* Render an intermediate representation of plain text
	*
	* @param  string $xml Intermediate representation
	* @return string      Rendered result
	*/
	protected function renderPlainText($xml)
	{
		// Remove the <t> and </t> tags
		$html = substr($xml, 3, -4);

		// Replace all <br/> with <br>
		$html = str_replace('<br/>', '<br>', $html);

		// Decode encoded characters from the Supplementary Multilingual Plane
		$html = $this->decodeSMP($html);

		return $html;
	}

	/**
	* Render an intermediate representation of rich text
	*
	* @param  string $xml Intermediate representation
	* @return string      Rendered result
	*/
	abstract protected function renderRichText($xml);

	/**
	* Get the value of a parameter
	*
	* @param  string $paramName
	* @return string
	*/
	public function getParameter($paramName)
	{
		return (isset($this->params[$paramName])) ? $this->params[$paramName] : '';
	}

	/**
	* Get the values of all parameters
	*
	* @return array Associative array of parameter names and values
	*/
	public function getParameters()
	{
		return $this->params;
	}

	/**
	* Set the value of a parameter from the stylesheet
	*
	* @param  string $paramName  Parameter name
	* @param  mixed  $paramValue Parameter's value
	* @return void
	*/
	public function setParameter($paramName, $paramValue)
	{
		$this->params[$paramName] = (string) $paramValue;
	}

	/**
	* Set the values of several parameters from the stylesheet
	*
	* @param  string $params Associative array of [parameter name => parameter value]
	* @return void
	*/
	public function setParameters(array $params)
	{
		foreach ($params as $paramName => $paramValue)
		{
			$this->setParameter($paramName, $paramValue);
		}
	}

	/**
	* Test for the presence of unsupported XML and throw an exception if found
	*
	* @param  string $xml XML
	* @return void
	*/
	protected function checkUnsupported($xml)
	{
		if (preg_match('((?<=<)[!?])', $xml, $m))
		{
			$errors = [
				'!' => 'DTDs, CDATA nodes and comments are not allowed',
				'?' => 'Processing instructions are not allowed'
			];

			throw new InvalidArgumentException($errors[$m[0]]);
		}
	}

	/**
	* Decode encoded characters from the Supplementary Multilingual Plane
	*
	* @param  string $str Encoded string
	* @return string      Decoded string
	*/
	protected function decodeSMP($str)
	{
		if (strpos($str, '&#') === false)
		{
			return $str;
		}

		return preg_replace_callback('(&#(?:x[0-9A-Fa-f]+|[0-9]+);)', __CLASS__ . '::decodeEntity', $str);
	}

	/**
	* Decode a matched SGML entity
	*
	* @param  string[] $m Captures from PCRE
	* @return string      Decoded entity
	*/
	protected static function decodeEntity(array $m)
	{
		return htmlspecialchars(html_entity_decode($m[0], ENT_QUOTES, 'UTF-8'), ENT_COMPAT);
	}

	/**
	* Restore the original locale
	*/
	protected function restoreLocale(): void
	{
		if ($this->savedLocale !== 'C')
		{
			setlocale(LC_NUMERIC, $this->savedLocale);
		}
	}

	/**
	* Temporarily set the locale to C
	*/
	protected function setLocale(): void
	{
		$this->savedLocale = setlocale(LC_NUMERIC, '0');
		if ($this->savedLocale !== 'C')
		{
			setlocale(LC_NUMERIC, 'C');
		}
	}
}