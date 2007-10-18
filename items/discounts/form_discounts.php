<?php session_start(); ?>

<html>
<head>


</head>

<body>
<?php

include ("../../settings.php");
include ("../../language/$cfg_language");
include ("../../classes/db_functions.php");
include ("../../classes/security_functions.php");
include ("../../classes/form.php");
include ("../../classes/display.php");

$lang=new language();
$dbf=new db_functions($cfg_server,$cfg_username,$cfg_password,$cfg_database,$cfg_tableprefix,$cfg_theme,$lang);
$sec=new security_functions($dbf,'Admin',$lang);
$display= new display($dbf->conn,$cfg_theme,$cfg_currency_symbol,$lang);

if(!$sec->isLoggedIn())
{
		header ("location: ../../login.php");
		exit();
}
//set default values, these will change if $action==update.
$item_id_value='';
$percent_off_value='';
$comment_value='';
$id=-1;

//decides if the form will be used to update or add a user.
if(isset($_GET['action']))
{
	$action=$_GET['action'];
}
else
{
	$action="insert";
}

//if action is update, sets variables to what the current users data is.
if($action=="update")
{
	$display->displayTitle("$lang->updateDiscount");
	
	if(isset($_GET['id']))
	{
		$id=$_GET['id'];
		$tablename = "$cfg_tableprefix".'discounts';
		$result = mysql_query("SELECT * FROM $tablename WHERE id=\"$id\"",$dbf->conn);
		
		$row = mysql_fetch_assoc($result);
		$item_id_value=$row['item_id'];
		$percent_off_value=$row['percent_off'];
		$comment_value=$row['comment'];
	}

}
else
{
	$display->displayTitle("$lang->addDiscount");

}
//creates a form object
$f1=new form('process_form_discounts.php','POST','discounts','300',$cfg_theme,$lang);

//creates form parts.
$itemtable = "$cfg_tableprefix".'items';

$item_option_titles=$dbf->getAllElements("$itemtable",'item_name','item_name');
$item_option_titles[0] = $dbf->idToField("$itemtable",'item_name',"$item_id_value");
$item_option_values=$dbf->getAllElements("$itemtable",'id','item_name');
$item_option_values[0] = $item_id_value;

$f1->createSelectField("<b>$lang->itemName:</b>",'item_id',$item_option_values,$item_option_titles,'160');

$f1->createInputField("<b>$lang->percentOff: (%)</b> ",'text','percent_off',"$percent_off_value",'24','150');
$f1->createInputField("$lang->comment: ",'text','comment',"$comment_value",'24','150');



//sends 2 hidden varibles needed for process_form_discounts.php.
echo "		
		<input type='hidden' name='action' value='$action'>
		<input type='hidden' name='id' value='$id'>";
$f1->endForm();

$dbf->closeDBlink();


?>
</body>
</html>	




