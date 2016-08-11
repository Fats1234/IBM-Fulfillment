<?php
   require_once "dbconfig.php";
   
   $ibmDatabase = new mysqli($dbhost,$dbuser,$dbpass,$database);

   if(isset($_POST['modref'])){
      $batchID=$_POST['batchID'];
      
      $query="SELECT ibm_reference FROM ibm_batch_history WHERE ibm_batch_id=$batchID";
      $result=$ibmDatabase->query($query);
      list($reference)=$result->fetch_row();
      
      if(!empty($_POST['reference'])){
         $query="UPDATE ibm_batch_history SET ibm_reference='".$_POST['reference']."' WHERE ibm_batch_id=".$_POST['batchID'];
         $ibmDatabase->query($query);
         $reference=$_POST['reference'];
      }
      
      //update start Date
      $query="SELECT ibm_fulfill_date FROM ibm_records_batch WHERE ibm_batch_id=$batchID ORDER BY ibm_fulfill_date";
      $result=$ibmDatabase->query($query);
      list($startDate)=$result->fetch_row();

      //update end Date
      $query="SELECT ibm_fulfill_date FROM ibm_records_batch WHERE ibm_batch_id=$batchID ORDER BY ibm_fulfill_date DESC";
      $result=$ibmDatabase->query($query);
      list($endDate)=$result->fetch_row();
          
      //update number of records
      $query="SELECT COUNT(*) FROM ibm_records_batch WHERE ibm_batch_id=$batchID AND ibm_record_deleted=0";
      $result=$ibmDatabase->query($query);
      list($numRecords)=$result->fetch_row();
      
      $timestamp=date("Y-m-d-His");
      $newBatchCSVName=preg_replace("/\s/","",$reference);
      $newBatchCSVName=preg_replace("/[^0-9a-zA-Z_.]/","-",$newBatchCSVName)."-batch-$timestamp.csv";
      
      $query="SELECT ibm_record_id,ibm_serial_number FROM ibm_records_batch WHERE ibm_batch_id=$batchID AND ibm_record_deleted=0";
      $results=$ibmDatabase->query($query);
      
      $fh=fopen("batch/$newBatchCSVName","wt");
      while(list($recordID,$serialNo) = $results->fetch_row()){
         $query="SELECT ibm_macaddress FROM ibm_batch_macaddress WHERE ibm_record_id=$recordID ORDER BY ibm_interface_number";
         $macaddresses=$ibmDatabase->query($query);
         $macaddress=array();
         while($record=$macaddresses->fetch_assoc()){
            $macaddress[]=$record['ibm_macaddress'];
         }
         $recStr=$serialNo.",".implode(",",$macaddress)."\n";
         fwrite($fh,$recStr);             
      }
      fclose($fh);
      
      //update with new info
      $query="UPDATE ibm_batch_history SET ibm_start_date='$startDate',ibm_end_date='$endDate',
                        ibm_reference='$reference',ibm_download_link='$newBatchCSVName',
                        ibm_number_of_records=$numRecords WHERE ibm_batch_id=$batchID";
      $ibmDatabase->query($query);
            
      header("Location:".$_SERVER['HTTP_REFERER']);
   }

   if(isset($_POST['openbatch'])){
      $query="UPDATE ibm_batch_history SET batch_open=1 WHERE ibm_batch_id=".$_POST['batchID']." AND batch_locked=0";
      if($result=$ibmDatabase->query($query)){
         header("Location:".$_SERVER['HTTP_REFERER']);
      }else{
         echo "Error opening closed batch with id:".$_POST['batchID'];
      }
   }
   
   if(isset($_POST['closebatch'])){
      $query="UPDATE ibm_batch_history SET batch_open=0 WHERE ibm_batch_id=".$_POST['batchID'];
      if($result=$ibmDatabase->query($query)){
         header("Location:".$_SERVER['HTTP_REFERER']);
      }else{
         echo "Error closing open batch with id:".$_POST['batchID'];
      }
   }
   
   if(isset($_POST['createbatch'])){
      $reference=$_POST['reference'];
      $timestamp=date("Y-m-d-His");
      $query="INSERT INTO ibm_batch_history SET ibm_reference='$reference',ibm_batch_complete_date='$timestamp',batch_open=1,batch_locked=0";
      if($result=$ibmDatabase->query($query)){
         header("Location:".$_SERVER['HTTP_REFERER']);
      }else{
         echo "Error creating new batch!";
      }
   }
   
   if(isset($_POST['addrecords'])){
      $batchRecords=implode(",",$_POST['recordID']);
      $sysTypeID=$_POST['sysTypeID'];
      
      $query="SELECT ibm_batch_id,ibm_reference FROM ibm_batch_history WHERE batch_open=1";
      if($result=$ibmDatabase->query($query)){
         list($batchID,$reference)=$result->fetch_row();
      }else{
         die("Error getting current open batch id or reference");
      }
      
      if(empty($batchRecords)){
         echo "<font size=\"5\" color=\"red\"><b>ERROR! Cannot complete batch without any records selected!</b></font><br><br>\n";      
      }else{
         //add records to batch
         $query="UPDATE ibm_records_batch SET ibm_batch_id=$batchID WHERE ibm_record_id IN ($batchRecords)";
         $ibmDatabase->query($query);
         
         //get date of first fulfilled system
         $query="SELECT ibm_fulfill_date 
                     FROM ibm_records_batch 
                     WHERE ibm_batch_id=$batchID 
                     ORDER BY ibm_fulfill_date LIMIT 1";
         $result=$ibmDatabase->query($query);
         list($startDate)=$result->fetch_row();
         
         //get date of last fulfilled system
         $query="SELECT ibm_fulfill_date 
                     FROM ibm_records_batch 
                     WHERE ibm_batch_id=$batchID 
                     ORDER BY ibm_fulfill_date DESC LIMIT 1";
         $result=$ibmDatabase->query($query);
         list($endDate)=$result->fetch_row();
         
         //get a list of all the records
         $query="SELECT ibm_record_id,ibm_serial_number FROM ibm_records_batch WHERE ibm_batch_id=$batchID ORDER BY ibm_record_id";
         $results = $ibmDatabase->query($query);
         $numRecords=$results->num_rows;
         
         //update the batch information
         $timestamp=date("Y-m-d-His");
         $batchFilename=preg_replace("/\s/","",$reference);
         $batchFilename=preg_replace("/[^0-9a-zA-Z_.]/","-",$batchFilename)."-batch-$timestamp.csv";
         $query="UPDATE ibm_batch_history SET ibm_start_date='$startDate',
                                 ibm_end_date='$endDate',ibm_download_link='$batchFilename',
                                 ibm_number_of_records=$numRecords,ibm_system_type_id=$sysTypeID
                                 WHERE ibm_batch_id=$batchID";
         $ibmDatabase->query($query);
         
         //write records to csv file
         $fh=fopen("batch/$batchFilename","wt");
         while(list($recordID,$serialNo) = $results->fetch_row()){
            $query="SELECT ibm_macaddress FROM ibm_batch_macaddress WHERE ibm_record_id=$recordID ORDER BY ibm_interface_number";
            $macaddresses=$ibmDatabase->query($query);
            $macaddress=array();
            while($record=$macaddresses->fetch_assoc()){
               $macaddress[]=$record['ibm_macaddress'];
            }
            $recStr=$serialNo.",".implode(",",$macaddress)."\n";
            fwrite($fh,$recStr);            
         }
         fclose($fh);
         
         header("Location:".$_SERVER['HTTP_REFERER']);
      }
   }
?>
