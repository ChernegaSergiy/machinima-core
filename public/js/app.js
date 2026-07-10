function getPlatform() {
    return window.__PLATFORM__ || { isEmbedded: false };
}

/**
 * Zero-click login bootstrap. Core knows NOTHING about how any given
 * platform detects itself or what an "assertion" looks like — it only
 * dynamically imports each registered adapter's bootstrap module and calls
 * its `detect()`. The only thing core owns is the transport (one POST to
 * /api/auth/bootstrap) and the reload circuit breaker below.
 */
async function bootstrapAuth() {
    const modulePaths = window.__BOOTSTRAP_MODULE_PATHS__ || [];
    if (modulePaths.length === 0) return;

    // Circuit breaker: at most one bootstrap attempt per page load. Without
    // this, a broken or slow-to-settle adapter (cookie not accepted, race
    // between detection and server state) could reload forever.
    const attempts = Number(sessionStorage.getItem('bootstrap_attempts') || '0');
    if (attempts >= 1) return;
    sessionStorage.setItem('bootstrap_attempts', String(attempts + 1));

    for (const modulePath of modulePaths) {
        let result = null;
        try {
            const mod = await import(modulePath);
            result = await mod.detect();
        } catch (e) {
            console.error('Platform bootstrap module failed:', modulePath, e);
        }

        if (!result || typeof result.assertion !== 'string' || !result.assertion) continue;

        try {
            const res = await fetch('/api/auth/bootstrap', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ provider: result.provider, assertion: result.assertion }),
            });

            if (res.ok) {
                sessionStorage.removeItem('bootstrap_attempts');
                window.location.reload();
                return;
            }
        } catch (e) {
            console.error('Platform bootstrap request failed:', e);
        }

        // This adapter detected itself but bootstrap failed — don't try the
        // remaining adapters too, and don't reload again this page load.
        return;
    }
}

/**
 * Presentational-only hook for the adapter owning the current session
 * (theme, back-button, etc). Never involved in login. Core just dispatches
 * a generic navigation event; what a hints module does with it is its own
 * business.
 */
function loadUiHints() {
    const modulePath = window.__UI_HINTS_MODULE_PATH__;
    if (!modulePath || window.__uiHintsLoaded) return;
    window.__uiHintsLoaded = true;

    import(modulePath)
        .then(mod => mod.apply(getPlatform()))
        .catch(e => console.error('Platform UI hints module failed:', modulePath, e));
}

document.addEventListener('turbo:load', function() {
    window.dispatchEvent(new CustomEvent('platform:navigate', {
        detail: { route: document.body.dataset.route },
    }));
});

function initApp() {
    const tabBar = document.getElementById('main-tab-bar');
    if (tabBar) {
        const links = tabBar.querySelectorAll('a[data-skeleton]');
        const activeClasses = ['opacity-100', 'font-extrabold', 'bg-black-5', 'border-t-3', 'border-border', '-mt-0-5'];
        const defaultClasses = ['opacity-60', 'font-bold'];

        if (!tabBar.dataset.listenersAttached) {
            links.forEach(link => {
                link.addEventListener('click', function() {
                    const skeleton = this.dataset.skeleton;
                    sessionStorage.setItem('active_tab', skeleton);

                    links.forEach(l => {
                        l.classList.remove(...activeClasses);
                        l.classList.add(...defaultClasses);
                    });
                    this.classList.remove(...defaultClasses);
                    this.classList.add(...activeClasses);
                });
            });
            tabBar.dataset.listenersAttached = 'true';
        }

        const serverActiveTab = Array.from(links).find(l => l.classList.contains('font-extrabold'));

        if (serverActiveTab) {
            sessionStorage.setItem('active_tab', serverActiveTab.dataset.skeleton);
        } else {
            const savedTab = sessionStorage.getItem('active_tab') || 'feed';
            const targetLink = tabBar.querySelector(`a[data-skeleton="${savedTab}"]`);
            if (targetLink) {
                targetLink.classList.remove(...defaultClasses);
                targetLink.classList.add(...activeClasses);
            }
        }
    }

    if (typeof lucide !== 'undefined') lucide.createIcons();

    const urlParams = new URLSearchParams(window.location.search);
    const scrollTargetId = urlParams.get('scrollTo') || (window.location.hash ? window.location.hash.substring(1) : null);

    if (scrollTargetId) {
        if (urlParams.has('scrollTo')) {
            urlParams.delete('scrollTo');
            const qs = urlParams.toString();
            const newUrl = window.location.pathname + (qs ? '?' + qs : '') + window.location.hash;
            window.history.replaceState({}, '', newUrl);
        }

        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                const target = document.getElementById(scrollTargetId);
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    target.style.transition = 'background-color 0.8s ease-out';
                    target.style.backgroundColor = 'var(--tg-theme-secondary-bg-color, #2c2c2e)';
                    setTimeout(() => target.style.backgroundColor = 'transparent', 2000);
                }
            });
        });
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initApp);
} else {
    initApp();
}

