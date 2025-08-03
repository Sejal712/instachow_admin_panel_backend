<?php
require_once 'db.php';

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

// Handle method override for multipart requests
if ($method === 'POST' && isset($_POST['_method'])) {
    $method = $_POST['_method'];
}

switch($method) {
    case 'GET':
        getFoodCategories($db);
        break;
    case 'POST':
        addFoodCategory($db);
        break;
    case 'PUT':
        updateFoodCategory($db);
        break;
    case 'DELETE':
        deleteFoodCategory($db);
        break;
    case 'DEBUG':
        debugModules($db);
        break;
    default:
        sendResponse(false, 'Method not allowed');
        break;
}

function getFoodCategories($db) {
    try {
        // Get module filter from query parameter
        $module = $_GET['module'] ?? 'Food';
        
        // Debug logging
        error_log("DEBUG: getFoodCategories called with module: " . $module);
        error_log("DEBUG: GET parameters: " . print_r($_GET, true));
        
        // Debug: Check what modules exist in database
        $debug_query = "SELECT DISTINCT module FROM master_categories";
        $debug_stmt = $db->prepare($debug_query);
        $debug_stmt->execute();
        $modules = $debug_stmt->fetchAll(PDO::FETCH_COLUMN);
        error_log("DEBUG: Available modules in database: " . implode(', ', $modules));
        
        // Debug: Check total count for this module
        $count_query = "SELECT COUNT(*) FROM master_categories WHERE module = :module";
        $count_stmt = $db->prepare($count_query);
        $count_stmt->bindParam(':module', $module);
        $count_stmt->execute();
        $count = $count_stmt->fetchColumn();
        error_log("DEBUG: Total categories for module '$module': " . $count);
        
        $query = "SELECT maste_cat_food_id, name, icon_url, module, created_at FROM master_categories WHERE module = :module ORDER BY created_at DESC";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':module', $module);
        $stmt->execute();
        
        $categories = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $categories[] = [
                'id' => $row['maste_cat_food_id'],
                'name' => $row['name'],
                'icon_url' => $row['icon_url'],
                'module' => $row['module'],
                'created_at' => $row['created_at']
            ];
            error_log("DEBUG: Found category: " . $row['name'] . " (module: " . $row['module'] . ")");
        }
        
        // Debug logging
        error_log("DEBUG: Found " . count($categories) . " categories for module: " . $module);
        
        sendResponse(true, 'Food categories retrieved successfully', $categories);
    } catch(PDOException $exception) {
        error_log("DEBUG: Database error: " . $exception->getMessage());
        sendResponse(false, 'Error: ' . $exception->getMessage());
    }
}

function addFoodCategory($db) {
    $name = '';
    $icon_url = '';
    $module = 'Food'; // Default to Food
    
    $content_type = $_SERVER['CONTENT_TYPE'] ?? '';
    
    if (strpos($content_type, 'multipart/form-data') !== false) {
        $name = $_POST['name'] ?? '';
        $icon_url = $_POST['icon_url'] ?? '';
        $module = $_POST['module'] ?? 'Food';
        
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploaded_image_path = handleImageUpload($_FILES['image']);
            if ($uploaded_image_path) {
                $icon_url = $uploaded_image_path;
            }
        }
    } else {
        $data = json_decode(file_get_contents("php://input"), true);
        $name = $data['name'] ?? '';
        $icon_url = $data['icon_url'] ?? '';
        $module = $data['module'] ?? 'Food';
    }
    
    if (empty($name)) {
        sendResponse(false, 'Field \'name\' is required');
        return;
    }
    
    try {
        $query = "INSERT INTO master_categories (name, icon_url, module, created_at) VALUES (:name, :icon_url, :module, NOW())";
        $stmt = $db->prepare($query);
        
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':icon_url', $icon_url);
        $stmt->bindParam(':module', $module);
        
        if ($stmt->execute()) {
            $category_id = $db->lastInsertId();
            
            $select_query = "SELECT maste_cat_food_id, name, icon_url, module, created_at FROM master_categories WHERE maste_cat_food_id = :id";
            $select_stmt = $db->prepare($select_query);
            $select_stmt->bindParam(':id', $category_id);
            $select_stmt->execute();
            
            $new_category = $select_stmt->fetch(PDO::FETCH_ASSOC);
            
            sendResponse(true, 'Food category added successfully', [
                'id' => $new_category['maste_cat_food_id'],
                'name' => $new_category['name'],
                'icon_url' => $new_category['icon_url'],
                'module' => $new_category['module'],
                'created_at' => $new_category['created_at']
            ]);
        } else {
            sendResponse(false, 'Failed to add food category');
        }
    } catch(PDOException $exception) {
        sendResponse(false, 'Error: ' . $exception->getMessage());
    }
}

