<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Employee extends Model { protected $fillable=['nip','name','department_id','user_id','join_date','employment_status','attendance_pin_hash','is_active']; protected $hidden=['attendance_pin_hash']; protected function casts(): array { return ['join_date'=>'date','is_active'=>'boolean']; } public function department(): BelongsTo { return $this->belongsTo(Department::class); } public function schedules(): HasMany { return $this->hasMany(ShiftSchedule::class); } }
