<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

date_default_timezone_set('Africa/Lagos');

class Database {
    private static $pdo = null;
    public static function conn() {
        if (self::$pdo === null) {
            $host = '127.0.0.1'; $db = 'easytrip'; $user = 'root'; $pass = '';
            $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
            self::$pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        }
        return self::$pdo;
    }
}

function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function naira($v) { return '₦' . number_format((float)$v, 2); }
function current_user() { return $_SESSION['user'] ?? null; }
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}
function verify_csrf() {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        http_response_code(419); exit('Invalid or expired form token. Please go back and try again.');
    }
}
function logged_in_user_record() {
    $u = current_user();
    if (!$u) return null;
    $st = Database::conn()->prepare('SELECT id,full_name,email,phone,role,created_at FROM users WHERE id=? LIMIT 1');
    $st->execute([$u['id']]);
    return $st->fetch() ?: null;
}
function require_login() {
    $u = current_user();
    if (!$u) {
        $next = basename($_SERVER['PHP_SELF']);
        if (!empty($_SERVER['QUERY_STRING'])) $next .= '?' . $_SERVER['QUERY_STRING'];
        header('Location: login.php?next=' . urlencode($next)); exit;
    }
    return $u;
}
function require_admin() {
    $u = require_login();
    if (($u['role'] ?? '') !== 'admin') { http_response_code(403); exit('Access denied.'); }
    return $u;
}

class Passenger {
    public static function find($id) {
        $st=Database::conn()->prepare('SELECT id,full_name,email,phone,role,created_at FROM users WHERE id=? LIMIT 1');
        $st->execute([$id]); return $st->fetch();
    }
    public static function all() {
        return Database::conn()->query("SELECT id,full_name,email,phone,role,created_at FROM users ORDER BY created_at DESC")->fetchAll();
    }
    public static function register($name, $email, $phone, $password) {
        $st = Database::conn()->prepare('INSERT INTO users (full_name,email,phone,password_hash,role) VALUES (?,?,?,?,\'passenger\')');
        $st->execute([$name, strtolower($email), $phone, password_hash($password, PASSWORD_DEFAULT)]);
        return Database::conn()->lastInsertId();
    }
    public static function login($email, $password) {
        $st = Database::conn()->prepare('SELECT * FROM users WHERE email=? LIMIT 1');
        $st->execute([strtolower($email)]); $u = $st->fetch();
        return ($u && $u['password_hash'] && password_verify($password, $u['password_hash'])) ? $u : false;
    }
}

class Route {
    public static function all() { return Database::conn()->query('SELECT * FROM routes ORDER BY origin,destination')->fetchAll(); }
}

class Bus {
    public static function search($origin, $destination, $date) {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) return [];
        $sql = "SELECT t.*, b.bus_name,b.bus_type,b.total_seats,r.origin,r.destination,
                (b.total_seats - (SELECT COUNT(*) FROM booking_seats bs JOIN bookings bk ON bk.id=bs.booking_id
                 WHERE bs.trip_id=t.id AND bk.status IN ('pending','confirmed') AND (bk.status='confirmed' OR bk.expires_at>NOW()))) seats_left
                FROM trips t JOIN buses b ON b.id=t.bus_id JOIN routes r ON r.id=t.route_id
                WHERE r.origin=? AND r.destination=? AND DATE(t.departure_time)=? AND t.status='scheduled'
                ORDER BY t.departure_time";
        $st = Database::conn()->prepare($sql); $st->execute([$origin,$destination,$date]); return $st->fetchAll();
    }
    public static function trip($id) {
        $st = Database::conn()->prepare('SELECT t.*,b.bus_name,b.bus_type,b.total_seats,r.origin,r.destination FROM trips t JOIN buses b ON b.id=t.bus_id JOIN routes r ON r.id=t.route_id WHERE t.id=?');
        $st->execute([$id]); return $st->fetch();
    }
}

