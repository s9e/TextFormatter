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

	/**
	* @var array unserialize() cache
	*/
	static protected $unserializeCache = array('a:0:{}' => array());

	/**
	* @var array Holds the list of parent scopes a given scope inherits from
	*/
	static protected $inherit = array('a:0:{}' => array());

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
	* @param  string $srcPerm Permission name
	* @param  string $rule    Rule name: either "grant" or "require"
	* @param  string $trgPerm Permission name
	* @return Acl             $this
	*/
	public function addRule($srcPerm, $rule, $trgPerm)
	{
		/**
		* @todo validate params
		*/
		$this->rules[$rule][$srcPerm][$trgPerm] = $trgPerm;

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

	//==============================================================================================
	// INTERNAL STUFF: misc
	//==============================================================================================

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
		* Build the full initial ACL, based on settings
		*/
		$acl = self::buildAcl($combinedSettings, $combinedRules);

		/**
		* Apply inheritance, grant/require rules
		*/
		self::resolveAcl($acl, $combinedRules);

		/**
		* Remove perms that are not granted in any scope
		*/
		self::removeEmptyPerms($acl);

		if (empty($acl))
		{
			return array();
		}

		/**
		* Replace allow/null/deny settings with '0' and '1'
		*/
		self::normalizeSettingsRepresentation($acl);

		/**
		* Remove unused dimensions and scopes
		*/
		self::optimizePermsDimensions($acl);

		/**
		* Sort perms by their dimensions
		*/
		$aclPerSpace = self::getAclBySpace($acl);

		/**
		* @var array
		*/
		$spaces = array();

		if (isset($aclPerSpace['a:0:{}']))
		{
			/**
			* All that confusing array_* mumbo jumbo is to only keep perms that are allowed and
			* assign them pos 0
			*/
			$allowed = array_keys(array_filter(array_map('array_filter', $aclPerSpace['a:0:{}'])));

			if ($allowed)
			{
				$spaces[] = array(
					'mask'  => "\x80",
					'perms' => array_fill_keys($allowed, 0)
				);
			}

			unset($aclPerSpace['a:0:{}']);
		}

		foreach ($aclPerSpace as $spaceId => $spaceAcl)
		{
			/**
			* Remove perms sharing the same mask. This has to be done space by space, because perms
			* from different spaces could generate the same mask regardless of their dimensions
			*/
			$permAliases = self::dedupPerms($spaceAcl);

			/**
			* Remove scopes sharing the same mask
			*/
			$scopeAliases = self::dedupScopes($spaceAcl);

			$spaces[] = self::generateSpace($spaceAcl, $permAliases, $scopeAliases);
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

	/**
	* Generate a bootstrap ACL array containing all possible scopes with null settings
	*
	* @param  array $spaceVals 2D array with scope dimensions first and all possible values second
	* @return array
	*/
	static protected function generateBootstrap(array $spaceVals)
	{
		$bootstrap = array('a:0:{}' => null);

		switch (count($spaceVals))
		{
			case 0:
				// do nothing
				break;

			case 1:
				foreach ($spaceVals as $scopeDim => $scopeVals)
				{
					foreach ($scopeVals as $scopeVal)
					{
						$scope                = array($scopeDim => $scopeVal);
						$scopeKey             = serialize($scope);
						$bootstrap[$scopeKey] = null;

						self::$unserializeCache[$scopeKey] = $scope;
						self::$inherit[$scopeKey]          = array($scopeDim => 'a:0:{}');
					}
				}
				break;

			default:
				$scopeCnt = array();
				foreach ($spaceVals as $scopeDim => $scopeVals)
				{
					/**
					* We add 1 for the global scope
					*/
					$scopeCnt[$scopeDim] = 1 + count($scopeVals);
				}

				/**
				* Make sure the second dimension of the array is indexed numerically
				*/
				$spaceVals = array_map('array_values', $spaceVals);

				$n   = 0;
				$max = array_product($scopeCnt);

				do
				{
					$scope = array();
					$div   = 1;

					foreach ($scopeCnt as $scopeDim => $cnt)
					{
						$j    = (int) ($n / $div) % $cnt;
						$div *= $cnt;

						if ($j)
						{
							$scope[$scopeDim] = $spaceVals[$scopeDim][$j - 1];
						}
					}

					$scopeKey = serialize($scope);

					self::$unserializeCache[$scopeKey] = $scope;

					foreach ($scope as $scopeDim => $scopeVal)
					{
						$tmp = $scope;
						unset($tmp[$scopeDim]);

						$tmpKey = serialize($tmp);

						self::$inherit[$scopeKey][$scopeDim] = $tmpKey;
						self::$unserializeCache[$tmpKey]     = $tmp;
					}

					$bootstrap[$scopeKey] = null;
				}
				while (++$n < $max);
		}

		return $bootstrap;
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
	* Resolve inheritance/apply rules to an ACL
	*
	* @param  array &$acl
	* @param  array $rules
	* @return void
	*/
	static protected function resolveAcl(array &$acl, array $rules)
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
					foreach (self::$inherit[$scope] as $inheritScope)
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

			foreach ($grant as $srcPerm => $trgPerms)
			{
				foreach ($acl[$srcPerm] as $scope => $setting)
				{
					if ($setting !== self::ALLOW)
					{
						continue;
					}

					foreach ($trgPerms as $trgPerm)
					{
						if (isset($acl[$trgPerm][$scope])
						 && !isset($grantees[$scope][$trgPerm]))
						{
							/**
							* The target perm's setting is either self::ALLOW, in which case there's
							* nothing to do, or self::DENY, which can't be overwritten anyway.
							*
							* We also check whether the target perm has been granted during this
							* iteration of the main loop. If it was, we record all the grantors so
							* that we only revoke the target perm if ALL of them get revoked.
							*/
							continue;
						}

						$acl[$trgPerm][$scope] = self::ALLOW;

						$grantors[$scope][$srcPerm][$trgPerm] = $trgPerm;
						$grantees[$scope][$trgPerm][$srcPerm] = $srcPerm;
					}
				}
			}

			//==================================================================
			// STEP 3: apply "require" rules
			//==================================================================

			foreach ($require as $srcPerm => $trgPerms)
			{
				foreach ($acl[$srcPerm] as $scope => &$setting)
				{
					foreach ($trgPerms as $trgPerm)
					{
						if ($setting !== self::ALLOW)
						{
							break;
						}

						if (isset($acl[$trgPerm][$scope]))
						{
							/**
							* Here we copy the target perm's setting regarless of whether it's
							* self::ALLOW or self::DENY. This is because if the target perm's
							* setting is self::DENY, there is no way for this setting to ever be
							* granted anyway, and the same goes for scopes that inherit from it.
							*/
							$setting = $acl[$trgPerm][$scope];
						}
						else
						{
							$setting = null;
							break;
						}
					}

					if ($setting !== self::ALLOW
					 && isset($grantors[$scope][$srcPerm]))
					{
						/**
						* That permission has been removed by a "require" rule
						*
						* Now, we are going to recursively remove permissions that were
						* granted by (or because of) that permission. Eventually, those
						* permissions will be granted back during the next iteration.
						*/
						$cancelGrantors = array($srcPerm => $srcPerm);

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

	static protected function generateSpace(array $spaceAcl, array $permAliases, array $scopeAliases)
	{
		$usedScopes = array();

		foreach ($spaceAcl as $perm => $settings)
		{
			foreach ($settings as $scopeKey => $setting)
			{
				foreach (self::$unserializeCache[$scopeKey] as $scopeDim => $scopeVal)
				{
					$usedScopes[$scopeDim][$scopeVal] = $scopeVal;
				}
			}
		}

		/**
		* Prepare to add the "wildcard" bits
		*/
		$mask = '';
		foreach ($spaceAcl as $perm => $settings)
		{
			$mask .= implode('', $settings);
		}

		$step     = 1;
		$chunkLen = 0;

		foreach ($usedScopes as $scopeDim => $scopeVals)
		{
			$chunkLen  += $step;
			$step       = $chunkLen;
			$repeat     = 1 + count($scopeVals);
			$chunkLen  *= $repeat;

			$space['wildcard'][$scopeDim] = $chunkLen;

			$i = 0;
			foreach ($scopeVals as $scopeVal)
			{
				$pos = ++$i * $step;
				$space['scopes'][$scopeDim][$scopeVal] = $pos;

				if (isset($scopeAliases[$scopeDim][$scopeVal]))
				{
					$space['scopes'][$scopeDim] += array_fill_keys($scopeAliases[$scopeDim][$scopeVal], $pos);
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

		foreach (array_keys($spaceAcl) as $i => $perm)
		{
			$space['perms'][$perm] = strpos($spaceMask, $permMasks[$i]);
		}

		foreach ($permAliases as $originalPerm => $aliases)
		{
			$pos = $space['perms'][$originalPerm];

			foreach ($aliases as $alias)
			{
				$space['perms'][$alias] = $pos;
			}
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
	* Will remove duplicates and return a list of aliases, if applicable.
	*
	* @param  array &$acl
	* @return array       A 2D array where keys are perms and values are aliases
	*/
	static protected function dedupPerms(array &$acl)
	{
		$permAliases = $masks = array();

		foreach ($acl as $perm => $settings)
		{
			$mask = implode('', $settings);

			if (isset($masks[$mask]))
			{
				/**
				* That mask is shared with another perm. We mark the current perm as an alias
				* of the other perm then remove it from the list
				*/
				$originalPerm = $masks[$mask];

				$permAliases[$originalPerm][] = $perm;

				unset($acl[$perm]);
				continue;
			}

			/**
			* The mask belongs to the first perm that uses it
			*/
			$masks[$mask] = $perm;
		}

		return $permAliases;
	}

	/**
	* Deduplicate scopes that share the same permission mask
	*
	* Will remove duplicates and return a list of aliases perm dimension, if applicable.
	*
	* @param  array &$acl
	* @return array
	*/
	static protected function dedupScopes(array &$acl)
	{
		/**
		* @var array 2D array holding the mask of every dimension/scope value
		*/
		$scopeMasks = array();

		foreach ($acl as $perm => $settings)
		{
			foreach ($settings as $scopeKey => $setting)
			{
				foreach (self::$unserializeCache[$scopeKey] as $scopeDim => $scopeVal)
				{
					if (!isset($scopeMasks[$scopeDim][$scopeVal]))
					{
						$scopeMasks[$scopeDim][$scopeVal] = '';
					}

					$scopeMasks[$scopeDim][$scopeVal] .= $setting;
				}
			}
		}

		/**
		* @var array 2D array using the dimension name as first key, a mask as second key and a list
		*            of scopes using this mask as values
		*/
		$scopesPerMasks = array();

		foreach ($scopeMasks as $scopeDim => $masks)
		{
			foreach ($masks as $scopeVal => $mask)
			{
				$scopesPerMask[$scopeDim][$mask][] = $scopeVal;
			}
		}

		$scopeAliases = array();
		foreach ($scopesPerMask as $scopeDim => $masks)
		{
			foreach ($masks as $mask => $scopeVals)
			{
				if (isset($scopeVals[1]))
				{
					$scopeAliases[$scopeDim][array_shift($scopeVals)] = $scopeVals;

					foreach ($scopeVals as $scopeVal)
					{
						foreach ($acl as $perm => &$settings)
						{
							self::removeSettingsByScopeVal($settings, $scopeDim, $scopeVal);
						}
						unset($settings);
					}
				}
			}
		}

		return $scopeAliases;
	}

	/**
	* Remove unused dimensions and scopes, in place
	*
	* @param  array &$acl
	* @return void
	*/
	static protected function optimizePermsDimensions(array &$acl)
	{
		/**
		* @var array Holds all the scopes (in array form) for all the perms
		*/
		$allScopes = array();
		
		/**
		* @var array Holds the scope values that cannot be deleted without corrupting the ACL
		*/
		$keepScopes = array();

		foreach ($acl as $perm => $settings)
		{
			$allScopes[$perm] = $keepScopes[$perm] = array();

			foreach ($settings as $scopeKey => $setting)
			{
				foreach (self::$unserializeCache[$scopeKey] as $scopeDim => $scopeVal)
				{
					$allScopes[$perm][$scopeDim][$scopeVal] = $scopeVal;
					if ($setting !== $settings[self::$inherit[$scopeKey][$scopeDim]])
					{
						$keepScopes[$perm][$scopeDim][$scopeVal] = $scopeVal;
					}
				}
			}
		}

		if ($keepScopes == $allScopes)
		{
			return;
		}

		/**
		* @var array Holds the scope values that could be deleted safely (with no side-effects)
		*            for each perm
		*/
		$deleteScopes = array();

		foreach ($allScopes as $perm => $scopes)
		{
			foreach ($scopes as $scopeDim => $scopeVals)
			{
				if (isset($keepScopes[$perm][$scopeDim]))
				{
					$deleteScopes[$perm][$scopeDim]
						= array_diff_key($scopeVals, $keepScopes[$perm][$scopeDim]);
				}
				else
				{
					/**
					* We don't need to keep any values for that dimension, therefore we can safely
					* remove the whole dimension
					*/
					foreach ($acl[$perm] as $scopeKey => $setting)
					{
						if (isset(self::$unserializeCache[$scopeKey][$scopeDim]))
						{
							unset($acl[$perm][$scopeKey]);
						}
					}
				}
			}
		}

		$deleteScopes = array_filter(array_map('array_filter', $deleteScopes));

		if (!$deleteScopes)
		{
			return;
		}

		foreach (self::getAclBySpace($acl) as $spaceId => $spaceAcl)
		{
			/**
			* We take the first perm of that space, see if any of its scope can be removed and test
			* whether it can be removed for all the perms in that space
			*/
			$perm = key($spaceAcl);

			if (!isset($deleteScopes[$perm]))
			{
				/**
				* Nothing to delete
				*/
				continue;
			}

			foreach ($deleteScopes[$perm] as $scopeDim => $scopeVals)
			{
				foreach ($scopeVals as $scopeVal => $void)
				{
					foreach ($spaceAcl as $spacePerm => $settings)
					{
						if (!isset($deleteScopes[$spacePerm][$scopeDim][$scopeVal]))
						{
							continue 3;
						}
					}

					/**
					* That scope can be removed safely
					*/
					foreach ($spaceAcl as $spacePerm => $settings)
					{
						self::removeSettingsByScopeVal($acl[$spacePerm], $scopeDim, $scopeVal);
					}
				}
			}
		}
	}

	/**
	* Return given ACL split by space
	*
	* @param  array $acl
	* @return array      spaceId as key, hash of (perm => settings) as value
	*/
	static protected function getAclBySpace($acl)
	{
		$permDims    = self::getPermsDims($acl);
		$aclPerSpace = array();

		foreach ($permDims as $perm => $dims)
		{
			$aclPerSpace[serialize($dims)][$perm] = $acl[$perm];
		}

		return $aclPerSpace;
	}

	/**
	* Remove all settings that match a scope, in place
	*
	* @param  array      &$settings
	* @param  string     $scopeDim
	* @param  int|string $scopeVal
	* @return void
	*/
	static protected function removeSettingsByScopeVal(array &$settings, $scopeDim, $scopeVal)
	{
		foreach ($settings as $scopeKey => $setting)
		{
			if (isset(self::$unserializeCache[$scopeKey][$scopeDim])
			 && self::$unserializeCache[$scopeKey][$scopeDim] === $scopeVal)
			{
				unset($settings[$scopeKey]);
			}
		}
	}

	/**
	* Build the initial ACL
	*
	* @param  array $combinedSettings
	* @param  array $combinedRules
	* @return array
	*/
	protected function buildAcl(array $combinedSettings, array $combinedRules)
	{
		$acl        = array();
		$permDims   = array();
		$usedScopes = array();

		foreach ($combinedSettings as $setting)
		{
			list($perm, $value, $scope) = $setting;
			$k = serialize($scope);

			/**
			* Do not overwrite DENY
			*/
			if (isset($acl[$perm][$k])
			 && $acl[$perm][$k] === self::DENY)
			{
				continue;
			}

			if (!isset($permDims[$perm]))
			{
				$permDims[$perm] = array();
			}

			foreach ($scope as $dim => $scopeVal)
			{
				$permDims[$perm][$dim] = $dim;
				$usedScopes[$dim][$scopeVal] = $scopeVal;
			}

			self::$unserializeCache[$k] = $scope;
			$acl[$perm][$k] = $value;
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

		do
		{
			$loop = false;

			foreach ($combinedRules as $type => $rules)
			{
				foreach ($rules as $srcPerm => $trgPerms)
				{
					if (!isset($acl[$srcPerm]))
					{
						continue;
					}

					foreach ($trgPerms as $trgPerm)
					{
						if (!isset($permDims[$trgPerm]))
						{
							/**
							* We need to re-evaluate scopes whenever we create a new perm
							*/
							$loop = true;
							$permDims[$trgPerm] = $acl[$trgPerm] = array();
						}

						$permDims[$trgPerm] += $permDims[$perm];
					}
				}
			}
		}
		while ($loop);

		$usedScopes = array_map('array_values', $usedScopes);
		$bootstrap  = array();

		foreach ($permDims as $perm => &$dims)
		{
			ksort($dims);
			$key = serialize($dims);

			if (!isset($bootstrap[$key]))
			{
				$bootstrap[$key] = self::generateBootstrap(array_intersect_key($usedScopes, $dims));
			}

			$acl[$perm] = array_merge($bootstrap[$key], $acl[$perm]);
		}

		return $acl;
	}

	/**
	* Get the dimensions of every perm
	*
	* The last setting of a perm always contains all of its dimensions, we use that property to
	* easily compute each perm's dimensions
	*
	* @param  array $acl
	* @return array
	*/
	static protected function getPermsDims(array $acl)
	{
		$permDims = array();

		foreach ($acl as $perm => $settings)
		{
			end($settings);
			$scopeKey = key($settings);

			if ($scopeKey === 'a:0:{}')
			{
				$permDims[$perm] = array();
			}
			else
			{
				$dims            = array_keys(self::$unserializeCache[$scopeKey]);
				$permDims[$perm] = array_combine($dims, $dims);
			}
		}

		return $permDims;
	}
}