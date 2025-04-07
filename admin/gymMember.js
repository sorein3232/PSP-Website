document.addEventListener("DOMContentLoaded", () => {
    const memberTable = document.querySelector('.member-table tbody');
    const appointmentTable = document.querySelector('.appointment-table tbody');
    const logoutButton = document.querySelector(".logout");

    // EVENT DELEGATION: Listen for button clicks inside the tables
    document.body.addEventListener("click", (event) => {
        const button = event.target;
        const row = button.closest("tr");
        if (!row) return;

        if (button.classList.contains("cancel")) {
            // Appointment Cancellation Logic
            let appointmentId = row.cells[2].textContent.trim(); // Get Appointment ID

            if (confirm(`Are you sure you want to cancel appointment ${appointmentId}?`)) {
                fetch("deleteAppointment.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ appointment_id: appointmentId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        row.remove(); // Remove row from table
                        alert("Appointment canceled successfully.");
                    } else {
                        alert("Failed to cancel appointment.");
                    }
                })
                .catch(error => {
                    console.error("Error:", error);
                    alert("An error occurred while canceling the appointment.");
                });
            }
            return;
        }

        // Membership Actions (Freeze, Unfreeze, Remove)
        let statusCell = row.cells[2]; // Membership Status column
        let userId = row.cells[1].textContent.trim(); // Get User ID
        let action = "";

        if (button.classList.contains("freeze")) {
            action = "freeze";
            statusCell.textContent = "FREEZE";
        } else if (button.classList.contains("unfreeze")) {
            action = "unfreeze";
            statusCell.textContent = "PAID";
        } else if (button.classList.contains("remove")) {
            if (confirm(`Are you sure you want to remove member ID ${userId}?`)) {
                fetch("deleteMember.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: `user_id=${userId}`
                })
                .then(response => response.text())
                .then(data => {
                    if (data.trim() === "success") {
                        row.remove();
                    } else {
                        alert("Failed to remove member. Server said: \n" + data);
                    }
                })
                .catch(error => {
                    console.error("Error:", error);
                    alert("An error occurred while removing the member.");
                });
            }
            return;
        }

        // Handle freeze/unfreeze actions
        if (action) {
            fetch("updateMember.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `user_id=${userId}&action=${action}`
            })
            .then(response => response.text())
            .then(data => {
                console.log("Server Response:", data);
                if (data.trim() !== "success") {
                    alert(`Failed to update membership. Server said: ${data}`);
                }
            })
            .catch(error => {
                console.error("Error:", error);
                alert("An error occurred while updating membership status.");
            });
        }
    });

    // Logout functionality
    if (logoutButton) {
        logoutButton.addEventListener("click", () => {
            if (confirm("Are you sure you want to log out?")) {
                window.location.href = "gymMembers.php?logout=true";
            }
        });
    }    
});