class Seat {
    public static function forTrip($tripId) {
        $trip = Bus::trip($tripId); if (!$trip) return [];
        $sql = "SELECT s.id,s.seat_number, EXISTS(
                SELECT 1 FROM booking_seats bs JOIN bookings bk ON bk.id=bs.booking_id
                WHERE bs.trip_id=? AND bs.seat_id=s.id AND bk.status IN ('pending','confirmed')
                AND (bk.status='confirmed' OR bk.expires_at>NOW())) taken
                FROM seats s WHERE s.bus_id=? ORDER BY s.seat_number";
        $st=Database::conn()->prepare($sql); $st->execute([$tripId,$trip['bus_id']]); return $st->fetchAll();
    }
    public static function takenIds($tripId) {
        $rows=self::forTrip($tripId); return array_values(array_map(fn($r)=>(int)$r['id'], array_filter($rows,fn($r)=>$r['taken'])));
    }
}

class Booking {
    public static function releaseExpired() {
        // Remove seat holds first so the UNIQUE(trip_id, seat_id) constraint releases them.
        // Confirmed bookings are never touched.
        $db = Database::conn();
        $db->beginTransaction();
        try {
            $db->exec("DELETE bs FROM booking_seats bs JOIN bookings bk ON bk.id=bs.booking_id WHERE bk.status='pending' AND bk.expires_at IS NOT NULL AND bk.expires_at<=NOW()");
            $db->exec("UPDATE bookings SET status='expired' WHERE status='pending' AND expires_at IS NOT NULL AND expires_at<=NOW()");
            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            throw $e;
        }
    }
    public static function create($userId,$tripId,$seatIds) {
        self::releaseExpired();
        $seatIds=array_values(array_unique(array_map('intval',(array)$seatIds)));
        if (!$seatIds || count($seatIds)>5) throw new Exception('Select between 1 and 5 seats.');
        $db=Database::conn(); $trip=Bus::trip($tripId); if(!$trip) throw new Exception('Trip not found.');
        $db->beginTransaction();
        try {
            $marks=implode(',',array_fill(0,count($seatIds),'?'));
            $params=array_merge([$trip['bus_id']],$seatIds);
            $st=$db->prepare("SELECT id FROM seats WHERE bus_id=? AND id IN ($marks) FOR UPDATE"); $st->execute($params);
            if(count($st->fetchAll())!==count($seatIds)) throw new Exception('Invalid seat selection.');
            $params=array_merge([$tripId],$seatIds);
            $st=$db->prepare("SELECT bs.seat_id FROM booking_seats bs JOIN bookings bk ON bk.id=bs.booking_id WHERE bs.trip_id=? AND bs.seat_id IN ($marks) AND bk.status IN ('pending','confirmed') AND (bk.status='confirmed' OR bk.expires_at>NOW()) FOR UPDATE");
            $st->execute($params); if($st->fetch()) throw new Exception('One of those seats has just been taken. Please choose again.');
            $ref='ET'.date('ymd').strtoupper(substr(bin2hex(random_bytes(4)),0,6));
            $total=(float)$trip['price']*count($seatIds);
            $st=$db->prepare("INSERT INTO bookings (booking_ref,user_id,trip_id,total_amount,status,expires_at) VALUES (?,?,?,?,'pending',DATE_ADD(NOW(),INTERVAL 15 MINUTE))");
            $st->execute([$ref,$userId,$tripId,$total]); $bid=$db->lastInsertId();
            $ins=$db->prepare('INSERT INTO booking_seats (booking_id,trip_id,seat_id) VALUES (?,?,?)'); foreach($seatIds as $sid)$ins->execute([$bid,$tripId,$sid]);
            $db->commit(); return $ref;
        } catch(Throwable $e){ if($db->inTransaction())$db->rollBack(); throw $e; }
    }
    public static function byRef($ref,$userId=null) {
        self::releaseExpired();
        $sql="SELECT bk.*,t.departure_time,t.arrival_time,t.price,b.bus_name,b.bus_type,r.origin,r.destination,
             GROUP_CONCAT(s.seat_number ORDER BY s.seat_number SEPARATOR ', ') seats,
             tk.ticket_code FROM bookings bk JOIN trips t ON t.id=bk.trip_id JOIN buses b ON b.id=t.bus_id JOIN routes r ON r.id=t.route_id
             LEFT JOIN booking_seats bs ON bs.booking_id=bk.id LEFT JOIN seats s ON s.id=bs.seat_id LEFT JOIN tickets tk ON tk.booking_id=bk.id
             WHERE bk.booking_ref=?";
        $p=[$ref]; if($userId!==null){$sql.=' AND bk.user_id=?';$p[]=$userId;} $sql.=' GROUP BY bk.id';
        $st=Database::conn()->prepare($sql);$st->execute($p);return $st->fetch();
    }
    public static function forUser($uid) {
        self::releaseExpired();
        $sql="SELECT bk.*,t.departure_time,b.bus_name,r.origin,r.destination,GROUP_CONCAT(s.seat_number ORDER BY s.seat_number SEPARATOR ', ') seats
              FROM bookings bk JOIN trips t ON t.id=bk.trip_id JOIN buses b ON b.id=t.bus_id JOIN routes r ON r.id=t.route_id
              LEFT JOIN booking_seats bs ON bs.booking_id=bk.id LEFT JOIN seats s ON s.id=bs.seat_id WHERE bk.user_id=? GROUP BY bk.id ORDER BY bk.created_at DESC";
        $st=Database::conn()->prepare($sql);$st->execute([$uid]);return $st->fetchAll();
    }
}

