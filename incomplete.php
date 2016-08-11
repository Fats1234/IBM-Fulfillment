<?php
   require_once "functions.php";
   require_once "dbconfig.php";
   require_once "HTML/Table.php";

   include "header.php";
   
   $ibmDatabase = new mysqli($dbhost,$dbuser,$dbpass,$database);
   
   if(isset($_POST['complete'])){
      $recordIDs=explode(",",$_POST['recordIDs']);
      
      //Get the current set number
      $query="SELECT ibm_set_number FROM ibm_records_batch WHERE ibm_record_deleted=0 ORDER BY ibm_set_number DESC LIMIT 1";
      $results=$ibmDatabase->query($query);
      list($currentSet)=$results->fetch_row();
      $nextSet=$currentSet+1;
      
      //complete set by setting all current set 0 records to the next set
      foreach($recordIDs as $recordID){
         if(!empty($recordID)){
            $query="UPDATE ibm_records_batch SET ibm_set_number=$nextSet WHERE ibm_record_id=$recordID";
            //echo $query;
            $result=$ibmDatabase->query($query);
         }
      }
      
      header("Location: set.php");
   }
   
   //flag to inform there are no incomplete records
   $incompleteRecordsExist=false;
   
   //get a list of all system types and import records to database
   $query="SELECT ibm_system_type_id, ibm_system_import_link, ibm_system_archive_server, ibm_system_archive_file, ibm_system_archive_dest_dir
            FROM ibm_system_type";
   $results=$ibmDatabase->query($query);
   
   while($type=$results->fetch_assoc()){
      $timestamp=date("Y-m-d-His");
      if(!empty($type['ibm_system_import_link'])){
         //if(!getRecordFile($type['ibm_system_archive_server'],$type['ibm_system_archive_file'],$type['ibm_system_import_link'])){
            //exit("File was not downloaded correctly.  Please try refreshing page to try again!");
         //}
         if($data=archiveFile($type['ibm_system_archive_server'],$type['ibm_system_archive_file'],$type['ibm_system_archive_dest_dir']."serial-$timestamp.txt")){
            importFileToDatabase($ibmDatabase,$data,$type['ibm_system_type_id']);
         }
         //if(importFileToDatabase($ibmDatabase,$type['ibm_system_import_link'],$type['ibm_system_type_id'])){
         //   archiveFile($type['ibm_system_archive_server'],$type['ibm_system_archive_file'],$type['ibm_system_archive_dest_dir']."serial-$timestamp.txt");
            //archiveFile("production03","/share001/IBM-FULFILLMENT/serial.txt","/share001/IBM-FULFILLMENT/archive/serial-$timestamp.txt");
         //}
      }
      
      //check if there are records if not do not display table
      $query = "SELECT ibm_record_id 
                  FROM ibm_records_batch 
                  WHERE ibm_set_number=0
                  AND ibm_system_type_id=".$type['ibm_system_type_id']."
                  AND ibm_record_deleted=0 LIMIT 1";
      $records = $ibmDatabase->query($query);
   
      if($records->num_rows){
         echo "<font size=\"5\">The Following </font><font size=\"5\" color=\"red\"><b><u>Incomplete</u></b></font><font size=\"5\"> Records Were Found:</font><br><br>";
         echo genRecordsTable($ibmDatabase,$type['ibm_system_type_id']);
         $incompleteRecordsExist=true;
      }
   }
   
   if(!$incompleteRecordsExist){
      echo "<font size=\"5\">No Incomplete Records Found</font><br>\n";
      echo "<font size=\"5\"><a href=\"set.php\">Click Here For The Most Recent Completed Set</a></font><br>";
   }
   
   //function importFileToDatabase($database,$file,$systemType){
   function importFileToDatabase($database,$data,$systemType){
   
      //$data=file_get_contents($file);
      //$fh = fopen($file, "r");
      //$data = fread($fh, filesize($file));
      //fclose($fh);
      //copy($file,"$file.old");
      //unlink($file);
      //rename($file,"/var/www/html/ibm/tmp/old-$file");
      //echo $data;
   
      if(!empty($data)){
         $logfile=fopen("/var/www/html/ibm/logs/log.txt","a") or die("Unable to write to log file!");
         fwrite($logfile,"RAWDATA: \n".$data."\n");
         //$data=preg_replace("/\r/","\n",$data);
         $records=explode("\n",$data);
         foreach($records as $record){            
            if(!empty($record)){
               fwrite($logfile,"RECORD: ".$record."\n");
               $timestamp=date("Y-m-d-His");
         
               $serial="";
               $macaddresses=array();
               $values=explode(",",$record);
               $serial=$values[0];
               for($i=1;$i<count($values);$i++){
                  $macaddresses[]=$values[$i];
               }
               
               //insert serial number record
               $query="INSERT INTO ibm_records_batch SET ibm_serial_number='$serial',ibm_fulfill_date='$timestamp',ibm_system_type_id=$systemType";
               //echo $query;
               fwrite($logfile,"QUERY: ".$query."\n");
               if($database->query($query)){
                  $recordID=$database->insert_id;
                  fwrite($logfile,"INSERTID: ".$recordID."\n");
               }else{
                  fwrite($logfile,"ERROR: An error occurred inserting RECORD: $record\n");
               }
               //insert mac address records
               foreach($macaddresses as $interface => $macaddress){
                  $interfaceID=$interface+1;
                  if(!empty($macaddress)){
                     $query="INSERT INTO ibm_batch_macaddress SET ibm_record_id=$recordID,ibm_serial_number='$serial',ibm_interface_number=$interfaceID,ibm_macaddress='$macaddress'";
                     //echo $query;
                     fwrite($logfile,"QUERY: ".$query."\n");
                     if($database->query($query)){                        
                        fwrite($logfile,"INSERT MACADDRESS SUCCESS!\n");
                     }else{
                        fwrite($logfile,"ERROR: An error occured inserting MAC ADDRESS: $macaddress"."\n");
                     }
                  }
               }
            }
         }
         fclose($logfile);
         return TRUE;
      }else{
         return FALSE;
      }
   }
   
   function getRecordFile($ftpServer,$remoteFile,$localFile){
      //open connection to ftp
      $success=FALSE;
      $conn_id = ftp_connect($ftpServer) or die("Couldn't connect to ftp server: $ftpServer");
      ftp_login($conn_id,'archive','polywell');
      if(ftp_size($conn_id,$remoteFile)==-1){
         $success=TRUE;
      }else{
         if(ftp_get($conn_id,$localFile,$remoteFile,FTP_ASCII)){
            $success=TRUE;
         }
      }
      ftp_close($conn_id);
      
      return $success;
   }
   
   function archiveFile($ftpServer,$file,$archiveFile){
      //open connection to ftp
      $timestamp=date("Y-m-d-His");
      $localFile="/var/www/html/ibm/tmp/fulfill-records-$timestamp.txt";
      $conn_id = ftp_connect($ftpServer);
      ftp_login($conn_id,'archive','polywell');
      
      if(ftp_size($conn_id,$file)==-1){
         //file doesn't exist
         return false;
      }else{
         ftp_rename($conn_id,$file,$archiveFile);         
         while(filesize($localFile) != ftp_size($conn_id,$archiveFile)){
            ftp_get($conn_id,$localFile,$archiveFile,FTP_BINARY);
         }
         
         $filecontent=file_get_contents($localFile);
      }
      ftp_close($conn_id);
      
      return $filecontent;
   }
   
   //function to generate records table for a system type
   function genRecordsTable($database,$systemType=1,$setNumber=0){
      $query="SELECT ibm_record_id, ibm_serial_number 
               FROM ibm_records_batch 
               WHERE ibm_record_deleted=0 
               AND ibm_set_number=$setNumber 
               AND ibm_system_type_id=$systemType
               ORDER BY ibm_record_id";
      //echo $query;
      $records=$database->query($query);
      
      $attrs=array('border' => '1');
      $recordsTable = new HTML_TABLE($attrs);
      $recordsTable->setHeaderContents(0,0,"Rec. No.");
      $recordsTable->setHeaderContents(0,1,"Serial Number");
      
      $row=1;
      $recordIDs="";
      $maxMAC=0;
      
      while($record=$records->fetch_assoc()){
         $serial=$record['ibm_serial_number'];
         $recordsTable->setCellContents($row,0,$row);
         $recordsTable->setCellContents($row,1,$record['ibm_serial_number']);
         $recordIDs.=$record['ibm_record_id'].",";
         //grab mac addresses from database
         $query="SELECT ibm_macaddress FROM ibm_batch_macaddress WHERE ibm_record_id=".$record['ibm_record_id']." ORDER BY ibm_interface_number";
         //echo $query;
         $macaddresses=$database->query($query);
         
         $macaddress=array();
         while($results=$macaddresses->fetch_assoc()){
            $macaddress[]=$results['ibm_macaddress'];
         }
         //we want to find the biggest amount of mac addresses in order to set the header columns
         $maxMAC=max($maxMAC,count($macaddress));
         
         for($i=0;$i<count($macaddress);$i++){
            $recordsTable->setCellContents($row,$i+2,$macaddress[$i]);
         }
         
         $row++;
         
         //search for duplicate in database
         $query="SELECT ibm_fulfill_date,ibm_set_number
                     FROM ibm_records_batch
                     WHERE ibm_serial_number='$serial'
                     AND ibm_record_deleted=0
                     AND ibm_set_number!=0";               
         $duplicateRecords=$database->query($query);
         if($duplicateRecords->num_rows){
            while($duplicate=$duplicateRecords->fetch_assoc()){
               echo "<font size='5'><font color='red'>!!!WARNING!!!</font>Serial Number <font color='red'>$serial</font>".
                        " Has Already been Fulfilled on <font color='red'>".$duplicate['ibm_fulfill_date']."</font>".
                        " In Set Number <font color='red'>".$duplicate['ibm_set_number']."</font></font><br>";
            }
         }
      }
      
      //set MAC Address Header column
      for($i=0;$i<$maxMAC;$i++){
         $recordsTable->setHeaderContents(0,$i+2,"MAC Address (eth$i)");
      }
     
      //get system type name
      $query="SELECT ibm_system_type_name FROM ibm_system_type WHERE ibm_system_type_id=$systemType";
      $result=$database->query($query);
      list($systemName)=$result->fetch_row();
     
      $altAttrs=array('class' => 'alt');
      $recordsTable->altRowAttributes(0,null,$altAttrs);
      
      $returnStr = "<br>\n<font size=\"5\"><u>$systemName</u> <br> Total Records: $records->num_rows</font><br>\n";
      $returnStr .= startForm("incomplete.php","POST");
      $returnStr .= genHidden("recordIDs",$recordIDs);
      $returnStr .= genHidden("systemType",$systemType);
      if(checkSerialDuplicates($database,$setNumber,$systemType)){         
         $returnStr .= genButton("complete","complete","Complete Current Set");
      }
      $returnStr .= endForm();
      $returnStr .= $recordsTable->toHTML();
      $returnStr .= "<br>\n";
      return $returnStr;
      
   }
   
   //find duplicated serial within a set
   function checkSerialDuplicates($database,$set=0,$systemType=1){
      $query="SELECT ibm_serial_number, COUNT(*) duplicate_count 
                  FROM ibm_records_batch
                  WHERE ibm_set_number=$set
                  AND ibm_record_deleted=0
                  AND ibm_system_type_id=$systemType
                  GROUP BY ibm_serial_number 
                  HAVING duplicate_count > 1";
      
      $duplicatedSerials=$database->query($query);
      if($duplicatedSerials->num_rows){
         while($duplicate=$duplicatedSerials->fetch_assoc()){
            $duplicateSerialNumber=$duplicate['ibm_serial_number'];
            $duplicateCount=$duplicate['duplicate_count'];
            echo "<font size='5'><font color='red'>!!!ERROR!!!</font>Serial Number <font color='red'>$duplicateSerialNumber</font> ".
                  "was found <font color='red'>$duplicateCount</font> times in this Set</font><br>";            
         }
         return FALSE;
      }else{
         return TRUE;
      }
   }
   
   include "footer.php";
?>