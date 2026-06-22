<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="assets/img/paw.png">
    <title>PetRegistry</title>
    <link rel="stylesheet" href="assets/css/index.css">
    <script src="assets/js/Script.js" defer></script>
</head>
<body>
<nav class="navbar">
    <div class="brand-title">PetRegistry</div>

    <a href="#" class="toggle-button">
        <span class="bar"></span>
        <span class="bar"></span>
        <span class="bar"></span>
    </a>

    <div class="navbar-links">
        <ul>
            <li><a href="../public/index.php">Home</a></li>

            <?php if (!isset($_SESSION['user_id'])): ?>
                <li><a href="../public/auth/login.php">Login</a></li>
                <li><a href="../public/auth/register.php">Register</a></li>
            <?php else: ?>

                <?php if ($_SESSION['role'] === 'vet'): ?>
                    <li><a href="../public/vet/dashboard.php">Dashboard</a></li>
                    <li><a href="../public/vet/vet_appointments.php">Appointments</a></li>
                    <li><a href="../public/vet/vet_messages.php">Messages</a></li>

                <?php elseif ($_SESSION['role'] === 'admin'): ?>
                    <li><a href="../public/admin/dashboard.php">Admin panel</a></li>

                <?php else: ?>
                    <li><a href="../public/user/dashboard.php">Dashboard</a></li>
                    <li><a href="../public/user/pets.php">My Pets</a></li>
                    <li><a href="../public/user/appointments.php">Appointments</a></li>
                    <li><a href="../public/user/messages.php">Messages</a></li>
                <?php endif; ?>

                <li><a href="../public/auth/logout.php">Logout</a></li>
            <?php endif; ?>
        </ul>
    </div>
</nav>
<section class="hero">
    <div class="hero-overlay"></div>
    <div class="hero-inner">
        <div class="hero-text">
            <h1>Keep your pet’s information safe, smart, and always with you.</h1>
            <p>Register your pet, choose a veterinarian, manage medical records, and generate a QR code for quick identification.</p>
            <div class="hero-actions">
                <a href="../public/auth/register.php" class="btn-primary">Register Now</a>
                <a href="#features" class="btn-ghost">Learn More</a>
            </div>
        </div>
        <div class="hero-side-card">
            <h2>Why PetRegistry?</h2>
            <ul>
                <li>✔ Centralized pet profiles</li>
                <li>✔ Vet-approved medical records</li>
                <li>✔ QR-code identification for lost pets</li>
                <li>✔ Appointment scheduling & reminders</li>
            </ul>
        </div>
    </div>
</section>
<section class="categories" id="features">
    <h2>MAIN SYSTEM FEATURES</h2>
    <p class="section-subtitle">
        Everything you need to manage your pet’s life in one place.
    </p>
    <div class="category-cards">

        <div class="card">
            <div class="card-icon">🐾</div>
            <img src= "assets/img/cute-dog-consultation.jpg" alt="Pet Management">
            <div class="card-body">
                <h3>Pet Management System</h3>
                <p>Create and edit detailed profiles for your pets, including breed, age, medical notes, and assigned veterinarian.</p>
            </div>
        </div>

        <div class="card">
            <div class="card-icon">📅</div>
            <img src="assets/img/veterinarian-taking-care-pet-dog.jpg" alt="Appointments">
            <div class="card-body">
                <h3>Appointment Scheduling</h3>
                <p>Book, manage, and cancel vet appointments, with clear overviews for both owners and veterinarians.</p>
            </div>
        </div>

        <div class="card">
            <div class="card-icon">🔐</div>
            <img src="assets/img/cute-little-dog-isolated-yellow.jpg" alt="Notifications">
            <div class="card-body">
                <h3>Notification & Security</h3>
                <p>Secure login, activation links, email reminders for visits, and QR codes for quick pet identification.</p>
            </div>
        </div>

    </div>
</section>

