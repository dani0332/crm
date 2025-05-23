<?php
// User management Permissions
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management Permissions - IMCRM</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <a href="index.php" class="back-link">← Back to Categories</a>
    
    <h1>User Management Permissions</h1>
    
    <div class="description">
        <p>
            This page documents all permissions related to user management in the IMCRM system. 
            These permissions control access to user creation, editing, role management, 
            and related system administration functions.
        </p>
    </div>
    
    <div class="category-section">
        <h2>User Administration</h2>
        <table>
            <thead>
                <tr>
                    <th>Permission Value</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><span class="permission-value">users-create</span></td>
                    <td>Allows administrators to create new user accounts in the system.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">users-edit</span></td>
                    <td>Allows administrators to edit existing user accounts.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">users-delete</span></td>
                    <td>Allows administrators to delete user accounts.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">users-list</span></td>
                    <td>Allows viewing the list of all users in the system.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">enable-impersonation</span></td>
                    <td>Allows administrators to impersonate other users for troubleshooting purposes.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">manage-user</span></td>
                    <td>Provides comprehensive access to user management functionality.</td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <div class="category-section">
        <h2>Role Management</h2>
        <table>
            <thead>
                <tr>
                    <th>Permission Value</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><span class="permission-value">role-create</span></td>
                    <td>Allows administrators to create new roles in the system.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">role-edit</span></td>
                    <td>Allows administrators to edit existing roles.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">role-delete</span></td>
                    <td>Allows administrators to delete roles.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">role-list</span></td>
                    <td>Allows viewing the list of all roles in the system.</td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <div class="category-section">
        <h2>Team & Department Management</h2>
        <table>
            <thead>
                <tr>
                    <th>Permission Value</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><span class="permission-value">department-create</span></td>
                    <td>Allows administrators to create new departments in the system.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">department-update</span></td>
                    <td>Allows administrators to update existing departments.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">department-list</span></td>
                    <td>Allows viewing the list of all departments in the system.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">department-manager</span></td>
                    <td>Provides department manager level access with additional privileges.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">teams-list</span></td>
                    <td>Allows viewing the list of all teams in the system.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">team-threshold-view</span></td>
                    <td>Allows viewing team threshold settings.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">team-allocation-threshold-view</span></td>
                    <td>Allows viewing team allocation threshold settings.</td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <div class="category-section">
        <h2>Partner Management</h2>
        <table>
            <thead>
                <tr>
                    <th>Permission Value</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><span class="permission-value">partners-create</span></td>
                    <td>Allows creation of new partner entities in the system.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">partners-edit</span></td>
                    <td>Allows editing of existing partner information.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">partners-delete</span></td>
                    <td>Allows deletion of partner entries from the system.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">partners-list</span></td>
                    <td>Allows viewing the list of all partners in the system.</td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <div class="category-section">
        <h2>System Administration</h2>
        <table>
            <thead>
                <tr>
                    <th>Permission Value</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><span class="permission-value">crm-admin</span></td>
                    <td>Provides comprehensive admin access to the CRM system.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">receive-notifications</span></td>
                    <td>Allows users to receive system notifications.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">tap-beta-access</span></td>
                    <td>Grants access to beta features in the TAP module.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">customers-list</span></td>
                    <td>Allows viewing the list of all customers in the system.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">customers-show</span></td>
                    <td>Allows viewing detailed customer information.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">customers-edit</span></td>
                    <td>Allows editing of customer information.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">customers-upload</span></td>
                    <td>Allows bulk uploading of customer data.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">customer-riskrrating-override</span></td>
                    <td>Allows overriding the risk rating for customers.</td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <footer>
        <p>IMCRM Permissions Documentation © 2025</p>
    </footer>
</body>
</html> 