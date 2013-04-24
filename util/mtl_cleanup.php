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

require_once dirname(__FILE__) . "/../init.php";
$logging = false;

$corpId = $boardCorpId;

Db::execute("CREATE TEMPORARY TABLE tmp_cleanup AS SELECT DISTINCT km.killID FROM zz_killmails AS km WHERE km.processed = 1 AND NOT EXISTS (SELECT DISTINCT pt.killID FROM zz_participants AS pt WHERE pt.killID = km.killID AND pt.corporationID = :corpId)", array("corpId" => $corpId));
Db::execute("DELETE FROM zz_manual_mails WHERE mKillID IN (SELECT -killID FROM tmp_cleanup WHERE killID < 0)");
Db::execute("DELETE FROM zz_killmails WHERE killID IN (SELECT killID FROM tmp_cleanup)");
Db::execute("DELETE FROM zz_participants WHERE killID IN (SELECT killID FROM tmp_cleanup)");
Db::execute("DELETE FROM zz_items WHERE killID IN (SELECT killID FROM tmp_cleanup)");

$cnt = Db::queryField("SELECT COUNT(*) AS cnt FROM tmp_cleanup", "cnt");

echo "Removed $cnt non-corp kills!\n";
exit(0);
