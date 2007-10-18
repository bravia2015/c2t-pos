<?php session_start(); ?>

<html>
<head>


</head>

<body>
<?php

include ("../settings.php");
include ("../language/$cfg_language");
include ("../classes/db_functions.php");
include ("../classes/security_functions.php");
include ("../classes/form.php");
include ("../classes/display.php");

$lang=new language();
$dbf=new db_functions($cfg_server,$cfg_username,$cfg_password,$cfg_database,$cfg_tableprefix,$cfg_theme,$lang);
$sec=new security_functions($dbf,'Admin',$lang);
$display=new display($dbf->conn,$cfg_theme,$cfg_currency_symbol,$lang);

if(!$sec->isLoggedIn())
{
		header ("location: ../login.php");
		exit();
}
//set default values, these will change if $action==update.
$paid_with_value='';
$comment_value='';
$id=-1;

//decides if the form will be used to update or add a user.


	$display->displayTitle("Update Sale");
	if(isset($_GET['id']))
	{
		$id=$_GET['id'];
		$tablename = "$cfg_tableprefix".'sales';
		$result = mysql_query("SELECT * FROM $tablename WHERE id=\"$id\"",$dbf->conn);
		
		$row = mysql_fetch_assoc($result);
		$paid_with_value=$row['paid_with'];
		$comment_value=$row['comment'];
	}

//creates a form object
$f1=new form('process_update_sale.php','POST','sale','325',$cfg_theme,$lang);

//creates form parts.
echo "<br><br><center><b>$lang->updateSaleID $id</b></center>";
$option_values=array("$paid_with_value",'Cash','Check', 'Credit','Gift Certificate','Account','Other');
$option_titles=array("$paid_with_value",$lang->cash,$lang->check,$lang->credit,$lang->giftCertificate,$lang->account,$lang->other);
$f1->createSelectField("<b>$lang->paidWith:</b>",'paid_with',$option_values,$option_titles,'130');
$f1->createInputField("<b>$lang->saleComment:</b>",'text','comment',"$comment_value",'24','180');
echo "		
		<input type='hidden' name='id' value='$id'>";
$f1->endForm();

$dbf->closeDBlink();

?>
	
</body>
</html>



