/* Updates the uptime in realtime, on the client side (since it's predictable).
Also looks neat. */
function animate_uptime()
{	
	var prefix = "Uptime: ";

	var secs = parseInt(raw_uptime % 60);
	var mins = parseInt(raw_uptime / 60 % 60);
	var hours = parseInt(raw_uptime / 3600 % 24);
	var days = parseInt(raw_uptime / 86400);

	days = (days == 0) ? '' : days + "d ";
	mins = (mins > 9) ? mins : "0" + mins;

	if (uptime_show_seconds)
	{
		secs = (secs > 9) ? secs : "0" + secs;
		new_uptime = prefix + days + hours + ":" + mins + ":" + secs;
	}
	else new_uptime = prefix + days + hours + ":" + mins;

	var uptime_location = document.getElementById("uptime");
	var replacement = document.createTextNode (new_uptime);
	uptime_location.replaceChild(replacement, uptime_location.childNodes[0]);

	raw_uptime ++;
	setTimeout ("animate_uptime()", 1000);
}

/* update_load() and update_ram() involve AJAX requests to the Ship backend,
which supplies JSON which is parsed by the browser's native JSON parser (many
advantages, though it may not work in older browers, which need to refresh the
page to see changes other than the uptime). */
function update_load()
{
	var prefix = "Load average: ";
	
	xmlhttp = new XMLHttpRequest();
	xmlhttp.onreadystatechange = function()
	{
		if (xmlhttp.readyState == 4 && xmlhttp.status == 200)
		{
			var json = JSON.parse (xmlhttp.responseText);
			document.getElementById("load").innerHTML = prefix + json.load;
		}
	}
	xmlhttp.open("GET", "backend.php?q=cpu", true);
	xmlhttp.send();
	
	setTimeout ("update_load()", refresh_rate);
}

function update_ram()
{
	xmlhttp = new XMLHttpRequest();
	xmlhttp.onreadystatechange = function()
	{
		if (xmlhttp.readyState == 4 && xmlhttp.status == 200)
		{
			var json = JSON.parse (xmlhttp.responseText);
			document.getElementById("ram_used").innerHTML = json.ram.used + " used (" + json.ram.pctused + "%)";
			document.getElementById("ram_used_meter").style.width = json.ram.pctused + "%";
			document.getElementById("swap_used").innerHTML = json.swap.used + " used (" + json.swap.pctused + "%)";
			document.getElementById("swap_used_meter").style.width = json.swap.pctused + "%";
		}
	}
	xmlhttp.open("GET", "backend.php?q=ram", true);
	xmlhttp.send();
	
	setTimeout ("update_ram()", refresh_rate);
}
