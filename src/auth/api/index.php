<?php
/**
 * Authentication Handler for Login Form
 * 
 * This PHP script handles user authentication via POST requests from the Fetch API.
 * It validates credentials against a MySQL database using PDO,
 * creates sessions, and returns JSON responses.
 */

// --- Session Management ---
// TODO: Start a PHP session using session_start()
// This must be called before any output is sent to the browser
// Sessions allow us to store user data across multiple pages
session_start();

// --- Set Response Headers ---
// TODO: Set the Content-Type header to 'application/json'
// This tells the browser that we're sending JSON data back
header('Content-Type: application/json; charset=utf-8');
// TODO: (Optional) Set CORS headers if your frontend and backend are on different domains
// You'll need headers for Access-Control-Allow-Origin, Methods, and Headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request quickly
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// --- Check Request Method ---
// TODO: Verify that the request method is POST
// Use the $_SERVER superglobal to check the REQUEST_METHOD
// If the request is not POST, return an error response and exit
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method. POST required.'
    ]);
    exit;
}

// --- Get POST Data ---
// TODO: Retrieve the raw POST data
// The Fetch API sends JSON data in the request body
// Use file_get_contents with 'php://input' to read the raw request body
$rawBody = file_get_contents('php://input');
// TODO: Decode the JSON data into a PHP associative array
// Use json_decode with the second parameter set to true
$data = json_decode($rawBody, true);
// TODO: Extract the email and password from the decoded data
// Check if both 'email' and 'password' keys exist in the array
// If either is missing, return an error response and exit
if (!is_array($data) || !isset($data['email']) || !isset($data['password'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Email and password are required.'
    ]);
    exit;
}

// TODO: Store the email and password in variables
// Trim any whitespace from the email
$email = trim($data['email']);
$password = $data['password'];

// --- Server-Side Validation (Optional but Recommended) ---
// TODO: Validate the email format on the server side
// Use the appropriate filter function for email validation
// If invalid, return an error response and exit
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid email format.'
    ]);
    exit;
}

// TODO: Validate the password length (minimum 8 characters)
// If invalid, return an error response and exit
if (strlen($password) < 8) {
    echo json_encode([
        'success' => false,
        'message' => 'Password must be at least 8 characters.'
    ]);
    exit;
}

// --- Database Connection ---
// TODO: Get the database connection using the provided function
// Assume getDBConnection() returns a PDO instance with error mode set to exception
// The function is defined elsewhere (e.g., in a config file or db.php)
function getDBConnection() {
    $host = 'localhost';
    $db   = 'course';
    $user = 'admin';
    $pass = 'password123';
    $dsn  = "mysql:host=$host;dbname=$db;charset=utf8mb4";

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    return new PDO($dsn, $user, $pass, $options);
}

$pdo = getDBConnection();

// TODO: Wrap database operations in a try-catch block to handle PDO exceptions
// This ensures you can return a proper JSON error response if something goes wrong
try {
    // --- Prepare SQL Query ---
    // TODO: Write a SQL SELECT query to find the user by email
    // Select the following columns: id, name, email, password
    // IMPORTANT: Also fetch is_admin (because schema requires it)
    $sql = "SELECT id, name, email, password, is_admin FROM users WHERE email = :email LIMIT 1";

    // --- Prepare the Statement ---
    $stmt = $pdo->prepare($sql);

    // --- Execute the Query ---
    // TODO: Execute with placeholder binding
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $stmt->execute();

    // --- Fetch User Data ---
    // TODO: Fetch the user record from the database
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // --- Verify User Exists and Password Matches ---
    // TODO: Check if a user was found
    if ($user && isset($user['password'])) {

        // TODO: Verify password using password_verify()
        $passwordMatches = password_verify($password, $user['password']);

        // --- Handle Successful Authentication ---
        if ($passwordMatches) {

            // TODO: Store user info in session
            $_SESSION['user_id']    = $user['id'];
            $_SESSION['user_name']  = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['is_admin']   = $user['is_admin']; // ⭐ ADDED
            $_SESSION['logged_in']  = true;

            // TODO: Prepare a success response
            $response = [
                'success' => true,
                'message' => 'Login successful',
                'user'    => [
                    'id'       => $user['id'],
                    'name'     => $user['name'],
                    'email'    => $user['email'],
                    'is_admin' => $user['is_admin'] // ⭐ ADDED
                ]
            ];

            // TODO: Return JSON response
            echo json_encode($response);
            exit;
        }
    }

    // --- Handle Failed Authentication ---
    $errorResponse = [
        'success' => false,
        'message' => 'Invalid email or password'
    ];

    // TODO: Return failure JSON
    echo json_encode($errorResponse);
    exit;

}
// TODO: Catch PDO exceptions in the catch block
catch (PDOException $e) {

    // TODO: Log error for debugging
    error_log('Database error in index.php: ' . $e->getMessage());

    // TODO: Return a generic error message
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while processing your request.'
    ]);
    exit;
}

// --- End of Script ---
?>
