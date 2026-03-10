<!DOCTYPE html>
<html>
<head>

<title>Login</title>

<style>

body{
font-family:Arial;
background:#f3f4f6;
display:flex;
align-items:center;
justify-content:center;
height:100vh;
}

.box{
width:340px;
background:white;
padding:35px;
border-radius:8px;
box-shadow:0 10px 30px rgba(0,0,0,0.1);
}

input{
width:100%;
padding:12px;
margin-bottom:15px;
border:1px solid #ddd;
border-radius:6px;
}

button{
width:100%;
padding:12px;
background:#2563eb;
border:none;
color:white;
border-radius:6px;
cursor:pointer;
}

.error{
color:red;
margin-bottom:10px;
}

</style>

</head>

<body>

<div class="box">

<h2>Login</h2>

<?php if(session()->getFlashdata('error')): ?>

<div class="error">
<?= session()->getFlashdata('error') ?>
</div>

<?php endif; ?>

<form method="post" action="/login">

<?= csrf_field() ?>

<input type="email" name="email" placeholder="Email" required>

<input type="password" name="password" placeholder="Password" required>

<button type="submit">Login</button>

</form>

</div>

</body>

</html>