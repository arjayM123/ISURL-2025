<?php
require_once 'config/database.php';
require_once 'admin_layout.php';

?>
<nav class="nav-cards">

        <a href="slider.php" class="nav-card">
            <i class="fas fa-images"></i>
            <span> Slider</span>
        </a>
        <a href="publication.php" class="nav-card">
            <i class="fas fa-newspaper"></i>
            <span>Library Publication</span>
        </a>
        <a href="news.php" class="nav-card">
            <i class="fas fa-calendar-alt"></i>
            <span>News & Updates</span>
        </a>
        <a href="manage_tables.php" class="nav-card">
            <i class="fas fa-table"></i>
            <span>Manage Tables</span>
        </a>
        <a href="profile.php" class="nav-card">
            <i class="fas fa-user"></i>
            <span>Admin Settings</span>
        </a>


    </nav>

    <div class="content">
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['message_type']; ?>">
                <?php 
                    echo $_SESSION['message'];
                    unset($_SESSION['message']);
                    unset($_SESSION['message_type']);
                ?>
            </div>
        <?php endif; ?>
    </div>
<style>
            .nav-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            padding: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .nav-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            text-decoration: none;
            color: #216c2a;
            text-align: center;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
        }

        .nav-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
        }

        .nav-card i {
            font-size: 2.5rem;
        }

        .nav-card span {
            font-size: 1.1rem;
            font-weight: bold;
        }
</style>