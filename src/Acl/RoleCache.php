<?php

/**
* @package   s9e\Toolkit
* @copyright Copyright (c) 2010-2011 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\Toolkit\Acl;

use InvalidArgumentException;

class RoleCache
{
	/**
	* @var array All registered roles
	*/
	protected $roles = array();

	/**
	* Add a Role to the cache
	*
	* @param  Role $role      Role to add
	* @param  bool $overwrite If FALSE, will throw an exception if the Role already exists
	* @return void
	*/
	public function add(Role $role, $overwrite = false)
	{
		$name = $role->getName();

		if (!$overwrite && isset($this->roles[$name]))
		{
			throw new InvalidArgumentException('There is already a role named ' . $name);
		}

		$this->roles[$name] = $role;
	}

	/**
	* Remove a cached Role
	*
	* Will do nothing if the Role is not in the cache
	*
	* @param  string $name Name of the Role
	* @return void
	*/
	public function remove($name)
	{
		unset($this->roles[$name]);
	}

	/**
	* Return a cached Role
	*
	* Will throw an exception if the requested role is not in the cache
	*
	* @param  string $name Name of the Role
	* @return Role
	*/
	public function get($name)
	{
		if (!isset($this->roles[$name]))
		{
			throw new InvalidArgumentException('There is no role named ' . $name);
		}

		return $this->roles[$name];
	}

	/**
	* Test whether a Role is cached
	*
	* @param  string $name Name of the Role
	* @return bool
	*/
	public function exists($name)
	{
		return isset($this->roles[$name]);
	}

	/**
	* Clear the cache
	*
	* @return void
	*/
	public function clear()
	{
		$this->roles = array();
	}
}