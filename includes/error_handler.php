<?php
function customErrorHandler($errno, $errstr, $errfile, $errline) {
    
    $error_message = "Gabim Sistemi [$errno]: $errstr në skedarin $errfile, rreshti $errline";
    
   
    echo "<div style='background-color: #f8d7da; border: 1px solid #f5c2c7; color: #842029; padding: 15px; margin: 10px; border-radius: 6px; font-family: sans-serif; text-align: center; position: relative; z-index: 9999;'>
            <strong>Kujdes (Error Handler):</strong><br>
            <small>$error_message</small>
          </div>";
          
    
    return true; 
}
set_error_handler("customErrorHandler");
?>