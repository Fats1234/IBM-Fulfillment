<?php
   function startForm($action,$method,$name="form",$newWindow=FALSE,$refresh=FALSE){
      $formStr="<form action=\"$action\" method=\"$method\" name=\"$name\" id=\"$name\"";
      if($refresh){
         $formStr.=" onsubmit=\"setTimeout(function () { window.location.reload(); }, 10)\"";
      }
      if($newWindow){
         $formStr.=" target=\"_blank\"";
      }
      $formStr.=">\n";
      return $formStr;
   }
   
   function endForm(){
      return "</form>\n";
   }

   function genTextBox($fieldname,$default=""){
      $textbox="<input type=\"text\" size=\"30\" name=\"$fieldname\" id=\"$fieldname\" value=\"$default\">\n";
      return $textbox;
   }

   function genTextArea($fieldname,$rows="10",$cols="50",$default_text=""){
      $textarea="<textarea rows=\"$rows\" cols=\"$cols\" name=\"$fieldname\">$default_text</textarea>";
      return $textarea;
   }

   function genCheckBox($fieldname,$value="",$checked=TRUE){
      if($checked){
         $checkBox="<input type=\"checkbox\" name=\"$fieldname"."[]\" id=\"$fieldname\" value=\"$value\" checked>\n";
      }else{
         $checkBox="<input type=\"checkbox\" name=\"$fieldname"."[]\" id=\"$fieldname\" value=\"$value\">\n";
      }
      
      return $checkBox;
   }
   
   function genDropBox($fieldname,$options,$width="250px",$default=""){
      if(empty($options)){
         return;
      }
      
      $dropbox = "<select name=\"$fieldname\" id=\"$fieldname\" style=\"width: $width\">\n";
      foreach($options as $option){
         if(!strcmp($option,$default)){
            $dropbox .= "<option value=\"$option\" selected>$option</option>\n";
         }else{
            $dropbox .= "<option value=\"$option\">$option</option>\n";
         }
      }
      $dropbox .= "</select>\n";
      return $dropbox;
   }
   
   function genButton($name="submit",$value="submit",$buttonText="submit"){
      $button="<button type=\"submit\" name=\"$name\" value=\"$value\">$buttonText</button>\n";
      return $button;
   }
   
   function genHidden($fieldname,$value){
      return "<input type=\"hidden\" name=\"$fieldname\" value=\"$value\">\n";
   }

?>