
<?php
/**
 * Assignment Management API
 * 
 * This is a RESTful API that handles all CRUD operations for course assignments
 * and their associated discussion comments.
 * It uses PDO to interact with a MySQL database.
 * 
 * Database Table Structures (for reference):
 * 
 * Table: assignments
 * Columns:
 *   - id (VARCHAR(50), PRIMARY KEY)
 *   - title (VARCHAR(200))
 *   - description (TEXT)
 *   - due_date (DATE)
 *   - files (TEXT)
 *   - created_at (TIMESTAMP)
 *   - updated_at (TIMESTAMP)
 * 
 * Table: comments_assignment
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - assignment_id (VARCHAR(50), FOREIGN KEY)
 *   - author (VARCHAR(100))
 *   - text (TEXT)
 *   - created_at (TIMESTAMP)
 * 
 * HTTP Methods Supported:
 *   - GET: Retrieve assignment(s) or comment(s)
 *   - POST: Create a new assignment or comment
 *   - PUT: Update an existing assignment
 *   - DELETE: Delete an assignment or comment
 * 
 * Response Format: JSON
 */

// ============================================================================
// HEADERS AND CORS CONFIGURATION
// ============================================================================

// TODO: Set Content-Type header to application/json
header('Content-Type: application/json');

// TODO: Set CORS headers to allow cross-origin requests
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// TODO: Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ============================================================================
// DATABASE CONNECTION
// ============================================================================

// TODO: Include the database connection class
require_once 'db_config.php';

// TODO: Create database connection
try {
    $db = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    // TODO: Set PDO to throw exceptions on errors
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(array('success' => false, 'message' => 'Database connection failed'));
    exit();
}

// ============================================================================
// REQUEST PARSING
// ============================================================================

// TODO: Get the HTTP request method
$method = $_SERVER['REQUEST_METHOD'];

// TODO: Get the request body for POST and PUT requests
$data = json_decode(file_get_contents('php://input'), true);

// TODO: Parse query parameters
$resource = isset($_GET['resource']) ? $_GET['resource'] : '';

// ============================================================================
// ASSIGNMENT CRUD FUNCTIONS
// ============================================================================

/**
 * Function: Get all assignments
 * Method: GET
 * Endpoint: ?resource=assignments
 * 
 * Query Parameters:
 *   - search: Optional search term to filter by title or description
 *   - sort: Optional field to sort by (title, due_date, created_at)
 *   - order: Optional sort order (asc or desc, default: asc)
 * 
 * Response: JSON array of assignment objects
 */
