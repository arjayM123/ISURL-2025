<?php 
// about.php
require_once 'config/database.php';

$db = new Database();
$conn = $db->getConnection();

// Fetch only 3 latest news and events with their images
$stmt = $conn->prepare("
    SELECT ne.*, ni.id as image_id, ni.file_mime 
    FROM news_events ne 
    LEFT JOIN news_images ni ON ne.id = ni.news_id 
    WHERE ne.deleted_at IS NULL 
    AND (ni.order_position = 0 OR ni.order_position IS NULL) 
    ORDER BY ne.created_at DESC 
    LIMIT 3
");
$stmt->execute();
$news_events = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '_layout.php';
?>
</head>
<body>
    <div class="container">
        <div class="main-content">
            <!-- Left Side: Main Content -->
            <div class="content-section">
                <!-- Vision & Mission Card -->
                <div class="card fade-in">
                    <div class="pt">
                        <h2 class="vision-header">Vision</h2>
                        <p class="vision-text">
                            A leading Research University in the ASEAN Region
                        </p>
                    </div>
                    <div class="pt">
                        <h2 class="mission-header">Mission</h2>
                        <p class="mission-text">
                            The Isabela State University is committed to develop globally competitive human, technological resources and services through quality instruction, innovative research, responsive community engagement and viable resource management programs for inclusive growth and sustainable development.
                        </p>
                    </div>
                </div>

                <!-- History Section -->
                <div class="history fade-in" id="history">
                    <h2 class="mission-header">History</h2>
                    <p>
                        The Isabela State University Library was founded simultaneously with the merging of the former Isabela State College of Agriculture with the Cagayan Valley Institute of Technology on June 10, 1978, and transferring the college level courses of Isabela School of Arts and Trades, Jones Rural School, Roxas Memorial Agricultural and Industrial School and San Mateo Vocational and Industrial School to be known as the Isabela State University on January 2002. At present, the Isabela State University has nine (9) campuses with its campus library and three (3) extension campuses. These libraries are maintained primarily to serve the academic needs of students and faculty.
                    </p>
                    <img src="assets/img/HISTORY-2.png" alt="University History" loading="lazy">
                </div>

                <!-- Organizational Chart Section -->
                <div class="org-chart-section fade-in">
                    <h2 class="">Organizational Chart</h2>
                    <div class="org" id="org-chart">
                        <img src="assets/img/chart.jpg" alt="Organizational Chart" loading="lazy">
                    </div>
                </div>
            </div>

            <!-- Right Side: News Sidebar -->
            <div class="news-sidebar fade-in">
                <h2>News & Updates</h2>
                <div class="news-list">
                    <?php if (!empty($news_events)): ?>
                        <?php foreach ($news_events as $news): ?>
                            <a href="reports_view.php?id=<?= htmlspecialchars($news['id']) ?>" class="news-item">
                                <?php if (!empty($news['image_id'])): ?>
                                    <img src="./api/get_news_image.php?id=<?= htmlspecialchars($news['image_id']) ?>" 
                                         alt="<?= htmlspecialchars($news['title']) ?>" 
                                         loading="lazy">
                                <?php endif; ?>
                                <div class="news-title">
                                    <?= htmlspecialchars($news['title']) ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="news-item" style="text-align: center; padding: 30px;">
                            <p style="color: #64748b; font-style: italic;">No news updates available at the moment.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Add fade-in animation on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        // Observe all fade-in elements
        document.querySelectorAll('.fade-in').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(20px)';
            el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(el);
        });

        // Add loading state for news items
        document.querySelectorAll('.news-item img').forEach(img => {
            img.addEventListener('load', () => {
                img.parentElement.classList.remove('loading');
            });
            
            img.addEventListener('error', () => {
                img.style.display = 'none';
            });
        });
    </script>

<?php include '_footer.php'; ?>

<style>
  /* ----------------------------- */
      #history {
    scroll-margin-top: 80px; /* Adjust based on your header height */
}
      #org-chart {
    scroll-margin-top: 150px; /* Adjust based on your header height */
}
  /* _____________________________ */
.container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
}

/* Main Layout */
.main-content {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 40px;
    margin-bottom: 40px;
}

.content-section {
    border-radius: 12px;
    overflow: hidden;
}

.news-sidebar {
    border-radius: 12px;
    padding: 30px;
    height: fit-content;
    position: sticky;
    top: 20px;
}

