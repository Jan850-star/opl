<?php
require_once 'db_connect.php';
session_start();

// Check if user is logged in as admin
if (!isset($_SESSION['admin_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: admin_login.php');
    exit();
}

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $product_name = trim($_POST['product_name']);
    $product_description = trim($_POST['product_description']);
    $product_price = floatval($_POST['product_price']);
    $product_category = trim($_POST['product_category']);
    $product_image = $_FILES['product_image'];
    $is_available = isset($_POST['is_available']) ? 1 : 0;
    
    // Validation
    if (empty($product_name) || empty($product_description) || $product_price <= 0 || empty($product_category)) {
        $error_message = "All fields are required and price must be greater than 0.";
    } else {
        try {
            // Handle image upload
            $image_path = '';
            if ($product_image['error'] == 0) {
                $upload_dir = 'uploads/products/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $file_extension = pathinfo($product_image['name'], PATHINFO_EXTENSION);
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
                
                if (in_array(strtolower($file_extension), $allowed_extensions)) {
                    $image_name = uniqid() . '.' . $file_extension;
                    $image_path = $upload_dir . $image_name;
                    
                    if (!move_uploaded_file($product_image['tmp_name'], $image_path)) {
                        $error_message = "Failed to upload image.";
                    }
                } else {
                    $error_message = "Invalid image format. Only JPG, PNG, and GIF are allowed.";
                }
            }
            
            if (empty($error_message)) {
                // Insert new product
                $insert_product = $pdo->prepare("
                    INSERT INTO products (product_name, product_description, product_price, product_category, product_image, is_available, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW())
                ");
                
                if ($insert_product->execute([$product_name, $product_description, $product_price, $product_category, $image_path, $is_available])) {
                    $success_message = "Product added successfully!";
                    // Clear form data
                    $_POST = [];
                } else {
                    $error_message = "Failed to add product. Please try again.";
                }
            }
        } catch (PDOException $e) {
            $error_message = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product - Chowking Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
        }
        .form-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
        }
        .form-control, .form-select {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }
        .form-control:focus, .form-select:focus {
            border-color: #2c3e50;
            box-shadow: 0 0 0 0.2rem rgba(44, 62, 80, 0.25);
        }
        .btn-primary {
            background: linear-gradient(45deg, #2c3e50, #34495e);
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(44, 62, 80, 0.3);
        }
        .alert {
            border-radius: 10px;
            border: none;
        }
        .logo {
            color: #2c3e50;
            font-size: 2.5rem;
            font-weight: bold;
        }
        .admin-badge {
            background: linear-gradient(45deg, #e74c3c, #c0392b);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }
        .image-preview {
            max-width: 200px;
            max-height: 200px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <a class="navbar-brand fw-bold" href="admin_dashboard.php">
                <i class="fas fa-user-shield me-2"></i>Chowking Admin
            </a>
            <div class="navbar-nav ms-auto">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user me-2"></i><?php echo htmlspecialchars($_SESSION['admin_name']); ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="admin_dashboard.php">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a></li>
                        <li><a class="dropdown-item" href="manage_products.php">
                            <i class="fas fa-utensils me-2"></i>Manage Products
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                        </a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="form-container p-5">
                    <div class="text-center mb-4">
                        <div class="logo">
                            <i class="fas fa-plus-circle me-2"></i>Add New Product
                        </div>
                        <div class="admin-badge d-inline-block mt-2 mb-3">
                            <i class="fas fa-crown me-1"></i>Product Management
                        </div>
                        <p class="text-muted">Add a new product to the menu</p>
                    </div>

                    <?php if ($error_message): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error_message); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($success_message): ?>
                        <div class="alert alert-success" role="alert">
                            <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success_message); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="product_name" class="form-label">
                                    <i class="fas fa-utensils me-2"></i>Product Name
                                </label>
                                <input type="text" class="form-control" id="product_name" name="product_name" 
                                       value="<?php echo htmlspecialchars($_POST['product_name'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="product_category" class="form-label">
                                    <i class="fas fa-tags me-2"></i>Category
                                </label>
                                <select class="form-select" id="product_category" name="product_category" required>
                                    <option value="">Select Category</option>
                                    <option value="Rice Meals" <?php echo (($_POST['product_category'] ?? '') === 'Rice Meals') ? 'selected' : ''; ?>>Rice Meals</option>
                                    <option value="Noodles" <?php echo (($_POST['product_category'] ?? '') === 'Noodles') ? 'selected' : ''; ?>>Noodles</option>
                                    <option value="Appetizers" <?php echo (($_POST['product_category'] ?? '') === 'Appetizers') ? 'selected' : ''; ?>>Appetizers</option>
                                    <option value="Beverages" <?php echo (($_POST['product_category'] ?? '') === 'Beverages') ? 'selected' : ''; ?>>Beverages</option>
                                    <option value="Desserts" <?php echo (($_POST['product_category'] ?? '') === 'Desserts') ? 'selected' : ''; ?>>Desserts</option>
                                    <option value="Combo Meals" <?php echo (($_POST['product_category'] ?? '') === 'Combo Meals') ? 'selected' : ''; ?>>Combo Meals</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="product_description" class="form-label">
                                <i class="fas fa-align-left me-2"></i>Description
                            </label>
                            <textarea class="form-control" id="product_description" name="product_description" 
                                      rows="4" required><?php echo htmlspecialchars($_POST['product_description'] ?? ''); ?></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="product_price" class="form-label">
                                    <i class="fas fa-dollar-sign me-2"></i>Price (₱)
                                </label>
                                <input type="number" class="form-control" id="product_price" name="product_price" 
                                       step="0.01" min="0" value="<?php echo htmlspecialchars($_POST['product_price'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="product_image" class="form-label">
                                    <i class="fas fa-image me-2"></i>Product Image
                                </label>
                                <input type="file" class="form-control" id="product_image" name="product_image" 
                                       accept="image/*" onchange="previewImage(this)">
                                <div id="imagePreview" class="mt-2"></div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_available" name="is_available" 
                                       <?php echo (($_POST['is_available'] ?? '') === '1') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="is_available">
                                    <i class="fas fa-check-circle me-2"></i>Available for ordering
                                </label>
                            </div>
                        </div>

                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-plus me-2"></i>Add Product
                            </button>
                        </div>
                    </form>

                    <div class="text-center mt-4">
                        <a href="admin_dashboard.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                        </a>
                        <a href="manage_products.php" class="btn btn-outline-primary ms-2">
                            <i class="fas fa-list me-2"></i>View All Products
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function previewImage(input) {
            const preview = document.getElementById('imagePreview');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = '<img src="' + e.target.result + '" class="image-preview" alt="Preview">';
                }
                reader.readAsDataURL(input.files[0]);
            } else {
                preview.innerHTML = '';
            }
        }
    </script>
</body>
</html>
