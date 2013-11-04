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

/**
 * Various API helper functions for the website
 */
class Api
{

	/**
	 * Checks a key for validity and KillLog access.
	 *
	 * @static
	 * @param $keyID The keyID to be checked.
	 * @param $vCode The vCode to be checked
	 * @return string A message, Success on success, otherwise an error.
	 */
	public static function checkAPI($keyID, $vCode)
	{
		$keyID = trim($keyID);
		$vCode = trim($vCode);
		if ($keyID == "" || $vCode == "")
			return "Error, no keyID and/or vCode";
		$keyID = (int)$keyID;
		if ($keyID == 0) {
			return "Invalid keyID.  Did you get the keyID and vCode mixed up?";
		}

		$pheal = Util::getPheal();
		$pheal = new Pheal($keyID, $vCode);
		try
		{
			$result = $pheal->accountScope->APIKeyInfo();
		}
		catch (Exception $e)
		{
			if (strlen($keyID) > 20)
				return "Error, you might have mistaken keyid for the vcode";
			return "Error: " . $e->getCode() . " Message: " . $e->getMessage();
		}

		$key = $result->key;
		$accessMask = $key->accessMask;
		$keyType = $key->type;
		$hasBits = self::hasBits($accessMask);

		if (!$hasBits) {
			return "Error, key does not have access to killlog, please modify key to add killlog access";
		}
		if ($hasBits) {
			return "success";
		}

	}

	/**
	 * Adds a key to the database.
	 *
	 * @static
	 * @param $keyID
	 * @param $vCode
	 * @param null $label
	 * @return string
	 */
	public static function addKey($keyID, $vCode, $label = null)
	{
		$userID = User::getUserID();
		if ($userID == null) $userID = 0;

		$exists = Db::queryRow("SELECT userID, keyID, vCode FROM zz_api WHERE keyID = :keyID AND vCode = :vCode", array(":keyID" => $keyID, ":vCode" => $vCode), 0);
		if ($exists == null) {
			// Insert the api key
			Db::execute("replace into zz_api (userID, keyID, vCode, label) VALUES (:userID, :keyID, :vCode, :label)", array(":userID" => $userID, ":keyID" => $keyID, ":vCode" => $vCode, ":label" => $label));
		} else if ($exists["userID"] == 0) {
			// Someone already gave us this key anonymously, give it to this user
			Db::execute("UPDATE zz_api SET userID = :userID, label = :label WHERE keyID = :keyID", array(":userID" => $userID, ":label" => $label, ":keyID" => $keyID));
			return "keyID $keyID previously existed in our database but has now been assigned to you.";
		} else {
			return "keyID $keyID is already in the database...";
		}

		$pheal = Util::getPheal();
		$pheal = new Pheal($keyID, $vCode);
		$result = $pheal->accountScope->APIKeyInfo();
		$key = $result->key;
		$keyType = $key->type;

		if ($keyType == "Account") $keyType = "Character";

		if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) $ip = $_SERVER["HTTP_CF_CONNECTING_IP"];
		elseif (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) $ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
		else $ip = $_SERVER['REMOTE_ADDR'];


