<?php
require_once __DIR__ . '/../vendor/autoload.php';
include_once("connection.php");

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;

// require_auth — JWT verification helper.
// Reads the Authorization: Bearer <token> header, verifies the signature
// against JWT_SECRET, and returns the decoded payload (contains user_id).
// On a missing/expired/invalid token it responds with a JSON error and exits.
function require_auth(){
    $response = [];

    $token = get_bearer_token();

    if($token == ""){
        $response["success"] = false;
        $response["error"] = "missing authorization header";
        echo json_encode($response);
        exit;
    }

    try {
        $decoded = JWT::decode($token, new Key(JWT_SECRET, "HS256"));
        return (array) $decoded;
    } catch (ExpiredException $e) {
        // token is well-formed but past its exp
        $response["success"] = false;
        $response["error"] = "token expired";
        echo json_encode($response);
        exit;
    } catch (Exception $e) {
        // bad signature, malformed token, anything else
        $response["success"] = false;
        $response["error"] = "invalid token";
        echo json_encode($response);
        exit;
    }
}

// get_bearer_token — pulls the token out of the Authorization header.
// Tries several common PHP/Apache sources so XAMPP setups always find it.
function get_bearer_token(){
    // 1. getallheaders() (available on Apache)
    if(function_exists("getallheaders")){
        $headers = getallheaders();
        foreach($headers as $name => $value){
            if(strtolower($name) == "authorization" && strpos($value, "Bearer ") === 0){
                return substr($value, 7);
            }
        }
    }

    // 2. direct server variable
    if(isset($_SERVER["HTTP_AUTHORIZATION"]) && strpos($_SERVER["HTTP_AUTHORIZATION"], "Bearer ") === 0){
        return substr($_SERVER["HTTP_AUTHORIZATION"], 7);
    }

    // 3. preserved by some rewrite rules
    if(isset($_SERVER["REDIRECT_HTTP_AUTHORIZATION"]) && strpos($_SERVER["REDIRECT_HTTP_AUTHORIZATION"], "Bearer ") === 0){
        return substr($_SERVER["REDIRECT_HTTP_AUTHORIZATION"], 7);
    }

    return "";
}
?>