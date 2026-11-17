<?php
function send_status_email($to, $subject, $message) {
    // Simple wrapper; suppress errors in case mail is not configured.
    @mail($to, $subject, $message);
}
?>