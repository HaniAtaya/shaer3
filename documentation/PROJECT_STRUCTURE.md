# هيكل المشروع المنظم - نظام جمع بيانات العائلات والأيتام

## 📁 البنية الجديدة للمشروع

تم تنظيم المشروع بشكل منطقي ومرتب لسهولة الصيانة والتطوير:

### 🏠 الملفات الأساسية (الجذر)
```
family-orphans-system/
├── index.php                    # الصفحة الرئيسية
├── admin.php                    # لوحة التحكم العامة
├── admin-dashboard.php          # لوحة تحكم الإدارة
├── admin-*.php                  # جميع ملفات إدارة النظام
├── family-registration.php      # تسجيل بيانات الأسرة
├── family-login.php            # تسجيل دخول الأسرة
├── family-update.php           # تحديث بيانات الأسرة
├── orphan-registration.php     # تسجيل بيانات الأيتام
├── death-registration.php      # تسجيل بيانات الوفيات
├── infant-registration.php     # تسجيل بيانات الرضع
├── lists.php                   # صفحة القوائم الرئيسية
└── includes/                   # ملفات المساعدة المشتركة
    ├── db_connection.php       # اتصال قاعدة البيانات
    ├── admin-sidebar.php       # شريط جانبي الإدارة
    ├── generate-access-code.php # توليد كود الوصول
    └── device-tracker.php      # تتبع الأجهزة
```

### 📊 مجلد التصدير (exports/)
```
exports/
├── export-families.php         # تصدير بيانات العائلات
├── export_deaths_excel.php     # تصدير الوفيات إلى Excel
├── export_deaths_pdf.php       # تصدير الوفيات إلى PDF
├── export_infants_excel.php    # تصدير الرضع إلى Excel
├── export_infants_pdf.php      # تصدير الرضع إلى PDF
└── export-all-reports.php      # تصدير جميع التقارير
```

### 📋 مجلد القوائم (lists/)
```
lists/
├── families-list.php          # قائمة العائلات
├── orphans-list.php           # قائمة الأيتام
├── infants-list.php           # قائمة الرضع
└── deaths-list.php            # قائمة الوفيات
```

### 🔧 مجلد المساعدة (utilities/)
```
utilities/
├── get-family-details.php     # جلب تفاصيل العائلة
├── get-orphan-details.php     # جلب تفاصيل اليتيم
├── get-admin-details.php      # جلب تفاصيل المشرف
├── change-password.php        # تغيير كلمة المرور
├── update-security-question.php # تحديث سؤال الأمان
└── bulk-delete-families.php   # حذف جماعي للعائلات
```

### 🎨 مجلد الأصول (assets/)
```
assets/
├── css/
│   └── admin-sidebar.css      # تنسيقات الشريط الجانبي
└── js/
    └── admin-sidebar.js       # سكريبت الشريط الجانبي
```

### 📁 مجلد المرفقات (uploads/)
```
uploads/
├── birth_certificates/        # شهادات الميلاد
├── death_certificates/         # شهادات الوفاة
├── death_photos/              # صور الوفيات
└── orphan_images/             # صور الأيتام
```

### 🔄 مجلد Laravel الاحتياطي (laravel-backup/)
```
laravel-backup/
├── app/                       # تطبيق Laravel
├── config/                    # إعدادات Laravel
├── database/                  # قاعدة البيانات Laravel
├── resources/                 # الموارد Laravel
├── routes/                    # المسارات Laravel
├── storage/                   # التخزين Laravel
├── tests/                     # الاختبارات Laravel
├── vendor/                    # المكتبات الخارجية
├── composer.json              # ملف Composer
├── package.json               # ملف NPM
└── artisan                    # أداة Laravel
```

## 🔗 المسارات المحدثة

تم تحديث جميع المسارات في الملفات لتتوافق مع البنية الجديدة:

### روابط القوائم
- `families-list.php` → `lists/families-list.php`
- `orphans-list.php` → `lists/orphans-list.php`
- `infants-list.php` → `lists/infants-list.php`
- `deaths-list.php` → `lists/deaths-list.php`

### روابط التصدير
- `export-*.php` → `exports/export-*.php`
- `export_deaths_excel.php` → `exports/export_deaths_excel.php`
- `export_infants_excel.php` → `exports/export_infants_excel.php`

### روابط المساعدة
- `get-*.php` → `utilities/get-*.php`
- `change-password.php` → `utilities/change-password.php`
- `update-security-question.php` → `utilities/update-security-question.php`

## ✅ المميزات

1. **تنظيم منطقي**: كل نوع من الملفات في مجلد منفصل
2. **سهولة الصيانة**: سهولة العثور على الملفات المطلوبة
3. **فصل الاهتمامات**: ملفات Laravel منفصلة عن ملفات PHP الأساسية
4. **مسارات محدثة**: جميع الروابط تعمل بشكل صحيح
5. **حفظ الملفات**: لا تم حذف أي ملف، فقط تم تنظيمها

## 🚀 كيفية الاستخدام

1. جميع الملفات الأساسية متاحة مباشرة من الجذر
2. ملفات القوائم متاحة من مجلد `lists/`
3. ملفات التصدير متاحة من مجلد `exports/`
4. ملفات المساعدة متاحة من مجلد `utilities/`
5. ملفات Laravel محفوظة في مجلد `laravel-backup/` للرجوع إليها عند الحاجة

## 📝 ملاحظات مهمة

- تم الحفاظ على جميع الوظائف الأساسية
- جميع المسارات تم تحديثها تلقائياً
- ملفات Laravel محفوظة في مجلد منفصل ولا تؤثر على عمل النظام
- يمكن استرجاع ملفات Laravel من مجلد `laravel-backup/` عند الحاجة
