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
include ("../classes/form.php");
 
$lang= new language();
$dbf=new db_functions($cfg_server,$cfg_username,$cfg_password,$cfg_database,$cfg_tableprefix,$cfg_theme,$lang);
$sec=new security_functions($dbf,'Report Viewer',$lang);
if(!$sec->isLoggedIn())
{
    header ("location: ../login.php");
    exit();
}

if(isset($_POST['date_range']))
{
	$date_range=$_POST['date_range'];
 	$dates=explode(':',$date_range);
	$date1=$dates[0];
	$date2=$dates[1];
	
	$brands_name=array();
	$brands_id=array();
	$brands_total=array();
	$brands_subtotal=array();
}

$brands_table=$cfg_tableprefix.'brands';
$sales_table=$cfg_tableprefix.'sales';
$sales_items_table=$cfg_tableprefix.'sales_items';

$display=new display($dbf->conn,$cfg_theme,$cfg_currency_symbol,$lang);
$display->displayTitle("$cfg_company $lang->allBrandsReport");

$tableheaders=array("$lang->brand","$lang->totalWithOutTax","$lang->totalWithTax","$lang->tax");


$result=mysql_query("SELECT * FROM $sales_table WHERE date between \"$date1\" and \"$date2\" ORDER BY id DESC",$dbf->conn);
$result2=mysql_query("SELECT * FROM $sales_table WHERE date between \"$date1\" and \"$date2\" ORDER BY id ASC",$dbf->conn);
$row=mysql_fetch_assoc($result);
$high_id=$row['id'];
$row=mysql_fetch_assoc($result2);
$low_id=$row['id'];

$result3=mysql_query("SELECT * FROM $sales_items_table WHERE sale_id BETWEEN \"$low_id\" and \"$high_id\" ORDER BY id DESC",$dbf->conn);
echo "<center><h4><font color='$display->list_of_color'>$lang->totalsForBrands<br>$lang->between $date1 $lang->and $date2</font></h4></center>";
echo '<hr>';
		echo "<table cellspacing='$display->cellspacing' cellpadding='$display->cellpadding' bgcolor='$display->table_bgcolor' width='50%' style=\"border: $display->border_style $display->border_color $display->border_width px\" align='center'>
		
		<tr bgcolor=$display->header_rowcolor>\n\n";
		for($k=0;$k< count($tableheaders);$k++)
		{
			echo "<th align='center'>\n<font color='$display->header_text_color' face='$display->headerfont_face' size='$display->headerfont_size'>$tableheaders[$k]</font>\n</th>\n";
		}
		echo '</tr>'."\n\n";	
		$rowCounter=0;
		$subtotal=0;
		$total=0;
		
		$brand_result=mysql_query("SELECT * FROM $brands_table order by brand");
		while($row=mysql_fetch_assoc($brand_result))
		{
			$brands_id[]=$row['id'];
			$brands_name[]=$row['brand'];
			$brands_total[$row['id']]=0;
			$brands_subtotal[$row['id']]=0;

		}
		
		while($row=mysql_fetch_assoc($result3))
		{
			$brand_of_item=$dbf->idToField($cfg_tableprefix.'items','brand_id',$row['item_id']);
			$brands_subtotal[$brand_of_item]+=$row['item_total_cost']-$row['item_total_tax'];
			$brands_total[$brand_of_item]+=$row['item_total_cost'];
		
		}
		
		for($k=0;$k<count($brands_id);$k++)
		{
			if($rowCounter%2==0)
			{
				echo "\n<tr bgcolor=$display->rowcolor1>\n";
			}
			else
			{
				echo "\n<tr bgcolor=$display->rowcolor2>\n";
			}
			
			$id=$brands_id[$k];
			$name=$brands_name[$k];
			$subtotal=number_format($brands_subtotal[$id],2,'.', '');
			$total=number_format($brands_total[$id],2,'.', '');
			$tax=number_format($total-$subtotal,2,'.', '');
			
			echo "\n<td  align='center'>\n<font color='$display->rowcolor_text' face='$display->rowfont_face' size='$display->rowfont_size'>$name</font>\n</td>\n";
			echo "\n<td  align='center'>\n<font color='$display->rowcolor_text' face='$display->rowfont_face' size='$display->rowfont_size'>\$$subtotal</font>\n</td>\n";
			echo "\n<td  align='center'>\n<font color='$display->rowcolor_text' face='$display->rowfont_face' size='$display->rowfont_size'>\$$total</font>\n</td>\n";
			echo "\n<td  align='center'>\n<font color='$display->rowcolor_text' face='$display->rowfont_face' size='$display->rowfont_size'>\$$tax</font>\n</td>\n";
			
			$rowCounter++;
		
		}
		
		echo '</table>';

?>
</body>
</html> 