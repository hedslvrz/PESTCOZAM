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
