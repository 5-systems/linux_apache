<?php
	require_once('5c_files_lib.php');
	require_once('5c_http_lib.php');

	date_default_timezone_set('Etc/GMT-3');

	$log_file='/var/log/5-systems/callback.log';

	$CallId='';
	$CallerId='';
	$CallDate='';
	$CalledId='';
	$CallerName='';
	$Comment='';
	$WebPage='';
	$AdvChannel='';
	$Department='';
	
	$num_parameters=count($argv);
	for( $i=0; $i<$num_parameters; $i++ ) {
		if($i==1) $CallId=$argv[$i];
		if($i==2) $CallerId=$argv[$i];
		if($i==3) $CallDate=$argv[$i];
		if($i==4) $CalledId=$argv[$i];
		if($i==5) $CallerName=$argv[$i];
		if($i==6) $Comment=$argv[$i];
		if($i==7) $WebPage=$argv[$i];
		if($i==8) $AdvChannel=$argv[$i];
		if($i==9) $Department=$argv[$i];
	}

	if( strlen($Comment)>=8 && substr($Comment, 0, 8)==='Comment='  ) {
		$Comment=substr($Comment, 8);
	}

        if( strlen($Comment)>=6 && substr($Comment, 0, 6)==='base64' ) {

		if( strlen($Comment)>6 ) {
			$Comment=substr($Comment, 6);
			$Comment=replace_special_base64($Comment);
			$Comment=base64_decode($Comment);
		}
		else {
			$Comment='';
		}

	}

        write_log('blank_line', $log_file, 'reg_call_back');
	write_log($argv, $log_file, 'reg_call_back');

	$url="http://apache_1C/crm_reg_call.php";

/*
	$url=$url."?".$CallId."&".$CallerId."&".$CallDate."&".$CalledId."&FromWeb=1&MissedCall=0";
	$url=$url."&".urlencode($CallerName);
	$url=$url."&Comment=".urlencode($Comment);
	$url=$url."&".$WebPage."&".$AdvChannel."&".$Department;
*/
        $parameters=array();
	$parameters['CallId']=get_parameter_value('CallId', $CallId);
	$parameters['CallerNumber']=get_parameter_value('CallerNumber', $CallerId);
	$parameters['CallDate']=get_parameter_value('CallDate', $CallDate);
	$parameters['CalledNumber']=get_parameter_value('CalledNumber', $CalledId);
	$parameters['FromWeb']='1';
	$parameters['MissedCall']='0';
	$parameters['ContactInfo']=get_parameter_value('ContactInfo', $CallerName);
	$parameters['Comment']=get_parameter_value('Comment', $Comment);
	$parameters['WebPage']=get_parameter_value('WebPage', $WebPage);
	$parameters['Department']=get_parameter_value('Department', $Department);
	$parameters['AdvChannel']=get_parameter_value('AdvChannel', $AdvChannel);

	$headers=array();
	$headers[] = "application/x-www-form-urlencoded";

        $response='';
        $response=request_POST($url, $parameters, $log_file, $headers);

        write_log($url, $log_file, 'reg_call_back');
        write_log('response: '.strVal($response), $log_file, 'reg_call_back');

	echo $response;


function get_parameter_value($param_name, $input_str) {

        $result=$input_str;

	$result=str_replace($param_name.'=', '', $result);

        return($result);
}

?>
