<?php
/**
 * Course Resources API
 * 
 * This is a RESTful API that handles all CRUD operations for course resources 
 * and their associated comments/discussions.
 * It uses PDO to interact with a MySQL database.
 * 
 * Database Table Structures (for reference):
 * 
 * Table: resources
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - title (VARCHAR(255))
 *   - description (TEXT)
 *   - link (VARCHAR(500))
 *   - created_at (TIMESTAMP)
 * 
 * Table: comments
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - resource_id (INT, FOREIGN KEY references resources.id)
 *   - author (VARCHAR(100))
 *   - text (TEXT)
 *   - created_at (TIMESTAMP)
 * 
 * HTTP Methods Supported:
 *   - GET: Retrieve resource(s) or comment(s)
 *   - POST: Create a new resource or comment
 *   - PUT: Update an existing resource
 *   - DELETE: Delete a resource or comment
 * 
 * Response Format: JSON
 * 
 * API Endpoints:
 *   Resources:
 *     GET    /api/resources.php                    - Get all resources
 *     GET    /api/resources.php?id={id}           - Get single resource by ID
 *     POST   /api/resources.php                    - Create new resource
 *     PUT    /api/resources.php                    - Update resource
 *     DELETE /api/resources.php?id={id}           - Delete resource
 * 
 *   Comments:
 *     GET    /api/resources.php?resource_id={id}&action=comments  - Get comments for resource
 *     POST   /api/resources.php?action=comment                    - Create new comment
 *     DELETE /api/resources.php?comment_id={id}&action=delete_comment - Delete comment
 */

// ============================================================================
// HEADERS AND INITIALIZATION
// ============================================================================

// TODO: Set headers for JSON response and CORS
// Set Content-Type to application/json
// Allow cross-origin requests (CORS) if needed
// Allow specific HTTP methods (GET, POST, PUT, DELETE, OPTIONS)
// Allow specific headers (Content-Type, Authorization)
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// TODO: Handle preflight OPTIONS request
// If the request method is OPTIONS, return 200 status and exit
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS'){
    http_response_code(200);
    exit;
}

// TODO: Include the database connection class
// Assume the Database class has a method getConnection() that returns a PDO instance
// Example: require_once '../config/Database.php';
require_once '../config/Database.php';

// TODO: Get the PDO database connection
// Example: $database = new Database();
// Example: $db = $database->getConnection();
$database = new Database();
$db = $database->getConnection();

// TODO: Get the HTTP request method
// Use $_SERVER['REQUEST_METHOD']
$method = $_SERVER['REQUEST_METHOD'];

// TODO: Get the request body for POST and PUT requests
// Use file_get_contents('php://input') to get raw POST data
// Decode JSON data using json_decode() with associative array parameter
$input = [];
if ($method === 'POST' || $method === 'PUT') {
    $raw_input = file_get_contents('php://input');
    $input = json_decode($raw_input, true);
}


// TODO: Parse query parameters
// Get 'action', 'id', 'resource_id', 'comment_id' from $_GET
$action = $_GET['action'] ?? null;
$id = $_GET['id'] ?? null;
$resourceId = $_GET['resource_id'] ?? null;
$commentId = $_GET['comment_id'] ?? null;


// ============================================================================
// RESOURCE FUNCTIONS
// ============================================================================

/**
 * Function: Get all resources
 * Method: GET
 * 
 * Query Parameters:
 *   - search: Optional search term to filter by title or description
 *   - sort: Optional field to sort by (title, created_at)
 *   - order: Optional sort order (asc or desc, default: desc)
 * 
 * Response:
 *   - success: true/false
 *   - data: Array of resource objects
 */
