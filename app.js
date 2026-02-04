/*
 * MAIN APPLICATION JAVASCRIPT
 * Client-side logic, camera, forms, PWA
 */

// ============================================================
// CONSTANTS & STATE
// ============================================================

const API_URL = './api.php';
let currentPhoto = null;
let currentPhotoType = null; // 'punch_in', 'punch_out', 'site_visit'
let cameraStream = null;
let userLocation = null;

// ============================================================
// UTILITY FUNCTIONS
// ============================================================

/**
 * Show toast notification
 */
function showToast(message, type = 'success', duration = 3000) {
    const toast = document.getElementById('toast');
    toast.textContent = message;
    toast.className = `toast show ${type}`;

    setTimeout(() => {
        toast.classList.remove('show');
    }, duration);
}

/**
 * Show/hide loading indicator
 */
function showLoading(message = 'Processing...') {
    const indicator = document.getElementById('loadingIndicator');
    const text = document.getElementById('loadingText');
    text.textContent = message;
    indicator.style.display = 'flex';
}

function hideLoading() {
    document.getElementById('loadingIndicator').style.display = 'none';
}

/**
 * Toggle menu drawer
 */
function toggleMenu() {
    const drawer = document.getElementById('menuDrawer');
    drawer.classList.toggle('active');

    // Close on background click
    if (drawer.classList.contains('active')) {
        document.addEventListener('click', closeMenuOnClick);
    }
}

function closeMenuOnClick(e) {
    const drawer = document.getElementById('menuDrawer');
    if (!drawer.contains(e.target) && !e.target.closest('.btn-icon')) {
        drawer.classList.remove('active');
        document.removeEventListener('click', closeMenuOnClick);
    }
}

function toggleAdminMenu() {
    toggleMenu();
}

/**
 * Get user geolocation
 */
async function getUserLocation() {
    return new Promise((resolve, reject) => {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    userLocation = {
                        latitude: position.coords.latitude,
                        longitude: position.coords.longitude,
                        accuracy: position.coords.accuracy
                    };
                    resolve(userLocation);
                },
                (error) => {
                    console.error('Geolocation error:', error);
                    reject(error);
                },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
            );
        } else {
            reject('Geolocation not supported');
        }
    });
}

/**
 * Calculate distance between two coordinates
 */
function calculateDistance(lat1, lon1, lat2, lon2) {
    const R = 6371000; // Earth's radius in meters
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;

    const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
              Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
              Math.sin(dLon / 2) * Math.sin(dLon / 2);

    const c = 2 * Math.asin(Math.sqrt(a));
    return R * c;
}

/**
 * Format date for display
 */
