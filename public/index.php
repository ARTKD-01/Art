<?php

declare(strict_types=1);

session_name('webdev_app_session');
$https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
session_set_cookie_params(['httponly'=>true, 'secure'=>$https || true, 'samesite'=>'None', 'path'=>'/']);
session_start();

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// PHP's built-in server uses this file as a router in Preview, so explicitly pass
// through the committed manifest and frontend assets instead of treating them as pages.
$static = [
    '/manus-routes.json' => ['path' => __DIR__.'/manus-routes.json', 'type' => 'application/json'],
    '/assets/app.css' => ['path' => __DIR__.'/assets/app.css', 'type' => 'text/css; charset=UTF-8'],
    '/assets/app.js' => ['path' => __DIR__.'/assets/app.js', 'type' => 'text/javascript; charset=UTF-8'],
];
if (isset($static[$path]) && is_file($static[$path]['path'])) {
    header('Content-Type: '.$static[$path]['type']);
    readfile($static[$path]['path']);
    exit;
}

if ($path === '/health') {
    header('Content-Type: application/json');
    echo json_encode(['ok'=>true,'service'=>'gangdesk','source'=>'discord']);
    exit;
}

if ($path === '/oauth/discord') {
    $client = getenv('DISCORD_CLIENT_ID');
    $redirect = getenv('DISCORD_REDIRECT_URI') ?: '/oauth/callback';
    if (!$client) { notice('ยังไม่ได้ตั้งค่า Discord OAuth ในระบบ'); redirect('/login'); }
    $_SESSION['oauth_state'] = bin2hex(random_bytes(24));
    $params = http_build_query(['client_id'=>$client,'redirect_uri'=>$redirect,'response_type'=>'code','scope'=>'identify guilds','state'=>$_SESSION['oauth_state']]);
    header('Location: https://discord.com/oauth2/authorize?'.$params); exit;
}
if ($path === '/oauth/callback') {
    if (!isset($_GET['state'], $_SESSION['oauth_state']) || !hash_equals($_SESSION['oauth_state'], (string)$_GET['state'])) { notice('การยืนยัน Discord ไม่ถูกต้อง'); redirect('/login'); }
    unset($_SESSION['oauth_state']);
    // Production integration point: exchange code server-side, verify guild membership, then store only the session identity.
    notice('ได้รับการยืนยันจาก Discord แล้ว แต่ต้องตั้งค่า DISCORD_CLIENT_ID/SECRET และ Guild ID ก่อนใช้งานจริง');
    redirect('/login');
}
if ($path === '/logout') { session_destroy(); redirect('/login'); }

$public = in_array($path, ['/', '/login', '/leader-login'], true);
$logged = !empty($_SESSION['discord_user']);
if (!$public && !$logged) { redirect('/login'); }

if ($method === 'POST' && $path === '/profile') {
    $_SESSION['profile_saved'] = true;
    notice('บันทึกโปรไฟล์ไว้ชั่วคราวแล้ว — เมื่อเชื่อม Discord สำเร็จจะบันทึกเป็น event ใน Discord');
    redirect('/profile');
}
if ($method === 'POST' && $path === '/leader-login') {
    notice('ระบบ PIN พร้อมใช้งานหลังตั้งค่า HEAD_PIN และบัญชีหัวแก๊งผ่าน Discord OAuth');
    redirect('/leader-login');
}

