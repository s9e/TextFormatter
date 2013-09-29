#!/usr/bin/php
<?php

$externs = [
	'es3.js' => [
		'var undefined',

		// functions
		'function encodeURIComponent(',
		'function escape(',
		'function isNaN(',
		'function parseInt(',

		// Object
		'function Object(',
		'Object.prototype.toString',

		// Array object
		'function Array(',
		'Array.prototype.forEach',
		'Array.prototype.indexOf',
		'Array.prototype.join',
		'Array.prototype.length',
		'Array.prototype.pop',
		'Array.prototype.push',
		'Array.prototype.reverse',
		'Array.prototype.shift',
		'Array.prototype.slice',
		'Array.prototype.sort',
		'Array.prototype.splice',

		// Date object
		'function Date(',
		'Date.parse',

		// Math object
		'var Math',
		'Math.floor',
		'Math.max',
		'Math.min',
		'Math.random',

		// Number object
		'function Number(',
		'Number.prototype.toString',

		// Regexp object
		'function RegExp(',
		'RegExp.prototype.exec',
		'RegExp.prototype.lastIndex',
		'RegExp.prototype.test',

		// String object
		'function String(',
		'String.fromCharCode',
		'String.prototype.charAt',
		'String.prototype.charCodeAt',
		'String.prototype.indexOf',
		'String.prototype.length',
		'String.prototype.replace',
		'String.prototype.split',
		'String.prototype.substr',
		'String.prototype.toLowerCase',
		'String.prototype.toUpperCase'
	],
	'gecko_dom.js' => [
		'Document.prototype.importNode',
		'Element.prototype.innerHTML'
	],
	'gecko_xml.js' => [
		'function DOMParser(',
		'DOMParser.prototype.parseFromString'
	],
	'w3c_dom1.js' => [
		'function Document(',
		'Document.prototype.createDocumentFragment',
		'Document.prototype.createElement',

		'function DocumentFragment(',

		'function NamedNodeMap(',
		'NamedNodeMap.prototype.item',
		'NamedNodeMap.prototype.length',

		'function Node(',
		'Node.prototype.appendChild',
		'Node.prototype.childNodes',
		'Node.prototype.cloneNode',
		'Node.prototype.insertBefore',
		'Node.prototype.nodeName',
		'Node.prototype.nodeType',
		'Node.prototype.nodeValue',
		'Node.prototype.ownerDocument',
		'Node.prototype.parentNode',
		'Node.prototype.removeChild',

		'function NodeList(',
		'NodeList.prototype.length',

		'function Element(',
	],
	'w3c_dom3.js' => [
		'Element.prototype.getAttributeNS',
		'Element.prototype.hasAttributeNS',
		'Element.prototype.removeAttributeNS',
		'Element.prototype.setAttributeNS',

		'Node.prototype.isEqualNode',
		'Node.prototype.namespaceURI',
		'Node.prototype.textContent'
	]
];

$out  = '';

foreach ($externs as $filename => $names)
{
	$file = file_get_contents(
		'compress.zlib://http://closure-compiler.googlecode.com/git/externs/' . $filename,
		false,
		stream_context_create(['http' => ['header' => 'Accept-Encoding: gzip']])
	);

	// Concat multiline definitions
	$file = preg_replace('#, *\n#', ', ', $file);

	preg_match_all('#/\\*\\*.*?\\*/\\n([^\\n]+)#s', $file, $m);

	foreach ($names as $name)
	{
		$len = strlen($name);

		foreach ($m[1] as $k => $line)
		{
			if (substr($line, 0, $len) === $name)
			{
				$out .= $m[0][$k] . "\n";
				continue 2;
			}
		}

		echo "Could not find $name\n";
	}
}

// Remove superfluous doc like comments and @see links
$out = preg_replace('#^ \\*(?!/| @(?!see)).*\\n#m', '', $out);

// Remove unnecessary annotations
$annotations = [
	" * @implements {EventTarget}\n",
];
$out = str_replace($annotations, '', $out);

// Prepend some legalese to be on the safe side
$out = '/*
 * Copyright 2008 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

// This file was auto-generated.
// See http://code.google.com/p/closure-compiler/source/browse/trunk/externs/ for the original source.
// See https://github.com/s9e/TextFormatter/blob/master/scripts/generateExterns.php for details.

' . $out;

file_put_contents(__DIR__ . '/../src/s9e/TextFormatter/Configurator/JavaScript/externs.js', $out);
die("Done.\n");