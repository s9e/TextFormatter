<?php

/**
* @package   s9e\Toolkit
* @copyright Copyright (c) 2010 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\Toolkit\Acl;

/**
* Not cryptographically strong but random enough to avoid false positives
*/
define('ANY', md5(mt_rand()));

class Reader
{
	protected $config;

	public function __construct(array $config)
	{
		$this->config = $config;
	}

	public function any()
	{
		return ANY;
	}

	public function isAllowed($perm, $scope = null)
	{
		if (!isset($this->config[$perm]))
		{
			return false;
		}

		$space = $this->config[$perm];
		$n     = $space['perms'][$perm];

		if (isset($scope))
		{
			if ($scope instanceof Resource)
			{
				$scope = $scope->getAclReaderScope();
			}

			if (is_array($scope))
			{
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

					if ($scopeVal === ANY)
					{
						if (isset($space['any'][$scopeDim]))
						{
							$n += $space['any'][$scopeDim];
						}
					}
					elseif (isset($space['scopes'][$scopeDim][$scopeVal]))
					{
						$n += $space['scopes'][$scopeDim][$scopeVal];
					}
				}
			}
			elseif ($scope === ANY)
			{
				if (isset($space['any']))
				{
					$n += array_sum($space['any']);
				}
			}
			else
			{
				throw new \InvalidArgumentException('$scope is expected to be an array or ' . __NAMESPACE__ . '\\ANY');
			}
		}

		return (bool) (ord($space['mask'][$n >> 3]) & (1 << (7 - ($n & 7))));
	}

	public function getPredicate($perm, $dim, $scope = null)
	{
		if (!isset($this->config[$perm]))
		{
			return array('type' => 'none');
		}

		$global = $this->isAllowed($perm, $scope);
		$space  = $this->config[$perm];

		if (!isset($space['scopes'][$dim]))
		{
			// That scope doesn't exist, rely on global setting
			return array('type' => ($global) ? 'all' : 'none');
		}

		$n      = $space['perms'][$perm];

		if (isset($scope))
		{
			if ($scope instanceof Resource)
			{
				$scope = $scope->getAclReaderScope();
			}

			if (is_array($scope))
			{
				if (isset($scope[$dim]))
				{
					throw new \InvalidArgumentException('$scope contains the same key we are looking for');
				}

				foreach ($scope as $scopeDim => $scopeVal)
				{
					if (is_float($scopeVal))
					{
						/**
						* Doubles need to be converted to strings. Booleans should be fine
						*/
						$scopeVal = (string) $scopeVal;
					}

					if ($scopeVal === ANY)
					{
						if (isset($space['any'][$scopeDim]))
						{
							$n += $space['any'][$scopeDim];
						}
					}
					elseif (isset($space['scopes'][$scopeDim][$scopeVal]))
					{
						$n += $space['scopes'][$scopeDim][$scopeVal];
					}
				}
			}
			elseif ($scope === ANY)
			{
				$global = $this->isAllowed($perm);

				if (isset($space['any']))
				{
					$any = $space['any'];
					unset($any[$dim]);

					$n += array_sum($any);
				}
			}
			/**
			* @codeCoverageIgnoreStart That case will normally be caught earlier in the first call
			*                          to isAllowed()
			*/
			else
			{
				throw new \InvalidArgumentException('$scope is expected to be an array, an object that implements ' . __NAMESPACE__ . '\\Resource, or ' . __NAMESPACE__ . '\\ANY');
			}
			// @codeCoverageIgnoreEnd
		}

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
}