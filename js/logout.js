document.getElementById("logoutNavBtn").onclick = function (e) {
  e.preventDefault();
  document.getElementById("logoutModal").classList.add("active");
};
document.getElementById("cancelLogoutBtn").onclick = function () {
  document.getElementById("logoutModal").classList.remove("active");
};
document.getElementById("logoutModal").onclick = function (e) {
  if (e.target === this) this.classList.remove("active");
};
