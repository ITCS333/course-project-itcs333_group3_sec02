<?php
/**
 * Student Management API
 * 
 * This is a RESTful API that handles all CRUD operations for student management.
 * It uses PDO to interact with a MySQL database.
 * 
 * Database Table Structure (for reference):
 * Table: users   <-- from schema
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - name (VARCHAR(100))
 *   - email (VARCHAR(100), UNIQUE)
 *   - password (VARCHAR(255)) - Hashed password
 *   - is_admin (TINYINT(1)) - 1 for admin, 0 for normal student
 *   - created_at (TIMESTAMP)
 * 
 * NOTE: There is no separate `student_id` column in the schema.
 *       In this API we treat `student_id` as the part before '@' in the email,
 *       e.g. '202101234' from '202101234@stu.uob.edu.bh'.
 * 
 * HTTP Methods Supported:
 *   - GET: Retrieve student(s)
 *   - POST: Create a new student OR change password
 *   - PUT: Update an existing student
 *   - DELETE: Delete a student
 * 
 * Response Format: JSON
 */

// TODO: Set headers for JSON response and CORS
// Set Content-Type to application/json
// Allow cross-origin requests (CORS) if needed
// Allow specific HTTP methods (GET, POST, PUT, DELETE, OPTIONS)
// Allow specific headers (Content-Type, Authorization)
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// TODO: Handle preflight OPTIONS request
// If the request method is OPTIONS, return 200 status and exit
if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}

// TODO: Include the database connection class
// Assume the Database class has a method getConnection() that returns a PDO instance
require_once "Database.php";

// TODO: Get the PDO database connection
$dbInstance = new Database();
$db = $dbInstance->getConnection();

// TODO: Get the HTTP request method
// Use $_SERVER['REQUEST_METHOD']
$method = $_SERVER["REQUEST_METHOD"];

// TODO: Get the request body for POST and PUT requests
// Use file_get_contents('php://input') to get raw POST data
// Decode JSON data using json_decode()
$rawData = file_get_contents("php://input");
$data = json_decode($rawData, true);
if (!is_array($data)) {
    $data = [];
}

// TODO: Parse query parameters for filtering and searching
$queryParams = $_GET;

/**
 * Function: Get all students or search for specific students
 * Method: GET
 * 
 * Query Parameters:
 *   - search: Optional search term to filter by name, student_id, or email
 *   - sort: Optional field to sort by (name, student_id, email)
 *   - order: Optional sort order (asc or desc)
 */
function getStudents($db) {
    // TODO: Check if search parameter exists
    // If yes, prepare SQL query with WHERE clause using LIKE
    // Search should work on name, student_id, and email fields
    $search = isset($_GET["search"]) ? "%".trim($_GET["search"])."%" : null;

    // TODO: Check if sort and order parameters exist
    // If yes, add ORDER BY clause to the query
    // Validate sort field to prevent SQL injection (only allow: name, student_id, email)
    // Validate order to prevent SQL injection (only allow: asc, desc)
    $allowedFields = ["name", "student_id", "email"];
    $sort = (isset($_GET["sort"]) && in_array($_GET["sort"], $allowedFields))
        ? $_GET["sort"]
        : "name";

    $order = (isset($_GET["order"]) && strtolower($_GET["order"]) === "desc")
        ? "DESC"
        : "ASC";

    // Map sort field to actual column / expression
    // `student_id` is derived from email prefix: SUBSTRING_INDEX(email, '@', 1)
    if ($sort === "student_id") {
        $orderBy = "student_id";
    } elseif ($sort === "email") {
        $orderBy = "email";
    } else {
        $orderBy = "name";
    }

    // TODO: Prepare the SQL query using PDO
    // Note: Do NOT select the password field
    // Using `users` table (from schema) and deriving student_id from email
    $sql = "
        SELECT 
            SUBSTRING_INDEX(email, '@', 1) AS student_id,
            name,
            email,
            created_at
        FROM users
    ";
    if ($search) {
        $sql .= " WHERE name LIKE :search 
                  OR SUBSTRING_INDEX(email, '@', 1) LIKE :search 
                  OR email LIKE :search";
    }
    $sql .= " ORDER BY $orderBy $order";

    $stmt = $db->prepare($sql);

    // TODO: Bind parameters if using search
    if ($search) {
        $stmt->bindParam(":search", $search, PDO::PARAM_STR);
    }

    // TODO: Execute the query
    $stmt->execute();

    // TODO: Fetch all results as an associative array
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // TODO: Return JSON response with success status and data
    sendResponse(["success" => true, "data" => $students]);
}


/**
 * Function: Get a single student by student_id
 * Method: GET
 * 
 * Query Parameters:
 *   - student_id: The student's university ID
 */
