// Report Modal Functions
function showReportModal() {
    const modal = document.getElementById('reportModal');
    modal.classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeReportModal() {
    const modal = document.getElementById('reportModal');
    modal.classList.remove('show');
    document.body.style.overflow = '';
}

// New add method functionality using inline input rather than prompt
document.querySelector('.add-method-btn').addEventListener('click', function() {
    if (document.querySelector('.new-method-input')) return;
    const inputElem = document.createElement('input');
    inputElem.type = 'text';
    inputElem.placeholder = 'Enter new treatment method';
    inputElem.className = 'new-method-input';
    inputElem.style.marginBottom = '10px';
    this.parentNode.insertBefore(inputElem, this);
    inputElem.focus();
    inputElem.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            addNewMethod(inputElem.value);
        }
    });
    inputElem.addEventListener('blur', function() {
        addNewMethod(inputElem.value);
    });
});
function addNewMethod(value) {
    if (value && value.trim() !== '') {
        const methodOptions = document.querySelector('.method-options');
        const methodValue = value.toLowerCase().trim();
        const methodId = 'method-' + methodValue.replace(/\s+/g, '-');
        const div = document.createElement('div');
        div.className = 'method-option';
        div.innerHTML = `
            <input type="checkbox" id="${methodId}" name="method[]" value="${methodValue}" checked>
            <label for="${methodId}">${value.trim()}</label>
        `;
        methodOptions.appendChild(div);
    }
    const inputElem = document.querySelector('.new-method-input');
    if (inputElem) inputElem.remove();
}

// New add custom chemical functionality using inline input rather than prompt
document.querySelector('.add-chemical-btn').addEventListener('click', function() {
    if (document.querySelector('.new-chemical-input')) return;
    const inputElem = document.createElement('input');
    inputElem.type = 'text';
    inputElem.placeholder = 'Enter new chemical or material name';
    inputElem.className = 'new-chemical-input';
    inputElem.style.marginBottom = '10px';
    this.parentNode.insertBefore(inputElem, this);
    inputElem.focus();
    inputElem.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            addNewChemical(inputElem.value);
        }
    });
    inputElem.addEventListener('blur', function() {
        addNewChemical(inputElem.value);
    });
});
function addNewChemical(value) {
    if (value && value.trim() !== '') {
        let customContainer = document.getElementById('customChemicalsContainer');
        if (!customContainer.querySelector('.custom-chemicals')) {
            const customType = document.createElement('div');
            customType.className = 'treatment-type custom-chemicals active';
            customType.innerHTML = `
                <div class="treatment-type-header">
                    <h4>Custom Chemicals & Materials</h4>
                    <i class='bx bx-chevron-up toggle-icon'></i>
                </div>
                <div class="treatment-type-content" style="display: block;">
                    <div class="treatment-method active">
                        <div class="treatment-method-header">
                            <h5>Custom Items</h5>
                            <i class='bx bx-chevron-up toggle-icon'></i>
                        </div>
                        <div class="treatment-method-content" style="display: block;">
                            <div class="chemical-items custom-items"></div>
                        </div>
                    </div>
                </div>
            `;
            customContainer.appendChild(customType);
            // Attach toggle events to new headers
            customType.querySelector('.treatment-type-header').addEventListener('click', toggleTreatmentType);
            customType.querySelector('.treatment-method-header').addEventListener('click', toggleTreatmentMethod);
        }
        const customItems = customContainer.querySelector('.custom-items');
        const div = document.createElement('div');
        div.className = 'chemical-item';
        const chemicalName = value.trim();
        div.innerHTML = `
            <label>
                <input type="checkbox" name="chemicals[]" value="${chemicalName}" checked>
                ${chemicalName}
            </label>
            <input type="number" name="chemical_qty[${chemicalName}]" class="quantity-input" placeholder="Qty" min="0" step="1" value="1">
        `;
        customItems.appendChild(div);
    }
    const inputElem = document.querySelector('.new-chemical-input');
    if (inputElem) inputElem.remove();
}

// Handle report form submission
document.getElementById('reportForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    try {
        const response = await fetch('../PHP CODES/submit_report.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        if (result.success) {
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: 'Report submitted successfully',
                confirmButtonColor: '#144578',
                timer: 2000,
                timerProgressBar: true,
                showConfirmButton: false,
                didClose: () => {
                    closeReportModal();
                }
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error submitting report: ' + result.message,
                confirmButtonColor: '#144578'
            });
        }
    } catch (error) {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'An error occurred while submitting the report',
            confirmButtonColor: '#144578'
        });
    }
});

