<?php
// Claims management Permissions
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Claims Management Permissions - IMCRM</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <a href="index.php" class="back-link">← Back to Categories</a>
    
    <h1>Claims Management Permissions</h1>
    
    <div class="description">
        <p>
            This page documents all permissions related to the new Claims Management module in the IMCRM system. 
            These permissions control access to claim creation, viewing, editing, status updates, document management, 
            and data export capabilities. The Claims Management module handles insurance claim requests across all 
            Lines of Business (LOB) including Car, Health, Life, Travel, and other insurance types.
        </p>
    </div>
    
    <div class="category-section roles-section">
        <h2>🎭 Claims Management Roles</h2>
        <p class="roles-intro">
            The Claims Management module has dedicated roles with comprehensive access to all claims-related operations. 
            These roles are designed to support the complete claims workflow from creation to closure.
        </p>
        
        <div class="role-cards">
            <div class="role-card manager-role">
                <div class="role-header">
                    <h3>Claims Manager</h3>
                    <span class="role-badge">CLAIMS_MANAGER</span>
                </div>
                <div class="role-description">
                    <p>The Claims Manager role handles daily claims-related tasks and operations. This role is assigned to advisors and managers who process and manage insurance claims on a day-to-day basis.</p>
                </div>
                <div class="role-permissions">
                    <h4>Full Access To:</h4>
                    <ul>
                        <li>✅ View all claims across all LOBs</li>
                        <li>✅ Create new claims (manual and CP-API integration)</li>
                        <li>✅ Edit and update claim details</li>
                        <li>✅ Manage claim statuses and sub-statuses</li>
                        <li>✅ Upload and delete claim documents</li>
                        <li>✅ Access secure document URLs</li>
                        <li>✅ Export claims data for reporting</li>
                        <li>✅ Download all claim documents</li>
                    </ul>
                </div>
                <div class="role-use-cases">
                    <h4>Typical Responsibilities:</h4>
                    <ul>
                        <li>Process and handle assigned claims daily</li>
                    </ul>
                </div>
            </div>
            
            <div class="role-card lead-role">
                <div class="role-header">
                    <h3>Claims Lead</h3>
                    <span class="role-badge">CLAIMS_LEAD</span>
                </div>
                <div class="role-description">
                    <p>The Claims Lead role oversees the entire claims management operations and department. This role is assigned to team leads and senior managers who supervise the claims team and ensure smooth workflow.</p>
                </div>
                <div class="role-permissions">
                    <h4>Full Access To:</h4>
                    <ul>
                        <li>✅ View all claims across all LOBs</li>
                        <li>✅ Create new claims (manual and CP-API integration)</li>
                        <li>✅ Edit and update claim details</li>
                        <li>✅ Manage claim statuses and sub-statuses</li>
                        <li>✅ Upload and delete claim documents</li>
                        <li>✅ Access secure document URLs</li>
                        <li>✅ Export claims data for reporting</li>
                        <li>✅ Download all claim documents</li>
                    </ul>
                </div>
                <div class="role-use-cases">
                    <h4>Typical Responsibilities:</h4>
                    <ul>
                        <li>Oversee and supervise daily claims operations</li>
                        <li>Support team members with complex cases</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <div class="category-section">
        <h2>Core Claim Operations</h2>
        <table>
            <thead>
                <tr>
                    <th>Permission Value</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><span class="permission-value">claim-list</span></td>
                    <td>Allows users to view and access the claims listing page. Grants access to view all claims with filtering and search capabilities. Required for accessing the claims index page.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">claim-create</span></td>
                    <td>Allows users to create new insurance claim requests in the system. Grants access to the claim creation form and ability to submit new claims to insurance providers via CP-API integration or manual entry.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">claim-edit</span></td>
                    <td>Allows users to edit existing insurance claims. Grants access to update claim details, customer information, incident details, and LOB-specific fields (car details, health service types, etc.).</td>
                </tr>
                <tr>
                    <td><span class="permission-value">claim-show</span></td>
                    <td>Allows users to view individual claim details. Grants access to the claim details page showing all claim information, customer details, documents, activities, and history logs.</td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <div class="category-section">
        <h2>Claim Status Management</h2>
        <table>
            <thead>
                <tr>
                    <th>Permission Value</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><span class="permission-value">claim-status-update</span></td>
                    <td>Allows users to update the main claim status (Open/Closed). This is typically used by team leads for high-level claim status management. Updates are logged in the claim activity history.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">claim-sub-status-update</span></td>
                    <td>Allows users to update claim sub-statuses and send customer notifications. Grants access to the sub-status update component with AI-powered message optimization. Sub-status changes trigger automatic claim closure logic based on LOB-specific rules.</td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <div class="category-section">
        <h2>Document Management</h2>
        <table>
            <thead>
                <tr>
                    <th>Permission Value</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><span class="permission-value">claim-document-upload</span></td>
                    <td>Allows users to upload documents to claims. Grants access to upload claim-related documents such as incident photos, damage reports, medical records, police reports, and other supporting documents via S3 integration.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">claim-document-delete</span></td>
                    <td>Allows users to delete documents from claims. Grants permission to remove uploaded documents from the claim with proper validation and audit logging.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">claim-document-s3-url</span></td>
                    <td>Allows users to generate and access temporary S3 URLs for secure document viewing. Required for viewing claim documents stored in S3 with time-limited access links.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">claim-download-all-documents</span></td>
                    <td>Allows users to download all claim documents as a single ZIP file. Useful for bulk document retrieval and claim package preparation.</td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <div class="category-section">
        <h2>Data Export & Reporting</h2>
        <table>
            <thead>
                <tr>
                    <th>Permission Value</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><span class="permission-value">claim-export-data</span></td>
                    <td>Allows users to export claim data to Excel/CSV format. Grants access to export filtered claims with comprehensive data including customer information, claim details, status history, and custom field selections.</td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <div class="category-section">
        <h2>Claims Module Features</h2>
        <div class="feature-grid">
            <div class="feature-card">
                <h3>LOB-Specific Validation</h3>
                <p>Claims support different validation rules based on Line of Business (Car, Health, Life, etc.) with conditional field requirements.</p>
            </div>
            <div class="feature-card">
                <h3>AI-Powered Notifications</h3>
                <p>Customer notification messages can be optimized using AI for better communication and professional tone.</p>
            </div>
            <div class="feature-card">
                <h3>Automatic Status Management</h3>
                <p>Claims automatically transition between statuses based on business rules, approval amounts, and completion criteria.</p>
            </div>
            <div class="feature-card">
                <h3>CP-API Integration</h3>
                <p>Direct claim creation integration with CP-API for submitting new insurance claims to the system.</p>
            </div>
            <div class="feature-card">
                <h3>Comprehensive Audit Trail</h3>
                <p>All claim actions, status changes, and updates are logged with complete audit history for compliance.</p>
            </div>
            <div class="feature-card">
                <h3>Document Security</h3>
                <p>Secure S3-based document storage with temporary URL generation for time-limited access.</p>
            </div>
        </div>
    </div>
    
    <style>
        .section-note {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 12px;
            margin: 15px 0;
            border-radius: 4px;
        }
        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .feature-card {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
        }
        .feature-card h3 {
            color: #0056b3;
            margin-top: 0;
            font-size: 1.1em;
        }
        .feature-card p {
            margin: 10px 0 0 0;
            font-size: 0.9em;
            color: #666;
        }
        
        /* Roles Section Styling */
        .roles-section {
            background: linear-gradient(to bottom, #f8f9fa 0%, #ffffff 100%);
            border: 2px solid #3498db;
            border-radius: 12px;
            padding: 25px;
            margin: 30px 0;
        }
        
        .roles-intro {
            background: #e7f3ff;
            border-left: 4px solid #3498db;
            padding: 15px;
            margin: 15px 0 25px 0;
            border-radius: 4px;
            font-size: 1em;
            line-height: 1.6;
        }
        
        .role-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
            gap: 25px;
            margin: 25px 0;
        }
        
        .role-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border-top: 5px solid #3498db;
        }
        
        .role-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.15);
        }
        
        .role-card.manager-role {
            border-top-color: #e74c3c;
        }
        
        .role-card.lead-role {
            border-top-color: #27ae60;
        }
        
        .role-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 2px solid #ecf0f1;
        }
        
        .role-header h3 {
            margin: 0;
            color: #2c3e50;
            font-size: 1.4em;
        }
        
        .role-badge {
            background: #34495e;
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.75em;
            font-weight: 600;
            font-family: 'Courier New', monospace;
            letter-spacing: 0.5px;
        }
        
        .manager-role .role-badge {
            background: #e74c3c;
        }
        
        .lead-role .role-badge {
            background: #27ae60;
        }
        
        .role-description {
            margin: 15px 0;
            padding: 12px;
            background: #f8f9fa;
            border-radius: 6px;
            font-size: 0.95em;
            line-height: 1.6;
            color: #555;
        }
        
        .role-permissions, .role-use-cases {
            margin: 20px 0;
        }
        
        .role-permissions h4, .role-use-cases h4 {
            color: #2c3e50;
            margin-bottom: 12px;
            font-size: 1.05em;
            border-bottom: 2px solid #ecf0f1;
            padding-bottom: 8px;
        }
        
        .role-permissions ul, .role-use-cases ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .role-permissions li, .role-use-cases li {
            padding: 8px 0 8px 25px;
            position: relative;
            font-size: 0.9em;
            line-height: 1.5;
        }
        
        .role-permissions li:before {
            position: absolute;
            left: 0;
        }
        
        .role-use-cases li:before {
            content: "▸";
            position: absolute;
            left: 8px;
            color: #3498db;
            font-weight: bold;
        }
        
        .admin-note {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-top: 25px;
        }
        
        .admin-note h4 {
            margin-top: 0;
            color: white;
            font-size: 1.2em;
        }
        
        .admin-note p {
            margin: 10px 0 0 0;
            line-height: 1.6;
        }
        
        .admin-note strong {
            background: rgba(255,255,255,0.2);
            padding: 2px 8px;
            border-radius: 4px;
            font-weight: 600;
        }
        
        @media (max-width: 968px) {
            .role-cards {
                grid-template-columns: 1fr;
            }
            
            .role-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
        }
    </style>
    
    <footer>
        <p>IMCRM Permissions Documentation © 2025</p>
    </footer>
</body>
</html> 