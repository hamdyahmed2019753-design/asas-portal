{{--
    Admin toast poller (Feature 10.2 / 10.3).
    wire:poll drives server-side toast + browser-event dispatch; the inline
    Alpine object handles the browser-only side-effects (sound, one-shot bell
    animation, native browser notifications). Inline x-data is used instead of
    a pushed <script> so the methods are always defined before Alpine inits the
    element — no script-ordering or re-execution concerns on Livewire updates.
--}}
<div
    wire:poll.12s="pollNew"
    wire:key="admin-toast-poller"
    x-data="{
        audioCtx: null,

        init() {
            // Request native-notification permission once, silently.
            if ('Notification' in window && Notification.permission === 'default') {
                Notification.requestPermission().catch(() => {});
            }

            // Browsers block Web Audio until the user interacts with the page, so
            // a beep fired from wire:poll (no gesture) stays silent. Unlock the
            // AudioContext on the FIRST real interaction so later beeps play.
            const unlock = () => {
                try {
                    if (! this.audioCtx) {
                        const Ctor = window.AudioContext || window.webkitAudioContext;
                        if (Ctor) this.audioCtx = new Ctor();
                    }
                    if (this.audioCtx && this.audioCtx.state === 'suspended') {
                        this.audioCtx.resume().catch(() => {});
                    }
                } catch (e) {}
                window.removeEventListener('pointerdown', unlock);
                window.removeEventListener('keydown', unlock);
            };
            window.addEventListener('pointerdown', unlock);
            window.addEventListener('keydown', unlock);
        },

        onNew(notifications) {
            if (! Array.isArray(notifications) || notifications.length === 0) {
                return;
            }
            this.ringBell();
            notifications.forEach((n, index) => {
                setTimeout(() => this.playBeep(), index * 350);
                this.browserNotify(n);
            });
        },

        playBeep() {
            try {
                if (! this.audioCtx) {
                    const Ctor = window.AudioContext || window.webkitAudioContext;
                    if (! Ctor) return;
                    this.audioCtx = new Ctor();
                }
                if (this.audioCtx.state === 'suspended') {
                    this.audioCtx.resume().catch(() => {});
                }
                const now = this.audioCtx.currentTime;
                const osc = this.audioCtx.createOscillator();
                const gain = this.audioCtx.createGain();
                osc.connect(gain);
                gain.connect(this.audioCtx.destination);
                osc.type = 'sine';
                osc.frequency.setValueAtTime(880, now);
                osc.frequency.setValueAtTime(1320, now + 0.09);
                gain.gain.setValueAtTime(0.0001, now);
                gain.gain.exponentialRampToValueAtTime(0.16, now + 0.02);
                gain.gain.exponentialRampToValueAtTime(0.0001, now + 0.32);
                osc.start(now);
                osc.stop(now + 0.32);
            } catch (e) {
                // Autoplay blocked or Web Audio unavailable — graceful fallback.
            }
        },

        ringBell() {
            const bell = document.querySelector('.fi-topbar-database-notifications-btn');
            if (! bell) return;
            bell.classList.remove('admin-bell-ring');
            void bell.offsetWidth; // force reflow so the class re-addition restarts it
            bell.classList.add('admin-bell-ring');
            setTimeout(() => bell.classList.remove('admin-bell-ring'), 1300);
        },

        browserNotify(n) {
            if (! ('Notification' in window) || Notification.permission !== 'granted') {
                return;
            }
            try {
                const iconLink = document.querySelector('link[rel="icon"], link[rel="shortcut icon"]');
                const options = { body: n.body || '', tag: n.id };
                if (iconLink && iconLink.href) {
                    options.icon = iconLink.href;
                }
                const note = new Notification(n.title || '', options);
                note.onclick = () => {
                    window.open(n.url || '/', '_blank');
                    note.close();
                    window.focus();
                };
            } catch (e) {
                // Native notifications blocked — the in-page toast covers it.
            }
        },
    }"
    x-init="init()"
    @admin-notifications-new.window="onNew($event.detail.notifications)"
    class="hidden"
    aria-hidden="true"
></div>
