/** @define {boolean} */
const HAS_METHOD_FORWARD = true;

/** @define {boolean} */
const HAS_METHOD_RESIZE = true;

/** @define {boolean} */
const HAS_METHOD_RESIZE_HEIGHT = true;

/** @define {boolean} */
const HAS_METHOD_RESIZE_WIDTH = true;

/** @this {!HTMLIFrameElement} */
(function()
{
	let c = new MessageChannel,
		w = this.contentWindow;
	c.port1.onmessage = (e) =>
	{
		let data   = e.data,
			method = data['method'];

		if (method === 'resize' && HAS_METHOD_RESIZE)
		{
			let height = +data['height'],
				width  = +data['width'];
			if (height && HAS_METHOD_RESIZE_HEIGHT)
			{
				this.style.height = height + 'px';
			}
			if (width && HAS_METHOD_RESIZE_WIDTH)
			{
				this.style.width = width + 'px';
			}
		}
		else if (method === 'forward' && HAS_METHOD_FORWARD)
		{
			window.addEventListener(
				'message',
				(e) =>
				{
					if (e.source.parent === w)
					{
						c.port1.postMessage(
							{
								'method': 'message',
								'data':   data['data']
							}
						);
					}
				}
			);
		}
	};
	w.postMessage('s9e:init:3', '*', [c.port2]);
}).call(/** @type {!HTMLIFrameElement} */  (document.querySelector('iframe')));