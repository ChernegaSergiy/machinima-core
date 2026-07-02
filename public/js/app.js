function initApp() {
    const tg = window.Telegram?.WebApp;
    if (tg) {
        tg.ready();
        tg.expand();
        
        if (tg.initData) {
            document.body.classList.add('is-tma');
            const currentRoute = window.APP_CONFIG.currentRoute;
            const rootRoutes = ['app_index', 'app_categories', 'app_authors', 'app_notifications', 'app_login'];
            if (!rootRoutes.includes(currentRoute)) {
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
            overlay.style.cssText = 'position: fixed; inset: 0; z-index: 9999; background: var(--background); overflow-y: auto;';
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
                                btnElement.classList.add('active');
                                const dislikeBtn = postActions.querySelector('.btn-dislike');
                                if (dislikeBtn) dislikeBtn.classList.remove('active');
                            } else if (data.likes < oldLikes) {
                                btnElement.classList.remove('active');
                            }
                        }
                    }
                    
                    if (type === 'dislike') {
                        btnElement.classList.toggle('active');
                        if (btnElement.classList.contains('active')) {
                            const likeBtn = postActions.querySelector('.btn-like');
                            if (likeBtn) likeBtn.classList.remove('active');
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
                    if (likeBtn) likeBtn.classList.add('active');
                } else if (dislikedIds.includes(postId)) {
                    const dislikeBtn = actions.querySelector('.btn-dislike');
                    if (dislikeBtn) dislikeBtn.classList.add('active');
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

    async function prependNotification() {
        const tgUserId = window.Telegram?.WebApp?.initDataUnsafe?.user?.id;
        if (!tgUserId) return;
        try {
            const tgData = window.Telegram?.WebApp?.initData;
            const headers = {};
            if (tgData && typeof tgData === 'string') {
                headers['X-Telegram-Init-Data'] = tgData.replace(/[^\x20-\x7E]/g, '');
            }
            const res = await fetch('/api/user/' + tgUserId + '/notifications', { headers });
            const json = await res.json();
            if (!json.success) return;
            const notifications = json.data.notifications;
            if (!notifications || notifications.length === 0) return;

            const existingIds = new Set();
            document.querySelectorAll('.list-item').forEach(el => {
                const href = el.getAttribute('href');
                if (href) {
                    const m = href.match(/\/notifications\/(\d+)\/redirect/);
                    if (m) existingIds.add(parseInt(m[1]));
                }
            });

            const newNotifs = notifications.filter(n => !existingIds.has(n.id));
            if (newNotifs.length === 0) return;

            const container = document.querySelector('.container');
            if (!container) return;

            const emptyState = container.querySelector('.empty-state');
            if (emptyState) emptyState.remove();

            const header = container.querySelector('.page-header-flex');

            newNotifs.forEach(notif => {
                const el = buildNotificationEl(notif);
                if (header) {
                    header.after(el);
                } else {
                    container.prepend(el);
                }
            });

            if (window.lucide) window.lucide.createIcons();

            const badge = document.getElementById('unread-count-badge');
            if (badge && json.data.unread_count > 0) {
                badge.innerText = json.data.unread_count > 99 ? '99+' : json.data.unread_count;
                badge.style.display = 'block';
            } else if (badge) {
                badge.style.display = 'none';
            }
        } catch(e) {
            console.error('prependNotification failed', e);
        }
    }

    function buildNotificationEl(notif) {
        const isUnread = !notif.is_read;
        const hasTarget = notif.target_id !== null && notif.target_id !== undefined;

        const wrapper = document.createElement(hasTarget ? 'a' : 'div');
        wrapper.className = 'list-item block no-underline' + (isUnread ? ' unread' : '');
        wrapper.style.cssText = 'color:inherit;text-decoration:none;display:flex;';

        if (hasTarget) {
            wrapper.href = '/notifications/' + notif.id + '/redirect';
            wrapper.setAttribute('data-skeleton', notif.target_type || '');
        }

        const iconDiv = document.createElement('div');
        iconDiv.className = 'list-icon';
        const icon = document.createElement('i');
        icon.setAttribute('data-lucide', 'bell');
        iconDiv.appendChild(icon);

        const contentDiv = document.createElement('div');
        contentDiv.className = 'list-content';

        const titleDiv = document.createElement('div');
        titleDiv.className = 'list-title';
        titleDiv.appendChild(document.createTextNode('Сповіщення '));
        if (isUnread) {
            const badgeSpan = document.createElement('span');
            badgeSpan.className = 'badge';
            badgeSpan.textContent = 'Нове';
            titleDiv.appendChild(badgeSpan);
        }

        const descDiv = document.createElement('div');
        descDiv.className = 'list-desc';
        descDiv.textContent = notif.message;

        const metaDiv = document.createElement('div');
        metaDiv.className = 'list-meta';
        const timeEl = document.createElement('time');
        timeEl.className = 'local-time';
        timeEl.setAttribute('datetime', notif.created_at);
        timeEl.setAttribute('data-format', 'datetime');
        const d = new Date(notif.created_at.replace(' ', 'T'));
        const opts = { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' };
        timeEl.textContent = isNaN(d.getTime()) ? notif.created_at : new Intl.DateTimeFormat('uk-UA', opts).format(d).replace(',', '');
        timeEl.setAttribute('data-formatted', 'true');
        metaDiv.appendChild(timeEl);

        contentDiv.appendChild(titleDiv);
        contentDiv.appendChild(descDiv);
        contentDiv.appendChild(metaDiv);

        wrapper.appendChild(iconDiv);
        wrapper.appendChild(contentDiv);

        return wrapper;
    }
    
    if (!window.mercureListenerAttached && window.APP_CONFIG && window.APP_CONFIG.mercureUrl) {
        try {
            const eventSource = new EventSource(window.APP_CONFIG.mercureUrl);
            eventSource.onmessage = async event => {
                const data = JSON.parse(event.data);
                if (data.type === 'NEW_COMMENT' && window.location.pathname === '/notifications') {
                    await prependNotification();
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
