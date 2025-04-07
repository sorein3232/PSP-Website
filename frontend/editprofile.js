document.addEventListener("DOMContentLoaded", function () {
    let currentField = "";

    // Password validation function
    function validatePassword(password) {
        const errors = [];

        // Check length
        if (password.length < 8 || password.length > 16) {
            errors.push("Password must be between 8-16 characters");
        }

        // Check for uppercase letter
        if (!/[A-Z]/.test(password)) {
            errors.push("Password must contain at least one uppercase letter");
        }

        // Check for special character
        if (!/[!@#$%^&*()_+\-=\[\]{};':\"\\|,.<>\/?]/.test(password)) {
            errors.push("Password must contain at least one special character");
        }

        return errors;
    }

    // Phone number validation function
    function validatePhilippinePhoneNumber(phoneNumber) {
        // Remove all non-digit characters
        const cleanedNumber = phoneNumber.replace(/\D/g, '');
        
        // Check total length after removing non-digit characters
        const length = cleanedNumber.length;
        
        // Valid Philippine mobile number scenarios
        if (length === 10) {
            // Standard 10-digit mobile number starting with 9
            return cleanedNumber[0] === '9';
        } else if (length === 11) {
            // Number with leading 0
            return cleanedNumber.slice(0, 2) === '09';
        } else if (length === 12) {
            // Number with country code
            return cleanedNumber.slice(0, 3) === '639';
        }
        
        return false;
    }

    // Normalize phone number to international format
    function normalizePhoneNumber(phoneNumber) {
        // Remove all non-digit characters
        const cleanedNumber = phoneNumber.replace(/\D/g, '');
        
        // Normalize to international format
        const length = cleanedNumber.length;
        
        if (length === 10) {
            // 9xxxxxxxxx -> 639xxxxxxxxx
            return '63' + cleanedNumber;
        } else if (length === 11 && cleanedNumber[0] === '0') {
            // 09xxxxxxxxx -> 639xxxxxxxxx
            return '63' + cleanedNumber.slice(1);
        } else if (length === 12 && cleanedNumber.slice(0, 3) === '639') {
            // Already in correct format
            return cleanedNumber;
        }
        
        return null;
    }

    window.openModal = function (field) {
        currentField = field;
    
        const modal = document.getElementById("editModal");
        const modalTitle = document.getElementById("modal-title");
        const modalBody = document.getElementById("modal-body");
    
        modalBody.innerHTML = "";
    
        if (field === "profile_picture") {
            modalTitle.textContent = "Change Profile Picture";
            modalBody.innerHTML = `
                <form id="editForm" enctype="multipart/form-data" style="padding-right: 20px;">
                    <input type="file" id="editValue" name="profile_picture" accept="image/*" required>
                    <input type="hidden" id="fieldName" name="field" value="profile_picture">
                    <div class="modal-buttons">
                        <button type="button" class="cancel-btn" onclick="closeModal()">Cancel</button>
                        <button type="submit" class="save-btn">Save</button>
                    </div>
                </form>
            `;
        } else if (field === "password") {
            modalTitle.textContent = "Change Password";
    
            modalBody.innerHTML = `
            <form id="editForm" style="padding-right: 20px;">
                <label for="currentPassword">Current Password</label>
                <input type="password" id="currentPassword" name="current_password" placeholder="Enter current password" required>

                <label for="newPassword">New Password</label>
                <input type="password" id="newPassword" name="new_password" placeholder="Enter new password" required>

                <label for="confirmPassword">Confirm Password</label>
                <input type="password" id="confirmPassword" name="confirm_password" placeholder="Confirm new password" required>

                <p class="password-requirements">
                    Password must:
                    <ul>
                        <li>Be 8-16 characters long</li>
                        <li>Contain at least one uppercase letter</li>
                        <li>Contain at least one special character</li>
                    </ul>
                </p>

                <input type="hidden" id="fieldName" name="field" value="password">

                <div class="modal-buttons">
                    <button type="submit" class="save-btn">Save</button>
                    <button type="button" class="cancel-btn" onclick="closeModal()">Cancel</button>
                </div>
            </form>
        `;

        } else {
            let inputType = "text";
            let placeholder = "";
            let currentValue = document.getElementById(field).textContent.trim();
    
            switch (field) {
                case "fullName":
                    modalTitle.textContent = "Edit Name";
                    placeholder = "Enter your full name";
                    break;
                case "username":
                    modalTitle.textContent = "Edit Username";
                    placeholder = "Enter your username";
                    currentValue = currentValue.replace("@", "");
                    break;
                case "phoneNumber":
                    modalTitle.textContent = "Edit Contact Number";
                    inputType = "tel";
                    placeholder = "Enter your phone number (e.g., 9171234567)";
                    currentValue = currentValue.replace(/\D/g, ''); // Remove non-digit characters
                    break;
                case "emailAddress":
                    modalTitle.textContent = "Edit Email Address";
                    inputType = "email";
                    placeholder = "Enter your email address";
                    break;
                case "birthday":
                    modalTitle.textContent = "Edit Birthday";
                    inputType = "date";
                    break;
            }
    
            modalBody.innerHTML = `
                <form id="editForm" style="padding-right: 20px;">
                    <input type="${inputType}" id="editValue" name="value" value="${currentValue}" placeholder="${placeholder}" required>
                    <input type="hidden" id="fieldName" name="field" value="${field}">
                    <div class="modal-buttons">
                        <button type="submit" class="save-btn">Save</button>
                        <button type="button" class="cancel-btn" onclick="closeModal()">Cancel</button>
                    </div>
                </form>
            `;
        }
    
        modal.style.display = "block";
    };
    
    document.addEventListener("submit", function (e) {
        if (e.target && e.target.id === "editForm") {
            e.preventDefault();
            
            // Special handling for password field
            if (currentField === "password") {
                const newPassword = document.getElementById("newPassword").value;
                const confirmPassword = document.getElementById("confirmPassword").value;
                const currentPassword = document.getElementById("currentPassword").value;

                // Validate passwords match
                if (newPassword !== confirmPassword) {
                    alert("New password and confirm password do not match");
                    return;
                }

                // Validate new password
                const passwordErrors = validatePassword(newPassword);
                if (passwordErrors.length > 0) {
                    alert(passwordErrors.join("\n"));
                    return;
                }
            }

            // Special handling for phone number field
            if (currentField === "phoneNumber") {
                const phoneNumber = document.getElementById("editValue").value;
                
                // Validate phone number
                if (!validatePhilippinePhoneNumber(phoneNumber)) {
                    alert("Invalid Philippine phone number format. Use 9xxxxxxxxx, 09xxxxxxxxx, or +639xxxxxxxxx");
                    return;
                }
            }

            saveChanges();
        }
    });
    
    function saveChanges() {
        const form = document.getElementById("editForm");
        const formData = new FormData(form);
        const field = formData.get("field");
    
        // Normalize phone number if editing phone number
        if (field === "phoneNumber") {
            const normalizedNumber = normalizePhoneNumber(formData.get("value"));
            if (normalizedNumber) {
                formData.set("value", normalizedNumber);
            } else {
                alert("Could not normalize phone number");
                return;
            }
        }
    
        fetch("update_profile.php", {
            method: "POST",
            body: formData,
        })
        .then(response => response.json())
        .then(data => {
            console.log("Server Response:", data);
    
            if (data.success) {
                if (field === "profile_picture") {
                    document.querySelector(".profile-picture img").src = "../frontend/uploads/" + data.profile_picture;
                } else {
                    let updatedValue = formData.get("value");
                    if (field === "username") {
                        updatedValue = "@" + updatedValue;
                    }
                    document.getElementById(field).textContent = updatedValue;
                }
    
                closeModal();
                alert("Profile updated successfully!");
            } else {
                alert("Error updating profile: " + data.error);
            }
        })
        .catch(error => {
            console.error("Fetch Error:", error);
            alert("An error occurred. Please try again.");
        });
    }
});