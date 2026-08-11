<?php

namespace Database\Seeders;

use App\Enums\ContactMessageStatus;
use App\Models\ContactMessage;
use Illuminate\Database\Seeder;

class ContactMessageSeeder extends Seeder
{
    public function run(): void
    {
        if (ContactMessage::query()->exists()) {
            return;
        }

        $messages = [
            ['name' => 'سارة أحمد', 'email' => 'sara.ahmed@example.com', 'subject' => 'استفسار عن مقاس', 'message' => 'عايزة أعرف جدول المقاسات للفستان الأزرق.', 'status' => ContactMessageStatus::Replied, 'admin_reply' => 'أهلًا بيكِ، جدول المقاسات موجود في وصف المنتج تحت زرار "دليل المقاسات".'],
            ['name' => 'محمد علي', 'email' => 'mohamed.ali@example.com', 'subject' => 'تأخر في التوصيل', 'message' => 'طلبي متأخر عن الموعد المتوقع بيومين.', 'status' => ContactMessageStatus::Read, 'admin_reply' => null],
            ['name' => 'ندى إبراهيم', 'email' => 'nada.ibrahim@example.com', 'subject' => 'مشكلة في الدفع', 'message' => 'حاولت أدفع بالبطاقة وفشلت العملية أكتر من مرة.', 'status' => ContactMessageStatus::New, 'admin_reply' => null],
            ['name' => 'خالد حسن', 'email' => 'khaled.hassan@example.com', 'subject' => 'طلب تعاون', 'message' => 'عندي متجر أزياء صغير وحابب أعرف إمكانية التعاون معاكم.', 'status' => ContactMessageStatus::New, 'admin_reply' => null],
            ['name' => 'مريم سيد', 'email' => 'mariam.sayed@example.com', 'subject' => 'شكر وتقدير', 'message' => 'التوصيل كان سريع والمنتج جودته عالية، شكرًا ليكم.', 'status' => ContactMessageStatus::Replied, 'admin_reply' => 'شكرًا لتقييمك الجميل، بنتمنالك تجربة تسوق ممتعة دايمًا معانا.'],
        ];

        foreach ($messages as $data) {
            ContactMessage::create([...$data, 'phone' => '010'.random_int(10000000, 99999999), 'ip' => '10.0.0.'.random_int(1, 254)]);
        }
    }
}
