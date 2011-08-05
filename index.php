<?php
define ('SHIP_VERSION', '2.0 (Consolidated Pre-Alpha 1)');

$hn = trim(`uname -n`);
$ip = trim(file_get_contents ('http://icanhazip.com/'));
$dn = $_SERVER['SERVER_NAME'];
$os = trim(file_get_contents ('/etc/issue.net'));
$kv = trim(`uname -rm`);

# helpful function for rounding disk sizes
# initial size must be in kB
function sizeup ($size, $p = 1)
{
	$sizes = array('EB', 'PB', 'TB', 'GB', 'MB', 'kB');
	$total = count($sizes);

	while ($total-- && $size > 1024) $size /= 1024;
	return sprintf ('%.'.$p.'f ', $size).$sizes[$total];
}

function typhoon_uptime()
{
	$cmd = explode (' ', file_get_contents ('/proc/uptime'));
	$seconds = round($cmd[0]);

	$secs = str_pad(intval($seconds % 60), 2, '0', STR_PAD_LEFT);
	$mins = str_pad(intval($seconds / 60 % 60), 2, '0', STR_PAD_LEFT);
	$hours = intval($seconds / 3600 % 24);
	$days = intval($seconds / 86400);

	$days = ($days == 0) ? '' : $days.'d ';

	return "{$days}{$hours}:{$mins}";
}
$ut = typhoon_uptime();

$cpu_proc = explode(':', `cat /proc/cpuinfo | grep 'model name' | head -1`);
$cpu = str_replace (array ('(R)','(C)','(TM)', 'CPU'), '', $cpu_proc[1]);

$ld = trim(`cat /proc/loadavg | awk '{ print $1, $2, $3 }'`);

function typhoon_ram_usage()
{
	$mem = `cat /proc/meminfo | grep -E '^(MemTotal|MemFree|Buffers|Cached|SwapTotal|SwapFree)' | sed -e 's/[kKMG]B//g'`;

	$proc = array ();
	foreach (explode ("\n", $mem) as $l)
	{
		@list ($key, $value) = explode (':', $l, 2);
		if (!empty ($key) and !empty ($value)) $proc[$key] = intval ($value);
	}

	$total = $proc['MemTotal'];
	$free = $proc['MemFree'] + $proc['Buffers'] + $proc['Cached'];
	$used = $total - $free;

	$swap_total = $proc['SwapTotal'];
	$swap_free = $proc['SwapFree'];
	$swap_used = $swap_total - $swap_free;

	$pct_used = round ($used / $total * 100);
	$pct_swap_used = round ($swap_used / $swap_total * 100);

	$total = sizeup ($total);
	$free = sizeup ($free);
	$used = sizeup ($used);

	$swap_total = sizeup ($swap_total);
	$swap_free = sizeup ($swap_free);
	$swap_used = sizeup ($swap_used);

	/*
	return array (
		'mem'	=> array ($total, $free, $used, $pct_used),
		'swap'	=> array ($swap_total, $swap_free, $swap_used, $pct_swap_used),
	);
	*/

	$status = 'ok'; # for future use

	return <<<RAM
<h2 class="ramheader">$total RAM</h2>
$used used (${pct_used}%)
<div class="meter container float {$status}">
	<div class="meter fill {$status}" style="width:${pct_used}%">
		<span class="pct">(${pct_used}% used)</span>
	</div>
</div>

<h2 class="swapheader">$swap_total swap</h2>
$swap_used used (${pct_swap_used}%)
<div class="meter container float {$status}">
	<div class="meter fill {$status}" style="width:${pct_swap_used}%">
		<span class="pct">(${pct_swap_used}% used)</span>
	</div>
</div>
RAM;
}

function typhoon_disk_temps()
{
	$sock = fsockopen ('127.0.0.1', 7634, $en, $em, 1);
	$data = '';
	while (($buffer = fgets ($sock, 512)) !== false)
	{
		$data .= $buffer;
	}

	# throw error if something happened
	if (!feof ($sock)) throw new Exception ($em);
	fclose ($sock);

	$disks = array();

	foreach (explode ('||', $data) as $d)
	{
		$c = explode('|', $d);

		foreach ($c as $k=>$v)
		{
			if (empty ($v)) unset ($c[$k]);
		}
		$c = array_values($c);

		$disks[] = $c;
	}
	# return $disks;

	$r = '';
	foreach ($disks as $d)
	{
		list ($dev, $model, $deg, $units) = $d;

		if ($deg >= 40)
		{
			if ($deg >= 50) $status = 'crit';
			else $status = 'warn';
		}
		else $status = 'ok';

		$r .= <<<DISK
<tr>
	<td class="dev">$dev</td>
	<td class="model">$model</td>
	<td class="temp ${status}"><span>${deg}&deg;${units}</span></td>
</tr>
DISK;
	}
	return $r;
}

