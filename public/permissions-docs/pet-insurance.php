<?php
// Pet insurance Permissions
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pet Insurance Permissions - IMCRM</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <a href="index.php" class="back-link">← Back to Categories</a>
    
    <h1>Pet Insurance Permissions</h1>
    
    <div class="description">
        <p>
            This page documents all permissions related to pet insurance in the IMCRM system. 
            These permissions control various aspects of pet insurance quote creation, management, 
            policy issuance, and reporting functionalities.
        </p>
    </div>
    
    <div class="category-section">
        <h2>Pet Quote Management</h2>
        <table>
            <thead>
                <tr>
                    <th>Permission Value</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><span class="permission-value">pet-quotes-create</span></td>
                    <td>Allows users to create new pet insurance quotes in the system.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">pet-quotes-edit</span></td>
                    <td>Allows users to edit existing pet insurance quotes.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">pet-quotes-delete</span></td>
                    <td>Allows users to delete pet insurance quotes from the system.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">pet-quotes-list</span></td>
                    <td>Allows users to view the list of pet insurance quotes.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">pet-quotes-show</span></td>
                    <td>Allows users to view detailed information about pet insurance quotes.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">pet-quotes-card</span></td>
                    <td>Enables the card view for pet insurance quotes and policies.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">pet-quotes-update</span></td>
                    <td>Allows users to update pet insurance quotes.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">pet-quotes-view</span></td>
                    <td>Provides view-only access to pet insurance quotes.</td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <div class="category-section">
        <h2>Pet Insurance Lead Management</h2>
        <table>
            <thead>
                <tr>
                    <th>Permission Value</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><span class="permission-value">pet-leadpool</span></td>
                    <td>Allows users to access and manage the pet insurance lead pool.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">pet-lead-allocation-dashboard</span></td>
                    <td>Allows users to access the pet insurance lead allocation dashboard.</td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <div class="category-section">
        <h2>Pet Insurance Reports & Dashboard</h2>
        <table>
            <thead>
                <tr>
                    <th>Permission Value</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><span class="permission-value">pet-comprehensive-dashboard</span></td>
                    <td>Allows users to access the comprehensive pet insurance dashboard.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">pet-conversion-report</span></td>
                    <td>Provides access to pet insurance conversion reports.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">pet-distribution-report</span></td>
                    <td>Provides access to pet insurance distribution reports.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">pet-as-at-report-manager</span></td>
                    <td>Provides access to pet insurance as-at reporting for managers.</td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <footer>
        <p>IMCRM Permissions Documentation © 2025</p>
    </footer>
</body>
</html> 