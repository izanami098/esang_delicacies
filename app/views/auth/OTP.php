<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../VCSS/OTP.css">
    <title>Verify Phone Number</title>
</head>
<body>
    <div class="container">
        <div class="form-container">
            <a href="javascript:history.back()" class="back-arrow">
                &larr;
            </a>

            <h1>Registration</h1>
            <?php $email = isset($_GET['email']) ? htmlspecialchars($_GET['email']) : ''; ?>
            <p class="instruction">Enter your email. We'll send you a verification code to confirm your identity.</p>
            <form id="email-form">
                <div class="email-input-container">
                    <input type="email" id="otp-email" placeholder="Enter your email" value="<?php echo $email; ?>" required readonly>
                </div>
                <button type="submit" id="send-otp-btn">Send OTP</button>
            </form>

            <div id="otp-verification" style="display: none;">
                <h2>Verify Your Email</h2>
                <p class="instruction">Enter your OTP Code here.</p>
                <div class="otp-input-container">
                    <input type="text" maxlength="1" class="otp-input" data-index="0">
                    <input type="text" maxlength="1" class="otp-input" data-index="1">
                    <input type="text" maxlength="1" class="otp-input" data-index="2">
                    <input type="text" maxlength="1" class="otp-input" data-index="3">
                </div>
                <p id="resend-info" style="display: none;">Didn't receive any code? <button id="resend-otp" class="resend-btn">Resend OTP</button></p>
                <button id="verify-otp-btn">Verify</button>
            </div>
        </div>
        <div class="image-section">
            </div>
    </div>
    <script src="../VJavaScript/OTP.js"></script>
</body>
</html>