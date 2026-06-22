<?php
session_start();
require_once __DIR__ . '/../../src/config/database.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="assets/img/paw.png">
    <title>Register | PetRegistry</title>
    <link rel="stylesheet" href="../assets/css/authStyle.css">
    <script src="../assets/js/Script.js" defer></script>
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
            <li><a href="../index.php">Home</a></li>

            <?php if (!isset($_SESSION['user_id'])): ?>
                <li><a href="../auth/login.php">Login</a></li>
                <li><a href="../auth/register.php">Register</a></li>
            <?php else: ?>

                <?php if ($_SESSION['role'] === 'vet'): ?>
                    <li><a href="../vet/dashboard.php">Dashboard</a></li>
                    <li><a href="../vet/vet_appointments.php">Appointments</a></li>
                    <li><a href="../vet/vet_messages.php">Messages</a></li>

                <?php elseif ($_SESSION['role'] === 'admin'): ?>
                    <li><a href="../admin/dashboard.php">Admin panel</a></li>

                <?php else: ?>
                    <li><a href="../user/dashboard.php">Dashboard</a></li>
                    <li><a href="../user/pets.php">My Pets</a></li>
                    <li><a href="../user/appointments.php">Appointments</a></li>
                    <li><a href="../user/messages.php">Messages</a></li>
                <?php endif; ?>

                <li><a href="../auth/logout.php">Logout</a></li>
            <?php endif; ?>
        </ul>
    </div>
</nav>
<div class="register-wrapper">
    <div class="register-illustration d-none d-lg-flex">
        <div class="register-illustration-inner">
            <h2>Welcome to PetRegistry</h2>
            <p>Register as a pet owner, add your first pet, and choose a trusted veterinarian.</p>
            <ul>
                <li>🐾 Secure pet profiles</li>
                <li>📅 Vet appointments & reminders</li>
                <li>🔐 Email activation & QR identification</li>
            </ul>
        </div>
    </div>

    <div class="box register-box">
        <h2 class="text-center mb-3">Create Your Account</h2>
        <p class="text-center text-muted small mb-4">
            Your email will be used as your username. Please fill in your information and your first pet’s details.
        </p>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <form action="../actions/process_register.php" method="POST" novalidate>
            <!-- USER INFORMATION -->
            <h4 class="section-title">Account information</h4>

            <div class="mb-3">
                <label class="form-label">Email (also used as username)</label>
                <div class="input-with-icon">
                    <span>📧</span>
                    <input type="email"
                           name="email"
                           class="form-control"
                           required
                           autocomplete="email"
                           placeholder="you@example.com">
                </div>
                <small class="form-text text-muted">
                    Your email must be unique and will also be used as your username.
                </small>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">First Name</label>
                    <div class="input-with-icon">
                        <span>👤</span>
                        <input type="text"
                               name="first_name"
                               class="form-control"
                               required
                               autocomplete="given-name"
                               placeholder="John">
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Last Name</label>
                    <div class="input-with-icon">
                        <span>👤</span>
                        <input type="text"
                               name="last_name"
                               class="form-control"
                               required
                               autocomplete="family-name"
                               placeholder="Doe">
                    </div>
                </div>
            </div>

            <div class="mb-3 mt-3">
                <label class="form-label">Phone</label>
                <div class="input-with-icon">
                    <span>📞</span>
                    <input type="text"
                           name="phone"
                           class="form-control"
                           required
                           autocomplete="tel"
                           placeholder="+36 30 123 4567">
                </div>
            </div>

            <div class="row g-3 mt-1">
                <div class="col-md-6">
                    <label class="form-label">Password</label>
                    <div class="input-with-icon">
                        <span>🔒</span>
                        <input type="password"
                               name="password"
                               class="form-control"
                               required
                               minlength="8"
                               placeholder="At least 8 characters">
                    </div>
                    <small class="form-text text-muted"></small>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Confirm Password</label>
                    <div class="input-with-icon">
                        <span>🔒</span>
                        <input type="password"
                               name="confirm_password"
                               class="form-control"
                               required
                               minlength="8"
                               placeholder="Repeat your password">
                    </div>
                </div>
            </div>

            <!-- PET INFORMATION -->
            <hr class="my-4">

            <h4 class="section-title">First pet information</h4>

            <div class="mb-3">
                <label class="form-label">Pet Name</label>
                <div class="input-with-icon">
                    <span>🐾</span>
                    <input type="text"
                           name="pet_name"
                           class="form-control"
                           required
                           placeholder="Buddy">
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Species</label>
                    <select name="species" class="form-control" required>
                        <option value="">Select species</option>
                        <option value="Dog">Dog</option>
                        <option value="Cat">Cat</option>
                        <option value="Bunny">Bunny</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Breed</label>
                    <input type="text"
                           name="breed"
                           class="form-control"
                           required
                           placeholder="Golden Retriever">
                </div>
            </div>

            <div class="mb-3 mt-3">
                <label class="form-label">Birth Year</label>
                <input type="number"
                       name="birth_year"
                       class="form-control"
                       min="1990"
                       max="<?php echo date('Y'); ?>"
                       required
                       placeholder="2020">
            </div>

            <!-- VET SELECTION -->
            <hr class="my-4">

            <h4 class="section-title">Choose a veterinarian</h4>

            <div class="mb-3">
                <label class="form-label">Veterinarian</label>
                <select name="vet_id" class="form-control" required>
                    <option value="">Select veterinarian</option>
                    <?php
                    try {
                        $stmt = $pdo->query("SELECT id, first_name, last_name FROM users WHERE role = 'vet'");
                        while ($vet = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            $label = trim(($vet['first_name'] ?? '') . ' ' . ($vet['last_name'] ?? ''));
                            if (!empty($vet['clinic_name'])) {
                                $label .= ' - ' . $vet['clinic_name'];
                            }
                            echo '<option value="' . (int)$vet['id'] . '">' . htmlspecialchars($label) . '</option>';
                        }
                    } catch (PDOException $e) {
                        echo '<option disabled>Error loading veterinarians</option>';
                    }
                    ?>
                </select>
                <small class="form-text text-muted">
                    You can change your veterinarian later if needed.
                </small>
            </div>

            <p class="text-center text-muted small mt-3 mb-0">
                After registration, you will receive an activation email. Please click the link in that email to activate your account.
            </p>
            <button type="submit" class="btn-success w-100 mt-3">
                Create Account
            </button>
        </form>
    </div>
</div>

</body>
</html>
