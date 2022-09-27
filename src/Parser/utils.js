/**
* @param  {string} str
* @return {string}
*/
function html_entity_decode(str)
{
	let b = document.createElement('b');
	html_entity_decode = function (str)
	{
		// We escape left brackets so that we don't inadvertently evaluate some nasty HTML such as
		// <img src=... onload=evil() />
		b.innerHTML = str.replace(/</g, '&lt;');

		return b.textContent;
	};

	return html_entity_decode(str);
}

/**
* @param  {string} str
* @return {string}
*/
function htmlspecialchars_compat(str)
{
	return str.replace(
		/[<>&"]/g,
		/**
		* @param  {string} c
		* @return {string}
		*/
		(c) =>
		{
			const t = {
				'<' : '&lt;',
				'>' : '&gt;',
				'&' : '&amp;',
				'"' : '&quot;'
			};
			return t[c];
		}
	);
}

/**
* @param  {string} str
* @return {string}
*/
function htmlspecialchars_noquotes(str)
{
	return str.replace(
		/[<>&]/g,
		/**
		* @param  {string} c
		* @return {string}
		*/
		(c) =>
		{
			const t = {
				'<' : '&lt;',
				'>' : '&gt;',
				'&' : '&amp;'
			};
			return t[c];
		}
	);
}

/**
* @param  {string} str
* @return {string}
*/
function rawurlencode(str)
{
	return encodeURIComponent(str).replace(
		/[!'()*]/g,
		/**
		* @param  {string} c
		* @return {string}
		*/
		(c) =>
		{
			return '%' + c.charCodeAt(0).toString(16).toUpperCase();
		}
	);
}

/**
* @return {boolean}
*/
function returnFalse()
{
	return false;
}

/**
* @return {boolean}
*/
function returnTrue()
{
	return true;
}