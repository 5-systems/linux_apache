<?php
    require_once('5c_files_lib.php');
    date_default_timezone_set('Etc/GMT-3');

    $log_file='/var/log/5-systems/get_calldata.log';

    $CallerId='';
    
    $num_parameters=count($argv);
    for( $i=0; $i<$num_parameters; $i++ ) {
	if($i==1) $CallerId=$argv[$i];
    }

    write_log('blank_line', $log_file, '');
    write_log($argv, $log_file, '');

    $url="http://apache_1C/getcallerdata.php";
    $url=$url."?CallerNumber=$CallerId";

    $curl = curl_init();
    $headers[] = "Content-Type:text/plain; charset=utf-8";
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_COOKIESESSION, false);
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 2);

    curl_setopt($curl, CURLOPT_URL, $url);

    $response='';
    $response=curl_exec($curl);

    $delimiter_pos=strpos($response, '<br/>');
    if( $delimiter_pos!==false ) $response=substr($response, 0, $delimiter_pos);

    echo $response;

    write_log($url, $log_file, '');

?>
