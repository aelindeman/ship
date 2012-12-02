# Ship #

Ship is a dead-simple web-based monitoring utility for Linux systems.

Ship’s designed to show a comprehensive yet concise amount of information in an easy to access format. It displays general system information — such as uptime, available memory, disk usage, and hard disk temperature — on a single web page. It is written in PHP, and works on most Linux systems.

## Dependencies ##

Ship requires the following:

  * A computer running some flavor of Linux
  * PHP 5.2 or later
  * Apache, nginx, lighttpd, or some other web server

Ship can also make use of [`hddtemp`](http://github.com/guzu/hddtemp) if it is running as a daemon on port 7634 (its default). `hddtemp` can be installed on most Ubuntu and Debain distros with:

    sudo apt-get install hddtemp


## Getting started ##

  * Download Ship
  * Put it in a place your web server can "see" it
  * Futz with `config.ini`, if you'd like
  * Open any web browser, and go to Ship


## Configuration ##

This is a list of configuration options that can be set in `config.ini`.

  * `stylesheet` - CSS stylesheet to use. Must be inside the "css" folder.
  * `auto_refresh` - Automatically reload data while Ship is open.
  * `refresh_rate` - How fast that data is reloaded.
  * `show_all_errors` - Essentially a debug mode, forces all errors to be displayed.
  * `uptime_display_sec` - Display seconds with uptime.
  * `process_count` - How many processes to list.
  * `use_old_ps_args` - Use an alternate, more compatible argument list for `ps`. Use this if the process list isn't working properly.
  * `disable_hddtemp` - Disables `hddtemp` functionality.
  * `temperature_units` - Units to use for disk temperatures.
  * `temperature_warn` and `temperature_crit` - When to warn about disk temperatures (for now, this simply highlights the temperature). Values should be in the same units as `temperature_units`.
  * `ignore_disk[]` - Ignore a single disk in the disk space list. Multiple `ignore_disk[]`s can be used to hide more than one.

It is not necessary to keep all of the keys in the config file; a default value will be loaded if the key is not present or set properly.

## Issues ##

  * Ship has only been tested on Ubuntu Server and Debian. It will probably work on other distros, but I haven't tested them.
  * Ship assumes a virtual `procfs` partition at `/proc/`. If there isn't one, Ship won't work at all.
  * `short_open_tag` **must** be enabled in `php.ini`. By default it is already enabled.
  * Not all web browsers support native JSON parsing. Ship will check for this and will display a message at the lower left if it is not supported. If the page information (besides the uptime clock) is not updating, this is probably the reason why.

## More information ##

  * Author: Alex Lindeman [[aelindeman](http://github.com/aelindeman/)]

## License ##

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program. If not, see <http://www.gnu.org/licenses/>