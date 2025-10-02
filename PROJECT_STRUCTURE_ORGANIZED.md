# هيكل المشروع المنظم - نظام إدارة الأيتام والعائلات

## 📁 البنية الجديدة المنظمة

```
family-orphans-system/
├── 📁 admin/                          # ملفات الإدارة
│   ├── 📁 auth/                       # ملفات تسجيل دخول الإدارة
│   │   ├── admin-login.php
│   │   └── admin-logout.php
│   ├── 📁 management/                # إدارة البيانات
│   │   ├── admin-admins.php
│   │   ├── admin-deaths.php
│   │   ├── admin-families.php
│   │   ├── admin-family-accounts.php
│   │   ├── admin-family-members.php
│   │   ├── admin-infants.php
│   │   └── admin-orphans.php
│   ├── 📁 pages/                      # صفحات الإدارة الرئيسية
│   │   ├── admin-dashboard.php
│   │   └── admin.php
│   ├── 📁 reports/                    # التقارير والسجلات
│   │   ├── admin-reports.php
│   │   └── admin-update-logs.php
│   └── 📁 settings/                   # الإعدادات
│       └── admin-settings.php
│
├── 📁 public/                         # الملفات العامة
│   ├── 📁 exports/                    # ملفات التصدير
│   │   ├── export_deaths_excel.php
│   │   ├── export_deaths_pdf.php
│   │   ├── export_infants_excel.php
│   │   ├── export_infants_pdf.php
│   │   ├── export-all-reports.php
│   │   └── export-families.php
│   ├── 📁 family/                     # ملفات العائلات
│   │   ├── family-login.php
│   │   ├── family-logout.php
│   │   └── family-update.php
│   ├── 📁 lists/                      # قوائم البيانات
│   │   ├── deaths-list.php
│   │   ├── families-list.php
│   │   ├── infants-list.php
│   │   ├── lists.php
│   │   └── orphans-list.php
│   └── 📁 registration/               # صفحات التسجيل
│       ├── death-registration.php
│       ├── family-registration.php
│       ├── infant-registration.php
│       └── orphan-registration.php
│
├── 📁 assets/                         # الملفات الثابتة
│   ├── 📁 css/
│   │   └── admin-sidebar.css
│   └── 📁 js/
│       └── admin-sidebar.js
│
├── 📁 includes/                       # الملفات المشتركة
│   ├── admin-sidebar.php
│   ├── check-permissions.php
│   ├── db_connection.php
│   ├── device-tracker.php
│   ├── generate-access-code.php
│   └── sidebar.php
│
├── 📁 database-scripts/               # سكريبتات قاعدة البيانات
│   ├── check_orphans_table.php
│   └── create_infants_table.php
│
├── 📁 documentation/                  # الوثائق
│   ├── BUG_FIXES.md
│   ├── EXPORT_FEATURES.md
│   ├── FAMILY_UPDATE_SYSTEM_README.md
│   ├── PROJECT_STRUCTURE.md
│   ├── QUICK_START_FAMILY_UPDATE.md
│   ├── README.md
│   └── SELECTION_FEATURES.md
│
├── 📁 uploads/                        # الملفات المرفوعة
│   ├── 📁 birth_certificates/
│   ├── 📁 death_certificates/
│   ├── 📁 death_photos/
│   └── 📁 orphan_images/
│
├── 📁 utilities/                       # الأدوات المساعدة
│   ├── bulk-delete-families.php
│   ├── change-password.php
│   ├── get-admin-details.php
│   ├── get-family-details.php
│   ├── get-orphan-details.php
│   └── update-security-question.php
│
├── 📁 backup/                         # النسخ الاحتياطية
│   ├── 📁 laravel-backup/             # نسخة احتياطية من Laravel
│   └── 📁 unused-files/               # الملفات غير المستخدمة
│       ├── admin-pages/
│       └── registration-pages/
│
└── index.php                          # الصفحة الرئيسية
```

## 🎯 فوائد التنظيم الجديد

### 1. **فصل واضح للوظائف**
- ملفات الإدارة منفصلة في مجلد `admin/`
- الملفات العامة في مجلد `public/`
- الملفات المشتركة في مجلد `includes/`

### 2. **تنظيم منطقي للمجلدات**
- `admin/auth/` - تسجيل دخول الإدارة
- `admin/management/` - إدارة البيانات
- `admin/reports/` - التقارير والسجلات
- `admin/settings/` - الإعدادات

### 3. **تنظيم الملفات العامة**
- `public/family/` - ملفات العائلات
- `public/registration/` - صفحات التسجيل
- `public/lists/` - قوائم البيانات
- `public/exports/` - ملفات التصدير

### 4. **حفظ النسخ الاحتياطية**
- مجلد `backup/` يحتوي على جميع النسخ الاحتياطية
- مجلد `unused-files/` للملفات غير المستخدمة

## 📋 ملاحظات مهمة

1. **لم يتم حذف أي ملف** - جميع الملفات محفوظة في أماكنها المناسبة
2. **النسخ الاحتياطية محفوظة** - مجلد Laravel كامل في `backup/laravel-backup/`
3. **المجلدات الفارغة** - تم نقلها إلى `backup/unused-files/`
4. **البنية واضحة** - كل مجلد له وظيفة محددة

## 🔧 كيفية الوصول للملفات

### للإدارة:
- تسجيل الدخول: `admin/auth/admin-login.php`
- لوحة التحكم: `admin/pages/admin-dashboard.php`
- إدارة العائلات: `admin/management/admin-families.php`

### للعائلات:
- تسجيل الدخول: `public/family/family-login.php`
- تحديث البيانات: `public/family/family-update.php`

### للتسجيل:
- تسجيل العائلة: `public/registration/family-registration.php`
- تسجيل اليتيم: `public/registration/orphan-registration.php`

## ✅ تم الانتهاء من التنظيم

جميع الملفات الآن في أماكنها الصحيحة والمنطقية، والمشروع منظم بشكل احترافي وسهل الصيانة.
