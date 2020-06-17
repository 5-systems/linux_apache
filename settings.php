<?php

    $get_list_of_files_from_filesystem=true;

    $tmp_files_dir='/var/tmp/records'; // write and read for apache
    $dir_records='/var/spool/asterisk/monitor/'; // read for apache
    $log_file='/var/log/records/get_record_log.txt'; // write and read for apache

    $url_get_record_phone_station=''; 
    $records_coeff_byte_to_sec_mp3_phone_station=0.000249303;
    $records_coeff_byte_to_sec_wav_phone_station=0.000062375;
    
?>