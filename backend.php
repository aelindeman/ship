<?php
/**
 * Ship 2.0 Backend
 *
 * Backend for the Ship index file. This file creates all of the modules, then
 * creates their corresponding HTML, which is then read by index.php and
 * displayed to the user along with some pretty CSS.
 *
 * Written and maintained by Alex Lindeman <aelindeman@gmail.com>
 * License: GNU General Public License v3
 *          (http://www.gnu.org/licenses/gpl-3.0.html)
*/

define ('SHIP_VERSION', '2.0 alpha 10');

# Disable caching
header ("Cache-Control: no-cache, must-revalidate");
header ("Expires: Thu, 1 Jan 1970 00:00:00 GMT");

class Ship
{
	# Ship meta stuff

	/* Error reporter variable. This tells the main Ship page if there were any
	errors rendering the page. Each message should be an additional array, with
	[0] as the message and [1] as the severity level: 0 for info, 1 for warning
	(which will display the message but not halt the page), and 2 for critical
	(which will die() the page). */
	protected $errors = array();

	/* Returns the array of errors. */
	public function errors ()
	{
		return $this->errors;
	}

	/* Adds an error to the error array. While this could be done directly, this
	is shorter and prevents duplicate errors. Returns true if the error was
	added to the array (the inverse of if there was a duplicate). */
	public function add_error ($errstr, $severity = 1)
	{
		$error = array ($errstr, intval ($severity));
		if (!in_array ($error, $this->errors))
		{
			$this->errors[] = $error;
			return true;
		}
		return false;
	}

	/* Ship configuration. Should be handled as read-only, since any changes
	made here will not (and, at the moment, can not) be saved. It is filled in
	during __construct() with the config() function. */
	protected $config = array();

