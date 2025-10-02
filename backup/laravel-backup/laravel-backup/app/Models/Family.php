<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Family extends Model
{
    use HasFactory;

    protected $fillable = [
        'first_name',
        'father_name',
        'grandfather_name',
        'family_name',
        'id_number',
        'gender',
        'birth_date',
        'id_issue_date',
        'family_branch',
        'primary_phone',
        'secondary_phone',
        'health_status',
        'health_details',
        'marital_status',
        'spouse_first_name',
        'spouse_father_name',
        'spouse_grandfather_name',
        'spouse_family_name',
        'spouse_id_number',
        'spouse_gender',
        'spouse_birth_date',
        'spouse_id_issue_date',
        'spouse_family_branch',
        'spouse_primary_phone',
        'spouse_secondary_phone',
        'spouse_health_status',
        'spouse_health_details',
        'original_governorate',
        'original_area',
        'original_neighborhood',
        'displacement_governorate',
        'displacement_area',
        'displacement_neighborhood',
        'housing_status',
        'family_members_count'
    ];

    protected $casts = [
        'birth_date' => 'date',
        'id_issue_date' => 'date',
        'spouse_birth_date' => 'date',
        'spouse_id_issue_date' => 'date',
    ];

    // العلاقة مع أفراد الأسرة
    public function familyMembers()
    {
        return $this->hasMany(FamilyMember::class);
    }

    // دالة للحصول على الاسم الكامل
    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->father_name . ' ' . $this->grandfather_name . ' ' . $this->family_name;
    }

    // دالة للحصول على اسم الزوج/الزوجة الكامل
    public function getSpouseFullNameAttribute()
    {
        if ($this->spouse_first_name) {
            return $this->spouse_first_name . ' ' . $this->spouse_father_name . ' ' . $this->spouse_grandfather_name . ' ' . $this->spouse_family_name;
        }
        return null;
    }

    // دالة للحصول على اسم المحافظة بالعربية
    public function getOriginalGovernorateNameAttribute()
    {
        $governorates = [
            'gaza' => 'غزة',
            'khan_younis' => 'خانيونس',
            'rafah' => 'رفح',
            'middle' => 'الوسطى',
            'north_gaza' => 'شمال غزة'
        ];
        return $governorates[$this->original_governorate] ?? $this->original_governorate;
    }

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
}
