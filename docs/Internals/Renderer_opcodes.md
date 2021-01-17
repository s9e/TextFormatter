<code>escape<code> values:

 - <code>attribute<code> escape `&` `<` `>` `"`
 - <code>comment<code> ?
 - <code>raw<code> no escaping
 - <code>text<code> escape `&` `<` `>`
 - <code>???<code> remove tags then unescape entities(?)
 - <code>url<code> ?

Escaping methods can be chained via buffer copy.

TODO: add output_method=(text|html) to buffer_transform.

<dl>
	<dt>buffer_buffer</dt>
	<dd>Append the content of buffer <code>source</code> to buffer <code>id</code>, escaped using the <code>escape</code> method.</dd>

	<dt>buffer_literal</dt>
	<dd>Append given string literal <code>value</code> to buffer <code>id</code>.</dd>

	<dt>buffer_transform</dt>
	<dd>Recursively process all child nodes, or nodes selected by optional XPath expression <code>select<code> and append the result to buffer <code>id</code>, escaped using the <code>escape</code> method.</dd>

	<dt>buffer_xpath</dt>
	<dd>Append the result of XPath expression <code>select</code> to buffer <code>id</code>, escaped using the <code>escape</code> method.</dd>

	<dt>block_choose</dt>
	<dd></dd>

	<dt>block_match</dt>
	<dd>Match each condition of this block against the string value of XPath expression <code>select</code>.</dd>

	<dt>clear_buffer</dt>
	<dd>Set the value of buffer <code>id</code> to an empty string.</dd>

	<dt>condition_match</dt>
	<dd>Match literal string <code>value</code> against this block's value.</dd>

	<dt>condition_otherwise</dt>
	<dd></dd>

	<dt>condition_when</dt>
	<dd><code>test</code></dd>
</dl>