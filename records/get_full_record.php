<?php

   if( count($_REQUEST)===0 ) {   
      if( count($argv)>1 ) $_REQUEST['id']=$argv[1];    
   }
      
   require_once('../settings.php');
   require_once('../lib/5c_files_lib.php');
   require_once('../lib/5c_http_lib.php');
   require_once('../lib/5c_std_lib.php');
   
   @$linkedid=$_REQUEST['id'];
   
   if( !isset($linkedid) ) $linkedid='';
   
   $result='';
   
   if( strlen($linkedid)===0 ) {
      write_log('id is not found ', $log_file, 'GET_REC_B');
      exit($result);
   }
   
   // get data records from database
   $uniqueid_array=array();
   $file_path_array=array();
   $file_exten_array=array();
   if( $get_list_of_files_from_filesystem===true ) {
       $data_array=get_records_from_filesystem($linkedid, $dir_records);
       
       reset($data_array);
       foreach( $data_array as $key=>$value ) {
           
           $file_name=$value['name'];
           $uniqueid_file=$value['uniqueid'];
           $file_exten=$value['exten'];
           
           $uniqueid_array[$file_name]=$uniqueid_file;
           $file_path_array[$file_name]=$key;
           $file_exten_array[$file_name]=$file_exten;
       }
       
       asort($uniqueid_array, SORT_NUMERIC);
       
   }
   else {
       $uniqueid_array=get_records_from_database($linkedid);
   }

   $headers=array();
   $headers[]='Content-Type:application/x-www-form-urlencoded; charset=utf-8';
   
   if( count($uniqueid_array)>0 ) {
      
      $files_concat_array=array();
      $files_delete_array=array();
      
      $cycle_index=0;
      reset($uniqueid_array);
      while( list($key, $value)=each($uniqueid_array) ) {
         
         $parameters['id']=$value;
         $parameters['infotype']='file';
         $parameters['index']='0';
         $parameters['filename']=$key;
        
         $return_headers=array();
         
         $file_size=0;
         $file_path='';
         if( array_key_exists($key, $file_path_array)==true  ) {
             $file_path=$file_path_array[$key];
             $file_size=filesize($file_path);
         }
         
         if( array_key_exists($key, $file_exten_array)==true
             && strcasecmp($file_exten_array[$key], 'wav')===0 ) {
                 
             $return_headers[]='Content-type: audio/wav';
         }
         else {
             $return_headers[]='Content-type: audio/mpeg';
         }
         
         $return_headers[]='Accept-Ranges: bytes';
         $return_headers[]='Content-Length: '.sprintf("%d", $file_size);
         
         $record_file='';
         if( strlen($url_get_record_phone_station)>0 ) { 
            $record_file=request_GET($url_get_record_phone_station, $parameters, $log_file, null, $headers, 60, $return_headers);
         }
         else {
             $handle = fopen($file_path, "rb");
             rewind($handle);
             
             $record_file = fread($handle, $file_size);
             fclose($handle);
         }
         
         if( count($uniqueid_array)===1 ) {
            
            while(list($key_2, $value_2)=each($return_headers)) {
               header($value_2);
            }   
            
            $result=$record_file;
         }
         else {
            
            $file_wav=false;
            while(list($key_2, $value_2)=each($return_headers)) {
               if( strpos(strtolower($value_2), 'content-type')!==false
                   && strpos(strtolower($value_2), 'wav')!==false ) {
                   
                   $file_wav=true;    
               }
            }
            
            $tmp_directory=$tmp_files_dir;
            if( strlen($tmp_files_dir)>0 && substr($tmp_files_dir, -1, 1)!=='/' ) {
                $tmp_directory.='/';
            }
            
            $filename=$tmp_directory.$key;
            
            if(file_exists($filename)) unlink($filename);
            
   		
	        $handle = fopen($filename, "w+");
 
            $write_status = fwrite($handle, $record_file);
            fflush($handle);
            fclose($handle);    
     
            if($write_status!==false) {
               $files_delete_array[]=$filename;

              if( $file_wav===false ) {
                  $files_concat_array[]=$filename;
               }
               else {

                  $filename_mp3=$filename;
                  if( strlen($filename_mp3)>3
                      && strtolower( substr($filename_mp3, -3) )==='wav' ) {

                      $filename_mp3=substr($filename_mp3, 0, strlen($filename_mp3)-3).'mp3';
                  }
                  else {
                      $filename_mp3.='.mp3';
                  }

                  // convert to mp3
                  $shell_command='lame --cbr -b 32k -m m '.$filename.' '.$filename_mp3.' 2>/dev/null';
                  $return_shell=exec_shell_command($shell_command, $log_file, 'LAME_CONV');
                  if( $return_shell===0 ) {
                     $files_concat_array[]=$filename_mp3;
                     $files_delete_array[]=$filename_mp3;
                  }

               }
                  
            }
            
            
         }        
         
         $cycle_index+=1;
      }
   
      if( count($files_concat_array)>1 ) {
         $shell_command='cat ';
         
         reset($files_concat_array);
         while(list($key, $value)=each($files_concat_array)) {
            if( file_exists($value) && filesize($value)>0 ) {
               $shell_command.=$value.' ';
            }
         }
         
         $filename=$filename.'_concatenated.mp3';
         $shell_command.=' | lame --mp3input --tt "record" --tl "record" --ta "record" --cbr -m m  -b 32k - '.$filename;
         
         $return_shell=exec_shell_command($shell_command, $log_file, 'LAME_CONV');
         if( $return_shell===0 ) {
             $handle = fopen($filename, "rb");
             rewind($handle);
             
             $result = fread($handle, filesize($filename));
             fclose($handle);
             
             $file_size=filesize($filename);
             
             header('Content-type: audio/mpeg');
             header('Accept-Ranges: bytes');
             header('Content-Length: '.sprintf("%d", $file_size));
             
             $files_delete_array[]=$filename;
         }
      }
      
      if( count($files_delete_array)>0 ) {
         
         reset($files_delete_array);
         while(list($key, $value)=each($files_delete_array)) {        
            unlink($value);
         }   
      
      }
      
   }   
   
   echo $result;
   