<section class="about">
    <div class="about-inner">
        <div class="about-image">
            <img src="assets/img/petTeam.jpg" alt="Pet-Friendly Team">
        </div>
        <div class="about-text">
            <h2>About the System</h2>
            <p class="quote">
                “PetRegistry aims to give every pet owner and veterinarian easy access to all essential pet information.”
            </p>
            <p>
                The platform is designed as a secure electronic registry for household pets. Each owner can register
                their pet, select a veterinarian, and maintain a full digital history of treatments, vaccinations,
                appointments and notes.
            </p>
            <p>
                Veterinarians can update medical records, manage appointment slots, and communicate important changes.
                Administrators oversee accounts and ensure safe access, so the system remains reliable and trustworthy.
            </p>
            <div class="about-highlights">
                <div class="highlight-item">
                    <span>💾</span>
                    <div>
                        <h4>Centralized Records</h4>
                        <p>All pet data stored in one secure database using modern web technologies.</p>
                    </div>
                </div>
                <div class="highlight-item">
                    <span>📧</span>
                    <div>
                        <h4>Smart Notifications</h4>
                        <p>Email-based reminders for upcoming treatments and password recovery.</p>
                    </div>
                </div>
                <div class="highlight-item">
                    <span>🧩</span>
                    <div>
                        <h4>Role-Based Access</h4>
                        <p>Different views and permissions for owners, vets and administrators.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<section class="vets">
    <h2>Meet Our Veterinarians</h2>
    <p class="section-subtitle">
        Trusted professionals who help keep your pets healthy and safe.
    </p>

    <div class="vet-cards">

        <div class="vet-card">
            <div class="vet-image-wrapper">
                <img src="assets/img/vet.jpg" alt="Dr Emily Carter">
            </div>
            <h3>Dr. Emily Carter</h3>
            <p class="vet-role">Canine & Feline Specialist</p>
            <p class="vet-desc">
                Over 10 years of experience in small animal practice, focused on preventive care and diagnostics.
            </p>
            <span class="vet-badge">10+ years experience</span>
        </div>

        <div class="vet-card">
            <div class="vet-image-wrapper">
                <img src="assets/img/female-veterinarian-examining-dog-s-mouth-table-clinic.jpg" alt="Dr Mark Reynolds">
            </div>
            <h3>Dr. Mark Reynolds</h3>
            <p class="vet-role">Exotic & Small Mammal Vet</p>
            <p class="vet-desc">
                Passionate about exotic pets, surgery and using digital tools to improve treatment outcomes.
            </p>
            <span class="vet-badge">Exotic animals</span>
        </div>

        <div class="vet-card">
            <div class="vet-image-wrapper">
                <img src="assets/img/close-up-veterinarian-taking-care-dog.jpg" alt="Dr Olivia Martinez">
            </div>
            <h3>Dr. Olivia Martinez</h3>
            <p class="vet-role">Nutrition & Chronic Care</p>
            <p class="vet-desc">
                Specializes in long-term care, nutrition plans and managing chronic conditions in pets.
            </p>
            <span class="vet-badge">Chronic care</span>
        </div>

    </div>
</section>
<footer>
    <div id="appModal" class="app-modal">
    <div class="app-modal-content">
        <span class="close-modal">&times;</span>
        <div class="app-modal-body">
            <div class="app-icon-large">🐾</div>
            <h3>Get the PetRegistry App!</h3>
            <p>For the best experience, including instant SOS notifications and easy QR scanning, download our mobile application.</p>
            <a href="assets/downloads/petregistry.apk" class="btn-primary">Download for Android</a><br><br><br>
            <a href="assets/downloads/petregistry.apk" class="btn-primary">Download for iOS</a>
            <p class="modal-secondary-text">Fast, secure, and always in your pocket.</p>
        </div>
    </div>
</div>
    <div class="footer-icons">
        <a href="https://www.instagram.com" target="_blank">
            <img src="assets/img/instagram.png" class="icon">
        </a>
        <a href="mailto:info@petregistry.com">
            <img src="assets/img/email.png" class="icon">
        </a>
        <a href="https://www.facebook.com" target="_blank">
            <img src="assets/img/facebook.png" class="icon">
        </a>

        <p>© 2025 PetRegistry — All rights reserved.</p>
    </div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('appModal');
    const closeBtn = document.querySelector('.close-modal');

    const isMobile = /iPhone|iPad|iPod|Android/i.test(navigator.userAgent);
    const modalShown = sessionStorage.getItem('appModalShown');

    if (isMobile && !modalShown) {
        modal.style.display = 'block';
        sessionStorage.setItem('appModalShown', 'true');
    }

    closeBtn.onclick = function() {
        modal.style.display = 'none';
    }

    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }
});
</script>
</body>
</html>