	/* Configuration loader function. This will attempt to load the config file
	if it exists, and will load a default "failsafe" one if the file cannot be
	opened. */
	public function config ($cfgfile = './config.ini')
	{
		# default configuration in case the configuration file is missing
		$defaults = array (
			'stylesheet' => 'default.css',
			'auto_refresh' => true,
			'refresh_rate' => 5,
			'show_all_errors' => false,
			'uptime_display_sec' => true,
			'disable_hddtemp' => false,
			'temperature_units' => 'c',
			'temperature_warn' => 40,
			'temperature_crit' => 50,
			'ignore_disk' => array(),
		);

		# check that we can access the file
		if (!file_exists ($cfgfile) or !is_readable ($cfgfile))
		{
			$this->add_error ('The default configuration has been loaded
			because the normal Ship configuration file ('.$cfgfile.') is
			missing or cannot be read by your web server. Check that it is in
			the proper location and change its permissions so that "others" can
			read it.', 0);
			return $defaults;
		}
		else
		{
			$cfg = parse_ini_file ($cfgfile, false);

			# second array takes precedent
			return array_merge ($defaults, $cfg);
		}
	}

	/* When initialized, the config variable should have the configuration in
	it. Also checks if Ship is running on a valid operating system. */
	public function __construct ()
	{
		# make sure all of the <?= echos work in index.php - these can be turned
		# off in the config but are always enabled in PHP 5.4.0 and later
		$shorttag = ini_get ('short_open_tag');
		$version = version_compare (PHP_VERSION, '5.4.0', '<');
		if (!$shorttag and $version)
		{
			die ('Ship requires that the short_open_tag preference is enabled in
			php.ini.');
		}

		# stop if the computer is using an OS that Ship doesn't work on
		$supported_oses = array ('Linux');
		if (!in_array (PHP_OS, $supported_oses))
		{
			die ('Unfortunately, Ship is not supported on this
			platform ('.PHP_OS.').');
		}
		
		$this->config = $this->config();
	}

	/* The Ship class shoudln't be echoed, but just in case it is, show the
	version number. */
	public function __tostring ()
	{
		return 'Ship '.SHIP_VERSION;
	}

	# Ship meta stuff ends - Ship module shared helper functions below

	/* Module helper function for calculating human-readable size of disks and
	such. First argument takes an integer of kB, second is how many decimal
	places you'd like. Returns the human-readable size. */
	private function calc_size ($kb, $p = 1)
	{
		$sizes = array ('EB', 'PB', 'TB', 'GB', 'MB', 'kB');
		$total = count ($sizes);

		while ($total-- && $kb > 1024) $kb /= 1024;
		return round ($kb, $p).' '.$sizes[$total];
	}

	# Helper functions ends - Ship modules below

	/* Machine information module. Displays hostname, domain name, IP address,
	operating system information, and uptime. Single parameter used for
	returning ONLY THE UPTIME as total number of seconds up. */
	public function machine ($uptime_raw = false)
	{
		# simple info stuff
		$machine = array (
			'hostname' => trim (file_get_contents('/proc/sys/kernel/hostname')),
			'ip' => trim ($_SERVER['SERVER_ADDR']),
			'domain' => trim (gethostbyaddr ($_SERVER['SERVER_ADDR'])),
			'os' => trim (`lsb_release -d | cut -f2-`),
			'kernel' => trim (`uname -rm`),
			'uptime' => '0d 00:00',
		);

		# uptime is a little more complicated
		$cmd = explode (' ', file_get_contents ('/proc/uptime'));
		$seconds = round($cmd[0]);

		if ($uptime_raw) return $seconds;
		else
		{
			# divide and mod instead of mktime (this is easier to use)
			$secs = str_pad (intval ($seconds % 60), 2, '0', STR_PAD_LEFT);
			$mins = str_pad (intval ($seconds / 60 % 60), 2, '0', STR_PAD_LEFT);
			$hours = intval ($seconds / 3600 % 24);
			$days = intval ($seconds / 86400);

			# only display days if we have to
			$days = ($days == 0) ? '' : $days.'d ';

			# check config if we are supposed to do seconds
			$config = $this->config;
			if ($config['uptime_display_sec'])
				$machine['uptime'] = "${days}${hours}:${mins}:${secs}";
			else
				$machine['uptime'] = "${days}${hours}:${mins}";
		}

		return $machine;
	}

	/* Displays information about the CPU and load average. */
	public function cpu ()
	{
		$cpu = array (
			'model' => '',
			'load' => '',
		);

		# get CPU information
		$proc = explode (':', trim (`cat /proc/cpuinfo | grep -i 'model name' | head -1`));

		# remove unnecessary words
		$remove = array ('(R)','(C)','(TM)', 'CPU', 'processor');
		$cpu['model'] = trim (str_ireplace ($remove, null, $proc[1]));

		# get load average and discard the process info at the end
		$load = trim (file_get_contents ('/proc/loadavg'));
		$load = explode (' ', $load);
		$cpu['load'] = implode (' ', array ($load[0], $load[1], $load[2]));

		return $cpu;
	}

	/* Provides information about running processes. 'count' parameter is how many processes to list under "top" processes. */
	public function processes ($count = 3)
	{
		$ps = array (
			'active' => 0,
			'total' => 0,
			'top' => array (),
		);

		# get the active and total number of running processes from loadavg
		$proc = trim (file_get_contents ('/proc/loadavg'));
		$num = explode (' ', $proc);

		list ($active, $total) = explode ('/', $num[3]);

		$ps['active'] = $active;
		$ps['total'] = $total;

		# add the top three processes to the list
		$top = trim (`ps axo pmem,pcpu,pid,comm k -pcpu,-pmem`);

		# nicely format the array
		$list = explode ("\n", $top);

		# start at 1 to remove the header row, which is always first
		for ($i = 1; $i < ($count + 1); $i ++)
		{
			if (isset ($list[$i]))
			{
				$split = preg_split('/\s+/', $list[$i], 4, PREG_SPLIT_NO_EMPTY);

				$process = array (
					'pid' => $split[2],
					'process' => $split[3],
					'cpu' => $split[1],
					'ram' => $split[0],
				);

				$ps['top'][] = $process;
			}
		}

		return $ps;
	}

	/* Displays information about RAM and swap usage. */
	public function ram ()
	{
		$ram = array (
			'ram' => array (
				'total' => 0,
				'free' => 0,
				'used' => 0,
				'pctused' => 0,
			),
			'swap' => array (
				'total' => 0,
				'free' => 0,
				'used' => 0,
				'pctused' => 0,
			),
		);

		$proc = `cat /proc/meminfo | grep -E '^(MemTotal|MemFree|Buffers|Cached|SwapTotal|SwapFree)' | sed -e 's/[kKMG]B//g'`;

		$step = array ();
		# make the "Thingy: Value" syntax of the proc file into an array
		foreach (explode ("\n", $proc) as $l)
		{
			@list ($key, $value) = explode (':', $l, 2);
			if (!empty ($key) and !empty ($value)) $step[$key] = intval($value);
		}

		# move the array into something easy to use

		$ram_free = $step['MemFree'] + $step['Buffers'] + $step['Cached'];

		$ram['ram']['total'] = $this->calc_size ($step['MemTotal']);
		$ram['ram']['free'] = $this->calc_size ($ram_free);
		$ram['ram']['used'] = $this->calc_size ($step['MemTotal'] - $ram_free);
		$ram['ram']['pctused'] = round (($step['MemTotal'] - $ram_free) / $step['MemTotal'] * 100);

		$ram['swap']['total'] = $this->calc_size ($step['SwapTotal']);
		$ram['swap']['free'] = $this->calc_size ($step['SwapFree']);
		$ram['swap']['used'] = $this->calc_size ($step['SwapTotal'] - $step['SwapFree']);
		$ram['swap']['pctused'] = round (($step['SwapTotal'] - $step['SwapFree']) / $step['SwapTotal'] * 100);

		return $ram;
	}

	/* Shows what the hddtemp daemon has to say about disks and their
	temperatures. */
	public function hddtemp ()
	{
		# make sure we can connect first, then read
		if ($sock = @fsockopen ('127.0.0.1', 7634, $en, $em, 3))
		{
			$data = '';
			while (($buffer = fgets ($sock, 1024)) !== false)
			{
				$data .= $buffer;
			}
			fclose ($sock);
		}
		else # couldn't connect
		{
			$this->add_error ('Ship could not connect to the hddtemp daemon on
			port 7634. Check that hddtemp is installed and running. Hard disk
			temperature information will not be available.', 1);
			return array();
		}

		$disks = array();
		foreach (explode ('||', $data) as $d)
		{
			$c = explode ('|', $d);

			# remove empty crap from the array
			foreach ($c as $k=>$v)
			{
				if (empty ($v)) unset ($c[$k]);
			}
			$c = array_values($c);

			# if hddtemp had a hiccup, skip the disk and add a note
			if ($c[3] == '*')
			{
				$this->add_error ("The temperature of the disk ${c[0]} wasn't
				displayed because hddtemp told Ship that it was '{$c[2]}'.", 0);
				continue;
			}

			$units = strtoupper ($this->config['temperature_units']);
			$temp = $c[2];

			# convoluted and awesome temperature convert-o-tron
			switch (strtoupper ($c[3]))
			{
				case 'C':
				{
					if ($units == 'F')
						$temp = round (1.8 * $temp + 32);
					else if ($units == 'K')
						$temp += 273;
					else
						$temp = $temp;
					break;
				}
				case 'F':
				{
					if ($units == 'C')
						$temp = round (($temp - 32) * 5/9);
					else if ($units == 'K')
						$temp = round ((($temp - 32) * 5/9) + 273);
					else
						$temp = $temp;
					break;
				}
				case 'K':
				{
					if ($units == 'C')
						$temp -= 273;
					else if ($units == 'F')
						$temp = round ((1.8 * $temp + 32) - 273);
					else
						$temp = $temp;
					break;
				}
			}

			# add status to the array
			if ($temp >= $this->config['temperature_warn'])
			{
				if ($temp >= $this->config['temperature_crit'])
					$status = 'crit';
				else
					$status = 'warn';
			} else
				$status = 'ok';

			# we're still in that foreach loop - add the disk to the array
			$disks[] = array (
				'dev' => $c[0],
				'model' => $c[1],
				'temp' => $temp,
				'units' => $units,
				'status' => $status,
			);
		}

		return $disks;
	}

	/* Shows mountpoints' capacity and space remaining. */
	public function diskspace ()
	{
		$proc = trim (`df -lkPT -x tmpfs | sed -e '1d'`);

		$disks = array();
		foreach (explode ("\n", $proc) as $d)
		{
			$dvals = preg_split ('/\s+/', $d, 7);

			# honor config ignore_disk settings
			if (in_array ($dvals[6], $this->config['ignore_disk']))
				continue;

			# add key names to the array
			$disks[] = array (
				'dev' => $dvals[0],
				'type' => $dvals[1],
				'total' => $this->calc_size ($dvals[2]),
				'used' => $this->calc_size ($dvals[3]),
				'free' => $this->calc_size ($dvals[4]),
				'pctused' => $dvals[5],
				'mount' => $dvals[6],
			);
		}

		return $disks;
	}
}

/* Accomodates AJAX requests to the page. The "q" parameter in the URL specifies
the function. The data is returned in a JSON array. */
if (!empty ($_GET['q']))
{
	$ship = new Ship();
	$config = $ship->config();

	$query = $_GET['q'];

	# provide the entire backend as json, or specify which section
	if ($query == 'json' or $query == 'all')
	{
		$data = array (
			'machine' => $ship->machine(),
			'cpu' => $ship->cpu(),
			'processes' => $ship->processes(),
			'ram' => $ship->ram(),
			'diskspace' => $ship->diskspace(),
		);

		if (!$config['disable_hddtemp']) $data['hddtemp'] = $ship->hddtemp();
	}
	# provide raw uptime for easier javascripting
	else if ($query == 'uptime')
	{
		$data = array (
			'uptime' => $ship->machine(true),
		);
	}
	else
	{
		$data = $ship->$query();
	}

	# still show errors, but ignore them unless they are critical
	if ($errors = $ship->errors() and $errors[0][1] > 1) die ($errors[0][0]);

	exit (json_encode($data));
}