function updateFoodCategory($db) {
    $id = '';
    $name = '';
    $icon_url = '';
    
    $content_type = $_SERVER['CONTENT_TYPE'] ?? '';
    
    // Check if this is a multipart request (including method override)
    if (strpos($content_type, 'multipart/form-data') !== false) {
        $id = $_POST['id'] ?? '';
        $name = $_POST['name'] ?? '';
        $icon_url = $_POST['icon_url'] ?? '';
        
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploaded_image_path = handleImageUpload($_FILES['image']);
            if ($uploaded_image_path) {
                $icon_url = $uploaded_image_path;
            }
        }
    } else {
        $data = json_decode(file_get_contents("php://input"), true);
        $id = $data['id'] ?? '';
        $name = $data['name'] ?? '';
        $icon_url = $data['icon_url'] ?? '';
    }
    
    if (empty($id)) {
        sendResponse(false, 'Field \'id\' is required');
        return;
    }
    
    if (empty($name)) {
        sendResponse(false, 'Field \'name\' is required');
        return;
    }
    
    try {
        $query = "UPDATE master_categories SET name = :name, icon_url = :icon_url WHERE maste_cat_food_id = :id";
        $stmt = $db->prepare($query);
        
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':icon_url', $icon_url);
        
        if ($stmt->execute()) {
            $select_query = "SELECT maste_cat_food_id, name, icon_url, module, created_at FROM master_categories WHERE maste_cat_food_id = :id";
            $select_stmt = $db->prepare($select_query);
            $select_stmt->bindParam(':id', $id);
            $select_stmt->execute();
            
            $updated_category = $select_stmt->fetch(PDO::FETCH_ASSOC);
            
            sendResponse(true, 'Food category updated successfully', [
                'id' => $updated_category['maste_cat_food_id'],
                'name' => $updated_category['name'],
                'icon_url' => $updated_category['icon_url'],
                'module' => $updated_category['module'],
                'created_at' => $updated_category['created_at']
            ]);
        } else {
            sendResponse(false, 'Failed to update food category');
        }
    } catch(PDOException $exception) {
        sendResponse(false, 'Error: ' . $exception->getMessage());
    }
}

function deleteFoodCategory($db) {
    $data = json_decode(file_get_contents("php://input"), true);
    
    $error = validateRequired($data, ['id']);
    if ($error) {
        sendResponse(false, $error);
        return;
    }
    
    try {
        $query = "DELETE FROM master_categories WHERE maste_cat_food_id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $data['id']);
        
        if ($stmt->execute()) {
            sendResponse(true, 'Food category deleted successfully');
        } else {
            sendResponse(false, 'Failed to delete food category');
        }
    } catch(PDOException $exception) {
        sendResponse(false, 'Error: ' . $exception->getMessage());
    }
}

function debugModules($db) {
    try {
        $query = "SELECT DISTINCT module FROM master_categories";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $modules = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        sendResponse(true, 'Available modules', $modules);
    } catch(PDOException $exception) {
        sendResponse(false, 'Error: ' . $exception->getMessage());
    }
}

function handleImageUpload($file) {
    $upload_dir = 'master_cat_food_img/';
    
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    if (!in_array($file_extension, $allowed_types)) {
        sendResponse(false, 'Invalid file type. Only JPG, JPEG, PNG, GIF, and WEBP are allowed.');
        return false;
    }
    
    if ($file['size'] > 5 * 1024 * 1024) {
        sendResponse(false, 'File size too large. Maximum 5MB allowed.');
        return false;
    }
    
    $new_filename = uniqid('cat_', true) . '.' . $file_extension;
    $upload_path = $upload_dir . $new_filename;
    
    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        return $upload_path;
    } else {
        sendResponse(false, 'Failed to upload image');
        return false;
    }
}
?>
