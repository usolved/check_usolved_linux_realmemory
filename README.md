# check_usolved_linux_realmemory

## Overview

This PHP Nagios plugin  calculates the real free memory according to http://www.linuxatemyram.com/.
When you think that your Linux memory is always near to 100% consumed, that is properly not true.
Linux calculates the free memory in a special way. This plugin tries to get the memory that is really still free to use.

The plugin also returns performance data.

## Authors

Ricardo Klement (www.usolved.net)

## Installation

Just copy the file check_usolved_linux_realmemory.php into your Nagios plugin directory.
For example into the path /usr/local/nagios/libexec/

Give check_usolved_linux_realmemory.php the permission for execution for the nagios user.
If you have at least PHP 5 this plugin should run out-of-the-box.

## Usage

### Test on command line
If you are in the Nagios plugin directory execute this command:

<pre><code>
./check_usolved_linux_realmemory.php -H localhost -C public -w 90 -c 95
</code></pre>

This should output something like this:

<pre><code>
OK - 18.8% Memory used (1119 MB of 5962 MB), 0% Swap used (0 MB of 7807 MB)
</code></pre>

Here are all arguments that can be used with this plugin:

<pre><code>
-H &lt;host address&gt;
Give the host address with the IP address or FQDN

-C &lt;snmp community&gt;
Give the SNMP Community String

-w &lt;warn&gt;
Warning treshold in percent

-c &lt;crit&gt;
Critical treshold in percent

[-V &lt;snmp version&gt;]
Optional: SNMP version 1 or 2c are supported, if argument not given version 1 is used by default

[-P &lt;perfdata&gt;]
Optional: Give 'yes' as argument if you wish performace data output
</code></pre>

### Install in Nagios

Edit your **commands.cfg** and add the following.

Example for basic check:

<pre><code>
define command {
    command_name    check_usolved_linux_realmemory
    command_line    $USER1$/check_usolved_linux_realmemory.php -H $HOSTADDRESS$ -C $_HOSTSNMPCOMMUNITY$ -w $ARG1$ -c $ARG2$
}
</code></pre>

Example for using performance data and specific snmp version:

<pre><code>
define command {
    command_name    check_usolved_linux_realmemory
    command_line    $USER1$/check_usolved_linux_realmemory.php -H $HOSTADDRESS$ -C $_HOSTSNMPCOMMUNITY$ -V $_HOSTSNMPVERSION$ -w $ARG1$ -c $ARG2$ -P $ARG3$
}
</code></pre>

Edit your **services.cfg** and add the following.

Example for basic check:

<pre><code>
define service{
	host_name				Test-Server
	service_description		Memory
	use						generic-service
	check_command			check_usolved_linux_realmemory!90!95
}
</code></pre>

Example for using performance data and excluding some partitions:

<pre><code>
define service{
	host_name				Test-Server
	service_description		Memory
	use						generic-service
	check_command			check_usolved_linux_realmemory!90!95!yes
}
</code></pre>

