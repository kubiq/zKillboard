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

global $boardDisableRegister;

if($boardDisableRegister)
{
    $app->render("404.html");
    return;
}

if($_POST)
{
    $username = "";
    $password = "";
    $password2 = "";
    $email = "";
    
    if(isset($_POST["username"]))
        $username = $_POST["username"];
    if(isset($_POST["password"]))
        $password = $_POST["password"];
    if(isset($_POST["password2"]))
        $password2 = $_POST["password2"];
    if(isset($_POST["email"]))
        $email = $_POST["email"];

    if(!$password || !$password2)
    {
        $error = "Missing password, please retry";
        $app->render("register.html", array("error" => $error));
    }
    elseif(!$email)
    {
        $error = "Missing email, please retry";
        $app->render("register.html", array("error" => $error));
    }
    elseif($password != $password2)
    {
        $error = "Passwords don't match, please retry";
        $app->render("register.html", array("error" => $error));
    }
    elseif(!$username)
    {
        $error = "Missing username, please retry";
        $app->render("register.html", array("error" => $error));
    }
    elseif($username && $email && ($password == $password2)) // woohoo
    {
        // Lets check if the user isn't already registered
        if(Registration::checkRegistration($username, $email) == NULL) // He hasn't already registered, lets do et!
        {
            $message = Registration::registerUser($username, $password, $email);
            $app->render("register.html", array("type" => $message["type"], "message" => $message["message"]));
        }
    }
}
else
    $app->render("register.html");
