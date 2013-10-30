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

global $subDomainKey, $subDomainRow;
if ($subDomainRow) {
	include( "view/overview.php" );
	return;
}

$topIsk = json_decode(Storage::retrieve("TopIsk"), true);
$topPods = json_decode(Storage::retrieve("TopPods"), true);
$topKillers = json_decode(Storage::retrieve("Top30dayChars"), true);

// get latest kills
$killsLimit = 50;
$kills = Kills::getKills(array("limit" => $killsLimit));

$app->render("index.html", array(
	"topPods" => $topPods,
	"topIsk" => $topIsk,
	"topKillers" => $topKillers,
	"kills" => $kills
));
