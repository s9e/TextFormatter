/*
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

/**
 * @param {string} uri
 * @return {string}
 * @nosideeffects
 */
function encodeURIComponent(uri) {}
/**
 * @param {string} str
 * @return {string}
 * @nosideeffects
 */
function escape(str) {}
/**
 * @param {*} num
 * @param {number|undefined} base
 * @return {number}
 * @nosideeffects
 */
function parseInt(num, base) {}
/**
 * @constructor
 * @param {...*} var_args
 * @return {!Array}
 * @nosideeffects
 */
function Array(var_args) {}
/**
 * @param {*=} opt_separator Specifies a string to separate each element of the
 * @return {string}
 * @this {Object}
 * @nosideeffects
 */
Array.prototype.join = function(opt_separator) {};
/**
 * @type {number}
 */
Array.prototype.length;
/**
 * @this {Object}
 * @modifies {this}
 */
Array.prototype.pop = function() {};
/**
 * @param {...*} var_args
 * @return {number} The new length of the array.
 * @this {Object}
 * @modifies {this}
 */
Array.prototype.push = function(var_args) {};
/**
 * @this {Object}
 * @modifies {this}
 */
Array.prototype.reverse = function() {};
/**
 * @param {*=} opt_begin Zero-based index at which to begin extraction.  A
 * @param {*=} opt_end Zero-based index at which to end extraction.  slice
 * @return {!Array}
 * @this {Object}
 * @nosideeffects
 */
Array.prototype.slice = function(opt_begin, opt_end) {};
/**
 * @param {Function=} opt_compareFunction Specifies a function that defines the
 * @this {Object}
 */
Array.prototype.sort = function(opt_compareFunction) {};
/**
 * @param {?=} opt_yr_num
 * @param {?=} opt_mo_num
 * @param {?=} opt_day_num
 * @param {?=} opt_hr_num
 * @param {?=} opt_min_num
 * @param {?=} opt_sec_num
 * @param {?=} opt_ms_num
 * @constructor
 * @return {string}
 * @nosideeffects
 */
function Date(opt_yr_num, opt_mo_num, opt_day_num, opt_hr_num, opt_min_num,
/**
 * @return {number}
 * @nosideeffects
 */
Date.prototype.getTime = function() {};
/**
 * @const
 */
var Math = {};
/**
 * @param {*} x
 * @return {number}
 * @nosideeffects
 */
Math.floor = function(x) {};
/**
 * @return {number}
 * @nosideeffects
 */
Math.random = function() {};
/**
 * @constructor
 * @param {*=} opt_value
 * @return {number}
 * @nosideeffects
 */
function Number(opt_value) {}
/**
 * @param {(number|Number)=} opt_radix An optional radix.
 * @return {string}
 * @nosideeffects
 * @override
 */
Number.prototype.toString = function(opt_radix) {};
/**
 * @constructor
 * @param {*=} opt_pattern
 * @param {*=} opt_flags
 * @return {!RegExp}
 * @nosideeffects
 */
function RegExp(opt_pattern, opt_flags) {}
/**
 * @param {*} str The string to search.
 * @return {Array.<string>} This should really return an Array with a few
 */
RegExp.prototype.exec = function(str) {};
/**
 * @type {number}
 */
RegExp.prototype.lastIndex;
/**
 * @param {*} str The string to search.
 * @return {boolean} Whether the string was matched.
 */
RegExp.prototype.test = function(str) {};
/**
 * @constructor
 * @param {*=} opt_str
 * @return {string}
 * @nosideeffects
 */
function String(opt_str) {}
/**
 * @param {...number} var_args
 * @return {string}
 * @nosideeffects
 */
String.fromCharCode = function(var_args) {};
/**
 * @param {number} index
 * @return {string}
 * @nosideeffects
 */
String.prototype.charAt = function(index) {};
/**
 * @param {number=} opt_index
 * @return {number}
 * @nosideeffects
 */
String.prototype.charCodeAt = function(opt_index) {};
/**
 * @param {string|null} searchValue
 * @param {(number|null)=} opt_fromIndex
 * @return {number}
 * @nosideeffects
 */
String.prototype.indexOf = function(searchValue, opt_fromIndex) {};
/**
 * @type {number}
 */
String.prototype.length;
/**
 * @param {RegExp|string} regex
 * @param {string|Function} str
 * @param {string=} opt_flags
 * @return {string}
 */
String.prototype.replace = function(regex, str, opt_flags) {};
/**
 * @param {*=} opt_separator
 * @param {number=} opt_limit
 * @return {!Array.<string>}
 * @nosideeffects
 */
String.prototype.split = function(opt_separator, opt_limit) {};
/**
 * @param {number} start
 * @param {number=} opt_length
 * @return {string} The specified substring.
 * @nosideeffects
 */
String.prototype.substr = function(start, opt_length) {};
/**
 * @return {string}
 * @nosideeffects
 */
String.prototype.toLowerCase = function() {};
/**
 * @return {string}
 * @nosideeffects
 */
String.prototype.toUpperCase = function() {};
