<?php
// Health insurance Permissions
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Health Insurance Permissions - IMCRM</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <a href="index.php" class="back-link">← Back to Categories</a>
    
    <h1>Health Insurance Permissions</h1>
    
    <div class="description">
        <p>
            This page documents all permissions related to health insurance in the IMCRM system. 
            These permissions control various aspects of health insurance quote creation, management, 
            policy issuance, and reporting functionalities.
        </p>
    </div>
    
    <div class="category-section">
        <h2>Health Quote Management</h2>
        <table>
            <thead>
                <tr>
                    <th>Permission Value</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><span class="permission-value">health-quotes-create</span></td>
                    <td>Allows users to create new health insurance quotes in the system.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">health-quotes-edit</span></td>
                    <td>Allows users to edit existing health insurance quotes.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">health-quotes-list</span></td>
                    <td>Allows users to view the list of health insurance quotes.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">health-quotes-access</span></td>
                    <td>Provides general access to the health quotes module.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">health-quotes-manager-access</span></td>
                    <td>Provides manager-level access to the health quotes module with additional privileges.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">health-revival-quotes-edit</span></td>
                    <td>Allows users to edit health revival quotes for policy renewals.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">health-revival-quotes-list</span></td>
                    <td>Allows users to view the list of health revival quotes.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">health-revival-quotes-show</span></td>
                    <td>Allows users to view detailed information about health revival quotes.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">add-manual-health-plan</span></td>
                    <td>Allows users to manually add health insurance plans to the system.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">health-cards</span></td>
                    <td>Allows users to access and manage health insurance cards.</td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <div class="category-section">
        <h2>Health Insurance Configuration</h2>
        <table>
            <thead>
                <tr>
                    <th>Permission Value</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><span class="permission-value">upload-health-coverages</span></td>
                    <td>Allows users to upload health coverage details to the system.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">upload-health-rates</span></td>
                    <td>Allows users to upload health insurance rates to the system.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">sic-health-config</span></td>
                    <td>Provides access to SIC (Standard Industry Classification) health configuration.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">manage-health</span></td>
                    <td>Provides comprehensive management access to the health insurance module.</td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <div class="category-section">
        <h2>Health Insurance Dashboard & Reports</h2>
        <table>
            <thead>
                <tr>
                    <th>Permission Value</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><span class="permission-value">health-comprehensive-dashboard</span></td>
                    <td>Allows users to access the comprehensive health insurance dashboard.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">health-lead-allocation-dashboard</span></td>
                    <td>Allows users to access the health lead allocation dashboard.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">health-conversion-report</span></td>
                    <td>Provides access to health insurance conversion reports.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">health-distribution-report</span></td>
                    <td>Provides access to health insurance distribution reports.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">health-as-at-report-manager</span></td>
                    <td>Provides access to health insurance as-at reporting for managers.</td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <footer>
        <p>IMCRM Permissions Documentation © 2023</p>
    </footer>
</body>
</html> 