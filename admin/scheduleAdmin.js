document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll(".remove").forEach(button => {
        button.addEventListener("click", function () {
            let row = this.closest("tr");
            let id = row.id.split("-")[1];

            if (confirm("Are you sure you want to delete this schedule?")) {
                fetch("deleteSchedule.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: "id=" + id
                })
                .then(response => response.text())
                .then(data => {
                    alert(data);
                    row.remove();
                })
                .catch(error => console.error("Error:", error));
            }
        });
    });

    document.querySelector("form").addEventListener("submit", function (event) {
        event.preventDefault();
        let formData = new FormData(this);
    
        fetch("scheduleAdmin.php", {
            method: "POST",
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            console.log(data);
            if (data.includes("Schedule updated successfully")) {
                alert("Schedule updated successfully!");
                window.location.reload();
            } else {
                alert("Error updating schedule: " + data);
            }
        })
        .catch(error => console.error("Error:", error));
    });
    
    
});
