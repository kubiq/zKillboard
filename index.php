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

// Load modules + database stuff (and the config)
require( "init.php" );

// initiate the timer!
$timer = new Timer();

// Starting Slim Framework
$app = new \Slim\Slim($config);

// Session
session_cache_limiter(false);
session_start();

// Check if the user has autologin turned on
if(!User::isLoggedIn()) User::autoLogin();

// Theme
$viewtheme = null;
if(User::isLoggedIn())
	$viewtheme = UserConfig::get("viewtheme");
$app->config(array("templates.path" => $baseDir."templates/" . ($viewtheme ? $viewtheme : "bootstrap")));

// Error handling
$app->error(function (\Exception $e) use ($app){
    include ( "view/error.php" );
});

// Determine domain
$serverName = @$_SERVER["SERVER_NAME"];
//if(Db::queryField("SELECT domain FROM zz_domains WHERE domain = :domain", "domain", array(":domain" => $serverName)))
//	Util::isValidSubdomain($serverName);

$restrictedSubDomains = array("www", "email", "mx", "ipv6", "blog", "forum", "cdn", "content", "static", "api", "image", "websocket", "news", "comments");
$subDomain = Util::endsWith($serverName, ".".$baseAddr) ? str_replace(".".$baseAddr, "", $serverName) : null;
if($serverName == $baseAddr)
	$subDomain = $boardDefaultSubdomain;
if (in_array($subDomain, $restrictedSubDomains) || !Util::isValidSubdomain($subDomain))
	header("Location: $fullAddr");

// Load the routes - always keep at the bottom of the require list ;)
include( "routes.php" );

// Load twig stuff
include( "twig.php" );

// Run the thing!
$app->run();
