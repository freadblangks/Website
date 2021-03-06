<?php
if (!defined('init_engine'))
{	
	header('HTTP/1.0 404 not found');
	exit;
}

class AccountsActivation
{
    protected $salt     = 'tdcActivation'; //Secret Word
	private   $key      = false;
	private   $account;
	
	public function __construct($accid = false)
	{
		if ($accid)
			$this->account = $accid;
	}
	
	/**
	**  Generates random key by the account ID and salt
	**/	
	public function generateKey()
	{
		$this->key = uniqid(mt_rand(), true) . sha1($this->account . $this->salt) . uniqid(mt_rand(), true);
		$this->key = str_replace('.', '', $this->key);
	}
	
	/**
	**  Registers the key generated by ($this->generateKey()) into the database
	**
	**  Returns:
	**  --------------------------------------------------------------------------------------------
	**  true  - Returned when the `activation` record is inserted
	**  false - Returned when the `activation` query failed to insert
	**        - Returned when there is no key
	**  --------------------------------------------------------------------------------------------
	**/		
	public function registerKey()
	{
		global $DB, $CORE;
		
		//check if we have key
		if ($this->key)
		{
			//erase old keys
			$delete_res = $DB->prepare("DELETE FROM `activations` WHERE `account` = :account");
			$delete_res->bindParam(':account', $this->account, PDO::PARAM_INT);
			$delete_res->execute();
			
			//insert new key	
			$insert_res = $DB->prepare("INSERT INTO `activations` (`account` ,`key` ,`time`) VALUES (:account, :key, :time)");
			$insert_res->bindParam(':account', $this->account, PDO::PARAM_INT);
			$insert_res->bindParam(':key', $this->key, PDO::PARAM_STR);
			$insert_res->bindParam(':time', $CORE->getTime(), PDO::PARAM_STR);
			$insert_res->execute();
			
			if ($insert_res->rowCount() < 1)
			{
				return false;
			}
		}
		else
		{
			return false;
		}
		
		return true;
	}
	
	/**
	**  Encodes the key on Base64 and returns it
	**/	
	public function get_encodedKey()
	{
		if ($this->key)
		{
			return  base64_encode($this->key);
		}
		else
		{
			//no key was generated, so we do it now
			$this->generateKey();
			//return the key using the same function
			return $this->get_encodedKey();
		}
	}

	/**
	**  Get account id, if stored
	**/	
	public function get_storedAccountID()
	{
		if ($this->account)
		{
			return  $this->account;
		}
		else
		{
			return false;
		}
	}

	/**
	**  Decodes the key on Base64 and stores it in the class
	**/	
	public function set_decodedKey($key = false)
	{
		if ($key)
		{
			$this->key = base64_decode($key);
		}
		else
		{
			return false;
		}
	}
	
	/**
	**  Sends mail for account activation
	**
	**  Selects account record by ID stored in the class from __construct()
	**
	**  Returns:
	**  --------------------------------------------------------------------------------------------
	**  true  - Returned when the mail is successfully sent
	**  false - Returned when the function fails to load mail HTML
	**        - Returned when the accounts query failed
	**        - Returned when the PHPMailer class failed to send mail
	**  --------------------------------------------------------------------------------------------
	**/	
	public function sendMail()
	{
		global $config, $DB;
				
		//setup the PHPMailer class
		$mail = new PHPMailerLite();
		$mail->IsSendmail();

  		$mail->SetFrom($config['Email'], 'DuloStore Support');
		
		//select the account record
		$res = $DB->prepare("SELECT id, email, firstName, lastName FROM `accounts` WHERE `id` = :account LIMIT 1");
		$res->bindParam(':account', $this->account, PDO::PARAM_INT);
		$res->execute();
		
		if ($res->rowCount() > 0)
		{
			$row = $res->fetch(PDO::FETCH_ASSOC);
			
			//get the message html
			$message = file_get_contents($config['RootPath'] . '/activation_mail.html');
			
			//break if the function failed to laod HTML
			if (!$message)
			{
				return false;
			}
			
			//replace the tags with info
			$search = array('{FIRST_NAME}', '{LAST_NAME}', '{URL}');
			$replace = array($row['firstName'], $row['lastName'], $config['BaseURL'] . '/index.php?page=activation&key=' . $this->get_encodedKey());
			$message = str_replace($search, $replace, $message);
			
  			$mail->AddAddress($row['email'], $row['firstName']. ' ' .$row['lastName']);
  			$mail->Subject = 'DuloStore Account Activation';			
  			$mail->MsgHTML($message);
  			if (!$mail->Send())
			{
				return false;
			}
		}
		else
		{
			return false;
		}
		
		return true;
	}
	
	/**
	**  Activates account by rawDecodedUrl key sent to email
	**
	**  Return Codes:
	**  --------------------------------------------------------------------------------------------
	**  'noKey'         - Returned when there is no key
	**  'missingRecord' - Returned when there is no activation record by the given key
	**  'expiredKey'    - Returned when the activation record is old (expired)
	**  'updateFailed'  - Returned when the accounts query has failed to update the status to active
	**  'success'       - Returned when the function succeeds
	**  --------------------------------------------------------------------------------------------
	**/
	public function activateByKey()
	{
		global $DB, $CORE;
		
		if ($this->key)
		{
			//make the query
			$res = $DB->prepare("SELECT * FROM `activations` WHERE `key` = :key LIMIT 1");
			//bind some parameters
			$res->bindParam(':key', $this->key, PDO::PARAM_STR);
			//run the query
			$res->execute();

			if ($res->rowCount() > 0)
			{
				//fetch associetive array
				$row = $res->fetch(PDO::FETCH_ASSOC);
				$this->account = $row['account'];
				
				//get the record time in timestamp
				$recordDate = new DateTime($row['time']);
				$recordTimestamp = $recordDate->getTimestamp();
				
				//create new time now -24 hours
				$newDate = $CORE->getTime(true);
				$newDate->modify("-24 hours");
				$agoTimestamp = $newDate->getTimestamp();
								
				//check if the key has expired
				if ($recordTimestamp < $agoTimestamp)
				{
					return 'expiredKey';
				}
				else
				{
					//delete the activation records
					$del = $DB->prepare("DELETE FROM `activations` WHERE `account` = :acc");
					$del->bindParam(':acc', $row['account'], PDO::PARAM_INT);
					$del->execute();
					
					//activate the account
					$update = $DB->prepare("UPDATE `accounts` SET `status` = 'active' WHERE `id` = :acc");
					$update->bindParam(':acc', $row['account'], PDO::PARAM_INT);
					$update->execute();
					
					if ($res->rowCount() > 0)
					{
						return 'success';
					}
					else
					{
						return 'updateFailed';
					}
				}
			}
			else
			{
				//no record with this key
				return 'missingRecord';
			}
		}
		else
		{
			return 'noKey';
		}
	}
	
	public function __destrruct()
	{
	}
}