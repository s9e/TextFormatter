/**
* @param {!Tag}   listItem
* @param {string} text
*/
function (listItem, text)
{
	// Test whether the list item is followed by a task checkbox
	let pos = listItem.getPos() + listItem.getLen();
	while (text.charAt(pos) === ' ')
	{
		++pos;
	}
	let str = text.substring(pos, pos + 3);
	if (!/\[[ Xx]\]/.test(str))
	{
		return;
	}

	// Create a tag for the task and assign it a random ID
	let taskId    = Math.random().toString(16).substring(2),
		taskState = (str === '[ ]') ? 'unchecked' : 'checked',
		task      = addSelfClosingTag('TASK', pos, 3);

	task.setAttribute('id',    taskId);
	task.setAttribute('state', taskState);

	listItem.cascadeInvalidationTo(task);
}