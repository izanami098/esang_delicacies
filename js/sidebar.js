document.addEventListener('DOMContentLoaded', function() {
    // Get modal elements
    const logoutLink = document.getElementById('logoutLink');
    const logoutModal = document.getElementById('logoutModal');
    const closeButton = document.querySelector('.close-button');
    const cancelLogoutButton = document.getElementById('cancelLogout');
    const confirmLogoutButton = document.getElementById('confirmLogout');

    // Show the logout modal when "Log Out" is clicked
    if (logoutLink) { // Check if the element exists to prevent errors
        logoutLink.addEventListener('click', function(event) {
            event.preventDefault(); // Prevent default link behavior
            logoutModal.classList.add('show');
        });
    }

    // Hide the modal when the close button is clicked
    if (closeButton) {
        closeButton.addEventListener('click', function() {
            logoutModal.classList.remove('show');
        });
    }

    // Hide the modal when the "Cancel" button is clicked
    if (cancelLogoutButton) {
        cancelLogoutButton.addEventListener('click', function() {
            logoutModal.classList.remove('show');
        });
    }

    // Handle the "Log Out" confirmation
    if (confirmLogoutButton) {
        confirmLogoutButton.addEventListener('click', function() {
            // Use the customer logout page which handles profile hash cleanup
            window.location.href = '/esang_delicacies/customer_logout.php';
        });
    }

    // Hide the modal if the user clicks anywhere outside of the modal content
    if (logoutModal) {
        window.addEventListener('click', function(event) {
            if (event.target == logoutModal) {
                logoutModal.classList.remove('show');
            }
        });
    }
});

/* Set the width of the side navigation to 250px */
        function openNav() {
            document.getElementById("mySidenav").style.width = "250px";
            document.querySelector(".main-content").style.marginLeft = "250px";
        }

        /* Set the width of the side navigation to 0 */
        function closeNav() {
            document.getElementById("mySidenav").style.width = "0";
            document.querySelector(".main-content").style.marginLeft = "0";
        }

/* Admin Sidebar only */
var dropdown = document.querySelector('.dropdown-btn');
if (dropdown) {
    dropdown.addEventListener('click', function() {
        this.classList.toggle('active');
        var dropdownContent = this.nextElementSibling;
        if (dropdownContent.style.display === "block") {
            dropdownContent.style.display = "none";
        } else {
            dropdownContent.style.display = "block";
        }
    });
}
