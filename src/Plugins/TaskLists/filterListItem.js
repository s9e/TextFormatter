/**
* @param {!Tag}   listItem
* @param {string} text
*/
function (listItem, text)
{
	// Test whether the list item is followed by a task checkbox
	var pos = listItem.getPos() + listItem.getLen();
	while (text.charAt(pos) === ' ')
	{
		++pos;
	}
	var str = text.substr(pos, 3);
	if (!/\[[ Xx]\]/.test(str))
	{
		return;
	}

	// Create a tag for the task and assign it a random ID
	var taskId    = Math.random().toString(16).substr(2),
		taskState = (str === '[ ]') ? 'unchecked' : 'checked',
		task      = addSelfClosingTag('TASK', pos, 3);

	task.setAttribute('id',    taskId);
	task.setAttribute('state', taskState);

	listItem.cascadeInvalidationTo(task);
}