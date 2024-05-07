(function ($) {
    $(document).ready(function () {
        $('#place_order').click(function () {
            if ($('select[name="billing_country"]').val() === 'BH') {
                console.log('Bahrain');
            }
        });
    });
})(jQuery);
