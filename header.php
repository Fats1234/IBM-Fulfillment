<?php
   require_once "HTML/Table.php";
   
   echo "<html>\n";
   echo "   <head>\n";
   echo "      <title>IBM System Fulfillment</title>\n";
   echo "      <h1 align='center'>IBM Fulfillment Management</h1>";
   echo "   </head>\n";
  
   echo "   <body>\n";

   echo "   <style type=\"text/css\">\n";
   echo "      body {background:#FFF3F3;}";
   echo "      form, table {margin:0px; padding:0px;}\n";
   echo "      table {border-collapse: collapse;}\n";
   echo "      u {text-decoration: underline;}\n";
   echo "      b {text-decoration: bold;}\n";
   echo "      td.alt {background:#D8F6CE;}\n";
   echo "      td.altprint{background:#D8F6CE; width:150px;align:right;}\n";
   echo "      td.print{width:150px;align:right;}\n";
   echo "      td.printheader{align:center;}\n";
   echo "      td.incomplete{background:#F78181;}";
   echo "      td.complete{background:#D8F6CE;}";
   echo "   </style>\n";
   
   echo "   <script type=\"text/javascript\" src=\"js/functions.js\"></script>\n";
   
   $attrs = array('width' => '100%','border' => '1');
   $taskTable=new HTML_Table($attrs);
   
   $taskTable->setHeaderContents(0,0,"<a href=\"batch.php\">Batch History</a>");
   $taskTable->setHeaderContents(0,1,"<a href=\"complete.php\">Batch Completion</a>");
   $taskTable->setHeaderContents(0,2,"<a href=\"edit.php\">Edit Records</a>");      
   $taskTable->setHeaderContents(0,3,"<a href=\"set.php\">Set History/Print</a>");
   $taskTable->setHeaderContents(0,4,"<a href=\"incomplete.php\">Incomplete Set</a>");
   
   $attrs = array('align' => 'center');
   $taskTable->setAllAttributes($attrs);
   
   $attrs = array('width'=>'20%');
   $taskTable->updateColAttributes(0,$attrs);
   $taskTable->updateColAttributes(1,$attrs);
   $taskTable->updateColAttributes(2,$attrs);
   $taskTable->updateColAttributes(3,$attrs);
   $taskTable->updateColAttributes(4,$attrs);
   
   echo $taskTable->toHTML();
   echo "<br><br><br>\n";
?>