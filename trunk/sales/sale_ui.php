<?php session_start(); 

include ("../settings.php");
include ("../language/$cfg_language");
$lang=new language();

//updating row for an item already in sale.
if(isset($_GET['update_item']))
{
	$k=$_GET['update_item'];
	$new_price=$_POST["price$k"];
	$new_tax=$_POST["tax$k"];
	$new_quantity=$_POST["quantity$k"];

	$item_info=explode(' ',$_SESSION['items_in_sale'][$k]);
	$item_id=$item_info[0];
	$percentOff=$item_info[4];
	
	$_SESSION['items_in_sale'][$k]=$item_id.' '.$new_price.' '.$new_tax.' '.$new_quantity.' '.$percentOff;
	header("location: sale_ui.php");
	
}

if(isset($_GET['discount']))
{
	$discount=$_POST['global_sale_discount'];

	if(is_numeric($discount))
	{
		for($k=0;$k<count($_SESSION['items_in_sale']);$k++)
		{
			$item_info=explode(' ',$_SESSION['items_in_sale'][$k]);
			$item_id=$item_info[0];
			$new_price=$item_info[1]*(1-($discount/100));
			$tax=$item_info[2];
			$quantity=$item_info[3];
			$percentOff=$item_info[4];
			
			$new_price=number_format($new_price,2,'.', '');
	
			$_SESSION['items_in_sale'][$k]=$item_id.' '.$new_price.' '.$tax.' '.$quantity.' '.$percentOff;
		}
	
		header("location: sale_ui.php?global_sale_discount=$discount");
	}
}


include ("../classes/db_functions.php");
include ("../classes/security_functions.php");		
include ("../classes/display.php");

$dbf=new db_functions($cfg_server,$cfg_username,$cfg_password,$cfg_database,$cfg_tableprefix,$cfg_theme,$lang);
$sec=new security_functions($dbf,'Sales Clerk',$lang);
$display=new display($dbf->conn,$cfg_theme,$cfg_currency_symbol,$lang);

if(isset($_POST['customer']))
{	
	if($cfg_numberForBarcode=="Row ID")
	{
		if($dbf->isValidCustomer($_POST['customer']))
		{
			$_SESSION['current_sale_customer_id']=$_POST['customer'];
		}
	}
	else//try account_number
	{
		$id=$dbf->fieldToid($cfg_tableprefix.'customers','account_number',$_POST['customer']);
				
		if($dbf->isValidCustomer($id))
		{
			$_SESSION['current_sale_customer_id']=$id;
		}
		else
		{
			echo "$lang->customerWithID/$lang->accountNumber ".$_POST['customer'].', '."$lang->isNotValid";
		}
	}
}

?>

<html>
<head>
<title>PHP Point Of Sale</title>
<script type="text/javascript" language="javascript">
<!--
function customerFocus()
{
	document.scan_customer.customer.focus();
	updateScanCustomerField();
}

function itemFocus()
{
	document.scan_item.item.focus();
	updateScanItemField();
}

function updateScanCustomerField()
{
	document.scan_customer.customer.value=document.scan_customer.customer_list.value;
}

function updateScanItemField()
{
	document.scan_item.item.value=document.scan_item.item_list.value;
}

//-->
</script>

</head>

<?php
if(isset($_SESSION['current_sale_customer_id']))
{
?>
<body onLoad="itemFocus();">
<?php
}
else
{
?>
<body onLoad="customerFocus();">
<?php
}



$table_bg=$display->sale_bg;
$items_table="$cfg_tableprefix".'items';

if(!$sec->isLoggedIn())
{
	header ("location: ../login.php");
	exit();
}


$display->displayTitle("$lang->newSale");

