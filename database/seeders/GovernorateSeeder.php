<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Governorate;
use Illuminate\Database\Seeder;

class GovernorateSeeder extends Seeder
{
    /**
     * All 27 Egyptian governorates with their major cities/districts.
     * updateOrCreate throughout — running this seeder twice must not
     * create duplicates or fail (Batch 2.1's idempotency requirement).
     */
    public function run(): void
    {
        foreach ($this->data() as $sort => [$code, $nameAr, $nameEn, $cities]) {
            $governorate = Governorate::updateOrCreate(
                ['code' => $code],
                [
                    'name' => ['ar' => $nameAr, 'en' => $nameEn],
                    'is_active' => true,
                    'sort' => $sort,
                ]
            );

            foreach ($cities as $citySort => [$cityAr, $cityEn]) {
                City::updateOrCreate(
                    ['governorate_id' => $governorate->id, 'name->en' => $cityEn],
                    [
                        'name' => ['ar' => $cityAr, 'en' => $cityEn],
                        'is_active' => true,
                        'sort' => $citySort,
                    ]
                );
            }
        }
    }

    /**
     * @return array<int, array{0: string, 1: string, 2: string, 3: array<int, array{0: string, 1: string}>}>
     */
    private function data(): array
    {
        return [
            ['CAI', 'القاهرة', 'Cairo', [
                ['مدينة نصر', 'Nasr City'], ['مصر الجديدة', 'Heliopolis'], ['المعادي', 'Maadi'],
                ['الزمالك', 'Zamalek'], ['وسط البلد', 'Downtown Cairo'], ['القاهرة الجديدة', 'New Cairo'],
                ['شبرا', 'Shubra'], ['عين شمس', 'Ain Shams'], ['حلوان', 'Helwan'], ['المرج', 'El Marg'],
            ]],
            ['GIZ', 'الجيزة', 'Giza', [
                ['الدقي', 'Dokki'], ['المهندسين', 'Mohandessin'], ['الهرم', 'Haram'],
                ['السادس من أكتوبر', '6th of October'], ['الشيخ زايد', 'Sheikh Zayed'],
                ['فيصل', 'Faisal'], ['إمبابة', 'Imbaba'], ['بولاق الدكرور', 'Boulaq El Dakrour'],
            ]],
            ['ALX', 'الإسكندرية', 'Alexandria', [
                ['المنتزه', 'Montaza'], ['سيدي جابر', 'Sidi Gaber'], ['سموحة', 'Smouha'],
                ['ميامي', 'Miami'], ['العجمي', 'Agami'], ['برج العرب', 'Borg El Arab'],
                ['سيدي بشر', 'Sidi Bishr'], ['محطة الرمل', 'Raml Station'],
            ]],
            ['QLY', 'القليوبية', 'Qalyubia', [
                ['بنها', 'Banha'], ['شبرا الخيمة', 'Shubra El Kheima'], ['قليوب', 'Qalyub'],
                ['الخانكة', 'Khanka'], ['طوخ', 'Toukh'], ['قها', 'Qaha'], ['كفر شكر', 'Kafr Shukr'],
            ]],
            ['PTS', 'بورسعيد', 'Port Said', [
                ['مدينة بورسعيد', 'Port Said City'], ['بورفؤاد', 'Port Fouad'], ['المناخ', 'El Manakh'],
                ['الزهور', 'El Zohour'], ['الشرق', 'El Sharq'], ['الضواحي', 'El Dawahy'],
            ]],
            ['SUZ', 'السويس', 'Suez', [
                ['مدينة السويس', 'Suez City'], ['الأربعين', 'Arbaeen'], ['الجناين', 'Ganayen'],
                ['فيصل', 'Faisal'], ['عتاقة', 'Attaqa'],
            ]],
            ['DAM', 'دمياط', 'Damietta', [
                ['مدينة دمياط', 'Damietta City'], ['دمياط الجديدة', 'New Damietta'], ['رأس البر', 'Ras El Bar'],
                ['فارسكور', 'Faraskur'], ['كفر سعد', 'Kafr Saad'], ['الزرقا', 'Zarqa'],
            ]],
            ['DKH', 'الدقهلية', 'Dakahlia', [
                ['المنصورة', 'Mansoura'], ['طلخا', 'Talkha'], ['ميت غمر', 'Mit Ghamr'],
                ['أجا', 'Aga'], ['بلقاس', 'Belqas'], ['دكرنس', 'Dekernes'], ['السنبلاوين', 'Sinbillawin'],
            ]],
            ['SHR', 'الشرقية', 'Sharqia', [
                ['الزقازيق', 'Zagazig'], ['العاشر من رمضان', '10th of Ramadan'], ['بلبيس', 'Belbeis'],
                ['أبو كبير', 'Abu Kabir'], ['فاقوس', 'Faqous'], ['منيا القمح', 'Minya El Qamh'],
                ['الحسينية', 'Husseiniya'],
            ]],
            ['GHR', 'الغربية', 'Gharbia', [
                ['طنطا', 'Tanta'], ['المحلة الكبرى', 'El Mahalla El Kubra'], ['كفر الزيات', 'Kafr El Zayat'],
                ['زفتى', 'Zefta'], ['سنتا', 'Santa'], ['قطور', 'Qutur'],
            ]],
            ['MNF', 'المنوفية', 'Monufia', [
                ['شبين الكوم', 'Shibin El Kom'], ['منوف', 'Menouf'], ['مدينة السادات', 'Sadat City'],
                ['أشمون', 'Ashmoun'], ['قويسنا', 'Quesna'], ['تلا', 'Tala'],
            ]],
            ['BEH', 'البحيرة', 'Beheira', [
                ['دمنهور', 'Damanhur'], ['كفر الدوار', 'Kafr El Dawwar'], ['رشيد', 'Rashid'],
                ['إدكو', 'Edku'], ['أبو حمص', 'Abu Hummus'], ['حوش عيسى', 'Hosh Isa'],
            ]],
            ['KFS', 'كفر الشيخ', 'Kafr El Sheikh', [
                ['مدينة كفر الشيخ', 'Kafr El Sheikh City'], ['دسوق', 'Desouk'], ['فوة', 'Fuwwah'],
                ['بلطيم', 'Baltim'], ['مطوبس', 'Metoubes'], ['سيدي سالم', 'Sidi Salem'],
            ]],
            ['FYM', 'الفيوم', 'Faiyum', [
                ['مدينة الفيوم', 'Faiyum City'], ['سنورس', 'Sinnuris'], ['طامية', 'Tamiya'],
                ['إطسا', 'Itsa'], ['إبشواي', 'Ibsheway'],
            ]],
            ['BNS', 'بني سويف', 'Beni Suef', [
                ['مدينة بني سويف', 'Beni Suef City'], ['الواسطى', 'El Wasta'], ['ناصر', 'Naser'],
                ['ببا', 'Beba'], ['سمسطا', 'Somasta'], ['الفشن', 'El Fashn'],
            ]],
            ['MNY', 'المنيا', 'Minya', [
                ['مدينة المنيا', 'Minya City'], ['ملوي', 'Mallawi'], ['سمالوط', 'Samalut'],
                ['مغاغة', 'Maghagha'], ['بني مزار', 'Beni Mazar'], ['دير مواس', 'Deir Mawas'],
            ]],
            ['ASY', 'أسيوط', 'Asyut', [
                ['مدينة أسيوط', 'Asyut City'], ['ديروط', 'Dairut'], ['منفلوط', 'Manfalut'],
                ['أبنوب', 'Abnub'], ['القوصية', 'El Ghanayem'], ['ساحل سليم', 'Sahel Selim'],
            ]],
            ['SOH', 'سوهاج', 'Sohag', [
                ['مدينة سوهاج', 'Sohag City'], ['أخميم', 'Akhmim'], ['جرجا', 'Girga'],
                ['طهطا', 'Tahta'], ['البلينا', 'El Balyana'], ['طما', 'Tima'],
            ]],
            ['QNA', 'قنا', 'Qena', [
                ['مدينة قنا', 'Qena City'], ['نقادة', 'Naqada'], ['قوص', 'Qus'],
                ['نجع حمادي', 'Nag Hammadi'], ['دشنا', 'Deshna'], ['أبوتشت', 'Abu Tesht'],
            ]],
            ['LXR', 'الأقصر', 'Luxor', [
                ['مدينة الأقصر', 'Luxor City'], ['إسنا', 'Esna'], ['أرمنت', 'Armant'],
                ['الطود', 'El Toud'], ['البياضية', 'El Bayadeya'],
            ]],
            ['ASN', 'أسوان', 'Aswan', [
                ['مدينة أسوان', 'Aswan City'], ['كوم أمبو', 'Kom Ombo'], ['إدفو', 'Edfu'],
                ['دراو', 'Daraw'], ['نصر النوبة', 'Nasr El Nuba'],
            ]],
            ['RSA', 'البحر الأحمر', 'Red Sea', [
                ['الغردقة', 'Hurghada'], ['سفاجا', 'Safaga'], ['القصير', 'El Quseir'],
                ['مرسى علم', 'Marsa Alam'], ['رأس غارب', 'Ras Ghareb'], ['شلاتين', 'Shalateen'],
            ]],
            ['NVL', 'الوادي الجديد', 'New Valley', [
                ['الخارجة', 'Kharga'], ['الداخلة', 'Dakhla'], ['الفرافرة', 'Farafra'],
                ['بلاط', 'Balat'], ['باريس', 'Paris'],
            ]],
            ['MAT', 'مطروح', 'Matrouh', [
                ['مرسى مطروح', 'Marsa Matrouh'], ['العلمين', 'El Alamein'], ['سيدي براني', 'Sidi Barrani'],
                ['سيوة', 'Siwa'], ['الحمام', 'El Hammam'],
            ]],
            ['NSI', 'شمال سيناء', 'North Sinai', [
                ['العريش', 'Arish'], ['الشيخ زويد', 'Sheikh Zuweid'], ['رفح', 'Rafah'],
                ['بئر العبد', 'Bir al-Abd'], ['نخل', 'Nakhl'],
            ]],
            ['SSI', 'جنوب سيناء', 'South Sinai', [
                ['شرم الشيخ', 'Sharm El Sheikh'], ['دهب', 'Dahab'], ['نويبع', 'Nuweiba'],
                ['طابا', 'Taba'], ['سانت كاترين', 'Saint Catherine'], ['الطور', 'El Tor'],
            ]],
            ['ISM', 'الإسماعيلية', 'Ismailia', [
                ['مدينة الإسماعيلية', 'Ismailia City'], ['فايد', 'Fayed'], ['القنطرة شرق', 'Qantara Sharq'],
                ['القنطرة غرب', 'Qantara Gharb'], ['أبو صلطان', 'Abu Sultan'],
            ]],
        ];
    }
}
