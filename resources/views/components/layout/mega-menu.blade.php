@props(['categories'])

{{--
    One shared panel container for every top-level category, not five
    separate absolutely-positioned elements — modules/mega-menu.js swaps
    which [data-mega-panel] is visible instead of juggling five independent
    open/close states. Positioned absolute inside <header> (which is
    `sticky`, itself a positioned element) so top-full lands exactly at the
    header's own bottom edge on desktop, where the mobile rows below it
    collapse to zero height at the md breakpoint.

    Desktop-only: the trigger buttons this panel responds to only exist in
    the md:flex nav row in header.blade.php — mobile has no way to open it,
    so no extra responsive classes are needed here beyond staying [hidden]
    by default.
--}}
<div
    id="mega-menu"
    data-module="mega-menu"
    data-mega-menu
    hidden
    class="absolute inset-x-0 top-full z-dropdown border-t border-line bg-canvas shadow-lg"
>
    @foreach ($categories as $slug => $category)
        <div data-mega-panel="{{ $slug }}" hidden>
            <div class="container grid grid-cols-3 gap-8 py-8">
                <div>
                    <h3 class="text-sm font-semibold text-ink">{{ __('layout.mega_menu.shop_by_type') }}</h3>
                    <ul class="mt-4 space-y-3">
                        @foreach ($category['subcategories'] as $subcategory)
                            <li>
                                <a href="#" class="text-sm text-muted transition-colors duration-150 ease-smooth hover:text-ink motion-reduce:transition-none">
                                    {{ $subcategory }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div>
                    <h3 class="text-sm font-semibold text-ink">{{ __('layout.mega_menu.quick_links_heading') }}</h3>
                    <ul class="mt-4 space-y-3">
                        <li><a href="#" class="text-sm text-muted transition-colors duration-150 ease-smooth hover:text-ink motion-reduce:transition-none">{{ __('layout.mega_menu.new_in') }}</a></li>
                        <li><a href="#" class="text-sm text-muted transition-colors duration-150 ease-smooth hover:text-ink motion-reduce:transition-none">{{ __('layout.mega_menu.best_sellers') }}</a></li>
                        <li><a href="#" class="text-sm text-danger transition-colors duration-150 ease-smooth hover:text-danger-700 motion-reduce:transition-none">{{ __('layout.mega_menu.sale') }}</a></li>
                        <li><a href="#" class="text-sm text-muted transition-colors duration-150 ease-smooth hover:text-ink motion-reduce:transition-none">{{ __('common.view_all') }}</a></li>
                    </ul>
                </div>

                <a href="#" class="group relative flex aspect-video items-end overflow-hidden rounded-xl bg-surface p-5">
                    <div class="absolute inset-0 bg-linear-to-t from-neutral-950/60 to-transparent" aria-hidden="true"></div>
                    <div class="relative">
                        <p class="text-lg font-semibold text-white">{{ __('layout.mega_menu.promo_heading') }}</p>
                        <span class="mt-1 inline-block text-sm text-white/90 underline-offset-4 group-hover:underline">
                            {{ __('layout.mega_menu.promo_cta') }}
                        </span>
                    </div>
                </a>
            </div>
        </div>
    @endforeach
</div>