if(empty($_SESSION['current_sale_customer_id']))
{
	$customers_table="$cfg_tableprefix".'customers';
	
	if(isset($_POST['customer_search']) and $_POST['customer_search']!='')
	{
	 	$search=$_POST['customer_search'];
		$_SESSION['current_customer_search']=$search;
	 	$customer_result=mysql_query("SELECT first_name,last_name,account_number,id FROM $customers_table WHERE last_name like \"%$search%\" or first_name like \"%$search%\" or id =\"$search\" ORDER by last_name",$dbf->conn);
    }
    elseif(isset($_SESSION['current_customer_search']))
	{
	 	$search=$_SESSION['current_customer_search'];
	 	$customer_result=mysql_query("SELECT first_name,last_name,account_number,id FROM $customers_table WHERE last_name like \"%$search%\" or first_name like \"%$search%\" or id =\"$search\" ORDER by last_name",$dbf->conn);

	}
	elseif($dbf->getNumRows($customers_table) >200)
	{
		$customer_result=mysql_query("SELECT first_name,last_name,account_number,id FROM $customers_table ORDER by last_name LIMIT 0,200",$dbf->conn);	
	}
	else
  	{
		$customer_result=mysql_query("SELECT first_name,last_name,account_number,id FROM $customers_table ORDER by last_name",$dbf->conn);
	}
	
	$customer_title=isset($_SESSION['current_customer_search']) ? "<b><font color='white'>$lang->selectCustomer: </font></b>":"<font color='white'>$lang->selectCustomer: </font>";

	echo "<table align='center' cellpadding='2' cellspacing='2' bgcolor='$table_bg'>
	<form name='select_customer' action='sale_ui.php' method='POST'>
	<tr><td align='left'><font color='white'>$lang->findCustomer:</font>
	<input type='text' size='8' name='customer_search'>
	<input type='submit' value='Go'><a href='delete.php?action=customer_search'><font size='-1' color='white'>[$lang->clearSearch]</font></a>
	</form></td></tr>
	
	<form name='scan_customer' action='sale_ui.php' method='POST'>
	<tr><td align='left'>$customer_title<select name='customer_list' onChange=\"updateScanCustomerField()\";>";
 		
 		while($row=mysql_fetch_assoc($customer_result))
		{
			 if($cfg_numberForBarcode=="Row ID")
			 {
			 	$id=$row['id'];
			 }
			 elseif($cfg_numberForBarcode=="Account/Item Number")
			 {
			 	$id=$row['account_number'];
			 }
			 echo $id;
			 $display_name=$row['last_name'].', '.$row['first_name'];
			 echo "<option value=$id>$display_name</option></center>";
		}
	echo "</select></td><br><br>";
	
	echo "<tr><td align='left'><center><small><font color='white'>($lang->scanInCustomer)</font></small></center>";
	echo"<font color='white'>$lang->customerID / $lang->accountNumber: </font><input type='text' name='customer' size='6'>
	<input type='submit'></td></tr>
	</form>";
	
}

