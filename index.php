<?php
/**
 * Ship 2.0 Frontend
 *
 * Frontend for Ship. This file is what is viewed by the end user. It gathers
 * information from backend.php and makes it all nice and pretty for the user.
 *
 * Written and maintained by Alex Lindeman <aelindeman@gmail.com>
 * License: Creative Commons Attribution-ShareAlike 3.0
 *          (http://creativecommons.org/licenses/by-sa/3.0)
*/

require_once ('./backend.php');
$ship = new ship();

# get all the modules and use small variable names for laziness
$config = $ship->config();

$ma = $ship->machine();
$cp = $ship->cpu();
$ra = $ship->ram();
$ht = $ship->hddtemp();
$df = $ship->diskspace();

$rr = $config['refresh_rate'];
$raw_ut = $ship->machine(true);

# prepare disk temperature table

$hddtemp = '';
foreach ($ship->hddtemp() as $d)
{
	$di = (strtoupper($config['temperature_units']) != 'K') ? '&deg;' : ' ';
	$hddtemp .= <<<DISK
<tr>
	<td class="dev">${d['dev']}</td>
	<td class="model">${d['model']}</td>
	<td class="temp ${d['status']}"><span>${d['temp']}${di}${d['units']}</span></td>
</tr>

DISK;
}

# prepare disk space table

$diskspace = '';
foreach ($ship->diskspace() as $d)
{
	$diskspace .= <<<DISK
<tr>
	<td class="disk" title="${d['dev']}">${d['mount']}</td>
	<td class="type">${d['type']}</td>
	<td class="size">
		${d['used']} of ${d['total']} used
		<div class="meter container" title="${d['pctused']} used">
			<div class="meter fill" style="width:${d['pctused']}">
				<span class="pct">&nbsp;</span>
			</div>
		</div>
	</td>
</tr>

DISK;
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<meta name="description" content="Ship <?=SHIP_VERSION?> - Simple hardware information for Linux" />

		<!-- iPhone meta tags -->
		<meta name="apple-mobile-web-app-capable" content="yes" />
		<meta name="apple-mobile-web-app-status-bar-style" content="black" />
		<link rel="apple-touch-icon" href="./img/iphone-icon.png" />
		<meta name="format-detection" content="telephone=no" />
		<meta name="viewport" content="width=420, initial-scale=0.75, user-scalable=no" />

		<title><?=$ma['hostname'].' - Ship '.SHIP_VERSION; ?></title>
		<link rel="stylesheet" href="./css/<?=$config['stylesheet']?>" type="text/css" />
		
		<!-- initialize some variables for scripts -->
		<script type="text/javascript">
		//<!--
			var refresh_rate = <?=intval($rr) * 1000 ?>;
			var raw_uptime = <?=intval($raw_ut)?>;
			var uptime_show_seconds = <?=$config['uptime_display_sec']?'true':'false'?>;
		//-->
		</script>
		<script src="./ship.js" type="text/javascript"></script>
		
	</head>
	<body>
		<div id="wrapper">
			<div id="ship">
				<div id="header">
					<span>Ship <?=SHIP_VERSION?></span>
				</div>
				<div id="machine">
					<div class="hostname"><h2><?=$ma['hostname']?></h2></div>
					<div class="os"><?=$ma['os']?><br /><?=$ma['kernel']?></div>
					<div class="net"><?=$ma['ip']?><br /><?=$ma['domain']?></div>
					<div class="uptime" id="uptime">Uptime: <?=$ma['uptime']?></div>
				</div>
				<div id="cpu">
					<h2><?=$cp['model']?></h2>
					<div class="load" id="load">Load average: <?=$cp['load']?></div>
				</div>
				<div id="memory">
					<div id="ram">
						<h2 class="ramheader"><?=$ra['ram']['total']?> RAM</h2>
						<span id="ram_used"><?=$ra['ram']['used']?> used (<?=$ra['ram']['pctused']?>%)</span>
						<div class="meter container float">
							<div id="ram_used_meter" class="meter fill" style="width:<?=$ra['ram']['pctused']?>%">
								<span class="pct">&nbsp;</span>
							</div>
						</div>
					</div>
					<div id="swap">
						<h2 class="ramheader"><?=$ra['swap']['total']?> swap</h2>
						<span id="swap_used"><?=$ra['swap']['used']?> used (<?=$ra['swap']['pctused']?>%)</span>
						<div class="meter container float">
							<div id="swap_used_meter" class="meter fill" style="width:<?=$ra['swap']['pctused']?>%">
								<span class="pct">&nbsp;</span>
							</div>
						</div>
					</div>
				</div>
				<div id="temps">
					<table>
						<tr class="header">
							<th>Disk</th>
							<th>Model</th>
							<th></th>
						</tr>
						<?=$hddtemp?>
					</table>
				</div>
				<div id="diskspace">
					<table>
						<tr class="header">
							<th>Mount</th>
							<th>Type</th>
							<th></th>
						</tr>
						<?=$diskspace?>
					</table>
				</div>
			</div>
		</div>
		<div id="footer">
			Ship <?=SHIP_VERSION?> - <a href="http://ael.me/ship/">ael.me/ship</a>
		</div>
		<!-- scripts -->
		<script type="text/javascript">
		//<!--
			animate_uptime();
			update_load();
			update_ram();
		//-->
		</script>
	</body>
</html>
