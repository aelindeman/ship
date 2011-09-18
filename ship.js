/**
 * Ship 2.0 Frontend Script
 *
 * Updates the data displayed on the frontend by loading the backend as a JSON
 * array.
*/

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

/* Processes data to keep the code in update_ship() cleaner. */
function do_load (data)
{
	var prefix = "Load average: ";
	document.getElementById("load").innerHTML =
		prefix + data.load;
}

function do_ram (data)
{
	document.getElementById("ram_used").innerHTML =
		data.ram.used + " used (" + data.ram.pctused + "%)";
	document.getElementById("ram_used_meter").style.width =
		data.ram.pctused + "%";
	document.getElementById("swap_used").innerHTML =
		data.swap.used + " used (" + data.swap.pctused + "%)";
	document.getElementById("swap_used_meter").style.width =
		data.swap.pctused + "%";
}

/* Updates the entire frontend with one JSON request instead of many little
ones. */
function update_ship ()
{
	var xmlhttp = new XMLHttpRequest();
	xmlhttp.onreadystatechange = function()
	{
		if (this.readyState == 4 && this.status == 200)
		{
			var json = JSON.parse (this.responseText);
			do_load (json.ram);
			do_ram (json.cpu);
		}
	}
	xmlhttp.open("GET", "./backend.php?q=all", true);
	xmlhttp.send();

	setTimeout ("update_ship()", refresh_rate);
}
