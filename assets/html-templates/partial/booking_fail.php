<?php
// Ensure this script is only accessed through WordPress
if (!defined('ABSPATH')) {
    exit;
}

$html = '<h1>Error ' . esc_html($confirm['error_message']) . '</h1>';
echo $html;
?>
