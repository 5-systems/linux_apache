<?php

   date_default_timezone_set('Etc/GMT-3');
   
   require_once('../settings.php');
   require_once('../lib/5c_files_lib.php');   
   require_once('../lib/5c_std_lib.php');     
        
   @$uniqueid=$_REQUEST['id'];
   @$min_call_size=$_REQUEST['min_size'];
   @$info_type=$_REQUEST['infotype'];
   @$record_index=$_REQUEST['index'];


   if( !isset($uniqueid) ) {
   	$uniqueid='';
   }   
   
   if( !isset($info_type) ) {
   	$info_type='description';
   }    

   if( !isset($record_index) ) {
      $record_index='0';
   }
   
   if( !isset($min_call_size) ) {
      $min_call_size='0';
   }
  
   write_log('blank_line', $log_file, 'GET_REC '.$uniqueid);
   write_log('Start time='.time(), $log_file, 'GET_REC '.$uniqueid);
   write_log($_REQUEST, $log_file, 'GET_REC '.$uniqueid);   
 
   $uniqueid_point=strpos($uniqueid, '.');
   if( strlen($uniqueid)!==10 && $uniqueid_point!==10 ) {
      write_log('Bad format of uniqueid: uniqueid='.$uniqueid, $log_file, 'GET_REC '.$uniqueid);
      exit;
   }
   
   $uniqueid_int=substr($uniqueid, 0, $uniqueid_point);
   $uniqueid_num=intVal($uniqueid);
   
   write_log('search for record file ... time='.time(), $log_file, 'GET_REC '.$uniqueid);
   
   $data=Array();
   if( is_array($dir_records) ) {
      
      reset($dir_records);
      while( list($key, $value)=each($dir_records) ) {
         $data=Array();
         $data=get_data_from_filesystem($value);
         
         if( count($data)>0 && is_array($data[0]) && count($data[0])>0
             && ( array_key_exists('NumberOfRecords', $data[0]) && $data[0]['NumberOfRecords']>0
                  || array_key_exists('recordingfile', $data[0]) ) ) {
            break;
         }
      }   
      
   }
   else {
      $data=get_data_from_filesystem($dir_records);     
   }

   if( count($data)>0 && is_array($data[0]) && count($data[0])>0 ) {
   	write_log('record file found finish time='.time(), $log_file, 'GET_REC '.$uniqueid);
   }
   else {
   	write_log('record file not found time='.time(), $log_file, 'GET_REC '.$uniqueid);
   }
 
   // Store result
   $recordfiles=Array();
   $result='';
   foreach($data as $cur_line) {
   
   if( is_array($cur_line) ) { 
   
   	if( $info_type=='description' ) $result.="<?xml version=\"1.0\" encoding=\"UTF-8\"?><Data>";	    
   	
   	foreach($cur_line as $key=>$value) {
   	
   		if( $info_type=='description' ) $result.='<'.$key.'>'.number_format($value, 0, '.', '').'</'.$key.'>';		
   		
   		if( $info_type=='file' && $key=='recordingfile' ) {
   			$filename=$value;
   			$file_size=filesize($filename);
   			
   			$handle = fopen($filename, "rb");
            rewind($handle);
            $contents = fread($handle, filesize($filename));
            fclose($handle);

   			$result=$contents;
   			
   			$file_extension='';
   			$content_type_header='Content-type: audio/mpeg';
   			if( strlen($filename)>3 ) {
   			   
   			   $file_extension=substr($filename, -3);
   			   if( strcasecmp($file_extension, 'wav')===0 ) {
   			       $content_type_header='Content-type: audio/wav';  
   			   }
    			}
   			
   			header($content_type_header);
            header('Accept-Ranges: bytes');
   			header('Content-Length: '.sprintf("%d", $file_size));
   		}
   	}
   	
   	if( $info_type=='description' ) $result.="</Data>";
   }    
   }
   
   echo $result;

   write_log('Finish time='.time(), $log_file, 'GET_REC '.$uniqueid); 

  
function get_data_from_filesystem($dir_records='') {

   global $records_coeff_byte_to_sec_mp3_phone_station;
   global $records_coeff_byte_to_sec_wav_phone_station;    
   
   global $uniqueid;
   global $min_call_size;
   global $info_type;
   global $record_index;   
   
   global $uniqueid_num;
   global $log_file;


   write_log('Start search...', $log_file, 'GET_REC '.$uniqueid);   


   // From file structure
   if( strlen($dir_records)==0 ) {
      write_log('Path to directory is not defined!', $log_file, 'GET_REC '.$uniqueid);
      exit;
   }
   
   $len_path_dir=strlen($dir_records);
   if( substr($dir_records, $len_path_dir-1, 1)!=='/' ) {
      $dir_records.='/';
   }
   
    
   $file_date=date('Ymd', $uniqueid_num);
   $file_year=date('Y', $uniqueid_num);
   $file_month=date('m', $uniqueid_num);
   $file_day=date('d', $uniqueid_num);	
	
   $dir_search=$dir_records;
   $found_files=select_files_linux($dir_search, $uniqueid);

   write_log('Search: dir='.$dir_search.' found files='.count($found_files), $log_file, 'GET_REC '.$uniqueid);   
   
   $dir_content=Array();	
   while( list($key, $value)=each($found_files) ) {
   
      $file_uniqueid=get_uniqueid($value['name'], 'u');
      if( strlen($file_uniqueid)===0 ) {
          $file_uniqueid=get_uniqueid($value['name']);      
      }
      
      if($file_uniqueid!=='') {
      	$dir_content[$key]=$file_uniqueid;	    
      }
      
   }
        
   asort($dir_content, SORT_NUMERIC);
   
   $selected_files=Array();
   foreach($dir_content as $current_file=>$key) {
      if( $key===$uniqueid ) {
      	$selected_files[]=$current_file;
      }
   }     
   
   $result_columns=Array();
   if( $info_type=='description' ) {
   
      $file_counter=0;
      $total_duration=0;
      foreach($selected_files as $current_file) {
      	$full_filename=$current_file;
      	
      	$file_extension='';
      	$file_name_length=strlen($current_file);
      	if(  $file_name_length>3 ) {
      		$file_extension=substr($current_file, $file_name_length-3, 3);
      	}
      			
      	$file_coeff_size_sec=0;
      	if( strcasecmp($file_extension, 'mp3')==0 ) {
      		$file_coeff_size_sec=$records_coeff_byte_to_sec_mp3_phone_station;
      	}
      	elseif( strcasecmp($file_extension, 'wav')==0 ) {
      		$file_coeff_size_sec=$records_coeff_byte_to_sec_wav_phone_station;
      	}
      	
      	if( file_exists($full_filename) ) {		
      		$file_counter+=1;
      		$total_duration+=round( filesize($full_filename)*$file_coeff_size_sec) ;
      	}	    
      }
      
      $result_columns['NumberOfRecords']=$file_counter;
      $result_columns['CallDuration']=$total_duration;
      
   }
   
   $record_index_num=(int)($record_index);
   if( $info_type=='file' && count($selected_files)>0 && $record_index_num>=0) {
      $result_columns['recordingfile']=$selected_files[$record_index_num];
   }
   
   $data[]=$result_columns;
   
   return($data);
}
 
function select_function($select_function_type, $file_path, $file_attributes) {
	$result=true;

	if( $file_attributes['directory']==true || $file_attributes['size']<60 ) $result=false;
	
	return($result);
}  

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

?>
