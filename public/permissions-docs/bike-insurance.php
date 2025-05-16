<?php
// Bike insurance Permissions
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bike Insurance Permissions - IMCRM</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <a href="index.php" class="back-link">← Back to Categories</a>
    
    <h1>Bike Insurance Permissions</h1>
    
    <div class="description">
        <p>
            This page documents all permissions related to motorcycle (bike) insurance in the IMCRM system. 
            These permissions control various aspects of bike insurance quote creation, management, 
            policy issuance, and reporting functionalities.
        </p>
    </div>
    
    <div class="category-section">
        <h2>Bike Quote Management</h2>
        <table>
            <thead>
                <tr>
                    <th>Permission Value</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><span class="permission-value">bike-quotes-create</span></td>
                    <td>Allows users to create new bike insurance quotes in the system.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">bike-quotes-edit</span></td>
                    <td>Allows users to edit existing bike insurance quotes.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">bike-quotes-delete</span></td>
                    <td>Allows users to delete bike insurance quotes from the system.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">bike-quotes-list</span></td>
                    <td>Allows users to view the list of bike insurance quotes.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">bike-quotes-show</span></td>
                    <td>Allows users to view detailed information about bike insurance quotes.</td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <div class="category-section">
        <h2>Bike Insurance Reports & Dashboard</h2>
        <table>
            <thead>
                <tr>
                    <th>Permission Value</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><span class="permission-value">bike-comprehensive-dashboard</span></td>
                    <td>Allows users to access the comprehensive bike insurance dashboard.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">bike-conversion-report</span></td>
                    <td>Provides access to bike insurance conversion reports.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">bike-distribution-report</span></td>
                    <td>Provides access to bike insurance distribution reports.</td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <footer>
        <p>IMCRM Permissions Documentation © 2023</p>
    </footer>
</body>
</html> 