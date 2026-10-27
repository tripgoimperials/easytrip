<?php
require 'includes/init.php';
$u=require_login();
$b=Booking::byRef($_GET['ref']??'',$u['id']);
if(!$b||$b['status']!=='pending') exit('This booking can no longer be paid. <a href="index.php">Start again</a>.');
$err='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    try {
        if(($_POST['demo_result']??'success')==='fail') {
            $err=Payment::recordFailed($b,'bank_transfer','Simulated bank transfer failed. Please try again.');
        } else {
            Payment::payByTransfer($b); header('Location: confirmation.php?ref='.$b['booking_ref']); exit;
        }
    } catch(Throwable $e){$err=$e->getMessage();}
}
include 'includes/header.php';
?>
<main class="page"><div class="wrap"><div class="auth" style="max-width:620px">
<form class="card panel" method="post">
<h2>Bank Transfer</h2>
<?php if($err): ?><div class="err"><?= e($err) ?></div><?php endif; ?>
<p class="subtitle">Transfer the exact amount using the demo account information below.</p>
<div class="card panel" style="margin-bottom:18px;background:#f4f9ff">
<div class="summary-row"><span>Bank</span><b>EasyTrip Demo Bank</b></div>
<div class="summary-row"><span>Account Name</span><b>EasyTrip Transport Ltd</b></div>
<div class="summary-row"><span>Account Number</span><b>0123456789</b></div>
<div class="summary-row"><span>Amount</span><b><?= naira($b['total_amount']) ?></b></div>
<div class="summary-row"><span>Narration</span><b><?= e($b['booking_ref']) ?></b></div>
</div>
<button class="btn primary full" type="submit" name="demo_result" value="success">Simulate Successful Transfer</button><button class="btn outline full" style="margin-top:10px" type="submit" name="demo_result" value="fail">Simulate Failed Transfer</button>
<p class="helper">Demo only — no real bank transfer is performed.</p>
</form></div></div></main>
<?php include 'includes/footer.php'; ?>
