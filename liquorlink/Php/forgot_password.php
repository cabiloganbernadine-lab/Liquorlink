<?php
session_start();
require_once __DIR__ . '/db.php';

$errors = [];
$stage = 1; // Stage 1: Enter username. Stage 2: Answer questions.
$user = null;
$username = '';

// If this is a fresh GET visit (not returning from an error), reset any preserved state
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $hasError = isset($_SESSION['errors']) && !empty($_SESSION['errors']);
    if (!$hasError) {
        unset($_SESSION['forgot_password_username']);
    }
}

// Check if we need to preserve stage 2 state after error
if (isset($_SESSION['forgot_password_username'])) {
    $username = $_SESSION['forgot_password_username'];
    $stmt = $pdo->prepare('SELECT id, username, security_q1, security_q2, security_q3 FROM users WHERE username = :username OR id_number = :username');
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch();
    if ($user) {
        $stage = 2;
        // The session variable is intentionally not unset here. It is required by
        // reset_password.php and must persist until the process is complete.
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['username'])) {
        // --- Stage 1 Submission ---
        $username = trim($_POST['username']);
        if (empty($username)) {
            $errors['username'] = 'Please enter your username or ID number.';
        } else {
            $stmt = $pdo->prepare('SELECT id, username, security_q1, security_q2, security_q3 FROM users WHERE username = :username OR id_number = :username');
            $stmt->execute(['username' => $username]);
            $user = $stmt->fetch();

            if ($user) {
                $stage = 2; // User found, proceed to stage 2
                $_SESSION['forgot_password_username'] = $user['username']; // Store username for the next step
            } else {
                $errors['username'] = 'User not found.';
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <base href="./">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LiquorLink - Forgot Password</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
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
                <a href="login.php" class="btn text-btn">LOG-IN</a>
            </div>
        </header>

        <main>
            <section class="auth-section">
                <div class="auth-container">
                    <h2 class="auth-title">Account Recovery</h2>

                    <?php if ($stage === 1): ?>
                        <p>Please enter your username or ID number to begin the recovery process.</p>
                        <form id="forgot-password-form-1" class="elegant-form" action="forgot_password.php" method="post">
                            <div class="form-section">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="username">Username or ID Number</label>
                                        <input type="text" id="username" name="username" required minlength="3" maxlength="80" value="<?php echo htmlspecialchars($username); ?>">
                                        <?php if(isset($errors['username'])) { echo '<div class="error-text">' . htmlspecialchars($errors['username']) . '</div>'; } ?>
                                    </div>
                                </div>
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="btn primary-btn">Find Account</button>
                            </div>
                        </form>
                    <?php else: // Stage 2 
                        // Get full question text from value
                        $q1_text = $user['security_q1'];
                        $q2_text = $user['security_q2'];
                        $q3_text = $user['security_q3'];
                        
                        $question_map = [
                            'best_friend_elementary' => 'Who is your best friend in Elementary?',
                            'favorite_pet_name' => 'What is the name of your favorite pet?',
                            'favorite_teacher_hs' => 'Who is your favorite teacher in high school?'
                        ];
                        
                        $q1_display = isset($question_map[$q1_text]) ? $question_map[$q1_text] : $q1_text;
                        $q2_display = isset($question_map[$q2_text]) ? $question_map[$q2_text] : $q2_text;
                        $q3_display = isset($question_map[$q3_text]) ? $question_map[$q3_text] : $q3_text;
                    ?>
                        <p style="margin-bottom: 1.5rem; text-align: center;">Please answer the following security questions for user <strong><?php echo htmlspecialchars($user['username']); ?></strong>.</p>
                        <?php 
                        if (isset($_SESSION['errors']['form'])) {
                            echo '<div class="error-text" style="margin-bottom: 1rem;">' . htmlspecialchars($_SESSION['errors']['form']) . '</div>';
                            unset($_SESSION['errors']['form']);
                        }
                        ?>
                        <form id="forgot-password-form-2" class="elegant-form forgot-password-form-2" action="reset_password.php" method="post">
                            <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user['id']); ?>">
                            <input type="hidden" name="security_q1" value="<?php echo htmlspecialchars($q1_text); ?>">
                            <input type="hidden" name="security_q2" value="<?php echo htmlspecialchars($q2_text); ?>">
                            <input type="hidden" name="security_q3" value="<?php echo htmlspecialchars($q3_text); ?>">
                            
                            <div class="step-form-grid step3-grid">
                                <!-- Question 1 -->
                                <div class="qa-row span-all">
                                    <div class="form-group">
                                        <label for="security_q1_select">Choose the Following Question: <span class="required-ast">*</span></label>
                                        <select id="security_q1_select" name="security_q1_display" class="question-select">
                                            <?php foreach ($question_map as $key => $question): ?>
                                                <option value="<?php echo htmlspecialchars($key); ?>" <?php echo ($key === $q1_text) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($question); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="security_a1_ans">Your Answer: <span class="required-ast">*</span></label>
                                        <div class="password-wrapper">
                                            <input type="password" id="security_a1_ans" name="security_a1" placeholder="Your Answer" class="answer-input" minlength="3" maxlength="50">
                                            <button type="button" class="password-toggle" id="toggle_fp_a1">👁️</button>
                                        </div>
                                        <div class="error-text" id="error-security_a1" style="display: none;"></div>
                                    </div>
                                    <div class="form-group">
                                        <label for="security_a1_re">Re-enter answer: <span class="required-ast">*</span></label>
                                        <div class="password-wrapper">
                                            <input type="password" id="security_a1_re" name="security_a1_re" placeholder="Re-enter answer" class="answer-input" minlength="3" maxlength="50">
                                            <button type="button" class="password-toggle" id="toggle_fp_a1_re">👁️</button>
                                        </div>
                                        <div class="error-text" id="error-security_a1_re" style="display: none;"></div>
                                    </div>
                                </div>
                                
                                <!-- Question 2 -->
                                <div class="qa-row span-all">
                                    <div class="form-group">
                                        <label for="security_q2_select">Choose the Following Question: <span class="required-ast">*</span></label>
                                        <select id="security_q2_select" name="security_q2_display" class="question-select">
                                            <?php foreach ($question_map as $key => $question): ?>
                                                <option value="<?php echo htmlspecialchars($key); ?>" <?php echo ($key === $q2_text) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($question); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="security_a2_ans">Your Answer: <span class="required-ast">*</span></label>
                                        <div class="password-wrapper">
                                            <input type="password" id="security_a2_ans" name="security_a2" placeholder="Your Answer" class="answer-input" minlength="3" maxlength="50">
                                            <button type="button" class="password-toggle" id="toggle_fp_a2">👁️</button>
                                        </div>
                                        <div class="error-text" id="error-security_a2" style="display: none;"></div>
                                    </div>
                                    <div class="form-group">
                                        <label for="security_a2_re">Re-enter answer: <span class="required-ast">*</span></label>
                                        <div class="password-wrapper">
                                            <input type="password" id="security_a2_re" name="security_a2_re" placeholder="Re-enter answer" class="answer-input" minlength="3" maxlength="50">
                                            <button type="button" class="password-toggle" id="toggle_fp_a2_re">👁️</button>
                                        </div>
                                        <div class="error-text" id="error-security_a2_re" style="display: none;"></div>
                                    </div>
                                </div>
                                
                                <!-- Question 3 -->
                                <div class="qa-row span-all">
                                    <div class="form-group">
                                        <label for="security_q3_select">Choose the Following Question: <span class="required-ast">*</span></label>
                                        <select id="security_q3_select" name="security_q3_display" class="question-select">
                                            <?php foreach ($question_map as $key => $question): ?>
                                                <option value="<?php echo htmlspecialchars($key); ?>" <?php echo ($key === $q3_text) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($question); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="security_a3_ans">Your Answer: <span class="required-ast">*</span></label>
                                        <div class="password-wrapper">
                                            <input type="password" id="security_a3_ans" name="security_a3" placeholder="Your Answer" class="answer-input" minlength="3" maxlength="50">
                                            <button type="button" class="password-toggle" id="toggle_fp_a3">👁️</button>
                                        </div>
                                        <div class="error-text" id="error-security_a3" style="display: none;"></div>
                                    </div>
                                    <div class="form-group">
                                        <label for="security_a3_re">Re-enter answer: <span class="required-ast">*</span></label>
                                        <div class="password-wrapper">
                                            <input type="password" id="security_a3_re" name="security_a3_re" placeholder="Re-enter answer" class="answer-input" minlength="3" maxlength="50">
                                            <button type="button" class="password-toggle" id="toggle_fp_a3_re">👁️</button>
                                        </div>
                                        <div class="error-text" id="error-security_a3_re" style="display: none;"></div>
                                    </div>
                                </div>
                                
                                
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="btn primary-btn">VERIFY ANSWERS</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </section>
        </main>

        <footer>
            <p>&copy; 2023 LiquorLink Bar Management System. All rights reserved.</p>
        </footer>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            function enforceMaxLength(el) {
                if (!el) return;
                const max = parseInt(el.getAttribute('maxlength'));
                if (!isNaN(max) && el.value.length > max) {
                    el.value = el.value.slice(0, max);
                }
            }
            function setInlineErrorAfter(el, message) {
                if (!el) return;
                let err = el.parentNode.querySelector('.error-text.inline');
                if (!err) {
                    err = document.createElement('div');
                    err.className = 'error-text inline';
                    el.parentNode.appendChild(err);
                }
                err.textContent = message || '';
                err.style.display = message ? 'block' : 'none';
                el.style.borderColor = message ? '#e74c3c' : '';
            }
            function ltrValidate(value, opts) {
                const v = (value || '').trim();
                if (!v) return null;
                const first = v.charAt(0);
                if (opts.first && !opts.first.test(first)) return opts.firstMsg || 'Invalid first character.';
                if (opts.allowed) {
                    for (let i = 1; i < v.length; i++) {
                        const ch = v.charAt(i);
                        if (!opts.allowed.test(ch)) {
                            return (opts.invalidMsgPrefix || 'Contains invalid character') + ` "${ch}".`;
                        }
                    }
                }
                return null;
            }
            function toggleFieldType(inputEl, btnEl) {
                if (!inputEl || !btnEl) return;
                const type = inputEl.getAttribute('type') === 'password' ? 'text' : 'password';
                inputEl.setAttribute('type', type);
                btnEl.textContent = type === 'password' ? '👁️' : '👁️‍🗨️';
            }

            const fpPairs = [
                ['security_a1_ans', 'toggle_fp_a1'],
                ['security_a1_re',  'toggle_fp_a1_re'],
                ['security_a2_ans', 'toggle_fp_a2'],
                ['security_a2_re',  'toggle_fp_a2_re'],
                ['security_a3_ans', 'toggle_fp_a3'],
                ['security_a3_re',  'toggle_fp_a3_re']
            ];

            fpPairs.forEach(([inputId, btnId]) => {
                const inputEl = document.getElementById(inputId);
                const btnEl = document.getElementById(btnId);
                if (inputEl && btnEl) {
                    btnEl.addEventListener('click', function () { toggleFieldType(inputEl, btnEl); });
                    ['input','paste'].forEach(evt => inputEl.addEventListener(evt, () => enforceMaxLength(inputEl)));
                    // LTR validation for answers: first must be letter/number, allow letters, numbers, spaces, and common punctuation
                    const errorId = 'error-' + inputEl.name;
                    const errorEl = document.getElementById(errorId);
                    const validateAnswer = () => {
                        const msg = ltrValidate(inputEl.value, {
                            first: /[A-Za-z0-9]/,
                            firstMsg: 'First character must be a letter or number.',
                            allowed: /[A-Za-z0-9\s.,'\-]/,
                            invalidMsgPrefix: 'Answer contains invalid character'
                        });
                        if (errorEl) {
                            errorEl.textContent = msg || '';
                            errorEl.style.display = msg ? 'block' : 'none';
                            inputEl.style.borderColor = msg ? '#e74c3c' : '';
                        }
                    };
                    ['input','blur'].forEach(evt => inputEl.addEventListener(evt, validateAnswer));
                }
            });
            const usernameEl = document.getElementById('username');
            if (usernameEl) {
                ['input','paste'].forEach(evt => usernameEl.addEventListener(evt, () => {
                    enforceMaxLength(usernameEl);
                    const msg = ltrValidate(usernameEl.value, {
                        first: /[A-Za-z0-9]/,
                        firstMsg: 'First character must be a letter or number.',
                        allowed: /[A-Za-z0-9_\-]/,
                        invalidMsgPrefix: 'Username contains invalid character'
                    });
                    setInlineErrorAfter(usernameEl, msg);
                }));
                usernameEl.addEventListener('blur', () => {
                    const msg = ltrValidate(usernameEl.value, {
                        first: /[A-Za-z0-9]/,
                        firstMsg: 'First character must be a letter or number.',
                        allowed: /[A-Za-z0-9_\-]/,
                        invalidMsgPrefix: 'Username contains invalid character'
                    });
                    setInlineErrorAfter(usernameEl, msg);
                });
            }
            
            // Custom form validation
            const form = document.getElementById('forgot-password-form-2');
            if (form) {
                form.addEventListener('submit', function (e) {
                    e.preventDefault(); // Prevent default form submission
                    
                    // Clear all previous errors
                    const errorElements = document.querySelectorAll('.error-text');
                    errorElements.forEach(errorEl => {
                        errorEl.style.display = 'none';
                        errorEl.textContent = '';
                    });
                    
                    let isValid = true;
                    
                    // Required fields validation
                    const requiredFields = [
                        'security_a1', 'security_a1_re',
                        'security_a2', 'security_a2_re',
                        'security_a3', 'security_a3_re'
                    ];
                    
                    requiredFields.forEach(fieldName => {
                        const field = document.querySelector(`[name="${fieldName}"]`);
                        const errorEl = document.getElementById(`error-${fieldName}`);
                        
                        if (!field.value.trim()) {
                            isValid = false;
                            if (errorEl) {
                                errorEl.textContent = 'This field is required. Please provide an answer.';
                                errorEl.style.display = 'block';
                            }
                            field.style.borderColor = '#e74c3c';
                        } else {
                            // LTR character validation for answers
                            const msg = ltrValidate(field.value, {
                                first: /[A-Za-z0-9]/,
                                firstMsg: 'First character must be a letter or number.',
                                allowed: /[A-Za-z0-9\s.,'\-]/,
                                invalidMsgPrefix: 'Answer contains invalid character'
                            });
                            if (msg) {
                                isValid = false;
                                if (errorEl) {
                                    errorEl.textContent = msg;
                                    errorEl.style.display = 'block';
                                }
                                field.style.borderColor = '#e74c3c';
                            } else {
                                field.style.borderColor = '';
                            }
                        }
                    });
                    
                    // Validation for matching answers
                    const pairs = [
                        ['security_a1', 'security_a1_re'],
                        ['security_a2', 'security_a2_re'],
                        ['security_a3', 'security_a3_re']
                    ];
                    
                    pairs.forEach(([field1, field2]) => {
                        const input1 = document.querySelector(`[name="${field1}"]`);
                        const input2 = document.querySelector(`[name="${field2}"]`);
                        const error1 = document.getElementById(`error-${field1}`);
                        const error2 = document.getElementById(`error-${field2}`);
                        
                        if (input1.value && input2.value && input1.value !== input2.value) {
                            isValid = false;
                            if (error1) {
                                error1.textContent = 'Answers do not match. Please ensure both answers are identical.';
                                error1.style.display = 'block';
                            }
                            if (error2) {
                                error2.textContent = 'Answers do not match. Please ensure both answers are identical.';
                                error2.style.display = 'block';
                            }
                            input1.style.borderColor = '#e74c3c';
                            input2.style.borderColor = '#e74c3c';
                        }
                    });
                    
                    // If validation passes, submit the form
                    if (isValid) {
                        form.submit();
                    } else {
                        // Scroll to the first error
                        const firstError = document.querySelector('.error-text[style*="block"]');
                        if (firstError) {
                            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }
                    }
                });
            }
            
            // Allow question dropdowns to be changed
            // Note: The actual question values are stored in hidden fields
            // The dropdowns are for display purposes but can be changed
        });
    </script>
</body>
</html>
