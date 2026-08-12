<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Error Messages
    |--------------------------------------------------------------------------
    |
    | Messages surfaced by custom exceptions' render() methods (see
    | app/Exceptions) - user-facing business-rule errors, not validation
    | messages (those stay in validation.php).
    |
    */

    'category_has_children' => 'التصنيف ده لسه فيه تصنيفات فرعية. انقل الفروع دي لتصنيف تاني أو احذفها الأول.',
    'category_has_products' => 'التصنيف ده لسه متربط بمنتجات. شيل المنتجات من التصنيف ده الأول.',
    'insufficient_stock' => 'الكمية المطلوبة مش متوفرة في المخزون حاليًا.',
    'coupon_limit_reached' => 'الكوبون ده وصل لأقصى عدد استخدام مسموح بيه.',
    'coupon_inactive' => 'الكوبون ده مش شغال حاليًا.',
    'coupon_not_started' => 'الكوبون ده لسه ما بدأش.',
    'coupon_expired' => 'الكوبون ده انتهت صلاحيته.',
    'coupon_min_order_not_met' => 'قيمة الطلب أقل من الحد الأدنى المطلوب لاستخدام الكوبون ده.',
    'coupon_usage_limit_reached' => 'الكوبون ده وصل لأقصى عدد استخدام مسموح بيه.',
    'coupon_user_limit_reached' => 'استخدمت الكوبون ده أقصى عدد مرات مسموح بيه.',
    'coupon_first_order_only' => 'الكوبون ده لأول طلب بس.',
    'coupon_not_applicable' => 'مفيش حاجة في السلة تستاهل الخصم ده.',
    'invalid_order_transition' => 'حالة الطلب مينفعش تتغيّر بالشكل ده.',
    'redirect_loop' => 'حصلت دورة توجيه غير آمنة، تم إيقافها.',
    'attribute_value_in_use' => 'القيمة دي مستخدمة في متغيرات منتجات حاليًا. شيل الارتباط ده الأول.',
    'attribute_is_variant_locked' => 'الخاصية دي مستخدمة في متغيرات منتجات، مينفعش تغيّر إعداد "متغيّر" ليها.',
    'product_missing_translation' => 'لازم الاسم والرابط يكونوا موجودين باللغتين العربي والإنجليزي.',
    'product_missing_description' => 'لازم الوصف الكامل يكون موجود باللغتين العربي والإنجليزي.',
    'product_missing_category' => 'لازم المنتج يكون مرتبط بتصنيف واحد على الأقل.',
    'product_missing_seo' => 'لازم عنوان ووصف SEO يكونوا موجودين باللغتين.',
    'product_missing_variant' => 'لازم المنتج يكون له متغيّر واحد نشط على الأقل (متاحة من Batch 3.2-B).',
    'product_missing_primary_image' => 'لازم المنتج يكون له صورة أساسية (متاحة من Batch 3.2-B).',
    'product_publish_not_allowed' => 'المنتج مينفعش يتنشر لسه — راجع الشروط الناقصة.',
    'attribute_value_must_be_non_variant' => 'القيمة دي بتاعة خاصية متغيّرة (زي المقاس أو اللون) — القيم دي بتتحدد بس عن طريق متغيرات المنتج، مش كخاصية عامة على المنتج نفسه.',

];
