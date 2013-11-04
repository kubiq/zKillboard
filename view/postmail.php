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

global $boardDisablePost;

if($boardDisablePost)
{
    $app->render("404.html");
    return;
}

$error = "";

if($_POST && !User::isRevoked())
{
	@$keyid = trim($_POST["keyid"]);
	@$vcode = trim($_POST["vcode"]);
	@$killmail = $_POST["killmail"];
	$label = "";
		
	// Apikey stuff
	if($keyid || $vcode)
	{
		$check = Api::checkAPI($keyid, $vcode);
		if($check == "success")
		{
			$error = array(Api::addKey($keyid, $vcode, $label));
		}
		else
		{
			$error = array($check);
		}
	}
	
	if($killmail)
	{
		$u = User::getUserInfo();
		if(User::isLoggedIn())
		{
			$return = Parser::parseRaw($killmail, $u["id"]);
			if(isset($return["success"]))
				$app->redirect("/detail/".$return["success"]."/");
			if(isset($return["dupe"]))
				$app->redirect("/detail/".$return["dupe"]."/");
			if(isset($return["error"]))
				$error = $return["error"];
		}
		else
			$error = array("Sorry, you need to be logged in to post manual killmails");
	}
}
if($_POST && User::isRevoked())
	$app->render("revoked.html");
else
	$app->render("postmail.html", array("message" => $error));
