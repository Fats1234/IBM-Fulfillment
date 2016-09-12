<?php

   require_once "dbconfig.php";
   require_once "HTML/Table.php";
   require_once "functions.php";
   
   include "header.php";
   
   $ibmDatabase = new mysqli($dbhost,$dbuser,$dbpass,$database);
   
   if(isset($_GET['success'])){
      //echo $_SERVER['HTTP_REFERER'];
   }
   
   //check to see if there are any open batches
   $query="SElECT ibm_batch_id,ibm_number_of_records,ibm_system_type_id
                     FROM ibm_batch_history
                     WHERE batch_open=1
                     AND batch_locked=0
                     LIMIT 1";
   $result=$ibmDatabase->query($query);
   list($batchID,$numRecords,$sysTypeID)=$result->fetch_row();
   
   //if there is an open batch
   if($result->num_rows){
      //display the current batch's information
      echo genCurrentBatchInfo($ibmDatabase,$batchID);
      
      //allow user to limit the amount of records shown per system type
      echo "<font size=\"4\"><b>Limit the amount of records shown:</b></font><br>\n";
      echo startForm("complete.php","GET")."\n";
      echo genTextBox("limit")."\n";
      echo genButton();
      echo endForm();
      echo "<br><br>\n";
      
      $limit=$_GET['limit'];
         if(!preg_match("/^[0-9]+$/",$limit)){
         $limit=FALSE;
      }
      if($numRecords){
         echo genCompletionTable($ibmDatabase,$sysTypeID);
      }else{
         //Create a table for each system type
         $query="SElECT ibm_system_type_id FROM ibm_system_type ORDER BY ibm_system_type_id";
         $results=$ibmDatabase->query($query);
         while(list($sysTypeID)=$results->fetch_row()){
            echo genCompletionTable($ibmDatabase,$sysTypeID,$limit);
            echo "<br><br>\n";
         }
      }
   }else{
      //There is no open batch so we list all closed batches as option
      $query="SELECT ibm_number_of_records FROM ibm_batch_history WHERE ibm_number_of_records=0 AND batch_locked=0";
      $result=$ibmDatabase->query($query);
      if(!$result->num_rows){
         //If no batch with 0 records exist then we allow user to create a new batch
         echo genCreateNewBatch();
         echo "<br>\n";
      }
      echo genBatchTable($ibmDatabase);
   }
   
   function genCurrentBatchInfo($database,$batchID){
      $query="SELECT ibm_start_date,ibm_end_date,ibm_batch_complete_date,
                           ibm_reference,ibm_number_of_records,ibm_system_type_id
                           FROM ibm_batch_history
                           WHERE ibm_batch_id=$batchID";
                           
      $result=$database->query($query);
      list($startDate,$endDate,$createDate,$reference,$numRecords,$sysTypeID)=$result->fetch_row();
      
      $query="SELECT ibm_system_type_name FROM ibm_system_type WHERE ibm_system_type_id=$sysTypeID";
      $result = $database->query($query);
      list($sysType) = $result->fetch_row();
      
      $batchInfoTable = new HTML_TABLE();
      
      $row=0;
      $batchInfoTable->setCellContents($row,0,"<b>Batch ID:</b>");
      $batchInfoTable->setCellContents($row++,1,$batchID);
      $batchInfoTable->setCellContents($row,0,"<b>Batch Reference:</b>");
      $batchInfoTable->setCellContents($row,1,$reference);
      $batchInfoTable->setCellContents($row++,2,startForm("modify_batch.php","POST").genHidden("batchID",$batchID).genTextBox("reference").
                                          genButton("modref","modref","Modify Reference").endForm());
      if($numRecords){
         $batchInfoTable->setCellContents($row,0,"<b>Batch System Type:</b>");
         $batchInfoTable->setCellContents($row++,1,$sysType);
      }
      $batchInfoTable->setCellContents($row,0,"<b>Batch Creation Date:<b>");
      $batchInfoTable->setCellContents($row++,1,$createDate);
      $batchInfoTable->setCellContents($row,0,"<b>Number of Records:</b>");
      $batchInfoTable->setCellContents($row,1,$numRecords);
      if($numRecords){
         $batchInfoTable->setCellContents($row++,2,"<a href='viewbatch.php?batchID=$batchID'>View Records</a>");
         $batchInfoTable->setCellContents($row,0,"<b>Fulfillment Date Start:</b>");
         $batchInfoTable->setCellContents($row++,1,$startDate);
         $batchInfoTable->setCellContents($row,0,"<b>Fulfillment Date End:</b>");
         $batchInfoTable->setCellContents($row++,1,$endDate);
      }else{
         $row++;
      }
      $batchInfoTable->setCellContents($row++,0,startForm("modify_batch.php","POST").genHidden("batchID",$batchID).
                                          genButton("closebatch","closebatch","Close Current Batch").endForm());
      
      $firstColAtt=array('width'=>'30%');
      $batchInfoTable->updateColAttributes(0,$firstColAtt);
      $batchInfoTable->updateColAttributes(1,$firstColAtt);
      
      $returnStr = "<font size='4'><b><u>Current Batch Information</u></b></font><br>\n";
      $returnStr .= $batchInfoTable->toHTML();
      $returnStr .= "<br><br>\n";
      
      return $returnStr;
      
   }
   
   function genCreateNewBatch(){
      $returnStr = "<font size='4'><b>Create a New Batch:</b></font><br>\n";
      $returnStr .= "Enter a Reference for New Batch:";
      $returnStr .= startForm("modify_batch.php","POST");
      $returnStr .= genTextBox("reference");
      $returnStr .= genButton("createbatch","createbatch","Open New Batch");
      $returnStr .= endForm();
      $returnStr .= "<br>\n";
      
      return $returnStr;
   }
   
   function genBatchTable($database){
      $attrs=array('border' => '1');
      $batchTable = new HTML_TABLE($attrs);
      
      $query="SELECT ibm_batch_id,ibm_reference,ibm_number_of_records,ibm_system_type_id,batch_open,batch_locked
                  FROM ibm_batch_history ORDER BY ibm_batch_id DESC";
      $results=$database->query($query);
      
      $batchTable->setHeaderContents(0,0,"System Type");
      $batchTable->setHeaderContents(0,1,"Number of Records");
      $batchTable->setHeaderContents(0,2,"Batch Reference");
      $batchTable->setHeaderContents(0,3,"Re-Open Batch");
      
      $row=1;
      while($batchRecord = $results->fetch_assoc()){
         //get system type name
         $query="SELECT ibm_system_type_name FROM ibm_system_type WHERE ibm_system_type_id=".$batchRecord['ibm_system_type_id'];
         $result=$database->query($query);
         list($systemTypeName)=$result->fetch_row();
         
         $batchID=$batchRecord['ibm_batch_id'];
         
         $batchTable->setCellContents($row,0,$systemTypeName);
         $batchTable->setCellContents($row,1,"<a href='viewbatch.php?batchID=$batchID'>".$batchRecord['ibm_number_of_records']."</a>");
         $batchTable->setCellContents($row,2,$batchRecord['ibm_reference']);
         if($batchRecord['batch_locked']){
            $batchTable->setCellContents($row,3,"<b>Locked</b>");
         }else{
            $batchTable->setCellContents($row,3,startForm("modify_batch.php","POST").
                                                genHidden("batchID",$batchRecord['ibm_batch_id']).
                                                genButton("openbatch","openbatch","Open Batch").endForm());
         }
         $row++;
      }
      
      $altAttrs=array('class' => 'alt');
      $batchTable->altRowAttributes(1,null,$altAttrs);
      $attrs = array('align' => 'center');
      $batchTable->updateColAttributes(1,$attrs);
      $batchTable->updateColAttributes(3,$attrs);
      
      $returnStr = "<font size='4'><b>Reopen a Closed Batch</b></font><br>\n";
      $returnStr .= $batchTable->toHTML();
      $returnStr .= "<br>\n";
      
      return $returnStr;
   }
   
   function genCompletionTable($database,$sysTypeID,$limit=0){   
      $query="SELECT ibm_record_id,ibm_serial_number,ibm_fulfill_date 
                  FROM ibm_records_batch 
                  WHERE ibm_batch_id=0 
                  AND ibm_system_type_id=$sysTypeID 
                  AND ibm_record_deleted=0
                  AND ibm_set_number!=0
                  ORDER BY ibm_record_id";
      
      if($limit){
         $query .= " LIMIT $limit";
      }
      
      $recordResults=$database->query($query);
      if(empty($recordResults->num_rows)){
         return;
      }
      
      $attrs=array('border' => '1');
      $batchTable = new HTML_TABLE($attrs);
      
      //set table column headers
      $batchTable->setHeaderContents(0,1,"<input type=\"checkbox\" onclick=\"checkAll(this)\" checked>");
      $batchTable->setHeaderContents(0,2,"Serial Number");
      $batchTable->setHeaderContents(0,3,"Date Fulfillment Completed");
      
      //grab number of macaddresses for each system
      $query="SELECT ibm_system_type_name,num_of_macaddresses FROM ibm_system_type WHERE ibm_system_type_id=$sysTypeID";
      $result=$database->query($query);
      list($sysTypeName,$numInterfaces)=$result->fetch_row();
      for($i=0;$i<$numInterfaces;$i++){
         $batchTable->setHeaderContents(0,$i+4,"MAC Address (eth$i)");
      }
      
      $row=1;
      while($record = $recordResults->fetch_assoc()){
         $batchTable->setCellContents($row,0,$row);
         $batchTable->setCellContents($row,1,genCheckBox("recordID",$record['ibm_record_id']));
         $batchTable->setCellContents($row,2,$record['ibm_serial_number']);
         $batchTable->setCellContents($row,3,$record['ibm_fulfill_date']);
         
         //get mac addresses
         $query="SELECT ibm_macaddress FROM ibm_batch_macaddress WHERE ibm_record_id=".$record['ibm_record_id']." ORDER BY ibm_interface_number";
         $macResults=$database->query($query);         
         
         $col=4;
         while($macaddress=$macResults->fetch_assoc()){
            $batchTable->setCellContents($row,$col++,$macaddress['ibm_macaddress']);
         }
         
         $row++;                  
      }
      
      $altAttrs=array('class' => 'alt');
      $batchTable->setColAttributes(0,array('align'=>'center'));
      $batchTable->altRowAttributes(0,null,$altAttrs);
      
      $returnStr = startForm("modify_batch.php","POST");
      $returnStr .= genHidden("sysTypeID",$sysTypeID);
      $returnStr .= "\n<font size=\"5\"><b>$sysTypeName:</b></font><br>\n";
      $returnStr .= genButton("addrecords","addrecords","Add Selected Systems to Current Open Batch");
      $returnStr .= "<br><br>";
      $returnStr .= $batchTable->toHTML();
      $returnStr .= endForm();
      
      return $returnStr;
   }

   include "footer.php";
   
?>
