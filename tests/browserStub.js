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
								'&#9829;'  : '♥'
							};

							return (entity in table) ? table[entity] : entity;
						}
					);
				}
			);
		};
	}
};