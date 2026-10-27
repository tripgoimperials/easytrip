<?php
require 'includes/init.php';
$u=require_login();
$b=Booking::byRef($_GET['ref']??'',$u['id']);
if(!$b||$b['status']!=='pending') exit('This booking can no longer be paid. <a href="index.php">Start again</a>.');
$err='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    try {
        if(($_POST['demo_result']??'success')==='fail') {
            $err=Payment::recordFailed($b,'ussd','Simulated USSD payment failed. Please try again.');
        } else {
            Payment::payByUssd($b); header('Location: confirmation.php?ref='.$b['booking_ref']); exit;
        }
    } catch(Throwable $e){$err=$e->getMessage();}
}
include 'includes/header.php';
?>
<main class="page"><div class="wrap"><div class="auth" style="max-width:620px">
<form class="card panel" method="post">
<h2>USSD Payment</h2>
<?php if($err): ?><div class="err"><?= e($err) ?></div><?php endif; ?>
<p class="subtitle">Use the demo USSD code below to simulate payment for this school project.</p>
<div class="summary-row"><span>Amount</span><b><?= naira($b['total_amount']) ?></b></div>
<div class="summary-row"><span>Booking Ref</span><b><?= e($b['booking_ref']) ?></b></div>
<div class="card panel" style="margin:18px 0;text-align:center;background:#f4f9ff">
<small>Demo USSD Code</small><h2 style="margin:8px 0">*737*50*<?= (int)$b['id'] ?>#</h2>
<p class="meta">Dial this code on your phone in a real integration. For this demo, click the button below after simulating the payment.</p>
</div>
<button class="btn primary full" type="submit" name="demo_result" value="success">Simulate Successful USSD Payment</button><button class="btn outline full" style="margin-top:10px" type="submit" name="demo_result" value="fail">Simulate Failed USSD Payment</button>
<p class="helper">Demo only — no real bank transaction is performed.</p>
</form></div></div></main>
<?php include 'includes/footer.php'; ?>
