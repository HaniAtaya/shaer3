<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FamilyMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'family_id',
        'full_name',
        'id_number',
        'gender',
        'birth_date',
        'relationship',
        'health_status',
        'health_details'
    ];

    protected $casts = [
        'birth_date' => 'date',
    ];

    // العلاقة مع الأسرة
    public function family()
    {
        return $this->belongsTo(Family::class);
    }

    // دالة للحصول على اسم الصلة بالعربية
    public function getRelationshipNameAttribute()
    {
        $relationships = [
            'son' => 'ابن',
            'daughter' => 'ابنة',
            'father' => 'أب',
            'mother' => 'أم',
            'brother' => 'أخ',
            'sister' => 'أخت',
            'grandfather' => 'جد',
            'grandmother' => 'جدة'
        ];
        return $relationships[$this->relationship] ?? $this->relationship;
    }

    // دالة للحصول على اسم الحالة الصحية بالعربية
    public function getHealthStatusNameAttribute()
    {
        $statuses = [
            'healthy' => 'سليم',
            'hypertension' => 'ضغط',
            'diabetes' => 'سكري',
            'other' => 'أخرى'
        ];
        return $statuses[$this->health_status] ?? $this->health_status;
    }
}
