<?php

/**
* @package   s9e\toolkit
* @copyright Copyright (c) 2010 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\toolkit\acl;

class reader
{
	protected $config;
	public $any;

	public function __construct(array $config)
	{
		$this->config = $config;
		$this->__wakeup();
	}

	public function __sleep()
	{
		return array('config');
	}

	public function __wakeup()
	{
		/**
		* Not cryptographically strong but random enough to avoid false positives
		*/
		$this->any = md5(mt_rand());
	}

	public function __call($perm, $args)
	{
		return $this->isAllowed($perm, (isset($args[0])) ? $args[0] : null);
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
			if (is_array($scope))
			{
				foreach ($scope as $scope_dim => $scope_val)
				{
					if ($scope_val === $this->any)
					{
						if (isset($space['any'][$scope_dim]))
						{
							$n += $space['any'][$scope_dim];
						}
					}
					elseif (isset($space['scopes'][$scope_dim][$scope_val]))
					{
						$n += $space['scopes'][$scope_dim][$scope_val];
					}
				}
			}
			elseif ($scope === $this->any)
			{
				if (isset($space['any']))
				{
					$n += array_sum($space['any']);
				}
			}
			else
			{
				throw new \InvalidArgumentException('$scope is expected to be an array or $this->any');
			}
		}

		return (bool) (ord($space['mask'][$n >> 3]) & (1 << (7 - ($n & 7))));
	}

	public function getPredicate($perm, $k, $scope = null)
	{
		if (!isset($this->config[$perm]))
		{
			return array('type' => 'none');
		}

		$global = $this->isAllowed($perm, $scope);
		$space  = $this->config[$perm];

		if (!isset($space['scopes'][$k]))
		{
			// That scope doesn't exist, rely on global setting
			return array('type' => ($global) ? 'all' : 'none');
		}

		$n      = $space['perms'][$perm];

		if (isset($scope))
		{
			if (is_array($scope))
			{
				if (isset($scope[$k]))
				{
					throw new \InvalidArgumentException('$scope contains the same key we are looking for');
				}

				foreach ($scope as $scope_dim => $scope_val)
				{
					if ($scope_val === $this->any)
					{
						if (isset($space['any'][$scope_dim]))
						{
							$n += $space['any'][$scope_dim];
						}
					}
					elseif (isset($space['scopes'][$scope_dim][$scope_val]))
					{
						$n += $space['scopes'][$scope_dim][$scope_val];
					}
				}
			}
			elseif ($scope === $this->any)
			{
				$global = $this->isAllowed($perm);

				if (isset($space['any']))
				{
					$any = $space['any'];
					unset($any[$k]);

					$n += array_sum($any);
				}
			}
			/**
			* @codeCoverageIgnoreStart That case will normally be caught earlier in the first call
			*                          to isAllowed()
			*/
			else
			{
				throw new \InvalidArgumentException('$scope is expected to be an array or $this->any');
			}
			// @codeCoverageIgnoreEnd
		}

		$yes = $no = array();
		foreach ($space['scopes'][$k] as $scope_val => $pos)
		{
			$_n = $n + $pos;

			if (ord($space['mask'][$_n >> 3]) & (1 << (7 - ($_n & 7))))
			{
				$yes[] = $scope_val;
			}
			else
			{
				$no[]  = $scope_val;
			}
		}

		if ($global)
		{
			if (empty($no))
			{
				return array('type' => 'all');
			}
			else
			{
				return array(
					'type'  => 'all_but',
					'which' => $no
				);
			}
		}
		elseif (!empty($yes))
		{
			return array(
				'type'  => 'some',
				'which' => $yes
			);
		}
		else
		{
			return array('type' => 'none');
		}
	}
}