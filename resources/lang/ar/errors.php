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

];
