<?php

/**
* @package   s9e\Toolkit
* @copyright Copyright (c) 2010 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\Toolkit\Acl;

use DOMDocument,
    DOMXPath,
	InvalidArgumentException;

/**
* Not cryptographically strong but random enough to avoid false positives
* @codeCoverageIgnore
*/
if (!defined('WILDCARD'))
{
	define('WILDCARD', md5(mt_rand()));
}

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
		$dom = new DOMDocument;
		$dom->loadXML($xml);

		$this->xpath = new DOMXPath($dom);

		$this->rootPath = rtrim($rootPath, '/');
	}

	/**
	* @return string
	*/
	public function wildcard()
	{
		return WILDCARD;
	}

	/**
	* @return bool
	*/
	public function isAllowed($perm, $scope = array())
	{
		return $this->xpath->evaluate("'1'=" . self::buildXPath($this->rootPath, $perm, $scope));
	}

	/**
	* Build the XPath needed to retrieve the character ('0' or '1') that represents given setting
	*
	* @param  string $rootPath XPath to the root node
	* @param  string $perm
	* @param  mixed  $scope
	* @return string
	*/
	static public function buildXPath($rootPath, $perm, $scope = array())
	{
		$nodePath = $rootPath . '/space[@' . $perm . ']';
		$xpath    = 'substring(' . $nodePath . ',1';

		if ($scope instanceof Resource)
		{
			$scope = $scope->getAclReaderScope();
		}

		if ($scope === WILDCARD)
		{
			$xpath .= '+sum(' . $nodePath . '/wildcard/@*)';
		}
		elseif (is_array($scope))
		{
			foreach ($scope as $scopeDim => $scopeVal)
			{
				$xpath .= "+concat('0'," . $nodePath . '/';

				if ($scopeVal === WILDCARD)
				{
					$xpath .= 'wildcard/@' . $scopeDim;
				}
				else
				{
					$xpath .= 'scopes/' . $scopeDim . '/@_' . $scopeVal;
				}

				$xpath .= ')';
			}
		}
		else
		{
			throw new InvalidArgumentException('$scope is expected to be an array, an object that implements Resource or $this->wildcard()');
		}

		$xpath .= ',1)';

		return $xpath;
	}
}