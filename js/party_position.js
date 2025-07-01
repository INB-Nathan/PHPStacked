document.addEventListener("DOMContentLoaded", function () {
  document.querySelectorAll(".party-name-link").forEach((link) => {
    link.addEventListener("click", function () {
      const partyId = this.dataset.partyid;
      const partyName = this.dataset.partyname;
      const modal = document.getElementById("partyMembersModal");
      const inner = document.getElementById("partyMembersInner");
      inner.innerHTML =
        '<div style="text-align:center;padding:30px;">Loading...</div>';
      modal.classList.add("active");

      fetch("party_members.php?id=" + encodeURIComponent(partyId))
        .then((resp) => resp.text())
        .then((html) => {
          inner.innerHTML = `<h2 style="margin-top:0;">${partyName} Members</h2>${html}`;
        })
        .catch(() => {
          inner.innerHTML =
            '<div style="color:red;text-align:center;">Failed to load members.</div>';
        });
    });
  });

  document.getElementById("closeModalBtn").onclick = () => {
    document.getElementById("partyMembersModal").classList.remove("active");
  };
  document.getElementById("modalBlurBG").onclick = () => {
    document.getElementById("partyMembersModal").classList.remove("active");
  };

  document.getElementById("logoutNavBtn").onclick = (e) => {
    e.preventDefault();
    document.getElementById("logoutModal").classList.add("active");
  };
  document.getElementById("cancelLogoutBtn").onclick = () => {
    document.getElementById("logoutModal").classList.remove("active");
  };
  document.getElementById("logoutModal").onclick = (e) => {
    if (e.target === e.currentTarget) {
      document.getElementById("logoutModal").classList.remove("active");
    }
  };
});
