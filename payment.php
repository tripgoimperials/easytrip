<?php
require 'includes/init.php';
$u=require_login();
$b=Booking::byRef($_GET['ref']??'',$u['id']);
if(!$b) exit('Booking not found.');
if($b['status']==='confirmed'){header('Location: confirmation.php?ref='.$b['booking_ref']);exit;}
include 'includes/header.php';
?>
<main class="page"><div class="wrap">
<h1>Payment</h1>
<?php if($b['status']==='expired'): ?>
<div class="err">Your seat hold expired. <a href="index.php">Start again</a>.</div>
<?php else: ?>
<div class="pay-grid">
  <div class="card panel">
    <h3>Booking Summary</h3>
    <div class="summary-row"><span>Route</span><b><?= e($b['origin'].' → '.$b['destination']) ?></b></div>
    <div class="summary-row"><span>Bus</span><b><?= e($b['bus_name']) ?></b></div>
    <div class="summary-row"><span>Travel Date</span><b><?= date('d M Y',strtotime($b['departure_time'])) ?></b></div>
    <div class="summary-row"><span>Seat Number</span><b><?= e($b['seats']) ?></b></div>
    <div class="summary-row total"><span>Total Amount</span><b><?= naira($b['total_amount']) ?></b></div>
    <small>Seats held until <?= date('g:i A',strtotime($b['expires_at'])) ?>.</small>
  </div>
  <div class="card panel methods">
    <h3>Choose Payment Method</h3>
    <label class="method"><input type="radio" name="payment_method" value="card" checked> 💳 Card Payment</label>
    <label class="method"><input type="radio" name="payment_method" value="ussd"> 📱 USSD</label>
    <label class="method"><input type="radio" name="payment_method" value="bank_transfer"> 🏦 Bank Transfer</label>
    <button type="button" class="btn primary full" id="proceedPayment">Proceed to Pay</button>
  </div>
</div>
<script>
document.getElementById('proceedPayment').addEventListener('click', function () {
    const method=document.querySelector('input[name="payment_method"]:checked').value;
    const ref=<?= json_encode($b['booking_ref']) ?>;
    const pages={card:'card.php',ussd:'ussd.php',bank_transfer:'transfer.php'};
    window.location.href=pages[method]+'?ref='+encodeURIComponent(ref);
});
</script>
<?php endif; ?>
</div></main>
<?php include 'includes/footer.php'; ?>
