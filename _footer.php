<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Responsive Footer</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Reset and base styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            margin: 0;
            min-height: 100vh;
            background: #f5f5f5;
        }



        footer {
            background: linear-gradient(120deg, #23272b 70%, #2d3a4a 100%);
            color: #fff;
            font-family: 'Segoe UI', Arial, sans-serif;
            position: relative;
            z-index: 2;
            box-shadow: 0 -2px 16px rgba(0,0,0,0.08);
            overflow: hidden;
        }

        /* Watermark logo background */
        footer::before {
            content: "";
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            background: url('assets/img/images-removebg-preview.png') no-repeat center center;
            background-size: clamp(150px, 25vw, 300px);
            opacity: 0.08;
            width: clamp(200px, 35vw, 400px);
            height: clamp(200px, 35vw, 400px);
            pointer-events: none;
            z-index: 0;
        }ZWlnaHQ9ImJvbGQiPklTVTwvdGV4dD4KPC9zdmc+') no-repeat center center;
            background-size: clamp(150px, 25vw, 300px);
            opacity: 0.08;
            width: clamp(200px, 35vw, 400px);
            height: clamp(200px, 35vw, 400px);
            pointer-events: none;
            z-index: 0;
        }

        /* Make sure footer content is above watermark */
        .footer-main,
        .footer-copyright {
            position: relative;
            z-index: 1;
        }

        .footer-main {
            display: flex;
            flex-wrap: wrap;
            max-width: 1200px;
            margin: 0 auto;
            padding: clamp(1.5rem, 4vw, 2.5rem) clamp(1rem, 3vw, 1.5rem);
            gap: clamp(1.5rem, 4vw, 2.5rem);
            justify-content: center;
            align-items: flex-start;
        }

        .footer-col {
            flex: 1 1 280px;
            min-width: 250px;
            margin-bottom: 1rem;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .footer-details-col {
            flex-direction: row;
            gap: clamp(1rem, 3vw, 2.5rem);
            align-items: flex-start;
            flex: 1 1 400px;
        }

        .footer-logo {
            width: clamp(70px, 15vw, 100px);
            height: clamp(70px, 15vw, 100px);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 0.5rem;
            border-radius: 50%;
            box-shadow: 0 2px 12px rgba(0,0,0,0.10);
            overflow: hidden;
            flex-shrink: 0;
        }

        .footer-logo img {
            width: 100%;
            height: auto;
            display: block;
            border-radius: 50%;
        }

        .footer-details {
            flex: 1;
        }

        .footer-details h3 {
            margin-bottom: 0.5rem;
            color: #ffc107;
            font-size: clamp(1rem, 2.5vw, 1.18rem);
            font-weight: 700;
            letter-spacing: 0.5px;
            line-height: 1.3;
        }

        .footer-details p {
            font-size: clamp(0.85rem, 2.2vw, 1rem);
            color: #e0e0e0;
            margin: 0.2rem 0;
            line-height: 1.5;
        }

        .footer-details hr {
            border: none;
            border-top: 1px solid #444;
            margin: 1rem 0 0.7rem 0;
        }

        .footer-sites {
            margin-top: 0.7rem;
        }

        .footer-sites p {
            font-weight: 600;
            color: #fff;
            margin-bottom: 0.5rem;
        }

        .footer-site-list {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .footer-site-list > div {
            margin-bottom: 0.3rem;
        }

        .footer-site-list span {
            display: block;
            color: #e0e0e0;
            font-size: clamp(0.8rem, 2vw, 0.9rem);
            margin-bottom: 0.2rem;
        }

        .footer-site-list a {
            color: #90caf9;
            text-decoration: none;
            font-size: clamp(0.75rem, 2vw, 0.85rem);
            transition: color 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.3em;
            word-break: break-word;
        }

        .footer-site-list a:hover {
            color: #ffc107;
        }

        .footer-links-col h3 {
            color: #ffc107;
            margin-bottom: 0.5rem;
            font-size: clamp(1rem, 2.5vw, 1.13rem);
            font-weight: 700;
        }

        .footer-links {
            list-style: none;
            padding: 0;
            margin: 0 0 1.2rem 0;
            width: 100%;
        }

        .footer-links li {
            margin-bottom: 0.6rem;
        }

        .footer-links a {
            color: #90caf9;
            text-decoration: none;
            font-size: clamp(0.9rem, 2.3vw, 1.03rem);
            transition: color 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.4em;
            word-break: break-word;
        }

        .footer-links a:hover {
            color: #ffc107;
        }

        .footer-social {
            margin-top: 1.5rem;
            text-align: center;
            width: 100%;
        }

        .footer-social p {
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #fff;
            letter-spacing: 0.5px;
            font-size: clamp(0.9rem, 2.2vw, 1rem);
        }

        .social-icons {
            display: flex;
            gap: 1.2rem;
            justify-content: center;
        }

        .social-icons a {
            color: #fff;
            font-size: clamp(1.2rem, 3vw, 1.6rem);
            background: #23272b;
            border-radius: 50%;
            width: clamp(32px, 8vw, 38px);
            height: clamp(32px, 8vw, 38px);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s, color 0.2s;
            box-shadow: 0 2px 8px rgba(0,0,0,0.07);
        }

        .social-icons a:hover {
            background: #ffc107;
            color: #23272b;
        }

        .footer-divider {
            border: none;
            border-top: 1.5px solid #444;
            margin: 1.5rem auto 1rem auto;
            width: 92%;
        }

        .footer-copyright {
            text-align: center;
            font-size: clamp(0.8rem, 2vw, 1rem);
            color: #bbb;
            padding-bottom: clamp(0.5rem, 2vw, 0.7rem);
            line-height: 1.7;
            font-weight: 500;
            padding-left: 1rem;
            padding-right: 1rem;
        }

        .footer-copyright .dev-credit {
            color: #90caf9;
            font-size: clamp(0.75rem, 1.8vw, 0.9rem);
            font-weight: 400;
        }

        .footer-copyright p {
            margin: 0;
        }

        /* Tablet Styles */
        @media screen and (max-width: 1024px) {
            .footer-main {
                gap: 2rem;
            }
            
            .footer-details-col {
                flex-direction: column;
                align-items: center;
                text-align: center;
                gap: 1.5rem;
            }
            
            .footer-logo {
                margin-bottom: 1rem;
            }
        }

        /* Mobile Styles */
        @media screen and (max-width: 768px) {
            .footer-main {
                flex-direction: column;
                align-items: center;
                gap: 2rem;
                padding: 2rem 1rem 1rem 1rem;
            }
            
            .footer-col {
                min-width: 0;
                width: 100%;
                max-width: 400px;
                align-items: center;
                text-align: center;
            }
            
            .footer-details-col {
                flex-direction: column;
                align-items: center;
                gap: 1.5rem;
                width: 100%;
            }
            
            .footer-details {
                text-align: center;
                width: 100%;
            }
            
            .footer-links {
                text-align: center;
            }
            
            .footer-social {
                text-align: center;
                margin-top: 1rem;
            }
        }

        /* Small Mobile Styles */
        @media screen and (max-width: 480px) {
            .footer-main {
                padding: 1.5rem 0.5rem 1rem 0.5rem;
                gap: 1.5rem;
            }
            
            .footer-details h3 {
                font-size: 1rem;
                line-height: 1.2;
            }
            
            .footer-site-list {
                gap: 0.7rem;
            }
            
            .social-icons {
                gap: 1rem;
                flex-wrap: wrap;
            }
            
            .footer-divider {
                margin: 1rem auto;
                width: 95%;
            }
            
            .footer-copyright {
                padding: 0 0.5rem 0.5rem 0.5rem;
            }
        }

        /* Extra Small Mobile Styles */
        @media screen and (max-width: 320px) {
            .footer-main {
                padding: 1rem 0.3rem 0.5rem 0.3rem;
            }
            
            .footer-logo {
                width: 60px;
                height: 60px;
            }
            
            .social-icons {
                gap: 0.8rem;
            }
            
            .footer-site-list a {
                font-size: 0.7rem;
            }
        }
    </style>
</head>
<body>

    <footer>
        <div class="footer-main">
            <!-- Left: Logo and header -->
            <div class="footer-col footer-details-col">
                <div class="footer-logo">
                    <img src="assets/img/images-removebg-preview.png" alt="ISU Logo" />
                </div>
                <div class="footer-details">
                    <h3>ISABELA STATE UNIVERSITY ROXAS CAMPUS</h3>
                    <p>2nd floor Admin Building,</p>
                    <p>Rang-Ayan, Roxas, Isabela</p>
                    <p>Philippines</p>
                    <hr>
                    <div class="footer-sites">
                        <p><strong>Other sites</strong></p>
                        <div class="footer-site-list">
                            <div>
                                <span>Roxas Campus, Mid Site</span>
                                <a href="https://www.google.com/maps?q=3J82+WP5,+Roxas,+Isabela" target="_blank">
                                    <i class="fas fa-map-marker-alt"></i> 3J82+WP5, Roxas, Isabela
                                </a>
                            </div>
                            <div>
                                <span>Roxas, Matusalem Site</span>
                                <a href="https://www.google.com/maps?q=3H7R+RH5,+Roxas,+Isabela" target="_blank">
                                    <i class="fas fa-map-marker-alt"></i> 3H7R+RH5, Roxas, Isabela
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Right: Quick Links -->
            <div class="footer-col footer-links-col">
                <h3>Quick Links</h3>
                <ul class="footer-links">
                    <li><a href="https://isuroxas.edu.ph/" target="_blank"><i class="fas fa-globe"></i> ISU Roxas</a></li>
                    <li><a href="https://isu.edu.ph/" target="_blank"><i class="fas fa-university"></i> ISU Main</a></li>
                    <li><a href="https://www.isujournals.ph/" target="_blank"><i class="fas fa-book"></i> ISU Journals</a></li>
                </ul>
                <div class="footer-social">
                    <p>Follow us</p>
                    <div class="social-icons">
                        <a href="https://web.facebook.com/isurlibrary" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>

                    </div>
                </div>
            </div>
        </div>
        <hr class="footer-divider">
        <div class="footer-copyright">
            <p>&copy; 2025 The ISU Roxas Library. All rights reserved.<br>
                <span class="dev-credit">Developed by Roysen Jinnery Mabini &amp; Franciss Mae Cabebe</span>
            </p>
        </div>
    </footer>
</body>
</html>