document.addEventListener('DOMContentLoaded', () => {
    let currentProfile = null;
    
    // DOM Elements
    const profilePhotoInput = document.getElementById('profilePhoto');
    const profilePhotoPreview = document.getElementById('profilePhotoPreview');
    const profilePhotoCircle = document.getElementById('profilePhotoCircle');
    const firstNameInput = document.getElementById('firstName');
    const lastNameInput = document.getElementById('lastName');
    const addressInput = document.getElementById('address');
    const phoneNumberInput = document.getElementById('phoneNumber');
    const userNameDisplay = document.getElementById('userNameDisplay');
    
    // Load customer profile data
    async function loadProfile() {
        try {
            console.log('Loading customer profile...');
            const response = await fetch('/esang_delicacies/public/api/get_customer_profile.php');
            
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
        addressInput.value = profile.address || '';
        phoneNumberInput.value = profile.phone_number || '';
        
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
                
                const response = await fetch('/esang_delicacies/public/api/upload_customer_profile_image.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Update profile image in database
                    await updateProfileImage(result.image_url);
                    
                    // Update preview
                    profilePhotoPreview.src = '/esang_delicacies/public/Images/profiles/customers/' + result.image_url;
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
            const response = await fetch('/esang_delicacies/public/api/update_customer_profile.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    first_name: currentProfile.first_name,
                    last_name: currentProfile.last_name,
                    phone_number: currentProfile.phone_number,
                    address: currentProfile.address,
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
            alert('Please fill in all required fields (First Name, Last Name, and Phone Number)');
            return;
        }
        
        // Basic phone number validation
        if (!phoneNumber.match(/^[0-9+\-\s()]+$/)) {
            alert('Please enter a valid phone number');
            return;
        }
        
        try {
            const response = await fetch('/esang_delicacies/public/api/update_customer_profile.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    first_name: firstName,
                    last_name: lastName,
                    phone_number: phoneNumber,
                    address: address,
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
                    currentProfile.address = address;
                }
                
                // Update sidebar display
                if (userNameDisplay) {
                    userNameDisplay.textContent = `${firstName} ${lastName}`;
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
        } else {
            // If no current profile, clear all fields
            firstNameInput.value = '';
            lastNameInput.value = '';
            addressInput.value = '';
            phoneNumberInput.value = '';
            
            // Reset profile image
            profilePhotoPreview.classList.add('hidden');
            profilePhotoPreview.src = '#';
            const iconElement = profilePhotoCircle.querySelector('i');
            if (iconElement) {
                iconElement.style.display = 'block';
            }
        }
    };
    
    // Click to upload photo
    profilePhotoCircle.addEventListener('click', () => {
        profilePhotoInput.click();
    });
    
    // Initial load
    loadProfile();
});