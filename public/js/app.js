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
}

document.addEventListener('turbo:load', initApp);
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initApp);
} else {
    initApp();
}

// Skeleton Screen Generation on Navigation
function getSkeletonHTML(type) {
    let title = 'Завантаження';
    if (type === 'categories') title = 'Категорії';
    else if (type === 'category') title = 'Категорія';
    else if (type === 'authors') title = 'Автори';
    else if (type === 'author') title = 'Автор';
    else if (type === 'notifications') title = 'Сповіщення';
    else if (type === 'post') title = 'Допис';
    else if (type === 'comment') title = 'Коментар';
    else title = 'Стрічка';

    let bodyContent = '';

    if (type === 'categories') {
        // Categories list skeleton
        bodyContent = `
            <div class="page-header-flex">
                <h1 class="page-title">${title}</h1>
            </div>
            ${Array(5).fill().map(() => `
                <div class="list-item">
                    <div class="list-icon skeleton-shimmer"></div>
                    <div class="list-content">
                        <div class="skeleton-text skeleton-shimmer" style="width: 50%; height: 16px; margin: 0;"></div>
                    </div>
                    <div class="skeleton-shimmer" style="width: 20px; height: 20px; border-radius: 4px; opacity: 0.3;"></div>
                </div>
            `).join('')}
        `;
    } else if (type === 'authors') {
        // Authors list skeleton
        bodyContent = `
            <div class="page-header-flex">
                <h1 class="page-title">${title}</h1>
            </div>
            ${Array(5).fill().map(() => `
                <div class="list-item">
                    <div class="list-icon skeleton-shimmer"></div>
                    <div class="list-content">
                        <div class="skeleton-text skeleton-shimmer" style="width: 45%; height: 16px; margin-bottom: 6px;"></div>
                        <div class="skeleton-text skeleton-shimmer" style="width: 70%; height: 14px; margin: 0;"></div>
                    </div>
                    <div class="skeleton-shimmer" style="width: 20px; height: 20px; border-radius: 4px; opacity: 0.3;"></div>
                </div>
            `).join('')}
        `;
    } else if (type === 'post' || type === 'comment') {
        // Detailed post skeleton
        bodyContent = `
            <div class="card">
                <div class="card-header">
                    <div class="avatar skeleton-shimmer"></div>
                    <div class="author-info" style="flex-grow: 1;">
                        <div class="skeleton-text skeleton-shimmer" style="width: 40%; height: 16px; margin-bottom: 4px;"></div>
                        <div class="skeleton-text skeleton-shimmer" style="width: 25%; height: 10px; margin: 0;"></div>
                    </div>
                </div>
                <div class="skeleton-text skeleton-shimmer" style="width: 85%; height: 28px; margin-bottom: 20px;"></div>
                <div style="margin-bottom: 24px;">
                    <div class="skeleton-text skeleton-shimmer body-line"></div>
                    <div class="skeleton-text skeleton-shimmer body-line"></div>
                    <div class="skeleton-text skeleton-shimmer body-line-short"></div>
                </div>
                <div class="skeleton-media skeleton-shimmer" style="aspect-ratio: auto; min-height: 250px;"></div>
                <div class="post-actions">
                    <div class="action-item skeleton-shimmer" style="width: 60px; color: transparent;"></div>
                    <div class="action-item skeleton-shimmer" style="width: 44px; color: transparent;"></div>
                    <div class="action-item skeleton-shimmer ml-auto" style="width: 50px; color: transparent;"></div>
                </div>
            </div>
            <h2 class="comment-section-title">
                <div class="skeleton-text skeleton-shimmer" style="width: 120px; height: 20px; margin: 0; display: inline-block; vertical-align: middle;"></div>
            </h2>
            <div id="comments-list">
                ${Array(2).fill().map(() => `
                    <div class="comment-item">
                        <div class="comment-header">
                            <div class="comment-avatar skeleton-shimmer"></div>
                            <div style="flex-grow: 1;">
                                <div class="skeleton-text skeleton-shimmer" style="width: 30%; height: 14px; margin-bottom: 4px;"></div>
                                <div class="skeleton-text skeleton-shimmer" style="width: 20%; height: 10px; margin: 0;"></div>
                            </div>
                        </div>
                        <div class="skeleton-text skeleton-shimmer body-line"></div>
                        <div class="skeleton-text skeleton-shimmer body-line-short"></div>
                        <div class="comment-actions" style="margin-top: 12px;">
                            <div class="reply-btn skeleton-shimmer" style="width: 120px; color: transparent;">&nbsp;</div>
                        </div>
                    </div>
                `).join('')}
            </div>
        `;
    } else if (type === 'notifications') {
        // Notifications list skeleton
        bodyContent = `
            <div class="page-header-flex">
                <h1 class="page-title">${title}</h1>
            </div>
            ${Array(4).fill().map(() => `
                <div class="list-item">
                    <div class="list-icon skeleton-shimmer"></div>
                    <div class="list-content">
                        <div class="skeleton-text skeleton-shimmer" style="width: 35%; height: 16px; margin-bottom: 6px;"></div>
                        <div class="skeleton-text skeleton-shimmer" style="width: 85%; height: 12px; margin-bottom: 6px;"></div>
                        <div class="skeleton-text skeleton-shimmer" style="width: 20%; height: 10px; margin: 0;"></div>
                    </div>
                </div>
            `).join('')}
        `;
    } else if (type === 'author') {
        return `
        <div class="top-nav">
            <div class="back-btn"><i data-lucide="arrow-left"></i> Назад</div>
        </div>
        <div class="container">
            <div class="profile-header">
                <div class="profile-avatar skeleton-shimmer" style="margin-bottom: 24px;"></div>
                <div class="skeleton-text skeleton-shimmer" style="width: 50%; height: 24px; margin: 0 auto 12px;"></div>
                <div class="skeleton-text skeleton-shimmer" style="width: 80%; height: 14px; margin: 0 auto 24px;"></div>
                <div class="skeleton-shimmer" style="width: 100%; height: 48px; border-radius: 12px;"></div>
            </div>
            <h2 class="section-title">
                <div class="skeleton-text skeleton-shimmer" style="width: 180px; height: 20px; margin: 0; display: inline-block; vertical-align: middle;"></div>
            </h2>
            ${Array(2).fill().map(() => `
                <div class="card">
                    <div class="card-header">
                        <div class="avatar skeleton-shimmer"></div>
                        <div class="author-info" style="flex-grow: 1;">
                            <div class="skeleton-text skeleton-shimmer" style="width: 35%; height: 14px; margin-bottom: 6px;"></div>
                            <div class="skeleton-text skeleton-shimmer" style="width: 20%; height: 10px; margin: 0;"></div>
                        </div>
                    </div>
                    <div class="skeleton-text skeleton-shimmer title"></div>
                    <div class="skeleton-text skeleton-shimmer body-line"></div>
                    <div class="skeleton-text skeleton-shimmer body-line-short"></div>
                    <div class="skeleton-media skeleton-shimmer"></div>
                    <div class="post-actions" style="border-top: none; padding-top: 0; margin-top: 24px;">
                        <div class="action-item skeleton-shimmer" style="width: 60px; color: transparent;"></div>
                        <div class="action-item skeleton-shimmer" style="width: 44px; color: transparent;"></div>
                        <div class="action-item skeleton-shimmer ml-auto" style="width: 50px; color: transparent;"></div>
                    </div>
                </div>
            `).join('')}
        </div>`;
    } else {
        // Standard feed or category/author items list skeleton
        bodyContent = `
            <div class="page-header-flex">
                <h1 class="page-title">${title}</h1>
            </div>
            ${Array(2).fill().map(() => `
                <div class="card">
                    <div class="card-header">
                        <div class="avatar skeleton-shimmer"></div>
                        <div class="author-info" style="flex-grow: 1;">
                            <div class="skeleton-text skeleton-shimmer" style="width: 35%; height: 14px; margin-bottom: 6px;"></div>
                            <div class="skeleton-text skeleton-shimmer" style="width: 20%; height: 10px; margin: 0;"></div>
                        </div>
                    </div>
                    <div class="skeleton-text skeleton-shimmer title"></div>
                    <div class="skeleton-text skeleton-shimmer body-line"></div>
                    <div class="skeleton-text skeleton-shimmer body-line-short"></div>
                    <div class="skeleton-media skeleton-shimmer"></div>
                    <div class="post-actions" style="border-top: none; padding-top: 0; margin-top: 24px;">
                        <div class="action-item skeleton-shimmer" style="width: 60px; color: transparent;"></div>
                        <div class="action-item skeleton-shimmer" style="width: 44px; color: transparent;"></div>
                        <div class="action-item skeleton-shimmer ml-auto" style="width: 50px; color: transparent;"></div>
                    </div>
                </div>
            `).join('')}
        `;
    }

    return `<div class="container">${bodyContent}</div>`;
}

document.addEventListener('turbo:click', function(event) {
    const link = event.target.closest('a');
    if (!link) return;
    
    const skeletonType = link.getAttribute('data-skeleton');
    if (skeletonType) {
        const mainContent = document.getElementById('main-content');
        if (mainContent) {
            mainContent.innerHTML = getSkeletonHTML(skeletonType);
        }
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
