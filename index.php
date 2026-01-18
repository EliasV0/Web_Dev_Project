<?php
// Start session
session_start();
require_once 'functions/check_generic.php';
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>University Campus</title>
    
    <!-- Map CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css?v=7">
</head>
<body>

    <header>
        <h1><a href="index.php" class="home-link" style="color: white; text-decoration: none;">University Portal</a></h1>
        
        <div class="nav-links">
            <?php 
            // Show links based on login status
            if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true): 
            ?>
                
                <span style="color: white; font-weight: 500; margin-right: 10px;">
                    Καλωσήρθατε, <?php echo e($_SESSION['username']); ?>
                </span>
 
                <a href="utilities/dashboard.php" class="btn-dashboard"> Dashboard
                </a>

                <a href="sign-in-out-up/logout.php">Αποσύνδεση</a>

            <?php else: ?>
                
                <a href="sign-in-out-up/login.php">Σύνδεση</a>
                <a href="sign-in-out-up/signup.php">Εγγραφή</a>

            <?php endif; ?>
        </div>
    </header>

    <div class="container">
        
        <!-- Hero section -->
        <div class="hero-section">
            <div class="hero-image">
                <img src="pictures/campus.jpg" alt="University Campus">
            </div>
            
            <div class="hero-text">
                <h2>Καλωσήρθατε στο Campus μας</h2>
                <p>Το πανεπιστήμιό μας προσφέρει σύγχρονες εγκαταστάσεις, βιβλιοθήκες και εργαστήρια τελευταίας τεχνολογίας. Βρισκόμαστε στο κέντρο της καινοτομίας.</p>
                <ul class="features-list">
                    <li> Σύγχρονα Αμφιθέατρα</li>
                    <li> Βιβλιοθήκη</li>
                    <li> Εργαστήρια Πληροφορικής</li>
                </ul>
            </div>
        </div>

        <hr class="divider">

        <!-- Map -->
        <div class="map-section">
            <h2>Η Τοποθεσία μας</h2>
            <div id="map"></div>
        </div>

    </div>

    <footer>
        <p>&copy; 2025 University Portal Project</p>
    </footer>

    <!-- Map Script -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        var lat = 35.34186779204526;
        var lng = 25.133446768293094;

        // Init map
        var map = L.map('map').setView([lat, lng], 15);

        // Load tiles
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap'
        }).addTo(map);

        // Add marker
        var marker = L.marker([lat, lng]).addTo(map);
        marker.bindPopup("<b>University Campus</b><br>Main Building.").openPopup();
    </script>

</body>
</html>