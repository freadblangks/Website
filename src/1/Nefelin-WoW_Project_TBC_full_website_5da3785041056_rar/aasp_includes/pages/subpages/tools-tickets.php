<?php 
$server = new server;
$account = new account;
$page = new page;

$page->validatePageAccess('Tools->Tickets');

?>
<div class="box_right_title">Tickets</div>
<?php if(!isset($_GET['guid'])) { ?>
<table class="center">
        <tr>
            <td><input type="checkbox" id="tickets_offline">View offline tickets</td>
            <td>
            <select id="tickets_realm">
           		 <?php
				 $server->selectDB('webdb');
				 
				$result = mysql_query("SELECT char_db,name,description FROM realms");
				if(mysql_num_rows($result)==0) 
				{
					echo '<option value="NULL">No realms found.</option>';
				}
				else 
				{
					echo '<option value="NULL">--Select a realm--</option>';
					while($row = mysql_fetch_assoc($result)) 
					{
						echo '<option value="'.$row['char_db'].'">'.$row['name'].' - <i>'.$row['description'].'</i></option>';
					}
				}
				 ?>
            </select>
            </td>
            <td>
            <input type="submit" value="Load" onclick="loadTickets()">
            </td>
        </tr>
</table>
<hr/>
<span id="tickets">
	   <?php 
	    if(isset($_SESSION['lastTicketRealm']))
		   {
			   ##############################
				if($GLOBALS['core_expansion']==3)
					$guidString = 'playerGuid';
				else
					$guidString = 'guid';	
				
				if($GLOBALS['core_expansion']==3)
					$closedString = 'closed';
				else
					$closedString = 'closedBy';
					
				if($GLOBALS['core_expansion']==3)
				
					$ticketString = 'guid';
				else
					$ticketString = 'ticketId';
				############################
						
			  $offline = $_SESSION['lastTicketRealmOffline'];
			  $realm = mysql_real_escape_string($_SESSION['lastTicketRealm']);
			  

				if($realm == "NULL")
				   die("<pre>Please select a realm.</pre>");
				
				mysql_select_db($realm);	
				
				$result = mysql_query("SELECT ".$ticketString.",name,message,createtime,".$guidString.",".$closedString." FROM gm_tickets ORDER BY ticketId DESC");
				if(mysql_num_rows($result)==0)
				   die("<pre>No tickets were found!</pre>");
				   
				echo '
				<table class="center">
				   <tr>
					   <th>ID</th>
					   <th>Name</th>
					   <th>Message</th>
					   <th>Created</th>
					   <th>Ticket Status</th>
					   <th>Player Status</th>
					   <th>Quick Tools</th>
				   </tr>
				';
				
				while($row = mysql_fetch_assoc($result)) 
				{
					$get = mysql_query("SELECT COUNT(online) FROM characters WHERE guid='".$row[$guidString]."' AND online='1'");
					if(mysql_result($get,0)==0 && $offline == "on") {
					echo '<tr>';
						echo '<td><a href="?p=tools&s=tickets&guid='.$row[$ticketString].'&db='.$realm.'">'.$row[$ticketString].'</td>';
						echo '<td><a href="?p=tools&s=tickets&guid='.$row[$ticketString].'&db='.$realm.'">'.$row['name'].'</td>';
						echo '<td><a href="?p=tools&s=tickets&guid='.$row[$ticketString].'&db='.$realm.'">'.substr($row['message'],0,15).'...</td>';
						echo '<td><a href="?p=tools&s=tickets&guid='.$row[$ticketString].'&db='.$realm.'">'.date('Y-m-d H:i:s',$row['createtime']).'</a></td>';
						
						if($row[$closedString]==1) 
							echo '<td><font color="red">Closed</font></td>';
						else
							echo '<td><font color="green">Open</font></td>';		
						
						$get = mysql_query("SELECT COUNT(online) FROM characters WHERE guid='".$row[$guidString]."' AND online='1'");
						if(mysql_result($get,0)>0)
						   echo '<td><font color="green">Online</font></td>';
						else
						   echo '<td><font color="red">Offline</font></td>';
						   
						?> <td><a href="#" onclick="deleteTicket('<?php echo $row[$ticketString]; ?>','<?php echo $realm; ?>')">Delete</a>
								&nbsp;
								<?php if($row[$closedString]==1) 
								{ ?>
									<a href="#" onclick="openTicket('<?php echo $row[$ticketString]; ?>','<?php echo $realm; ?>')">Open</a>
								<?php }
								else 
								{
								?>
							   <a href="#" onclick="closeTicket('<?php echo $row[$ticketString]; ?>','<?php echo $realm; ?>')">Close</a>
							   <?php
								}
								?>
								</td><?php
							echo '<tr>';
							}
            }
            echo '</table>'; 
		   }
		   else
			echo '<pre>Please select a realm.</pre>';
	   ?>
</span>
<?php } 
elseif(isset($_GET['guid'])) 
{
	if($GLOBALS['core_expansion']==3)
		$guidString = 'playerGuid';
	else
		$guidString = 'guid';	
	
	if($GLOBALS['core_expansion']==3)
		$closedString = 'closed';
	else
		$closedString = 'closedBy';		
		
	if($GLOBALS['core_expansion']==3)
		$ticketString = 'guid';
	else
		$ticketString = 'ticketId';		
	
	mysql_select_db($_GET['db']);
	$result = mysql_query("SELECT name,message,createtime,".$guidString.",".$closedString." FROM gm_tickets WHERE ".$ticketString."='".(int)$_GET['guid']."'");
	$row = mysql_fetch_assoc($result);
	?>
    <table style="width: 100%;" class="center">
        <tr>
            <td>
            	<span class='blue_text'>Submitted by:</span>
            </td>	
            <td>
				<?php echo $row['name']; ?>
            </td>
                
            <td>
            	<span class='blue_text'>Created:</span>
            </td>
            <td>
				<?php echo date("Y-m-d H:i:s",$row['createtime']); ?>
            </td>
               
            <td>
            	<span class='blue_text'>Ticket Status:</span>
            </td>
            <td>
				<?php
                if($row[$closedString]==1) 
                    echo '<font color="red">Closed</font>';
                else
                    echo '<font color="green">Open</font>';
                ?>
            </td>
            
            <td>
            	<span class='blue_text'>Player Status:</span>
            </td>
            <td>
            	<?php
				$get = mysql_query("SELECT COUNT(online) FROM characters WHERE guid='".$row[$guidString]."' AND online='1'");
				if(mysql_result($get,0)>0)
				   	echo '<font color="green">Online</font>';
				else
				   echo '<font color="red">Offline</font>';
			   ?>
            </td>
                
        </tr>
    </table>
    <hr/>
    <?php
	echo nl2br($row['message']);
	?>
    <hr/>
    <pre>
        <a href="?p=tools&s=tickets">&laquo; Back to tickets</a>
        &nbsp; &nbsp; &nbsp;
        <a href="#" onclick="deleteTicket('<?php echo $_GET['guid']; ?>','<?php echo $_GET['db']; ?>')">Remove ticket</a>
        &nbsp; &nbsp; &nbsp;
        <?php if($row[$closedString]==1) 
			{ ?>
				<a href="#" onclick="openTicket('<?php echo $_GET['guid']; ?>','<?php echo $_GET['db']; ?>')">Open ticket</a>
			<?php }
			else 
			{
			?>
		  		<a href="#" onclick="closeTicket('<?php echo $_GET['guid']; ?>','<?php echo $_GET['db']; ?>')">Close ticket</a>
		   <?php
			}
		   ?>
    </pre>
    <?php
}

?>
