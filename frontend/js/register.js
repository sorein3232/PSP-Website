document.addEventListener("DOMContentLoaded", function () {
    const form = document.querySelector("form");

    form.addEventListener("submit", async function (event) {
        event.preventDefault(); // Prevent normal form submission

        const formData = new FormData(form);

        try {
            const response = await fetch("register.php", {
                method: "POST",
                body: formData
            });

            const result = await response.json(); // Parse JSON response

            if (result.status === "error") {
                console.error(`[ERROR] ${result.message}`); // Show error in console
            } else if (result.status === "success") {
                console.log(`[SUCCESS] ${result.message}`);
                setTimeout(() => {
                    window.location.href = "login.php"; // Redirect after success
                }, 2000);
            }
        } catch (error) {
            console.error(`[ERROR] ❌ Network error. Please try again later.`);
        }
    });
});
