document.addEventListener('DOMContentLoaded', function () {
    let currentOrder = null;
    let syncService = null;
    let showFeedbackForm = false; // Track if we should show feedback instead of rider info
    let feedbackRatings = {
        delivery: 0,
        taste: 0,
        food_quality: 0,
        service: 0
    };
    
    // DOM Elements
    const progressSteps = document.querySelectorAll('.progress-step');
    const deliveredStep = document.querySelector('[data-title="delivered"]');
    const viewDetailsLink = document.getElementById('viewDetailsLink');
    const returnOrderBtn = document.getElementById('returnOrderBtn');
    const riderInfoSection = document.getElementById('riderInfoSection');
    const deliveredRiderInfo = document.getElementById('deliveredRiderInfo');
    const deliveryFeedbackSection = document.getElementById('deliveryFeedbackSection');
    const viewRiderDetailsBtn = document.getElementById('viewRiderDetailsBtn');
    const submitFeedbackBtn = document.getElementById('submitFeedbackBtn');
    const returnItemBtn = document.getElementById('returnItemBtn');

    // Modals
    const selectReasonModal = document.getElementById('selectReasonModal');
    const requestSummaryModal = document.getElementById('requestSummaryModal');
    const refundGcashModal = document.getElementById('refundGcashModal');
    const riderDetailsModal = document.getElementById('riderDetailsModal');

    // Buttons and Links
    const closeReasonModal = document.getElementById('closeReasonModal');
    const doneReasonBtn = document.getElementById('doneReasonBtn');
    const changeReasonLink = document.getElementById('changeReasonLink');
    const refundGcashOption = document.getElementById('refundGcashOption');
    const addGcashLink = document.getElementById('addGcashLink');
    const closeGcashModal = document.getElementById('closeGcashModal');
    const closeRiderDetailsModal = document.getElementById('closeRiderDetailsModal');

    // Dynamic content elements
    const selectedReasonSpan = document.getElementById('selectedReason');
    const refundMethodOptions = document.querySelectorAll('.refund-option');
    const refundMethodInputs = document.querySelectorAll('input[name="refund-method"]');
    const gcashForm = document.getElementById('gcashForm');
    const submitRequestBtn = document.getElementById('submitRequestBtn');

    // Pre-fill GCash form with placeholder data
    const gcashFirstName = document.getElementById('gcashFirstName');
    const gcashLastName = document.getElementById('gcashLastName');
    const gcashNumber = document.getElementById('gcashNumber');
    
    // Load order status from API
    async function loadOrderStatus() {
        console.log('Loading order status...');
        try {
            const response = await fetch('/esang_delicacies/public/api/get_customer_order_status.php');
            console.log('API response status:', response.status);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const result = await response.json();
            console.log('API response data:', result);
            
            if (result.success && result.has_orders) {
                console.log('Customer has orders, updating UI');
                currentOrder = result.order;
                updateUI(result);
            } else {
                console.log('Customer has no orders, showing message');
                showNoOrdersMessage();
            }
        } catch (error) {
            console.error('Error loading order status:', error);
            showErrorMessage('Failed to load order status. Please refresh the page.');
        }
    }
    
    // Update UI with order data
    function updateUI(orderData) {
        updateProgressBar(orderData.progress_step);
        updateRiderInfo(orderData.order.rider, orderData.show_rider_details);
        updateReturnButton(orderData.can_return);
        updateViewDetailsLink(orderData.can_return);
    }

    // Function to update the progress bar based on step
    function updateProgressBar(step) {
        const progress = document.getElementById('progress');
        
        progressSteps.forEach((progressStep, index) => {
            progressStep.classList.remove('active');
            if (index <= step) {
                progressStep.classList.add('active');
            }
        });

        if (step >= 0) {
            const percent = (step / (progressSteps.length - 1)) * 100;
            progress.style.width = percent + '%';
        }
    }
    
    // Update rider information display
    function updateRiderInfo(riderData, showRiderDetails) {
        const deliveredRiderInfo = document.getElementById('deliveredRiderInfo');
        const deliveryFeedbackSection = document.getElementById('deliveryFeedbackSection');
        
        if (riderData && showRiderDetails) {
            // Check if order is delivered
            if (currentOrder && currentOrder.status === 'delivered') {
                if (showFeedbackForm) {
                    // Show feedback form instead of rider info
                    if (deliveryFeedbackSection) {
                        deliveryFeedbackSection.style.display = 'block';
                    }
                    if (deliveredRiderInfo) {
                        deliveredRiderInfo.style.display = 'none';
                    }
                } else {
                    // Show delivered rider info section with new design
                    if (deliveredRiderInfo) {
                        deliveredRiderInfo.style.display = 'block';
                        
                        // Update rider information
                        document.getElementById('riderName').textContent = riderData.name;
                        document.getElementById('riderPhone').textContent = riderData.phone;
                        document.getElementById('riderPlateNumber').textContent = riderData.plate_number || 'Not specified';
                        
                        // Update customer location (from order delivery address)
                        const customerLocation = currentOrder.delivery_address || 'Location not available';
                        document.getElementById('customerLocation').textContent = customerLocation;
                        
                        // Auto-transition to feedback after 3 seconds
                        setTimeout(async () => {
                            showFeedbackForm = true;
                            updateRiderInfo(riderData, showRiderDetails);
                            // Load existing feedback when switching to feedback form
                            await loadExistingFeedback();
                        }, 3000);
                    }
                    if (deliveryFeedbackSection) {
                        deliveryFeedbackSection.style.display = 'none';
                    }
                }
                
                // Hide regular rider info section
                if (riderInfoSection) {
                    riderInfoSection.style.display = 'none';
                }
            } else {
                // Show regular rider info section for other statuses
                if (riderInfoSection) {
                    riderInfoSection.style.display = 'block';
                    
                    // Update summary section
                    document.getElementById('riderNameSummary').textContent = riderData.name;
                    document.getElementById('riderPhoneSummary').textContent = riderData.phone;
                    document.getElementById('riderTrackingSummary').textContent = `Tracking: ${riderData.tracking_id}`;
                }
                
                // Hide delivered rider info and feedback sections
                if (deliveredRiderInfo) {
                    deliveredRiderInfo.style.display = 'none';
                }
                if (deliveryFeedbackSection) {
                    deliveryFeedbackSection.style.display = 'none';
                }
            }
            
            // Update modal content (common for both designs)
            document.getElementById('modalRiderName').textContent = riderData.name;
            document.getElementById('modalTrackingId').textContent = riderData.tracking_id;
            document.getElementById('modalRiderPhone').textContent = riderData.phone;
            document.getElementById('modalRiderPlate').textContent = riderData.plate_number || 'Not specified';
            document.getElementById('modalRiderEmail').textContent = riderData.email || 'Not available';
        } else {
            // Hide all rider info sections
            if (riderInfoSection) {
                riderInfoSection.style.display = 'none';
            }
            if (deliveredRiderInfo) {
                deliveredRiderInfo.style.display = 'none';
            }
            if (deliveryFeedbackSection) {
                deliveryFeedbackSection.style.display = 'none';
            }
        }
    }
    
    // Update return button visibility
    function updateReturnButton(canReturn) {
        returnOrderBtn.style.display = canReturn ? 'block' : 'none';
    }
    
    // Update view details link visibility
    function updateViewDetailsLink(canReturn) {
        viewDetailsLink.style.display = canReturn ? 'inline' : 'none';
    }
    
    // Show message when no orders found
    function showNoOrdersMessage() {
        console.log('Showing no orders message');
        const container = document.querySelector('.container');
        
        // Hide existing elements
        const progressSection = document.querySelector('.order-status-header');
        if (progressSection) {
            progressSection.style.display = 'none';
        }
        
        // Create or update no orders message
        let noOrdersDiv = document.querySelector('.no-orders-message');
        if (!noOrdersDiv) {
            noOrdersDiv = document.createElement('div');
            noOrdersDiv.className = 'no-orders-message';
            container.appendChild(noOrdersDiv);
        }
        
        noOrdersDiv.innerHTML = `
            <div style="text-align: center; padding: 50px; background: #f8f9fa; border-radius: 10px; margin: 20px;">
                <i class="fas fa-shopping-cart" style="font-size: 64px; color: #ccc; margin-bottom: 20px;"></i>
                <h2 style="color: #666; margin-bottom: 10px;">No Orders Found</h2>
                <p style="color: #999; margin-bottom: 30px;">You haven't placed any orders yet.</p>
                <a href="customer_dashboard.php" class="btn btn-primary" style="background: #ffc107; color: #000; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Start Shopping</a>
            </div>
        `;
    }
    
    // Show error message
    function showErrorMessage(message) {
        const container = document.querySelector('.container');
        container.innerHTML = `
            <div class="error-message">
                <h2>Error Loading Order Status</h2>
                <p>${message}</p>
                <button onclick="location.reload()" class="btn btn-primary">Retry</button>
            </div>
        `;
    }
    
    // Show feedback success message
    function showFeedbackSuccessMessage(overallRating) {
        const riderInfoSection = document.getElementById('riderInfoSection');
        const deliveredRiderInfo = document.getElementById('deliveredRiderInfo');
        
        // Create success message container
        const successDiv = document.createElement('div');
        successDiv.className = 'feedback-success-message';
        successDiv.style.cssText = `
            background: #d4edda;
            color: #155724;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: center;
            border: 1px solid #c3e6cb;
        `;
        
        successDiv.innerHTML = `
            <i class="fas fa-check-circle" style="font-size: 48px; color: #28a745; margin-bottom: 15px;"></i>
            <h3 style="margin: 10px 0; color: #155724;">Feedback Submitted Successfully!</h3>
            <p style="margin: 5px 0; color: #155724;">Your overall rating: <strong>${overallRating}/5 stars</strong></p>
            <p style="margin: 5px 0; color: #6c757d; font-size: 14px;">Thank you for helping us improve our service!</p>
        `;
        
        // Insert after the delivered rider info section
        if (deliveredRiderInfo && deliveredRiderInfo.parentNode) {
            deliveredRiderInfo.parentNode.insertBefore(successDiv, deliveredRiderInfo.nextSibling);
        } else if (riderInfoSection && riderInfoSection.parentNode) {
            riderInfoSection.parentNode.insertBefore(successDiv, riderInfoSection.nextSibling);
        }
    }
    
    // Load existing feedback for the current order
    async function loadExistingFeedback() {
        if (!currentOrder || !currentOrder.order_id || currentOrder.status !== 'delivered') {
            return;
        }
        
        try {
            const response = await fetch(`/esang_delicacies/api/get_feedback.php?order_id=${currentOrder.order_id}`);
            const result = await response.json();
            
            if (response.ok && result.success && result.data) {
                const feedback = result.data;
                console.log('Existing feedback found:', feedback);
                
                // Update feedback ratings
                feedbackRatings.delivery = feedback.delivery_rating;
                feedbackRatings.taste = feedback.taste_rating;
                feedbackRatings.food_quality = feedback.food_quality_rating;
                feedbackRatings.service = feedback.service_rating;
                
                // Update star displays
                updateStarRatings();
                
                // Fill comment if exists
                const commentTextarea = document.getElementById('feedbackComment');
                if (commentTextarea && feedback.comment) {
                    commentTextarea.value = feedback.comment;
                }
                
                // Update submit button text
                if (submitFeedbackBtn) {
                    submitFeedbackBtn.textContent = 'Update Feedback';
                }
                
                console.log('Pre-filled existing feedback ratings');
            } else {
                console.log('No existing feedback found for this order');
            }
        } catch (error) {
            console.error('Error loading existing feedback:', error);
        }
    }
    
    // Update star ratings display based on current feedbackRatings
    function updateStarRatings() {
        Object.keys(feedbackRatings).forEach(category => {
            const rating = feedbackRatings[category];
            if (rating > 0) {
                const starRating = document.querySelector(`.star-rating[data-category="${category}"]`);
                if (starRating) {
                    const stars = starRating.querySelectorAll('.fa-star');
                    stars.forEach((star, index) => {
                        star.classList.remove('active');
                        if (index < rating) {
                            star.classList.add('active');
                        }
                    });
                }
            }
        });
    }


    // --- Modal Logic ---

    // Open Select Reason Modal

    submitRequestBtn.addEventListener('click', () => {
        const selectedRefundMethod = document.querySelector('input[name="refund-method"]:checked');
        if (!selectedRefundMethod) {
            alert("Please select a refund method.");
            return;
        }
        console.log('Return request submitted:', {
            reason: selectedReasonSpan.textContent,
            refundMethod: selectedRefundMethod.value,
            gcashDetails: {
                firstName: gcashFirstName.value,
                lastName: gcashLastName.value,
                number: gcashNumber.value
            }
        });
        alert('Your return request has been submitted!');
        requestSummaryModal.style.display = 'none';
    });

    // Event Listeners
    
    // View Rider Details - from "view details" link
    viewDetailsLink.addEventListener('click', (e) => {
        e.preventDefault();
        riderDetailsModal.style.display = 'flex';
    });
    
    // View Rider Details - from rider info section button
    viewRiderDetailsBtn.addEventListener('click', (e) => {
        e.preventDefault();
        riderDetailsModal.style.display = 'flex';
    });
    
    // Close rider details modal
    closeRiderDetailsModal.addEventListener('click', () => {
        riderDetailsModal.style.display = 'none';
    });
    
    // Return order button
    returnOrderBtn.addEventListener('click', () => {
        selectReasonModal.style.display = 'flex';
    });
    
    // Close return reason modal
    closeReasonModal.addEventListener('click', () => {
        selectReasonModal.style.display = 'none';
    });
    
    // Done with reason selection
    doneReasonBtn.addEventListener('click', () => {
        const selectedReason = document.querySelector('input[name="return-reason"]:checked');
        if (selectedReason) {
            selectedReasonSpan.textContent = selectedReason.value;
            selectReasonModal.style.display = 'none';
            requestSummaryModal.style.display = 'flex';
        } else {
            alert('Please select a reason for return.');
        }
    });
    
    // Change reason link
    changeReasonLink.addEventListener('click', (e) => {
        e.preventDefault();
        requestSummaryModal.style.display = 'none';
        selectReasonModal.style.display = 'flex';
    });
    
    // Add GCash link
    addGcashLink.addEventListener('click', (e) => {
        e.preventDefault();
        refundGcashModal.style.display = 'flex';
    });
    
    // Close GCash modal
    closeGcashModal.addEventListener('click', () => {
        refundGcashModal.style.display = 'none';
    });
    
    // GCash form submission
    gcashForm.addEventListener('submit', (e) => {
        e.preventDefault();
        refundGcashModal.style.display = 'none';
        alert('GCash details saved!');
    });
    
    // Submit return request
    submitRequestBtn.addEventListener('click', () => {
        const selectedRefundMethod = document.querySelector('input[name="refund-method"]:checked');
        if (!selectedRefundMethod) {
            alert("Please select a refund method.");
            return;
        }
        console.log('Return request submitted:', {
            orderId: currentOrder ? currentOrder.order_id : null,
            reason: selectedReasonSpan.textContent,
            refundMethod: selectedRefundMethod.value,
            gcashDetails: {
                firstName: gcashFirstName.value,
                lastName: gcashLastName.value,
                number: gcashNumber.value
            }
        });
        alert('Your return request has been submitted!');
        requestSummaryModal.style.display = 'none';
    });
    
    // Close modals when clicking outside
    window.addEventListener('click', (e) => {
        if (e.target === selectReasonModal) {
            selectReasonModal.style.display = 'none';
        }
        if (e.target === requestSummaryModal) {
            requestSummaryModal.style.display = 'none';
        }
        if (e.target === refundGcashModal) {
            refundGcashModal.style.display = 'none';
        }
        if (e.target === riderDetailsModal) {
            riderDetailsModal.style.display = 'none';
        }
    });
    
    // Initialize sync service for real-time updates
    initializeSyncService();
    
    // Initialize - Load order status on page load
    loadOrderStatus();
    
    // --- Feedback System Event Handlers ---
    
    // Star rating functionality
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('fa-star') && e.target.closest('.star-rating')) {
            const starRating = e.target.closest('.star-rating');
            const category = starRating.dataset.category;
            const rating = parseInt(e.target.dataset.rating);
            
            // Update feedback ratings
            feedbackRatings[category] = rating;
            
            // Update star display
            const stars = starRating.querySelectorAll('.fa-star');
            stars.forEach((star, index) => {
                star.classList.remove('active', 'hovered');
                if (index < rating) {
                    star.classList.add('active');
                }
            });
        }
    });
    
    // Star hover effects
    document.addEventListener('mouseover', function(e) {
        if (e.target.classList.contains('fa-star') && e.target.closest('.star-rating')) {
            const starRating = e.target.closest('.star-rating');
            const rating = parseInt(e.target.dataset.rating);
            const stars = starRating.querySelectorAll('.fa-star');
            
            stars.forEach((star, index) => {
                star.classList.remove('hovered');
                if (index < rating) {
                    star.classList.add('hovered');
                }
            });
        }
    });
    
    // Remove hover effects when mouse leaves star rating
    document.addEventListener('mouseout', function(e) {
        if (e.target.classList.contains('fa-star') && e.target.closest('.star-rating')) {
            const starRating = e.target.closest('.star-rating');
            const stars = starRating.querySelectorAll('.fa-star');
            stars.forEach(star => {
                star.classList.remove('hovered');
            });
        }
    });
    
    // Submit feedback button
    if (submitFeedbackBtn) {
        submitFeedbackBtn.addEventListener('click', async function() {
            // Check if all ratings are provided
            const categories = ['delivery', 'taste', 'food_quality', 'service'];
            const missingRatings = categories.filter(cat => feedbackRatings[cat] === 0);
            
            if (missingRatings.length > 0) {
                alert('Please provide ratings for all categories before submitting.');
                return;
            }
            
            if (!currentOrder || !currentOrder.order_id) {
                alert('Order information not available. Please refresh the page.');
                return;
            }
            
            // Get comment from textarea if exists
            const commentTextarea = document.getElementById('feedbackComment');
            const comment = commentTextarea ? commentTextarea.value.trim() : '';
            
            // Prepare feedback data
            const feedbackData = {
                order_id: currentOrder.order_id,
                customer_id: currentOrder.customer_id,
                delivery_rating: feedbackRatings.delivery,
                taste_rating: feedbackRatings.taste,
                food_quality_rating: feedbackRatings.food_quality,
                service_rating: feedbackRatings.service,
                comment: comment || null,
                is_anonymous: 0 // Default to non-anonymous
            };
            
            console.log('Submitting feedback:', feedbackData);
            
            // Disable submit button during API call
            submitFeedbackBtn.disabled = true;
            submitFeedbackBtn.textContent = 'Submitting...';
            
            try {
                const response = await fetch('/esang_delicacies/api/save_feedback.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(feedbackData)
                });
                
                const result = await response.json();
                
                if (response.ok && result.success) {
                    alert('Thank you for your feedback! Your review has been submitted successfully.');
                    
                    // Hide feedback section after successful submission
                    if (deliveryFeedbackSection) {
                        deliveryFeedbackSection.style.display = 'none';
                    }
                    
                    // Show a success message or confirmation UI
                    showFeedbackSuccessMessage(result.data.overall_rating);
                } else {
                    throw new Error(result.error || 'Failed to submit feedback');
                }
            } catch (error) {
                console.error('Error submitting feedback:', error);
                alert('Failed to submit feedback. Please try again.');
            } finally {
                // Re-enable submit button
                submitFeedbackBtn.disabled = false;
                submitFeedbackBtn.textContent = 'Submit Feedback';
            }
        });
    }
    
    // Return item button (opens the return reason modal)
    if (returnItemBtn) {
        returnItemBtn.addEventListener('click', function() {
            selectReasonModal.style.display = 'flex';
        });
    }
    
    // Initialize real-time synchronization service
    function initializeSyncService() {
        if (typeof OrderSyncService !== 'undefined') {
            syncService = new OrderSyncService({
                pollType: 'customer',
                pollInterval: 10000, // 10 seconds for customer updates
                onStatusUpdate: handleStatusUpdate,
                onNotification: handleNotification,
                onError: handleSyncError,
                onConnect: handleSyncConnect
            });
        } else {
            console.warn('OrderSyncService not available, real-time updates disabled');
        }
    }
    
    // Handle real-time status updates
    function handleStatusUpdate(update) {
        console.log('Received order status update:', update);
        
        if (currentOrder && currentOrder.order_id === update.order_id) {
            // Update current order status
            currentOrder.status = update.status;
            
            // Reload full order data to get complete information
            loadOrderStatus();
            
            // Show notification to user
            showStatusNotification(
                `Your order status has been updated to: ${getStatusDisplayName(update.status)}`,
                'success'
            );
        }
    }
    
    // Handle notifications
    function handleNotification(notification) {
        console.log('Received notification:', notification);
        showStatusNotification(notification.message, 'info');
    }
    
    // Handle sync errors
    function handleSyncError(error) {
        console.error('Sync error:', error);
        // Optionally show a subtle error indicator
    }
    
    // Handle sync connection
    function handleSyncConnect() {
        console.log('Real-time sync connected');
    }
    
    // Get user-friendly status display name
    function getStatusDisplayName(status) {
        const statusMap = {
            'pending': 'Order Placed',
            'confirmed': 'Order Confirmed',
            'preparing': 'Being Prepared',
            'ready_for_pickup': 'Ready for Pickup',
            'out_for_delivery': 'Out for Delivery',
            'delivered': 'Delivered',
            'cancelled': 'Cancelled'
        };
        return statusMap[status] || status.charAt(0).toUpperCase() + status.slice(1);
    }
    
    // Show status notification to user
    function showStatusNotification(message, type = 'info') {
        // Remove any existing notification
        const existingNotification = document.querySelector('.status-notification');
        if (existingNotification) {
            existingNotification.remove();
        }
        
        // Create notification element
        const notification = document.createElement('div');
        notification.className = 'status-notification';
        
        // Set styles based on notification type
        const styles = {
            info: { bg: '#d1ecf1', color: '#0c5460', border: '#bee5eb' },
            success: { bg: '#d4edda', color: '#155724', border: '#c3e6cb' },
            warning: { bg: '#fff3cd', color: '#856404', border: '#ffeaa7' },
            error: { bg: '#f8d7da', color: '#721c24', border: '#f5c6cb' }
        };
        
        const style = styles[type] || styles.info;
        
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${style.bg};
            color: ${style.color};
            padding: 15px 20px;
            border-radius: 8px;
            border: 1px solid ${style.border};
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 10000;
            max-width: 350px;
            animation: slideIn 0.3s ease-out;
        `;
        
        notification.innerHTML = `
            <div style="display: flex; align-items: center; gap: 10px;">
                <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : type === 'warning' ? 'fa-exclamation-triangle' : 'fa-info-circle'}"></i>
                <span>${message}</span>
                <button onclick="this.parentElement.parentElement.remove()" style="background: none; border: none; color: ${style.color}; font-size: 18px; cursor: pointer; margin-left: auto;">×</button>
            </div>
        `;
        
        // Add CSS animation if not already present
        if (!document.querySelector('style[data-notification-styles]')) {
            const styleElement = document.createElement('style');
            styleElement.setAttribute('data-notification-styles', 'true');
            styleElement.textContent = `
                @keyframes slideIn {
                    from { transform: translateX(100%); opacity: 0; }
                    to { transform: translateX(0); opacity: 1; }
                }
            `;
            document.head.appendChild(styleElement);
        }
        
        // Add to page
        document.body.appendChild(notification);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 5000);
    }
});
