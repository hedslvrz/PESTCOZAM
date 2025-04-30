<!-- Change Password Modal -->
<div id="changePasswordModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h3>Change Password</h3>
        <form id="changePasswordForm">
            <div class="form-group">
                <label for="current_password">Current Password</label>
                <input type="password" id="current_password" name="current_password" required>
            </div>
            
            <div class="form-group">
                <label for="new_password">New Password</label>
                <input type="password" id="new_password" name="new_password" required>
                
                <div class="password-strength-meter">
                    <div class="strength-fill"></div>
                </div>
                <div class="password-strength-text"></div>
                
                <div class="password-requirements">
                    <p>Password must:</p>
                    <ul>
                        <li>Be at least 8 characters long</li>
                        <li>Contain at least one letter</li>
                        <li>Contain at least one number</li>
                        <li>For stronger security, include uppercase, lowercase, and special characters</li>
                    </ul>
                </div>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
                <div class="password-match-status"></div>
            </div>
            
            <div class="modal-buttons">
                <button type="button" class="cancel-button">Cancel</button>
                <button type="submit" class="save-password">Save Changes</button>
            </div>
        </form>
    </div>
</div>
