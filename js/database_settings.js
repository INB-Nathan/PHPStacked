document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    
    const actionButtons = document.querySelectorAll('.action-btn');
    const confirmationModal = document.getElementById('confirmationModal');
    const modalMessage = document.getElementById('modalMessage');
    const actionInput = document.getElementById('actionInput');
    const cancelActionBtn = document.getElementById('cancelActionBtn');
    
    actionButtons.forEach(button => {
        button.addEventListener('click', function() {
            const action = this.dataset.action;
            
            switch(action) {
                case 'clear_users':
                    modalMessage.textContent = "Are you sure you want to delete all voter accounts? This action cannot be undone.";
                    break;
                case 'clear_elections':
                    modalMessage.textContent = "Are you sure you want to delete all elections and their associated data? This action cannot be undone.";
                    break;
                case 'clear_positions':
                    modalMessage.textContent = "Are you sure you want to delete all positions? This will also remove candidates. This action cannot be undone.";
                    break;
                case 'clear_parties':
                    modalMessage.textContent = "Are you sure you want to delete all political parties (except Independent)? This action cannot be undone.";
                    break;
                case 'clear_votes':
                    modalMessage.textContent = "Are you sure you want to delete all votes? Election results will be lost. This action cannot be undone.";
                    break;
                case 'reset_database':
                    modalMessage.textContent = "WARNING: This will reset the ENTIRE database to its initial state. All data except administrator accounts will be permanently deleted. This action CANNOT be undone.";
                    break;
            }
            
            actionInput.value = action;
            confirmationModal.style.display = 'flex';
            document.getElementById('adminPassword').focus();
        });
    });
    
    cancelActionBtn.addEventListener('click', function() {
        confirmationModal.style.display = 'none';
    });
    
    confirmationModal.addEventListener('click', function(e) {
        if (e.target === confirmationModal) {
            confirmationModal.style.display = 'none';
        }
    });
});