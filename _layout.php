
<?php
date_default_timezone_set('Asia/Manila');
?>

<?php
date_default_timezone_set('Asia/Manila');
include_once 'config/database.php'; // Adjust path if needed

$db = new Database();
$conn = $db->getConnection();

$ip = $_SERVER['REMOTE_ADDR'];
$now = date('Y-m-d H:i:s');

// Check if this IP has visited in the last 24 hours
$stmt = $conn->prepare("SELECT last_visit FROM site_visits WHERE ip_address = ?");
$stmt->execute([$ip]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    // First visit from this IP
    $stmt = $conn->prepare("INSERT INTO site_visits (ip_address, last_visit) VALUES (?, ?)");
    $stmt->execute([$ip, $now]);
} else {
    $last_visit = strtotime($row['last_visit']);
    if (time() - $last_visit > 86400) { // 86400 seconds = 1 day
        // More than 1 day since last visit, update last_visit
        $stmt = $conn->prepare("UPDATE site_visits SET last_visit = ? WHERE ip_address = ?");
        $stmt->execute([$now, $ip]);
    }
}

// Get total unique visits (IPs)
$total_visits = $conn->query("SELECT COUNT(*) FROM site_visits")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ISU ROXAS-LIBRARY</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Open+Sans:wght@400;500;600&family=Montserrat:wght@700;800&display=swap"
        rel="stylesheet">
</head>
<style>
.library-info-card {
    display: flex;
    flex-direction: row;
    align-items: center;
    justify-content: space-between;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 8px;
    padding: 0.5rem 1rem;
    margin: 0;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    min-width: 400px;
    gap: 1rem;
}
.clock-container, .library-hours {
    flex: 1;
}
.clock-container {
    text-align: center;
    margin-bottom: 0;
}

#digital-clock {
    font-size: 1.3rem;
    font-weight: bold;
    color: white;
    text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.2);
    font-family: 'Arial', sans-serif;
    margin: 0;
}

.library-hours {
    border-left: 1px solid rgba(255, 255, 255, 0.3);
    padding-left: 1rem;
}

.library-hours p {
    color: white;
    line-height: 1.2;
    font-size: 0.9rem;
    margin: 0;
    text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.2);
}

/* Adjust header buttons layout */
.header-buttons {
    display: flex;
    gap: 0.5rem;
    align-items: center;
    padding-right: 1rem;
}

@media screen and (max-width: 768px) {
    .library-info-card {
        background: rgba(255, 255, 255, 0.15);
        width: 100%;
        justify-content: center;
        padding: 0.5rem;
    }
    
    .header-buttons {
        padding: 0.5rem;
    }
}

</style>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const clockDisplay = document.getElementById('digital-clock');
    // Get server time from data attribute
    let serverTime = new Date(clockDisplay.getAttribute('data-server-time').replace(' ', 'T'));
    
    function updateClock() {
        let hours = serverTime.getHours();
        const minutes = String(serverTime.getMinutes()).padStart(2, '0');
        const ampm = hours >= 12 ? 'pm' : 'am';
        hours = hours % 12;
        hours = hours ? hours : 12;
        hours = String(hours).padStart(2, '0');
        clockDisplay.textContent = `${hours}:${minutes} ${ampm}`;
        // Increment serverTime by 1 minute
        serverTime.setMinutes(serverTime.getMinutes() + 1);
    }

    updateClock(); // Initial call
    setInterval(updateClock, 60000); // Update every minute
});
</script>
<body>
    <header>
        <div class="top-header">
            <div class="logo">
                <img src="assets/img/images-removebg-preview.png" alt="ISU Logo">
                <h1>ISU ROXAS-LIBRARY</h1>
                <button class="dropdown-toggle">
                    <i class="fas fa-chevron-down"></i>
                </button>
            </div>
            <div class="header-buttons">
<div class="library-info-card">
    <div class="clock-container">
<div id="digital-clock" data-server-time="<?php echo date('Y-m-d H:i:s'); ?>"></div>
    </div>
    <div class="library-hours">
        <p>Monday - Friday<br>7:00 AM - 5:00 PM</p>
    </div>
</div>
                <a href="https://docs.google.com/forms/d/e/1FAIpQLSduOSnigxIj-a6c9YwVLWkeQ7erdOcsc8KH075WsDn8QrT34Q/viewform?usp=header" class="btn">SATISFACTION SURVEY</a>
                <div class="site-visits">
                    <span>SITE VISITS</span>
