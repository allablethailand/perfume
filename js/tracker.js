(function () {
    const ENDPOINT = 'analytics/track.php';

    function getVisitorId() {
        let id = localStorage.getItem('_vid');
        if (!id) {
            id = 'v_' + Date.now() + '_' + Math.random().toString(36).slice(2);
            localStorage.setItem('_vid', id);
        }
        return id;
    }

    function getSessionId() {
        const now = Date.now();
        const exp = parseInt(sessionStorage.getItem('_sid_exp') || '0');
        let sid = sessionStorage.getItem('_sid');
        if (!sid || now > exp) {
            sid = 's_' + now + '_' + Math.random().toString(36).slice(2);
            sessionStorage.setItem('_sid', sid);
        }
        sessionStorage.setItem('_sid_exp', now + 30 * 60 * 1000);
        return sid;
    }

    function parseReferrer(ref) {
        if (!ref) return 'direct';
        if (/google\.|bing\.|yahoo\./.test(ref))                          return 'search';
        if (/facebook\.|instagram\.|twitter\.|tiktok\.|line\./.test(ref)) return 'social';
        return 'other';
    }

    function getDeviceType() {
        if (/Mobi|Android/i.test(navigator.userAgent)) return 'mobile';
        if (/Tablet|iPad/i.test(navigator.userAgent))  return 'tablet';
        return 'desktop';
    }

    function send(action, payload) {
        const body = JSON.stringify({
            action,
            session_id: getSessionId(),
            visitor_id: getVisitorId(),
            ...payload
        });
        if (action === 'page_exit' && navigator.sendBeacon) {
            navigator.sendBeacon(ENDPOINT, body);
        } else {
            fetch(ENDPOINT, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body,
                keepalive: true
            }).catch(() => {});
        }
    }

    // ── ตรวจสอบว่าเคยขอ geo แล้วหรือยัง ──────────────
    // เก็บใน localStorage เพื่อไม่ถามซ้ำทุกหน้า
    function getStoredGeo() {
        try {
            const raw = localStorage.getItem('_geo');
            if (!raw) return null;
            const obj = JSON.parse(raw);
            // หมดอายุใน 24 ชั่วโมง
            if (Date.now() - obj.ts > 86400000) {
                localStorage.removeItem('_geo');
                return null;
            }
            return obj;
        } catch { return null; }
    }

    function saveGeo(lat, lng) {
        localStorage.setItem('_geo', JSON.stringify({ lat, lng, ts: Date.now() }));
    }

    // ── ส่ง pageview พร้อม geo ────────────────────────
    const pageStart  = Date.now();
    const currentUrl = location.href;
    let maxScroll    = 0;

    function sendPageEnter(lat, lng) {
        send('page_enter', {
            url:             currentUrl,
            page_title:      document.title,
            referrer_url:    document.referrer,
            referrer_source: parseReferrer(document.referrer),
            device_type:     getDeviceType(),
            user_agent:      navigator.userAgent,
            timezone:        Intl.DateTimeFormat().resolvedOptions().timeZone,
            lat:             lat || '',
            lng:             lng || ''
        });
    }

    // ── Logic หลัก ────────────────────────────────────
    const stored = getStoredGeo();

    if (stored) {
        // มี geo เก่าแล้ว → ส่งเลย ไม่ถามซ้ำ
        sendPageEnter(stored.lat, stored.lng);

    } else if ('geolocation' in navigator) {
        // ขอ permission — จะ popup แค่ครั้งแรก
        // timeout 5 วิ ถ้าปฏิเสธหรือช้า → ส่งโดยไม่มี geo
        const geoTimeout = setTimeout(() => sendPageEnter('', ''), 5000);

        navigator.geolocation.getCurrentPosition(
            pos => {
                clearTimeout(geoTimeout);
                const lat = pos.coords.latitude;
                const lng = pos.coords.longitude;
                saveGeo(lat, lng);   // บันทึก cache 24 ชม.
                sendPageEnter(lat, lng);
            },
            err => {
                // ปฏิเสธ / error → ส่งโดยไม่มี geo (ใช้ IP แทน)
                clearTimeout(geoTimeout);
                sendPageEnter('', '');
            },
            { timeout: 5000, maximumAge: 3600000 }
        );
    } else {
        // browser ไม่รองรับ
        sendPageEnter('', '');
    }

    // Scroll tracking
    window.addEventListener('scroll', () => {
        const s = Math.round(
            (window.scrollY / (document.body.scrollHeight - window.innerHeight)) * 100
        );
        if (s > maxScroll) maxScroll = Math.min(s, 100);
    }, { passive: true });

    // Page exit
    function onExit() {
        send('page_exit', {
            url:          currentUrl,
            duration:     Math.round((Date.now() - pageStart) / 1000),
            scroll_depth: maxScroll
        });
    }
    window.addEventListener('pagehide',     onExit);
    window.addEventListener('beforeunload', onExit);

    // Custom event
    window.trackEvent = function (name, target, value) {
        send('event', {
            event_name:   name,
            event_target: target || '',
            event_value:  value  || '',
            url:          currentUrl
        });
    };

    // Auto-track clicks
    document.addEventListener('click', function (e) {
        const el = e.target.closest('a, button, [data-track]');
        if (!el) return;
        const label = el.dataset.track || el.innerText?.trim().slice(0, 100) || el.href;
        if (label) window.trackEvent('click', label);
    });

})();