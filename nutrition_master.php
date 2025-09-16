<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

require_once 'db.php';
$database = new Database();
$conn = $database->getConnection();

class NutritionMaster {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function getAll() {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM nutrition_master ORDER BY name ASC");
            $stmt->execute();
            return ['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    public function add($name) {
        try {
            $stmt = $this->conn->prepare("INSERT INTO nutrition_master (name) VALUES (:name)");
            $stmt->bindParam(':name', $name);
            $stmt->execute();
            return ['success' => true, 'message' => 'Added successfully', 'data' => ['nutrition_id' => $this->conn->lastInsertId(), 'name' => $name]];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Insert error: ' . $e->getMessage()];
        }
    }

    public function update($id, $name) {
        try {
            $stmt = $this->conn->prepare("UPDATE nutrition_master SET name = :name WHERE nutrition_id = :id");
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            return ['success' => true, 'message' => 'Updated successfully'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Update error: ' . $e->getMessage()];
        }
    }

    public function delete($id) {
        try {
            $stmt = $this->conn->prepare("DELETE FROM nutrition_master WHERE nutrition_id = :id");
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            return ['success' => true, 'message' => 'Deleted successfully'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Delete error: ' . $e->getMessage()];
        }
    }
}

$nutrition = new NutritionMaster($conn);
$method = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents('php://input'), true);

switch ($method) {
    case 'GET':
        echo json_encode($nutrition->getAll());
        break;
    case 'POST':
        $name = $data['name'] ?? '';
        echo json_encode(empty($name) ? ['success' => false, 'message' => 'Name required'] : $nutrition->add($name));
        break;
    case 'PUT':
        $id = $data['id'] ?? 0;
        $name = $data['name'] ?? '';
        echo json_encode($id && $name ? $nutrition->update($id, $name) : ['success' => false, 'message' => 'ID and name required']);
        break;
    case 'DELETE':
        $id = $data['id'] ?? 0;
        echo json_encode($id ? $nutrition->delete($id) : ['success' => false, 'message' => 'ID required']);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}