function getAllAssignments($db) {
    // TODO: Start building the SQL query
    $sql = "SELECT * FROM assignments WHERE 1=1";
    $params = array();
    
    // TODO: Check if 'search' query parameter exists in $_GET
    if(isset($_GET['search']) && !empty($_GET['search'])) {
        $sql .= " AND (title LIKE :search OR description LIKE :search)";
        $params[':search'] = '%' . $_GET['search'] . '%';
    }
    
    // TODO: Check if 'sort' and 'order' query parameters exist
    $allowedSort = array('title', 'due_date', 'created_at');
    $allowedOrder = array('asc', 'desc');
    
    $sort = isset($_GET['sort']) ? $_GET['sort'] : 'created_at';
    $order = isset($_GET['order']) ? $_GET['order'] : 'asc';
    
    if(validateAllowedValue($sort, $allowedSort) && validateAllowedValue($order, $allowedOrder)) {
        $sql .= " ORDER BY " . $sort . " " . $order;
    }
    
    // TODO: Prepare the SQL statement using $db->prepare()
    $stmt = $db->prepare($sql);
    
    // TODO: Bind parameters if search is used
    foreach($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    // TODO: Execute the prepared statement
    $stmt->execute();
    
    // TODO: Fetch all results as associative array
    $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // TODO: For each assignment, decode the 'files' field from JSON to array
    for($i = 0; $i < count($assignments); $i++) {
        $assignments[$i]['files'] = json_decode($assignments[$i]['files'], true);
    }
    
    // TODO: Return JSON response
    sendResponse(array('success' => true, 'data' => $assignments));
}


/**
 * Function: Get a single assignment by ID
 * Method: GET
 * Endpoint: ?resource=assignments&id={assignment_id}
 * 
 * Query Parameters:
 *   - id: The assignment ID (required)
 * 
 * Response: JSON object with assignment details
 */
function getAssignmentById($db, $assignmentId) {
    // TODO: Validate that $assignmentId is provided and not empty
    if(empty($assignmentId)) {
        sendResponse(array('success' => false, 'message' => 'Assignment ID is required'), 400);
        return;
    }
    
    // TODO: Prepare SQL query to select assignment by id
    $sql = "SELECT * FROM assignments WHERE id = :id";
    $stmt = $db->prepare($sql);
    
    // TODO: Bind the :id parameter
    $stmt->bindValue(':id', $assignmentId);
    
    // TODO: Execute the statement
    $stmt->execute();
    
    // TODO: Fetch the result as associative array
    $assignment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // TODO: Check if assignment was found
    if(!$assignment) {
        sendResponse(array('success' => false, 'message' => 'Assignment not found'), 404);
        return;
    }
    
    // TODO: Decode the 'files' field from JSON to array
    $assignment['files'] = json_decode($assignment['files'], true);
    
    // TODO: Return success response with assignment data
    sendResponse(array('success' => true, 'data' => $assignment));
}


/**
 * Function: Create a new assignment
 * Method: POST
 * Endpoint: ?resource=assignments
 * 
 * Required JSON Body:
 *   - title: Assignment title (required)
 *   - description: Assignment description (required)
 *   - due_date: Due date in YYYY-MM-DD format (required)
 *   - files: Array of file URLs/paths (optional)
 * 
 * Response: JSON object with created assignment data
 */
function createAssignment($db, $data) {
    // TODO: Validate required fields
    if(empty($data['title']) || empty($data['description']) || empty($data['due_date'])) {
        sendResponse(array('success' => false, 'message' => 'Title, description, and due_date are required'), 400);
        return;
    }
    
    // TODO: Sanitize input data
    $title = sanitizeInput($data['title']);
    $description = sanitizeInput($data['description']);
    $dueDate = $data['due_date'];
    
    // TODO: Validate due_date format
    if(!validateDate($dueDate)) {
        sendResponse(array('success' => false, 'message' => 'Invalid date format. Use YYYY-MM-DD'), 400);
        return;
    }
    
    // TODO: Generate a unique assignment ID
    $assignmentId = 'asg_' . time();
    
    // TODO: Handle the 'files' field
    $files = isset($data['files']) ? $data['files'] : array();
    $filesJson = json_encode($files);
    
    // TODO: Prepare INSERT query
    $sql = "INSERT INTO assignments (id, title, description, due_date, files, created_at, updated_at) 
            VALUES (:id, :title, :description, :due_date, :files, NOW(), NOW())";
    $stmt = $db->prepare($sql);
    
    // TODO: Bind all parameters
    $stmt->bindValue(':id', $assignmentId);
    $stmt->bindValue(':title', $title);
    $stmt->bindValue(':description', $description);
    $stmt->bindValue(':due_date', $dueDate);
    $stmt->bindValue(':files', $filesJson);
    
    // TODO: Execute the statement
    $result = $stmt->execute();
    
    // TODO: Check if insert was successful
    if($result) {
        sendResponse(array(
            'success' => true, 
            'message' => 'Assignment created successfully',
            'data' => array(
                'id' => $assignmentId,
                'title' => $title,
                'description' => $description,
                'due_date' => $dueDate,
                'files' => $files
            )
        ), 201);
        return;
    }
    
    // TODO: If insert failed, return 500 error
    sendResponse(array('success' => false, 'message' => 'Failed to create assignment'), 500);
}


/**
 * Function: Update an existing assignment
 * Method: PUT
 * Endpoint: ?resource=assignments
 * 
 * Required JSON Body:
 *   - id: Assignment ID (required, to identify which assignment to update)
 *   - title: Updated title (optional)
 *   - description: Updated description (optional)
 *   - due_date: Updated due date (optional)
 *   - files: Updated files array (optional)
 * 
 * Response: JSON object with success status
 */
function updateAssignment($db, $data) {
    // TODO: Validate that 'id' is provided in $data
    if(empty($data['id'])) {
        sendResponse(array('success' => false, 'message' => 'Assignment ID is required'), 400);
        return;
    }
    
    // TODO: Store assignment ID in variable
    $assignment_id = $data['id'];
    
    // TODO: Check if assignment exists
    $checkSql = "SELECT id FROM assignments WHERE id = :id";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->bindValue(':id', $assignment_id);
    $checkStmt->execute();
    
    if(!$checkStmt->fetch()) {
        sendResponse(array('success' => false, 'message' => 'Assignment not found'), 404);
        return;
    }
    
    // TODO: Build UPDATE query dynamically based on provided fields
    $setClauses = array();
    $params = array(':id' => $assignment_id);
    
    // TODO: Check which fields are provided and add to SET clause
    if(isset($data['title'])) {
        $setClauses[] = "title = :title";
        $params[':title'] = sanitizeInput($data['title']);
    }
    
    if(isset($data['description'])) {
        $setClauses[] = "description = :description";
        $params[':description'] = sanitizeInput($data['description']);
    }
    
    if(isset($data['due_date'])) {
        if(!validateDate($data['due_date'])) {
            sendResponse(array('success' => false, 'message' => 'Invalid date format'), 400);
            return;
        }
        $setClauses[] = "due_date = :due_date";
        $params[':due_date'] = $data['due_date'];
    }
    
    if(isset($data['files'])) {
        $setClauses[] = "files = :files";
        $params[':files'] = json_encode($data['files']);
    }
    
    // TODO: If no fields to update (besides updated_at), return 400 error
    if(empty($setClauses)) {
        sendResponse(array('success' => false, 'message' => 'No fields to update'), 400);
        return;
    }
    
    // TODO: Complete the UPDATE query
    $setClauses[] = "updated_at = NOW()";
    $sql = "UPDATE assignments SET " . implode(', ', $setClauses) . " WHERE id = :id";
    
    // TODO: Prepare the statement
    $stmt = $db->prepare($sql);
    
    // TODO: Bind all parameters dynamically
    foreach($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    // TODO: Execute the statement
    $result = $stmt->execute();
    
    // TODO: Check if update was successful
    if($result) {
        sendResponse(array('success' => true, 'message' => 'Assignment updated successfully'));
        return;
    }
    
    // TODO: If no rows affected, return appropriate message
    sendResponse(array('success' => false, 'message' => 'No changes made'), 200);
}


/**
 * Function: Delete an assignment
 * Method: DELETE
 * Endpoint: ?resource=assignments&id={assignment_id}
 * 
 * Query Parameters:
 *   - id: Assignment ID (required)
 * 
 * Response: JSON object with success status
 */
function deleteAssignment($db, $assignmentId) {
    // TODO: Validate that $assignmentId is provided and not empty
    if(empty($assignmentId)) {
        sendResponse(array('success' => false, 'message' => 'Assignment ID is required'), 400);
        return;
    }
    
    // TODO: Check if assignment exists
    $checkSql = "SELECT id FROM assignments WHERE id = :id";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->bindValue(':id', $assignmentId);
    $checkStmt->execute();
    
    if(!$checkStmt->fetch()) {
        sendResponse(array('success' => false, 'message' => 'Assignment not found'), 404);
        return;
    }
    
    // TODO: Delete associated comments first (due to foreign key constraint)
    $deleteCommentsSql = "DELETE FROM comments_assignment WHERE assignment_id = :assignment_id";
    $deleteCommentsStmt = $db->prepare($deleteCommentsSql);
    $deleteCommentsStmt->bindValue(':assignment_id', $assignmentId);
    $deleteCommentsStmt->execute();
    
    // TODO: Prepare DELETE query for assignment
    $sql = "DELETE FROM assignments WHERE id = :id";
    $stmt = $db->prepare($sql);
    
    // TODO: Bind the :id parameter
    $stmt->bindValue(':id', $assignmentId);
    
    // TODO: Execute the statement
    $result = $stmt->execute();
    
    // TODO: Check if delete was successful
    if($result) {
        sendResponse(array('success' => true, 'message' => 'Assignment deleted successfully'));
        return;
    }
    
    // TODO: If delete failed, return 500 error
    sendResponse(array('success' => false, 'message' => 'Failed to delete assignment'), 500);
}

// COMMENT CRUD FUNCTIONS
// ============================================================================

/**
 * Function: Get all comments for a specific assignment
 * Method: GET
 * Endpoint: ?resource=comments&assignment_id={assignment_id}
 * 
 * Query Parameters:
 *   - assignment_id: The assignment ID (required)
 * 
 * Response: JSON array of comment objects
 */
function getCommentsByAssignment($db, $assignmentId) {
    // TODO: Validate that $assignmentId is provided and not empty
    if(empty($assignmentId)) {
        sendResponse(array('success' => false, 'message' => 'Assignment ID is required'), 400);
        return;
    }
    
    // TODO: Prepare SQL query to select all comments for the assignment
    $sql = "SELECT * FROM comments_assignment WHERE assignment_id = :assignment_id ORDER BY created_at ASC";
    $stmt = $db->prepare($sql);
    
    // TODO: Bind the :assignment_id parameter
    $stmt->bindValue(':assignment_id', $assignmentId);
    
    // TODO: Execute the statement
    $stmt->execute();
    
    // TODO: Fetch all results as associative array
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // TODO: Return success response with comments data
    sendResponse(array('success' => true, 'data' => $comments));
}

/**
 * Function: Create a new comment
 * Method: POST
 * Endpoint: ?resource=comments
 * 
 * Required JSON Body:
 *   - assignment_id: Assignment ID (required)
 *   - author: Comment author name (required)
 *   - text: Comment content (required)
 * 
 * Response: JSON object with created comment data
 */
function createComment($db, $data) {
    // TODO: Validate required fields
    if(empty($data['assignment_id']) || empty($data['author']) || empty($data['text'])) {
        sendResponse(array('success' => false, 'message' => 'Assignment ID, author, and text are required'), 400);
        return;
    }
    
    // TODO: Sanitize input data
    $assignment_id = sanitizeInput($data['assignment_id']);
    $author = sanitizeInput($data['author']);
    $text = sanitizeInput($data['text']);
    
    // TODO: Validate that text is not empty after trimming
    if(empty(trim($text))) {
        sendResponse(array('success' => false, 'message' => 'Comment text cannot be empty'), 400);
        return;
    }
    
    // TODO: Verify that the assignment exists
    $checkSql = "SELECT id FROM assignments WHERE id = :id";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->bindValue(':id', $assignment_id);
    $checkStmt->execute();
    
    if(!$checkStmt->fetch()) {
        sendResponse(array('success' => false, 'message' => 'Assignment not found'), 404);
        return;
    }
    
    // TODO: Prepare INSERT query for comment
    $sql = "INSERT INTO comments_assignment (assignment_id, author, text, created_at) 
            VALUES (:assignment_id, :author, :text, NOW())";
    $stmt = $db->prepare($sql);
    
    // TODO: Bind all parameters
    $stmt->bindValue(':assignment_id', $assignment_id);
    $stmt->bindValue(':author', $author);
    $stmt->bindValue(':text', $text);
    
    // TODO: Execute the statement
    $result = $stmt->execute();
    
    // TODO: Get the ID of the inserted comment
    $commentId = $db->lastInsertId();
    
    // TODO: Return success response with created comment data
    if($result) {
        sendResponse(array(
            'success' => true,
            'message' => 'Comment created successfully',
            'data' => array(
                'id' => $commentId,
                'assignment_id' => $assignment_id,
                'author' => $author,
                'text' => $text
            )
        ), 201);
        return;
    }
    
    sendResponse(array('success' => false, 'message' => 'Failed to create comment'), 500);
}

/**
 * Function: Delete a comment
 * Method: DELETE
 * Endpoint: ?resource=comments&id={comment_id}
 * 
 * Query Parameters:
 *   - id: Comment ID (required)
 * 
 * Response: JSON object with success status
 */
function deleteComment($db, $commentId) {
    // TODO: Validate that $commentId is provided and not empty
    if(empty($commentId)) {
        sendResponse(array('success' => false, 'message' => 'Comment ID is required'), 400);
        return;
    }
    
    // TODO: Check if comment exists
    $checkSql = "SELECT id FROM comments_assignment WHERE id = :id";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->bindValue(':id', $commentId);
    $checkStmt->execute();
    
    if(!$checkStmt->fetch()) {
        sendResponse(array('success' => false, 'message' => 'Comment not found'), 404);
        return;
    }
    
    // TODO: Prepare DELETE query
    $sql = "DELETE FROM comments_assignment WHERE id = :id";
    $stmt = $db->prepare($sql);
    
    // TODO: Bind the :id parameter
    $stmt->bindValue(':id', $commentId);
    
    // TODO: Execute the statement
    $result = $stmt->execute();
    
    // TODO: Check if delete was successful
    if($result) {
        sendResponse(array('success' => true, 'message' => 'Comment deleted successfully'));
        return;
    }
    
    // TODO: If delete failed, return 500 error
    sendResponse(array('success' => false, 'message' => 'Failed to delete comment'), 500);
}

// ============================================================================
// MAIN REQUEST ROUTER
// ============================================================================

try {
    // TODO: Get the 'resource' query parameter to determine which resource to access
    $resource = isset($_GET['resource']) ? $_GET['resource'] : '';
    
    // TODO: Route based on HTTP method and resource type
    if($method === 'GET') {
        // TODO: Handle GET requests
        if($resource === 'assignments') {
            // TODO: Check if 'id' query parameter exists
            if(isset($_GET['id'])) {
                getAssignmentById($db, $_GET['id']);
            } else {
                getAllAssignments($db);
            }
        } elseif($resource === 'comments') {
            // TODO: Check if 'assignment_id' query parameter exists
            if(isset($_GET['assignment_id'])) {
                getCommentsByAssignment($db, $_GET['assignment_id']);
            } else {
                sendResponse(array('success' => false, 'message' => 'assignment_id parameter is required'), 400);
            }
        } else {
            // TODO: Invalid resource, return 400 error
            sendResponse(array('success' => false, 'message' => 'Invalid resource'), 400);
        }
    } elseif($method === 'POST') {
        // TODO: Handle POST requests (create operations)
        if($resource === 'assignments') {
            // TODO: Call createAssignment($db, $data)
            createAssignment($db, $data);
        } elseif($resource === 'comments') {
            // TODO: Call createComment($db, $data)
            createComment($db, $data);
        } else {
            // TODO: Invalid resource, return 400 error
            sendResponse(array('success' => false, 'message' => 'Invalid resource'), 400);
        }
    } elseif($method === 'PUT') {
        // TODO: Handle PUT requests (update operations)
        if($resource === 'assignments') {
            // TODO: Call updateAssignment($db, $data)
            updateAssignment($db, $data);
        } else {
            // TODO: PUT not supported for other resources
            sendResponse(array('success' => false, 'message' => 'PUT method not supported for this resource'), 405);
        }
    } elseif($method === 'DELETE') {
        // TODO: Handle DELETE requests
        if($resource === 'assignments') {
            // TODO: Get 'id' from query parameter or request body
            $id = isset($_GET['id']) ? $_GET['id'] : (isset($data['id']) ? $data['id'] : null);
            deleteAssignment($db, $id);
        } elseif($resource === 'comments') {
            // TODO: Get comment 'id' from query parameter
            $id = isset($_GET['id']) ? $_GET['id'] : null;
            deleteComment($db, $id);
        } else {
            // TODO: Invalid resource, return 400 error
            sendResponse(array('success' => false, 'message' => 'Invalid resource'), 400);
        }
    } else {
        // TODO: Method not supported
        sendResponse(array('success' => false, 'message' => 'HTTP method not supported'), 405);
    }
} catch(PDOException $e) {
    // TODO: Handle database errors
    sendResponse(array('success' => false, 'message' => 'Database error: ' . $e->getMessage()), 500);
} catch(Exception $e) {
    // TODO: Handle general errors
    sendResponse(array('success' => false, 'message' => 'Error: ' . $e->getMessage()), 500);
}

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Helper function to send JSON response and exit
 * 
 * @param array $data - Data to send as JSON
 * @param int $statusCode - HTTP status code (default: 200)
 */
function sendResponse($data, $statusCode = 200) {
    // TODO: Set HTTP response code
    http_response_code($statusCode);
    
    // TODO: Ensure data is an array
    if(!is_array($data)) {
        $data = array('data' => $data);
    }
    
    // TODO: Echo JSON encoded data
    echo json_encode($data);
    
    // TODO: Exit to prevent further execution
    exit();
}

/**
 * Helper function to sanitize string input
 * 
 * @param string $data - Input data to sanitize
 * @return string - Sanitized data
 */
function sanitizeInput($data) {
    // TODO: Trim whitespace from beginning and end
    $data = trim($data);
    
    // TODO: Remove HTML and PHP tags
    $data = strip_tags($data);
    
    // TODO: Convert special characters to HTML entities
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    
    // TODO: Return the sanitized data
    return $data;
}

/**
 * Helper function to validate date format (YYYY-MM-DD)
 * 
 * @param string $date - Date string to validate
 * @return bool - True if valid, false otherwise
 */
function validateDate($date) {
    // TODO: Use DateTime::createFromFormat to validate
    $d = DateTime::createFromFormat('Y-m-d', $date);
    
    // TODO: Return true if valid, false otherwise
    return $d && $d->format('Y-m-d') === $date;
}

/**
 * Helper function to validate allowed values (for sort fields, order, etc.)
 * 
 * @param string $value - Value to validate
 * @param array $allowedValues - Array of allowed values
 * @return bool - True if valid, false otherwise
 */
function validateAllowedValue($value, $allowedValues) {
    // TODO: Check if $value exists in $allowedValues array
    return in_array($value, $allowedValues);
}
?>