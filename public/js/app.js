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
    initApp();
    
    // Only restore scroll on back/forward navigation
    if (event.detail.action === 'restore') {
        const savedPos = sessionStorage.getItem('scroll_' + window.location.pathname);
        if (savedPos !== null) {
            // Use requestAnimationFrame to ensure DOM is fully rendered (especially if restoring from cache)
            requestAnimationFrame(() => {
                requestAnimationFrame(() => {
                    window.scrollTo(0, parseInt(savedPos, 10));
                });
            });
        }
    }
});

// Skeleton Screen Generation on Navigation
document.addEventListener('turbo:click', function(event) {
    // Save scroll position explicitly before we mutate the DOM (which ruins Turbo's automatic scroll saving)
    sessionStorage.setItem('scroll_' + window.location.pathname, window.scrollY);
    
    const link = event.target.closest('a');
    if (!link) return;
    
    const skeletonType = link.getAttribute('data-skeleton');
    if (skeletonType) {
        const mainContent = document.getElementById('main-content');
        if (mainContent) {
            let template = document.getElementById(`skeleton-${skeletonType}`);
            // Fallback to default if specific skeleton doesn't exist
            if (!template && skeletonType !== 'none') {
                template = document.getElementById('skeleton-default');
            }
            if (template) {
                // VERY IMPORTANT: Save the original DOM so Turbo can cache it properly for the Back button!
                window.originalMainContentHTML = mainContent.innerHTML;
                mainContent.innerHTML = template.innerHTML;
            }
        }
    }
});

// Restore original DOM before Turbo saves it to cache
document.addEventListener('turbo:before-cache', function() {
    if (window.originalMainContentHTML) {
        const mainContent = document.getElementById('main-content');
        if (mainContent) {
            mainContent.innerHTML = window.originalMainContentHTML;
        }
        window.originalMainContentHTML = null;
    }
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
    
    if (!window.mercureListenerAttached && window.APP_CONFIG && window.APP_CONFIG.mercureUrl) {
        try {
            const eventSource = new EventSource(window.APP_CONFIG.mercureUrl);
            eventSource.onmessage = async event => {
                const data = JSON.parse(event.data);
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
