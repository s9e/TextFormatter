<h2>Built-in filters</h2>

[Up-to-date API doc](https://s9e.github.io/TextFormatter/api/s9e/TextFormatter/Configurator/Items/AttributeFilters.html).

<dl>
<dt>#alnum</dt>
<dd>Alphanumeric value. Matches <code>/^[0-9A-Za-z]+$/</code>.</dd>

<dt>#choice</dt>
<dd>A white list of case-sensitive (optionally insensitive) values.</dd>

<dt>#color</dt>
<dd>Any string that looks like a CSS color. Matches hex values such as <code>#123</code> or <code>#123456</code>, color names such as <code>red</code> (or any string composed of letters from a to z) and CSS rgb() values such as <code>rgb(12, 34, 56)</code>.</dd>

<dt>#email</dt>
<dd>A well-formed email address. Uses ext/filter's FILTER_VALIDATE_EMAIL filter. The JavaScript version is much more lenient.</dd>

<dt>#false</dt>
<dd>Dummy filter that systematically invalidates the attribute value.</dd>

<dt>#float</dt>
<dd>A decimal value. Uses ext/filter's FILTER_VALIDATE_FLOAT filter. Returns a float, not a string.</dd>

<dt>#fontfamily</dt>
<dd>A CSS font-family value. More restrictive than the CSS specs. Font names can only contain ASCII letters and digits. They can be quoted and separated by a comma optionally surrounded with spaces.</dd>

<dt>#hashmap</dt>
<dd>Requires an associative array that maps strings to their replacement. Case-sensitive. Preserves unknown values by default.</dd>

<dt>#identifier</dt>
<dd>A string of letters, numbers, dashes and underscores. Matches <code>/^[-0-9A-Za-z_]+$/</code>.</dd>

<dt>#int</dt>
<dd>An integer value. Uses ext/filter's FILTER_VALIDATE_INT filter. Returns an integer, not a string.</dd>

<dt>#ip</dt>
<dd>A valid IPv4 or IPv6 address. Uses ext/filter's FILTER_VALIDATE_IP filter.</dd>

<dt>#ipport</dt>
<dd>A valid IPv4 or IPv6 address with a port such as <code>127.0.0.1:80</code> or <code>[ff02::1]:80</code>. Uses ext/filter's FILTER_VALIDATE_IP filter for the IP part.</dd>

<dt>#ipv4</dt>
<dd>A valid IPv4 address. Uses ext/filter's FILTER_VALIDATE_IP filter with the FILTER_FLAG_IPV4 flag.</dd>

<dt>#ipv6</dt>
<dd>A valid IPv6 address. Uses ext/filter's FILTER_VALIDATE_IP filter with the FILTER_FLAG_IPV6 flag.</dd>

<dt>#map</dt>
<dd>Requires an associative array that maps strings to their replacement. Case-insensitive by default. Preserves unknown values by default.</dd>

<dt>#number</dt>
<dd>A string made of digits. Matches <code>/^[0-9]+$/</code>. Note that unlike #int, "0123" is a valid number. Returns a string.</dd>

<dt>#range</dt>
<dd>An integer value, adjusted for given range. Requires a <code>min</code> option and a <code>max</code> option. Uses ext/filter's FILTER_VALIDATE_INT filter. Values outside of range are adjusted to closest valid value. Returns an integer.</dd>

<dt>#regexp</dt>
<dd>A string that matches given regexp. Requires a <code>regexp</code> option.</dd>

<dt>#simpletext</dt>
<dd>A string that matches <code>/^[- +,.0-9A-Za-z_]+$/</code>.</dd>

<dt>#timestamp</dt>
<dd>A unit of time such as <code>1h12m30s</code> converted to a number of seconds. Also accepts a number.</dd>

<dt>#uint</dt>
<dd>An unsigned integer value. Same as #int, but rejects values less than 0.</dd>

<dt>#url</dt>
<dd>A valid URL as per <a href="https://url.spec.whatwg.org/">the URL living standard</a>, with some small differences. Emphasis is on security features rather than strict compliance. Trims surrounding whitespace and encodes illegal characters to facilitate compliance with <a href="https://w3c.github.io/html/infrastructure.html#valid-url-potentially-surrounded-by-spaces">HTML specs</a>. Uses the settings from UrlConfig. Punycodes IDNs if <code>idn_to_ascii()</code> is available. URL-encodes non-ASCII characters. Also URL-encodes the following characters <code>"'()&lt;&gt;</code> as well as line terminators to make the URL safer to use in CSS and JavaScript. Validates scheme against allowed schemes. Validates host against the white- and black- lists of hosts. Supports relative URLs.</dd>

</dl>