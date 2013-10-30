<?php
/* zKillboard
 * Copyright (C) 2012-2013 EVE-KILL Team and EVSCO.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

$base = dirname(__FILE__);

function exception_error_handler($errno, $errstr, $errfile, $errline ) {
    throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
}

// Force all warnings into errors
set_error_handler("exception_error_handler");

if (file_exists("$base/../config.php")) {
	out("|r|Your config.php is already setup, if you want to reinstall please delete it.", true);
}

out("We will prompt you with a few questions.  If at any time you are unsure and want to
back out of the installation hit |g|CTRL+C.|n|

|g|Questions will always have a default answer specified in []'s.  Example:
What is 1+1? [2]|n|

Hitting enter will let you select the default answer.

Some database questions:");

$settings = array();

// Database
$settings["dbuser"] = prompt("Database username?", "zkillboard");
$settings["dbpassword"] = prompt("Database password?", "zkillboard");
$settings["dbname"] = prompt("Database name?", "zkillboard");
$settings["dbhost"] = prompt("Database server?", "localhost");

// Memcache
$settings["memcache"] = "";
$settings["memcacheport"] = "";

$memc = prompt("|g|Do you have memcached installed?|n|", "yes");
if($memc == "yes")
{
	$settings["memcache"] = prompt("Memcache server?", "localhost");
	$settings["memcacheport"] = prompt("Memcache port?", "11211");
}

// Redis
$settings["redis"] = "";
$settings["redisport"] = "";

$redis = prompt("|g|Do you have Redis and Phpredis installed?|n|", "yes");
if($redis == "yes")
{
	$settings["redis"] = prompt("Redis server?", "localhost");
	$settings["redisport"] = prompt("Redis port?", "6379");
}

// Pheal cache
out("|g|It is highly recommended you find a good location other than the default for these files.|n|");
$settings["phealcachelocation"] = prompt("Where do you want to store Pheal's cache files?", "/tmp/");

// Server addr
out("What is the address of your server? |g|e.g. zkillboard.com|n|");
$settings["baseaddr"] = prompt("Domain name?", "zkillboard.com");

// Log
$settings["logfile"] = prompt("Log file location?", "/var/log/zkb.log");

// Image server
out("Image and API server defaults to the zKillboard proxies, you can however use CCPs servers if you want: \n|g|https://api.eveonline.com and https://image.eveonline.com|n|");
$settings["apiserver"] = prompt("API Server?", "https://api.zkillboard.com/");
$settings["imageserver"] = prompt("Image Server?", "https://image.zkillboard.com/");

// Secret key for cookies
out("A secret key is needed for your cookies to be encrypted.");
$cookiesecret = prompt("Secret key for cookies?", uniqid(time()));
$settings["cookiesecret"] = sha1($cookiesecret);

$settings["uuid"] = uniqid("zKB", true);

// Get default config
$configFile = file_get_contents("$base/config.new.php");

// Create the new config
foreach($settings as $key=>$value) {
	$configFile = str_replace("%$key%", $value, $configFile);
}

// Save the file and then attempt to load and initialize from that file
$configLocation = "$base/../config.php";
if (file_put_contents($configLocation, $configFile) === false) out("|r|Unable to write configuration file at $configLocation", true);

try {
	out("|g|Config file written, now attempting to initialize settings");
	require_once( "$base/../config.php" );

	if($debug)
	{
		ini_set('display_errors', 1);
		error_reporting(E_ALL);
	}

	out("Installing composer:\n");
	chdir("$base/..");

	passthru("php -r \"eval('?>'.file_get_contents('https://getcomposer.org/installer'));\"");

	chdir("$base/..");
	out("\nInstalling vendor files");
	passthru("php composer.phar update");

	out("\n|g|composer install complete!");

	require( "$base/../vendor/autoload.php" );

	chdir("$base/..");

	// zkb class autoloader
	spl_autoload_register("zkbautoload");

	$one = Db::queryField("select 1 one from dual", "one", array(), 1);
	if ($one != "1")
		throw new Exception("We were able to connect but the database did not return the expected '1' for: select 1 one from dual;");
	out("|g|Success! Database initialized.");
} catch (Exception $ex) {
	out("|r|Error! Removing configuration file.");
	unlink($configLocation);
	throw $ex;
}

// Move bash_complete_zkillboard to the bash_complete folder
try {
	file_put_contents("/etc/bash_completion.d/zkillboard", file_get_contents("$base/bash_complete_zkillboard"));
	exec("chmod +x $base/../cli.php");
} catch (Exception $ex) {
	out("|r|Error! Couldn't move the bash_complete file into /etc/bash_completion.d/, please do this after the installer is done.");
}

// Now install the db structure
try {
	$sqlFiles = scandir("$base/sql");
	foreach($sqlFiles as $file) {
		if (Util::endsWith($file, ".sql")) {
			$table = str_replace(".sql", "", $file);
			out("Adding table |g|$table|n| ... ", false, false);
			$sqlFile = "$base/sql/$file";
			loadFile($sqlFile);
			// Ensure the table starts with base parameters and doesn't inherit anything from zkillboard.com
			if (!Util::startsWith($table, "ccp_")) Db::execute("truncate table $table");
			out("|g|done");
		}
	}
} catch (Exception $ex) {
	out("|r|Error! Removing configuration file.");
	unlink($configLocation);
	throw $ex;
}

try {
	out("|g|Installing default admin user...");
	// Install the default admin user
	Db::execute("INSERT INTO zz_users (username, moderator, admin, password) VALUES ('admin', 1, 1, '$2y$10\$maxuZ/qozcjIgr7ZSnrWJemywbThbPiJDYIuOk9eLxF0pGE5SkNNu')");
	out("\n\n|r|*** NOTICE ***\nDefault admin user has password 'admin'\nIt is strongly recommended you change this password!\n*** NOTICE ***\n");
} catch (Exception $ex) {
	out("|r|Error! Unable to add default admin user...");
	unlink($configLocation);
	throw $ex;
}

// Launch the EDK transfer crap
if(strtolower(prompt("|g|Do you want to migrate kills from an existing EDK installation? |r|(experimental)|n|", "y/N")) == "y")
{
	$edkPath = prompt("Root path of your edk installation?");
	if($edkPath)
	{
		$cmd = "";

		if(defined("PHP_BINARY"))
			$cmd = PHP_BINARY . " ";
		else
			$cmd = "php ";

		$cmd .= escapeshellarg('$base/edkConversion.php') . " ";
		$cmd .= escapeshellarg($edkPath) . " ";
		$cmd .= escapeshellarg($settings["dbhost"]) . " ";
		$cmd .= escapeshellarg($settings["dbuser"]) . " ";
		$cmd .= escapeshellarg($settings["dbpassword"]) . " ";
		$cmd .= escapeshellarg($settings["dbname"]);

		passthru($cmd);
	}
}

out("|g|Enjoy your new installation of zKillboard, you may browse to it here: http://" . $settings["baseaddr"] . "\n");
exit;

function zkbautoload($class_name)
{
	global $base;
	$fileName = "$base/../classes/$class_name.php";
	if (file_exists($fileName))
	{
		require_once $fileName;
		return;
	}
}

function loadFile($file) {
	if (Util::endsWith($file, ".gz")) $handle = gzopen($file, "r");
	else $handle = fopen($file, "r");

	$query = "";
	while ($buffer = fgets($handle)) {
		$query .= $buffer;
		if (strpos($query, ";") !== false) {
			$query = str_replace(";", "", $query);
			Db::execute($query);
			$query = "";
		}
	}
	fclose($handle);
}

function out($message, $die = false, $newline = true)
{
	$colors = array(
		"|w|" => "1;37", //White
		"|b|" => "0;34", //Blue
		"|g|" => "0;32", //Green
		"|r|" => "0;31", //Red
		"|n|" => "0" //Neutral
		);

	$message = "$message|n|";
	foreach($colors as $color => $value)
		$message = str_replace($color, "\033[".$value."m", $message);

	if($newline)
		echo $message.PHP_EOL;
	else
		echo $message;
	if($die) die();
}

function prompt($prompt, $default = "") {
	out("$prompt [$default] ", false, false);
	$answer = trim(fgets(STDIN));
	echo "\n";
	if (strlen($answer) == 0) return $default;
	return $answer;
}

// Password prompter kindly borrowed from http://stackoverflow.com/questions/187736/command-line-password-prompt-in-php
function prompt_silent($prompt = "Enter Password:") {
	$command = "/usr/bin/env bash -c 'echo OK'";
	if (rtrim(shell_exec($command)) !== 'OK') {
		trigger_error("Can't invoke bash");
		return;
	}
	$command = "/usr/bin/env bash -c 'read -s -p \""
		. addslashes($prompt)
		. "\" mypassword && echo \$mypassword'";
	$password = rtrim(shell_exec($command));
	echo "\n";
	return $password;
}

