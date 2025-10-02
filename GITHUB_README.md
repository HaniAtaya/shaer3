# 🏠 الشاعر عائلتي - نظام إدارة العائلات والأيتام

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-blue.svg)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-orange.svg)](https://mysql.com)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.0-purple.svg)](https://getbootstrap.com)

## 📋 نظرة عامة

نظام متكامل وشامل لجمع وإدارة بيانات العائلات والأيتام والشهداء والرضع في فلسطين. يوفر النظام حلولاً متقدمة لتوثيق المعلومات وتقديم المساعدة المطلوبة للعائلات المتضررة.

## ✨ المميزات الرئيسية

### 🏘️ إدارة العائلات
- ✅ تسجيل شامل لبيانات الأسرة
- ✅ إدارة أفراد العائلة
- ✅ تحديث البيانات بسهولة
- ✅ نظام أمان متقدم

### 👶 إدارة الأيتام
- ✅ تسجيل بيانات الأيتام
- ✅ معلومات المسؤول والأب المتوفي
- ✅ رفع الصور والوثائق
- ✅ تتبع حالة اليتيم

### 📊 التقارير والإحصائيات
- ✅ لوحة تحكم تفاعلية
- ✅ إحصائيات شاملة
- ✅ تقارير مفصلة
- ✅ تصدير البيانات (Excel, PDF)

### 🔐 نظام الأمان
- ✅ تسجيل دخول آمن
- ✅ صلاحيات متدرجة
- ✅ تتبع العمليات
- ✅ حماية البيانات

## 🚀 البدء السريع

### المتطلبات
- PHP 7.4 أو أحدث
- MySQL 5.7 أو أحدث
- خادم ويب (Apache/Nginx)
- XAMPP/WAMP (للتنمية المحلية)

### التثبيت

1. **استنساخ المشروع**
```bash
git clone https://github.com/yourusername/family-orphans-system.git
cd family-orphans-system
```

2. **إعداد قاعدة البيانات**
- إنشاء قاعدة بيانات جديدة: `family_orphans_system`
- استيراد ملفات SQL من مجلد `database-scripts/`

3. **تكوين الاتصال**
- تحديث إعدادات قاعدة البيانات في الملفات
- ضبط المسارات حسب البيئة

4. **تشغيل المشروع**
- رفع الملفات على الخادم
- الوصول إلى `index.php`

## 📁 هيكل المشروع

```
family-orphans-system/
├── 📁 admin/                    # ملفات الإدارة
│   ├── 📁 auth/                 # تسجيل دخول الإدارة
│   ├── 📁 management/           # إدارة البيانات
│   ├── 📁 pages/                # صفحات الإدارة
│   ├── 📁 reports/              # التقارير
│   └── 📁 settings/             # الإعدادات
├── 📁 public/                   # الملفات العامة
│   ├── 📁 family/               # ملفات العائلات
│   ├── 📁 registration/         # صفحات التسجيل
│   ├── 📁 lists/                # قوائم البيانات
│   └── 📁 exports/              # ملفات التصدير
├── 📁 assets/                   # الملفات الثابتة
├── 📁 includes/                 # الملفات المشتركة
├── 📁 utilities/                # الأدوات المساعدة
├── 📁 uploads/                  # الملفات المرفوعة
└── 📁 documentation/            # الوثائق
```

## 🔧 الاستخدام

### للإدارة
- **تسجيل الدخول**: `admin/auth/admin-login.php`
- **لوحة التحكم**: `admin/pages/admin-dashboard.php`
- **إدارة العائلات**: `admin/management/admin-families.php`

### للعائلات
- **تسجيل الدخول**: `public/family/family-login.php`
- **تحديث البيانات**: `public/family/family-update.php`

### للتسجيل
- **تسجيل العائلة**: `public/registration/family-registration.php`
- **تسجيل اليتيم**: `public/registration/orphan-registration.php`

## 📚 الوثائق

- [دليل البدء السريع](documentation/QUICK_START_FAMILY_UPDATE.md)
- [هيكل المشروع](documentation/PROJECT_STRUCTURE.md)
- [مميزات التصدير](documentation/EXPORT_FEATURES.md)
- [إصلاح الأخطاء](documentation/BUG_FIXES.md)
- [دليل النشر](DEPLOYMENT.md)

## 🛠️ التطوير

### المساهمة
1. Fork المشروع
2. إنشاء فرع للميزة الجديدة
3. Commit التغييرات
4. Push للفرع
5. إنشاء Pull Request

### الإبلاغ عن الأخطاء
- استخدم Issues للإبلاغ عن الأخطاء
- قدم تفاصيل كاملة عن المشكلة
- أرفق لقطات الشاشة إذا لزم الأمر

## 📄 الترخيص

هذا المشروع مرخص تحت رخصة MIT - راجع ملف [LICENSE](LICENSE) للتفاصيل.

## 👥 الفريق

- **المطور الرئيسي**: Hani Alshaer
- **البريد الإلكتروني**: haatayani@gmail.com
- **الهاتف**: 00970593804084

## 🙏 شكر وتقدير

شكر خاص لجميع المتطوعين والمساهمين في هذا المشروع الإنساني.

---

**ملاحظة**: هذا المشروع مخصص للمساعدة الإنسانية في فلسطين. يرجى استخدامه بمسؤولية وأخلاقية.

## 📞 الدعم

للحصول على الدعم أو المساعدة:
- 📧 البريد الإلكتروني: haatayani@gmail.com
- 📱 الهاتف: 00970593804084
- 🐛 Issues: استخدم قسم Issues في GitHub

---

⭐ إذا أعجبك المشروع، لا تنس إعطاؤه نجمة!

## 🎯 المشروع جاهز للرفع على GitHub!

### ✅ ما تم إنجازه:
- [x] تنظيم شامل للمشروع
- [x] تصحيح جميع المسارات
- [x] إنشاء ملفات GitHub المطلوبة
- [x] إضافة الوثائق الشاملة
- [x] إعداد ملفات الأمان
- [x] إنشاء قوالب Issues و Pull Requests

### 🚀 خطوات الرفع:
1. إنشاء repository جديد على GitHub
2. رفع جميع الملفات
3. إضافة وصف المشروع
4. تفعيل GitHub Pages (اختياري)
5. إضافة Contributors

**المشروع جاهز 100% للرفع على GitHub!** 🎉
