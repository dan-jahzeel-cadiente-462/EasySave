function createBackup(format) {
    const btn = event.target.closest('button');
    btn.disabled = true;
    btn.textContent = 'Creating backup...';

    fetch('{{ path("app_admin_account_create_backup") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({format: format})
    }).then(response => {
        if (response.ok) {
            alert('Backup created successfully!');
            location.reload();
        } else {
            alert('Failed to create backup');
            btn.disabled = false;
            btn.textContent = 'Create ' + format.toUpperCase() + ' Backup';
        }
    }).catch(error => {
        alert('Error creating backup: ' + error);
        btn.disabled = false;
        btn.textContent = 'Create ' + format.toUpperCase() + ' Backup';
    });
}

function deleteAllData() {
    if (confirm('⚠️ Are you absolutely sure? This will DELETE ALL your data permanently. This action CANNOT be undone.\n\nType "DELETE" to confirm.')) {
        const confirmText = prompt('Please type DELETE to confirm:');
        if (confirmText === 'DELETE') {
            fetch('{{ path("app_admin_account_delete_data") }}', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }).then(response => {
                if (response.ok) {
                    alert('All data has been deleted. You will be logged out.');
                    window.location.href = '{{ path("app_home") }}';
                } else {
                    alert('Failed to delete data');
                }
            });
        }
    }
}