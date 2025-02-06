<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Duygula - Modern İletişim Platformu</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #36393f;
            color: #fff;
            line-height: 1.6;
            scroll-snap-type: y mandatory;
            overflow-x: hidden;
            height: 100vh;
        }

        .navbar {
            background: rgba(47, 49, 54, 0.9);
            backdrop-filter: blur(10px);
            padding: 16px 40px;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .nav-brand {
            display: flex;
            align-items: center;
        }

        .nav-brand span {
            font-size: 24px;
            font-weight: 800;
            letter-spacing: -0.5px;
            background: linear-gradient(90deg, #5865F2, #EB459E);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 24px;
        }

        .nav-links a {
            color: #dcddde;
            text-decoration: none;
            font-weight: 500;
            font-size: 15px;
            transition: color 0.2s;
        }

        .nav-links a:hover {
            color: #fff;
        }

        .btn-primary {
            background-color: #5865F2;
            color: #fff !important;
            padding: 10px 20px;
            border-radius: 28px;
            transition: background-color 0.2s, transform 0.2s;
        }

        .btn-primary:hover {
            background-color: #4752C4;
            transform: translateY(-1px);
        }

        .hero {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 120px 20px;
            position: relative;
            overflow: hidden;
            background: linear-gradient(135deg, #313338 0%, #2B2D31 100%);
            scroll-snap-align: start;
            scroll-snap-stop: always;
        }

        .hero::before {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            background: url('assets/images/hero-bg.svg') center/cover;
            opacity: 0.1;
            pointer-events: none;
        }

        .hero h1 {
            font-size: 56px;
            font-weight: 800;
            margin-bottom: 24px;
            line-height: 1.2;
            max-width: 800px;
            background: linear-gradient(90deg, #5865F2, #EB459E);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero p {
            font-size: 20px;
            color: #b9bbbe;
            margin-bottom: 40px;
            max-width: 600px;
        }

        .cta-buttons {
            display: flex;
            gap: 16px;
            margin-top: 20px;
        }

        .btn-download, .btn-web {
            padding: 16px 32px;
            border-radius: 28px;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
        }

        .btn-download {
            background-color: #23272A;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-download:hover {
            background-color: #2C2F33;
            transform: translateY(-1px);
        }

        .btn-web {
            background-color: #5865F2;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-web:hover {
            background-color: #4752C4;
            transform: translateY(-1px);
        }

        .features {
            min-height: 100vh;
            padding: 80px 40px;
            background: #2B2D31;
            position: relative;
            overflow: hidden;
        }

        html {
            scroll-behavior: smooth;
            overflow-x: hidden;
        }

        body {
            scroll-snap-type: y proximity;
            overflow-x: hidden;
        }

        .hero {
            scroll-snap-align: start;
            scroll-snap-stop: always;
        }

        .feature-section {
            max-width: 1200px;
            margin: 0 auto;
            min-height: 100vh;
            display: flex;
            align-items: center;
            gap: 60px;
            padding: 40px 0;
            opacity: 0;
            transform: translateY(60px);
            transition: all 0.8s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
            scroll-snap-align: center;
            scroll-snap-stop: normal;
        }

        .feature-section.visible {
            opacity: 1;
            transform: translateY(0);
        }

        .feature-section:nth-child(odd) .feature-content {
            opacity: 0;
            transform: translateX(-50px);
            transition: all 0.8s cubic-bezier(0.16, 1, 0.3, 1) 0.2s;
        }

        .feature-section:nth-child(even) .feature-content {
            opacity: 0;
            transform: translateX(50px);
            transition: all 0.8s cubic-bezier(0.16, 1, 0.3, 1) 0.2s;
        }

        .feature-section.visible .feature-content {
            opacity: 1;
            transform: translateX(0);
        }

        .feature-image {
            flex: 1;
            position: relative;
            opacity: 0;
            transform: scale(0.9);
            transition: all 0.8s cubic-bezier(0.16, 1, 0.3, 1) 0.4s;
        }

        .feature-image img {
            width: 100%;
            max-width: 500px;
            border-radius: 16px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.2);
            transition: transform 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .feature-image::before {
            content: '';
            position: absolute;
            inset: -20px;
            background: linear-gradient(135deg, rgba(88, 101, 242, 0.1), rgba(235, 69, 158, 0.1));
            border-radius: 24px;
            transform: translateZ(-1px);
            opacity: 0;
            transition: all 0.6s ease;
        }

        .feature-section.visible .feature-image {
            opacity: 1;
            transform: scale(1);
        }

        .feature-section.visible .feature-image::before {
            opacity: 1;
            transform: translateZ(0);
        }

        .feature-image {
            perspective: 1000px;
        }

        .feature-image img {
            transition: transform 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .feature-image:hover img {
            transform: translateZ(20px) rotate3d(1, 1, 0, 2deg);
        }

        .feature-title {
            font-size: 40px;
            font-weight: 800;
            margin-bottom: 24px;
            background: linear-gradient(90deg, #5865F2, #EB459E);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .feature-description {
            font-size: 18px;
            color: #b9bbbe;
            line-height: 1.6;
        }

        .scroll-down {
            position: absolute;
            bottom: 40px;
            left: 50%;
            transform: translateX(-50%);
            color: #fff;
            font-size: 14px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            opacity: 0.7;
            transition: opacity 0.2s;
            cursor: pointer;
            text-decoration: none;
            animation: float 3s ease-in-out infinite;
        }

        .scroll-down:hover {
            opacity: 1;
        }

        .scroll-down i {
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {
                transform: translateY(0);
            }
            40% {
                transform: translateY(-10px);
            }
            60% {
                transform: translateY(-5px);
            }
        }

        @keyframes float {
            0%, 100% {
                transform: translateX(-50%) translateY(0);
            }
            50% {
                transform: translateX(-50%) translateY(-10px);
            }
        }

        @media (max-width: 768px) {
            .navbar {
                padding: 16px 20px;
            }

            .nav-links a:not(.btn-primary) {
                display: none;
            }

            .hero h1 {
                font-size: 40px;
            }

            .hero p {
                font-size: 18px;
            }

            .cta-buttons {
                flex-direction: column;
            }

            .feature-section {
                flex-direction: column !important;
                text-align: center;
                padding: 40px 20px;
            }

            .feature-title {
                font-size: 32px;
            }

            .feature-image img {
                max-width: 100%;
            }
        }

        .download-section {
            background: linear-gradient(135deg, #2B2D31 0%, #1E1F22 100%);
            padding: 120px 40px;
            text-align: center;
        }

        .download-content {
            max-width: 1200px;
            margin: 0 auto;
        }

        .download-content h2 {
            font-size: 48px;
            font-weight: 800;
            margin-bottom: 24px;
            background: linear-gradient(90deg, #5865F2, #EB459E);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .download-content > p {
            font-size: 20px;
            color: #b9bbbe;
            margin-bottom: 60px;
        }

        .download-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 24px;
            margin-top: 40px;
        }

        .download-card {
            background: rgba(47, 49, 54, 0.6);
            border-radius: 16px;
            padding: 32px;
            transition: transform 0.2s, background-color 0.2s;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.1);
        }

        .download-card:hover {
            transform: translateY(-5px);
            background: rgba(47, 49, 54, 0.8);
        }

        .download-card i {
            font-size: 48px;
            margin-bottom: 24px;
            background: linear-gradient(90deg, #5865F2, #EB459E);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .download-card h3 {
            font-size: 24px;
            margin-bottom: 8px;
            color: #fff;
        }

        .download-card p {
            color: #b9bbbe;
            margin-bottom: 24px;
            font-size: 14px;
        }

        .download-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #5865F2;
            color: #fff;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 24px;
            font-weight: 600;
            transition: background-color 0.2s;
        }

        .download-btn:hover {
            background: #4752C4;
        }

        @media (max-width: 768px) {
            .download-section {
                padding: 80px 20px;
            }
            
            .download-content h2 {
                font-size: 36px;
            }
            
            .download-grid {
                grid-template-columns: 1fr;
            }
        }

        .features {
            background: linear-gradient(135deg, #2B2D31, #1E1F22);
            position: relative;
            overflow: hidden;
        }

        .features::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 50% 50%, rgba(88, 101, 242, 0.1), transparent 70%);
            animation: pulse 8s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% {
                opacity: 0.5;
                transform: scale(1);
            }
            50% {
                opacity: 1;
                transform: scale(1.2);
            }
        }

        /* Scroll çubuğunu özelleştirme */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: rgba(0,0,0,0.1);
        }

        ::-webkit-scrollbar-thumb {
            background: rgba(88, 101, 242, 0.5);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: rgba(88, 101, 242, 0.8);
        }

        .section {
            height: 100vh;
            width: 100%;
            position: relative;
            scroll-snap-align: start;
            scroll-snap-stop: always;
        }

        #features {
            height: 100vh;
            background: linear-gradient(135deg, #2B2D31, #1E1F22);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 80px 40px;
            overflow: hidden;
        }

        .features-container {
            max-width: 1200px;
            width: 100%;
            margin: 0 auto;
            text-align: center;
        }

        .feature-title {
            font-size: 48px;
            font-weight: 800;
            margin-bottom: 16px;
            background: linear-gradient(90deg, #5865F2, #EB459E);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .feature-subtitle {
            font-size: 20px;
            color: #b9bbbe;
            margin-bottom: 48px;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
            margin-top: 40px;
        }

        .feature-card {
            background: rgba(47, 49, 54, 0.6);
            border-radius: 16px;
            padding: 24px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.1);
            transition: transform 0.3s ease;
        }

        .feature-icon {
            font-size: 32px;
            margin-bottom: 16px;
        }

        .feature-card h3 {
            font-size: 20px;
            margin-bottom: 12px;
        }

        .feature-card p {
            font-size: 14px;
            line-height: 1.5;
        }

        /* Scroll snap davranışını güçlendirelim */
        html {
            scroll-behavior: smooth;
        }

        body {
            scroll-snap-type: y mandatory;
            overflow-y: auto;
            overflow-x: hidden;
            height: 100vh;
        }

        .section {
            height: 100vh;
            width: 100%;
            scroll-snap-align: start;
            scroll-snap-stop: always;
        }

        /* Download section'ı features'dan ayıralım */
        .download-section {
            height: 100vh;
            background: linear-gradient(135deg, #1E1F22, #2B2D31);
            scroll-snap-align: start;
            scroll-snap-stop: always;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">
            <span>Duygula</span>
        </div>
        <div class="nav-links">
            <a href="#features">Özellikler</a>
            <a href="#download">İndir</a>
            <a href="app/login.php" class="btn-primary">Web Sürümünü Kullan</a>
        </div>
    </nav>

    <section class="hero section">
        <h1>Topluluğunuz İçin Modern İletişim Platformu</h1>
        <p>Arkadaşlarınızla sohbet edin, topluluklar oluşturun, paylaşın.</p>
        <div class="cta-buttons">
            <a href="#download" class="btn-download">
                <i class="fas fa-download"></i>
                Masaüstü Uygulaması
            </a>
            <a href="app/login.php" class="btn-web">
                <i class="fas fa-globe"></i>
                Tarayıcıda Kullan
            </a>
        </div>
        <a href="#features" class="scroll-down">
            <span>Daha fazlasını keşfet</span>
            <i class="fas fa-chevron-down"></i>
        </a>
    </section>

    <section id="features" class="section">
        <div class="features-container">
            <h2 class="feature-title">Neden Duygula?</h2>
            <p class="feature-subtitle">Modern iletişimin tüm ihtiyaçları için tek platform</p>
            
            <div class="features-grid">
                <div class="feature-card">
                    <i class="fas fa-comments feature-icon"></i>
                    <h3>Anlık Mesajlaşma</h3>
                    <p>Hızlı ve güvenli mesajlaşma deneyimi ile arkadaşlarınızla her an iletişimde kalın.</p>
                </div>
                
                <div class="feature-card">
                    <i class="fas fa-video feature-icon"></i>
                    <h3>Görüntülü Görüşme</h3>
                    <p>HD kalitesinde görüntülü görüşmeler ile uzaktaki sevdiklerinizi yanınızda hissedin.</p>
                </div>
                
                <div class="feature-card">
                    <i class="fas fa-users feature-icon"></i>
                    <h3>Topluluklar</h3>
                    <p>İlgi alanlarınıza göre topluluklar oluşturun veya mevcut topluluklara katılın.</p>
                </div>
                
                <div class="feature-card">
                    <i class="fas fa-shield-alt feature-icon"></i>
                    <h3>Güvenlik</h3>
                    <p>Uçtan uca şifreleme ile mesajlarınız ve görüşmeleriniz güvende.</p>
                </div>
                
                <div class="feature-card">
                    <i class="fas fa-share-alt feature-icon"></i>
                    <h3>Kolay Paylaşım</h3>
                    <p>Dosya, fotoğraf ve videoları tek tıkla paylaşın.</p>
                </div>
                
                <div class="feature-card">
                    <i class="fas fa-palette feature-icon"></i>
                    <h3>Özelleştirme</h3>
                    <p>Arayüzü zevkinize göre özelleştirin ve kendi temanızı yaratın.</p>
                </div>
            </div>
        </div>
    </section>

    <section id="download" class="download-section section">
        <div class="download-content">
            <h2>Hazırsanız Başlayalım</h2>
            <p>iOS, Android, Windows, Mac veya Linux için Duygula'yı indirin.</p>
            
            <div class="download-grid">
                <div class="download-card">
                    <i class="fab fa-windows"></i>
                    <h3>Windows</h3>
                    <p>Windows 8.1+</p>
                    <a href="#" class="download-btn">İndir</a>
                </div>
                
                <div class="download-card">
                    <i class="fab fa-apple"></i>
                    <h3>macOS</h3>
                    <p>macOS 10.13+</p>
                    <a href="#" class="download-btn">İndir</a>
                </div>
                
                <div class="download-card">
                    <i class="fab fa-linux"></i>
                    <h3>Linux</h3>
                    <p>Ubuntu, Debian...</p>
                    <a href="#" class="download-btn">İndir</a>
                </div>
                
            </div>
        </div>
    </section>

    <script>
        window.onload = () => {
            window.scrollTo(0, 0);
        }

        const sections = document.querySelectorAll('.section');
        let lastScrollTime = 0;
        const scrollCooldown = 1000; // 1 saniye bekleme süresi

        window.addEventListener('wheel', (e) => {
            const now = Date.now();
            if (now - lastScrollTime < scrollCooldown) return;
            
            const direction = e.deltaY > 0 ? 1 : -1;
            const currentSection = Array.from(sections).find(section => {
                const rect = section.getBoundingClientRect();
                return Math.abs(rect.top) < window.innerHeight / 2;
            });
            
            if (currentSection) {
                const currentIndex = Array.from(sections).indexOf(currentSection);
                const nextIndex = currentIndex + direction;
                
                if (nextIndex >= 0 && nextIndex < sections.length) {
                    lastScrollTime = now;
                    sections[nextIndex].scrollIntoView({ behavior: 'smooth' });
                }
            }
        });

        // Smooth scroll için
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const targetId = this.getAttribute('href');
                const targetElement = document.querySelector(targetId);
                if (targetElement) {
                    lastScrollTime = Date.now();
                    targetElement.scrollIntoView({ behavior: 'smooth' });
                }
            });
        });
    </script>
</body>
</html> 