<?php
// Travel insurance Permissions
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Travel Insurance Permissions - IMCRM</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <a href="index.php" class="back-link">← Back to Categories</a>
    
    <h1>Travel Insurance Permissions</h1>
    
    <div class="description">
        <p>
            This page documents all permissions related to travel insurance in the IMCRM system. 
            These permissions control various aspects of travel insurance quote creation, management, 
            policy issuance, and reporting functionalities.
        </p>
    </div>
    
    <div class="category-section">
        <h2>Travel Quote Management</h2>
        <table>
            <thead>
                <tr>
                    <th>Permission Value</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><span class="permission-value">travel-quotes-create</span></td>
                    <td>Allows users to create new travel insurance quotes in the system.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">travel-quotes-edit</span></td>
                    <td>Allows users to edit existing travel insurance quotes.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">travel-quotes-list</span></td>
                    <td>Allows users to view the list of travel insurance quotes.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">travel-quotes-show</span></td>
                    <td>Allows users to view detailed information about travel insurance quotes.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">travel-quotes-policy-detail-edit</span></td>
                    <td>Allows users to edit policy details for travel insurance quotes.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">travel-hapex</span></td>
                    <td>Provides access to travel HAPEX (High-value Automated Policy Exchange) functionality.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">travel-sic-allocation</span></td>
                    <td>Allows users to manage SIC (Standard Industry Classification) allocations for travel insurance.</td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <div class="category-section">
        <h2>Travel Insurance Configuration</h2>
        <table>
            <thead>
                <tr>
                    <th>Permission Value</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><span class="permission-value">manage-travel</span></td>
                    <td>Provides comprehensive management access to the travel insurance module.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">transapp-create</span></td>
                    <td>Allows users to create new travel application records.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">transapp-edit</span></td>
                    <td>Allows users to edit existing travel application records.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">transapp-delete</span></td>
                    <td>Allows users to delete travel application records.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">transapp-list</span></td>
                    <td>Allows users to view the list of travel application records.</td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <div class="category-section">
        <h2>Travel Insurance Dashboard & Reports</h2>
        <table>
            <thead>
                <tr>
                    <th>Permission Value</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><span class="permission-value">dashboard-travel</span></td>
                    <td>Allows users to access the travel insurance dashboard.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">travel-comprehensive-dashboard</span></td>
                    <td>Allows users to access the comprehensive travel insurance dashboard.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">travel-conversion-report</span></td>
                    <td>Provides access to travel insurance conversion reports.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">travel-distribution-report</span></td>
                    <td>Provides access to travel insurance distribution reports.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">travel-as-at-report-manager</span></td>
                    <td>Provides access to travel insurance as-at reporting for managers.</td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <footer>
        <p>IMCRM Permissions Documentation © 2023</p>
    </footer>
</body>
</html> 