/* Card Styles */
.card {
    padding: 40px;
}

.pt {
    margin-bottom: 30px;
}

.pt:last-child {
    margin-bottom: 0;
}

/* Headers */
.vision-header, .mission-header {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 15px;
    color: #1e293b;
    position: relative;
    padding-bottom: 10px;
}

.vision-header::after, .mission-header::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 60px;
    height: 3px;
    background: linear-gradient(135deg, #1ea311ff, #0f7905ff);
    border-radius: 2px;
}

/* Text Styles */
.vision-text, .mission-text {
    font-size: 1.1rem;
    line-height: 1.7;
    color: #47515fff;
    text-align: justify;
}

.vision-text {
    font-weight: 600;
    font-size: 1.3rem;
    color: #1e293b;
    font-style: italic;
}

/* History Section */
.history {
    padding: 40px;
    border-bottom: 1px solid #e2e8f0;
}

.history h2 {
    margin-bottom: 20px;
}

.history p {
    font-size: 1.1rem;
    line-height: 1.7;
    color: #64748b;
    text-align: justify;
    margin-bottom: 30px;
}

.history img {
    width: 100%;
    height: auto;
    border-radius: 8px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    margin-bottom: 30px;
}

/* Organizational Chart */
.org-chart-section {
    padding: 40px;
}

.org-chart-section h2 {
    text-align: center;
    margin-bottom: 30px;
    font-size: 2rem;
    color: #1e293b;
}

.org {
    text-align: center;
}

.org img {
    max-width: 100%;
    height: auto;
    border-radius: 8px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

/* News Sidebar */
.news-sidebar h2 {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1d610cff;
    margin-bottom: 25px;
    text-align: center;
    position: relative;
    padding-bottom: 15px;
}

.news-sidebar h2::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 40px;
    height: 2px;
    background: #1d610cff;
    border-radius: 2px;
}

/* News Items */
.news-item {
    display: block;
    margin-bottom: 20px;
    padding: 20px;
    border-radius: 8px;
    transition: all 0.3s ease;
    text-decoration: none;
    Text-align:center;

}

.news-item:last-child {
    margin-bottom: 0;
}

/* Fixed image size */
.news-item img {
    width: 100%;
    height: 190px;
    object-fit: cover;
    object-position: center;
    background-color: #f1f5f9;
    display: block;
    margin-bottom:10px;
}

/* News Title */
.news-title {
    font-size: 0.95rem;
    font-weight: 600;
    line-height: 1.4;
    color: #1e293b !important;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Responsive Design */
@media screen and (max-width: 1200px) {
    .container {
        padding: 15px;
    }

    .main-content {
        gap: 30px;
    }

    .card, .history, .org-chart-section {
        padding: 30px;
    }

    .news-sidebar {
        padding: 25px;
    }
}

@media screen and (max-width: 968px) {
    .main-content {
        display: flex;
        flex-direction: column;
        gap: 30px;
    }

    .content-section {
        order: 1;
    }

    .news-sidebar {
        order: 2;
        position: static;
        margin-top: 30px;
        padding: 25px;
    }

    .vision-header, .mission-header {
        font-size: 1.8rem;
    }

    .card, .history, .org-chart-section {
        padding: 25px;
    }

    .news-sidebar h2 {
        font-size: 1.4rem;
    }
}

@media screen and (max-width: 768px) {
    .container {
        padding: 10px;
    }

    .card, .history, .org-chart-section {
        padding: 20px;
    }

    .news-sidebar {
        padding: 20px;
    }

    .vision-header, .mission-header {
        font-size: 1.6rem;
    }

    .vision-text, .mission-text, .history p {
        font-size: 1rem;
    }

    .news-item {
        padding: 15px;
    }

    .news-title {
        font-size: 0.9rem;
    }
}

@media screen and (max-width: 480px) {
    .card, .history, .org-chart-section, .news-sidebar {
        padding: 15px;
    }

    .vision-header, .mission-header {
        font-size: 1.4rem;
    }

    .org-chart-section h2 {
        font-size: 1.6rem;
    }

    .news-sidebar h2 {
        font-size: 1.3rem;
    }

    .news-item img {
        height: 140px;
    }
}

/* Additional Utility Classes */
.fade-in {
    animation: fadeIn 0.6s ease-in;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Loading States */
.news-item.loading {
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: loading 2s infinite;
}

@keyframes loading {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}

</style>