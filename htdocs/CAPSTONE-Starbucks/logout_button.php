<?php
// Logout Button Component
// This file can be included in other pages to add a logout button

// Check if user is logged in
$is_logged_in = isset($_SESSION['customer_id']) || isset($_SESSION['admin_id']);
$user_type = '';
$redirect_url = '';

if (isset($_SESSION['customer_id'])) {
    $user_type = 'customer';
    $redirect_url = 'customer_dashboard.php';
} elseif (isset($_SESSION['admin_id'])) {
    $user_type = 'admin';
    $redirect_url = 'admin_dashboard.php';
}
?>

<?php if ($is_logged_in): ?>
<div class="logout-section">
    <a href="logout_confirm.php" class="logout-btn" title="Logout from your account">
        <span class="logout-icon">🚪</span>
        <span class="logout-text">Logout</span>
    </a>
</div>

<style>
.logout-section {
    display: inline-block;
}

.logout-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.6rem 1.2rem;
    background: linear-gradient(135deg, #dc3545, #c82333);
    color: white;
    text-decoration: none;
    border-radius: 8px;
    font-weight: 500;
    font-size: 0.9rem;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(220, 53, 69, 0.3);
    border: none;
    cursor: pointer;
}

.logout-btn:hover {
    background: linear-gradient(135deg, #c82333, #bd2130);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(220, 53, 69, 0.4);
    color: white;
    text-decoration: none;
}

.logout-btn:active {
    transform: translateY(0);
    box-shadow: 0 2px 6px rgba(220, 53, 69, 0.3);
}

.logout-icon {
    font-size: 1rem;
}

.logout-text {
    font-weight: 600;
}

/* Responsive design */
@media (max-width: 768px) {
    .logout-btn {
        padding: 0.5rem 1rem;
        font-size: 0.8rem;
    }
    
    .logout-text {
        display: none;
    }
    
    .logout-icon {
        font-size: 1.2rem;
    }
}

/* Alternative styles for different contexts */
.logout-btn.small {
    padding: 0.4rem 0.8rem;
    font-size: 0.8rem;
}

.logout-btn.large {
    padding: 0.8rem 1.5rem;
    font-size: 1rem;
}

.logout-btn.outline {
    background: transparent;
    color: #dc3545;
    border: 2px solid #dc3545;
    box-shadow: none;
}

.logout-btn.outline:hover {
    background: #dc3545;
    color: white;
}
</style>

<script>
// Add confirmation dialog
document.addEventListener('DOMContentLoaded', function() {
    const logoutBtn = document.querySelector('.logout-btn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function(e) {
            // Let the confirmation page handle the confirmation
            // This just ensures the link works properly
        });
    }
});
</script>
<?php endif; ?>
