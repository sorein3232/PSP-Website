document.addEventListener("DOMContentLoaded", function () {
    const billingHeader = document.querySelector(".billings-header");
    const billingDetails = document.getElementById("billing-details");
    const billingIcon = document.getElementById("billing-icon");

    if (billingDetails) {
        billingDetails.style.display = "none"; // Ensure it's hidden initially
    }

    function toggleBillingDetails() {
        if (billingDetails.style.display === "none" || billingDetails.style.display === "") {
            billingDetails.style.display = "block";
            billingIcon.classList.remove("fa-chevron-down");
            billingIcon.classList.add("fa-chevron-up");
        } else {
            billingDetails.style.display = "none";
            billingIcon.classList.remove("fa-chevron-up");
            billingIcon.classList.add("fa-chevron-down");
        }
    }

    if (billingHeader) {
        billingHeader.addEventListener("click", toggleBillingDetails);
    }
});
