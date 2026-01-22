document.addEventListener('DOMContentLoaded', function() {
    const pendingTab = document.getElementById('pending-tab');
    const approvedTab = document.getElementById('approved-tab');
    const pendingTable = document.getElementById('pending-table');
    const approvedTable = document.getElementById('approved-table');

    if (!pendingTab || !approvedTab || !pendingTable || !approvedTable) return;

    pendingTab.addEventListener('click', function() {
        pendingTab.classList.add('active');
        approvedTab.classList.remove('active');
        pendingTable.style.display = '';
        approvedTable.style.display = 'none';
    });

    approvedTab.addEventListener('click', function() {
        approvedTab.classList.add('active');
        pendingTab.classList.remove('active');
        approvedTable.style.display = '';
        pendingTable.style.display = 'none';
    });
});
