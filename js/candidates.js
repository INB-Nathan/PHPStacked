/**
 * Candidates.js - Handles dynamic loading of positions and parties
 * based on selected election in the candidate form
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize the form
    initCandidateForm();
});

/**
 * Initialize the candidate form
 */
function initCandidateForm() {
    const electionDropdown = document.getElementById('election_id');
    
    // Only continue if we're on a page with the election dropdown
    if (!electionDropdown) return;
    
    // Add change event listener
    electionDropdown.addEventListener('change', updatePositionsAndParties);
    
    // If an election is already selected (edit mode), load positions and parties
    if (electionDropdown.value) {
        updatePositionsAndParties();
    }
}

/**
 * Update position and party dropdowns based on selected election
 */
function updatePositionsAndParties() {
    const electionId = document.getElementById('election_id').value;
    const positionDropdown = document.getElementById('position_id');
    const partyDropdown = document.getElementById('party_id');
    const positionLoading = document.getElementById('position-loading');
    const partyLoading = document.getElementById('party-loading');
    
    // If no election selected, disable both dropdowns
    if (!electionId) {
        disableDropdown(positionDropdown, '-- Select Election First --');
        disableDropdown(partyDropdown, '-- Select Election First --');
        return;
    }
    
    // Load positions
    loadPositions(electionId, positionDropdown, positionLoading);
    
    // Load parties
    loadParties(electionId, partyDropdown, partyLoading);
}

/**
 * Load positions for the selected election
 */
function loadPositions(electionId, dropdown, loadingElement) {
    disableDropdown(dropdown, 'Loading...');
    showLoading(loadingElement);
    
    fetch(`candidates.php?action=get_positions_by_election&election_id=${electionId}`)
        .then(handleResponse)
        .then(positions => {
            hideLoading(loadingElement);
            
            // Clear dropdown
            dropdown.innerHTML = '';
            
            if (positions.length === 0) {
                disableDropdown(dropdown, 'No positions available for this election');
            } else {
                // Add default option
                addOption(dropdown, '', '-- Select Position --');
                
                // Add each position as an option
                positions.forEach(position => {
                    addOption(dropdown, position.id, position.position_name);
                });
                
                // Enable the dropdown
                dropdown.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error fetching positions:', error);
            hideLoading(loadingElement);
            disableDropdown(dropdown, 'Error loading positions');
        });
}

/**
 * Load parties for the selected election
 */
function loadParties(electionId, dropdown, loadingElement) {
    disableDropdown(dropdown, 'Loading...');
    showLoading(loadingElement);
    
    fetch(`candidates.php?action=get_parties_by_election&election_id=${electionId}`)
        .then(handleResponse)
        .then(parties => {
            hideLoading(loadingElement);
            
            // Clear dropdown
            dropdown.innerHTML = '';
            
            // Always add Independent option first
            addOption(dropdown, '0', 'Independent (No Party)');
            
            // Add all other parties
            parties.forEach(party => {
                // Skip any party named "Independent" since we already added our own
                if (party.name.toLowerCase() === 'independent') return;
                
                addOption(dropdown, party.id, party.name);
            });
            
            // Enable the dropdown
            dropdown.disabled = false;
        })
        .catch(error => {
            console.error('Error fetching parties:', error);
            hideLoading(loadingElement);
            disableDropdown(dropdown, 'Error loading parties');
        });
}

/**
 * Helper function to handle fetch response
 */
function handleResponse(response) {
    if (!response.ok) {
        throw new Error('Network response was not ok: ' + response.statusText);
    }
    return response.json();
}

/**
 * Helper function to show loading indicator
 */
function showLoading(element) {
    if (element) element.style.display = 'inline';
}

/**
 * Helper function to hide loading indicator
 */
function hideLoading(element) {
    if (element) element.style.display = 'none';
}

/**
 * Helper function to disable a dropdown and set a message
 */
function disableDropdown(dropdown, message) {
    dropdown.disabled = true;
    dropdown.innerHTML = '';
    addOption(dropdown, '', message);
}

/**
 * Helper function to add an option to a dropdown
 */
function addOption(dropdown, value, text) {
    const option = document.createElement('option');
    option.value = value;
    option.textContent = text;
    dropdown.appendChild(option);
}
