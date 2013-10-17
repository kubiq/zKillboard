<?php

error_reporting(E_ALL & ~E_NOTICE);

if(PHP_SAPI != "cli")
    die("");

if($argc != 6)
    die("Usage: ".$argv[0]." /path/to/edk/root zkb_db_host zkb_db_user zkb_db_pass zkb_db_name\n");

// This is the ZKB db.
$db_host = $argv[2];
$db_user = $argv[3];
$db_pass = $argv[4];
$db_zkb_db = $argv[5];

// This is the path to your EDK
$KB_HOME = $argv[1];

@set_time_limit(0);

@ini_set('include_path', ini_get('include_path').':./common/includes');

chdir($KB_HOME);

require_once('kbconfig.php');
require_once('common/includes/globals.php');
require_once('common/includes/db.php');
require_once('common/includes/class.edkerror.php');

$kill = array();
$ammo = array();
$launcher = array();
$currentLowSlot = 11;
$currentMidSlot = 19;
$currentHighSlot = 27;
$currentRigSlot = 92;
$currentSubSlot = 125;

$zkbDb = new mysqli($db_host, $db_user, $db_pass, $db_zkb_db);
if($zkbDb->connect_errno)
{
	die("ZKB connect error");											  
}															  
															   
$zkbDb->autocommit(false);												 
															   
$qry = DBFactory::getDBQuery();											    
$qry->execute("select kll.kll_id from kb3_kills as kll order by kll.kll_id desc");					 
															   
$rows = array();													   
while($row = $qry->getRow())											       
	$rows[] = $row;												    
															   
