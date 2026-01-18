<?php
// Check if field is valid (regex and length)
function check_field($field, $regex, $min, $max){
    $final_field = isset($field) ? trim($field) : "";

    // Check if empty
    if($final_field === ""){
        return false;
    }

    // Match regex pattern
    if(!preg_match($regex, $final_field)){
        return false;
    }

    // Check length
    if(strlen($final_field) < $min || strlen($final_field) > $max){
        return false;
    }

    return true;
}

// Escape HTML for security
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
?>