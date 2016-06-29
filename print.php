<?php
   require_once "dbconfig.php";
   require_once "functions.php";
   
   $ibmDatabase = new mysqli($dbhost,$dbuser,$dbpass,$database);
   
   if(isset($_POST['print'])){
      if(empty($_POST['recordID'])) header("Location: http://polyerp01/labels/create.php?label=".$labelName);
      
      //Workaround for IE (button tags in IE do not support the value parameter)
      $labelName=preg_replace("/Print /","",$_POST['print']);
      $labelName=preg_replace("/ Label/","",$labelName);
      
      $searchRecords=implode(",",$_POST['recordID']);
      echo startForm("http://polyerp01/labels/create.php?label=".$labelName,"POST","labelForm")."\n";
      
      //Grab the label id based on the label name and system type id
      $query="SELECT ibm_label_id FROM ibm_fulfillment_labels WHERE ibm_label_name='$labelName' AND ibm_system_type_id=".$_POST['systemTypeID'];
      //echo $query;
      $result=$ibmDatabase->query($query);
      list($labelID)=$result->fetch_row();
      
      //Now grab the fields needed based on the label id
      $query="SELECT ibm_label_field_name, ibm_interface_id FROM ibm_fulfillment_label_fields WHERE ibm_label_id=$labelID";
      //echo $query;
      $fields=$ibmDatabase->query($query);

      while($field=$fields->fetch_assoc()){
         $fieldValues="";
         if(!$field['ibm_interface_id']){
            //serial field when id = 0
            $query = "SELECT ibm_serial_number 
                        FROM ibm_records_batch 
                        WHERE ibm_record_id IN ($searchRecords) 
                        ORDER BY FIELD(ibm_record_id,$searchRecords)";
            //echo $query;            
            $results=$ibmDatabase->query($query);            
            while($value=$results->fetch_assoc()){
               $fieldValues .= $value['ibm_serial_number']."\n";
            }
         }else{
            $query = "SELECT ibm_macaddress 
                        FROM ibm_batch_macaddress 
                        WHERE ibm_record_id IN ($searchRecords) 
                        AND ibm_interface_number=".$field['ibm_interface_id']." 
                        ORDER BY FIELD(ibm_record_id,$searchRecords)";
            //echo $query;
            $results=$ibmDatabase->query($query);
            while($value=$results->fetch_assoc()){
               $fieldValues .= $value['ibm_macaddress']."\n";
            }
            $fieldValues = preg_replace("/:/","",$fieldValues);
         }
         echo genHidden($field['ibm_label_field_name'],$fieldValues);
      }      
      echo endForm();
      
      //auto submit form via javascript
      echo "<script type=\"text/javascript\">\n";
      echo "document.getElementById(\"labelForm\").submit();\n";
      echo "</script>\n";
      
   }
?>