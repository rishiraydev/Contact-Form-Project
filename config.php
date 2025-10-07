<?php
/**
 * Configuration file for Contact Form
 * Database credentials, email settings, and reCAPTCHA keys
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'contact_form');
define('DB_USER', 'root'); // Change to your MySQL username
define('DB_PASS', '');     // Change to your MySQL password

// Email configuration
define('SMTP_HOST', 'smtp.gmail.com'); // Change to your SMTP server
define('SMTP_USER', 'your-email@gmail.com'); // Change to your email
define('SMTP_PASS', 'your-app-password'); // Change to your email password
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');
define('EMAIL_FROM', 'your-email@gmail.com');
define('EMAIL_FROM_NAME', 'Contact Form');
define('EMAIL_TO', 'admin@yoursite.com'); // Where to send notifications

// reCAPTCHA configuration
define('RECAPTCHA_SITE_KEY', 'your-recaptcha-site-key'); // Get from Google reCAPTCHA
define('RECAPTCHA_SECRET_KEY', 'your-recaptcha-secret-key'); // Get from Google reCAPTCHA

// Enable error reporting for development (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set timezone
date_default_timezone_set('America/New_York');
?>
