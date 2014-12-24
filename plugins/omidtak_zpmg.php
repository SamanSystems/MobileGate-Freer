<?php
/**
 * Plugin Name: Zarinpal Mobile Gate Module For Freer
 * Plugin URI: http://omidtak.ir
 * Version: 1.0 
 * Release Date : 2014 20 December
 * Author: Omid Aran
 * Author Email: info[at]omidtak[dot]ir
 */
 
$pluginData[omidtak_zpmg][type] = 'payment';
$pluginData[omidtak_zpmg][name] = 'پرداخت موبایلی زرین پال';
$pluginData[omidtak_zpmg][uniq] = 'omidtak_zpmg';
$pluginData[omidtak_zpmg][description] = 'مخصوص پرداخت با درگاه موبایلی زرین پال </br><a href="https://ir.zarinpal.com" target="_blank">ZarinPal.Com</a>';
$pluginData[omidtak_zpmg][author][name] = 'امید آران Omidtak.ir';
$pluginData[omidtak_zpmg][author][url] = 'http://omidtak.ir';
$pluginData[omidtak_zpmg][author][email] = 'info@omidtak.ir';

$pluginData[omidtak_zpmg][field][config][1][title] = 'کد دروازه پرداخت';
$pluginData[omidtak_zpmg][field][config][1][name] = 'merchantID';
$pluginData[omidtak_zpmg][field][config][2][title] = 'چک کردن پرداخت کاربر بصورت خودکار (زمان وارد شده باید بر حسب ثانیه باشد)';
$pluginData[omidtak_zpmg][field][config][2][name] = 'time';

function gateway__omidtak_zpmg($data)
{
	global $config,$smarty,$db;
	$amount = $data[amount]/10;
	$data[title] = 'پرداخت فاکتور';
	$callback = $data[callback] . '&invoice_id='.$data[invoice_id].'&amount='.$amount;
	$client = new SoapClient('https://de.zarinpal.com/pg/services/WebGate/wsdl', array('encoding' => 'UTF-8')); 
	
	$result = $client->PaymentRequest(
						array(
								'MerchantID'  => $data[merchantID],
								'Amount' 	  => $amount,
								'Description' => 'پرداخت فاکتور '.$data[invoice_id],
								'CallbackURL' => $callback
						)
	);
	
	if($result->Status == 100)
	{	
		$time = ($data[time] > 1) ? $data[time] : 10;	
		$order_id = intval($result->Authority);
		$ussd = '*770*97*2*'.$order_id.'#';
		$data[message] = '<img src="https://chart.googleapis.com/chart?chs=200x200&cht=qr&chl=tel:%2A770%2A97%2A2%2A'.$order_id.'%23&choe=UTF-8&chld=Q|0" title="'.$ussd.'" /></br>';
		$data[message] .= 'کاربر گرامی برای پرداخت کافیست کد زیر را </br>';
		$data[message] .= $order_id.'#*2*97*770*</br>';
		$data[message] .= 'با تلفن همراه خود شماره گیری نمایید.</br>';
		$data[message] .= 'سیستم بصورت خودکار تراکنش شما را چک خواهد کرد و درصورت پرداخت به صفحه تحویل محصول هدایت خواهید شد .</br><a href="'.$callback.'&order_id='.$order_id.'&do=check"> چک کردن پرداخت شما </a>';		
		$data[message] .= '<div id=result></div>
		<script type=text/javascript>
			setInterval(function()
			{ 
			    $.ajax({
			      type:"post",
			      url:"omidtak_zpmg.php",
			      data:{order_id:"'.$order_id.'"},
			      success:function(data)
			      {
			      		if(data == "ok")
			      		{
			      			$("#result").html("وضعیت فاکتور شما : <font color=green>پرداخت شده</font>");		
			          		window.location = ("'.$callback.'&order_id='.$order_id.'&do=check");
			          	}
			          	else
			          		$("#result").html("وضعیت فاکتور شما : <font color=red>منتظر پرداخت</font>");
			      }
			    });
			}, '.$time.'000);  
		</script>'; 
	} 
	else 
	{
		switch($result->Status) 
		{ 
			case '-1' : $res = "اطلاعات ارسال شده ناقص است."; break; 
			case '-2' : $res = "IP و يا مرچنت كد پذيرنده صحيح نيست."; break; 
			case '-3' : $res = "با توجه به محدوديت هاي شاپرك امكان پرداخت با رقم درخواست شده ميسر نمي باشد."; break; 
			case '-4' : $res = "سطح تاييد پذيرنده پايين تر از سطح نقره اي است."; break; 
			case '-11' : $res = "درخواست مورد نظر يافت نشد."; break; 
			case '-21' : $res = "هيچ نوع عمليات مالي براي اين تراكنش يافت نشد."; break; 
			case '-22' : $res = "تراكنش نا موفق ميباشد."; break; 
			case '-33' : $res = "رقم تراكنش با رقم پرداخت شده مطابقت ندارد."; break; 
			case '-34' : $res = "سقف تقسيم تراكنش از لحاظ تعداد يا رقم عبور نموده است"; break; 
			case '-40' : $res = "اجازه دسترسي به متد مربوطه وجود ندارد."; break; 
			case '-41' : $res = "غيرمعتبر ميباشد AdditionalData اطلاعات ارسال شده مربوط به"; break;
			case '-54' : $res = "درخواست مورد نظر آرشيو شده."; break; 
			case '-101' : $res = "تراكنش انجام شده است. PaymentVerification عمليات پرداخت موفق بوده و قبلا"; break; 			
		}
		$data[message] = 'خطا ('.$result->Status.') : '.$res;
		$order_id = 'err_'.time();
	}
		
	$update[payment_rand] = $order_id;
	$sql = $db->queryUpdate('payment', $update, 'WHERE payment_rand = "'.$data[invoice_id].'" LIMIT 1;');
	$db->execute($sql);
	
	$smarty->assign('data', $data);
	$smarty->display('message.tpl');
	exit;        
}

