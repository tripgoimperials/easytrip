<?php
require 'includes/init.php';$admin=require_admin();$db=Database::conn();$err='';$ok='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();$action=$_POST['action']??'';
 try{
  if($action==='add'){
   $name=trim($_POST['full_name']??'');$email=trim($_POST['email']??'');$phone=trim($_POST['phone']??'');$password=$_POST['password']??'';
   if($name===''||!filter_var($email,FILTER_VALIDATE_EMAIL)||!preg_match('/^[0-9+]{10,15}$/',$phone)||strlen($password)<6)throw new Exception('Enter valid passenger details and a password of at least 6 characters.');
   Passenger::register($name,$email,$phone,$password);$ok='Passenger account added.';
  } elseif($action==='edit'){
   $id=(int)($_POST['id']??0);$name=trim($_POST['full_name']??'');$email=strtolower(trim($_POST['email']??''));$phone=trim($_POST['phone']??'');
   if(!$id||$name===''||!filter_var($email,FILTER_VALIDATE_EMAIL)||!preg_match('/^[0-9+]{10,15}$/',$phone))throw new Exception('Enter valid passenger details.');
   $st=$db->prepare("UPDATE users SET full_name=?,email=?,phone=? WHERE id=? AND role='passenger'");$st->execute([$name,$email,$phone,$id]);$ok='Passenger account updated.';
  } elseif($action==='delete'){
   $id=(int)($_POST['id']??0);$st=$db->prepare("SELECT role FROM users WHERE id=?");$st->execute([$id]);if($st->fetchColumn()!=='passenger')throw new Exception('Passenger account not found.');
   $db->beginTransaction();
   $db->prepare('DELETE p FROM payments p JOIN bookings b ON b.id=p.booking_id WHERE b.user_id=?')->execute([$id]);
   $db->prepare('DELETE FROM bookings WHERE user_id=?')->execute([$id]);
   $db->prepare("DELETE FROM users WHERE id=? AND role='passenger'")->execute([$id]);
   $db->commit();$ok='Passenger account and its booking records were removed.';
  }
 }catch(Throwable $e){if($db->inTransaction())$db->rollBack();$err=$e instanceof PDOException && $e->getCode()==23000?'That email address or phone number already exists.':$e->getMessage();}
}
$edit=null;if(isset($_GET['edit'])){$st=$db->prepare("SELECT id,full_name,email,phone FROM users WHERE id=? AND role='passenger'");$st->execute([(int)$_GET['edit']]);$edit=$st->fetch();}
$rows=$db->query("SELECT u.id,u.full_name,u.email,u.phone,u.created_at,COUNT(b.id) bookings FROM users u LEFT JOIN bookings b ON b.user_id=u.id WHERE u.role='passenger' GROUP BY u.id ORDER BY u.created_at DESC")->fetchAll();
include 'includes/header.php'; ?>
<main class="page"><div class="wrap"><div class="welcome"><div><h2>Manage Passengers</h2><p class="meta">Accounts shown here come directly from the MySQL users table.</p></div><a class="btn outline" href="admin.php">← Admin Dashboard</a></div><?php if($err):?><div class="err"><?=e($err)?></div><?php endif;?><?php if($ok):?><div class="status" style="margin-bottom:15px"><?=e($ok)?></div><?php endif;?>
<div class="grid2"><form class="card panel" method="post"><h3><?= $edit?'Edit Passenger':'Add Passenger' ?></h3><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="<?=$edit?'edit':'add'?>"><?php if($edit):?><input type="hidden" name="id" value="<?=(int)$edit['id']?>"><?php endif;?><div class="field"><label>Full Name</label><input name="full_name" value="<?=e($edit['full_name']??'')?>" required></div><div class="field"><label>Email</label><input type="email" name="email" value="<?=e($edit['email']??'')?>" required></div><div class="field"><label>Phone</label><input name="phone" value="<?=e($edit['phone']??'')?>" required></div><?php if(!$edit):?><div class="field"><label>Temporary Password</label><input type="password" name="password" minlength="6" required></div><?php endif;?><button class="btn primary full"><?=$edit?'Save Changes':'Add Passenger'?></button><?php if($edit):?><a class="btn outline full" style="display:block;text-align:center;margin-top:8px" href="admin-passengers.php">Cancel</a><?php endif;?></form>
<div class="card panel"><h3>Database Information</h3><div class="summary-row"><span>Database</span><b>easytrip</b></div><div class="summary-row"><span>Account Table</span><b>users</b></div><div class="summary-row"><span>Passenger Accounts</span><b><?=count($rows)?></b></div><p class="meta">You can also inspect the same records in XAMPP → phpMyAdmin → easytrip → users.</p></div></div>
<div class="card panel" style="margin-top:20px"><h3>Passenger Accounts</h3><div class="table-wrap"><table class="table"><thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Bookings</th><th>Registered</th><th>Actions</th></tr></thead><tbody><?php if(!$rows):?><tr><td colspan="7">No passenger accounts yet.</td></tr><?php endif;?><?php foreach($rows as $r):?><tr><td><?=(int)$r['id']?></td><td><?=e($r['full_name'])?></td><td><?=e($r['email'])?></td><td><?=e($r['phone'])?></td><td><?=(int)$r['bookings']?></td><td><?=date('d M Y',strtotime($r['created_at']))?></td><td><a href="admin-passengers.php?edit=<?=(int)$r['id']?>">Edit</a> · <form method="post" style="display:inline" onsubmit="return confirm('Delete this passenger and all of their booking/payment/ticket records?');"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=(int)$r['id']?>"><button class="link-button" type="submit">Delete</button></form></td></tr><?php endforeach;?></tbody></table></div></div></div></main><?php include 'includes/footer.php'; ?>
