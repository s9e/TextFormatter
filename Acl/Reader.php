<?php

/**
* @package   s9e\Toolkit
* @copyright Copyright (c) 2010 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\Toolkit\Acl;

/**
* Not cryptographically strong but random enough to avoid false positives
* @codeCoverageIgnore
*/
if (!defined('WILDCARD'))
{
	define('WILDCARD', md5(mt_rand()));
}

class Reader
{
	protected $config;

	public function __construct(array $config)
	{
		$this->config = $config;
	}

	public function wildcard()
	{
		return WILDCARD;
	}

	public function isAllowed($perm, $scope = array())
	{
		if (!isset($this->config[$perm]))
		{
			return false;
		}

		$n = $this->getBitNumber($perm, $scope);

		return (bool) (ord($this->config[$perm]['mask'][$n >> 3]) & (1 << (7 - ($n & 7))));
	}

	public function getPredicate($perm, $dim, $scope = array())
	{
		if (!isset($this->config[$perm]))
		{
			return array('type' => 'none');
		}

		$space  = $this->config[$perm];

		if ($scope === WILDCARD)
		{
			$scope = array_fill_keys(array_keys($space['wildcard']), WILDCARD);
			unset($scope[$dim]);

			$global = $this->isAllowed($perm);
		}
		else
		{
			$global = $this->isAllowed($perm, $scope);
		}

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

	protected function getBitNumber($perm, &$scope)
	{
		$space = $this->config[$perm];
		$n     = $space['perms'][$perm];

		if ($scope instanceof Resource)
		{
			$scope = $scope->getAclReaderScope();
		}

		if (is_array($scope))
		{
			if (isset($scope[WILDCARD]))
			{
				/**
				* Wildcard used as a key
				*/
				$scope += array_fill_keys(array_keys($space['wildcard']), $scope[WILDCARD]);
				unset($scope[WILDCARD]);
			}

			foreach ($scope as $scopeDim => $scopeVal)
			{
				if (is_float($scopeVal))
				{
					/**
					* Floats need to be converted to strings. Booleans should be fine as they
					* are automatically converted to integers when used as array keys
					*/
					$scopeVal = (string) $scopeVal;
				}

				if (isset($space['scopes'][$scopeDim][$scopeVal]))
				{
					$n += $space['scopes'][$scopeDim][$scopeVal];
				}
				elseif ($scopeVal === WILDCARD)
				{
					if (isset($space['wildcard'][$scopeDim]))
					{
						$n += $space['wildcard'][$scopeDim];
					}
				}
			}
		}
		elseif ($scope === WILDCARD)
		{
			if (isset($space['wildcard']))
			{
				$n += array_sum($space['wildcard']);
			}
		}
		else
		{
			throw new \InvalidArgumentException('$scope is expected to be an array, an object that implements Resource or $this->wildcard(), ' . gettype($scope) . ' given');
		}

		return $n;
	}
}