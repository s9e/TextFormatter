<?php

/**
* @package   s9e\Toolkit
* @copyright Copyright (c) 2010 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\Toolkit\Acl;

class Reader
{
	protected $config;

	public function __construct(array $config)
	{
		$this->config = $config;
	}

	public function isAllowed($perm, $scope = array(), $additionalScope = array())
	{
		if (!isset($this->config[$perm]))
		{
			return false;
		}

		$n = $this->getBitNumber($perm, $scope, $additionalScope);

		return (bool) (ord($this->config[$perm]['mask'][$n >> 3]) & (1 << (7 - ($n & 7))));
	}

	public function getPredicate($perm, $dim, $scope = array())
	{
		if (!isset($this->config[$perm]))
		{
			return array('type' => 'none');
		}

		if ($scope instanceof Wildcard)
		{
			$this->normalizeScope($perm, $scope);
			unset($scope[$dim]);

			$global = $this->isAllowed($perm);
		}
		else
		{
			$this->normalizeScope($perm, $scope);
			$global = $this->isAllowed($perm, $scope);
		}

		$space= $this->config[$perm];
		if (!isset($space['scopes'][$dim]))
		{
			// That scope doesn't exist, rely on global setting
			return array('type' => ($global) ? 'all' : 'none');
		}

		if (is_array($scope) && isset($scope[$dim]))
		{
			throw new \InvalidArgumentException('$scope contains the same key we are looking for');
		}

		$n = $this->getBitNumber($perm, $scope);

		$allowed = $denied = array();
		foreach ($space['scopes'][$dim] as $scopeVal => $pos)
		{
			$_n = $n + $pos;

			if (ord($space['mask'][$_n >> 3]) & (1 << (7 - ($_n & 7))))
			{
				$allowed[] = $scopeVal;
			}
			else
			{
				$denied[]  = $scopeVal;
			}
		}

		if ($global)
		{
			if (empty($denied))
			{
				return array('type' => 'all');
			}
			else
			{
				return array(
					'type'  => 'all_but',
					'which' => $denied
				);
			}
		}
		elseif (!empty($allowed))
		{
			return array(
				'type'  => 'some',
				'which' => $allowed
			);
		}
		else
		{
			return array('type' => 'none');
		}
	}

	protected function normalizeScope($perm, &$scope)
	{
		if ($scope instanceof Resource)
		{
			$scope = $scope->getAclReaderScope();
		}

		if ($scope instanceof Wildcard)
		{
			$scope = array_fill_keys(array_keys($this->config[$perm]['wildcard']), $scope);
		}
		elseif (is_array($scope))
		{
			foreach ($scope as &$scopeVal)
			{
				if (is_float($scopeVal))
				{
					/**
					* Floats need to be converted to strings. Booleans should be fine as they
					* are automatically converted to integers when used as array keys
					*/
					$scopeVal = (string) $scopeVal;
				}
			}
		}
		else
		{
			throw new \InvalidArgumentException('$scope is expected to be an array, an object that implements Resource or an instance of Wildcard, ' . gettype($scope) . ' given');
		}
	}

	protected function getBitNumber($perm, $scope)
	{
		$space = $this->config[$perm];
		$n     = $space['perms'][$perm];

		$this->normalizeScope($perm, $scope);

		foreach ($scope as $scopeDim => $scopeVal)
		{
			if ($scopeVal instanceof Wildcard)
			{
				if (isset($space['wildcard'][$scopeDim]))
				{
					$n += $space['wildcard'][$scopeDim];
				}
			}
			elseif (isset($space['scopes'][$scopeDim][$scopeVal]))
			{
				$n += $space['scopes'][$scopeDim][$scopeVal];
			}
		}

		return $n;
	}
}