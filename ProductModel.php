<?php
class ProductModel {
    private $pdo;
    private $table_name = "products";

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }


    public function getAll() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY id DESC";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    public function getById($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }


    public function create($data) {
        $query = "
            INSERT INTO " . $this->table_name . " 
            (name, description, price, category_id) 
            VALUES (:name, :description, :price, :category_id)
        ";
        
        $stmt = $this->pdo->prepare($query);
        

        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':price', $data['price']);
        $stmt->bindParam(':category_id', $data['category_id']);

        if ($stmt->execute()) {
            return $this->pdo->lastInsertId();
        }

        return false;
    }


    public function update($id, $data) {
        $fields = [];
        $params = [':id' => $id];

        if (isset($data['name'])) {
            $fields[] = "name = :name";
            $params[':name'] = $data['name'];
        }
        if (isset($data['description'])) {
            $fields[] = "description = :description";
            $params[':description'] = $data['description'];
        }
        if (isset($data['price'])) {
            $fields[] = "price = :price";
            $params[':price'] = $data['price'];
        }
        if (isset($data['category_id'])) {
            $fields[] = "category_id = :category_id";
            $params[':category_id'] = $data['category_id'];
        }

        if (empty($fields)) {
            return false;
        }

        $query = "
            UPDATE " . $this->table_name . " 
            SET " . implode(', ', $fields) . " 
            WHERE id = :id
        ";

        $stmt = $this->pdo->prepare($query);
        return $stmt->execute($params);
    }


    public function delete($id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->pdo->prepare($query);
        return $stmt->execute([$id]);
    }


    public function exists($id) {
        $query = "SELECT id FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch() !== false;
    }


    public function validate($data, $forUpdate = false) {
        $errors = [];

        if (!$forUpdate || isset($data['name'])) {
            if (empty(trim($data['name']))) {
                $errors[] = "Name is required";
            }
        }

        if (!$forUpdate || isset($data['price'])) {
            if (isset($data['price']) && (!is_numeric($data['price']) || $data['price'] < 0)) {
                $errors[] = "Price must be a positive number";
            }
        }

        return $errors;
    }
}

?>
