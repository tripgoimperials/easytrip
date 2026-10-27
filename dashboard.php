<?php
require 'includes/init.php';
$sessionUser=require_login();
$profile=logged_in_user_record();
if(!$profile){ session_destroy(); header('Location: login.php'); exit; }
$db=Database::conn();
$st=$db->prepare("SELECT COUNT(*) c,COALESCE(SUM(total_amount),0) s FROM bookings WHERE user_id=? AND status='confirmed'");$st->execute([$profile['id']]);$t=$st->fetch();
$st=$db->prepare("SELECT COUNT(*) FROM bookings bk JOIN trips t ON t.id=bk.trip_id WHERE bk.user_id=? AND bk.status='confirmed' AND t.departure_time>NOW()");$st->execute([$profile['id']]);$up=$st->fetchColumn();
$st=$db->prepare("SELECT COUNT(*) FROM bookings bk JOIN trips t ON t.id=bk.trip_id WHERE bk.user_id=? AND bk.status='confirmed' AND t.departure_time<=NOW()");$st->execute([$profile['id']]);$completed=$st->fetchColumn();
$recent=Booking::forUser($profile['id']);
include 'includes/header.php';
?>
<main class="page"><div class="wrap">
<div class="welcome"><div><small>Welcome back,</small><h2><?= e($profile['full_name']) ?></h2><div class="meta"><?= e($profile['email']) ?> · <?= e($profile['phone']) ?></div></div><span>🔔</span></div>
<div class="stats"><div class="card stat"><span>Total Bookings</span><strong><?= (int)$t['c'] ?></strong></div><div class="card stat"><span>Upcoming Trips</span><strong><?= (int)$up ?></strong></div><div class="card stat"><span>Completed Trips</span><strong><?= (int)$completed ?></strong></div><div class="card stat"><span>Total Spent</span><strong><?= naira($t['s']) ?></strong></div></div>
<div class="grid2"><div class="card panel"><h3>Book a New Trip</h3><p class="meta">Search routes and reserve an available seat.</p><a class="btn primary full" href="index.php">Search Buses</a></div><div class="card panel"><h3>My Account</h3><div class="summary-row"><span>Name</span><b><?= e($profile['full_name']) ?></b></div><div class="summary-row"><span>Email</span><b><?= e($profile['email']) ?></b></div><div class="summary-row"><span>Phone</span><b><?= e($profile['phone']) ?></b></div><a class="btn outline full" href="profile.php">Edit Profile</a></div></div>
<?php if($recent): ?><div class="card panel" style="margin-top:20px"><h3>Recent Booking</h3><b><?= e($recent[0]['origin'].' → '.$recent[0]['destination']) ?></b><div class="meta"><?= date('d M Y | g:i A',strtotime($recent[0]['departure_time'])) ?> · <?= e($recent[0]['bus_name']) ?> · Seat <?= e($recent[0]['seats']) ?></div><div style="margin-top:12px"><a class="btn outline" href="history.php">View My Bookings</a></div></div><?php endif; ?>
</div></main><?php include 'includes/footer.php'; ?>