$pages = [
 '/dashboard'=>['ภาพรวม','สรุปสถานะจาก Discord'], '/attendance'=>['เช็คชื่อ','เช็คชื่อรายวันจากข้อมูล Discord'], '/members'=>['รายชื่อแก๊ง','สมาชิกและยศจาก Discord'],
 '/deliveries'=>['ส่งของแก๊ง','ส่งของสัปดาห์ละหนึ่งครั้ง'], '/delivery-history'=>['รายการส่งของ','ประวัติการส่งของ'], '/evidence'=>['หลักฐานส่งของ','ไฟล์แนบจาก Discord'], '/withdrawals'=>['เบิกของแก๊ง','คำขอเบิกและสถานะ'], '/leave'=>['แจ้งลา','ช่วงเวลาและเหตุผลการลา'],
 '/absence'=>['ขาดเช็คชื่อ','จำนวนวันที่ไม่เช็คชื่อ'], '/stats'=>['สถิติรวม','ภาพรวมรายวันและรายสัปดาห์'], '/phones'=>['เบอร์ในเกม','เบอร์ติดต่อในเกมของสมาชิก'], '/profile'=>['โปรไฟล์ของฉัน','โปรไฟล์ Discord และข้อมูลส่วนตัว'], '/settings'=>['ตั้งค่า','ตั้งค่าการเชื่อมต่อและหน้าตาเว็บ']
];

