#!/usr/bin/php
<?php
/*
Calculates the real free memory according to http://www.linuxatemyram.com/.
When you think that your Linux memory is always near to 100% consumed, that is properly not true.
Linux calculates the free memory in a special way. This plugin tries to get the memory that is really still free to use.

You don't need special libraries. If you have PHP 5 or higher installed it should be working.

Copyright (c) 2014 www.usolved.net 
Published under https://github.com/usolved/check_usolved_linux_realmemory


This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

//---------------------------------------------------------------------------
//------------------------------- Functions ---------------------------------

function show_help($help_for)
{
	if(empty($help_for))
		$help_for = "";
	
	if($help_for == "ERROR_ARGUMENT_H")
	{
		echo "Unknown - argument -H (host address) is required but missing\n";
		exit(3);
	}
	else if($help_for == "ERROR_ARGUMENT_C")
	{
		echo "Unknown - argument -C (snmp community string) is required but missing\n";
		exit(3);
	}
	else if($help_for == "ERROR_ARGUMENT_c")
	{
		echo "Unknown - argument -c (scritical treshold) is required but missing\n";
		exit(3);
	}
	else if($help_for == "ERROR_ARGUMENT_w")
	{
		echo "Unknown - argument -w (warning treshold) is required but missing\n";
		exit(3);
	}
	else if($help_for == "SNMP")
	{
		echo "Unknown - Could't read SNMP information. Properly the host isn't configured correctly for SNMP or wrong SNMP version was given.\n";
		exit(3);
	}
	else if($help_for == "ERROR_ARGUMENT_NUMERIC")
	{	
		echo "Unknown - Warning and Critical have to be numeric.\n";
		exit(3);
	}
	else if($help_for == "ERROR_OS")
	{	
		echo "Unknown - Could't read SNMP information. Properly this is a Windows system but this script only works with Linux.\n";
		exit(3);
	}
	else
	{
		echo "Usage:";
		echo "
	./".basename(__FILE__)." -H <host address> -C <snmp community> -w <warn> -c <crit> [-V <snmp version>] [-P <perfdata>]\n\n";
		
		echo "Options:";
		echo "
	./".basename(__FILE__)."\n
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
	\n";

		echo "Example:";
		echo "
	./".basename(__FILE__)." -H localhost -C public -V 2 -w 90 -c 95 -P yes\n\n";
		
		exit(3);
	}
}



function snmp_get($snmp_host, $snmp_community, $snmp_oid, $snmp_version)
{
	if($snmp_version == "1")
	{
		if($snmp_return = @snmpget($snmp_host, $snmp_community, $snmp_oid))
			return $snmp_return;
		else
			show_help("SNMP");
	}
	else if($snmp_version == "2c" || $snmp_version == "2")
	{
		if($snmp_return = @snmp2_get($snmp_host, $snmp_community, $snmp_oid))
			return $snmp_return;
		else
			show_help("SNMP");
	}
	else
		show_help("SNMP");
}


//---------------------------------------------------------------------------
//---------------- Get arguments and set variables --------------------------

//get arguments from cli
$arguments 						= array();
$arguments 						= getopt("H:C:V:w:c:P:");

if(is_array($arguments) && count($arguments) < 4)
	show_help("");
	
if((isset($arguments['w']) && !is_numeric($arguments['w'])) || (isset($arguments['c']) && !is_numeric($arguments['c'])))
	show_help("ERROR_ARGUMENT_NUMERIC");


//get and check host address argument
if(isset($arguments['H']))
	$snmp_host				= $arguments['H'];
else
	show_help("ERROR_ARGUMENT_H");

//get and check snmp community string argument
if(isset($arguments['C']))
	$snmp_community			= $arguments['C'];
else
	show_help("ERROR_ARGUMENT_C");

//get and check warning argument
if(isset($arguments['w']))
	$snmp_warning			= $arguments['w'];
else
	show_help("ERROR_ARGUMENT_w");

//get and check critical argument
if(isset($arguments['c']))
	$snmp_critical			= $arguments['c'];	
else
	show_help("ERROR_ARGUMENT_c");
	
//get and check snmp version argument
if(isset($arguments['V']))
	$snmp_version			= $arguments['V'];
else
	$snmp_version			= 1;

//get and check perfdata argument
if(isset($arguments['P']))
	$perfdata			= $arguments['P'];
else
	$perfdata			= "";
	
$output_perfdata 				= "";
$output_string	 				= "";
$output_string_extended	 		= "";


$exit_code						= 0;
$storage_info[][]				= array();

//---------------------------------------------------------------------------
//------------------------------ Set OID paths -------------------------------

$snmp_oid_totalMemoryInMachine	= ".1.3.6.1.4.1.2021.4.5.0";
$snmp_oid_totalMemoryFree		= ".1.3.6.1.4.1.2021.4.6.0";
$snmp_oid_totalCachedMemory		= ".1.3.6.1.4.1.2021.4.15.0";
$snmp_oid_totalMemoryBuffered	= ".1.3.6.1.4.1.2021.4.14.0";

$snmp_oid_totalSwapSize			= ".1.3.6.1.4.1.2021.4.3.0";
$snmp_oid_availableSwapSpace	= ".1.3.6.1.4.1.2021.4.4.0";



//---------------------------------------------------------------------------
//--------------- Walk through SNMP informations from server ----------------

preg_match('!\d+!', snmp_get($snmp_host, $snmp_community, $snmp_oid_totalMemoryInMachine, $snmp_version), $matchesTotalMemoryInMachine);
preg_match('!\d+!', snmp_get($snmp_host, $snmp_community, $snmp_oid_totalMemoryFree, $snmp_version), $matchesTotalMemoryFree);
preg_match('!\d+!', snmp_get($snmp_host, $snmp_community, $snmp_oid_totalCachedMemory, $snmp_version), $matchesTotalCachedMemory);
preg_match('!\d+!', snmp_get($snmp_host, $snmp_community, $snmp_oid_totalMemoryBuffered, $snmp_version), $matchesTotalMemoryBuffered);

preg_match('!\d+!', snmp_get($snmp_host, $snmp_community, $snmp_oid_totalSwapSize, $snmp_version), $matchesTotalSwapSize);
preg_match('!\d+!', snmp_get($snmp_host, $snmp_community, $snmp_oid_availableSwapSpace, $snmp_version), $matchesAvailableSwapSpace);

if(!isset($matchesTotalMemoryInMachine[0]))
	show_help("ERROR_OS");

$totalMemoryInMachine		= $matchesTotalMemoryInMachine[0];
$totalMemoryFree			= $matchesTotalMemoryFree[0];
$totalCachedMemory			= $matchesTotalCachedMemory[0];
$totalMemoryBuffered 		= $matchesTotalMemoryBuffered[0];

$totalSwapSize 				= $matchesTotalSwapSize[0];
$availableSwapSpace 		= $matchesAvailableSwapSpace[0];

$totalMemoryFreeCalculated 	= $totalMemoryFree + $totalCachedMemory + $totalMemoryBuffered;


//---------------------------------------------------------------------------
//--------------- Logic ----------------

$totalMemoryFreeCalculatedPercentage	= 100 - round( (100 / $totalMemoryInMachine) * $totalMemoryFreeCalculated, 1);

$totalMemoryFreeCalculatedMB			= floor(round(($totalMemoryInMachine - $totalMemoryFreeCalculated) / 1024, 2));
$totalMemoryInMachineMB					= floor(round($totalMemoryInMachine / 1024, 2));

//--------------

$totalSwapSizeMB						= floor(round($totalSwapSize / 1024, 2));
$availableSwapSpaceMB					= floor(round($availableSwapSpace / 1024, 2));

$totalSwapUsedCalculatedPercentage		= floor(100 - round( (100 / $totalSwapSize) * $availableSwapSpace, 1));
$totalSwapUsedMB						= $totalSwapSizeMB - $availableSwapSpaceMB;
//echo "{$totalSwapSizeMB} | {$availableSwapSpaceMB} = {$totalSwapUsedCalculatedPercentage}%";

if($totalMemoryFreeCalculatedPercentage > $snmp_critical || $totalSwapUsedCalculatedPercentage>0)
{
	$exit_code	= 2;
}
else if($totalMemoryFreeCalculatedPercentage > $snmp_warning)
{
	$exit_code	= 1;
}

$output_string .= "{$totalMemoryFreeCalculatedPercentage}% Memory used ({$totalMemoryFreeCalculatedMB} MB of {$totalMemoryInMachineMB} MB), {$totalSwapUsedCalculatedPercentage}% Swap used ({$totalSwapUsedMB} MB of {$totalSwapSizeMB} MB)";


if($perfdata == "yes")
{
	$snmp_warning_mb	= round( (($totalMemoryInMachine / 100) * $snmp_warning) / 1024, 0);
	$snmp_critical_mb	= round( (($totalMemoryInMachine / 100) * $snmp_critical) / 1024, 0);
	
	$output_perfdata	= "Memory={$totalMemoryFreeCalculatedMB}MB Size={$totalMemoryInMachineMB}MB;{$snmp_warning_mb};{$snmp_critical_mb};0;{$totalMemoryInMachineMB}";
}	

//---------------------------------------------------------------------------
//--------------- Output to stdout ----------------



if($exit_code == 0)
	echo "OK";
else if($exit_code == 1)
	echo "Warning";
else if($exit_code == 2)
	echo "Critical";


//Output host information
echo " - ".$output_string;


if($perfdata == "yes")
	echo "|".$output_perfdata;

echo "\n";
//exit with specific code
exit($exit_code);


?>