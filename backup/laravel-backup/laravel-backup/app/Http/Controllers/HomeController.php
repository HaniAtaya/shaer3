<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * عرض الصفحة الرئيسية
     */
    public function index()
    {
        return view('home');
    }

    /**
     * عرض صفحة التعليمات
     */
    public function instructions()
    {
        return view('instructions');
    }

    /**
     * عرض صفحة تسجيل بيانات الأسرة
     */
    public function familyRegistration()
    {
        return view('family-registration');
    }

    /**
     * عرض صفحة تسجيل بيانات الأيتام
     */
    public function orphanRegistration()
    {
        return view('orphan-registration');
    }
}
