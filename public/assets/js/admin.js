/**
 * ELMS Admin — AJAX license actions.
 *
 * Buttons/links with class "elms-action" perform a POST to
 * /admin/licenses/{id}/{action} with the CSRF token, then reload.
 * Elements with class "elms-renew" post a renew action with a date.
 */
(function () {
    'use strict';

    const BASE = '/license/public';

    function csrf() {
        return window.ELMS_CSRF || '';
    }

    async function postAction(id, action, extra) {
        const body = new URLSearchParams();
        body.set('_csrf', csrf());
        if (extra) {
            Object.keys(extra).forEach((k) => body.set(k, extra[k]));
        }
        const res = await fetch(`${BASE}/admin/licenses/${id}/${action}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body.toString(),
        });
        let data = {};
        try { data = await res.json(); } catch (e) { data = { status: false, message: 'Unexpected response' }; }
        return data;
    }

    function confirmMsg(action) {
        const map = {
            suspend: 'Suspend this license?',
            unsuspend: 'Unsuspend this license?',
            terminate: 'Permanently terminate this license?',
            reset: 'Reset domain/IP/activation bindings?',
            delete: 'Delete this license permanently?',
        };
        return map[action] || 'Are you sure?';
    }

    document.addEventListener('click', async function (e) {
        const actionEl = e.target.closest('.elms-action');
        if (actionEl) {
            e.preventDefault();
            const id = actionEl.getAttribute('data-id');
            const action = actionEl.getAttribute('data-action');
            if (!window.confirm(confirmMsg(action))) return;

            actionEl.classList.add('disabled');
            const data = await postAction(id, action);
            if (data.status) {
                window.location.reload();
            } else {
                alert(data.message || 'Action failed');
                actionEl.classList.remove('disabled');
            }
            return;
        }

        const renewEl = e.target.closest('.elms-renew');
        if (renewEl) {
            e.preventDefault();
            const id = renewEl.getAttribute('data-id');
            const input = document.getElementById('renewDate');
            const date = input ? input.value : '';
            if (!date) { alert('Pick a new expiry date first.'); return; }
            const data = await postAction(id, 'renew', { expiry_date: date });
            if (data.status) {
                window.location.reload();
            } else {
                alert(data.message || 'Renew failed');
            }
        }
    });
})();
