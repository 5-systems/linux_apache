<?php
	require_once('5c_files_lib.php');
	date_default_timezone_set('Etc/GMT-3');

	$log_file='/var/log/5-systems/get_calltype.log';

	$CallerId='';
	
	$num_parameters=count($argv);
	for( $i=0; $i<$num_parameters; $i++ ) {
		if($i==1) $CallerId=$argv[$i];
	}

	write_log('blank_line', $log_file, '');
	write_log($argv, $log_file, '');

        $url="http://apache_1C/getcalltype.php";
	$url=$url."?CallerNumber=$CallerId";

	$curl = curl_init();
	$headers[] = "Content-Type:text/plain; charset=utf-8";
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($curl, CURLOPT_HEADER, false);
	curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($curl, CURLOPT_COOKIESESSION, false);
	curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);

	curl_setopt($curl, CURLOPT_URL, $url);

	$response=curl_exec($curl);

	//if( strpos($CallerId, '4955404614')!==false ) $response='<type>1</type><number>911</number>';

	echo $response;

	write_log($url, $log_file, '');
	write_log('response='.$response, $log_file, '');
?>