function typhoon_disk_space()
{
	# Doesn't technically use procfs.
	$proc = trim (`df -lPT --exclude=tmpfs | sed -e '1d'`);

	$disks = array();
	foreach (explode ("\n", $proc) as $d)
	{
		$disks[] = preg_split('/\s+/', $d, 7);
	}

	$r = '';
	foreach ($disks as $d)
	{
		$total = sizeup ($d[2]);
		$used = sizeup ($d[3]);
		$free = sizeup ($d[4]);

		$r .= <<<DISK
<tr>
	<td class="disk" title="${d[0]}">${d[6]}</td>
	<td class="type">${d[1]}</td>
	<td class="size">
		$used of $total used
		<div class="meter container">
			<div class="meter fill" style="width:${d[5]}%">
				<span class="pct">(${d[5]}% used)</span>
			</div>
		</div>
	</td>
DISK;
	}

	return $r;
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html;charset=UTF-8" />

		<!-- iPhone meta tags -->
		<meta name="apple-mobile-web-app-capable" content="yes" />
		<meta name="apple-mobile-web-app-status-bar-style" content="black" />
		<meta name="format-detection" content="telephone=no" />
		<meta name="viewport" content="width=420, initial-scale=0.75, user-scalable=no" />


		<title><?=$hn?> - Ship <?=SHIP_VERSION?></title>
		<style type="text/css">
@import "reset.css";
body
{
	background-color: #EEE;
	font: normal 12px "Lucida Grande", "Lucida Sans Unicode", "Lucida Sans", Geneva, Verdana, sans-serif;
	-webkit-text-size-adjust: none;
}
#wrapper {
	margin: 10px auto;
	width: 400px;
}

#header {
	background: url('logo.png') no-repeat 10px 50%;
	padding-left: 130px !important;
	height: 40px;
}
#header span {
	display: none;
}

#ship > div {
	background-color: #FFF;
	border: 1px solid #CCC;
	border-radius: 5px;
	-moz-border-radius: 5px;
	-webkit-border-radius: 5px;
	margin-bottom: 10px;
	padding: 10px;
}

#machine .hn h2 {
	background: transparent url('computer.png') no-repeat 100% 50%;
	font-size: 18px;
	font-weight: bold;
	line-height: 24px;
	margin: 0 0 5px 0;
	padding-right: 32px;
}
#machine .ip {
	float: right;
	font-family: Consolas, 'Lucida Console', 'DejaVu Sans Mono', 'Menlo', monospace;
	text-align: right;
}
#machine .os, #machine .kv {
	clear: left;
	float: left;
}
#machine .up {
	clear: both;
	padding-top: 5px;
}

#load .cpu {
	font-weight: bold;
}

h2.swapheader {
	margin-top: 10px;
}

.meter {
	border: 1px solid #246;
	padding: 0 !important;
	width: 200px;

	border-radius: 2px;
	-moz-border-radius: 2px;
	-webkit-border-radius: 2px;
}
.meter.container.float {
	clear: all;
	float: right;
	margin-top: 4px;
}
.meter.container {
	background-color: #FFF;
}
.meter.fill {
	background-color: #468;
	border: none !important;
	height: 6px !important;
	float: none !important;

	border-radius: 0;
	-moz-border-radius: 0;
	-webkit-border-radius: 0;
}
.meter .pct {
	display: none;
	float: right;
}
.meter.warn {
	border-color: #C60;
}
.meter.warn.fill {
	background-color: #F90;
}
.meter.crit {
	border-color: #900;
}
.meter.crit.fill {
	background-color: #C00;
}

#temps table {
	width: 100%;
}
#temps th {
	text-align: left;
	line-height: 18px;
}
#temps td {
	font: normal 12px Consolas, 'Lucida Console', 'DejaVu Sans Mono', 'Menlo', monospace;
	line-height: 18px;
}
#temps .temp {
	text-align: right;
}
#temps .temp span {
	display: inline-block;
	padding: 0 2px;
}
#temps .temp.warn span {
	background-color: #F90;
	color: #FFF;
}
#temps .temp.crit span {
	background-color: #F00;
	color: #FFF;
}

#diskspace table {
	width: 100%;
}
#diskspace th {
	text-align: left;
	line-height: 18px;
}
#diskspace td {
	line-height: 16px;
	padding: 3px 0 0 0;
}
#diskspace .disk {
	width: 100px;
	font-family: Consolas, 'Lucida Console', 'DejaVu Sans Mono', 'Menlo', monospace;
}
#diskspace .type {
	width: 80px;
	font-family: Consolas, 'Lucida Console', 'DejaVu Sans Mono', 'Menlo', monospace;
}
#diskspace .size {
	width: 200px;
}

#footer {
	width: 390px;
	margin: 0 auto 10px auto;
	font-size: 9px;
	color: #A9A9A9;
	text-align: right;
}
#footer a:link, #footer a:visited {
	color: inherit;
	text-decoration: none;
}
#footer a:hover {
	text-decoration: underline;
}

		</style>
	</head>
	<body>
		<div id="wrapper">
			<div id="ship">
				<div id="header">
					<span>Ship <?=SHIP_VERSION?></span>
				</div>
				<div id="machine">
					<div class="hn"><h2><?=$hn?></h2></div>
					<div class="ip"><?=$ip?><br /><?=$dn?></div>
					<div class="os"><?=$os?></div>
					<div class="kv"><?=$kv?></div>
					<div class="up">Uptime: <?=$ut?></div>
				</div>
				<div id="load">
					<h2><?=$cpu?></h2>
					<div class="load">Load average: <?=$ld?></div>
				</div>
				<div id="ram">
					<?=typhoon_ram_usage()?>
				</div>
				<div id="temps">
					<table>
						<tr class="header">
							<th>Disk</th>
							<th>Model</th>
							<th></th>
						</tr>
						<?=typhoon_disk_temps()?>
					</table>
				</div>
				<div id="diskspace">
					<table>
						<tr class="header">
							<th>Mount</th>
							<th>Type</th>
							<th></th>
						</tr>
						<?=typhoon_disk_space()?>
					</table>
				</div>
			</div>
		</div>
		<div id="footer">
			Ship <?=SHIP_VERSION?> - <a href="http://ael.me/ship/">ael.me/ship</a>
		</div>
	</body>
</html>
