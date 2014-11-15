function(str)
{
	return encodeURIComponent(str).replace(
		/[!'()*]/g,
		/**
		* @param {!string} c
		*/
		function(c)
		{
			return '%' + c.charCodeAt(0).toString(16).toUpperCase();
		}
	);
}