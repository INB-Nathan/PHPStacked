/**
 * Election status updater
 * Provides functionality to periodically check and update election statuses
 * in the UI without requiring a page reload.
 */

document.addEventListener('DOMContentLoaded', function() {
    // Check if we're on a page that displays elections
    if (document.querySelector('.election-card')) {
        // Initial update
        updateElectionStatuses();
        
        // Set up periodic updates every minute
        setInterval(updateElectionStatuses, 60000);
    }
});

/**
 * Fetches current election statuses from the server and updates the UI
 */
function updateElectionStatuses() {
    fetch('check_election_statuses.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            // Update each election card with the latest status
            data.elections.forEach(election => {
                const electionCard = document.querySelector(`.election-card[data-election-id="${election.id}"]`);
                if (electionCard) {
                    // Update the status badge
                    const statusBadge = electionCard.querySelector('.status-badge');
                    if (statusBadge) {
                        statusBadge.className = 'status-badge';
                        statusBadge.classList.add(`status-${election.status.toLowerCase()}`);
                        statusBadge.textContent = election.status.charAt(0).toUpperCase() + election.status.slice(1);
                    }
                    
                    // Show/hide vote button based on status
                    const voteButton = electionCard.querySelector('.vote-button');
                    const votedBadge = electionCard.querySelector('.voted-badge');
                    
                    if (election.status === 'active' && !election.has_voted) {
                        if (voteButton) voteButton.style.display = 'inline-block';
                        if (votedBadge) votedBadge.style.display = 'none';
                    } else {
                        if (voteButton) voteButton.style.display = 'none';
                        
                        // Show voted badge only if they've actually voted
                        if (votedBadge) {
                            votedBadge.style.display = election.has_voted ? 'inline-block' : 'none';
                        }
                        
                        
                    // If election is now completed, remove it from the UI since we don't show completed elections
                    if (election.status === 'completed') {
                        // If there's a parent container, fade out and remove the card
                        if (electionCard.parentNode) {
                            electionCard.style.transition = 'opacity 0.5s ease';
                            electionCard.style.opacity = '0';
                            
                            // Remove after transition
                            setTimeout(() => {
                                electionCard.remove();
                                
                                // Check if this was the last card in the section
                                const section = document.querySelector('.election-section:first-child');
                                if (section && section.querySelectorAll('.election-card').length === 0) {
                                    // Add "no elections" message
                                    const message = document.createElement('p');
                                    message.className = 'no-elections';
                                    message.textContent = 'No active elections available at this time.';
                                    message.style.opacity = '0';
                                    section.appendChild(message);
                                    
                                    // Fade in the message
                                    setTimeout(() => {
                                        message.style.transition = 'opacity 0.5s ease';
                                        message.style.opacity = '1';
                                    }, 10);
                                }
                            }, 500);
                        }
                        return; // Skip further updates for completed elections
                    }
                    }
                }
            });
            
            // If no active elections, update the section message
            const activeSection = document.querySelector('.election-section:first-child');
            if (activeSection && data.elections.filter(e => e.status === 'active').length === 0) {
                const noElectionsMsg = activeSection.querySelector('.no-elections');
                if (!noElectionsMsg) {
                    const existingCards = activeSection.querySelectorAll('.election-card');
                    if (existingCards.length === 0) {
                        const message = document.createElement('p');
                        message.className = 'no-elections';
                        message.textContent = 'No active elections available at this time.';
                        activeSection.appendChild(message);
                    }
                }
            }
        })
        .catch(error => {
            console.error('Error updating election statuses:', error);
        });
}
