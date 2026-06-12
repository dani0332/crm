<?php
// Admin & Configuration Permissions
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin & Configuration Permissions - IMCRM</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <a href="index.php" class="back-link">← Back to Categories</a>
    
    <h1>Admin & Configuration Permissions</h1>
    
    <div class="description">
        <p>
            This page documents all permissions related to system administration and configuration in the IMCRM system. 
            These permissions control various aspects of system setup, administration, and configurations 
            that affect the entire platform.
        </p>
    </div>
    
    <div class="category-section">
        <h2>System Configuration</h2>
        <table>
            <thead>
                <tr>
                    <th>Permission Value</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><span class="permission-value">rule-config-list</span></td>
                    <td>Allows viewing the list of rule configurations.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">rule-config-create</span></td>
                    <td>Allows creating new rule configurations (allocation rules).</td>
                </tr>
                <tr>
                    <td><span class="permission-value">rule-config-update</span></td>
                    <td>Allows editing existing rule configurations (allocation rules).</td>
                </tr>
                <tr>
                    <td><span class="permission-value">quad-config-list</span></td>
                    <td>Allows viewing the list of quad configurations.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">tier-config-list</span></td>
                    <td>Allows viewing the list of tier configurations.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">embedded-product-config</span></td>
                    <td>Allows configuration of embedded product settings.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">embedded-product-view</span></td>
                    <td>Allows viewing embedded product configurations.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">embedded-product-payment-cancel</span></td>
                    <td>Allows cancellation of embedded product payments.</td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <div class="category-section">
        <h2>Insurance Types & Providers</h2>
        <table>
            <thead>
                <tr>
                    <th>Permission Value</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><span class="permission-value">type-of-insurance-create</span></td>
                    <td>Allows creation of new insurance types.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">type-of-insurance-edit</span></td>
                    <td>Allows editing of existing insurance types.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">type-of-insurance-delete</span></td>
                    <td>Allows deletion of insurance types.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">type-of-insurance-list</span></td>
                    <td>Allows viewing the list of insurance types.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">sub-type-of-insurance-create</span></td>
                    <td>Allows creation of new insurance subtypes.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">sub-type-of-insurance-edit</span></td>
                    <td>Allows editing of existing insurance subtypes.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">sub-type-of-insurance-delete</span></td>
                    <td>Allows deletion of insurance subtypes.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">sub-type-of-insurance-list</span></td>
                    <td>Allows viewing the list of insurance subtypes.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">insurance-company-create</span></td>
                    <td>Allows creation of new insurance company records.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">insurance-company-edit</span></td>
                    <td>Allows editing of existing insurance company records.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">insurance-company-delete</span></td>
                    <td>Allows deletion of insurance company records.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">insurance-company-list</span></td>
                    <td>Allows viewing the list of insurance companies.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">inusrance-provider-create</span></td>
                    <td>Allows creation of new insurance provider records.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">inusrance-provider-edit</span></td>
                    <td>Allows editing of existing insurance provider records.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">inusrance-provider-list</span></td>
                    <td>Allows viewing the list of insurance providers.</td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <div class="category-section">
        <h2>Status & Reason Management</h2>
        <table>
            <thead>
                <tr>
                    <th>Permission Value</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><span class="permission-value">status-create</span></td>
                    <td>Allows creation of new status options in the system.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">status-edit</span></td>
                    <td>Allows editing of existing status options.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">status-delete</span></td>
                    <td>Allows deletion of status options.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">status-list</span></td>
                    <td>Allows viewing the list of status options.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">reason-create</span></td>
                    <td>Allows creation of new reason codes/options in the system.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">reason-edit</span></td>
                    <td>Allows editing of existing reason codes/options.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">reason-delete</span></td>
                    <td>Allows deletion of reason codes/options.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">reason-list</span></td>
                    <td>Allows viewing the list of reason codes/options.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">claims-status-create</span></td>
                    <td>Allows creation of new claims status options.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">claims-status-edit</span></td>
                    <td>Allows editing of existing claims status options.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">claims-status-delete</span></td>
                    <td>Allows deletion of claims status options.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">claims-status-list</span></td>
                    <td>Allows viewing the list of claims status options.</td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <div class="category-section">
        <h2>Activity Management</h2>
        <table>
            <thead>
                <tr>
                    <th>Permission Value</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><span class="permission-value">activities-assigned-to-view</span></td>
                    <td>Allows viewing activities assigned to the user.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">activities-list</span></td>
                    <td>Allows viewing the list of all activities in the system.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">all-quotes-view-only-access</span></td>
                    <td>Provides view-only access to all quotes in the system.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">pause-auto-followups</span></td>
                    <td>Allows pausing automatic follow-up activities.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">cancel-send-update</span></td>
                    <td>Allows cancellation of send update operations.</td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <div class="category-section">
        <h2>Discount & Age Settings</h2>
        <table>
            <thead>
                <tr>
                    <th>Permission Value</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><span class="permission-value">age-discount-create</span></td>
                    <td>Allows creation of new age-based discount rules.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">age-discount-edit</span></td>
                    <td>Allows editing of existing age-based discount rules.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">age-discount-delete</span></td>
                    <td>Allows deletion of age-based discount rules.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">age-discount-list</span></td>
                    <td>Allows viewing the list of age-based discount rules.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">discount-list</span></td>
                    <td>Allows viewing the list of all discount options.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">discount-management</span></td>
                    <td>Provides access to discount management functions.</td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <footer>
        <p>IMCRM Permissions Documentation © 2025</p>
    </footer>
</body>
</html> 