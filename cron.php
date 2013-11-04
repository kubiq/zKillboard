#!/usr/bin/env php
<?php

if(php_sapi_name() != "cli")
    die("This is a cli script!");

if(!extension_loaded('pcntl'))
    die("This script needs the pcntl extension!");

$base = __DIR__;
require_once( "config.php" );

if($debug)
{
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
}

// vendor autoload
require( "vendor/autoload.php" );

// zkb class autoloader
spl_autoload_register("zkbautoload");

function zkbautoload($class_name)
{
    $baseDir = dirname(__FILE__);
    $fileName = "$baseDir/classes/$class_name.php";
    if (file_exists($fileName))
    {
        require_once $fileName;
        return;
    }
}

interface cliCommand {
    public function getDescription();
    public function getAvailMethods();
    public function execute($parameters);
}

$curTime = time();
$cronInfo = array();

$files = scandir("$base/cli");
foreach($files as $file)
{
    if(!preg_match("/^cli_(.+)\\.php$/", $file, $match))
        continue;

    $command = $match[1];
    $className = "cli_$command";
    require_once "$base/cli/$file";

    if(!is_subclass_of($className, "cliCommand"))
        continue;

    if(!method_exists($className, "getCronInfo"))
        continue;

    $class = new $className();
    $cronInfo[$command] = $class->getCronInfo();
    unset($class);
}

if(file_exists("$base/cron.overrides"))
{
    $overrides = file_get_contents("$base/cron.overrides");
    $overrides = json_decode($overrides, true);

    foreach($overrides as $command => $info)
        $cronInfo[$command] = $info;
}

foreach($cronInfo as $command => $info)
{
    foreach($info as $interval => $arguments)
    {
	    Log::log("-> Running $command with interval $interval");
        runCron($command, $interval, $arguments);
    }
}

function runCron($command, $interval, $args)
{
    global $base, $curTime;

    if(is_array($args))
        array_unshift($args, $command);
    else if($args != "")
        $args = explode(" ", "$command $args");
    else
        $args = array($command);

    $cronName = implode(".", $args);
    $locker = "lastCronRun.$cronName";
    $lastRun = (int)Storage::retrieve($locker, 0);

    $dateFormat = "D M j G:i:s T Y";
    if($curTime - $lastRun < $interval)
    {
		// No need to say we're not running...
        return;
    }

    Log::log("Cron $cronName running at ".date($dateFormat, $curTime));

    Storage::store($locker, $curTime);

    $pid = pcntl_fork();
    if($pid < 0)
    {
        Storage::store($locker, $lastRun);
        return;
    }

    if($pid != 0)
        return;

    putenv("SILENT_CLI=1");
    pcntl_exec("$base/cliLock.sh", $args);
    Storage::store($locker, $lastRun);
    die("Executing $command failed!");
}
