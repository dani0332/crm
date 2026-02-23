<?php
// Reports dashboard Permissions
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Dashboard Permissions - IMCRM</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <a href="index.php" class="back-link">← Back to Categories</a>
    
    <h1>Reports & Dashboard Permissions</h1>
    
    <div class="description">
        <p>
            This page documents all permissions related to reports and dashboards in the IMCRM system. 
            These permissions control access to various types of reports, data extraction capabilities, 
            management reporting, and dashboard views across the platform.
        </p>
    </div>
    
    <div class="category-section">
        <h2>Dashboard Access</h2>
        <table>
            <thead>
                <tr>
                    <th>Permission Value</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><span class="permission-value">dashboard-view</span></td>
                    <td>Allows users to access standard dashboard views.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">main-dashboard-view</span></td>
                    <td>Allows users to access the main system dashboard.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">comprehensive-dashboard-view</span></td>
                    <td>Allows users to access comprehensive dashboard views with detailed metrics.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">tpl-dashboard-view</span></td>
                    <td>Allows users to access the TPL (Third Party Liability) dashboard view.</td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <div class="category-section">
        <h2>Report Access</h2>
        <table>
            <thead>
                <tr>
                    <th>Permission Value</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><span class="permission-value">view-all-reports</span></td>
                    <td>Allows users to view all available reports in the system.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">access-report-sm</span></td>
                    <td>Allows users to access sales manager reports.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">advisor-conversion-report-view</span></td>
                    <td>Allows users to view advisor conversion reports.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">advisor-distribution-report-view</span></td>
                    <td>Allows users to view advisor distribution reports.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">advisor-performance-report-view</span></td>
                    <td>Allows users to view advisor performance reports.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">advisor-retention-report-view</span></td>
                    <td>Allows users to view advisor retention reports.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">conversion-as-at-report</span></td>
                    <td>Allows users to view conversion as-at reports.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">extract-report</span></td>
                    <td>Allows users to extract data for custom reporting.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">management-report</span></td>
                    <td>Allows users to access management-level reports.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">manager-retention-report-view</span></td>
                    <td>Allows users to view manager retention reports.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">pipeline-report-view</span></td>
                    <td>Allows users to view pipeline reports for sales forecasting.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">revival-conversion-report-view</span></td>
                    <td>Allows users to view revival conversion reports.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">stale-leads-report-view</span></td>
                    <td>Allows users to view reports on stale or inactive leads.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">total-premium-leads-sales-report</span></td>
                    <td>Allows users to view total premium leads and sales reports.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">utm-leads-sales-report</span></td>
                    <td>Allows users to view reports on leads and sales by UTM parameters.</td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <div class="category-section">
        <h2>Data Exports & Processing</h2>
        <table>
            <thead>
                <tr>
                    <th>Permission Value</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><span class="permission-value">export-plan-detail</span></td>
                    <td>Allows users to export plan details for reporting.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">auditable</span></td>
                    <td>Makes user actions auditable for reporting purposes.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">quote-sync-logs</span></td>
                    <td>Allows users to view quote synchronization logs for reporting.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">api-logs-view</span></td>
                    <td>Allows users to view API logs for system integration reporting.</td>
                </tr>
               
            </tbody>
        </table>
    </div>
    
    <footer>
        <p>IMCRM Permissions Documentation © 2025</p>
    </footer>
</body>
</html> 