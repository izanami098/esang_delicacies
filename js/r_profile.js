document.addEventListener('DOMContentLoaded', function() {
    const profilePhotoCircle = document.getElementById('profilePhotoCircle');
    const profilePhotoInput = document.getElementById('profilePhoto');
    const profilePhotoPreview = document.getElementById('profilePhotoPreview');

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
    const address = document.getElementById('address').value;
    const phoneNumber = document.getElementById('phoneNumber').value;
    const plateNumber = document.getElementById('plateNumber').value;
    const profilePhotoFile = document.getElementById('profilePhoto').files[0];

    // Basic validation (you can add more robust validation)
    if (!firstName || !lastName || !address || !phoneNumber || !plateNumber) {
        alert('Please fill in all required fields.');
        return;
    }

    // In a real application, you would send this data to a server
    // For demonstration, we'll just log it to the console
    console.log('Profile Saved!');
    console.log('First Name:', firstName);
    console.log('Last Name:', lastName);
    console.log('Address:', address);
    console.log('Phone Number:', phoneNumber);
    console.log('Plate Number:', plateNumber);

    if (profilePhotoFile) {
        console.log('Profile Photo:', profilePhotoFile.name, profilePhotoFile.type);
        // You would typically upload this file to a server
    } else {
        console.log('No new profile photo uploaded.');
    }

    alert('Profile updated successfully!');
    // You might want to clear the form or redirect the user after saving
}