		Log::ircAdmin("API: $keyID has been added.  Type: $keyType ($ip)");
		return "Success, your $keyType key has been added.";
	}

	/**
	 * Deletes a key owned by the currently logged in user.
	 *
	 * @static
	 * @param $keyID
	 * @return string
	 */
	public static function deleteKey($keyID)
	{
		$userID = user::getUserID();
		Db::execute("DELETE FROM zz_api_characters WHERE keyID = :keyID", array(":keyID" => $keyID));
		Db::execute("DELETE FROM zz_api WHERE userID = :userID AND keyID = :keyID", array(":userID" => $userID, ":keyID" => $keyID));
		return "$keyID has been deleted";
	}

	/**
	 * Returns a list of keys owned by the currently logged in user.
	 *
	 * @static
	 * @return Returns
	 */
	public static function getKeys($userID)
	{
		$userID = user::getUserID();
		$result = Db::query("SELECT keyID, vCode, label, lastValidation, errorCode FROM zz_api WHERE userID = :userID order by keyID", array(":userID" => $userID), 0);
		return $result;
	}

	/**
	 * Returns an array of charactery keys.
	 *
	 * @static
	 * @return Returns
	 */
	public static function getCharacterKeys($userID)
	{
		$result = Db::query("select c.* from zz_api_characters c left join zz_api a on (c.keyID = a.keyID) where a.userID = :userID", array(":userID" => $userID), 0);
		return $result;
	}

	/**
	 * Returns an array of the characters assigned to this user.
	 *
	 * @static
	 * @return array
	 */
	public static function getCharacters($userID)
	{
		$db = Db::query("SELECT characterID FROM zz_api_characters c left join zz_api a on (c.keyID = a.keyID) where userID = :userID", array(":userID" => $userID), 0);
		$results = Info::addInfo($db);
		return $results;
	}

	/**
	 * Tests the access mask for KillLog access
	 *
	 * @static
	 * @param $accessMask
	 * @return bool
	 */
	public static function hasBits($accessMask)
	{
		return ((int)($accessMask & 256) > 0);
	}

	/**
	 * API exception handling
	 * 
	 * @static
	 * @param $keyID
	 * @param $charID
	 * @param $exception
	 * @return void
	 */
	public static function handleApiException($keyID, $charID, $exception)
	{
		$code = $exception->getCode();
		$message = $exception->getMessage();
		$clearCharacter = false;
		$clearAllCharacters = false;
		$clearApiEntry = false;
		$updateCacheTime = false;
		$demoteCharacter = false;
		$cacheUntil = 0;
		switch ($code) {
			case 904:
				$msg = "Error 904 detected using key $keyID";
				Log::log($msg);
				$msg = "|r|$msg";
	//			Log::irc($msg);
	//			Log::admin($msg);
				break;
			case 403:
			case 502:
			case 503: // Service Unavailable - try again later
				$cacheUntil = time() + 300;
				$updateCacheTime = true;
				break;
			case 119: // Kills exhausted: retry after [{0}]
				$cacheUntil = $exception->cached_until;
				$updateCacheTime = true;
				break;
			case 120: // Expected beforeKillID [{0}] but supplied [{1}]: kills previously loaded.
				$cacheUntil = $exception->cached_until;
				$updateCacheTime = true;
				break;
			case 221: // Demote toon, illegal page access
				$clearAllCharacters = true;
				$clearApiEntry = true;
				break;
			case 220:
			case 200: // Current security level not high enough.
				// Typically happens when a key isn't a full API Key
				$clearAllCharacters = true;
				$clearApiEntry = true;
				//$code = 203; // Force it to go away, no point in keeping this key
				break;
			case 522:
			case 201: // Character does not belong to account.
				// Typically caused by a character transfer
				$clearCharacter = true;
				break;
			case 207: // Not available for NPC corporations.
			case 209:
				$demoteCharacter = true;
				break;
			case 222: // account has expired
			$clearAllCharacters = true;
			$clearApiEntry = true;
			$cacheUntil = time() + (7 * 24 * 3600); // Try again in a week
			break;
			case 403:
			case 211: // Login denied by account status
				// Remove characters, will revalidate with next doPopulate
				$clearAllCharacters = true;
				$clearApiEntry = true;
				break;
			case 202: // API key authentication failure.
			case 203: // Authentication failure - API is no good and will never be good again
			case 204: // Authentication failure.
			case 205: // Authentication failure (final pass).
			case 210: // Authentication failure.
			case 521: // Invalid username and/or password passed to UserData.LoginWebUser().
				$clearAllCharacters = true;
				$clearApiEntry = true;
				break;
			case 500: // Internal Server Error (More CCP Issues)
			case 520: // Unexpected failure accessing database. (More CCP issues)
			case 404: // URL Not Found (CCP having issues...)
			case 902: // Eve backend database temporarily disabled
				$updateCacheTime = true;
				$cacheUntil = time() + 3600; // Try again in an hour...
				break;
			case 0: // API Date could not be read / parsed, original exception (Something is wrong with the XML and it couldn't be parsed)
			default: // try again in 5 minutes
				Log::log("$keyID - Unhandled error - Code $code - $message");
				//$updateCacheTime = true;
				$clearApiEntry = true;
				//$cacheUntil = time() + 300;
		}

		if ($demoteCharacter && $charID != 0) {
			if (false === Db::execute("update zz_api_characters set isDirector = 'F' where characterID = :charID", array(":charID" => $charID), false)) {
				$clearCharacter = true;
			}
		}

		if ($clearCharacter && $charID != 0) {
			Db::execute("delete from zz_api_characters where keyID = :keyID and characterID = :charID", array(":keyID" => $keyID, ":charID" => $charID));
		}

		if ($clearAllCharacters) {
			Db::execute("delete from zz_api_characters where keyID = :keyID", array(":keyID" => $keyID));
		}

		if ($clearApiEntry) {
			Db::execute("update zz_api set errorCode = :code where keyID = :keyID", array(":keyID" => $keyID, ":code" => $code));
		}

		if ($updateCacheTime && $cacheUntil != 0 && $charID != 0) {
			Db::execute("update zz_api_characters set cachedUntil = :cacheUntil where characterID = :charID",
					array(":cacheUntil" => $cacheUntil, ":charID" => $charID));
		}
		Db::execute("update zz_api_characters set errorCode = :code where keyID = :keyID and characterID = :charID", array(":keyID" => $keyID, ":charID" => $charID, ":code" => $code));
	}

	public static function fetchApis()
	{
		global $baseDir;

		$fetchesPerSecond = 30;
		$timer = new Timer();
		$preFetched = array();

		$maxTime = 60 * 1000;
		while ($timer->stop() < $maxTime) {
			Db::execute("delete from zz_api_characters where isDirector = ''");

			$allChars = Db::query("select apiRowID, cachedUntil from zz_api_characters where errorCount < 10 and cachedUntil < date_sub(now(), interval 30 second) order by cachedUntil, keyID, characterID limit 1000", array(), 0);

			$total = sizeof($allChars);
			$corpsToSkip = array();
			$iterationCount = 0;

			if ($total == 0) sleep(1);
			else foreach ($allChars as $char) {
				if ($timer->stop() > $maxTime) return;

				$apiRowID = $char["apiRowID"];
				$cachedUntil = $char["cachedUntil"];

				Db::execute("update zz_api_characters set cachedUntil = date_add(if(cachedUntil=0, now(), cachedUntil), interval 5 minute), lastChecked = now() where apiRowID = :id", array(":id" => $apiRowID));

				$m = $iterationCount % $fetchesPerSecond;
				$command = "flock -w 60 {$baseDir}cache/locks/preFetch.$m zkillboard apiFetchKillLog $apiRowID";
				$command = escapeshellcmd($command);
				exec("$command >/dev/null 2>/dev/null &");
				Log::log('> Running command' . $command);
				$iterationCount++;
				if ($m == 0) { sleep(1); }
			}
		}
	}

	public static function doApiSummary()
	{
		$lastActualKills = Db::queryField("select contents count from zz_storage where locker = 'actualKills'", "count", array(), 0);
		$actualKills = Db::queryField("select count(*) count from zz_killmails where processed = 1", "count", array(), 0);

		$lastTotalKills = Db::queryField("select contents count from zz_storage where locker = 'totalKills'", "count", array(), 0);
		$totalKills = Db::queryField("select count(*) count from zz_killmails", "count", array(), 0);

		Db::execute("replace into zz_storage (locker, contents) values ('totalKills', $totalKills)");
		Db::execute("replace into zz_storage (locker, contents) values ('actualKills', $actualKills)");
		Db::execute("delete from zz_storage where locker like '%KillsProcessed'");

		$actualDifference = number_format($actualKills - $lastActualKills, 0);
		$totalDifference = number_format($totalKills - $lastTotalKills, 0);

		Log::irc("|g|$actualDifference|n| mails processed | |g|$totalDifference|n| kills added");
	}

	public static function processRawApi($keyID, $charID, $killlog)
	{
		$count = 0;
		$maxKillID = Db::queryField("select maxKillID from zz_api_characters where keyID = :keyID and characterID = :charID", "maxKillID",
				array(":keyID" => $keyID, ":charID" => $charID), 0);
		if ($maxKillID === null) $maxKillID = 0;
		$insertedMaxKillID = $maxKillID;
		foreach ($killlog->kills as $kill) {
			$killID = $kill->killID;
			//if ($killID < $maxKillID) continue;
			$insertedMaxKillID = max($insertedMaxKillID, $killID);

			$json = json_encode($kill->toArray());
			$hash = Util::getKillHash(null, $kill);
			$mKillID = Db::queryField("select killID from zz_killmails where killID < 0 and processed = 1 and hash = :hash", "killID", array(":hash" => $hash), 0);
			if ($mKillID) Kills::cleanDupe($mKillID, $killID);
			$added = Db::execute("insert ignore into zz_killmails (killID, hash, source, kill_json) values (:killID, :hash, :source, :json)",
					array(":killID" => $killID, ":hash" => $hash, ":source" => "keyID:$keyID", ":json" => $json));
			$count += $added;
		}
		if ($maxKillID != $insertedMaxKillID) {
			Db::execute("insert into zz_api_characters (keyID, characterID, maxKillID) values (:keyID, :charID, :maxKillID)
					on duplicate key update maxKillID = :maxKillID",
					array(":keyID" => $keyID, ":charID" => $charID, "maxKillID" => $insertedMaxKillID));
		}
		return $count;
	}
}
