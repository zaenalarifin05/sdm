<?php
use Illuminate\Support\Facades\Route;
Route::get('/', fn () => response()->json(['application'=>'SDM Pabrik','status'=>'ok']));
