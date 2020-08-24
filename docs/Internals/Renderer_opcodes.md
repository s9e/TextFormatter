`escape` values:

 - `attribute`
 - `comment` ?
 - `raw`
 - `text`
 - `url` ?

<dl>
	<dt>apply_templates</dt>
	<dd>Recursiverly process all child nodes, or nodes selected by optional XPath expression `select`.</dd>

	<dt>buffer_literal</dt>
	<dd>Store given string literal into buffer `id`.</dd>

	<dt>buffer_xpath</dt>
	<dd>Store the result of XPath expression `expr` into buffer `id`.</dd>

	<dt>output_buffer</dt>
	<dd>Output the content of buffer `id` escaped using `escape` strategy.</dd>

	<dt>output_literal</dt>
	<dd>Output given string literal.</dd>

	<dt>output_xpath</dt>
	<dd>Output the result of XPath expression `expr` escaped using `escape` strategy.</dd>
</dl>