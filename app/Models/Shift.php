<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Shift extends Model { protected $fillable=['code','name','start_time','end_time','crosses_midnight','is_active']; protected function casts(): array { return ['crosses_midnight'=>'boolean','is_active'=>'boolean']; } public function schedules(): HasMany { return $this->hasMany(ShiftSchedule::class); } }
