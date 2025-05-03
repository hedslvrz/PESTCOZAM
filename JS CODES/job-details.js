// Report Modal Functions
function showReportModal() {
    const modal = document.getElementById('reportModal');
    if (modal) {
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
    }
}

function closeReportModal() {
    const modal = document.getElementById('reportModal');
    if (modal) {
        modal.classList.remove('show');
        document.body.style.overflow = '';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Function to toggle treatment type dropdown
    window.toggleTreatmentType = function(event) {
        const header = event.currentTarget;
        const type = header.closest('.treatment-type') || header.closest('.standalone-treatment-type');
        const content = type.querySelector('.treatment-type-content');
        const icon = header.querySelector('.toggle-icon');
        
        type.classList.toggle('active');
        
        // Get the section content container - this is what we need to fix
        const sectionContent = type.closest('.section-content');
        
        if (type.classList.contains('active')) {
            // Show content
            content.style.display = 'block';
            icon.classList.remove('bx-chevron-down');
            icon.classList.add('bx-chevron-up');
            
            // Reset any fixed heights
            content.style.height = 'auto';
            
            // Ensure proper section sizing
            if (sectionContent) sectionContent.style.height = 'auto';
        } else {
            // Hide content
            content.style.display = 'none';
            icon.classList.remove('bx-chevron-up');
            icon.classList.add('bx-chevron-down');
            
            // Ensure proper section sizing
            if (sectionContent) sectionContent.style.height = 'auto';
        }
        
        // Fix parent container sizing - force reflow
        if (sectionContent) {
            sectionContent.style.minHeight = '0';
            sectionContent.offsetHeight; // Force reflow
        }
        
        // Fix any adjacent element spacing issues
        const detailSection = type.closest('.detail-section');
        if (detailSection) {
            detailSection.style.height = 'auto';
            detailSection.style.minHeight = '0';
        }
    };

    window.toggleTreatmentMethod = function(event) {
        const header = event.currentTarget;
        const method = header.closest('.treatment-method');
        const content = method.querySelector('.treatment-method-content');
        const icon = header.querySelector('.toggle-icon');
        
        method.classList.toggle('active');
        
        // Get parent containers
        const typeContent = method.closest('.treatment-type-content');
        const sectionContent = method.closest('.section-content');
        
        if (method.classList.contains('active')) {
            content.style.display = 'block';
            icon.classList.remove('bx-chevron-down');
            icon.classList.add('bx-chevron-up');
            
            // Ensure content takes natural height
            content.style.height = 'auto';
        } else {
            content.style.display = 'none';
            icon.classList.remove('bx-chevron-up');
            icon.classList.add('bx-chevron-down');
        }
        
        // Fix parent containers
        if (typeContent) typeContent.style.height = 'auto';
        if (sectionContent) {
            sectionContent.style.height = 'auto';
            sectionContent.style.minHeight = '0';
        }
        
        // Fix detail section container
        const detailSection = method.closest('.detail-section');
        if (detailSection) {
            detailSection.style.height = 'auto';
        }
    };
    
    // Function to fix all container heights after DOM operations
    function fixContainerHeights() {
        // Fix all detail sections
        document.querySelectorAll('.detail-section').forEach(section => {
            section.style.height = 'auto';
            section.style.minHeight = '0';
        });
        
        // Fix all section content areas
        document.querySelectorAll('.section-content').forEach(content => {
            content.style.height = 'auto';
            content.style.minHeight = '0';
        });
        
        // Fix all treatment type content areas
        document.querySelectorAll('.treatment-type-content').forEach(content => {
            if (content.closest('.treatment-type').classList.contains('active')) {
                content.style.height = 'auto';
            }
        });
    }
    
    // Call this function after any potential DOM height changes
    window.addEventListener('resize', fixContainerHeights);
    
    // Initial call to fix any height issues on load
    setTimeout(fixContainerHeights, 100);

    // Function to handle quantity input based on checkbox status
    function setupQuantityToggle(checkbox, quantityInput) {
        // Initial state setup
        if (!checkbox.checked) {
            quantityInput.disabled = true;
            quantityInput.value = '0';
        } else {
            quantityInput.disabled = false;
            if (parseInt(quantityInput.value) === 0) {
                quantityInput.value = '1';
            }
        }
        
        // Toggle quantity input when checkbox changes
        checkbox.addEventListener('change', function() {
            if (this.checked) {
                quantityInput.disabled = false;
                if (parseInt(quantityInput.value) === 0) {
                    quantityInput.value = '1';
                }
                quantityInput.focus();
            } else {
                quantityInput.disabled = true;
                quantityInput.value = '0';
            }
        });
    }

    // Set up all checkboxes and quantity inputs
    document.querySelectorAll('input[name="chemicals[]"], input[name="devices[]"]').forEach(checkbox => {
        const chemicalItem = checkbox.closest('.chemical-item');
        if (chemicalItem) {
            const quantityInput = chemicalItem.querySelector('input[type="number"]');
            if (quantityInput) {
                setupQuantityToggle(checkbox, quantityInput);
            }
        }
    });

    // New add method functionality using inline input rather than prompt
    const addMethodButton = document.querySelector('.add-method-btn');
    if (addMethodButton) {
        addMethodButton.addEventListener('click', function() {
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
    }

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

    // Enhanced add custom chemical functionality with better quantity handling
    const addChemicalButton = document.querySelector('.add-chemical-btn');
    if (addChemicalButton) {
        addChemicalButton.addEventListener('click', function() {
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
    }

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
                const typeHeader = customType.querySelector('.treatment-type-header');
                const methodHeader = customType.querySelector('.treatment-method-header');
                
                if (typeHeader) typeHeader.addEventListener('click', window.toggleTreatmentType);
                if (methodHeader) methodHeader.addEventListener('click', window.toggleTreatmentMethod);
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
            
            // Add event listener to the new checkbox and quantity input
            const checkbox = div.querySelector('input[type="checkbox"]');
            const quantityInput = div.querySelector('input[type="number"]');
            if (checkbox && quantityInput) {
                setupQuantityToggle(checkbox, quantityInput);
            }
        }
        const inputElem = document.querySelector('.new-chemical-input');
        if (inputElem) inputElem.remove();
    }

    // Add event listeners for treatment type headers
    document.querySelectorAll('.treatment-type-header').forEach(header => {
        header.addEventListener('click', window.toggleTreatmentType);
    });

    // Add event listeners for treatment method headers
    document.querySelectorAll('.treatment-method-header').forEach(header => {
        header.addEventListener('click', window.toggleTreatmentMethod);
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

    // Add change event to quantity inputs to ensure they're valid numbers
    document.querySelectorAll('.quantity-input').forEach(input => {
        input.addEventListener('change', function() {
            // Ensure the value is valid
            let value = parseInt(this.value);
            if (isNaN(value) || value < 0) {
                this.value = 0;
            }
            
            // If checkbox is checked but quantity is 0, set it to 1
            const parentItem = this.closest('.chemical-item');
            const checkbox = parentItem?.querySelector('input[type="checkbox"]');
            
            if (checkbox && checkbox.checked && parseInt(this.value) === 0) {
                this.value = 1;
            }
        });
    });

    // Handle form submission with AJAX
    const jobDetailsForm = document.getElementById('jobDetailsForm');
    if (jobDetailsForm) {
        jobDetailsForm.addEventListener('submit', function(e) {
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
    }
    
    // Handle report form submission
    const reportForm = document.getElementById('reportForm');
    if (reportForm) {
        reportForm.addEventListener('submit', async function(e) {
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
    }

    // After setting up treatment methods or adding chemicals
    document.querySelectorAll('.add-method-btn, .add-chemical-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            // Fix heights after adding new elements
            setTimeout(fixContainerHeights, 300);
        });
    });

    // Call fixContainerHeights after auto-expanding sections
    setTimeout(fixContainerHeights, 300);
});
