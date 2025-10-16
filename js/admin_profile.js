document.addEventListener('DOMContentLoaded', () => {
    let currentProfile = null;
    
    // DOM Elements
    const profilePhotoInput = document.getElementById('profilePhoto');
    const profilePhotoPreview = document.getElementById('profilePhotoPreview');
    const profilePhotoCircle = document.getElementById('profilePhotoCircle');
    const firstNameInput = document.getElementById('firstName');
    const lastNameInput = document.getElementById('lastName');
    const emailInput = document.getElementById('email');
    const phoneNumberInput = document.getElementById('phoneNumber');
    const addressInput = document.getElementById('address');
    const userNameDisplay = document.getElementById('userNameDisplay');
    
    // Load user profile data
    async function loadProfile() {
        try {
            console.log('Loading user profile...');
            const response = await fetch('/esang_delicacies/public/api/get_admin_profile.php');
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const result = await response.json();
            console.log('Profile data loaded:', result);
            
            if (result.success) {
                currentProfile = result.profile;
                populateForm(result.profile);
                
                // Update user name in sidebar if available
                if (userNameDisplay && currentProfile.first_name && currentProfile.last_name) {
                    userNameDisplay.textContent = `${currentProfile.first_name} ${currentProfile.last_name}`;
                }
            } else {
                console.error('Failed to load profile:', result.message);
                alert('Failed to load profile: ' + result.message);
            }
        } catch (error) {
            console.error('Error loading profile:', error);
            alert('Error loading profile: ' + error.message);
        }
    }
    
    // Populate form with profile data
    function populateForm(profile) {
        firstNameInput.value = profile.first_name || '';
        lastNameInput.value = profile.last_name || '';
        emailInput.value = profile.email || '';
        phoneNumberInput.value = profile.phone_number || '';
        addressInput.value = profile.address || '';
        
        // Set profile image if exists
        if (profile.profile_image) {
            profilePhotoPreview.src = profile.profile_image;
            profilePhotoPreview.classList.remove('hidden');
            const iconElement = profilePhotoCircle.querySelector('i');
            if (iconElement) {
                iconElement.style.display = 'none';
            }
        }
    }
    
    // Handle profile photo upload
    profilePhotoInput.addEventListener('change', async function(e) {
        const file = e.target.files[0];
        if (file) {
            // Validate file type
            if (!file.type.startsWith('image/')) {
                alert('Please select an image file');
                return;
            }
            
            // Validate file size (5MB max)
            if (file.size > 5 * 1024 * 1024) {
                alert('File size must be less than 5MB');
                return;
            }
            
            try {
                // Upload image
                const formData = new FormData();
                formData.append('profile_image', file);
                
                const response = await fetch('/esang_delicacies/public/api/upload_profile_image.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Update profile image in database
                    await updateProfileImage(result.image_url);
                    
                    // Update preview
                    profilePhotoPreview.src = result.image_url;
                    profilePhotoPreview.classList.remove('hidden');
                    const iconElement = profilePhotoCircle.querySelector('i');
                    if (iconElement) {
                        iconElement.style.display = 'none';
                    }
                    
                    alert('Profile image updated successfully!');
                } else {
                    alert('Failed to upload image: ' + result.message);
                }
            } catch (error) {
                console.error('Error uploading image:', error);
                alert('Error uploading image: ' + error.message);
            }
        }
    });
    
    // Update profile image in database
    async function updateProfileImage(imageUrl) {
        try {
            const response = await fetch('/esang_delicacies/public/api/update_admin_profile.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    first_name: currentProfile.first_name,
                    last_name: currentProfile.last_name,
                    phone_number: currentProfile.phone_number,
                    profile_image: imageUrl
                })
            });
            
            const result = await response.json();
            if (result.success) {
                currentProfile.profile_image = imageUrl;
            }
        } catch (error) {
            console.error('Error updating profile image:', error);
        }
    }
    
    // Save profile function
    window.saveProfile = async function() {
        const firstName = firstNameInput.value.trim();
        const lastName = lastNameInput.value.trim();
        const phoneNumber = phoneNumberInput.value.trim();
        const address = addressInput.value.trim();
        
        if (!firstName || !lastName || !phoneNumber) {
            alert('Please fill in all required fields');
            return;
        }
        
        try {
            const response = await fetch('/esang_delicacies/public/api/update_admin_profile.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    first_name: firstName,
                    last_name: lastName,
                    phone_number: phoneNumber,
                    profile_image: currentProfile ? currentProfile.profile_image : ''
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                alert('Profile updated successfully!');
                // Update current profile
                if (currentProfile) {
                    currentProfile.first_name = firstName;
                    currentProfile.last_name = lastName;
                    currentProfile.phone_number = phoneNumber;
                }
            } else {
                alert('Failed to update profile: ' + result.message);
            }
        } catch (error) {
            console.error('Error updating profile:', error);
            alert('Error updating profile: ' + error.message);
        }
    };
    
    // Reset profile function
    window.resetProfile = function() {
        if (currentProfile) {
            populateForm(currentProfile);
        }
    };
    
    // Click to upload photo
    profilePhotoCircle.addEventListener('click', () => {
        profilePhotoInput.click();
    });
    
    // Initial load
    loadProfile();
});
