<?php
// Promo Code Validation and Management Functions

/**
 * Validate and apply promo code
 * @param string $code The promo code to validate
 * @param float $order_amount The total order amount
 * @param int $customer_id The customer ID (optional)
 * @return array Result array with success status and discount information
 */
function validatePromoCode($code, $order_amount, $customer_id = null) {
    global $connection;
    
    $code = trim(strtoupper($code));
    
    // Get promo code details
    $query = "SELECT * FROM promo_codes WHERE code = ? AND status = 'active'";
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, "s", $code);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) == 0) {
        return [
            'success' => false,
            'message' => 'Invalid promo code.'
        ];
    }
    
    $promo = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    // Check if promo code is within valid date range
    $current_time = time();
    $valid_from = strtotime($promo['valid_from']);
    $valid_until = strtotime($promo['valid_until']);
    
    if ($current_time < $valid_from) {
        return [
            'success' => false,
            'message' => 'Promo code is not yet valid.'
        ];
    }
    
    if ($current_time > $valid_until) {
        return [
            'success' => false,
            'message' => 'Promo code has expired.'
        ];
    }
    
    // Check if usage limit has been reached
    if ($promo['used_count'] >= $promo['usage_limit']) {
        return [
            'success' => false,
            'message' => 'Promo code usage limit has been reached.'
        ];
    }
    
    // Check minimum order amount
    if ($order_amount < $promo['min_order_amount']) {
        return [
            'success' => false,
            'message' => 'Minimum order amount of ₱' . number_format($promo['min_order_amount'], 2) . ' required for this promo code.'
        ];
    }
    
    // Calculate discount amount
    $discount_amount = 0;
    
    if ($promo['discount_type'] == 'percentage') {
        $discount_amount = ($order_amount * $promo['discount_value']) / 100;
        
        // Apply maximum discount limit if set
        if ($promo['max_discount_amount'] > 0 && $discount_amount > $promo['max_discount_amount']) {
            $discount_amount = $promo['max_discount_amount'];
        }
    } else {
        $discount_amount = $promo['discount_value'];
        
        // Ensure discount doesn't exceed order amount
        if ($discount_amount > $order_amount) {
            $discount_amount = $order_amount;
        }
    }
    
    // Calculate final amount after discount
    $final_amount = $order_amount - $discount_amount;
    
    return [
        'success' => true,
        'message' => 'Promo code applied successfully!',
        'promo_id' => $promo['id'],
        'discount_type' => $promo['discount_type'],
        'discount_value' => $promo['discount_value'],
        'discount_amount' => $discount_amount,
        'original_amount' => $order_amount,
        'final_amount' => $final_amount,
        'savings' => $discount_amount
    ];
}

/**
 * Apply promo code and update usage count
 * @param int $promo_id The promo code ID
 * @param int $customer_id The customer ID
 * @param float $discount_amount The discount amount applied
 * @param int $order_id The order ID (optional)
 * @return bool Success status
 */
function applyPromoCode($promo_id, $customer_id, $discount_amount, $order_id = null) {
    global $connection;
    
    // Start transaction
    mysqli_begin_transaction($connection);
    
    try {
        // Update promo code usage count
        $update_query = "UPDATE promo_codes SET used_count = used_count + 1 WHERE id = ?";
        $stmt = mysqli_prepare($connection, $update_query);
        mysqli_stmt_bind_param($stmt, "i", $promo_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        // Record usage in promo_code_usage table
        $usage_query = "INSERT INTO promo_code_usage (promo_code_id, customer_id, order_id, discount_amount) VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($connection, $usage_query);
        mysqli_stmt_bind_param($stmt, "iiid", $promo_id, $customer_id, $order_id, $discount_amount);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        // Commit transaction
        mysqli_commit($connection);
        return true;
        
    } catch (Exception $e) {
        // Rollback transaction on error
        mysqli_rollback($connection);
        return false;
    }
}

/**
 * Get active promo codes for display
 * @return array List of active promo codes
 */
function getActivePromoCodes() {
    global $connection;
    
    $query = "SELECT * FROM promo_codes 
              WHERE status = 'active' 
              AND valid_from <= NOW() 
              AND valid_until >= NOW() 
              AND used_count < usage_limit
              ORDER BY created_at DESC";
    
    $result = mysqli_query($connection, $query);
    $promos = [];
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $promos[] = $row;
        }
    }
    
    return $promos;
}

/**
 * Get promo code usage statistics
 * @param int $promo_id The promo code ID
 * @return array Usage statistics
 */
function getPromoCodeStats($promo_id) {
    global $connection;
    
    $query = "SELECT 
                pc.code,
                pc.description,
                pc.used_count,
                pc.usage_limit,
                pc.valid_from,
                pc.valid_until,
                COUNT(pcu.id) as total_uses,
                SUM(pcu.discount_amount) as total_discount_given
              FROM promo_codes pc
              LEFT JOIN promo_code_usage pcu ON pc.id = pcu.promo_code_id
              WHERE pc.id = ?
              GROUP BY pc.id";
    
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, "i", $promo_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    return mysqli_fetch_assoc($result);
}
?>