function getAllResources($db) {
    // TODO: Initialize the base SQL query
    // SELECT id, title, description, link, created_at FROM resources
    $sql = "SELECT id, title, description, link, created_at FROM resources";
    $params = [];
    
    // TODO: Check if search parameter exists
    // If yes, add WHERE clause using LIKE to search title and description
    // Use OR to search both fields
    $search = $_GET['search'] ?? null;
    if ($search) {
        $sql .= "WHERE title LIKE :search OR description LIKE :search";
        $params[':search'] = "%" . $search . "%";
    }
    
    // TODO: Check if sort parameter exists and validate it
    // Only allow: title, created_at
    // Default to created_at if not provided or invalid
    $sort = $_GET['sort'] ?? 'created_at';
    $allowed_sort = ['title', 'created_at'];
    if (!in_array($sort, $allowed_sort)) {
        $sort = 'created_at';
    }
    
    // TODO: Check if order parameter exists and validate it
    // Only allow: asc, desc
    // Default to desc if not provided or invalid
    $order = $_GET['order'] ?? 'desc';
    $allowed_order = ['asc', 'desc'];
    if (!in_array($order, $allowed_order)){
        $order = 'desc';
    }    
    // TODO: Add ORDER BY clause to query
    $sql .= " ORDER BY $sort $order";
    
    // TODO: Prepare the SQL query using PDO
    $stmt = $db->prepare($sql);
    
    // TODO: If search parameter was used, bind the search parameter
    // Use % wildcards for LIKE search
    if ($search) {
        $stmt->bindValue(':search' , "%" . $search . "%");
    }
    
    // TODO: Execute the query
    $stmt->execute();
    
    // TODO: Fetch all results as an associative array
    $resources = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // TODO: Return JSON response with success status and data
    // Use the helper function sendResponse()
    sendResponse(array('success' => true, 'data' => $resources));
}


/**
 * Function: Get a single resource by ID
 * Method: GET
 * 
 * Parameters:
 *   - $resourceId: The resource's database ID
 * 
 * Response:
 *   - success: true/false
 *   - data: Resource object or error message
 */
function getResourceById($db, $resourceId) {
    // TODO: Validate that resource ID is provided and is numeric
    // If not, return error response with 400 status
    if (empty($resourceId) || !is_numeric($resourceId)){
        sendResponse(array('success' => false, 'message' => 'Invalid resource ID'), 400);
        return;
    }
    
    // TODO: Prepare SQL query to select resource by id
    // SELECT id, title, description, link, created_at FROM resources WHERE id = ?
    $sql = "SELECT id, title, description, link, created_at FROM resources WHERE id = ?";
    $stmt = $db->prepare($sql);
    
    // TODO: Bind the resource_id parameter
    $stmt->bindValue(1, $resourceId, PDO::PARAM_INT);
    
    // TODO: Execute the query
    $stmt->execute();
    
    // TODO: Fetch the result as an associative array
    $resource = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // TODO: Check if resource exists
    // If yes, return success response with resource data
    // If no, return error response with 404 status
    if ($resource) {
        sendResponse(array('success' => true, 'data' => $resource));
    }else {
        sendResponse(array('success' => false, 'message' => 'Resource not found'), 404);
    }
    return;
}


/**
 * Function: Create a new resource
 * Method: POST
 * 
 * Required JSON Body:
 *   - title: Resource title (required)
 *   - description: Resource description (optional)
 *   - link: URL to the resource (required)
 * 
 * Response:
 *   - success: true/false
 *   - message: Success or error message
 *   - id: ID of created resource (on success)
 */
