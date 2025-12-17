<?php
session_start();
require_once __DIR__ . '/db.php';

// Authorization Check
if (!isset($_SESSION['reset_authorized_for_user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['reset_authorized_for_user_id'];
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($password)) {
        $errors['password'] = 'New password cannot be empty.';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters long.';
    } elseif ($password !== $confirm_password) {
        $errors['confirm_password'] = 'Passwords do not match.';
    }

    // Disallow using the same (recent/current) password
    if (empty($errors)) {
        try {
            $stmtCur = $pdo->prepare('SELECT password FROM users WHERE id = ?');
            $stmtCur->execute([$user_id]);
            $rowCur = $stmtCur->fetch(PDO::FETCH_ASSOC);
            if ($rowCur && !empty($rowCur['password']) && password_verify($password, $rowCur['password'])) {
                $errors['password'] = 'New password must be different from your current password.';
            }
        } catch (PDOException $e) {
            $errors['form'] = 'A database error occurred.';
        }
    }

    if (empty($errors)) {
        // Hash the new password and update the database
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');

        try {
            $stmt->execute([$passwordHash, $user_id]);
            // Clean up session and set success message for login page
            unset($_SESSION['reset_authorized_for_user_id']);
            $_SESSION['success_message'] = 'Successfully Change Password';
            header('Location: login.php');
            exit();
        } catch (PDOException $e) {
            $errors['form'] = 'A database error occurred.';
        }
    }
    
    if (!empty($errors['confirm_password'])) {
        $errors['form'] = 'Mismatch Password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LiquorLink - Change Password</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        #password-strength-status { margin-top: 5px; height: 10px; }
        .strength-weak { background-color: #e74c3c; }
        .strength-medium { background-color: #f39c12; }
        .strength-strong { background-color: #2ecc71; }
    </style>
</head>
<body>
    <div class="container full-width">
        <!-- Prospect Name Above Header -->
        <div class="prospect-name">LiquorLink - Bar Management System</div>
        
        <header class="elegant-header">
            <div class="logo"><h1>LiquorLink</h1></div>
            <nav><ul><li><a href="../index.html">HOME</a></li></ul></nav>
            <div class="header-buttons">
                <a href="../index.html" class="btn text-btn">Home</a>
            </div>
        </header>

        <main>
            <section class="auth-section">
                <div class="auth-container">
                    <h2 class="auth-title">Change Password</h2>
                    <?php if(isset($errors['form'])) { echo '<div class="error-text" style="text-align: center; margin-bottom: 1rem; font-weight: bold;">' . htmlspecialchars($errors['form']) . '</div>'; } ?>
                    <form class="elegant-form" action="" method="post" novalidate>
                        <div class="form-section">
                            <div class="form-group">
                                <label for="new_password">Enter Password</label>
                                <div class="password-wrapper">
                                    <input type="password" id="new_password" name="new_password" required>
                                    <button type="button" class="password-toggle" id="toggleNewPassword">👁️</button>
                                </div>
                                <div id="password-strength-status"></div>
                                <?php if(isset($errors['password'])) { echo '<div class="error-text">' . htmlspecialchars($errors['password']) . '</div>'; } ?>
                            </div>
                            <div class="form-group">
                                <label for="confirm_password">Re-enter Password</label>
                                <div class="password-wrapper">
                                    <input type="password" id="confirm_password" name="confirm_password" required>
                                    <button type="button" class="password-toggle" id="toggleConfirmNewPassword">👁️</button>
                                </div>
                                <?php if(isset($errors['confirm_password'])) { echo '<div class="error-text">' . htmlspecialchars($errors['confirm_password']) . '</div>'; } ?>
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn primary-btn">Set New Password</button>
                        </div>
                    </form>
                </div>
            </section>
        </main>

        <footer>
            <p>&copy; 2023 LiquorLink Bar Management System. All rights reserved.</p>
        </footer>
    </div>
    <script>
        // Basic password strength meter with text labels
        const passwordEl = document.getElementById('new_password');
        const strengthStatusEl = document.getElementById('password-strength-status');
        const formEl = document.querySelector('form.elegant-form');
        const confirmEl = document.getElementById('confirm_password');
        const toggleNewBtn = document.getElementById('toggleNewPassword');
        const toggleConfirmBtn = document.getElementById('toggleConfirmNewPassword');

        function toggleFieldType(inputEl, btnEl) {
            if (!inputEl || !btnEl) return;
            const type = inputEl.getAttribute('type') === 'password' ? 'text' : 'password';
            inputEl.setAttribute('type', type);
            btnEl.textContent = type === 'password' ? '👁️' : '👁️‍🗨️';
        }

        if (toggleNewBtn && passwordEl) {
            toggleNewBtn.addEventListener('click', function () { toggleFieldType(passwordEl, toggleNewBtn); });
        }
        if (toggleConfirmBtn && confirmEl) {
            toggleConfirmBtn.addEventListener('click', function () { toggleFieldType(confirmEl, toggleConfirmBtn); });
        }

        if (passwordEl && strengthStatusEl) {
            passwordEl.addEventListener('keyup', function () {
                const val = passwordEl.value;
                let strength = 0;
                if (val.length >= 8) strength++;
                if (val.match(/[a-z]/) && val.match(/[A-Z]/)) strength++;
                if (val.match(/[0-9]/)) strength++;
                if (val.match(/[^a-zA-Z0-9]/)) strength++;

                strengthStatusEl.className = '';
                strengthStatusEl.innerHTML = '';
                
                if (val.length > 0) {
                    // Create strength bar
                    const strengthBar = document.createElement('div');
                    strengthBar.style.height = '10px';
                    strengthBar.style.marginTop = '5px';
                    
                    // Create strength text
                    const strengthText = document.createElement('div');
                    strengthText.style.marginTop = '3px';
                    strengthText.style.fontSize = '0.85rem';
                    strengthText.style.fontWeight = '500';
                    strengthText.style.textAlign = 'center';
                    strengthText.style.textTransform = 'uppercase';
                    strengthText.style.letterSpacing = '0.5px';
                    
                    if (strength <= 2) {
                        strengthBar.style.backgroundColor = '#e74c3c';
                        strengthText.textContent = 'Weak';
                        strengthText.style.color = '#e74c3c';
                    } else if (strength === 3) {
                        strengthBar.style.backgroundColor = '#f39c12';
                        strengthText.textContent = 'Medium';
                        strengthText.style.color = '#f39c12';
                    } else if (strength >= 4) {
                        strengthBar.style.backgroundColor = '#2ecc71';
                        strengthText.textContent = 'Strong';
                        strengthText.style.color = '#2ecc71';
                    }
                    
                    strengthStatusEl.appendChild(strengthBar);
                    strengthStatusEl.appendChild(strengthText);
                }
            });
        }

        // Custom client-side validation to replace native tooltip
        if (formEl && passwordEl && confirmEl) {
            formEl.addEventListener('submit', function (e) {
                // Clear existing inline errors
                document.querySelectorAll('.error-text.client').forEach(el => el.remove());

                let valid = true;

                const addError = (inputEl, msg) => {
                    const err = document.createElement('div');
                    err.className = 'error-text client';
                    err.textContent = msg;
                    inputEl.insertAdjacentElement('afterend', err);
                };

                const pwd = passwordEl.value.trim();
                const re  = confirmEl.value.trim();

                if (!pwd) {
                    valid = false;
                    addError(passwordEl, 'New password cannot be empty.');
                } else if (pwd.length < 8) {
                    valid = false;
                    addError(passwordEl, 'Password must be at least 8 characters long.');
                }

                if (!re) {
                    valid = false;
                    addError(confirmEl, 'Please re-enter your password.');
                } else if (pwd && re && pwd !== re) {
                    valid = false;
                    addError(confirmEl, 'Passwords do not match.');
                }

                if (!valid) {
                    e.preventDefault();
                    e.stopPropagation();
                }
            });
        }
    </script>
</body>
</html>
