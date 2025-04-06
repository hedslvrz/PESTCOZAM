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

// Add new treatment method
document.querySelector('.add-method-btn').addEventListener('click', function() {
    const input = prompt('Enter new treatment method:');
    if (input) {
        const checkboxGroup = this.closest('.checkbox-group');
        const label = document.createElement('label');
        label.innerHTML = `
            <input type="checkbox" name="method[]" value="${input.toLowerCase()}">
            ${input}
        `;
        checkboxGroup.insertBefore(label, this);
    }
});

// Add new chemical
document.querySelector('.add-chemical-btn').addEventListener('click', function() {
    const input = prompt('Enter new chemical name:');
    if (input) {
        const checkboxGroup = this.closest('.checkbox-group');
        const label = document.createElement('label');
        label.innerHTML = `
            <input type="checkbox" name="chemicals[]" value="${input.toLowerCase()}">
            ${input}
        `;
        checkboxGroup.insertBefore(label, this);
    }
});

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
            alert('Report submitted successfully');
            closeReportModal();
        } else {
            alert('Error submitting report: ' + result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('An error occurred while submitting the report');
    }
});
