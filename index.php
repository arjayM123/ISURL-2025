<?php
// Include database connection
include 'config/database.php';
include '_layout.php';

$db = new Database();
$conn = $db->getConnection();

// Fetch active sliders
$stmt = $conn->query("SELECT id, file_mime, file_content FROM sliders WHERE deleted_at IS NULL AND file_content IS NOT NULL ORDER BY created_at DESC");
$sliders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Pagination setup
$posts_per_page = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $posts_per_page;

// Get total count for pagination
$total_stmt = $conn->query("SELECT COUNT(*) FROM news_events WHERE deleted_at IS NULL");
$total_posts = $total_stmt->fetchColumn();
$total_pages = ceil($total_posts / $posts_per_page);

// Fetch publications for the newsletter section (updated table name and structure)
$pub_stmt = $conn->query("SELECT id, title, created_at FROM publications ORDER BY created_at DESC");
$publications = $pub_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch paginated news and events with their images
$stmt = $conn->prepare("
    SELECT 
        ne.*,
        ni.id as image_id,
        ni.file_mime
    FROM news_events ne 
    LEFT JOIN news_images ni ON ne.id = ni.news_id 
    WHERE ne.deleted_at IS NULL 
    AND (ni.order_position = 0 OR ni.order_position IS NULL)
    ORDER BY ne.created_at DESC 
    LIMIT :limit OFFSET :offset
");
$stmt->bindValue(':limit', $posts_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$news_events = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>


<div class="container">
    <section class="slider-section">
        <div class="slider-container">
            <?php foreach ($sliders as $index => $slider): ?>
                <?php if (strpos($slider['file_mime'], 'image') === 0): ?>
                    <div class="slide <?php echo $index === 0 ? 'active' : ''; ?>">
                        <img src="./api/get_slider_file.php?id=<?php echo $slider['id']; ?>" alt="Slider Image" loading="lazy">
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>

            <button class="slider-btn prev" onclick="moveSlide(-1)">❮</button>
            <button class="slider-btn next" onclick="moveSlide(1)">❯</button>

            <div class="dots-container">
                <?php
                // Only count image slides for dots
                $imageCount = 0;
                foreach ($sliders as $slider) {
                    if (strpos($slider['file_mime'], 'image') === 0) {
                        $imageCount++;
                    }
                }
                for ($i = 0; $i < $imageCount; $i++): ?>
                    <span class="dot <?php echo $i === 0 ? 'active' : ''; ?>"
                        onclick="currentSlide(<?php echo $i + 1; ?>)"></span>
                <?php endfor; ?>
            </div>
        </div>
    </section>
    <div class="content">
        <div class="content-left" id="news-events">
            <h2>NEWS & UPDATES</h2>
            <?php foreach ($news_events as $news): ?>
                <div class="news-card">
                    <div class="news-card-grid<?php echo empty($news['image_id']) ? ' no-image' : ''; ?>">
                        <?php if (!empty($news['image_id'])): ?>
                            <div class="news-image">
                                <img src="./api/get_news_image.php?id=<?php echo $news['image_id']; ?>" alt="News Image"
                                    loading="lazy">
                            </div>
                        <?php endif; ?>
                        <div class="news-details">
                            <h3 class="news-title"><?php echo htmlspecialchars($news['title']); ?></h3>
                            <div class="news-date">
                                Posted: <?php echo date('F j, Y', strtotime($news['created_at'])); ?>
                            </div>
                            <div class="news-content">
                                <?php
                                $content = strip_tags($news['content']);
                                if (strlen($content) > 200) {
                                    echo substr($content, 0, 200) . '... <a href="reports_view.php?id=' . $news['id'] . '">View more</a>';
                                } else {
                                    echo $content;
                                    // Always show "View more" button
                                    echo ' <a href="reports_view.php?id=' . $news['id'] . '">View more</a>';
                                }
                                ?>
                            </div>
                        </div>

                    </div>
                </div>
            <?php endforeach; ?>

            <!-- Pagination Links -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>">&laquo; Prev</a>
                    <?php endif; ?>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>" <?php if ($i == $page)
                               echo ' class="active"'; ?>><?php echo $i; ?></a>
                    <?php endfor; ?>
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>">Next &raquo;</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="content-right">
            <section class="standalone"  id="standalone">
                <h2>OPEN RESOURCES</h2>
                <div class="standalone-list">
                    <a href="#" class="standalone-card" id="opac-modal-trigger">
                        <div class="standalone-icon">
                            <i class="fas fa-book-open " style="color:rgb(83, 209, 144);"></i>
                        </div>
                    </a>
                    <a href="https://isur-ora.example.com" class="standalone-card" target="_blank">
                        <div class="standalone-icon">
                            <i class="fas fa-database" style="color: #6495ED;"></i>
                        </div>
                        <span class="standalone-title">ISUR-ORA</span>
                    </a>
                </div>
                <br>
                <section id="newsletter">
                    <h2>
                        Newsletter
                    </h2>
                    <div style="display:flex; flex-direction:column; gap:0.7rem;">
                        <?php foreach ($publications as $pub): ?>
                            <a href="library_publication.php?id=<?php echo $pub['id']; ?>"
                                style="font-size:1.1rem; color:#226c2a; font-weight:600; text-decoration:underline;">
                                <?php echo htmlspecialchars($pub['title']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </section>

            </section>
        </div>

    </div>

    <div id="opac-modal" class="opac-modal">
        <div class="opac-modal-content">
            <span class="opac-modal-close" id="opac-modal-close">&times;</span>
            <img src="assets/img/OPAC.png" alt="OPAC System" class="opac-image">
            <h1>Online Public Access Catalog (OPAC)</h1>
            <div class="description">
                <strong>Definition:</strong> An <b>Online Public Access Catalog (OPAC)</b> is a digital database of
                materials held by a library or group of libraries. It allows users to search, locate, and access
                information about books, journals, and other resources available in the library collection.<br><br>
                <strong>About ISUR-OPAC:</strong> The ISUR-OPAC is a modern, user-friendly Online Public Access Catalog
                designed to help library users easily search, locate, and access library resources. With a responsive
                interface and intuitive navigation, users can efficiently find books, journals, and digital materials
                from any device. The system emphasizes accessibility, reliability, and professionalism, supporting the
                mission of libraries to provide seamless information services to their communities.
            </div>
            <div class="credits">
                ISUR-OPAC developed by Roysen Jinnery Mabini &amp; Franciss Mae Cabebe<br>
                Date: 2025
            </div>
        </div>
    </div>


    <!-- database -->
    <section class="online-resources" id="database">
        <a href="https://m.me/isurlibrary" target="_blank" class="messenger-float" title="Chat with us on Messenger">
            <img src="assets/img/icons8-facebook-messenger.svg" alt="Messenger" style="width:50px;height:50px;">
            <span class="messenger-tooltip">
                Contact the librarian via Messenger<br>
                For any concerns or assistance
            </span>
        </a>
        <h2>DATABASE</h2>
        <div class="resources-grid">
            <a href="#" class="resource database-modal-trigger" data-href="https://ebooks.wtbooks.net/">
                <img src="assets/img/photo_6208347872177539673_w.jpg" alt="wtbooks">
                <span class="resource-title">WORLD TECHNOLOGIES</span>
            </a>
            <a href="#" class="resource database-modal-trigger" data-href="https://search.ebscohost.com">
                <img src="assets/img/photo_6208347872177539674_w.jpg" alt="wtbooks">
                <span class="resource-title">EBSCO</span>
            </a>
            <a href="#" class="resource database-modal-trigger"
                data-href="https://portal.igpublish.com/iglibrary/signin?targeturl=/">
                <img src="assets/img/photo_6208347872177539676_w.jpg" alt="wtbooks">
                <span class="resource-title">EBSCO</span>
            </a>
            <a href="#" class="resource database-modal-trigger" data-href="https://tdmebooks.com/">
                <img src="assets/img/photo_6221807827236799725_w.jpg" alt="wtbooks">
                <span class="resource-title">TDMEBOOKS</span>
            </a>
        </div>

        <!-- Database Modal -->
        <div id="database-modal" class="opac-modal">
            <div class="opac-modal-content">
                <span class="opac-modal-close" id="database-modal-close">&times;</span>
                <h1>Access to Database</h1>
                <div class="description">
                    <strong>Note:</strong> Before accessing the database, please ask the librarian staff about the email
                    and password required for login.<br><br>
                    For assistance, you may contact the library staff via Messenger or proceed if you already have the
                    credentials.
                </div>
                <div style="display: flex; flex-direction: column; gap: 1rem; align-items: center;">
                    <a href="https://m.me/isurlibrary" target="_blank" class="standalone-card"
                        style="max-width:320px; width:100%; background:#e3f2fd; color:#226c2a; flex-direction:row; gap:1rem; box-shadow:none; border:1px solid #6495ED;">
                        <i class="fab fa-facebook-messenger" style="font-size:2rem; color:#0084ff;"></i>
                        <span style="font-weight:600;">Via Messenger</span>
                    </a>
                    <a href="" target="_blank" class="standalone-card" id="database-continue-btn"
                        style="max-width:320px; width:100%; background:#226c2a; color:#fff; flex-direction:row; gap:1rem; box-shadow:none; border:1px solid #226c2a;">
                        <i class="fas fa-arrow-right" style="font-size:1.5rem;"></i>
                        <span style="font-weight:600;">Continue</span>
                    </a>
                </div>
            </div>
        </div>
    </section>
    </section>
    <!-- Google Map stays on top -->
    <div style="width:100%; display:flex; justify-content:center; margin-bottom:0;">
        <iframe
            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3879.936267241347!2d121.6299605!3d17.1116341!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x338ff58f01a39fcd%3A0xcc8bad6a36978b4f!2sIsabela%20State%20University%20-%20Roxas%20Campus%2C%20East%20Site!5e0!3m2!1sen!2sph!4v1719400000000!5m2!1sen!2sph"
            width="100%" height="300" style="border:0; border-radius:0 0 12px 12px;" allowfullscreen="" loading="lazy"
            referrerpolicy="no-referrer-when-downgrade">
        </iframe>
    </div>


    <style>
        .standalone-list {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            margin-top: 1rem;
        }

        .standalone-card {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            gap: 1.2rem;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(33, 108, 42, 0.08), 0 1.5px 4px rgba(99, 99, 99, 0.4);
            padding: 1.2rem 1.5rem;
            text-decoration: none;
            transition: box-shadow 0.2s, transform 0.2s;
            border: 1px solid #e3f2fd;
        }


        .standalone-icon {
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 1px 4px rgba(33, 108, 42, 0.08);
            font-size: 3rem;
            color: #226c2a;
        }

        .standalone-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #226c2a;
            letter-spacing: 0.5px;
        }

        @media screen and (max-width: 992px) {
            .standalone-list {
                flex-direction: row;
                justify-content: center;
                gap: 1rem;
            }
        }

        @media screen and (max-width: 600px) {
            .standalone-list {
                flex-direction: column;
                gap: 1rem;
            }

            .standalone-card {
                padding: 1rem;
            }

            .standalone-title {
                font-size: 1.1rem;
            }
        }

        .messenger-float {
            position: fixed;
            right: 30px;
            bottom: 100px;
            z-index: 9999;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: box-shadow 0.2s;
            cursor: pointer;
            background: none;
            /* Remove background */
            box-shadow: none;
            /* Remove box-shadow */
            padding: 0;
            /* Remove padding */
        }


        .messenger-float img {
            display: block;
            border-radius: 0;
            /* Remove border-radius */
            box-shadow: none;
            /* Remove box-shadow */
            padding: 0;
            /* Remove padding */
            background: none;
            /* Remove background */
        }

        .messenger-tooltip {
            visibility: hidden;
            opacity: 0;
            width: 220px;
            background: #222;
            color: #fff;
            text-align: center;
            border-radius: 8px;
            padding: 12px 16px;
            position: absolute;
            right: 70px;
            bottom: 10px;
            font-size: 1rem;
            line-height: 1.4;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.18);
            transition: opacity 0.3s, visibility 0.3s;
            pointer-events: none;

        }

        .messenger-float:hover .messenger-tooltip {
            visibility: visible;
            opacity: 1;
        }

        @media screen and (max-width: 600px) {
            .messenger-float {
                right: 3px;
                bottom: 80px;
            }

            .messenger-tooltip {
                right: 50px;
                width: 180px;
            }
        }

        .container {
            width: 100%;
        }

        .content {
            display: grid;
            grid-template-columns: 3fr 1fr;
            gap: 10rem;
            padding: 2rem;
        }

        .content-left {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            order: 1;
            /* Default order */
        }

        .content-right {
            height: fit-content;
            order: 2;
            /* Default order */
        }

        .standalone-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }


        @media screen and (max-width: 992px) {
            .content {
                grid-template-columns: 1fr;
                gap: 2rem;
            }

            .content-left,
            .content-right {
                width: 100%;
            }

            .content-right {
                order: 2;
            }

            .content-left {
                order: 1;
            }

            .standalone-list {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 1rem;
            }
        }

        @media screen and (max-width: 576px) {
            .standalone-list {
                grid-template-columns: 1fr;
            }
        }

        /* ----------------------------------news------------------------------ */
        #news-events {
            scroll-margin-top: 120px;
            /* Adjust based on your header height */
        }

        #standalone {
            scroll-margin-top: 85px;
            /* Adjust based on your header height */
        }

        #newsletter {
            scroll-margin-top: 120px;
            /* Adjust based on your header height */
        }

        #database {
            scroll-margin-top: 60px;
            /* Adjust based on your header height */
        }

        .news-card {
            overflow: hidden;
            transition: box-shadow 0.2s;
        }


        .online-resources {
            background: #f9f9f9;
            padding: 2rem;
            border-radius: 10px;
        }

        .resources-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 2rem;
            margin: 2rem 0;
        }

        .resource {
            display: block;
            position: relative;
            border-radius: 3px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.9);
            transition: all 0.3s ease;
            text-decoration: none;
            margin: 0 auto;
        }

        .resource:hover {
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        .resource img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .resource-title {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 1rem;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            text-align: center;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .resource:hover .resource-title {
            background: rgba(33, 108, 42, 0.9);
        }

        @media screen and (max-width: 992px) {
            .resources-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media screen and (max-width: 576px) {
            .resources-grid {
                grid-template-columns: 1fr;
            }
        }

        .slider-section {
            border-top: #333 5px solid;
            border-bottom: #333 5px solid;
        }

        .slider-container {
            width: 100%;
            height: 600px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding-top: 200px;
        }

        .slide {
            display: none;
            width: 100%;
            height: 100%;
            position: absolute;
            top: 0;
            left: 0;
        }

        .slide.active {
            display: block;
            animation: fade 0.8s ease;
        }

        .slide img,
        .slide video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            /* Align to top to match ISU's header style */
        }

        .slider-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(0, 0, 0, 0);
            color: white;
            padding: 1rem;
            border: none;
            cursor: pointer;
            font-size: 1.5rem;
            transition: all 0.3s ease;
            z-index: 2;
        }

        .slider-btn:hover {
            background: rgba(0, 0, 0, 0.32);
        }

        .prev {
            left: 1rem;
        }

        .next {
            right: 1rem;
        }

        .dots-container {
            position: absolute;
            bottom: 1rem;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 0.5rem;
            z-index: 2;
        }

        .dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.5);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .dot.active,
        .dot:hover {
            background: white;
        }

        @keyframes fade {
            from {
                opacity: 0.4;
            }

            to {
                opacity: 1;
            }
        }

        @media screen and (max-width: 768px) {
            .slider-container {
                width: 100%;
                height: 200px;
            }

            .slide img,
            .slide video {
                width: 100%;
                height: 100%;
                object-fit: contain;
                /* Show full image/video without cropping */
                object-position: center;
                background: #333;
                /* Optional: white background for letterboxing */
                display: block;
            }

            .slider-btn {
                padding: 0.75rem;
                font-size: 1.2rem;
            }

            .dot {
                width: 10px;
                height: 10px;
            }
        }

        @media screen and (max-width: 380px) {
            .slider-section {
                margin-top: 10px;
            }

            .slider-btn {
                padding: 0.5rem;
                font-size: 1rem;
            }
        }

        .opac-modal {
            display: none;
            position: fixed;
            z-index: 99999;
            left: 0;
            top: 0;
            width: 100vw;
            height: 100vh;
            overflow: auto;
            background: rgba(44, 62, 80, 0.45);
        }

        .opac-modal-content {
            background: #fff;
            margin: 4% auto;
            padding: 32px 24px;
            border-radius: 12px;
            max-width: 720px;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.18);
            position: relative;
            text-align: left;
            font-family: 'Segoe UI', Arial, sans-serif;
        }

        .opac-modal-close {
            position: absolute;
            top: 18px;
            right: 24px;
            font-size: 2rem;
            color: #888;
            cursor: pointer;
            font-weight: bold;
            transition: color 0.2s;
        }

        .opac-modal-close:hover {
            color: #226c2a;
        }

        .opac-image {
            width: 100%;
            max-width: 400px;
            display: block;
            margin: 0 auto 18px auto;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.5);


        }

        .opac-modal-content h1 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 12px;
            font-size: 1.4rem;
            letter-spacing: 1px;
        }

        .opac-modal-content .description {
            text-align: justify;
            color: #444;
            font-size: 1.01rem;
            line-height: 1.7;
            margin-bottom: 18px;
        }

        .opac-modal-content .credits {
            text-align: center;
            font-size: 0.98rem;
            color: #888;
            margin-top: 18px;
            border-top: 1px solid #e0e0e0;
            padding-top: 10px;
        }

        @media screen and (max-width: 600px) {
            .opac-modal-content {
                padding: 30px 15px;
                max-width: 90vw;
            }

            .opac-modal-content h1 {
                font-size: 1.1rem;
            }
        }

        //* News Events Section - Fixed Height Cards with Uniform Sizing */
        #news-events {
            scroll-margin-top: 120px;
            padding: 0;
            width: 100%;
            overflow: hidden;
        }

        #news-events h2 {
            font-size: 2rem;
            font-weight: 700;
            color: #226c2a;
            margin-bottom: 2rem;
            text-align: left;
            letter-spacing: 0.5px;
            border-bottom: 3px solid #226c2a;
            padding-bottom: 0.5rem;
        }

        .news-card {
            background: #fff;
            margin-bottom: 2rem;
            overflow: hidden;
            transition: all 0.3s ease;
            width: 100%;
            max-width: 100%;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            height: 300px;
            /* Fixed height for all cards */
            border-radius: 8px;
        }

        .news-card-grid {
            display: grid;
            grid-template-columns: 420px 1fr;
            gap: 0;
            height: 100%;
            width: 100%;
        }

        .news-card-grid.no-image {
            grid-template-columns: 1fr;
        }

        .news-image {
            position: relative;
            overflow: hidden;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
        }

        .news-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            transition: transform 0.3s ease;
            display: block;
        }


        .news-details {
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            background: #fff;
            width: 100%;
            box-sizing: border-box;
            height: 100%;
            overflow: hidden;
        }

        .news-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #2c3e50;
            margin: 0 0 1rem 0;
            line-height: 1.4;
            letter-spacing: 0.3px;
            word-wrap: break-word;
            overflow-wrap: break-word;
            height: 3.5rem;
            /* Fixed height for consistent spacing */
            overflow: hidden;
            display: flex;
            align-items: flex-start;
        }

        .news-date {
            font-size: 0.9rem;
            color: #7f8c8d;
            margin-bottom: 1rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            height: 1.2rem;
            flex-shrink: 0;
        }

        .news-date::before {
            content: "📅";
            font-size: 0.8rem;
        }

        .news-content {
            font-size: 0.95rem;
            color: #4a5568;
            line-height: 1.6;
            margin: 0;
            word-wrap: break-word;
            overflow-wrap: break-word;
            flex-grow: 1;
            overflow: hidden;
            text-align: justify;
            /* Justify text */
            display: flex;
            flex-direction: column;
        }

        .news-content a {
            color: #226c2a;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s ease;
            position: relative;
            margin-top: auto;
            /* Push "View more" to bottom */
            align-self: flex-start;
            margin-top: 0.5rem;
        }


        .news-content a::after {
            content: " →";
            font-weight: bold;
            transition: transform 0.2s ease;
        }


        /* Pagination Styles */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            margin-top: 3rem;
            flex-wrap: wrap;
        }

        .pagination a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 1rem;
            background: #fff;
            color: #226c2a;
            text-decoration: none;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.2s ease;
            min-width: 44px;
            height: 44px;
        }

        .pagination a:hover {
            background: #226c2a;
            color: #fff;
            border-color: #226c2a;
            transform: translateY(-1px);
        }

        .pagination a.active {
            background: #226c2a;
            color: #fff;
            border-color: #226c2a;
            cursor: default;
        }

        .pagination a.active:hover {
            transform: none;
        }

        /* Cards without images */
        .news-card-grid.no-image .news-details {
            padding: 2rem;
        }

        .news-card-grid.no-image .news-title {
            height: auto;
            margin-bottom: 1.5rem;
        }

        .news-card-grid.no-image .news-content {
            font-size: 1rem;
        }

        /* Responsive adjustments - minimal media queries */
        @media screen and (max-width: 768px) {
            .news-card {
                height: 400px;
                /* Taller for mobile stacked layout */
            }

            .news-card-grid {
                grid-template-columns: 1fr;
                grid-template-rows: 200px 1fr;
                /* Fixed 200px for image, rest for content */
            }

            .news-image {
                height: 200px;
                width: 100%;
            }

            .news-details {
                height: 200px;
                /* Fixed content area height */
                padding: 1.25rem;
            }

            .news-title {
                font-size: 1.2rem;
                height: 3rem;
            }

            .news-content {
                font-size: 0.9rem;
            }
        }

        @media screen and (max-width: 480px) {
            .content {
                padding: 0.75rem;
            }

            .news-card {
                height: 380px;
                margin-bottom: 1.5rem;
            }

            .news-card-grid {
                grid-template-rows: 180px 1fr;
            }

            .news-image {
                height: 180px;
            }

            .news-details {
                padding: 1rem;
                height: 200px;
            }

            .news-title {
                font-size: 1.1rem;
                height: 2.8rem;
            }

            .news-content {
                font-size: 0.85rem;
            }

            .pagination a {
                padding: 0.5rem;
                font-size: 0.9rem;
                min-width: 40px;
                height: 40px;
            }
        }

        /* Accessibility and performance */
        @media (prefers-reduced-motion: reduce) {
            * {
                transition: none !important;
                transform: none !important;
            }
        }

        @media screen and (prefers-contrast: high) {
            .news-title {
                color: #000;
            }

            .news-content {
                color: #000;
            }

            .pagination a {
                border: 2px solid #000;
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            let currentSlideIndex = 1;
            const slides = document.getElementsByClassName("slide");
            const dots = document.getElementsByClassName("dot");
            const totalSlides = slides.length;

            if (totalSlides > 0) {
                showSlides(currentSlideIndex);

                // Auto advance slides every 5 seconds
                setInterval(() => {
                    moveSlide(1);
                }, 5000);
            }

            window.moveSlide = function (n) {
                if (totalSlides > 0) {
                    showSlides(currentSlideIndex += n);
                }
            }

            window.currentSlide = function (n) {
                if (totalSlides > 0) {
                    showSlides(currentSlideIndex = n);
                }
            }

            function showSlides(n) {
                if (n > totalSlides) currentSlideIndex = 1;
                if (n < 1) currentSlideIndex = totalSlides;

                [...slides].forEach(slide => slide.classList.remove("active"));
                [...dots].forEach(dot => dot.classList.remove("active"));

                slides[currentSlideIndex - 1].classList.add("active");
                dots[currentSlideIndex - 1].classList.add("active");
            }

        });
        document.addEventListener('DOMContentLoaded', function () {
            var modal = document.getElementById('opac-modal');
            var trigger = document.getElementById('opac-modal-trigger');
            var closeBtn = document.getElementById('opac-modal-close');
            if (trigger && modal && closeBtn) {
                trigger.onclick = function (e) {
                    e.preventDefault();
                    modal.style.display = 'block';
                };
                closeBtn.onclick = function () {
                    modal.style.display = 'none';
                };
                window.onclick = function (event) {
                    if (event.target == modal) {
                        modal.style.display = 'none';
                    }
                };
            }
        });
        document.addEventListener('DOMContentLoaded', function () {
            // OPAC modal
            var modal = document.getElementById('opac-modal');
            var trigger = document.getElementById('opac-modal-trigger');
            var closeBtn = document.getElementById('opac-modal-close');
            if (trigger && modal && closeBtn) {
                trigger.onclick = function (e) {
                    e.preventDefault();
                    modal.style.display = 'block';
                };
                closeBtn.onclick = function () {
                    modal.style.display = 'none';
                };
                window.onclick = function (event) {
                    if (event.target == modal) {
                        modal.style.display = 'none';
                    }
                };
            }

            // DATABASE modal for all cards
            var dbModal = document.getElementById('database-modal');
            var dbTriggers = document.querySelectorAll('.database-modal-trigger');
            var dbCloseBtn = document.getElementById('database-modal-close');
            var dbContinueBtn = document.getElementById('database-continue-btn');
            if (dbTriggers.length && dbModal && dbCloseBtn && dbContinueBtn) {
                dbTriggers.forEach(function (trigger) {
                    trigger.onclick = function (e) {
                        e.preventDefault();
                        var link = trigger.getAttribute('data-href');
                        dbContinueBtn.setAttribute('href', link);
                        dbModal.style.display = 'block';
                    };
                });
                dbCloseBtn.onclick = function () {
                    dbModal.style.display = 'none';
                };
                window.addEventListener('click', function (event) {
                    if (event.target == dbModal) {
                        dbModal.style.display = 'none';
                    }
                });
            }
        });
    </script>
    <?php include '_footer.php'; ?>