<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="style.css">
    
    <!-- reCAPTCHA API -->
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h2 class="text-center mb-0">Contact Us</h2>
                    </div>
                    <div class="card-body p-4">
                        
                        <!-- Display error/success messages if redirected -->
                        <?php
                        if (isset($_GET['status'])) {
                            if ($_GET['status'] == 'success') {
                                echo '<div class="alert alert-success">Thank you! Your message has been sent successfully.</div>';
                            } elseif ($_GET['status'] == 'error') {
                                $error_type = $_GET['type'] ?? 'unknown';
                                $error_message = '';
                                
                                switch ($error_type) {
                                    case 'validation':
                                        $error_message = 'Please fill in all required fields correctly.';
                                        break;
                                    case 'recaptcha':
                                        $error_message = 'reCAPTCHA verification failed. Please try again.';
                                        break;
                                    case 'database':
                                        $error_message = 'There was a problem saving your message. Please try again later.';
                                        break;
                                    case 'email':
                                        $error_message = 'There was a problem sending your message. Please try again later.';
                                        break;
                                    default:
                                        $error_message = 'An unexpected error occurred. Please try again.';
                                }
                                
                                echo '<div class="alert alert-danger">' . $error_message . '</div>';
                            }
                        }
                        ?>
                        
                        <form id="contactForm" action="process.php" method="POST" novalidate>
                            <div class="mb-3">
                                <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" required 
                                       value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                                <div class="invalid-feedback">Please provide your name.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="email" name="email" required 
                                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                                <div class="invalid-feedback">Please provide a valid email address.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="subject" class="form-label">Subject <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="subject" name="subject" required 
                                       value="<?php echo isset($_POST['subject']) ? htmlspecialchars($_POST['subject']) : ''; ?>">
                                <div class="invalid-feedback">Please provide a subject for your message.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="message" class="form-label">Message <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="message" name="message" rows="5" required><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
                                <div class="invalid-feedback">Please provide your message.</div>
                            </div>
                            
                            <!-- reCAPTCHA Widget -->
                            <div class="mb-3">
                                <div class="g-recaptcha" data-sitekey="<?php echo RECAPTCHA_SITE_KEY; ?>"></div>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">Send Message</button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="text-center mt-3">
                    <p class="text-muted">We'll get back to you as soon as possible.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Form Validation Script -->
    <script>
        // Client-side form validation
        (function() {
            'use strict';
            
            const form = document.getElementById('contactForm');
            
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                
                form.classList.add('was-validated');
            }, false);
        })();
    </script>
</body>
</html>
