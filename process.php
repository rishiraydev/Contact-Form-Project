<?php
/**
 * Process contact form submission
 * Handles validation, database storage, and email notification
 */

// Include configuration
require_once 'config.php';

// Start session for storing form data in case of errors
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// Initialize response array
$response = [
    'status' => 'error',
    'type' => 'unknown',
    'message' => 'An unexpected error occurred.'
];

try {
    // Validate form data
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $recaptcha_response = $_POST['g-recaptcha-response'] ?? '';
    
    // Check if all required fields are filled
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $response['type'] = 'validation';
        $response['message'] = 'All fields are required.';
        throw new Exception('Validation failed: Missing required fields');
    }
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['type'] = 'validation';
        $response['message'] = 'Please provide a valid email address.';
        throw new Exception('Validation failed: Invalid email format');
    }
    
    // Validate reCAPTCHA
    if (empty($recaptcha_response)) {
        $response['type'] = 'recaptcha';
        $response['message'] = 'Please complete the reCAPTCHA.';
        throw new Exception('Validation failed: reCAPTCHA not completed');
    }
    
    // Verify reCAPTCHA with Google
    $recaptcha_url = 'https://www.google.com/recaptcha/api/siteverify';
    $recaptcha_data = [
        'secret' => RECAPTCHA_SECRET_KEY,
        'response' => $recaptcha_response,
        'remoteip' => $_SERVER['REMOTE_ADDR']
    ];
    
    $recaptcha_options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($recaptcha_data)
        ]
    ];
    
    $recaptcha_context = stream_context_create($recaptcha_options);
    $recaptcha_result = file_get_contents($recaptcha_url, false, $recaptcha_context);
    $recaptcha_response_data = json_decode($recaptcha_result, true);
    
    if (!$recaptcha_response_data['success']) {
        $response['type'] = 'recaptcha';
        $response['message'] = 'reCAPTCHA verification failed. Please try again.';
        throw new Exception('Validation failed: reCAPTCHA verification failed');
    }
    
    // Sanitize inputs to prevent XSS
    $name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $email = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
    $subject = htmlspecialchars($subject, ENT_QUOTES, 'UTF-8');
    $message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    
    // Connect to database
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
    
    // Prepare and execute SQL statement with prepared statements
    $sql = "INSERT INTO contacts (name, email, subject, message, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $name,
        $email,
        $subject,
        $message,
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
    ]);
    
    // Load PHPMailer
    require_once 'vendor/autoload.php';
    
    // Create and configure PHPMailer instance
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port = SMTP_PORT;
        
        // Recipients
        $mail->setFrom(EMAIL_FROM, EMAIL_FROM_NAME);
        $mail->addAddress(EMAIL_TO);
        $mail->addReplyTo($email, $name);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = "New Contact Form Submission: " . $subject;
        
        $email_body = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; }
                .header { background: #f8f9fa; padding: 10px; border-left: 4px solid #007bff; }
                .field { margin-bottom: 10px; }
                .label { font-weight: bold; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h2>New Contact Form Submission</h2>
            </div>
            <div class='field'>
                <span class='label'>Name:</span> $name
            </div>
            <div class='field'>
                <span class='label'>Email:</span> $email
            </div>
            <div class='field'>
                <span class='label'>Subject:</span> $subject
            </div>
            <div class='field'>
                <span class='label'>Message:</span><br>
                <div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin-top: 5px;'>
                    " . nl2br($message) . "
                </div>
            </div>
            <div class='field'>
                <span class='label'>Submitted:</span> " . date('F j, Y \a\t g:i A') . "
            </div>
        </body>
        </html>
        ";
        
        $mail->Body = $email_body;
        
        // Plain text version for email clients that don't support HTML
        $mail->AltBody = "
        New Contact Form Submission
        
        Name: $name
        Email: $email
        Subject: $subject
        Message: 
        $message
        
        Submitted: " . date('F j, Y \a\t g:i A');
        
        // Send email
        $mail->send();
        
        // Success response
        $response['status'] = 'success';
        $response['message'] = 'Thank you! Your message has been sent successfully.';
        
    } catch (Exception $e) {
        // Email sending failed, but message was saved to database
        error_log("PHPMailer Error: " . $e->getMessage());
        $response['type'] = 'email';
        $response['message'] = 'Your message was received, but there was a problem sending the notification.';
        // We don't throw here because the message was saved to database
    }
    
} catch (PDOException $e) {
    // Database error
    error_log("Database Error: " . $e->getMessage());
    $response['type'] = 'database';
    $response['message'] = 'There was a problem saving your message. Please try again later.';
} catch (Exception $e) {
    // Other errors (validation, etc.)
    error_log("Form Processing Error: " . $e->getMessage());
    // Response already set in the catch blocks
}

// Redirect with appropriate status
if ($response['status'] === 'success') {
    header('Location: index.php?status=success');
} else {
    // Store form data in session to repopulate form on error
    $_SESSION['form_data'] = [
        'name' => $_POST['name'] ?? '',
        'email' => $_POST['email'] ?? '',
        'subject' => $_POST['subject'] ?? '',
        'message' => $_POST['message'] ?? ''
    ];
    
    header('Location: index.php?status=error&type=' . $response['type']);
}

exit;
?>
