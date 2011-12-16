Ship 2.0 Readme
===============

1. Prerequisites
----------------

Ship is designed to be run on GNU/Linux distributions with a `procfs` partition.
This is where Ship gets all of its data, so if it doesn't exist, it won't work.
Ubuntu and other Debian-derived distributions are perfect (what I use while
writing and testing code for Ship), and others will probably work too.

You'll also need PHP 5.2 or later, and to make things simple, a web server along
with it. Chances are you need a web server for something else anyway, so
hopefully Ship won't consume too much of your space.

Ship also needs a few programs before it can run:

- `cat`
- `df`
- `grep`
- `lsb_release`
- `uname`
- `who`

All of these are probably already installed on your computer, since they are
very standard Linux utilities - but if something doesn't work, you can
double-check this list.

Ship can also make use of `hddtemp`, but it's not 100% necessary (though highly
recommended).

2. Setup
--------

First, you must download Ship, which you've probably already done, so you can
skip this part.

There are 2 ways you can download Ship: you can check out the trunk from
Subversion using:

    svn co svn://ael.me/ship/trunk ship2

or, you can download a tarball from the [Ship website](http://ael.me/ship/). If
you know how to use Subversion, you probably don't need to follow the rest of
this tutorial, and can just check out directly to the web server folder.

Once you've downloaded the tarball, you can extract the Ship folder anywhere
convenient, like your home folder or (for the sake of this readme) the desktop.
To do this, you can use any utility that can read `.tar.gz` files, like, well,
`tar`:

    tar xfvz ship-current.tar.gz ~/Desktop/ship2

or an archive manager with a GUI like `file-roller` and simply drag the "ship"
folder out.

Next, we'll have to move the folder to a place where your web server can see it,
like `/var/www/` or `~/htdocs/`:

	mv -iv ~/Desktop/ship2 /var/www/

Now open your web browser of choice, and go to
[http://localhost/ship2](http://localhost/ship2). You should see Ship!

If you want to look at the statistics of the computer from a *different*
computer, use the computer's IP address instead of "localhost". This is very
(very!) handy for monitoring a server or another computer you're not sitting in
front of.

3. FAQ & Troubleshooting
------------------------

### Where'd the Settings page go? How do I change the settings now? ###

Unfortunately for you, I removed the friendly GUI editor found in previous
versions of Ship because there was a lot of redundant code and I felt like it
added a lot of unnecessary complexity. Now you have to manually edit
`config.ini` with a text editor like `nano` in order to change the
configuration. Defaults are stored within `backend.php` now, so you don't even
need the configuration file if you don't want it there and like the way Ship is
by default. (Ship will load the defaults automatically without complaining if it
can't find `config.ini`.)

### Nothing is updating itself. ###

Make sure Javascript is enabled, and your browser supports it. Nearly all of
them do. (This is also a good way to *keep* Ship from updating the data it
displays if you're on a slow connection.)

### Only the uptime is updating itself. ###

If **only** the uptime is changing, that may mean your browser does not have a
native JSON parser. To fix this, you need a newer browser which supports newer
standards (such as [Firefox](http://mozilla.com/firefox) or
[Chrome](http://google.com/chrome)). You can also check the `refresh_rate`
setting, do "Edit > Select All", and see what unhighlights itself after waiting
however long `refresh_rate` is set to.

### Why isn't hddtemp working? ###

This is more likely a problem with hddtemp than a problem with Ship. Make sure
hddtemp is running on port 7634, and then refresh Ship. You should also try
restarting hddtemp if it still doesn't work.

### I don't want to install hddtemp. Can I disable the warning? ###

Absolutely. Open up `config.ini` and change the `disable_hddtemp` key to "true".

### Ship is telling me to enable `short_open_tag`, but I don't know how. ###

I use the short (and quite convenient) PHP open tag to print data to the screen
in Ship. However, this means you need the `short_open_tag` setting enabled in
the PHP configuration file. It is *usually* enabled, but if Ship complains about
it, you can edit your `php.ini` file and change the `short_open_tag` setting to
"`On`". The location of `php.ini` depends on your operating system and web 
server configuration - for me it's in `/etc/php5/cgi/php.ini` - but you can try
to look for it by running this command in a terminal:

    whereis php.ini
    
You can also upgrade to PHP 5.4 (where short echo tags are always enabled), but
I would advise against that just for trying to run Ship.

### Ship says my operating system is unsupported. ###

Remember, Ship **does not work** on Mac OS X or Windows.

If you're not running one of those, Ship checks to see what operating system
you're running with the `PHP_OS` constant. The only way to bypass this is to add
your operating system to the portion of the source code where Ship performs this
check (`__construct()` in `backend.php`), or to remove that check altogether.
You won't break anything by doing this, but Ship will try to run and might
either crash spectacularly with many many errors, or will run correctly.

### Ship is ugly. I want to make it look better. ###

Sure! You just need to know CSS. Duplicate `css/default.css` as a starting
point, and tell the configuration (`config.ini`) to use your new CSS file. There
is also an alternate stylesheet (included) that you can use if you're lazy.
