// This file contains some JavaScript-specific utilities

/**
* @param  {!string} str
* @return {!string}
*/
function html_entity_decode(str)
{
	var b = document.createElement('b');

	// We escape left brackets so that we don't inadvertently evaluate some nasty HTML such as
	// <img src=... onload=evil() />
	b.innerHTML = str.replace(/</g, '&lt;');

	return b.textContent;
}