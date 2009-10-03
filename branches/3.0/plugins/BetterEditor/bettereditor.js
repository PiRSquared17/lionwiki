function resizeTextarea(obj, step)
{
	var txt = findClosest("contentTextarea", obj);

	if(txt.offsetHeight + step > 0)
		txt.style.height = (txt.offsetHeight + step) + "px";
}

function insertSyntax(obj, otag, ctag, content)
{
	var txt = findClosest("contentTextarea", obj);

	start = txt.selectionStart,
	end = txt.selectionEnd;

	txt.focus();

	if(start != end)
		content = txt.value.substring(start, end);

	txt.value = txt.value.substring(0, start) + otag + content + ctag + txt.value.substring(end);
	txt.selectionStart = (txt.value.substring(0, start) + otag).length;
	txt.selectionEnd = txt.selectionStart + content.length;
}

function findClosest(classname, node)
{
	do {
		var arr = getElementsByClassName(classname, node);

		node = node.parentNode;
	} while(arr.length == 0);

	return arr[0];
}

function getElementsByClassName(classname, node)
{
	if(!node)
		node = document.getElementsByTagName("body")[0];

	var a = [];

	var re = new RegExp('\\b' + classname + '\\b');

	var els = node.getElementsByTagName("*");

	for(var i = 0, j = els.length; i < j; i++)
		if(re.test(els[i].className))
			a.push(els[i]);

	return a;
}