document.addEventListener('turbo:load', function(event) {
    initApp();
});

document.addEventListener('turbo:click', function(event) {
    const link = event.target.closest('a');
    if (!link) return;

    sessionStorage.setItem('scroll:' + window.location.pathname, window.scrollY);

    const skeletonType = link.getAttribute('data-skeleton');
    if (skeletonType) {
        const old = document.getElementById('skeleton-overlay-active');
        if (old) old.remove();

        let template = document.getElementById(`skeleton-${skeletonType}`);
        if (!template && skeletonType !== 'none') {
            template = document.getElementById('skeleton-default');
        }
        if (template) {
            const overlay = document.createElement('div');
            overlay.id = 'skeleton-overlay-active';
            overlay.style.cssText = 'position: fixed; inset: 0; z-index: 90; background: var(--background); overflow-y: auto;';
            overlay.innerHTML = template.innerHTML;
            document.body.appendChild(overlay);
        }
    }
});

document.addEventListener('turbo:load', function(event) {
    const y = sessionStorage.getItem('scroll:' + window.location.pathname);
    if (y) {
        sessionStorage.removeItem('scroll:' + window.location.pathname);
        requestAnimationFrame(() => {
            window.scrollTo(0, parseInt(y));
        });
    }

    const overlay = document.getElementById('skeleton-overlay-active');
    if (overlay) overlay.remove();
});

function setActive(el, active) {
    const svg = el && el.querySelector('svg');
    if (!svg) return;
    const i = document.createElement('i');
    i.setAttribute('data-lucide', el.classList.contains('btn-like') ? 'heart' : 'thumbs-down');
    if (active) i.setAttribute('fill', 'currentColor');
    const cls = svg.getAttribute('class');
    if (cls) i.setAttribute('class', cls);
    svg.replaceWith(i);
    if (window.lucide) window.lucide.createIcons();
    if (active) el.classList.add('active');
    else el.classList.remove('active');
}

window.interactFeed = async function(contentId, type, btnElement) {
    if (btnElement && type !== 'view') {
        btnElement.style.pointerEvents = 'none';
        btnElement.style.opacity = '0.5';
        btnElement.style.transition = 'opacity 0.2s';
    }
    try {
        const res = await fetch('/api/interact', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({
                content_id: contentId,
                type: type,
                user_id: window.currentUserId
            })
        });
        const data = await res.json();

        if (btnElement && type !== 'view') {
            btnElement.style.pointerEvents = 'auto';
            btnElement.style.opacity = '1';
        }

        if (data.success) {
            if (btnElement && type !== 'view') {
                const postActions = btnElement.closest('.post-actions');
                if (postActions) {
                    const likesEl = postActions.querySelector('.likes-count');
                    if (likesEl && data.likes !== undefined) {
                        const oldLikes = parseInt(likesEl.innerText);
                        likesEl.innerText = data.likes;
                        if (type === 'like') {
                            if (data.likes > oldLikes) {
                                setActive(btnElement, true);
                                const dislikeBtn = postActions.querySelector('.btn-dislike');
                                if (dislikeBtn) setActive(dislikeBtn, false);
                            } else if (data.likes < oldLikes) {
                                setActive(btnElement, false);
                            }
                        }
                    }

                    if (type === 'dislike') {
                        const wasActive = btnElement.classList.contains('active');
                        setActive(btnElement, !wasActive);
                        if (!wasActive) {
                            const likeBtn = postActions.querySelector('.btn-like');
                            if (likeBtn) setActive(likeBtn, false);
                        }
                    }
                }
            }
        } else {
            alert('Помилка: ' + data.error);
        }
    } catch(e) {
        console.error(e);
        if (btnElement && type !== 'view') {
            btnElement.style.pointerEvents = 'auto';
            btnElement.style.opacity = '1';
        }
    }
};