function createResource($db, $data) {
    // TODO: Validate required fields
    // Check if title and link are provided and not empty
    // If any required field is missing, return error response with 400 status
    if (empty($data['title']) || empty($data['link'])) {
        sendResponse(array('success' => false, 'message' => 'Title and link are required'), 400);
        return;
    }
    
    // TODO: Sanitize input data
    // Trim whitespace from all fields
    // Validate URL format for link using filter_var with FILTER_VALIDATE_URL
    // If URL is invalid, return error response with 400 status
    $title = trim($data['title']);
    $description = trim($data['description'] ?? '');
    $link = trim($data['link']);
    if (!filter_var($link, FILTER_VALIDATE_URL)) {
        sendResponse(array('success' => false, 'message' => 'Invalid URL format'), 400);
        return;
    }

    
    // TODO: Set default value for description if not provided
    // Use empty string as default
    if (empty($description)) {
        $description = '';
    }
    
    // TODO: Prepare INSERT query
    // INSERT INTO resources (title, description, link) VALUES (?, ?, ?)
    $sql = "INSERT INTO resources (title, description, link) VALUES (?, ?, ?)";
    $stmt = $db->prepare($sql);
    
    // TODO: Bind parameters
    // Bind title, description, and link
    $stmt->bindValue(':title', $title);
    $stmt->bindValue(':description', $description);
    $stmt->bindValue(':link', $link);
    
    // TODO: Execute the query
    $stmt->execute();
    
    // TODO: Check if insert was successful
    // If yes, get the last inserted ID using $db->lastInsertId()
    // Return success response with 201 status and the new resource ID
    // If no, return error response with 500 status
    if ($stmt) {
        $newID = $db->lastInsertId();
        sendResponse(array(
            'success' => true, 
            'message' => 'Resource created successfully', 
            'id' => $newID),
        201);
    }else {
        sendResponse(array('success' => false, 'message' => 'Failed to create resource'), 500);
    }
}


/**
 * Function: Update an existing resource
 * Method: PUT
 * 
 * Required JSON Body:
 *   - id: The resource's database ID (required)
 *   - title: Updated resource title (optional)
 *   - description: Updated description (optional)
 *   - link: Updated URL (optional)
 * 
 * Response:
 *   - success: true/false
 *   - message: Success or error message
 */
function updateResource($db, $data) {
    // TODO: Validate that resource ID is provided
    // If not, return error response with 400 status
    if (empty($data['id']) || !is_numeric($data['id'])) {
        sendResponse(array('success' => false, 'message' => 'Valid Resource ID required'), 400);
        return;
    }
    
    // TODO: Check if resource exists
    // Prepare and execute a SELECT query to find the resource by id
    // If not found, return error response with 404 status
    $resourceId = $data['id'];
    $checkSql = "SELECT id FROM resources WHERE id = ?";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->bindValue(':id', $resourceId);
    $checkStmt->execute();

    if (!$checkStmt->fetch()) {
        sendResponse(array('success' => false, 'message' => 'Resource not found'),404);
        return;
    }
    
    // TODO: Build UPDATE query dynamically based on provided fields
    // Initialize empty arrays for fields to update and values
    // Check which fields are provided (title, description, link)
    // Add each provided field to the update arrays
    $setClauses = array();
    $params = array(':id' => $resourceId);

    if (isset($data['title'])) {
        $setClauses[] = "title = :title";
        $params[':title'] = sanitizeInput($data['title']);
    }

    if (isset($data['description'])) {
        $setClauses[] = "description = :description";
        $params[':description'] = sanitizeInput($data['description']);
    }
    
    if (isset($data['link'])) {
        $setClauses[] = "link = :link";
        $params[':link'] = sanitizeInput($data['link']);
    }

    // TODO: If no fields to update, return error response with 400 status
    if (empty($setClauses)) {
        sendResponse(array('success' =>false, 'message' => 'No fields to update'), 400);
        return;
    }
    
    // TODO: If link is being updated, validate URL format
    // Use filter_var with FILTER_VALIDATE_URL
    // If invalid, return error response with 400 status
    if (isset($data['link'])) {
        if (!filter_var($data['link'], FILTER_VALIDATE_URL)) {
            sendResponse(array('success' => false, 'message' => 'Invalid URL format'), 400);
            return;
        }
    }
    
    // TODO: Build the complete UPDATE SQL query
    // UPDATE resources SET field1 = ?, field2 = ? WHERE id = ?
    $sql = "UPDATE resources SET " . implode(",", $setClauses) . " WHERE id = :id";
    
    // TODO: Prepare the query
    $stmt = $db-> prepare($sql);
    
    // TODO: Bind parameters dynamically
    // Bind all update values, then bind the resource ID at the end
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    // TODO: Execute the query
    $success = $stmt->execute();
    
    // TODO: Check if update was successful
    // If yes, return success response with 200 status
    // If no, return error response with 500 status
    if ($success) {
        sendResponse(array('success' => true, 'message' => 'Resource updated successfully'), 200);
    } else {
        sendResponse(array('success' => false, 'message' => 'Failed to update resource'), 500);
    }
}