<span class="counter"><?php echo $total_visits; ?></span>
                </div>
            </div>
        </div>
        <div class="nav-header">
            <button class="nav-toggle">
                <i class="fas fa-bars"></i>
            </button>

            <nav class="main-nav">
                <a href="index.php" class="nav-btn">HOME</a>
                <a href="index.php#news-events" class="nav-btn">NEWS</a>
                <div class="nav-dropdown">
                    <a href="resources.php" class="nav-btn dropdown-trigger">
                        RESOURCES <i class="fas fa-chevron-down"></i>
                    </a>
                    <div class="dropdown-menu">
                        <a href="index.php#database">Databases</a>
                        <a href="index.php#standalone">Open Resources</a>
                        <a href="index.php#newsletter">Library Publication</a>
                    </div>
                </div>
                <div class="nav-dropdown">
                    <a href="about.php" class="nav-btn dropdown-trigger">
                        ABOUT US <i class="fas fa-chevron-down"></i>
                    </a>
                    <div class="dropdown-menu">

                        <a href="policy.php">Library Policy</a>
                        <a href="about.php#history">Library History</a>
                        <a href="about.php">Vision & Mission</a>
                        <a href="about.php#org-chart">Organizational Chart</a>
                    </div>
                </div>
                
                <div class="nav-dropdown">
                    <a href="campus-libraries.php" class="nav-btn dropdown-trigger">
                        Services <i class="fas fa-chevron-down"></i>
                    </a>
                    <div class="dropdown-menu">
                        <a href="isu-citizen-chart.php">Citizen’s Charter</a>
                        <a href="#">Library Services</a>
                    </div>
                </div>
            </nav>
        </div>

    </header>
   <button class="scrollToTopBtn" id="scrollToTopBtn" title="Go to top" >
    <img src="assets/img/scroll-up.png" alt="Scroll to top" style="width:52px;height:52px;display:block;margin:auto;">
</button>
    <style>
        /* Add to your <style> section */
