
document.addEventListener('DOMContentLoaded', () => {
    const emailForm = document.getElementById('email-form');
    const otpVerificationSection = document.getElementById('otp-verification');
    const sendOtpBtn = document.getElementById('send-otp-btn');
    const verifyOtpBtn = document.getElementById('verify-otp-btn');
    const otpInputs = document.querySelectorAll('.otp-input');
    const resendBtn = document.getElementById('resend-otp');
    const resendInfo = document.getElementById('resend-info');

    // Email form submission
    emailForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const email = document.getElementById('otp-email').value;
        try {
            const response = await fetch('/esang_delicacies/public/api/send_otp.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `email=${encodeURIComponent(email)}`
            });
            const data = await response.json();
            if (data.success) {
                emailForm.style.display = 'none';
                otpVerificationSection.style.display = 'block';
                setTimeout(() => {
                    resendInfo.style.display = 'block';
                }, 5000);
                alert('OTP sent! (Demo: ' + (data.otp || '') + ')');
            } else {
                let msg = 'Failed to send OTP: ' + (data.message || '');
                if (data.exception) msg += '\nException: ' + data.exception;
                if (data.trace) msg += '\nTrace: ' + data.trace;
                alert(msg);
            }
        } catch (err) {
            alert('Error sending OTP.');
        }
    });

    // OTP input field logic
    otpInputs.forEach((input, index) => {
        input.addEventListener('input', (e) => {
            if (e.target.value.length === 1 && index < otpInputs.length - 1) {
                otpInputs[index + 1].focus();
            }
        });

        input.addEventListener('keydown', (e) => {
            if (e.key === 'Backspace' && e.target.value === '' && index > 0) {
                otpInputs[index - 1].focus();
            }
        });
    });

    // Verify OTP button
    verifyOtpBtn.addEventListener('click', async () => {
        const otpCode = Array.from(otpInputs).map(input => input.value).join('');
        const email = document.getElementById('otp-email').value;
        if (otpCode.length === 4) {
            try {
                const response = await fetch('/esang_delicacies/public/api/verify_otp.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `email=${encodeURIComponent(email)}&otp=${encodeURIComponent(otpCode)}`
                });
                const data = await response.json();
                if (data.success) {
                    alert('OTP Verified! Account created. You can now log in.');
                    window.location.href = "../auth/LogIn.php";
                } else {
                    alert(data.message || 'Invalid OTP. Please try again.');
                }
            } catch (err) {
                alert('Error verifying OTP.');
            }
        } else {
            alert('Please enter a 4-digit OTP.');
        }
    });


    // Resend OTP button
    resendBtn.addEventListener('click', async () => {
        const email = document.getElementById('otp-email').value;
        try {
            const response = await fetch('/esang_delicacies/public/api/send_otp.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `email=${encodeURIComponent(email)}`
            });
            const data = await response.json();
            if (data.success) {
                alert('New OTP has been sent! (Demo: ' + data.otp + ')');
            } else {
                alert('Failed to resend OTP: ' + data.message);
            }
        } catch (err) {
            alert('Error resending OTP.');
        }
    });
});