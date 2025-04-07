<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/login.css">
    <title>Login</title>
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <h1>Log In</h1>
            <form id="loginForm">
                <input type="email" name="emailAddress" placeholder="Email" required>
                <input type="password" name="password" placeholder="Password" required>
                <div class="extra-links">
                    <p>Don't have an account? <a href="register.php">Sign up</a></p>
                </div>
                <button class="login" type="submit">Login</button>
                <div class="forgot-password">
                    <a href="forgot_password.php">Forgot Password?</a>
                </div>
            </form>
            <p id="message"></p>
            <p id="lockout-timer" style="display: none; color: red;"></p>
        </div>
    </div>

    <script>
        document.getElementById("loginForm").addEventListener("submit", function(event) {
            event.preventDefault();

            let formData = new FormData(this);

            fetch("login_process.php", {
                method: "POST",
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                const messageElement = document.getElementById("message");
                const lockoutTimerElement = document.getElementById("lockout-timer");

                messageElement.innerHTML = data.message;

                if (data.status === "success") {
                    setTimeout(() => {
                        window.location.href = "profile.php"; // Redirect to profile page
                    }, 2000);
                }

                if (data.lockout_seconds) {
                    lockoutTimerElement.style.display = "block";
                    startLockoutTimer(data.lockout_seconds);
                }
            })
            .catch(error => {
                console.error("Error:", error);
                document.getElementById("message").innerHTML = "❌ An error occurred. Please try again.";
            });
        });

        function startLockoutTimer(seconds) {
            const lockoutTimerElement = document.getElementById("lockout-timer");
            let remainingTime = seconds;

            function updateTimer() {
                if (remainingTime <= 0) {
                    lockoutTimerElement.style.display = "none";
                    lockoutTimerElement.innerHTML = "";
                    return;
                }
                lockoutTimerElement.innerHTML = `Account locked. Try again in ${remainingTime} seconds. ⏳`;
                remainingTime--;
                setTimeout(updateTimer, 1000);
            }

            updateTimer();
        }
    </script>
</body>
</html>