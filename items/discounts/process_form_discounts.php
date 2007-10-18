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

//creates 2 objects needed for this script.
$lang=new language();
$dbf=new db_functions($cfg_server,$cfg_username,$cfg_password,$cfg_database,$cfg_tableprefix,$cfg_theme,$lang);
$sec=new security_functions($dbf,'Admin',$lang);

//checks if user is logged in.
if(!$sec->isLoggedIn())
{
	header ("location: ../../login.php");
	exit ();
}

//variables needed globably in this file.
$tablename="$cfg_tableprefix".'discounts';
$field_names=null;
$field_data=null;
$id=-1;



	//checks to see if action is delete and an ID is specified. (only delete uses $_GET.)
	if(isset($_GET['action']) and isset($_GET['id']))
	{
		$action=$_GET['action'];
		$id=$_GET['id'];
	}
	//checks to make sure data is comming from form ($action is either delete or update)
	elseif(isset($_POST['item_id']) and isset($_POST['percent_off']) and isset($_POST['comment']) and isset($_POST['id']) and isset($_POST['action']) )
	{
		
		$action=$_POST['action'];
		$id = $_POST['id'];
		
		//gets variables entered by user.
		$item_id=$_POST['item_id'];
		$percent_off=$_POST['percent_off'];
		$comment=$_POST['comment'];
	
		
		//insure all fields are filled in.
		if($item_id=='' or $percent_off=='')
		{
			echo "$lang->forgottenFields";
			exit();
		}
		else
		{
			$field_names=array('item_id','percent_off','comment');
			$field_data=array("$item_id","$percent_off","$comment");	
	
		}
		
	}
	else
	{
		//outputs error message because user did not use form to fill out data.
		echo "$lang->mustUseForm";
		exit();
	}
	


switch ($action)
{
	//finds out what action needs to be taken and preforms it by calling methods from dbf class.
	case $action=="insert":
		$dbf->insert($field_names,$field_data,$tablename,true);

	break;
		
	case $action=="update":
		$dbf->update($field_names,$field_data,$tablename,$id,true);
				
	break;
	
	case $action=="delete":
		$dbf->deleteRow($tablename,$id);
	
	break;
	
	default:
		echo "$lang->noActionSpecified";
	break;
}
$dbf->closeDBlink();

?>
<br>
<a href="manage_discounts.php"><?php echo $lang->manageDiscounts ?>--></a>
<br>
<a href="form_discounts.php?action=insert"><?php echo $lang->discountAnItem ?>--></a>
</body>
</html>