document.addEventListener('DOMContentLoaded', function(){
  var showBtn = document.getElementById('showAddBtn');
  var addForm = document.getElementById('addForm');
  if (showBtn && addForm) {
    showBtn.addEventListener('click', function(){ addForm.style.display = (addForm.style.display === 'none' ? 'block' : 'none'); });
  }
});

// Sidebar toggle for mobile
document.addEventListener("DOMContentLoaded", function () {
  const sidebar = document.querySelector(".sidebar");
  const toggle = document.getElementById("sidebarToggle");
  if (sidebar && toggle) {
    toggle.addEventListener("click", () => {
      sidebar.classList.toggle("hidden");
    });
  }
});

