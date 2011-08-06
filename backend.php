<?php
/**
 * Ship 2.0 Backend
 *
 * Backend for the Ship index file. This file creates all of the modules, then
 * creates their corresponding HTML, which is then read by index.php and
 * displayed to the user along with some pretty CSS.
 *
 * Written and maintained by Alex Lindeman <aelindeman@gmail.com>
 * License: Creative Commons Attribution-ShareAlike 3.0
 *          (http://creativecommons.org/licenses/by-sa/3.0)
*/

define ('SHIP_VERSION', '2.0 alpha 4'); 

class ship
{
	# Ship meta stuff

	/* Error reporter variable. This tells the main Ship page if there were any
	errors rendering the page. Each message should be an additional array, with
	[0] as the message and [1] as the severity level: 0 for info, 1 for warning
	(which will display the message but not halt the page), and 2 or higher for
	critical (which will die() the page). */
	private $errors = array();
	
	/* Ship configuration. Should be handled as read-only, since any changes
	made here will not (and, at the moment, can not) be saved. It is filled in
	later with the config function. This should be used only in this file. */
	private $config = array();
	
	/* Configuration loader function. This will attempt to load the config file
	if it exists, and will load a default "failsafe" one if the file cannot be
	opened. */
	public function config ($cfgfile = './config.ini')
	{
		# default configuration in case the configuration file is missing
		$defaults = array (
			'stylesheet' => 'default.css',
			'refresh_rate' => 5,
			'uptime_display_sec' => false,
			'temperature_units' => 'c',
			'temperature_warn' => 40,
			'temperature_crit' => 50,
			'ignore_disk' => array(),
		);
		
		# check that we can access the file
		if (!file_exists ($cfgfile) or !is_readable($cfgfile))
		{
			$this->errors[] = array ('The default configuration has been loaded
			because the normal Ship configuration file ('.$cfgfile.') is
			missing or cannot be read by your web server. Check that it is in
			the proper location and change its permissions so that "others" can
			read it.', 1);
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
	it. Also checks if there's a procfs to make sure Ship will run properly. */
	public function __construct ()
	{
		$this->config = $this->config();
		
		$supported_oses = array('Linux');
		if (!in_array(PHP_OS, $supported_oses))
			die ('Unfortunately, Ship is not supported on this platform ('.
			PHP_OS.') yet.');
	}
	
	/* The Ship class shoudln't be echo'd, but just in case it is, show the
	version number. */
	public function __tostring ()
	{
		return 'Ship '.SHIP_VERSION;
	}
	
	# Ship meta stuff ends - all modules are below
	
	/* Module helper function for calculating human-readable size of disks and
	such. First argument takes an integer of kB, second is how many decimal
	places you'd like. Returns the human-readable size. */
	private function calc_size ($kb, $p = 1)
	{
		$sizes = array('EB', 'PB', 'TB', 'GB', 'MB', 'kB');
		$total = count($sizes);
		
		while ($total-- && $kb > 1024) $kb /= 1024;
		return sprintf ('%.'.$p.'f ', $kb).$sizes[$total];
	}
	
	/* Machine information module. Displays hostname, domain name, IP address,
	operating system information, and uptime. Single parameter used for
	returning ONLY THE UPTIME as total number of seconds up. */
	public function machine ($uptime_raw = false)
	{
		# simple info stuff
		$machine = array (
			'hostname' => trim (file_get_contents('/proc/sys/kernel/hostname')),
			'ip' => trim ($_SERVER['SERVER_ADDR']),
			'domain' => trim (gethostbyaddr($_SERVER['SERVER_ADDR'])),
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
			$secs = str_pad(intval($seconds % 60), 2, '0', STR_PAD_LEFT);
			$mins = str_pad(intval($seconds / 60 % 60), 2, '0', STR_PAD_LEFT);
			$hours = intval($seconds / 3600 % 24);
			$days = intval($seconds / 86400);

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
		
		# get information
		$proc = explode (':', trim (`cat /proc/cpuinfo | grep -i 'model name' | head -1`));
		$cpu['model'] = trim (str_replace (array ('(R)','(C)','(TM)', 'CPU'), '', $proc[1]));

		$cpu['load'] = trim (`cat /proc/loadavg | awk '{ print $1, $2, $3 }'`);
		
		return $cpu;
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
	temperatures. Port can be changed with first parameter. */
	public function hddtemp ($port = 7634)
	{
		# make sure we can connect first, then read
		if ($sock = @fsockopen ('127.0.0.1', $port, $en, $em, 3))
		{
			$data = '';
			while (($buffer = fgets ($sock, 1024)) !== false)
			{
				$data .= $buffer;
			}
			fclose ($sock);
		}
		else return 'Couldn\'t connect to the hddtemp daemon.';

		$disks = array();

		foreach (explode ('||', $data) as $d)
		{
			$c = explode('|', $d);

			# remove empty crap from the array
			foreach ($c as $k=>$v)
			{
				if (empty ($v)) unset ($c[$k]);
			}
			$c = array_values($c);
			
			$units = strtoupper ($this->config['temperature_units']);
			$temp = $c[2];
		
			# convoluted and awesome temperature convert-o-tron
			switch (strtoupper ($c[3]))
			{
				case 'C':
				{
					if ($units == 'F')
						$temp = round(1.8 * $temp + 32);
					else if ($units == 'K')
						$temp += 273;
					else
						$temp = $temp;
					break;
				}
				case 'F':
				{
					if ($units == 'C')
						$temp = round(($temp - 32) * 5/9);
					else if ($units == 'K')
						$temp = round((($temp - 32) * 5/9) + 273);
					else
						$temp = $temp;
					break;
				}
				case 'K':
				{
					if ($units == 'C')
						$temp -= 273;
					else if ($units == 'F')
						$temp = round((1.8 * $temp + 32) - 273);
					else
						$temp = $temp;
					break;
				}	
			}
			
			# add status to the array to simplify things
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
			$dvals = preg_split('/\s+/', $d, 7);
			
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

/* Accomodates AJAX requests to the page. The "q" parameter in the URL specifies the function. The data
is returned in a JSON array. */
if (!empty ($_GET['q']))
{
	$ship = new ship();
	$config = $ship->config();

	$query = $_GET['q'];
	$data = $ship->$query();
	
	exit (json_encode($data));
}
