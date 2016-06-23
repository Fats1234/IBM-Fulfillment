<?php
   $ibmDatabase = new mysqli($dbhost,$dbuser,$dbpass,$database);
   
   $attrs = array('width' => '100%','border' => '1');
   $setTable = new HTML_Table($attrs);
   //first set, previous set, next set, last set, unprinted
   //set the first set of the batch
   $query="SELECT ibm_set_number FROM ibm_records_batch WHERE ibm_set_number > 0 ORDER BY ibm_set_number LIMIT 1";
   $result=$ibmDatabase->query($query);
   if($result) list($firstSet)=$result->fetch_row();
   
   //set the last set of the batch
   $query="SELECT ibm_set_number FROM ibm_records_batch WHERE ibm_set_number > 0 ORDER BY ibm_set_number DESC LIMIT 1";
   $result=$ibmDatabase->query($query);
   if($result) list($lastSet)=$result->fetch_row();
   
   //set the curret set of the batch as defined by the user
   if(isset($_GET['set'])){
      $currentSet=$_GET['set'];
      $query="SELECT ibm_set_number FROM ibm_records_batch WHERE ibm_set_number=$currentSet LIMIT 1";
      $result=$ibmDatabase->query($query);
      $nextSet=$currentSet+1;
      if(empty($result->num_rows)) $currentSet=$lastSet;
   }else{
      $currentSet=$lastSet;
   }
   
   //set previous set of the batch
   $query="SELECT ibm_set_number FROM ibm_records_batch WHERE ibm_set_number < $currentSet AND ibm_set_number > 0 ORDER BY ibm_set_number DESC LIMIT 1";
   $result=$ibmDatabase->query($query);
   if($result) list($prevSet)=$result->fetch_row();
   
   //set next set of the batch
   $query="SELECT ibm_set_number FROM ibm_records_batch WHERE ibm_set_number > $currentSet LIMIT 1";
   $result=$ibmDatabase->query($query);
   if($result) list($nextSet)=$result->fetch_row();
   
   $firstSetHeadStr="<a href=\"set.php?set=$firstSet\">Oldest Set</a>";
   if(empty($firstSet)) $firstSetHeadStr="Oldest Set";
   
   $prevSetHeadStr="<a href=\"set.php?set=$prevSet\">Previous Set</a>";
   if(empty($prevSet)) $prevSetHeadStr="Previous Set";
   
   $nextSetHeadStr="<a href=\"set.php?set=$nextSet\">Next Set</a>";
   if(empty($nextSet)) $nextSetHeadStr="Next Set";
   
   $lastSetHeadStr="<a href=\"set.php?set=$lastSet\">Most Recent Set</a>";
   if(empty($lastSet)) $lastSetHeadStr="Most Recent Set"; 
   
   $setTable->setHeaderContents(0,0,$firstSetHeadStr);
   $setTable->setHeaderContents(0,1,$prevSetHeadStr);   
   $setTable->setHeaderContents(0,2,$nextSetHeadStr);
   $setTable->setHeaderContents(0,3,$lastSetHeadStr);
   $setTable->setHeaderContents(0,4,"<a href=\"set.php?search=1\">Serial Search</a>");
   
   $attrs = array('align' => 'center');
   $setTable->setAllAttributes($attrs);
   
   $attrs = array('width'=>'20%');
   $setTable->updateColAttributes(0,$attrs);
   $setTable->updateColAttributes(1,$attrs);
   $setTable->updateColAttributes(2,$attrs);
   $setTable->updateColAttributes(3,$attrs);
   $setTable->updateColAttributes(4,$attrs);
   
   echo $setTable->toHTML();
   echo "<br><br>\n";
?>