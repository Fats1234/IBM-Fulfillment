<?php
   require_once "dbconfig.php";
   require_once "HTML/Table.php";
   require_once "functions.php";
   
   include "header.php";
   include "printheader.php";
   
   $ibmDatabase = new mysqli($dbhost,$dbuser,$dbpass,$database);
   if(isset($_GET['search'])){
      if(isset($_POST['findSerial'])){
         if($output=genSetTable($ibmDatabase,0,$_POST['serialNumbers'])){
            echo "<font size='5'>The Following Matching Records Were Found:</font><br>";
            echo $output;
         }else{
            echo "<font size='5' color='red'>No Matching Records Found!</font><br>";
         }
      }
      
      echo "<br><br><br><font size='4'>Enter Full Serial Number To Search For (Exmple: I123456):<br>".
               "Partial Serial Numbers Will Not Work.<br>One Serial Number Per Line</font><br>";
      echo startForm('set.php?search=1',POST,'serialSearch');
      echo genTextArea('serialNumbers');
      echo "<br><br>";
      echo genButton('findSerial','findSerial','Serial Number Search');
      echo endForm();
   }else{ 
      if(!empty($currentSet)){
      
         if($currentSet == $lastSet){
            echo "<font size=\"5\">This is the </font><font size=\"5\" color=\"green\"><b><u>Most Recent</u></b></font><font size=\"5\"> Set of Completed Records</font><br>";
         }elseif($currentSet == $firstSet){
            echo "<font size=\"5\">This is the </font><font size=\"5\" color=\"red\"><b><u>Oldest</u></b></font><font size=\"5\"> Set of Completed Records</font><br>";
         }else{
            echo "<font size=\"5\">This is an </font><font size=\"5\" color=\"red\"><b><u>Old</u></b></font><font size=\"5\"> Set of Completed Records</font><br>";
         }
         echo genSetTable($ibmDatabase,$currentSet);
      }else{
         echo "<font size=\"5\">No Records Found!</font>";
      }
   }
   
   function genLabelPrintTable($setNumber,$database,$systemType=1){
      $attrs=array('border' => '1');
      $labelPrintTable = new HTML_TABLE($attrs);
      
      //get all possible labels for system type
      $query="SELECT ibm_label_name, ibm_label_image FROM ibm_fulfillment_labels WHERE ibm_system_type_id=$systemType";
      $result=$database->query($query);
      
      $column=0;
      while($fulfillLabel=$result->fetch_assoc()){
         $labelPrintTable->setCellContents(0,$column,"<img src=images/".$fulfillLabel['ibm_label_image'].">");
         $labelPrintTable->setCellContents(1,$column,genButton("print",$fulfillLabel['ibm_label_name'],"Print ".$fulfillLabel['ibm_label_name']." Label"));
         $column++;
      }
      
      $labelPrintTable->setAllAttributes("align=\"center\"");
      
      return $labelPrintTable->toHTML()."<br>";
      
   }
   
   function genSetTable($database,$setNumber,$searchStr=""){
      if(empty($searchStr)){
         $query="SELECT ibm_record_id, ibm_serial_number, ibm_fulfill_date, ibm_system_type_id 
                     FROM ibm_records_batch 
                     WHERE ibm_record_deleted=0 
                     AND ibm_set_number=$setNumber 
                     ORDER BY ibm_record_id";
      }else{
         $serialArray=explode("\n",str_replace("\r","",trim($searchStr)));
         foreach($serialArray as $index => $serialNo){
            if(!empty($serialNo)){
               $serialArray[$index]="'$serialNo'";
            }else{
               unset($serialArray[$index]);
            }
         }
         $serialStr=implode(',',$serialArray);
         $query="SELECT ibm_record_id, ibm_serial_number, ibm_fulfill_date, ibm_system_type_id
                     FROM ibm_records_batch
                     WHERE ibm_serial_number
                     IN ($serialStr)
                     AND ibm_record_deleted=0
                     ORDER BY ibm_system_type_id, FIELD(ibm_serial_number, $serialStr)";
                     
         //echo $query;
      }
      //echo $query;
      $records=$database->query($query);
      
      if(!$records->num_rows) return FALSE;
      
      $row=1;
      $macaddresses="";      
      $prevSysTypeID=0;
      $returnStr="";
      
      while($record=$records->fetch_assoc()){
         if($record['ibm_system_type_id'] != $prevSysTypeID){
            if($prevSysTypeID != 0){
               //set MAC Address Header column
               for($i=0;$i<$maxMAC;$i++){
                  $setTable->setHeaderContents(0,$i+4,"MAC Address (eth$i)");
               }
               $altAttrs=array('class' => 'alt');
               $setTable->setColAttributes(0,array('align'=>'center'));
               $setTable->altRowAttributes(0,null,$altAttrs);
               $returnStr .= $setTable->toHTML();
               $returnStr .= endForm();
               $row=1;
            }
            $returnStr .= startForm("print.php","POST","printLabel",TRUE);            
            $returnStr .= genLabelPrintTable($setNumber,$database,$record['ibm_system_type_id']);
            $attrs=array('border' => '1');
            $setTable = new HTML_TABLE($attrs);
            $setTable->setHeaderContents(0,0,"Print Record");
            $setTable->setHeaderContents(0,1,"<input type=\"checkbox\" onclick=\"checkAll(this)\" checked>");
            $setTable->setHeaderContents(0,2,"Serial Number");
            $setTable->setHeaderContents(0,3,"Date Fulfillment Completed");
         } 
         $setTable->setCellContents($row,0,"$row");
         $setTable->setCellContents($row,1,genCheckBox("recordID",$record['ibm_record_id']));
         $setTable->setCellContents($row,2,$record['ibm_serial_number']);
         $setTable->setCellContents($row,3,$record['ibm_fulfill_date']);
         
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
            $setTable->setCellContents($row,$i+4,$macaddress[$i]);
         }
         
         $lastSystemDate=$record['ibm_fulfill_date'];
         $row++;
         $prevSysTypeID=$record['ibm_system_type_id'];
      }      
      
      for($i=0;$i<$maxMAC;$i++){
         $setTable->setHeaderContents(0,$i+4,"MAC Address (eth$i)");
      }
      $altAttrs=array('class' => 'alt');
      $setTable->setColAttributes(0,array('align'=>'center'));
      $setTable->altRowAttributes(0,null,$altAttrs);
      $returnStr .= $setTable->toHTML();
      $returnStr .= endForm();
            
      $currentDate=date("Y-m-d");
      $lastSystemDate=substr($lastSystemDate,0,10);
      if(strcmp($currentDate,$lastSystemDate)==0){
         //date is today, color is green
         $dateColor="green";
         $todayStr="Today";
      }else{
         //date is not today, color is red
         $dateColor="red";
         $todayStr="Not Today!";
      }
      
      //$returnStr=startForm("print.php","POST","printLabel",TRUE);
      //$returnStr.=genHidden("set",$setNumber);
      $headerStr="<font size=\"5\">Number of Records: <font color=\"green\">$records->num_rows</font></font><br>";
      if(empty($searchStr)) $headerStr.="<font size=\"5\">Date of Last Fulfilled System In This Set: <font color=\"$dateColor\">$lastSystemDate($todayStr)</font></font><br>";
      if(empty($searchStr)) $headerStr.="<font size=\"4\">Completed Set ID: $setNumber</font><br>";
      $headerStr.= "<br>";
      //$returnStr.=genLabelPrintTable($setNumber,$database);
      //$returnStr.="<br>";
      //$returnStr.=$setTable->toHTML();
      //$returnStr.=endForm();
      
      return $headerStr.$returnStr;
   }
   
   include "footer.php";
?>