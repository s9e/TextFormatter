<?php

/**
* @package   s9e\toolkit
* @copyright Copyright (c) 2010 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\Toolkit\Acl;

interface Resource
{
	/**
	* Return the value to be used by the Acl to uniquely identify this resource
	*
	* @return integer|string
	*/
	public function getAclId();

	/**
	* Return the name of this kind of resource
	*
	* @return string
	*/
	public function getAclResourceName();

	/**
	* Return the attributes used by the Acl to scope access to this resource
	*
	* @return array
	*/
	public function getAclAttributes();
}