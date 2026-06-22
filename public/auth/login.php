<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login | PetRegistry</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
            <h2>Welcome back!</h2>
            <p>Log in to access your pets, appointments and notifications.</p>
            <ul>
                <li>🐾 Manage pet profiles</li>
                <li>📅 See your scheduled vet visits</li>
                <li>🔐 Secure login with bcrypt</li>
            </ul>
        </div>
    </div>

    <!-- LOGIN FORM -->
    <div class="box register-box">
        <h2 class="text-center mb-3">Log In</h2>

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

        <form action="../actions/process_login.php" method="POST" autocomplete="off">
            <div class="mb-3">
                <label for="email" class="form-label">Email (Username)</label>
                <div class="input-with-icon">
                    <span>📧</span>
                    <input type="email"
                           name="email"
                           id="email"
                           class="form-control"
                           required
                           placeholder="you@example.com">
                </div>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <div class="input-with-icon">
                    <span>🔒</span>
                    <input type="password"
                           name="password"
                           id="password"
                           class="form-control"
                           required
                           placeholder="Your password">
                </div>
            </div>

            <button type="submit" class="btn-success w-100 mt-2">Login</button>

            <p class="mt-3 text-center">
                <a href="forgot_password.php" class="text-success">Forgot your password?</a><br>
                <a href="register.php" class="text-success">Create a new account</a>
            </p>
        </form>
    </div>
</div>

</body>
</html>
