# check_usolved_linux_realmemory

## Overview

This PHP Nagios plugin  calculates the real free memory according to [http://www.linuxatemyram.com](http://www.linuxatemyram.com).
When you think that your Linux memory is always near to 100% consumed, that is properly not true.
Linux calculates the free memory in a special way. This plugin tries to get the memory that is really still free to use.

The plugin also returns performance data.

## Authors

Ricardo Klement ([www.usolved.net](http://usolved.net))

## Installation

Just copy the file check_usolved_linux_realmemory.php into your Nagios plugin directory.
For example into the path /usr/local/nagios/libexec/

Give check_usolved_linux_realmemory.php the permission for execution for the nagios user.
If you have at least PHP 5 this plugin should run out-of-the-box.

Make sure to have the PHP SNMP module installed and enabled in your php.ini.

&gt; apt-get install php5-snmp (Ubuntu, Debian, ...)
or
&gt; yum install php-snmp (RedHat, CentOS, ...)

## Usage

### Test on command line
If you are in the Nagios plugin directory execute this command:

```
./check_usolved_linux_realmemory.php -H localhost -C public -w 90 -c 95
```

This should output something like this:

```
OK - 18.8% Memory used (1119 MB of 5962 MB), 0% Swap used (0 MB of 7807 MB)
```

Here are all arguments that can be used with this plugin:

```
-H <host address>
Give the host address with the IP address or FQDN

-C <snmp community>
Give the SNMP Community String

-w <warn>
Warning treshold in percent

-c <crit>
Critical treshold in percent

[-V <snmp version>]
Optional: SNMP version 1 or 2c are supported, if argument not given version 1 is used by default

[-P <perfdata>]
Optional: Give 'yes' as argument if you wish performace data output
```

### Install in Nagios

Edit your **commands.cfg** and add the following.

Example for basic check:

```
define command {
    command_name    check_usolved_linux_realmemory
    command_line    $USER1$/check_usolved_linux_realmemory.php -H $HOSTADDRESS$ -C $_HOSTSNMPCOMMUNITY$ -w $ARG1$ -c $ARG2$
}
```

Example for using performance data and specific snmp version:

```
define command {
    command_name    check_usolved_linux_realmemory
    command_line    $USER1$/check_usolved_linux_realmemory.php -H $HOSTADDRESS$ -C $_HOSTSNMPCOMMUNITY$ -V $_HOSTSNMPVERSION$ -w $ARG1$ -c $ARG2$ -P $ARG3$
}
```

Edit your **services.cfg** and add the following.

Example for basic check:

```
define service{
	host_name				Test-Server
	service_description		Memory
	use						generic-service
	check_command			check_usolved_linux_realmemory!90!95
}
```

Example for using performance data and excluding some partitions:

```
define service{
	host_name				Test-Server
	service_description		Memory
	use						generic-service
	check_command			check_usolved_linux_realmemory!90!95!yes
}
```

## What's new

v1.0
Initial release
