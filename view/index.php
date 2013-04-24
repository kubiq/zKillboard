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

global $subDomainKey, $subDomainRow, $boardCorpId;
if ($subDomainRow && !(isset($overrideSubdomain) && $overrideSubdomain)) {
	include( "view/overview.php" );
	return;
}

$corpID = $boardCorpId;

$topIsk = Stats::getTopIsk(array("pastSeconds" => (30*86400), "!corporationID" => $corpID, "limit" => 5));
$topPods = Stats::getTopIsk(array("shipTypeID" => 670, "pastSeconds" => (30*86400), "!corporationID" => $corpID, "limit" => 5));
$topPointList = Stats::getTopPoints("killID", array("losses" => true, "pastSeconds" => (30*86400), "!corporationID" => $corpID, "limit" => 5));
$topPoints = Kills::getKillsDetails($topPointList);

$top = array();
$top[] = json_decode(Cache::get("zKBTop3dayChars"), true);

$app->etag(md5(serialize($top)));
$app->expires("+5 minutes");

$app->render("index.html", array("topPods" => $topPods, "topIsk" => $topIsk, "topPoints" => $topPoints, "topKillers" => $top));
