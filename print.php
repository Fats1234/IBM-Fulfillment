<?php
   require_once "dbconfig.php";
   require_once "functions.php";
   
   $ibmDatabase = new mysqli($dbhost,$dbuser,$dbpass,$database);
   
   if(isset($_POST['print'])){
      //Workaround for IE (button tags in IE do not support the value parameter)
      $labelName=preg_replace("/Print /","",$_POST['print']);
      $labelName=preg_replace("/ Label/","",$labelName);
      
      //get a list of serial numbers      
      if(empty($_POST['recordID'])) header("Location: http://polyerp01/labels/create.php?label=".$labelName);
      $searchRecords=implode(",",$_POST['recordID']);
      $query = "SELECT ibm_record_id,ibm_serial_number FROM ibm_records_batch WHERE ibm_record_id IN ($searchRecords) ORDER BY FIELD(ibm_record_id,$searchRecords)";
      $records = $ibmDatabase->query($query);
      
      $serialNumbers="";
      $macadds1="";
      $macadds2="";
      $macadds3="";
      while($record=$records->fetch_assoc()){
         $serialNumbers .= $record['ibm_serial_number']."\n";
         
         //get first macaddress of record
         $query = "SELECT ibm_macaddress FROM ibm_batch_macaddress WHERE ibm_interface_number=0 AND ibm_record_id=".$record['ibm_record_id'];
         $results = $ibmDatabase->query($query);
         $result = $results->fetch_assoc();
         $macadds1 .= $result['ibm_macaddress']."\n";
         
         $query = "SELECT ibm_macaddress FROM ibm_batch_macaddress WHERE ibm_interface_number=1 AND ibm_record_id=".$record['ibm_record_id'];
         $results = $ibmDatabase->query($query);
         $result = $results->fetch_assoc();
         $macadds2 .= $result['ibm_macaddress']."\n";
         
         $query = "SELECT ibm_macaddress FROM ibm_batch_macaddress WHERE ibm_interface_number=2 AND ibm_record_id=".$record['ibm_record_id'];
         $results = $ibmDatabase->query($query);
         $result = $results->fetch_assoc();
         $macadds3 .= $result['ibm_macaddress']."\n";
      }
      
      //remove colons for printing
      $macadds1=preg_replace("/:/","",$macadds1);
      $macadds2=preg_replace("/:/","",$macadds2);
      $macadds3=preg_replace("/:/","",$macadds3);
      
      //generate form and submit form via javascript;
      echo startForm("http://polyerp01/labels/create.php?label=".$labelName,"POST","labelForm")."\n";
      echo genHidden("serial",$serialNumbers)."\n";
      echo genHidden("serialno",$serialNumbers)."\n";
      echo genHidden("mac1",$macadds1)."\n";
      echo genHidden("mac2",$macadds2)."\n";
      echo genHidden("mac3",$macadds3)."\n";
      echo endForm();
      
      //auto submit form via javascript
      echo "<script type=\"text/javascript\">\n";
      echo "document.getElementById(\"labelForm\").submit();\n";
      echo "</script>\n";
      
   }
?>