/**
 * Function: Delete a resource
 * Method: DELETE
 * 
 * Parameters:
 *   - $resourceId: The resource's database ID
 * 
 * Response:
 *   - success: true/false
 *   - message: Success or error message
 * 
 * Note: This should also delete all associated comments
 */
function deleteResource($db, $resourceId) {
    // TODO: Validate that resource ID is provided and is numeric
    // If not, return error response with 400 status
    if (empty($resourceId) || !is_numeric($resourceId)) {
        sendResponse(array('success' => false, 'message' => 'Invalid resource ID'), 400);
        return;
    }
    
    // TODO: Check if resource exists
    // Prepare and execute a SELECT query
    // If not found, return error response with 404 status
    $checkSql = "SELECT id FROM resources WHERE id = ?";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->bindValue(':id', $resourceId);
    $checkStmt->execute();

    if (!$checkStmt->fetch()){
        sendResponse(array('success' =>false, 'message' => 'Resource not found'), 404);
        return;
    }
    
    // TODO: Begin a transaction (for data integrity)
    // Use $db->beginTransaction()
    $db->beginTransaction();
    
    try {
        // TODO: First, delete all associated comments
        // Prepare DELETE query for comments table
        // DELETE FROM comments WHERE resource_id = ?
        $deleteCommentsSql = "DELETE FROM comments WHERE resource_id = :resource_id";
        $deleteCommentsStmt = $db->prepare($deleteCommentsSql);

        // TODO: Bind resource_id and execute
        $deleteCommentsStmt->bindValue(':resource_id', $resourceId);
        $deleteCommentsStmt->execute();
        
        // TODO: Then, delete the resource
        // Prepare DELETE query for resources table
        // DELETE FROM resources WHERE id = ?
        $deleteResourceSql = "DELETE FROM resources WHERE id = :resource_id";
        $deleteResourceStmt = $db->prepare($deleteResourceSql);
        
        // TODO: Bind resource_id and execute
        $deleteResourceStmt->bindValue(':resource_id', $resourceId);
        $deleteResourceStmt->execute();
        
        // TODO: Commit the transaction
        // Use $db->commit()
        $db->commit();
        
        // TODO: Return success response with 200 status
        sendResponse(array('success' => true, 'message' =>'Resource deleted successfully'), 200);
        
    } catch (Exception $e) {
        // TODO: Rollback the transaction on error
        // Use $db->rollBack()
        $db->rollBack();
        
        // TODO: Return error response with 500 status
        sendResponse(array('success' => false, 'message' => 'Failed to delete resource'), 500);
    }
}


// ============================================================================
// COMMENT FUNCTIONS
// ============================================================================

/**
 * Function: Get all comments for a specific resource
 * Method: GET with action=comments
 * 
 * Query Parameters:
 *   - resource_id: The resource's database ID (required)
 * 
 * Response:
 *   - success: true/false
 *   - data: Array of comment objects
 */
