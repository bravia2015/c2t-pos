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
include ("../classes/display.php");

$lang=new language();
$dbf=new db_functions($cfg_server,$cfg_username,$cfg_password,$cfg_database,$cfg_tableprefix,$cfg_theme,$lang);
$sec=new security_functions($dbf,'Sales Clerk',$lang);
$display=new display($dbf->conn,$cfg_theme,$cfg_currency_symbol,$lang);

if(!$sec->isLoggedIn())
{
	header ("location: ../login.php");
	exit();
}

$table_bg=$display->sale_bg;
$num_items=count($_SESSION['items_in_sale']);

if($num_items==0)
{
	echo "<b>$lang->youMustSelectAtLeastOneItem</b><br>";
	echo "<a href=javascript:history.go(-1)>$lang->refreshAndTryAgain</a>";
	exit();
}
$customers_table=$cfg_tableprefix.'customers';
$items_table=$cfg_tableprefix.'items';
$sales_items_table=$cfg_tableprefix.'sales_items';
$sales_table=$cfg_tableprefix.'sales';

//general sale info
$paid_with=isset($_POST['paid_with'])?$_POST['paid_with']:'';
$comment=isset($_POST['comment'])?$_POST['comment']:'';
$customer_name=$dbf->idToField($customers_table,'first_name',$_SESSION['current_sale_customer_id']).' '.$dbf->idToField($customers_table,'last_name',$_SESSION['current_sale_customer_id']);

//totals
$finalTax=$_POST['totalTax'];
$sale_total_cost=$_POST['finalTotal'];
$temp_total_items_purchased=$_POST['totalItemsPurchased'];

$amt_tendered=$_POST['amt_tendered']; 
$amt_change=$amt_tendered-$sale_total_cost; 
$amt_tendered=number_format($amt_tendered, 2,'.',''); 
$amt_change=number_format($amt_change, 2,'.',''); 

$now=date("F j, Y, g:i a");
echo "
<center>$now<br>
<h4>$lang->orderBy: $customer_name [$lang->paidWith $paid_with]</h4>

<table border='0' cellspacing='0' cellpadding='2' bgcolor='$table_bg'>

		   <tr>
		   <th><font color='CCCCCC'>$lang->itemOrdered</font></th>
	   	   <th><font color='CCCCCC'>$lang->unitPrice</font></th>
		   <th><font color='CCCCCC'>$lang->quantity</font></th>
		   <th><font color='CCCCCC'>$lang->extendedPrice</font></th>
		   </tr>";


$todaysDate=date("Y-m-d");
$subtotal=number_format($sale_total_cost-$finalTax,2,'.', '');
$final_tax=number_format($finalTax,2,'.', '');

$field_names=array('date','customer_id','sale_sub_total','sale_total_cost','paid_with','items_purchased','sold_by','comment');
$field_data=array($todaysDate,$_SESSION['current_sale_customer_id'],$subtotal,$sale_total_cost,$paid_with,$temp_total_items_purchased,$_SESSION['session_user_id'],$comment);
$dbf->insert($field_names,$field_data,$sales_table,false);
$saleID=mysql_insert_id();

$field_names=array('sale_id','item_id','quantity_purchased','item_unit_price','item_buy_price','item_tax_percent','item_total_tax','item_total_cost');

$temp_item_id='';
$temp_item_name='';
$temp_quantity_purchased=0;
$temp_item_unit_price=0;
$temp_item_buy_price=0;
$temp_item_tax_percent=0;
$temp_item_tax=0;
$temp_item_cost=0;
$item_info=array();