foreach($rows as $row)												     
{															  
	$edkKill = new Kill($row["kll_id"]);

	$kill = array(
	"killID" => $edkKill->getExternalID(),
	"solarSystemID" => $edkKill->getSystem()->getExternalID(),
	"killTime" => $edkKill->getTimeStamp(),
	"moonID" => 0,
	"victim" => array(
		"shipTypeID" => $edkKill->getVictimShip()->getExternalID(),
		"damageTaken" => $edkKill->getDamageTaken(),
		"factionName" => "None",
		"factionID" => 0,
		"allianceName" => "None",
		"allianceID" => 0,
		"corporationName" => $edkKill->getVictimCorpName(),
		"corporationID" => $edkKill->getVictimCorp()->getExternalID(),
		"characterName" => $edkKill->getVictimName(),
		"characterID" => $edkKill->getVictim()->getExternalID(),
	),
	"attackers" => array(),
	"items" => array()
	);

	if($edkKill->getIsVictimFaction())
	{
		$kill["victim"]["factionID"] = $edkKill->getVictimAlliance()->getExternalID();
		$kill["victim"]["factionName"] = $edkKill->getVictimFactionName();
	}
	else if($edkKill->getVictimAllianceName() != "None" && $edkKill->getVictimAllianceName() != "Unknown" && $edkKill->getVictimAllianceName())
	{
		$kill["victim"]["allianceID"] = $edkKill->getVictimAlliance()->getExternalID();
		$kill["victim"]["allianceName"] = $edkKill->getVictimAllianceName();
	}

	if(in_array($edkKill->getVictimShip()->getClass()->getID(), array(35, 36, 37, 38)))
	{
		if($edkKill->getVictimName() != $edkKill->getSystem()->getName())
		{
			$value = $edkKill->getVictimName();
			$split = explode("-", $value);
			$value = "";
			$size = sizeof($split) - 1;
			for ($a = $size; $a >= 0; $a--)
			{
				$value = $split[$a] . ($a == $size ? "" : "-") . $value;
				if($value == "None")
					continue;
				$mres = $zkbDb->query("select itemID from ccp_mapDenormalize where itemName = '".$zkbDb->real_escape_string(trim($value))."'") or die($zkbDb->error);
				if($mres->num_rows > 0)
				{
					$kill['moonID'] = (int)$mres->fetch_object()->itemID;
					break;
				}
			}
		}
	}

	foreach($edkKill->getInvolved() as $inv)
	{
		$a_id = 0;
		$f_id = 0;
		$a_n = "None";
		$f_n = "None";
		$a = $inv->getAlliance();

		if($a->isFaction())
		{
			$f_id = $a->getExternalID();
			$f_n = $a->getName();
		}
		else if($a->getName() != "None" && $a->getName() != "Unknown" && $a->getName())
		{
			$a_id = $a->getExternalID();
			$a_n = $a->getName();
		}

		$kill["attackers"][] = array(
			"characterID" => $inv->getPilot()->getExternalID(),
			"characterName" => $inv->getPilot()->getName(),
			"corporationID" => $inv->getCorp()->getExternalID(),
			"corporationName" => $inv->getCorp()->getName(),
			"allianceID" => $a_id,
			"allianceName" => $a_n,
			"factionID" => $f_id,
			"factionName" => $f_n,
			"securityStatus" => $inv->getSecStatus(),
			"damageDone" => $inv->getDamageDone(),
			"finalBlow" => $edkKill->getFBPilotID() == $inv->getPilotID() ? 1 : 0,
			"weaponTypeID" => $inv->getWeaponID(),
			"shipTypeID" => $inv->getShipID(),
			);

		Cacheable::delCache($a);
		Cacheable::delCache($inv->getPilot());
		Cacheable::delCache($inv->getCorp());
	}

	$ammo = array();
	$launcher = array();
	$currentLowSlot = 11;
	$currentMidSlot = 19;
	$currentHighSlot = 27;
	$currentRigSlot = 92;
	$currentSubSlot = 125;

	if(count($edkKill->getDestroyedItems()) > 0)
	{
		foreach($edkKill->getDestroyedItems() as $destroyed)
		{
			processItem($destroyed->getItem(), $destroyed->getItem()->getExternalID(), $destroyed->getLocationID(), $destroyed->getItem()->getSlot(), $destroyed->getQuantity(), 0);
			Cacheable::delCache($destroyed->getItem());
		}
	}

	if(count($edkKill->getDroppedItems()) > 0)
	{
		foreach($edkKill->getDroppedItems() as $dropped)
		{
			processItem($dropped->getItem(), $dropped->getItem()->getExternalID(), $dropped->getLocationID(), $dropped->getItem()->getSlot(), 0, $dropped->getQuantity());
			Cacheable::delCache($dropped->getItem());
		}
	}

	foreach($ammo as $amId => $tAmmo)
	{
		$found = 0;

		foreach($launcher as $lauId => $tLauncher)
		{
			if($tLauncher["lGrp"] == $tAmmo["lGrp"] && $tLauncher["lSize"] == $tAmmo["lSize"])
			{
				$kill["items"][] = array(
					"typeID" => $tAmmo["typeID"],
					"flag" => $tLauncher["flag"],
					"qtyDropped" => $tAmmo["qtyDropped"],
					"qtyDestroyed" => $tAmmo["qtyDestroyed"],
					"singleton" => 0,
					);

				unset($launcher[$lauId]);
				$found = 1;
				break;
			}
		}

		if($found)
			unset($ammo[$amId]);
	}

	if(count($ammo) > 0)
	{
		echo "ammo:";
		var_dump($ammo);
		echo "launcher:";
		var_dump($launcher);
		echo "Warning: There is ammo left over!\n";
	}

	$hash = hash("sha256", ":".$kill['killTime'].":".$kill['solarSystemID'].":".$kill['moonID']."::".$kill['victim']['characterID'].":".$kill['victim']['shipTypeID'].":".$kill['victim']['damageTaken'].":");

	if(!$kill['killID'] || $kill['killID'] <= 0)
	{
		$rawMail = $zkbDb->real_escape_string($edkKill->getRawMail());
		$zkbDb->query("insert ignore into zz_manual_mails (hash, rawText) values ('$hash', '$rawMail')") or die($zkbDb->error);
		$kill['killID'] = -1 * $zkbDb->insert_id;
	}

	$json = $zkbDb->real_escape_string(json_encode($kill));

	$zkbDb->query("insert ignore into zz_killmails (killID, hash, source, kill_json) values (".$kill['killID'].", '$hash', 'EDK Import', '$json')") or die($zkbDb->error);

	echo "Transfered kill with EDK-ID ".$edkKill->getID()." to zKB ID ".$kill['killID']."\n";

	Cacheable::delCache($edkKill->getVictim());
	Cacheable::delCache($edkKill->getVictimCorp());
	Cacheable::delCache($edkKill->getVictimShip());
	Cacheable::delCache($edkKill->getSystem());
	Cacheable::delCache($edkKill->getVictimAlliance());
	Cacheable::delCache($edkKill);

	unset($kill);
	unset($edkKill);
}

echo "Commiting transaction...\n";
$zkbDb->commit();
$zkbDb->close();
echo "Done\n";
exit(0);

