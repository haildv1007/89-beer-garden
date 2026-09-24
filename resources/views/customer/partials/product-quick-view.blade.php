<div class="modal fade product-quick-view" id="productQuickView" tabindex="-1" aria-labelledby="productQuickViewTitle"
    aria-hidden="true" data-more-label="{{ __('customer_ui.read_more') }}"
    data-less-label="{{ __('customer_ui.collapse') }}">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <button class="product-modal-close" type="button" data-bs-dismiss="modal"
                aria-label="{{ __('customer_ui.close') }}">×</button>
            <div class="product-modal-layout">
                <div class="product-modal-gallery" data-product-modal-gallery>
                    <figure class="product-modal-media">
                        <img data-product-modal-image src="" alt="" draggable="false">
                        <video data-product-modal-video controls preload="metadata" hidden></video>
                        <span class="product-modal-status" data-product-modal-status></span>
                        <div class="product-modal-tags" data-product-modal-tags></div>
                        <span class="product-modal-media-count" data-product-modal-media-count hidden>
                            <span data-product-modal-media-count-text></span>
                        </span>
                        <div class="product-modal-thumbnails" data-product-modal-thumbnails
                            aria-label="{{ __('customer_ui.product_media') }}"></div>
                    </figure>
                </div>
                <div class="product-modal-content">
                    <span class="product-category" data-product-modal-category></span>
                    <h2 id="productQuickViewTitle" data-product-modal-name></h2>
                    <div class="product-modal-description-wrap">
                        <p class="product-modal-description" data-product-modal-description></p>
                        <button class="product-modal-description-toggle" type="button"
                            data-product-modal-description-toggle hidden>{{ __('customer_ui.read_more') }}</button>
                    </div>
                    <div class="product-modal-price" data-product-modal-price></div>
                    <form class="product-modal-form js-submit-once" data-add-to-cart method="post"
                        action="{{ route('customer.cart.items.store') }}">
                        @csrf
                        <input data-product-modal-id type="hidden" name="product_id" value="">
                        <div class="product-quantity">
                            <span>{{ __('customer_order.quantity') }}</span>
                            <div class="quantity-stepper">
                                <button type="button" data-quantity-minus aria-label="−">−</button>
                                <input data-product-modal-quantity name="quantity" type="number" min="1" max="1000"
                                    value="1" required>
                                <button type="button" data-quantity-plus aria-label="+">+</button>
                            </div>
                        </div>
                        <button class="btn btn-reservation product-modal-submit"
                            data-product-modal-submit>{{ __('customer_order.add_to_cart') }}</button>
                    </form>
                    <a class="product-modal-detail" data-product-modal-detail href="">
                        {{ __('app.view_details') }} <span aria-hidden="true">→</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