function formatDate(date) {
    if (typeof date === 'string') {
        date = new Date(date);
    }
    return date.toLocaleDateString('en-IN', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

/**
 * Format time for display
 */
function formatTime(time) {
    if (typeof time === 'string') {
        const [hours, minutes] = time.split(':');
        return `${hours}:${minutes}`;
    }
    return time;
}

// ============================================================
// CAMERA FUNCTIONS
// ============================================================

/**
 * Take punch in photo
 */
async function takePunchInPhoto() {
    currentPhotoType = 'punch_in';
    openCamera();
}

/**
 * Take punch out photo
 */
async function takePunchOutPhoto() {
    currentPhotoType = 'punch_out';
    openCamera();
}

/**
 * Open camera modal
 */
async function openCamera() {
    const modal = document.getElementById('cameraModal');
    modal.classList.add('active');

    try {
        const constraints = {
            video: {
                facingMode: 'user',
                width: { ideal: 1280 },
                height: { ideal: 720 }
            },
            audio: false
        };

        cameraStream = await navigator.mediaDevices.getUserMedia(constraints);
        const video = document.getElementById('cameraVideo');
        video.srcObject = cameraStream;
        video.play();
    } catch (error) {
        console.error('Camera error:', error);
        showToast('Unable to access camera', 'error');
        closeCameraModal();
    }
}

/**
 * Close camera modal
 */
function closeCameraModal() {
    const modal = document.getElementById('cameraModal');
    modal.classList.remove('active');

    // Stop camera stream
    if (cameraStream) {
        cameraStream.getTracks().forEach(track => track.stop());
        cameraStream = null;
    }
}

/**
 * Capture photo from camera
 */
async function capturePhoto() {
    const video = document.getElementById('cameraVideo');
    const canvas = document.createElement('canvas');
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    const ctx = canvas.getContext('2d');
    ctx.drawImage(video, 0, 0);

    canvas.toBlob((blob) => {
        currentPhoto = blob;
        displayPhotoPreview();
        closeCameraModal();

        // Enable submit button
        const submitBtn = document.getElementById(`${currentPhotoType}Submit`);
        if (submitBtn) {
            submitBtn.disabled = false;
        }
    }, 'image/jpeg', 0.8);
}

/**
 * Display photo preview
 */
function displayPhotoPreview() {
    if (!currentPhoto) return;

    const reader = new FileReader();
    reader.onload = (e) => {
        const previewId = currentPhotoType === 'punch_in' ? 'photoPreview' : 'outPhotoPreview';
        const preview = document.getElementById(previewId);

        if (preview) {
            preview.innerHTML = `
                <div style="margin-top: 16px; border-radius: 8px; overflow: hidden;">
                    <img src="${e.target.result}" style="width: 100%; height: auto; display: block;">
                    <small style="display: block; margin-top: 8px; color: #6B7280;">Photo captured ✓</small>
                </div>
            `;
        }
    };
    reader.readAsDataURL(currentPhoto);
}

/**
 * Toggle camera flash
 */
function toggleCameraFlash() {
    // Note: Flash support varies by device
    showToast('Flash not available on this device', 'warning');
}

// ============================================================
// LOGIN HANDLER
// ============================================================

async function handleLogin(e) {
    e.preventDefault();

    const loginId = document.getElementById('login_id').value;
    const pin = document.getElementById('login_pin').value;
    const errorDiv = document.getElementById('loginError');

    // Clear previous errors
    errorDiv.style.display = 'none';
    errorDiv.textContent = '';

    showLoading('Signing in...');

    try {
        const formData = new FormData();
        formData.append('action', 'login');
        formData.append('login_id', loginId);
        formData.append('pin', pin);

        const response = await fetch(API_URL, {
            method: 'POST',
            body: formData
        });

        const result = await response.json();
        hideLoading();

        if (result.success) {
            showToast('Login successful', 'success');
            setTimeout(() => {
                window.location.href = result.user.role === 'admin' ? '?page=admin' : '?page=dashboard';
            }, 500);
        } else {
            errorDiv.textContent = result.message || 'Login failed';
            errorDiv.style.display = 'block';
        }
    } catch (error) {
        hideLoading();
        console.error('Login error:', error);
        errorDiv.textContent = 'Network error. Please try again.';
        errorDiv.style.display = 'block';
    }
}

// ============================================================
// PUNCH IN/OUT MODALS
// ============================================================

function showPunchInModal() {
    document.getElementById('punchInModal').classList.add('active');
}

function closePunchInModal() {
    document.getElementById('punchInModal').classList.remove('active');
    document.getElementById('punchInForm').reset();
    document.getElementById('photoPreview').innerHTML = '';
    currentPhoto = null;
}

function showPunchOutModal() {
    document.getElementById('punchOutModal').classList.add('active');
}

function closePunchOutModal() {
    document.getElementById('punchOutModal').classList.remove('active');
    document.getElementById('punchOutForm').reset();
    document.getElementById('outPhotoPreview').innerHTML = '';
    currentPhoto = null;
}

/**
 * Handle punch in
 */
async function handlePunchIn(e) {
    e.preventDefault();

    const slot = document.getElementById('slot').value;

    if (!currentPhoto) {
        showToast('Please take a photo', 'warning');
        return;
    }

    showLoading('Getting location and punching in...');

    try {
        // Get location
        const location = await getUserLocation();

        // Prepare form data
        const formData = new FormData();
        formData.append('action', 'punch_in');
        formData.append('slot', slot);
        formData.append('latitude', location.latitude);
        formData.append('longitude', location.longitude);
        formData.append('photo', currentPhoto, 'punch_in.jpg');

        // Send to API
        const response = await fetch(API_URL, {
            method: 'POST',
            body: formData
        });

        const result = await response.json();
        hideLoading();

        if (result.success) {
            showToast(result.message, 'success');
            closePunchInModal();
            currentPhoto = null;

            // Refresh page after 1 second
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showToast(result.message, 'error');
        }
    } catch (error) {
        hideLoading();
        console.error('Punch in error:', error);
        showToast('Error: ' + error, 'error');
    }
}

/**
 * Handle punch out
 */
async function handlePunchOut(e) {
    e.preventDefault();

    const workSummary = document.getElementById('work_summary').value;

    if (!currentPhoto) {
        showToast('Please take a photo', 'warning');
        return;
    }

    if (!workSummary.trim()) {
        showToast('Work summary is required', 'warning');
        return;
    }

    showLoading('Getting location and punching out...');

    try {
        // Get location
        const location = await getUserLocation();

        // Prepare form data
        const formData = new FormData();
        formData.append('action', 'punch_out');
        formData.append('work_summary', workSummary);
        formData.append('latitude', location.latitude);
        formData.append('longitude', location.longitude);
        formData.append('photo', currentPhoto, 'punch_out.jpg');

        // Send to API
        const response = await fetch(API_URL, {
            method: 'POST',
            body: formData
        });

        const result = await response.json();
        hideLoading();

        if (result.success) {
            showToast(`Punched out - Day: ${result.day_count}`, 'success');
            closePunchOutModal();
            currentPhoto = null;

            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showToast(result.message, 'error');
        }
    } catch (error) {
        hideLoading();
        console.error('Punch out error:', error);
        showToast('Error: ' + error, 'error');
    }
}

/**
 * Start site visit
 */
async function startSiteVisit() {
    if (!confirm('Converting to Site Visit. This will lock the day as Full Day.')) {
        return;
    }

    showLoading('Converting to site visit...');

    try {
        const location = await getUserLocation();

        // For site visit, we need to take a photo
        currentPhotoType = 'site_visit';
        openCamera();

        // Wait for photo capture
        await new Promise(resolve => {
            const interval = setInterval(() => {
                if (currentPhoto) {
                    clearInterval(interval);
                    resolve();
                }
            }, 100);

            // Timeout after 5 minutes
            setTimeout(() => clearInterval(interval), 300000);
        });

        // Send conversion
        const formData = new FormData();
        formData.append('action', 'convert_site_visit');
        formData.append('latitude', location.latitude);
        formData.append('longitude', location.longitude);
        formData.append('photo', currentPhoto, 'site_visit.jpg');

        const response = await fetch(API_URL, {
            method: 'POST',
            body: formData
        });

        const result = await response.json();
        hideLoading();

        if (result.success) {
            showToast('Converted to Site Visit - Full Day locked', 'success');
            currentPhoto = null;

            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showToast(result.message, 'error');
        }
    } catch (error) {
        hideLoading();
        console.error('Site visit error:', error);
        showToast('Error: ' + error, 'error');
    }
}

// ============================================================
// LEAVE HANDLERS
// ============================================================

async function applyLeave(e) {
    e.preventDefault();

    const leaveType = document.getElementById('leave_type').value;
    const leaveDate = document.getElementById('leave_date').value;

    if (!leaveType || !leaveDate) {
        showToast('Please select leave type and date', 'warning');
        return;
    }

    showLoading('Applying leave...');

    try {
        const formData = new FormData();
        formData.append('action', 'apply_leave');
        formData.append('leave_type', leaveType);
        formData.append('leave_date', leaveDate);

        const response = await fetch(API_URL, {
            method: 'POST',
            body: formData
        });

        const result = await response.json();
        hideLoading();

        if (result.success) {
            showToast('Leave applied successfully', 'success');
            document.getElementById('leave_type').value = '';
            document.getElementById('leave_date').value = '';

            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            showToast(result.message, 'error');
        }
    } catch (error) {
        hideLoading();
        console.error('Leave error:', error);
        showToast('Error applying leave', 'error');
    }
}

// ============================================================
// EXPENSE HANDLERS
// ============================================================

async function addExpense(e) {
    e.preventDefault();

    const amount = document.getElementById('exp_amount').value;
    const date = document.getElementById('exp_date').value;
    const description = document.getElementById('exp_desc').value;
    const receipt = document.getElementById('exp_receipt').files[0] || null;

    if (!amount || !date || !description) {
        showToast('Please fill all required fields', 'warning');
        return;
    }

    showLoading('Adding expense...');

    try {
        const formData = new FormData();
        formData.append('action', 'add_expense');
        formData.append('amount', amount);
        formData.append('expense_date', date);
        formData.append('description', description);
        if (receipt) {
            formData.append('receipt_image', receipt);
        }

        const response = await fetch(API_URL, {
            method: 'POST',
            body: formData
        });

        const result = await response.json();
        hideLoading();

        if (result.success) {
            showToast('Expense added successfully', 'success');
            document.getElementById('exp_amount').value = '';
            document.getElementById('exp_date').value = '';
            document.getElementById('exp_desc').value = '';
            document.getElementById('exp_receipt').value = '';

            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            showToast(result.message, 'error');
        }
    } catch (error) {
        hideLoading();
        console.error('Expense error:', error);
        showToast('Error adding expense', 'error');
    }
}

// ============================================================
// ADMIN HANDLERS
// ============================================================

async function approveLeave(leaveId) {
    if (!confirm('Approve this leave?')) {
        return;
    }

    showLoading('Approving leave...');

    try {
        const formData = new FormData();
        formData.append('action', 'approve_leave');
        formData.append('leave_id', leaveId);

        const response = await fetch(API_URL, {
            method: 'POST',
            body: formData
        });

        const result = await response.json();
        hideLoading();

        if (result.success) {
            showToast('Leave approved', 'success');
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showToast(result.message, 'error');
        }
    } catch (error) {
        hideLoading();
        console.error('Error:', error);
        showToast('Error approving leave', 'error');
    }
}

async function rejectLeave(leaveId) {
    if (!confirm('Reject this leave?')) {
        return;
    }

    showLoading('Rejecting leave...');

    try {
        const formData = new FormData();
        formData.append('action', 'reject_leave');
        formData.append('leave_id', leaveId);

        const response = await fetch(API_URL, {
            method: 'POST',
            body: formData
        });

        const result = await response.json();
        hideLoading();

        if (result.success) {
            showToast('Leave rejected', 'success');
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showToast(result.message, 'error');
        }
    } catch (error) {
        hideLoading();
        console.error('Error:', error);
        showToast('Error rejecting leave', 'error');
    }
}

async function approveExpense(expenseId) {
    if (!confirm('Approve this expense?')) {
        return;
    }

    showLoading('Approving expense...');

    try {
        const formData = new FormData();
        formData.append('action', 'approve_expense');
        formData.append('expense_id', expenseId);

        const response = await fetch(API_URL, {
            method: 'POST',
            body: formData
        });

        const result = await response.json();
        hideLoading();

        if (result.success) {
            showToast('Expense approved', 'success');
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showToast(result.message, 'error');
        }
    } catch (error) {
        hideLoading();
        console.error('Error:', error);
        showToast('Error approving expense', 'error');
    }
}

async function rejectExpense(expenseId) {
    if (!confirm('Reject this expense?')) {
        return;
    }

    showLoading('Rejecting expense...');

    try {
        const formData = new FormData();
        formData.append('action', 'reject_expense');
        formData.append('expense_id', expenseId);

        const response = await fetch(API_URL, {
            method: 'POST',
            body: formData
        });

        const result = await response.json();
        hideLoading();

        if (result.success) {
            showToast('Expense rejected', 'success');
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showToast(result.message, 'error');
        }
    } catch (error) {
        hideLoading();
        console.error('Error:', error);
        showToast('Error rejecting expense', 'error');
    }
}

// ============================================================
// UTILITY HANDLERS
// ============================================================

function prevMonth() {
    // To be implemented by backend
    showToast('Navigation implemented server-side', 'info');
}

function nextMonth() {
    // To be implemented by backend
    showToast('Navigation implemented server-side', 'info');
}

// ============================================================
// CLOSE MODALS ON BACKGROUND CLICK
// ============================================================

document.addEventListener('DOMContentLoaded', () => {
    const modals = document.querySelectorAll('.modal');

    modals.forEach(modal => {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.classList.remove('active');
            }
        });
    });

    // Set date input to today
    const dateInputs = document.querySelectorAll('input[type="date"]');
    dateInputs.forEach(input => {
        if (!input.value) {
            input.value = new Date().toISOString().split('T')[0];
        }
    });
});

