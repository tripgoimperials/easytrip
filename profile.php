<?php
require 'includes/init.php';$u=require_login();$db=Database::conn();$err='';$ok='';$profile=logged_in_user_record();
if(!$profile){session_destroy();header('Location: login.php');exit;}
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();$name=trim($_POST['full_name']??'');$email=strtolower(trim($_POST['email']??''));$phone=trim($_POST['phone']??'');
 if($name===''||!filter_var($email,FILTER_VALIDATE_EMAIL)||!preg_match('/^[0-9+]{10,15}$/',$phone))$err='Enter a valid name, email address and phone number.';
 else try{$st=$db->prepare('UPDATE users SET full_name=?,email=?,phone=? WHERE id=?');$st->execute([$name,$email,$phone,$profile['id']]);$_SESSION['user']['name']=$name;$profile=logged_in_user_record();$ok='Profile updated successfully.';}catch(PDOException $e){$err=$e->getCode()==23000?'That email address or phone number is already in use.':'Unable to update profile.';}
}
include 'includes/header.php'; ?>
<main class="page"><div class="auth" style="max-width:620px"><form class="card panel" method="post"><h1>My Profile</h1><p class="subtitle">These details are loaded directly from your EasyTrip database account.</p><?php if($err):?><div class="err"><?=e($err)?></div><?php endif;?><?php if($ok):?><div class="status"><?=e($ok)?></div><?php endif;?><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><div class="field"><label>Full Name</label><input name="full_name" value="<?=e($profile['full_name'])?>" required></div><div class="field"><label>Email Address</label><input type="email" name="email" value="<?=e($profile['email'])?>" required></div><div class="field"><label>Phone Number</label><input name="phone" value="<?=e($profile['phone'])?>" required></div><button class="btn primary full">Save Changes</button></form></div></main><?php include 'includes/footer.php'; ?>
