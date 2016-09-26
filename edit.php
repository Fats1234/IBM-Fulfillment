<?php
   require_once "dbconfig.php";
   require_once "HTML/Table.php";
   require_once "functions.php";
   
   include "header.php";

   $ibmDatabase = new mysqli($dbhost,$dbuser,$dbpass,$database);
   echo startForm("edit.php","POST");
   if(isset($_POST['delete'])){
      $recordID=$_POST['recordID'];
      $query="UPDATE ibm_records_batch SET ibm_record_deleted=1 WHERE ibm_record_id=$recordID";
      if($ibmDatabase->query($query)){
         echo "<font size='4' color='green'>Successfully Deleted Record ID: $recordID</font><br>";
      }else{
         echo "<font size='4' color='red'>Error Deleting Record ID: $recordID</font><br>";
      };
   }
   
   if(isset($_POST['undelete'])){
      $recordID=$_POST['recordID'];
      $query="UPDATE ibm_records_batch SET ibm_record_deleted=0 WHERE ibm_record_id=$recordID";
      if($ibmDatabase->query($query)){
         echo "<font size='4' color='green'>Successfully Restored Record ID: $recordID</font><br>";
      }else{
         echo "<font size='4' color='red'>Error Restoring Record ID: $recordID</font><br>";
      }
   }
   
   if(isset($_POST['search'])){
      $searchString = $_POST['macOrSerial'];
      if($resultsTable = genRecordsTable($ibmDatabase,0,$searchString)){
         echo "<font size=\"5\" color=\"green\">The following Records were found matching \"$searchString\"</font><br><br><br>";
         echo $resultsTable;
         echo "<br><br><br>";
      }else{
         echo "<font size=\"5\" color=\"red\">No Records found that match \"$searchString\"</font><br><br><br>";
      }
   }

   $searchTable = new HTML_Table();
   $searchTable->setHeaderContents(0,0,"MAC/Serial:");
   $searchTable->setCellContents(1,0,genTextBox("macOrSerial"));
   $searchTable->setCellContents(1,1,genButton("search","search","Find Record"));
   $searchTable->updateColAttributes(0,$attrs);
   
   echo "<font size=\"4\"><b>Enter a partial or full Serial Number/MAC Address (Example: 00e066554321)</b></font><br>";
   echo $searchTable->toHTML();
   echo endForm();
   echo "<br><br><br>";
   
   function genRecordsTable($database,$set=0,$searchStr=""){
      if(empty($searchStr)){
         return;
      }
      $query="SELECT ibm_records_batch.ibm_record_id, ibm_records_batch.ibm_record_deleted
                  FROM ibm_records_batch INNER JOIN ibm_batch_macaddress 
                  ON ibm_records_batch.ibm_record_id = ibm_batch_macaddress.ibm_record_id
                  WHERE REPLACE(ibm_batch_macaddress.ibm_macaddress,':','') LIKE '%$searchStr%' 
                  OR ibm_records_batch.ibm_serial_number LIKE '%$searchStr%'
                  GROUP BY ibm_records_batch.ibm_record_id
                  LIMIT 200";      
      //echo $query;
      $searchResults=$database->query($query);
      
      $attrs = array('border' => '1');
      $recordTable = new HTML_Table($attrs);
      
      $recordTable->setHeaderContents(0,0,"Serial Number");
      $recordTable->setHeaderContents(0,1,"MAC Address(eth0)");
      $recordTable->setHeaderContents(0,2,"MAC Address(eth1)");
      $recordTable->setHeaderContents(0,3,"MAC Address(eth2)");
      $recordTable->setHeaderContents(0,4,"Fulfillment Date");
      $recordTable->setHeaderContents(0,5,"Delete/Undelete");
      
      $row=1;
      
      while($record=$searchResults->fetch_assoc()){
         $recordID=$record['ibm_record_id'];
         $query="SELECT ibm_records_batch.ibm_serial_number,ibm_records_batch.ibm_fulfill_date,ibm_records_batch.ibm_record_deleted,
                     GROUP_CONCAT(ibm_batch_macaddress.ibm_macaddress SEPARATOR ',') AS ibm_macaddresses
                     FROM ibm_records_batch INNER JOIN ibm_batch_macaddress                     
                     ON ibm_records_batch.ibm_record_id = ibm_batch_macaddress.ibm_record_id
                     WHERE ibm_records_batch.ibm_record_id='$recordID'
                     GROUP BY ibm_records_batch.ibm_serial_number";
         
         $recordResult=$database->query($query);
         $recordValuesByID=$recordResult->fetch_assoc();
         
         $macaddresses=explode(",",$recordValuesByID['ibm_macaddresses']);
         
         $recordTable->setCellContents($row,0,$recordValuesByID['ibm_serial_number']);         
         $recordTable->setCellContents($row,1,$macaddresses[0]);
         $recordTable->setCellContents($row,2,$macaddresses[1]);
         $recordTable->setCellContents($row,3,$macaddresses[2]);
         $recordTable->setCellContents($row,4,$recordValuesByID['ibm_fulfill_date']);
         if($record['ibm_record_deleted']){
            $recordTable->setCellContents($row,5,startForm("edit.php","POST").genHidden("recordID",$recordID).genButton("undelete","undelete","Un-Delete").endForm());
         }else{
            $recordTable->setCellContents($row,5,startForm("edit.php","POST").genHidden("recordID",$recordID).genButton("delete","delete","Delete").endForm());
         }
         
         $row++;

      }
      
      $attrs = array('width'=>'200px','align' => 'center');
      $recordTable->updateColAttributes(5,$attrs);
      $altAttrs=array('class' => 'alt');
      $recordTable->altRowAttributes(1,null,$altAttrs);
      
      if($searchResults->num_rows){
         return $recordTable->toHTML();
      }else{
         return false;
      }
   
   }
   
   include "footer.php";
   
?>