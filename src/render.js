let xslt = {
	/**
	* @param {string} xsl
	*/
	init: function(xsl)
	{
		xslt.proc = new XSLTProcessor;
		xslt.proc['importStylesheet'](xslt.loadXML(xsl));
	},

	/**
	* @param  {string} xml
	* @return {!Document}
	*/
	loadXML: function(xml)
	{
		const dom = (new DOMParser).parseFromString(xml, 'text/xml');
		if (!dom)
		{
			throw 'Cannot parse ' + xml;
		}

		return dom;
	},

	/**
	* @param {string} paramName  Parameter name
	* @param {string} paramValue Parameter's value
	*/
	setParameter: function(paramName, paramValue)
	{
		xslt.proc['setParameter'](null, paramName, paramValue);
	},

	/**
	* @param  {string}    xml
	* @param  {!Document} targetDoc
	* @return {!DocumentFragment}
	*/
	transformToFragment: function(xml, targetDoc)
	{
		return xslt.proc['transformToFragment'](xslt.loadXML(xml), targetDoc);
	}
};
xslt.init(xsl);

/**
* Parse a given text and render it into given HTML element
*
* @param  {string} text
* @param  {!HTMLElement} target
* @return {!Node}
*/
function preview(text, target)
{
	let targetDoc = target.ownerDocument;
	if (!targetDoc)
	{
		throw 'Target does not have a ownerDocument';
	}

	let resultFragment = xslt.transformToFragment(parse(text).replace(/<[eis]>[^<]*<\/[eis]>/g, ''), targetDoc),
		lastUpdated    = target;

	// https://bugs.chromium.org/p/chromium/issues/detail?id=266305
	if (typeof window !== 'undefined' && 'chrome' in window)
	{
		resultFragment.querySelectorAll('script').forEach(
			function (oldScript)
			{
				let newScript = document.createElement('script');
				for (let attribute of oldScript['attributes'])
				{
					newScript['setAttribute'](attribute.name, attribute.value);
				}
				newScript.textContent = oldScript.textContent;

				oldScript.parentNode.replaceChild(newScript, oldScript);
			}
		);
	}

	// Compute and refresh hashes
	if (HINT.hash)
	{
		computeHashes(resultFragment);
	}

	// Apply post-processing
	if (HINT.onRender)
	{
		executeEvents(resultFragment, 'render');
	}

	/**
	* Compute and set all hashes in given document fragment
	*
	* @param {!DocumentFragment} fragment
	*/
	function computeHashes(fragment)
	{
		let nodes = fragment.querySelectorAll('[data-s9e-livepreview-hash]'),
			i     = nodes.length;
		while (--i >= 0)
		{
			nodes[i]['setAttribute']('data-s9e-livepreview-hash', hash(nodes[i].outerHTML));
		}
	}

	/**
	* Execute an event's code on a given node
	*
	* @param {!Element} node
	* @param {string}   eventName
	*/
	function executeEvent(node, eventName)
	{
		let code     = node.getAttribute('data-s9e-livepreview-on' + eventName),
			codeHash = hash(code);
		if (!functionCache[codeHash])
		{
			functionCache[codeHash] = new Function(code);
		}

		functionCache[codeHash]['call'](node);
	}

	/**
	* Locate and execute an event on given document fragment or element
	*
	* @param {!DocumentFragment|!Element} root
	* @param {string}                     eventName
	*/
	function executeEvents(root, eventName)
	{
		// Execute the event on the root node, as there is no self-or-descendant selector in CSS
		if (root instanceof Element && root['hasAttribute']('data-s9e-livepreview-on' + eventName))
		{
			executeEvent(root, eventName);
		}

		let nodes = root.querySelectorAll('[data-s9e-livepreview-on' + eventName + ']'),
			i     = nodes.length;
		while (--i >= 0)
		{
			executeEvent(nodes[i], eventName);
		}
	}

	/**
	* Update the content of given node oldParent to match node newParent
	*
	* @param {!Node} oldParent
	* @param {!Node} newParent
	*/
	function refreshElementContent(oldParent, newParent)
	{
		let oldNodes = oldParent.childNodes,
			newNodes = newParent.childNodes,
			oldCnt   = oldNodes.length,
			newCnt   = newNodes.length,
			oldNode,
			newNode,
			left     = 0,
			right    = 0;

		// Skip the leftmost matching nodes
		while (left < oldCnt && left < newCnt)
		{
			oldNode = oldNodes[left];
			newNode = newNodes[left];
			if (!refreshNode(oldNode, newNode))
			{
				break;
			}

			++left;
		}

		// Skip the rightmost matching nodes
		let maxRight = Math.min(oldCnt - left, newCnt - left);
		while (right < maxRight)
		{
			oldNode = oldNodes[oldCnt - (right + 1)];
			newNode = newNodes[newCnt - (right + 1)];
			if (!refreshNode(oldNode, newNode))
			{
				break;
			}

			++right;
		}

		// Remove the old dirty nodes in the middle of the tree
		let i = oldCnt - right;
		while (--i >= left)
		{
			oldParent.removeChild(oldNodes[i]);
			lastUpdated = oldParent;
		}

		// Test whether there are any nodes in the new tree between the matching nodes at the left
		// and the matching nodes at the right
		let rightBoundary = newCnt - right;
		if (left >= rightBoundary)
		{
			return;
		}

		// Clone the new nodes
		let newNodesFragment = targetDoc.createDocumentFragment();
		i = left;
		do
		{
			newNode = newNodes[i];
			if (HINT.onUpdate && newNode instanceof Element)
			{
				executeEvents(newNode, 'update');
			}
			lastUpdated = newNodesFragment.appendChild(newNode);
		}
		while (i < --rightBoundary);

		// If we haven't skipped any nodes to the right, we can just append the fragment
		if (!right)
		{
			oldParent.appendChild(newNodesFragment);
		}
		else
		{
			oldParent.insertBefore(newNodesFragment, oldParent.childNodes[left]);
		}
	}

	/**
	* Update given node oldNode to make it match newNode
	*
	* @param {!Node} oldNode
	* @param {!Node} newNode
	* @return {boolean} Whether the node can be skipped
	*/
	function refreshNode(oldNode, newNode)
	{
		if (oldNode.nodeName !== newNode.nodeName || oldNode.nodeType !== newNode.nodeType)
		{
			return false;
		}

		if (oldNode instanceof HTMLElement && newNode instanceof HTMLElement)
		{
			if (!oldNode.isEqualNode(newNode) && !elementHashesMatch(oldNode, newNode))
			{
				if (HINT.onUpdate && newNode['hasAttribute']('data-s9e-livepreview-onupdate'))
				{
					executeEvent(newNode, 'update');
				}
				syncElementAttributes(oldNode, newNode);
				refreshElementContent(oldNode, newNode);
			}
		}
		// Node.TEXT_NODE || Node.COMMENT_NODE
		else if (oldNode.nodeType === 3 || oldNode.nodeType === 8)
		{
			if (oldNode.nodeValue !== newNode.nodeValue)
			{
				oldNode.nodeValue = newNode.nodeValue;
				lastUpdated = oldNode;
			}
		}

		return true;
	}

	/**
	* Test whether both given elements have a hash value and both match
	*
	* @param  {!HTMLElement} oldEl
	* @param  {!HTMLElement} newEl
	* @return {boolean}
	*/
	function elementHashesMatch(oldEl, newEl)
	{
		if (!HINT.hash)
		{
			// Hashes can never match if there are no hashes in any template
			return false;
		}
		const attrName = 'data-s9e-livepreview-hash';

		return oldEl['hasAttribute'](attrName) && newEl['hasAttribute'](attrName) && oldEl['getAttribute'](attrName) === newEl['getAttribute'](attrName);
	}

	/**
	* Hash given string
	*
	* @param  {string} text
	* @return {number}
	*/
	function hash(text)
	{
		let pos = text.length, s1 = 0, s2 = 0;
		while (--pos >= 0)
		{
			s1 = (s1 + text.charCodeAt(pos)) % 0xFFFF;
			s2 = (s1 + s2) % 0xFFFF;
		}

		return (s2 << 16) | s1;
	}

	/**
	* Make the set of attributes of given element oldEl match newEl's
	*
	* @param {!HTMLElement} oldEl
	* @param {!HTMLElement} newEl
	*/
	function syncElementAttributes(oldEl, newEl)
	{
		let oldAttributes = oldEl['attributes'],
			newAttributes = newEl['attributes'],
			oldCnt        = oldAttributes.length,
			newCnt        = newAttributes.length,
			i             = oldCnt,
			ignoreAttrs   = ' ' + oldEl.getAttribute('data-s9e-livepreview-ignore-attrs') + ' ';

		while (--i >= 0)
		{
			let oldAttr      = oldAttributes[i],
				namespaceURI = oldAttr['namespaceURI'],
				attrName     = oldAttr['name'];

			if (HINT.ignoreAttrs && ignoreAttrs.indexOf(' ' + attrName + ' ') > -1)
			{
				continue;
			}
			if (!newEl.hasAttributeNS(namespaceURI, attrName))
			{
				oldEl.removeAttributeNS(namespaceURI, attrName);
				lastUpdated = oldEl;
			}
		}

		i = newCnt;
		while (--i >= 0)
		{
			let newAttr      = newAttributes[i],
				namespaceURI = newAttr['namespaceURI'],
				attrName     = newAttr['name'],
				attrValue    = newAttr['value'];

			if (HINT.ignoreAttrs && ignoreAttrs.indexOf(' ' + attrName + ' ') > -1)
			{
				continue;
			}
			if (attrValue !== oldEl.getAttributeNS(namespaceURI, attrName))
			{
				oldEl.setAttributeNS(namespaceURI, attrName, attrValue);
				lastUpdated = oldEl;
			}
		}
	}

	refreshElementContent(target, resultFragment);

	return lastUpdated;
}

/**
* Set the value of a stylesheet parameter
*
* @param {string} paramName  Parameter name
* @param {string} paramValue Parameter's value
*/
function setParameter(paramName, paramValue)
{
	xslt.setParameter(paramName, paramValue);
}