//Add to sales_items table
for($k=0;$k<$num_items;$k++)
{
	$item_info=explode(' ',$_SESSION['items_in_sale'][$k]);
	
	$temp_item_id=$item_info[0];
	$temp_item_name=$dbf->idToField($items_table,'item_name',$temp_item_id);
	$temp_quantity_purchased=$item_info[3];
	$temp_item_unit_price=number_format($item_info[1],2,'.', '');
	$temp_item_buy_price=number_format($dbf->idToField($items_table,'buy_price',$temp_item_id),2,'.', '');
	$temp_item_tax_percent=$item_info[2];
	$temp_item_tax=number_format($temp_item_tax_percent/100*$temp_item_unit_price*$temp_quantity_purchased,2,'.', '');
	$temp_item_cost=number_format(($temp_item_unit_price*$temp_quantity_purchased)+$temp_item_tax,2,'.', '');

	$field_data=array("$saleID","$temp_item_id","$temp_quantity_purchased","$temp_item_unit_price","$temp_item_buy_price","$temp_item_tax_percent","$temp_item_tax","$temp_item_cost");
	$new_quantity=$dbf->idToField($items_table,'quantity',$temp_item_id)-$temp_quantity_purchased;
	$query="UPDATE $items_table SET quantity=\"$new_quantity\" WHERE $temp_item_id=id";
	mysql_query($query,$dbf->conn);
	$dbf->insert($field_names,$field_data,$sales_items_table,false);
	echo "<tr><td align='center'><font color='white'>$temp_item_name</font></td>
			  <td align='center'><font color='white'>$cfg_currency_symbol$temp_item_unit_price</font></td>
			  <td align='center'><font color='white'>$temp_quantity_purchased</font></td>
			  <td align='center'><font color='white'>$cfg_currency_symbol$temp_item_cost</font></td>
		  </tr>";

}

echo "</table><br>
<table border='0' align='center'><tr><td><b>$lang->saleSubTotal: $cfg_currency_symbol$subtotal</b></td></tr>";
echo "<tr><td><b>$lang->tax: $cfg_currency_symbol$final_tax</b></td></tr>";
echo "<tr><td><b>$lang->saleTotalCost: $cfg_currency_symbol$sale_total_cost</b></td></tr>";

if($amt_tendered!=0)
{
	echo "<tr><td><b>$lang->amtTendered: $cfg_currency_symbol$amt_tendered</b></td></tr>"; 
	echo "<tr><td><br/><b>$lang->amtChange: $cfg_currency_symbol$amt_change</b><br/></td></tr>"; 

}
echo "<tr><td><b>$lang->saleID: $saleID</b></td></tr></table></table>";

$sec->closeSale();
$dbf->closeDBlink();

echo"<br><br><b>$lang->contact $cfg_company:</b><p>";
if($cfg_address!='')
{
	$temp_address=nl2br($cfg_address);
	echo "$lang->address: $temp_address <br>";

}
if($cfg_phone!='')
{
	echo "$lang->phoneNumber: $cfg_phone <br>";

}

if($cfg_email!='')
{
	echo "$lang->email: $cfg_email <br>";

}

if($cfg_fax!='')
{
	echo "$lang->fax: $cfg_fax <br>";

}


if($cfg_website!='')
{
	echo "$lang->website <a href=$cfg_website>$cfg_website</a> <br>";

}


if($cfg_other!='')
{
	echo "$lang->other: $cfg_other <br>";

}


?>
<br><br>
<SCRIPT Language="Javascript">

/*
This script is written by Eric (Webcrawl@usa.net)
For full source code, installation instructions,
100's more DHTML scripts, and Terms Of
Use, visit dynamicdrive.com
*/

function printit(){  
if (window.print) {
    window.print() ;  
} else {
    var WebBrowser = '<OBJECT ID="WebBrowser1" WIDTH=0 HEIGHT=0 CLASSID="CLSID:8856F961-340A-11D0-A96B-00C04FD705A2"></OBJECT>';
document.body.insertAdjacentHTML('beforeEnd', WebBrowser);
    WebBrowser1.ExecWB(6, 2);//Use a 1 vs. a 2 for a prompting dialog box    WebBrowser1.outerHTML = "";  
}
}
</script>

<SCRIPT Language="Javascript">  
var NS = (navigator.appName == "Netscape");
var VERSION = parseInt(navigator.appVersion);
if (VERSION > 3) {
    document.write('<form><input type=button value="Print" name="Print" onClick="printit()"></form>');        
}
</script>
</body>
</html>