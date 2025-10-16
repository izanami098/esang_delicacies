// Function to show/hide the terms and conditions based on the user role
        function toggleTerms() {
            const userRoleSelect = document.getElementById('user_role');
            const termsContainer = document.getElementById('customer-terms-container');
            const agreeCheckbox = document.getElementById('agree-terms');

            if (userRoleSelect.value === 'customer') {
                termsContainer.style.display = 'flex';
                agreeCheckbox.setAttribute('required', 'required');
            } else {
                termsContainer.style.display = 'none';
                agreeCheckbox.removeAttribute('required');
                agreeCheckbox.checked = false; // Uncheck if the role changes
            }
        }

        // Get the modal
        const modal = document.getElementById("terms-modal");
        // Get the button that opens the modal
        const viewTermsBtn = document.getElementById("view-terms");
        // Get the <span> element that closes the modal
        const closeBtn = document.getElementsByClassName("close-button")[0];
        
        // When the user clicks the link, open the modal 
        viewTermsBtn.onclick = function(event) {
          event.preventDefault(); // Prevents the link from navigating
          modal.style.display = "block";
        }
        
        // When the user clicks on <span> (x), close the modal
        closeBtn.onclick = function() {
          modal.style.display = "none";
        }
        
        // When the user clicks anywhere outside of the modal, close it
        window.onclick = function(event) {
          if (event.target == modal) {
            modal.style.display = "none";
          }
        }
        
        // Call the function on page load to set the initial state
        window.onload = toggleTerms;