document.addEventListener('turbo:load', async function() {
    bootstrapAuth();
    loadUiHints();

    let tgUserId = window.currentUserId;
    if (tgUserId) {
        try {
            const res = await fetch('/api/user/' + tgUserId + '/interactions');
            const data = await res.json();
            if (data.success) {
                const likedIds = data.likes || [];
                const dislikedIds = data.dislikes || [];
                document.querySelectorAll('.post-actions').forEach(actions => {
                    const postId = parseInt(actions.getAttribute('data-post-id'));
                    if (likedIds.includes(postId)) {
                        const likeBtn = actions.querySelector('.btn-like');
                        if (likeBtn) setActive(likeBtn, true);
                    } else if (dislikedIds.includes(postId)) {
                        const dislikeBtn = actions.querySelector('.btn-dislike');
                        if (dislikeBtn) setActive(dislikeBtn, true);
                    }
                });
            }
        } catch(e) {}
    }

        try {
        const unreadRes = await fetch('/api/notifications/unread-count', {
            headers: { 'Cache-Control': 'no-cache', 'Pragma': 'no-cache' },
            credentials: 'same-origin',
            cache: 'no-store'
        });
        const unreadData = await unreadRes.json();
        const badge = document.getElementById('unread-count-badge');
        if (badge && unreadData.count > 0) {
            badge.innerText = unreadData.count > 99 ? '99+' : unreadData.count;
            badge.style.display = 'block';
        } else if (badge) {
            badge.style.display = 'none';
        }
    } catch(e) {}

    if (!window.mercureListenerAttached && window.APP_CONFIG && window.APP_CONFIG.mercureUrl) {
        try {
            const eventSource = new EventSource(window.APP_CONFIG.mercureUrl);
            eventSource.onmessage = async event => {
                const data = JSON.parse(event.data);
                if (data.type === 'NEW_COMMENT' && window.location.pathname === '/notifications') {
                    if (typeof Turbo !== 'undefined') {
                        Turbo.visit(window.location.href, { action: "replace" });
                    } else if (window.history.length > 1) {
                        window.history.back();
                    } else {
                        window.location.reload();
                    }
                    return;
                }
                if (data.type === 'NEW_COMMENT' || data.type === 'STATS_UPDATE') {
                    try {
                        const unreadRes = await fetch('/api/notifications/unread-count', {
                            headers: { 'Cache-Control': 'no-cache', 'Pragma': 'no-cache' },
                            credentials: 'same-origin',
                            cache: 'no-store'
                        });
                        const unreadData = await unreadRes.json();
                        const badge = document.getElementById('unread-count-badge');
                        if (badge && unreadData.count > 0) {
                            badge.innerText = unreadData.count > 99 ? '99+' : unreadData.count;
                            badge.style.display = 'block';
                        } else if (badge) {
                            badge.style.display = 'none';
                        }
                    } catch (e) {}
                }
            };
            window.mercureListenerAttached = true;
        } catch(e) {
            console.log('Mercure listener could not be attached', e);
        }
    }

    window.formatLocalTimes = function() {
        document.querySelectorAll('time.local-time:not([data-formatted])').forEach(el => {
            const date = new Date(el.getAttribute('datetime'));
            if (!isNaN(date)) {
                const format = el.getAttribute('data-format') || 'datetime';
                const opts = { day: '2-digit', month: '2-digit', year: 'numeric' };
                if (format === 'datetime') {
                    opts.hour = '2-digit'; opts.minute = '2-digit';
                }
                el.innerText = new Intl.DateTimeFormat('uk-UA', opts).format(date).replace(',', '');
                el.setAttribute('data-formatted', 'true');
            }
        });
    };
    window.formatLocalTimes();
});
