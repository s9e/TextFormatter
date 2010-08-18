<?php

/**
* @package   s9e\Toolkit
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
	* Will be used by the Builder when passed a Resource as a scope. It will be replaced by an array
	* with one element using getAclResourceName() as key and getAclId() as value.
	*
	* @return string
	*/
	public function getAclResourceName();

	/**
	* Return the attributes used by the Reader to scope access to this resource
	*
	* Will typically contain the name and values returned by getAclResourceName() and getAclId()
	* plus any relevant attribute.
	*
	* @return array
	*/
	public function getAclAttributes();
}