function processItem($edkitem, $typeId, $locId, $slot, $destQty, $dropQty)
{
	global $kill, $ammo, $launcher, $currentHighSlot, $currentMidSlot, $currentLowSlot, $currentRigSlot, $currentSubSlot;

	$item = array(
		"typeID" => $typeId,
		"flag" => $locId,
		"qtyDropped" => $dropQty,
		"qtyDestroyed" => $destQty,
		"singleton" => $locId == 9 ? 2 : 0,
		);

	if($locId == 8) // Implant
	{
		$item["flag"] = 89;
	}
	else if($locId == 6) // Drone Bay
	{
		$item["flag"] = 87;
	}
	else if($locId == 4 || $locId == 9) // Cargo
	{
		$item["flag"] = 5;
	}
	else
	{
		$lGrp = $edkitem->get_used_launcher_group();

		if($lGrp == 1156)
			$lGrp = 76;
		else if($lGrp == 290)
			$lGrp = 212;
		else if($lGrp == 213)
			$lGrp = 209;
		else if($lGrp == 511)
			$lGrp = 509;

		if($slot == 1) // Highslot
		{
			if($lGrp == 0)
			{
				if($item["qtyDropped"])
					$item["qtyDropped"] = 1;
				if($item["qtyDestroyed"])
					$item["qtyDestroyed"] = 1;

				for($i = 0; $i < $destQty + $dropQty; $i++)
				{
					$item["flag"] = $currentHighSlot;
					$currentHighSlot += 1;

					$group = $edkitem->get_group_id();

					if ($group == 483				 // Modulated Deep Core Miner II, Modulated Strip Miner II and Modulated Deep Core Strip Miner II
					 || $group == 53				  // Laser Turrets
					 || $group == 55				  // Projectile Turrets
					 || $group == 74				  // Hybrid Turrets
					 || ($group >= 506 && $group <= 511)	      // Some Missile Lauchers
					 || $group == 481				 // Probe Launchers
					 || $group == 899				 // Warp Disruption Field Generator I
					 || $group == 771				 // Heavy Assault Missile Launchers
					 || $group == 589				 // Interdiction Sphere Lauchers
					 || $group == 524				 // Citadel Torpedo Launchers
					 || $group == 862)				// Bomb Launcher
					{
						if ($group == 511)
							$group = 509;

						$launcher[] = array(
							"lGrp" => $group,
							"lSize" => $edkitem->get_used_charge_size(),
							"flag" => $item["flag"],
							);
					}

					$kill["items"][] = $item; 
				}

				$item = null;
			}
			else
			{
				$ammo[] = array(
					"typeID" => $typeId,
					"qtyDropped" => $dropQty,
					"qtyDestroyed" => $destQty,
					"lGrp" => $lGrp,
					"lSize" => ($lGrp == 481 ? 0 : $edkitem->get_ammo_size($edkitem->getName())),
					);

				$item = null;
			}
		}
		else if($slot == 2) // Med Slot
		{
			if($lGrp == 0)
			{
				if($item["qtyDropped"])
					$item["qtyDropped"] = 1;
				if($item["qtyDestroyed"])
					$item["qtyDestroyed"] = 1;

				for($i = 0; $i < $destQty + $dropQty; $i++)
				{
					$item["flag"] = $currentMidSlot;
					$currentMidSlot += 1;

					$group = $edkitem->get_group_id();

					if ($group == 76   // Capacitor Boosters
					 || $group == 208  // Remote Sensor Dampeners
					 || $group == 212  // Sensor Boosters
					 || $group == 291  // Tracking Disruptors
					 || $group == 213  // Tracking Computers
					 || $group == 209  // Tracking Links
					 || $group == 290  // Remote Sensor Boosters
					 || $group == 1156) // Ancil Shield Boosters
					{
						if($group == 1156)
							$group = 76;
						else if($group == 290)
							$group = 212;
						else if($group == 213)
							$group = 209;

						$launcher[] = array(
							"lGrp" => $group,
							"lSize" => 0,
							"flag" => $item["flag"],
							);
					}

					$kill["items"][] = $item;
				}

				$item = null;
			}
			else
			{
				$ammo[] = array(
					"typeID" => $typeId,
					"qtyDropped" => $dropQty,
					"qtyDestroyed" => $destQty,
					"lGrp" => $lGrp,
					"lSize" => 0,
					);

				$item = null;
			}
		}
		else if($slot == 3) // Low Slot
		{
			if($item["qtyDropped"])
				$item["qtyDropped"] = 1;
			if($item["qtyDestroyed"])
				$item["qtyDestroyed"] = 1;

			for($i = 0; $i < $destQty + $dropQty; $i++)
			{
				$item["flag"] = $currentLowSlot;
				$currentLowSlot += 1;

				$kill["items"][] = $item;
			}

			$item = null;
		}
		else if($slot == 5) // Rig
		{
			if($item["qtyDropped"])
				$item["qtyDropped"] = 1;
			if($item["qtyDestroyed"])
				$item["qtyDestroyed"] = 1;

			for($i = 0; $i < $destQty + $dropQty; $i++)
			{
				$item["flag"] = $currentRigSlot;
				$currentRigSlot += 1;

				$kill["items"][] = $item;
			}

			$item = null;
		}
		else if($slot == 7) // Subsys
		{
			if($item["qtyDropped"])
				$item["qtyDropped"] = 1;
			if($item["qtyDestroyed"])
				$item["qtyDestroyed"] = 1;

			for($i = 0; $i < $destQty + $dropQty; $i++)
			{
				$item["flag"] = $currentSubSlot;
				$currentSubSlot += 1;

				$kill["items"][] = $item;
			}

			$item = null;
		}
	}

	if($item !== null)
		$kill["items"][] = $item;
}