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
    'stocktake_no_change' => 'العدّة اللي دخّلتها مطابقة للمخزون الحالي بالظبط — مفيش حاجة تتسجّل.',
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
    'attribute_value_used_in_images' => 'القيمة دي مستخدمة في صور منتجات حاليًا. شيل الارتباط ده الأول.',
    'attribute_is_variant_locked' => 'الخاصية دي مستخدمة في متغيرات منتجات، مينفعش تغيّر إعداد "متغيّر" ليها.',
    'product_missing_translation' => 'لازم الاسم والرابط يكونوا موجودين باللغتين العربي والإنجليزي.',
    'product_missing_description' => 'لازم الوصف الكامل يكون موجود باللغتين العربي والإنجليزي.',
    'product_missing_category' => 'لازم المنتج يكون مرتبط بتصنيف واحد على الأقل.',
    'product_missing_seo' => 'لازم عنوان ووصف SEO يكونوا موجودين باللغتين.',
    'product_missing_variant' => 'لازم المنتج يكون له متغيّر واحد نشط على الأقل.',
    'product_missing_primary_image' => 'لازم المنتج يكون له صورة أساسية.',
    'product_publish_not_allowed' => 'المنتج مينفعش يتنشر لسه — راجع الشروط الناقصة.',
    'attribute_value_must_be_non_variant' => 'القيمة دي بتاعة خاصية متغيّرة (زي المقاس أو اللون) — القيم دي بتتحدد بس عن طريق متغيرات المنتج، مش كخاصية عامة على المنتج نفسه.',
    'attribute_value_must_be_variant' => 'القيمة دي بتاعة خاصية غير متغيّرة — مينفعش تُستخدم كمحور لمتغيرات المنتج.',
    'attribute_value_must_be_color' => 'القيمة دي مش تابعة لخاصية "لون" — مينفعش تُستخدم لربط صورة بلون.',
    'variant_protected_stock' => 'المتغيّر ده عليه مخزون فعلي حاليًا.',
    'variant_protected_reserved' => 'المتغيّر ده عليه حجز نشط حاليًا (في سلة عميل).',
    'variant_protected_movements' => 'المتغيّر ده له سجل حركات مخزون.',
    'variant_protected_sales' => 'المتغيّر ده اتباع قبل كده.',
    'variant_deletion_blocked' => 'مينفعش تشيل المتغيّرات دي — راجع الأسباب لكل واحد منها.',
    'variant_matrix_conflict' => 'حد تاني عدّل بعض المتغيّرات دي قبلك. راجع الصفوف المتعارضة وأعد التحميل.',
    'variant_matrix_limit_exceeded' => 'عدد التوافيق المطلوب (:requested) أكبر من الحد الأقصى المسموح به (:limit).',
    'variant_matrix_inconsistent' => 'حصل تعارض داخلي في مجموعة خصائص بعض المتغيّرات — لم يتم حفظ أي تعديل. حاول تاني، ولو تكررت المشكلة بلّغ الدعم الفني.',
    'product_image_temp_file_missing' => 'الملف المرفوع مش موجود، ممكن يكون انتهت صلاحيته. ارفع الصورة تاني.',
    'product_image_unreadable' => 'الملف ده مش صورة سليمة، جرّب صورة تانية.',
    'product_images_limit_exceeded' => 'وصلت للحد الأقصى لعدد صور المنتج (:limit صورة).',

];
