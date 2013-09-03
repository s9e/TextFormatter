var contentLen, endTagLen, endTagPos, m, regexp, startTagLen, startTagPos, url;

// Inline links
if (text.indexOf('[') > -1)
{
	regexp = /\[(.*?)\]\(((?:[^\\]*(?:\\.)*)*?)\)/g;

	while (m = regexp.exec(text))
	{
		contentLen  = m[1].length;
		startTagPos = m['index'];
		startTagLen = 1;
		endTagPos   = startTagPos + 1 + contentLen;
		endTagLen   = m[0].length - 1 - contentLen;
		url         = m[2].replace(/\\(.)/g, '$1');

		addTagPair('URL', startTagPos, startTagLen, endTagPos, endTagLen).setAttribute('url', url);
	}
}