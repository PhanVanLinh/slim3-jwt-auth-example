<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \Firebase\JWT\JWT;
$app->post('/login', function (Request $request, Response $response) {
    $data = $request->getParsedBody();
    $result = file_get_contents('./users.json');
    $users = json_decode($result, true);
    $userName = $data['userName'];
    $password = $data['password'];
    foreach ($users as $key => $user) {
        if ($user['userName'] == $userName && $user['password'] == $password) {
            $currentUser = $user;
        }
    }
    if (!isset($currentUser)) {
        echo json_encode("No user found");
    } else {
        $token_from_db = findTokenInDatabase($this->db, $currentUser);
        if($token_from_db == null){
            //echo "insert";
            // Create a new token if a user is found but not a token corresponding to whom.
            $key = "your_secret_key";
            $payload = array(
                "iss" => "http://your-domain.com", 
                "iat" => time(), "exp" => time() + (3600 * 24 * 15), 
                "context" => ["user" => ["userName" => $currentUser['userName'], 
                "userId" => $currentUser['userId']]]);
            try {
                $token = JWT::encode($payload, $key);
            } catch(Exception $e) {
                echo "encode error: "+ $e->getMessage();
            }

            insertToken($this->db, $token, $payload, $currentUser);
            $token_from_db = findTokenInDatabase($this->db, $currentUser);
        }
        return $response->withStatus(200)->withJson([
            "token" => $token_from_db->value, 
            "userId" => $token_from_db->user_id, 
            "createdDate" => $token_from_db->user_id, 
            "expirationDate" => $token_from_db->user_id, 
        ]);
    }
});
function findTokenInDatabase($db, $currentUser) {
    // Find a corresponding token.
    $sql = "SELECT * FROM tokens
            WHERE user_id = :userId AND expiration_date >" . time();
    $token_from_db = null;
    try {
        $stmt = $db->prepare($sql);
        $stmt->bindParam("userId", $currentUser['userId']);
        $stmt->execute();
        $token_from_db = $stmt->fetchObject();
        $db = null;
        return $token_from_db;
    } catch(PDOException $e) {
        echo '{"error":{"text":' . $e->getMessage() . '}}';
    }
    return $token_from_db;
}

function insertToken($db, $token, $payload, $currentUser){
    $sql = "INSERT INTO tokens (user_id, value, created_date, expiration_date)
                VALUES (:user_id, :value, :created_date, :expiration_date)";
    try {
        $stmt = $db->prepare($sql);
        $stmt->bindParam("user_id", $currentUser['userId']);
        $stmt->bindParam("value", $token);
        $stmt->bindParam("created_date", $payload['iat']);
        $stmt->bindParam("expiration_date", $payload['exp']);
        $stmt->execute();
        $db = null;
        return true;
    } catch(PDOException $e) {
        echo '{"error":{"text":' . $e->getMessage() . '}}';
    }
    return false;
}

// The route to get a secured data.
$app->get('/restricted', function (Request $request, Response $response) {
    $jwt = $request->getHeaders();
    $key = "your_secret_key";
    try {
        $decoded = JWT::decode($jwt['HTTP_AUTHORIZATION'][0], $key, array('HS256'));
    }
    catch(Exception $e) {
        echo $e->getMessage();
    }
    if (isset($decoded)) {
        $sql = "SELECT * FROM tokens WHERE user_id = :user_id";
        try {
            $db = $this->db;
            $stmt = $db->prepare($sql);
            $stmt->bindParam("user_id", $decoded->context->user->user_id);
            $stmt->execute();
            $user_from_db = $stmt->fetchObject();
            $db = null;
            if (isset($user_from_db->user_id)) {
                return $response->withStatus(401)->withJson("This is your secure resource");
            }
        }
        catch(PDOException $e) {
            echo '{"error":{"text":' . $e->getMessage() . '}}';
        }
    }
});