function getCommentsByResourceId($db, $resourceId) {
    // TODO: Validate that resource_id is provided and is numeric
    // If not, return error response with 400 status
    if (empty($resourceId) || !is_numeric($resourceId)) {
        sendResponse(array('success' => false, 'message' => 'Invalid resource ID'), 400);
        return;
    }
    
    // TODO: Prepare SQL query to select comments for the resource
    // SELECT id, resource_id, author, text, created_at 
    // FROM comments 
    // WHERE resource_id = ? 
    // ORDER BY created_at ASC
    $sql = "SELECT id, resource_id, author, text, created_at
            FROM comments
            WHERE resource_id = :resource_id
            ORDER BY created_at ASC";
    $stmt = $db->prepare($sql);
    
    // TODO: Bind the resource_id parameter
    $stmt->bindValue(':resource_id', $resourceId);
    
    // TODO: Execute the query
    $stmt->execute();
    
    // TODO: Fetch all results as an associative array
    $comments = $stmt->fetchall(PDO::FETCH_ASSOC);
    
    // TODO: Return success response with comments data
    // Even if no comments exist, return empty array (not an error)
    sendResponse(array('success' => true, 'data' => $comments));
}


/**
 * Function: Create a new comment
 * Method: POST with action=comment
 * 
 * Required JSON Body:
 *   - resource_id: The resource's database ID (required)
 *   - author: Name of the comment author (required)
 *   - text: Comment text content (required)
 * 
 * Response:
 *   - success: true/false
 *   - message: Success or error message
 *   - id: ID of created comment (on success)
 */
function createComment($db, $data) {
    // TODO: Validate required fields
    // Check if resource_id, author, and text are provided and not empty
    // If any required field is missing, return error response with 400 status
    if (empty($data['resource_id']) || empty($data['author']) || empty($data['text'])) {
        sendResponse(array('success' => false, ' message'=> 'resource_id, author and text are required'), 400);
        return;
    }
    
    // TODO: Validate that resource_id is numeric
    // If not, return error response with 400 status
    if (!is_numeric($data['resource_id'])) {
        sendResponse(array('success' => false, 'message' => 'Invalid resource ID'), 400);
        return;
    }
    
    // TODO: Check if the resource exists
    // Prepare and execute SELECT query on resources table
    // If resource not found, return error response with 404 status
    $resourceId = $data['resource_id'];
    $checkSql = "SELECT id FROM resources WHERE id = :resource_id";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->bindValue(':resource_id', $resourceId);
    $checkStmt->execute();

    if (!$checkStmt->fetch()) {
        sendResponse(array('success' => false, 'message' => 'Resource not found'), 404);
        return;
    }
    
    // TODO: Sanitize input data
    // Trim whitespace from author and text
    $author = sanitizeInput($data['author']);
    $text = sanitizeInput($data['text']);
    
    // TODO: Prepare INSERT query
    // INSERT INTO comments (resource_id, author, text) VALUES (?, ?, ?)
    $sql = "INSERT INTO comments (resource_id, author, text) VALUES (:resource_id, :author, :text)";
    $stmt = $db->prepare($sql);
    
    // TODO: Bind parameters
    // Bind resource_id, author, and text
    $stmt->bindValue(':resource_id', $resourceId);
    $stmt->bindValue(':author', $author);
    $stmt->bindValue(':text', $text)
    
    // TODO: Execute the query
    $stmt->execute();
    
    // TODO: Check if insert was successful
    // If yes, get the last inserted ID using $db->lastInsertId()
    // Return success response with 201 status and the new comment ID
    // If no, return error response with 500 status
    if ($stmt) {
        $newID = $db->lastInsertId();
        sendResponse(array(
            'success' => true,
            'message' => 'Comment created successfully',
            'id' => $newID),
        201);
    } else{
        sendResponse(array('success' => false, 'message'=> 'Failed to create comment'), 500);
    }
}


/**
 * Function: Delete a comment
 * Method: DELETE with action=delete_comment
 * 
 * Query Parameters or JSON Body:
 *   - comment_id: The comment's database ID (required)
 * 
 * Response:
 *   - success: true/false
 *   - message: Success or error message
 */
function deleteComment($db, $commentId) {
    // TODO: Validate that comment_id is provided and is numeric
    // If not, return error response with 400 status
    
    // TODO: Check if comment exists
    // Prepare and execute a SELECT query
    // If not found, return error response with 404 status
    
    // TODO: Prepare DELETE query
    // DELETE FROM comments WHERE id = ?
    
    // TODO: Bind the comment_id parameter
    
    // TODO: Execute the query
    
    // TODO: Check if delete was successful
    // If yes, return success response with 200 status
    // If no, return error response with 500 status
}


