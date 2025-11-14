<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Content-Type: application/json; charset=UTF-8");

require_once 'config/Database.php';
require_once 'config/Auth.php';
require_once 'models/ProductModel.php';

class ProductAPI {
    private $db;
    private $pdo;
    private $auth;
    private $productModel;

    public function __construct() {
        $this->db = new Database();
        $this->pdo = $this->db->getConnection();
        $this->auth = new Auth($this->pdo);
        $this->productModel = new ProductModel($this->pdo);
    }

    private function sendResponse($statusCode, $data) {
        http_response_code($statusCode);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function getRequestPath() {
        $request_uri = $_SERVER['REQUEST_URI'];
        $script_name = $_SERVER['SCRIPT_NAME'];
        
        $path = str_replace(dirname($script_name), '', $request_uri);
        $path = parse_url($path, PHP_URL_PATH);
        $path = trim($path, '/');
        
        return $path;
    }

    public function handleRequest() {
        if ($_SERVER['REQUEST_METHOD'] !== 'OPTIONS') {
            if (!$this->auth->validateApiKey()) {
                return;
            }
        }

        $path = $this->getRequestPath();
        $paths = explode('/', $path);
        $resource = $paths[0] ?? '';
        $id = $paths[1] ?? null;

        $method = $_SERVER['REQUEST_METHOD'];
        $inputData = json_decode(file_get_contents("php://input"), true);

        if ($resource === 'products') {
            switch ($method) {
                case 'GET':
                    if ($id) {
                        $product = $this->productModel->getById($id);
                        if ($product) {
                            $this->sendResponse(200, $product);
                        } else {
                            $this->sendResponse(404, ['error' => 'Product not found']);
                        }
                    } else {
                        $products = $this->productModel->getAll();
                        $this->sendResponse(200, $products);
                    }
                    break;

                case 'POST':
                    if (!$inputData) {
                        $this->sendResponse(400, ['error' => 'Invalid JSON data']);
                    }
                    
                    if (empty($inputData['name']) || !isset($inputData['price'])) {
                        $this->sendResponse(400, ['error' => 'Name and price are required']);
                    }
                    
                    if (!is_numeric($inputData['price']) || $inputData['price'] < 0) {
                        $this->sendResponse(400, ['error' => 'Price must be a positive number']);
                    }
                    
                    $newId = $this->productModel->create($inputData);
                    if ($newId) {
                        $this->sendResponse(201, [
                            'message' => 'Product created successfully',
                            'id' => $newId,
                            'product' => $this->productModel->getById($newId)
                        ]);
                    } else {
                        $this->sendResponse(500, ['error' => 'Failed to create product']);
                    }
                    break;

                case 'PUT':
                    if (!$inputData) {
                        $this->sendResponse(400, ['error' => 'Invalid JSON data']);
                    }
                    
                    if (!$id) {
                        $this->sendResponse(400, ['error' => 'Product ID is required']);
                    }
                    
                    if (!$this->productModel->exists($id)) {
                        $this->sendResponse(404, ['error' => 'Product not found']);
                    }
                    
                    if ($this->productModel->update($id, $inputData)) {
                        $this->sendResponse(200, [
                            'message' => 'Product updated successfully',
                            'product' => $this->productModel->getById($id)
                        ]);
                    } else {
                        $this->sendResponse(500, ['error' => 'Failed to update product']);
                    }
                    break;

                case 'DELETE':
                    if (!$id) {
                        $this->sendResponse(400, ['error' => 'Product ID is required']);
                    }
                    
                    if (!$this->productModel->exists($id)) {
                        $this->sendResponse(404, ['error' => 'Product not found']);
                    }
                    
                    if ($this->productModel->delete($id)) {
                        $this->sendResponse(200, ['message' => 'Product deleted successfully']);
                    } else {
                        $this->sendResponse(500, ['error' => 'Failed to delete product']);
                    }
                    break;

                default:
                    $this->sendResponse(405, ['error' => 'Method not allowed']);
            }
        } else {
            $this->sendResponse(404, ['error' => 'Resource not found']);
        }
    }
}

$api = new ProductAPI();
$api->handleRequest();
?>