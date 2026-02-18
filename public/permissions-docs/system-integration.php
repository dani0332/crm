<?php
// System integration Permissions
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Integration Permissions - IMCRM</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <a href="index.php" class="back-link">← Back to Categories</a>
    
    <h1>System Integration Permissions</h1>
    
    <div class="description">
        <p>
            This page documents all permissions related to system integrations in the IMCRM system. 
            These permissions control access to various API endpoints, integration features, 
            third-party system connections, and related functionalities.
        </p>
    </div>
    
    <div class="category-section">
        <h2>API Integration</h2>
        <table>
            <thead>
                <tr>
                    <th>Permission Value</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><span class="permission-value">api-logs-view</span></td>
                    <td>Allows users to view API logs for monitoring integrations.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">view-sage-api-logs</span></td>
                    <td>Allows users to view Sage API integration logs.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">car-quotes-resubmit-api</span></td>
                    <td>Allows users to resubmit car quote requests to external APIs.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">quote-sync-logs</span></td>
                    <td>Allows users to view quote synchronization logs.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">quote-raw-data</span></td>
                    <td>Allows users to access raw data for quotes.</td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <div class="category-section">
        <h2>Sage 300 ERP Integration</h2>
        <table>
            <thead>
                <tr>
                    <th>Permission Value</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><span class="permission-value">view-sage-api-logs</span></td>
                    <td>Allows users to view Sage 300 API integration logs for troubleshooting.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">SAGE_PROCESS_ISSUE_MANAGEMENT</span></td>
                    <td>Allows users to view, monitor, and troubleshoot failed Sage 300 booking processes. Provides access to the failed processes management interface with detailed error logs, payment information, and CSV export capabilities.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">sage-booking-create</span></td>
                    <td>Allows users to initiate policy booking to Sage 300 ERP system.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">sage-booking-retry</span></td>
                    <td>Allows users to retry failed Sage 300 booking processes after resolving errors.</td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <div class="category-section">
        <h2>External Systems</h2>
        <table>
            <thead>
                <tr>
                    <th>Permission Value</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
              
                <tr>
                    <td><span class="permission-value">transapp-create</span></td>
                    <td>Allows users to create new transaction applications for external systems.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">transapp-edit</span></td>
                    <td>Allows users to edit existing transaction applications for external systems.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">transapp-delete</span></td>
                    <td>Allows users to delete transaction applications for external systems.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">transapp-list</span></td>
                    <td>Allows users to view the list of transaction applications for external systems.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">migrate-insly-lead</span></td>
                    <td>Allows users to migrate leads from the Insly system.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">view-insly-book-policy</span></td>
                    <td>Allows users to view Insly book policy integration details.</td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <div class="category-section">
        <h2>Chat & Support Integration</h2>
        <table>
            <thead>
                <tr>
                    <th>Permission Value</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><span class="permission-value">instant-alfred-chat-logs</span></td>
                    <td>Allows users to view Instant Alfred chat integration logs.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">tap-beta-access</span></td>
                    <td>Provides access to TAP beta integration features.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">insurer-payment-link</span></td>
                    <td>Allows access to insurer payment link integration.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">search-insurer-commission-tax-invoice-number</span></td>
                    <td>Allows searching for insurer commission tax invoice numbers.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">search-insurer-tax-invoice-number</span></td>
                    <td>Allows searching for insurer tax invoice numbers.</td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <div class="category-section">
        <h2>Travel Integration</h2>
        <table>
            <thead>
                <tr>
                    <th>Permission Value</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><span class="permission-value">travel-sic-allocation</span></td>
                    <td>Allows users to manage travel SIC allocation integration.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">travel-hapex</span></td>
                    <td>Provides access to travel Hapex integration features.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">sic-health-config</span></td>
                    <td>Allows users to configure SIC health integration settings.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">enable-impersonation</span></td>
                    <td>Allows users to impersonate other users for testing and support.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">receive-notifications</span></td>
                    <td>Allows users to receive system notifications.</td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <footer>
        <p>IMCRM Permissions Documentation © 2025</p>
    </footer>
</body>
</html> 