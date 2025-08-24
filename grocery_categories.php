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
        getGroceryCategories($db);
        break;
    case 'POST':
        addGroceryCategory($db);
        break;
    case 'PUT':
        updateGroceryCategory($db);
        break;
    case 'DELETE':
        deleteGroceryCategory($db);
        break;
    default:
        sendResponse(false, 'Method not allowed');
        break;
}

function getGroceryCategories($db) {
    try {
        $query = "SELECT maste_cat_food_id, name, icon_url, module, created_at FROM master_categories WHERE module = 'Grocery' ORDER BY created_at DESC";
        $stmt = $db->prepare($query);
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
        }
        
        sendResponse(true, 'Grocery categories retrieved successfully', $categories);
    } catch(PDOException $exception) {
        sendResponse(false, 'Error: ' . $exception->getMessage());
    }
}

function addGroceryCategory($db) {
    $name = '';
    $icon_url = '';
    $module = 'Grocery'; // Fixed to Grocery module
    
    $content_type = $_SERVER['CONTENT_TYPE'] ?? '';
    
    if (strpos($content_type, 'multipart/form-data') !== false) {
        $name = $_POST['name'] ?? '';
        $icon_url = $_POST['icon_url'] ?? '';
        
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploaded_image_path = handleGroceryImageUpload($_FILES['image']);
            if ($uploaded_image_path) {
                $icon_url = $uploaded_image_path;
            }
        }
    } else {
        $data = json_decode(file_get_contents("php://input"), true);
        $name = $data['name'] ?? '';
        $icon_url = $data['icon_url'] ?? '';
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
            
            sendResponse(true, 'Grocery category added successfully', [
                'id' => $new_category['maste_cat_food_id'],
                'name' => $new_category['name'],
                'icon_url' => $new_category['icon_url'],
                'module' => $new_category['module'],
                'created_at' => $new_category['created_at']
            ]);
        } else {
            sendResponse(false, 'Failed to add grocery category');
        }
    } catch(PDOException $exception) {
        sendResponse(false, 'Error: ' . $exception->getMessage());
    }
}

function updateGroceryCategory($db) {
    $id = '';
    $name = '';
    $icon_url = '';
    $module = 'Grocery'; // Fixed to Grocery module
    
    $content_type = $_SERVER['CONTENT_TYPE'] ?? '';
    
    // Check if this is a multipart request (including method override)
    if (strpos($content_type, 'multipart/form-data') !== false) {
        $id = $_POST['id'] ?? '';
        $name = $_POST['name'] ?? '';
        $icon_url = $_POST['icon_url'] ?? '';
        
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploaded_image_path = handleGroceryImageUpload($_FILES['image']);
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
        $query = "UPDATE master_categories SET name = :name, icon_url = :icon_url WHERE maste_cat_food_id = :id AND module = 'Grocery'";
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
            
            sendResponse(true, 'Grocery category updated successfully', [
                'id' => $updated_category['maste_cat_food_id'],
                'name' => $updated_category['name'],
                'icon_url' => $updated_category['icon_url'],
                'module' => $updated_category['module'],
                'created_at' => $updated_category['created_at']
            ]);
        } else {
            sendResponse(false, 'Failed to update grocery category');
        }
    } catch(PDOException $exception) {
        sendResponse(false, 'Error: ' . $exception->getMessage());
    }
}

function deleteGroceryCategory($db) {
    $data = json_decode(file_get_contents("php://input"), true);
    
    $error = validateRequired($data, ['id']);
    if ($error) {
        sendResponse(false, $error);
        return;
    }
    
    try {
        $query = "DELETE FROM master_categories WHERE maste_cat_food_id = :id AND module = 'Grocery'";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $data['id']);
        
        if ($stmt->execute()) {
            sendResponse(true, 'Grocery category deleted successfully');
        } else {
            sendResponse(false, 'Failed to delete grocery category');
        }
    } catch(PDOException $exception) {
        sendResponse(false, 'Error: ' . $exception->getMessage());
    }
}

function handleGroceryImageUpload($file) {
    $upload_dir = 'master_cat_grocessory_img/';
    
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
    
    $new_filename = uniqid('grocery_cat_', true) . '.' . $file_extension;
    $upload_path = $upload_dir . $new_filename;
    
    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        return $upload_path;
    } else {
        sendResponse(false, 'Failed to upload image');
        return false;
    }
}
?>
