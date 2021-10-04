<?php
	require_once('5c_files_lib.php');
	require_once('5c_http_lib.php');
        require_once('5c_database_lib.php');
        require_once('amocrm_settings.php');

	date_default_timezone_set('Etc/GMT-3');

	$log_file='/var/log/5-systems/calls.log';

	$CallId='';
	$CallDate='';
	$CallerId='';
	$CalledId='';
	$ExtNum='';
	
	$num_parameters=count($argv);
	for( $i=0; $i<$num_parameters; $i++ ) {
		if($i==1) $CallId=$argv[$i];
		if($i==2) $CallDate=$argv[$i];
		if($i==3) $CallerId=$argv[$i];
		if($i==4) $CalledId=$argv[$i];
		if($i==5) $ExtNum=$argv[$i];
	}
	
	write_log('blank_line', $log_file, 'reg_call_inbound');
	write_log($argv, $log_file, 'reg_call_inbound');

	$url="http://95.216.7.226:8088/crm_reg_call.php";
	$url=$url."?CallId=$CallId&CallerNumber=$CallerId&CallDate=$CallDate&CalledNumber=$CalledId&FirstCalledNumber=$ExtNum&Outcoming=0&MissedCall=0&PhoneStation=AS";

	$parameters=array();
        $response='';

        $queue_name=remove_symbols($CallerId);
	$queue_name=substr($queue_name, -10);
        $db_conn=new mysqli($amocrm_database_host, $amocrm_database_user, $amocrm_database_password, $amocrm_database_name);

        $lock_status=lock_database($db_conn, '', 1, 0.1, 1, 0, 1, 0.0, $queue_name, 200);
	if( $lock_status===true ) {
		$response=request_GET($url, $parameters);
	}

        if( $lock_status===true
            && isset($db_conn) ) {

                unlock_database($db_conn, '', $queue_name);
        }

        // close connection to db
        if( isset($db_conn) ) {
                $db_conn->close();
        }

	write_log($url, $log_file, 'reg_call_inbound');

	echo $response;

?>
