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

date_default_timezone_set("UTC");

// Database parameters
$dbUser = "%dbuser%";
$dbPassword = "%dbpassword%";
$dbName = "%dbname%";
$dbHost = "%dbhost%";

// External Servers
$apiServer = "%apiserver%";
$imageServer = "%imageserver%";

// Base
$baseFile = __FILE__;
$baseDir = dirname($baseFile) . "/";
$baseUrl = "/";
$baseAddr = "%baseaddr%";
$fullAddr = "http://" . $baseAddr;
chdir($baseDir);

// CorpKB settings
$boardCorpId = "517110379";       // Corp ID for corp-centric zKB
$boardDefaultSubdomain = "mtl";   // Default subdomain if hit with no/unknown subdomain. Remember, you have to create that subdomain yourself.
$boardDisablePost = true;         // Disables the post page
$boardDisableRegister = true;     // Disables the register page
$boardUUID = "%uuid%";

// Logfile
$logfile = "%logfile%";

// Memcache
$memcacheServer = "%memcache%";
$memcachePort = "%memcacheport%";

// Pheal
$phealCacheLocation = "%phealcachelocation%";

// Cookiiieeeee
$cookie_name = "zKB";
$cookie_time = (3600 * 24 * 30); // 30 days
$cookie_secret = "%cookiesecret%";

// API
$apiTimeBetweenAccess = 6; // 6 seconds between each request
$apiWhiteList = array(
	"85.88.24.82", // DOTLAN
	"82.221.99.219", // zKillboard
);

// Stomp
$stompServer = "";
$stompUser = "";
$stompPassword = "";

// CloudFlare
$cfUser = "";
$cfKey = "";

// Email stuff
$emailsmtp = "";
$emailusername = "";
$emailpassword = "";
$sentfromemail = "";
$sentfromdomain = "";

// Twitter
$consumerKey = "";
$consumerSecret = "";
$accessToken = "";
$accessTokenSecret = "";

// Slim config
// to enable log, add "log.writer" => call after "log.enabled" => true, - you might have to load it in index after init has run and do $config["log.writer"] = call;
$config = array(
	"mode" => "production",
	"debug" => false,
	"log.enabled" => false,
	"cookies.secret_key" => $cookie_secret
	);