function get_uniqueid($filename, $uniqueid_type='') {
    
    $result='';
    $filename_len=strlen($filename);
    
    if( $filename_len==0 ) retun($result);
    
    $start_index=-1;
    $first_point=-1;
    for( $i=0; $i<$filename_len-10; $i++) {
		if( $filename[$i]=='1' && $filename[$i+10]=='.' ) {
		    
		    if( $uniqueid_type==='' || ($i>1 && $filename[$i-2]==$uniqueid_type && $filename[$i-1]=='-') ) {
			     $start_index=$i;
			     $first_point=$i+10;
			     break;
		    }
		    
		}
    }
        
    if( $start_index<0 ) return($result);
    
    $file_uniqueid=substr($filename, $start_index, 10);
    $uniqueid_len=strlen($file_uniqueid);
    for( $i=0; $i<$uniqueid_len; $i++ ) {
		if( !ctype_digit( $file_uniqueid[$i] ) ) return($result);   
    }   
    
    $second_point=false;
    if( strlen($filename)>($first_point+1) ) {
        
        $filename_length=strlen($filename);
        for( $i=$first_point+1; $i<$filename_length; $i++ ) {
            if( !ctype_digit( $filename[$i] ) ) {
                $second_point=$i;
                break;
            }
        }
        
    }
    
    if( $second_point!==false && ($second_point-$first_point)<10 ) {
    
        for( $i=$first_point+1; $i<$second_point; $i++ ) {
			$ext_uniqueid=substr($filename, $i, 1);
	    
			if( $i==$first_point+1 ) $file_uniqueid.='.';
	    
			if( ctype_digit( $ext_uniqueid ) ) {
				$file_uniqueid.=$ext_uniqueid;	    
			}
			else {
				break;
			}
	    
		}    
    }
        
    $result=$file_uniqueid;
    
    return($result);
}   


function get_records_from_database($linkedid) {
 
    global $crm_linkedid_host;
    global $crm_linkedid_user;
    global $crm_linkedid_password;
    global $crm_linkedid_database_name;
    global $log_file;
    
    $result=array();

    $files_array=array();
    
    $db_conn=new mysqli($crm_linkedid_host, $crm_linkedid_user, $crm_linkedid_password, $crm_linkedid_database_name);
    
    if ( strlen($db_conn->connect_error)>0 ) {
        $result_message=$db_conn->connect_error;
        write_log('Connection to database is failed: '.$result_message, $log_file, 'GET_REC_B');
    }
    
    $query_text="";
    $query_text.="use &database_name&;";
    
    template_set_parameter('database_name', $crm_linkedid_database_name, $query_text);
    $query_status=$db_conn->query($query_text);
    if( $query_status===false ) {
        write_log('Cannot select database: '.mysql_error($db_conn), $log_file, 'GET_REC_B');
        return($result);
    }
    $file_name=$value['name'];
    $query_text="";
    $query_text.="select filename from cdr where &select_condition& order by uniqueid asc;";
    
    $select_condition="linkedid='".$linkedid."' and LENGTH(coalesce(filename, ''))>0 ";
    template_set_parameter('select_condition', $select_condition, $query_text);
    template_set_parameter('table', 'cdr', $query_text);
    
    $query_result=$db_conn->query($query_text);
    if( $query_result!==false ) {
        
        while ($row = $query_result->fetch_assoc()) {
            $files_array[]=$row['filename'];
        }
        
    }
    else {
        
        write_log('Cannot select uniqueid from database ', $log_file, 'GET_REC_B');
        
    }
    
    $uniqueid_array=array();
    if( count($files_array)>0 ) {
        
        reset($files_array);
        while( list($key, $value)=each($files_array) ) {
            $uniqueid=get_uniqueid($value);
            
            if( strlen($uniqueid)>=10 ) {
                $uniqueid_array[$value]=$uniqueid;
            }
        }
        
    }

    $result=$uniqueid_array;

    return($result);
}



function get_records_from_filesystem($linkedid, $search_dir) {
    
    $result=array();

    $files_array=select_files_linux($search_dir, $linkedid);
    
    $files_by_name=array();
    reset($files_array);
    foreach( $files_array as $key=>$value ) {
        $file_name=$value['name'];
        
        $uniqueid_from_file=get_uniqueid($file_name, 'u');
        if( strlen($uniqueid_from_file)===0 ) {
            $uniqueid_from_file=get_uniqueid($file_name);         
        }
               
        if( strlen($uniqueid_from_file)>0 ) {
            $file_info=$value;
            $file_info['uniqueid']=$uniqueid_from_file;
            
            $result[$key]=$file_info;
        }
    }
    
    return($result);
}


function select_function($select_function_type, $file_path, $file_attributes) {
    
    $result=false;
    
    if( $file_attributes['directory']===false ) {
        $result=true;
    }
    
    return($result);
} 

?>
