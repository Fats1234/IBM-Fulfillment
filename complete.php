<?php

   require_once "dbconfig.php";
   require_once "HTML/Table.php";
   require_once "functions.php";
   
   include "header.php";
   
   $ibmDatabase = new mysqli($dbhost,$dbuser,$dbpass,$database);
   
   if(isset($_POST['complete'])){
      $batchRecords=implode(",",$_POST['recordID']);
      $numRecords=count($_POST['recordID']);
      $reference=$_POST['reference'];
      $sysTypeID=$_POST['sysTypeID'];
      
      if(empty($batchRecords)){
         echo "<font size=\"5\" color=\"red\"><b>ERROR! Cannot complete batch without any records selected!</b></font><br><br>\n";
      }elseif(empty($reference)){
         echo "<font size=\"5\" color=\"red\"><b>ERROR! Reference field cannot be empty!</b></font><br><br>\n";
      }else{
         $query="SELECT ibm_fulfill_date 
                     FROM ibm_records_batch 
                     WHERE ibm_record_id IN ($batchRecords) 
                     ORDER BY ibm_fulfill_date LIMIT 1";
         $result=$ibmDatabase->query($query);
         list($startDate)=$result->fetch_row();
         
         $query="SELECT ibm_fulfill_date 
                     FROM ibm_records_batch 
                     WHERE ibm_record_id IN ($batchRecords) 
                     ORDER BY ibm_fulfill_date DESC LIMIT 1";
         $result=$ibmDatabase->query($query);
         list($endDate)=$result->fetch_row();
         
         $completeDate=date("Y-m-d-His");
         $batchFilename=preg_replace("/\s/","",$reference);
         $batchFilename=preg_replace("/[^0-9a-zA-Z_.]/","-",$batchFilename)."-batch-$completeDate.csv";
         $query="INSERT INTO ibm_batch_history SET ibm_start_date='$startDate',
                                 ibm_end_date='$endDate',ibm_batch_complete_date='$completeDate',
                                 ibm_reference='$reference',ibm_download_link='$batchFilename',
                                 ibm_number_of_records=$numRecords,ibm_system_type_id=$sysTypeID";
         $ibmDatabase->query($query);
         $batchID=$ibmDatabase->insert_id;
         
         $query="UPDATE ibm_records_batch SET ibm_batch_id=$batchID WHERE ibm_record_id IN ($batchRecords)";
         $ibmDatabase->query($query);
         
         $query="SELECT ibm_record_id,ibm_serial_number FROM ibm_records_batch WHERE ibm_record_id IN ($batchRecords) ORDER BY ibm_record_id";
         $results = $ibmDatabase->query($query);
         
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
         
         echo "<font size=\"5\" color=\"green\"><b>Batch Completed Successfully!</b></font><br><br>\n";
      }
      
   }
   
   echo "<font size=\"4\"><b>Limit the amount of records shown:</b></font><br>\n";
   echo startForm("complete.php","GET")."\n";
   echo genTextBox("limit")."\n";
   echo genButton();
   echo endForm();
   echo "<br><br>\n";
   
   //Create a table for each system type
   $query="SElECT ibm_system_type_id FROM ibm_system_type ORDER BY ibm_system_type_id";
   $results=$ibmDatabase->query($query);
   while(list($sysTypeID)=$results->fetch_row()){
      $limit=$_GET['limit'];
      if(!preg_match("/^[0-9]+$/",$limit)){
         $limit=FALSE;
      }
      echo genCompletionTable($ibmDatabase,$sysTypeID,$limit);
      echo "<br><br>\n";
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
      
      $returnStr = startForm("complete.php","POST");
      $returnStr .= genHidden("sysTypeID",$sysTypeID);
      $returnStr .= "\n<font size=\"5\"><b>$sysTypeName:</b></font><br><br>\n";
      $returnStr .= "Enter a reference number for this batch:<br>\n";
      $returnStr .= genTextBox("reference");
      $returnStr .= genButton("complete","complete","Complete Batch");
      $returnStr .= "<br><br>";
      $returnStr .= $batchTable->toHTML();
      $returnStr .= endForm();
      
      return $returnStr;
   }

   include "footer.php";
   
?>