class Payment {
    private static function complete($booking,$method,$last4=null) {
        $allowed=['card','ussd','bank_transfer'];
        if(!in_array($method,$allowed,true)) throw new Exception('Invalid payment method.');
        if($booking['status']!=='pending' || strtotime($booking['expires_at'])<=time()) throw new Exception('Booking expired.');
        $db=Database::conn();$db->beginTransaction();
        try {
            $ref='PAY-'.strtoupper(bin2hex(random_bytes(6)));
            $st=$db->prepare("INSERT INTO payments (booking_id,method,amount,gateway_reference,card_last4,status,paid_at) VALUES (?,?,?,?,?,'success',NOW())");
            $st->execute([$booking['id'],$method,$booking['total_amount'],$ref,$last4]);
            $up=$db->prepare("UPDATE bookings SET status='confirmed',expires_at=NULL WHERE id=? AND status='pending'");
            $up->execute([$booking['id']]);
            if($up->rowCount()!==1) throw new Exception('This booking can no longer be paid.');
            $code='TKT-'.strtoupper(bin2hex(random_bytes(8)));
            $db->prepare('INSERT INTO tickets (booking_id,ticket_code) VALUES (?,?)')->execute([$booking['id'],$code]);
            $db->commit(); return $ref;
        } catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
    }
    public static function recordFailed($booking,$method,$reason='Simulated payment failure') {
        $allowed=['card','ussd','bank_transfer'];
        if(!in_array($method,$allowed,true)) throw new Exception('Invalid payment method.');
        $ref='FAIL-'.strtoupper(bin2hex(random_bytes(5)));
        $st=Database::conn()->prepare("INSERT INTO payments (booking_id,method,amount,gateway_reference,status,paid_at) VALUES (?,?,?,?, 'failed', NULL)");
        $st->execute([$booking['id'],$method,$booking['total_amount'],$ref]);
        return $reason;
    }
    public static function payByCard($booking,$last4) { return self::complete($booking,'card',$last4); }
    public static function payByUssd($booking) { return self::complete($booking,'ussd'); }
    public static function payByTransfer($booking) { return self::complete($booking,'bank_transfer'); }
}
