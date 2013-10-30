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

global $boardCorpId;

$corpID = $boardCorpId;

$parameters = array("limit" => 10, "kills" => true, "pastSeconds" => (14*86400), "cacheTime" => 3600);
$corp_parameters = $parameters;
$corp_parameters["corporationID"] = $corpID;
$alltime = false;

$topKillers[] = array("type" => "character", "data" => Stats::getTopPilots($corp_parameters, $alltime));

$topKillers[] = array("type" => "system", "data" => Stats::getTopSystems($parameters, $alltime));
$topKillers[] = array("type" => "region", "data" => Stats::getTopRegions($parameters, $alltime));

$topKillers[] = array("type" => "ship", "data" => Stats::getTopShips($parameters, $alltime));
$topKillers[] = array("type" => "group", "data" => Stats::getTopGroups($parameters, $alltime));
$topKillers[] = array("type" => "weapon", "data" => Stats::getTopWeapons($parameters, $alltime));

unset($parameters["kills"]);
unset($corp_parameters["kills"]);
$parameters["losses"] = true;
$corp_parameters["losses"] = true;
$topLosers[] = array("type" => "character", "ranked" => "Losses", "data" => Stats::getTopPilots($corp_parameters, $alltime));

$topLosers[] = array("type" => "ship", "ranked" => "Losses", "data" => Stats::getTopShips($corp_parameters, $alltime));
$topLosers[] = array("type" => "group", "ranked" => "Losses", "data" => Stats::getTopGroups($corp_parameters, $alltime));

$app->render("lasthour.html", array("topKillers" => $topKillers, "topLosers" => $topLosers, "time" => date("H:i")));
