<?php
require_once 'db.php';

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        getGrocerySubCategories($db);
        break;
    case 'POST':
        addGrocerySubCategory($db);
        break;
    case 'PUT':
        updateGrocerySubCategory($db);
        break;
    case 'DELETE':
        deleteGrocerySubCategory($db);
        break;
    default:
        sendResponse(false, 'Method not allowed');
        break;
}

function getGrocerySubCategories($db) {
    try {
        $query = "SELECT sc.id, sc.name, sc.description, sc.master_cat_food_id, 
                         mc.name as master_category_name, mc.module, sc.created_at
                  FROM sub_categories sc
                  INNER JOIN master_categories mc ON sc.master_cat_food_id = mc.maste_cat_food_id
                  WHERE mc.module = 'Grocery'
                  ORDER BY sc.created_at DESC";
        
        $stmt = $db->prepare($query);
        $stmt->execute();
        
        $subCategories = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $subCategories[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'description' => $row['description'],
                'master_cat_food_id' => $row['master_cat_food_id'],
                'master_category_name' => $row['master_category_name'],
                'module' => $row['module'],
                'created_at' => $row['created_at']
            ];
        }
        
        sendResponse(true, 'Grocery sub-categories retrieved successfully', $subCategories);
    } catch(PDOException $exception) {
        sendResponse(false, 'Error: ' . $exception->getMessage());
    }
}

function addGrocerySubCategory($db) {
    $data = json_decode(file_get_contents("php://input"), true);
    
    $error = validateRequired($data, ['name', 'master_cat_food_id']);
    if ($error) {
        sendResponse(false, $error);
        return;
    }
    
    $name = $data['name'];
    $description = $data['description'] ?? '';
    $master_cat_food_id = $data['master_cat_food_id'];
    
    try {
        // Verify that the master category belongs to Grocery module
        $verify_query = "SELECT maste_cat_food_id FROM master_categories WHERE maste_cat_food_id = :id AND module = 'Grocery'";
        $verify_stmt = $db->prepare($verify_query);
        $verify_stmt->bindParam(':id', $master_cat_food_id);
        $verify_stmt->execute();
        
        if ($verify_stmt->rowCount() === 0) {
            sendResponse(false, 'Invalid master category ID or category does not belong to Grocery module');
            return;
        }
        
        $query = "INSERT INTO sub_categories (name, description, master_cat_food_id, created_at) VALUES (:name, :description, :master_cat_food_id, NOW())";
        $stmt = $db->prepare($query);
        
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':master_cat_food_id', $master_cat_food_id);
        
        if ($stmt->execute()) {
            $sub_category_id = $db->lastInsertId();
            
            $select_query = "SELECT sc.id, sc.name, sc.description, sc.master_cat_food_id, 
                                   mc.name as master_category_name, mc.module, sc.created_at
                            FROM sub_categories sc
                            INNER JOIN master_categories mc ON sc.master_cat_food_id = mc.maste_cat_food_id
                            WHERE sc.id = :id";
            $select_stmt = $db->prepare($select_query);
            $select_stmt->bindParam(':id', $sub_category_id);
            $select_stmt->execute();
            
            $new_sub_category = $select_stmt->fetch(PDO::FETCH_ASSOC);
            
            sendResponse(true, 'Grocery sub-category added successfully', $new_sub_category);
        } else {
            sendResponse(false, 'Failed to add grocery sub-category');
        }
    } catch(PDOException $exception) {
        sendResponse(false, 'Error: ' . $exception->getMessage());
    }
}

function updateGrocerySubCategory($db) {
    $data = json_decode(file_get_contents("php://input"), true);
    
    $error = validateRequired($data, ['id', 'name', 'master_cat_food_id']);
    if ($error) {
        sendResponse(false, $error);
        return;
    }
    
    $id = $data['id'];
    $name = $data['name'];
    $description = $data['description'] ?? '';
    $master_cat_food_id = $data['master_cat_food_id'];
    
    try {
        // Verify that the master category belongs to Grocery module
        $verify_query = "SELECT maste_cat_food_id FROM master_categories WHERE maste_cat_food_id = :id AND module = 'Grocery'";
        $verify_stmt = $db->prepare($verify_query);
        $verify_stmt->bindParam(':id', $master_cat_food_id);
        $verify_stmt->execute();
        
        if ($verify_stmt->rowCount() === 0) {
            sendResponse(false, 'Invalid master category ID or category does not belong to Grocery module');
            return;
        }
        
        $query = "UPDATE sub_categories SET name = :name, description = :description, master_cat_food_id = :master_cat_food_id WHERE id = :id";
        $stmt = $db->prepare($query);
        
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':master_cat_food_id', $master_cat_food_id);
        
        if ($stmt->execute()) {
            $select_query = "SELECT sc.id, sc.name, sc.description, sc.master_cat_food_id, 
                                   mc.name as master_category_name, mc.module, sc.created_at
                            FROM sub_categories sc
                            INNER JOIN master_categories mc ON sc.master_cat_food_id = mc.maste_cat_food_id
                            WHERE sc.id = :id";
            $select_stmt = $db->prepare($select_query);
            $select_stmt->bindParam(':id', $id);
            $select_stmt->execute();
            
            $updated_sub_category = $select_stmt->fetch(PDO::FETCH_ASSOC);
            
            sendResponse(true, 'Grocery sub-category updated successfully', $updated_sub_category);
        } else {
            sendResponse(false, 'Failed to update grocery sub-category');
        }
    } catch(PDOException $exception) {
        sendResponse(false, 'Error: ' . $exception->getMessage());
    }
}

function deleteGrocerySubCategory($db) {
    $data = json_decode(file_get_contents("php://input"), true);
    
    $error = validateRequired($data, ['id']);
    if ($error) {
        sendResponse(false, $error);
        return;
    }
    
    try {
        $query = "DELETE FROM sub_categories WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $data['id']);
        
        if ($stmt->execute()) {
            sendResponse(true, 'Grocery sub-category deleted successfully');
        } else {
            sendResponse(false, 'Failed to delete grocery sub-category');
        }
    } catch(PDOException $exception) {
        sendResponse(false, 'Error: ' . $exception->getMessage());
    }
}

// Get master categories for Grocery module (for dropdown)
function getGroceryMasterCategories($db) {
    try {
        $query = "SELECT maste_cat_food_id, name FROM master_categories WHERE module = 'Grocery' ORDER BY name ASC";
        $stmt = $db->prepare($query);
        $stmt->execute();
        
        $categories = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $categories[] = [
                'id' => $row['maste_cat_food_id'],
                'name' => $row['name']
            ];
        }
        
        sendResponse(true, 'Grocery master categories retrieved successfully', $categories);
    } catch(PDOException $exception) {
        sendResponse(false, 'Error: ' . $exception->getMessage());
    }
}
?>
