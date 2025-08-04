<?php
require_once 'config/database.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ISU Roxas Library Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
    :root {
        --primary-color: #216c2a;
        --primary-dark: #1a5621;
        --secondary-color: #f4f6f9;
        --text-light: #ffffff;
        --text-dark: #2d3748;
        --success: #059669;
        --error: #dc2626;
        --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        background: var(--secondary-color);
        min-height: 100vh;
        line-height: 1.5;
        color: var(--text-dark);
    }

    .header {
        background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
        color: var(--text-light);
        padding: 1.5rem;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        position: sticky;
        top: 0;
        z-index: 100;
    }

    .header-content {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 3rem;
        max-width: 1200px;
        margin: 0 auto;
        padding: 0.5rem;
    }

    .header img {
        width: 85px;
        height: 85px;
        object-fit: contain;
        filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.2));
        transition: transform 0.3s ease;
        background:white;
        border-radius: 50%;
    }

        .logo-container {
        display: flex;
        align-items: center;
        gap: 2rem;
    }

    .header img:hover {
        transform: scale(1.05);
        filter: drop-shadow(0 6px 8px rgba(0, 0, 0, 0.25));
    }


    .alert {
        padding: 1rem 2rem;
        margin: 1rem auto;
        border-radius: 8px;
        text-align: center;
        max-width: 600px;
        animation: slideIn 0.3s ease;
        box-shadow: var(--shadow-sm);
    }

    .alert-success {
        background: #ecfdf5;
        color: var(--success);
        border: 1px solid #6ee7b7;
    }

    .alert-error {
        background: #fee2e2;
        color: var(--error);
        border: 1px solid #fecaca;
    }

    .logout-btn {
        position: fixed;
        top: 1.5rem;
        right: 1.5rem;
        background: rgba(255, 255, 255, 0.1);
        color: var(--text-light);
        padding: 0.6rem 1.2rem;
        border-radius: 8px;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        backdrop-filter: blur(8px);
        transition: all 0.3s ease;
        font-weight: 500;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .logout-btn:hover {
        background: rgba(255, 255, 255, 0.2);
        transform: translateY(-1px);
        box-shadow: var(--shadow-md);
    }

    .logout-btn i {
        font-size: 1.1rem;
    }
        .title-container {
        display: flex;
        flex-direction: column;
        gap: 0.3rem;
    }
        .header h1 {
        font-size: 2.2rem;
        font-weight: 700;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        line-height: 1.2;
        letter-spacing: 0.5px;
        margin: 0;
    }

    .header .subtitle {
        font-size: 1.4rem;
        font-weight: 500;
        color: rgba(255, 255, 255, 0.9);
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    .header .subtitle {
        font-size: 1.4rem;
        font-weight: 500;
        color: rgba(255, 255, 255, 0.9);
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }


    /* Animations */
    @keyframes slideIn {
        from {
            transform: translateY(-10px);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .header-content {
            flex-direction: column;
            gap: 1rem;
            text-align: center;
        }

        .header h1 {
            font-size: 1.5rem;
        }

        .logout-btn {
            top: 1rem;
            right: 1rem;
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
        }
    }

    /* Content Container */
    .content {
        max-width: 1200px;
        margin: 2rem auto;
        padding: 0 1rem;
    }

    /* Custom Scrollbar */
    ::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }

    ::-webkit-scrollbar-track {
        background: #f1f1f1;
    }

    ::-webkit-scrollbar-thumb {
        background: var(--primary-color);
        border-radius: 4px;
    }

    ::-webkit-scrollbar-thumb:hover {
        background: var(--primary-dark);
    }
    
    </style>
</head>
<body>
<header class="header">
    <a href="admin_logout.php" class="logout-btn">
        <i class="fas fa-sign-out-alt"></i>
        <span>Logout</span>
    </a>
    <div class="header-content">
        <div class="logo-container">
            <img src="assets/img/images-removebg-preview.png" alt="ISU Logo">
            <div class="title-container">
                <h1>ISU ROXAS LIBRARY</h1>
                <div class="subtitle">ADMIN PANEL</div>
            </div>
        </div>
    </div>
</header>
</body>
</html>