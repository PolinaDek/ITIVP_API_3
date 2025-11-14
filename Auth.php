<?php
class Auth {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }


    public function validateApiKey() {

    $headers = getallheaders();
    $api_key = $headers['X-API-Key'] ?? '';
    

    if (empty($api_key)) {
        $api_key = $_SERVER['HTTP_X_API_KEY'] ?? '';
    }
    
    if (empty($api_key)) {
        foreach ($_SERVER as $key => $value) {
            if (strtoupper($key) === 'HTTP_X_API_KEY') {
                $api_key = $value;
                break;
            }
        }
    }


    error_log("API Key received: " . ($api_key ?: 'EMPTY'));

    if (empty($api_key)) {
        $this->sendError(401, 'API key is missing');
        return false;
    }

    $stmt = $this->pdo->prepare("SELECT * FROM api_keys WHERE api_key = ? AND is_active = 1");
    $stmt->execute([$api_key]);
    $api_key_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$api_key_data) {
        error_log("API Key not found in database: " . $api_key);
        $this->sendError(401, 'Invalid API key');
        return false;
    }

    return true;
}

 
    private function sendError($statusCode, $message) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode(['error' => $message]);
        exit;
    }

    public function generateApiKey($user_id) {
        $api_key = 'key_' . bin2hex(random_bytes(16));
        $hashed_key = password_hash($api_key, PASSWORD_DEFAULT);

        $stmt = $this->pdo->prepare("
            INSERT INTO api_keys (api_key, user_id, is_active) 
            VALUES (?, ?, 1)
        ");
        
        if ($stmt->execute([$hashed_key, $user_id])) {
            return $api_key; 
        }

        return false;
    }
}
?>