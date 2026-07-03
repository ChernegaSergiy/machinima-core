function initApp() {
    const tg = window.Telegram?.WebApp;
    if (tg) {
        tg.ready();
        tg.expand();
        
        if (tg.initData) {
            document.body.classList.add('is-tma');
            const currentPath = window.location.pathname;
            const rootPaths = ['/', '/categories', '/authors', '/notifications', '/profile', '/login'];
            if (!rootPaths.includes(currentPath)) {
                tg.BackButton.show();
                if (!window.tgBackAssigned) {
                    tg.BackButton.onClick(() => window.history.back());
                    window.tgBackAssigned = true;
                }
            } else {
                tg.BackButton.hide();
            }
        }
    }
    if (typeof lucide !== 'undefined') lucide.createIcons();
    
    const urlParams = new URLSearchParams(window.location.search);
    const scrollTargetId = urlParams.get('scrollTo') || (window.location.hash ? window.location.hash.substring(1) : null);
    
    if (scrollTargetId) {
        // Clean up the URL to avoid scrolling again on manual refresh
        if (urlParams.has('scrollTo')) {
            urlParams.delete('scrollTo');
            const qs = urlParams.toString();
            const newUrl = window.location.pathname + (qs ? '?' + qs : '') + window.location.hash;
            window.history.replaceState({}, '', newUrl);
        }

        // Double requestAnimationFrame ensures the DOM is fully laid out and painted
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

// Check initial load
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initApp);
} else {
    initApp();
}

document.addEventListener('turbo:load', function(event) {
    initApp();
});

// Save scroll position before navigating away
document.addEventListener('turbo:click', function(event) {
    const link = event.target.closest('a');
    if (!link) return;

    sessionStorage.setItem('scroll:' + window.location.pathname, window.scrollY);

    const skeletonType = link.getAttribute('data-skeleton');
    if (skeletonType) {
        // Remove any leftover skeleton overlay first
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

// Restore scroll position after page loads
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

// Robust Architecture: Inject auth header into every Turbo navigation request natively.
document.addEventListener('turbo:before-fetch-request', function (event) {
    const tgData = window.Telegram?.WebApp?.initData;
    if (tgData && typeof tgData === 'string') {
        event.detail.fetchOptions.headers['X-Telegram-Init-Data'] = tgData.replace(/[^\x20-\x7E]/g, '');
    }
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

// Global interaction logic for feed items
window.interactFeed = async function(contentId, type, btnElement) {
    if (btnElement && type !== 'view') {
        btnElement.style.pointerEvents = 'none';
        btnElement.style.opacity = '0.5';
        btnElement.style.transition = 'opacity 0.2s';
    }
    try {
        const tgData = window.Telegram?.WebApp?.initData;
        const headers = { 'Content-Type': 'application/json' };
        if (tgData && typeof tgData === 'string') {
            headers['X-Telegram-Init-Data'] = tgData.replace(/[^\x20-\x7E]/g, '');
        }
        const res = await fetch('/api/interact', {
            method: 'POST',
            headers: headers,
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

// Load active interactions on page load
document.addEventListener('turbo:load', async function() {
    const tgUserId = window.Telegram?.WebApp?.initDataUnsafe?.user?.id;
    if (!tgUserId) return;
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

    try {
        const tgData = window.Telegram?.WebApp?.initData;
        const headers = { 'Cache-Control': 'no-cache', 'Pragma': 'no-cache' };
        if (tgData && typeof tgData === 'string') {
            headers['X-Telegram-Init-Data'] = tgData.replace(/[^\x20-\x7E]/g, '');
        }
        const unreadRes = await fetch('/api/notifications/unread-count', {
            headers: headers,
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
                    } else {
                        window.location.reload();
                    }
                    return;
                }
                if (data.type === 'NEW_COMMENT' || data.type === 'STATS_UPDATE') {
                    try {
                        const tgData = window.Telegram?.WebApp?.initData;
                        const headers = { 'Cache-Control': 'no-cache', 'Pragma': 'no-cache' };
                        if (tgData && typeof tgData === 'string') {
                            headers['X-Telegram-Init-Data'] = tgData.replace(/[^\x20-\x7E]/g, '');
                        }
                        const unreadRes = await fetch('/api/notifications/unread-count', {
                            headers: headers,
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
