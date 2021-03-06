<?php
/**
 * Ship 2.0 Frontend
 *
 * Frontend for Ship. This file is what is viewed by the end user. It gathers
 * information from backend.php and makes it all nice and pretty for the user.
 *
 * Written and maintained by Alex Lindeman <aelindeman@gmail.com>
 * License: GNU General Public License v3
 *          (http://www.gnu.org/licenses/gpl-3.0.html)
*/

require_once ('./backend.php');
$ship = new Ship();

# get all the modules and use small variable names for laziness
$config = $ship->config();

$ma = $ship->machine();
$ut = $ship->uptime();
$cp = $ship->cpu();
$ps = $ship->processes();
$ra = $ship->ram();
$df = $ship->diskspace();

# Make sure the refresh rate is actually an integer
$rr = $config['refresh_rate'];
if (!is_int ($rr) or intval ($rr) < 1)
{
	$rr = 5;
}

# prepare process list
$processes = '';
foreach ($ps['top'] as $p)
{
	$processes .= <<<PS
<tr>
	<td class="pid">${p['pid']}</td>
	<td class="process">${p['process']}</td>
	<td class="user">{$p['user']}</td>
	<td class="cpu">${p['cpu']}</td>
	<td class="ram">${p['ram']}</td>
</tr>
PS;
}

# prepare disk temperature table
if (!$config['disable_hddtemp'])
{
	$ht = $ship->hddtemp();
	$hddtemp = '';
	foreach ($ht as $d)
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
}

# prepare disk space table
$diskspace = '';
foreach ($df as $d)
{
	$diskspace .= <<<DISK
<tr>
	<td class="disk" title="${d['dev']}">${d['mount']}</td>
	<td class="type">${d['type']}</td>
	<td class="size">
		${d['used']} of ${d['total']} used
		<div class="meter container" title="${d['pctused']} used (${d['free']} free)">
			<div class="meter fill" style="width:${d['pctused']}">
				<span class="pct">&nbsp;</span>
			</div>
		</div>
	</td>
</tr>

DISK;
}

/* Returns whether or not an iPhone is viewing the page, which can be used for
adding specific CSS rules like -webkit-text-size-adjust. */
function fix_css ()
{
	return (strpos ($_SERVER['HTTP_USER_AGENT'], 'iPhone') !== false);
}

/* Displays non-fatal backend errors, if any. */
function show_nonfatal_errors ($errors, $config)
{
	if (sizeof ($errors) > 0)
	{
		$disp = '<ul id="errors">';
		foreach ($errors as $e)
		{
			if ($e[1] > 0 or $config['show_all_errors'])
			{
				$severity = strtr ($e[1], array ('0' => 'info', '1' => 'warn', '2' => 'crit'));
				$disp .= '<li class="'.$severity.'">'.$e[0].'</li>';
			}
		}
		return $disp.'</ul>';
	}
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
		<link rel="apple-touch-startup-image" href="./img/default.png" />
		<meta name="format-detection" content="telephone=no" />
		<meta name="viewport" content="width=420, initial-scale=0.75, user-scalable=no" />

		<title><?=$ma['hostname'].' - Ship '.SHIP_VERSION; ?></title>
		<link rel="stylesheet" href="./css/<?=$config['stylesheet']?>" type="text/css" />
		<link rel="icon" type="image/png" href="./img/icon.png" />

		<!-- initialize some variables for scripts -->
		<script type="text/javascript">
		//<!--
			var auto_refresh = <?=$config['auto_refresh']?>;
			var refresh_rate = <?=intval ($rr) * 1000 ?>;
			var raw_uptime = <?=intval ($ut)?>;
			var uptime_show_seconds = <?=$config['uptime_display_sec'] ? 'true' : 'false'?>;
		//-->
		</script>
		<script src="./ship.js" type="text/javascript"></script>

	</head>
	<?=(fix_css()) ? '<body class="iphone">' : '<body>'; ?>
		<?=show_nonfatal_errors($ship->errors(), $config)?>
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
				<div id="processes">
					<table>
						<thead>
							<tr class="header">
								<th colspan="2" id="pstotal"><?=$ps['total']?> processes</th>
								<th class="user">User</th>
								<th class="cpu">% CPU</th>
								<th class="ram">MEM</th>
							</tr>
						</thead>
						<tbody id="pstable">
							<?=$processes?>
						</tbody>
					</table>
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
				<?php if (!empty ($ht)) { ?>
				<div id="temps">
					<table>
						<thead>
							<tr class="header">
								<th>Disk</th>
								<th>Model</th>
								<th></th>
							</tr>
						</thead>
						<tbody id="hdttable">
							<?=$hddtemp?>
						</tbody>
					</table>
				</div>
				<?php } ?>
				<div id="diskspace">
					<table>
						<thead>
							<tr class="header">
								<th>Mount</th>
								<th>Type</th>
								<th></th>
							</tr>
						</thead>
						<tbody id="disktable">
							<?=$diskspace?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
		<div id="footer">
			<div id="artoggle">
				<a href="#" onclick="toggle_auto_refresh()">Auto-refresh <span id="arstatus"><?=$config['auto_refresh'] ? 'on' : 'off'?></span></a>
			</div>
			<div class="info">
				Ship <?=SHIP_VERSION?> - <a href="http://ael.me/ship/">ael.me/ship</a>
			</div>
			<div class="clearer"></div>
		</div>
		<!-- scripts -->
		<script type="text/javascript">
		//<!--
			animate_uptime();
			update_ship();
		//-->
		</script>
	</body>
</html>
