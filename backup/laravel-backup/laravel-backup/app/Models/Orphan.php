<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Orphan extends Model
{
    use HasFactory;

    protected $fillable = [
        'guardian_full_name',
        'guardian_id_number',
        'guardian_gender',
        'guardian_birth_date',
        'guardian_relationship',
        'guardian_primary_phone',
        'guardian_secondary_phone',
        'deceased_father_name',
        'deceased_father_id_number',
        'martyrdom_date',
        'death_certificate_image',
        'orphan_full_name',
        'orphan_id_number',
        'orphan_gender',
        'orphan_birth_date',
        'orphan_health_status',
        'orphan_health_details',
        'orphan_image',
        'is_war_martyr',
        'displacement_governorate',
        'displacement_area',
        'displacement_neighborhood',
        'housing_status',
        'bank_name',
        'bank_phone',
        'account_number'
    ];

    protected $casts = [
        'guardian_birth_date' => 'date',
        'martyrdom_date' => 'date',
        'orphan_birth_date' => 'date',
        'is_war_martyr' => 'boolean',
    ];

    // دالة للحصول على اسم صلة المسؤول بالعربية
    public function getGuardianRelationshipNameAttribute()
    {
        $relationships = [
            'son' => 'ابن',
            'daughter' => 'ابنة',
            'brother' => 'أخ',
            'sister' => 'أخت',
            'grandfather' => 'جد',
            'grandmother' => 'جدة',
            'mother' => 'أم',
            'father' => 'أب'
        ];
        return $relationships[$this->guardian_relationship] ?? $this->guardian_relationship;
    }

    // دالة للحصول على اسم الحالة الصحية بالعربية
    public function getOrphanHealthStatusNameAttribute()
    {
        $statuses = [
            'healthy' => 'سليم',
            'hypertension' => 'ضغط',
            'diabetes' => 'سكري',
            'other' => 'أخرى'
        ];
        return $statuses[$this->orphan_health_status] ?? $this->orphan_health_status;
    }

    // دالة للحصول على اسم المحافظة بالعربية
    public function getDisplacementGovernorateNameAttribute()
    {
        $governorates = [
            'gaza' => 'غزة',
            'khan_younis' => 'خانيونس',
            'rafah' => 'رفح',
            'middle' => 'الوسطى',
            'north_gaza' => 'شمال غزة'
        ];
        return $governorates[$this->displacement_governorate] ?? $this->displacement_governorate;
    }

    // دالة للحصول على حالة السكن بالعربية
    public function getHousingStatusNameAttribute()
    {
        $statuses = [
            'tent' => 'خيمة',
            'apartment' => 'شقة',
            'house' => 'بيت',
            'school' => 'مدرسة'
        ];
        return $statuses[$this->housing_status] ?? $this->housing_status;
    }
}