function redirect(string $to): never { header('Location: '.$to); exit; }
function notice(string $message): void { $_SESSION['notice'] = $message; }
function e(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
function navItem(string $href, string $label, string $path): string { $active = $href === $path ? 'active' : ''; return '<a class="nav-item '.$active.'" href="'.e($href).'"><span>'.e($label).'</span></a>'; }
function layout(string $title, string $subtitle, string $content, string $path): void {
    $notice = $_SESSION['notice'] ?? null; unset($_SESSION['notice']);
    $user = $_SESSION['discord_user'] ?? null;
    ?><!doctype html><html lang="th"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=e($title)?> · Gang Desk</title><link rel="preconnect" href="https://fonts.googleapis.com"><link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet"><link rel="stylesheet" href="/assets/app.css"></head><body>
    <div class="ambient"></div><div class="shell"><aside class="rail"><a class="brand" href="/dashboard"><span class="brand-mark">GD</span><span><b>GANG</b><small>DESK</small></span></a><div class="rail-label">COMMAND RAIL</div><nav><?=navItem('/dashboard','ภาพรวม',$path).navItem('/attendance','เช็คชื่อ',$path).navItem('/members','สมาชิกแก๊ง',$path).navItem('/deliveries','ส่งของแก๊ง',$path).navItem('/delivery-history','รายการส่งของ',$path).navItem('/evidence','หลักฐาน',$path).navItem('/withdrawals','เบิกของ',$path).navItem('/leave','แจ้งลา',$path).navItem('/absence','ขาดเช็คชื่อ',$path).navItem('/stats','สถิติรวม',$path).navItem('/phones','เบอร์ในเกม',$path) ?></nav><div class="rail-bottom"><a class="nav-item" href="/settings">ตั้งค่า</a><a class="nav-item" href="/profile">โปรไฟล์ของฉัน</a><a class="nav-item" href="/logout">ออกจากระบบ</a></div></aside><main class="main"><header class="topbar"><div><div class="eyebrow">GANG OPERATIONS / DISCORD DATA PLANE</div><h1><?=e($title)?></h1><p><?=e($subtitle)?></p></div><div class="user-chip"><?php if ($user): ?><span class="avatar"><?=e(strtoupper(substr((string)($user['username'] ?? 'D'),0,1)))?></span><span><?=e($user['username'] ?? 'Discord member')?></span><?php else: ?><span class="status-dot"></span>ยังไม่เชื่อม Discord<?php endif; ?></div></header><?php if ($notice): ?><div class="notice"><?=e($notice)?></div><?php endif; ?><?=$content?></main></div><script src="/assets/app.js"></script></body></html><?php
}

if ($path === '/' || $path === '/login') { ob_start(); ?><section class="hero-login"><div class="hero-copy"><div class="eyebrow">MIDNIGHT GLASS LEDGER</div><h2>ศูนย์บัญชาการ<br><em>ของทีมคุณ</em></h2><p>ระบบแก๊งที่เชื่อมข้อมูล การเช็คชื่อ ส่งของ และสิทธิ์สมาชิกไว้กับ Discord โดยตรง ไม่มีข้อมูลตัวอย่าง ไม่มีฐานข้อมูลเงา</p><div class="login-actions"><a class="btn primary" href="/oauth/discord">เข้าสู่ระบบด้วย Discord <span>→</span></a><a class="btn ghost" href="/leader-login">หัวแก๊งเข้าสู่ระบบด้วย PIN</a></div><div class="login-note"><span class="pulse"></span>แจ้งเตือนทั้งหมดส่งเข้า Discord เท่านั้น</div></div><div class="hero-orbit"><div class="orbit-ring ring-a"></div><div class="orbit-ring ring-b"></div><div class="orbit-core"><span>GD</span><small>CONNECTED<br>BY DESIGN</small></div></div></section><?php layout('Gang Desk','แดชบอร์ดปฏิบัติการของแก๊งที่เชื่อมกับ Discord',$content=ob_get_clean(),$path); exit; }
if ($path === '/leader-login') { ob_start(); ?><section class="auth-card"><div class="auth-icon">◆</div><h2>หัวแก๊งเท่านั้น</h2><p>ยืนยันตัวตนด้วย Discord ก่อน แล้วจึงกรอก PIN ในช่องลับ ระบบจะไม่แสดงหรือส่ง PIN กลับในหน้าเว็บ</p><form method="post" class="form-stack"><label>PIN ของหัวแก๊ง<input type="password" name="pin" placeholder="••••••••" autocomplete="current-password"></label><button class="btn primary" type="submit">ยืนยันสิทธิ์หัวแก๊ง <span>→</span></button></form><a class="back-link" href="/login">← กลับหน้าเข้าสู่ระบบ Discord</a></section><?php layout('หัวแก๊ง','พื้นที่ควบคุมที่มีสิทธิ์สูง',$content=ob_get_clean(),$path); exit; }

if (isset($pages[$path])) { [$title,$subtitle] = $pages[$path]; ob_start(); ?><div class="page-grid"><section class="panel hero-panel"><div class="panel-kicker">LIVE FROM DISCORD</div><h2><?=e($title)?></h2><p><?=e($subtitle)?> เมื่อเชื่อมต่อ Discord และตั้งค่า channel แล้ว ข้อมูลจริงจะปรากฏที่นี่</p><div class="empty-state"><div class="empty-icon">⌁</div><strong>ยังไม่มีข้อมูลที่เชื่อมต่อ</strong><span>หน้านี้จะแสดงเฉพาะข้อมูลจริงจาก Discord — ระบบจะไม่ใส่ข้อมูลตัวอย่าง</span><?php if ($path === '/profile'): ?><form method="post" class="profile-form"><label>ชื่อที่แสดงของฉัน<input name="display_name" placeholder="กรอกชื่อที่ต้องการแสดง"></label><label>ชื่อ IC<input name="ic_name" placeholder="กรอกชื่อ IC"></label><button class="btn primary" type="submit">บันทึกโปรไฟล์</button></form><?php elseif ($path === '/settings'): ?><div class="settings-grid"><div class="setting-row"><span>แหล่งข้อมูล</span><b>Discord only</b></div><div class="setting-row"><span>สถานะ Bot</span><b class="warn">รอตั้งค่า secret</b></div><div class="setting-row"><span>พื้นหลัง / โลโก้</span><b>พร้อมปรับจากหน้านี้</b></div></div><?php else: ?><a class="btn ghost" href="/settings">ไปตั้งค่าการเชื่อมต่อ Discord</a><?php endif; ?></div></section><aside class="side-stack"><div class="metric-card"><span>DATA SOURCE</span><strong>DISCORD</strong><small>แหล่งข้อมูลหลักของแก๊ง</small></div><div class="metric-card accent"><span>NOTIFICATIONS</span><strong>DISCORD ONLY</strong><small>ไม่มีอีเมลหรือศูนย์แจ้งเตือนแยก</small></div></aside></div><?php layout($title,$subtitle,$content=ob_get_clean(),$path); exit; }
http_response_code(404); layout('ไม่พบหน้า','เส้นทางนี้ไม่มีอยู่ในระบบ','<section class="panel"><h2>404</h2><p>ไม่พบหน้าที่ร้องขอ</p></section>',$path);