function callback__omidtak_zpmg($data)
{
	global $db,$get,$smarty;

	if($get['do'] == 'check')
	{
		$order_id = intval($get['order_id']);
		$sql = "SELECT * FROM payment WHERE payment_rand = '{$order_id}' LIMIT 1;";	
		$payment = $db->fetch($sql);
				
		if ($payment[payment_status] == 2)
		{
			$output[status] = 1;
			$output[res_num] = $payment[payment_res_num];
			$output[ref_num] = $payment[payment_ref_num];
			$output[payment_id] = $payment[payment_id];					
		}	
		else
		{
			$output[status]	= 0;
			$output[message]= 'پرداخت موفقيت آميز نبود . </br><a href="#" onclick="window.location.reload();"> بررسی دوباره </a>';
		}										
	}
	else
	{
		if($get['Status'] == 'OK')
		{
			$client = new SoapClient('https://de.zarinpal.com/pg/services/WebGate/wsdl', array('encoding' => 'UTF-8')); 		
			$result = $client->PaymentVerification(
							  	array(
										'MerchantID' => $data[merchantID],
										'Authority'  => $get['Authority'],
										'Amount'	 => $get['amount']
									)
			);
			
			if($result->Status == 100)
			{
				$res = intval($get['Authority']);
				$sql = "SELECT * FROM payment WHERE payment_rand = '{$res}' LIMIT 1;";
				$payment = $db->fetch($sql);
				
				$output[status] = 1;
				$output[res_num] = (int) $get[invoice_id];
				$output[ref_num] = $result->RefID;
				$output[payment_id] = $payment[payment_id];			
			} 
			else 
				$output[status]	= 0;	
		} 
		else 
		{
			$output[status]	= 0;
			$output[message]= 'پرداخت موفقيت آميز نبود .';
		}
	}					
	return $output;
}