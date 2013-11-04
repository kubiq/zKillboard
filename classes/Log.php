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

require_once __DIR__ . '/../config.php';

class Log
{

	public function __construct()
	{
		trigger_error('The class "log" may only be invoked statically.', E_USER_ERROR);
	}

	public static function log($text)
	{
		if (!file_exists(LOG_FILE)) {
			trigger_error('Log file does not exist "' . LOG_FILE . '"');
			return;
		}

		if (!is_writable(dirname(LOG_FILE))) {
			trigger_error('Log is not writable');
			return;
		}
		error_log(date("Ymd H:i:s") . " $text \n", 3, LOG_FILE);
	}

	/*
	   Mapped by Eggdrop to log into #esc
	 */
	public static function irc($text, $from = "zkillboard - ")
	{
		$text = Log::addIRCColors($text);
		if (!file_exists(LOG_FILE_IRC) && !is_writable(dirname(LOG_FILE_IRC))) trigger_error('Cant create logFile: ' . LOG_FILE_IRC); // Can't create the file
		error_log("\n" . date("Ymd H:i:s") . " ${from}$text\n", 3, LOG_FILE_IRC);
	}


	public static function ircAdmin($text, $from = "zkillboard - ")
	{
		$text = Log::addIRCColors($text);
		if (!file_exists(LOG_FILE_IRC_ADMIN) && !is_writable(dirname(LOG_FILE_IRC_ADMIN))) trigger_error('Cant create logFile: ' . LOG_FILE_IRC_ADMIN); // Can't create the file
		error_log("\n${from}$text\n", 3, LOG_FILE_IRC_ADMIN);
	}

	public static function error($text)
	{
		if (!file_exists(LOG_FILE_ERROR) && !is_writable(dirname(LOG_FILE_ERROR))) trigger_error('Cant create logFile: ' . LOG_FILE_ERROR);
		error_log(date("Ymd H:i:s") . " $text \n", 3, LOG_FILE_ERROR);
	}

	public static $colors = array(
		"|r|" => "\x0305", // red
		"|g|" => "\x0303", // green
		"|w|" => "\x0300", // white
		"|b|" => "\x0302", // blue
		"|blk|" => "\x0301", // black
		"|c|" => "\x0310", // cyan
		"|y|" => "\x0308", // yellow
		"|n|" => "\x03", // reset
	);

	public static function addIRCColors($msg)
	{
		foreach (Log::$colors as $color => $value) {
			$msg = str_replace($color, $value, $msg);
		}
		return $msg;
	}

	public static function stripIRCColors($msg)
	{
		foreach (Log::$colors as $color => $value) {
			$msg = str_replace($color, "", $msg);
		}
		return $msg;
	}

	public static function firePHP($msg)
	{
		ChromePhp::log($msg);
	}
}

/*
Bold: \002text\002
Underline: \037text\037

Start and end with \003

White: \0030text\003
\0030: white
\0031: black
\0032: blue
\0033: green
\0034: light red
\0035: brown
\0036: purple
\0037: orange
\0038: yellow
\0039: light green
\0310: cyan
\0311: light cyan
\0312: light blue
\0313: pink
\0314: gr
\0315: light grey
 */
