// Main JavaScript for China Orange Inc

document.addEventListener('DOMContentLoaded', function() {
    // Price calculation for order form
    const weightInput = document.getElementById('weight');
    const totalPriceInput = document.getElementById('totalPrice');
    const orderTotalSpan = document.getElementById('orderTotal');
    const pricePerKg = 2.50;

    if (weightInput) {
        weightInput.addEventListener('input', function() {
            const weight = parseFloat(this.value) || 0;
            const total = weight * pricePerKg;
            
            if (totalPriceInput) {
                totalPriceInput.value = '$' + total.toFixed(2);
            }
            if (orderTotalSpan) {
                orderTotalSpan.textContent = total.toFixed(2);
            }
        });
    }

    // Order form submission
    const orderForm = document.getElementById('orderForm');
    if (orderForm) {
        orderForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const weight = parseFloat(document.getElementById('weight').value);
            const address = document.getElementById('address').value.trim();
            const notes = document.getElementById('notes').value.trim();
            
            // Validation
            if (weight < 5) {
                showAlert('Minimum order is 5kg', 'danger');
                return;
            }
            
            if (!address) {
                showAlert('Please provide a shipping address', 'danger');
                return;
            }
            
            // Submit order
            submitOrder({
                weight: weight,
                address: address,
                notes: notes,
                total: weight * pricePerKg
            });
        });
    }

    // Chat functionality
    initializeChat();
    
    // Auto-refresh dashboard data
    if (window.location.pathname.includes('dashboard')) {
        setInterval(refreshDashboardData, 30000); // Refresh every 30 seconds
    }
});

function submitOrder(orderData) {
    const submitBtn = document.querySelector('#orderForm button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    // Show loading state
    submitBtn.innerHTML = '<span class="loading"></span> Processing...';
    submitBtn.disabled = true;
    
    fetch('/api/orders.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(orderData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (data.requiresLogin) {
                showAlert('Please login or register to complete your order', 'info');
                setTimeout(() => {
                    window.location.href = '/login.php?redirect=' + encodeURIComponent(window.location.pathname);
                }, 2000);
            } else {
                showAlert('Order placed successfully!', 'success');
                document.getElementById('orderForm').reset();
                setTimeout(() => {
                    window.location.href = '/dashboard.php';
                }, 2000);
            }
        } else {
            showAlert(data.message || 'Failed to place order', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('An error occurred. Please try again.', 'danger');
    })
    .finally(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
}

function showAlert(message, type = 'info') {
    // Remove existing alerts
    const existingAlerts = document.querySelectorAll('.alert-custom');
    existingAlerts.forEach(alert => alert.remove());
    
    // Create new alert
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-dismissible fade show alert-custom`;
    alert.style.cssText = 'position: fixed; top: 100px; right: 20px; z-index: 9999; min-width: 300px;';
    alert.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(alert);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (alert.parentNode) {
            alert.remove();
        }
    }, 5000);
}

function initializeChat() {
    const chatForm = document.getElementById('chatForm');
    const chatMessages = document.getElementById('chatMessages');
    
    if (chatForm) {
        chatForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const messageInput = document.getElementById('messageInput');
            const message = messageInput.value.trim();
            
            if (!message) return;
            
            // Add message to chat
            addChatMessage(message, 'user');
            messageInput.value = '';
            
            // Send to server
            sendChatMessage(message);
        });
    }
    
    // Load chat history
    if (chatMessages) {
        loadChatHistory();
        
        // Auto-refresh chat every 10 seconds
        setInterval(loadChatHistory, 10000);
    }
}

function addChatMessage(message, sender, timestamp = null) {
    const chatMessages = document.getElementById('chatMessages');
    if (!chatMessages) return;
    
    const messageDiv = document.createElement('div');
    messageDiv.className = `chat-message ${sender}`;
    
    const time = timestamp ? new Date(timestamp) : new Date();
    const timeString = time.toLocaleTimeString();
    
    messageDiv.innerHTML = `
        <div>${message}</div>
        <div class="chat-timestamp">${timeString}</div>
    `;
    
    chatMessages.appendChild(messageDiv);
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

function sendChatMessage(message) {
    fetch('/api/chat.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ message: message })
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            showAlert(data.message || 'Failed to send message', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Failed to send message', 'danger');
    });
}

function loadChatHistory() {
    fetch('/api/chat.php')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const chatMessages = document.getElementById('chatMessages');
            if (chatMessages) {
                chatMessages.innerHTML = '';
                data.messages.forEach(msg => {
                    addChatMessage(
                        msg.message, 
                        msg.is_from_admin ? 'admin' : 'user',
                        msg.created_at
                    );
                });
            }
        }
    })
    .catch(error => {
        console.error('Error loading chat:', error);
    });
}

function refreshDashboardData() {
    // Refresh order status and balance
    fetch('/api/dashboard.php')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update balance
            const balanceElements = document.querySelectorAll('.user-balance');
            balanceElements.forEach(el => {
                el.textContent = '$' + parseFloat(data.balance).toFixed(2);
            });
            
            // Update order counts
            if (data.orderCounts) {
                Object.keys(data.orderCounts).forEach(status => {
                    const element = document.getElementById(`count-${status}`);
                    if (element) {
                        element.textContent = data.orderCounts[status];
                    }
                });
            }
        }
    })
    .catch(error => {
        console.error('Error refreshing dashboard:', error);
    });
}

// Utility functions
function formatCurrency(amount) {
    return '$' + parseFloat(amount).toFixed(2);
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
}

function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

// Smooth scrolling for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});
