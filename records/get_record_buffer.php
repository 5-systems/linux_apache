<?php

   require_once('../settings.php');
   require_once('../lib/5c_files_lib.php');
   require_once('../lib/5c_http_lib.php');
   require_once('../lib/5c_std_lib.php');
   
   $result='';
   
   write_log('blank_line', $log_file, 'GET_REC_B');
   write_log('Start ', $log_file, 'GET_REC_B');

   $return_headers=array();
   $parameters=$_REQUEST;
   $record_file=request_GET($url_get_record_phone_station, $parameters, $log_file, null, null, 60, $return_headers);


   while(list($key_2, $value_2)=each($return_headers)) {
       header($value_2);
   }

   $result=$record_file;

   write_log('Finish ', $log_file, 'GET_REC_B');

   echo $result;
   
?>
