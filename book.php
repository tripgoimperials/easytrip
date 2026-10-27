<?php require 'includes/init.php'; $u = require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php'); exit; }
$tid = (int)$_POST['trip'];
try { $ref = Booking::create($u['id'], $tid, $_POST['seats'] ?? []); header('Location: payment.php?ref=' . $ref); }
catch (Exception $e) { header('Location: seats.php?trip=' . $tid . '&err=' . urlencode($e->getMessage())); }
