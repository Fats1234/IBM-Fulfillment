<?php
   require_once "dbconfig.php";
   
   $ibmDatabase = new mysqli($dbhost,$dbuser,$dbpass,$database);

   if(isset($_POST['modify'])){
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
            
      header("Location:batch.php");
   }

?>
