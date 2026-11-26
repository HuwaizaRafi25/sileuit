import http from 'k6/http';
import { check, sleep } from 'k6';

// TC-18 / NFR-01: 50 pengguna virtual selama 15 menit.
// Jalankan di staging:  k6 run k6/load-test.js -e BASE_URL=https://staging.sileuit.id
export const options = {
    vus: 50,
    duration: '15m',
    thresholds: {
        http_req_duration: ['p(95)<1500'],
        http_req_failed: ['rate<0.02'],
    },
};

const BASE = __ENV.BASE_URL || 'http://localhost:8000';

export default function () {
    const health = http.get(`${BASE}/healthz`);
    check(health, { 'healthz 200': (r) => r.status === 200 });

    const login = http.get(`${BASE}/login`);
    check(login, { 'login 200': (r) => r.status === 200 });

    sleep(1);
}
