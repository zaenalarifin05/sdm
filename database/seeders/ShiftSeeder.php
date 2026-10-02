<?php
namespace Database\Seeders;
use App\Models\Shift; use Illuminate\Database\Seeder;
class ShiftSeeder extends Seeder { public function run(): void { foreach ([['SHIFT-1','Shift 1','07:00','15:00',false],['SHIFT-2','Shift 2','15:00','23:00',false],['SHIFT-3','Shift 3','23:00','07:00',true]] as [$code,$name,$start,$end,$cross]) { Shift::updateOrCreate(['code'=>$code],['name'=>$name,'start_time'=>$start,'end_time'=>$end,'crosses_midnight'=>$cross,'is_active'=>true]); } } }
