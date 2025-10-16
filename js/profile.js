document.addEventListener('DOMContentLoaded', function() {
    const profilePhotoCircle = document.getElementById('profilePhotoCircle');
    const profilePhotoInput = document.getElementById('profilePhoto');
    const profilePhotoPreview = document.getElementById('profilePhotoPreview');

    // Fetch profile data from backend
    fetch('/esang_delicacies/public/api/profile.php')
        .then(res => res.json())
        .then(data => {
            if (data.success && data.profile) {
                // Fill form fields if they exist
                if (document.getElementById('firstName') && data.profile.name) {
                    // Split name if possible
                    const parts = data.profile.name.split(' ');
                    document.getElementById('firstName').value = parts[0] || '';
                    document.getElementById('lastName').value = parts.slice(1).join(' ') || '';
                }
                if (document.getElementById('email') && data.profile.email) {
                    document.getElementById('email').value = data.profile.email;
                }
                if (document.getElementById('address') && data.profile.address) {
                    document.getElementById('address').value = data.profile.address;
                }
                if (document.getElementById('phoneNumber') && data.profile.phoneNumber) {
                    document.getElementById('phoneNumber').value = data.profile.phoneNumber;
                }
            }
        });

    profilePhotoCircle.addEventListener('click', function() {
        profilePhotoInput.click();
    });

    profilePhotoInput.addEventListener('change', function(event) {
        const file = event.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                profilePhotoPreview.src = e.target.result;
                profilePhotoPreview.classList.remove('hidden');
            };
            reader.readAsDataURL(file);
        }
    });
});

function saveProfile() {
    const firstName = document.getElementById('firstName').value;
    const lastName = document.getElementById('lastName').value;
    const email = document.getElementById('email') ? document.getElementById('email').value : '';
    const address = document.getElementById('address') ? document.getElementById('address').value : '';
    const phoneNumber = document.getElementById('phoneNumber') ? document.getElementById('phoneNumber').value : '';
    const profilePhotoFile = document.getElementById('profilePhoto').files[0];

    // Basic validation (customize as needed)
    if (!firstName || !lastName) {
        alert('Please fill in all required fields.');
        return;
    }

    // Prepare data for backend
    const formData = new FormData();
    formData.append('name', firstName + ' ' + lastName);
    if (email) formData.append('email', email);
    if (address) formData.append('address', address);
    if (phoneNumber) formData.append('phoneNumber', phoneNumber);
    // Profile photo upload not handled in backend yet

    fetch('/esang_delicacies/public/api/profile.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('Profile updated successfully!');
        } else {
            alert('Failed to update profile: ' + (data.error || 'Unknown error'));
        }
    });
}