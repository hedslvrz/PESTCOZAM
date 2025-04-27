<?php
session_start();
require_once "../database.php";

echo "<h1>Login API Debug</h1>";

// Test database connection
echo "<h2>Testing Database Connection</h2>";
try {
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db) {
        echo "<p style='color:green'>✓ Database connection successful</p>";
        
        // Test if we can query the users table
        try {
            $stmt = $db->query("SELECT COUNT(*) as count FROM users");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "<p>Found {$result['count']} users in the database</p>";
            
            // List a few sample users (don't show passwords in production)
            $stmt = $db->query("SELECT id, firstname, lastname, email, role, status FROM users LIMIT 3");
            echo "<h3>Sample Users:</h3>";
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Status</th></tr>";
            while ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo "<tr>";
                echo "<td>{$user['id']}</td>";
                echo "<td>{$user['firstname']} {$user['lastname']}</td>";
                echo "<td>{$user['email']}</td>";
                echo "<td>{$user['role']}</td>";
                echo "<td>{$user['status']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } catch (Exception $e) {
            echo "<p style='color:red'>Error querying users table: {$e->getMessage()}</p>";
        }
    } else {
        echo "<p style='color:red'>✗ Database connection failed</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>Database connection error: {$e->getMessage()}</p>";
}

// Test environment variables
echo "<h2>Environment Information</h2>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "</p>";
echo "<p>Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "</p>";

// Database configuration
echo "<h2>Database Configuration</h2>";
echo "<p>The database connection is configured with:</p>";
echo "<ul>";
echo "<li>Host: localhost</li>";
echo "<li>Database Name: u302876046_pestcozam</li>";
echo "<li>Username: u302876046_root</li>";
echo "<li>Password: [hidden]</li>";
echo "</ul>";

// Test local database connection
echo "<h2>Testing Local Database Connection</h2>";
try {
    $localConn = new PDO("mysql:host=localhost;dbname=pestcozam", "root", "");
    $localConn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p style='color:green'>✓ Local database connection successful</p>";
    
    // Check if we can query
    try {
        $stmt = $localConn->query("SHOW TABLES");
        echo "<p>Available tables:</p>";
        echo "<ul>";
        while ($table = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<li>" . array_values($table)[0] . "</li>";
        }
        echo "</ul>";
    } catch (Exception $e) {
        echo "<p style='color:red'>Error querying local database: {$e->getMessage()}</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color:red'>Local database connection error: {$e->getMessage()}</p>";
    
    // Try connecting without database specified
    try {
        $basicConn = new PDO("mysql:host=localhost", "root", "");
        $basicConn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo "<p style='color:green'>✓ Connected to MySQL server without database</p>";
        
        // Check available databases
        $stmt = $basicConn->query("SHOW DATABASES");
        echo "<p>Available databases:</p>";
        echo "<ul>";
        while ($db = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<li>" . $db['Database'] . "</li>";
        }
        echo "</ul>";
    } catch (PDOException $e2) {
        echo "<p style='color:red'>Basic MySQL connection error: {$e2->getMessage()}</p>";
    }
}

// Test login form submission
echo "<h2>Test Login Form</h2>";
echo "<p>Use this form to test the login API directly:</p>";
?>

<form id="testLogin" style="margin: 20px 0; padding: 15px; border: 1px solid #ccc; border-radius: 5px;">
    <div style="margin-bottom: 10px;"></div></div>
        <label for="email" style="display: block; margin-bottom: 5px;">Email:</label>
        <input type="email" id="email" name="email" value="dunnlvrz13@gmail.com" style="width: 100%; padding: 8px;">
    </div>
    <div style="margin-bottom: 10px;"></div>
        <label for="password" style="display: block; margin-bottom: 5px;">Password:</label>
        <input type="password" id="password" name="password" value="password123" style="width: 100%; padding: 8px;">
    </div>
    <button type="button" id="submitBtn" style="padding: 8px 15px; background: #1E2A5A; color: white; border: none; border-radius: 4px; cursor: pointer;">Test Login</button>
</form>

<div id="result" style="margin-top: 20px; padding: 15px; border: 1px solid #ccc; border-radius: 5px; display: none;"></div>
    <h3>API Response:</h3>
    <pre id="response" style="background: #f5f5f5; padding: 10px; overflow: auto;"></pre>
</div>

<script>
document.getElementById('submitBtn').addEventListener('click', function() {
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    
    // Create FormData
    const formData = new FormData();
    formData.append('email', email);
    formData.append('password', password);
    
    fetch('../PHP CODES/login_api.php', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => {
        // Get the content type
        const contentType = response.headers.get('content-type');
        
        // First try to get the response text
        return response.text().then(text => {
            document.getElementById('result').style.display = 'block';
            
            // Try to parse as JSON
            try {
                const data = JSON.parse(text);
                document.getElementById('response').textContent = JSON.stringify(data, null, 2);
                return;
            } catch (e) {
                // Not JSON, just show the raw response
                document.getElementById('response').textContent = text;
            }
        });
    })
    .catch(error => {
        document.getElementById('result').style.display = 'block';
        document.getElementById('response').textContent = 'Fetch Error: ' + error.message;
    });
});
</script>
