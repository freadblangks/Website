<?php
if (!defined('AXE'))
	exit;


//common include
$box_simple_wide = new Template("styles/".$style."/box_simple_wide.php");
$box_wide = new Template("styles/".$style."/box_wide.php");
$box_wide->setVar("imagepath", 'styles/'.$style.'/images/');
$box_simple_wide->setVar("imagepath", 'styles/'.$style.'/images/');
//end common include


//

$box_simple_wide->setVar("content", $cont1);
print $box_simple_wide->toString(); ?>


							<?php 
							$cont2='<table cellpadding="2" cellspacing="2"> <tr> <td valign="top">
							</div> <div class="sub-box1" align="left">
		<strong><font color="#9a2828">Your donation failed</strong><br><br></font> <strong>As a security precaution you will need to send more then just 1 sms to confirm your donation.
	<br> Make sure that you follow the instructions to 100%. </strong> <br><br>
	<font color="#9a2828"><a href="./quest.php?name=donate_sms"><b>> Try again<b></a></font>
		
	
		</div>
</center>
								

';

							 $cont2.='</td></tr></table>'; 
?>
                           
<?php
$box_wide->setVar("content_title", "Donation Failed");	
$box_wide->setVar("content", $cont2);					
print $box_wide->toString();							
?>				