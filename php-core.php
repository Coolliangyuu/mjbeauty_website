<?php
// ==========================================
// 1. 核心盲盒抽取逻辑（本地绝对路径极速读取）
// ==========================================
$quotesFile = __DIR__ . '/mj-quotes.txt';
$randomQuote = "我们是𝐌𝐉全球华人之家"; 
if (file_exists($quotesFile)) {
    $quotesArray = file($quotesFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!empty($quotesArray)) {
        $randomQuote = $quotesArray[array_rand($quotesArray)];
    }
}

$logosFile = __DIR__ . '/logos.txt'; 
$logoBaseUrl = "https://1818461212.v.123pan.cn/1818461212/%E7%BD%91%E7%AB%99/mj.lyimc.top/logos/";
$randomLogoUrl = "https://1818461212.v.123pan.cn/1818461212/%E7%BD%91%E7%AB%99/mj.lyimc.top/logo.jpg"; 

if (file_exists($logosFile)) {
    $logosArray = file($logosFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!empty($logosArray)) {
        $randomFileName = trim($logosArray[array_rand($logosArray)]);
        if (!empty($randomFileName)) {
            $randomLogoUrl = $logoBaseUrl . $randomFileName;
        }
    }
}

// 把变量挂载到全局，方便闭包内部随时调用，彻底消除 use 导致的白屏/拒绝隐患
$GLOBALS['mj_quote'] = $randomQuote;
$GLOBALS['mj_logo'] = $randomLogoUrl;

// ==========================================
// 2. 拦截并自动篡改 HTML（超强兼容防御版）
// ==========================================
// 只有当请求不是普通 Ajax/PJAX 异步请求，或者明确是主文档时才开启缓冲区
if (!isset($_SERVER['HTTP_X_PJAX']) && !isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    
    ob_start(function($html) {
        // 1. 严格防护：如果网页没有 head 标签，或者被 PJAX 局部截断，绝对不篡改，直接放行
        if (stripos($html, '<head>') === false) {
            return $html;
        }

        // 2. 检查当前输出的 Content-Type，如果不是 text/html（比如变成了普通的js/json），立刻放行
        $headers = headers_list();
        $isHtml = true;
        foreach ($headers as $header) {
            if (stripos($header, 'Content-Type:') !== false && stripos($header, 'text/html') === false) {
                $isHtml = false;
                break;
            }
        }
        if (!$isHtml) return $html;

        $quote = $GLOBALS['mj_quote'];
        $logo = $GLOBALS['mj_logo'];

        // 🚀 核心替换逻辑不变
        $html = preg_replace('/<meta[^>]*property=["\']og:image["\'][^>]*content=["\'][^"\']*["\'][^ >]*>/i', '<meta property="og:image" content="'.$logo.'">', $html);
        $html = preg_replace('/<meta[^>]*property=["\']og:description["\'][^>]*content=["\'][^"\']*["\'][^ >]*>/i', '<meta property="og:description" content="'.$quote.'">', $html);
        $html = preg_replace('/<meta[^>]*name=["\']description["\'][^>]*content=["\'][^"\']*["\'][^ >]*>/i', '<meta name="description" content="'.$quote.'">', $html);
        $html = preg_replace('/<link[^>]*rel=["\']shortcut icon["\'][^>]*href=["\'][^"\']*["\'][^ >]*>/i', '<link rel="shortcut icon" type="image/ico" href="'.$logo.'">', $html);
        $html = preg_replace('/<img[^>]*class=["\']mj-icon["\'][^>]*src=["\'][^"\']*["\'][^ >]*>/i', '<img src="'.$logo.'" class="mj-icon" alt="🌍𝐌𝐉全球华人之家🕊️">', $html);
        $html = preg_replace('/<div[^>]*id=["\']mj-random-quote["\'][^>]*>.*?<\/div>/is', '<div id="mj-random-quote">'.$quote.'</div>', $html);

        return $html;
    });

}