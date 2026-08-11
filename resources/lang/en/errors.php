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

    'category_has_children' => 'This category still has sub-categories. Move or delete them first.',
    'category_has_products' => 'This category still has products assigned to it. Remove them from this category first.',

];
