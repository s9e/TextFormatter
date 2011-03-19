<?php

/**
* @package   s9e\Toolkit
* @copyright Copyright (c) 2009 The SimpleDOM authors
* @copyright Copyright (c) 2010 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\Toolkit\SimpleDOM\Tests;
use s9e\Toolkit\SimpleDOM\SimpleDOM;

include_once __DIR__ . '/../../src/SimpleDOM/SimpleDOM.php';
 
class loadHTMLTest extends \PHPUnit_Framework_TestCase
{
	public function testWellFormedXML()
	{
		$html =
			'<html>
				<head>
					<title>Hello HTML</title>
				</head>
				<body>
					<p>Hello World!</p>
				</body>
			</html>';

		$node = SimpleDOM::loadHTML($html);

		$this->assertXmlStringEqualsXmlString($html, $node->asXML());
	}

	public function testFromValidHTMLMalformedXML()
	{
		$html =
			'<html>
				<head>
					<title>Hello HTML</title>
					<link rel="stylesheet" type="text/css" href="test.css">
				</head>
				<body>
					<p>Hello World!<br><font size=1>New line</font></p>
				</body>
			</html>';

		$xml =
			'<html>
				<head>
					<title>Hello HTML</title>
					<link rel="stylesheet" type="text/css" href="test.css" />
				</head>
				<body>
					<p>Hello World!<br /><font size="1">New line</font></p>
				</body>
			</html>';

		$node = SimpleDOM::loadHTML($html);

		$this->assertXmlStringEqualsXmlString($xml, $node->asXML());
	}

	public function testInvalidHTMLEntityAreSilentlyFixed()
	{
		$html =
			'<html>
				<body>
					<p><a href="test?a=1&b=2">link</a></p>
				</body>
			</html>';

		$xml =
			'<html>
				<body>
					<p><a href="test?a=1&amp;b=2">link</a></p>
				</body>
			</html>';

		$node = SimpleDOM::loadHTML($html);

		$this->assertXmlStringEqualsXmlString($xml, $node->asXML());
	}

	public function testErrorsCanBeRetrieved()
	{
		$html =
			'<html>
				<body>
					<p><a href="test?a=1&b=2">link</a>
					<p><i>not closed
				</body>
			</html>';

		$node = SimpleDOM::loadHTML($html, $errors);

		$this->assertInternalType('array', $errors, '$errors was not initialized as an array');

		if (is_array($errors))
		{
			$this->assertSame(2, count($errors), '$errors did not contain the expected number of errors');

			$errors = array_values(array_slice($errors, -2));

			$this->assertStringStartsWith("htmlParseEntityRef: expecting ';'", $errors[0]->message);
			$this->assertStringStartsWith("Opening and ending tag mismatch: body and i", $errors[1]->message);
		}
	}

	/**
	* @depends testErrorsCanBeRetrieved
	*/
	public function testOnlyRelevantErrorsAreReturned()
	{
		/**
		* Generate some errors then rerun testErrorsCanBeRetrieved
		*/
		$old = libxml_use_internal_errors(true);
		SimpleDOM::loadHTML('<html><bogus>');
		$this->testErrorsCanBeRetrieved();
		libxml_use_internal_errors($old);
	}
}