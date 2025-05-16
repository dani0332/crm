<?php
// Claims management Permissions
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Claims Management Permissions - IMCRM</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <a href="index.php" class="back-link">← Back to Categories</a>
    
    <h1>Claims Management Permissions</h1>
    
    <div class="description">
        <p>
            This page documents all permissions related to claims management in the IMCRM system. 
            These permissions control various aspects of insurance claim creation, handling, 
            processing, and administration.
        </p>
    </div>
    
    <div class="category-section">
        <h2>Claims Operations</h2>
        <table>
            <thead>
                <tr>
                    <th>Permission Value</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><span class="permission-value">claim-create</span></td>
                    <td>Allows users to create new insurance claims in the system.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">claim-edit</span></td>
                    <td>Allows users to edit existing insurance claims.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">claim-delete</span></td>
                    <td>Allows users to delete insurance claims from the system.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">claim-list</span></td>
                    <td>Allows users to view the list of insurance claims.</td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <div class="category-section">
        <h2>Claims Status Management</h2>
        <table>
            <thead>
                <tr>
                    <th>Permission Value</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><span class="permission-value">claims-status-create</span></td>
                    <td>Allows users to create new claims status options in the system.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">claims-status-edit</span></td>
                    <td>Allows users to edit existing claims status options.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">claims-status-delete</span></td>
                    <td>Allows users to delete claims status options from the system.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">claims-status-list</span></td>
                    <td>Allows users to view the list of claims status options.</td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <div class="category-section">
        <h2>Claims Handlers</h2>
        <table>
            <thead>
                <tr>
                    <th>Permission Value</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><span class="permission-value">handler-create</span></td>
                    <td>Allows users to create new claims handler records in the system.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">handler-edit</span></td>
                    <td>Allows users to edit existing claims handler records.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">handler-delete</span></td>
                    <td>Allows users to delete claims handler records from the system.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">handler-list</span></td>
                    <td>Allows users to view the list of claims handlers.</td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <footer>
        <p>IMCRM Permissions Documentation © 2023</p>
    </footer>
</body>
</html> 