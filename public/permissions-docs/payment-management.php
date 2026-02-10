<?php
// Payment management Permissions
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Management Permissions - IMCRM</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <a href="index.php" class="back-link">← Back to Categories</a>
    
    <h1>Payment Management Permissions</h1>
    
    <div class="description">
        <p>
            This page documents all permissions related to payment management in the IMCRM system. 
            These permissions control various aspects of payment processing, approvals, discounts, 
            and related financial operations.
        </p>
    </div>
    
    <div class="category-section">
        <h2>Payment Operations</h2>
        <table>
            <thead>
                <tr>
                    <th>Permission Value</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><span class="permission-value">payment-create</span></td>
                    <td>Allows users to create new payment records in the system.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">payment-edit</span></td>
                    <td>Allows users to edit existing payment records.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">payment-list</span></td>
                    <td>Allows users to view the list of payment records.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">approve-payments</span></td>
                    <td>Allows users to approve pending payments.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">reapprove-payment</span></td>
                    <td>Allows users to reapprove payments that require additional verification.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">payments-void</span></td>
                    <td>Allows users to void or cancel existing payments.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">can-post-premium-prepayment</span></td>
                    <td>Allows users to post premium prepayments.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">manager-authorised-payment-summary</span></td>
                    <td>Allows managers to view authorized payment summaries.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">manage-payment-detail</span></td>
                    <td>Provides comprehensive access to payment detail management.</td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <div class="category-section">
        <h2>Payment Modes & Channels</h2>
        <table>
            <thead>
                <tr>
                    <th>Permission Value</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><span class="permission-value">payment-mode-create</span></td>
                    <td>Allows users to create new payment modes in the system.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">payment-mode-edit</span></td>
                    <td>Allows users to edit existing payment modes.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">payment-mode-delete</span></td>
                    <td>Allows users to delete payment modes.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">payment-mode-list</span></td>
                    <td>Allows users to view the list of payment modes.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">insurer-payment-link</span></td>
                    <td>Allows users to access and manage insurer payment links.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">manage-insurer-payment-channel</span></td>
                    <td>Provides access to manage insurer payment channels.</td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <div class="category-section">
        <h2>Payment Verifications & Collections</h2>
        <table>
            <thead>
                <tr>
                    <th>Permission Value</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><span class="permission-value">payment-verification-collected-by-broker</span></td>
                    <td>Allows verification of payments collected by brokers.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">payment-verification-collected-by-insurer</span></td>
                    <td>Allows verification of payments collected by insurers.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">payments-frequency-terms-collected-by-broker-add</span></td>
                    <td>Allows adding payment frequency terms for broker-collected payments.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">payments-frequency-terms-collected-by-insurer-add</span></td>
                    <td>Allows adding payment frequency terms for insurer-collected payments.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">payments-frequency-upfront-split-collected-by-broker-add</span></td>
                    <td>Allows adding upfront split payment options for broker-collected payments.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">search-insurer-tax-invoice-number</span></td>
                    <td>Allows searching for insurer tax invoice numbers.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">search-insurer-commission-tax-invoice-number</span></td>
                    <td>Allows searching for insurer commission tax invoice numbers.</td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <div class="category-section">
        <h2>Discounts & Financial Adjustments</h2>
        <table>
            <thead>
                <tr>
                    <th>Permission Value</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><span class="permission-value">discount-list</span></td>
                    <td>Allows users to view the list of available discounts.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">discount-management</span></td>
                    <td>Provides comprehensive access to discount management features.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">payments-discount-add</span></td>
                    <td>Allows users to add discounts to payments.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">payments-discount-edit</span></td>
                    <td>Allows users to edit discounts applied to payments.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">age-discount-create</span></td>
                    <td>Allows users to create age-based discount rules.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">age-discount-edit</span></td>
                    <td>Allows users to edit age-based discount rules.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">age-discount-delete</span></td>
                    <td>Allows users to delete age-based discount rules.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">age-discount-list</span></td>
                    <td>Allows users to view the list of age-based discount rules.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">payments-credit-approval-add</span></td>
                    <td>Allows users to add credit approvals for payments.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">temp-update-totalprice</span></td>
                    <td>Allows temporary updates to total price figures.</td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <div class="category-section">
        <h2>Proforma & Policy Details</h2>
        <table>
            <thead>
                <tr>
                    <th>Permission Value</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><span class="permission-value">proforma-create</span></td>
                    <td>Allows users to create and download proforma payment request PDFs. This includes generating proforma invoices for both main leads and policy endorsements (send updates), with automatic version tracking.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">proforma-payment-request-add</span></td>
                    <td>Allows users to add "Proforma Payment Request" (PPR) as a payment method option in the payment dropdown. This enables creating payment records with upfront frequency and single split payment structure.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">plan-details-add</span></td>
                    <td>Allows users to add insurance plan details.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">policy-details-add</span></td>
                    <td>Allows users to add insurance policy details.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">policy-details-add-vat</span></td>
                    <td>Allows users to add VAT information to policy details.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">legacy-installments</span></td>
                    <td>Allows access to legacy installment payment records.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">legacy-invoices</span></td>
                    <td>Allows access to legacy invoice records.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">legacy-payments</span></td>
                    <td>Allows access to legacy payment records.</td>
                </tr>
                <tr>
                    <td><span class="permission-value">view-legacy-details</span></td>
                    <td>Allows viewing legacy payment and policy details.</td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <footer>
        <p>IMCRM Permissions Documentation © 2025</p>
    </footer>
</body>
</html> 