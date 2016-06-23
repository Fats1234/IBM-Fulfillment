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
   
   function genLabelPrintTable($setNumber){
      $attrs=array('border' => '1');
      $labelPrintTable = new HTML_TABLE($attrs);
      
      $buttonIBMSystem = genButton("print","IBMSystem","Print IBMSystem Label");
      $buttonIBMserialonly = genButton("print","IBMserialonly","Print IBMserialonly Label");
      $buttonIBMmacadd = genButton("print","IBM-macaddress","Print IBM-macaddress Label");
      
      $labelPrintTable->setCellContents(0,0,"<img src=images/IBMsystem.png>");
      $labelPrintTable->setCellContents(0,1,"<img src=images/IBMserial-only.png>");
      $labelPrintTable->setCellContents(0,2,"<img src=images/IBM-mac.png>");
      $labelPrintTable->setCellContents(1,0,$buttonIBMSystem);
      $labelPrintTable->setCellContents(1,1,$buttonIBMserialonly);
      $labelPrintTable->setCellContents(1,2,$buttonIBMmacadd);
      
      $labelPrintTable->setAllAttributes("align=\"center\"");
      
      return $labelPrintTable->toHTML();
      
   }
   
   function genSetTable($database,$setNumber,$searchStr=""){
      if(empty($searchStr)){
         $query="SELECT ibm_record_id, ibm_serial_number, ibm_fulfill_date 
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
         $query="SELECT ibm_record_id, ibm_serial_number, ibm_fulfill_date
                     FROM ibm_records_batch
                     WHERE ibm_serial_number
                     IN ($serialStr)
                     AND ibm_record_deleted=0
                     ORDER BY FIELD(ibm_serial_number, $serialStr)";
                     
         //echo $query;
      }
      //echo $query;
      $records=$database->query($query);
      
      if(!$records->num_rows) return FALSE;
      
      $attrs=array('border' => '1');
      $setTable = new HTML_TABLE($attrs);      
      $setTable->setHeaderContents(0,2,"Serial Number");
      $setTable->setHeaderContents(0,3,"MAC Address (eth0)");
      $setTable->setHeaderContents(0,4,"MAC Address (eth1)");
      $setTable->setHeaderContents(0,5,"MAC Address (eth2)");
      $setTable->setHeaderContents(0,6,"Date Fulfillment Completed");
      $setTable->setHeaderContents(0,0,"Print Record");
      $setTable->setHeaderContents(0,1,"<input type=\"checkbox\" onclick=\"checkAll(this)\" checked>");
      
      $row=1;
      $macaddresses="";
      
      while($record=$records->fetch_assoc()){
         $setTable->setCellContents($row,2,$record['ibm_serial_number']);
         $setTable->setCellContents($row,6,$record['ibm_fulfill_date']);
         $setTable->setCellContents($row,0,"$row");
         $setTable->setCellContents($row,1,genCheckBox("recordID",$record['ibm_record_id']));
         //grab mac addresses from database
         $query="SELECT ibm_macaddress FROM ibm_batch_macaddress WHERE ibm_record_id=".$record['ibm_record_id']." ORDER BY ibm_interface_number";
         //echo $query;
         $macaddresses=$database->query($query);
         
         $macaddress=array();
         while($results=$macaddresses->fetch_assoc()){
            $macaddress[]=$results['ibm_macaddress'];
         }
         
         $setTable->setCellContents($row,3,$macaddress[0]);
         $setTable->setCellContents($row,4,$macaddress[1]);
         $setTable->setCellContents($row,5,$macaddress[2]);
         
         $lastSystemDate=$record['ibm_fulfill_date'];
         $row++;
      }      
      
      $altAttrs=array('class' => 'alt');
      $setTable->setColAttributes(0,array('align'=>'center'));
      $setTable->altRowAttributes(0,null,$altAttrs);
            
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
      
      $returnStr=startForm("print.php","POST","printLabel",TRUE);
      $returnStr.=genHidden("set",$setNumber);
      $returnStr.="<font size=\"5\">Number of Records: <font color=\"green\">$records->num_rows</font></font><br>";
      if(empty($searchStr)) $returnStr.="<font size=\"5\">Date of Last Fulfilled System In This Set: <font color=\"$dateColor\">$lastSystemDate($todayStr)</font></font><br>";
      if(empty($searchStr)) $returnStr.="<font size=\"4\">Completed Set ID: $setNumber</font><br>";
      $returnStr.= "<br>";
      $returnStr.=genLabelPrintTable($setNumber);
      $returnStr.="<br>";
      $returnStr.=$setTable->toHTML();
      $returnStr.=endForm();
      
      return $returnStr;
   }
   
   include "footer.php";
?>