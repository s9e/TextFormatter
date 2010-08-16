<?php

/**
* @package   s9e\Toolkit
* @copyright Copyright (c) 2010 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\Toolkit\Acl;

/**
* This class is not meant to be as feature-rich as the default reader. Instead, it provides support
* for querying an ACL stored in XML form.
*/
class XMLReader
{
	/**
	* @var \DOMXPath
	*/
	protected $xpath;

	/**
	* @var string Path to the root node
	*/
	protected $rootPath;

	public function __construct($xml, $rootPath = '/acl')
	{
		$dom = new \DOMDocument;
		$dom->loadXML($xml);

		$this->xpath = new \DOMXPath($dom);

		$this->rootPath = rtrim($rootPath, '/');
	}

	/**
	* @return bool
	*/
	public function isAllowed($perm, array $scope = array())
	{
		return $this->xpath->evaluate("'1'=" . self::buildXPath($this->rootPath, $perm, $scope));
	}

	/**
	* Build the XPath needed to retrieve the character ('0' or '1') representing given perm/scope
	*
	* @param  string $rootPath XPath to the root node
	* @param  string $perm
	* @param  array  $scope
	* @return string
	*/
	static public function buildXPath($rootPath, $perm, array $scope = array())
	{
		$nodePath = $rootPath . '/space[@' . $perm . ']';
		$xpath    = 'substring(' . $nodePath . ',1';

		foreach ($scope as $scopeDim => $scopeVal)
		{
			$xpath .= '+concat("0",' . $nodePath . '/scopes/' . $scopeDim . '/@_' . $scopeVal . ')';
		}

		$xpath .= ',1)';

		return $xpath;
	}
}