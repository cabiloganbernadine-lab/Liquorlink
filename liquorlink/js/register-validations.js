// Auto-format ID number with dash
document.addEventListener('DOMContentLoaded', function() {
    const idNumberInput = document.getElementById('id_number');
    
    if (idNumberInput) {
        idNumberInput.addEventListener('input', function(e) {
            // Remove any non-digit characters
            let value = e.target.value.replace(/\D/g, '');
            
            // Add dash after 4 digits
            if (value.length > 4) {
                value = value.substring(0, 4) + '-' + value.substring(4);
            }
            
            // Limit to 9 characters (XXXX-XXXX)
            if (value.length > 9) {
                value = value.substring(0, 9);
            }
            
            e.target.value = value;
        });
        
        // Add validation for extension name
        const extensionName = document.getElementById('extensionName');
        if (extensionName) {
            extensionName.addEventListener('input', function() {
                const value = this.value.trim();
                const allowed = ['jr', 'sr', 'ii', 'iii', 'iv', 'v', 'vi', 'vii', 'viii', 'ix', 'x'];
                const allowedWithDot = ['jr.', 'sr.'];
                
                if (value === '') return;
                
                const lower = value.toLowerCase();
                if (!allowed.includes(lower) && !allowedWithDot.includes(lower)) {
                    this.setCustomValidity('Please enter a valid extension (e.g., Jr, Sr, II, III, IV, etc.)');
                } else {
                    this.setCustomValidity('');
                }
            });
        }
    }
});

// Form validation
function validateForm() {
    let isValid = true;
    const form = document.getElementById('register-form');
    
    // Clear previous error messages
    const errorMessages = form.querySelectorAll('.error-message');
    errorMessages.forEach(el => el.remove());
    
    // Validate name fields (20-50 chars)
    const nameFields = ['firstName', 'middleInitial', 'lastName'];
    nameFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            const value = field.value.trim();
            if (value.length < 20 || value.length > 50) {
                showError(field, 'Must be between 20 and 50 characters');
                isValid = false;
            }
            
            // Check for invalid characters
            if (/[^a-zA-Z\s'-]/.test(value)) {
                showError(field, 'Contains invalid characters. Only letters, spaces, hyphens, and apostrophes are allowed.');
                isValid = false;
            }
        }
    });
    
    // Validate address fields (20-50 chars)
    const addressFields = ['purok', 'barangay', 'cityMunicipality', 'province'];
    addressFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field && field.value.trim() !== '') {
            const value = field.value.trim();
            if (value.length < 20 || value.length > 50) {
                showError(field, 'Must be between 20 and 50 characters');
                isValid = false;
            }
        }
    });
    
    // Validate zip code (4-5 digits)
    const zipCode = document.getElementById('zipCode');
    if (zipCode && zipCode.value.trim() !== '') {
        const value = zipCode.value.trim();
        if (!/^\d{4,5}$/.test(value)) {
            showError(zipCode, 'Zip code must be 4 to 5 digits');
            isValid = false;
        }
    }
    
    return isValid;
}

// Helper function to show error messages
function showError(field, message) {
    const error = document.createElement('div');
    error.className = 'error-message';
    error.style.color = '#e74c3c';
    error.style.fontSize = '0.8em';
    error.style.marginTop = '5px';
    error.textContent = message;
    
    // Insert after the field
    field.parentNode.insertBefore(error, field.nextSibling);
    
    // Add error class to field
    field.classList.add('error');
    
    // Remove error class when field gains focus
    field.addEventListener('focus', function() {
        this.classList.remove('error');
        if (error.parentNode) {
            error.parentNode.removeChild(error);
        }
    }, { once: true });
}

// Add event listener to form submission
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('register-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            if (!validateForm()) {
                e.preventDefault();
            }
        });
    }
});
