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

$limit = 50;
$killPages = 10;

switch($type)
{
	case "5b":
		$kills = Kills::getKills(array("iskValue" => 5000000000, "limit" => $limit, "page" => $page, "cacheTime" => 300));
	break;
	case "10b":
		$kills = Kills::getKills(array("iskValue" => 10000000000, "limit" => $limit, "page" => $page, "cacheTime" => 300));
	break;
	case "t1":
		$kills = Kills::getKills(array("groupID" => array(419,27,29,547,26,420,25,28,941,463,237,31), "limit" => $limit, "cacheTime" => 300, "losses" => true, "page" => $page));
	break;
	case "t2":
		$kills = Kills::getKills(array("groupID" => array(324,898,906,540,830,893,543,541,833,358,894,831,902,832,900,834,380), "limit" => $limit, "cacheTime" => 300, "losses" => true, "page" => $page));
	break;
	case "t3":
		$kills = Kills::getKills(array("groupID" => array(963), "limit" => $limit, "cacheTime" => 300, "losses" => true, "page" => $page));
	break;
	case "frigates":
		$kills = Kills::getKills(array("groupID" => array(324,893,25,831,237), "limit" => $limit, "cacheTime" => 300, "losses" => true, "page" => $page));
	break;
	case "destroyers":
		$kills = Kills::getKills(array("groupID" => array(420,541), "limit" => $limit, "cacheTime" => 300, "losses" => true, "page" => $page));
	break;
	case "cruisers":
		$kills = Kills::getKills(array("groupID" => array(906,26,833,358,894,832,963), "limit" => $limit, "cacheTime" => 300, "losses" => true, "page" => $page));
	break;
	case "battlecruisers":
		$kills = Kills::getKills(array("groupID" => array(419,540), "limit" => $limit, "cacheTime" => 300, "losses" => true, "page" => $page));
	break;
	case "battleships":
		$kills = Kills::getKills(array("groupID" => array(27,898,900), "limit" => $limit, "cacheTime" => 300, "losses" => true, "page" => $page));
	break;
	case "solo":
		$kills = Kills::getKills(array("losses" => true, "solo" => true, "limit" => $limit, "!shipTypeID" => 670, "!groupID" => array(237, 31), "cacheTime" => 3600, "page" => $page));
	break;
	case "capitals":
		$kills = Kills::getKills(array("groupID" => array(547,485), "limit" => $limit, "cacheTime" => 300, "losses" => true, "page" => $page));
	break;
	case "freighters":
		$kills = Kills::getKills(array("groupID" => array(513,902,941), "limit" => $limit, "cacheTime" => 300, "losses" => true, "page" => $page));
	break;
	case "supers":
		$kills = Kills::getKills(array("groupID" => array(30, 659), "limit" => $limit, "cacheTime" => 300, "losses" => true, "page" => $page));
	break;
	case "w-space":
		$kills = Kills::getKills(array("w-space" => true, "page" => $page));
	break;
	default:
		$kills = Kills::getKills(array("combined" => true, "page" => $page));
	break;
}

global $boardCorpId;
foreach($kills as $killID => $kill)
{
	if($kill["victim"]["corporationID"] == $boardCorpId)
	{
		$kill["displayAsLoss"] = true;
		$kills[$killID] = $kill;
	}
}

$app->render("kills.html", array("kills" => $kills, "numPages" => $killPages, "page" => $page, "pageType" => $type));