function getStudentById($db, $studentId) {
    // TODO: Prepare SQL query to select student by student_id
    // Here student_id is derived from email prefix
    $sql = "
        SELECT 
            SUBSTRING_INDEX(email, '@', 1) AS student_id,
            name,
            email,
            created_at 
        FROM users 
        WHERE SUBSTRING_INDEX(email, '@', 1) = :student_id 
        LIMIT 1
    ";

    // TODO: Bind the student_id parameter
    $stmt = $db->prepare($sql);
    $stmt->bindParam(":student_id", $studentId, PDO::PARAM_STR);

    // TODO: Execute the query
    $stmt->execute();

    // TODO: Fetch the result
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    // TODO: Check if student exists
    // If yes, return success response with student data
    // If no, return error response with 404 status
    if ($student) {
        sendResponse(["success" => true, "data" => $student]);
    } else {
        sendResponse(["success" => false, "message" => "Student not found"], 404);
    }
}


/**
 * Function: Create a new student
 * Method: POST
 * 
 * Required JSON Body:
 *   - student_id: The student's university ID (must be unique)
 *   - name: Student's full name
 *   - email: Student's email (must be unique)
 *   - password: Default password (will be hashed)
 */
function createStudent($db, $data) {
    // TODO: Validate required fields
    // Check if student_id, name, email, and password are provided
    // If any field is missing, return error response with 400 status
    if (empty($data["student_id"]) || empty($data["name"]) || empty($data["email"]) || empty($data["password"])) {
        sendResponse(["success" => false, "message" => "Missing required fields"], 400);
    }

    // TODO: Sanitize input data
    // Trim whitespace from all fields
    // Validate email format using filter_var()
    $student_id = sanitizeInput($data["student_id"]);
    $name       = sanitizeInput($data["name"]);
    $email      = sanitizeInput($data["email"]);
    $password   = $data["password"];

    if (!validateEmail($email)) {
        sendResponse(["success" => false, "message" => "Invalid email format"], 400);
    }

    // TODO: Check if student_id or email already exists
    // Prepare and execute a SELECT query to check for duplicates
    // If duplicate found, return error response with 409 status (Conflict)
    // Here we check both:
    //  - email exact match
    //  - student_id match via SUBSTRING_INDEX(email, '@', 1)
    $check = $db->prepare("
        SELECT id FROM users 
        WHERE SUBSTRING_INDEX(email, '@', 1) = :sid 
           OR email = :email 
        LIMIT 1
    ");
    $check->bindParam(":sid", $student_id, PDO::PARAM_STR);
    $check->bindParam(":email", $email, PDO::PARAM_STR);
    $check->execute();

    if ($check->fetch(PDO::FETCH_ASSOC)) {
        sendResponse(["success" => false, "message" => "Student ID or email already exists"], 409);
    }

    // TODO: Hash the password
    // Use password_hash() with PASSWORD_DEFAULT
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // TODO: Prepare INSERT query
    // Using `users` table (schema) and setting is_admin = 0 for students
    $sql = "INSERT INTO users (name, email, password, is_admin)
            VALUES (:name, :email, :password, 0)";
    $stmt = $db->prepare($sql);

    // TODO: Bind parameters
    // Bind student_id, name, email, and hashed password
    // Note: student_id is not stored in a separate column; it's derived from email.
    $stmt->bindParam(":name", $name, PDO::PARAM_STR);
    $stmt->bindParam(":email", $email, PDO::PARAM_STR);
    $stmt->bindParam(":password", $hashedPassword, PDO::PARAM_STR);

    // TODO: Execute the query
    if ($stmt->execute()) {
        // TODO: Check if insert was successful
        sendResponse(["success" => true, "message" => "Student created successfully"], 201);
    } else {
        sendResponse(["success" => false, "message" => "Failed to create student"], 500);
    }
}


/**
 * Function: Update an existing student
 * Method: PUT
 * 
 * Required JSON Body:
 *   - student_id: The student's university ID (to identify which student to update)
 *   - name: Updated student name (optional)
 *   - email: Updated student email (optional)
 */
function updateStudent($db, $data) {
    // TODO: Validate that student_id is provided
    // If not, return error response with 400 status
    if (empty($data["student_id"])) {
        sendResponse(["success" => false, "message" => "student_id is required"], 400);
    }

    $sid = sanitizeInput($data["student_id"]);

    // TODO: Check if student exists
    // Prepare and execute a SELECT query to find the student
    // If not found, return error response with 404 status
    $check = $db->prepare("
        SELECT id FROM users 
        WHERE SUBSTRING_INDEX(email, '@', 1) = :sid 
        LIMIT 1
    ");
    $check->bindParam(":sid", $sid, PDO::PARAM_STR);
    $check->execute();

    if (!$check->fetch(PDO::FETCH_ASSOC)) {
        sendResponse(["success" => false, "message" => "Student not found"], 404);
    }

    // TODO: Build UPDATE query dynamically based on provided fields
    // Only update fields that are provided in the request
    $fields = [];
    $params = [":sid" => $sid];

    if (!empty($data["name"])) {
        $fields[] = "name = :name";
        $params[":name"] = sanitizeInput($data["name"]);
    }

    if (!empty($data["email"])) {
        $email = sanitizeInput($data["email"]);

        if (!validateEmail($email)) {
            sendResponse(["success" => false, "message" => "Invalid email"], 400);
        }

        // TODO: If email is being updated, check if new email already exists
        // Prepare and execute a SELECT query
        // Exclude the current student from the check
        // If duplicate found, return error response with 409 status
        $dup = $db->prepare("
            SELECT id FROM users 
            WHERE email = :email 
              AND SUBSTRING_INDEX(email, '@', 1) != :sid
        ");
        $dup->bindParam(":email", $email, PDO::PARAM_STR);
        $dup->bindParam(":sid", $sid, PDO::PARAM_STR);
        $dup->execute();

        if ($dup->fetch(PDO::FETCH_ASSOC)) {
            sendResponse(["success" => false, "message" => "Email already in use"], 409);
        }

        $fields[] = "email = :email";
        $params[":email"] = $email;
    }

    if (empty($fields)) {
        sendResponse(["success" => false, "message" => "No fields to update"], 400);
    }

    $sql = "UPDATE users SET ".implode(", ", $fields)." 
            WHERE SUBSTRING_INDEX(email, '@', 1) = :sid";
    $stmt = $db->prepare($sql);

    // TODO: Bind parameters dynamically
    // Bind only the parameters that are being updated
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }

    // TODO: Execute the query
    if ($stmt->execute()) {
        // TODO: Check if update was successful
        sendResponse(["success" => true, "message" => "Student updated"]);
    } else {
        sendResponse(["success" => false, "message" => "Failed to update student"], 500);
    }
}


/**
 * Function: Delete a student
 * Method: DELETE
 * 
 * Query Parameters or JSON Body:
 *   - student_id: The student's university ID
 */
function deleteStudent($db, $studentId) {
    // TODO: Validate that student_id is provided
    // If not, return error response with 400 status
    if (empty($studentId)) {
        sendResponse(["success" => false, "message" => "student_id required"], 400);
    }

    $sid = sanitizeInput($studentId);

    // TODO: Check if student exists
    // Prepare and execute a SELECT query
    // If not found, return error response with 404 status
    $check = $db->prepare("
        SELECT id FROM users 
        WHERE SUBSTRING_INDEX(email, '@', 1) = :sid 
        LIMIT 1
    ");
    $check->bindParam(":sid", $sid, PDO::PARAM_STR);
    $check->execute();

    if (!$check->fetch(PDO::FETCH_ASSOC)) {
        sendResponse(["success" => false, "message" => "Student not found"], 404);
    }

    // TODO: Prepare DELETE query
    $sql = "DELETE FROM users WHERE SUBSTRING_INDEX(email, '@', 1) = :sid";
    $stmt = $db->prepare($sql);

    // TODO: Bind the student_id parameter
    $stmt->bindParam(":sid", $sid, PDO::PARAM_STR);

    // TODO: Execute the query
    $success = $stmt->execute();

    // TODO: Check if delete was successful
    // If yes, return success response
    // If no, return error response with 500 status
    if ($success) {
        sendResponse([
            'success' => true,
            'message' => 'Student deleted successfully'
        ], 200);
    } else {
        sendResponse([
            'success' => false,
            'message' => 'Failed to delete student'
        ], 500);
    }
}


/**
 * Function: Change password
 * Method: POST with action=change_password
 * 
 * Required JSON Body:
 *   - student_id: The student's university ID (identifies whose password to change)
 *   - current_password: The student's current password
 *   - new_password: The new password to set
 */
function changePassword($db, $data) {
    // TODO: Validate required fields
    // Check if student_id, current_password, and new_password are provided
    // If any field is missing, return error response with 400 status
    if (
        empty($data['student_id'])      ||
        empty($data['current_password']) ||
        empty($data['new_password'])
    ) {
        sendResponse([
            'success' => false,
            'message' => 'Missing required fields'
        ], 400);
    }

    $student_id      = sanitizeInput($data['student_id']);
    $currentPassword = $data['current_password'];
    $newPassword     = $data['new_password'];

    // TODO: Validate new password strength
    // Check minimum length (at least 8 characters)
    // If validation fails, return error response with 400 status
    if (strlen($newPassword) < 8) {
        sendResponse([
            'success' => false,
            'message' => 'New password must be at least 8 characters'
        ], 400);
    }

    // TODO: Retrieve current password hash from database
    // Prepare and execute SELECT query to get password
    $sql = "
        SELECT password FROM users 
        WHERE SUBSTRING_INDEX(email, '@', 1) = :student_id 
        LIMIT 1
    ";
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':student_id', $student_id, PDO::PARAM_STR);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    // TODO: Verify current password
    // Use password_verify() to check if current_password matches the hash
    // If verification fails, return error response with 401 status (Unauthorized)
    if (!$row) {
        sendResponse([
            'success' => false,
            'message' => 'Student not found'
        ], 404);
    }

    $hashedPassword = $row['password'];

    if (!password_verify($currentPassword, $hashedPassword)) {
        sendResponse([
            'success' => false,
            'message' => 'Current password is incorrect'
        ], 401);
    }

    // TODO: Hash the new password
    // Use password_hash() with PASSWORD_DEFAULT
    $newHashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

    // TODO: Update password in database
    // Prepare UPDATE query
    $updateSql = "
        UPDATE users 
        SET password = :password 
        WHERE SUBSTRING_INDEX(email, '@', 1) = :student_id
    ";
    $updateStmt = $db->prepare($updateSql);

    // TODO: Bind parameters and execute
    $updateStmt->bindParam(':password', $newHashedPassword, PDO::PARAM_STR);
    $updateStmt->bindParam(':student_id', $student_id, PDO::PARAM_STR);
    $success = $updateStmt->execute();

    // TODO: Check if update was successful
    // If yes, return success response
    // If no, return error response with 500 status
    if ($success) {
        sendResponse([
            'success' => true,
            'message' => 'Password updated successfully'
        ], 200);
    } else {
        sendResponse([
            'success' => false,
            'message' => 'Failed to update password'
        ], 500);
    }
}


// ============================================================================
// MAIN REQUEST ROUTER
// ============================================================================

try {
    // TODO: Route the request based on HTTP method
    
    if ($method === 'GET') {
        // TODO: Check if student_id is provided in query parameters
        // If yes, call getStudentById()
        // If no, call getStudents() to get all students (with optional search/sort)
        if (!empty($queryParams['student_id'])) {
            getStudentById($db, $queryParams['student_id']);
        } else {
            getStudents($db);
        }

    } elseif ($method === 'POST') {
        // TODO: Check if this is a change password request
        // Look for action=change_password in query parameters
        // If yes, call changePassword()
        // If no, call createStudent()
        $action = isset($queryParams['action']) ? $queryParams['action'] : '';
        if ($action === 'change_password') {
            changePassword($db, $data);
        } else {
            createStudent($db, $data);
        }

    } elseif ($method === 'PUT') {
        // TODO: Call updateStudent()
        updateStudent($db, $data);

    } elseif ($method === 'DELETE') {
        // TODO: Get student_id from query parameter or request body
        // Call deleteStudent()
        $studentId = $queryParams['student_id'] ?? ($data['student_id'] ?? null);
        deleteStudent($db, $studentId);

    } else {
        // TODO: Return error for unsupported methods
        // Set HTTP status to 405 (Method Not Allowed)
        // Return JSON error message
        sendResponse([
            'success' => false,
            'message' => 'Method Not Allowed'
        ], 405);
    }

} catch (PDOException $e) {
    // TODO: Handle database errors
    // Log the error message (optional)
    // Return generic error response with 500 status
    error_log('Database error: ' . $e->getMessage());
    sendResponse([
        'success' => false,
        'message' => 'Database error'
    ], 500);

} catch (Exception $e) {
    // TODO: Handle general errors
    // Return error response with 500 status
    error_log('General error: ' . $e->getMessage());
    sendResponse([
        'success' => false,
        'message' => 'Internal server error'
    ], 500);
}


// ============================================================================
// HELPER FUNCTIONS (Optional but Recommended)
// ============================================================================

/**
 * Helper function to send JSON response
 * 
 * @param mixed $data - Data to send
 * @param int $statusCode - HTTP status code
 */
function sendResponse($data, $statusCode = 200) {
    // TODO: Set HTTP response code
    http_response_code($statusCode);

    // TODO: Echo JSON encoded data
    echo json_encode($data);

    // TODO: Exit to prevent further execution
    exit;
}


/**
 * Helper function to validate email format
 * 
 * @param string $email - Email address to validate
 * @return bool - True if valid, false otherwise
 */
function validateEmail($email) {
    // TODO: Use filter_var with FILTER_VALIDATE_EMAIL
    // Return true if valid, false otherwise
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}


/**
 * Helper function to sanitize input
 * 
 * @param string $data - Data to sanitize
 * @return string - Sanitized data
 */
function sanitizeInput($data) {
    // TODO: Trim whitespace
    $data = trim($data);

    // TODO: Strip HTML tags using strip_tags()
    $data = strip_tags($data);

    // TODO: Convert special characters using htmlspecialchars()
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');

    // Return sanitized data
    return $data;
}

?>
