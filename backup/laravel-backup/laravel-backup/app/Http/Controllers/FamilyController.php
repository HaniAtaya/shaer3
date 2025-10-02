<?php

namespace App\Http\Controllers;

use App\Models\Family;
use App\Models\FamilyMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FamilyController extends Controller
{
    /**
     * عرض قائمة العائلات
     */
    public function index()
    {
        $families = Family::with('familyMembers')->paginate(10);
        return view('admin.families.index', compact('families'));
    }

    /**
     * عرض تفاصيل عائلة معينة
     */
    public function show($id)
    {
        $family = Family::with('familyMembers')->findOrFail($id);
        return view('admin.families.show', compact('family'));
    }

    /**
     * حفظ بيانات الأسرة الجديدة
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'father_name' => 'required|string|max:255',
            'grandfather_name' => 'required|string|max:255',
            'family_name' => 'required|string|max:255',
            'id_number' => 'required|string|max:20|unique:families',
            'gender' => 'required|in:male,female',
            'birth_date' => 'required|date',
            'id_issue_date' => 'required|date',
            'family_branch' => 'required|string|max:255',
            'primary_phone' => 'required|string|max:20',
            'secondary_phone' => 'nullable|string|max:20',
            'health_status' => 'required|in:healthy,hypertension,diabetes,other',
            'health_details' => 'nullable|string',
            'marital_status' => 'required|in:married,divorced,widowed,elderly,provider,special_needs',
            'original_governorate' => 'required|in:gaza,khan_younis,rafah,middle,north_gaza',
            'original_area' => 'required|string|max:255',
            'original_neighborhood' => 'required|string|max:255',
            'displacement_governorate' => 'required|in:gaza,khan_younis,rafah,middle,north_gaza',
            'displacement_area' => 'required|string|max:255',
            'displacement_neighborhood' => 'required|string|max:255',
            'housing_status' => 'required|in:tent,apartment,house,school',
            'family_members_count' => 'required|integer|min:0',
            'family_members' => 'required|array',
            'family_members.*.full_name' => 'required|string|max:255',
            'family_members.*.id_number' => 'required|string|max:20',
            'family_members.*.gender' => 'required|in:male,female',
            'family_members.*.birth_date' => 'required|date',
            'family_members.*.relationship' => 'required|in:son,daughter,father,mother,brother,sister,grandfather,grandmother',
            'family_members.*.health_status' => 'required|in:healthy,hypertension,diabetes,other',
            'family_members.*.health_details' => 'nullable|string',
        ]);

        // إضافة بيانات الزوج/الزوجة إذا كان متزوج
        if ($request->marital_status === 'married') {
            $validated = array_merge($validated, $request->validate([
                'spouse_first_name' => 'required|string|max:255',
                'spouse_father_name' => 'required|string|max:255',
                'spouse_grandfather_name' => 'required|string|max:255',
                'spouse_family_name' => 'required|string|max:255',
                'spouse_id_number' => 'required|string|max:20',
                'spouse_gender' => 'required|in:male,female',
                'spouse_birth_date' => 'required|date',
                'spouse_id_issue_date' => 'required|date',
                'spouse_family_branch' => 'required|string|max:255',
                'spouse_primary_phone' => 'required|string|max:20',
                'spouse_secondary_phone' => 'nullable|string|max:20',
                'spouse_health_status' => 'required|in:healthy,hypertension,diabetes,other',
                'spouse_health_details' => 'nullable|string',
            ]));
        }

        // إنشاء الأسرة
        $family = Family::create($validated);

        // إضافة أفراد الأسرة
        foreach ($request->family_members as $memberData) {
            $family->familyMembers()->create($memberData);
        }

        return redirect()->route('home')->with('success', 'تم تسجيل بيانات الأسرة بنجاح');
    }

    /**
     * تحديث بيانات عائلة
     */
    public function update(Request $request, $id)
    {
        $family = Family::findOrFail($id);
        
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'father_name' => 'required|string|max:255',
            'grandfather_name' => 'required|string|max:255',
            'family_name' => 'required|string|max:255',
            'id_number' => 'required|string|max:20|unique:families,id_number,' . $id,
            'gender' => 'required|in:male,female',
            'birth_date' => 'required|date',
            'id_issue_date' => 'required|date',
            'family_branch' => 'required|string|max:255',
            'primary_phone' => 'required|string|max:20',
            'secondary_phone' => 'nullable|string|max:20',
            'health_status' => 'required|in:healthy,hypertension,diabetes,other',
            'health_details' => 'nullable|string',
            'marital_status' => 'required|in:married,divorced,widowed,elderly,provider,special_needs',
            'original_governorate' => 'required|in:gaza,khan_younis,rafah,middle,north_gaza',
            'original_area' => 'required|string|max:255',
            'original_neighborhood' => 'required|string|max:255',
            'displacement_governorate' => 'required|in:gaza,khan_younis,rafah,middle,north_gaza',
            'displacement_area' => 'required|string|max:255',
            'displacement_neighborhood' => 'required|string|max:255',
            'housing_status' => 'required|in:tent,apartment,house,school',
            'family_members_count' => 'required|integer|min:0',
        ]);

        $family->update($validated);

        return redirect()->route('admin.families.index')->with('success', 'تم تحديث بيانات الأسرة بنجاح');
    }

    /**
     * حذف عائلة
     */
    public function destroy($id)
    {
        $family = Family::findOrFail($id);
        $family->delete();

        return redirect()->route('admin.families.index')->with('success', 'تم حذف الأسرة بنجاح');
    }
}
