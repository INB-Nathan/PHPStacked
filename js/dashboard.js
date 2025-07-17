document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.dashboard-list li[data-href]').forEach(function(item) {
        item.addEventListener('click', function() {
            window.location.href = item.getAttribute('data-href');
        });
    });
});