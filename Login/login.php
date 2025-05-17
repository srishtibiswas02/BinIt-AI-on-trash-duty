<?php
    session_start();

    // Database connection
    $server = "sql207.infinityfree.com";
    $username = "if0_39005718";
    $password = "BinIt020804";
    $db_name = "if0_39005718_binit_db";

    $php_err = "";

    // Create connection with database
    $conn = new mysqli($server, $username, $password, $db_name);
    if ($conn->connect_error) {
        $php_err = "Connection failed";
        exit;
    }

    // Create table if not exists
    $table_query = "CREATE TABLE IF NOT EXISTS signup_tb (
        S_no INT AUTO_INCREMENT PRIMARY KEY,
        First_name VARCHAR(50) NOT NULL,
        Last_name VARCHAR(50) NOT NULL,
        Email_id VARCHAR(100) UNIQUE NOT NULL,
        Username VARCHAR(50) UNIQUE NOT NULL,
        Password VARCHAR(255) NOT NULL
    )";
    if (!$conn->query($table_query)) {
        $php_err = "Table creation failed: ";
        exit;
    }

    // Initialize variables
    $f_name = $l_name = $eid = $uname = $pass = "";
    $name_err = $eid_err = $uname_err = $pass_err = $empty_err = "";

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        if (isset($_POST["signup"])) 
        { 
            if (empty($_POST["f_name"]) || empty($_POST["l_name"]) || empty($_POST["eid"]) || empty($_POST["uname"]) || empty($_POST["pass"])) 
            {
                $php_err = "Please fill all the fields.";
            } 
            else 
            {
                $f_name = htmlspecialchars($_POST["f_name"]);
                $l_name = htmlspecialchars($_POST["l_name"]);
                $eid = htmlspecialchars($_POST["eid"]);
                $uname = htmlspecialchars($_POST["uname"]);
                $pass = htmlspecialchars($_POST["pass"]);

                // Validation checks
                if (!preg_match("/^[a-zA-Z]{2,}$/", $f_name) || !preg_match("/^[a-zA-Z]{2,}$/", $l_name)) {
                    $php_err = "Name must contain only letters.";
                } elseif (!filter_var($eid, FILTER_VALIDATE_EMAIL)) {
                    $php_err = "Enter a valid email address.";
                } elseif (!preg_match("/^[a-zA-Z0-9]{5,}$/", $uname)) {
                    $php_err = "Username must contain only letters and numbers.";
                } elseif (strlen($pass) < 6) {
                    $php_err = "Password must be at least 6 characters.";
                } else {
                    // Check for existing username or email
                    $stmt = $conn->prepare("SELECT * FROM signup_tb WHERE Username = ? OR Email_id = ?");
                    $stmt->bind_param("ss", $uname, $eid);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows > 0) {
                        $php_err = "This Email ID or Username already exists.";
                    } else {
                        // Hash password and insert user
                        $hash_pass = password_hash($pass, PASSWORD_DEFAULT);
                        $stmt = $conn->prepare("INSERT INTO signup_tb (First_name, Last_name, Email_id, Username, Password) VALUES (?, ?, ?, ?, ?)");
                        $stmt->bind_param("sssss", $f_name, $l_name, $eid, $uname, $hash_pass);
                        if(!$stmt->execute()) { // Check if the execution was successful
                            $php_err = "Data insertion failed: " . $stmt->error; // Get the specific error message
                        }
                    }
                    $stmt->close();
                    // exit();
                }
            }
        }
    }

    // Login Logic
    $login_err = "";
    
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        if (isset($_POST["login"])) {
            if (empty($_POST["uname"]) || empty($_POST["pass"])) 
            {
                $login_err = "Please fill all the fields.";
            } 
            else {
                $uname = htmlspecialchars($_POST["uname"]);
                $pass = htmlspecialchars($_POST["pass"]);
    
                $db_name = "if0_39005718_binit_db";
                $conn = new mysqli("sql207.infinityfree.com", "if0_39005718", "BinIt020804", $db_name);
    
                if ($conn->connect_error) {
                    $login_err = "Error connecting with the server";
                } else {
                    // Use a prepared statement to prevent SQL injection
                    $stmt = $conn->prepare("SELECT Password FROM signup_tb WHERE Username = ?");
                    $stmt->bind_param("s", $uname);
                    $stmt->execute();
                    $stmt->store_result();
                    
                    if ($stmt->num_rows == 0) {
                        $login_err = "User Not Found! Enter correct credentials.";
                    } else {
                        $stmt->bind_result($hash_pass);
                        $stmt->fetch();
    
                        // Use password_verify to check the hashed password
                        if (password_verify($pass, $hash_pass)) {
                            $_SESSION["username"] = $uname;
                            header("Location: ../Main/dashboard.php");
                            exit();
                        } else {
                            $login_err = "Incorrect password.";
                        }
                    }
                    $stmt->close();
                }
                $conn->close();
            }
        }
    }
    
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BinIt</title>
    <link rel="stylesheet" href="style_login.css">
    <link rel="icon" href="./logo.png" type="image/x-icon">
