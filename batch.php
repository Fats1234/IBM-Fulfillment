<?php

   require_once "dbconfig.php";
   require_once "HTML/Table.php";
   require_once "functions.php";
   
   include "header.php";
   
   $ibmDatabase = new mysqli($dbhost,$dbuser,$dbpass,$database);
   
   echo genBatchTable($ibmDatabase);
   
   function genBatchTable($database){
      $attrs=array('border' => '1');
      $batchTable = new HTML_TABLE($attrs);
      $batchTable->setHeaderContents(0,0,"System Type");
      $batchTable->setHeaderContents(0,1,"Number of Records");
      $batchTable->setHeaderContents(0,2,"Start Date");
      $batchTable->setHeaderContents(0,3,"End Date");
      $batchTable->setHeaderContents(0,4,"CSV Download Link");
      $batchTable->setHeaderContents(0,5,"Reference Note");
      $batchTable->setHeaderContents(0,6,"Modify Reference");
      
      $query="SELECT ibm_batch_id,ibm_start_date,ibm_end_date,ibm_batch_complete_date,
                        ibm_reference,ibm_download_link,ibm_number_of_records,
                        ibm_system_type_id FROM ibm_batch_history WHERE batch_open=0 ORDER BY ibm_batch_id DESC";
      $batchResults=$database->query($query);                  
      
      $row=1;
      while($batch=$batchResults->fetch_assoc()){
         //get system type name
         $query="SELECT ibm_system_type_name FROM ibm_system_type WHERE ibm_system_type_id=".$batch['ibm_system_type_id'];
         $result=$database->query($query);
         $batchID=$batch['ibm_batch_id'];
         list($sysType)=$result->fetch_row();
         $batchTable->setCellContents($row,0,$sysType);
         $batchTable->setCellContents($row,1,"<a href='viewbatch.php?batchID=$batchID'>".$batch['ibm_number_of_records']."</a>");
         $batchTable->setCellContents($row,2,substr($batch['ibm_start_date'],0,10));
         $batchTable->setCellContents($row,3,substr($batch['ibm_end_date'],0,10));
         $batchTable->setCellContents($row,4,"<a href=\"batch/".$batch['ibm_download_link']."\">Batch CSV</a>");
         $batchTable->setCellContents($row,5,$batch['ibm_reference']);
         $batchTable->setCellContents($row,6,startForm("modify_reference.php","POST").genTextBox("reference").
                                          genButton("modify","modify","Modify Reference").genHidden("batchID",$batch['ibm_batch_id']).
                                          endForm());
         
         $row++;
      }
      
      $attrs20 = array('width'=>'20%','align' => 'center');
      $attrs15 = array('width'=>'15%','align' => 'center');
      $attrs10 = array('width'=>'10%','align' => 'center');
      $batchTable->updateColAttributes(0,$attrs10);
      $batchTable->updateColAttributes(1,$attrs10);
      $batchTable->updateColAttributes(2,$attrs10);
      $batchTable->updateColAttributes(3,$attrs10);
      $batchTable->updateColAttributes(4,$attrs15);
      $batchTable->updateColAttributes(5,$attrs20);
   
      $altAttrs=array('class' => 'alt');
      $batchTable->altRowAttributes(1,null,$altAttrs);
      
      return $batchTable->toHTML();
   }
   
   include "footer.php";

?>
