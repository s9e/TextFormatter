<?php

/**
* @package   s9e\Toolkit
* @copyright Copyright (c) 2010 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\Toolkit\Acl;

class Acl
{
	/**
	* @var array List of settings directly associated with this ACL 
	*/
	protected $settings = array();

	/**
	* @var array List of rules directly associated with this ACL 
	*/
	protected $rules = array(
		'grant'   => array(),
		'require' => array()
	);

	/**
	* @var array List of instances of Acl that this ACL inherits from
	*/
	protected $parents = array();

	/**
	* @var array List of instances of Acl that inherit from this
	*/
	protected $children = array();

	/**
	* @var Reader
	*/
	protected $reader;

	const ALLOW = 1;
	const DENY  = 0;

	/**
	* Grant a permission in given scope
	*
	* This method is chainable.
	*
	* @param  string $perm  Permission name
	* @param  array  $scope Permission scope
	* @return Acl           $this
	*/
	public function allow($perm, $scope = array())
	{
		return $this->add(self::ALLOW, $perm, $scope);
	}

	/**
	* Deny a permission in given scope
	*
	* This method is chainable.
	*
	* @param  string $perm  Permission name
	* @param  array  $scope Permission scope
	* @return Acl           $this
	*/
	public function deny($perm, $scope = array())
	{
		return $this->add(self::DENY, $perm, $scope);
	}

	/**
	* Add a rule
	*
	* This method is chainable.
	*
	* @param  string $perm         Permission name
	* @param  string $rule         Rule name: either "grant" or "require"
	* @param  string $foreignPerm  Permission name
	* @return Acl                  $this
	*/
	public function addRule($perm, $rule, $foreignPerm)
	{
		/**
		* @todo validate params
		*/
		$this->rules[$rule][$perm][$foreignPerm] = $foreignPerm;

		$this->unsetReader();

		return $this;
	}

	public function getReader()
	{
		if (isset($this->reader))
		{
			return $this->reader;
		}

		if (!class_exists(__NAMESPACE__ . '\\Reader'))
		{
			include __DIR__ . '/Reader.php';
		}

		return $this->reader = new Reader($this->getReaderConfig());
	}

	public function getReaderConfig()
	{
		return self::asArray($this->buildSpaces());
	}

	public function getReaderXML()
	{
		return self::asXML($this->buildSpaces());
	}

	public function getSettings($includeParents = true)
	{
		$combinedSettings = $this->settings;

		if ($includeParents)
		{
			foreach ($this->parents as $parent)
			{
				$combinedSettings = array_merge($combinedSettings, $parent->getSettings());
			}
		}

		return $combinedSettings;
	}

	public function getRules($includeParents = true)
	{
		$combinedRules = $this->rules;

		if ($includeParents)
		{
			foreach ($this->parents as $parent)
			{
				$combinedRules = array_merge_recursive($combinedRules, $parent->getRules());
			}
		}

		return $combinedRules;
	}

	public function addParent(Acl $acl)
	{
		$this->parents[] = $acl;
		$acl->children[] = $this;
		$this->unsetReader();

		return $this;
	}

	public function isAllowed($perm, $scope = array(), $additionalScope = array())
	{
		return $this->getReader()->isAllowed($perm, $scope, $additionalScope);
	}

	public function getPredicate($perm, $dim, $scope = array())
	{
		return $this->getReader()->getPredicate($perm, $dim, $scope);
	}

	protected function unsetReader()
	{
		unset($this->reader);

		foreach ($this->children as $child)
		{
			$child->unsetReader();
		}
	}

	protected function add($value, $perm, $scope)
	{
		if ($scope instanceof Resource)
		{
			$scope = $scope->getAclBuilderScope();
		}

		if (is_array($scope))
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

					case 'boolean':
						$v = (int) $v;
						break;

					case 'double':
						$v = (string) $v;
						break;

					default:
						throw new \InvalidArgumentException('Invalid type for scope ' . $k . ': integer or string expected, ' . gettype($v) . ' given');
				}
			}
			unset($v);

			ksort($scope);
		}
		else
		{
			throw new \InvalidArgumentException('Scope must be an array or an object that implements ' . __NAMESPACE__ . '\\Resource, ' . gettype($scope) . ' given');
		}

		$this->settings[] = array($perm, $value, $scope);

		$this->unsetReader();

		return $this;
	}

	protected function buildSpaces()
	{
		/**
		* Holds this ACL's settings as well as its parents'
		*/
		$combinedSettings = $this->getSettings();

		/**
		* Holds this ACL's rulesas well as its parents'
		*/
		$combinedRules = $this->getRules();

		/**
		* Holds the whole access control list
		*/
		$acl = array();

		/**
		* Holds the dimensions used for each permission
		*/
		$permDims = array();

		/**
		* @var array Holds the list of scopes used in any permission
		*/
		$usedScopes = array();

		foreach ($combinedSettings as $setting)
		{
			list($perm, $value, $scope) = $setting;

			foreach ($scope as $dim => $scopeVal)
			{
				$permDims[$perm][$dim] = $dim;
				$usedScopes[$dim][$scopeVal] = $scopeVal;
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
		* Sort scope values so that the result bitfield is the same no matter in which order the
		* settings were added. It doesn't cost much and in some cases it might help detect whether
		* new settings have actually changed the ACL.
		*
		* Also sort the dimensions, this is necessary for the "wildcard" bit loop
		*/
		ksort($usedScopes);
		foreach ($usedScopes as $dim => &$scopeVals)
		{
			ksort($scopeVals);
		}
		unset($scopeVals);

		/**
		* Add global perms to $permDims
		*/
		$permDims += array_fill_keys(array_keys($acl), array());

		do
		{
			$loop = false;

			foreach ($combinedRules as $type => $rules)
			{
				foreach ($rules as $perm => $foreignPerms)
				{
					if (!isset($acl[$perm]))
					{
						continue;
					}

					foreach ($foreignPerms as $foreignPerm)
					{
						if (!isset($permDims[$foreignPerm]))
						{
							/**
							* We need to re-evaluate scopes whenever we create a new perm
							*/
							$loop = true;
							$permDims[$foreignPerm] = $acl[$foreignPerm] = array();
						}

						$permDims[$foreignPerm] += $permDims[$perm];
					}
				}
			}
		}
		while ($loop);

		$usedScopes = array_map('array_values', $usedScopes);
		$bootstrap  = array();
		$inherit    = array('a:0:{}' => array());

		foreach ($permDims as $perm => &$dims)
		{
			ksort($dims);
			$key = serialize(array_keys($dims));

			if (!isset($bootstrap[$key]))
			{
				self::unroll($dims, $usedScopes, $bootstrap[$key], $inherit);
			}

			$acl[$perm] = array_merge($bootstrap[$key], $acl[$perm]);
		}
		unset($dims, $bootstrap);

		/**
		* Apply inheritance, grant/require rules
		*/
		self::resolveAcl($acl, $combinedRules, $inherit);

		/**
		* Remove perms that are not granted in any scope
		*/
		self::removeEmptyPerms($acl);

		/**
		* Adjust $permDims
		*/
		$permDims = array_intersect_key($permDims, $acl);

		if (empty($acl))
		{
			return array();
		}

		//======================================================================
		// The ACL is ready, we prepare to reduce it
		//======================================================================

		/**
		* Replace allow/null/deny settings with '0' and '1'
		*/
		self::normalizeSettingsRepresentation($acl);

		/**
		* @var array unserialize() cache. Note: we could use a global cache that would be fed by the
		*            first loop that creates $acl as well as the routine in $this->bootstrap(), but
		*            the performance gain isn't very important in most cases
		*/
		$u = self::getUnserializeCache($acl);

		//======================================================================
		// Here we reduce the ACL by optimizing away redundant stuff
		//======================================================================

		/**
		* First we sort perms by their dimensions
		*/
		$permsPerSpace = self::seedUniverse($permDims, $acl);
		unset($permDims, $acl);

		/**
		* @var array
		*/
		$spaces = array();

		/**
		* We iterate over $permsPerSpace using references so that we operate on the original
		* rather than a copy. This way, we can alter it and see the modifications in the next
		* iteration. This property is essential for optimizing away whole dimensions
		*/
		foreach ($permsPerSpace as $spaceId => &$perms)
		{
			if ($spaceId === 'a:0:{}'
			 || empty($perms))
			{
				/**
				* We ignore empty spaces for obvious reasons, and we do the global space separately
				* as it does not require all that processing: no wildcard, no dedup, etc...
				*/
				continue;
			}

			/**
			* Remove perms sharing the same mask and perms that are not granted in any scope
			*/
			$permAliases = self::dedupPermsByMask($perms);

			$space = self::generateSpace($permsPerSpace, $spaceId, $usedScopes, $inherit, $u);

			foreach ($permAliases as $originalPerm => $aliases)
			{
				$pos = $space['perms'][$originalPerm];

				foreach ($aliases as $alias)
				{
					$space['perms'][$alias] = $pos;
				}
			}

			if ($space)
			{
				$spaces[] = $space;
			}
		}
		unset($perms);

		if (!empty($permsPerSpace['a:0:{}']))
		{
			/**
			* All that confusing array_* mumbo jumbo is to only keep perms that are allowed and
			* assign them pos 0
			*/
			$allowed = array_keys(array_filter(array_map('array_filter', $permsPerSpace['a:0:{}'])));

			if ($allowed)
			{
				$spaces[] = array(
					'mask'  => "\x80",
					'perms' => array_fill_keys($allowed, 0)
				);
			}
		}

		return $spaces;
	}

	static protected function asXML(array $spaces)
	{
		$xml = new \XMLWriter;
		$xml->openMemory();
		$xml->startElement('acl');

		foreach ($spaces as $space)
		{
			$xml->startElement('space');

			foreach ($space['perms'] as $perm => $pos)
			{
				$xml->writeAttribute($perm, $pos);
			}

			if (!empty($space['scopes']))
			{
				$xml->startElement('scopes');
				foreach ($space['scopes'] as $scopeDim => $scopeVals)
				{
					$xml->startElement($scopeDim);
					foreach ($scopeVals as $scopeVal => $pos)
					{
						$xml->writeAttribute('_' . $scopeVal, $pos);
					}
					$xml->endElement();
				}
				$xml->endElement();
			}

			if (!empty($space['wildcard']))
			{
				$xml->startElement('wildcard');
				foreach ($space['wildcard'] as $scopeDim => $pos)
				{
					$xml->writeAttribute($scopeDim, $pos);
				}
				$xml->endElement();
			}

			foreach (str_split($space['mask']) as $c)
			{
				$xml->text(substr('0000000' . decbin(ord($c)), -8));
			}

			$xml->endElement();
		}
		$xml->endDocument();

		return trim($xml->outputMemory(true));
	}

	static protected function asArray(array $spaces)
	{
		$ret = array();
		foreach ($spaces as &$space)
		{
			foreach ($space['perms'] as $perm => $pos)
			{
				$ret[$perm] =& $space;
			}

			/**
			* Remove perms that use position 0 from the list
			*/
			$space['perms'] = array_filter($space['perms']);

			if (empty($space['perms']))
			{
				unset($space['perms']);
			}
		}

		return $ret;
	}

	static protected function unroll(array $dims, array $usedScopes, &$bootstrap, array &$inherit)
	{
		$bootstrap = array('a:0:{}' => null);

		switch (count($dims))
		{
			case 0:
				// do nothing
				return;

			case 1:
				$dim = end($dims);

				foreach ($usedScopes[$dim] as $scopeVal)
				{
					$scopeKey             = serialize(array($dim => $scopeVal));
					$bootstrap[$scopeKey] = null;
					$inherit[$scopeKey]   = array($dim => 'a:0:{}');
				}
				return;

			default:
				$scopeCnt = array();
				foreach ($dims as $dim)
				{
					/**
					* We add 1 for the global scope
					*/
					$scopeCnt[$dim] = 1 + count($usedScopes[$dim]);
				}

				$n   = 0;
				$max = array_product($scopeCnt);

				do
				{
					$scope = array();
					$div   = 1;

					foreach ($scopeCnt as $dim => $cnt)
					{
						$j    = (int) ($n / $div) % $cnt;
						$div *= $cnt;

						if ($j)
						{
							$scope[$dim] = $usedScopes[$dim][$j - 1];
						}
					}

					$scopeKey = serialize($scope);

					foreach ($scope as $dim => $scopeVal)
					{
						$tmp = $scope;
						unset($tmp[$dim]);

						$inherit[$scopeKey][$dim] = serialize($tmp);
					}

					$bootstrap[$scopeKey] = null;
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

	/**
	* Receive an array of spaces, create all subspaces
	*
	* This method will receive an array where keys are every existing perms and their value is an
	* array containing their possible dimensions (both as keys and values).
	*
	* For instance, $permDims will look something like:
	*    array('perm' => array('x' => 'x'), 'otherperm' => array('x' => 'x', 'y' => 'y'))
	*
	* It will return an array where keys are every possible space and subspace (for instance, in the
	* previous example, it would return a serialized (x,y) space plus two subspaces (x) and (y) plus
	* one global space) and their values are an array with perms are keys and and settings as
	* values. All that ordering by their number of dimensions descending, so that if we remove a
	* dimension from a perm during a loop, we can keep processing the perms normally and deal with
	* the newly-reduced perm in a later iteration.
	*/
	static protected function seedUniverse(array $permDims, array $acl)
	{
		$permsPerSpace = array('a:0:{}' => array());

		foreach ($permDims as $perm => $dims)
		{
			$spaceId = serialize($dims);

			if (!isset($permsPerSpace[$spaceId]))
			{
				$dims = array_values($dims);
				$i    = pow(2, count($dims));

				while (--$i >= 0)
				{
					$subspace = array();
					foreach ($dims as $k => $dim)
					{
						if ($i & pow(2, $k))
						{
							$subspace[$dim] = $dim;
						}
					}
					$subspaceId = serialize($subspace);

					if (!isset($permsPerSpace[$subspaceId]))
					{
						$permsPerSpace[$subspaceId] = array();
					}
				}
			}

			$permsPerSpace[$spaceId][$perm] = $acl[$perm];
		}

		/**
		* Sort the spaces by their number of dimensions, descending
		*/
		uksort($permsPerSpace, function($a, $b)
		{
			return count(unserialize($b)) - count(unserialize($a));
		});

		return $permsPerSpace;
	}

	/**
	* Resolve inheritance/apply rules to an ACL
	*
	* @param  array &$acl
	* @param  array $rules
	* @param  array $inherit
	* @return void
	*/
	static protected function resolveAcl(array &$acl, array $rules, array $inherit)
	{
		//======================================================================
		// This is the loop that builds the whole ACL
		//======================================================================

		$hash    = crc32(serialize($acl));
		$hashes  = array($hash => 1);

		$grant   = array_intersect_key($rules['grant'], $acl);
		$require = array_intersect_key($rules['require'], $acl);

		do
		{
			//==================================================================
			// STEP 1: apply inheritance
			//==================================================================

			foreach ($acl as $perm => &$settings)
			{
				foreach ($settings as $scope => &$setting)
				{
					foreach ($inherit[$scope] as $inheritScope)
					{
						if ($setting === self::DENY)
						{
							break;
						}

						/**
						* NOTE: here, $setting is either null or self::ALLOW, and the inherited
						*       setting is either self::ALLOW or self::DENY, both of which can
						*       unconditionally overwrite the current setting
						*/
						if (isset($settings[$inheritScope]))
						{
							$setting = $settings[$inheritScope];
						}
					}
				}
			}
			unset($settings, $setting);

			//==================================================================
			// STEP 2: apply "grant" rules
			//==================================================================

			$grantors = $grantees = array();

			foreach ($grant as $perm => $foreignPerms)
			{
				foreach ($acl[$perm] as $scope => $setting)
				{
					if ($setting !== self::ALLOW)
					{
						continue;
					}

					foreach ($foreignPerms as $foreignPerm)
					{
						if (isset($acl[$foreignPerm][$scope])
						 && !isset($grantees[$scope][$foreignPerm]))
						{
							/**
							* The foreign perm is either self::ALLOW, in which case there's nothing
							* to do, or self::DENY, which can't be overwritten anyway.
							*
							* We also check whether the foreign perm has been granted during this
							* iteration of the main loop. If it was, we record all the grantors so
							* that we only revoke the foreign perm if ALL of them get revoked.
							*/
							continue;
						}

						$acl[$foreignPerm][$scope] = self::ALLOW;

						$grantors[$scope][$perm][$foreignPerm] = $foreignPerm;
						$grantees[$scope][$foreignPerm][$perm] = $perm;
					}
				}
			}

			//==================================================================
			// STEP 3: apply "require" rules
			//==================================================================

			foreach ($require as $perm => $foreignPerms)
			{
				foreach ($acl[$perm] as $scope => &$setting)
				{
					foreach ($foreignPerms as $foreignPerm)
					{
						if ($setting !== self::ALLOW)
						{
							break;
						}

						if (isset($acl[$foreignPerm][$scope]))
						{
							/**
							* Here we copy the foreign setting regarless of whether it's self::ALLOW
							* or self::DENY. This is because if the foreign setting is self::DENY,
							* there is no way for this setting to ever be granted anyway, and the
							* same goes for scopes that inherit from it.
							*/
							$setting = $acl[$foreignPerm][$scope];
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
						$cancelGrantors = array($perm => $perm);

						do
						{
							/**
							* @var array Holds the list of foreign perms that have been granted by
							*            each canceled perm
							*/
							$cancelGrantees = array_intersect_key(
							                      $grantors[$scope],
							                      $cancelGrantors
							                   );
							$cancelGrantors = array();

							foreach ($cancelGrantees as $grantor => $_grantees)
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
										$cancelGrantors[$grantee] = $grantee;
									}
								}
							}
						}
						while (!empty($cancelGrantors));
					}
				}
				unset($setting);
			}

			//==================================================================
			// FINAL STEP: check whether the ACL has changed and exit the loop accordingly
			//==================================================================

			$hash = crc32(serialize($acl));
			if (isset($hashes[$hash]))
			{
				break;
			}
			$hashes[$hash] = 1;
		}
		while (1);
	}

	/**
	* Replace an ACL's settings with their text representation: '0' or '1'
	*
	* @param  array &$acl
	* @return void
	*/
	static protected function normalizeSettingsRepresentation(array &$acl)
	{
		foreach ($acl as $perm => &$settings)
		{
			foreach ($settings as &$setting)
			{
				$setting = (string) (int) $setting;
			}
		}
	}

	/**
	* Precompute the result of unserialize() applied to every the scopes of an ACL
	*
	* @param  array $acl
	* @return array      array('a:1:{s:1:"x";i:1;}' => array('x' => 1))
	*/
	static protected function getUnserializeCache(array $acl)
	{
		$scopes = array();
		foreach ($acl as $perm => $settings)
		{
			/**
			* Accumulate all settings, even though we're only interested in their array key/scope
			*/
			$scopes += $settings;
		}
		$scopes = array_keys($scopes);

		return array_combine($scopes, array_map('unserialize', $scopes));
	}

	static protected function generateSpace(array &$permsPerSpace, $spaceId, array $usedScopes, array $inherit, array $u)
	{
		$perms =& $permsPerSpace[$spaceId];

		$dims  = unserialize($spaceId);
		$space = array('perms' => array());

		$move = array();

		foreach ($perms as $perm => &$settings)
		{
			$deleteDims = $dims;

			foreach ($settings as $scope => $setting)
			{
				foreach ($u[$scope] as $dim => $scopeVal)
				{
					if ($setting !== $settings[$inherit[$scope][$dim]])
					{
						/**
						* That setting differs from the setting it inherits from, we can't
						* delete its dimensions
						*/
						unset($deleteDims[$dim]);

						if (empty($deleteDims))
						{
							break 2;
						}
					}
				}
			}

			if ($deleteDims)
			{
				$newDims    = array_diff_key($dims, $deleteDims);
				$newSpaceId = serialize($newDims);

				$move[$newSpaceId][$perm] = $deleteDims;
			}
		}
		unset($settings);

		foreach ($move as $newSpaceId => $movingPerms)
		{
			if (count($movingPerms) + count($permsPerSpace[$newSpaceId]) < 2)
			{
				/**
				* Do not move that perm if it's going to be the only perm of that space. In that
				* case it's more efficient to leave it in its current space rather than creating
				* a new one because many perms can share the same space by reference at
				* virtually no cost
				*/
				continue;
			}

			foreach ($movingPerms as $perm => $deleteDims)
			{
				foreach ($u as $scope => &$scopeVals)
				{
					if (isset($perms[$perm][$scope]))
					{
						foreach ($deleteDims as $dim)
						{
							if (isset($scopeVals[$dim]))
							{
								unset($perms[$perm][$scope]);
							}
						}
					}
				}
				$permsPerSpace[$newSpaceId][$perm] = $perms[$perm];
				unset($perms[$perm], $scopeVals);
			}
		}
		unset($movingPerms);

		if (empty($perms))
		{
			/**
			* No perms left in this space
			*/
			return array();
		}

		/**
		* @var array Holds the used scopeVals for each dimension
		*/
		$dimScopes = array_intersect_key($usedScopes, $dims);

		/**
		* Sort masks per dimension, detect scoped settings that are identical to the global
		* setting
		*/
		$dimMasks = $delete = array();

		foreach ($dimScopes as $dim => &$scopeVals)
		{
			$scopeVals = array_combine($scopeVals, $scopeVals);

			/**
			* First mark every scope to be deleted, then unmark them individually as we confirm
			* that they differ from their parent scope
			*/
			$delete[$dim] = array_fill_keys($scopeVals, true);
		}
		unset($scopeVals);

		foreach ($perms as $perm => $settings)
		{
			foreach ($settings as $scope => $setting)
			{
				foreach ($u[$scope] as $dim => $scopeVal)
				{
					if ($setting !== $settings[$inherit[$scope][$dim]])
					{
						/**
						* That setting differs from the setting it inherits from, we can't
						* delete its scope value
						*/
						unset($delete[$dim][$scopeVal]);
					}

					$mask =& $dimMasks[$dim][$scopeVal];
					if (!isset($mask))
					{
						$mask = '';
					}
					$mask .= $setting;
				}
			}
		}
		unset($mask);

		$scopesPerMask = array();
		foreach ($dimMasks as $dim => $masks)
		{
			foreach ($masks as $scopeVal => $mask)
			{
				if (isset($delete[$dim][$scopeVal]))
				{
					/**
					* Skip scopes that are already marked for deletion
					*/
					continue;
				}
				$scopesPerMask[$dim][$mask][] = $scopeVal;
			}
		}

		/**
		* Find and remove scoped masks that are identical to each others.
		*
		* For example, if all settings where x=5 are identical to all settings where x=6 then
		* we remove x=5 and declare 5 and alias of 6 in dimension x
		*/
		$scopeAliases = array();
		foreach ($scopesPerMask as $dim => $masks)
		{
			foreach ($masks as $mask => $scopeVals)
			{
				if (isset($scopeVals[1]))
				{
					/**
					* More than one scope for that mask
					*/
					$scopeVal = array_shift($scopeVals);
					$scopeAliases[$dim][$scopeVal] = $scopeVals;

					$delete[$dim] += array_flip($scopeVals);
				}
			}
			unset($scopeVals);
		}
		unset($masks);

		if (!empty($delete))
		{
			/**
			* @todo find out why creating $scopeVals as a reference is faster
			*/
			foreach ($u as $scope => &$scopeVals)
			{
				foreach ($scopeVals as $dim => $scopeVal)
				{
					if (isset($delete[$dim][$scopeVal]))
					{
						foreach ($perms as $perm => &$settings)
						{
							unset($settings[$scope]);
						}
						break;
					}
				}
			}
			unset($scopeVals, $settings);

			foreach ($delete as $dim => $scopeVals)
			{
				$dimScopes[$dim] = array_diff_key($dimScopes[$dim], $scopeVals);
			}

			$dimScopes = array_filter($dimScopes);
		}

		/**
		* Prepare to add the "wildcard" bits
		*/
		$mask = '';
		foreach ($perms as $perm => $settings)
		{
			$mask .= implode('', $settings);
		}

		$step     = 1;
		$chunkLen = 0;

		foreach ($dimScopes as $dim => $scopeVals)
		{
			$chunkLen  += $step;
			$step       = $chunkLen;
			$repeat     = 1 + count($scopeVals);
			$chunkLen  *= $repeat;

			$space['wildcard'][$dim] = $chunkLen;

			$i = 0;
			foreach ($scopeVals as $scopeVal)
			{
				$pos = ++$i * $step;
				$space['scopes'][$dim][$scopeVal] = $pos;

				if (isset($scopeAliases[$dim][$scopeVal]))
				{
					$space['scopes'][$dim] += array_fill_keys($scopeAliases[$dim][$scopeVal], $pos);
				}
			}

			$tmp = '';
			foreach (str_split($mask, $chunkLen) as $chunk)
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

		$permMasks = str_split($mask, $chunkLen + $step);
		$spaceMask = self::mergeMasks($permMasks);

		foreach (array_keys($perms) as $i => $perm)
		{
			$space['perms'][$perm] = strpos($spaceMask, $permMasks[$i]);
		}

		$space['mask'] = implode('', array_map('chr', array_map('bindec', str_split($spaceMask . str_repeat('0', 8 - (strlen($spaceMask) & 7)), 8))));

		return $space;
	}

	/**
	* Remove perms that are not granted in any scope
	*
	* @param  array &$acl
	* @return void
	*/
	static protected function removeEmptyPerms(array &$acl)
	{
		$unsetPerms = array();

		foreach ($acl as $perm => &$settings)
		{
			foreach ($settings as $setting)
			{
				if ($setting)
				{
					continue 2;
				}
			}

			$unsetPerms[] = $perm;
		}

		foreach ($unsetPerms as $perm)
		{
			unset($acl[$perm]);
		}
	}

	/**
	* Deduplicate perms that share the same permission mask
	*
	* @param  array &$perms
	* @return array         A 2D array where keys are perms and values are aliases
	*/
	static protected function dedupPermsByMask(array &$perms)
	{
		$permAliases = $masks = array();

		foreach ($perms as $perm => $settings)
		{
			$mask = implode('', $settings);

			if (isset($masks[$mask]))
			{
				/**
				* That mask is shared with another perm. We mark the current perm as an alias
				* of the other perm then remove it from the list
				*/
				$originalPerm = $masks[$mask];

				$permAliases[$originalPerm][$perm] = $perm;

				unset($perms[$perm]);
				continue;
			}

			/**
			* The mask belongs to the first perm that uses it
			*/
			$masks[$mask] = $perm;
		}

		return $permAliases;
	}
}