<?php

   require_once "dbconfig.php";
   require_once "HTML/Table.php";
   require_once "functions.php";
   
   include "header.php";
   
   $ibmDatabase = new mysqli($dbhost,$dbuser,$dbpass,$database);

   if(isset($_GET['batchID'])){
      $batchID=$_GET['batchID'];
      
      $query="SELECT batch_open,batch_locked,ibm_reference FROM ibm_batch_history WHERE ibm_batch_id=$batchID";
      $result=$ibmDatabase->query($query);
      list($batchOpened,$batchLocked,$reference)=$result->fetch_row();
      
      $query = "SELECT ibm_record_id,ibm_serial_number,ibm_fulfill_date FROM ibm_records_batch WHERE ibm_batch_id=$batchID AND ibm_record_deleted=0";
      //echo $query;
      $result=$ibmDatabase->query($query);
      
      //set up a table to display records in batch
      $attrs=array('border' => '1');
      $batchTable = new HTML_TABLE($attrs);
      $col=0;
      $batchTable->setHeaderContents(0,$col++,"Rec. No");
      //if batch is opened and not locked show an extra row to allow removing records
      if($batchOpened && !$batchLocked){
         $batchTable->setHeaderContents(0,$col++,"Remove Record");
      }
      $batchTable->setHeaderContents(0,$col++,"Serial Number");
      $batchTable->setHeaderContents(0,$col++,"Date Fulfillment Completed");
       
      $row=1;
      while($record=$result->fetch_assoc()){
         //get macaddress for each record
         $query="SELECT ibm_macaddress FROM ibm_batch_macaddress WHERE ibm_record_id=".$record['ibm_record_id']." ORDER BY ibm_interface_number";
         //echo $query;
         $macaddresses=$ibmDatabase->query($query);
         $macaddress=array();
         
         $col=0;         
         $batchTable->setCellContents($row,$col++,$row);
         if($batchOpened && !$batchLocked){
            $batchTable->setCellContents($row,$col++,startForm("modify_batch.php","POST").
                                                      genHidden("recordID",$record['ibm_record_id']).
                                                      genButton("remRecord","remRecord","Remove Record").
                                                      endForm());
         }
         $batchTable->setCellContents($row,$col++,$record['ibm_serial_number']);
         $batchTable->setCellContents($row,$col++,$record['ibm_fulfill_date']);
         
         while($results=$macaddresses->fetch_assoc()){
            $macaddress[]=$results['ibm_macaddress'];
         }
         //we want to find the biggest amount of mac addresses in order to set the header columns
         $maxMAC=max($maxMAC,count($macaddress));         
         for($i=0;$i<count($macaddress);$i++){
            $batchTable->setCellContents($row,$i+$col,$macaddress[$i]);
         }
         
         $row++;
      }
      
      for($i=0;$i<$maxMAC;$i++){
         $batchTable->setHeaderContents(0,$i+$col,"MAC Address (eth$i)");
      }      
      
      $altAttrs=array('class' => 'alt');
      $batchTable->setColAttributes(0,array('align'=>'center'));
      $batchTable->altRowAttributes(0,null,$altAttrs);
      
      echo "<font size='4'><b>Batch ID: </b>$batchID</font><br>\n";
      echo "<font size='4'><b>Batch Reference: </b>$reference</font><br><br>\n";
      echo "<font size='4'><b>This Batch Contains The Following Records:</b></font><br>\n";      
      echo $batchTable->toHTML();
   }
   
   include "footer.php";

?>