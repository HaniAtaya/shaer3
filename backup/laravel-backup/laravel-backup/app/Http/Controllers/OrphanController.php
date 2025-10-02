<?php

namespace App\Http\Controllers;

use App\Models\Orphan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class OrphanController extends Controller
{
    /**
     * عرض قائمة الأيتام
     */
    public function index()
    {
        $orphans = Orphan::paginate(10);
        return view('admin.orphans.index', compact('orphans'));
    }

    /**
     * عرض تفاصيل يتيم معين
     */
    public function show($id)
    {
        $orphan = Orphan::findOrFail($id);
        return view('admin.orphans.show', compact('orphan'));
    }

    /**
     * حفظ بيانات اليتيم الجديد
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'guardian_full_name' => 'required|string|max:255',
            'guardian_id_number' => 'required|string|max:20',
            'guardian_gender' => 'required|in:male,female',
            'guardian_birth_date' => 'required|date',
            'guardian_relationship' => 'required|in:son,daughter,brother,sister,grandfather,grandmother,mother,father',
            'guardian_primary_phone' => 'required|string|max:20',
            'guardian_secondary_phone' => 'nullable|string|max:20',
            'deceased_father_name' => 'required|string|max:255',
            'deceased_father_id_number' => 'required|string|max:20',
            'martyrdom_date' => 'required|date',
            'death_certificate_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'orphan_full_name' => 'required|string|max:255',
            'orphan_id_number' => 'required|string|max:20|unique:orphans',
            'orphan_gender' => 'required|in:male,female',
            'orphan_birth_date' => 'required|date',
            'orphan_health_status' => 'required|in:healthy,hypertension,diabetes,other',
            'orphan_health_details' => 'nullable|string',
            'orphan_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'is_war_martyr' => 'boolean',
            'displacement_governorate' => 'required|in:gaza,khan_younis,rafah,middle,north_gaza',
            'displacement_area' => 'required|string|max:255',
            'displacement_neighborhood' => 'required|string|max:255',
            'housing_status' => 'required|in:tent,apartment,house,school',
            'bank_name' => 'nullable|string|max:255',
            'bank_phone' => 'nullable|string|max:20',
            'account_number' => 'nullable|string|max:255',
        ]);

        // رفع صورة شهادة الوفاة
        if ($request->hasFile('death_certificate_image')) {
            $validated['death_certificate_image'] = $request->file('death_certificate_image')->store('death_certificates', 'public');
        }

        // رفع صورة الطفل
        if ($request->hasFile('orphan_image')) {
            $validated['orphan_image'] = $request->file('orphan_image')->store('orphan_images', 'public');
        }

        Orphan::create($validated);

        return redirect()->route('home')->with('success', 'تم تسجيل بيانات اليتيم بنجاح');
    }

    /**
     * تحديث بيانات يتيم
     */
    public function update(Request $request, $id)
    {
        $orphan = Orphan::findOrFail($id);
        
        $validated = $request->validate([
            'guardian_full_name' => 'required|string|max:255',
            'guardian_id_number' => 'required|string|max:20',
            'guardian_gender' => 'required|in:male,female',
            'guardian_birth_date' => 'required|date',
            'guardian_relationship' => 'required|in:son,daughter,brother,sister,grandfather,grandmother,mother,father',
            'guardian_primary_phone' => 'required|string|max:20',
            'guardian_secondary_phone' => 'nullable|string|max:20',
            'deceased_father_name' => 'required|string|max:255',
            'deceased_father_id_number' => 'required|string|max:20',
            'martyrdom_date' => 'required|date',
            'death_certificate_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'orphan_full_name' => 'required|string|max:255',
            'orphan_id_number' => 'required|string|max:20|unique:orphans,orphan_id_number,' . $id,
            'orphan_gender' => 'required|in:male,female',
            'orphan_birth_date' => 'required|date',
            'orphan_health_status' => 'required|in:healthy,hypertension,diabetes,other',
            'orphan_health_details' => 'nullable|string',
            'orphan_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'is_war_martyr' => 'boolean',
            'displacement_governorate' => 'required|in:gaza,khan_younis,rafah,middle,north_gaza',
            'displacement_area' => 'required|string|max:255',
            'displacement_neighborhood' => 'required|string|max:255',
            'housing_status' => 'required|in:tent,apartment,house,school',
            'bank_name' => 'nullable|string|max:255',
            'bank_phone' => 'nullable|string|max:20',
            'account_number' => 'nullable|string|max:255',
        ]);

        // رفع صورة شهادة الوفاة
        if ($request->hasFile('death_certificate_image')) {
            if ($orphan->death_certificate_image) {
                Storage::disk('public')->delete($orphan->death_certificate_image);
            }
            $validated['death_certificate_image'] = $request->file('death_certificate_image')->store('death_certificates', 'public');
        }

        // رفع صورة الطفل
        if ($request->hasFile('orphan_image')) {
            if ($orphan->orphan_image) {
                Storage::disk('public')->delete($orphan->orphan_image);
            }
            $validated['orphan_image'] = $request->file('orphan_image')->store('orphan_images', 'public');
        }

        $orphan->update($validated);

        return redirect()->route('admin.orphans.index')->with('success', 'تم تحديث بيانات اليتيم بنجاح');
    }

    /**
     * حذف يتيم
     */
    public function destroy($id)
    {
        $orphan = Orphan::findOrFail($id);
        
        // حذف الصور
        if ($orphan->death_certificate_image) {
            Storage::disk('public')->delete($orphan->death_certificate_image);
        }
        if ($orphan->orphan_image) {
            Storage::disk('public')->delete($orphan->orphan_image);
        }
        
        $orphan->delete();

        return redirect()->route('admin.orphans.index')->with('success', 'تم حذف اليتيم بنجاح');
    }
}
