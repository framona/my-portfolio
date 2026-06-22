<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../Images/profilepic.jpg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="authStyle.css">
    <script src="../Script.js" defer></script>
</head>
<body>
<nav class="navbar">
    <div class="brand-title">WorkoutPro</div>
    <a href="#" class="toggle-button">
        <span class="bar"></span>
        <span class="bar"></span>
        <span class="bar"></span>
    </a>
    <div class="navbar-links">
        <ul>
            <!-- Everyone sees Home -->
            <li><a href="../index.php">Home</a></li>

            <?php if (!isset($_SESSION['user_id'])): ?>
                <!-- Guest: only Register and Login -->
                <li><a href="../auth/register.php">Register</a></li>
                <li><a href="../auth/login.php">Login</a></li>

            <?php else: ?>
                <!-- Logged-in user -->
                <?php if (!empty($_SESSION['is_trainer']) && $_SESSION['is_trainer'] == 1): ?>
                    <!-- Trainer links -->
                    <li><a href="../trainer/dashboard.php">My exercises</a></li>
                    <li><a href="../trainer/add_exercise.php">Add exercise</a></li>
                <?php else: ?>
                    <!-- Regular user links -->
                    <li><a href="../workout/workouts.php">Browse workouts</a></li>
                    <li><a href="../workout/view_workout.php">My workouts</a></li>
                    <li><a href="../trainer/add_workout.php">Add workout plan</a></li>
                <?php endif; ?>

                <!-- Common links for any logged-in user -->
                <li><a href="../profile/profile.php">Profile</a></li>
                <li><a href="../auth/logout.php">Logout</a></li>
            <?php endif; ?>

            <?php if (!empty($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <!-- Admin-only link -->
                <li><a href="../admin/panel.php">Admin</a></li>
            <?php endif; ?>
        </ul>
    </div>
</nav>
    <div class="box">
        <h2 class="text-center">Login</h2>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
        <?php endif; ?>

        <form action="../actions/process_login.php" method="POST" autocomplete="off">
            <div>
                <label for="email" class="form-label">Email (Username)</label>
                <input type="email" name="email" id="email" class="form-control" required autofocus />
            </div>

            <div>
                <label for="password" class="form-label">Password</label>
                <input type="password" name="password" id="password" class="form-control" required />
            </div><br>

            <button type="submit" class="btn btn-danger w-100">Login</button>
        </form>

        <p class="mt-3 text-center">
            <a href="../auth/forgot_password.php" class="text-danger">Forgot your password?</a><br />
            <a href="../auth/register.php" class="text-danger">Create a new account</a>
        </p>
    </div>
</body>
</html>