</head>
<body>
    <div class="container">
        <div class="forms-container">
            <form class="sign-in-form" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" >
                <h2>Log In</h2>
                <div class="input-group">
                    <input type="text" placeholder="Username" class="input-field" name="uname" required>
                    <span class="validation-icon">✓</span>
                    <div class="error-message">Please enter your username</div>
                </div>
                <div class="input-group">
                    <input type="password" placeholder="Password" class="input-field" name="pass" required>
                    <span class="validation-icon">✓</span>
                    <div class="error-message">Password must be at least 6 characters</div>
                </div>
                <div class="login_err"><?php echo $php_err;?></div>
                <button type="submit" class="btn" id ="login" name = "login">LogIn</button>
            </form>
            <form class="sign-up-form" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" >
                <h2>Create Account</h2>
                <div class="names-row"> 
                    <div class="input-group">
                        <input type="text" placeholder="First Name" name="f_name" class="input-field" required>
                        <span class="validation-icon">✓</span>
                        <div class="error-message">Please enter your first name</div>
                    </div>
                    <div class="input-group">
                        <input type="text" placeholder="Last Name"  name="l_name" class="input-field" required>
                        <span class="validation-icon">✓</span>
                        <div class="error-message">Please enter your last name</div>
                    </div>
                </div>
                <div class="input-group">
                    <input type="email" placeholder="Email" name="eid" class="input-field" required>
                    <span class="validation-icon">✓</span>
                    <div class="error-message">Please enter a valid email address</div>
                </div>
                <div class="input-group">
                    <input type="text" placeholder="Username" name="uname" class="input-field" required>
                    <span class="validation-icon">✓</span>
                    <div class="error-message">Please enter your username</div>
                </div>
                <div class="input-group">
                    <input type="password" placeholder="Password" name="pass" class="input-field" required>
                    <span class="validation-icon">✓</span>
                    <div class="error-message">Password must be at least 6 characters</div>
                    <div class="progress-bar">
                        <div class="progress"></div>
                    </div>
                </div>
                <div class="php_err"><?php echo $login_err; ?></div>
                <button type="submit" class="btn" id ="signup" name="signup">Sign Up</button>
            </form>
        </div>
        <div class="green-panel">
            <h2>Welcome!</h2>
            <p>Enter your personal details and start your journey with us!</p>
            <button class="btn toggle-btn">Login</button>
        </div>
    </div>

    <script>
        // Previous toggle functionality
        const container = document.querySelector('.container');
        const toggleBtn = document.querySelector('.toggle-btn');
        let isSignUpMode = true; // Start with the Sign-in form visible

        toggleBtn.addEventListener('click', () => {
            isSignUpMode = !isSignUpMode;
            container.classList.toggle('sign-up-mode');
            toggleBtn.textContent = isSignUpMode ? 'Login' : 'Sign Up'; // Toggle button text

            const greenPanelTitle = document.querySelector('.green-panel h2');
            const greenPanelText = document.querySelector('.green-panel p');
            
            if (isSignUpMode) {
                greenPanelTitle.textContent = 'Welcome!';
                greenPanelText.textContent = 'Enter your personal details and start your journey with us!';
            } else {
                greenPanelTitle.textContent = 'Welcome Back!';
                greenPanelText.textContent = 'To keep connected with us please login with your personal info';
            }
        });

        // Form validation
        function validateEmail(email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        }

        function validatePassword(password) {
            return password.length >= 8;
        }

        function validateName(name) {
            return name.length >= 2; 
        }

        // Input validation and interactive feedback
        document.querySelectorAll('.input-field').forEach(input => {
            const errorMessage = input.parentElement.querySelector('.error-message');
            const progressBar = input.parentElement.querySelector('.progress-bar');
            
            input.addEventListener('input', () => {
                let isValid = true;
                
                if (input.type === 'email') {
                    isValid = validateEmail(input.value);
                } else if (input.type === 'password') {
                    isValid = validatePassword(input.value);
                    if (progressBar) {
                        progressBar.style.display = 'block';
                        const progress = Math.min((input.value.length / 12) * 100, 100);
                        progressBar.querySelector('.progress').style.width = `${progress}%`; 
                    }
                } else if (input.placeholder === 'First Name' || input.placeholder === 'Last Name' || input.placeholder === "Username") {
                    isValid = validateName(input.value);
                }

                // Only toggle 'error' class for signup form
                if (input.closest('form').classList.contains('sign-up-form')) {
                    input.classList.toggle('error', !isValid && input.value.length > 0);
                }
                input.classList.toggle('success', isValid && input.value.length > 0);
                errorMessage.style.display = !isValid && input.value.length > 0 ? 'block' : 'none';

                // Enable/disable submit button
                const form = input.closest('form');
                const allInputs = Array.from(form.querySelectorAll('.input-field'));
                const submitBtn = form.querySelector('.btn');
                submitBtn.disabled = !allInputs.every(input => 
                    input.classList.contains('success')
                );
            });
        });

        // Handle form submission
        document.querySelectorAll('.btn').forEach(button => {
            if (!button.classList.contains('toggle-btn')) {
                button.addEventListener('click', function() {
                    if (!this.disabled) {
                        const form = this.closest('form');
                        const successMessage = form.querySelector('.success-message');
                        successMessage.style.display = 'block';
                        
                        // Reset form after success
                        setTimeout(() => {
                            form.reset();
                            successMessage.style.display = 'none';
                            form.querySelectorAll('.input-field').forEach(input => {
                                input.classList.remove('success');
                            });
                            form.querySelector('.btn').disabled = true;
                            if (form.querySelector('.progress-bar')) {
                                form.querySelector('.progress').style.width = '0';
                                form.querySelector('.progress-bar').style.display = 'none';
                            }
                        }, 2000);
                    }
                });
            }
        });
    </script>
</body>
</html>