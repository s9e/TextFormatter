#!/usr/bin/php
<?php

$externs = [
	'contrib/nodejs/punycode.js' => [
		'var punycode',
		'punycode.toASCII'
	],
	'externs/browser/deprecated.js' => [
		'function XSLTProcessor('
	],
	'externs/es3.js' => [
		'var Infinity',
		'var undefined',

		'var symbol',
		'function Symbol(',

		'function decodeURIComponent(',
		'function encodeURIComponent(',
		'function escape(',
		'function isNaN(',
		'function parseInt(',

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
		'Array.prototype.unshift',

		'function Date(',
		'Date.parse',

		'function Function(',

		'var Math',
		'Math.floor',
		'Math.max',
		'Math.min',
		'Math.random',

		'function Number(',
		'Number.prototype.toString',

		'function Object(',
		'Object.prototype.toString',

		'function RegExp(',
		'RegExp.prototype.exec',
		'RegExp.prototype.lastIndex',
		'RegExp.prototype.test',

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
	'externs/browser/gecko_dom.js' => [
		'Element.prototype.innerHTML'
	],
	'externs/browser/gecko_xml.js' => [
		'function DOMParser(',
		'DOMParser.prototype.parseFromString'
	],
	'externs/browser/ie_dom.js' => [
		'var window'
	],
	'externs/browser/w3c_dom1.js' => [
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
		'Node.prototype.firstChild',
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

		'function Window(',
	],
	'externs/browser/w3c_dom2.js' => [
		'Document.prototype.importNode',
		'function HTMLDocument(',
		'function HTMLElement',
	],
	'externs/browser/w3c_dom3.js' => [
		'Element.prototype.getAttributeNS',
		'Element.prototype.hasAttributeNS',
		'Element.prototype.removeAttributeNS',
		'Element.prototype.setAttributeNS',

		'Node.prototype.isEqualNode',
		'Node.prototype.namespaceURI',
		'Node.prototype.textContent'
	],
	'externs/browser/window.js' => [
		'var document;'
	]
];

function wget($url)
{
	$filepath = sys_get_temp_dir() . '/' . basename($url);
	if (!file_exists($filepath))
	{
		copy(
			'compress.zlib://' . $url,
			$filepath,
			stream_context_create(['http' => ['header' => 'Accept-Encoding: gzip']])
		);
	}

	return file_get_contents($filepath);
}

function getExterns($externs)
{
	$out  = '';
	foreach ($externs as $filename => $names)
	{
		$url = 'https://raw.githubusercontent.com/google/closure-compiler/master/' . $filename;

		// Concat multiline definitions
		$file = preg_replace('#, *\n#', ', ', wget($url));

		// Remove the file header
		$file = preg_replace('(/\\*\\*.*?@fileoverview.*?\\*/)s', '', $file);

		preg_match_all('(/\\*\\*.*?\\*/\\s*(\\w[^\\n]+))s', $file, $m);
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
	 * Copyright 2008 The Closure Compiler Authors
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
	// See https://github.com/google/closure-compiler for the original source.
	// See https://github.com/s9e/TextFormatter/blob/master/scripts/generateExterns.php for details.

	' . $out;

	return str_replace("\t", '', $out);
}

$dir = __DIR__ . '/../src/Configurator/JavaScript/';
file_put_contents($dir . 'externs.service.js', getExterns($externs));
unset($externs['externs/es3.js']);
unset($externs['externs/es6.js']);
file_put_contents($dir . 'externs.application.js', getExterns($externs));

die("Done.\n");