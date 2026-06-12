<?php
// Include necessary PHP code here
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IMCRM Permissions Documentation</title>
    <link rel="stylesheet" href="./styles.css">
    <style>
        .card-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        .card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            background-color: #f9f9f9;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            background-color: #f0f7ff;
        }
        .card h3 {
            margin-top: 0;
        }
    </style>
</head>
<body>
    <h1>IMCRM Permissions Documentation</h1>
    
    <p>
        This documentation provides a comprehensive overview of all permissions in the IMCRM system, 
        categorized by Line of Business or domain areas. Each permission controls specific access or actions 
        within the system.
    </p>

    <h2>Permission Categories</h2>
    
    <div class="card-container">
        <!-- Insurance Lines of Business -->
        <div class="card">
            <h3>Car Insurance</h3>
            <p>Permissions related to car insurance quotes, policies, and management.</p>
            <a href="car-insurance.php">View Permissions</a>
        </div>
        
        <div class="card">
            <h3>Health Insurance</h3>
            <p>Permissions related to health insurance products and services.</p>
            <a href="health-insurance.php">View Permissions</a>
        </div>
        
        <div class="card">
            <h3>Travel Insurance</h3>
            <p>Permissions related to travel insurance quotes and policies.</p>
            <a href="travel-insurance.php">View Permissions</a>
        </div>
        
        <div class="card">
            <h3>Home Insurance</h3>
            <p>Permissions related to home insurance products and services.</p>
            <a href="home-insurance.php">View Permissions</a>
        </div>
        
        <div class="card">
            <h3>Life Insurance</h3>
            <p>Permissions related to life insurance quotes, policies, and management.</p>
            <a href="life-insurance.php">View Permissions</a>
        </div>
        
        <div class="card">
            <h3>Pet Insurance</h3>
            <p>Permissions related to pet insurance products and services.</p>
            <a href="pet-insurance.php">View Permissions</a>
        </div>
        
        <div class="card">
            <h3>Bike Insurance</h3>
            <p>Permissions related to motorcycle insurance.</p>
            <a href="bike-insurance.php">View Permissions</a>
        </div>
        
        <div class="card">
            <h3>Yacht Insurance</h3>
            <p>Permissions related to yacht and boat insurance.</p>
            <a href="yacht-insurance.php">View Permissions</a>
        </div>
        
        <div class="card">
            <h3>Cycle Insurance</h3>
            <p>Permissions related to bicycle insurance.</p>
            <a href="cycle-insurance.php">View Permissions</a>
        </div>
        
        <div class="card">
            <h3>Group Medical Insurance</h3>
            <p>Permissions related to group medical insurance plans.</p>
            <a href="group-medical-insurance.php">View Permissions</a>
        </div>
        
        <div class="card">
            <h3>Business Insurance</h3>
            <p>Permissions related to business insurance products.</p>
            <a href="business-insurance.php">View Permissions</a>
        </div>
        
        <div class="card">
            <h3>Jetski Insurance</h3>
            <p>Permissions related to jetski insurance quotes and policies.</p>
            <a href="jetski-insurance.php">View Permissions</a>
        </div>
        
        <!-- System Categories -->
        <div class="card">
            <h3>User Management</h3>
            <p>Permissions related to user, role, and team management.</p>
            <a href="user-management.php">View Permissions</a>
        </div>
        
        <div class="card">
            <h3>Lead Management</h3>
            <p>Permissions related to lead handling and processing.</p>
            <a href="lead-management.php">View Permissions</a>
        </div>
        
        <div class="card">
            <h3>Payment Management</h3>
            <p>Permissions related to payment processing and management.</p>
            <a href="payment-management.php">View Permissions</a>
        </div>
        
        <div class="card">
            <h3>Reports & Dashboard</h3>
            <p>Permissions related to reports and dashboard access.</p>
            <a href="reports-dashboard.php">View Permissions</a>
        </div>
        
        <div class="card">
            <h3>Claims Management</h3>
            <p>Permissions for the new Claims Management module including claim operations, status updates, document handling, and data export across all Lines of Business.</p>
            <a href="claims-management.php">View Permissions</a>
        </div>
        
        <div class="card">
            <h3>Admin & Configuration</h3>
            <p>System administration and configuration permissions.</p>
            <a href="admin-configuration.php">View Permissions</a>
        </div>
        
        <div class="card">
            <h3>Document Management</h3>
            <p>Permissions related to document handling, verification, and storage.</p>
            <a href="document-management.php">View Permissions</a>
        </div>
        
        <div class="card">
            <h3>Renewal Management</h3>
            <p>Permissions related to policy renewals and batches.</p>
            <a href="renewal-management.php">View Permissions</a>
        </div>
        
        <div class="card">
            <h3>System Integration</h3>
            <p>Permissions related to API integrations and external systems.</p>
            <a href="system-integration.php">View Permissions</a>
        </div>
    </div>

    <footer>
        <p>IMCRM Permissions Documentation © 2025</p>
    </footer>
</body>
</html> 