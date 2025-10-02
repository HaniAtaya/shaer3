<?php

namespace App\Http\Controllers;

use App\Models\Family;
use App\Models\Orphan;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    /**
     * عرض لوحة التحكم الرئيسية
     */
    public function dashboard()
    {
        $stats = [
            'total_families' => Family::count(),
            'total_orphans' => Orphan::count(),
            'war_martyrs' => Orphan::where('is_war_martyr', true)->count(),
            'governorates' => $this->getGovernorateStats(),
            'health_stats' => $this->getHealthStats(),
            'family_branches' => $this->getFamilyBranchStats(),
        ];

        return view('admin.dashboard', compact('stats'));
    }

    /**
     * إحصائيات المحافظات
     */
    private function getGovernorateStats()
    {
        $familyStats = Family::selectRaw('displacement_governorate, COUNT(*) as count')
            ->groupBy('displacement_governorate')
            ->pluck('count', 'displacement_governorate')
            ->toArray();

        $orphanStats = Orphan::selectRaw('displacement_governorate, COUNT(*) as count')
            ->groupBy('displacement_governorate')
            ->pluck('count', 'displacement_governorate')
            ->toArray();

        $governorates = ['gaza', 'khan_younis', 'rafah', 'middle', 'north_gaza'];
        $governorateNames = [
            'gaza' => 'غزة',
            'khan_younis' => 'خانيونس',
            'rafah' => 'رفح',
            'middle' => 'الوسطى',
            'north_gaza' => 'شمال غزة'
        ];

        $result = [];
        foreach ($governorates as $gov) {
            $result[] = [
                'name' => $governorateNames[$gov],
                'families' => $familyStats[$gov] ?? 0,
                'orphans' => $orphanStats[$gov] ?? 0,
            ];
        }

        return $result;
    }

    /**
     * إحصائيات الحالة الصحية
     */
    private function getHealthStats()
    {
        $familyHealth = Family::selectRaw('health_status, COUNT(*) as count')
            ->groupBy('health_status')
            ->pluck('count', 'health_status')
            ->toArray();

        $orphanHealth = Orphan::selectRaw('orphan_health_status, COUNT(*) as count')
            ->groupBy('orphan_health_status')
            ->pluck('count', 'orphan_health_status')
            ->toArray();

        $healthStatuses = ['healthy', 'hypertension', 'diabetes', 'other'];
        $healthNames = [
            'healthy' => 'سليم',
            'hypertension' => 'ضغط',
            'diabetes' => 'سكري',
            'other' => 'أخرى'
        ];

        $result = [];
        foreach ($healthStatuses as $status) {
            $result[] = [
                'name' => $healthNames[$status],
                'families' => $familyHealth[$status] ?? 0,
                'orphans' => $orphanHealth[$status] ?? 0,
            ];
        }

        return $result;
    }

    /**
     * إحصائيات الفروع العائلية
     */
    private function getFamilyBranchStats()
    {
        return Family::selectRaw('family_branch, COUNT(*) as count')
            ->groupBy('family_branch')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
    }

    /**
     * تصدير بيانات العائلات
     */
    public function exportFamilies(Request $request)
    {
        $families = Family::with('familyMembers')->get();
        
        // هنا يمكن إضافة منطق التصدير إلى Excel أو PDF
        // سأقوم بإنشاء ملف CSV بسيط
        
        $filename = 'families_' . date('Y-m-d_H-i-s') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($families) {
            $file = fopen('php://output', 'w');
            
            // إضافة BOM للعربية
            fwrite($file, "\xEF\xBB\xBF");
            
            // رؤوس الأعمدة
            fputcsv($file, [
                'الاسم الكامل',
                'رقم الهوية',
                'الجنس',
                'تاريخ الميلاد',
                'الفرع العائلي',
                'رقم الهاتف',
                'المحافظة الأصلية',
                'منطقة النزوح',
                'عدد أفراد الأسرة'
            ]);

            foreach ($families as $family) {
                fputcsv($file, [
                    $family->full_name,
                    $family->id_number,
                    $family->gender === 'male' ? 'ذكر' : 'أنثى',
                    $family->birth_date->format('Y-m-d'),
                    $family->family_branch,
                    $family->primary_phone,
                    $family->original_governorate_name,
                    $family->displacement_area,
                    $family->family_members_count
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * تصدير بيانات الأيتام
     */
    public function exportOrphans(Request $request)
    {
        $orphans = Orphan::all();
        
        $filename = 'orphans_' . date('Y-m-d_H-i-s') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($orphans) {
            $file = fopen('php://output', 'w');
            
            // إضافة BOM للعربية
            fwrite($file, "\xEF\xBB\xBF");
            
            // رؤوس الأعمدة
            fputcsv($file, [
                'اسم اليتيم',
                'رقم هوية اليتيم',
                'الجنس',
                'تاريخ الميلاد',
                'اسم المسؤول',
                'صلة المسؤول',
                'اسم الأب المتوفي',
                'تاريخ الاستشهاد',
                'شهيد حرب',
                'المحافظة',
                'منطقة النزوح'
            ]);

            foreach ($orphans as $orphan) {
                fputcsv($file, [
                    $orphan->orphan_full_name,
                    $orphan->orphan_id_number,
                    $orphan->orphan_gender === 'male' ? 'ذكر' : 'أنثى',
                    $orphan->orphan_birth_date->format('Y-m-d'),
                    $orphan->guardian_full_name,
                    $orphan->guardian_relationship_name,
                    $orphan->deceased_father_name,
                    $orphan->martyrdom_date->format('Y-m-d'),
                    $orphan->is_war_martyr ? 'نعم' : 'لا',
                    $orphan->displacement_governorate_name,
                    $orphan->displacement_area
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
