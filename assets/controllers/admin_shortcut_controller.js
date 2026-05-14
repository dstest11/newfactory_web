import { Controller } from '@hotwired/stimulus';

/**
 * Mounts on <body>. Always-on (even for anonymous viewers) so the keyboard
 * shortcut Ctrl+Shift+A (Cmd+Shift+A on macOS) works without exposing any
 * visible affordance. Behaviour:
 *
 *   - Not logged in: shortcut navigates to the admin login page.
 *   - Logged in:     shortcut toggles the inline-edit controller's "active"
 *                    mode by re-dispatching as a `inline-edit:toggle` event.
 *
 * The login redirect URL + logged-in flag come from data-values written by
 * Twig in base.html.twig (server-side truth source).
 */
export default class extends Controller {
    static values = {
        loginPath: { type: String, default: '/admin/login' },
        loggedIn: { type: Boolean, default: false },
    };

    connect() {
        this.boundHandle = this.handleKeydown.bind(this);
        document.addEventListener('keydown', this.boundHandle);
    }

    disconnect() {
        document.removeEventListener('keydown', this.boundHandle);
    }

    handleKeydown(event) {
        // Ctrl+Shift+A on Win/Linux, Cmd+Shift+A on macOS. Use code 'KeyA'
        // (avoids issues with non-Latin layouts changing event.key).
        const modPressed = event.ctrlKey || event.metaKey;
        if (!modPressed || !event.shiftKey || event.code !== 'KeyA') {
            return;
        }
        event.preventDefault();

        if (!this.loggedInValue) {
            window.location.assign(this.loginPathValue);
            return;
        }

        // Logged in — toggle inline-edit. The inline-edit controller listens
        // for the custom event on the same body element it's mounted on.
        document.body.dispatchEvent(new CustomEvent('inline-edit:toggle'));
    }
}
