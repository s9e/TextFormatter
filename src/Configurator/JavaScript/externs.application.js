/*
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

/**
* @const
*/
var punycode = {};
/**
* @param {string} domain
* @return {string}
*/
punycode.toASCII;
/** @constructor */
function XSLTProcessor() {}
/**
* @constructor
*/
function DOMParser() {}
/**
* @param {!TrustedHTML|string} src The UTF16 string to be parsed.
* @param {string} type The content type of the string.
* @return {!Document}
*/
DOMParser.prototype.parseFromString = function(src, type) {};
/**
* @type {Window}
*/
HTMLIFrameElement.prototype.contentWindow;
/**
* @constructor
*/
function MessageChannel() {}
/**
* @type {!MessagePort}
*/
MessageChannel.prototype.port1;
/**
* @type {!MessagePort}
*/
MessageChannel.prototype.port2;
/**
* @constructor
* @implements {EventTarget}
* @implements {Transferable}
*/
function MessagePort() {}
/**
* @type {?function(!MessageEvent<?>): void}
*/
MessagePort.prototype.onmessage;
/**
* @param {*} message
* @param {Array<!Transferable>=} opt_transfer
* @return {undefined}
*/
MessagePort.prototype.postMessage = function(message, opt_transfer) {};
/**
* @constructor
* @extends {Event}
* @param {string} type
* @param {MessageEventInit<T>=} opt_eventInitDict
* @template T
*/
function MessageEvent(type, opt_eventInitDict) {}
/**
* @type {T}
*/
MessageEvent.prototype.data;
/**
* @type {string}
*/
MessageEvent.prototype.origin;
/**
* @type {!Array<MessagePort>}
*/
MessageEvent.prototype.ports;
/**
* @type {Window}
*/
MessageEvent.prototype.source;
/**
* @record
* @extends {EventInit}
* @template T
*/
function MessageEventInit() {}
/**
* @record
*/
function StructuredSerializeOptions() {}
/**
* @param {*} message
* @param {(string|!WindowPostMessageOptions)=} targetOriginOrOptions
* @param {(!Array<!Transferable>)=} transfer
* @return {void}
*/
Window.prototype.postMessage = function(message, targetOriginOrOptions, transfer) {};
/**
* @record
* @extends {StructuredSerializeOptions}
*/
function WindowPostMessageOptions() {}
/**
* @constructor
* @extends {CSSProperties}
* @implements {IObject<(string|number), string>}
* @implements {IArrayLike<string>}
* @implements {Iterable<string>}
*/
function CSSStyleDeclaration() {}
/**
* @constructor
*/
function CSSProperties() {}
/**
* @constructor
* @extends {Node}
*/
function Document() {}
/**
* @return {!DocumentFragment}
* @nosideeffects
*/
Document.prototype.createDocumentFragment = function() {};
/**
* @param {string} tagName
* @param {({is: string}|string)=} opt_typeExtension
* @return {!Element}
* @nosideeffects
*/
Document.prototype.createElement = function(tagName, opt_typeExtension) {};
/**
* @constructor
* @extends {Node}
*/
function DocumentFragment() {}
/**
* @param {string} name
* @param {?number=} flags
* @return {string}
* @nosideeffects
*/
Element.prototype.getAttribute = function(name, flags) {};
/**
* @constructor
* @implements {IObject<(string|number), T>}
* @implements {IArrayLike<T>}
* @implements {Iterable<T>}
* @template T
*/
function NamedNodeMap() {}
/**
* @param {number} index
* @return {Node}
* @nosideeffects
*/
NamedNodeMap.prototype.item = function(index) {};
/**
* @type {number}
*/
NamedNodeMap.prototype.length;
/**
* @constructor
* @implements {EventTarget}
*/
function Node() {}
/**
* @param {Node} newChild
* @return {!Node}
*/
Node.prototype.appendChild = function(newChild) {};
/**
* @type {!NodeList<!Node>}
*/
Node.prototype.childNodes;
/**
* @param {boolean} deep
* @return {THIS}
* @this {THIS}
* @template THIS
* @nosideeffects
*/
Node.prototype.cloneNode = function(deep) {};
/**
* @type {Node}
*/
Node.prototype.firstChild;
/**
* @param {Node} newChild
* @param {Node} refChild
* @return {!Node}
*/
Node.prototype.insertBefore = function(newChild, refChild) {};
/**
* @type {string}
*/
Node.prototype.nodeName;
/**
* @type {number}
*/
Node.prototype.nodeType;
/**
* @type {string}
*/
Node.prototype.nodeValue;
/**
* @type {Document}
*/
Node.prototype.ownerDocument;
/**
* @type {Node}
*/
Node.prototype.parentNode;
/**
* @param {Node} oldChild
* @return {!Node}
*/
Node.prototype.removeChild = function(oldChild) {};
/**
* @param {Node} newChild
* @param {Node} oldChild
* @return {!Node}
*/
Node.prototype.replaceChild = function(newChild, oldChild) {};
/**
* @constructor
* @implements {IArrayLike<T>}
* @implements {Iterable<T>}
* @template T
*/
function NodeList() {}
/**
* @param {?function(this:S, T, number, !NodeList<T>): ?} callback
* @param {S=} opt_thisobj
* @template S
* @return {undefined}
*/
NodeList.prototype.forEach = function(callback, opt_thisobj) {};
/**
* @type {number}
*/
NodeList.prototype.length;
/**
* @constructor
* @extends {Node}
*/
function Element() {}
/**
* @constructor
* @implements {EventTarget}
*/
function Window() {}
/**
* @param {!Node} externalNode
* @param {boolean=} deep
* @return {!Node}
*/
Document.prototype.importNode = function(externalNode, deep) {};
/**
* @type {string}
* @implicitCast
*/
Element.prototype.innerHTML;
/**
* @type {string}
* @implicitCast
*/
Element.prototype.outerHTML;
/**
* @constructor
* @extends {Document}
*/
function HTMLDocument() {}
/**
* @constructor
* @extends {Element}
*/
function HTMLElement() {}
/**
* @type {!CSSStyleDeclaration}
*/
HTMLElement.prototype.style;
/**
* @constructor
* @extends {HTMLElement}
*/
function HTMLIFrameElement() {}
/**
* @constructor
* @extends {HTMLElement}
*/
function HTMLScriptElement() {}
/**
* @param {?string} namespaceURI
* @param {string} localName
* @return {string}
* @nosideeffects
*/
Element.prototype.getAttributeNS = function(namespaceURI, localName) {};
/**
* @param {?string} namespaceURI
* @param {string} localName
* @return {boolean}
* @nosideeffects
*/
Element.prototype.hasAttributeNS = function(namespaceURI, localName) {};
/**
* @param {?string} namespaceURI
* @param {string} localName
* @return {undefined}
*/
Element.prototype.removeAttributeNS = function(namespaceURI, localName) {};
/**
* @param {?string} namespaceURI
* @param {string} qualifiedName
* @param {string|number|boolean} value Values are converted to strings with
* @return {undefined}
*/
Element.prototype.setAttributeNS = function(namespaceURI, qualifiedName, value) {};
/**
* @param {Node} arg
* @return {boolean}
* @nosideeffects
*/
Node.prototype.isEqualNode = function(arg) {};
/**
* @param {string} query
* @return {!NodeList<!Element>}
* @nosideeffects
*/
Node.prototype.querySelectorAll = function(query) {};
/**
* @type {string}
*/
Node.prototype.namespaceURI;
/**
* @type {string}
* @implicitCast
*/
Node.prototype.textContent;
/**
* @record
* @extends {EventListenerOptions}
*/
var AddEventListenerOptions = function() {};
/**
* @constructor
* @param {string} type
* @param {EventInit=} opt_eventInitDict
*/
function Event(type, opt_eventInitDict) {}
/**
* @record
*/
function EventInit() {}
/**
* @interface
*/
function EventListener() {}
/**
* @record
*/
var EventListenerOptions = function() {};
/**
* @interface
*/
function EventTarget() {}
/**
* @param {string} type
* @param {EventListener|function(this:THIS, !Event):*} listener
* @param {(boolean|!AddEventListenerOptions)=} opt_options
* @return {undefined}
* @this {THIS}
* @template THIS
*/
EventTarget.prototype.addEventListener = function(type, listener, opt_options) {};
/** @constructor */
function TrustedHTML() {}
/**
* @const {!HTMLDocument}
*/
var document;
/**
* @type {!Window}
*/
var window;
