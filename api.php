<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Content-Type: application/json; charset=UTF-8");

require_once 'config/Database.php';
require_once 'config/Auth.php';
require_once 'models/ProductModel.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    $auth = new Auth($pdo);
    $productModel = new ProductModel($pdo);
    
  
    if ($_SERVER['REQUEST_METHOD'] !== 'OPTIONS') {
        if (!$auth->validateApiKey()) {
            exit;
        }
    }
    

    $inputData = json_decode(file_get_contents("php://input"), true);
    
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
       
            if (isset($_GET['id'])) {
                $product = $productModel->getById($_GET['id']);
                if ($product) {
                    echo json_encode($product, JSON_UNESCAPED_UNICODE);
                } else {
                    http_response_code(404);
                    echo json_encode(['error' => 'Product not found']);
                }
            } else {
                $products = $productModel->getAll();
                echo json_encode($products, JSON_UNESCAPED_UNICODE);
            }
            break;
            
        case 'POST':
     
            if (!$inputData) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid JSON data']);
                break;
            }
            
     
            if (empty($inputData['name']) || !isset($inputData['price'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Name and price are required']);
                break;
            }
            
   
            if (!is_numeric($inputData['price']) || $inputData['price'] < 0) {
                http_response_code(400);
                echo json_encode(['error' => 'Price must be a positive number']);
                break;
            }
            
            $newId = $productModel->create($inputData);
            if ($newId) {
                http_response_code(201);
                echo json_encode([
                    'message' => 'Product created successfully',
                    'id' => $newId,
                    'product' => $productModel->getById($newId)
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to create product']);
            }
            break;
            
        case 'PUT':
      
            if (!$inputData) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid JSON data']);
                break;
            }
            
            if (!isset($inputData['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Product ID is required']);
                break;
            }
            
   
            if (!$productModel->exists($inputData['id'])) {
                http_response_code(404);
                echo json_encode(['error' => 'Product not found']);
                break;
            }
            
            if ($productModel->update($inputData['id'], $inputData)) {
                echo json_encode([
                    'message' => 'Product updated successfully',
                    'product' => $productModel->getById($inputData['id'])
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to update product']);
            }
            break;
            
        case 'DELETE':
 
            if (!isset($_GET['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Product ID is required']);
                break;
            }
            
   
            if (!$productModel->exists($_GET['id'])) {
                http_response_code(404);
                echo json_encode(['error' => 'Product not found']);
                break;
            }
            
            if ($productModel->delete($_GET['id'])) {
                echo json_encode(['message' => 'Product deleted successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to delete product']);
            }
            break;
            
        case 'OPTIONS':
        
            http_response_code(200);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}

?>
