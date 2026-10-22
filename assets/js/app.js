/**
 * NovaServe AI - Global Application Utility & Toast Service
 */

window.showToast = function(message, type = 'primary') {
    const toastEl = document.getElementById('systemToast');
    const toastMsg = document.getElementById('toastMessage');
    if (!toastEl || !toastMsg) return;

    // Reset styles
    toastEl.className = 'toast align-items-center text-white border-0';
    if (type === 'success') {
        toastEl.classList.add('bg-success');
    } else if (type === 'danger') {
        toastEl.classList.add('bg-danger');
    } else if (type === 'warning') {
        toastEl.classList.add('bg-warning', 'text-dark');
    } else {
        toastEl.classList.add('bg-primary');
    }

    toastMsg.textContent = message;
    const toastInstance = new bootstrap.Toast(toastEl, { delay: 4000 });
    toastInstance.show();
};
