const customModal = {
    init() {
        if (!document.getElementById('customModal')) {
            const modalHTML = `
                <div id="customModal" class="modal">
                    <div class="modal-content">
                        <span class="close">&times;</span>
                        <h2 id="modalTitle"></h2>
                        <div id="modalMessage"></div>
                        <div class="form-buttons">
                            <button type="button" class="save-btn" id="modalOkBtn">OK</button>
                        </div>
                    </div>
                </div>
            `;
            document.body.insertAdjacentHTML('beforeend', modalHTML);
        }

        // Get modal elements
        const modal = document.getElementById('customModal');
        const closeBtn = modal.querySelector('.close');
        const okBtn = document.getElementById('modalOkBtn');

        // Close modal when clicking X
        closeBtn.onclick = () => this.hide();

        // Close modal when clicking outside
        window.onclick = (event) => {
            if (event.target == modal) {
                this.hide();
            }
        };
    },

    show(message, title = 'Message', callback = null) {
        this.init();
        const modal = document.getElementById('customModal');
        document.getElementById('modalTitle').textContent = title;
        document.getElementById('modalMessage').textContent = message;
        
        const okButton = document.getElementById('modalOkBtn');
        okButton.onclick = () => {
            this.hide();
            if (callback) callback();
        };
        
        modal.style.display = 'flex';
    },

    hide() {
        const modal = document.getElementById('customModal');
        modal.style.display = 'none';
    },

    showError(message, callback = null) {
        this.show(message, 'Error', callback);
        const modalContent = document.querySelector('.modal-content');
        modalContent.style.borderTop = '4px solid #dc3545';
    },

    showSuccess(message, callback = null) {
        this.show(message, 'Success', callback);
        const modalContent = document.querySelector('.modal-content');
        modalContent.style.borderTop = '4px solid #28a745';
    },

    showWarning(message, callback = null) {
        this.show(message, 'Warning', callback);
        const modalContent = document.querySelector('.modal-content');
        modalContent.style.borderTop = '4px solid #ffc107';
    },

    showConfirm(message, onConfirm, onCancel = null) {
        this.init();
        const modal = document.getElementById('customModal');
        document.getElementById('modalTitle').textContent = 'Confirm';
        document.getElementById('modalMessage').textContent = message;
        
        const buttonsContainer = modal.querySelector('.form-buttons');
        buttonsContainer.innerHTML = `
            <button id="modalConfirmBtn" class="save-btn">Yes</button>
            <button id="modalCancelBtn" class="save-btn">No</button>
        `;
        
        document.getElementById('modalConfirmBtn').onclick = () => {
            this.hide();
            if (onConfirm) onConfirm();
        };
        
        document.getElementById('modalCancelBtn').onclick = () => {
            this.hide();
            if (onCancel) onCancel();
        };
        
        modal.style.display = 'flex';
        const modalContent = document.querySelector('.modal-content');
        modalContent.style.borderTop = '4px solid #ffc107';
    },

    showAssignmentSuccess(technicianName) {
        // Save current section before refresh
        const currentSection = document.querySelector('.section.active').id;
        localStorage.setItem('activeSection', currentSection);
        
        this.show(
            `Successfully assigned to ${technicianName}!`,
            'Assignment Complete',
            () => window.location.reload()
        );
        const modalContent = document.querySelector('.modal-content');
        modalContent.style.borderTop = '4px solid #28a745';
    },

    showUpdateSuccess(message = "Technician information updated successfully!") {
        this.show(
            message,
            'Update Successful',
            () => window.location.reload()
        );
        const modalContent = document.querySelector('.modal-content');
        modalContent.style.borderTop = '4px solid #28a745';
    },

    handleLogout() {
        this.show(
            "Are you sure you want to logout?",
            'Logout Confirmation',
            () => {
                window.location.href = "../HTML CODES/login.php";
            }
        );
    }
};

// Replace common alerts with custom modals
window.alert = function(message) {
    customModal.show(message);
};

window.confirm = function(message) {
    return new Promise((resolve) => {
        customModal.showConfirm(message, 
            () => resolve(true),
            () => resolve(false)
        );
    });
};
