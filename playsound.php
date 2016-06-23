<?php
   if(isset($_POST['play'])){
      switch($_POST['status']) {
         case 'success':
            exec("aplay /export/share001/IBM-FULFILLMENT/sounds/success-beep.wav");
            break;
         case 'error':
            exec("aplay /export/share001/IBM-FULFILLMENT/sounds/error-beep.wav");
            break;
      }
   }
?>