<?php
// Group medical insurance Permissions
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Group Medical Insurance Permissions - IMCRM</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <a href="index.php" class="back-link">← Back to Categories</a>
    
    <h1>Group Medical Insurance Permissions</h1>
    
    <div class="description">
        <p>
            This page documents all permissions related to group medical insurance in the IMCRM system. 
            These permissions control various aspects of group medical insurance quote creation, management, 
            policy issuance, and reporting functionalities.
        </p>
    </div>
    
    <div class="category-section">
        <h2>Group Medical Quote Management</h2>
        <table>
            <thead>
                <tr>
                    <th>Permission Value</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><span class="permission-value">gm-quotes-create</span></td>
                    <td>Allows users to create new group medical insurance quotes in the system.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">gm-quotes-edit</span></td>
                    <td>Allows users to edit existing group medical insurance quotes.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">gm-quotes-list</span></td>
                    <td>Allows users to view the list of group medical insurance quotes.</td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <div class="category-section">
        <h2>Group Medical Insurance Lead Management</h2>
        <table>
            <thead>
                <tr>
                    <th>Permission Value</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><span class="permission-value">group-medical-leadpool</span></td>
                    <td>Allows users to access and manage the group medical insurance lead pool.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">group-medical-lead-allocation-dashboard</span></td>
                    <td>Allows users to access the group medical insurance lead allocation dashboard.</td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <div class="category-section">
        <h2>Group Medical Insurance Reports & Dashboard</h2>
        <table>
            <thead>
                <tr>
                    <th>Permission Value</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><span class="permission-value">groupmedicals-comprehensive-dashboard</span></td>
                    <td>Allows users to access the comprehensive group medical insurance dashboard.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">groupmedicals-conversion-report</span></td>
                    <td>Provides access to group medical insurance conversion reports.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">groupmedicals-distribution-report</span></td>
                    <td>Provides access to group medical insurance distribution reports.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">groupmedicals-as-at-report-manager</span></td>
                    <td>Provides access to group medical insurance as-at reporting for managers.</td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <footer>
        <p>IMCRM Permissions Documentation © 2023</p>
    </footer>
</body>
</html> 