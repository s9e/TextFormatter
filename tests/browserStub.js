window = {};
document = {
	createElement: function()
	{
		return new function()
		{
			this.__defineGetter__(
				'textContent',
				function ()
				{
					return this.innerHTML.replace(
						/&[^;]+;/g,
						function (entity)
						{
							var table = {
								'&lt;'     : '<',
								'&gt;'     : '>',
								'&amp;'    : '&',
								'&quot;'   : '"',
								'&hearts;' : '♥',
								'&#x2665;' : '♥',
								'&#9829;'  : '♥',
								'&#32;'    : ' ',
								'&#00;'    : '\0'
							};

							return (entity in table) ? table[entity] : entity;
						}
					);
				}
			);
		};
	}
};

// Emulate punycode.js from https://github.com/bestiejs/punycode.js/
punycode = {
	toASCII: function(host)
	{
		var table= {
			'www.älypää.com': 'www.xn--lyp-plada.com'
		}

		return (host in table) ? table[host] : host;
	}
};
