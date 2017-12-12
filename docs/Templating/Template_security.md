<h2>Template security</h2>

By default, a number of security checks are performed on templates whenever a new renderer is created.

### PHP tags are forbidden

Technically, the PHP tags `<?php` and `?>` are valid XML even though they have no effect. Leaving them in does not pose a security risk in itself. However, if the rendering stylesheet was dumped to disk and then somehow read by the PHP interpreter, those tags could become active. Since they have no practical use, they are disallowed. Similarly, instructions that would create PHP tags in the output are disallowed.

### Output escaping cannot be disabled

Text content and other values are automatically escaped by XSLT processors but it can be disabled with the [disable-output-escaping attribute](https://www.w3.org/TR/xslt#disable-output-escaping). Disabling output escaping could lead to HTML injection, and therefore it is forbidden.

### Exotic XSL is disabled

A number of rarely-used XSL features are disabled, such as [attribute sets](https://www.w3.org/TR/xslt#attribute-sets), `<xsl:copy/>`, `<xsl:copy-of/>` targeting anything but a single attribute, the `document()` function, as well as dynamic element names and attribute names.

### Dynamic content in JavaScript, CSS and URLs

Security checks are performed on JavaScript, CSS and URL attributes and elements that contain dynamic content (e.g. user input.) For instance, a template such as `<a href="{@url}">...</a>` will be rejected if the `url` attribute is not filtered with the `#url` filter. Similarly, only safe data such as numbers or URLs are allowed in a JavaScript or CSS context. The primary goal is to prevent accidental XSS vectors and make it harder to create malicious templates that could be used in a social-engineered effort to create an XSS vector, while still allowing some user input in CSS, JavaScript and URLs.

Examples of what's allowed or not can be found in the [testdox output](https://github.com/s9e/TextFormatter/blob/master/docs/testdox.txt) or the `s9e\TextFormatter\Tests\Configurator\TemplateChecks` tests.

### Flash's AllowScriptAccess is restricted to sameDomain

This is to prevent external objects from taking over the page. Note that this has been the default setting since Flash 9.
