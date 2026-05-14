import { Controller } from '@hotwired/stimulus';

/**
 * Inline content editor for editors with ROLE_EDITOR.
 *
 * Mounts on <body> when data-controller="inline-edit" is present (Twig only
 * outputs the binding for logged-in editors). Activation can be toggled via
 *   - clicking the "Zapnout editaci" button in the edit bar
 *   - dispatching the `inline-edit:toggle` event on the body (Ctrl+Shift+A)
 *
 * Editable elements opt in by setting:
 *
 *   data-cms-field="<type>:<documentIdOrSingle>:<path>"
 *
 *   - type:               Strapi pluralName slug, e.g. "newfactory-homepage",
 *                         "newfactory-faqs", "newfactory-products".
 *   - documentIdOrSingle: "single" for singleType OR the documentId for an
 *                         item in a collection.
 *   - path:               whitelisted field key (see dosmart_cms_core.editable_fields).
 *
 * On blur the controller PATCHes the bundle's CmsProxyController. The bundle
 * validates the path against the EditableFieldRegistry, writes to Strapi
 * (PUT /api/<plural>[/<documentId>] for singles/collections), and invalidates
 * the matching cache tag — that's the cache-bust path.
 */
export default class extends Controller {
    static targets = ['bar', 'toggle', 'toggleLabel'];
    static values = {
        apiBase: { type: String, default: '/api/cms' },
        csrfMeta: { type: String, default: 'cms-csrf-token' },
        activeClass: { type: String, default: 'cms-edit-active' },
    };

    connect() {
        this.editables = [];
        this.active = false;
        this.csrfToken = this.readCsrfToken();
        this.boundToggle = this.toggle.bind(this);
        document.body.addEventListener('inline-edit:toggle', this.boundToggle);
        // Beware: deactivated by default so editor can read the page without
        // border noise. First Ctrl+Shift+A or button click switches it on.
    }

    disconnect() {
        document.body.removeEventListener('inline-edit:toggle', this.boundToggle);
        this.disableAll();
    }

    readCsrfToken() {
        const meta = document.querySelector(`meta[name="${this.csrfMetaValue}"]`);
        return meta ? meta.getAttribute('content') : null;
    }

    toggle() {
        if (this.active) {
            this.disableAll();
            this.active = false;
        } else {
            this.enableAll();
            this.active = true;
        }
        this.updateUi();
    }

    updateUi() {
        document.body.classList.toggle(this.activeClassValue, this.active);
        if (this.hasToggleLabelTarget) {
            this.toggleLabelTarget.textContent = this.active ? 'Vypnout editaci' : 'Zapnout editaci';
        }
        if (this.hasToggleTarget) {
            this.toggleTarget.classList.toggle('cms-edit-bar__toggle--on', this.active);
        }
    }

    enableAll() {
        const nodes = document.querySelectorAll('[data-cms-field]');
        nodes.forEach((el) => {
            if (el.dataset._cmsBound === '1') return;
            el.dataset._cmsBound = '1';
            el.contentEditable = 'true';
            el.classList.add('cms-edit-field');

            const onFocus = () => {
                el.dataset._cmsOriginal = el.innerHTML;
                el.classList.add('cms-edit-field--editing');
            };
            const onBlur = () => {
                el.classList.remove('cms-edit-field--editing');
                const next = el.innerText.trim();
                const prev = (el.dataset._cmsOriginal || '').replace(/<[^>]*>/g, '').trim();
                if (next === prev) {
                    return;
                }
                this.saveField(el, next);
            };
            const onKey = (event) => {
                if (event.key === 'Escape') {
                    event.preventDefault();
                    el.innerHTML = el.dataset._cmsOriginal || '';
                    el.blur();
                    return;
                }
                if (event.key === 'Enter') {
                    const tag = (el.tagName || '').toLowerCase();
                    const headingLike = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'span', 'strong'];
                    if (!event.shiftKey && headingLike.includes(tag)) {
                        event.preventDefault();
                        el.blur();
                    }
                }
            };

            el.addEventListener('focus', onFocus);
            el.addEventListener('blur', onBlur);
            el.addEventListener('keydown', onKey);
            this.editables.push({ el, onFocus, onBlur, onKey });
        });
    }

    disableAll() {
        this.editables.forEach(({ el, onFocus, onBlur, onKey }) => {
            el.contentEditable = 'false';
            el.classList.remove('cms-edit-field', 'cms-edit-field--editing', 'cms-edit-field--saving', 'cms-edit-field--ok', 'cms-edit-field--err');
            el.removeEventListener('focus', onFocus);
            el.removeEventListener('blur', onBlur);
            el.removeEventListener('keydown', onKey);
            delete el.dataset._cmsBound;
        });
        this.editables = [];
    }

    /**
     * Save a single field. Parses data-cms-field="<type>:<id|single>:<path>",
     * builds the proxy URL, PATCHes JSON, then paints status.
     */
    async saveField(el, value) {
        const raw = el.dataset.cmsField || '';
        const parts = raw.split(':');
        if (parts.length < 3) {
            this.paintStatus(el, 'err', 'Bad data-cms-field');
            return;
        }
        const [type, id, ...pathParts] = parts;
        const path = pathParts.join(':');

        const url = (id === 'single' || id === '' )
            ? `${this.apiBaseValue}/single/${encodeURIComponent(type)}`
            : `${this.apiBaseValue}/collection/${encodeURIComponent(type)}/${encodeURIComponent(id)}`;

        el.classList.add('cms-edit-field--saving');
        try {
            const res = await fetch(url, {
                method: 'PATCH',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-Token': this.csrfToken || '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ path, value }),
            });
            if (res.ok) {
                this.paintStatus(el, 'ok', 'Uloženo');
                return;
            }
            // Roll back on error.
            el.innerHTML = el.dataset._cmsOriginal || '';
            let errorMsg = `HTTP ${res.status}`;
            try {
                const body = await res.json();
                if (body && body.error) errorMsg = body.error;
            } catch (_) { /* ignore parse errors */ }
            this.paintStatus(el, 'err', errorMsg);
        } catch (e) {
            el.innerHTML = el.dataset._cmsOriginal || '';
            this.paintStatus(el, 'err', 'Strapi nedostupný');
        }
    }

    paintStatus(el, state, message) {
        el.classList.remove('cms-edit-field--saving');
        const klass = state === 'ok' ? 'cms-edit-field--ok' : 'cms-edit-field--err';
        el.classList.add(klass);
        if (this.hasBarTarget) {
            this.barTarget.dataset.lastMessage = message;
            const pill = this.barTarget.querySelector('.cms-edit-bar__pill');
            if (pill) {
                pill.dataset.state = state;
                pill.textContent = state === 'ok' ? `OK · ${message}` : `CHYBA · ${message}`;
            }
        }
        setTimeout(() => {
            el.classList.remove(klass);
            if (this.hasBarTarget) {
                const pill = this.barTarget.querySelector('.cms-edit-bar__pill');
                if (pill) {
                    pill.removeAttribute('data-state');
                    pill.textContent = `EDITOR · ${document.querySelector('meta[name="cms-editor-email"]')?.getAttribute('content') || ''}`;
                }
            }
        }, 1800);
    }
}
