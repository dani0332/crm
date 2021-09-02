@extends('layouts.app')
@section('title','Home')
@section('content')
<script>
<?php
  $user = ["email" => Auth::user()->email, "name" => Auth::user()->name, "id" => Auth::user()->id, "role" =>  strtolower(Auth::user()->usersroles[0]->name)];
  $user = json_encode($user);
?>
localStorage.setItem("session", JSON.stringify(<?php echo $user;?>));
alert(localStorage.getItem("session"));
</script>
<p style="text-align: center;">Welcome to My Alfred CRM</p>
@endsection