// ============================================================================
// MAIN REQUEST ROUTER
// ============================================================================

try {
    // TODO: Route the request based on HTTP method and action parameter
    
    if ($method === 'GET') {
        // TODO: Check the action parameter to determine which function to call
        
        // If action is 'comments', get comments for a resource
        // TODO: Check if action === 'comments'
        // Get resource_id from query parameters
        // Call getCommentsByResourceId()
        
        // If id parameter exists, get single resource
        // TODO: Check if 'id' parameter exists in $_GET
        // Call getResourceById()
        
        // Otherwise, get all resources
        // TODO: Call getAllResources()
        
    } elseif ($method === 'POST') {
        // TODO: Check the action parameter to determine which function to call
        
        // If action is 'comment', create a new comment
        // TODO: Check if action === 'comment'
        // Call createComment()
        
        // Otherwise, create a new resource
        // TODO: Call createResource()
        
    } elseif ($method === 'PUT') {
        // TODO: Update a resource
        // Call updateResource()
        
    } elseif ($method === 'DELETE') {
        // TODO: Check the action parameter to determine which function to call
        
        // If action is 'delete_comment', delete a comment
        // TODO: Check if action === 'delete_comment'
        // Get comment_id from query parameters or request body
        // Call deleteComment()
        
        // Otherwise, delete a resource
        // TODO: Get resource id from query parameter or request body
        // Call deleteResource()
        
    } else {
        // TODO: Return error for unsupported methods
        // Set HTTP status to 405 (Method Not Allowed)
        // Return JSON error message using sendResponse()
    }
    
} catch (PDOException $e) {
    // TODO: Handle database errors
    // Log the error message (optional, use error_log())
    // Return generic error response with 500 status
    // Do NOT expose detailed error messages to the client in production
    
} catch (Exception $e) {
    // TODO: Handle general errors
    // Log the error message (optional)
    // Return error response with 500 status
}


// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Helper function to send JSON response
 * 
 * @param array $data - Data to send (should include 'success' key)
 * @param int $statusCode - HTTP status code (default: 200)
 */
function sendResponse($data, $statusCode = 200) {
    // TODO: Set HTTP response code using http_response_code()
    
    // TODO: Ensure data is an array
    // If not, wrap it in an array
    
    // TODO: Echo JSON encoded data
    // Use JSON_PRETTY_PRINT for readability (optional)
    
    // TODO: Exit to prevent further execution
    exit;
}


/**
 * Helper function to validate URL format
 * 
 * @param string $url - URL to validate
 * @return bool - True if valid, false otherwise
 */
function validateUrl($url) {
    // TODO: Use filter_var with FILTER_VALIDATE_URL
    // Return true if valid, false otherwise
}


/**
 * Helper function to sanitize input
 * 
 * @param string $data - Data to sanitize
 * @return string - Sanitized data
 */
function sanitizeInput($data) {
    // TODO: Trim whitespace using trim()
    
    // TODO: Strip HTML tags using strip_tags()
    
    // TODO: Convert special characters using htmlspecialchars()
    // Use ENT_QUOTES to escape both double and single quotes
    
    // TODO: Return sanitized data
}


/**
 * Helper function to validate required fields
 * 
 * @param array $data - Data array to validate
 * @param array $requiredFields - Array of required field names
 * @return array - Array with 'valid' (bool) and 'missing' (array of missing fields)
 */
function validateRequiredFields($data, $requiredFields) {
    // TODO: Initialize empty array for missing fields
    
    // TODO: Loop through required fields
    // Check if each field exists in data and is not empty
    // If missing or empty, add to missing fields array
    
    // TODO: Return result array
    // ['valid' => (count($missing) === 0), 'missing' => $missing]
}

?>