// Ensure dropdown functionality for treatment types and devices
document.addEventListener('DOMContentLoaded', function() {
    // Function to toggle treatment type dropdown
    function toggleTreatmentType() {
        const type = this.closest('.treatment-type');
        const content = type.querySelector('.treatment-type-content');
        const icon = this.querySelector('.toggle-icon');
        
        // Toggle active class
        type.classList.toggle('active');
        
        // Toggle content display
        if (type.classList.contains('active')) {
            content.style.display = 'block';
            icon.classList.remove('bx-chevron-down');
            icon.classList.add('bx-chevron-up');
        } else {
            content.style.display = 'none';
            icon.classList.remove('bx-chevron-up');
            icon.classList.add('bx-chevron-down');
        }
    }
    
    // Function to toggle treatment method dropdown
    function toggleTreatmentMethod() {
        const method = this.closest('.treatment-method');
        const content = method.querySelector('.treatment-method-content');
        const icon = this.querySelector('.toggle-icon');
        
        // Toggle active class
        method.classList.toggle('active');
        
        // Toggle content display
        if (method.classList.contains('active')) {
            content.style.display = 'block';
            icon.classList.remove('bx-chevron-down');
            icon.classList.add('bx-chevron-up');
        } else {
            content.style.display = 'none';
            icon.classList.remove('bx-chevron-up');
            icon.classList.add('bx-chevron-down');
        }
    }

    // Add event listeners for treatment type headers
    document.querySelectorAll('.treatment-type-header').forEach(header => {
        header.addEventListener('click', toggleTreatmentType);
    });

    // Add event listeners for treatment method headers
    document.querySelectorAll('.treatment-method-header').forEach(header => {
        header.addEventListener('click', toggleTreatmentMethod);
    });
    
    // Auto-expand sections with checked items
    document.querySelectorAll('input[name="chemicals[]"]:checked, input[name="devices[]"]:checked').forEach(checkbox => {
        // Find and expand parent treatment method
        const method = checkbox.closest('.treatment-method');
        if (method) {
            method.classList.add('active');
            const methodContent = method.querySelector('.treatment-method-content');
            const methodIcon = method.querySelector('.treatment-method-header .toggle-icon');
            if (methodContent) methodContent.style.display = 'block';
            if (methodIcon) {
                methodIcon.classList.remove('bx-chevron-down');
                methodIcon.classList.add('bx-chevron-up');
            }
        }
        
        // Find and expand parent treatment type
        const type = checkbox.closest('.treatment-type');
        if (type) {
            type.classList.add('active');
            const typeContent = type.querySelector('.treatment-type-content');
            const typeIcon = type.querySelector('.treatment-type-header .toggle-icon');
            if (typeContent) typeContent.style.display = 'block';
            if (typeIcon) {
                typeIcon.classList.remove('bx-chevron-down');
                typeIcon.classList.add('bx-chevron-up');
            }
        }
    });
    
    // Expand the devices section by default
    const devicesSection = document.querySelector('.devices-section .treatment-type');
    if (devicesSection) {
        devicesSection.classList.add('active');
        const content = devicesSection.querySelector('.treatment-type-content');
        const icon = devicesSection.querySelector('.toggle-icon');
        if (content) content.style.display = 'block';
        if (icon) {
            icon.classList.remove('bx-chevron-down');
            icon.classList.add('bx-chevron-up');
        }
    }

    document.getElementById('jobDetailsForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Show loading indicator
        const saveButton = document.querySelector('.save-btn');
        const originalButtonText = saveButton.innerHTML;
        saveButton.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i> Saving...';
        saveButton.disabled = true;

        // Get form data - maintaining all input fields as they are
        const formData = new FormData(this);

        // Submit form with FormData
        fetch('../PHP CODES/process_job_details.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            // Restore button
            saveButton.innerHTML = originalButtonText;
            saveButton.disabled = false;     
            if (data.success) {
                // Show success message using SweetAlert2
                Swal.fire({
                    icon: 'success',
                    title: 'Saved!',
                    text: data.message,
                    timer: 3000,
                    showConfirmButton: false
                });
                
                // Reload the page to show updated data
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                // Show error message using SweetAlert2
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message || 'Unknown error',
                });
            }
        })
        .catch(error => {
            // Restore button
            saveButton.innerHTML = originalButtonText;
            saveButton.disabled = false;
            
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'An error occurred while saving',
            });
        });
    });
});
