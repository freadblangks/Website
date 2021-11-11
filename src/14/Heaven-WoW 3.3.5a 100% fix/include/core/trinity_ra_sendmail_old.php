<?php
/***********************************************************
*	Sendmail function for TrinityCore
*	by AXE
*   this file is required for all cores
************************************************************/

/***********************************************************
* 		 GLOBAL FUNCTIONS (required for all cores)
************************************************************/
function sendmail($playername,$playerguid, $subject, $text, $item, $shopid=0,  $money=0, $realmid='1') //returns, IMPORTANT: do not remove <!-- success --> if success
{
	global $server, $ra_user, $ra_pass, $REALM_DB, $a_user, $se_c, $realm;
	
	$playername = clean_string($playername);
    $subject = clean_string($subject); //no whitespaces
	$item = (int)$item; //item id
	$realmid = (int)$realmid;
	
	if ($item<>'')
		$item = " ".$item;
		
    $text = clean_string($text);
	$money = (int)$money;
	
	$telnet = fsockopen($server, $realm[$realmid]['port_ra'], $error, $error_str, 3);
	if($telnet)
	{
		sleep(3);

       	//fgets($telnet,1024); // Motd
		fputs($telnet,$ra_user."\n");
		
		
		//fgets($telnet,1024); // PASS
		fputs($telnet,$ra_pass."\n");
		
		sleep(3);
		
		$remote_login = fgets($telnet,1024);
		//if(strstr($remote_login, "Thanks for using the best Emulator <3."))
		//{
			if ($item<>'' && $item<>'0')//send item
			{
				//sendmail to RA console
				fputs($telnet, ".send items ".$playername." \"".$subject."\" \"".$text."\"".$item."\n");
				$easf=time();
				$mailtext="A mail with an item was sent!";
			}
			elseif ($money>'0' && $money<>'')//send money
			{
				fputs($telnet, ".send money ".$playername." \"".$subject."\" \"".$text."\" ".$money."\n");
				$moneytext="A mail with gold was sent!";
			}
			else //send letter
			{
				fputs($telnet, ".send mail ".$playername." \"".$subject."\" \"".$text."\"\n");
				$moneytext="A mail without any items or gold was sent!";
			}
			$test1111 = fgets($telnet,1024);
			$moneytext .= '<br>Console reported: "<i>'.$test1111.'</i>"';

			//check database if actuall item is there
			//WebsiteVoteShopREFXXXXXXX ->this is unique	
			$res = $REALM_DB->prepare("SELECT * FROM `mail` WHERE `receiver` = :receiver AND `subject` = :subject LIMIT 1");
			$res->bindParam(':receiver', $playerguid, PDO::PARAM_INT);
			$res->bindParam(':subject', $subject, PDO::PARAM_STR);
			$res->execute();
			
			if($res->rowCount() == 0)
			{
				$status="Rechecking script. (Just to make sure your mail is actually sent.):<br><br><center><iframe style='width:96%;  height:100px' src='./include/core/trinity_ra_iframe_mailcheck.php?shopid=".$shopid."&reciver=".$playerguid."&subject=".$subject."&realmid=".$realmid."&shash=".sha1($a_user['id'].$playerguid.$subject.$se_c.$shopid)."'><a href='./include/core/trinity_ra_iframe_mailcheck.php?shopid=".$shopid."&reciver=".$playerguid."&subject=".$subject."&realmid=".$realmid."&shash=".sha1($a_user['id'].$playerguid.$subject.$se_c.$shopid)."'>Check here if your mail has been sent.</a></iframe></center>";
			}
			unset($res);
			
			return  "<!-- success --><span class=\"colorgood\">".$mailtext.$moneytext."<br></span><br>".$status;		
		
		fclose($telnet);
	}
	else
		return  "<span class=\"colorbad\">The Trinity server is currently offline. This procedure can only succeed if the Trinity server is online.</span>";
}

/*************************************************************
* 		NON GLOBAL FUNCTIONS (not required for other cores)
**************************************************************/
function clean_string($string) //returns
{
    return str_replace(array("\n", "\""), "", $string);
}

function test_ra_connection() //used in htdocs/telnet-test.php, echoes
{
	global $server,$ra_user,$ra_pass,$realm;
	$telnet = fsockopen($server, $realm[1]['port_ra'], $error, $error_str, 3);
	
	
	if($telnet)
	{
		
		//fgets($telnet,1024); // Motd
		fputs($telnet, 'USER '.$ra_user."\n");
		echo "USER ".$ra_user."<br>";
		sleep(3);
		
		//fgets($telnet,1024); // PASS
		fputs($telnet,'PASS '. $ra_pass."\n");
		echo "PASS *****<br>";
		
		sleep(3);
		
		$remote_login = fgets($telnet,1024);
		echo  "The console reported: <i>".$remote_login."</i><br>";
			
		fclose($telnet);
	}
	else
		echo  "<font color=\"red\">A Telnet connection issue occured: <i>".$error_str."</i></font><br>";
}
function sendmail_confirm($receiver, $subject, $realmid)//returns
{	
	global $db, $realm;
	
	$REALM_DB = newRealmPDO($realmid);
	
	if ($REALM_DB)
	{
		$res = $REALM_DB->prepare("SELECT * FROM `mail` WHERE `receiver` = :receiver AND `subject` = :subject LIMIT 1");
		$res->bindParam(':receiver', $receiver, PDO::PARAM_INT);
		$res->bindParam(':subject', $subject, PDO::PARAM_STR);
		$res->execute();
			
		if($res->rowCount() == 0)
		{
			return "Checking for mail in DB... <font color='red'>Error: Your mail was not found!</font>";//you can change this text
		}
		else
		{
			return "Checking for mail in DB... <font color='lime'>Item is successfully sent!</font>";//do not change text or 'recheck' script will not work
		}
	}
	else
	{
		return "Checking for mail in DB... <font color='red'>Error: Failed to connection to the realm database!</font>";
	}
}