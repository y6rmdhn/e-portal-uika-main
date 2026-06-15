<?php

namespace App\Services;

use App\Repositories\Interfaces\LoginLogRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Jenssegers\Agent\Agent;

class LoginLogService
{
    // Maksimal gagal login sebelum di-throttle
    const MAX_ATTEMPTS_PER_IP    = 10; // per IP dalam 15 menit
    const MAX_ATTEMPTS_PER_EMAIL = 5;  // per email dalam 15 menit
    const LOCKOUT_MINUTES        = 15;
    const SUSPICIOUS_THRESHOLD   = 10; // IP dianggap mencurigakan

    public function __construct(
        protected LoginLogRepositoryInterface $repository
    ) {}

    /**
     * Catat login sukses. Dipanggil di AuthController setelah token digenerate.
     */
    public function logSuccess(string $userId, Request $request): void
    {
        $this->repository->create(
            array_merge($this->buildLogData($request), [
                'user_id' => $userId,
                'status' => 'success',
            ])
        );

        // reset counter gagal login jika ada
        $this->clearFailedCache($request->ip(), $request->input('email'));
    }

    /**
     * Catat login gagal. Dipanggil di AuthController saat kredensial salah.
     */
    public function logFailure(Request $request, string $reason = 'invalid_credentials'): void
    {
        $this->repository->create(
            array_merge($this->buildLogData($request), [
                'user_id' => null,
                'status' => 'failed',
                'failure_reason' => $reason,
            ])
        );

        // increment counter gagal login
        $this->incrementFailedCache($request->ip(), $request->input('email'));
    }

    /**
     * ambil log
     */

    public function getAllLogs(array $filters = []): LengthAwarePaginator
    {
        return $this->repository->getAllLogs($filters);
    }

    public function getLogsByUser(string $userId, array $filters = [])
    {
        return $this->repository->getLogsByUser($userId, $filters);
    }

    public function getSuspiciousIps(): array
    {
        return Cache::remember('security.suspicious_ips', now()->addMinutes(5), function () {
            return $this->repository->getSuspiciousIps(self::SUSPICIOUS_THRESHOLD, 60)->toArray();
        });
    }


    // ─── Rate Limiting (manual, berbasis cache) ───────────────────────────────

    /**
     * Cek apakah IP ini sedang di-lockout.
     * Dipanggil di AuthController SEBELUM proses autentikasi.
     */

    public function isIpBlocked(string $ip): bool
    {
        return Cache::has($this->ipLockKey($ip));
    }

    /**
     * Cek apakah email ini sedang di-lockout.
     */
    public function isEmailBlocked(string $email): bool
    {
        return Cache::has($this->emailLockKey($email));
    }

    /**
     * Berapa menit tersisa sebelum lockout berakhir.
     */
    public function getLockoutRemainingSeconds(string $identifier, string $type = 'ip'): int
    {
        $key = $type === 'ip'
            ? $this->ipLockKey($identifier)
            : $this->emailLockKey($identifier);

        if (!Cache::has($key)) return 0;

        return self::LOCKOUT_MINUTES * 60;
    }

    /**
     * Summary status rate limiting untuk response API (dikembalikan ke client).
     */
    public function getRateLimitStatus(string $ip, string $email): array
    {
        $ipAttempts = (int) Cache::get($this->ipCountKey($ip), 0);
        $emailAttempts = (int) Cache::get($this->emailCountKey($email), 0);


        return [
            'ip_attempts'       => $ipAttempts,
            'ip_remaining'      => max(0, self::MAX_ATTEMPTS_PER_IP - $ipAttempts),
            'ip_blocked'        => $this->isIpBlocked($ip),
            'email_attempts'    => $emailAttempts,
            'email_remaining'   => max(0, self::MAX_ATTEMPTS_PER_EMAIL - $emailAttempts),
            'email_blocked'     => $this->isEmailBlocked($email),
            'lockout_minutes'   => self::LOCKOUT_MINUTES,
        ];
    }

    // ─── Cleanup ──────────────────────────────────────────────────────────────
    /**
     * Hapus log lama. Dipanggil dari Artisan Command / Scheduler.
     */
    public function purgeOldLogs(int $days = 90): int
    {
        return $this->repository->deleteOldLogs($days);
    }

    // ─── Private Helpers ──────────────────────────────────────────────────────
    private function buildLogData(Request $request): array
    {
        $agent = new Agent();
        $agent->setUserAgent($request->userAgent());

        $deviceType = 'desktop';
        if ($agent->isMobile())  $deviceType = 'mobile';
        if ($agent->isTablet())  $deviceType = 'tablet';

        return [
            'ip_address'      => $request->ip(),
            'user_agent'      => $request->userAgent(),
            'browser'         => $agent->browser(),
            'browser_version' => $agent->version($agent->browser()),
            'platform'        => $agent->platform(),
            'device_type'     => $deviceType,
        ];
    }

    private function incrementFailedCache(string $ip, ?string $email): void
    {
        // counter IP
        $ipCountKey = $this->ipCountKey($ip);
        Cache::add($ipCountKey, 0, now()->addMinutes(self::LOCKOUT_MINUTES));

        $ipCount = Cache::increment($ipCountKey);

        if ($ipCount >= self::MAX_ATTEMPTS_PER_IP) {
            Cache::put($this->ipLockKey($ip), true, now()->addMinutes(self::LOCKOUT_MINUTES));
        }

        // counter email
        if ($email) {
            $emailCountKey = $this->emailCountKey($email);
            Cache::add($emailCountKey, 0, now()->addMinutes(self::LOCKOUT_MINUTES));

            $emailCount = Cache::increment($emailCountKey);

            if ($emailCount >= self::MAX_ATTEMPTS_PER_EMAIL) {
                Cache::put($this->emailLockKey($email), true, now()->addMinutes(self::LOCKOUT_MINUTES));
            }
        }
    }

    private function clearFailedCache(string $ip, ?string $email): void
    {
        Cache::forget($this->ipCountKey($ip));
        Cache::forget($this->ipLockKey($ip));

        if ($email) {
            Cache::forget($this->emailCountKey($email));
            Cache::forget($this->emailLockKey($email));
        }
    }


    private function ipCountKey(string $ip): string
    {
        return "login_fail_ip:{$ip}";
    }

    private function ipLockKey(string $ip): string
    {
        return "login_lock_ip:{$ip}";
    }

    private function emailCountKey(string $email): string
    {
        return "login_fail_email:" . md5($email);
    }

    private function emailLockKey(string $email): string
    {
        return "login_lock_email:" . md5($email);
    }
}