// ============================================================
// NOTIFICATION HANDLERS
// ============================================================

/**
 * Toggle notification dropdown
 */
function toggleNotifications() {
    const dropdown = document.getElementById('notificationDropdown');
    const isActive = dropdown.classList.contains('active');

    if (isActive) {
        dropdown.classList.remove('active');
    } else {
        dropdown.classList.add('active');
        loadNotifications();
    }

    // Close on outside click
    if (!isActive) {
        setTimeout(() => {
            document.addEventListener('click', closeNotificationsOnClick);
        }, 100);
    }
}

function closeNotificationsOnClick(e) {
    const dropdown = document.getElementById('notificationDropdown');
    const bell = document.querySelector('.notification-bell');

    if (!dropdown.contains(e.target) && !bell.contains(e.target)) {
        dropdown.classList.remove('active');
        document.removeEventListener('click', closeNotificationsOnClick);
    }
}

/**
 * Load notifications
 */
async function loadNotifications() {
    const list = document.getElementById('notificationList');

    try {
        const formData = new FormData();
        formData.append('action', 'get_notifications');

        const response = await fetch(API_URL, {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success && result.notifications) {
            if (result.notifications.length === 0) {
                list.innerHTML = '<div class="notification-empty">No notifications</div>';
            } else {
                list.innerHTML = result.notifications.map(notif => `
                    <div class="notification-item ${notif.is_read ? '' : 'unread'}" onclick="markNotificationRead(${notif.id}, '${notif.link || ''}')">
                        <div class="notification-icon">${getNotificationIcon(notif.type)}</div>
                        <div class="notification-content">
                            <div class="notification-title">${notif.title}</div>
                            <div class="notification-message">${notif.message}</div>
                            <div class="notification-time">${formatNotificationTime(notif.created_at)}</div>
                        </div>
                    </div>
                `).join('');
            }

            // Update badge
            updateNotificationBadge(result.unread_count);
        }
    } catch (error) {
        console.error('Error loading notifications:', error);
        list.innerHTML = '<div class="notification-empty">Error loading notifications</div>';
    }
}

/**
 * Get notification icon based on type
 */
function getNotificationIcon(type) {
    const icons = {
        'leave_approved': '✓',
        'leave_rejected': '✗',
        'expense_approved': '💰',
        'expense_rejected': '✗',
        'admin': '👤',
        'default': '🔔'
    };
    return icons[type] || icons['default'];
}

/**
 * Format notification time
 */
function formatNotificationTime(timestamp) {
    const date = new Date(timestamp);
    const now = new Date();
    const diff = Math.floor((now - date) / 1000); // seconds

    if (diff < 60) return 'Just now';
    if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
    if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`;
    if (diff < 604800) return `${Math.floor(diff / 86400)}d ago`;

    return date.toLocaleDateString();
}

/**
 * Mark notification as read
 */
async function markNotificationRead(notificationId, link) {
    try {
        const formData = new FormData();
        formData.append('action', 'mark_notification_read');
        formData.append('notification_id', notificationId);

        await fetch(API_URL, {
            method: 'POST',
            body: formData
        });

        // Reload notifications
        await loadNotifications();

        // Navigate if link exists
        if (link) {
            window.location.href = link;
        }
    } catch (error) {
        console.error('Error marking notification as read:', error);
    }
}

/**
 * Mark all notifications as read
 */
async function markAllNotificationsRead() {
    try {
        const formData = new FormData();
        formData.append('action', 'mark_all_notifications_read');

        const response = await fetch(API_URL, {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            showToast('All notifications marked as read', 'success');
            await loadNotifications();
        }
    } catch (error) {
        console.error('Error marking all notifications as read:', error);
        showToast('Error marking notifications as read', 'error');
    }
}

/**
 * Update notification badge
 */
function updateNotificationBadge(count) {
    const badge = document.getElementById('notificationBadge');

    if (badge) {
        if (count > 0) {
            badge.textContent = count;
            badge.style.display = 'flex';
        } else {
            badge.style.display = 'none';
        }
    } else if (count > 0) {
        // Create badge if it doesn't exist
        const bell = document.querySelector('.notification-bell');
        const newBadge = document.createElement('span');
        newBadge.className = 'notification-badge';
        newBadge.id = 'notificationBadge';
        newBadge.textContent = count;
        bell.appendChild(newBadge);
    }
}

/**
 * Auto-refresh notifications every 30 seconds
 */
setInterval(() => {
    if (document.getElementById('notificationDropdown')) {
        loadNotifications();
    }
}, 30000);

// ============================================================
// CONTACT MANAGEMENT HANDLERS
// ============================================================

/**
 * Add contact
 */
async function addContact(e) {
    e.preventDefault();

    const name = document.getElementById('contact_name').value;
    const role = document.getElementById('contact_role').value;
    const phone = document.getElementById('contact_phone').value;
    const email = document.getElementById('contact_email').value;

    if (!name || !role || !phone) {
        showToast('Please fill all required fields', 'warning');
        return;
    }

    showLoading('Adding contact...');

    try {
        const formData = new FormData();
        formData.append('action', 'add_contact');
        formData.append('name', name);
        formData.append('role', role);
        formData.append('phone', phone);
        formData.append('email', email);

        const response = await fetch(API_URL, {
            method: 'POST',
            body: formData
        });

        const result = await response.json();
        hideLoading();

        if (result.success) {
            showToast('Contact added successfully', 'success');
            document.getElementById('contact_name').value = '';
            document.getElementById('contact_role').value = '';
            document.getElementById('contact_phone').value = '';
            document.getElementById('contact_email').value = '';

            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showToast(result.message, 'error');
        }
    } catch (error) {
        hideLoading();
        console.error('Error:', error);
        showToast('Error adding contact', 'error');
    }
}

/**
 * Edit contact
 */
async function editContact(contactId, name, role, phone, email) {
    const newName = prompt('Name:', name);
    if (newName === null) return;

    const newRole = prompt('Role:', role);
    if (newRole === null) return;

    const newPhone = prompt('Phone:', phone);
    if (newPhone === null) return;

    const newEmail = prompt('Email:', email);
    if (newEmail === null) return;

    showLoading('Updating contact...');

    try {
        const formData = new FormData();
        formData.append('action', 'edit_contact');
        formData.append('contact_id', contactId);
        formData.append('name', newName);
        formData.append('role', newRole);
        formData.append('phone', newPhone);
        formData.append('email', newEmail);

        const response = await fetch(API_URL, {
            method: 'POST',
            body: formData
        });

        const result = await response.json();
        hideLoading();

        if (result.success) {
            showToast('Contact updated successfully', 'success');
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showToast(result.message, 'error');
        }
    } catch (error) {
        hideLoading();
        console.error('Error:', error);
        showToast('Error updating contact', 'error');
    }
}

/**
 * Delete contact
 */
async function deleteContact(contactId) {
    if (!confirm('Delete this contact?')) {
        return;
    }

    showLoading('Deleting contact...');

    try {
        const formData = new FormData();
        formData.append('action', 'delete_contact');
        formData.append('contact_id', contactId);

        const response = await fetch(API_URL, {
            method: 'POST',
            body: formData
        });

        const result = await response.json();
        hideLoading();

        if (result.success) {
            showToast('Contact deleted successfully', 'success');
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showToast(result.message, 'error');
        }
    } catch (error) {
        hideLoading();
        console.error('Error:', error);
        showToast('Error deleting contact', 'error');
    }
}

// ============================================================
// ADMIN MANAGEMENT HANDLERS
// ============================================================

/**
 * Add admin
 */
async function addAdmin(e) {
    e.preventDefault();

    const name = document.getElementById('admin_name').value;
    const email = document.getElementById('admin_email').value;
    const phone = document.getElementById('admin_phone').value;
    const pin = document.getElementById('admin_pin').value;

    if (!name || !email || !phone || !pin) {
        showToast('Please fill all required fields', 'warning');
        return;
    }

    if (pin.length !== 4 || !/^\d+$/.test(pin)) {
        showToast('PIN must be 4 digits', 'warning');
        return;
    }

    showLoading('Adding admin...');

    try {
        const formData = new FormData();
        formData.append('action', 'add_admin');
        formData.append('name', name);
        formData.append('email', email);
        formData.append('phone', phone);
        formData.append('pin', pin);

        const response = await fetch(API_URL, {
            method: 'POST',
            body: formData
        });

        const result = await response.json();
        hideLoading();

        if (result.success) {
            showToast('Admin added successfully', 'success');
            document.getElementById('admin_name').value = '';
            document.getElementById('admin_email').value = '';
            document.getElementById('admin_phone').value = '';
            document.getElementById('admin_pin').value = '';

            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showToast(result.message, 'error');
        }
    } catch (error) {
        hideLoading();
        console.error('Error:', error);
        showToast('Error adding admin', 'error');
    }
}

// ============================================================
// PWA SUPPORT (OPTIONAL - ADVANCED)
// ============================================================

// Install button (if needed)
let deferredPrompt = null;

window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPrompt = e;
});
