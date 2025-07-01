function updatePositionsAndParties() {
  const electionId = document.getElementById("election_id").value;
  const positionDropdown = document.getElementById("position_id");
  const partyDropdown = document.getElementById("party_id");
  const positionLoading = document.getElementById("position-loading");
  const partyLoading = document.getElementById("party-loading");
  const csrfToken = document
    .querySelector('meta[name="csrf-token"]')
    .getAttribute("content");

  if (!electionId) {
    positionDropdown.disabled = true;
    positionDropdown.innerHTML =
      '<option value="">-- Select Election First --</option>';
    return;
  }

  positionDropdown.disabled = true;
  if (positionLoading) positionLoading.style.display = "block";

  fetch(
    `candidates.php?action=get_positions_by_election&election_id=${electionId}`,
    {
      headers: {
        "X-Requested-With": "XMLHttpRequest",
      },
    }
  )
    .then((response) => response.json())
    .then((positions) => {
      if (positionLoading) positionLoading.style.display = "none";
      positionDropdown.disabled = false;

      positionDropdown.innerHTML = "";

      if (positions.length === 0) {
        const option = document.createElement("option");
        option.value = "";
        option.textContent = "No positions available for this election";
        positionDropdown.appendChild(option);
        positionDropdown.disabled = true;
      } else {
        const defaultOption = document.createElement("option");
        defaultOption.value = "";
        defaultOption.textContent = "-- Select Position --";
        positionDropdown.appendChild(defaultOption);

        positions.forEach((position) => {
          const option = document.createElement("option");
          option.value = position.id;
          option.textContent = position.position_name;
          positionDropdown.appendChild(option);
        });
      }
    })
    .catch((error) => {
      console.error("Error fetching positions:", error);
      if (positionLoading) positionLoading.style.display = "none";
      positionDropdown.innerHTML =
        '<option value="">Error loading positions</option>';
    });

  if (partyLoading) partyLoading.style.display = "block";

  fetch(
    `candidates.php?action=get_parties_by_election&election_id=${electionId}`,
    {
      headers: {
        "X-Requested-With": "XMLHttpRequest",
      },
    }
  )
    .then((response) => response.json())
    .then((parties) => {
      if (partyLoading) partyLoading.style.display = "none";

      partyDropdown.innerHTML = "";

      const independentOption = document.createElement("option");
      independentOption.value = "1";
      independentOption.textContent = "Independent";
      partyDropdown.appendChild(independentOption);

      parties.forEach((party) => {
        if (party.name.toLowerCase() === "independent") return;

        const option = document.createElement("option");
        option.value = party.id;
        option.textContent = party.name;
        partyDropdown.appendChild(option);
      });
    })
    .catch((error) => {
      console.error("Error fetching parties:", error);
      if (partyLoading) partyLoading.style.display = "none";
    });
}

document.addEventListener("DOMContentLoaded", function () {
  const electionDropdown = document.getElementById("election_id");
  if (electionDropdown && electionDropdown.value) {
    updatePositionsAndParties();
  }

  if (typeof logoutNavBtnClickHandler === "undefined") {
    const logoutNavBtn = document.getElementById("logoutNavBtn");
    const logoutModal = document.getElementById("logoutModal");
    const cancelLogoutBtn = document.getElementById("cancelLogoutBtn");

    if (logoutNavBtn) {
      logoutNavBtn.onclick = function (e) {
        e.preventDefault();
        logoutModal.classList.add("active");
      };
    }

    if (cancelLogoutBtn) {
      cancelLogoutBtn.onclick = function () {
        logoutModal.classList.remove("active");
      };
    }

    if (logoutModal) {
      logoutModal.onclick = function (e) {
        if (e.target === this) this.classList.remove("active");
      };
    }
  }
});
