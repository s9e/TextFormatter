<?php

/**
* @package   s9e\toolkit
* @copyright Copyright (c) 2010 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\toolkit\acl;

class builder
{
	protected $settings = array();
	protected $rules = array(
		'grant'   => array(),
		'require' => array()
	);

	const ALLOW = 1;
	const DENY = 0;

	public function allow($perm, array $scope = array())
	{
		return $this->add(self::ALLOW, $perm, $scope);
	}

	public function deny($perm, array $scope = array())
	{
		return $this->add(self::DENY, $perm, $scope);
	}

	public function addRule($perm, $rule, $foreign_perm)
	{
		/**
		* @todo validate params
		*/
		$this->rules[$rule][$perm][$foreign_perm] = $foreign_perm;
	}

	public function getReader()
	{
		return new reader($this->getReaderConfig());
	}

	public function getReaderConfig()
	{
		return $this->build();
	}

	protected function add($value, $perm, array $scope)
	{
		if ($scope)
		{
			foreach ($scope as $k => &$v)
			{
				switch (gettype($v))
				{
					case 'integer':
						// nothing to do
						break;

					case 'string':
						/**
						* Numbers passed as strings may get cast to integer when they are used as
						* array keys, which happens quite often in our routines. We make sure that
						* this cast happens right now before any processing occurs
						*/
						$v = key(array($v => 0));
						break;

					default:
						throw new \InvalidArgumentException('Invalid type for scope ' . $k . ': integer or string expected, ' . gettype($v) . ' given');
				}
			}

			ksort($scope);
		}

		$this->settings[] = array($perm, $value, $scope);
	}

	protected function build()
	{
		/**
		* Holds the whole access control list
		*/
		$acl = array();

		/**
		* Holds the dimensions used for each permission
		*/
		$perm_dims = array();

		/**
		* Holds the list of scopes used for any permission
		*/
		$used_scopes = array();
		foreach ($this->settings as $setting)
		{
			list($perm, $value, $scope) = $setting;

			foreach ($scope as $dim => $scope_val)
			{
				$perm_dims[$perm][$dim] = $dim;
				$used_scopes[$dim][$scope_val] = $scope_val;
			}

			$k = serialize($scope);

			/**
			* If the current value is not set or is ALLOW, overwrite it
			*/
			if (!isset($acl[$perm][$k])
			 || $value === self::DENY)
			{
				$acl[$perm][$k] = $value;
			}
		}

		/**
		* Add global perms to $perm_dims
		*/
		$perm_dims += array_fill_keys(array_keys($acl), array());

		do
		{
			$grant = array_intersect_key($this->rules['grant'], $acl);
			$loop  = false;

			foreach ($grant as $perm => $foreign_perms)
			{
				foreach ($foreign_perms as $foreign_perm)
				{
					if (!isset($perm_dims[$foreign_perm]))
					{
						/**
						* We need to re-evaluate scopes whenever we create a new perm
						*/
						$loop = true;
						$perm_dims[$foreign_perm] = $acl[$foreign_perm] = array();
					}

					$perm_dims[$foreign_perm] += $perm_dims[$perm];
				}
			}
		}
		while ($loop);

		$used_scopes = array_map('array_values', $used_scopes);
		$bootstrap   = array();
		$inherit     = array('a:0:{}' => array());

		$hash   = crc32(serialize($acl));
		$hashes = array($hash => 1);

		foreach ($perm_dims as $perm => &$dims)
		{
			ksort($dims);
			$key = serialize(array_keys($dims));

			if (!isset($bootstrap[$key]))
			{
				self::unroll($dims, $used_scopes, $bootstrap[$key], $inherit);
			}

			$acl[$perm] = array_merge($bootstrap[$key], $acl[$perm]);
		}
		unset($dims);

		/**
		* After the ACL array has been set up, we prepare the "require" rules
		*/
		$require = array_intersect_key($this->rules['require'], $acl);

		do
		{
			/**
			* STEP 1: apply inheritance
			*/
			foreach ($acl as $perm => &$settings)
			{
				foreach ($settings as $scope => &$setting)
				{
					foreach ($inherit[$scope] as $inherit_scope)
					{
						if ($setting === self::DENY)
						{
							break;
						}

						/**
						* NOTE: here, $setting is either null or self::ALLOW
						*/
						if (isset($settings[$inherit_scope]))
						{
							$setting = $settings[$inherit_scope];
						}
					}
				}
			}
			unset($settings, $setting);

			/**
			* STEP 2: apply "grant" rules
			*/
			$grantors = $grantees = array();

			foreach ($grant as $perm => $foreign_perms)
			{
				foreach ($acl[$perm] as $scope => $setting)
				{
					if ($setting !== self::ALLOW)
					{
						continue;
					}

					foreach ($foreign_perms as $foreign_perm)
					{
						if (isset($acl[$foreign_perm][$scope])
						 && !isset($grantees[$scope][$foreign_perm]))
						{
							/**
							* The foreign perm is either self::ALLOW, in which case there's nothing
							* to do, or self::DENY, which can't be overwritten anyway.
							*
							* We also check whether the foreign perm has been granted during this
							* iteration of the main loop. If it was, we record all the grantors so
							* that we only revoked the foreign perm if ALL of them get revoked.
							*/
							continue;
						}

						$acl[$foreign_perm][$scope] = self::ALLOW;

						$grantors[$scope][$perm][$foreign_perm] = $foreign_perm;
						$grantees[$scope][$foreign_perm][$perm] = $perm;
					}
				}
			}

			/**
			* STEP 3: apply "require" rules
			*/
			foreach ($require as $perm => $foreign_perms)
			{
				foreach ($acl[$perm] as $scope => &$setting)
				{
					foreach ($foreign_perms as $foreign_perm)
					{
						if ($setting !== self::ALLOW)
						{
							break;
						}

						if (isset($acl[$foreign_perm][$scope]))
						{
							/**
							* Here we copy the foreign setting regarless of whether it's self::ALLOW
							* or self::DENY. This is because if the foreign setting is self::DENY,
							* there is no way for this setting to ever be granted anyway, and the
							* same goes for scopes that inherit from it.
							*/
							$setting = $acl[$foreign_perm][$scope];
						}
						else
						{
							$setting = null;
							break;
						}
					}

					if ($setting !== self::ALLOW
					 && isset($grantors[$scope][$perm]))
					{
						/**
						* That permission has been removed by a "require" rule
						*
						* Now, we are going to recursively remove permissions that were
						* granted by (or because of) that permission. Eventually, those
						* permissions will be granted back during the next iteration.
						*/
						$cancel_grantors = array($perm => $perm);

						do
						{
							/**
							* Holds the list of foreign perms that have been granted by each
							* canceled perm
							*/
							$cancel_grantees = array_intersect_key(
							                      $grantors[$scope],
							                      $cancel_grantors
							                   );
							$cancel_grantors = array();

							foreach ($cancel_grantees as $grantor => $_grantees)
							{
								foreach ($_grantees as $grantee)
								{
									/**
									* Here we completely remove any trace of that grant
									*/
									unset(
										$grantors[$scope][$grantor][$grantee],
										$grantees[$scope][$grantee][$grantor]
									);

									if (empty($grantees[$scope][$grantee]))
									{
										/**
										* That grantee has no valid grantors left
										*/
										$acl[$grantee][$scope] = null;
										$cancel_grantors[$grantee] = $grantee;
									}
								}
							}
						}
						while (!empty($cancel_grantors));
					}
				}
				unset($setting);
			}

			/**
			* FINAL STEP: check whether the ACL has changed and exit the loop accordingly
			*/
			$hash = crc32(serialize($acl));
			if (isset($hashes[$hash]))
			{
				break;
			}
			$hashes[$hash] = 1;
		}
		while (1);

		/**
		* Replace allow/null/deny settings with '0' and '1'
		*/
		foreach ($acl as $perm => &$settings)
		{
			foreach ($settings as &$setting)
			{
				$setting = (string) (int) $setting;
			}
		}
		unset($settings, $setting);

		/**
		* @var array unserialize() cache. Note: we could use a global cache that would be fed by the
		*            first loop that creates $acl as well as the routine in $this->bootstrap(), but
		*            the performance gain isn't very important in most cases
		*/
		$u = array();
		foreach ($acl as $perm => $settings)
		{
			$scopes = array_keys(array_diff_key($settings, $u));
			if ($scopes)
			{
				$u += array_combine($scopes, array_map('unserialize', $scopes));
			}
		}

		/**
		* Sort perms by their dimensions to make sure we don't mix up perms with different scopings.
		* We refer to the ensemble of their dimensions as their "space"
		*/
		$perms_per_space = array();
		foreach ($perm_dims as $perm => $dims)
		{
			$perms_per_space[serialize($dims)][$perm] = $acl[$perm];
		}
		unset($acl);

		$ret = array();

		if (isset($perms_per_space['a:0:{}']))
		{
			/**
			* All that confusing array_* mumbo jumbo is to only keep perms that are allowed and
			* assign them pos 0
			*/
			$allowed = array_keys(array_filter(array_map('array_filter', $perms_per_space['a:0:{}'])));

			if ($allowed)
			{
				$space = array(
					'mask'  => "\x80",
					'perms' => array_fill_keys($allowed, 0)
				);

				foreach ($allowed as $perm)
				{
					$ret[$perm] =& $space;
				}
				unset($space);
			}
			unset($perms_per_space['a:0:{}']);
		}

		foreach ($perms_per_space as $space_id => $perms)
		{
			$dims  = unserialize($space_id);
			$space = array();

			/**
			* Remove perms sharing the same mask and perms that are not granted in any scope
			*/
			$masks = $perm_aliases = array();
			foreach ($perms as $perm => $settings)
			{
				$mask = implode('', $settings);

				if (strpos($mask, '1') === false)
				{
					/**
					* This perm isn't granted in any scope, we just remove it
					*/
					unset($perms[$perm]);
					continue;
				}

				if (isset($masks[$mask]))
				{
					$perm_aliases[$perm] = $masks[$mask];
					unset($perms[$perm]);
					continue;
				}

				$masks[$mask] = $perm;
			}

			/**
			* @var array Holds the used scope_vals for each dimension
			*/
			$dim_scopes = array_intersect_key($used_scopes, $dims);

			/**
			* Sort masks per dimension, detect scoped settings that are identical to the global
			* setting
			*/
			$dim_masks = $delete = array();

			foreach ($dim_scopes as $dim => &$scope_vals)
			{
				$scope_vals = array_combine($scope_vals, $scope_vals);

				/**
				* First mark every scope to be deleted, then unmark them individually as we confirm
				* that they differ from their parent scope
				*/
				$delete[$dim] = array_fill_keys($scope_vals, true);
			}
			unset($scope_vals);

			foreach ($perms as $perm => $settings)
			{
				foreach ($settings as $scope => $setting)
				{
					foreach ($u[$scope] as $dim => $scope_val)
					{
						if ($setting !== $settings[$inherit[$scope][$dim]])
						{
							unset($delete[$dim][$scope_val]);
						}

						$mask =& $dim_masks[$dim][$scope_val];
						if (!isset($mask))
						{
							$mask = '';
						}
						$mask .= $setting;
					}
				}
			}
			unset($mask);

			$scopes_per_mask = array();
			foreach ($dim_masks as $dim => $masks)
			{
				foreach ($masks as $scope_val => $mask)
				{
					if (isset($delete[$dim][$scope_val]))
					{
						/**
						* Skip scopes that are already marked for deletion
						*/
						continue;
					}
					$scopes_per_mask[$dim][$mask][] = $scope_val;
				}
			}

			/**
			* Find and remove scoped masks that are identical to each others
			*/
			$scope_aliases = array();
			foreach ($scopes_per_mask as $dim => $masks)
			{
				foreach ($masks as $mask => $scope_vals)
				{
					if (isset($scope_vals[1]))
					{
						/**
						* More than one scope for that mask
						*/
						$scope_val = array_pop($scope_vals);
						$scope_aliases[$dim][$scope_val] = $scope_vals;

						$delete[$dim] += array_flip($scope_vals);
					}
				}
				unset($scope_vals);
			}
			unset($masks);

			if (!empty($delete))
			{
				/**
				* @todo find out why creating $scope_vals as a reference is faster
				*/
				foreach ($u as $scope => &$scope_vals)
				{
					foreach ($scope_vals as $dim => $scope_val)
					{
						if (isset($delete[$dim][$scope_val]))
						{
							foreach ($perms as $perm => &$settings)
							{
								unset($settings[$scope]);
							}
							break;
						}
					}
				}
				unset($scope_vals, $settings);

				foreach ($delete as $dim => $scope_vals)
				{
					$dim_scopes[$dim] = array_diff_key($dim_scopes[$dim], $scope_vals);
				}
			}

			if (empty($perms))
			{
				/**
				* No perms left in this space
				*/
				continue;
			}

			/**
			* Add the "any" bit
			*/
			$mask = '';
			foreach ($perms as $perm => $settings)
			{
				$mask .= implode('', $settings);
			}

			$step      = 1;
			$chunk_len = 0;

			foreach ($dim_scopes as $dim => $scope_vals)
			{
				$chunk_len  += $step;
				$step        = $chunk_len;
				$repeat      = 1 + count($scope_vals);
				$chunk_len  *= $repeat;

				$space['any'][$dim] = $chunk_len;

				$i = 0;
				foreach ($scope_vals as $scope_val)
				{
					$pos = ++$i * $step;
					$space['scopes'][$dim][$scope_val] = $pos;

					if (isset($scope_aliases[$dim][$scope_val]))
					{
						$space['scopes'][$dim] += array_fill_keys($scope_aliases[$dim][$scope_val], $pos);
					}
				}

				$tmp = '';
				foreach (str_split($mask, $chunk_len) as $chunk)
				{
					$tmp .= $chunk;

					$i = 0;
					do
					{
						$j = 0;
						do
						{
							if ($chunk[$i + $j * $step] === '1')
							{
								$tmp .= '1';
								continue 2;
							}
						}
						while (++$j < $repeat);

						$tmp .= '0';
					}
					while (++$i < $step);
				}
				$mask = $tmp;
			}

			$perm_masks = str_split($mask, $chunk_len + $step);
			$space_mask = self::mergeMasks($perm_masks);

			foreach (array_keys($perms) as $i => $perm)
			{
				$pos = strpos($space_mask, $perm_masks[$i]);
				$space['perms'][$perm] = $pos;

				$space['perms'] += array_fill_keys(array_keys($perm_aliases, $perm, true), $pos);
			}

			$space['mask'] = implode('', array_map('chr', array_map('bindec', str_split($space_mask . str_repeat('0', 8 - (strlen($space_mask) & 7)), 8))));

			foreach ($space['perms'] as $perm => $pos)
			{
				$ret[$perm] =& $space;
			}
			unset($space);
		}

		return $ret;
	}

	static protected function unroll(array $dims, array $used_scopes, &$bootstrap, array &$inherit)
	{
		$bootstrap = array('a:0:{}' => null);

		switch (count($dims))
		{
			case 0:
				// do nothing
				return;

			case 1:
				$dim = end($dims);

				foreach ($used_scopes[$dim] as $scope_val)
				{
					$scope_key             = serialize(array($dim => $scope_val));
					$bootstrap[$scope_key] = null;
					$inherit[$scope_key]   = array($dim => 'a:0:{}');
				}
				return;

			default:
				$scope_cnt = array();
				foreach ($dims as $dim)
				{
					/**
					* We add 1 for the global scope
					*/
					$scope_cnt[$dim] = 1 + count($used_scopes[$dim]);
				}

				$n   = 0;
				$max = array_product($scope_cnt);

				do
				{
					$scope = array();
					$div   = 1;

					foreach ($scope_cnt as $dim => $cnt)
					{
						$j    = (int) ($n / $div) % $cnt;
						$div *= $cnt;

						if ($j)
						{
							$scope[$dim] = $used_scopes[$dim][$j - 1];
						}
					}

					$scope_key = serialize($scope);

					foreach ($scope as $dim => $scope_val)
					{
						$tmp = $scope;
						unset($tmp[$dim]);

						$inherit[$scope_key][$dim] = serialize($tmp);
					}

					$bootstrap[$scope_key] = null;
				}
				while (++$n < $max);
		}
	}

	static protected function mergeMasks(array $masks)
	{
		$i   = count($masks);
		$max = max(array_map('strlen', $masks)) - 1;

		while (--$i)
		{
			$min = 0;

			foreach ($masks as $k => $mask)
			{
				$tmp = $masks;
				$tmp[$k] = '';
				$tmp = implode(' ', $tmp) . ' ';

				$len = $max;

				do
				{
					$pos = strpos($tmp, substr($mask, 0, $len) . ' ');

					if ($pos !== false)
					{
						$left  = substr_count($tmp, ' ', 0, $pos);
						$right = $k;
						$min   = $len;

						if ($len === $max)
						{
							break 2;
						}
						break;
					}
				}
				while (--$len > $min);
			}

			if (!$min)
			{
				break;
			}

			/**
			* We adjust $max as we progress, as we know that matches will only get shorter.
			* Therefore, we can never match more than $min characters
			*/
			$max = $min;

			$masks[$left] .= substr($masks[$right], $min);
			unset($masks[$right]);

			/**
			* Because we remove the longest matches from the top of the list, we shift a few entries
			* to the bottom of the list so that we can get to new masks faster instead of
			* reevaluating the same first few entries over and over.
			*
			* Also, array_shift() reindexes the array, which is essential for the algorithm to
			* function
			*/
			do
			{
				$masks[] = array_shift($masks);
			}
			while (--$right >= 0);
		}

		return implode('', $masks);
	}
}