if(isset($_SESSION['current_sale_customer_id']))
{	
	if(isset($_POST['item']))
	{
		$item=$_POST['item'];
		$discount='0%';
		if($cfg_numberForBarcode=="Account/Item Number")
		{
				$item=$dbf->fieldToid($items_table,'item_number',$_POST['item']);

		}
		
		if($dbf->isValidItem($item))
		{
			if($dbf->isItemOnDiscount($item))
			{
				$discount=$dbf->getPercentDiscount($item).'%';
				$itemPrice=$dbf->getDiscountedPrice($item);
				
			}
			else
			{	
				$itemPrice=$dbf->idToField($items_table,'unit_price',$item);
			}
			$itemTax=$dbf->idToField($items_table,'tax_percent',$item);
			$_SESSION['items_in_sale'][]=$item.' '.$itemPrice.' '.$itemTax.' '.'1'.' '.$discount;

			
		}
		else
		{
			echo "$lang->itemWithID/$lang->itemNumber ".$_POST['item'].', '."$lang->isNotValid";
		}
	
	}
	
	if(isset($_SESSION['items_in_sale']))
	{
		$num_items=count($_SESSION['items_in_sale']);

	}
	else
	{
		$num_items=0;
	}	
	$temp_item_name='';
	$temp_item_id='';
	$temp_quantity='';
	$temp_price='';
	$finalSubTotal=0;
	$finalTax=0;
	$finalTotal=0;
	$totalItemsPurchased=0;
	
	$item_info=array();

	$customers_table="$cfg_tableprefix".'customers';
	$order_customer_first_name=$dbf->idToField($customers_table,'first_name',$_SESSION['current_sale_customer_id']);
	$order_customer_last_name=$dbf->idToField($customers_table,'last_name',$_SESSION['current_sale_customer_id']);
	$order_customer_name=$order_customer_first_name.' '.$order_customer_last_name;

	echo "<hr><center><a href=delete.php?action=all>[$lang->clearSale]</a></center>";
	
	
	  $items_table="$cfg_tableprefix".'items';
	  $brands_table="$cfg_tableprefix".'brands';
  
  
	  if(isset($_POST['item_search'])  and $_POST['item_search']!='')
	  {
	  	$search=$_POST['item_search'];
	  	$_SESSION['current_item_search']=$search;
	  	$item_result=mysql_query("SELECT item_name,unit_price,tax_percent,brand_id,item_number,quantity,id FROM $items_table WHERE item_name like \"%$search%\" or item_number= \"$search\" or id =\"$search\" ORDER by item_name",$dbf->conn);
	  }
	  elseif(isset($_SESSION['current_item_search']))
	  {
	  	$search=$_SESSION['current_item_search'];
	  	$item_result=mysql_query("SELECT item_name,unit_price,tax_percent,brand_id,item_number,quantity,id FROM $items_table WHERE item_name like \"%$search%\" or item_number= \"$search\" or id =\"$search\" ORDER by item_name",$dbf->conn);

	  }
	  elseif($dbf->getNumRows($items_table) >200)
	  {
	  	$item_result=mysql_query("SELECT item_name,unit_price,tax_percent,brand_id,item_number,quantity,id FROM $items_table ORDER by item_name LIMIT 0,200",$dbf->conn);
	  }
	  else
	  {
	  	$item_result=mysql_query("SELECT item_name,unit_price,tax_percent,brand_id,item_number,quantity,id FROM $items_table ORDER by item_name",$dbf->conn);
	  }
	  		
  
		$item_title=isset($_SESSION['current_item_search']) ? "<b><font color='white'>$lang->selectItem: </font></b>":"<font color=white>$lang->selectItem: </font>";
	    echo "<form name='select_item' action='sale_ui.php' method='POST'>
		<table border='0' bgcolor='$table_bg' align='center'>
		<tr><td align='left'><font color='white'>$lang->findItem: <input type='text' size='8' name='item_search'></font>
		<input type='submit' value='Go'><a href='delete.php?action=item_search'><font size='-1' color='white'>[$lang->clearSearch]</font></a></td></tr>";
		
			echo "</form><tr><td><form name='scan_item' action='sale_ui.php' method='POST'>
				$item_title <select name='item_list' onChange=\"updateScanItemField()\";>\n";
 
	  while($row=mysql_fetch_assoc($item_result))
	  {
	    if($cfg_numberForBarcode=="Row ID")
	    {
	  		$id=$row['id'];
	  		
	  	}
	  	elseif($cfg_numberForBarcode=="Account/Item Number")
	  	{
	  		$id=$row['item_number'];
	  	}
	  	
	  	$quantity=$row['quantity'];
	  	$brand_id=$row['brand_id'];
	  	$brand_name=$dbf->idToField("$brands_table",'brand',"$brand_id");
	  	$unit_price=$row['unit_price'];
	  	$tax_percent=$row['tax_percent'];
	  	$option_value=$id;
	    $display_item="$brand_name".'- '.$row['item_name'];
	    if($quantity <=0)
	    {
	    	 	echo "<option value='$option_value'>$display_item ($lang->outOfStockWarn)</option>\n";

	    }
	    else
	    {
	    	echo "<option value='$option_value'>$display_item</option>\n";

	    }
  
	  }
echo "</select></td></tr>
	<tr><td><center><small><font color='white'>($lang->scanInItem)</font></small></center>
	<font color='white'>$lang->itemID / $lang->itemNumber: </font><input type='text' name='item' size='6'>
	<input type='submit'></form></td></tr>
	<center>$lang->orderFor: <b>$order_customer_name</b></center><br>

</table>";
	
	
	
	echo "<h3 align='center'>$lang->shoppingCart</h3>
	
	<form name='add_sale' action='addsale.php' method='POST'>";
	echo "<table border='0' bgcolor='$table_bg' cellspacing='0' cellpadding='2' align='center'>
	<tr><th><font color=CCCCCC>$lang->remove</font></th>
	<th><font color=CCCCCC>$lang->itemName</font></th>
	<th><font color=CCCCCC>$lang->unitPrice</font></th>
	<th><font color=CCCCCC>$lang->tax %</font></th>
	<th><font color=CCCCCC>$lang->quantity</font></th>
	<th><font color=CCCCCC>$lang->extendedPrice</font></th>
	<th><font color=CCCCCC>$lang->update</font></th>	
	<th><font color=CCCCCC>$lang->percentOff</font></th>
	</tr>";

	for($k=0;$k<$num_items;$k++)
	{
		$item_info=explode(' ',$_SESSION['items_in_sale'][$k]);
		$temp_item_id=$item_info[0];
		$temp_item_name=$dbf->idToField($items_table,'item_name',$temp_item_id);
		$temp_price=$item_info[1];
		$temp_tax=$item_info[2];
		$temp_quantity=$item_info[3];
		$temp_discount=$item_info[4];
		
		$subTotal=$temp_price*$temp_quantity;
		$tax=$subTotal*($temp_tax/100);
		$rowTotal=$subTotal+$tax;
		$rowTotal=number_format($rowTotal,2,'.', '');
		
		$finalSubTotal+=$subTotal;
		$finalTax+=$tax;
		$finalTotal+=$rowTotal;
		$totalItemsPurchased+=$temp_quantity;
	
		echo "<tr><td align='center'><a href=delete.php?action=item&pos=$k><font color=white>[$lang->delete]</font></a></td>
				  <td align='center'><font color='white'><b>$temp_item_name</b></font></td>
				  <td align='center'><input type=text name='price$k' value='$temp_price' size='8'></td>
				  <td align='center'><input type=text name='tax$k' value='$temp_tax' size='3'></td>
				  <td align='center'><input type=text name='quantity$k' value='$temp_quantity' size='3'></td>
				  <td align='center'><font color='white'><b>$cfg_currency_symbol$rowTotal</b></font></td>
				  <td align='center'><input type='button' name='updateQuantity$k' value='$lang->update' onclick=\"document.add_sale.action='sale_ui.php?update_item=$k';document.add_sale.submit();\"></td>
				  <td align='center'><font color='white'><b>$temp_discount $lang->percentOff</b></font></td>
				  <input type='hidden' name='item_id$k' value='$temp_item_id'>
				  </tr>";
	}

	
	$finalSubTotal=number_format($finalSubTotal,2,'.', '');
	$finalTax=number_format($finalTax,2,'.', '');
	$finalTotal=number_format($finalTotal,2,'.', '');
	
	echo '</table>';
	
	
	echo "<table align='center' ><br>
	<tr><td align='left'>$lang->saleSubTotal: $cfg_currency_symbol$finalSubTotal</td></tr>
	<tr><td align='left'>$lang->tax: $cfg_currency_symbol$finalTax</td></tr>";
	if(isset($_GET['global_sale_discount']))
	{
		$discount=$_GET['global_sale_discount'];
		echo"<tr><td align='left'>$discount% $lang->percentOff</td></tr>";

	}
	echo"<tr><td align='left'><b>$lang->saleTotalCost: $cfg_currency_symbol$finalTotal</b></td></tr>";
	
	echo'</table>';
	
	echo "<br>
		<table align='center' bgcolor='$table_bg'><br>
		<tr><td align='left'><font color='white'>$lang->globalSaleDiscount</font></td>
		<td align='left'><input type='text' name='global_sale_discount' size='3'></td>
		<td><input type='button' name='updateQuantity$k' value='$lang->update' onclick=\"document.add_sale.action='sale_ui.php?discount=true';document.add_sale.submit();\"></td></tr>
		</table><br>";

	echo "<br><table border='0' bgcolor='$table_bg' align='center'>
	<tr>
	<td>
	<font color='white'>$lang->paidWith:</font> 
	</td>
	<td>
	<select name='paid_with'>
	<option value='$lang->cash'>$lang->cash</option>
	<option value='$lang->check'>$lang->check</option>
	<option value='$lang->credit'>$lang->credit</option>
	<option value='$lang->giftCertificate'>$lang->giftCertificate</option>
	<option value='$lang->account'>$lang->account</option>
	<option value='$lang->other'>$lang->other</option>
	</select>
	<font color='white'>$lang->amtTendered:<input type='text' name='amt_tendered'></font>
	</td>
	</tr>
	<tr>
	<td>
	<font color='white'>$lang->saleComment:</font>
	</td>
	<td>
	<input type=text name=comment size=25>
	</td>
	</tr>

	</table>
	  <br>
  	  <input type=hidden name='totalItemsPurchased' value='$totalItemsPurchased'>
  	  <input type=hidden name='totalTax' value='$finalTax'>
  	  <input type=hidden name='finalTotal' value='$finalTotal'>
	  <center><input type='submit' value='Add Sale'></center></form>";		
}


  
$dbf->closeDBlink();


?>
</body>
</html>