.scrollToTopBtn {
    display: none;
    position: fixed;
    bottom: 20px;
    right: 40px;
    z-index: 999;
    background: none;
    border: none;
    border-radius: 50%;
    width: 45px;
    height: 45px;
    cursor: pointer;
    align-items: center;
    justify-content: center;
}
@media screen and (max-width: 600px) {
    .scrollToTopBtn {
        right: 10px;

    }
}
    html {
        scroll-behavior: smooth;
    }

        /* Add this inside your existing <style> tag */
        /* Hide scrollbar for Chrome, Safari and Opera */
        ::-webkit-scrollbar {
            display: none;
        }

        /* Hide scrollbar for IE, Edge and Firefox */
        * {
            -ms-overflow-style: none;
            /* IE and Edge */
            scrollbar-width: none;
            /* Firefox */
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            padding-top: 140px;
            font-weight: 400;
            line-height: 1.6;
        }

        .hide-header {
            transform: translateY(-100%);
        }

        .nav-header.top-position {
            top: 0;
        }

        .top-header {
            height: auto;
            min-height: 80px;
            background-color: #216c2a;
            padding: .8rem;
            display: flex;
            justify-content: space-between;
            position: fixed;
            width: 100%;
            top: 0;
            left: 0;
            z-index: 1000;
            transition: transform 0.3s ease-in-out;
        }

        .logo {
            display: flex;
            align-items: center;
            color: white;
            flex-shrink: 0;
        }

        .logo h1 {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: clamp(1.2rem, 2vw, 1.5rem);
            font-weight: 700;
            letter-spacing: 1px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
            background: linear-gradient(to right, #ffffff, #e6e6e6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            transition: all 0.3s ease;
        }

        .logo img {
            width: clamp(40px, 8vw, 60px);
            height: clamp(40px, 8vw, 60px);
            margin-right: 0.8rem;
            background-color: white;
            border-radius: 50%;
        }

        .header-buttons {
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
            transition: all 0.3s ease-in-out;
        }

        .btn {
            background-color: #f4e242;
            padding: 0.8rem 1.5rem;
            text-decoration: none;
            color: green;
            border-radius: 4px;
            white-space: nowrap;
            font-size: clamp(0.8rem, 1.5vw, 1rem);
            transition: all 0.3s ease;
            border: 2px solid transparent;
            font-weight: 600;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }


        .btn:hover {
            background-color: #ffffff;
            border-color: #f4e242;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }


.site-visits {
    background: linear-gradient(90deg, #f4e242 60%, #fffbe6 100%);
    padding: 0.5rem 1rem;
    display: flex;
    align-items: center;
    gap: 0.7rem;
    font-size: 1rem;
    font-weight: 600;
    color: #226c2a;
    box-shadow: 0 2px 8px rgba(34, 108, 42, 0.08);
    border: 2px solid #f4e242;
    margin-left: 0.5rem;
    transition: box-shadow 0.2s, background 0.2s;
}

.site-visits .counter {
    color: black;
    border-radius: 50%;
    padding: 0.4em 0.9em;
    font-size: 1em;
    font-weight: bold;
    margin-left: 0.5em;
    letter-spacing: 1px;
    transition: background 0.2s, color 0.2s;
}
@media screen and (max-width: 768px) {
    .site-visits {
        width: 100%;
        text-align: center;
        justify-content: center; /* Center content horizontally */
        margin-left: 0;
    }
    .site-visits .counter {
        margin-left: 0;
        padding: 0.4em 0.8em; /* Adjust padding for smaller screens */
        font-size: .8em; /* Adjust font size for smaller screens */
    }
}


        @media screen and (max-width: 768px) {
            .top-header {
                padding: 0.8rem;
                flex-direction: column;
            }

            .header-buttons {
                display: none;
                position: absolute;
                top: 100%;
                left: 0;
                width: 100%;
                background-color: #216c2a;
                padding: 1rem;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                transform: translateY(-100%);
                opacity: 0;
                visibility: hidden;
            }

            .header-buttons.show {
                display: flex;
                flex-direction: column;
                gap: 0.5rem;
                transform: translateY(0);
                opacity: 1;
                visibility: visible;
                animation: slideDown 0.3s ease-in-out forwards;
            }

            .btn {
                width: 100%;
                text-align: center;
                padding: 1rem;
                margin: 0;
                transition: all 0.3s ease;
            }

            .site-visits {
                width: 100%;
                text-align: center;
            }
            .library-info-card {
        flex-direction: column;
        align-items: stretch;
        width: 100%;
        min-width: 0;
        gap: 0.5rem;
    }
    .clock-container, .library-hours {
        flex: unset;
        width: 100%;
        text-align: center;
        border-left: none;
        padding-left: 0;
    }
    .library-hours {
        margin-top: 0.5rem;
    }
        }

        @keyframes slideDown {
            from {
                transform: translateY(-10px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .dropdown-toggle {
            display: none;
            background: none;
            border: none;
            color: white;
            font-size: 1.2rem;
            cursor: pointer;
            padding: 0.5rem;
            margin-left: 0.5rem;
        }

        @media screen and (max-width: 768px) {
            .dropdown-toggle {
                display: inline-block;
            }

            .header-buttons {
                display: none;
                position: absolute;
                top: 74px;
                left: 0;
                width: 100%;
                background-color: #216c2a;
                padding: 1rem;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                z-index: 1000;
            }

            .header-buttons.show {
                display: flex;
            }

            .logo {
                width: 100%;
                justify-content: space-between;
            }
        }

        .nav-header {
            background-color: rgb(214, 214, 214);
            padding: 0.5rem;
            text-align: center;
            position: fixed;
            width: 100%;
            top: 80px;
            /* Height of top-header */
            left: 0;
            z-index: 999;
            transition: top 0.3s ease-in-out;
            box-shadow: 1px 1px 5px rgba(0, 0, 0, 0.8);
        }

        .main-nav {
            display: flex;
            justify-content: center;
            gap: 1rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .nav-btn {
            background-color: transparent;
            color: black;
            padding: 0.5rem 1rem;
            text-decoration: none;
            border-radius: 4px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .nav-btn:hover {
            color: green;
        }

        @media screen and (max-width: 768px) {
            .main-nav {
        display: none;
        flex-direction: column;
        gap: 0.5rem;
        width: 100%; /* Ensure full width */
        align-items: stretch; /* Make nav buttons stretch full width */
        background: rgb(214, 214, 214); /* Match nav-header background */
        margin: 0;
        padding: 0.5rem 0;
        box-sizing: border-box;
            }

    .nav-btn {
        display: block;
        padding: 0.8rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        text-align: left; /* Or center if you prefer */
        width: 100%;
    }
        }

        .nav-toggle {
            display: none;
            background: none;
            border: none;
            color: #216c2a;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0.5rem;
            margin: 0 auto;
        }

        @media screen and (max-width: 768px) {
            .nav-toggle {
                display: block;
            }

            .main-nav {
                display: none;
                flex-direction: column;
                gap: 0.5rem;
            }

            .main-nav.show {
                display: flex;
                animation: slideDown 0.3s ease-in-out forwards;
            }
        }

        .nav-dropdown {
            position: relative;
            display: inline-block;
        }

        .dropdown-trigger {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
        }

        .dropdown-trigger i {
            font-size: 0.8rem;
            transition: transform 0.3s ease;
        }

        .dropdown-menu {
            position: absolute;
            top: 100%;
            left: 0;
            background: white;
            min-width: 200px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            border-radius: 4px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .dropdown-menu a {
            display: block;
            padding: 0.8rem 1rem;
            color: #333;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .dropdown-menu a:hover {
            background: #216c2a;
            color: white;
        }

        /* Remove hover styles and add active class styles */
        .nav-dropdown.active .dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .nav-dropdown.active .dropdown-trigger i {
            transform: rotate(180deg);
        }

        /* Mobile styles */
        @media screen and (max-width: 768px) {
            .dropdown-menu {
                position: static;
                background: rgb(230, 230, 230);
                opacity: 1;
                visibility: hidden;
                max-height: 0;
                overflow: hidden;
                transform: none;
                box-shadow: none;
                transition: all 0.3s ease;
            }

            .nav-dropdown.active .dropdown-menu {
                visibility: visible;
                max-height: 500px;
            }

            .dropdown-menu a {
                padding: 0.8rem 2rem;
            }
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const dropdownToggle = document.querySelector('.dropdown-toggle');
            const headerButtons = document.querySelector('.header-buttons');
            const chevronIcon = dropdownToggle.querySelector('i');

            dropdownToggle.addEventListener('click', function (e) {
                e.stopPropagation();
                headerButtons.classList.toggle('show');
                chevronIcon.style.transform = headerButtons.classList.contains('show')
                    ? 'rotate(180deg)'
                    : 'rotate(0)';
                chevronIcon.style.transition = 'transform 0.3s ease';
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', function (e) {
                if (!dropdownToggle.contains(e.target) && !headerButtons.contains(e.target)) {
                    headerButtons.classList.remove('show');
                    chevronIcon.style.transform = 'rotate(0)';
                }
            });
            const dropdownTriggers = document.querySelectorAll('.dropdown-trigger');

            dropdownTriggers.forEach(trigger => {
                trigger.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();

                    // Close all other dropdowns first
                    dropdownTriggers.forEach(otherTrigger => {
                        if (otherTrigger !== trigger) {
                            otherTrigger.parentElement.classList.remove('active');
                            otherTrigger.querySelector('i').style.transform = 'rotate(0)';
                        }
                    });

                    const dropdown = this.parentElement;
                    dropdown.classList.toggle('active');
                    const icon = this.querySelector('i');
                    icon.style.transform = dropdown.classList.contains('active')
                        ? 'rotate(180deg)'
                        : 'rotate(0)';
                });
            });

            // Close dropdowns when clicking outside
            document.addEventListener('click', function (e) {
                dropdownTriggers.forEach(trigger => {
                    const dropdown = trigger.parentElement;
                    if (!dropdown.contains(e.target)) {
                        dropdown.classList.remove('active');
                        trigger.querySelector('i').style.transform = 'rotate(0)';
                    }
                });
            });
        });
        // Nav toggle functionality
        const navToggle = document.querySelector('.nav-toggle');
        const mainNav = document.querySelector('.main-nav');

        navToggle.addEventListener('click', function (e) {
            e.stopPropagation();
            mainNav.classList.toggle('show');
            const isExpanded = mainNav.classList.contains('show');
            this.setAttribute('aria-expanded', isExpanded);
        });

        // Close nav when clicking outside
        document.addEventListener('click', function (e) {
            if (!navToggle.contains(e.target) && !mainNav.contains(e.target)) {
                mainNav.classList.remove('show');
                navToggle.setAttribute('aria-expanded', false);
            }
        });
        let lastScroll = 0;
        const topHeader = document.querySelector('.top-header');
        const navHeader = document.querySelector('.nav-header');
        const scrollThreshold = 80; // Adjust this value as needed

        window.addEventListener('scroll', function () {
            const currentScroll = window.pageYOffset;

            // Determine scroll direction
            if (currentScroll > lastScroll && currentScroll > scrollThreshold) {
                // Scrolling down
                topHeader.classList.add('hide-header');
                navHeader.classList.add('top-position');
            } else if (currentScroll < lastScroll && currentScroll < scrollThreshold) {
                // Scrolling up
                topHeader.classList.remove('hide-header');
                navHeader.classList.remove('top-position');
            }

            lastScroll = currentScroll;
        }, { passive: true });
document.addEventListener('DOMContentLoaded', function() {
    const scrollBtn = document.getElementById('scrollToTopBtn');
    window.addEventListener('scroll', function() {
        scrollBtn.style.display = window.pageYOffset > 200 ? 'flex' : 'none';
    });
    scrollBtn.addEventListener('click', function() {
        window.scrollTo({ top: 0, behavior: 'smooth' }); // Use native smooth scroll
    });
});
    </script>