<?php
// Lead management Permissions
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lead Management Permissions - IMCRM</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <a href="index.php" class="back-link">← Back to Categories</a>
    
    <h1>Lead Management Permissions</h1>
    
    <div class="description">
        <p>
            This page documents all permissions related to lead management in the IMCRM system. 
            These permissions control various aspects of lead handling, assignment, allocation, 
            and status management across all lines of business.
        </p>
    </div>
    
    <div class="category-section">
        <h2>Lead Administration</h2>
        <table>
            <thead>
                <tr>
                    <th>Permission Value</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><span class="permission-value">assign-paid-leads</span></td>
                    <td>Allows users to assign paid leads to team members or advisors.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">buy-leads</span></td>
                    <td>Allows users to purchase or acquire new leads in the system.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">lead-allocation-view</span></td>
                    <td>Allows users to view lead allocation information.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">lead-card-search</span></td>
                    <td>Allows users to search for leads using the card interface.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">lead-distribution-report-view</span></td>
                    <td>Allows users to view lead distribution reports.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">manual-lead-assignment-QA</span></td>
                    <td>Allows quality assurance users to manually assign leads.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">search-all-lead-lob</span></td>
                    <td>Allows users to search leads across all lines of business.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">view-all-leads</span></td>
                    <td>Allows users to view all leads in the system regardless of assignment.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">super-lead-status-change</span></td>
                    <td>Allows privileged users to change lead status with special permissions.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">team-allocation-threshold-view</span></td>
                    <td>Allows users to view team allocation thresholds for leads.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">team-threshold-view</span></td>
                    <td>Allows users to view team thresholds for lead allocation.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">update-lead-status-to-fake-duplicate</span></td>
                    <td>Allows users to mark leads as fake or duplicate.</td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <div class="category-section">
        <h2>Lead Data & Exports</h2>
        <table>
            <thead>
                <tr>
                    <th>Permission Value</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><span class="permission-value">data-extraction</span></td>
                    <td>Allows users to extract lead data from the system.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">data-extraction-search-all-leads</span></td>
                    <td>Allows users to search all leads for data extraction purposes.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">export-leads-detail-with-email-mobile</span></td>
                    <td>Allows users to export lead details including email and mobile information.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">export-no-contactinfo</span></td>
                    <td>Allows users to export leads that have no contact information.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">export-rm-leads</span></td>
                    <td>Allows users to export relationship manager leads.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">segment-filter</span></td>
                    <td>Allows users to filter leads by segment.</td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <div class="category-section">
        <h2>Telemarketing Management</h2>
        <table>
            <thead>
                <tr>
                    <th>Permission Value</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><span class="permission-value">telemarketing-create</span></td>
                    <td>Allows users to create new telemarketing records.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">telemarketing-delete</span></td>
                    <td>Allows users to delete telemarketing records.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">telemarketing-edit</span></td>
                    <td>Allows users to edit telemarketing records.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">telemarketing-list</span></td>
                    <td>Allows users to view the list of telemarketing records.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">tm-call-status-create</span></td>
                    <td>Allows users to create new telemarketing call status options.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">tm-call-status-delete</span></td>
                    <td>Allows users to delete telemarketing call status options.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">tm-call-status-edit</span></td>
                    <td>Allows users to edit telemarketing call status options.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">tm-call-status-list</span></td>
                    <td>Allows users to view the list of telemarketing call status options.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">tm-lead-status-create</span></td>
                    <td>Allows users to create new telemarketing lead status options.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">tm-lead-status-delete</span></td>
                    <td>Allows users to delete telemarketing lead status options.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">tm-lead-status-edit</span></td>
                    <td>Allows users to edit telemarketing lead status options.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">tm-lead-status-list</span></td>
                    <td>Allows users to view the list of telemarketing lead status options.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">tm-insurance-type-create</span></td>
                    <td>Allows users to create new telemarketing insurance type options.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">tm-insurance-type-delete</span></td>
                    <td>Allows users to delete telemarketing insurance type options.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">tm-insurance-type-edit</span></td>
                    <td>Allows users to edit telemarketing insurance type options.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">tm-insurance-type-list</span></td>
                    <td>Allows users to view the list of telemarketing insurance type options.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">tm-upload-leads-create</span></td>
                    <td>Allows users to upload new leads for telemarketing.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">tm-upload-leads-delete</span></td>
                    <td>Allows users to delete telemarketing lead uploads.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">tm-upload-leads-edit</span></td>
                    <td>Allows users to edit telemarketing lead uploads.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">tm-upload-leads-list</span></td>
                    <td>Allows users to view the list of telemarketing lead uploads.</td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <footer>
        <p>IMCRM Permissions Documentation © 2023</p>
    </footer>
</body>
</html> 