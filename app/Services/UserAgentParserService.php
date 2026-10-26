<?php

namespace App\Services;

class UserAgentParserService
{
    /**
     * Phân tích chuỗi User Agent thành thông tin thiết bị, trình duyệt và hệ điều hành.
     *
     * @param  string|null  $userAgent
     * @return array{
     *     device_type: string,
     *     device_label: string,
     *     device_icon: string,
     *     browser: string,
     *     browser_name: string,
     *     browser_version: string,
     *     browser_icon: string,
     *     platform: string,
     *     platform_name: string,
     *     platform_icon: string,
     *     summary: string,
     *     raw: string
     * }
     */
    public static function parse(?string $userAgent): array
    {
        $ua = trim((string) $userAgent);
        if ($ua === '') {
            return [
                'device_type' => 'unknown',
                'device_label' => 'Không xác định',
                'device_icon' => 'fas fa-question-circle',
                'browser' => 'Không xác định',
                'browser_name' => 'Không xác định',
                'browser_version' => '',
                'browser_icon' => 'fas fa-globe',
                'platform' => 'Không xác định',
                'platform_name' => 'Không xác định',
                'platform_icon' => 'fas fa-desktop',
                'summary' => 'Không có thông tin User Agent',
                'raw' => '',
            ];
        }

        // 1. Phân loại loại thiết bị (Device Type)
        $deviceType = 'desktop';
        $deviceLabel = 'Máy tính để bàn / Laptop';
        $deviceIcon = 'fas fa-desktop';

        if (preg_match('/(tablet|ipad|playbook|silk)|(android(?!.*mobi))/i', $ua)) {
            $deviceType = 'tablet';
            $deviceLabel = 'Máy tính bảng (Tablet)';
            $deviceIcon = 'fas fa-tablet-alt';
        } elseif (preg_match('/(mobi|ipod|iphone|blackberry|opera mini|iemobile|windows phone)/i', $ua)) {
            $deviceType = 'mobile';
            $deviceLabel = 'Thiết bị di động (Mobile)';
            $deviceIcon = 'fas fa-mobile-alt';
        } elseif (preg_match('/(curl|postman|insomnia|bot|crawl|slurp|spider|mediapartners|symfony)/i', $ua)) {
            $deviceType = 'bot';
            $deviceLabel = 'Công cụ lập trình / Bot API';
            $deviceIcon = 'fas fa-robot';
        }

        // 2. Phân loại Hệ điều hành (Platform / OS)
        $platform = 'Hệ điều hành khác';
        $platformName = 'Khác';
        $platformIcon = 'fas fa-laptop';

        if (preg_match('/windows nt 10\.0/i', $ua)) {
            $platform = 'Windows 10/11';
            $platformName = 'Windows';
            $platformIcon = 'fab fa-windows';
        } elseif (preg_match('/windows nt 6\.3/i', $ua)) {
            $platform = 'Windows 8.1';
            $platformName = 'Windows';
            $platformIcon = 'fab fa-windows';
        } elseif (preg_match('/windows nt 6\.2/i', $ua)) {
            $platform = 'Windows 8';
            $platformName = 'Windows';
            $platformIcon = 'fab fa-windows';
        } elseif (preg_match('/windows nt 6\.1/i', $ua)) {
            $platform = 'Windows 7';
            $platformName = 'Windows';
            $platformIcon = 'fab fa-windows';
        } elseif (preg_match('/windows nt 6\.0/i', $ua)) {
            $platform = 'Windows Vista';
            $platformName = 'Windows';
            $platformIcon = 'fab fa-windows';
        } elseif (preg_match('/windows nt 5\.1/i', $ua)) {
            $platform = 'Windows XP';
            $platformName = 'Windows';
            $platformIcon = 'fab fa-windows';
        } elseif (preg_match('/windows/i', $ua)) {
            $platform = 'Windows';
            $platformName = 'Windows';
            $platformIcon = 'fab fa-windows';
        } elseif (preg_match('/iphone/i', $ua)) {
            $platform = 'iOS (iPhone)';
            $platformName = 'iOS';
            $platformIcon = 'fab fa-apple';
            if (preg_match('/os ([\d_]+)/i', $ua, $matches)) {
                $platform = 'iOS ' . str_replace('_', '.', $matches[1]);
            }
        } elseif (preg_match('/ipad/i', $ua)) {
            $platform = 'iPadOS';
            $platformName = 'iPadOS';
            $platformIcon = 'fab fa-apple';
            if (preg_match('/os ([\d_]+)/i', $ua, $matches)) {
                $platform = 'iPadOS ' . str_replace('_', '.', $matches[1]);
            }
        } elseif (preg_match('/macintosh|mac os x/i', $ua)) {
            $platform = 'macOS';
            $platformName = 'macOS';
            $platformIcon = 'fab fa-apple';
            if (preg_match('/mac os x ([\d_]+)/i', $ua, $matches)) {
                $ver = str_replace('_', '.', $matches[1]);
                $platform = 'macOS ' . $ver;
            }
        } elseif (preg_match('/android/i', $ua)) {
            $platform = 'Android';
            $platformName = 'Android';
            $platformIcon = 'fab fa-android';
            if (preg_match('/android ([\d\.]+)/i', $ua, $matches)) {
                $platform = 'Android ' . $matches[1];
            }
        } elseif (preg_match('/linux/i', $ua)) {
            $platform = 'Linux';
            $platformName = 'Linux';
            $platformIcon = 'fab fa-linux';
        }

        // 3. Phân loại Trình duyệt (Browser)
        $browser = 'Trình duyệt khác';
        $browserName = 'Trình duyệt';
        $browserVersion = '';
        $browserIcon = 'fas fa-globe';

        if (preg_match('/edg\/([0-9\.]+)/i', $ua, $matches)) {
            $browserName = 'Microsoft Edge';
            $browserVersion = explode('.', $matches[1])[0];
            $browser = 'Edge ' . $browserVersion;
            $browserIcon = 'fab fa-edge';
        } elseif (preg_match('/coc_coc_browser\/([0-9\.]+)/i', $ua, $matches)) {
            $browserName = 'Cốc Cốc';
            $browserVersion = explode('.', $matches[1])[0];
            $browser = 'Cốc Cốc ' . $browserVersion;
            $browserIcon = 'fas fa-globe-asia';
        } elseif (preg_match('/opr\/([0-9\.]+)/i', $ua, $matches)) {
            $browserName = 'Opera';
            $browserVersion = explode('.', $matches[1])[0];
            $browser = 'Opera ' . $browserVersion;
            $browserIcon = 'fab fa-opera';
        } elseif (preg_match('/samsungbrowser\/([0-9\.]+)/i', $ua, $matches)) {
            $browserName = 'Samsung Internet';
            $browserVersion = explode('.', $matches[1])[0];
            $browser = 'Samsung Internet ' . $browserVersion;
            $browserIcon = 'fas fa-mobile';
        } elseif (preg_match('/chrome\/([0-9\.]+)/i', $ua, $matches)) {
            $browserName = 'Google Chrome';
            $browserVersion = explode('.', $matches[1])[0];
            $browser = 'Chrome ' . $browserVersion;
            $browserIcon = 'fab fa-chrome';
        } elseif (preg_match('/firefox\/([0-9\.]+)/i', $ua, $matches)) {
            $browserName = 'Mozilla Firefox';
            $browserVersion = explode('.', $matches[1])[0];
            $browser = 'Firefox ' . $browserVersion;
            $browserIcon = 'fab fa-firefox-browser';
        } elseif (preg_match('/version\/([0-9\.]+).*safari/i', $ua, $matches)) {
            $browserName = 'Apple Safari';
            $browserVersion = explode('.', $matches[1])[0];
            $browser = 'Safari ' . $browserVersion;
            $browserIcon = 'fab fa-safari';
        } elseif (preg_match('/curl\/([0-9\.]+)/i', $ua, $matches)) {
            $browserName = 'cURL';
            $browserVersion = $matches[1];
            $browser = 'cURL ' . $browserVersion;
            $browserIcon = 'fas fa-terminal';
        } elseif (preg_match('/postmanruntime\/([0-9\.]+)/i', $ua, $matches)) {
            $browserName = 'Postman';
            $browserVersion = $matches[1];
            $browser = 'Postman ' . $browserVersion;
            $browserIcon = 'fas fa-paper-plane';
        } elseif (preg_match('/symfony/i', $ua)) {
            $browserName = 'Symfony Client';
            $browser = 'Symfony (Internal/Test)';
            $browserIcon = 'fas fa-code';
        }

        $summary = $browser . ' trên ' . $platform . ' (' . $deviceLabel . ')';

        return [
            'device_type' => $deviceType,
            'device_label' => $deviceLabel,
            'device_icon' => $deviceIcon,
            'browser' => $browser,
            'browser_name' => $browserName,
            'browser_version' => $browserVersion,
            'browser_icon' => $browserIcon,
            'platform' => $platform,
            'platform_name' => $platformName,
            'platform_icon' => $platformIcon,
            'summary' => $summary,
            'raw' => $ua,
        ];
    }
}
