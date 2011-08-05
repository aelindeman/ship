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
