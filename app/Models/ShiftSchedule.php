<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class ShiftSchedule extends Model { protected $fillable=['employee_id','shift_id','work_date','status','created_by']; protected function casts(): array { return ['work_date'=>'date']; } public function employee(): BelongsTo { return $this->belongsTo(Employee::class); } public function shift(): BelongsTo { return $this->belongsTo(Shift::class); } }
