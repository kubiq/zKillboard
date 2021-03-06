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

class cli_createAPIKeys implements cliCommand
{
	public function getDescription()
	{
		return "Creates API Keys for the zKB API";
	}

	public function getAvailMethods()
	{
		return ""; // Space seperated list
	}

	public function execute($parameters)
	{
		$users = Db::query("SELECT id FROM zz_users WHERE id NOT IN (SELECT userID FROM zz_zkbapi)");

		foreach($users as $id)
			zKBApi::createKey($id["id"]);
	}
}
