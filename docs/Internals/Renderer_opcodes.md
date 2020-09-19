<code>escape<code> values:

 - <code>attribute<code>
 - <code>comment<code> ?
 - <code>raw<code>
 - <code>text<code>
 - <code>url<code> ?

<dl>
	<dt>apply_templates</dt>
	<dd>Recursiverly process all child nodes, or nodes selected by optional XPath expression <code>select<code>.</dd>

	<dt>buffer_append_literal</dt>
	<dd>Append given string literal <code>content</code> to buffer <code>id</code>.</dd>

	<dt>buffer_append_xpath</dt>
	<dd>Append the result of XPath expression <code>expr</code> to buffer <code>id</code>.</dd>

	<dt>buffer_set_literal</dt>
	<dd>Store given string literal <code>content</code> into buffer <code>id</code>.</dd>

	<dt>buffer_set_xpath</dt>
	<dd>Store the result of XPath expression <code>expr</code> into buffer <code>id</code>.</dd>

	<dt>conditional</dt>
	<dd></dd>

	<dt>condition_default</dt>
	<dd></dd>

	<dt>condition_xpath</dt>
	<dd><code>expr</code></dd>

	<dt>output_buffer</dt>
	<dd>Output the content of buffer <code>id</code> escaped using <code>escape</code> strategy.</dd>

	<dt>output_literal</dt>
	<dd>Output given string literal <code>content</code>.</dd>

	<dt>output_xpath</dt>
	<dd>Output the result of XPath expression <code>expr</code> escaped using <code>escape</code> strategy.</dd>
</dl>