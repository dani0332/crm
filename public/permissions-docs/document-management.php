<?php
// Document management Permissions
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document Management Permissions - IMCRM</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <a href="index.php" class="back-link">← Back to Categories</a>
    
    <h1>Document Management Permissions</h1>
    
    <div class="description">
        <p>
            This page documents all permissions related to document management in the IMCRM system. 
            These permissions control various aspects of document handling, verification, storage, 
            and management throughout the system.
        </p>
    </div>
    
    <div class="category-section">
        <h2>Document Operations</h2>
        <table>
            <thead>
                <tr>
                    <th>Permission Value</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><span class="permission-value">document-delete</span></td>
                    <td>Allows users to delete documents from the system.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">document-verify</span></td>
                    <td>Allows users to verify and approve documents in the system.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">download-all-documents</span></td>
                    <td>Allows users to download all documents associated with a record.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">auditdocument-upload</span></td>
                    <td>Allows users to upload documents for audit purposes.</td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <div class="category-section">
        <h2>Application Storage</h2>
        <table>
            <thead>
                <tr>
                    <th>Permission Value</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><span class="permission-value">application-storage-create</span></td>
                    <td>Allows users to create new application storage entries.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">application-storage-edit</span></td>
                    <td>Allows users to edit existing application storage entries.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">application-storage-list</span></td>
                    <td>Allows users to view the list of application storage entries.</td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <div class="category-section">
        <h2>Policy & Quote Documents</h2>
        <table>
            <thead>
                <tr>
                    <th>Permission Value</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><span class="permission-value">send-policy-to-customer-button</span></td>
                    <td>Allows users to send policy documents to customers.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">send-update-to-customer-button</span></td>
                    <td>Allows users to send policy updates to customers.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">send-update-create</span></td>
                    <td>Allows users to create policy update documents.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">send-update-edit-notes</span></td>
                    <td>Allows users to edit notes on policy update documents.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">send-update-correct-policy-upload-add</span></td>
                    <td>Allows users to upload corrected policy documents.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">quote-raw-data</span></td>
                    <td>Allows users to access raw data for quote documents.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">send-and-book-policy-button</span></td>
                    <td>Allows users to send and book policies.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">send-and-book-update-button</span></td>
                    <td>Allows users to send and book policy updates.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">send-update-add-booking</span></td>
                    <td>Allows users to add booking information to updates.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">send-update-cancel-from-inception-add</span></td>
                    <td>Allows users to add cancellation from inception to updates.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">send-update-cancel-from-inception-and-reissue-add</span></td>
                    <td>Allows users to add cancellation from inception and reissue to updates.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">send-update-correct-policy-details-add</span></td>
                    <td>Allows users to add corrected policy details to updates.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">send-update-endo-fin-add</span></td>
                    <td>Allows users to add financial endorsements to updates.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">send-update-endo-non-fin-add</span></td>
                    <td>Allows users to add non-financial endorsements to updates.</td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <div class="category-section">
        <h2>Anti-Money Laundering (AML) Documents</h2>
        <table>
            <thead>
                <tr>
                    <th>Permission Value</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><span class="permission-value">aml-audit</span></td>
                    <td>Allows users to audit AML compliance documents.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">aml-decision-update</span></td>
                    <td>Allows users to update AML decisions based on document verification.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">aml-decision-update-true-match</span></td>
                    <td>Allows users to update AML true match decisions based on document verification.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">aml-list</span></td>
                    <td>Allows users to view the list of AML documents and records.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">skip-bridger-aml</span></td>
                    <td>Allows users to skip the Bridger AML document verification process.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">customer-riskrrating-override</span></td>
                    <td>Allows users to override customer risk ratings in AML documentation.</td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <footer>
        <p>IMCRM Permissions Documentation © 2025</p>
    </footer>
</body>
</html> 