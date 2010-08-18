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
	*
	*
	* @return array
	*/
	public function getAclBuilderScope();

	/**
	*
	*
	* @return array
	*/
	public function getAclReaderScope();
}