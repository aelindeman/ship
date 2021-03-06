/**
 * Ship 2.0 Frontend Script
 *
 * Updates the data displayed on the frontend by loading the backend as a JSON
 * array.
*/

/* Timeouts for the update timeouts so they can be cancelled if the uptime
toggler is toggled. */
var upd_uptime_timeout;
var upd_ship_timeout;

/* Function called when the auto-refresh toggle button is clicked. */
function toggle_auto_refresh ()
{
	// switch and show auto_refresh value in toggle button label
	auto_refresh = !auto_refresh;
	var refresh_state = auto_refresh ? "on" : "off";

	document.getElementById("arstatus").innerHTML = refresh_state;

	// the uptime clock will not have updated while auto_refresh was disabled,
	// so get the current uptime
	if (auto_refresh)
	{
		var xmlhttp = new XMLHttpRequest();
		xmlhttp.onreadystatechange = function()
		{
			if (this.readyState == 4 && this.status == 200)
			{
				raw_uptime = JSON.parse (this.responseText);
			}
		}
		xmlhttp.open ("GET", "./backend.php?q=uptime", true);
		xmlhttp.send();

		// tell the updaters to start again
		animate_uptime();
		update_ship();
	}
	else
	{
		clearTimeout (upd_uptime_timeout);
		clearTimeout (upd_ship_timeout);
	}

	return false;
}

/* Updates the uptime in realtime, on the client side (since it's predictable).
Also looks neat. */
function animate_uptime()
{
	var prefix = "Uptime: ";

	var secs = parseInt (raw_uptime % 60);
	var mins = parseInt (raw_uptime / 60 % 60);
	var hours = parseInt (raw_uptime / 3600 % 24);
	var days = parseInt (raw_uptime / 86400);

	days = (days == 0) ? '' : days + "d ";
	mins = (mins > 9) ? mins : "0" + mins;

	if (uptime_show_seconds)
	{
		secs = (secs > 9) ? secs : "0" + secs;
		new_uptime = prefix + days + hours + ":" + mins + ":" + secs;
	}
	else new_uptime = prefix + days + hours + ":" + mins;

	var uptime_location = document.getElementById("uptime");
	var replacement = document.createTextNode(new_uptime);
	uptime_location.replaceChild(replacement, uptime_location.childNodes[0]);

	raw_uptime ++;

	if (auto_refresh)
	{
		upd_uptime_timeout = setTimeout ("animate_uptime()", 1000);
	}
}

/* The do_*() functions process data independently to keep the code in
update_ship() cleaner. */
function do_load ()
{
	var prefix = "Load average: ";
	document.getElementById("load").innerHTML =
		prefix + data.cpu.load;
}

function do_processes ()
{
	var table = "";

	for (i in data.processes.top)
	{
		pid = data.processes.top[i].pid;
		process = data.processes.top[i].process;
		user = data.processes.top[i].user;
		cpu = data.processes.top[i].cpu;
		ram = data.processes.top[i].ram;

		table += "<tr><td class='pid'>" + pid + "</td><td class='process'>" +
			process + "</td><td class='user'>" + user + "</td><td class='cpu'>" + cpu + "</td><td class='ram'>" +
			ram + "</td></tr>";
	}

	document.getElementById("pstotal").innerHTML = data.processes.total +
		" processes";

	document.getElementById("pstable").innerHTML = table;
}

function do_ram ()
{
	document.getElementById("ram_used").innerHTML =
		data.ram.ram.used + " used (" + data.ram.ram.pctused + "%)";
	document.getElementById("ram_used_meter").style.width =
		data.ram.ram.pctused + "%";
	document.getElementById("swap_used").innerHTML =
		data.ram.swap.used + " used (" + data.ram.swap.pctused + "%)";
	document.getElementById("swap_used_meter").style.width =
		data.ram.swap.pctused + "%";
}

function do_hddtemp ()
{
	var table = "";

	// don't use degree symbol if using Kelvin
	di = (data.hddtemp[0].units.toUpperCase() != "K") ? "&deg;" : " ";

	for (i in data.hddtemp)
	{
		dev = data.hddtemp[i].dev;
		model = data.hddtemp[i].model;
		temp = data.hddtemp[i].temp;
		units = data.hddtemp[i].units;
		status = data.hddtemp[i].status;

		table += "<tr><td class='dev'>" + dev + "</td><td class='model'>" +
			model + "</td><td class='temp " + status + "'><span>" + temp +
			di + units + "</span></td></tr>";
	}

	document.getElementById("hdttable").innerHTML = table;
}

function do_diskspace ()
{
	var table = "";

	for (i in data.diskspace)
	{
		dev = data.diskspace[i].dev;
		type = data.diskspace[i].type;
		mount = data.diskspace[i].mount;

		total = data.diskspace[i].total;
		used = data.diskspace[i].used;
		free = data.diskspace[i].free;
		pctused = data.diskspace[i].pctused;

		meter = "<div class='meter container' title='" + pctused + " used (" +
			free + " free)'><div class='meter fill' style='width:" + pctused +
			"'><span class='pct'>&nbsp;</span></div></div>";
		row = "<tr><td class='disk' title='" + dev + "'>" + mount +
			"</td><td class='type'>" + type + "</td><td class='size'>" + used +
			" of " + total + " used" + meter + "</td></tr>";

		table += row;
	}

	document.getElementById("disktable").innerHTML = table;
}

/* Updates the entire frontend with one JSON request instead of many little
ones. */
function update_ship ()
{
	// Make sure the browser supports native JSON parsing. If not, then disable
	// the auto-updater.
	if (typeof JSON != 'object')
	{
		document.getElementById("artoggle").innerHTML =
			"<span class='nojson'>Auto-refresh is not supported</span>";

		return false;
	}

	var xmlhttp = new XMLHttpRequest();
	xmlhttp.onreadystatechange = function()
	{
		if (this.readyState == 4 && this.status == 200)
		{
			data = JSON.parse (this.responseText);

			// update the sections individually
			do_load();
			do_processes();
			do_ram();
			do_hddtemp();
			do_diskspace();
		}
	}
	xmlhttp.open ("GET", "./backend.php?q=all", true);
	xmlhttp.send();

	// refresh rate really shouldn't be less than one second or it will
	// dramatically increase load on the server
	if (refresh_rate < 1) refresh_rate = 1;

	if (auto_refresh)
	{
		upd_ship_timeout = setTimeout ("update_ship()", refresh_rate);
	}
}
